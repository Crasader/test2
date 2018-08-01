<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\EmailVerifyCode;
use BB\DurianBundle\Entity\User;

class EmailVerifyCodeTest extends DurianTestCase
{
    /**
     * 基本測試
     */
    public function testBasic()
    {
        $expireAt = new \DateTime('2015-3-27 17:00:00');
        $code = hash('sha256', 'secret key');
        $emailVerifyCode = new EmailVerifyCode(1, $code, $expireAt);

        $this->assertEquals(1, $emailVerifyCode->getUserId());
        $this->assertEquals($code, $emailVerifyCode->getCode());
        $this->assertEquals($expireAt, $emailVerifyCode->getExpireAt());
    }
}
