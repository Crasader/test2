<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatCashOffer;

/**
 * 測試現金優惠統計
 *
 * @author Sweet 2014.11.14
 */
class StatCashOfferTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $at = new \DateTime('2013-01-10 12:00:00');
        $stat = new StatCashOffer($at, 8, 156);

        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(8, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(0, $stat->getOfferDepositAmount());
        $this->assertEquals(0, $stat->getOfferDepositCount());
        $this->assertEquals(0, $stat->getOfferBackCommissionAmount());
        $this->assertEquals(0, $stat->getOfferBackCommissionCount());
        $this->assertEquals(0, $stat->getOfferCompanyDepositAmount());
        $this->assertEquals(0, $stat->getOfferCompanyDepositCount());
        $this->assertEquals(0, $stat->getOfferOnlineDepositAmount());
        $this->assertEquals(0, $stat->getOfferOnlineDepositCount());
        $this->assertEquals(0, $stat->getOfferActiveAmount());
        $this->assertEquals(0, $stat->getOfferActiveCount());
        $this->assertEquals(0, $stat->getOfferRegisterAmount());
        $this->assertEquals(0, $stat->getOfferRegisterCount());
        $this->assertEquals(0, $stat->getOfferAmount());
        $this->assertEquals(0, $stat->getOfferCount());

        $stat->setId(11);
        $stat->setCurrency(156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferDepositAmount(1000);
        $stat->setOfferDepositCount(5);
        $stat->setOfferBackCommissionAmount(800);
        $stat->setOfferBackCommissionCount(4);
        $stat->setOfferCompanyDepositAmount(600);
        $stat->setOfferCompanyDepositCount(3);
        $stat->setOfferOnlineDepositAmount(400);
        $stat->setOfferOnlineDepositCount(2);
        $stat->setOfferActiveAmount(200);
        $stat->setOfferActiveCount(1);
        $stat->setOfferRegisterAmount(100);
        $stat->setOfferRegisterCount(2);
        $stat->setOfferAmount(3200);
        $stat->setOfferCount(17);

        $this->assertEquals(11, $stat->getId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(7, $stat->getParentId());
        $this->assertEquals(1000, $stat->getOfferDepositAmount());
        $this->assertEquals(5, $stat->getOfferDepositCount());
        $this->assertEquals(800, $stat->getOfferBackCommissionAmount());
        $this->assertEquals(4, $stat->getOfferBackCommissionCount());
        $this->assertEquals(600, $stat->getOfferCompanyDepositAmount());
        $this->assertEquals(3, $stat->getOfferCompanyDepositCount());
        $this->assertEquals(400, $stat->getOfferOnlineDepositAmount());
        $this->assertEquals(2, $stat->getOfferOnlineDepositCount());
        $this->assertEquals(200, $stat->getOfferActiveAmount());
        $this->assertEquals(1, $stat->getOfferActiveCount());
        $this->assertEquals(100, $stat->getOfferRegisterAmount());
        $this->assertEquals(2, $stat->getOfferRegisterCount());
        $this->assertEquals(3200, $stat->getOfferAmount());
        $this->assertEquals(17, $stat->getOfferCount());

        $stat->addOfferDepositAmount(1000);
        $stat->addOfferDepositCount(5);
        $stat->addOfferBackCommissionAmount(800);
        $stat->addOfferBackCommissionCount(4);
        $stat->addOfferCompanyDepositAmount(600);
        $stat->addOfferCompanyDepositCount(3);
        $stat->addOfferOnlineDepositAmount(400);
        $stat->addOfferOnlineDepositCount(2);
        $stat->addOfferActiveAmount(200);
        $stat->addOfferActiveCount(1);
        $stat->addOfferRegisterAmount(100);
        $stat->addOfferRegisterCount(2);
        $stat->addOfferAmount(3200);
        $stat->addOfferCount(17);

        $this->assertEquals(2000, $stat->getOfferDepositAmount());
        $this->assertEquals(10, $stat->getOfferDepositCount());
        $this->assertEquals(1600, $stat->getOfferBackCommissionAmount());
        $this->assertEquals(8, $stat->getOfferBackCommissionCount());
        $this->assertEquals(1200, $stat->getOfferCompanyDepositAmount());
        $this->assertEquals(6, $stat->getOfferCompanyDepositCount());
        $this->assertEquals(800, $stat->getOfferOnlineDepositAmount());
        $this->assertEquals(4, $stat->getOfferOnlineDepositCount());
        $this->assertEquals(400, $stat->getOfferActiveAmount());
        $this->assertEquals(2, $stat->getOfferActiveCount());
        $this->assertEquals(200, $stat->getOfferRegisterAmount());
        $this->assertEquals(4, $stat->getOfferRegisterCount());
        $this->assertEquals(6400, $stat->getOfferAmount());
        $this->assertEquals(34, $stat->getOfferCount());

        $stat->addOfferDepositCount();
        $stat->addOfferBackCommissionCount();
        $stat->addOfferCompanyDepositCount();
        $stat->addOfferOnlineDepositCount();
        $stat->addOfferActiveCount();
        $stat->addOfferRegisterCount();
        $stat->addOfferCount();

        $this->assertEquals(11, $stat->getOfferDepositCount());
        $this->assertEquals(9, $stat->getOfferBackCommissionCount());
        $this->assertEquals(7, $stat->getOfferCompanyDepositCount());
        $this->assertEquals(5, $stat->getOfferOnlineDepositCount());
        $this->assertEquals(3, $stat->getOfferActiveCount());
        $this->assertEquals(5, $stat->getOfferRegisterCount());
        $this->assertEquals(35, $stat->getOfferCount());
    }
}
