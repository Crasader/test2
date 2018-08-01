<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashEntryOperator;

class CashEntryOperatorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $operator = new CashEntryOperator(1, 'smith');

        $this->assertEquals(1, $operator->getEntryId());
        $this->assertEquals('smith', $operator->getUsername());

        $array = ['entry_id' => 1, 'username' => 'smith'];
        $this->assertEquals($array, $operator->toArray());
    }
}
