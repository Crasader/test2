<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentGatewayRandomFloatVendor;

class PaymentGatewayRandomFloatVendorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $paymentGatewayRandomFloatVendor = new PaymentGatewayRandomFloatVendor(1, 2);
        $pgrfvArray = $paymentGatewayRandomFloatVendor->toArray();

        $this->assertEquals(1, $pgrfvArray['payment_gateway_id']);
        $this->assertEquals(2, $pgrfvArray['payment_vendor_id']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $paymentGatewayRandomFloatVendor = new PaymentGatewayRandomFloatVendor(1, 2);

        $this->assertNull($paymentGatewayRandomFloatVendor->getId());
        $this->assertEquals(1, $paymentGatewayRandomFloatVendor->getPaymentGatewayId());
        $this->assertEquals(2, $paymentGatewayRandomFloatVendor->getPaymentVendorId());
    }
}
