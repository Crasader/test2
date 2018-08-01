<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\GeoipCountry;

class GeoipCountryTest extends DurianTestCase
{
    /**
     * 檢測預設值, set and get
     */
    public function testNewGeoipCountryAndSetAndGet()
    {
        $ipCountry = new GeoipCountry('TW');

        $ipCountry->setEnName('Taiwan');
        $ipCountry->setZhTwName('台灣');
        $ipCountry->setZhCnName('台灣');

        $ipCountryArray = $ipCountry->toArray();

        $this->AssertEquals('TW', $ipCountryArray['country_code']);
        $this->AssertEquals('Taiwan', $ipCountryArray['en_name']);
        $this->AssertEquals('台灣', $ipCountryArray['zh_tw_name']);
        $this->AssertEquals('台灣', $ipCountryArray['zh_cn_name']);
    }
}
