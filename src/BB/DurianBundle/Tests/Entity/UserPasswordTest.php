<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\UserPassword;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserPassword;

/**
 * @author George 2014.11.18
 */
class UserPasswordTest extends DurianTestCase
{
    /**
     * 基本測試
     */
    public function testExecute()
    {
        $now = new \DateTime('now');

        $user = new User();
        $user->setUsername('user');

        $passwd= new UserPassword($user);

        $this->assertEquals($user, $passwd->getUser());

        $passwd->setHash('123');
        $this->assertEquals('123', $passwd->getHash());

        $passwd->setModifiedAt($now);
        $this->assertEquals($now, $passwd->getModifiedAt());

        $passwd->setExpireAt($now);
        $this->assertEquals($now, $passwd->getExpireAt());

        $passwd->setReset(true);
        $this->assertTrue($passwd->isReset());

        $passwd->addErrNum();
        $this->assertEquals(1, $passwd->getErrNum());

        $passwd->zeroErrNum();
        $this->assertEquals(0, $passwd->getErrNum());

        $passwd->setOncePassword('1232266');
        $this->assertEquals('1232266', $passwd->getOncePassword());

        $passwd->setUsed(true);
        $this->assertTrue($passwd->isUsed());

        $passwd->setOnceExpireAt($now);
        $this->assertEquals($now, $passwd->getOnceExpireAt());
    }

    /**
     * 測試修改時間是否等於到期時間30天
     */
    public function testModifiedAtIsEqualToExpireAtAfterThirtyDays()
    {
        $user = new User();
        $user->setUsername('user');

        $passwd= new UserPassword($user);

        $modifiedAt = $passwd->getModifiedAt();
        $expireAt = $passwd->getExpireAt();

        $this->assertEquals($expireAt, $modifiedAt->add(new \DateInterval('P30D')));
    }

    /**
     * 測試從刪除使用者密碼備份設定使用者密碼
     */
    public function testSetFromRemoved()
    {
        $user = new User();
        $userPassword = new UserPassword($user);

        $hash = '123';
        $userPassword->setHash($hash);
        $now = new \DateTime();
        $userPassword->setModifiedAt($now);
        $userPassword->setReset(true);
        $userPassword->addErrNum();

        $removedUser = new RemovedUser($user);
        $removedUserPassword = new RemovedUserPassword($removedUser, $userPassword);
        $userPassword2 = new UserPassword($user);

        $userPassword2->setFromRemoved($removedUserPassword);
        $this->assertEquals($hash, $userPassword2->getHash());
        $this->assertEquals($now, $userPassword2->getModifiedAt());
        $this->assertTrue($userPassword2->isReset());
        $this->assertEquals(1, $userPassword2->getErrNum());
    }

    /**
     * 測試從刪除使用者密碼備份設定使用者密碼，但指派錯誤
     */
    public function testSetFromRemovedButNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'UserPassword not belong to this user',
            150010138
        );

        $user1 = new User();
        $user1->setId(1);
        $userPassword1 = new UserPassword($user1);

        $user2 = new User();
        $user2->setId(2);
        $userPassword2 = new UserPassword($user2);

        $removedUser = new RemovedUser($user2);
        $removedUserPassword = new RemovedUserPassword($removedUser, $userPassword2);

        $userPassword1->setFromRemoved($removedUserPassword);
    }
}
