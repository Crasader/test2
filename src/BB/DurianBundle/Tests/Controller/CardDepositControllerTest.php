<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\CardDepositController;
use Symfony\Component\HttpFoundation\Request;

class CardDepositControllerTest extends ControllerTest
{
    /**
     * 測試取得租卡可用入款商家時付款廠商不存在
     */
    public function testGetDepositMerchantCardWithoutPaymentVendor()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentVendor found',
            150720001
        );

        $parameter = ['payment_vendor_id' => '99999'];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDepositMerchantCardAction($request, 2);
    }

    /**
     * 測試取得租卡可用入款商家時租卡排序設定不存在
     */
    public function testGetDepositMerchantCardWithoutOrderStrategy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardCharge found',
            150720003
        );

        $parameter = ['payment_vendor_id' => '1'];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getDomain')
            ->willReturn(2);
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getMerchantCardIdByVendor', 'find', 'findOneBy'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('getMerchantCardIdByVendor')
            ->willReturn([1, 2, 3]);
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($paymentVendor);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDepositMerchantCardAction($request, 2);
    }

    /**
     * 租卡入款未傳入金額
     */
    public function testCardDepositWithoutAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150720004
        );

        $request = new Request();
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 2);
    }

    /**
     * 租卡入款指定幣別不支援
     */
    public function testCardDepositWithUnsupportCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150720005
        );

        $parameter = [
            'amount' => '1',
            'currency' => 'ABC'
        ];

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 2);
    }

    /**
     * 租卡付款指定幣別不支援
     */
    public function testCardDepositWithUnsupportPaywayCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Payway currency not support',
            150720006
        );

        $parameter = [
            'amount' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'ABC'
        ];

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 2);
    }

    /**
     * 租卡入款找不到使用者
     */
    public function testCardDepositWithoutUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150720002
        );

        $parameter = [
            'amount' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY'
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 999);
    }

    /**
     * 租卡入款租卡不存在
     */
    public function testCardDepositWithNoCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Card found',
            150720007
        );

        $parameter = [
            'amount' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY'
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 2);
    }

    /**
     * 租卡入款找不到付款廠商
     */
    public function testCardDepositWithoutPaymentVendor()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentVendor found',
            150720001
        );

        $parameter = [
            'amount' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'payment_vendor_id' => '9999'
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $card = $this->getMockBuilder('BB\DurianBundle\Entity\Card')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getCard')
            ->willReturn($card);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($user);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 2);
    }

    /**
     * 租卡入款指定的幣別匯率不存在
     */
    public function testCardDepositWithNoExchange()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such exchange',
            150720009
        );

        $parameter = [
            'amount' => '1',
            'payment_vendor_id' => '1',
            'currency' => 'USD',
            'payway_currency' => 'CNY'
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $card = $this->getMockBuilder('BB\DurianBundle\Entity\Card')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getCard')
            ->willReturn($card);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($paymentVendor);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $exRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findByCurrencyAt'])
            ->getMock();
        $exRepo->expects($this->once())
            ->method('findByCurrencyAt')
            ->willReturn(null);
        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $emShare->expects($this->once())
            ->method('getRepository')
            ->willReturn($exRepo);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 2);
    }

    /**
     * 租卡入款時租卡排序設定不存在
     */
    public function testCardDepositWithoutOrderStrategy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardCharge found',
            150720003
        );

        $parameter = [
            'amount' => '1',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY'
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $card = $this->getMockBuilder('BB\DurianBundle\Entity\Card')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getCard')
            ->willReturn($card);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'getMerchantCardIdByVendor'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('getMerchantCardIdByVendor')
            ->willReturn([1, 2, 3]);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($paymentVendor);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 2);
    }

    /**
     * 租卡入款時找不到商家
     */
    public function testCardDepositWithoutMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            150720018
        );

        $parameter = [
            'amount' => '1',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY'
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $card = $this->getMockBuilder('BB\DurianBundle\Entity\Card')
            ->disableOriginalConstructor()
            ->getMock();
        $cardCharge = $this->getMockBuilder('BB\DurianBundle\Entity\CardCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getCard')
            ->willReturn($card);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'getMerchantCardIdByVendor', 'getMinOrderMerchantCard'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('getMerchantCardIdByVendor')
            ->willReturn([1, 2, 3]);
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardCharge);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($paymentVendor);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->cardDepositAction($request, 2);
    }

    /**
     * 測試取得租卡入款加密參數沒帶入返回通知網址
     */
    public function testCardDepositParamsButNotifyUrlNotExist()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No notify_url specified',
            150720012
        );

        $request = new Request();
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getParamsAction($request, 201501080000000001);
    }

    /**
     * 測試取得入款加密參數找不到入款資料
     */
    public function testCardDepositParamsButEntryNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardDepositEntry found',
            150720019
        );

        $parameters = ['notify_url' => 'http://localhost/'];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($parameters);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getParamsAction($request, 9999);
    }

    /**
     * 測試取得入款加密參數找不到使用者id
     */
    public function testCardDepositParamsNoUserFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150720002
        );

        $parameters = ['notify_url' => 'http://localhost/'];

        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($parameters);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getParamsAction($request, 201501080000000001);
    }

    /**
     * 測試取得入款加密參數找不到商家id
     */
    public function testCardDepositParamsNoMercahntFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            150720018
        );

        $parameters = ['notify_url' => 'http://localhost/'];

        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($user);

        $request = new Request($parameters);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getParamsAction($request, 201501080000000001);
    }

    /**
     * 測試取單筆入款明細不存在的情況
     */
    public function testGetCardDepositEntryNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardDepositEntry found',
            150720019
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

        $request = new Request(['master_db' => 1]);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getEntryAction($request, 9999);
    }

    /**
     * 測試修改租卡入款明細時未帶入要修改的備註
     */
    public function testSetCardDepositEntryWithoutMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No memo specified',
            150720013
        );

        $request = new Request();
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setEntryAction($request, 201502010000000001);
    }

    /**
     * 測試修改租卡入款明細時備註非UTF8
     */
    public function testSetCardDepositEntryButMemoNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = ['memo' => mb_convert_encoding('明細備註', 'GB2312', 'UTF-8')];

        $request = new Request([], $parameters);
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setEntryAction($request, 201502010000000001);
    }

    /**
     * 測試修改不存在的租卡入款明細
     */
    public function testSetCardDepositEntryWithEntryNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardDepositEntry found',
            150720019
        );

        $parameters = ['memo' => 'hrhrhr'];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $parameters);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setEntryAction($request, 201502010000000001);
    }

    /**
     * 測試帶入不合法的開始筆數，來取得租卡入款明細列表
     */
    public function testListEntryByInvalidFirstResult()
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
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->listEntryAction($request);
    }

    /**
     * 測試帶入不合法的顯示筆數，來取得租卡入款明細列表
     */
    public function testListEntryByInvalidMaxResults()
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
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->listEntryAction($request);
    }

    /**
     * 測試取得租卡入款明細列表未帶時間參數
     */
    public function testListEntryWithoutTimeParameter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150720014
        );

        $request = new Request();
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }

    /**
     * 測試租卡入款明細列表帶入的入款幣別不支援
     */
    public function testListEntryWithCurrencyUnsupport()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150720005
        );

        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'currency' => 'ABC'
        ];

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }

    /**
     * 測試租卡入款明細列表帶入的付款幣別不支援
     */
    public function testListEntryWithPaywayCurrencyUnsupport()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Payway currency not support',
            150720006
        );

        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'payway_currency' => 'ABC'
        ];

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }

    /**
     * 測試取得租卡入款明細總計未帶時間參數
     */
    public function testCardDepositTotalAmountWithoutTimeParameter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150720014
        );

        $request = new Request();
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getTotalAmountAction($request);
    }

    /**
     * 測試取得租卡入款明細總計帶入的入款幣別不支援
     */
    public function testCardDepositTotalAmountWithCurrencyUnsupport()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150720005
        );

        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'currency' => 'ABC'
        ];

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getTotalAmountAction($request);
    }

    /**
     * 測試取得租卡入款明細總計帶入的付款幣別不支援
     */
    public function testCardDepositTotalAmountWithPaywayCurrencyUnsupport()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Payway currency not support',
            150720006
        );

        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'payway_currency' => 'ABC'
        ];

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getTotalAmountAction($request);
    }

    /**
     * 測試確認入款時找不到入款明細
     */
    public function testCardDepositConfirmButEntryNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardDepositEntry found',
            150720019
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
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 9999);
    }

    /**
     * 測試人工確認入款時未帶入操作者Id及操作者名稱
     */
    public function testCardDepositManualConfirmWithoutOperatorIdAndOperatorName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Operator can not be null',
            150720015
        );

        $parameter = ['manual' => '1'];

        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201502010000000001);
    }

    /**
     * 測試人工確認入款時, 代入不存在的操作者Id及合法操作者名稱
     */
    public function testCardDepositManualConfirmWithInvalidOperatorIdAndValidOperatorName()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No operator found',
            150720016
        );

        $parameter = [
            'manual' => '1',
            'operator_id' => '9999999',
            'operator_name' => 'operator_name'
        ];

        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201502010000000001);
    }

    /**
     * 測試確認入款時明細已被確認
     */
    public function testCardDepositConfirmButEntryHasBeenConfirmed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'CardDepositEntry has been confirmed',
            150720017
        );

        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $cardDepositEntry->expects($this->once())
            ->method('isConfirm')
            ->willReturn(true);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201502010000000001);
    }

    /**
     * 測試確認入款時找不到商家
     */
    public function testCardDepositConfirmButMerchantCardNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            150720018
        );

        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201502010000000001);
    }

    /**
     * 測試確認入款時找不到使用者
     */
    public function testCardDepositConfirmButUserNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150720002
        );

        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($merchantCard);

        $request = new Request();
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201502010000000001);
    }

    /**
     * 測試確認入款時找不到租卡
     */
    public function testCardDepositConfirmButCardNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Card found',
            150720007
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($user);

        $request = new Request();
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201502010000000002);
    }

    /**
     * 測試人工確認入款時入款金額超過限制
     */
    public function testCardDepositManualConfirmButAmountExceedLimitation()
    {
        $this->setExpectedException(
            'RangeException',
            'Amount exceed DepositConfirmQuota of operator',
            150720021
        );

        $parameter = [
            'manual' => '1',
            'operator_id' => '1'
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $card = $this->getMockBuilder('BB\DurianBundle\Entity\Card')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getCard')
            ->willReturn($card);
        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $cardDepositEntry->expects($this->once())
            ->method('getAt')
            ->willReturn(new \Datetime('now'));
        $cardDepositEntry->expects($this->once())
            ->method('getAmount')
            ->willReturn(1000);
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(4))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->at(5))
            ->method('find')
            ->willReturn($user);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201502010000000002);
    }

    /**
     * 測試人工確認入款時, 代入金額上限為0的操作者
     */
    public function testCardDepositManualConfirmWithDepositConfirmQuotaNotExistsOperator()
    {
        $this->setExpectedException(
            'RangeException',
            'Amount exceed DepositConfirmQuota of operator',
            150720021
        );

        $parameter = [
            'manual' => '1',
            'operator_id' => '1'
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $card = $this->getMockBuilder('BB\DurianBundle\Entity\Card')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getCard')
            ->willReturn($card);
        $depositConfirmQuota = $this->getMockBuilder('BB\DurianBundle\Entity\DepositConfirmQuota')
            ->disableOriginalConstructor()
            ->getMock();
        $depositConfirmQuota->expects($this->once())
            ->method('getAmount')
            ->willReturn(0);
        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $cardDepositEntry->expects($this->once())
            ->method('getAt')
            ->willReturn(new \Datetime('now'));
        $cardDepositEntry->expects($this->once())
            ->method('getAmount')
            ->willReturn(1000);
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($user);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($depositConfirmQuota);
        $em->expects($this->at(4))
            ->method('find')
            ->willReturn($merchantCard);
        $em->expects($this->at(5))
            ->method('find')
            ->willReturn($user);

        $request = new Request([], $parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->confirmAction($request, 201502010000000002);
    }

    /**
     * 測試租卡入款解密驗證時明細不存在
     */
    public function testCardDepositVerifyDecodeButEntryNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardDepositEntry found',
            150720019
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
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->verifyDecodeAction($request, 9999);
    }

    /**
     * 測試租卡入款解密驗證時商家不存在
     */
    public function testCardDepositVerifyDecodeButMerchantCardNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            150720018
        );

        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->verifyDecodeAction($request, 201501080000000001);
    }

    /**
     * 測試租卡入款解密驗證時未傳入IP
     */
    public function testCardDepositVerifyDecodeWithoutBindIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            150720022
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isBindIp')
            ->willReturn(true);
        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);

        $request = new Request();
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->verifyDecodeAction($request, 201501080000000001);
    }

    /**
     * 測試租卡入款解密驗證時傳入的IP格式不合法
     */
    public function testCardDepositVerifyDecodeWithErrorIpFormat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            150720022
        );

        $parameter = ['bindIp' => '111.222.333.444'];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isBindIp')
            ->willReturn(true);
        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->verifyDecodeAction($request, 201501080000000001);
    }

    /**
     * 測試租卡入款解密驗證時傳入的IP不為已綁定的IP
     */
    public function testCardDepositVerifyDecodeWithErrorBindIp()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This ip is not bind',
            150720023
        );

        $parameter = ['bindIp' => '111.111.111.111'];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isBindIp')
            ->willReturn(true);
        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->verifyDecodeAction($request, 201501080000000001);
    }

    /**
     * 測試取得入款查詢結果時找不到入款明細
     */
    public function testCardTrackingCanNotFindDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardDepositEntry found',
            150720019
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

        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->trackingAction(9999);
    }

    /**
     * 測試取得入款查詢結果時支付平台不支援訂單查詢
     */
    public function testCardTrackingWithNotAutoReop()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support order tracking',
            150720024
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isAutoReop')
            ->willReturn(false);
        $merchantCard = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantCard->expects($this->once())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);
        $cardDepositEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cardDepositEntry);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantCard);

        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->trackingAction(201501080000000001);
    }

    /**
     * 測試取得租卡可用付款方式時傳入不支援幣別
     */
    public function testGetPaymentMethodWithUnAvailableCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150720008
        );

        $parameter = ['currency' => 'abc'];

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getPaymentMethodAction($request, 2);
    }

    /**
     * 測試取得租卡可用付款廠商時傳入不支援幣別
     */
    public function testGetPaymentVendorWithUnAvailableCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150720008
        );

        $parameter = ['currency' => 'abc'];

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getPaymentVendorAction($request, 2);
    }

    /**
     * 測試取得租卡可用付款廠商時未指定付款方式
     */
    public function testGetPaymentVendorWithoutPaymentMethod()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No payment method id specified',
            150720020
        );

        $parameter = ['currency' => 'CNY'];

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getPaymentVendorAction($request, 2);
    }

    /**
     * 測試取得租卡可用付款廠商時傳入空的付款方式
     */
    public function testGetPaymentVendorWithEmptyPaymentMethod()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No payment method id specified',
            150720020
        );

        $parameter = [
            'currency' => 'CNY',
            'payment_method_id' => ''
        ];

        $request = new Request($parameter);
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getPaymentVendorAction($request, 2);
    }

    /**
     * 測試取得實名認證所需參數但找不到租卡入款明細
     */
    public function testGetRealNameAuthParamsButCannotFindCardDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardDepositEntry found',
            150720019
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
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getRealNameAuthParamsAction($request, 201304280000000001);
    }

    /**
     * 測試取得實名認證所需參數但找不到租卡商家
     */
    public function testGetRealNameAuthParamsButCannotFindMerchantCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            150720018
        );

        $entry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($entry);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getRealNameAuthParamsAction($request, 201304280000000001);
    }

    /**
     * 測試取得實名認證結果但找不到租卡入款明細
     */
    public function testGetRealNameAuthButCannotFindCardDepositEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No CardDepositEntry found',
            150720019
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
        $controller = new CardDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getRealNameAuthAction($request, 201304280000000001);
    }
}
