<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\MaintainController;

class MaintainControllerTest extends ControllerTest
{
    /**
     * 測試取得不合法的測試帳號但沒帶parentId
     */
    public function testGetIllegalTesterWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150100012
        );

        $request = new Request();
        $controller = new MaintainController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getIllegalTestUserAction($request);
    }

    /**
     * 測試設定遊戲維護資訊未帶入維護開始時間
     */
    public function testSetMaintainByGameWithoutBeginAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No begin_at specified',
            150100006
        );

        $request = new Request();
        $controller = new MaintainController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setMaintainByGameAction($request, 1);
    }

    /**
     * 測試設定遊戲維護資訊未帶入維護結束時間
     */
    public function testSetMaintainByGameWithoutEndAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No end_at specified',
            150100007
        );

        // 測試沒帶入維護結束時間
        $parameters = ['begin_at' => '2013-03-08T00:00:00+0800'];

        $request = new Request([], $parameters);
        $controller = new MaintainController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setMaintainByGameAction($request, 1);
    }

    /**
     * 測試設定遊戲維護資訊帶入錯誤維護區間
     */
    public function testSetMaintainByGameWithInvalidTimeZone()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illgegal game maintain time',
            150100008
        );

        // 測試帶入錯誤維護區間
        $parameters = [
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-07T00:00:00+0800'
        ];

        $request = new Request([], $parameters);
        $controller = new MaintainController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setMaintainByGameAction($request, 1);
    }

    /**
     * 測試設定遊戲維護資訊帶入錯誤notice_interval格式
     */
    public function testSetMaintainByGameWithInvalidNoticeInterval()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid notice_interval',
            150100016
        );

        $parameters = [
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-09T00:00:00+0800',
            'notice_interval' => 'abc'
        ];

        $request = new Request([], $parameters);
        $controller = new MaintainController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setMaintainByGameAction($request, 1);
    }

    /**
     * 測試設定遊戲維護資訊帶入錯誤提醒時間
     */
    public function testSetMaintainByGameWithInvalidNoticeTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal notice time',
            150100017
        );

        $parameters = [
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-09T00:00:00+0800',
            'notice_interval' => 120
        ];

        $request = new Request([], $parameters);
        $controller = new MaintainController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setMaintainByGameAction($request, 1);
    }

    /**
     * 測試新增白名單帶入錯誤IP
     */
    public function testCreateWhitelistWithWrongIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid IP',
            150100013
        );

        $parameters = ['ip' => 'aaa'];

        $request = new Request([], $parameters);
        $controller = new MaintainController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createWhitelistAction($request);
    }

    /**
     * 測試刪除白名單帶入錯誤IP
     */
    public function testDeleteWhitelistWithWrongIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid IP',
            150100013
        );

        $parameters = ['ip' => 'aaa'];

        $request = new Request([], $parameters);
        $controller = new MaintainController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->deleteWhitelistAction($request);
    }
}
