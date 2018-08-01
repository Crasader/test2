<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Oauth;
use BB\DurianBundle\Entity\OauthVendor;

class OauthTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testOauthBasic()
    {
        $vendor = new OauthVendor('weibo');

        $oauth = new Oauth(
            $vendor,
            5,
            '734811042',
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com'
        );

        $this->assertEquals(0, $oauth->getId());
        $this->assertEquals($vendor, $oauth->getVendor());
        $this->assertEquals('weibo', $oauth->getVendor()->getName());
        $this->assertEquals('734811042', $oauth->getAppId());
        $this->assertEquals('be70399cea8b4a9c700247f6324fa7e2', $oauth->getAppKey());
        $this->assertEquals('http://playesb.com', $oauth->getRedirectUrl());

        $newVendor = new OauthVendor('facebook');
        $oauth->setVendor($newVendor);
        $oauth->setAppId('aaa');
        $oauth->setAppKey('bbb');
        $oauth->setRedirectUrl('yahoo.com');

        $this->assertEquals($newVendor, $oauth->getVendor());
        $this->assertEquals('facebook', $oauth->getVendor()->getName());

        $array = $oauth->toArray();
        $this->assertEquals(0, $array['id']);
        $this->assertEquals(0, $array['vendor_id']);
        $this->assertEquals(5, $array['domain']);
        $this->assertEquals('aaa', $array['app_id']);
        $this->assertEquals('bbb', $array['app_key']);
        $this->assertEquals('yahoo.com', $array['redirect_url']);
    }
}
