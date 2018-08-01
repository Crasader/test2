<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\CardChargeController;
use Symfony\Component\HttpFoundation\Request;

class CardChargeControllerTest extends ControllerTest
{
    /**
     * 測試新增租卡線上支付未帶入排序設定
     */
    public function testNewCardChargeWithoutOrderStrategy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No order_strategy specified',
            150710001
        );

        $request = new Request();
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的排序設定
     */
    public function testNewCardChargeWithInvalidOrderStrategy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_strategy',
            150710002
        );

        $query = ['order_strategy' => 9];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付未帶入大股東最高存款金額
     */
    public function testNewCardChargeWithoutDepositScMax()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sc_max specified',
            150710003
        );

        $query = ['order_strategy' => 0];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的大股東最高存款金額
     */
    public function testNewCardChargeWithInvalidDepositScMax()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sc_max specified',
            150710003
        );

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => ''
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的大股東最低存款金額
     */
    public function testNewCardChargeWithInvalidDepositScMin()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sc_min specified',
            150710004
        );

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 'A'
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的股東最高存款金額
     */
    public function testNewCardChargeWithInvalidDepositCoMax()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_co_max specified',
            150710005
        );

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => ''
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的股東最低存款金額
     */
    public function testNewCardChargeWithInvalidDepositCoMin()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_co_min specified',
            150710006
        );

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => ''
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的總代理最高存款金額
     */
    public function testNewCardChargeWithInvalidDepositSaMax()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sa_max specified',
            150710007
        );

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => 0,
            'deposit_sa_max' => ''
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的總代理最低存款金額
     */
    public function testNewCardChargeWithInvalidDepositSaMin()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sa_min specified',
            150710008
        );

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => 0,
            'deposit_sa_max' => 0,
            'deposit_sa_min' => ''
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的代理最高存款金額
     */
    public function testNewCardChargeWithInvalidDepositAgMax()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_ag_max specified',
            150710009
        );

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => 0,
            'deposit_sa_max' => 0,
            'deposit_sa_min' => 0,
            'deposit_ag_max' => ''
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不合法的代理最低存款金額
     */
    public function testNewCardChargeWithInvalidDepositAgMin()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_ag_min specified',
            150710010
        );

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => 0,
            'deposit_sa_max' => 0,
            'deposit_sa_min' => 0,
            'deposit_ag_max' => 0,
            'deposit_ag_min' => ''
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增租卡線上支付帶入不存在的domain
     */
    public function testNewCardChargeWithDomainNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No domain found',
            150710011
        );

        $em = $this->getMockEm();

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => 0,
            'deposit_sa_max' => 0,
            'deposit_sa_min' => 0,
            'deposit_ag_max' => 0,
            'deposit_ag_min' => 0
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增重複的租卡線上支付
     */
    public function testNewDuplicateCardCharge()
    {
        $this->setExpectedException(
            'RuntimeException',
            'CardCharge already exists',
            150710012
        );

        $em = $this->getMockEm();
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($cardCharge);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => 0,
            'deposit_sa_max' => 0,
            'deposit_sa_min' => 0,
            'deposit_ag_max' => 0,
            'deposit_ag_min' => 0
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試同分秒新增重複的租卡線上支付
     */
    public function testNewDuplicateCardChargeConcurrency()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150710017
        );

        $em = $this->getMockEm();
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception('An exception occurred while executing', 0, $pdoExcep);

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

        $query = [
            'order_strategy' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => 0,
            'deposit_sa_max' => 0,
            'deposit_sa_min' => 0,
            'deposit_ag_max' => 0,
            'deposit_ag_min' => 0
        ];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試取得不存在的租卡線上支付設定
     */
    public function testGetCardChargeNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardCharge found',
            150710013
        );

        $em = $this->getMockEm();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(2);
    }

    /**
     * 測試設定不存在的租卡線上支付設定
     */
    public function testSetCardChargeNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardCharge found',
            150710013
        );

        $em = $this->getMockEm();

        $request = new Request();
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的排序設定
     */
    public function testSetCardChargeWithInvalidOrderStrategy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_strategy',
            150710002
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['order_strategy' => 999];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的大股東最高存款金額
     */
    public function testSetCardChargeWithDepositScMaxNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sc_max specified',
            150710003
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['deposit_sc_max' => ''];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的大股東最低存款金額
     */
    public function testSetCardChargeWithDepositScMinNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sc_min specified',
            150710004
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['deposit_sc_min' => ''];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的股東最高存款金額
     */
    public function testSetCardChargeWithDepositCoMaxNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_co_max specified',
            150710005
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['deposit_co_max' => ''];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的股東最低存款金額
     */
    public function testSetCardChargeWithDepositCoMinNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_co_min specified',
            150710006
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['deposit_co_min' => ''];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的總代理最高存款金額
     */
    public function testSetCardChargeWithDepositSaMaxNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sa_max specified',
            150710007
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['deposit_sa_max' => ''];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的總代理最低存款金額
     */
    public function testSetCardChargeWithDepositSaMinNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_sa_min specified',
            150710008
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['deposit_sa_min' => ''];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的代理最高存款金額
     */
    public function testSetCardChargeWithDepositAgMaxNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_ag_max specified',
            150710009
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['deposit_ag_max' => ''];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定租卡線上支付設定帶入不合法的代理最低存款金額
     */
    public function testSetCardChargeWithDepositAgMinNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid deposit_ag_min specified',
            150710010
        );

        $em = $this->getMockEm();
        $opLogger = $this->getMockLoggerOperation();

        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($cardCharge);

        $query = ['deposit_ag_min' => ''];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試取得支付平台手續費但租卡線上支付設定不存在
     */
    public function testGetCardPaymentGatewayFeeWithNoCardCharge()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardCharge found',
            150710013
        );

        $em = $this->getMockEm();

        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getCardPaymentGatewayFeeAction(1);
    }

    /**
     * 測試設定支付平台手續費但沒帶入手續費
     */
    public function testSetCardPaymentGatewayFeeWithoutFees()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No fees specified',
            150710014
        );

        $request = new Request();
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCardPaymentGatewayFeeAction($request, 1);
    }

    /**
     * 測試設定支付平台手續費時傳入空陣列
     */
    public function testSetCardPaymentGatewayFeeWithEmptyArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No fees specified',
            150710014
        );

        $query = [];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCardPaymentGatewayFeeAction($request, 1);
    }

    /**
     * 測試設定支付平台手續費但帶入手續費格式錯誤(not array)
     */
    public function testSetCardPaymentGatewayFeeWithErrorFees()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No fees specified',
            150710014
        );

        $query = ['fees' => 123];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCardPaymentGatewayFeeAction($request, 1);
    }

    /**
     * 測試設定支付平台手續費但未帶入手續費率
     */
    public function testSetCardPaymentGatewayFeeWithoutRate()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid CardPaymentGatewayFee rate specified',
            150710015
        );

        $fees[] = ['payment_gateway_id' => 1];
        $query = ['fees' => $fees];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCardPaymentGatewayFeeAction($request, 1);
    }

    /**
     * 測試設定支付平台手續費但帶不是浮點數入手續費率
     */
    public function testSetCardPaymentGatewayFeeWithRateNotFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid CardPaymentGatewayFee rate specified',
            150710015
        );

        $fees[] = [
            'payment_gateway_id' => 1,
            'rate' => 'LetItGo'
        ];
        $query = ['fees' => $fees];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCardPaymentGatewayFeeAction($request, 1);
    }

    /**
     * 測試設定支付平台手續費但租卡線上支付設定不存在
     */
    public function testSetCardPaymentGatewayFeeWithNoCardCharge()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardCharge found',
            150710013
        );

        $em = $this->getMockEm();
        $fees[] = [
            'payment_gateway_id' => 1,
            'rate' => 1.2
        ];
        $query = ['fees' => $fees];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setCardPaymentGatewayFeeAction($request, 1);
    }

    /**
     * 測試設定支付平台手續費但帶支付平台不存在
     */
    public function testSetCardPaymentGatewayFeeWithoutPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            150710016
        );

        $em = $this->getMockEm();
        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($cardCharge);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn(null);

        $fees[] = [
            'payment_gateway_id' => 1,
            'rate' => 1.2
        ];
        $query = ['fees' => $fees];

        $request = new Request([], $query);
        $controller = new CardChargeController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setCardPaymentGatewayFeeAction($request, 1);
    }

    /**
     * Mock a EntityManager
     *
     * @return EntityManager
     */
    private function getMockEm()
    {
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        return $em;
    }

    /**
     * Mock a Operation Logger
     *
     * @return Operation
     */
    private function getMockLoggerOperation()
    {
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        return $opLogger;
    }
}
