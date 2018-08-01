<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedCredit;

class RemovedCreditTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testRemovedCreditBasic()
    {
        $userId = 3;
        $user = new User();
        $userRefl = new \ReflectionClass($user);
        $userReflProperty = $userRefl->getProperty('id');
        $userReflProperty->setAccessible(true);
        $userReflProperty->setValue($user, $userId);

        $creditId = 2;
        $groupNum = 5;
        $credit = new Credit($user, $groupNum);
        $creditRefl = new \ReflectionClass($credit);
        $creditReflProperty = $creditRefl->getProperty('id');
        $creditReflProperty->setAccessible(true);
        $creditReflProperty->setValue($credit, $creditId);

        $removedUser = new RemovedUser($user);
        $removedCredit = new RemovedCredit($removedUser, $credit);

        $this->assertEquals($creditId, $removedCredit->getId());
        $this->assertEquals($removedUser, $removedCredit->getRemovedUser());
        $this->assertEquals($groupNum, $removedCredit->getGroupNum());

        $array = $removedCredit->toArray();

        $this->assertEquals($creditId, $array['id']);
        $this->assertEquals($userId, $array['user_id']);
        $this->assertEquals($groupNum, $array['group']);
    }

    /**
     * 測試信用額度指派錯誤
     */
    public function testCreditNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Credit not belong to this user',
            150010158
        );

        $user1 = new User();
        $user1->setId(1);
        $credit = new Credit($user1, 5);

        $user2 = new User();
        $user2->setId(2);
        $removedUser = new RemovedUser($user2);
        $removedCredit = new RemovedCredit($removedUser, $credit);
    }
}
