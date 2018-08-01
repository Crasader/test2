<?php

namespace BB\DurianBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\RemovedUser;

class UserTest extends DurianTestCase
{
    /**
     * 測試new User產生的物件設定值和預設值都正確
     */
    public function testNewUser()
    {
        $user = new User();

        // 基本資料檢查
        $this->assertNull($user->getParent());
        $this->assertNull($user->getUsername());
        $this->assertNull($user->getPassword());
        $this->assertNull($user->getAlias());
        $this->assertNull($user->getDomain());
        $this->assertNull($user->getLastLogin());
        $this->assertNull($user->getLastBank());

        $this->assertEquals(156, $user->getCurrency());
        $this->assertFalse($user->isRent());
        $this->assertNull($user->getParent());


        // 建立時間和更新時間相同
        $createdAt  = $user->getCreatedAt();
        $modifiedAt = $user->getModifiedAt();
        $this->assertEquals($createdAt, $modifiedAt);

        // 密碼逾期比建立時間多30天
        $passwordExpireAt = $createdAt->add(new \DateInterval('P30D'));
        $this->assertEquals($user->getPasswordExpireAt(), $passwordExpireAt);

        $userid = '8645123';
        $user->setId($userid);
        $this->assertEquals($userid, $user->getId());

        // 已有id不可重複設定
        $user->setId('5438');
        $this->assertEquals($userid, $user->getId());

        $username = 'abcdefg';
        $user->setUsername($username);

        $passwd = 'abc123';
        $user->setPassword($passwd);
        $this->assertEquals($passwd, $user->getPassword());

        $alias = 'abcdefg';
        $user->setAlias($alias);

        $domain = 101;
        $user->setDomain($domain);

        $date = new \Datetime('now');
        $user->setLastLogin($date);
        $this->assertEquals($date, $user->getLastLogin());

        $bankId = 1;
        $user->setLastBank($bankId);

        $currency = 344; // HKD
        $user->setCurrency($currency);
        $this->assertEquals($currency, $user->getCurrency());

        $role = 6;
        $user->setRole($role);

        $user->setModifiedAt($date);
        $this->assertEquals($date, $user->getModifiedAt());

        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->disableOriginalConstructor()
            ->getMock();

        $user->addCredit($credit);
        $this->assertCount(1, $user->getCredits());

        $shareLimit  = $this->getMockBuilder('BB\DurianBundle\Entity\ShareLimit')
            ->disableOriginalConstructor()
            ->getMock();

        $user->addShareLimit($shareLimit);
        $this->assertCount(1, $user->getShareLimits());

        $shareLimitNext = $this->getMockBuilder('BB\DurianBundle\Entity\ShareLimitNext')
            ->disableOriginalConstructor()
            ->getMock();

        $user->addShareLimitNext($shareLimitNext);
        $this->assertCount(1, $user->getShareLimitNexts());

        $this->assertEquals(0, $user->getSubSizeFlag());

        $array = $user->toArray();

        $this->assertEquals($userid, $array['id']);
        $this->assertEquals($username, $array['username']);
        $this->assertEquals($domain, $array['domain']);
        $this->assertEquals($alias, $array['alias']);
        $this->assertFalse($array['sub']);
        $this->assertTrue($array['enable']);
        $this->assertFalse($array['block']);
        $this->assertFalse($array['bankrupt']);
        $this->assertFalse($array['test']);
        $this->assertEquals(0, $array['size']);
        $this->assertEquals(0, $array['err_num']);
        $this->assertEquals('HKD', $array['currency']);
        $this->assertEquals($createdAt, new \DateTime($array['created_at']));
        $this->assertEquals($date, new \DateTime($array['modified_at']));
        $this->assertEquals($date, new \DateTime($array['last_login']));
        $this->assertFalse($array['password_reset']);
        $this->assertEquals($bankId, $array['last_bank']);
        $this->assertEquals($role, $array['role']);

        $passwordExpireAt = $user->getPasswordExpireAt()->format(\DateTime::ISO8601);
        $this->assertEquals($passwordExpireAt, $array['password_expire_at']);
    }

