<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Level;

class LevelTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $level = new Level(123, '第一層', 1, 1);
        $levelArray = $level->toArray();

        $this->assertEquals(123, $levelArray['domain']);
        $this->assertEquals(1, $levelArray['order_strategy']);
        $this->assertEquals(1, $levelArray['order_id']);
        $this->assertEquals('第一層', $levelArray['alias']);
        $this->assertNull($levelArray['created_at_start']);
        $this->assertNull($levelArray['created_at_end']);
        $this->assertEquals(0, $levelArray['deposit_count']);
        $this->assertEquals(0, $levelArray['deposit_total']);
        $this->assertEquals(0, $levelArray['deposit_max']);
        $this->assertEquals(0, $levelArray['withdraw_count']);
        $this->assertEquals(0, $levelArray['withdraw_total']);
        $this->assertEquals(0, $levelArray['user_count']);
        $this->assertEquals('', $levelArray['memo']);

        // 測試使用者建立時間的條件起始值和結束值不為null
        $startTime = new \DateTime('2015-03-01 00:00:00');
        $endTime = new \DateTime('2015-03-10 00:00:00');

        $level->setCreatedAtStart($startTime);
        $level->setCreatedAtEnd($endTime);

        $levelArray = $level->toArray();

        $this->assertEquals('2015-03-01T00:00:00+0800', $levelArray['created_at_start']);
        $this->assertEquals('2015-03-10T00:00:00+0800', $levelArray['created_at_end']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $level = new Level(123, '第一層', 1, 1);

        $this->assertEquals(123, $level->getDomain());
        $this->assertEquals(1, $level->getOrderStrategy());
        $this->assertEquals(1, $level->getOrderId());
        $this->assertEquals('第一層', $level->getAlias());
        $this->assertNull($level->getCreatedAtStart());
        $this->assertNull($level->getCreatedAtEnd());
        $this->assertEquals(0, $level->getDepositCount());
        $this->assertEquals(0, $level->getDepositTotal());
        $this->assertEquals(0, $level->getDepositMax());
        $this->assertEquals(0, $level->getWithdrawCount());
        $this->assertEquals(0, $level->getWithdrawTotal());
        $this->assertEquals(0, $level->getUserCount());
        $this->assertEquals('', $level->getMemo());

        $startTime = new \DateTime('2015-03-01 00:00:00');
        $endTime = new \DateTime('2015-03-10 00:00:00');

        $level->setOrderStrategy(0);
        $level->setAlias('第二層');
        $level->setOrderId(5);
        $level->setCreatedAtStart($startTime);
        $level->setCreatedAtEnd($endTime);
        $level->setDepositCount(2);
        $level->setDepositTotal(10);
        $level->setDepositMax(7);
        $level->setWithdrawCount(8);
        $level->setWithdrawTotal(1000);
        $level->setUserCount(456);
        $level->setMemo('test');

        $this->assertEquals(0, $level->getOrderStrategy());
        $this->assertEquals('第二層', $level->getAlias());
        $this->assertEquals(5, $level->getOrderId());
        $this->assertEquals($startTime, $level->getCreatedAtStart());
        $this->assertEquals($endTime, $level->getCreatedAtEnd());
        $this->assertEquals('2', $level->getDepositCount());
        $this->assertEquals('10', $level->getDepositTotal());
        $this->assertEquals('7', $level->getDepositMax());
        $this->assertEquals('8', $level->getWithdrawCount());
        $this->assertEquals('1000', $level->getWithdrawTotal());
        $this->assertEquals('456', $level->getUserCount());
        $this->assertEquals('test', $level->getMemo());
    }
}
