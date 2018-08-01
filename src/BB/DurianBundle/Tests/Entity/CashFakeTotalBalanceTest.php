<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashFakeTotalBalance;

class CashFakeTotalBalanceTest extends DurianTestCase
{
    /**
     * 新增一個CashFakeTotalBalance
     */
    public function testNewCashFakeTotalBalance()
    {
        $parentId = 777;
        $cftb = new CashFakeTotalBalance($parentId, 156); // CNY

        $this->assertNull($cftb->getAt());
        $this->assertEquals(0, $cftb->getEnableBalance());
        $this->assertEquals(0, $cftb->getDisableBalance());
        $this->assertEquals(156, $cftb->getCurrency());

        $enableBalance  = 99999999.9999;
        $cftb->setEnableBalance($enableBalance);

        $disableBalance  = 123456.9784;
        $cftb->setDisableBalance($disableBalance);

        $at = new \DateTime('now');
        $cftb->setAt($at);

        $this->assertEquals($enableBalance, $cftb->getEnableBalance());
        $this->assertEquals($disableBalance, $cftb->getDisableBalance());
        $this->assertEquals($at, $cftb->getAt());
        $this->assertEquals($parentId, $cftb->getParentId());

        $array = $cftb->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertEquals($cftb->getAt()->format(\DateTime::ISO8601), $array['at']);
        $this->assertEquals($parentId, $array['parent_id']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals($enableBalance, $array['enable_balance']);
        $this->assertEquals($disableBalance, $array['disable_balance']);
    }
}
