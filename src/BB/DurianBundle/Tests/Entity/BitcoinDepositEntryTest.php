<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BitcoinDepositEntry;


class BitcoinDepositEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $data = [
            'id' => 201701010000000001,
            'bitcoin_wallet_id' => 1,
            'bitcoin_address_id' => 2,
            'bitcoin_address' => 'address',
            'user_id' => 3,
            'domain' => 4,
            'level_id' => 5,
            'currency' => 840,
            'payway_currency' => 156,
            'amount' => 1000,
            'bitcoin_amount' => 0.02222,
            'rate' => 6.671,
            'payway_rate' => 2.33,
            'bitcoin_rate' => 0.00001111,
            'rate_difference' => 0.00002222,
        ];
        $entry = new BitcoinDepositEntry($data);

        $this->assertEquals(201701010000000001, $entry->getId());
        $this->assertEquals(1, $entry->getBitcoinWalletId());
        $this->assertEquals(2, $entry->getBitcoinAddressId());
        $this->assertEquals('address', $entry->getBitcoinAddress());
        $this->assertEquals(3, $entry->getUserId());
        $this->assertEquals(4, $entry->getDomain());
        $this->assertEquals(5, $entry->getLevelId());
        $this->assertEquals(0, $entry->getAmountEntryId());
        $this->assertEquals(840, $entry->getCurrency());
        $this->assertEquals(156, $entry->getPaywayCurrency());
        $this->assertEquals(1000, $entry->getAmount());
        $this->assertEquals(0.02222, $entry->getBitcoinAmount());
        $this->assertEquals(6.671, $entry->getRate());
        $this->assertEquals(2.33, $entry->getPaywayRate());
        $this->assertEquals(0.00001111, $entry->getBitcoinRate());
        $this->assertEquals(0.00002222, $entry->getRateDifference());
        $this->assertEquals('', $entry->getOperator());
        $this->assertEquals('', $entry->getMemo());

        $this->assertTrue($entry->isProcess());
        $this->assertFalse($entry->isConfirm());
        $this->assertFalse($entry->isCancel());
        $this->assertFalse($entry->isControl());
        $this->assertEquals(6671, $entry->getAmountConvBasic());
        $this->assertEquals(2863.0901, $entry->getAmountConv());

        $entry->setMemo('這是測試');
        $this->assertEquals('這是測試', $entry->getMemo());

        $entry->setAmountEntryId(10059685975);
        $this->assertEquals(10059685975, $entry->getAmountEntryId());

        $this->assertNull($entry->getConfirmAt());
        $entry->confirm();
        $entry->setOperator('operator');
        $entry->control();
        $this->assertTrue($entry->getConfirmAt() instanceof \DateTime);
        $this->assertTrue($entry->isConfirm());
        $this->assertFalse($entry->isProcess());
        $this->assertEquals('operator', $entry->getOperator());
        $this->assertTrue($entry->isControl());

        $entry->cancel();
        $this->assertTrue($entry->isCancel());

        $now = new \DateTime('now');
        $nowString = $now->format('YmdHis');
        $entry->setAt($nowString);
        $this->assertEquals($now, $entry->getAt());

        $array = $entry->toArray();

        $this->assertEquals(201701010000000001, $array['id']);
        $this->assertEquals(1, $array['bitcoin_wallet_id']);
        $this->assertEquals(2, $array['bitcoin_address_id']);
        $this->assertEquals('address', $array['bitcoin_address']);
        $this->assertEquals(3, $array['user_id']);
        $this->assertEquals(4, $array['domain']);
        $this->assertEquals(5, $array['level_id']);
        $this->assertEquals($now, new \DateTime($array['at']));
        $this->assertFalse($array['process']);
        $this->assertTrue($array['confirm']);
        $this->assertTrue($array['cancel']);
        $this->assertEquals(10059685975, $array['amount_entry_id']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals('CNY', $array['payway_currency']);
        $this->assertEquals(1000, $array['amount']);
        $this->assertEquals(6671, $array['amount_conv_basic']);
        $this->assertEquals(2863.0901, $array['amount_conv']);
        $this->assertEquals(0.02222, $array['bitcoin_amount']);
        $this->assertEquals(6.671, $array['rate']);
        $this->assertEquals(2.33, $array['payway_rate']);
        $this->assertEquals(0.00001111, $array['bitcoin_rate']);
        $this->assertEquals(0.00002222, $array['rate_difference']);
        $this->assertTrue($entry->isControl());
        $this->assertEquals('operator', $array['operator']);
        $this->assertEquals('這是測試', $array['memo']);

        $confirmAt = $entry->getConfirmAt()->format(\DateTime::ISO8601);
        $this->assertEquals($confirmAt, $array['confirm_at']);
    }
}
