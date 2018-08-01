<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\OauthController;

class OauthControllerTest extends ControllerTest
{
    /**
     * 測試取得使用者資訊, 但OauthId不正確
     */
    public function testGetUserProfileWithInvalidOauthId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid oauth id',
            150230001
        );

        // 沒帶oauth_id
        $parameters = ['code' => '1234'];

        $query = new Request($parameters);
        $controller = new OauthController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getUserProfileAction($query);
    }

    /**
     * 測試取得使用者資訊, 但OauthCode不正確
     */
    public function testGetUserProfileWithInvalidOauthCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No oauth code specified',
            150230003
        );

        // 沒帶code
        $parameters = ['oauth_id' => 2];

        $query = new Request($parameters);
        $controller = new OauthController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getUserProfileAction($query);
    }

    /**
     * 測試新增oauth設定時，app_id不合法
     */
    public function testCreateOauthWithInvalidAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid app id',
            150230004
        );

        $parameters = [
            'vendor_id'    => 1,
            'domain'       => 3,
            'app_id'       => '',
            'app_key'      => 'abcd',
            'redirect_url' => 'http://f**kbook.com',
        ];

        $query = new Request([], $parameters);
        $controller = new OauthController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($query);
    }

    /**
     * 測試新增oauth設定時，app_key不合法
     */
    public function testCreateOauthWithInvalidAppKey()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid app key',
            150230005
        );

        $parameters = [
            'vendor_id'    => 1,
            'domain'       => 3,
            'app_id'       => '1234',
            'app_key'      => '',
            'redirect_url' => 'http://f**kbook.com',
        ];

        $query = new Request([], $parameters);
        $controller = new OauthController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($query);
    }

    /**
     * 測試新增oauth綁定設定, 帶入非法的openid
     */
    public function testCreateOauthBindingWithInvalidOpenId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid oauth openid',
            150230006
        );

        // openid為空
        $parameters = array(
            'user_id'   => 51,
            'vendor_id' => 1,
            'openid'    => '',
        );

        $query = new Request([], $parameters);
        $controller = new OauthController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createBindingAction($query);
    }

    /**
     * 測試判斷oauth帳號是否已經跟使用者做綁定, 其中vendor_id為空
     */
    public function testIsBindingWithoutVendorId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid oauth vendor',
            150230008
        );

        $parameters = ['openid' => 'abcd1234'];

        $query = new Request($parameters);
        $controller = new OauthController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->isBindingAction($query);
    }

    /**
     * 測試判斷oauth帳號是否已經跟使用者做綁定, 其中openid為空
     */
    public function testIsBindingWithoutOpenid()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid oauth openid',
            150230006
        );

        $parameters = ['vendor_id' => 1];

        $query = new Request($parameters);
        $controller = new OauthController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->isBindingAction($query);
    }
}
