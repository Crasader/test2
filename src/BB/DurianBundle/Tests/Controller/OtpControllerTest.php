<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\OtpController;

class OtpControllerTest extends ControllerTest
{
    /**
     * 測試取得 otp 驗證結果不傳 username
     */
    public function testVerifyWithoutUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No username specified',
            150800001
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->verifyAction($request);
    }

    /**
     * 測試取得 otp 驗證帶入不合法長度 username
     */
    public function testVerifyWithInvalidUsernameLength()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username length given',
            150010012
        );

        $container = static::$kernel->getContainer();
        $request = new Request(['username' => 'usernameeeeeeeeeeeeeeeeeeeee']);
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->verifyAction($request);
    }

    /**
     * 測試取得 otp 驗證帶入不合法字元 username
     */
    public function testVerifyWithInvalidUsernameCharacter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username character given',
            150010013
        );

        $container = static::$kernel->getContainer();
        $request = new Request(['username' => 'user%name']);
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->verifyAction($request);
    }

    /**
     * 測試取得 otp 驗證結果不傳 domain
     */
    public function testVerifyWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150800002
        );

        $container = static::$kernel->getContainer();
        $request = new Request(['username' => 'username']);
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->verifyAction($request);
    }

    /**
     * 測試取得 otp 驗證帶入非法 domain
     */
    public function testVerifyWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            150800005
        );

        $container = static::$kernel->getContainer();
        $request = new Request(['username' => 'username', 'domain' => 'abc']);
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->verifyAction($request);
    }

    /**
     * 測試取得 otp 驗證結果不傳 otp_token
     */
    public function testVerifyWithoutOtpToken()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No otp_token specified',
            150800003
        );

        $container = static::$kernel->getContainer();
        $request = new Request(['username' => 'username', 'domain' => 1]);
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->verifyAction($request);
    }

    /**
     * 測試新增全域IP，未帶ip
     */
    public function testCreateGlobalIpWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            150800007
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->createGlobalIpAction($request);
    }

    /**
     * 測試新增全域IP，IP不合法
     */
    public function testCreateGlobalIpWithInvalidIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid IP',
            150800008
        );

        $parameters = ['ip' => '9487.0.0.1'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->createGlobalIpAction($request);
    }

    /**
     * 測試刪除全域IP，未帶IP
     */
    public function testRemoveGlobalIpWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            150800010
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->removeGlobalIpAction($request);
    }

    /**
     * 測試編輯全域IP，未帶IP
     */
    public function testEditGlobalIpWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            150800012
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new OtpController();
        $controller->setContainer($container);

        $controller->editGlobalIpAction($request);
    }
}
