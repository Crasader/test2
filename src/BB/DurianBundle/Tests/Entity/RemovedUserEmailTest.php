<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserEmail;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserEmail;

/**
 * RemovedUserEmailTest Entity UnitTest
 *
 * @author sin-hao 2015.07.13
 */
class RemovedUserEmailTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testRemovedUserEmailBasic()
    {
        $user = new User();
        $user->setId(1);
        $email = new UserEmail($user);

        $now = new \DateTime();
        $email->setConfirmAt($now);

        $removedUser = new RemovedUser($user);
        $removeUserEmail = new RemovedUserEmail($removedUser, $email);

        $this->assertEquals($removedUser, $removeUserEmail->getRemovedUser());
        $this->assertNull($removeUserEmail->getEmail());
        $this->assertFalse($removeUserEmail->isConfirm());
        $this->assertEquals($now, $removeUserEmail->getConfirmAt());

        //測試toArray
        $array = $removeUserEmail->toArray();
        $this->assertEquals(1, $array['user_id']);
        $this->assertNull($array['email']);
        $this->assertFalse($array['confirm']);
        $this->assertEquals($now, new \DateTime($array['confirm_at']));
    }

    /**
     * 測試email指派錯誤
     */
    public function testUserEmailNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'UserEmail not belong to this user',
            150010132
        );

        $user1 = new User();
        $user1->setId(1);
        $email = new UserEmail($user1);

        $user2 = new User();
        $user2->setId(2);
        $removeUser = new RemovedUser($user2);
        $removeEmail = new RemovedUserEmail($removeUser, $email);
    }
}
