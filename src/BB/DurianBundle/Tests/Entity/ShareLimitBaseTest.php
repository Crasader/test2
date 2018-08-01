<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\ShareLimitBase;

/**
 * 測試 ShareLimitBase
 */
class ShareLimitBaseTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $groupNum = 5;
        $entry = new ShareLimitBase($groupNum);

        $this->assertEquals($groupNum, $entry->getGroupNum());

        $entry->setUpper(100);
        $this->assertEquals(100, $entry->getUpper());

        $entry->setUpper(200);
        $this->assertEquals(200, $entry->getUpper());

        $entry->setLower(0);
        $this->assertEquals(0, $entry->getLower());

        $entry->setLower(100);
        $this->assertEquals(100, $entry->getLower());

        $entry->setParentUpper(100);
        $this->assertEquals(100, $entry->getParentUpper());

        $entry->setParentUpper(200);
        $this->assertEquals(200, $entry->getParentUpper());

        $entry->setParentLower(0);
        $this->assertEquals(0, $entry->getParentLower());

        $entry->setParentLower(100);
        $this->assertEquals(100, $entry->getParentLower());

        $entry->setMin1(50);
        $this->assertEquals(50, $entry->getMin1());

        $entry->setMax1(5);
        $this->assertEquals(5, $entry->getMax1());

        $entry->setMax2(10);
        $this->assertEquals(10, $entry->getMax2());

        $this->assertTrue($entry->isChanged());
        $this->assertFalse($entry->resetChanged()->isChanged());
    }
}
