<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\PaymentGatewayFee;
use BB\DurianBundle\Entity\CashDepositEntry;

/**
 * 測試 PaymentGatewayFeeTest
 */
class PaymentGatewayFeeTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $domain = 1;
        $name = 'name';
        $preset = true;
        $code = 2;
        $name2 = 'name2';
        $postUrl = '';
        $rate = 1.33;
        $withdrawRate = 0.75;

        $paymentCharge = new PaymentCharge(CashDepositEntry::PAYWAY_CASH, $domain, $name, $preset);
        $paymentGateway = new PaymentGateway($code, $name2, $postUrl, 1);
        $entry = new PaymentGatewayFee($paymentCharge, $paymentGateway);

        $this->assertEquals($paymentCharge, $entry->getPaymentCharge());
        $this->assertEquals($paymentGateway, $entry->getPaymentGateway());

        $entry->setRate($rate);
        $entry->setWithdrawRate($withdrawRate);

        $array = $entry->toArray();

        $this->assertEquals(0, $array['payment_gateway_id']);
        $this->assertEquals(0, $array['payment_charge_id']);
        $this->assertEquals($rate, $array['rate']);
        $this->assertEquals($withdrawRate, $array['withdraw_rate']);

        $this->assertEquals(0.75, $entry->getWithdrawRate());
    }
}
