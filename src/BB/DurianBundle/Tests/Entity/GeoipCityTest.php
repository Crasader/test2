<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\GeoipCity;

class GeoipCityTest extends DurianTestCase
{
    /**
     * 檢測預設值, set and get
     */
    public function testNewGeoipCityAndSetAndGet()
    {
        $ipCity = new GeoipCity(22, 'CN', '01', 'Beijing');

        $this->AssertEquals(22, $ipCity->getRegionId());
        $this->AssertEquals('CN', $ipCity->getCountryCode());
        $this->AssertEquals('01', $ipCity->getRegionCode());
        $this->AssertEquals('Beijing', $ipCity->getCityCode());

        $ipCity->setEnName('Beijing');
        $ipCity->setZhTwName('北京');
        $ipCity->setZhCnName('北京');

        $ipCityArray = $ipCity->toArray();

        $this->AssertEquals('CN', $ipCityArray['country_code']);
        $this->AssertEquals('01', $ipCityArray['region_code']);
        $this->AssertEquals('Beijing', $ipCityArray['city_code']);
        $this->AssertEquals('Beijing', $ipCityArray['en_name']);
        $this->AssertEquals('北京', $ipCityArray['zh_tw_name']);
        $this->AssertEquals('北京', $ipCityArray['zh_cn_name']);
    }
}
