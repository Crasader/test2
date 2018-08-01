<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\OutsideTrans;

/**
 * 測試外接額度交易紀錄
 */
class OutsideTransTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $id = 5431123;
        $userId = 1;
        $currency = 156;
        $createdAt = '2013-09-09 21:09:21';
        $opcode = 11111;
        $amount = 22;
        $memo = '33';
        $refId = 999;
        $group = 1;

        $entry = new outsideTrans();

        $entry->setId($id);
        $entry->setCreatedAt($createdAt);
        $entry->setUserId($userId);
        $entry->setCurrency($currency);
        $entry->setOpcode($opcode);
        $entry->setAmount($amount);
        $entry->setMemo($memo);
        $entry->setRefId($refId);
        $entry->setGroup($group);

        $this->assertEquals($id, $entry->getId());
        $this->assertEquals($createdAt, $entry->getCreatedAt());
        $this->assertEquals($userId, $entry->getUserId());
        $this->assertEquals($currency, $entry->getCurrency());
        $this->assertEquals($opcode, $entry->getOpcode());
        $this->assertEquals($amount, $entry->getAmount());
        $this->assertEquals($memo, $entry->getMemo());
        $this->assertEquals($refId, $entry->getRefId());
        $this->assertEquals($group, $entry->getGroup());
        $this->assertFalse($entry->isChecked());
        $this->assertNull($entry->getCheckedAt());

        $array = $entry->toArray();
        $this->assertEquals($id, $array['id']);
        $this->assertEquals($userId, $array['user_id']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals($opcode, $array['opcode']);
        $this->assertEquals($amount, $array['amount']);
        $this->assertEquals($refId, $array['ref_id']);
        $this->assertEquals((new \DateTime($createdAt))->format(\DateTime::ISO8601), $array['created_at']);
        $this->assertEquals($memo, $array['memo']);
        $this->assertEquals($group, $array['group']);
        $this->assertFalse($array['checked']);
        $this->assertNull($array['checked_at']);

        $array = $entry->setRefId(0)->toArray();
        $this->assertEquals('', $array['ref_id']);
    }
}
