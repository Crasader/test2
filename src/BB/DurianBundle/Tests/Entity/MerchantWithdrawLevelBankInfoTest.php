<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantWithdrawLevelBankInfo;

class MerchantWithdrawLevelBankInfoTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $bankInfo = $this->getMockBuilder('BB\DurianBundle\Entity\BankInfo')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(5);

        $mwlb = new MerchantWithdrawLevelBankInfo(12, 34, $bankInfo);
        $mwlbArray = $mwlb->toArray();

        $this->assertEquals(12, $mwlbArray['merchant_withdraw_id']);
        $this->assertEquals(34, $mwlbArray['level_id']);
        $this->assertEquals(5, $mwlbArray['bank_info']);
    }

    /**
     * 測試getter
     */
    public function testGetter()
    {
        $bankInfo = $this->getMockBuilder('BB\DurianBundle\Entity\BankInfo')
            ->disableOriginalConstructor()
            ->getMock();

        $mwlb = new MerchantWithdrawLevelBankInfo(12, 34, $bankInfo);

        $this->assertEquals(12, $mwlb->getMerchantWithdrawId());
        $this->assertEquals(34, $mwlb->getLevelId());
        $this->assertEquals($bankInfo, $mwlb->getBankInfo());
    }
}
