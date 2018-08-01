<?php

namespace BB\DurianBundle\Remit;

use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;
use Buzz\Client\Curl;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * BB自動認款2.0
 */
class BBv2 extends ContainerAware implements AutoRemitInterface
{
    /**
     * @var Curl
     */
    private $client;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var RemitAccount
     */
    private $remitAccount = null;

    /**
     * 自動認款平台支援的銀行對應編號
     *
     * @var array
     */
    private $bankMap = [
        '3' => 'ABOC', // 農業銀行
    ];

    /**
     * @param Curl $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * 設定自動認款帳號
     *
     * @param RemitAccount $remitAccount
     */
    public function setRemitAccount(RemitAccount $remitAccount)
    {
        $this->remitAccount = $remitAccount;
    }

    /**
     * 檢查是否設定自動認款帳號
     */
    public function verifyRemitAccount()
    {
        if (!$this->remitAccount) {
            throw new \RuntimeException('No auto RemitAccount found', 150870014);
        }
    }

    /**
     * 檢查自動認款 API KEY 是否合法
     *
     * @param string $apiKey
     */
    public function checkApiKey($apiKey)
    {
    }

    /**
     * 啟用自動認款帳號時需檢查設定
     */
    public function checkNewAutoRemitAccount()
    {
        $em = $this->getEntityManager();
        $autoRemitChecker = $this->container->get('durian.auto_remit_checker');
        $ip = $this->container->getParameter('payment_ip');
        $host = $this->container->getParameter('auto_confirm_bbv2_host');
        $this->verifyRemitAccount();
        $remitAccount = $this->remitAccount;
        $domain = $remitAccount->getDomain();

        // 取得銀行對應代碼
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', $remitAccount->getAutoRemitId());
        $bankInfos = $autoRemit->getBankInfo();

        $support = false;
        foreach ($bankInfos as $bankInfo) {
            if ($remitAccount->getBankInfoId() == $bankInfo->getId()) {
                $support = true;
                break;
            }
        }

        if (!$support) {
            throw new \RuntimeException('BankInfo is not supported by AutoRemitBankInfo', 150870015);
        }

        // 檢查廳是否支持該自動認款平台
        $domainUser = $em->find('BBDurianBundle:User', $domain);
        $domainAutoRemit = $autoRemitChecker->getPermission($domain, $autoRemit, $domainUser);

        if (!$domainAutoRemit || !$domainAutoRemit->getEnable()) {
            throw new \RuntimeException('Domain is not supported by AutoConfirm', 150870016);
        }

        $bankCode = $this->bankMap[$remitAccount->getBankInfoId()];
        $account = $remitAccount->getAccount();

        $curlParam = [
            'method' => 'GET',
            'uri' => "/api/v2_1/banks/{$bankCode}/accounts/{$account}/order_placeable",
            'ip' => $ip,
            'host' => $host,
            'param' => '',
            'header' => [
                'Authorization' => $this->getAuthorization(),
                'Company-Code' => $this->getDomainLoginCode($domain),
            ],
        ];
        return $this->curlRequest($curlParam);
    }

    /**
     * 啟用自動認款帳號時需檢查設定
     */
    public function checkAutoRemitAccount()
    {
        $result = $this->checkNewAutoRemitAccount();

        if (!isset($result['order_placeable'])) {
            throw new \RuntimeException('No auto confirm return parameter specified', 150870018);
        }

        if ($result['order_placeable'] !== true) {
            throw new \RuntimeException('Please confirm auto_remit_account in the platform.', 150870027);
        }
    }

    /**
     * 提交自動認款訂單，並回傳自動認款 id
     *
     * @param integer $orderNumber 訂單號
     * @param array $payData 付款人相關資料
     *     string pay_card_number 付款卡卡號
     *     string pay_username 付款人姓名
     *     string amount 付款金額
     * @return integer
     */
    public function submitAutoRemitEntry($orderNumber, $payData)
    {
        $em = $this->getEntityManager();
        $autoRemitChecker = $this->container->get('durian.auto_remit_checker');
        $ip = $this->container->getParameter('payment_ip');
        $host = $this->container->getParameter('auto_confirm_bbv2_host');
        $this->verifyRemitAccount();
        $remitAccount = $this->remitAccount;
        $domain = $remitAccount->getDomain();

        // 取得銀行對應代碼
        $bankCode = $this->bankMap[$remitAccount->getBankInfoId()];

        // 檢查廳是否支付該自動認款平台
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', $remitAccount->getAutoRemitId());
        $domainUser = $em->find('BBDurianBundle:User', $domain);
        $domainAutoRemit = $autoRemitChecker->getPermission($domain, $autoRemit, $domainUser);

        if (!$domainAutoRemit || !$domainAutoRemit->getEnable()) {
            throw new \RuntimeException('Domain is not supported by AutoConfirm', 150870017);
        }

        $createTime = (new \DateTime())->format(\DateTime::ISO8601);
        $requestData = [
            'identification_id' => $orderNumber,
            'identification_username' => $payData['username'],
            'destination_bank_swift_code' => $bankCode,
            'destination_bank_account_number' => $remitAccount->getAccount(),
            'source_bank_account_number' => '',
            'source_bank_account_name' => '',
            'amount' => $payData['amount'],
            'available_since' => $createTime,
        ];

        if ($payData['pay_card_number']) {
            $requestData['source_bank_account_number'] = $payData['pay_card_number'];
        }

        if ($payData['pay_username']) {
            $requestData['source_bank_account_name'] = $payData['pay_username'];
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/v2_1/orders',
            'ip' => $ip,
            'host' => $host,
            'param' => json_encode($requestData),
            'header' => [
                'Content-Type' => 'application/json',
                'Authorization' => $this->getAuthorization(),
                'Company-Code' => $this->getDomainLoginCode($domain),
            ],
        ];
        $result = $this->curlRequest($curlParam);

        if (!isset($result['hash_id'])) {
            throw new \RuntimeException('No auto confirm return parameter specified', 150870018);
        }

        return $result['hash_id'];
    }

