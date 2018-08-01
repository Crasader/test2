<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantStat;

class MerchantStatTest extends DurianTestCase
{

    /**
     * 測試新增次數統計
     */
    public function testBasic()
    {
        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
                ->disableOriginalConstructor()
                ->getMock();

        $day = new \DateTime('2012-01-01T00:00:00-0400');

        $stat = new MerchantStat($merchant, $day, 2);

        $this->assertNull($stat->getId());
        $this->assertEquals($merchant, $stat->getMerchant());
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
