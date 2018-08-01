<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatDomainCashOpcode;

class StatDomainCashOpcodeTest extends DurianTestCase
{
    /**
     * 測試新增廳的現金 opcode 統計
     */
    public function testNewStatDomainCashOpcode()
    {
        $at = new \DateTime('2014-07-07 12:00:00');

        $stat = new StatDomainCashOpcode($at, 1, 156, 1013);

        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(1, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(1013, $stat->getOpcode());
        $this->assertEquals(0, $stat->getAmount());
        $this->assertEquals(0, $stat->getCount());

        $at = new \DateTime('2014-07-08 12:00:00');

        $stat->setId(1);
        $stat->setDomain(2);
        $stat->setAmount(100);
        $stat->setAt($at);
        $stat->setCurrency(901);
        $stat->setUserId(5);
        $stat->setOpcode(1014);
        $stat->addCount(50);

        $this->assertEquals(1, $stat->getId());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(100, $stat->getAmount());
        $this->assertEquals($at, $stat->getAt());
        $this->assertEquals(5, $stat->getUserId());
        $this->assertEquals(1014, $stat->getOpcode());
        $this->assertEquals(50, $stat->getCount());
    }
}
