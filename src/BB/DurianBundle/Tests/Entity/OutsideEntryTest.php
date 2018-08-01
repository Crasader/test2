<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\OutsideEntry;

/**
 * 測試外接額度明細
 */
class OutsideEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $id = 5431123;
        $userId = 1;
        $currency = 156;
        $createdAt = 20130909210921;
        $opcode = 11111;
        $amount = 22;
        $memo = '33';
        $balance = 10;
        $refId = 999;
        $group = 1;

        $entry = new outsideEntry();

        $entry->setId($id);
        $entry->setCreatedAt($createdAt);
        $entry->setUserId($userId);
        $entry->setCurrency($currency);
        $entry->setOpcode($opcode);
        $entry->setAmount($amount);
        $entry->setMemo($memo);
        $entry->setBalance($balance);
        $entry->setRefId($refId);
        $entry->setGroup($group);

        $this->assertEquals($id, $entry->getId());
        $this->assertEquals($createdAt, $entry->getCreatedAt());
        $this->assertEquals($userId, $entry->getUserId());
        $this->assertEquals($currency, $entry->getCurrency());
        $this->assertEquals($opcode, $entry->getOpcode());
        $this->assertEquals($amount, $entry->getAmount());
        $this->assertEquals($memo, $entry->getMemo());
        $this->assertEquals($balance, $entry->getBalance());
        $this->assertEquals($refId, $entry->getRefId());
        $this->assertEquals($group, $entry->getGroup());

        $array = $entry->toArray();
        $this->assertEquals($id, $array['id']);
        $this->assertEquals($userId, $array['user_id']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals($opcode, $array['opcode']);
        $this->assertEquals($amount, $array['amount']);
        $this->assertEquals($balance, $array['balance']);
        $this->assertEquals($refId, $array['ref_id']);
        $this->assertEquals((new \DateTime($createdAt))->format(\DateTime::ISO8601), $array['created_at']);
        $this->assertEquals($memo, $array['memo']);
        $this->assertEquals($group, $array['group']);

        $array = $entry->setRefId(0)->toArray();
        $this->assertEquals('', $array['ref_id']);
    }
}
