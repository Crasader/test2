<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\SlideController;

class SlideControllerTest extends ControllerTest
{
    /**
     * 測試帳號綁定手勢登入裝置沒給裝置識別ID
     */
    public function testCreateBindingWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790003
        );

        $params = [
            'user_id' => '30',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->createBindingAction($request);
    }

    /**
     * 測試帳號綁定手勢登入裝置沒給手勢密碼
     */
    public function testCreateBindingWithoutSlidePassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No slide_password specified',
            150790004
        );

        $params = [
            'user_id' => '30',
            'app_id' => '123456',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->createBindingAction($request);
    }

    /**
     * 測試帳號綁定手勢登入裝置沒給綁定標記
     */
    public function testCreateBindingWithoutBindingToken()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No binding_token specified',
            150790005
        );

        $params = [
            'user_id' => '30',
            'app_id' => '123456',
            'slide_password' => '9487942'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->createBindingAction($request);
    }

    /**
     * 測試移除一筆手勢登入綁定沒給user_id
     */
    public function testRemoveBindingWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150790010
        );

        $params = ['app_id' => '123456'];

        $container = static::$kernel->getContainer();
        $request = new Request($params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->removeBindingAction($request);
    }

    /**
     * 測試移除一筆手勢登入綁定沒給裝置識別ID
     */
    public function testRemoveBindingWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790011
        );

        $params = ['user_id' => '30'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->removeBindingAction($request);
    }

    /**
     * 測試移除一裝置上所有綁定的手勢登入沒給裝置識別ID
     */
    public function testRemoveAllBindingsWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790013
        );

        $params = [];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->removeAllBindingsAction($request);
    }

    /**
     * 測試驗證裝置產生手勢登入標記沒給裝置識別ID
     */
    public function testGenerateAccessTokenWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790015
        );

        $params = [
            'user_id' => '30',
            'slide_password' => '9487942'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->generateAccessTokenAction($request);
    }

    /**
     * 測試驗證裝置產生手勢登入標記沒給手勢密碼
     */
    public function testGenerateAccessTokenWithoutSlidePassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No slide_password specified',
            150790016
        );

        $params = [
            'user_id' => '30',
            'app_id' => '123456'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->generateAccessTokenAction($request);
    }

    /**
     * 測試修改裝置綁定名稱沒給user_id
     */
    public function testEditBindingNameWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150790019
        );

        $params = [
            'app_id' => '123456',
            'device_name' => 'My Phone'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->editBindingNameAction($request);
    }

    /**
     * 測試修改裝置綁定名稱沒給裝置識別ID
     */
    public function testEditBindingNameWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790020
        );

        $params = [
            'user_id' => '30',
            'device_name' => 'My Phone'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->editBindingNameAction($request);
    }

    /**
     * 測試修改裝置綁定名稱沒給裝置名稱
     */
    public function testEditBindingNameWithoutDeviceName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No device_name specified',
            150790021
        );

        $params = [
            'user_id' => '30',
            'app_id' => '123456'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->editBindingNameAction($request);
    }

    /**
     * 測試列出裝置所有綁定使用者沒給裝置識別ID
     */
    public function testListBindingUsersByDeviceWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790023
        );

        $params = ['access_token' => 'a38a428908c32e189957fe1d9404141955c42456'];

        $container = static::$kernel->getContainer();
        $request = new Request($params, []);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->listBindingUsersByDeviceAction($request);
    }

    /**
     * 測試列出裝置所有綁定使用者沒給存取標記
     */
    public function testListBindingUsersByDeviceWithoutAccessToken()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No access_token specified',
            150790047
        );

        $params = ['app_id' => 'mitsuha'];

        $container = static::$kernel->getContainer();
        $request = new Request($params, []);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->listBindingUsersByDeviceAction($request);
    }

    /**
     * 測試裝置停用手勢登入沒給裝置識別ID
     */
    public function testDisableDeviceWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790024
        );

        $container = static::$kernel->getContainer();
        $request = new Request([], []);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->disableDeviceAction($request);
    }

    /**
     * 測試解凍手勢登入綁定沒給user_id
     */
    public function testUnblockBindingWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150790026
        );

        $params = ['app_id' => '123456'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->unblockBindingAction($request);
    }

    /**
     * 測試解凍手勢登入綁定沒給裝置識別ID
     */
    public function testUnblockBindingWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790027
        );

        $params = ['user_id' => '30'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->unblockBindingAction($request);
    }

    /**
     * 測試手勢密碼登入沒給帳號
     */
    public function testSlideLoginWithoutUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No username specified',
            150790029
        );

        $params = [
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'entrance' => '3',
            'app_id' => '123456',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
            'access_token' => 'c62770c862847dbe66ebd6f43d10975849fc1234'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }

    /**
     * 測試手勢密碼登入沒給IP
     */
    public function testSlideLoginWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            150790030
        );

        $params = [
            'username' => 'tester',
            'domain' => '2',
            'entrance' => '3',
            'app_id' => '123456',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
            'access_token' => 'c62770c862847dbe66ebd6f43d10975849fc1234'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }

    /**
     * 測試手勢密碼登入沒給domain
     */
    public function testSlideLoginWithErrorDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150790031
        );

        $params = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'entrance' => '3',
            'app_id' => '123456',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
            'access_token' => 'c62770c862847dbe66ebd6f43d10975849fc1234'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }

    /**
     * 測試手勢密碼登入沒給裝置識別ID
     */
    public function testSlideLoginWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150790033
        );

        $params = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'entrance' => '3',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
            'access_token' => 'c62770c862847dbe66ebd6f43d10975849fc1234'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }

    /**
     * 測試手勢密碼登入沒給手勢密碼
     */
    public function testSlideLoginWithoutSlidePassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No slide_password specified',
            150790034
        );

        $params = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'entrance' => '3',
            'app_id' => '123456',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
            'access_token' => 'c62770c862847dbe66ebd6f43d10975849fc1234'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }

    /**
     * 測試手勢密碼登入沒給存取標記
     */
    public function testSlideLoginWithoutAccessToken()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No access_token specified',
            150790036
        );

        $params = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'entrance' => '3',
            'app_id' => '123456',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }

    /**
     * 測試手勢密碼登入，帶入不存在的entrance
     */
    public function testSlideLoginWithNotExistEntrance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid entrance given',
            150790037
        );

        $params = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'entrance' => '9',
            'app_id' => '123456',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
            'access_token' => 'c62770c862847dbe66ebd6f43d10975849fc1234'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }

    /**
     * 測試手勢密碼登入沒給entrance
     */
    public function testSlideLoginWithoutEntrance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid entrance given',
            150790037
        );

        $params = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'app_id' => '123456',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
            'access_token' => 'c62770c862847dbe66ebd6f43d10975849fc1234'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }

    /**
     * 測試手勢密碼登入帶入非法 X-FORWARDED-FOR
     */
    public function testSlideLoginWithInvalidXForwardedFor()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid x_forwarded_for format',
            150250015
        );

        $params = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'entrance' => '3',
            'app_id' => '123456',
            'slide_password' => '9487942',
            'binding_token' => 'e53b67a51bd7dca4fcfc124f14f891fbe06282a3',
            'access_token' => 'c62770c862847dbe66ebd6f43d10975849fc1234',
            'x_forwarded_for' => 'asdsad'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new SlideController();
        $controller->setContainer($container);

        $controller->slideLoginAction($request);
    }
}
