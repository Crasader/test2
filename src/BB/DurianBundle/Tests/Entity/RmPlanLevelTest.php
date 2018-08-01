<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RmPlanLevel;

class RmPlanLevelTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $rpLevel = new RmPlanLevel(1, 5, '第三層');

        $this->assertNull($rpLevel->getId());
        $this->assertEquals(1, $rpLevel->getPlanId());
        $this->assertEquals(5, $rpLevel->getLevelId());
        $this->assertEquals('第三層', $rpLevel->getLevelAlias());

        $array = $rpLevel->toArray();
        $this->assertEquals(5, $array['level_id']);
        $this->assertEquals('第三層', $array['level_alias']);
    }
}
