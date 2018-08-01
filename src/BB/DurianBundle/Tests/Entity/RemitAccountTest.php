<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitAccount;

class RemitAccountTest extends DurianTestCase
{
    /**
     * 測試新增修改
     */
    public function testNewAndSet()
    {
        $remitAccount = new RemitAccount(2, 1, 1, '1234567890', 156);

        $this->assertEquals(2, $remitAccount->getDomain());
        $this->assertEquals(1, $remitAccount->getBankInfoId());
        $this->assertEquals('1234567890', $remitAccount->getAccount());
        $this->assertEquals(0, $remitAccount->getBalance());
        $this->assertEquals(0, $remitAccount->getBankLimit());
        $this->assertEquals(0, $remitAccount->getAutoRemitId());
        $this->assertFalse($remitAccount->isAutoConfirm());
        $this->assertFalse($remitAccount->isPasswordError());
        $this->assertFalse($remitAccount->isCrawlerOn());
        $this->assertFalse($remitAccount->isCrawlerRun());
        $this->assertNull($remitAccount->getCrawlerUpdate());
        $this->assertEmpty($remitAccount->getWebBankAccount());
        $this->assertEmpty($remitAccount->getWebBankPassword());
        $this->assertEquals(156, $remitAccount->getCurrency());
        $this->assertEquals('', $remitAccount->getControlTips());
        $this->assertEquals('', $remitAccount->getRecipient());
        $this->assertEquals('', $remitAccount->getMessage());
        $this->assertTrue($remitAccount->isEnabled());
        $this->assertFalse($remitAccount->isSuspended());
        $this->assertFalse($remitAccount->isDeleted());

        $remitAccount->setDomain(1);
        $remitAccount->setId(1);
        $remitAccount->setBankInfoId(2);
        $remitAccount->setBalance(10);
        $remitAccount->setBankLimit(20);
        $remitAccount->setAccountType(0);
        $remitAccount->setAutoRemitId(1);
        $remitAccount->setAutoConfirm(true);
        $remitAccount->setPasswordError(true);
        $remitAccount->setCrawlerOn(true);
        $remitAccount->setCrawlerRun(true);
        $remitAccount->setAccount('9876543210');
        $remitAccount->setWebBankAccount('acc');
        $remitAccount->setWebBankPassword('pw');
        $remitAccount->setCurrency(901);
        $remitAccount->setControlTips('Tips');
        $remitAccount->setRecipient('Who');
        $remitAccount->setMessage('Message');
        $remitAccount->disable();
        $remitAccount->suspend();
        $remitAccount->delete();
        $time = new \DateTime('2017-09-10 13:13:13');
        $remitAccount->setCrawlerUpdate($time);

        $data = $remitAccount->toArray();
        $this->assertEquals(1, $data['domain']);
        $this->assertEquals(1, $data['id']);
        $this->assertEquals(2, $data['bank_info_id']);
        $this->assertEquals(10, $data['balance']);
        $this->assertEquals(20, $data['bank_limit']);
        $this->assertEquals(1, $data['auto_remit_id']);
        $this->assertTrue($data['auto_confirm']);
        $this->assertTrue($data['password_error']);
        $this->assertTrue($data['crawler_on']);
        $this->assertTrue($data['crawler_run']);
        $this->assertEquals('2017-09-10T13:13:13+0800', $data['crawler_update']);
        $this->assertEquals('9876543210', $data['account']);
        $this->assertEquals('acc', $data['web_bank_account']);
        $this->assertEquals('TWD', $data['currency']);
        $this->assertEquals('Tips', $data['control_tips']);
        $this->assertEquals('Who', $data['recipient']);
        $this->assertEquals('Message', $data['message']);
        $this->assertFalse($data['enable']);
        $this->assertTrue($data['suspend']);
        $this->assertTrue($data['deleted']);

        $remitAccount->enable();
        $remitAccount->resume();
        $remitAccount->recover();
        $this->assertTrue($remitAccount->isEnabled());
        $this->assertFalse($remitAccount->isSuspended());
        $this->assertFalse($remitAccount->isDeleted());
    }
}
