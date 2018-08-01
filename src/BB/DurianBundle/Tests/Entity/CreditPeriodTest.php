<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\Entity\User;

class CreditPeriodTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $at = new \DateTime('1999-03-24');
        $groupNum = 1;
        $user = new User();
        $credit = new Credit($user, $groupNum);
        $entry = new CreditPeriod($credit, $at);

        $entry->setVersion(666);
        $entryArray = $entry->toArray();

        $this->assertEquals(0, $entry->getId());
        $this->assertEquals(0, $entry->getUserId());
        $this->assertEquals($groupNum, $entry->getGroupNum());
        $this->assertEquals($credit, $entry->getCredit());
        $this->assertEquals(0, $entry->getAmount());
        $this->assertTrue($entry->getAt() instanceof \DateTime);
        $this->assertEquals(666, $entry->getVersion());

        $this->assertEquals('1999-03-24', $entryArray['at']->format('Y-m-d'));
        $this->assertEquals(0, $entryArray['user_id']);
        $this->assertEquals($groupNum, $entryArray['group']);
        $this->assertEquals(0, $entryArray['amount']);
        $this->assertEquals(666, $entryArray['version']);
    }

    /**
     * 測試CreditPeriod是否正確建立 & 基本的get.set function是否正確
     */
    public function testNewCreditPeriodAndCheckDefaultValue()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();

        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->disableOriginalConstructor()
            ->setMethods(['addCreditPeriod', 'getUser'])
            ->getMock();
        $credit->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $at = new \DateTime('2011-01-01');

        $period = new CreditPeriod($credit, $at);

        $this->assertEquals(0, $period->getAmount());
        $this->assertEquals($credit, $period->getCredit());
        $this->assertEquals($at, $period->getAt());
    }

    /**
     * 測試是否正確建立中午更新Group CreditPeriod
     */
    public function testNewCreditPeriodByNoonUpdateGroup()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();

        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->disableOriginalConstructor()
            ->setMethods(['addCreditPeriod', 'getUser', 'getGroupNum'])
            ->getMock();
        $credit->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));
        $credit->expects($this->any())
            ->method('getGroupNum')
            ->will($this->returnValue(3));

        $at = new \DateTime('2011-01-01 12:00:00');

        $period = new CreditPeriod($credit, $at);

        $this->assertEquals(0, $period->getAmount());
        $this->assertEquals($credit, $period->getCredit());
        $this->assertEquals($at, $period->getAt());
    }

    /**
     * 測試addAmount
     */
    public function testAddAmount()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();

        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->disableOriginalConstructor()
            ->setMethods(['addCreditPeriod', 'getUser'])
            ->getMock();
        $credit->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $at = new \DateTime('2011-01-01');

        $period = new CreditPeriod($credit, $at);

        $this->assertEquals(0, $period->getAmount());

        $period->addAmount(500);
        $this->assertEquals(500, $period->getAmount());

        $period->addAmount(-100);
        $this->assertEquals(400, $period->getAmount());
    }

    /**
     * 測試如amount為負會出現例外
     */
    public function testAmountOfPeriodCanNotBeNegative()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Amount of period can not be negative',
            150060008
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();
        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->disableOriginalConstructor()
            ->setMethods(array('addCreditPeriod', 'getUser'))
            ->getMock();
        $credit->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $at = new \DateTime('2011-01-01');

        $period = new CreditPeriod($credit, $at);

        $period->addAmount(-1);
    }

    /**
     * 測試輸入金額超過最大值
     */
    public function testAddAmountExceedsTheMaxValueOfAmountWillThrowException()
    {
        $this->setExpectedException('RangeException', 'Amount exceed the MAX value', 150060007);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();
        $credit = $this->getMockBuilder('BB\DurianBundle\Entity\Credit')
            ->disableOriginalConstructor()
            ->setMethods(array('addCreditPeriod', 'getUser'))
            ->getMock();
        $credit->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $at = new \DateTime('2011-01-01');

        $period = new CreditPeriod($credit, $at);

        $period->addAmount(10000000000 + 1);
    }

    /**
     * 測試記錄額度最小有效位數
     * 目前到小數點下4位
     */
    public function testAddAmountExceedsTheNumberOfDecimalPlacesWillThrowException()
    {
        $this->setExpectedException('RangeException', 'The decimal digit of amount exceeds limitation', 150060037);

        $period = $this->getMockBuilder('BB\DurianBundle\Entity\CreditPeriod')
                ->disableOriginalConstructor()
                ->setMethods(array('getId'))
                ->getMock();

        $period->addAmount(9.99999);
    }
}
