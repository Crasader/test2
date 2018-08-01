<?php

namespace BB\DurianBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\User;

class ShareLimitTest extends DurianTestCase
{
    /**
     * 測試不能新增相同group number的ShareLimit
     */
    public function testNewShareLimitWouldCheckIfDuplicateShareLimit()
    {
        $this->setExpectedException('RuntimeException', 'Duplicate ShareLimit', 150010010);

        $parent = new User();

        $user = new User();
        $user->setParent($parent);
        $user->setUsername('user');
        $user->setPassword('pass');
        $user->setAlias('alias');

        $share = new ShareLimit($user, 1);
        $share = new ShareLimit($user, 1);
    }

    /**
     * 測試new ShareLimit entity
     */
    public function testNewShareLimitEntity()
    {
        $parentUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['isSub', 'isEnabled', 'getAllParents'])
            ->getMock();

        $parentUser->expects($this->any())
            ->method('isSub')
            ->will($this->returnValue(false));

        $parentUser->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $parentUser->expects($this->any())
            ->method('getAllParents')
            ->will($this->returnValue(new ArrayCollection));

        $user = new User();
        $user->setParent($parentUser);

        $share = new ShareLimit($user, 2);

        $share->setUpper(50.1);
        $share->setLower(0);
        $share->setParentUpper(50);
        $share->setParentLower(40);

        $this->assertEquals($user, $share->getUser());
        $this->assertTrue($share->hasParent());

        $array = $share->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertEquals(0, $array['user_id']);
        $this->assertEquals(2, $array['group']);
        $this->assertEquals(50.1, $array['upper']);
        $this->assertEquals(0, $array['lower']);
        $this->assertEquals(50, $array['parent_upper']);
        $this->assertEquals(40, $array['parent_lower']);
    }

    /**
     * 測試getParent
     */
    public function testGetParent()
    {
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setPassword('pass');
        $user1->setAlias('alias');

        $user2 = new User();
        $user2->setParent($user1);
        $user2->setUsername('user2');
        $user2->setPassword('pass');
        $user2->setAlias('alias');

        $share1 = new ShareLimit($user1, 1);
        $share2 = new ShareLimit($user2, 1);

        $this->assertNull($share1->getParent());
        $this->assertEquals($share1, $share2->getParent());
    }
}