    /**
     * 測試設定及讀取上層行為是否正常
     */
    public function testHasParentAndGetParentAndGetAllParents()
    {

        $user1 = new User();

        $user2 = new User();
        $user2->setParent($user1);

        $user3 = new User();
        $user3->setParent($user2);


        $this->assertFalse($user1->hasParent());
        $this->assertTrue($user2->hasParent());
        $this->assertTrue($user3->hasParent());

        $this->assertNull($user1->getParent());
        $this->assertEquals($user1, $user2->getParent());
        $this->assertEquals($user2, $user3->getParent());

        $parents = $user1->getAllParents();
        $this->assertEquals(0, count($parents));
        $this->assertEquals(0, count($user1->getAllParentsId()));
        $this->assertNull($parents[0]);

        $parents = $user2->getAllparents();
        $this->assertEquals(1, count($parents));
        $this->assertEquals(1, count($user2->getAllParentsId()));
        $this->assertEquals($user1, $parents[0]);
        $this->assertNull($parents[1]);

        $parents = $user3->getAllParents();
        $this->assertEquals(2, count($parents));
        $this->assertEquals(2, count($user3->getAllParentsId()));
        $this->assertEquals($user2, $parents[0]);
        $this->assertEquals($user1, $parents[1]);
        $this->assertNull($parents[2]);
    }

    /**
     * 測試停用、凍結、停權、測試帳號、隱藏測試帳號、租卡體系、重設密碼等功能
     */
    public function testEnableBlockTestBankruptRentAndPasswordReset()
    {
        $parent = new User();

        $user = new User();
        $user->setParent($parent);
        $user->setUsername('user');

        $user->setParent(new User());

        $this->assertTrue($user->isEnabled());
        $user->disable();
        $this->assertFalse($user->isEnabled());
        $user->enable();
        $this->assertTrue($user->isEnabled());

        $this->assertFalse($user->isBlock());
        $user->block();
        $this->assertTrue($user->isBlock());
        $user->unblock();
        $this->assertFalse($user->isBlock());

        $this->assertFalse($user->isBankrupt());
        $user->setBankrupt(true);
        $this->assertTrue($user->isBankrupt());
        $user->setBankrupt(false);
        $this->assertFalse($user->isBankrupt());

        $this->assertFalse($user->isTest());
        $user->setTest(true);
        $this->assertTrue($user->isTest());
        $user->setTest(false);
        $this->assertFalse($user->isTest());

        $this->assertFalse($user->isHiddenTest());
        $user->setHiddenTest(true);
        $this->assertTrue($user->isHiddenTest());
        $user->setHiddenTest(false);
        $this->assertFalse($user->isHiddenTest());

        $this->assertFalse($user->isRent());
        $user->setRent(true);
        $this->assertTrue($user->isRent());
        $user->setRent(false);
        $this->assertFalse($user->isRent());

        $this->assertFalse($user->isPasswordReset());
        $user->setPasswordReset(true);
        $this->assertTrue($user->isPasswordReset());
        $user->setPasswordReset(false);
        $this->assertFalse($user->isPasswordReset());
    }

    /**
     * 測試啟用時上層不能為停用
     */
    public function testEnableWhenParentDisable()
    {
        $this->setExpectedException('RuntimeException', 'Can not enable when parent is disable', 150010046);

        $parent = new User();

        // 上層為空時，可任意停啟用。
        $parent->disable();
        $parent->enable();

        $user = new User();
        $user->setParent($parent);

        $parent->disable();
        $user->enable();
    }

