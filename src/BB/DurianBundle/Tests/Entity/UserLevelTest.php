<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserLevel;

class UserLevelTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $userId = 123;
        $level = 1;

        $user = new User();
        $user->setId($userId);

        $userLevel = new UserLevel($user, $level);

        $ulArray = $userLevel->toArray();

        $this->assertFalse($ulArray['locked']);
        $this->assertEquals($userId, $ulArray['user_id']);
        $this->assertEquals($level, $ulArray['level_id']);
        $this->assertEquals(0, $ulArray['last_level_id']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $user = new User();
        $userLevel = new UserLevel($user, 1);

        $this->assertEquals($user, $userLevel->getUser());

        $this->assertEquals(1, $userLevel->getLevelId());

        $level = 2;
        $lastLevel = $userLevel->getLevelId();

        $userLevel->setLevelId($level);
        $userLevel->locked();

        $this->assertTrue($userLevel->isLocked());
        $this->assertEquals($user, $userLevel->getUser());
        $this->assertEquals($level, $userLevel->getLevelId());
        $this->assertEquals($lastLevel, $userLevel->getLastLevelId());

        $userLevel->unLocked();
        $this->assertFalse($userLevel->isLocked());
    }
}
