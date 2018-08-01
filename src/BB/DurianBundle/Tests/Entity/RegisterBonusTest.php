<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RegisterBonus;

class RegisterBonusTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $registerBonus = new RegisterBonus($user);

        $this->assertEquals($registerBonus->getUserid(), $user->getId());
        $this->assertEquals(0, $registerBonus->getAmount());
        $this->assertEquals(0, $registerBonus->getMultiply());
        $this->assertTrue($registerBonus->isRefundCommision());

        $registerBonus->setAmount(10);
        $registerBonus->setMultiply(5);
        $registerBonus->setRefundCommision(0);

        $array = $registerBonus->toArray();

        $this->assertEquals(10, $array['amount']);
        $this->assertEquals(5, $array['multiply']);
        $this->assertFalse($array['refund_commision']);
    }
}