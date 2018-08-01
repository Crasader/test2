<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\BlacklistController;

class BlacklistControllerTest extends ControllerTest
{
    /**
     * 測試新增黑名單，未指定任何欄位
     */
    public function testCreateBlacklistWithoutFields()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No blacklist fields specified',
            150650013
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->createAction($request);
    }

    /**
     * 測試新增黑名單，指定超過一個欄位
     */
    public function testCreateBlacklistWithMoreThanOneFields()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot specify more than one blacklist fields',
            150650014
        );

        $parameters = [
            'account' => 'abcd123',
            'identity_card' => '11223344',
            'name_real' => '隔壁老王',
            'telephone' => '01234567',
            'email' => '112@ttm.com',
            'ip' => '128.0.0.1',
            'note' => 'test note'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->createAction($request);
    }

    /**
     * 測試新增黑名單，未指定操作者
     */
    public function testCreateBlacklistWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150650031
        );

        $parameters = ['account' => '12qw!@#'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->createAction($request);
    }

    /**
     * 測試不合法操作端新增黑名單
     */
    public function testCreateBlacklistWithInvalidTerminal()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Creating whole domain blacklist is not allowed',
            150650032
        );

        $parameters = [
            'account' => '12aaaaa',
            'operator' => '123'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->createAction($request);
    }

    /**
     * 測試新增黑名單銀行帳號格式不合法
     */
    public function testCreateBlacklistWithInvalidAccount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid account',
            150650003
        );

        $parameters = [
            'account' => '12qw!@#',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->createAction($request);
    }

    /**
     * 測試新增黑名單email帶入0
     */
    public function testCreateBlacklistEmailWithZero()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid email given',
            150010127
        );

        $parameters = [
            'email' => '0',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->createAction($request);
    }

    /**
     * 測試新增黑名單IP格式不合法
     */
    public function testCreateBlacklistWithInvalidIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid IP',
            150650024
        );

        $parameters = [
            'ip' => '13.#.0.',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->createAction($request);
    }

    /**
     * 測試新增黑名單IP帶入0
     */
    public function testCreateBlacklistIpWithZero()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid IP',
            150650024
        );

        $parameters = [
            'ip' => '0',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->createAction($request);
    }

    /**
     * 測試修改黑名單，未帶入備註修改內容
     */
    public function testEditBlacklistWithoutNote()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No note specified',
            150650010
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->editAction($request, 1);
    }

    /**
     * 測試查詢黑名單帶不合法的開始時間
     */
    public function testGetBlacklistWithInvalidStartAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid start_at',
            150650022
        );

        $parameters = ['start_at' => 'qwer'];

        $container = static::$kernel->getContainer();
        $request = new Request($parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->getBlacklistAction($request);
    }

    /**
     * 測試查詢黑名單帶不合法的結束時間
     */
    public function testGetBlacklistWithInvalidEndAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid end_at',
            150650023
        );

        $parameters = ['end_at' => 'qwer'];

        $container = static::$kernel->getContainer();
        $request = new Request($parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->getBlacklistAction($request);
    }

    /**
     * 測試刪除黑名單，未指定操作者
     */
    public function testRemoveBlacklistWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150650031
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->removeAction($request, 1);
    }

    /**
     * 測試查詢黑名單操作紀錄，帶不合法的開始時間
     */
    public function testGetBlacklistOperationLogWithInvalidStartAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid start_at',
            150650029
        );

        $parameters = ['start_at' => 'qwer'];

        $container = static::$kernel->getContainer();
        $request = new Request($parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->getBlacklistOperationLogAction($request);
    }

    /**
     * 測試查詢黑名單操作紀錄，帶不合法的結束時間
     */
    public function testGetBlacklistOperationLogWithInvalidEndAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid end_at',
            150650030
        );

        $parameters = ['end_at' => 'qwer'];

        $container = static::$kernel->getContainer();
        $request = new Request($parameters);
        $controller = new BlacklistController();
        $controller->setContainer($container);
        $controller->getBlacklistOperationLogAction($request);
    }
}
