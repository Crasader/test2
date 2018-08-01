<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\AutoRemit;
use BB\DurianBundle\Entity\BankInfo;

class AutoRemitTest extends DurianTestcase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $autoRemit = new AutoRemit('TongLueYun', '同略雲');

        $autoRemitArray = $autoRemit->toArray();

        $this->assertNull($autoRemitArray['id']);
        $this->assertEquals('TongLueYun', $autoRemitArray['label']);
        $this->assertEquals('同略雲', $autoRemitArray['name']);
        $this->assertFalse($autoRemitArray['removed']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $autoRemit = new AutoRemit('TongLueYun', '同略雲');

        $this->assertEquals('TongLueYun', $autoRemit->getLabel());
        $autoRemit->setLabel('BB');
        $this->assertEquals('BB', $autoRemit->getLabel());

        $this->assertEquals('同略雲', $autoRemit->getName());
        $autoRemit->setName('BB自動認款');
        $this->assertEquals('BB自動認款', $autoRemit->getName());

        $this->assertFalse($autoRemit->isRemoved());
        $autoRemit->remove();
        $this->assertTrue($autoRemit->isRemoved());
    }

    /**
     * 測試設定支援的銀行
     */
    public function testBankInfo()
    {
        $autoRemit = new AutoRemit('TongLueYun', '同略雲');
        $bankInfo = new BankInfo('世界銀行');

        $autoRemit->addBankInfo($bankInfo);
        $apbi = $autoRemit->getBankInfo();
        $this->assertEquals($bankInfo, $apbi[0]);

        $autoRemit->removeBankInfo($bankInfo);
        $this->assertCount(0, $autoRemit->getBankInfo());
    }
}
