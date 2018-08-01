<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantLevel;

class MerchantLevelTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $merchantLevel = new MerchantLevel(12, 34, 1);
        $merchantLevelArray = $merchantLevel->toArray();

        $this->assertEquals(12, $merchantLevelArray['merchant_id']);
        $this->assertEquals(34, $merchantLevelArray['level_id']);
        $this->assertEquals(1, $merchantLevelArray['order_id']);
        $this->assertNull($merchantLevelArray['version']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $merchantLevel = new MerchantLevel(12, 34, 1);

        $this->assertEquals(12, $merchantLevel->getMerchantId());
        $this->assertEquals(34, $merchantLevel->getLevelId());
        $this->assertEquals(1, $merchantLevel->getOrderId());
        $this->assertNull($merchantLevel->getVersion());

        $merchantLevel->setOrderId(2);
        $this->assertEquals(2, $merchantLevel->getOrderId());
    }
}
