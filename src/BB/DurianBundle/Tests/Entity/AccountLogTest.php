<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\AccountLog;

class AccountLogTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $accountLog = new AccountLog();

        $this->assertEquals(0, $accountLog->getCount());
        $this->assertEquals(AccountLog::UNTREATED, $accountLog->getStatus());
        $this->assertTrue($accountLog->getUpdateAt() instanceof \DateTime);
        $this->assertNull($accountLog->getId());

        $accountLog->setCurrencyName(901); // TWD
        $this->assertEquals(901, $accountLog->getCurrencyName());

        $accountLog->setAccount('danvanci');
        $this->assertEquals('danvanci', $accountLog->getAccount());

        $accountLog->setWeb('esball');
        $this->assertEquals('esball', $accountLog->getWeb());

        $now = new \DateTime('now');
        $accountLog->setAccountDate($now);
        $this->assertEquals($now, $accountLog->getAccountDate());

        $accountLog->setAccountName('王大明');
        $this->assertEquals('王大明', $accountLog->getAccountName());

        $accountLog->setAccountNo('0x9d3016517d294a06a2193e8cae2e108dt56f4j3D');
        $this->assertEquals('0x9d3016517d294a06a2193e8cae2e108dt56f4j3D', $accountLog->getAccountNo());

        $accountLog->setBankName('台灣銀行');
        $this->assertEquals('台灣銀行', $accountLog->getBankName());

        $accountLog->setGold(100);
        $this->assertEquals(100, $accountLog->getGold());

        $accountLog->setRemark('首次出款');
        $this->assertEquals('首次出款', $accountLog->getRemark());

        $accountLog->setCheck02(0);
        $this->assertEquals(0, $accountLog->getCheck02());

        $accountLog->setMoney01(110);
        $this->assertEquals(110, $accountLog->getMoney01());

        $accountLog->setMoney02(5);
        $this->assertEquals(5, $accountLog->getMoney02());

        $accountLog->setMoney03(5);
        $this->assertEquals(5, $accountLog->getMoney03());

        $accountLog->setFromId(1);
        $this->assertEquals(1, $accountLog->getFromId());

        $accountLog->setPreviousId(0);
        $this->assertEquals(0, $accountLog->getPreviousId());

        $accountLog->setIsTest(true);
        $this->assertTrue($accountLog->isTest());

        $this->assertFalse($accountLog->isDetailModified());
        $accountLog->detailModified();
        $this->assertTrue($accountLog->isDetailModified());

        $accountLog->setMultipleAdudit('');
        $this->assertEquals('', $accountLog->getMultipleAudit());

        $accountLog->setStatusStr('');
        $this->assertEquals('', $accountLog->getStatusStr());

        $accountLog->setStatus(AccountLog::SENT);
        $this->assertEquals(AccountLog::SENT, $accountLog->getStatus());

        $accountLog->addCount();
        $this->assertEquals(1, $accountLog->getCount());

        $accountLog->zeroCount();
        $this->assertEquals(0, $accountLog->getCount());

        $accountLog->setPreviousId(1);
        $this->assertEquals(1, $accountLog->getPreviousId());

        $accountLog->setPreviousId(2);
        $this->assertEquals(1, $accountLog->getPreviousId());

        $accountLog->setDomain(6);
        $this->assertEquals(6, $accountLog->getDomain());

        $accountLog->setLevelId(1);
        $this->assertEquals(1, $accountLog->getLevelId());

        $accountLogArray = $accountLog->toArray();

        $this->assertNull($accountLogArray['id']);
        $this->assertEquals('TWD', $accountLogArray['currency_name']);
        $this->assertEquals('danvanci', $accountLogArray['account']);
        $this->assertEquals('esball', $accountLogArray['web']);
        $this->assertEquals($now, new \DateTime($accountLogArray['account_date']));
        $this->assertEquals('王大明', $accountLogArray['account_name']);
        $this->assertEquals('0x9d3016517d294a06a2193e8cae2e108dt56f4j3D', $accountLogArray['account_no']);
        $this->assertEquals('台灣銀行', $accountLogArray['bank_name']);
        $this->assertEquals(100, $accountLogArray['gold']);
        $this->assertEquals('首次出款', $accountLogArray['remark']);
        $this->assertEquals(0, $accountLogArray['check02']);
        $this->assertEquals(110, $accountLogArray['money01']);
        $this->assertEquals(5, $accountLogArray['money02']);
        $this->assertEquals(5, $accountLogArray['money03']);
        $this->assertEquals(1, $accountLogArray['from_id']);
        $this->assertEquals(1, $accountLogArray['previous_id']);
        $this->assertTrue($accountLogArray['is_test']);
        $this->assertTrue($accountLogArray['detail_modified']);
        $this->assertEquals('', $accountLogArray['multiple_audit']);
        $this->assertEquals('', $accountLogArray['status_str']);
        $this->assertEquals(1, $accountLogArray['status']);
        $this->assertEquals(0, $accountLogArray['count']);

        $updateAt = $accountLog->getUpdateAt()->format(\DateTime::ISO8601);
        $this->assertEquals($updateAt, $accountLogArray['update_at']);
        $this->assertEquals(6, $accountLogArray['domain']);
        $this->assertEquals(1, $accountLogArray['level_id']);
    }
}
