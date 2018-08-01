<?php

namespace BB\DurianBundle\Remit;

use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;
use Buzz\Client\Curl;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerAware;

class Helper extends ContainerAware
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var Curl
     */
    private $client;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

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
     * 檢查公司入款帳號是否達到限額
     *
     * @param RemitAccount $remitAccount
     * @return boolean
     */
    public function isBankLimitReached(RemitAccount $remitAccount)
    {
        // banklimit 0 代表沒有限額
        if ($remitAccount->getBankLimit() == 0) {
            return false;
        }

        // 取得目前統計資料
        $remitAccountStat = $this->getEntityManager()
            ->getRepository('BBDurianBundle:RemitAccountStat')
            ->getCurrentStat($remitAccount);

        if (!$remitAccountStat) {
            return false;
        }

        // 檢查收入是否達到限額
        if ($remitAccountStat->getIncome() >= $remitAccount->getBankLimit()) {
            return true;
        }

        return false;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }
}
