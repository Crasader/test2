<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\GeoipBlock;

class GeoipBlockTest extends DurianTestCase
{
    /**
     * 檢測預設值, set and get
     */
    public function testNewGeoipCityAndSetAndGet()
    {
        //參數為ip起、ip終、versionId, counrtyId(台灣8),regionId(8),cityId(Taipei9)
        $ipBlock = new GeoipBlock(3758096128, 3758096383, 2, 8, 8, 9);
        $ipBlockArray = $ipBlock->toArray();

        $this->AssertEquals(8, $ipBlockArray['country_id']);
        $this->AssertEquals(8, $ipBlockArray['region_id']);
        $this->AssertEquals(9, $ipBlockArray['city_id']);
        $this->AssertEquals(3758096128, $ipBlockArray['ip_start']);
        $this->AssertEquals(3758096383, $ipBlockArray['ip_end']);
        $this->AssertEquals(2, $ipBlockArray['ip_version']);
    }
}
