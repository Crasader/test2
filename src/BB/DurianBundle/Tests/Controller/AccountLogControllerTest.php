<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\AccountLogController;
use Symfony\Component\HttpFoundation\Request;

class AccountLogControllerTest extends ControllerTest
{
    /**
     * 測試歸零帳戶系統參數發送次數找不到Account紀錄
     */
    public function testZeroAccCountWithoutAccountLog()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such account log',
            160001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $controller = new AccountLogController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->zeroAccCountAction(9999);
    }

    /**
     * 測試設定到帳戶系統紀錄狀態帶入不合法的狀態
     */
    public function testSetAccStatusWithIllegalStatus()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No status specified',
            160002
        );

        $query = ['status' => 5];
        $request = new Request([], $query);

        $controller = new AccountLogController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setStatusAction($request, 1);
    }

    /**
     * 測試設定到帳戶系統紀錄狀態沒帶入狀態
     */
    public function testSetAccStatusWithoutStatus()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No status specified',
            160002
        );

        $request = new Request();

        $controller = new AccountLogController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setStatusAction($request, 1);
    }

    /**
     * 測試設定到帳戶系統紀錄狀態帶入空字串
     */
    public function testSetAccStatusWithNullStatus()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No status specified',
            160002
        );

        $query = ['status' => ''];
        $request = new Request([], $query);

        $controller = new AccountLogController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setStatusAction($request, 1);
    }

    /**
     * 測試設定到帳戶系統紀錄狀態找不到Account紀錄
     */
    public function testSetAccStatusWithoutAccountLog()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such account log',
            160001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $query = ['status' => 1];
        $request = new Request([], $query);

        $controller = new AccountLogController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setStatusAction($request, 999);
    }
}
