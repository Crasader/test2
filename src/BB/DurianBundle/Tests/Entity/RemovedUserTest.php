<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\RemovedCash;
use BB\DurianBundle\Entity\RemovedCashFake;
use BB\DurianBundle\Entity\RemovedCredit;
use BB\DurianBundle\Entity\RemovedCard;

class RemovedUserTest extends DurianTestCase
{
    /**
     * 測試基本的功能
     */
    public function testRemovedUserBasic()
    {
        $parent = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                       ->setMethods(array('getId'))
                       ->getMock();

        $parent->expects($this->any())
               ->method('getId')
               ->will($this->returnValue(2));

        $username = 'user';
        $passwd   = 'pass';
        $alias    = 'alias';
        $now      = new \DateTime('now');
        $bankId   = 1;
        $userId   = 11;

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                     ->setMethods(array('getId'))
                     ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($userId));

        $user->setParent($parent);
        $user->setUsername($username);
        $user->setPassword($passwd);
        $user->setAlias($alias);
        $user->setDomain(2);
        $user->setLastLogin($now);
        $user->setLastBank($bankId);
        $user->setCreatedAt($now);
        $user->setRole(7);
        $user->addSize();
        $user->addErrNum();
        $user->setHiddenTest(true);

        $rmUser = new RemovedUser($user);
        $rmUser->setModifiedAt($now);
        $rmUser->setCreatedAt($now);
        $rmUser->setRole(6);

        $this->assertEquals(2, $rmUser->getParentId());
        $this->assertEquals(156, $rmUser->getCurrency());

        $this->assertFalse($rmUser->isRent());

        $this->assertEquals($now, $rmUser->getCreatedAt());
        $this->assertEquals($now, $rmUser->getModifiedAt());
        $this->assertEquals($now, $rmUser->getLastLogin());

        $this->assertEquals($passwd, $rmUser->getPassword());
        $this->assertEquals($rmUser->getPasswordExpireAt(), $user->getPasswordExpireAt());

        $array = $rmUser->toArray();

        $this->assertEquals($userId, $array['userId']);
        $this->assertEquals($username, $array['username']);
        $this->assertEquals(2, $array['domain']);
        $this->assertEquals($alias, $array['alias']);
        $this->assertFalse($array['sub']);
        $this->assertTrue($array['enable']);
        $this->assertFalse($array['block']);
        $this->assertFalse($array['bankrupt']);
        $this->assertEquals($rmUser->getPasswordExpireAt(), new \DateTime($array['password_expire_at']));
        $this->assertFalse($array['password_reset']);
        $this->assertFalse($array['test']);
        $this->assertEquals(1, $array['size']);
        $this->assertEquals(1, $array['err_num']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals($now, new \DateTime($array['created_at']));
        $this->assertEquals($now, new \DateTime($array['modified_at']));
        $this->assertEquals($now, new \DateTime($array['last_login']));
        $this->assertEquals($bankId, $array['last_bank']);
        $this->assertEquals(6, $array['role']);
        $this->assertTrue($array['hidden_test']);

        // 測試當有使用者的密碼逾期時間為0000-00-00 00:00:00，則回傳原預設值
        $zeroPwdExpireAtUser = clone $user;
        $zeroPwdExpireAtUser->setPasswordExpireAt(new \DateTime('0000-00-00 00:00:00'));

        $rmZeroPwdExpireAtUser = new RemovedUser($zeroPwdExpireAtUser);

        $this->assertEquals($rmZeroPwdExpireAtUser->getPasswordExpireAt(), $now->add(new \DateInterval('P30D')));
    }

    /**
     * 測試能否增添與取得Cash, CashFake, Credit, Card物件
     */
    public function testOneToManyRelation()
    {
        $currency = 156;

        $user = new User();
        $cash = new Cash($user, $currency);
        $cashFake = new CashFake($user, $currency);
        $credit = new Credit($user, 2);
        $card = new Card($user);

        $removedUser = new RemovedUser($user);
        $removedCash = new RemovedCash($removedUser, $cash);
        $removedCashFake = new RemovedCashFake($removedUser, $cashFake);
        $removedCredit = new RemovedCredit($removedUser, $credit);
        $removedCard = new RemovedCard($removedUser, $card);

        $this->assertEquals($removedCash, $removedUser->getRemovedCash());
        $this->assertEquals($removedCashFake, $removedUser->getRemovedCashFake());
        $this->assertEquals($removedCredit, $removedUser->getRemovedCredits()[2]);
        $this->assertEquals($removedCard, $removedUser->getRemovedCard());
    }
}
