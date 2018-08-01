<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\ChatRoom;

class ChatRoomTest extends DurianTestCase
{
    /**
     * 測試基本功能
     */
    public function testBasic()
    {
        $user = new User();
        $chatRoom = new ChatRoom($user);

        $result = $chatRoom->toArray();
        $this->assertEquals($user->getId(), $result['user_id']);
        $this->assertTrue($result['readable']);
        $this->assertTrue($result['writable']);
        $this->assertNull($result['ban_at']);

        $banAt = new \DateTime('99110303112211');
        $chatRoom->setReadable(false);
        $chatRoom->setWritable(false);
        $chatRoom->setBanAt($banAt);

        $result = $chatRoom->toArray();
        $this->assertFalse($result['readable']);
        $this->assertFalse($result['writable']);
        $this->assertEquals($result['ban_at'], $banAt->format(\DateTime::ISO8601));
    }
}
