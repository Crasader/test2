<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\AuthController;

class AuthControllerTest extends ControllerTest
{
    /**
     * 測試email認證但帶入空字串
     */
    public function testCheckEmailVerifyWithEmptyString()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No verify code specified',
            150390012
        );

        $params = ['code' => ''];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new AuthController();
        $controller->setContainer($container);
        $controller->verifyEmailAction($request);
    }

    /**
     * 測試不輸入新密碼會跳例外
     */
    public function testSetPwdWithoutNewPwdGiven()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No new_password specified',
            150390002
        );

        $params = [
            'old_password' => '123456',
            'confirm_password' => 'aaabbb',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $request = new Request([], $params);
        $controller = new AuthController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setPasswordAction($request, 4);
    }

    /**
     * 測試不輸入舊密碼會跳例外
     */
    public function testSetPwdWithoutOldPwdGiven()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No old_password specified',
            150390001
        );

        $params = [
            'new_password' => 'aaabbb',
            'confirm_password' => 'aaabbb',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $request = new Request([], $params);
        $controller = new AuthController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setPasswordAction($request, 4);
    }

    /**
     * 測試不輸入密碼逾期時間會跳例外
     */
    public function testSetPwdWithoutPwdExpireGiven()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No password_expire_at specified',
            150390004
        );

        $params = [
            'old_password' => '123456',
            'new_password' => 'aaabbb',
            'confirm_password' => 'aaabbb'
        ];

        $request = new Request([], $params);
        $controller = new AuthController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setPasswordAction($request, 4);
    }

    /**
     * 測試新密碼與驗證密碼不同會跳例外
     */
    public function testSetPwdWithNewPwdNotSameAsConfirmPwd()
    {
        $this->setExpectedException(
            'RunTimeException',
            'New password and confirm password are different',
            150390007
        );

        $params = [
            'old_password' => '123456',
            'new_password' => 'aaabbb',
            'confirm_password' => 'cccccc',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $request = new Request([], $params);
        $controller = new AuthController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setPasswordAction($request, 4);
    }

    /**
     * 測試新密碼與驗證密碼同語意會跳例外 (EX: 0e1111 & 000000)
     */
    public function testSetPwdWithNewPwdSemanticsSameAsConfirmPwd()
    {
        $this->setExpectedException(
            'RunTimeException',
            'New password and confirm password are different',
            150390007
        );

        $params = [
            'old_password' => '123456',
            'new_password' => '000000',
            'confirm_password' => '0e1111',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $request = new Request([], $params);
        $controller = new AuthController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setPasswordAction($request, 4);
    }

    /**
     * 測試沒有confirm password會跳例外
     */
    public function testSetPasswordWithoutConfirmPassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No confirm_password specified',
            150390003
        );

        $params = [
            'new_password' => '123456',
            'password_expire_at' => '2015-11-19 08:44:43',
            'verify' => 0
        ];

        $request = new Request([], $params);
        $controller = new AuthController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setPasswordAction($request, 4);
    }

    /**
     * 測試新增臨時密碼沒有帶操作者
     */
    public function testCreateOncePasswordWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150390017
        );

        $request = new Request();
        $controller = new AuthController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createOncePasswordAction($request, 1);
    }
}
