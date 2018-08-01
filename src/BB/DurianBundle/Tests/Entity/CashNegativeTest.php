<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashNegative;

class CashNegativeTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $neg = new CashNegative(1, 156);
        $neg->setCashId(8);
        $neg->setBalance(-2);
        $neg->setVersion(3);
        $neg->setEntryId(4);
        $neg->setOpcode(1234);
        $neg->setAt('20161118010203');
        $neg->setAmount(-100);
        $neg->setEntryBalance(-1);
        $neg->setRefId(6);
        $neg->setMemo('test-memo');

        $this->assertEquals(1, $neg->getUserId());
        $this->assertEquals(156, $neg->getCurrency());
        $this->assertEquals(8, $neg->getCashId());
        $this->assertEquals(-2, $neg->getBalance());
        $this->assertEquals(3, $neg->getVersion());
        $this->assertEquals(4, $neg->getEntryId());
        $this->assertEquals(1234, $neg->getOpcode());
        $this->assertEquals('20161118010203', $neg->getAt());
        $this->assertEquals(-100, $neg->getAmount());
        $this->assertEquals(-1, $neg->getEntryBalance());
        $this->assertEquals(6, $neg->getRefId());
        $this->assertEquals('test-memo', $neg->getMemo());
        $this->assertTrue($neg->isNegative());

        $neg->setRefId(0);
        $ret = $neg->toArray();
        $this->assertEquals(8, $ret['cash']['id']);
        $this->assertEquals(1, $ret['cash']['user_id']);
        $this->assertEquals('CNY', $ret['cash']['currency']);
        $this->assertEquals(-2, $ret['cash']['balance']);
        $this->assertEquals(4, $ret['entry']['id']);
        $this->assertEquals(8, $ret['entry']['cash_id']);
        $this->assertEquals(1, $ret['entry']['user_id']);
        $this->assertEquals('CNY', $ret['entry']['currency']);
        $this->assertEquals(1234, $ret['entry']['opcode']);
        $this->assertEquals('2016-11-18T01:02:03+0800', $ret['entry']['created_at']);
        $this->assertEquals(-100, $ret['entry']['amount']);
        $this->assertEquals(-1, $ret['entry']['balance']);
        $this->assertEmpty($ret['entry']['ref_id']);
        $this->assertEquals('test-memo', $ret['entry']['memo']);
    }
}
