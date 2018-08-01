<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CardCharge;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\CardPaymentGatewayFee;

/**
 * 測試 CardPaymentGatewayFee
 */
class CardPaymentGatewayFeeTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $domain = 2;
        $name = 'name';
        $code = 2;
        $postUrl = '';
        $rate = 1.33;

        $cardCharge = new CardCharge($domain);
        $paymentGateway = new PaymentGateway($code, $name, $postUrl, 1);
        $fee = new CardPaymentGatewayFee($cardCharge, $paymentGateway);

        $this->assertEquals($cardCharge, $fee->getCardCharge());
        $this->assertEquals($paymentGateway, $fee->getPaymentGateway());

        $fee->setRate($rate);

        $array = $fee->toArray();

        $this->assertEquals(0, $array['payment_gateway_id']);
        $this->assertEquals(0, $array['card_charge_id']);
        $this->assertEquals($rate, $array['rate']);
    }
}
