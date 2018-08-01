<?php

namespace BB\DurianBundle\Remit;

use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Symfony\Component\DependencyInjection\ContainerAware;

class AutoRemitMaker extends ContainerAware
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
     * 檢查自動認款 API KEY 是否合法
     *
     * @param string $label
     * @param string $apiKey
     */
    public function checkApiKey($label, $apiKey)
    {
        $obj = $this->getAutoRemitObjectByLabel($label);

        return $obj->checkApiKey($apiKey);
    }

    /**
     * 啟用自動認款帳號時需檢查設定
     *
     * @param RemitAccount $remitAccount
     */
    public function checkAutoRemitAccount(RemitAccount $remitAccount)
    {
        $obj = $this->getAutoRemitObjectByRemitAccount($remitAccount);

        return $obj->checkAutoRemitAccount();
    }

    /**
     * 新增自動認款帳號時需檢查設定
     *
     * @param RemitAccount $remitAccount
     */
    public function checkNewAutoRemitAccount(RemitAccount $remitAccount)
    {
        $obj = $this->getAutoRemitObjectByRemitAccount($remitAccount);
        $obj->checkNewAutoRemitAccount();
    }

    /**
     * 提交自動認款訂單
     *
     * @param RemitAccount $remitAccount
     * @param integer $orderNumber
     * @param array $payData
     */
    public function submitAutoRemitEntry(RemitAccount $remitAccount, $orderNumber, $payData)
    {
        $obj = $this->getAutoRemitObjectByRemitAccount($remitAccount);

        return $obj->submitAutoRemitEntry($orderNumber, $payData);
    }

    /**
     * 取消自動認款訂單
     *
     * @param RemitAccount $remitAccount
     * @param RemitEntry $remitEntry
     */
    public function cancelAutoRemitEntry(RemitAccount $remitAccount, RemitEntry $remitEntry)
    {
        $obj = $this->getAutoRemitByRemitEntry($remitAccount, $remitEntry);

        return $obj->cancelAutoRemitEntry($remitEntry);
    }

    /**
     * 依檔名取得自動認款平台物件
     *
     * @param string $label
     * @return AutoRemitInterface
     */
    private function getAutoRemitObjectByLabel($label)
    {
        $path = '\BB\DurianBundle\Remit\\';
        $fullObjectPath = $path . $label;

        $obj = new $fullObjectPath();
        $obj->setClient($this->client);
        $obj->setResponse($this->response);
        $obj->setContainer($this->container);

        return $obj;
    }

    /**
     * 依自動認款帳號取得自動認款平台物件
     *
     * @param RemitAccount $remitAccount
     * @return AutoRemitInterface
     */
    private function getAutoRemitObjectByRemitAccount(RemitAccount $remitAccount)
    {
        $em = $this->getEntityManager();
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', $remitAccount->getAutoRemitId());

        $obj = $this->getAutoRemitObjectByLabel($autoRemit->getLabel());
        $obj->setRemitAccount($remitAccount);

        return $obj;
    }

    /**
     * 依入款明細取得自動認款平台物件
     *
     * @param RemitAccount $remitAccount
     * @param RemitEntry $remitEntry
     * @return AutoRemitInterface
     */
    private function getAutoRemitByRemitEntry(RemitAccount $remitAccount, RemitEntry $remitEntry)
    {
        $em = $this->getEntityManager();
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', $remitEntry->getAutoRemitId());

        $obj = $this->getAutoRemitObjectByLabel($autoRemit->getLabel());
        $obj->setRemitAccount($remitAccount);

        return $obj;
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
