<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserStat;

class UserStatTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $userStat = new UserStat($user);

        $this->assertEquals($user->getId(), $userStat->getUserId());
        $this->assertEquals(0, $userStat->getDepositCount());
        $this->assertEquals(0, $userStat->getDepositTotal());
        $this->assertEquals(0, $userStat->getDepositMax());
        $this->assertEquals(0, $userStat->getRemitCount());
        $this->assertEquals(0, $userStat->getRemitTotal());
        $this->assertEquals(0, $userStat->getRemitMax());
        $this->assertEquals(0, $userStat->getManualCount());
        $this->assertEquals(0, $userStat->getManualTotal());
        $this->assertEquals(0, $userStat->getManualMax());
        $this->assertEquals(0, $userStat->getWithdrawCount());
        $this->assertEquals(0, $userStat->getWithdrawTotal());
        $this->assertEquals(0, $userStat->getWithdrawMax());
        $this->assertEquals(0, $userStat->getSudaCount());
        $this->assertEquals(0, $userStat->getSudaTotal());
        $this->assertEquals(0, $userStat->getSudaMax());
        $this->assertNull($userStat->getFirstDepositAt());
        $this->assertEquals(0, $userStat->getFirstDepositAmount());

        $userStat->setDepositCount(1);
        $userStat->setDepositTotal(10);
        $userStat->setDepositMax(20);
        $userStat->setRemitCount(2);
        $userStat->setRemitTotal(30);
        $userStat->setRemitMax(40);
        $userStat->setManualCount(3);
        $userStat->setManualTotal(50);
        $userStat->setManualMax(60);
        $userStat->setWithdrawCount(4);
        $userStat->setWithdrawTotal(70);
        $userStat->setWithdrawMax(80);
        $userStat->setSudaCount(5);
        $userStat->setSudaTotal(80);
        $userStat->setSudaMax(90);

        $now = new \DateTime('now');
        $userStat->setModifiedAt($now);
        $this->assertEquals($now, $userStat->getModifiedAt());

        $userStat->setModifiedAt();

        $userStat->setFirstDepositAt(20160705145100);
        $firstDepositAt = new \DateTime('2016-07-05 14:51:00');
        $this->assertEquals($firstDepositAt, $userStat->getFirstDepositAt());

        $userStat->setFirstDepositAmount(100.01);
        $this->assertEquals(100.01, $userStat->getFirstDepositAmount());

        $array = $userStat->toArray();
        $this->assertEquals(0, $array['user_id']);
        $this->assertEquals(1, $array['deposit_count']);
        $this->assertEquals(10, $array['deposit_total']);
        $this->assertEquals(20, $array['deposit_max']);
        $this->assertEquals(2, $array['remit_count']);
        $this->assertEquals(30, $array['remit_total']);
        $this->assertEquals(40, $array['remit_max']);
        $this->assertEquals(3, $array['manual_count']);
        $this->assertEquals(50, $array['manual_total']);
        $this->assertEquals(60, $array['manual_max']);
        $this->assertEquals(4, $array['withdraw_count']);
        $this->assertEquals(70, $array['withdraw_total']);
        $this->assertEquals(80, $array['withdraw_max']);
        $this->assertEquals(5, $array['suda_count']);
        $this->assertEquals(80, $array['suda_total']);
        $this->assertEquals(90, $array['suda_max']);
        $this->assertEquals($firstDepositAt->format(\DateTime::ISO8601), $array['first_deposit_at']);
        $this->assertEquals(100.01, $array['first_deposit_amount']);
    }
}
