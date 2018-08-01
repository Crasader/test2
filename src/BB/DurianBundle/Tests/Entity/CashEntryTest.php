<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\User;

class CashEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $cash = new Cash($user, 156); // CNY

        $memo = 'This is new memo';

        // 1001:DEPOSIT
        $entry = new CashEntry($cash, 1001, 100, $memo);
        $refId = 12345678901;
        $entry->setRefId($refId);

        $time = new \DateTime('now');
        $entry->setCreatedAt($time);
        $entry->setAt($time->format('YmdHis'));

        $this->assertEquals($cash->getId(), $entry->getCashId());
        $this->assertEquals($cash->getUser()->getId(), $entry->getUserId());
        $this->assertEquals($cash->getCurrency(), $entry->getCurrency());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(100, $entry->getAmount());
        $this->assertEquals($time, $entry->getCreatedAt());
        $this->assertEquals($time->format('YmdHis'), $entry->getAt());
        $this->assertEquals(100, $entry->getBalance());
        $this->assertEquals($memo, $entry->getMemo());
        $this->assertEquals($refId, $entry->getRefId());
        $this->assertEquals(0, $entry->getCashVersion());

        $entry->setMemo('This is new memo2');
        $this->assertEquals('This is new memo2', $entry->getMemo());

        $time->add(new \DateInterval('PT1M'));
        $entry->setCreatedAt($time);
        $entry->setAt($time->format('YmdHis'));

        $this->assertEquals($time, $entry->getCreatedAt());
        $this->assertEquals($time->format('YmdHis'), $entry->getAt());

        $entry->setCashVersion(1);
        $this->assertEquals(1, $entry->getCashVersion());

        $entry->setRefId(0);
        $entry->setUserId(99);
        $entry->setCurrency(901);
        $cashEntryArray = $entry->toArray();

        $this->assertEquals(0, $cashEntryArray['id']);
        $this->assertEquals(0, $cashEntryArray['cash_id']);
        $this->assertEquals(99, $cashEntryArray['user_id']);
        $this->assertEquals('TWD', $cashEntryArray['currency']);
        $this->assertEquals(1001, $cashEntryArray['opcode']);
        $this->assertEquals($time, new \DateTime($cashEntryArray['created_at']));
        $this->assertEquals(100, $cashEntryArray['amount']);
        $this->assertEquals('This is new memo2', $cashEntryArray['memo']);
        $this->assertEquals('', $cashEntryArray['ref_id']);
        $this->assertEquals(100, $cashEntryArray['balance']);
    }

    /**
     * 測試允許額度為負的情況
     */
    public function testAllowNegativeAmount()
    {
        $user = new User();
        $cash = new Cash($user, 156); // CNY

        // 10005:UNCANCEL
        $entry = new CashEntry($cash, 10005, -1);
        $this->assertEquals(-1, $entry->getAmount());

        // 10003:RE_PAYOFF
        $entry = new CashEntry($cash, 10003, -1);
        $this->assertEquals(-1, $entry->getAmount());

        // 9899:MIGRATION-SK
        $entry = new CashEntry($cash, 9899, -1);
        $this->assertEquals(-1, $entry->getAmount());

        // 9897:MIGRATION-BALL
        $entry = new CashEntry($cash, 9897, -1);
        $this->assertEquals(-1, $entry->getAmount());

        // 9898:MIGRATION-BB
        $entry = new CashEntry($cash, 9898, -1);
        $this->assertEquals(-1, $entry->getAmount());
    }

    /**
     * 測試入款額度為負的情況
     */
    public function testDepositNegativeAmount()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150040046);

        $user = new User();
        $cash = new Cash($user, 156); // CNY

        // 1001:DEPOSIT
        $entry = new CashEntry($cash, 1001, -1);
    }

    /**
     * 測試出款額度為負的情況
     */
    public function testWithdrawNegativeAmount()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150040046);

        $user = new User();
        $cash = new Cash($user, 156); // CNY

        // 1002:WITHDRAWAL
        $entry = new CashEntry($cash, 1002, -1);
    }

    /**
     * 測試帳號轉移時balance不可超過DB設定的最大位數
     */
    public function testMIGRATIONCanNotExceedMaximumSetting()
    {
        $this->setExpectedException(
            'RangeException',
            'The balance exceeds the MAX amount',
            150040044
        );

        $user = new User();
        $cash = new Cash($user, 156); // CNY

        // 9899:MIGRATION-SK
        $entry = new CashEntry($cash, 9899, 1000000000001);
    }

    /**
     * 測試額度的amount跟balance皆為負時要跳exception
     */
    public function testAmountBalanceAreNegative()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150040046);

        $user = new User();
        $cash = new Cash($user, 156); // CNY

        // amount:- balance:- no special opcode=> illegal
        $entry = new CashEntry($cash, 1001, -100);
    }
}
