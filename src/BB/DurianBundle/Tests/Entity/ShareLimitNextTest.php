<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Entity\User;

class ShareLimitNextTest extends DurianTestCase
{
    /**
     * 測試同使用者不能新增相同group number的ShareLimitNext
     */
    public function testNewShareLimitNextWouldCheckDuplicate()
    {
        $this->setExpectedException('RuntimeException', 'Duplicate ShareLimitNext', 150010011);

        $parent = new User();

        $user = new User();
        $user->setParent($parent);
        $user->setUsername('user');
        $user->setPassword('pass');
        $user->setAlias('alias');

        new ShareLimit($user, 1);

        new ShareLimitNext($user, 1);
        new ShareLimitNext($user, 1);
    }

    /**
     * 測試需先新增有相同群組編號的ShareLimit
     */
    public function testCanNotAddShareLimitNextIfNoShareLimitWithTheSameGroupNumber()
    {
        $this->setExpectedException('RuntimeException', 'Add the ShareLimit with the same group number first', 150080021);

        $parent = new User();

        $user = new User();
        $user->setParent($parent);
        $user->setUsername('user');
        $user->setPassword('pass');
        $user->setAlias('alias');

        new ShareLimitNext($user, 1);
    }

    /**
     * 測試new ShareLimitNext entity
     */
    public function testNewShareLimitNextEntity()
    {
        $parent = new User();

        $user = new User();
        $user->setParent($parent);
        $user->setUsername('user');
        $user->setPassword('pass');
        $user->setAlias('alias');

        $share = new ShareLimit($user, 2);

        $share->setUpper(50.1);
        $share->setLower(0);
        $share->setParentUpper(50);
        $share->setParentLower(40);

        $share = new ShareLimitNext($user, 2);

        $share->setUpper(50.1);
        $share->setLower(0);
        $share->setParentUpper(50);
        $share->setParentLower(40);

        $this->assertEquals(0, $share->getId());
        $this->assertEquals($user, $share->getUser());
        $this->assertEquals(2, $share->getGroupNum());
        $this->assertequals(50.1, $share->getUpper());
        $this->assertEquals(0, $share->getLower());
        $this->assertEquals(50, $share->getParentUpper());
        $this->assertEquals(40, $share->getParentLower());

        $this->assertNull($share->getParent());
    }

    /**
     * 測試回傳呼叫getParent得到Null
     */
    public function testGetParent()
    {
        $user = new User();
        $share = new ShareLimit($user, 2);
        $share = new ShareLimitNext($user, 2);
        $this->assertNull($share->getParent());
    }
}
