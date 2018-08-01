<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\User;

class CashFakeEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $cashFake = new CashFake($user, 156); // CNY

        $memo = 'This is new memo';

        // 1001:DEPOSIT
        $entry = new CashFakeEntry($cashFake, 1001, 100, $memo);
        $refId = 12345678901;
        $entry->setRefId($refId);

        $time = new \DateTime('now');
        $entry->setCreatedAt($time);

        $this->assertEquals($cashFake->getId(), $entry->getCashFakeId());
        $this->assertEquals($cashFake->getUser()->getId(), $entry->getUserId());
        $this->assertEquals($cashFake->getCurrency(), $entry->getCurrency());
        $this->assertEquals($time, $entry->getCreatedAt());
        $this->assertEquals($memo, $entry->getMemo());
        $this->assertEquals($refId, $entry->getRefId());
        $this->assertEquals(0, $entry->getCashFakeVersion());

        $entry->setMemo('This is new memo2');

        $time->add(new \DateInterval('PT1M'));
        $entry->setCreatedAt($time);
        $this->assertEquals($time, $entry->getCreatedAt());

        $entry->setCashFakeVersion(1);
        $this->assertEquals(1, $entry->getCashFakeVersion());

        $entry->setRefId(0);

        $array = $entry->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertEquals(0, $array['cash_fake_id']);
        $this->assertEquals(0, $array['user_id']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals(1001, $array['opcode']);
        $this->assertEquals($time, new \DateTime($array['created_at']));
        $this->assertEquals(100, $array['amount']);
        $this->assertEquals(100, $array['balance']);
        $this->assertEquals('', $array['ref_id']);
        $this->assertEquals('This is new memo2', $array['memo']);
    }

    /**
     * 測試允許額度為負的情況
     */
    public function testAllowNegativeAmount()
    {
        $user = new User();
        $cashFake = new CashFake($user, 156); // CNY

        // 10005:UNCANCEL
        $entry = new CashFakeEntry($cashFake, 10005, -1);
        $this->assertEquals(-1, $entry->getAmount());

        // 10003:RE_PAYOFF
        $entry = new CashFakeEntry($cashFake, 10003, -1);
        $this->assertEquals(-1, $entry->getAmount());

        // 9899:MIGRATION-SK
        $entry = new CashFakeEntry($cashFake, 9899, -1);
        $this->assertEquals(-1, $entry->getAmount());

        // 9897:MIGRATION-BALL
        $entry = new CashFakeEntry($cashFake, 9897, -1);
        $this->assertEquals(-1, $entry->getAmount());

        // 9898:MIGRATION-BB
        $entry = new CashFakeEntry($cashFake, 9898, -1);
        $this->assertEquals(-1, $entry->getAmount());
    }

    /**
     * 測試入款額度為負的情況
     */
    public function testDepositNegativeAmount()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150050031);

        $user = new User();
        $cashFake = new CashFake($user, 156); // CNY

        // 1001:DEPOSIT
        $entry = new CashFakeEntry($cashFake, 1001, -1);
    }

    /**
     * 測試出款額度為負的情況
     */
    public function testWithdrawNegativeAmount()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150050031);

        $user = new User();
        $cashFake = new CashFake($user, 156); // CNY

        // 1002:WITHDRAWAL
        $entry = new CashFakeEntry($cashFake, 1002, -1);
    }

    /**
     * 測試帳號轉移時balance不可超過設定的最大位數
     */
    public function testMIGRATIONCanNotExceedMaximumSetting()
    {
        $this->setExpectedException(
            'RangeException',
            'The balance exceeds the MAX amount',
            150050030
        );

        $user = new User();
        $cashFake = new CashFake($user, 156); // CNY

        // 9899:MIGRATION-SK
        $entry = new CashFakeEntry($cashFake, 9899, 1000000000001);
    }

    /**
     * 測試轉移額度為負的情況
     */
    public function testTransferNegativeAmount()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150050031);

        $user = new User();
        $cashFake = new CashFake($user, 156); // CNY

        // 1003 TRANSFER
        $entry = new CashFakeEntry($cashFake, 1003, -1);
    }

    /**
     * 測試額度的amount跟balance皆為負時要跳exception
     */
    public function testAmountBalanceAreNegative()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150050031);

        $user = new User();
        $cashFake = new CashFake($user, 156); // CNY

        // amount:- balance:- no special opcode=> illegal
        $entry = new CashFakeEntry($cashFake, 1001, -100);
    }
}
