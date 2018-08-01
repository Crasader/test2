<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashFakeError;

class CashFakeErrorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $entry = new CashFakeError();

        $this->assertEquals(0, $entry->getId());

        $entry->setCashFakeId(0);
        $this->assertEquals(0, $entry->getCashFakeId());

        $entry->setUserId(0);
        $this->assertEquals(0, $entry->getUserId());

        $entry->setCurrency(0);
        $this->assertEquals(0, $entry->getCurrency());

        $entry->setBalance(0);
        $this->assertEquals(0, $entry->getBalance());

        $entry->setTotalAmount(0);
        $this->assertEquals(0, $entry->getTotalAmount());

        $entry->setAt(new \DateTime());
    }
}
