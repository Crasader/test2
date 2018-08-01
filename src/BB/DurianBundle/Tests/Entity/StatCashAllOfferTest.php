<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatCashAllOffer;

/**
 * 測試匯款、返點、現金匯款優惠總計
 *
 * @author Sweet 2014.11.14
 */
class StatCashAllOfferTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $at = new \DateTime('2013-01-10 12:00:00');
        $stat = new StatCashAllOffer($at, 8, 156);

        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(8, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(0, $stat->getOfferRebateRemitAmount());
        $this->assertEquals(0, $stat->getOfferRebateRemitCount());

        $stat->setId(11);
        $stat->setCurrency(156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferRebateRemitAmount(1000);
        $stat->setOfferRebateRemitCount(5);

        $this->assertEquals(11, $stat->getId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(7, $stat->getParentId());
        $this->assertEquals(1000, $stat->getOfferRebateRemitAmount());
        $this->assertEquals(5, $stat->getOfferRebateRemitCount());

        $stat->addOfferRebateRemitAmount(1000);
        $stat->addOfferRebateRemitCount(5);

        $this->assertEquals(2000, $stat->getOfferRebateRemitAmount());
        $this->assertEquals(10, $stat->getOfferRebateRemitCount());

        $stat->addOfferRebateRemitCount();

        $this->assertEquals(11, $stat->getOfferRebateRemitCount());
    }
}
