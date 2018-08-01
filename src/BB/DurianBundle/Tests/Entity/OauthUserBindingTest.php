<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\OauthUserBinding;
use BB\DurianBundle\Entity\OauthVendor;

class OauthUserBindingTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testOauthUserBindingBasic()
    {
        $vendor = new OauthVendor("weibo");
        $oauthBinding = new OauthUserBinding(
            2000,
            $vendor,
            '9998888039212'
        );

        $this->assertEquals(0, $oauthBinding->getId());
        $this->assertEquals(2000, $oauthBinding->getUserId());
        $this->assertEquals('weibo', $oauthBinding->getVendor()->getName());
        $this->assertEquals('9998888039212', $oauthBinding->getOpenid());

        $newVendor = new OauthVendor("facebook");
        $oauthBinding->setVendor($newVendor);
        $oauthBinding->setUserId(2);
        $oauthBinding->setOpenid('abc');

        $this->assertEquals('facebook', $oauthBinding->getVendor()->getName());

        $array = $oauthBinding->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertEquals(2, $array['user_id']);
        $this->assertEquals(0, $array['vendor_id']);
        $this->assertEquals('abc', $array['openid']);
    }
}
