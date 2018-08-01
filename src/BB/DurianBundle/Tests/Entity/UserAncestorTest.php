<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserAncestor;

class UserAncestorTest extends DurianTestCase
{
    /**
     * 測試UserAncestor
     */
    public function testNewUserAncestor()
    {
        $ancestor = new User();
        $user     = new User();
        $depth    = 1;
        $userAncestor = new UserAncestor($user, $ancestor, $depth);

        $this->assertEquals($user, $userAncestor->getUser());
        $this->assertEquals($ancestor, $userAncestor->getAncestor());
        $this->assertEquals($depth, $userAncestor->getDepth());
    }
}
