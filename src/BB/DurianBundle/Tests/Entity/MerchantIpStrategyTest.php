<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantIpStrategy;

class MerchantIpStrategyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
                ->disableOriginalConstructor()
                ->getMock();

        $strategy = new MerchantIpStrategy($merchant, 1, 2, 3);

        $this->assertNull($strategy->getId());
        $this->assertEquals($merchant, $strategy->getMerchant());
        $strategyArray = $strategy->toArray();

        $this->assertEquals(1, $strategyArray['country_id']);
        $this->assertEquals(2, $strategyArray['region_id']);
        $this->assertEquals(3, $strategyArray['city_id']);

        //測當region city 為null時依然不會壞
        $strategyCountry = new MerchantIpStrategy($merchant, 1, null, null);
        $strategyCountryArray = $strategyCountry->toArray();

        $this->assertEquals(1, $strategyCountryArray['country_id']);
        $this->assertEquals(null, $strategyCountryArray['region_id']);
        $this->assertEquals(null, $strategyCountryArray['city_id']);
    }
}
