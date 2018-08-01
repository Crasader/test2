<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantCardStat;

class MerchantCardStatTest extends DurianTestCase
{
    /**
     * 測試新增租卡商家統計
     */
    public function testBasic()
    {
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();

        $domain = 2;
        $day = new \DateTime('2012-01-01T00:00:00+0800');
        $stat = new MerchantCardStat($merchantCard, $day, $domain);

        $this->assertNull($stat->getId());
        $this->assertEquals($merchantCard, $stat->getMerchantCard());
        $this->assertEquals(20120101000000, $stat->getAt());
        $this->assertEquals($domain, $stat->getDomain());
        $this->assertEquals(0, $stat->getCount());
        $this->assertEquals(0, $stat->getTotal());

        $result = $stat->toArray();
        $this->assertEquals('2012-01-01T00:00:00+0800', $result['at']);
        $this->assertEquals($domain, $result['domain']);
        $this->assertEquals(0, $result['count']);
        $this->assertEquals(0, $result['total']);
    }
}
