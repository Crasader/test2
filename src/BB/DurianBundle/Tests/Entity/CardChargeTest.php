<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CardCharge;

class CardChargeTest extends DurianTestCase
{
    /**
     * 測試新增修改
     */
    public function testCardCharge()
    {
        $domain = 2;
        $cardCharge = new CardCharge($domain);

        $orderStrategy = CardCharge::STRATEGY_COUNTS;
        $cardCharge->setOrderStrategy($orderStrategy);
        $cardCharge->setDepositScMax(3);
        $cardCharge->setDepositScMin(4);
        $cardCharge->setDepositCoMax(5);
        $cardCharge->setDepositCoMin(6);
        $cardCharge->setDepositSaMax(7);
        $cardCharge->setDepositSaMin(8);
        $cardCharge->setDepositAgMax(9);
        $cardCharge->setDepositAgMin(10);

        $ccArray = $cardCharge->toArray();

        $this->assertEquals($orderStrategy, $ccArray['order_strategy']);
        $this->assertEquals(3, $ccArray['deposit_sc_max']);
        $this->assertEquals(4, $ccArray['deposit_sc_min']);
        $this->assertEquals(5, $ccArray['deposit_co_max']);
        $this->assertEquals(6, $ccArray['deposit_co_min']);
        $this->assertEquals(7, $ccArray['deposit_sa_max']);
        $this->assertEquals(8, $ccArray['deposit_sa_min']);
        $this->assertEquals(9, $ccArray['deposit_ag_max']);
        $this->assertEquals(10, $ccArray['deposit_ag_min']);
    }
}
