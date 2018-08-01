<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\OutsidePayway;

/**
 * 測試 OutsidePayway Entity
 */
class OutsidePaywayTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $payway = new OutsidePayway(1);
        $this->assertFalse($payway->isBodog());
        $this->assertFalse($payway->isSuncity());

        $payway->setBodog(true);
        $payway->setSuncity(true);

        $this->assertTrue($payway->isBodog());
        $this->assertTrue($payway->isSuncity());

        $array = $payway->toArray();
        $this->assertEquals(1, $array['domain']);
        $this->assertTrue($array['bodog']);
        $this->assertTrue($array['suncity']);

        $payway->setBodog(false);
        $payway->setSuncity(false);

        $this->assertFalse($payway->isBodog());
        $this->assertFalse($payway->isSuncity());

        $array = $payway->toArray();
        $this->assertEquals(1, $array['domain']);
        $this->assertFalse($array['bodog']);
        $this->assertFalse($array['suncity']);

    }
}
