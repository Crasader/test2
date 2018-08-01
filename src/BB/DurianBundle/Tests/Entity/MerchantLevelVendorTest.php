<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantLevelVendor;

class MerchantLevelVendorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $paymentVendor->expects($this->any())
            ->method('getId')
            ->willReturn(5);

        $mlv = new MerchantLevelVendor(12, 34, $paymentVendor);
        $mlvArray = $mlv->toArray();

        $this->assertEquals(12, $mlvArray['merchant_id']);
        $this->assertEquals(34, $mlvArray['level_id']);
        $this->assertEquals(5, $mlvArray['payment_vendor']);
    }

    /**
     * 測試getter
     */
    public function testGetter()
    {
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();

        $mlv = new MerchantLevelVendor(12, 34, $paymentVendor);

        $this->assertEquals(12, $mlv->getMerchantId());
        $this->assertEquals(34, $mlv->getLevelId());
        $this->assertEquals($paymentVendor, $mlv->getPaymentVendor());
    }
}
