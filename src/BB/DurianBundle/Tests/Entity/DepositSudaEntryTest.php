<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\DepositSudaEntry;

/**
 * 測試 DepositSudaEntry
 */
class DepositSudaEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $param = [
            'seq_id' => 53,
            'merchant_number' => 41600,
            'order_id' => 'aada942251dc5c8fa483c7817bd8ba71',
            'code' => 'icbc',
            'alias' => 'test',
            'amount' => 1234.5678,
            'offer_deposit' => 12.3456,
            'offer_other' => 1.2345,
            'bank_info_id' => 1,
            'recipient' => 'recipient',
            'account' => '123456789012',
            'fee' => 5.6789,
            'merchant_suda_id' => 2,
            'memo' => 'memo'
        ];

        $depositSuda = new DepositSudaEntry($user, $param);

        $depositSuda->confirm();

        $ret = $depositSuda->toArray();
        $this->assertEquals(53, $ret['seq_id']);
        $this->assertEquals(41600, $ret['merchant_number']);
        $this->assertEquals('aada942251dc5c8fa483c7817bd8ba71', $ret['order_id']);
        $this->assertEquals('icbc', $ret['code']);
        $this->assertEquals('test', $ret['alias']);
        $this->assertEquals(1234.5678, $ret['amount']);
        $this->assertEquals(12.3456, $ret['offer_deposit']);
        $this->assertEquals(1.2345, $ret['offer_other']);
        $this->assertEquals(1, $ret['bank_info_id']);
        $this->assertEquals('recipient', $ret['recipient']);
        $this->assertEquals('123456789012', $ret['account']);
        $this->assertEquals(5.6789, $ret['fee']);
        $this->assertEquals(2, $ret['merchant_suda_id']);
        $this->assertNotNull($ret['created_at']);
        $this->assertEquals('memo', $ret['memo']);

        $depositSuda->setId(123);
        $this->assertEquals(123, $depositSuda->getId());

        $depositSuda->setSeqId(54);
        $this->assertEquals(54, $depositSuda->getSeqId());

        $depositSuda->setMerchantNumber(123456);
        $this->assertEquals(123456, $depositSuda->getMerchantNumber());

        $depositSuda->setOrderId('d6a7f806a9b9d08a6ae292d28f4ee429');
        $this->assertEquals('d6a7f806a9b9d08a6ae292d28f4ee429', $depositSuda->getOrderId());

        $depositSuda->setCode('ccb');
        $this->assertEquals('ccb', $depositSuda->getCode());

        $depositSuda->setAlias('test123');
        $this->assertEquals('test123', $depositSuda->getAlias());

        $depositSuda->setAmount(987.6543);
        $this->assertEquals(987.6543, $depositSuda->getAmount());

        $depositSuda->setOfferDeposit(876.5432);
        $this->assertEquals(876.5432, $depositSuda->getOfferDeposit());

        $depositSuda->setOfferOther(5.4321);
        $this->assertEquals(5.4321, $depositSuda->getOfferOther());

        $depositSuda->setDomain(231);
        $this->assertEquals(231, $depositSuda->getDomain());

        $depositSuda->setUserId(9);
        $this->assertEquals(9, $depositSuda->getUserId());

        $depositSuda->setBankInfoId(9);
        $this->assertEquals(9, $depositSuda->getBankInfoId());

        $depositSuda->setRecipient('recipient123');
        $this->assertEquals('recipient123', $depositSuda->getRecipient());

        $depositSuda->setAccount('987654321098');
        $this->assertEquals('987654321098', $depositSuda->getAccount());

        $depositSuda->setFee(5.4321);
        $this->assertEquals(5.4321, $depositSuda->getFee());

        $depositSuda->unconfirm();
        $this->assertNotTrue($depositSuda->isConfirm());

        $depositSuda->confirm();
        $this->assertTrue($depositSuda->isConfirm());
        $this->assertInstanceOf('\DateTime', $depositSuda->getConfirmAt());

        $depositSuda->cancel();
        $this->assertTrue($depositSuda->isCancel());
        $this->assertInstanceOf('\DateTime', $depositSuda->getConfirmAt());

        $depositSuda->uncancel();
        $this->assertNotTrue($depositSuda->isCancel());

        $depositSuda->setMerchantSudaId(3301);
        $this->assertEquals(3301, $depositSuda->getMerchantSudaId());

        $depositSuda->setCheckedUsername('check_new');
        $this->assertEquals('check_new', $depositSuda->getCheckedUsername());

        $depositSuda->setCreatedAt('2013-01-11');
        $this->assertEquals('2013-01-11', $depositSuda->getCreatedAt());

        $depositSuda->setMemo('oops');
        $this->assertEquals('oops', $depositSuda->getMemo());
    }
}
