<?php

namespace BB\DurianBundle\Remit;

use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;
use Buzz\Client\Curl;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * 同略雲
 */
class TongLueYun extends ContainerAware implements AutoRemitInterface
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BCM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CNCB', // 中信銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣東發展銀行
        '15' => 'PAB', // 深圳平安銀行
        '16' => 'PSBC', // 中國郵政
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
        $ip = $this->container->getParameter('payment_ip');

        $requestData = ['apikey' => $apiKey];

        $host = $this->container->getParameter('remit_auto_confirm_host');

        $curlParam = [
            'method' => 'POST',
            'uri' => '/authority/system/api/list_order/',
            'ip' => $ip,
            'host' => $host,
            'param' => json_encode($requestData),
        ];

        $this->curlRequest($curlParam);
    }

    /**
     * 啟用自動認款帳號時需檢查設定
     */
    public function checkNewAutoRemitAccount()
    {
        $em = $this->getEntityManager();
        $ip = $this->container->getParameter('payment_ip');
        $host = $this->container->getParameter('remit_auto_confirm_host');
        $this->verifyRemitAccount();
        $remitAccount = $this->remitAccount;

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

        // 取得apiKey
        $criteria = [
            'domain' => $remitAccount->getDomain(),
            'autoRemitId' => $remitAccount->getAutoRemitId(),
        ];
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', $criteria);

        if (!$domainAutoRemit) {
            throw new \RuntimeException('Domain is not supported by AutoConfirm', 150870016);
        }

        $requestData = [
            'apikey' => $domainAutoRemit->getApiKey(),
            'bank_flag' => $this->bankMap[$remitAccount->getBankInfoId()],
            'card_number' => $remitAccount->getAccount(),
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/authority/system/api/query_bankcard/',
            'ip' => $ip,
            'host' => $host,
            'param' => json_encode($requestData),
        ];
        $this->curlRequest($curlParam);
    }

    /**
     * 啟用自動認款帳號時需檢查設定
     */
    public function checkAutoRemitAccount()
    {
        $this->checkNewAutoRemitAccount();
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
        $ip = $this->container->getParameter('payment_ip');
        $host = $this->container->getParameter('remit_auto_confirm_host');
        $this->verifyRemitAccount();
        $remitAccount = $this->remitAccount;

        // 取得銀行對應代碼
        $bankCode = $this->bankMap[$remitAccount->getBankInfoId()];

        // 取得apiKey
        $criteria = [
            'domain' => $remitAccount->getDomain(),
            'autoRemitId' => $remitAccount->getAutoRemitId(),
        ];
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', $criteria);

        if (!$domainAutoRemit) {
            throw new \RuntimeException('Domain is not supported by AutoConfirm', 150870017);
        }

        $requestData = [
            'apikey' => $domainAutoRemit->getApiKey(),
            'order_id' => $orderNumber,
            'bank_flag' => $bankCode,
            'card_login_name' => '',
            'card_number' => $remitAccount->getAccount(),
            'pay_card_number' => '',
            'pay_username' => '',
            'amount' => $payData['amount'],
            'create_time' => time(),
            'comment' => $orderNumber,
        ];

        if ($payData['pay_card_number']) {
            $requestData['pay_card_number'] = $payData['pay_card_number'];
        }

        if ($payData['pay_username']) {
            $requestData['pay_username'] = $payData['pay_username'];
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/authority/system/api/place_order/',
            'ip' => $ip,
            'host' => $host,
            'param' => json_encode($requestData),
        ];
        $result = $this->curlRequest($curlParam);

        if (!isset($result['id'])) {
            throw new \RuntimeException('No auto confirm return parameter specified', 150870018);
        }

        return $result['id'];
    }

    /**
     * 取消自動認款訂單
     *
     * @param RemitEntry $remitEntry
     */
    public function cancelAutoRemitEntry(RemitEntry $remitEntry)
    {
        $em = $this->getEntityManager();
        $ip = $this->container->getParameter('payment_ip');
        $host = $this->container->getParameter('remit_auto_confirm_host');

        // 取得apiKey
        $criteria = [
            'domain' => $remitEntry->getDomain(),
            'autoRemitId' => $remitEntry->getAutoRemitId(),
        ];
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', $criteria);
        if (!$domainAutoRemit) {
            throw new \RuntimeException('Domain is not supported by AutoConfirm', 150870019);
        }

        $remitAutoConfirm = $em->find('BBDurianBundle:RemitAutoConfirm', $remitEntry->getId());

        if (!$remitAutoConfirm) {
            throw new \RuntimeException('No RemitAutoConfirm found', 150870020);
        }

        $requestData = [
            'apikey' => $domainAutoRemit->getApiKey(),
            'id' => $remitAutoConfirm->getAutoConfirmId(),
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/authority/system/api/revoke_order/',
            'ip' => $ip,
            'host' => $host,
            'param' => json_encode($requestData),
        ];
        $this->curlRequest($curlParam);
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
     * @return array
     */
    private function curlRequest($curlParam)
    {
        $logger = $this->container->get('durian.remit_auto_confirm_logger');

        $request = new Request($curlParam['method']);
        $request->setContent($curlParam['param']);
        $request->fromUrl($curlParam['ip'] . $curlParam['uri']);
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

        // 紀錄 log
        $message = [
            'serverIp' => $curlParam['ip'],
            'host' => $curlParam['host'],
            'method' => $curlParam['method'],
            'uri' => $curlParam['uri'],
            'param' => $curlParam['param'],
            'output' => $result,
        ];
        $logger->record($message);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Auto Confirm connection failure', 150870022);
        }

        $retArray = json_decode($result, true);

        if (!isset($retArray['success'])) {
            throw new \RuntimeException('Please confirm auto_remit_account in the platform.', 150870027);
        }

        // 成功時 success 返回 true
        if (!$retArray['success'] && isset($retArray['message'])) {
            $customizeMsg = sprintf(
                "無法完成指定的操作。(自動認款平台錯誤資訊 : %s)\n" .
                "請確認欲執行操作的銀行帳戶已於自動認款平台內正確設置後再重新執行操作。",
                $retArray['message']
            );

            throw new \RuntimeException($customizeMsg, 150870024);
        }

        if (!$retArray['success']) {
            throw new \RuntimeException('Auto Confirm failed', 150870025);
        }

        return $retArray;
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
