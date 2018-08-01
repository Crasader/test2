<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\UserCreatedPerIp;

class UserCreatedPerIpTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $clientIp = '127.0.0.1';

        $time = new \DateTime(date('Y-m-d H:00:00'));

        $stat = new UserCreatedPerIp($clientIp, $time, 2);
        $statArray = $stat->toArray();

        $this->assertNull($stat->getId());
        $this->assertEquals(0, $stat->getCount());
        $this->assertEquals(ip2long($clientIp), $stat->getIp());
        $this->assertEquals($time->format('YmdH\0000'), $stat->getAt());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals($clientIp, $statArray['ip']);
    }
}
