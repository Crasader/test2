<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;

class PaymentVendorTest extends DurianTestCase
{
    /**
     * 測試新增修改
     */
    public function testNewAndSetPaymentVendor()
    {
        $paymentMethod = new PaymentMethod('储值卡支付');
        $name = '移动储值卡';
        $paymentVendor = new PaymentVendor($paymentMethod, $name);
        $this->assertEquals($paymentMethod, $paymentVendor->getPaymentMethod());
        $this->assertEquals($name, $paymentVendor->getName());

        $newName = '联通储值卡';
        $paymentVendor->setName($newName);
        $paymentVendor->setId(2);

        $pvArray = $paymentVendor->toArray();
        $this->assertEquals('2', $pvArray['id']);
        $this->assertEquals($newName, $pvArray['name']);
        $this->assertNull($pvArray['payment_method']);
    }
}
