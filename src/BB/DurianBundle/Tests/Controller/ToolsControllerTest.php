<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\ToolsController;

class ToolsControllerTest extends ControllerTest
{
    /**
     * 測試列出裝置所有綁定使用者沒給裝置識別ID
     */
    public function testListBindingUsersByDeviceWithoutAppId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No app_id specified',
            150170035
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new ToolsController();
        $controller->setContainer($container);

        $controller->listBindingUsersByDeviceAction($request);
    }

    /**
     * 測試修改現金明細建立時間，帶入錯誤id格式觸發例外
     */
    public function testReviseCashEntryActionWithInvalidId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid cash entry id',
            150170020
        );

        $parameters = [
            'entry_id' => 'abc',
            'at'       => '2013-12-01T07:59:59+0800',
            'new_at'   => '2013-12-30T07:59:59+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->reviseCashEntryAction($request);
    }

    /**
     * 測試修改現金明細建立時間，帶入錯誤時間格式觸發例外
     */
    public function testReviseCashEntryActionWithInvalidAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid cash entry at',
            150170021
        );

        $parameters = [
            'entry_id' => '1',
            'at'       => '2013-12-32T25:61:62+0800',
            'new_at'   => '2013-12-30T07:59:59+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->reviseCashEntryAction($request);
    }

    /**
     * 測試修改現金明細建立時間，查無明細觸發例外
     */
    public function testReviseCashEntryActionButNoEntryFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash entry found',
            150170024
        );

        $parameters = [
            'entry_id' => '1',
            'at'       => '2013-12-01T07:59:59+0800',
            'new_at'   => '2013-12-30T07:59:59+0800'
        ];

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findOneBy'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue(null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['getRepository', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.his_entity_manager', $em);

        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->reviseCashEntryAction($request);
    }

    /**
     * 測試修改假現金明細建立時間，帶入錯誤id格式觸發例外
     */
    public function testReviseCashFakeEntryActionWithInvalidId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid cash fake entry id',
            150170022
        );

        $parameters = [
            'entry_id' => 'abc',
            'at'       => '2013-12-01T07:59:59+0800',
            'new_at'   => '2013-12-30T07:59:59+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->reviseCashfakeEntryAction($request);
    }

    /**
     * 測試修改假現金明細建立時間，帶入錯誤時間格式觸發例外
     */
    public function testReviseCashFakeEntryActionWithInvalidAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid cash fake entry at',
            150170023
        );

        $parameters = [
            'entry_id' => '1',
            'at'       => '2013-12-32T25:61:62+0800',
            'new_at'   => '2013-12-30T07:59:59+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->reviseCashfakeEntryAction($request);
    }

    /**
     * 測試修改假現金明細建立時間，查無明細觸發例外
     */
    public function testReviseCashFakeEntryActionButNoEntryFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash fake entry found',
            150170025
        );

        $parameters = [
            'entry_id' => '1',
            'at'       => '2013-12-01T07:59:59+0800',
            'new_at'   => '2013-12-30T07:59:59+0800'
        ];

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findOneBy'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue(null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['getRepository', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.his_entity_manager', $em);

        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->reviseCashfakeEntryAction($request);
    }

    /**
     * 測試修正背景，但未指定執行數量或啟用狀態
     */
    public function testSetBgProcessWithoutNumAndEnable()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No name of process, number or enable specified',
            150170008
        );

        $parameters = ['process' => ['activate-sl-next']];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->setBgProcessAction($request);
    }

    /**
     * 測試修正背景，但沒有選擇程式名稱
     */
    public function testSetBgProcessWithNameNotSpecified()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No name of process, number or enable specified',
            150170008
        );

        $parameters = [
            'process' => [''],
            'num' => ['1']
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->setBgProcessAction($request);
    }

    /**
     * 測試修正背景執行數量，但num為字串
     */
    public function testSetBgProcessWithEmptyNum()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid number',
            150170009
        );

        $parameters = [
            'process' => ['activate-sl-next'],
            'num' => ['wrong param']
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->setBgProcessAction($request);
    }

    /**
     * 測試刪除kue job，未帶類型
     */
    public function testDeleteKueJobWithoutType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No kue type or status specified',
            150170029
        );

        $parameter = [
            'type' => 'null',
            'status' => 'null',
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameter);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->deleteKueJobAction($request);
    }

    /**
     * 測試刪除kue job，未帶狀態
     */
    public function testDeleteKueJobWithoutNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No kue type or status specified',
            150170029
        );

        $parameter = [
            'type' => 'test',
            'status' => 'null'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameter);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->deleteKueJobAction($request);
    }

    /**
     * 測試刪除kue job，區間帶負數
     */
    public function testDeleteKueJobWithNegativeFrom()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid range specified',
            150170030
        );

        $parameter = [
            'type' => 'test',
            'status' => 'active',
            'from' => -1,
            'to' => -1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameter);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->deleteKueJobAction($request);
    }

    /**
     * 測試刪除kue job，無效的區間
     */
    public function testDeleteKueJobWithInvalidRange()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid range specified',
            150170030
        );

        $parameter = [
            'type' => 'test',
            'status' => 'active',
            'from' => 2,
            'to' => 1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $parameter);
        $controller = new ToolsController();
        $controller->setContainer($container);
        $controller->deleteKueJobAction($request);
    }
}
