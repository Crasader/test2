<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CardError;

class CardErrorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $cardError = new CardError();

        $cardError->setId(0);
        $this->assertEquals(0, $cardError->getId());

        $cardError->setCardId(0);
        $this->assertEquals(0, $cardError->getCardId());

        $cardError->setUserId(0);
        $this->assertEquals(0, $cardError->getUserId());

        $cardError->setBalance(0);
        $this->assertEquals(0, $cardError->getBalance());

        $cardError->setTotalAmount(0);
        $this->assertEquals(0, $cardError->getTotalAmount());

        $cardError->setAt(new \DateTime());
    }
}
