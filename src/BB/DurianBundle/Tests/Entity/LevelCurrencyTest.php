<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Level;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\LevelCurrency;

class LevelCurrencyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $level = new Level($user,'A0001', '未分層', 1);

        $payway = CashDepositEntry::PAYWAY_CASH;
        $domain = 6;
        $preset = 1;

        $paymentCharge = new PaymentCharge($payway, $domain, 'aaaaaa', $preset);

        $levelCurrency = new LevelCurrency($level, 156);

        $this->assertEquals($level->getId(), $levelCurrency->getLevelId());
        $this->assertEquals('156', $levelCurrency->getCurrency());
        $this->assertNull($levelCurrency->getPaymentCharge());
        $this->assertEquals(0, $levelCurrency->getUserCount());

        $levelCurrency->setPaymentCharge($paymentCharge);
        $levelCurrency->setUserCount(2);

        $array = $levelCurrency->toArray();

        $this->assertEquals($level->getId(), $array['level_id']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals($paymentCharge->getId(), $array['payment_charge_id']);
        $this->assertEquals(2, $array['user_count']);
    }
}
