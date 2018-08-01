<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatCashDepositWithdraw;

/**
 * 測試現金出入款統計
 *
 * @author Sweet 2014.10.30
 */
class StatCashDepositWithdrawTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $at = new \DateTime('2013-01-10 12:00:00');
        $stat = new StatCashDepositWithdraw($at, 8, 156);

        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(8, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(0, $stat->getDepositAmount());
        $this->assertEquals(0, $stat->getDepositCount());
        $this->assertEquals(0, $stat->getWithdrawAmount());
        $this->assertEquals(0, $stat->getWithdrawCount());
        $this->assertEquals(0, $stat->getDepositWithdrawAmount());
        $this->assertEquals(0, $stat->getDepositWithdrawCount());

        $stat->setId(11);
        $stat->setCurrency(156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setDepositAmount(1000);
        $stat->setDepositCount(5);
        $stat->setWithdrawAmount(400);
        $stat->setWithdrawCount(2);
        $stat->setDepositWithdrawAmount(1400);
        $stat->setDepositWithdrawCount(7);

        $this->assertEquals(11, $stat->getId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(7, $stat->getParentId());
        $this->assertEquals(1000, $stat->getDepositAmount());
        $this->assertEquals(5, $stat->getDepositCount());
        $this->assertEquals(400, $stat->getWithdrawAmount());
        $this->assertEquals(2, $stat->getWithdrawCount());
        $this->assertEquals(1400, $stat->getDepositWithdrawAmount());
        $this->assertEquals(7, $stat->getDepositWithdrawCount());

        $stat->addDepositAmount(1000);
        $stat->addDepositCount(5);
        $stat->addWithdrawAmount(400);
        $stat->addWithdrawCount(2);
        $stat->addDepositWithdrawAmount(1400);
        $stat->addDepositWithdrawCount(7);

        $this->assertEquals(2000, $stat->getDepositAmount());
        $this->assertEquals(10, $stat->getDepositCount());
        $this->assertEquals(800, $stat->getWithdrawAmount());
        $this->assertEquals(4, $stat->getWithdrawCount());
        $this->assertEquals(2800, $stat->getDepositWithdrawAmount());
        $this->assertEquals(14, $stat->getDepositWithdrawCount());

        $stat->addDepositCount();
        $stat->addWithdrawCount();
        $stat->addDepositWithdrawCount();

        $this->assertEquals(11, $stat->getDepositCount());
        $this->assertEquals(5, $stat->getWithdrawCount());
        $this->assertEquals(15, $stat->getDepositWithdrawCount());
    }
}
