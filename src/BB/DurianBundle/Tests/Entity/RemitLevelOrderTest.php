<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitLevelOrder;

class RemitLevelOrderTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $remitLevelOrder = new RemitLevelOrder(2, 1);

        $this->assertEquals(2, $remitLevelOrder->getDomain());
        $this->assertEquals(1, $remitLevelOrder->getLevelId());
        $this->assertFalse($remitLevelOrder->getByCount());
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $remitLevelOrder = new RemitLevelOrder(2, 1);

        $this->assertEquals(2, $remitLevelOrder->getDomain());
        $this->assertEquals(1, $remitLevelOrder->getLevelId());
        $this->assertFalse($remitLevelOrder->getByCount());

        $remitLevelOrder->setByCount(true);

        $this->assertTrue($remitLevelOrder->getByCount());
    }
}
