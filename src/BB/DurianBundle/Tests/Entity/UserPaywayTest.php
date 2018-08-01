<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\UserPayway;
use BB\DurianBundle\Entity\User;

/**
 * 測試 UserPayway Entity
 */
class UserPaywayTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User;
        $user->setId(1);

        $payway = new UserPayway($user);
        $this->assertFalse($payway->isCashEnabled());
        $this->assertFalse($payway->isCashFakeEnabled());
        $this->assertFalse($payway->isCreditEnabled());
        $this->assertFalse($payway->isOutsideEnabled());

        $payway->enableCash();
        $payway->enableCashFake();
        $payway->enableCredit();
        $payway->enableOutside();

        $this->assertTrue($payway->isCashEnabled());
        $this->assertTrue($payway->isCashFakeEnabled());
        $this->assertTrue($payway->isCreditEnabled());
        $this->assertTrue($payway->isOutsideEnabled());

        $array = $payway->toArray();
        $this->assertEquals(1, $array['user_id']);
        $this->assertTrue($array['cash']);
        $this->assertTrue($array['cash_fake']);
        $this->assertTrue($array['credit']);
        $this->assertTrue($array['outside']);

        $payway->disableCash();
        $payway->disableCashFake();
        $payway->disableCredit();
        $payway->disableOutside();

        $this->assertFalse($payway->isCashEnabled());
        $this->assertFalse($payway->isCashFakeEnabled());
        $this->assertFalse($payway->isCreditEnabled());
        $this->assertFalse($payway->isOutsideEnabled());

        $array = $payway->toArray();
        $this->assertEquals(1, $array['user_id']);
        $this->assertFalse($array['cash']);
        $this->assertFalse($array['cash_fake']);
        $this->assertFalse($array['credit']);
        $this->assertFalse($array['outside']);
    }
}
