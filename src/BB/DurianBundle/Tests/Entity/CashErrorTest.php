<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashError;

class CashErrorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $cashError = new CashError();

        $cashError->setId(0);
        $this->assertEquals(0, $cashError->getId());

        $cashError->setCashId(0);
        $this->assertEquals(0, $cashError->getCashId());

        $cashError->setUserId(0);
        $this->assertEquals(0, $cashError->getUserId());

        $cashError->setCurrency(0);
        $this->assertEquals(0, $cashError->getCurrency());

        $cashError->setBalance(0);
        $this->assertEquals(0, $cashError->getBalance());

        $cashError->setTotalAmount(0);
        $this->assertEquals(0, $cashError->getTotalAmount());

        $cashError->setAt(new \DateTime());
    }
}
