<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\CashFakeTransferEntry;
use BB\DurianBundle\Entity\User;

class CashFakeTransferEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $userId = 11;
        $domain = 2;
        $user = new User();
        $user->setDomain($domain);
        $refl = new \ReflectionClass($user);
        $reflProperty = $refl->getProperty('id');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($user, $userId);

        $currency = 156;
        $cashFake = new CashFake($user, $currency);

        $opCode = 1006;
        $amount = 100;
        $memo = 'This is new memo';
        $refId = 12345678901;
        $entryId = 8;

        $entry = new CashFakeEntry($cashFake, $opCode, $amount, $memo);
        $entry->setRefId($refId);
        $refl = new \ReflectionClass(get_parent_class($entry));
        $reflProperty = $refl->getProperty('id');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($entry, $entryId);

        $time = $entry->getCreatedAt();
        $interval = '+1 minute';
        $time2 = clone $time;
        $time2->modify($interval);

        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $tEntry->setCreatedAt($time2);
        $time2String = $time2->format('YmdHis');
        $tEntry->setAt($time2String);

        $this->assertEquals($entry->getId(), $tEntry->getId());
        $this->assertEquals($user->getId(), $tEntry->getUserId());
        $this->assertEquals($user->getDomain(), $tEntry->getDomain());
        $this->assertEquals($cashFake->getCurrency(), $tEntry->getCurrency());
        $this->assertEquals($entry->getOpcode(), $tEntry->getOpcode());
        $this->assertEquals($entry->getAmount(), $tEntry->getAmount());
        $this->assertEquals($entry->getCreatedAt()->modify($interval), $tEntry->getCreatedAt());
        $this->assertEquals($time2String, $tEntry->getAt());
        $this->assertEquals($entry->getBalance(), $tEntry->getBalance());
        $this->assertEquals($entry->getMemo(), $tEntry->getMemo());
        $this->assertEquals($entry->getRefId(), $tEntry->getRefId());

        $array = $tEntry->toArray();

        $this->assertEquals($entryId, $array['id']);
        $this->assertEquals($userId, $array['user_id']);
        $this->assertEquals($domain, $array['domain']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals($opCode, $array['opcode']);
        $this->assertEquals($time2, new \DateTime($array['created_at']));
        $this->assertEquals($amount, $array['amount']);
        $this->assertEquals($amount, $array['balance']);
        $this->assertEquals($refId, $array['ref_id']);
        $this->assertEquals($memo, $array['memo']);

        $entry = new CashFakeEntry($cashFake, $opCode, $amount, $memo);
        $entry->setRefId(0);

        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $array = $tEntry->toArray();

        $this->assertEquals(0, $tEntry->getRefId());
        $this->assertEquals('', $array['ref_id']);
    }

    /**
     * 測試加入非外部轉帳Opcode
     */
    public function testNewEntryWithoutTransferOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150050046
        );

        $domain = 2;
        $user = new User();
        $user->setDomain($domain);

        $currency = 156;
        $cash = new CashFake($user, $currency);

        $memo = 'This is new memo';
        $refId = 1234567890;

        $entry = new CashFakeEntry($cash, 9899, 100, $memo);
        $entry->setRefId($refId);

        new CashFakeTransferEntry($entry, $domain);
    }
}
