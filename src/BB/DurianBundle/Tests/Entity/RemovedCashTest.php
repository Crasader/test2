<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedCash;

class RemovedCashTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testRemovedCashBasic()
    {
        $user = new User();
        $userRefl = new \ReflectionClass($user);
        $userReflProperty = $userRefl->getProperty('id');
        $userReflProperty->setAccessible(true);
        $userReflProperty->setValue($user, 3);

        $cash = new Cash($user, 156); // CNY
        $cashRefl = new \ReflectionClass($cash);
        $cashReflProperty = $cashRefl->getParentClass()->getProperty('id');
        $cashReflProperty->setAccessible(true);
        $cashReflProperty->setValue($cash, 2);

        $removedUser = new RemovedUser($user);
        $removedCash = new RemovedCash($removedUser, $cash);

        $this->assertEquals(2, $removedCash->getId());
        $this->assertEquals($removedUser, $removedCash->getRemovedUser());
        $this->assertEquals(156, $removedCash->getCurrency());
        $this->assertEquals(0, $removedCash->getBalance());

        $cash->setBalance(100);
        $removedUser = new RemovedUser($user);
        $removedCash = new RemovedCash($removedUser, $cash);

        $this->assertEquals(2, $removedCash->getId());
        $this->assertEquals($removedUser, $removedCash->getRemovedUser());
        $this->assertEquals(156, $removedCash->getCurrency());
        $this->assertEquals(100, $removedCash->getBalance());

        $array = $removedCash->toArray();

        $this->assertEquals(2, $array['id']);
        $this->assertEquals(3, $array['user_id']);
        $this->assertEquals(100, $array['balance']);
        $this->assertEquals('CNY', $array['currency']);
    }

    /**
     * 測試現金指派錯誤
     */
    public function testCashNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cash not belong to this user',
            150010133
        );

        $user1 = new User();
        $user1->setId(1);
        $cash = new Cash($user1, 156); // CNY

        $user2 = new User();
        $user2->setId(2);
        $removedUser = new RemovedUser($user2);
        $removedCash = new RemovedCash($removedUser, $cash);
    }
}
