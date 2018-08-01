<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashWithdrawEntry;
use BB\DurianBundle\Entity\WithdrawEntryLock;

/**
 * 測試出款資料鎖定
 */
class WithdrawEntryLockTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $user->setId(50);
        $cash = new Cash($user, 156);
        $withdrawEntry = new CashWithdrawEntry($cash, 10, 1, 1, 1, 1, 0, '127.0.0.1');
        $withdrawEntry->setId(6);

        $welOperator = new WithdrawEntryLock($withdrawEntry, 'test123');

        $this->assertEquals('test123', $welOperator->getOperator());
    }
}
