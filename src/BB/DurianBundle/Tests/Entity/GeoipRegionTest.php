<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\GeoipRegion;

class GeoipRegionTest extends DurianTestCase
{
    /**
     * 檢測預設值, set and get
     */
    public function testNewGeoipRegionAndSetAndGet()
    {
        $ipRegion = new GeoipRegion(1, 'TW', '02');

        $this->AssertEquals(1, $ipRegion->getCountryId());
        $this->AssertEquals('TW', $ipRegion->getCountryCode());
        $this->AssertEquals('02', $ipRegion->getRegionCode());

        $ipRegion->setEnName('Taipei');
        $ipRegion->setZhTwName('台北');
        $ipRegion->setZhCnName('天龍國');

        $ipRegionArray = $ipRegion->toArray();

        $this->AssertEquals('TW', $ipRegionArray['country_code']);
        $this->AssertEquals('02', $ipRegionArray['region_code']);
        $this->AssertEquals('Taipei', $ipRegionArray['en_name']);
        $this->AssertEquals('台北', $ipRegionArray['zh_tw_name']);
        $this->AssertEquals('天龍國', $ipRegionArray['zh_cn_name']);
    }
}
