<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RmPlan;
use BB\DurianBundle\Entity\RmPlanQueue;

class RmPlanQueueTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $now = new \DateTime('now');
        $rPlan = new RmPlan('engineer', 6, 5, null, $now, 'test');
        $rPlan->setId(10);

        $queue = new RmPlanQueue($rPlan);
        $this->assertEquals(10, $queue->getPlanId());
    }
}
