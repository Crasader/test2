<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\LoginController;

class LoginControllerTest extends ControllerTest
{
    /**
     * 測試登入不傳username
     */
    public function testUserLoginWithoutUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No username specified',
            150250004
        );

        $params = [
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->loginAction($request);
    }

    /**
     * 測試登入不傳IP
     */
    public function testUserLoginWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            150250005
        );

        $params = [
            'username' => 'tester',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->loginAction($request);
    }

    /**
     * 測試登入不傳domain
     */
    public function testUserLoginWithErrorDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150250006
        );

        $params = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'password' => '123456',
            'entrance' => '3'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->loginAction($request);
    }

    /**
     * 測試登入，帶入不存在的entrance
     */
    public function testUserLoginWithNotExistEntrance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid entrance given',
            150250001
        );

        $params = [
            'username' => 'tester',
            'domain' => '2',
            'ip' => 'this.is.ip.address',
            'password' => '123456',
            'entrance' => '9'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->loginAction($request);
    }

    /**
     * 測試登入沒帶入entrance
     */
    public function testUserLoginWithoutEntrance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid entrance given',
            150250001
        );

        $params = [
            'username' => 'tester',
            'domain' => '2',
            'ip' => 'this.is.ip.address',
            'password' => '123456'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->loginAction($request);
    }

    /**
     * 測試登入不傳密碼
     */
    public function testUserLoginWithErrorPassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No password specified',
            150250007
        );

        $params = [
            'username' => 'tester',
            'ip' => '192.157.111.25',
            'domain' => '2',
            'entrance' => '3'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->loginAction($request);
    }

    /**
     * 測試登入帶入非法 X-FORWARDED-FOR
     */
    public function testUserLoginWithInvalidXForwardedFor()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid x_forwarded_for format',
            150250015
        );

        $params = [
            'username' => 'tester',
            'ip' => '192.157.111.25',
            'domain' => '2',
            'entrance' => '3',
            'password' => '123456',
            'x_forwarded_for' => 'asdsad'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->loginAction($request);
    }

    /**
     * 測試oauth登入,但openid未帶值
     */
    public function testOauthLoginWithInvalidOpenId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid oauth openid',
            150250010
        );

        $params = [
            'oauth_id' => 2,
            'openid' => '',
            'ip' => '127.0.0.1',
            'entrance' => '3'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->oauthLoginAction($request);
    }

    /**
     * 測試oauth登入,但不傳IP
     */
    public function testOauthLoginWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            150250005
        );

        $params = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'entrance' => '3'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->oauthLoginAction($request);
    }

    /**
     * 測試oauth登入,但不傳Entrance
     */
    public function testOauthLoginWithoutEntrance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid entrance given',
            150250001
        );

        $params = [
            'oauth_id' => 2,
            'openid'=> '2382158635',
            'ip' => '127.0.0.1'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->oauthLoginAction($request);
    }

    /**
     * 測試oauth登入,但傳錯誤的Entrance
     */
    public function testOauthLoginWithoutErrorEntrance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid entrance given',
            150250001
        );

        $params = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '127.0.0.1',
            'entrance' => '9'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->oauthLoginAction($request);
    }

    /**
     * 測試登出時session_id帶空值
     */
    public function testLogoutWithoutSessionId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No session_id specified',
            150250008
        );

        $params = ['session_id' => ''];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->logoutAction($request);
    }

    /**
     * 測試登出時帶入錯誤的session_id
     */
    public function testLogoutWithErrorSessionId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Session not found',
            150250013
        );

        $params = ['session_id' => 'session12434'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->logoutAction($request);
    }

    /**
     * 測試取得登入記錄未帶時間參數
     */
    public function testGetLogListWithoutTimes()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150250016
        );

        $request = new Request();
        $controller = new LoginController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLogListAction($request);
    }

    /**
     * 測試用ip和上層使用者取得登入記錄未帶時間參數
     */
    public function testGetLogListByIpParentWithoutTimes()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150250016
        );

        $request = new Request();
        $controller = new LoginController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLogListByIpParentAction($request);
    }

    /**
     * 測試用ip和上層使用者取得登入記錄帶入不合法ip
     */
    public function testGetLogListByIpParentWithInvalidIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid IP',
            150250026
        );

        $params = [
            'start' => '2012-01-01 00:00:00',
            'end' => '2012-01-02 00:00:00'
        ];

        $request = new Request($params);
        $controller = new LoginController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLogListByIpParentAction($request);
    }

    /**
     * 測試用ip和上層使用者取得登入記錄未指定parent_id
     */
    public function testGetLogListByIpParentWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150250027
        );

        $params = [
            'start' => '2012-01-01 00:00:00',
            'end' => '2012-01-02 00:00:00',
            'ip' => '123.123.123.123'
        ];

        $request = new Request($params);
        $controller = new LoginController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLogListByIpParentAction($request);
     }

    /**
     * 取得與使用者相同IP登入的最後登入紀錄未帶時間參數
     */
    public function testGetSameIpWithoutTimes()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150250016
        );

        $params = ['user_id' => 1];

        $request = new Request($params);
        $controller = new LoginController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getSameIpAction($request);
    }

    /**
     * 取得與使用者相同IP登入的最後登入紀錄未帶使用者ID
     */
    public function testGetSameIpWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150250025
        );

        $params = [
            'start' => '2012-01-01 00:00:00',
            'end' => '2012-01-02 00:00:00'
        ];

        $request = new Request($params);
        $controller = new LoginController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getSameIpAction($request);
    }

    /**
     * 測試查詢最後成功登入紀錄未帶username參數
     */
    public function testGetLastLoginWithoutUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No username specified',
            150250004
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new LoginController();
        $controller->setContainer($container);

        $controller->getLastLoginByUsernameAction($request);
    }
}
