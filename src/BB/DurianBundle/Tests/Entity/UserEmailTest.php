<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\UserEmail;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserEmail;

class UserEmailTest extends DurianTestCase
{
    /**
     * 基本測試
     */
    public function testBasic()
    {
        $now = new \DateTime('now');
        $user = new User();
        $user->setId(1);

        $email = new UserEmail($user);

        $this->assertEquals($user, $email->getUser());

        $email->setEmail('gg@mail.com');
        $email->setConfirm(true);
        $email->setConfirmAt($now);

        $array = $email->toArray();
        $nowStr = $now->format(\DateTime::ISO8601);

        $this->assertEquals(1, $array['user_id']);
        $this->assertEquals('gg@mail.com', $array['email']);
        $this->assertTrue($array['confirm']);
        $this->assertEquals($nowStr, $array['confirm_at']);

        $email->removeConfirmAt();
        $this->assertNull($email->getConfirmAt());
    }

    /**
     * 測試從刪除使用者信箱備份設定使用者信箱
     */
    public function testSetFromRemoved()
    {
        $user = new User();
        $userEmail = new UserEmail($user);

        $userEmail->setEmail('gg@mail.com');
        $userEmail->setConfirm(true);

        $removedUser = new RemovedUser($user);
        $removedUserEmail = new RemovedUserEmail($removedUser, $userEmail);
        $userEmail2 = new UserEmail($user);

        $userEmail2->setFromRemoved($removedUserEmail);
        $this->assertEquals($userEmail->toArray(), $userEmail2->toArray());
    }

    /**
     * 測試從刪除使用者信箱備份設定使用者信箱，但指派錯誤
     */
    public function testSetFromRemovedButNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'UserEmail not belong to this user',
            150010132
        );

        $user1 = new User();
        $user1->setId(1);
        $userEmail1 = new UserEmail($user1);

        $user2 = new User();
        $user2->setId(2);
        $userEmail2 = new UserEmail($user2);

        $removedUser = new RemovedUser($user2);
        $removedUserEmail = new RemovedUserEmail($removedUser, $userEmail2);

        $userEmail1->setFromRemoved($removedUserEmail);
    }
}
