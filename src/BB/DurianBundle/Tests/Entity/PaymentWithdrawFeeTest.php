<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentWithdrawFee;

/**
 * 測試 PaymentWithdrawFee
 */
class PaymentWithdrawFeeTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $id = 99;
        $freePeriod = 100;
        $freeCount = 50;
        $amountMax = 15.99;
        $amountPercent = 0.3;
        $withdrawMax = 20000;
        $withdrawMin = 1;

        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $paymentCharge->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        $entry = new PaymentWithdrawFee($paymentCharge);

        $this->assertEquals($paymentCharge, $entry->getPaymentCharge());

        $entry->setFreePeriod($freePeriod);
        $entry->setFreeCount($freeCount);
        $entry->setAmountMax($amountMax);
        $entry->setAmountPercent($amountPercent);
        $entry->setWithdrawMax($withdrawMax);
        $entry->setWithdrawMin($withdrawMin);
        $entry->setMobileFreePeriod(50);
        $entry->setMobileFreeCount(30);
        $entry->setMobileAmountMax(100.5);
        $entry->setMobileAmountPercent(0.8);
        $entry->setMobileWithdrawMax(10000.3);
        $entry->setMobileWithdrawMin(50.2);
        $entry->setBitcoinFreePeriod(10);
        $entry->setBitcoinFreeCount(5);
        $entry->setBitcoinAmountMax(10.1234);
        $entry->setBitcoinAmountPercent(2);
        $entry->setBitcoinWithdrawMax(1000.1234);
        $entry->setBitcoinWithdrawMin(100.4321);
        $entry->setAccountReplacementTips(true);
        $entry->setAccountTipsInterval(2);

        $array = $entry->toArray();

        $this->assertEquals($id, $array['payment_charge_id']);
        $this->assertEquals($freePeriod, $array['free_period']);
        $this->assertEquals($freeCount, $array['free_count']);
        $this->assertEquals($amountMax, $array['amount_max']);
        $this->assertEquals($amountPercent, $array['amount_percent']);
        $this->assertEquals($withdrawMax, $array['withdraw_max']);
        $this->assertEquals($withdrawMin, $array['withdraw_min']);
        $this->assertEquals(50, $array['mobile_free_period']);
        $this->assertEquals(30, $array['mobile_free_count']);
        $this->assertEquals(100.5, $array['mobile_amount_max']);
        $this->assertEquals(0.8, $array['mobile_amount_percent']);
        $this->assertEquals(10000.3, $array['mobile_withdraw_max']);
        $this->assertEquals(50.2, $array['mobile_withdraw_min']);
        $this->assertEquals(10, $array['bitcoin_free_period']);
        $this->assertEquals(5, $array['bitcoin_free_count']);
        $this->assertEquals(10.1234, $array['bitcoin_amount_max']);
        $this->assertEquals(2, $array['bitcoin_amount_percent']);
        $this->assertEquals(1000.1234, $array['bitcoin_withdraw_max']);
        $this->assertEquals(100.4321, $array['bitcoin_withdraw_min']);
        $this->assertEquals(true, $array['account_replacement_tips']);
        $this->assertEquals(2, $array['account_tips_interval']);
    }
}