    /**
     * 取消自動認款訂單
     *
     * @param RemitEntry $remitEntry
     */
    public function cancelAutoRemitEntry(RemitEntry $remitEntry)
    {
        $em = $this->getEntityManager();
        $autoRemitChecker = $this->container->get('durian.auto_remit_checker');
        $ip = $this->container->getParameter('payment_ip');
        $host = $this->container->getParameter('auto_confirm_bbv2_host');
        $domain = $remitEntry->getDomain();

        // 檢查廳是否支付該自動認款平台
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', $remitEntry->getAutoRemitId());
        $domainUser = $em->find('BBDurianBundle:User', $domain);
        $domainAutoRemit = $autoRemitChecker->getPermission($domain, $autoRemit, $domainUser);

        if (!$domainAutoRemit || !$domainAutoRemit->getEnable()) {
            throw new \RuntimeException('Domain is not supported by AutoConfirm', 150870019);
        }

        $remitAutoConfirm = $em->find('BBDurianBundle:RemitAutoConfirm', $remitEntry->getId());
        if (!$remitAutoConfirm) {
            throw new \RuntimeException('No RemitAutoConfirm found', 150870020);
        }

        $hashId = $remitAutoConfirm->getAutoConfirmId();

        $curlParam = [
            'method' => 'PUT',
            'uri' => "/api/v2_1/orders/{$hashId}/revocation",
            'ip' => $ip,
            'host' => $host,
            'param' => '',
            'header' => [
                'Authorization' => $this->getAuthorization(),
                'Company-Code' => $this->getDomainLoginCode($domain),
            ],
        ];
        $result = $this->curlRequest($curlParam);

        if (!isset($result['status'])) {
            throw new \RuntimeException('No auto confirm return parameter specified', 150870018);
        }

        if ($result['status'] !== 'revoked') {
            throw new \RuntimeException('Auto Confirm failed', 150870025);
        }
    }

    /**
     * 發送 curl 請求
     *
     * @param array $curlParam 參數說明如下
     *     string method 提交方式
     *     string uri
     *     string ip
     *     string host
     *     string param 提交的參數
     *     array header
     * @return array
     */
    private function curlRequest($curlParam)
    {
        $logger = $this->container->get('durian.remit_auto_confirm_logger');

        $request = new Request($curlParam['method']);
        $request->setContent($curlParam['param']);
        $request->fromUrl($curlParam['ip'] . $curlParam['uri']);
        $request->setHeaders($curlParam['header']);
        $request->addHeader("Host: {$curlParam['host']}");

        $client = new Curl();
        $response = new Response();

        if ($this->client) {
            $client = $this->client;
        }

        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $client->setOption(CURLOPT_TIMEOUT, 10);

        try {
            $client->send($request, $response);
        } catch (\Exception $e) {
            throw new \RuntimeException('Auto Confirm connection failure', 150870021);
        }

        if ($this->response) {
            $response = $this->response;
        }

        $result = trim($response->getContent());

        if (isset($curlParam['header']['Authorization'])) {
            $curlParam['header']['Authorization'] = '******';
        }

        // 紀錄 log
        $message = [
            'serverIp' => $curlParam['ip'],
            'host' => $curlParam['host'],
            'method' => $curlParam['method'],
            'uri' => $curlParam['uri'],
            'header' => $curlParam['header'],
            'param' => $curlParam['param'],
            'output' => $result,
        ];
        $logger->record($message);

        if ($response->getStatusCode() == 404) {
            throw new \RuntimeException('AutoRemitAccount not exist', 150870023);
        }

        if ($response->getStatusCode() != 200 && !$response->getContent()) {
            throw new \RuntimeException('Auto Confirm connection failure', 150870022);
        }

        $retArray = json_decode($result, true);

        if (isset($retArray['error_code'])) {
            $customizeMsg = sprintf(
                "無法完成指定的操作。(自動認款平台錯誤資訊 : %s)\n" .
                "請確認欲執行操作的銀行帳戶已於自動認款平台內正確設置後再重新執行操作。",
                $retArray['error_code']
            );

            throw new \RuntimeException($customizeMsg, 150870024);
        }

        return $retArray;
    }

    /**
     * 回傳API驗證信息
     *
     * @return string
     */
    private function getAuthorization()
    {
        return $this->container->getParameter('auto_confirm_bbv2_token');
    }

    /**
     * 回傳廳主後置碼
     *
     * @param $domain 廳主
     * @return string
     */
    private function getDomainLoginCode($domain)
    {
        $em = $this->getEntityManager();
        $domainConfig = $em->find('BBDurianBundle:DomainConfig', $domain);

        return $domainConfig->getLoginCode();
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->container->get('doctrine')->getManager($name);
    }
}
