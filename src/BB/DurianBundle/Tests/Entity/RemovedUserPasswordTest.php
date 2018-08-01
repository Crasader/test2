<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserPassword;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserPassword;

/**
 * RemovedUserPasswordTest Entity UnitTest
 *
 * @author Cullen 2015.11.19
 */
class RemovedUserPasswordTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testRemovedUserPasswordBasic()
    {
        $expireAt = new \DateTime('20151119000000');
        $onceExpireAt = new \DateTime('20151120000000');
        $user = new User();
        $user->setId(1);
        $password = new UserPassword($user);
        $password->setExpireAt($expireAt);
        $password->setOnceExpireAt($onceExpireAt);

        $removedUser = new RemovedUser($user);
        $removeUserPassword = new RemovedUserPassword($removedUser, $password);

        $this->assertEquals($removedUser, $removeUserPassword->getRemovedUser());
        $this->assertNull($removeUserPassword->getHash());
        $this->assertEquals($expireAt, $removeUserPassword->getExpireAt());

        $now = new \DateTime();
        $this->assertLessThanOrEqual($now, $removeUserPassword->getModifiedAt());
        $this->assertFalse($removeUserPassword->isReset());
        $this->assertNull($removeUserPassword->getOncePassword());
        $this->assertFalse($removeUserPassword->isUsed());
        $this->assertEquals($onceExpireAt, $removeUserPassword->getOnceExpireAt());

        //測試toArray
        $array = $removeUserPassword->toArray();
        $this->assertEquals(1, $array['user_id']);
        $this->assertNull($array['hash']);
        $this->assertEquals($expireAt, new \DateTime($array['expire_at']));
        $this->assertFalse($array['reset']);
        $this->assertEquals(0, $array['err_num']);
        $this->assertNull($array['once_password']);
        $this->assertFalse($array['used']);
        $this->assertEquals($onceExpireAt, new \DateTime($array['once_expire_at']));

        //測試expire_at為0000-00-00 00:00:00
        $password->setExpireAt(new \DateTime('0000-00-00 00:00:00'));
        $removeUserPassword = new RemovedUserPassword($removedUser, $password);

        $this->assertEquals(1, $removeUserPassword->getRemovedUser()->getUserId());
    }

    /**
     * 測試password指派錯誤
     */
    public function testUserPasswordNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'UserPassword not belong to this user',
            150010138
        );

        $user1 = new User();
        $user1->setId(1);
        $password = new UserPassword($user1);

        $user2 = new User();
        $user2->setId(2);
        $removeUser = new RemovedUser($user2);
        $removePassword = new RemovedUserPassword($removeUser, $password);
    }
}
