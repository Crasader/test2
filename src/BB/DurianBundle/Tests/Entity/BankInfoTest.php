<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BankInfo;

class BankInfoTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testNewBankInfo()
    {
        $bankname = '中國銀行';
        $bankInfo = new BankInfo($bankname);

        $arrayData = $bankInfo->toArray();

        $this->assertNull($arrayData['id']);
        $this->assertEquals($bankname, $arrayData['bankname']);
        $this->assertEquals('', $arrayData['bank_url']);
        $this->assertFalse($arrayData['virtual']);
        $this->assertEquals('', $arrayData['abbr']);
        $this->assertFalse($arrayData['auto_withdraw']);

        $bankInfo->setId(2);
        $bankInfo->setVirtual(true);
        $bankInfo->setWithdraw(true);
        $bankInfo->setBankUrl('http://www.boc.cn/');
        $bankInfo->setAbbr('中銀');
        $bankInfo->setAutoWithdraw(true);
        $this->assertEquals('2', $bankInfo->getId());
        $this->assertTrue($bankInfo->getVirtual());
        $this->assertTrue($bankInfo->getWithdraw());
        $this->assertEquals('http://www.boc.cn/', $bankInfo->getBankUrl());
        $this->assertEquals('中銀', $bankInfo->getAbbr());
        $this->assertTrue($bankInfo->isAutoWithdraw());

        $bankInfo->disable();
        $this->assertFalse($bankInfo->isEnabled());

        $bankInfo->enable();
        $this->assertTrue($bankInfo->isEnabled());
    }
}
