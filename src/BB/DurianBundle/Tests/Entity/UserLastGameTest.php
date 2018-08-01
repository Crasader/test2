<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\UserLastGame;
use BB\DurianBundle\Entity\User;

class UserLastGameTest extends DurianTestCase
{
    /**
     * 基本測試
     */
    public function testBasic()
    {
        $now = new \DateTime('now');
        $user = new User();
        $user->setId(1);

        $userLastGame = new UserLastGame($user);

        $this->assertEquals($user, $userLastGame->getUser());
        $this->assertTrue($userLastGame->isEnabled());
        $this->assertEquals(1, $userLastGame->getLastGameCode());
        $this->assertNull($userLastGame->getModifiedAt());

        $userLastGame->disable();
        $this->assertFalse($userLastGame->isEnabled());

        $userLastGame->setLastGameCode(4);
        $this->assertEquals(4, $userLastGame->getLastGameCode());

        $userLastGame->setModifiedAt($now);
        $this->assertNotNull($userLastGame->getModifiedAt());

        $userLastGame->enable();
        $array = $userLastGame->toArray();

        $this->assertEquals(1, $array['user_id']);
        $this->assertTrue($userLastGame->isEnabled());
        $this->assertEquals(4, $array['last_game_code']);
    }
}
