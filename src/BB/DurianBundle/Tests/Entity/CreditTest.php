<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedCredit;
use BB\DurianBundle\Entity\RemovedUser;

class CreditTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $groupNum = 1;
        $line = 100;

        $credit = new Credit($user, $groupNum);

        $this->assertEquals(0, $credit->getId());
        $this->assertEquals($user, $credit->getUser());
        $this->assertTrue($credit->isEnable());

        $this->assertEquals(0, $credit->getTotalLine());

        $credit->setLine($line);
        $this->assertEquals($line, $credit->getLine());

        $credit->setLastEntryAt(20150101235959);
        $this->assertEquals(20150101235959, $credit->getLastEntryAt());

        $array = $credit->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertEquals(0, $array['user_id']);
        $this->assertEquals($groupNum, $array['group']);
        $this->assertTrue($array['enable']);
        $this->assertEquals($line, $array['line']);
        $this->assertEquals($line, $array['balance']);

        $credit = new Credit($user, 3);
        $this->assertEquals(0, $credit->getBalanceAt(new \DateTime()));
    }

    /**
     * 測試getBalance會由getBalanceAt帶入現在時間取得餘額
     */
    public function testGetBalanceWillTheSameWithGetBalanceAtNow()
    {
        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->setMethods(['getBalanceAt'])
            ->disableOriginalConstructor()
            ->getMock();

        $credit->expects($this->once())
            ->method('getBalanceAt')
            ->with($this->equalTo(new \DateTime('now')))
            ->will($this->returnValue(1000));

        $this->assertEquals(1000, $credit->getBalance());
    }

    /**
     * 測試是否啟用
     */
    public function testIsEnabled()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $credit = new Credit($user, 1);
        $this->assertTrue($credit->isEnable());

        $credit->disable();
        $this->assertFalse($credit->isEnable());

        $credit->enable();
        $this->assertTrue($credit->isEnable());

    }

    /**
     * 測試依照日期取餘額是否正確
     */
    public function testGetBalanceAt()
    {
        $collection = new ArrayCollection();

        $builder = $this->getMockBuilder('BB\DurianBundle\Entity\CreditPeriod')
                ->disableOriginalConstructor();

        $period = $builder->getMock();

        $period->expects($this->any())
                ->method('getAt')
                ->will($this->returnValue(new \DateTime('2011-01-01')));

        $period->expects($this->any())
                ->method('getAmount')
                ->will($this->returnValue(100));

        $collection->add($period);

        $period = $builder->getMock();

        $period->expects($this->any())
                ->method('getAt')
                ->will($this->returnValue(new \DateTime('2011-01-03')));

        $period->expects($this->any())
                ->method('getAmount')
                ->will($this->returnValue(10));

        $collection->add($period);

        $user = new User();
        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->setConstructorArgs([$user, 1])
            ->setMethods(['getPeriods', 'getLine', 'getTotalLine'])
            ->getMock();

        $credit->expects($this->any())
                ->method('getPeriods')
                ->will($this->returnValue($collection));

        $credit->expects($this->any())
                ->method('getLine')
                ->will($this->returnValue(500));

        $credit->expects($this->any())
                ->method('getTotalLine')
                ->will($this->returnValue(80));

        $this->assertEquals(
            310,
            $credit->getBalanceAt(new \DateTime('2010-12-31'))
        );
        $this->assertEquals(
            310,
            $credit->getBalanceAt(new \DateTime('2011-01-01'))
        );
        $this->assertEquals(
            410,
            $credit->getBalanceAt(new \DateTime('2011-01-02'))
        );
        $this->assertEquals(
            410,
            $credit->getBalanceAt(new \DateTime('2011-01-03'))
        );
        $this->assertEquals(
            420,
            $credit->getBalanceAt(new \DateTime('2011-01-04'))
        );
    }

    /**
     * 測試Credit物件的line與totoalLine預設等於零
     */
    public function testLineAndTotalLineSetZeroAsDefault()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $credit = new Credit($user, 1);

        $this->assertEquals(0, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());
    }

    /**
     * 測試getParent
     */
    public function testGetParent()
    {
        $parentCredit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
                ->disableOriginalConstructor()
                ->getMock();

        $parent = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                ->disableOriginalConstructor()
                ->getMock();

        $parent->expects($this->any())
                ->method('getCredit')
                ->with($this->equalTo(1))
                ->will($this->returnValue($parentCredit));

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                ->disableOriginalConstructor()
                ->getMock();

        $user->expects($this->any())
                ->method('getParent')
                ->will($this->returnValue($parent));

        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
                ->disableOriginalConstructor()
                ->setMethods(array('getGroupNum', 'getUser'))
                ->getMock();

        $credit->expects($this->any())
                ->method('getGroupNum')
                ->will($this->returnValue(1));

        $credit->expects($this->any())
                ->method('getUser')
                ->will($this->returnValue($user));

        $this->assertEquals($parentCredit, $credit->getParent());
    }

    /**
     * 測試從被移除的信用額度設定信用額度ID
     */
    public function testSetIdFromRemovedCredit()
    {
        $user1 = new User();
        $user1->setId(1);
        $credit1 = new Credit($user1, 3);
        $reflection = new \ReflectionClass($credit1);
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($credit1, 2);
        $this->assertEquals(2, $credit1->getId());

        $removedUser = new RemovedUser($user1);
        $removedCredit = new RemovedCredit($removedUser, $credit1);

        $user2 = new User();
        $user2->setId(1);

        $credit2 = new Credit($user2, 3);
        $credit2->setId($removedCredit);
        $this->assertEquals($credit1->getId(), $credit2->getId());
    }

    /**
     * 測試從被移除的信用額度設定信用額度ID，但指派錯誤
     */
    public function testSetIdFromRemovedCreditButNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Removed credit not belong to this user',
            150010160
        );

        $user1 = new User();
        $user1->setId(1);
        $credit1 = new Credit($user1, 3);

        $removedUser = new RemovedUser($user1);
        $removedCredit = new RemovedCredit($removedUser, $credit1);

        $user2 = new User();
        $user2->setId(2);

        $credit2 = new Credit($user2, 3);
        $credit2->setId($removedCredit);
    }
}
