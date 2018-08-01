<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\LevelController;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\DomainConfig;

class LevelControllerTest extends ControllerTest
{
    /**
     * 測試新增層級時缺少domain
     */
    public function testCreateWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150620001
        );

        $request = new Request();
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時缺少alias
     */
    public function testCreateWithoutAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No alias specified',
            150620002
        );

        $params = ['domain' => 3];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時alias非UTF8
     */
    public function testCreateButAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'domain' => 3,
            'alias' => mb_convert_encoding('第一層', 'GB2312', 'UTF-8')
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時memo非UTF8
     */
    public function testCreateButMemoNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'domain' => 3,
            'alias' => '第一層',
            'memo' => mb_convert_encoding('備註', 'GB2312', 'UTF-8')
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時代入不合法的order_strategy
     */
    public function testCreateWithInvalidOrderStrategy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_strategy',
            150620003
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '3'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時代入空字串的order_strategy
     */
    public function testCreateWithOrderStrategyIsEmpty()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_strategy',
            150620003
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => ''
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時缺少created_at_start
     */
    public function testCreateWithoutCreatedAtStart()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid created_at_start',
            150620005
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時created_at_start格式不對
     */
    public function testCreateWithInvalidCreatedAtStart()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid created_at_start',
            150620005
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '1234'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時缺少created_at_end
     */
    public function testCreateWithoutCreatedAtEnd()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid created_at_end',
            150620007
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時created_at_end格式不對
     */
    public function testCreateWithInvalidCreatedAtEnd()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid created_at_end',
            150620007
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => 'rwe'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時缺少deposit_count
     */
    public function testCreateWithoutDepositCount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositCount must be an integer',
            150620009
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時deposit_count不是整數
     */
    public function testCreateWithDepositCountNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositCount must be an integer',
            150620009
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 'abc123'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時缺少withdraw_count
     */
    public function testCreateWithoutWithdrawCount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'WithdrawCount must be an integer',
            150620011
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時withdraw_count不是整數
     */
    public function testCreateWithWithdrawCountNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'WithdrawCount must be an integer',
            150620011
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 'abc123'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時缺少deposit_total
     */
    public function testCreateWithoutDepositTotal()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositTotal must be an integer',
            150620013
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時deposit_total不是整數
     */
    public function testCreateWithDepositTotalNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositTotal must be an integer',
            150620013
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 'abc123'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時deposit_total超過上限
     */
    public function testCreateButDepositTotalIsOutOfLimitation()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The deposit_total is out of limitation',
            150620056
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-30 15:07:57',
            'created_at_end' => '2015-09-30 16:07:57',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => '1000000000000'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時缺少deposit_max
     */
    public function testCreateWithoutDepositMax()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositMax must be an integer',
            150620015
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時deposit_max不是整數
     */
    public function testCreateWithDepositMaxNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositMax must be an integer',
            150620015
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 'abc123'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時deposit_max超過上限
     */
    public function testCreateButDepositMaxIsOutOfLimitation()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The deposit_max is out of limitation',
            150620057
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-30 15:07:57',
            'created_at_end' => '2015-09-30 16:07:57',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => '1000000000000'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時缺少withdraw_total
     */
    public function testCreateWithoutWithdrawTotal()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'WithdrawTotal must be an integer',
            150620017
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時withdraw_total不是整數
     */
    public function testCreateWithWithdrawTotalNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'WithdrawTotal must be an integer',
            150620017
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0,
            'withdraw_total' => 'abc123'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時withdraw_total超過上限
     */
    public function testCreateButWithdrawTotalIsOutOfLimitation()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The withdraw_total is out of limitation',
            150620058
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-30 15:07:57',
            'created_at_end' => '2015-09-30 16:07:57',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0,
            'withdraw_total' => '1000000000000'
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時帶入不存在的domain
     */
    public function testCreateWithDomainNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No domain found',
            150620018
        );

        $params = [
            'domain' => 999,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0,
            'withdraw_total' => 0
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時帶入非廳主
     */
    public function testCreateWithUserNotADomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            150620024
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0,
            'withdraw_total' => 0
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getRole')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時新增重複層級別名資料
     */
    public function testCreateWithDuplicateLevelAlias()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate Level alias',
            150620019
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0,
            'withdraw_total' => 0
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getRole')
            ->willReturn(7);

        $duplicateLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($duplicateLevel);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'beginTransaction', 'getRepository', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($user);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時層級已超過限制的數量
     */
    public function testCreateButNumberOfLevelExceedsTheMaxNumber()
    {
        $this->setExpectedException(
            'RangeException',
            'The number of level exceeds the max number',
            150620055
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0,
            'withdraw_total' => 0
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getRole')
            ->willReturn(7);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countNumOf', 'getDefaultOrder'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('countNumOf')
            ->willReturn(51);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository', 'beginTransaction', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($user);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時發生一般的Exception(非重複資料)
     */
    public function testCreateButExceptionOccur()
    {
        $this->setExpectedException(
            'Exception',
            'MySQL server has gone away',
            2006
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0,
            'withdraw_total' => 0
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getRole')
            ->willReturn(7);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'getDefaultOrder'])
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository', 'beginTransaction', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($user);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('MySQL server has gone away', 2006));

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增層級時發生重複資料的Exception
     */
    public function testCreateButDatabaseIsBusy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150620020
        );

        $params = [
            'domain' => 3,
            'alias' => '第三層',
            'order_strategy' => '1',
            'created_at_start' => '2015-09-21 16:21:56',
            'created_at_end' => '2030-09-21 16:21:56',
            'deposit_count' => 0,
            'withdraw_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 0,
            'withdraw_total' => 0
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getRole')
            ->willReturn(7);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'getDefaultOrder'])
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository', 'beginTransaction', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($user);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;

        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150620020, $pdoExcep));

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試修改層級時代入空的alias
     */
    public function testSetWithEmptyAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No alias specified',
            150620002
        );

        $params = ['alias' => ''];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時alias非UTF8
     */
    public function testSetButAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = ['alias' => mb_convert_encoding('第一層', 'GB2312', 'UTF-8')];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時memo非UTF8
     */
    public function testSetButMemoNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = ['memo' => mb_convert_encoding('備註', 'GB2312', 'UTF-8')];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入不合法的order_strategy
     */
    public function testSetWithInvalidOrderStrategy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_strategy',
            150620003
        );

        $params = ['order_strategy' => '3'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入空字串的order_strategy
     */
    public function testSetWithOrderStrategyIsEmpty()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_strategy',
            150620003
        );

        $params = ['order_strategy' => ''];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入格式不對的created_at_start
     */
    public function testSetWithInvalidCreatedAtStart()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid created_at_start',
            150620005
        );

        $params = ['created_at_start' => '1234'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入格式不對的created_at_end
     */
    public function testSetWithInvalidCreatedAtEnd()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid created_at_end',
            150620007
        );

        $params = ['created_at_end' => '1234'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入不是整數的deposit_count
     */
    public function testSetWithDepositCountNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositCount must be an integer',
            150620009
        );

        $params = ['deposit_count' => 'abc123'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入不是整數的withdraw_count
     */
    public function testSetWithWithdrawCountNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'WithdrawCount must be an integer',
            150620011
        );

        $params = ['withdraw_count' => 'abc123'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入不是整數的deposit_total
     */
    public function testSetWithDepositTotalNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositTotal must be an integer',
            150620013
        );

        $params = ['deposit_total' => 'abc123'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時deposit_total超過上限
     */
    public function testSetButDepositTotalIsOutOfLimitation()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The deposit_total is out of limitation',
            150620056
        );

        $params = ['deposit_total' => '1000000000000'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入不是整數的deposit_max
     */
    public function testSetWithDepositMaxNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'DepositMax must be an integer',
            150620015
        );

        $params = ['deposit_max' => 'abc123'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時deposit_max超過上限
     */
    public function testSetButDepositMaxIsOutOfLimitation()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The deposit_max is out of limitation',
            150620057
        );

        $params = ['deposit_max' => '1000000000000'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時代入不是整數的withdraw_total
     */
    public function testSetWithWithdrawTotalNotInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'WithdrawTotal must be an integer',
            150620017
        );

        $params = ['withdraw_total' => 'abc123'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時withdraw_total超過上限
     */
    public function testSetButWithdrawTotalIsOutOfLimitation()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The withdraw_total is out of limitation',
            150620058
        );

        $params = ['withdraw_total' => '1000000000000'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時資料找不到
     */
    public function testSetWithLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150620022
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改層級時重複層級別名資料
     */
    public function testSetWithDuplicateLevelAlias()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate Level alias',
            150620019
        );

        $params = [
            'alias' => '第三層',
            'created_at_start' => '2015-03-01T09:00:00+0800',
            'created_at_end' => '2015-03-15T09:00:00+0800',
            'deposit_count' => '100',
            'deposit_total' => '1000',
            'deposit_max' => '9999',
            'withdraw_count' => '100',
            'withdraw_total' => '9999',
            'memo' => 'edit'
        ];

        $toArray = [
            'alias' => '第一層',
            'domain' => 2
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->setMethods(['toArray'])
            ->getMock();
        $level->expects($this->any())
            ->method('toArray')
            ->willReturn($toArray);

        $duplicateLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($duplicateLevel);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'beginTransaction', 'getRepository', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($level);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setAction($request, 6);
    }

    /**
     * 測試修改層級時發生重複資料的Exception
     */
    public function testSetButDatabaseIsBusy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150620020
        );

        $params = ['alias' => '第三層'];

        $toArray = [
            'alias' => '第一層',
            'domain' => 2
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->setMethods(['toArray'])
            ->getMock();
        $level->expects($this->any())
            ->method('toArray')
            ->willReturn($toArray);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'beginTransaction', 'getRepository', 'flush', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($level);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;

        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150620020, $pdoExcep));

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setAction($request, 6);
    }

    /**
     * 測試修改層級時發生一般的Exception(非重複資料)
     */
    public function testSetButExceptionOccur()
    {
        $this->setExpectedException(
            'Exception',
            'MySQL server has gone away',
            2006
        );

        $params = ['alias' => '第三層'];

        $toArray = [
            'alias' => '第一層',
            'domain' => 2
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->setMethods(['toArray'])
            ->getMock();
        $level->expects($this->any())
            ->method('toArray')
            ->willReturn($toArray);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'beginTransaction', 'getRepository', 'flush', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($level);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('MySQL server has gone away', 2006));

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setAction($request, 6);
    }

    /**
     * 測試回傳單筆層級資料找不到
     */
    public function testGetWithLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150620022
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction($request, 999);
    }

    /**
     * 測試刪除層級找不到
     */
    public function testRemoveWithLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150620022
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction($request, 1);
    }

    /**
     * 測試刪除層級時當會員人數不為零不能刪除
     */
    public function testRemoveButLeveltUserCountIsNotZero()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove Level when Level has user',
            150620023
        );

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->setMethods(['getUserCount'])
            ->getMock();
        $level->expects($this->once())
            ->method('getUserCount')
            ->willReturn(10);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($level);

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction($request, 2);
    }

    /**
     * 測試刪除層級時，層級被設定成使用者的預設層級
     */
    public function testRemoveButLevelIsSetByPresetLevel()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove when Level is set by PresetLevel',
            150620044
        );

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->setMethods(['getUserCount'])
            ->getMock();
        $level->expects($this->once())
            ->method('getUserCount')
            ->willReturn(0);

        $presetLevel = $this->getMockBuilder('BB\DurianBundle\Entity\PresetLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($presetLevel);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($level);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction($request, 2);
    }

    /**
     * 測試刪除層級時會員層級在轉移中，不能刪除
     */
    public function testRemoveButUserLevelTransferring()
    {
        $this->setExpectedException(
            'RuntimeException',
            'User Level transferring',
            150620030
        );

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->setMethods(['getUserCount'])
            ->getMock();
        $level->expects($this->once())
            ->method('getUserCount')
            ->willReturn(0);

        $ltTarget = $this->getMockBuilder('BB\DurianBundle\Entity\LevelTransfer')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->at(2))
            ->method('findOneBy')
            ->willReturn($ltTarget);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($level);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction($request, 2);
    }

    /**
     * 測試刪除層級時層級被設定成層級網址，不能刪除
     */
    public function testRemoveButLevelIsSetByLevelUrl()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove when Level is set by LevelUrl',
            150620059
        );

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->setMethods(['getUserCount'])
            ->getMock();
        $level->expects($this->once())
            ->method('getUserCount')
            ->willReturn(0);

        $levelUrl = $this->getMockBuilder('BB\DurianBundle\Entity\LevelUrl')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->at(3))
            ->method('findOneBy')
            ->willReturn($levelUrl);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($level);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction($request, 2);
    }

    /**
     * 測試層級轉移時缺少source
     */
    public function testTransferWithoutSource()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No source specified',
            150620031
        );

        $request = new Request();
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時source帶入非陣列
     */
    public function testTransferWithSourceIsString()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No source specified',
            150620031
        );

        $params = ['source' => 'source'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時source帶入空陣列
     */
    public function testTransferWithEmptySource()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No source specified',
            150620031
        );

        $params = ['source' => []];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時缺少target
     */
    public function testTransferWithoutTarget()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No target specified',
            150620025
        );

        $params = ['source' => [1]];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時帶入的source與target相同
     */
    public function testTransferButSourceSameAsTarget()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Source level can not be the same as target level',
            150620026
        );

        $params = [
            'source' => [2, 3, 1, 4],
            'target' => 1
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時帶入不存在的source
     */
    public function testTransferWithSourceNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No source level found',
            150620027
        );

        $params = [
            'source' => [1, 3],
            'target' => 2
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$level]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時帶入不存在的target
     */
    public function testTransferWithTargetNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No target level found',
            150620028
        );

        $params = [
            'source' => [1, 3],
            'target' => 2
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$level, $level]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時但來源層級的廳與目標層級不同
     */
    public function testTransferButTransferDifferentDomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot transfer to different domain',
            150620029
        );

        $params = [
            'source' => [1],
            'target' => 2
        ];

        $sourceLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $sourceLevel->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $targetLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $targetLevel->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$sourceLevel]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($targetLevel);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時會員層級正在轉移中
     */
    public function testTransferButUserLevelTransferring()
    {
        $this->setExpectedException(
            'RuntimeException',
            'User Level transferring',
            150620030
        );

        $params = [
            'source' => [1],
            'target' => 2
        ];

        $sourceLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $sourceLevel->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $targetLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $targetLevel->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$sourceLevel]);

        $levelTransfer = $this->getMockBuilder('BB\DurianBundle\Entity\LevelTransfer')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($targetLevel);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($levelTransfer);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時新增重複廳及來源資料
     */
    public function testTransferWithDuplicateDomainAndSource()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150620020
        );

        $params = [
            'source' => [1],
            'target' => 2
        ];

        $sourceLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $sourceLevel->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $targetLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $targetLevel->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findBy')
            ->willReturn([$sourceLevel]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($targetLevel);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;

        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150620020, $pdoExcep));

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->transferAction($request);
    }

    /**
     * 測試層級轉移時發生一般的Exception(非重複資料)
     */
    public function testTransferButExceptionOccur()
    {
        $this->setExpectedException(
            'Exception',
            'MySQL server has gone away',
            2006
        );

        $params = [
            'source' => [1],
            'target' => 2
        ];

        $sourceLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $sourceLevel->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $targetLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $targetLevel->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findBy')
            ->willReturn([$sourceLevel]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($targetLevel);
        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('MySQL server has gone away', 2006));

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->transferAction($request);
    }

    /**
     * 測試新增層級網址時未帶入level_id
     */
    public function testCreateLevelUrlWithoutLevelId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            150620038
        );

        $request = new Request();
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createUrlAction($request);
    }

    /**
     * 測試新增層級網址時未帶入url
     */
    public function testCreateLevelUrlWithoutUrl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No url specified',
            150620033
        );

        $params = ['level_id' => '1'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createUrlAction($request);
    }

    /**
     * 測試新增層級網址時找不到層級
     */
    public function testCreateLevelUrlWithLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150620022
        );

        $params = [
            'level_id' => '999',
            'url' => 'acc.com'
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'clear'])
            ->getMock();

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createUrlAction($request);
    }

    /**
     * 測試新增層級網址時啟用網址已存在
     */
    public function testCreateLevelUrlWithEnableLevelUrlAlreadyExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Enable LevelUrl Already Exist',
            150620032
        );

        $params = [
            'level_id' => '999',
            'url' => 'acc.com',
            'enable' => '1'
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $levelUrl = $this->getMockBuilder('BB\DurianBundle\Entity\LevelUrl')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn([$levelUrl]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($level);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createUrlAction($request);
    }

    /**
     * 測試新增層級網址時發生一般的Exception(非重複資料)
     */
    public function testCreateLevelUrlButExcepitonOccur()
    {
        $this->setExpectedException(
            'Exception',
            'MySQL server has gone away',
            2006
        );

        $params = [
            'level_id' => '999',
            'url' => 'acc.com'
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($level);

        $em->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('MySQL server has gone away', 2006));

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();

        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->createUrlAction($request);
    }

    /**
     * 測試新增層級網址時發生重複資料的Exception
     */
    public function testCreateLevelUrlButDatabaseIsBusy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150620020
        );

        $params = [
            'level_id' => '999',
            'url' => 'acc.com'
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($level);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \RuntimeException('Database is busy', 150620020, $pdoExcep);

        $em->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();

        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->createUrlAction($request);
    }

    /**
     * 測試修改層級網址時找不到層級網址
     */
    public function testSetLevelUrlWithLevelUrlNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No LevelUrl found',
            150620035
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setUrlAction($request, 999);
    }

    /**
     * 測試修改層級網址時啟用網址已存在
     */
    public function testSetLevelUrlWithEnableLevelUrlAlreadyExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Enable LevelUrl Already Exist',
            150620032
        );

        $params = ['enable' => '1'];

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();

        $levelUrl = $this->getMockBuilder('BB\DurianBundle\Entity\LevelUrl')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn([$levelUrl]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($levelUrl);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setUrlAction($request, 1);
    }

    /**
     * 測試刪除層級網址時找不到層級網址
     */
    public function testRmoveLevelUrlWithLevelUrlNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No LevelUrl found',
            150620035
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'clear'])
            ->getMock();

        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeUrlAction(999);
    }

    /**
     * 測試設定層級順序沒帶入層級
     */
    public function testSetLevelOrderWithoutLevels()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No levels specified',
            150620037
        );

        $request = new Request();
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序帶入層級參數非陣列
     */
    public function testSetLevelOrderWithLevelsNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No levels specified',
            150620037
        );

        $params = ['levels' => ''];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序沒帶入第一筆層級id
     */
    public function testSetLevelOrderWithoutFirstLevelId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            150620038
        );

        $params = [
            'levels' => [
                [
                    'order_id' => 1,
                    'version' => 5
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序帶入第一筆層級不存在
     */
    public function testSetLevelOrderWithFirstLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150620022
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn(null);

        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'order_id' => 1,
                    'version' => 5
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序帶入層級id不存在
     */
    public function testSetLevelOrderWithoutLevelId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            150620038
        );

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();
        $level1 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level1->expects($this->any())
            ->method('getVersion')
            ->willReturn(5);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level1);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($level1);
        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'order_id' => 1,
                    'version' => 5
                ],
                [
                    'order_id' => 2,
                    'version' => 4
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序，順序參數不存在
     */
    public function testSetLevelOrderWithoutOrderId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No order_id specified',
            150620039
        );

        $level1 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level1);

        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'version' => 5
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序版本號不存在
     */
    public function testSetLevelOrderWithoutVersion()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No version specified',
            150620040
        );

        $level1 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level1);

        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'order_id' => 1
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序帶入層級不存在
     */
    public function testSetLevelOrderWithLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150620022
        );

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);

        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'order_id' => 1,
                    'version' => 5
                ],
                [
                    'level_id' => 2,
                    'order_id' => 2,
                    'version' => 4
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序帶入廳主不相符
     */
    public function testSetLevelOrderWithDomainNotMatch()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Level domain not match',
            150620041
        );

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();
        $level1 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level1->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $level1->expects($this->any())
            ->method('getVersion')
            ->willReturn(5);
        $level2 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level2->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level1);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($level1);
        $em->expects($this->at(4))
            ->method('find')
            ->willReturn($level2);
        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'order_id' => 1,
                    'version' => 5
                ],
                [
                    'level_id' => 2,
                    'order_id' => 2,
                    'version' => 4
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序帶入版本號不相符
     */
    public function testSetLevelOrderWithVersionNotMatch()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Level order has been changed',
            150620042
        );

        $level1 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level1->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $level1->expects($this->any())
            ->method('getVersion')
            ->willReturn(3);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level1);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($level1);

        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'order_id' => 1,
                    'version' => 5
                ],
                [
                    'level_id' => 2,
                    'order_id' => 2,
                    'version' => 4
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定層級順序回傳順序id重複
     */
    public function testSetLevelOrderWithDuplicateOrderId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate Level orderId',
            150620043
        );

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getDuplicatedOrder'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('getDuplicatedOrder')
            ->willReturn(1);
        $level1 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level1->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $level1->expects($this->any())
            ->method('getVersion')
            ->willReturn(2);
        $level2 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level2->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $level2->expects($this->any())
            ->method('getVersion')
            ->willReturn(3);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level1);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($level1);
        $em->expects($this->at(4))
            ->method('find')
            ->willReturn($level2);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'order_id' => 1,
                    'version' => 2
                ],
                [
                    'level_id' => 3,
                    'order_id' => 2,
                    'version' => 3
                ]
            ]
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試回傳層級內使用者資料帶入不合法的開始筆數
     */
    public function testGetLevelUsersByInvalidFirstResult()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid first_result',
            150610004
        );

        $parameters = [
            'first_result' => -5,
            'max_results' => 1
        ];

        $request = new Request([], $parameters);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getUsersAction($request, 1);
    }

    /**
     * 測試回傳層級內使用者資料帶入不合法的顯示筆數
     */
    public function testGetLevelUsersByInvalidMaxResults()

    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid max_results',
            150610005
        );

        $parameters = [
            'first_result' => 0,
            'max_results' => -1
        ];

        $request = new Request([], $parameters);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getUsersAction($request, 1);
    }

    /**
     * 測試回傳層級內使用者資料帶入不存在的Level
     */
    public function testGetLevelUsersWithLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150620022
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getUsersAction($request, 999);
    }

    /**
     * 測試回傳層級內使用者資料帶入不存在的Domain
     */
    public function testGetLevelUsersWithDomainNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No domain found',
            150620018
        );

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($level);
        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $emShare->expects($this->at(0))
            ->method('find')
            ->willReturn(new DomainConfig(1, 'test1234', 'dt'));

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->getUsersAction($request, 1);
    }

    /**
     * 測試新增預設層級時，未帶入層級id
     */
    public function testCreatePresetWithoutLevelId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            150620038
        );

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createPresetAction($request, 35660);
    }

    /**
     * 測試新增預設層級時，帶入不存在的使用者
     */
    public function testCreatePresetButNoSuchUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150620045
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $params = ['level_id' => 1];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createPresetAction($request, 35660);
    }

    /**
     * 測試新增預設層級時，帶入不存在的層級
     */
    public function testCreatePresetButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150620022
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);

        $params = ['level_id' => 1];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createPresetAction($request, 35660);
    }

    /**
     * 測試新增預設層級時，帶入的層級的廳與使用者的不同
     */
    public function testCreatePresetButLevelDomainNotMatch()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Level domain not match',
            150620041
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getDomain')
            ->willReturn(1);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level->expects($this->once())
            ->method('getDomain')
            ->willReturn(2);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);

        $params = ['level_id' => 1];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createPresetAction($request, 35660);
    }

    /**
     * 測試新增預設層級時，但使用者的預設層級已存在
     */
    public function testCreatePresetButPresetLevelAlreadyExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PresetLevel already exists',
            150620047
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getDomain')
            ->willReturn(1);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level->expects($this->once())
            ->method('getDomain')
            ->willReturn(1);

        $presetLevel = $this->getMockBuilder('BB\DurianBundle\Entity\PresetLevel')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($presetLevel);

        $params = ['level_id' => 1];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createPresetAction($request, 35660);
    }

    /**
     * 測試新增預設層級時，新增重複的使用者資料
     */
    public function testCreatePresetWithDuplicateUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150620020
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level->expects($this->once())
            ->method('getDomain')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;

        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150640005, $pdoExcep));

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $params = ['level_id' => 1];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->createPresetAction($request, 35660);
    }

    /**
     * 測試新增預設層級時，發生一般的Exception(非重複資料)
     */
    public function testCreatePresetButExceptionOccur()
    {
        $this->setExpectedException(
            'Exception',
            'MySQL server has gone away',
            2006
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level->expects($this->once())
            ->method('getDomain')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);

        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('MySQL server has gone away', 2006));

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $params = ['level_id' => 1];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->createPresetAction($request, 35660);
    }

    /**
     * 測試刪除預設層級時，帶入未設定預設層級的使用者
     */
    public function testRemovePresetButPresetLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PresetLevel found',
            150620046
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removePresetAction(35660);
    }

    /**
     * 測試回傳層級列表帶入不合法的開始筆數
     */
    public function testListWithInvalidFirstResult()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid first_result',
            150610004
        );

        $parameters = [
            'first_result' => -5,
            'max_results' => 1
        ];

        $request = new Request($parameters);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->listAction($request);
    }

    /**
     * 測試回傳層級列表帶入不合法的顯示筆數
     */
    public function testListByInvalidWithInvalidMaxResults()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid max_results',
            150610005
        );

        $parameters = [
            'first_result' => 0,
            'max_results' => -1
        ];

        $request = new Request($parameters);
        $controller = new LevelController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->listAction($request);
    }

    /**
     * 測試設定層級幣別付款設定沒帶入幣別
     */
    public function testSetCurrencyWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No currency specified',
            150620048
        );

        $request = new Request();
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCurrencyAction($request, 1);
    }

    /**
     * 測試設定層級幣別付款設定帶入幣別為空
     */
    public function testSetCurrencyWithCurrencyIsNull()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No currency specified',
            150620048
        );

        $params = ['currency' => ''];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCurrencyAction($request, 1);
    }

    /**
     * 測試設定層級幣別付款設定帶入幣別不合法
     */
    public function testSetCurrencyWithIllegalCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150620049
        );

        $params = ['currency' => 'ABC'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCurrencyAction($request, 1);
    }

    /**
     * 測試設定層級幣別付款設定沒帶入支付設定
     */
    public function testSetCurrencyWithoutPaymentCharge()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No PaymentCharge specified',
            150620050
        );

        $params = ['currency' => 'CNY'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCurrencyAction($request, 1);
    }

    /**
     * 測試設定層級幣別付款設定代入不存在的支付設定
     */
    public function testSetCurrencyCanNotFoundPaymentCharge()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentCharge found',
            150620051
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $params = [
            'currency' => 'CNY',
            'payment_charge_id' => 1
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setCurrencyAction($request, 1);
    }

    /**
     * 測試設定層級幣別付款設定找不到層級幣別資料
     */
    public function testSetCurrencyWithNoLevelCurrencyFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No LevelCurrency found',
            150620052
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($paymentCharge);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $params = [
            'currency' => 'CNY',
            'payment_charge_id' => 1
        ];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setCurrencyAction($request, 1);
    }

    /**
     * 測試取得層級幣別資料, 代入不支援的幣別
     */
    public function testGetLevelCurrencyWithIllegalCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150620049
        );

        $params = ['currency' => 'ABC'];

        $request = new Request([], $params);
        $controller = new LevelController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCurrencyAction($request, 1);
    }
}
