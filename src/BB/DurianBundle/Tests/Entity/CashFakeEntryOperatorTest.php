<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashFakeEntryOperator;

class CashFakeEntryOperatorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $operator = new CashFakeEntryOperator(1, 'smith');
        $operator->setTransferOut(true);
        $operator->setWhom('ChouShinShin');
        $operator->setLevel(3);

        $this->assertEquals(1, $operator->getEntryId());
        $this->assertEquals('smith', $operator->getUsername());

        $array = array('entry_id' => 1,
                       'username' => 'smith',
                       'whom'     => 'ChouShinShin',
                       'level'    => 3,
                       'transfer_out' => true);

        $this->assertEquals($array, $operator->toArray());
        $this->assertEquals($array['whom'], $operator->getWhom());
        $this->assertEquals($array['transfer_out'], $operator->getTransferOut());
        $this->assertEquals($array['level'], $operator->getLevel());
    }
}
