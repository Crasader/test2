<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\LastLogin;

class LastLoginTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $ip = '192.168.1.1';
        $last = new LastLogin(1, $ip);

        $this->assertEquals(1, $last->getUserId());
        $this->assertEquals(0, $last->getLoginLogId());
        $this->assertEquals($ip, $last->getIp());
        $this->assertEquals(0, $last->getErrNum());
        $this->assertNull($last->getAt());

        $ip = '192.168.2.3';
        $at = new \DateTime('2016-01-02 13:00:00');
        $last->setIp($ip);
        $last->addErrNum();
        $last->setAt($at);
        $last->setLoginLogId(123);

        $array = $last->toArray();

        $this->assertEquals(1, $array['user_id']);
        $this->assertEquals(123, $array['login_log_id']);
        $this->assertEquals($ip, $array['ip']);
        $this->assertEquals(1, $array['err_num']);
        $this->assertEquals($at->format(\DateTime::ISO8601), $array['at']);

        $last->zeroErrNum();
        $this->assertEquals(0, $last->getErrNum());
    }
}
