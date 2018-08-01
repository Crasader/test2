<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\PaymentGatewayCurrency;

class PaymentGatewayCurrencyTest extends DurianTestCase
{
    /**
     * 測試支付平台幣別基本功能
     */
    public function testPaymentGatewayCurrencyBasic()
    {
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $pgCurrency = new PaymentGatewayCurrency($paymentGateway, 156); // CNY

        $this->assertEquals(156, $pgCurrency->getCurrency());
        $this->assertEquals($paymentGateway, $pgCurrency->getPaymentGateway());

        $array = $pgCurrency->toArray();
        $this->assertEquals(0, $array['payment_gateway_id']);
        $this->assertEquals('CNY', $array['currency']);
    }
}
