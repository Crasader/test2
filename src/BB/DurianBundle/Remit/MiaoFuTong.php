<?php

namespace BB\DurianBundle\Remit;

use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * 秒付通
 */
class MiaoFuTong extends ContainerAware implements AutoRemitInterface
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
        $this->verifyRemitAccount();
        $remitAccount = $this->remitAccount;
        $domain = $remitAccount->getDomain();

        // 檢查對應銀行
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

        // 檢查廳的開關
        $domainUser = $em->find('BBDurianBundle:User', $domain);

        $domainAutoRemit = $autoRemitChecker->getPermission($domain, $autoRemit, $domainUser);

        if (!$domainAutoRemit || !$domainAutoRemit->getEnable()) {
            throw new \RuntimeException('Domain is not supported by AutoConfirm', 150870016);
        }
    }

    /**
     * 啟用自動認款帳號時需檢查設定
     */
    public function checkAutoRemitAccount()
    {
        $this->checkNewAutoRemitAccount();
    }

    /**
     * 提交自動認款訂單
     *
     * @param string $orderNumber 訂單號
     * @param array $payData 訂單相關資料
     */
    public function submitAutoRemitEntry($orderNumber, $payData)
    {
    }

    /**
     * 取消自動認款訂單
     *
     * @param RemitEntry $remitEntry 自動認款訂單
     */
    public function cancelAutoRemitEntry(RemitEntry $remitEntry)
    {
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
