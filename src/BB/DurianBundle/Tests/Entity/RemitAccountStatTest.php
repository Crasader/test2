<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitAccountStat;

class RemitAccountStatTest extends DurianTestCase
{
    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $remitAccount = new RemitAccount(1, 2, 3, 4, 'CNY');
        $at = new \DateTime('2017-01-01T12:59:59+0800');
        $remitAccountStat = new RemitAccountStat($remitAccount, $at);

        $this->assertEquals(0, $remitAccountStat->getCount());
        $this->assertEquals(0, $remitAccountStat->getIncome());
        $this->assertEquals(0, $remitAccountStat->getPayout());

        $remitAccountStat->setCount(10);
        $remitAccountStat->setIncome(100.05);
        $remitAccountStat->setPayout(10.99);

        $remitAccountStat = $remitAccountStat->toArray();

        $this->assertNull($remitAccountStat['id']);
        $this->assertEquals($remitAccount->getId(), $remitAccountStat['remit_account_id']);
        $this->assertEquals('2017-01-01T00:00:00+0800', $remitAccountStat['at']);
        $this->assertEquals(10, $remitAccountStat['count']);
        $this->assertEquals(100.05, $remitAccountStat['income']);
        $this->assertEquals(10.99, $remitAccountStat['payout']);
    }
}
