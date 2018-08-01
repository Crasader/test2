<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantWithdrawStat;

class MerchantWithdrawStatTest extends DurianTestCase
{
    /**
     * 測試新增次數統計
     */
    public function testBasic()
    {
        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $day = new \DateTime('2012-01-01T00:00:00-0400');

        $stat = new MerchantWithdrawStat($merchantWithdraw, $day, 2);

        $this->assertNull($stat->getId());
        $this->assertEquals($merchantWithdraw, $stat->getMerchantWithdraw());
        $this->assertEquals(20120101000000, $stat->getAt());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(0, $stat->getCount());
        $this->assertEquals(0, $stat->getTotal());

        $result = $stat->toArray();
        $this->assertEquals('2012-01-01T00:00:00+0800', $result['at']);
        $this->assertEquals(2, $result['domain']);
        $this->assertEquals(0, $result['count']);
        $this->assertEquals(0, $result['total']);

        $stat->setCount(1);
        $this->assertEquals(1, $stat->getCount());

        $stat->setTotal(100);
        $this->assertEquals(100, $stat->getTotal());
    }
}
