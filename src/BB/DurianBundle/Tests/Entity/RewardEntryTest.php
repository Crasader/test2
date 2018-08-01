<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RewardEntry;

/**
 * 測試紅包明細
 */
class RewardEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $now = new \DateTime('now');

        $entry = new RewardEntry(1, 100);
        $entry->setId(10);
        $entry->setUserId(7);

        $this->assertEquals(10, $entry->getId());
        $this->assertEquals(1, $entry->getRewardId());
        $this->assertEquals(7, $entry->getUserId());
        $this->assertEquals(100, $entry->getAmount());
        $this->assertLessThan(5, abs($entry->getCreatedAt()->diff($now)->format('%s')));
        $this->assertNull($entry->getObtainAt());
        $this->assertNull($entry->getPayOffAt());

        $entry->setObtainAt(new \DateTime('2016-03-10T00:00:00+0800'));
        $entry->setPayOffAt(new \DateTime('2016-03-11T00:00:00+0800'));

        $ret = $entry->toArray();
        $this->assertEquals(10, $ret['id']);
        $this->assertEquals(1, $ret['reward_id']);
        $this->assertEquals(7, $ret['user_id']);
        $this->assertEquals(100, $ret['amount']);
        $this->assertEquals('2016-03-10T00:00:00+0800', $ret['obtain_at']);
        $this->assertEquals('2016-03-11T00:00:00+0800', $ret['payoff_at']);
    }
}
