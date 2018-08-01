<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\DomainTotalTest;

class DomainTotalTestTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $totalTest = new DomainTotalTest(100);

        $this->assertEquals(100, $totalTest->getDomain());
        $this->assertEquals(0, $totalTest->getTotalTest());

        $totalTest->addTotalTest(5);
        $this->assertEquals(5, $totalTest->getTotalTest());

        $totalTest->setTotalTest(10);
        $this->assertEquals(10, $totalTest->getTotalTest());

        $now = new \DateTime('now');

        $this->assertFalse($totalTest->isRemoved());
        $totalTest->remove();
        $this->assertTrue($totalTest->isRemoved());

        $totalTest->setAt($now);
        $this->assertEquals($now, $totalTest->getAt());

        $array = $totalTest->toArray();
        $this->assertEquals(100, $array['domain']);
        $this->assertTrue($array['removed']);
        $this->assertEquals(10, $array['total_test']);
        $this->assertEquals($now->format(\DateTime::ISO8601), $array['at']);
    }
}
