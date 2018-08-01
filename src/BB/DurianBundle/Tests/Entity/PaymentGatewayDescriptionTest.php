<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\PaymentGatewayDescription;

class PaymentGatewayDescriptionTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $paymentGatewayDescription = new PaymentGatewayDescription($paymentGateway, 'number', 'test123');
        $pgdArray = $paymentGatewayDescription->toArray();

        $this->assertEquals('number', $pgdArray['name']);
        $this->assertEquals('test123', $pgdArray['value']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $paymentGatewayDescription = new PaymentGatewayDescription($paymentGateway, 'number', 'test123');

        $this->assertNull($paymentGatewayDescription->getId());
        $this->assertNull($paymentGatewayDescription->getPaymentGatewayId());
        $this->assertEquals('number', $paymentGatewayDescription->getName());
        $this->assertEquals('test123', $paymentGatewayDescription->getValue());

        $paymentGatewayDescription->setValue('hello');
        $this->assertEquals('hello', $paymentGatewayDescription->getValue());
    }
}
