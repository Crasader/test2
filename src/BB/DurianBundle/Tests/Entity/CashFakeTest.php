<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashFake;

class CashFakeTest extends DurianTestCase
{
    /**
     * 測試新增的CashFake物件
     */
    public function testNewCashFake()
    {
        $currency = 156; // CNY

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getParent'])
            ->getMock();

        $parentUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getCashFake'])
            ->getMock();

        $parentFake = new CashFake($parentUser, $currency);

        $parentUser->expects($this->any())
            ->method('getCashFake')
            ->will($this->returnValue($parentFake));

        $user->expects($this->at(0))
            ->method('getParent')
            ->will($this->returnValue(null));

        $user->expects($this->at(1))
            ->method('getParent')
            ->will($this->returnValue($parentUser));

        $fake = new CashFake($user, $currency);

        $this->assertEquals($currency, $fake->getCurrency());
        $this->assertEquals($user, $fake->getUser());
        $this->assertEquals(0, $fake->getBalance());

        $fake->addPreSub(50);
        $fake->addPreAdd(50);

        $this->assertNull($fake->getParent());
        $this->assertEquals($parentFake, $fake->getParent());

        $array = $fake->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertNull($array['user_id']);
        $this->assertEquals(-50, $array['balance']);
        $this->assertEquals(50, $array['pre_sub']);
        $this->assertEquals(50, $array['pre_add']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertTrue($array['enable']);
        $this->assertNull($array['last_entry_at']);

        $fake->setLastEntryAt(20150817121233);
        $fake->setVersion(2);
        $this->assertEquals(20150817121233, $fake->getLastEntryAt());
        $this->assertEquals(2, $fake->getVersion());
    }

    /**
     * 測試如果傳入的使用者已經擁有CashFake物件，
     * CashFake物件不能新增，
     */
    public function testCashFakeEntityCanNotBeNewIfTheUserAlreadyHasHadOne()
    {
        $this->setExpectedException('RuntimeException', 'CashFake for the user already exists', 150010008);

        $cashFake = $this->getMockBuilder('BB\DurianBundle\Entity\CashFake')
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(array('getCashFake'))
            ->getMock();

        $user->expects($this->any())
            ->method('getCashFake')
            ->will($this->returnValue($cashFake));

        $currency = 156; // CNY

        new CashFake($user, $currency);
    }

    /**
     * 測試是否啟用
     */
    public function testIsEnabled()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $currency = 156; // CNY
        $cashFake = new CashFake($user, $currency);
        $this->assertTrue($cashFake->isEnable());

        $cashFake->disable();
        $this->assertFalse($cashFake->isEnable());

        $cashFake->enable();
        $this->assertTrue($cashFake->isEnable());
    }

    /**
     * 測試設定餘額是否為負數
     */
    public function testNegative()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $cashFake = new CashFake($user, 156);

        $this->assertFalse($cashFake->getNegative());

        $cashFake->setNegative(true);

        $this->assertTrue($cashFake->getNegative());
    }
}
