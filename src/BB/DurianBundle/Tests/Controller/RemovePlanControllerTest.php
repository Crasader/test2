<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\RemovePlanController;

class RemovePlanControllerTest extends ControllerTest
{
    /**
     * 測試新增一筆刪除使用者申請單，沒有帶建立者
     */
    public function testCreatePlanWithoutCreator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No creator specified',
            150630001
        );

        $request = new Request([], []);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPlanAction($request);
    }

    /**
     * 測試新增一筆刪除使用者申請單，沒有帶parentId
     */
    public function testCreatePlanWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150630002
        );

        $parameter = ['creator' => 'a'];

        $request = new Request([], $parameter);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPlanAction($request);
    }

    /**
     * 測試新增一筆刪除使用者申請單，帶入負數depth
     */
    public function testCreatePlanWithNegativeDepth()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid depth',
            150630003
        );

        $parameters = [
            'creator'   => 'a',
            'parent_id' => 1,
            'depth'     => -1
        ];

        $request = new Request([], $parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPlanAction($request);
    }

    /**
     * 測試新增一筆刪除使用者申請單，沒帶last_login，created_at參數
     */
    public function testCreatePlanWithoutLastLoginAndCreatedAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No last_login or created_at specified',
            150630004
        );

        $parameters = [
            'creator'   => 'a',
            'parent_id' => 1,
            'depth'     => 5
        ];

        $request = new Request([], $parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPlanAction($request);
    }

    /**
     * 測試新增一筆刪除使用者申請單，last_login，created_at同時帶入
     */
    public function testCreatePlanWithLastLoginAndCreatedAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Last_login and created_at cannot be specified at same time',
            150630023
        );

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 1,
            'depth'      => 5,
            'last_login' => '201601010000',
            'created_at' => '201601010000'
        ];

        $request = new Request([], $parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPlanAction($request);
    }

    /**
     * 測試新增一筆刪除使用者申請單，帶入不合法last_login
     */
    public function testCreatePlanWithInvalidLastLogin()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid last_login',
            150630005
        );

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 1,
            'depth'      => 5,
            'last_login' => 'aaa',
        ];

        $request = new Request([], $parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPlanAction($request);
    }

    /**
     * 測試新增一筆刪除使用者申請單，帶入不合法Created_at
     */
    public function testCreatePlanWithInvalidCreatedAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid created_at',
            150630022
        );

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 1,
            'depth'      => 5,
            'created_at' => 'abc'
        ];

        $request = new Request([], $parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPlanAction($request);
    }

    /**
     * 測試新增一筆刪除使用者申請單，沒有帶計畫名稱
     */
    public function testCreatePlanWithoutTitle()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Title can not be null',
            150630006
        );

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 1,
            'depth'      => 5,
            'last_login' => '2015-03-01 00:00:00'
        ];

        $request = new Request([], $parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPlanAction($request);
    }

    /**
     * 測試撤銷刪除使用者，帶入非陣列users
     */
    public function testCancelPlanUserWithNotArrayUsers()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid users',
            150630014
        );

        $parameters = ['users' => 51];

        $request = new Request([], $parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->cancelPlanUserAction($request, 1);
    }

    /**
     * 測試列出刪除計畫，帶入不合法最後登入時間
     */
    public function testGetPlanWithInvalidLastLogin()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid last_login',
            150630005
        );

        $parameters = [
            'last_login' => 'aaa',
            'first_result' => 0,
            'max_results' => 10
        ];

        $request = new Request($parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getPlanAction($request);
    }

    /**
     * 測試列出刪除計畫，帶入不合法使用者建立時間
     */
    public function testGetPlanWithInvalidCreatedAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid created_at',
            150630022
        );

        $parameters = [
            'created_at' => 'aaa',
            'first_result' => 0,
            'max_results' => 10
        ];

        $request = new Request($parameters);
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getPlanAction($request);
    }

    /**
     * 測試檢查刪除使用者計畫是否完成，plan_user_id未帶
     */
    public function testCheckPlanFinishWithoutPlanUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No plan_user_id specified',
            150630018
        );

        $request = new Request();
        $controller = new RemovePlanController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->checkPlanFinishByPlanUserIdAction($request);
    }
}
