<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedCashFake;

class RemovedCashFakeTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testRemovedCashFakeBasic()
    {
        $userId = 3;
        $user = new User();
        $userRefl = new \ReflectionClass($user);
        $userReflProperty = $userRefl->getProperty('id');
        $userReflProperty->setAccessible(true);
        $userReflProperty->setValue($user, $userId);

        $cashFakeId = 2;
        $currency = 'CNY';
        $currencyCode = 156;
        $cashFake = new CashFake($user, $currencyCode);
        $cashFakeRefl = new \ReflectionClass($cashFake);
        $cashFakeReflProperty = $cashFakeRefl->getParentClass()->getProperty('id');
        $cashFakeReflProperty->setAccessible(true);
        $cashFakeReflProperty->setValue($cashFake, $cashFakeId);

        $removedUser = new RemovedUser($user);
        $removedCashFake = new RemovedCashFake($removedUser, $cashFake);

        $this->assertEquals($cashFakeId, $removedCashFake->getId());
        $this->assertEquals($removedUser, $removedCashFake->getRemovedUser());
        $this->assertEquals($currencyCode, $removedCashFake->getCurrency());

        $array = $removedCashFake->toArray();

        $this->assertEquals($cashFakeId, $array['id']);
        $this->assertEquals($userId, $array['user_id']);
        $this->assertEquals($currency, $array['currency']);
    }

    /**
     * 測試假現金指派錯誤
     */
    public function testCashFakeNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'CashFake not belong to this user',
            150010157
        );

        $user1 = new User();
        $user1->setId(1);
        $cashFake = new CashFake($user1, 156);

        $user2 = new User();
        $user2->setId(2);
        $removedUser = new RemovedUser($user2);
        $removedCashFake = new RemovedCashFake($removedUser, $cashFake);
    }
}
