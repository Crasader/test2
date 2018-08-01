<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\DepositController;
use BB\DurianBundle\Entity\CashDepositEntry;
use Symfony\Component\HttpFoundation\Request;

class DepositControllerTest extends ControllerTest
{
    /**
     * 測試入款帶入不支援的幣別
     */
    public function testPaymentDepositIllegalCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['currency' => 'ABC'];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款帶入不支援的付款種類
     */
    public function testPaymentDepositIllegalPayway()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal payway specified',
            370023
        );

        $query = [
            'currency' => 'CNY',
            'payway' => '999'
        ];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款沒帶入金額
     */
    public function testPaymentDepositWithoutAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            370011
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH
        ];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款沒帶入IP
     */
    public function testPaymentDepositWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            370026
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'amount' => 1
        ];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試現金入款但會員層級不存在
     */
    public function testPaymentCashDepositButUserLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            370056
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款金額帶入超過四位小數點
     */
    public function testPaymentDepositWithInvalidAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1.23456789
        ];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->paymentDepositAction($request, 5);
    }

    /**
     * 測試入款帶入金額不能為零或是負數
     */
    public function testPaymentDepositWithAmountCanNotBeZeroOrNegative()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount can not be zero or negative',
            150370058
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 0.0000
        ];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->paymentDepositAction($request, 5);
    }

    /**
     * 測試取得入款加密參數沒帶入支付通知網址
     */
    public function testGetDepositParamsWithoutNotifyUrl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No notify_url specified',
            370007
        );

        $request = new Request();
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositParamsAction($request, 201304280000000001);
    }

    /**
     * 測試取得入款加密參數找不到入款明細
     */
    public function testGetDepositParamsCannotFindDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );
        $params = ['notify_url' => 'http://localhost/'];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($params);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDepositParamsAction($request, 201304280000000001);
    }

    /**
     * 測試入款時找不到使用者
     */
    public function testPaymentDepositCanNotFindUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            370013
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1.234
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 9999);
    }

    /**
     * 測試入款時找不到付款廠商
     */
    public function testPaymentDepositNoPaymentVendorFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentVendor found',
            370032
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1.234
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
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
            ->willReturn($userLevel);
        $operation = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $operation->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.deposit_operator', $operation);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款時找不到付款方式資料
     */
    public function testPaymentDepositCanNotFindPaywayEntity()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot find specified payway',
            370035
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1.234
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($userLevel);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentVendor);
        $operation = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $operation->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.deposit_operator', $operation);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款時找不到幣別轉換匯率
     */
    public function testPaymentDepositWithPaywayCurrencyInvalidExchange()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No such exchange',
            370033
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1.234
        ];

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($cash);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($userLevel);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentVendor);
        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepoShare = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findByCurrencyAt'])
            ->getMock();
        $emShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepoShare);
        $entityRepoShare->expects($this->at(0))
            ->method('findByCurrencyAt')
            ->willReturn(null);
        $operation = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $operation->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $container->set('durian.deposit_operator', $operation);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款時找不到入款幣別轉換匯率
     */
    public function testPaymentDepositWithCurrencyInvalidExchange()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No such exchange',
            370033
        );

        $query = [
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1.234
        ];

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $exchange = $this->getMockBuilder('BB\DurianBundle\Entity\Exchange')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($cash);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($userLevel);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentVendor);
        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepoShare = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findByCurrencyAt'])
            ->getMock();
        $entityRepoShare->expects($this->at(0))
            ->method('findByCurrencyAt')
            ->willReturn($exchange);
        $emShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepoShare);
        $operation = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $operation->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $container->set('durian.deposit_operator', $operation);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款時帶入商家Id找不到商號
     */
    public function testPaymentDepositWithMerchantIdButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            370031
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1,
            'merchant_id' => '999'
        ];

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);
        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($cash);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($userLevel);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentVendor);
        $operation = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $operation->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.deposit_operator', $operation);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款時帶入商家Id，支付金額大於商家設定最大支付金額
     */
    public function testPaymentDepositWithMerchantIdButAmountExceedTheMaxValue()
    {
        $this->setExpectedException(
            'RangeException',
            'Amount exceed the amount limit',
            150370067
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 50,
            'merchant_id' => '999'
        ];

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);
        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->any())
            ->method('getAmountLimit')
            ->willReturn(20);
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($cash);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(4))
            ->method('find')
            ->will($this->onConsecutiveCalls($user, $userLevel, $paymentVendor, $merchant));
        $operation = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $operation->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.deposit_operator', $operation);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試入款時找不到商號
     */
    public function testPaymentDepositNotFindMerchant()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            370031
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1
        ];

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);
        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'getMerchantsBy'])
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($cash);
        $entityRepo->expects($this->at(1))
            ->method('getMerchantsBy')
            ->willReturn([]);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($userLevel);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentVendor);
        $operation = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $operation->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.deposit_operator', $operation);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

     /**
     * 測試入款時根據層級的排序設定取得商家找不到商號
     */
    public function testPaymentDepositGetMerchantByOrderStrategyNoMerchantFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            180006
        );

        $query = [
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);
        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'getMerchantsBy', 'getMerchantCountByIds', 'getMinOrderMerchant'])
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($paymentVendor);
        $entityRepo->expects($this->at(1))
            ->method('getMerchantsBy')
            ->willReturn($merchant);
        $entityRepo->expects($this->any())
            ->method('getMerchantsBy')
            ->willReturn($merchant);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($userLevel);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentVendor);
        $em->expects($this->any())
            ->method('find')
            ->willReturn($level);
        $depositOp = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $depositOp->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.deposit_operator', $depositOp);
        $controller->setContainer($container);

        $controller->paymentDepositAction($request, 8);
    }

    /**
     * 測試取得入款明細資料帶入錯誤入款幣別
     */
    public function testGetDepositEntriesListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['currency' => 'AAAA'];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositEntryListAction($request);
    }

    /**
     * 測試取得入款明細資料帶入空的入款幣別
     */
    public function testGetDepositEntriesListWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['currency' => ''];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositEntryListAction($request);
    }

    /**
     * 測試取得入款明細資料總計未帶參數時的例外
     */
    public function testGetDepositWithoutParameter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parameter specified',
            370003
        );

        $request = new Request();
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositEntryListAction($request);
    }

    /**
     * 測試取得入款明細資料帶入錯誤付款種類的幣別
     */
    public function testGetDepositEntriesListWithInvalidPaywayCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['payway_currency' => 'AAAA'];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositEntryListAction($request);
    }

    /**
     * 測試取得入款明細資料帶入空的付款種類的幣別
     */
    public function testGetDepositEntriesListWithEmptyPaywayCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['payway_currency' => ''];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositEntryListAction($request);
    }

    /**
     * 測試取得入款明細資料總計時帶入錯誤入款幣別
     */
    public function testGetDepositTotalAmountWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['currency' => 'AAAA'];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositTotalAmountAction($request);
    }

    /**
     * 測試取得入款明細資料總計時帶入空的入款幣別
     */
    public function testGetDepositTotalAmountWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['currency' => ''];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositTotalAmountAction($request);
    }

    /**
     * 測試取得入款明細資料總計時帶入錯誤付款種類的幣別
     */
    public function testGetDepositTotalAmountWithInvalidPaywayCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['payway_currency' => 'AAAA'];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositTotalAmountAction($request);
    }

    /**
     * 測試取得入款明細資料總計時帶入空的付款種類的幣別
     */
    public function testGetDepositTotalAmountWithEmptyPaywayCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            370034
        );

        $query = ['payway_currency' => ''];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositTotalAmountAction($request);
    }

    /**
     * 測試修改入款明細時未帶入備註
     */
    public function testSetCashDepositEntryMemoButNoMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No memo specified',
            370027
        );

        $request = new Request();
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setDepositEntryAction($request, 201304280000000001);
    }

    /**
     * 測試修改入款明細時的例外
     */
    public function testSetCashDepositEntryMemoButExceptionOccur()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );

        $query = ['memo' => 'hrhrhr'];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'clear'])
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setDepositEntryAction($request, 999);
    }

    /**
     * 測試修改入款明細時輸入非UTF8
     */
    public function testSetCashDepositEntryMemoNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $query = ['memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setDepositEntryAction($request, 1);
    }

    /**
     * 測試入款人工確認時備註輸入非UTF8
     */
    public function testManualConfirmMemoNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $entry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $entry->expects($this->once())
            ->method('isConfirm')
            ->willReturn(false);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'clear'])
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($entry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $query = ['memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualConfirmDepositAction($request, 201304280000000001);
    }

    /**
     * 測試已確認的入款無法再改變
     */
    public function testManualConfirmConfirmedDepositEntry()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Deposit entry has been confirmed',
            370002
        );

        $entry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $entry->expects($this->once())
            ->method('isConfirm')
            ->willReturn(true);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'clear'])
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($entry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualConfirmDepositAction($request, 201304280000000001);
    }

    /**
     * 測試改變不存在的入款明細狀態
     */
    public function testManualConfirmNoneExistDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'clear'])
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualConfirmDepositAction($request, 932);
    }

    /**
     * 測試入款解密驗證時找不到入款明細
     */
    public function testDepositVerifyDecodeCanNotFindCashDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'clear'])
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->cashDepositVerifyDecode($request, 9999);
    }

    /**
     * 測試入款解密驗證時找不到商家
     */
    public function testDepositVerifyDecodeCanNotFindMerchant()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            370031
        );

        $entry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'clear'])
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($entry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->cashDepositVerifyDecode($request, 201401280000000001);
    }

    /**
     * 測試取單筆入款明細時，此筆明細不存在的情況
     */
    public function testGetCashDepositEntryNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'clear'])
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDepositEntryAction($request, 999);
    }

    /**
     * 測試新增人工入款最大金額未帶入金額
     */
    public function testCreateDepositConfirmQuotaWithoutAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            370011
        );

        $request = new Request();
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createDepositConfirmQuotaAction($request, 9);
    }

    /**
     * 測試新增人工入款最大金帶入額無效的金額
     */
    public function testCreateDepositConfirmQuotaWithInvalidAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount must be an integer',
            370012
        );

        $query = ['amount' => 'invalid'];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createDepositConfirmQuotaAction($request, 9);
    }

    /**
     * 測試新增人工入款最大金額帶入不存在的使用者
     */
    public function testCreateDepositConfirmQuotaWithUserNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            370013
        );

        $query = ['amount' => 1000];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createDepositConfirmQuotaAction($request, 1);
    }

    /**
     * 測試新增人工入款最大金額資料已存在
     */
    public function testCreateDepositConfirmQuotaDataExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Deposit confirm quota already exists',
            370009
        );

        $query = ['amount' => 100];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $confirmQuota = $this->getMockBuilder('BB\DurianBundle\Entity\DepositConfirmQuota')
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
            ->willReturn($confirmQuota);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createDepositConfirmQuotaAction($request, 8);
    }

    /**
     * 測試設定人工入款最大金額未帶入金額
     */
    public function testSetDepositConfirmQuotaWithoutAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            370011
        );

        $request = new Request();
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setDepositConfirmQuotaAction($request, 8);
    }

    /**
     * 測試設定人工入款最大金額帶入無效金額
     */
    public function testSetDepositConfirmQuotaWithInvalidAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount must be an integer',
            370012
        );

        $query = ['amount' => -1];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setDepositConfirmQuotaAction($request, 9);
    }

    /**
     * 測試設定人工入款最大金額帶入不存在的使用者
     */
    public function testSetDepositConfirmQuotaWithUserNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            370013
        );

        $query = ['amount' => 1000];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setDepositConfirmQuotaAction($request, 1);
    }

    /**
     * 測試設定人工入款最大金額資料為不存在的
     */
    public function testSetDepositConfirmQuotaDataNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No deposit confirm quota found',
            370010
        );

        $query = ['amount' => 99];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
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
            ->willReturn(null);

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setDepositConfirmQuotaAction($request, 9);
    }

    /**
     * 測試回傳人工入款最大金額帶入不存在的使用者
     */
    public function testGetDepositConfirmQuotaWithUserNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            370013
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDepositConfirmQuotaAction($request, 1);
    }

    /*
     * 測試確認入款時找不到入款明細
     */
    public function testConfirmCanNotFindCashDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller = new DepositController();
        $controller->setContainer($container);

        $controller->confirmAction($request, 9999);
    }

    /**
     * 測試人工確認入款, 未代入操作者Id及操作者名稱
     */
    public function testManualConfirmWithoutOperatorIdAndOperatorName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Operator can not be null',
            370037
        );

        $query = ['manual' => 1];

        $request = new Request([], $query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->confirmAction($request, 201304280000000001);
    }

    /**
     * 測試人工確認入款, 代入不存在的操作者Id及合法操作者名稱
     */
    public function testManualConfirmWithInvalidOperatorIdAndValidOperatorName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid operator specified',
            370022
        );

        $query = [
            'manual' => 1,
            'operator_id' => 999,
            'operator_name' => 'operator_name'
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201304280000000001);
    }

    /**
     * 測試取得入款查詢結果時找不到入款明細
     */
    public function testDepositTrackingCanNotFindDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDepositTrackingAction($request, 9999);
    }

    /**
     * 測試取得使用者入款優惠參數找不到使用者
     */
    public function testGetUserDepositOfferParamsNotFindUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            370013
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getUserDepositOfferParamsAction(999);
    }

    /**
     * 測試取得使用者入款優惠參數但會員層級不存在
     */
    public function testGetUserDepositOfferParamsButUserLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            370056
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

        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getUserDepositOfferParamsAction(1);
    }

    /**
     * 測試新增異常入款提醒email, 但信箱無效
     */
    public function testCreateAbnormalDepositNotifyEmailButInvalid()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid email given',
            150370061
        );

        $request = new Request([]);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->createAbnormalDepositNotifyEmailAction($request);
    }

    /**
     * 測試新增異常入款提醒email, 但email重複
     */
    public function testCreateAbnormalDepositNotifyEmailButDuplicate()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate AbnormalDepositNotifyEmail',
            150370062
        );

        $notifyEmail = $this->getMockBuilder('BB\DurianBundle\Entity\AbnormalDepositNotifyEmail')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($notifyEmail);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($entityRepo);

        $query = ['email' => 'abc@gmail.com'];

        $request = new Request([], $query);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAbnormalDepositNotifyEmailAction($request);
    }

    /**
     * 測試移除異常入款提醒email, 但email不存在
     */
    public function testRemoveAbnormalDepositNotifyEmailWithNoFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AbnormalDepositNotifyEmail found',
            150370063
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAbnormalDepositNotifyEmailAction(1);
    }

    /**
     * 測試取得實名認證所需參數但找不到入款明細
     */
    public function testGetRealNameAuthParamsButCannotFindDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getRealNameAuthParamsAction($request, 201304280000000001);
    }

    /**
     * 測試取得實名認證所需參數但找不到商家
     */
    public function testGetRealNameAuthParamsButCannotFindMerchant()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            370031
        );

        $entry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($entry);

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

        $request = new Request(['master_db' => 1]);
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getRealNameAuthParamsAction($request, 201304280000000001);
    }

    /**
     * 測試取得實名認證結果但找不到入款明細
     */
    public function testGetRealNameAuthButCannotFindDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash deposit entry found',
            370001
        );

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new DepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getRealNameAuthAction($request, 201304280000000001);
    }

    /**
     * 測試修改異常確認入款明細執行狀態操作者為空
     */
    public function testDepositPayStatusErrorCheckedOperatorIsNull()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Operator can not be null',
            370037
        );

        $request = new Request([]);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->depositPayStatusErrorCheckedAction($request);
    }

    /**
     * 測試取得異常確認入款明細列表帶入不合法的開始筆數
     */
    public function testGetDepositPayStatusErrorListWithInvalidFirstResult()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid first_result',
            150610004
        );

        $query = [
            'first_result' => -5,
            'max_results' => 1,
        ];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositPayStatusErrorListAction($request);
    }

    /**
     * 測試取得異常確認入款明細列表帶入不合法的顯示筆數
     */
    public function testGetDepositPayStatusErrorListWithInvalidMaxResults()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid max_results',
            150610005
        );

        $query = [
            'first_result' => 0,
            'max_results' => -1,
        ];

        $request = new Request($query);
        $controller = new DepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDepositPayStatusErrorListAction($request);
    }
}
