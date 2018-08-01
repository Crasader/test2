<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantWithdrawLevel;

class MerchantWithdrawLevelTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $merchantWithdrawLevel = new MerchantWithdrawLevel(12, 34, 1);
        $merchantWithdrawLevelArray = $merchantWithdrawLevel->toArray();

        $this->assertEquals(12, $merchantWithdrawLevelArray['merchant_withdraw_id']);
        $this->assertEquals(34, $merchantWithdrawLevelArray['level_id']);
        $this->assertEquals(1, $merchantWithdrawLevelArray['order_id']);
        $this->assertNull($merchantWithdrawLevelArray['version']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $merchantWithdrawLevel = new MerchantWithdrawLevel(12, 34, 1);

        $this->assertEquals(12, $merchantWithdrawLevel->getMerchantWithdrawId());
        $this->assertEquals(34, $merchantWithdrawLevel->getLevelId());
        $this->assertEquals(1, $merchantWithdrawLevel->getOrderId());
        $this->assertNull($merchantWithdrawLevel->getVersion());

        $merchantWithdrawLevel->setOrderId(2);
        $this->assertEquals(2, $merchantWithdrawLevel->getOrderId());
    }
}
