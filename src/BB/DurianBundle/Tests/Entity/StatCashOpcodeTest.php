<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatCashOpcode;

/**
 * 測試現金Opcode統計
 *
 * @author Chuck 2014.10.07
 */
class StatCashOpcodeTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $at = new \DateTime('2013-01-10 12:00:00');
        $stat = new StatCashOpcode($at, 1, 156);

        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(1, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(0, $stat->getAmount());
        $this->assertEquals(0, $stat->getCount());

        $stat->setId(11);
        $stat->setCurrency(951);
        $stat->setDomain(5);
        $stat->setParentId(7);
        $stat->setOpcode(1234);
        $stat->setAmount(789);
        $stat->setCount(500);

        $this->assertEquals(11, $stat->getId());
        $this->assertEquals(951, $stat->getCurrency());
        $this->assertEquals(5, $stat->getDomain());
        $this->assertEquals(7, $stat->getParentId());
        $this->assertEquals(1234, $stat->getOpcode());
        $this->assertEquals(789, $stat->getAmount());
        $this->assertEquals(500, $stat->getCount());

        $stat->addAmount(100);
        $stat->addCount(5);

        $this->assertEquals(889, $stat->getAmount());
        $this->assertEquals(505, $stat->getCount());

        $stat->addCount();
        $this->assertEquals(506, $stat->getCount());
    }
}
