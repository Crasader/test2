<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\OauthVendor;

class OauthVendorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $vendor = new OauthVendor("facebook");
        $this->assertEquals('facebook', $vendor->getName());

        $vendor->setName("qq");
        $vendor->setApiUrl('graph.facebook.com');

        $array = $vendor->toArray();
        $this->assertEquals(0, $array['id']);
        $this->assertEquals('qq', $array['name']);
        $this->assertEquals('graph.facebook.com', $array['api_url']);
    }
}
