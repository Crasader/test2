<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\MerchantCardController;
use Symfony\Component\HttpFoundation\Request;

class MerchantCardControllerTest extends ControllerTest
{
    /**
     * 測試新增租卡商家沒帶入支付平台
     */
    public function testNewMerchantCardWithoutPaymentGatewayId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No payment_gateway_id specified',
            700001
        );

        $parameters = [
            'alias' => 'EZPAY',
            'number' => '123456789',
            'currency' => 'CNY',
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家沒帶入別名
     */
    public function testNewMerchantCardWithoutAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantCard alias',
            700002
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'number' => '123456789',
            'currency' => 'CNY',
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家的alias輸入非UTF8字串
     */
    public function testNewMerchantCardAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'number' => '123456789',
            'currency' => 'CNY',
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家沒帶入商號
     */
    public function testNewMerchantCardWithoutNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantCard number',
            700003
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'currency' => 'CNY',
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家沒帶入廳主
     */
    public function testNewMerchantCardWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            700008
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家沒帶入幣別
     */
    public function testNewMerchantCardWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            700004
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家帶入系統不支援的幣別
     */
    public function testNewMerchantCardWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            700004
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'RMB',
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家但密鑰長度過長
     */
    public function testNewMerchantCardButPrivateKeyIsTooLong()
    {
        $this->setExpectedException(
            'RangeException',
            'Private Key is too long',
            150700034
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => str_repeat('1', 1025),
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家帶入不存在的支付平台
     */
    public function testNewMerchantCardWithPaymentGatewayNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            700025
        );

        $parameters = [
            'payment_gateway_id' => 5566,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家帶入被刪除的支付平台
     */
    public function testNewMerchantCardWithRemovedPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            700025
        );

        $parameters = [
            'payment_gateway_id' => 77,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
        ];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增租卡商家帶入支付平台不支援的幣別
     */
    public function testNewMerchantCardWithCurrencyNotSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Currency is not support by PaymentGateway',
            700005
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'VND',
        ];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試同分秒新增租卡商家
     */
    public function testNewMerchantCardConcurrency()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Could not create MerchantCard because MerchantCard is updating',
            700006
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'VND',
        ];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGatewayCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGatewayCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'updatePaymentGatewayVersion'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($paymentGatewayCurrency);
        $entityRepo->expects($this->once())
            ->method('updatePaymentGatewayVersion')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'beginTransaction', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試取得不存在的租卡商家
     */
    public function testGetMerchantCardNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(1);
    }

    /**
     * 測試修改租卡商家帶入錯誤的別名
     */
    public function testSetMerchantCardWithInvalidAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantCard alias',
            700002
        );

        $parameters = ['alias' => ''];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家alias非UTF8
     */
    public function testSetMerchantCardAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = ['alias' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家帶入錯誤的商號
     */
    public function testSetMerchantCardWithInvalidNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantCard number',
            700003
        );

        $parameters = ['number' => ''];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家帶入錯誤的廳主
     */
    public function testSetMerchantCardWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            700008
        );

        $parameters = ['domain' => ''];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改不存在的租卡商家
     */
    public function testSetNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家帶入不存在的支付平台
     */
    public function testSetMerchantCardWithPamentGatewayNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            700025
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);

        $parameters = ['payment_gateway_id' => 5566];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家帶入被刪除的支付平台
     */
    public function testSetMerchantCardWithRemovedPamentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            700025
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentGateway);

        $parameters = ['payment_gateway_id' => 77];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家帶入重複的商號(支付平台)
     */
    public function testSetMerchantCardWithDuplicateNumberByPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate MerchantCard number',
            700007
        );

        $logOperation = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($merchantCard);
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger->expects($this->any())
            ->method('create')
            ->willReturn($logOperation);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentGateway);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $parameters = ['payment_gateway_id' => 2];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家帶入重複的商號(商號)
     */
    public function testSetMerchantCardWithDuplicateNumberByNumber()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate MerchantCard number',
            700007
        );

        $logOperation = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($merchantCard);
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger->expects($this->any())
            ->method('create')
            ->willReturn($logOperation);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $parameters = ['number' => 5566002];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家帶入支付平台不支援的幣別(改幣別)
     */
    public function testSetMerchantCardWithInvalidCurrency()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Currency is not support by PaymentGateway',
            700005
        );

        $logOperation = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger->expects($this->any())
            ->method('create')
            ->willReturn($logOperation);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $parameters =  ['currency' => 'USD'];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改租卡商家帶入支付平台不支援的幣別(改支付平台)
     */
    public function testSetMerchantCardWithInvalidPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Currency is not support by PaymentGateway',
            700005
        );

        $logOperation = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger->expects($this->any())
            ->method('create')
            ->willReturn($logOperation);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentGateway);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $parameters = ['payment_gateway_id' => 1];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試刪除租卡商家不存在
     */
    public function testRemoveNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試刪除啟用的租卡商家
     */
    public function testRemoveEnabledMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot delete when MerchantCard enabled',
            700009
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isEnabled')
            ->willReturn(1);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試刪除已暫停租卡商家
     */
    public function testRemoveSuspendedMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot delete when MerchantCard suspended',
            700010
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isSuspended')
            ->willReturn(1);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試取得租卡商家列表帶入錯誤幣別
     */
    public function testMerchantCardListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            700011
        );

        $parameters = ['currency' => 'RMB'];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listAction($request);
    }

    /**
     * 測試取得租卡商家列表帶入空幣別
     */
    public function testMerchantCardListWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            700011
        );

        $parameters = ['currency' => ''];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listAction($request);
    }

    /**
     * 測試停用租卡商家不存在
     */
    public function testDisableNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->disableAction(1);
    }

    /**
     * 測試停用未核准的租卡商家
     */
    public function testDisableUnapprovedMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot modify when MerchantCard is not approved',
            700012
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->disableAction(1);
    }

    /**
     * 測試啟用租卡商家不存在
     */
    public function testEnableNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試啟用未核准的租卡商家
     */
    public function testEnableUnapprovedMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot modify when MerchantCard is not approved',
            700012
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試啟用已刪除的租卡商家
     */
    public function testEnableMerchantCardButMerchantRemoved()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantCard is removed',
            150700035
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchantCard->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試啟用支付平台被刪除的租卡商家
     */
    public function testEnableMerchantCardButPaymentGatewayRemoved()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway is removed',
            700013
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchantCard->expects($this->once())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試暫停租卡商家不存在
     */
    public function testSuspendNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->suspendAction(1);
    }

    /**
     * 測試暫停未核准的租卡商家
     */
    public function testSuspendUnapprovedMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot modify when MerchantCard is not approved',
            700012
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->suspendAction(1);
    }

    /**
     * 測試暫停核准但被停用的租卡商家
     */
    public function testSuspendApprovedAndDisabledMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot modify when MerchantCard disabled',
            700014
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchantCard->expects($this->once())
            ->method('isEnabled')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->suspendAction(1);
    }

    /**
     * 測試恢復租卡商家不存在
     */
    public function testResumeNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->resumeAction(1);
    }

    /**
     * 測試恢復未核准的租卡商家
     */
    public function testResumeUnapprovedMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot modify when MerchantCard is not approved',
            700012
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->resumeAction(1);
    }

    /**
     * 測試恢復核准但被停用的租卡商家
     */
    public function testResumeApprovedAndDisabledMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot modify when MerchantCard disabled',
            700014
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchantCard->expects($this->once())
            ->method('isEnabled')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->resumeAction(1);
    }

    /**
     * 測試核准不存在的租卡商家
     */
    public function testApproveNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->approveAction(1);
    }

    /**
     * 測試取得租卡商家的付款方式租卡商家不存在
     */
    public function testMerchantCardGetPaymentMethodButNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getPaymentMethodAction(1);
    }

    /**
     * 測試設定租卡商家的付款方式租卡商家不存在
     */
    public function testMerchantCardSetPaymentMethodButNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $conn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getConnection', 'getRepository', 'clear'])
            ->getMock();
        $em->expects($this->once())
            ->method('getConnection')
            ->willReturn($conn);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setPaymentMethodAction($request, 1);
    }

    /**
     * 測試取得租卡商家的付款廠商租卡商家不存在
     */
    public function testMerchantCardGetPaymentVendorButNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getPaymentVendorAction(1);
    }

    /**
     * 測試設定租卡商家的付款廠商租卡商家不存在
     */
    public function testMerchantCardSetPaymentVendorButNoneExistMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'beginTransaction', 'getRepository', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setPaymentVendorAction($request, 1);
    }

    /**
     * 測試租卡商家設定的付款廠商不在支付平台設定的可用廠商中
     */
    public function testMerchantCardSetPaymentVendorNotSupportByPaymentGateway()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal PaymentVendor',
            700017
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentVendorOptionByMerchantCard'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('getPaymentVendorOptionByMerchantCard')
            ->willReturn([1, 2, 4, 5]);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'beginTransaction', 'getRepository', 'rollback', 'clear'])
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $paymentVendor = [1, 3];
        $parameters = ['payment_vendor' => $paymentVendor];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setPaymentVendorAction($request, 1);
    }

    /**
     * 測試同分秒新增租卡商家支援銀行
     */
    public function testMerchantCardSetPaymentVendorWithDuplicatedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            700033
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentVendor'])
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('getPaymentVendor')
            ->willReturn($paymentVendor);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentVendorOptionByMerchantCard', 'find'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('getPaymentVendorOptionByMerchantCard')
            ->willReturn([1, 2, 4, 5]);
        $entityRepo->expects($this->any())
            ->method('find')
            ->willReturn($paymentVendor);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'beginTransaction', 'getRepository', 'flush', 'rollback', 'persist', 'clear'])
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

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

        $parameters = ['payment_vendor' => [1, 4]];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setPaymentVendorAction($request, 1);
    }

    /**
     * 取得租卡商家排序設定但租卡商家不存在
     */
    public function testGetMerchantCardOrderIdMerchantCardNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getOrderAction(999);
    }

    /**
     * 取得租卡商家排序設定但無設定值
     */
    public function testgetMerchantCardOrderIdSettingNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCardOrder found',
            700018
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getOrderAction(999);
    }

    /**
     * 設定租卡商家排序但沒傳domain
     */
    public function testSetMerchantCardOrderWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            700008
        );

        $request = new Request();
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request);
    }

    /**
     * 設定租卡商家排序但沒傳設定參數
     */
    public function testSetMerchantCardOrderWithoutMerchantCards()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No merchant_cards specified',
            700019
        );

        $parameter = ['domain' => 2];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request);
    }

    /**
     * 設定租卡商家排序傳入不合法順序
     */
    public function testSetMerchantCardOrderWithInvalidOrderId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_id',
            700020
        );

        $parameter = [
            'domain' => 2,
            'merchant_cards' => [
                [
                    'merchant_card_id' => 1,
                    'order_id' => 't',
                    'version' => 1
                ]
            ]
        ];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request);
    }

    /**
     * 設定租卡商家排序租卡商家不存在
     */
    public function testSetMerchantCardOrderButMerchantCardNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $parameter = [
            'domain' => 2,
            'merchant_cards' => [
                [
                    'merchant_card_id' => 1,
                    'order_id' => 1,
                    'version' => 1
                ]
            ]
        ];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 設定租卡商家排序但商家未啟用
     */
    public function testSetMerchantCardOrderWithDisableMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot modify when MerchantCard disabled',
            700014
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('isEnabled')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($merchantCard);

        $parameter = [
            'domain' => 2,
            'merchant_cards' => [
                [
                    'merchant_card_id' => 1,
                    'order_id' => 1,
                    'version' => 1
                ]
            ]
        ];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 設定租卡商家排序但租卡排序不存在
     */
    public function testSetMerchantCardOrderOrderNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCardOrder found',
            700018
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('isEnabled')
            ->willReturn(1);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($merchantCard);

        $parameter = [
            'domain' => 2,
            'merchant_cards' => [
                [
                    'merchant_card_id' => 1,
                    'order_id' => 1,
                    'version' => 1
                ]
            ]
        ];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 設定租卡商家排序但版本錯誤
     */
    public function testSetMerchantCardOrderVersionError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantCardOrder has been changed',
            700021
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('isEnabled')
            ->willReturn(1);
        $merchantCardOrder = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCardOrder')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($merchantCardOrder);

        $parameter = [
            'domain' => 2,
            'merchant_cards' => [
                [
                    'merchant_card_id' => 1,
                    'order_id' => 1,
                    'version' => 1
                ]
            ]
        ];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 設定租卡商家排序但排序重複
     */
    public function testSetMerchantCardOrderButOrderDuplicate()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate order_id',
            700022
        );

        $logOperation = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->any())
            ->method('isEnabled')
            ->willReturn(1);
        $merchantCardOrder = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCardOrder')
            ->disableOriginalConstructor()
            ->setMethods(['getVersion'])
            ->getMock();
        $merchantCardOrder->expects($this->any())
            ->method('getVersion')
            ->willReturn(1);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getDuplicatedOrder'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('getDuplicatedOrder')
            ->willReturn([1, 3]);
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger->expects($this->any())
            ->method('create')
            ->willReturn($logOperation);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($merchantCardOrder);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $parameter = [
            'domain' => 2,
            'merchant_cards' => [
                [
                    'merchant_card_id' => 1,
                    'order_id' => 1,
                    'version' => 1
                ]
            ]
        ];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setOrderAction($request);
    }

    /**
     * 測試設定商家金鑰找不到租卡商家
     */
    public function testSetMerchantCardKeyWithoutMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setKeyAction($request, 99);
    }

    /**
     * 測試移除租卡商家金鑰但金鑰不存在
     */
    public function testRemoveMerchantCardKeyWithMerchantCardKeyNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCardKey found',
            700023
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeKeyAction(1);
    }

    /**
     * 測試修改租卡商家密鑰但密鑰長度過長
     */
    public function testSetMerchantCardPrivateKeyButPrivateKeyIsTooLong()
    {
        $this->setExpectedException(
            'RangeException',
            'Private Key is too long',
            150700034
        );

        $query = [
            'private_key' => str_repeat('1', 1025),
        ];

        $request = new Request([], $query);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setPrivateKeyAction($request, 1);
    }

    /**
     * 測試修改租卡商家密鑰但租卡商家不存在
     */
    public function testSetMerchantCardPrivateKeyHasNoMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setPrivateKeyAction($request, 1);
    }

    /**
     * 測試取得租卡商家設定但商家不存在
     */
    public function testGetMerchantCardExtraButMerchantIsNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getExtraAction($request, 1);
    }

    /**
     * 測試取得租卡商家設定,但無此商家設定
     */
    public function testNoMerchantCardExtraFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCardExtra found',
            700027
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'clear'])
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $parameters = ['name' => 'test'];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getExtraAction($request, 1);
    }

    /**
     * 測試設定租卡商家其他設定傳入空的設定值
     */
    public function testSetMerchantCardExtraWithEmptyExtra()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No MerchantCardExtra specified',
            700028
        );

        $parameters = [
            'merchant_card_extra' => [
                [
                    'name' => '',
                    'value' => ''
                ]
            ]
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試設定不存在的租卡商家
     */
    public function testSetMerchantCardExtraWithMerchantNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $parameters = [
            'merchant_card_extra' => [
                [
                    'name' => 'over',
                    'value' => '111'
                ]
            ]
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試設定租卡商家其他設定來設定停用金額
     */
    public function testSetMerchantCardExtraWithBankLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot set bankLimit',
            700029
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);

        $parameters = [
            'merchant_card_extra' => [
                [
                    'name' => 'bankLimit',
                    'value' => '111'
                ],
                [
                    'name' => 'gohometime',
                    'value' => '222'
                ]
            ]
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試設定不存在的設定
     */
    public function testSetMerchantCardExtraWithInvalidName()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCardExtra found',
            700027
        );

        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'clear'])
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);


        $parameters = [
            'merchant_card_extra' => [
                [
                    'name' => 'over',
                    'value' => '111'
                ],
                [
                    'name' => 'gohometime',
                    'value' => '222'
                ]
            ]
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試設定租卡商家停用金額帶入金額不合法
     */
    public function testMerchantCardBankLimitInvalidValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantCardExtra value',
            700030
        );

        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $parameters = ['value' => '0.1'];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setBankLimitAction($request, 1);
    }

    /**
     * 測試設定租卡商家停用金額商家不存在
     */
    public function testMerchantCardBankLimitWithMerchantCardNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            700024
        );

        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $parameters = ['value' => '2'];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $opLogger);
        $controller->setContainer($container);

        $controller->setBankLimitAction($request, 1);
    }

    /**
     * 測試取得商號停用金額相關資訊帶入錯誤幣別
     */
    public function testMerchantCardBankLimitListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            700011
        );

        $parameter = ['currency' => 'AAAA'];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listBankLimitAction($request);
    }

    /**
     * 測試取得商號停用金額相關資訊帶入空幣別
     */
    public function testMerchantCardBankLimitListWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            700011
        );

        $parameter = ['currency' => ''];

        $request = new Request([], $parameter);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listBankLimitAction($request);
    }

    /**
     * 測試帶入不合法的開始筆數，來取得商號訊息
     */
    public function testGetMerchantCardRecordByInvalidFirstResult()
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
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getRecordAction($request, 1);
    }

    /**
     * 測試帶入不合法的顯示筆數，來取得商號訊息
     */
    public function testGetMerchantCardRecordByInvalidMaxResults()
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
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getRecordAction($request, 1);
    }

    /**
     * 測試取得商號訊息沒有帶入搜尋時間條件
     */
    public function testGetMerchantCardRecordWithTimeEmpty()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            700031
        );

        $request = new Request();
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getRecordAction($request, 1);
    }

    /**
     * 測試取得商號訊息搜尋時間條件帶入空白字元參數
     */
    public function testGetMerchantCardRecordWithSpace()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            700031
        );

        $parameters = ['start' => ''];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getRecordAction($request, 1);
    }

    /**
     * 測試取得商號訊息帶入不合法domain
     */
    public function testGetMerchantCardRecordWithInvalidDomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No domain found',
            700032
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $parameters = [
            'start' => '2015-09-22T00:00:00+0800',
            'end' => '2015-09-23T00:00:00+0800'
        ];

        $request = new Request([], $parameters);
        $controller = new MerchantCardController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getRecordAction($request, 99);
    }
}