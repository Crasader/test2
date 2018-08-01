<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantLevelMethod;

class MerchantLevelMethodTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $paymentMethod = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentMethod')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $paymentMethod->expects($this->any())
            ->method('getId')
            ->willReturn(5);

        $mlm = new MerchantLevelMethod(12, 34, $paymentMethod);
        $mlmArray = $mlm->toArray();

        $this->assertEquals(12, $mlmArray['merchant_id']);
        $this->assertEquals(34, $mlmArray['level_id']);
        $this->assertEquals(5, $mlmArray['payment_method']);
    }

    /**
     * 測試getter
     */
    public function testGetter()
    {
        $paymentMethod = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentMethod')
            ->disableOriginalConstructor()
            ->getMock();

        $mlm = new MerchantLevelMethod(12, 34, $paymentMethod);

        $this->assertEquals(12, $mlm->getMerchantId());
        $this->assertEquals(34, $mlm->getLevelId());
        $this->assertEquals($paymentMethod, $mlm->getPaymentMethod());
    }
}
