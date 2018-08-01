<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantWithdrawIpStrategy;

class MerchantWithdrawIpStrategyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $strategy = new MerchantWithdrawIpStrategy($merchantWithdraw, 1, 2, 3);

        $this->assertNull($strategy->getId());
        $this->assertEquals(1, $strategy->getCountry());
        $this->assertEquals(2, $strategy->getRegion());
        $this->assertEquals(3, $strategy->getCity());
        $this->assertEquals($merchantWithdraw, $strategy->getMerchantWithdraw());

        $strategyArray = $strategy->toArray();
        $this->assertEquals(1, $strategyArray['country_id']);
        $this->assertEquals(2, $strategyArray['region_id']);
        $this->assertEquals(3, $strategyArray['city_id']);

        // 測當region city 為null時依然不會壞
        $strategyCountry = new MerchantWithdrawIpStrategy($merchantWithdraw, 1, null, null);

        $strategyCountryArray = $strategyCountry->toArray();
        $this->assertEquals(1, $strategyCountryArray['country_id']);
        $this->assertNull($strategyCountryArray['region_id']);
        $this->assertNull($strategyCountryArray['city_id']);
    }
}