    /**
     * 測試父層不能是子帳號
     */
    public function testParentCanNotBeSubUser()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Sub user can not be parent',
            150010001
        );

        $parent = new User();
        $parent->setUsername('parent');
        $parent->setSub(true);

        $user = new User();
        $user->setParent($parent);
        $user->setUsername('user');
    }

    /**
     * 測試自身的上層包含自身(遞迴關係)
     */
    public function testParentRecursion()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'nheritance loop detected',
            150010003
        );

        $user = new User();

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

        $result = new ArrayCollection;
        $result[] = $user;

        $parentUser->expects($this->any())
            ->method('getAllParents')
            ->will($this->returnValue($result));

        $user->setParent($parentUser);
    }

    /**
     * 測試新增帳號時父層如已停用會出現例外
     */
    public function testParentUserCanNotBeDisabledWhenCreateNewUser()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'User disabled can not be parent',
            150010002
        );

        $parent = new User();
        $parent->setUsername('parent');

        $parent->disable();

        $user = new User();
        $user->setParent($parent);
        $user->setUsername('user');
    }

    /**
     * 測試當帳號沒有Cash物件時getCash將回傳null
     */
    public function testGetCashWillReturnNullWhenUserDoNotHaveAnyCashEntity()
    {
        $parent = new User();

        $user = new User();
        $user->setParent($parent);

        $this->assertNull($user->getCash());
    }

    /**
     * 測試user能否正確增添Cash, Credit, Card物件
     */
    public function testAddOneToManyRelation()
    {
        $currency = 156; // CNY
        $parent = new User();

        $user = new User();
        $user->setParent($parent);

        $cash    = new Cash($user, $currency);
        $credit  = new Credit($user, 2);
        $card    = new Card($user);

        $this->assertEquals($cash, $user->getCash());
        $this->assertEquals($credit, $user->getCredit(2));
        $this->assertEquals($card, $user->getCard());
    }

    /**
     * 測試isAncestor功能正常
     */
    public function testIsAncestor()
    {
        $parent = new User();

        $user1 = new User();
        $user1->setParent($parent);

        $user2 = new User();
        $user2->setParent($user1);

        $user3 = new User();
        $user3->setParent($user2);

        $user4 = new User();
        $user4->setParent($user3);

        $this->assertFalse($user1->isAncestor($user1));
        $this->assertTrue($user2->isAncestor($user1));
        $this->assertTrue($user3->isAncestor($user1));
        $this->assertTrue($user4->isAncestor($user1));

        $this->assertFalse($user1->isAncestor($user2));
        $this->assertFalse($user2->isAncestor($user2));
        $this->assertTrue($user3->isAncestor($user2));
        $this->assertTrue($user4->isAncestor($user2));

        $this->assertFalse($user1->isAncestor($user4));
        $this->assertFalse($user2->isAncestor($user4));
        $this->assertFalse($user3->isAncestor($user4));
        $this->assertFalse($user4->isAncestor($user4));
    }

    /**
     * 測試帳號更改密碼後是否正確更新密碼到期時間
     */
    public function testChangePasswordWillAlsoChangePasswordExpireAt()
    {
        $now = new \DateTime('now');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['setPasswordExpireAt'])
            ->getMock();

        $user->expects($this->once())
            ->method('setPasswordExpireAt')
            ->with($this->greaterThanOrEqual($now->add(new \DateInterval('P30D'))));

        $user->setPassword('abc123');
    }

    /**
     * 測試使用者不能新增相同群組編號的Credit物件
     */
    public function testCanNotAddCreditEntityWithTheSameGroupNum()
    {
        $this->setExpectedException('RuntimeException', 'Duplicate Credit', 150010006);

        $parent = new User();

        $user = new User();
        $user->setParent($parent);
        $user->setUsername('user');

        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->setConstructorArgs([$user, 1])
            ->setMethods(['getGroupNum'])
            ->getMock();

        $credit->expects($this->any())
            ->method('getGroupNum')
            ->will($this->returnValue(1));

        $user->addCredit($credit);
        $user->addCredit($credit);
    }

    /**
     * 測試currency欄位不能是null
     */
    public function testNewUserWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency can not be null',
            150010041
        );

        $user = new User();

        $user->setCurrency('');
    }

    /**
     * 測試登入錯誤計次
     */
    public function testErrNum()
    {
        $user = new User();

        $this->assertEquals(0, $user->getErrNum());

        $user->addErrNum();

        $this->assertEquals(1, $user->getErrNum());

        $user->ZeroErrNum();

        $this->assertEquals(0, $user->getErrNum());
    }

    /**
     * 測試user重複加入租卡
     */
    public function testAddCardExists()
    {
        $this->setExpectedException('RuntimeException', 'Card entity for the user already exists', 150010009);

        $user = new User();
        $card   = new \BB\DurianBundle\Entity\Card($user);
        $user->addCard($card);
        $user->addCard($card);
    }

    /**
     * 測試從刪除使用者備份設定使用者資料
     */
    public function testSetFromRemoved()
    {
        $user = new User();

        $user->setId(5438);
        $user->setUsername('abcdefg');
        $user->setPassword('abc123');
        $user->setAlias('abcdefg');
        $user->setDomain(101);
        $user->setLastBank(1);
        $user->setCurrency(344);
        $user->setRole(6);

        $removedUser = new RemovedUser($user);

        $user2 = new User();
        $user2->setFromRemoved($removedUser);
        $this->assertEquals($user->toArray(), $user2->toArray());

        //測試當passwordExpireAt的日期為0000-00-00時，是否會增加30天
        $removedUser2 = $this->getMockBuilder('BB\DurianBundle\Entity\RemovedUser')
            ->setConstructorArgs([$user])
            ->getMock();

        $dateTime = new \DateTime('0000-00-00 00:00:00');
        $now = new \DateTime('2017-09-01 16:00:00');
        $addTime = new \DateTime($now->format('Y-m-d H:i:s'));
        $addTime = $addTime->add(new \DateInterval('P30D'));

        $removedUser2->expects($this->any())
            ->method('getPasswordExpireAt')
            ->willReturn($dateTime);
        $removedUser2->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn($now);

        $user3 = new User();
        $user3->setFromRemoved($removedUser2);

        $this->assertEquals($addTime, $user3->getPasswordExpireAt());
    }

    /**
     * 測試轉字串
     */
    public function testToString()
    {
        $user = new User();
        $user->setId(5438);
        $string = $user->__toString();

        $this->assertInternalType('string', $string);
        $this->assertEquals('5438', $string);
    }
}
