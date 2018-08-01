<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RmPlanUserExtraBalance;

/**
 * 測試記錄刪除使用者計畫下的外接額度
 */
class RmPlanUserExtraBalanceTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $rpueBalance = new RmPlanUserExtraBalance(1, 'ab', 100);

        $array = $rpueBalance->toArray();
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('ab', $array['platform']);
        $this->assertEquals(100, $array['balance']);
    }
}
