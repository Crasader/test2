<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BitcoinWithdrawEntry;

class BitcoinWithdrawEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $data = [
            'id' => 1,
            'user_id' => 2,
            'domain' => 3,
            'level_id' => 4,
            'currency' => 840,
            'amount' => 1000,
            'bitcoin_amount' => 0.02222,
            'rate' => 6.671,
            'bitcoin_rate' => 0.00001111,
            'rate_difference' => 0.00002222,
            'audit_fee' => 15,
            'audit_charge' => 10,
            'deduction' => 5,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address',
            'note' => 'note test',
        ];
        $entry = new BitcoinWithdrawEntry($data);

        $this->assertEquals(1, $entry->getId());
        $this->assertEquals(2, $entry->getUserId());
        $this->assertEquals(3, $entry->getDomain());
        $this->assertEquals(4, $entry->getLevelId());
        $this->assertNotNull($entry->getAt());
        $this->assertEquals(0, $entry->getAmountEntryId());
        $this->assertEquals(0, $entry->getPreviousId());
        $this->assertEquals(840, $entry->getCurrency());
        $this->assertEquals(1000, $entry->getAmount());
        $this->assertEquals(0.02222, $entry->getBitcoinAmount());
        $this->assertEquals(6.671, $entry->getRate());
        $this->assertEquals(0.00001111, $entry->getBitcoinRate());
        $this->assertEquals(0.00002222, $entry->getRateDifference());
        $this->assertEquals(15, $entry->getAuditFee());
        $this->assertEquals(10, $entry->getAuditCharge());
        $this->assertEquals(5, $entry->getDeduction());
        $this->assertEquals(970, $entry->getRealAmount());
        $this->assertEquals('127.0.0.1', $entry->getIp());
        $this->assertEquals('', $entry->getOperator());
        $this->assertEquals('address', $entry->getWithdrawAddress());
        $this->assertEquals('', $entry->getRefId());
        $this->assertEquals('', $entry->getMemo());
        $this->assertEquals('note test', $entry->getNote());

        $this->assertTrue($entry->isProcess());
        $this->assertFalse($entry->isConfirm());
        $this->assertFalse($entry->isCancel());
        $this->assertFalse($entry->isLocked());
        $this->assertFalse($entry->isManual());
        $this->assertFalse($entry->isFirst());
        $this->assertFalse($entry->isDetailModified());
        $this->assertFalse($entry->isControl());

        $entry->setMemo('這是測試');
        $this->assertEquals('這是測試', $entry->getMemo());

        $entry->setPreviousId(555);
        $this->assertEquals(555, $entry->getPreviousId());
        $entry->detailModified();
        $this->assertTrue($entry->isDetailModified());

        $entry->first();
        $this->assertTrue($entry->isFirst());

        $entry->setAmountEntryId(10059685975);
        $this->assertEquals(10059685975, $entry->getAmountEntryId());

        $entry->locked();
        $entry->setOperator('operator');
        $entry->control();
        $this->assertTrue($entry->isLocked());
        $this->assertEquals('operator', $entry->getOperator());
        $this->assertTrue($entry->isControl());

        $entry->unlocked();
        $entry->setOperator('');
        $entry->resetControl();
        $this->assertFalse($entry->isLocked());
        $this->assertEquals('', $entry->getOperator());
        $this->assertFalse($entry->isControl());

        $this->assertNull($entry->getConfirmAt());
        $entry->confirm();
        $entry->manual();
        $this->assertTrue($entry->getConfirmAt() instanceof \DateTime);
        $this->assertTrue($entry->isConfirm());
        $this->assertFalse($entry->isProcess());
        $this->assertTrue($entry->isManual());

        $entry->setRefId('txId');
        $this->assertEquals('txId', $entry->getRefId());

        $entry->cancel();
        $this->assertTrue($entry->isCancel());

        $array = $entry->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(2, $array['user_id']);
        $this->assertEquals(3, $array['domain']);
        $this->assertEquals(4, $array['level_id']);
        $this->assertEquals($entry->getAt()->format(\DateTime::ISO8601), $array['at']);
        $this->assertFalse($array['process']);
        $this->assertTrue($array['confirm']);
        $this->assertTrue($array['cancel']);
        $this->assertFalse($array['locked']);
        $this->assertTrue($array['manual']);
        $this->assertTrue($array['first']);
        $this->assertTrue($array['detailModified']);
        $this->assertEquals(10059685975, $array['amount_entry_id']);
        $this->assertEquals(555, $array['previous_id']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals(1000, $array['amount']);
        $this->assertEquals(0.02222, $array['bitcoin_amount']);
        $this->assertEquals(6.671, $array['rate']);
        $this->assertEquals(0.00001111, $array['bitcoin_rate']);
        $this->assertEquals(0.00002222, $array['rate_difference']);
        $this->assertEquals(6671, $array['amount_conv']);
        $this->assertEquals(33.355, $array['deduction_conv']);
        $this->assertEquals(66.71, $array['audit_charge_conv']);
        $this->assertEquals(100.065, $array['audit_fee_conv']);
        $this->assertEquals(6470.87, $array['real_amount_conv']);
        $this->assertEquals(5, $array['deduction']);
        $this->assertEquals(10, $array['audit_charge']);
        $this->assertEquals(15, $array['audit_fee']);
        $this->assertEquals(970, $array['real_amount']);
        $this->assertEquals('127.0.0.1', $array['ip']);
        $this->assertFalse($entry->isControl());
        $this->assertEquals('', $array['operator']);
        $this->assertEquals('address', $array['withdraw_address']);
        $this->assertEquals('txId', $array['ref_id']);
        $this->assertEquals('這是測試', $array['memo']);
        $this->assertEquals('note test', $array['note']);

        $confirmAt = $entry->getConfirmAt()->format(\DateTime::ISO8601);
        $this->assertEquals($confirmAt, $array['confirm_at']);
    }
}
