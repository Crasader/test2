<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashTotalBalance;

class CashTotalBalanceTest extends DurianTestCase
{
    /**
     * 新增一個CashTotalBalance
     */
    public function testNewCashTotalBalance()
    {
        $parentId = 777;
        $ctb = new CashTotalBalance($parentId, 901); // TWD

        $this->assertNull($ctb->getAt());
        $this->assertEquals($parentId, $ctb->getParentId());
        $this->assertEquals(0, $ctb->getEnableBalance());
        $this->assertEquals(0, $ctb->getDisableBalance());
        $this->assertEquals(901, $ctb->getCurrency());

        $enableBalance  = 99999999.9999;
        $ctb->setEnableBalance($enableBalance);

        $disableBalance  = 123456.9784;
        $ctb->setDisableBalance($disableBalance);

        $at = new \DateTime('now');
        $ctb->setAt($at);

        $this->assertEquals($enableBalance, $ctb->getEnableBalance());
        $this->assertEquals($disableBalance, $ctb->getDisableBalance());
        $this->assertEquals($at, $ctb->getAt());

        $array = $ctb->toArray();
        $this->assertEquals(0, $array['id']);
        $this->assertEquals($ctb->getAt(), new \DateTime($array['at']));
        $this->assertEquals($ctb->getParentId(), $array['parent_id']);
        $this->assertEquals('TWD', $array['currency']);
        $this->assertEquals($ctb->getEnableBalance(), $array['enable_balance']);
        $this->assertEquals($ctb->getDisableBalance(), $array['disable_balance']);
    }
}
