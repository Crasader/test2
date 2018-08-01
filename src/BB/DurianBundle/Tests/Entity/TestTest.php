<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Test;

class TestTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $test = new Test();

        $this->assertEquals("", $test->getMemo());

        $test->setMemo("111");
        $this->assertEquals("111", $test->getMemo());
        $this->assertEquals(0, $test->getId());
    }
}
