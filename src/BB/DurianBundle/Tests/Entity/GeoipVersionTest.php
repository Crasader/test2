<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\GeoipVersion;

class GeoipVersionTest extends DurianTestCase
{
    /**
     * 檢測預設值 及轉成陣列
     */
    public function testNewGeoipVersionAndSetAndGet()
    {
        $now = new \DateTime('2013-01-17 16:37:22');
        $updateTime = $now->add(new \DateInterval('PT1M'));

        $ipVersion = new GeoipVersion();
        $ipVersion->setCreatedAt($now);
        $ipVersion->setUpdateAt($updateTime);

        $arrayIpVersion = $ipVersion->toArray();

        $this->AssertFalse((bool)$arrayIpVersion['status']);
        $this->AssertEquals($now->format('Y-m-d H:i:s'), $arrayIpVersion['created_at']);
        $this->AssertEquals($now->format('Y-m-d H:i:s'), $arrayIpVersion['update_at']);
    }
}
