<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\MerchantController;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\CashDepositEntry;
use Buzz\Exception\ClientException;
use Buzz\Message\Response;

class MerchantControllerTest extends ControllerTest
{
    /**
     * 測試新增商家的alias輸入帶入非UTF8
     */
    public function testNewMerchantAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $query = ['alias' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家沒帶入支付平台
     */
    public function testNewMerchantWithoutPaymentGatewayId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            500001
        );

        $query = ['payment_gateway_id' => ''];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家帶入非法的支付種類
     */
    public function testNewMerchantWithInvalidPayway()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid payway',
            500011
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家沒帶入別名
     */
    public function testNewMerchantWithoutAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid Merchant alias',
            500015
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家沒帶入商號
     */
    public function testNewMerchantWithoutNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid Merchant number',
            500016
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家沒帶入廳主
     */
    public function testNewMerchantWithoutDomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            500008
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家沒帶入幣別
     */
    public function testNewMerchantWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            500013
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家帶入錯誤的幣別
     */
    public function testNewMerchantWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            500013
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'RMB'
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家但密鑰長度過長
     */
    public function testNewMerchantButPrivateKeyIsTooLong()
    {
        $this->setExpectedException(
            'RangeException',
            'Private Key is too long',
            150500052
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => str_repeat('1', 1025),
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增商家帶入不存在的層級
     */
    public function testNewMerchantButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            500036
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'level_id' => [
                '1',
                '2'
            ]
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
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

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增商家帶入不存在的支付平台
     */
    public function testNewMerchantButPaymentGatewayNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            500001
        );

        $query = [
            'payment_gateway_id' => 999,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'level_id' => [1, 2]
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
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

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增商家帶入已刪除的支付平台
     */
    public function testNewMerchantWithRemovedPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway is removed',
            500022
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'level_id' => [1, 2]
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$level, $level]);

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增商家帶入不合法單筆最大支付金額
     */
    public function testNewMerchantWithInvalidAmountLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid amount limit',
            150500054
        );

        $query = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'level_id' => [1, 2],
            'amount_limit' => 'test',
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'updatePaymentGatewayVersion','findOneBy'])
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findBy')
            ->willReturn([$level, $level]);
        $entityRepo->expects($this->at(3))
            ->method('findBy')
            ->willReturn(true);
        $entityRepo->expects($this->at(1))
            ->method('findOneBy')
            ->willReturn([]);
        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試取得不存在的商家
     */
    public function testGetMerchantNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(1);
    }

    /**
     * 測試修改商家alias非UTF8
     */
    public function testEditMerchantAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $opLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn('something');

        $query = ['alias' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('durian.operation_logger', $opLogger);
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改商家帶入錯誤的別名
     */
    public function testEditMerchantWithInvalidAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid Merchant alias',
            500015
        );

        $query = ['alias' => ''];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改商家帶入錯誤的商號
     */
    public function testEditMerchantWithInvalidNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid Merchant number',
            500016
        );

        $query = [
            'alias' => 'TestPay',
            'number' => '',
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改商家帶入錯誤的廳主
     */
    public function testEditMerchantWithInvalidDomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            500008
        );

        $query = [
            'alias' => 'TestPay',
            'number' => '111111111',
            'domain' => ' '
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改不存在的商家
     */
    public function testEditMerchantNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $query = [
            'alias' => 'TestPay',
            'number' => '111111111',
            'domain' => 1
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改商家帶入不存在的支付平台
     */
    public function testEditMerchantPaymentGatewayNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            500001
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchant);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn(null);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'TestPay',
            'number' => '111111111',
            'domain' => 1
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改商家帶入已刪除的支付平台
     */
    public function testEditMerchantWithRemovedPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway is removed',
            500022
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->any())
            ->method('isRemoved')
            ->willReturn(true);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchant);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentGateway);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'TestPay',
            'number' => '111111111',
            'domain' => 1
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改商家帶入不合法的單筆最大支付金額
     */
    public function testEditMerchantWithInvalidAmountLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid amount limit',
            150500054
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->at(0))
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchant);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentGateway);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $query = [
            'payment_gateway_id' => 1,
            'amount_limit' => 'test',
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試刪除商家但商家不存在
     */
    public function testRemoveMerchantButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試刪除已啟用商家
     */
    public function testRemoveEnabledMerchant()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot delete when merchant enabled',
            500026
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->any())
            ->method('isEnabled')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試刪除已暫停商家
     */
    public function testRemoveSuspendedMerchant()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot delete when merchant suspended',
            500027
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->any())
            ->method('isEnabled')
            ->willReturn(0);
        $merchant->expects($this->any())
            ->method('isSuspended')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試取得商家列表帶入空幣別
     */
    public function testMerchantListWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            500023
        );

        $query = ['currency' => ''];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listAction($request, 1);
    }

    /**
     * 測試取得商家列表帶入錯誤幣別
     */
    public function testMerchantListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            500023
        );

        $query = ['currency' => 'AAAA'];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listAction($request, 1);
    }

    /**
     * 測試取得購物網商家列表, 未帶入webUrl
     */
    public function testGetMerchantListByWebUrlWithNoWebUrlSpecified()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No web_url specified',
            500037
        );

        $request = new Request([]);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listByWebUrlAction($request, 1);
    }

    /**
     * 測試取得購物網商家列表, 未帶入IP
     */
    public function testGetMerchantListByWebUrlWithNoIPSpecified()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            500005
        );

        $query = ['web_url' => 'http://ezshop.com'];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listByWebUrlAction($request, 1);
    }

    /**
     * 測試設定商家停用金額,金額不合法
     */
    public function testMerchantBankLimitInvalidValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantExtra value',
            500017
        );

        $query = ['value' => 0.1];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setMerchantBankLimitAction($request, 1);
    }

    /**
     * 測試停用商家時商家未核准
     */
    public function testMerchantDisableWhenNotApprove()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when merchant is not approved',
            500028
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->disableAction(1);
    }

    /**
     * 測試啟用商家時未核准
     */
    public function testMerchantEnableWhenNotApprove()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when merchant is not approved',
            500028
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試啟用商家時商家已刪除
     */
    public function testEnableMerchantWhenMerchantRemoved()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Merchant is removed',
            150500053
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchant->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試啟用商家時支付平台已刪除
     */
    public function testEnableMerchantWhenPaymentGatewayRemoved()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway is removed',
            500022
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchant->expects($this->once())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試暫停商家時未核准
     */
    public function testMerchantSuspendWhenNotApprove()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when merchant is not approved',
            500028
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->suspendAction(1);
    }

    /**
     * 測試暫停商家時商家停用
     */
    public function testMerchantSuspendWhenDisabled()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when merchant disabled',
            500033
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchant->expects($this->once())
            ->method('isEnabled')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->suspendAction(1);
    }

    /**
     * 測試恢復暫停商家時未核准
     */
    public function testMerchantResumeWhenNotApprove()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when merchant is not approved',
            500028
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->resumeAction(1);
    }

    /**
     * 測試恢復暫停商家時商家停用
     */
    public function testMerchantResumeWhenDisabled()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when merchant disabled',
            500033
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchant->expects($this->once())
            ->method('isEnabled')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->resumeAction(1);
    }

    /**
     * 測試取得商家設定,但無此商家設定
     */
    public function testGetMerchantExtraNoMerchantExtraFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantExtra found',
            500002
        );

        $repo = $this->getMockBuilder('BB\DurianBundle\Repository\MerchantExtra')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock();
        $repo->expects($this->any())
            ->method('findBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(true);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $query = ['name' => 'ilii1!l1i'];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getMerchantExtraAction($request, 1);
    }

    /**
     * 測試設定不存在的商家
     */
    public function testSetMerchantExtraWithMerchantNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $extras = [];
        $extras[] = [
            'name' => 'over',
            'value' => '111'
        ];
        $extras[] = [
            'name' => 'gohometime',
            'value' => '222'
        ];

        $query = ['merchant_extra' => $extras];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setMerchantExtraAction($request, 1);
    }

    /**
     * 測試設定商家其他設定傳入空的設定值
     */
    public function testSetMerchantExtraWithEmptyExtra()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No Merchant Extra specified',
            500007
        );

        $extras[] = [
            'name' => '',
            'value' => ''
        ];

        $query = ['merchant_extra' => $extras];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setMerchantExtraAction($request, 1);
    }

    /**
     * 測試設定商家其他設定來設定停用金額
     */
    public function testSetMerchantExtraWithBankLimit()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantExtra found',
            500002
        );

        $extras[] = [
            'name' => 'bankLimit',
            'value' => '111'
        ];

        $query = ['merchant_extra' => $extras];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setMerchantExtraAction($request, 1);
    }

    /**
     * 測試取得商號停用金額相關資訊帶入錯誤幣別
     */
    public function testMerchantBankLimitListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            500023
        );

        $query = ['currency' => 'AAAA'];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getMerchantBankLimitListAction($request);
    }

    /**
     * 測試取得商號停用金額相關資訊帶入空幣別
     */
    public function testMerchantBankLimitListWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            500023
        );

        $query = ['currency' => ''];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getMerchantBankLimitListAction($request);
    }

    /**
     * 測試新增商號ip限制帶入空country_id
     */
    public function testAddMerchantIpStrategyWithEmptyCountryId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No country id given',
            500009
        );

        $query = ['country_id' => ''];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試新增商號ip限制時帶入不存在國家
     */
    public function testAddMerchantIpStrategyCountryNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot find specified country',
            500029
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchant);

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $query = ['country_id' => 999];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試新增商號ip限制時帶入不存在區域
     */
    public function testAddMerchantIpStrategyRegionNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot find specified region',
            500030
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $country = $this->getMockBuilder('BB\DurianBundle\Entity\GeoipCountry')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchant);

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $emShare->expects($this->at(0))
            ->method('find')
            ->willReturn($country);

        $query = [
            'country_id' => 1,
            'region_id' => 999
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試新增商號ip限制時帶入不存在城市
     */
    public function testAddMerchantIpStrategyCityNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot find specified city',
            500031
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $country = $this->getMockBuilder('BB\DurianBundle\Entity\GeoipCountry')
            ->disableOriginalConstructor()
            ->getMock();

        $region = $this->getMockBuilder('BB\DurianBundle\Entity\GeoipRegion')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchant);

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $emShare->expects($this->at(0))
            ->method('find')
            ->willReturn($country);

        $emShare->expects($this->at(1))
            ->method('find')
            ->willReturn($region);

        $query = [
            'country_id' => 1,
            'region_id' => 1,
            'city_id' => 999
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試新增商號ip限制新增重複設定
     */
    public function testAddMerchantIpStrategyWithDuplicateStrategy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate MerchantIpStrategy',
            500020
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $country = $this->getMockBuilder('BB\DurianBundle\Entity\GeoipCountry')
            ->disableOriginalConstructor()
            ->getMock();

        $region = $this->getMockBuilder('BB\DurianBundle\Entity\GeoipRegion')
            ->disableOriginalConstructor()
            ->getMock();

        $city = $this->getMockBuilder('BB\DurianBundle\Entity\GeoipCity')
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->getMockBuilder('BB\DurianBundle\Repository\MerchantIpStrategy')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $repo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(true);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchant);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $emShare->expects($this->at(0))
            ->method('find')
            ->willReturn($country);

        $emShare->expects($this->at(1))
            ->method('find')
            ->willReturn($region);

        $emShare->expects($this->at(2))
            ->method('find')
            ->willReturn($city);

        $query = [
            'country_id' => 1,
            'region_id' => 1,
            'city_id' => 1
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試移除商號ip限制不存在ip限制
     */
    public function testRemoveIpStrategyIpStrategyNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No IpStrategy found',
            500003
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeIpStrategyAction(1);
    }

    /**
     * 測試取得商號訊息帶入不合法的開始筆數
     */
    public function testGetMerchantRecordWithInvalidFirstResult()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid first_result',
            150610004
        );

        $params = [
            'first_result' => -5,
            'max_results' => 1
        ];

        $request = new Request([], $params);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getMerchantRecordAction($request, 2);
    }

    /**
     * 測試取得商號訊息帶入不合法的顯示筆數
     */
    public function testGetMerchantRecordWithInvalidMaxResults()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid max_results',
            150610005
        );

        $params = [
            'first_result' => 0,
            'max_results' => -1
        ];

        $request = new Request([], $params);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getMerchantRecordAction($request, 2);
    }

    /**
     * 測試取得商號訊息帶入不合法domain
     */
    public function testGetMerchantRecordWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            500010
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getMerchantRecordAction($request, 40);
    }

    /**
     * 測試檢查IP限制但是沒帶入IP
     */
    public function testCheckMerchantIpLimitWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            500005
        );

        $query = ['ip' => ''];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->checkMerchantIpLimitAction($request, 1);
    }

    /**
     * 測試檢查IP限制但是商家不存在
     */
    public function testCheckMerchantIpLimitWithMerchantNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $query = ['ip' => '42.4.0.0'];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->checkMerchantIpLimitAction($request, 99);
    }

    /**
     * 測試核准商家但商家不存在
     */
    public function testApproveMerchantButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->approveAction(555);
    }

    /**
     * 測試設定商家金鑰找不到商家
     */
    public function testSetMerchantKeyWithoutMerchant()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setKeyAction($request, 99);
    }

    /**
     * 測試移除商家金鑰但金鑰不存在
     */
    public function testRemoveMerchantKeyWithMerchantKeyNotExsit()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantKey found',
            500004
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeMerchantKeyAction(1);
    }

    /**
     * 測試修改商家密鑰但密鑰長度過長
     */
    public function testSetMerchantPrivateKeyButPrivateKeyIsTooLong()
    {
        $this->setExpectedException(
            'RangeException',
            'Private Key is too long',
            150500052
        );

        $query = [
            'private_key' => str_repeat('1', 1025),
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setPrivateKeyAction($request, 1);
    }

    /**
     * 測試修改商家私鑰但商家不存在
     */
    public function testSetMerchantPrivateKeyHasNoMerchant()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setPrivateKeyAction($request, 99);
    }

    /**
     * 測試檢查shopUrl連線是否正常商家不存在
     */
    public function testShopUrlCheckConnectionWithNoMerchantfound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            500034
        );

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->shopUrlCheckConnectionAction(999);
    }

    /**
     * 測試檢查shopUrl連線是否正常購物網網址不合法
     */
    public function testShopUrlCheckConnectionWithInvalidShopUrl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid shopUrl',
            150500040
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->any())
            ->method('getShopUrl')
            ->willReturn('test');
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->shopUrlCheckConnectionAction(1);
    }

    /**
     * 測試檢查shopUrl連線是否正常購物網解析錯誤
     */
    public function testShopUrlCheckConnectionShopUrlResolveError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'ShopUrl resolve error',
            150500043
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->any())
            ->method('getShopUrl')
            ->willReturn('http://test/pay/');
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $controller = $this->getMockBuilder('BB\DurianBundle\Controller\MerchantController')
            ->disableOriginalConstructor()
            ->setMethods(['getPayIp', 'getHostIp'])
            ->getMock();
        $controller->expects($this->any())
            ->method('getPayIp')
            ->willReturn(['127.0.0.1']);
        $controller->expects($this->any())
            ->method('getHostIp')
            ->willReturn('1.1.1.1');

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->shopUrlCheckConnectionAction(1);
    }

    /**
     * 測試檢查shopUrl連線是否正常
     */
    public function testShopUrlCheckConnection()
    {
        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->any())
            ->method('getShopUrl')
            ->willReturn('http://test/pay/');
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $controller = $this->getMockBuilder('BB\DurianBundle\Controller\MerchantController')
            ->disableOriginalConstructor()
            ->setMethods(['getPayIp', 'getHostIp'])
            ->getMock();
        $controller->expects($this->any())
            ->method('getPayIp')
            ->willReturn(['127.0.0.1']);
        $controller->expects($this->any())
            ->method('getHostIp')
            ->willReturn('127.0.0.1');

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $json = $controller->shopUrlCheckConnectionAction(1);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 檢查shopUrl的ip解析未帶入廳
     */
    public function testShopUrlCheckIpWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150500044
        );

        $request = new Request([]);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 檢查shopUrl的ip解析未帶入購物網
     */
    public function testShopUrlCheckIpWithoutShopUrl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No shop_url specified',
            150500045
        );

        $query = ['domain' => '6'];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 檢查shopUrl的ip解析帶入不合法的購物網網址
     */
    public function testShopUrlCheckIpWithInvalidShopUrl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid shopUrl',
            150500040
        );

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $query = [
            'domain' => '6',
            'shop_url' => 'test123'
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 檢查shopUrl的ip解析廳主不存在
     */
    public function testShopUrlCheckIpWithNotADomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            500008
        );

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $query = [
            'domain' => '6',
            'shop_url' => 'http://test/'
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 測試檢查shopUrl的ip解析連線逾時
     */
    public function testShopUrlCheckIpConnectionTimeout()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Curl getPayIp api failed',
            150500046
        );

        $domain = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $domain->expects($this->any())
            ->method('getParent')
            ->willReturn(null);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($domain);

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->disableOriginalConstructor()
            ->setMethods(['send'])
            ->getMock();

        $timeoutException = new ClientException(
            'Operation timed out after 30002 milliseconds with 0 out of -1 bytes received',
            28
        );
        $mockClient->expects($this->any())
            ->method('send')
            ->will($this->throwException($timeoutException));

        $response = new Response();
        $response->setContent('');
        $response->addHeader('HTTP/1.1 200 OK');

        $query = [
            'domain' => '6',
            'shop_url' => 'http://test/',
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 測試檢查shopUrl的ip解析連線失敗
     */
    public function testShopUrlCheckIpConnectionFailure()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Curl getPayIp api failed',
            150500046
        );

        $domain = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $domain->expects($this->any())
            ->method('getParent')
            ->willReturn(null);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($domain);

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $query = [
            'domain' => '6',
            'shop_url' => 'http://test/'
        ];

        $request = new Request([], $query);
        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 檢查shopUrl的ip解析取得域名IP連線逾時
     */
    public function testShopUrlCheckIpGetHostIpConnectionTimeout()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Get host ip connection failure',
            150500050
        );

        $domain = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $domain->expects($this->any())
            ->method('getParent')
            ->willReturn(null);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($domain);

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->disableOriginalConstructor()
            ->setMethods(['send'])
            ->getMock();

        $controller = $this->getMockBuilder('BB\DurianBundle\Controller\MerchantController')
            ->disableOriginalConstructor()
            ->setMethods(['getPayIp'])
            ->getMock();

        $controller->expects($this->any())
            ->method('getPayIp')
            ->willReturn(['127.0.0.1']);

        $timeoutException = new ClientException(
            'Operation timed out after 30002 milliseconds with 0 out of -1 bytes received',
            28
        );
        $mockClient->expects($this->any())
            ->method('send')
            ->will($this->throwException($timeoutException));

        $response = new Response();
        $response->setContent('');
        $response->addHeader('HTTP/1.1 200 OK');

        $query = [
            'domain' => '6',
            'shop_url' => 'http://test/',
        ];

        $request = new Request([], $query);
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 檢查shopUrl的ip解析取得域名IP連線失敗
     */
    public function testShopUrlCheckIpGetHostIpConnectionFailure()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Get host ip connection failure',
            150500050
        );

        $domain = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $domain->expects($this->any())
            ->method('getParent')
            ->willReturn(null);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($domain);

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $controller = $this->getMockBuilder('BB\DurianBundle\Controller\MerchantController')
            ->disableOriginalConstructor()
            ->setMethods(['getPayIp'])
            ->getMock();

        $controller->expects($this->any())
            ->method('getPayIp')
            ->willReturn(['127.0.0.1']);

        $response = new Response();
        $response->setContent('');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $query = [
            'domain' => '6',
            'shop_url' => 'http://test/'
        ];

        $request = new Request([], $query);
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 檢查shopUrl的ip解析錯誤
     */
    public function testShopUrlCheckIpInvalidResolveError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'ShopUrl resolve error',
            150500043
        );

        $domain = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $domain->expects($this->any())
            ->method('getParent')
            ->willReturn(null);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($domain);

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $controller = $this->getMockBuilder('BB\DurianBundle\Controller\MerchantController')
            ->disableOriginalConstructor()
            ->setMethods(['getPayIp'])
            ->getMock();

        $controller->expects($this->any())
            ->method('getPayIp')
            ->willReturn(['127.0.0.1']);

        $response = new Response();
        $response->setContent('1.1.1.1');
        $response->addHeader('HTTP/1.1 200 OK');

        $query = [
            'domain' => '6',
            'shop_url' => 'http://test/'
        ];

        $request = new Request([], $query);
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $controller->shopUrlCheckIpAction($request);
    }

    /**
     * 檢查shopUrl的ip解析成功
     */
    public function testShopUrlCheckIp()
    {
        $domain = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $domain->expects($this->any())
            ->method('getParent')
            ->willReturn(null);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($domain);

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('127.0.0.1');
        $response->addHeader('HTTP/1.1 200 OK');

        $controller = $this->getMockBuilder('BB\DurianBundle\Controller\MerchantController')
            ->disableOriginalConstructor()
            ->setMethods(['getPayIp'])
            ->getMock();

        $controller->expects($this->any())
            ->method('getPayIp')
            ->willReturn(['127.0.0.1']);

        $query = [
            'domain' => '6',
            'shop_url' => 'http://127.0.0.1/'
        ];

        $request = new Request([], $query);
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $json = $controller->shopUrlCheckIpAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試通知重設商家白名單連線失敗
     */
    public function testWhitelistResetConnectionFailure()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Merchant whitelist reset connection failure',
            150500048
        );

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $controller->whitelistResetAction();
    }

    /**
     * 測試通知重設商家白名單失敗
     */
    public function testWhitelistResetFailed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Merchant whitelist reset failed',
            150500049
        );

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('ERROR');
        $response->addHeader('HTTP/1.1 200 OK');

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $controller->whitelistResetAction();
    }

    /**
     * 測試通知重設商家白名單
     */
    public function testWhitelistReset()
    {
        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('YES');
        $response->addHeader('HTTP/1.1 200 OK');

        $controller = new MerchantController();
        $container = static::$kernel->getContainer();
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->setContainer($container);

        $json = $controller->whitelistResetAction();
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
    }
}
