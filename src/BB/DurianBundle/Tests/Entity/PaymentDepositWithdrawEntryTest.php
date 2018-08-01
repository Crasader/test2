<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\PaymentDepositWithdrawEntry;
use BB\DurianBundle\Entity\User;

class PaymentDepositWithdrawEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $cash = new Cash($user, 156);

        // 1039 DEPOSIT-ONLINE-IN 線上存款
        $cashEntry = new CashEntry($cash, 1039, 100, 'memo');
        $paymentDepositWithdrawEntry = new PaymentDepositWithdrawEntry($cashEntry, 2, 'haha');
        $pdweArray = $paymentDepositWithdrawEntry->toArray();

        $at = new \DateTime($cashEntry->getAt());
        $this->assertEquals(0, $pdweArray['id']);
        $this->assertEquals($at->format(\DateTime::ISO8601), $pdweArray['at']);
        $this->assertEquals('', $pdweArray['merchant_id']);
        $this->assertEquals('', $pdweArray['remit_account_id']);
        $this->assertEquals(2, $pdweArray['domain']);
        $this->assertEquals(0, $pdweArray['user_id']);
        $this->assertEquals('', $pdweArray['ref_id']);
        $this->assertEquals('CNY', $pdweArray['currency']);
        $this->assertEquals(1039, $pdweArray['opcode']);
        $this->assertEquals(100, $pdweArray['amount']);
        $this->assertEquals(100, $pdweArray['balance']);
        $this->assertEquals('memo', $pdweArray['memo']);
        $this->assertEquals('haha', $pdweArray['operator']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $user = new User();
        $cash = new Cash($user, 156);

        // 1039 DEPOSIT-ONLINE-IN 線上存款
        $cashEntry = new CashEntry($cash, 1039, 100, 'memo');
        $cashEntry->setRefId(1234567890);

        $domain = 2;
        $operator = 'haha';

        $paymentDepositWithdrawEntry = new PaymentDepositWithdrawEntry($cashEntry, $domain, $operator);

        $this->assertEquals($cashEntry->getId(), $paymentDepositWithdrawEntry->getId());
        $this->assertEquals(new \DateTime($cashEntry->getAt()), $paymentDepositWithdrawEntry->getAt());
        $this->assertEquals(0, $paymentDepositWithdrawEntry->getMerchantId());
        $this->assertEquals(0, $paymentDepositWithdrawEntry->getRemitAccountId());
        $this->assertEquals($domain, $paymentDepositWithdrawEntry->getDomain());
        $this->assertEquals($cashEntry->getUserId(), $paymentDepositWithdrawEntry->getUserId());
        $this->assertEquals($cashEntry->getRefId(), $paymentDepositWithdrawEntry->getRefId());
        $this->assertEquals($cashEntry->getCurrency(), $paymentDepositWithdrawEntry->getCurrency());
        $this->assertEquals($cashEntry->getOpcode(), $paymentDepositWithdrawEntry->getOpcode());
        $this->assertEquals($cashEntry->getAmount(), $paymentDepositWithdrawEntry->getAmount());
        $this->assertEquals($cashEntry->getBalance(), $paymentDepositWithdrawEntry->getBalance());
        $this->assertEquals($cashEntry->getMemo(), $paymentDepositWithdrawEntry->getMemo());
        $this->assertEquals($operator, $paymentDepositWithdrawEntry->getOperator());

        $time = new \DateTime('now');
        $time->add(new \DateInterval('PT1M'));
        $paymentDepositWithdrawEntry->setAt($time->format('YmdHis'));
        $this->assertEquals($time, $paymentDepositWithdrawEntry->getAt());

        $paymentDepositWithdrawEntry->setMerchantId(88888);
        $this->assertEquals(88888, $paymentDepositWithdrawEntry->getMerchantId());

        $paymentDepositWithdrawEntry->setRemitAccountId(12345);
        $this->assertEquals(12345, $paymentDepositWithdrawEntry->getRemitAccountId());
    }

    /**
     * 測試加入不合法Opcode
     */
    public function testNewEntryWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150040058
        );

        $user = new User();
        $cash = new Cash($user, 156);

        // 9899 MIGRATION 從SK舊系統轉移
        $entry = new CashEntry($cash, 9899, 100, 'memo');

        new PaymentDepositWithdrawEntry($entry, 2);
    }
}
