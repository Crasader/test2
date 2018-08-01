<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\PaymentGatewayBindIp;

class PaymentGatewayBindIpTest extends DurianTestCase
{
    /**
     * 基本測試
     */
    public function testBasic()
    {
        $paymentGateway = new PaymentGateway('BBPay', 'BBPay', '', 1);

        $ip = '123.123.123.123';
        $pgbi = new PaymentGatewayBindIp($paymentGateway, $ip);

        $this->assertNull($pgbi->getId());
        $this->assertEquals($paymentGateway, $pgbi->getPaymentGateway());
        $this->assertEquals($ip, $pgbi->getIp());

        $data = [
            'id' => $pgbi->getId(),
            'payment_gateway_id' => $paymentGateway->getId(),
            'ip' => $ip
        ];

        $this->assertEquals($data, $pgbi->toArray());
    }
}
