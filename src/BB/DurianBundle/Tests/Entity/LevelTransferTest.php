<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\LevelTransfer;

class LevelTransferTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $levelTransfer = new LevelTransfer(123, 456, 789);
        $ltArray = $levelTransfer->toArray();

        $this->assertEquals(123, $ltArray['domain']);
        $this->assertEquals(456, $ltArray['source']);
        $this->assertEquals(789, $ltArray['target']);
        $this->assertNotNull($ltArray['created_at']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $levelTransfer = new LevelTransfer(123, 456, 789);

        $this->assertEquals(123, $levelTransfer->getDomain());
        $this->assertEquals(456, $levelTransfer->getSource());
        $this->assertEquals(789, $levelTransfer->getTarget());
        $this->assertNotNull($levelTransfer->getCreatedAt());

        $levelTransfer->setTarget(987);
        $this->assertEquals(987, $levelTransfer->getTarget());
    }
}
