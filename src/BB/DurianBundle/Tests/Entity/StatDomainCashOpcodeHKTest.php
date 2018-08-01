<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatDomainCashOpcodeHK;

class StatDomainCashOpcodeHKTest extends DurianTestCase
{
    /**
     * 測試基本功能
     */
    public function testBasic()
    {
        $at = new \DateTime('2016-01-02 13:00:00');

        $stat = new StatDomainCashOpcodeHK($at, 8, 156, 1013);

        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(8, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(1013, $stat->getOpcode());
        $this->assertEquals(0, $stat->getAmount());
        $this->assertEquals(0, $stat->getCount());

        $at = new \DateTime('2016-01-22 01:00:00');

        $stat->setId(2);
        $stat->setDomain(2);
        $stat->setAmount(100);
        $stat->setAt($at);
        $stat->setCurrency(901);
        $stat->setUserId(7);
        $stat->setOpcode(1011);
        $stat->addCount(45);

        $this->assertEquals(2, $stat->getId());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(100, $stat->getAmount());
        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(7, $stat->getUserId());
        $this->assertEquals(1011, $stat->getOpcode());
        $this->assertEquals(45, $stat->getCount());
    }
}
