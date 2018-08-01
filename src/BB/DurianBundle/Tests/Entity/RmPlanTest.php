<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RmPlan;

class RmPlanTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $now = new \DateTime('now');
        $rPlan = new RmPlan('engineer', 6, 5, null, $now, 'test');

        $this->assertEquals('engineer', $rPlan->getCreator());
        $this->assertEquals(6, $rPlan->getParentId());
        $this->assertEquals(5, $rPlan->getDepth());
        $this->assertNull($rPlan->getUserCreatedAt());
        $this->assertNotNull($rPlan->getCreatedAt());
        $this->assertNull($rPlan->getModifiedAt());
        $this->assertFalse($rPlan->isQueueDone());
        $this->assertTrue($rPlan->isUntreated());
        $this->assertFalse($rPlan->isConfirm());
        $this->assertFalse($rPlan->isCancel());
        $this->assertFalse($rPlan->isFinished());
        $this->assertEquals('test', $rPlan->getTitle());

        $rPlan->queueDone();
        $this->assertTrue($rPlan->isQueueDone());

        $rPlan->userCreated();
        $rPlan->confirm();
        $rPlan->cancel();
        $rPlan->finish();
        $rPlan->setMemo('測試');
        $rPlan->setModifiedAt($now);
        $rPlan->setFinishAt($now);

        $array = $rPlan->toArray();
        $createdAt = $rPlan->getCreatedAt()->format(\DateTime::ISO8601);
        $lastLogin = $rPlan->getLastLogin()->format(\DateTime::ISO8601);
        $modifiedAt = $rPlan->getModifiedAt()->format(\DateTime::ISO8601);
        $finishAt = $rPlan->getFinishAt()->format(\DateTime::ISO8601);

        $this->assertEquals($createdAt, $array['created_at']);
        $this->assertEquals($lastLogin, $array['last_login']);
        $this->assertEquals($modifiedAt, $array['modified_at']);
        $this->assertEquals($finishAt, $array['finish_at']);
        $this->assertFalse($array['untreated']);
        $this->assertTrue($array['user_created']);
        $this->assertTrue($array['confirm']);
        $this->assertTrue($array['cancel']);
        $this->assertTrue($array['finished']);
        $this->assertEquals('測試', $array['memo']);
    }
}
