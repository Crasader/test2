<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentMethod;

class PaymentMethodTest extends DurianTestCase
{
    /**
     * 測試新增修改
     */
    public function testNewAndEditPaymentMethod()
    {
        $name = '人民币借记卡';

        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMethod = new PaymentMethod($name);
        $this->assertEquals($name, $paymentMethod->getName());
        $this->assertTrue($paymentMethod->isWeb());
        $this->assertFalse($paymentMethod->isMobile());

        $newName = '信用卡支付';
        $paymentMethod->setName($newName);

        $pmArray = $paymentMethod->toArray();
        $this->assertEquals($newName, $pmArray['name']);

        $paymentMethod->addVendor($paymentVendor);
        $this->assertCount(1, $paymentMethod->getVendors());

        $paymentMethod->removeVendor($paymentVendor);
        $this->assertCount(0, $paymentMethod->getVendors());
    }
}
