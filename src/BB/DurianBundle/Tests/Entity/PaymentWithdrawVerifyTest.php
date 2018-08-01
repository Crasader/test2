<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentWithdrawVerify;

/**
 * 測試 PaymentWithdrawVerify
 */
class PaymentWithdrawVerifyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $id = 99;
        $needVerify = true;
        $verifyTime = 20140609173000;
        $verifyAmount = 500;

        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $paymentCharge->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        $entry = new PaymentWithdrawVerify($paymentCharge);

        $this->assertEquals($id, $entry->getPaymentCharge()->getId());
        $this->assertEquals($paymentCharge, $entry->getPaymentCharge());

        $entry->setNeedVerify($needVerify);
        $entry->setVerifyTime($verifyTime);
        $entry->setVerifyAmount($verifyAmount);

        $array = $entry->toArray();

        $this->assertEquals($id, $array['payment_charge_id']);
        $this->assertTrue($array['need_verify']);
        $this->assertEquals($verifyTime, $array['verify_time']);
        $this->assertEquals($verifyAmount, $array['verify_amount']);
    }
}
