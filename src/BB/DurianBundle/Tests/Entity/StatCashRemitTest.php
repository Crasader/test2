<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatCashRemit;

/**
 * 測試現金匯款優惠統計
 *
 * @author Sweet 2014.11.14
 */
class StatCashRemitTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $at = new \DateTime('2013-01-10 12:00:00');
        $stat = new StatCashRemit($at, 8, 156);

        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(8, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(0, $stat->getOfferRemitAmount());
        $this->assertEquals(0, $stat->getOfferRemitCount());
        $this->assertEquals(0, $stat->getOfferCompanyRemitAmount());
        $this->assertEquals(0, $stat->getOfferCompanyRemitCount());
        $this->assertEquals(0, $stat->getRemitAmount());
        $this->assertEquals(0, $stat->getRemitCount());

        $stat->setId(11);
        $stat->setCurrency(156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferRemitAmount(1000);
        $stat->setOfferRemitCount(5);
        $stat->setOfferCompanyRemitAmount(600);
        $stat->setOfferCompanyRemitCount(3);
        $stat->setRemitAmount(1600);
        $stat->setRemitCount(8);

        $this->assertEquals(11, $stat->getId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(7, $stat->getParentId());
        $this->assertEquals(1000, $stat->getOfferRemitAmount());
        $this->assertEquals(5, $stat->getOfferRemitCount());
        $this->assertEquals(600, $stat->getOfferCompanyRemitAmount());
        $this->assertEquals(3, $stat->getOfferCompanyRemitCount());
        $this->assertEquals(1600, $stat->getRemitAmount());
        $this->assertEquals(8, $stat->getRemitCount());

        $stat->addOfferRemitAmount(1000);
        $stat->addOfferRemitCount(5);
        $stat->addOfferCompanyRemitAmount(600);
        $stat->addOfferCompanyRemitCount(3);
        $stat->addRemitAmount(1600);
        $stat->addRemitCount(8);

        $this->assertEquals(2000, $stat->getOfferRemitAmount());
        $this->assertEquals(10, $stat->getOfferRemitCount());
        $this->assertEquals(1200, $stat->getOfferCompanyRemitAmount());
        $this->assertEquals(6, $stat->getOfferCompanyRemitCount());
        $this->assertEquals(3200, $stat->getRemitAmount());
        $this->assertEquals(16, $stat->getRemitCount());

        $stat->addOfferRemitCount();
        $stat->addOfferCompanyRemitCount();
        $stat->addRemitCount();

        $this->assertEquals(11, $stat->getOfferRemitCount());
        $this->assertEquals(7, $stat->getOfferCompanyRemitCount());
        $this->assertEquals(17, $stat->getRemitCount());
    }
}
