<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatCashOpcodeHK;

class StatCashOpcodeHKTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $at = new \DateTime('2016-01-02 13:00:00');
        $stat = new StatCashOpcodeHK($at, 8, 156);

        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(8, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(0, $stat->getAmount());
        $this->assertEquals(0, $stat->getCount());

        $stat->setId(2);
        $stat->setCurrency(951);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1011);
        $stat->setAmount(10);
        $stat->setCount(5);

        $this->assertEquals(2, $stat->getId());
        $this->assertEquals(951, $stat->getCurrency());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(7, $stat->getParentId());
        $this->assertEquals(1011, $stat->getOpcode());
        $this->assertEquals(10, $stat->getAmount());
        $this->assertEquals(5, $stat->getCount());

        $stat->addAmount(100);
        $stat->addCount(5);

        $this->assertEquals(110, $stat->getAmount());
        $this->assertEquals(10, $stat->getCount());

        $stat->addCount();
        $this->assertEquals(11, $stat->getCount());
    }
}
