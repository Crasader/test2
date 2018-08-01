<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\AutoConfirmEntry;
use BB\DurianBundle\Entity\RemitAccount;

class AutoConfirmEntryTest extends DurianTestCase
{
    /**
     * 測試新增匯款記錄
     */
    public function testNewAutoConfirmEntry()
    {
        $remitAccountId = 1;

        $remitAccount = new RemitAccount(6, 1, 1, '1234567890', 156);
        $remitAccount->setId($remitAccountId);

        $data['method'] = '電子匯入';
        $data['name'] = '我是匯款人唷';
        $data['account'] = '0987654321';
        $data['amount'] = '100';
        $data['fee'] = '100';
        $data['balance'] = '1000';
        $data['memo'] = '123123123';
        $data['message'] = '銀河分行清算中心.轉存';
        $data['time'] = '2017-01-01 00:00:00';

        $autoConfirmEntry = new AutoConfirmEntry($remitAccount, $data);

        $autoConfirmEntry->setRemitAccountId($remitAccountId);
        $autoConfirmEntry->confirm();

        $autoConfirmEntryArray = $autoConfirmEntry->toArray();

        $this->assertNull($autoConfirmEntryArray['id']);
        $this->assertEquals(0, $autoConfirmEntryArray['remit_entry_id']);
        $this->assertTrue($autoConfirmEntryArray['confirm']);
        $this->assertFalse($autoConfirmEntryArray['manual']);
        $this->assertEquals($remitAccountId, $autoConfirmEntryArray['remit_account_id']);
        $this->assertEquals($data['method'], $autoConfirmEntryArray['method']);
        $this->assertEquals($data['name'], $autoConfirmEntryArray['name']);
        $this->assertEquals($data['account'], $autoConfirmEntryArray['account']);
        $this->assertNull($autoConfirmEntryArray['ref_id']);
        $this->assertEquals($data['amount'], $autoConfirmEntryArray['amount']);
        $this->assertEquals($data['fee'], $autoConfirmEntryArray['fee']);
        $this->assertEquals($data['balance'], $autoConfirmEntryArray['balance']);
        $this->assertEquals($data['memo'], $autoConfirmEntryArray['trade_memo']);
        $this->assertEquals($data['message'], $autoConfirmEntryArray['message']);
        $this->assertEquals('', $autoConfirmEntryArray['memo']);
        $dataTime = new \DateTime($data['time']);
        $this->assertEquals($dataTime->format(\DateTime::ISO8601), $autoConfirmEntryArray['trade_at']);

        $this->assertNull($autoConfirmEntry->getRefId());
        $autoConfirmEntry->setRefId('thisisrefid');
        $this->assertEquals('thisisrefid', $autoConfirmEntry->getRefId());

        $autoConfirmEntry->setManual(false);
        $this->assertFalse($autoConfirmEntry->isManual());
        $autoConfirmEntry->setManual(true);
        $this->assertTrue($autoConfirmEntry->isManual());

        $this->assertEquals(0, $autoConfirmEntry->getRemitEntryId());
        $autoConfirmEntry->setRemitEntryId('20170101000001');
        $this->assertEquals('20170101000001', $autoConfirmEntry->getRemitEntryId());

        $this->assertEquals(new \DateTime($data['time']), $autoConfirmEntry->getTradeAt());
        $autoConfirmEntry->setTradeAt(new \DateTime('2017-12-31 23:59:59'));
        $this->assertEquals(new \DateTime('2017-12-31 23:59:59'), $autoConfirmEntry->getTradeAt());

        $this->assertEquals($data['amount'], $autoConfirmEntry->getAmount());
        $autoConfirmEntry->setAmount('999');
        $this->assertEquals(999, $autoConfirmEntry->getAmount());

        $this->assertEquals($data['fee'], $autoConfirmEntry->getFee());
        $autoConfirmEntry->setFee('1');
        $this->assertEquals(1, $autoConfirmEntry->getFee());

        $this->assertEquals($data['balance'], $autoConfirmEntry->getBalance());
        $autoConfirmEntry->setBalance('1100');
        $this->assertEquals(1100, $autoConfirmEntry->getBalance());

        $this->assertEquals($data['method'], $autoConfirmEntry->getMethod());
        $autoConfirmEntry->setMethod('臨櫃匯款');
        $this->assertEquals('臨櫃匯款', $autoConfirmEntry->getMethod());

        $this->assertEquals($data['account'], $autoConfirmEntry->getAccount());
        $autoConfirmEntry->setAccount('4567891230');
        $this->assertEquals('4567891230', $autoConfirmEntry->getAccount());

        $this->assertEquals($data['name'], $autoConfirmEntry->getName());
        $autoConfirmEntry->setName('walker');
        $this->assertEquals('walker', $autoConfirmEntry->getName());

        $this->assertEquals($data['memo'], $autoConfirmEntry->getTradeMemo());
        $autoConfirmEntry->setTradeMemo('This is trade memo.');
        $this->assertEquals('This is trade memo.', $autoConfirmEntry->getTradeMemo());

        $this->assertEquals($data['message'], $autoConfirmEntry->getMessage());
        $autoConfirmEntry->setMessage('This is message.');
        $this->assertEquals('This is message.', $autoConfirmEntry->getMessage());

        $this->assertEquals('', $autoConfirmEntry->getMemo());
        $autoConfirmEntry->setMemo('This is memo.');
        $this->assertEquals('This is memo.', $autoConfirmEntry->getMemo());
    }
}
