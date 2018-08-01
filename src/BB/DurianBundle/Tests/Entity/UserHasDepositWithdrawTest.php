<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;
use BB\DurianBundle\Entity\User;

/**
 * 測試使用者存提款紀錄
 */
class UserHasDepositWithdrawTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $user->setId(1);

        $depositAt = new \DateTime('2013-01-10 12:00:00');
        $withdrawAt = new \DateTime('2013-01-10 16:00:00');
        $stat = new UserHasDepositWithdraw($user, $depositAt, $withdrawAt, true, true);

        $this->assertEquals(1, $stat->getUserId());
        $this->assertEquals($depositAt, $stat->getDepositAt());
        $this->assertEquals($withdrawAt, $stat->getWithdrawAt());
        $this->assertTrue($stat->isDeposited());
        $this->assertTrue($stat->isWithdrew());
        $this->assertNull($stat->getFirstDepositAt());

        $depositAt->add(new \DateInterval('P1D'));
        $stat->setDepositAt($depositAt);
        $this->assertEquals($depositAt, $stat->getDepositAt());

        $withdrawAt->add(new \DateInterval('P1D'));
        $stat->setWithdrawAt($withdrawAt);
        $this->assertEquals($withdrawAt, $stat->getWithdrawAt());

        $stat->setDeposit(false);
        $stat->setWithdraw(false);

        $this->assertFalse($stat->isDeposited());
        $this->assertFalse($stat->isWithdrew());

        $stat->setFirstDepositAt(20160705145100);
        $firstDepositAt = new \DateTime('2016-07-05 14:51:00');
        $this->assertEquals($firstDepositAt, $stat->getFirstDepositAt());

        $statArray = $stat->toArray();

        $this->assertEquals(1, $statArray['user_id']);
        $this->assertEquals($depositAt->format(\DateTime::ISO8601), $statArray['deposit_at']);
        $this->assertEquals($withdrawAt->format(\DateTime::ISO8601), $statArray['withdraw_at']);
        $this->assertFalse($statArray['deposit']);
        $this->assertFalse($statArray['withdraw']);
        $this->assertEquals($firstDepositAt->format(\DateTime::ISO8601), $statArray['first_deposit_at']);
    }
}
