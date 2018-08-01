<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Reward;

/**
 * 測試紅包活動
 */
class RewardtTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $begin = '2016-01-01T00:00:00+0800';
        $end = '2016-01-10T00:00:00+0800';
        $now = new \DateTime('now');

        $reward = new Reward('test', 1, 100, 10, 1, 15, $begin, $end);

        $this->assertNull($reward->getId());
        $this->assertEquals('test', $reward->getName());
        $this->assertEquals(1, $reward->getDomain());
        $this->assertEquals(100, $reward->getAmount());
        $this->assertEquals(10, $reward->getQuantity());
        $this->assertEquals($begin, $reward->getBeginAt()->format(\DateTime::ISO8601));
        $this->assertEquals($end, $reward->getEndAt()->format(\DateTime::ISO8601));
        $this->assertLessThan(5, abs($reward->getCreatedAt()->diff($now)->format('%s')));
        $this->assertEquals(0,$reward->getObtainAmount());
        $this->assertEquals(0,$reward->getObtainQuantity());
        $this->assertFalse($reward->isEntryCreated());
        $this->assertFalse($reward->isCancel());
        $this->assertEmpty($reward->getMemo());

        $reward->setEntryCreated();
        $reward->setMemo('test');
        $reward->addObtainQuantity();
        $reward->addObtainAmount(100);
        $reward->cancel();
        $reward->setEndAt(new \DateTime('2016-07-02T00:00:00+0800'));

        $ret = $reward->toArray();
        $this->assertEquals('test', $ret['name']);
        $this->assertEquals(1, $ret['domain']);
        $this->assertEquals(100, $ret['amount']);
        $this->assertEquals(10, $ret['quantity']);
        $this->assertEquals($begin, $ret['begin_at']);
        $this->assertEquals('2016-07-02T00:00:00+0800', $ret['end_at']);
        $this->assertEquals(100, $ret['obtain_amount']);
        $this->assertEquals(1, $ret['obtain_quantity']);
        $this->assertTrue($ret['entry_created']);
        $this->assertTrue($ret['cancel']);
        $this->assertEquals('test', $ret['memo']);
    }
}
