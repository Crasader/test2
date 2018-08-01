<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\StatDomainDepositAmount;

class StatDomainDepositAmountTest extends DurianTestCase
{
    /**
     * 測試基本功能
     */
    public function testBasic()
    {
        $at = new \DateTime('2014-07-07 12:00:00');

        $stat = new StatDomainDepositAmount(6, $at->format('Ymd'));

        $this->assertEquals(6, $stat->getDomain());
        $this->assertEquals('20140707', $stat->getAt());
        $this->assertEquals(0, $stat->getAmount());

        $at2 = new \DateTime('2014-07-08 12:00:00');

        $stat->setDomain(2);
        $stat->setAt($at2->format('Ymd'));
        $stat->setAmount(100);

        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals('20140708', $stat->getAt());
        $this->assertEquals(100, $stat->getAmount());
    }
}
