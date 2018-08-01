<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\MerchantWithdrawController;
use Symfony\Component\HttpFoundation\Request;

class MerchantWithdrawControllerTest extends ControllerTest
{
    /**
     * 測試取得不存在的出款商家
     */
    public function testGetMerchantWithdrawNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(1);
    }

    /**
     * 測試刪除出款商家但出款商家不存在
     */
    public function testRemoveButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試刪除已啟用出款商家
     */
    public function testRemoveEnabledMerchantWithdraw()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot delete when MerchantWithdraw enabled',
            150730017
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->any())
            ->method('isEnabled')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試刪除已暫停出款商家
     */
    public function testRemoveSuspendedMerchantWithdraw()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot delete when MerchantWithdraw suspended',
            150730018
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->any())
            ->method('isEnabled')
            ->willReturn(0);
        $merchantWithdraw->expects($this->any())
            ->method('isSuspended')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試停用時出款商家未核准
     */
    public function testDisableWhenNotApprove()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when MerchantWithdraw is not approved',
            150730002
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->disableAction(1);
    }

    /**
     * 測試修改出款商家alias非UTF8
     */
    public function testEditMerchantWithdrawAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($merchantWithdraw);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $query = [
            'alias' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'number' => '111111111',
            'domain' => 1
        ];

        $request = new Request([], $query);
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);

        $controller = new MerchantWithdrawController();
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改出款商家帶入錯誤的別名
     */
    public function testEditMerchantWithdrawWithInvalidAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantWithdraw alias',
            150730006
        );

        $query = ['alias' => ''];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改出款商家帶入錯誤的商號
     */
    public function testEditMerchantWithdrawWithInvalidNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantWithdraw number',
            150730007
        );

        $query = [
            'alias' => 'TestPay',
            'number' => '',
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改出款商家帶入錯誤的廳主
     */
    public function testEditMerchantWithdrawWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            150730008
        );

        $query = [
            'alias' => 'TestPay',
            'number' => '111111111',
            'domain' => ' '
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改不存在的出款商家
     */
    public function testEditMerchantWithdrawNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改出款商家帶入不存在的支付平台
     */
    public function testEditMerchantWithdrawPaymentGatewayNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            150730014
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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改出款商家帶入已刪除的支付平台
     */
    public function testEditMerchantWithdrawWithRemovedPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway is removed',
            150730004
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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改出款商家但支付平台不支援
     */
    public function testEditMerchantWithdrawButNotSupportedByPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantWithdraw is not supported by PaymentGateway',
            150730033
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->any())
            ->method('isRemoved')
            ->willReturn(false);
        $paymentGateway->expects($this->any())
            ->method('isWithdraw')
            ->willReturn(false);

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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定商家金鑰找不到出款商家
     */
    public function testSetKeyButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setKeyAction($request, 99);
    }

    /**
     * 測試移除出款商家金鑰但金鑰不存在
     */
    public function testRemoveKeyButMerchantWithdrawKeyNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdrawKey found',
            150730029
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeKeyAction(1);
    }

    /**
     * 測試修改密鑰但密鑰長度過長
     */
    public function testSetPrivateKeyButPrivateKeyIsTooLong()
    {
        $this->setExpectedException(
            'RangeException',
            'Private Key is too long',
            150730031
        );

        $query = [
            'private_key' => str_repeat('1', 1025),
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setPrivateKeyAction($request, 1);
    }

    /**
     * 測試修改私鑰但出款商家不存在
     */
    public function testSetPrivateKeyButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setPrivateKeyAction($request, 99);
    }

    /**
     * 測試恢復暫停出款商家但出款商家不存在
     */
    public function testMerchantWithdrawResumeButNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->resumeAction(1);
    }

    /**
     * 測試恢復暫停出款商家但出款商家未核准
     */
    public function testMerchantWithdrawResumeButNotApprove()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when MerchantWithdraw is not approved',
            150730002
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->resumeAction(1);
    }

    /**
     * 測試恢復暫停出款商家時出款商家停用
     */
    public function testMerchantWithdrawResumeButDisabled()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when MerchantWithdraw disabled',
            150730003
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchantWithdraw->expects($this->once())
            ->method('isEnabled')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->resumeAction(1);
    }

    /**
     * 測試啟用出款商家但出款商家未核准
     */
    public function testEnableMerchantWithdrawButNotApprove()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when MerchantWithdraw is not approved',
            150730002
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試啟用出款商家但商家已刪除
     */
    public function testEnableMerchantWithdrawButMerchantRemoved()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway is removed',
            150730004
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchantWithdraw->expects($this->once())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試啟用出款商家但支付平台已刪除
     */
    public function testEnableMerchantWithdrawButPaymentGatewayRemoved()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway is removed',
            150730004
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchantWithdraw->expects($this->once())
            ->method('getPaymentGateway')
            ->willReturn($paymentGateway);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->enableAction(1);
    }

    /**
     * 測試取得出款商家列表帶入空幣別
     */
    public function testGetMerchantWithdrawListWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150730005
        );

        $query = ['currency' => ''];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listAction($request, 1);
    }

    /**
     * 測試取得出款商家列表帶入錯誤幣別
     */
    public function testGetMerchantWithdrawListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150730005
        );

        $query = ['currency' => 'AAAA'];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listAction($request, 1);
    }

    /**
     * 測試取得出款商家其他設定但出款商家不存在
     */
    public function testGetMerchantWithdrawExtraButNoMerchantWithdrawFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getExtraAction($request, 1);
    }

    /**
     * 測試取得出款商家其他設定但出款商家其他設定不存在
     */
    public function testGetMerchantWithdrawExtraButNoMerchantWithdrawExtraFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdrawExtra found',
            150730019
        );

        $repo = $this->getMockBuilder('BB\DurianBundle\Repository\MerchantWithdrawExtra')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock();
        $repo->expects($this->any())
            ->method('findBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(true);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $request = new Request();
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getExtraAction($request, 1);
    }

    /**
     * 測試設定出款商家其他設定未帶入參數
     */
    public function testSetMerchantWithdrawExtraWithEmptyExtra()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No MerchantWithdrawExtra specified',
            150730027
        );

        $request = new Request();
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試設定出款商家其他設定傳入空的設定值
     */
    public function testSetMerchantWithdrawExtraWithEmptyExtraValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No MerchantWithdrawExtra specified',
            150730027
        );

        $extras[] = [
            'name' => '',
            'value' => ''
        ];

        $query = ['merchant_withdraw_extra' => $extras];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試設定出款商家其他設定來設定停用金額
     */
    public function testSetMerchantWithdrawExtraWithBankLimit()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot set bankLimit',
            150730030
        );

        $extras[] = [
            'name' => 'bankLimit',
            'value' => '111'
        ];

        $query = ['merchant_withdraw_extra' => $extras];

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantWithdraw);

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

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試設定不存在的出款商家
     */
    public function testSetMerchantWithdrawExtraWithMerchantWithdrawNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
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

        $query = ['merchant_withdraw_extra' => $extras];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試設定不存在的出款商家設定
     */
    public function testSetMerchantWithdrawExtraWithMerchantWithdrawExtraNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdrawExtra found',
            150730019
        );

        $repo = $this->getMockBuilder('BB\DurianBundle\Repository\MerchantWithdrawExtra')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $repo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantWithdraw);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $extras = [];
        $extras[] = [
            'name' => 'over',
            'value' => '111'
        ];
        $extras[] = [
            'name' => 'gohometime',
            'value' => '222'
        ];

        $query = ['merchant_withdraw_extra' => $extras];
        $request = new Request([], $query);

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

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setExtraAction($request, 1);
    }

    /**
     * 測試取得出款商號停用金額相關資訊帶入錯誤幣別
     */
    public function testGetMerchantWithdrawBankLimitListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150730005
        );

        $query = ['currency' => 'AAAA'];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getBankLimitListAction($request);
    }

    /**
     * 測試取得出款商號停用金額相關資訊帶入空幣別
     */
    public function testGetMerchantWithdrawBankLimitListWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150730005
        );

        $query = ['currency' => ''];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getBankLimitListAction($request);
    }

    /**
     * 測試取得出款商家訊息帶入不合法的開始筆數
     */
    public function testGetMerchantWithdrawRecordWithInvalidFirstResult()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid first_result',
            150610004
        );

        $params = [
            'domain' => 2,
            'first_result' => -5,
            'max_results' => 1
        ];

        $request = new Request([], $params);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getRecordAction($request);
    }

    /**
     * 測試取得出款商家訊息帶入不合法的顯示筆數
     */
    public function testGetMerchantWithdrawRecordWithInvalidMaxResults()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid max_results',
            150610005
        );

        $params = [
            'domain' => 2,
            'first_result' => 0,
            'max_results' => -1
        ];

        $request = new Request([], $params);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getRecordAction($request);
    }

    /**
     * 測試取得出款商家訊息帶入不合法domain
     */
    public function testGetMerchantWithdrawRecordWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            150730008
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $params = [
            'domain' => 40,
        ];

        $request = new Request([], $params);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getRecordAction($request);
    }

    /**
     * 測試取得出款商家訊息未帶入domain
     */
    public function testGetMerchantWithdrawRecordWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            150730008
        );

        $request = new Request();
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getRecordAction($request);
    }

    /**
     * 測試核准出款商家但出款商家不存在
     */
    public function testApproveButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->approveAction(555);
    }

    /**
     * 測試新增出款商家沒帶入支付平台
     */
    public function testNewMerchantWithdrawWithoutPaymentGatewayId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No payment_gateway_id specified',
            150730016
        );

        $query = ['payment_gateway_id' => ''];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家沒帶入別名
     */
    public function testNewMerchantWithdrawWithoutAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantWithdraw alias',
            150730006
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家的alias輸入帶入非UTF8
     */
    public function testNewMerchantWithdrawAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家沒帶入商號
     */
    public function testNewMerchantWithdrawWithoutNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantWithdraw number',
            150730007
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家沒帶入廳主
     */
    public function testNewMerchantWithdrawWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            150730008
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家沒帶入幣別
     */
    public function testNewMerchantWithdrawWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150730009
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => ''
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家帶入錯誤的幣別
     */
    public function testNewMerchantWithdrawWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150730009
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'RMB'
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家但密鑰長度過長
     */
    public function testNewMerchantWithdrawButPrivateKeyIsTooLong()
    {
        $this->setExpectedException(
            'RangeException',
            'Private Key is too long',
            150730031
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => str_repeat('1', 1025),
        ];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家帶入不存在的層級
     */
    public function testNewMerchantWithdrawButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150730010
        );

        $query = [
            'payment_gateway_id' => 1,
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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家帶入不存在的支付平台
     */
    public function testNewMerchantWithdrawWithInvalidPaymentGatewayId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            150730014
        );

        $query = [
            'payment_gateway_id' => 999,
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
            ->willReturn([$level, $level]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家帶入已刪除的支付平台
     */
    public function testNewMerchantWithdrawWithRemovedPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway is removed',
            150730004
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'level_id' => [
                '1',
                '2'
            ]
        ];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isRemoved')
            ->willReturn(1);

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
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家帶入不支援出款的支付平台
     */
    public function testNewMerchantWithdrawNotSupportedByPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantWithdraw is not supported by PaymentGateway',
            150730033
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'level_id' => [
                '1',
                '2'
            ]
        ];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isWithdraw')
            ->willReturn(0);

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
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增出款商家帶入支付平台不支援的幣別
     */
    public function testNewMerchantWithdrawWithCurrencyNotSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Currency is not support by PaymentGateway',
            150730011
        );

        $query = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'VND',
            'level_id' => [
                '1',
                '2'
            ]
        ];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isWithdraw')
            ->willReturn(1);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findBy')
            ->willReturn([$level, $level]);
        $entityRepo->expects($this->at(1))
            ->method('findBy')
            ->willReturn([]);

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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試同分秒新增出款商家
     */
    public function testNewMerchantWithdrawConcurrency()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Could not create MerchantWithdraw because MerchantWithdraw is updating',
            150730012
        );

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'VND'
        ];

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->once())
            ->method('isWithdraw')
            ->willReturn(1);
        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGatewayCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGatewayCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'updatePaymentGatewayVersion'])
            ->getMock();
        $entityRepo->expects($this->at(1))
            ->method('findBy')
            ->willReturn($level);
        $entityRepo->expects($this->at(2))
            ->method('findBy')
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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試設定出款商家停用金額,金額不合法
     */
    public function testSetMerchantWithdrawBankLimitInvalidValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid MerchantWithdrawExtra value',
            150730020
        );

        $query = ['value' => 0.1];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setMerchantWithdrawBankLimitAction($request, 1);
    }

    /**
     * 測試設定出款商家停用金額但商家不存在
     */
    public function testSetMerchantWithdrawBankLimitButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $query = ['value' => 1000];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setMerchantWithdrawBankLimitAction($request, 999);
    }

    /**
     * 測試暫停出款商家不存在
     */
    public function testSuspendNoneExistMerchantWithdraw()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->suspendAction(1);
    }

    /**
     * 測試暫停未核准的出款商家
     */
    public function testSuspendUnapprovedMerchantWithdraw()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when MerchantWithdraw is not approved',
            150730002
        );

        $merchantAgent = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantAgent->expects($this->once())
            ->method('isApproved')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantAgent);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->suspendAction(1);
    }

    /**
     * 測試暫停核准但被停用的出款商家
     */
    public function testSuspendApprovedAndDisabledMerchantWithdraw()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when MerchantWithdraw disabled',
            150730003
        );

        $merchantAgent = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantAgent->expects($this->once())
            ->method('isApproved')
            ->willReturn(1);
        $merchantAgent->expects($this->once())
            ->method('isEnabled')
            ->willReturn(0);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($merchantAgent);

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->suspendAction(1);
    }

    /**
     * 測試新增出款商家ip限制帶入空country_id
     */
    public function testAddMerchantWithdrawIpStrategyWithEmptyCountryId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No country id given',
            150730021
        );

        $query = ['country_id' => ''];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試新增出款商家ip限制但商家不存在
     */
    public function testAddMerchantWithdrawIpStrategyButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $query = ['country_id' => 1];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 999);
    }

    /**
     * 測試新增出款商家ip限制時帶入不存在國家
     */
    public function testAddMerchantWithdrawIpStrategyCountryNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot find specified country',
            150730022
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($merchantWithdraw);

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $query = ['country_id' => 999];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試新增出款商家ip限制時帶入不存在區域
     */
    public function testAddMerchantWithdrawIpStrategyRegionNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot find specified region',
            150730023
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $country = $this->getMockBuilder('BB\DurianBundle\Entity\GeoipCountry')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($merchantWithdraw);

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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試新增出款商家ip限制時帶入不存在城市
     */
    public function testAddMerchantWithdrawIpStrategyCityNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot find specified city',
            150730024
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
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
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($merchantWithdraw);

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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試新增出款商家ip限制新增重複設定
     */
    public function testAddMerchantWithdrawIpStrategyWithDuplicateStrategy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate MerchantWithdrawIpStrategy',
            150730025
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdrawIpStrategy = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdrawIpStrategy')
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

        $repo = $this->getMockBuilder('BB\DurianBundle\Repository\MerchantWithdrawIpStrategy')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $repo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($merchantWithdrawIpStrategy);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($merchantWithdraw);
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
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('doctrine.orm.share_entity_manager', $emShare);
        $controller->setContainer($container);

        $controller->addIpStrategyAction($request, 1);
    }

    /**
     * 測試回傳出款商號ip限制但商家不存在
     */
    public function testGetMerchantWithdrawIpStrategyButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getIpStrategyAction($request, 999);
    }

    /**
     * 測試移除出款商家ip限制不存在ip限制
     */
    public function testRemoveIpStrategyIpStrategyNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No IpStrategy found',
            150730026
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeIpStrategyAction(1);
    }

    /**
     * 測試檢查出款商家ip限制但是沒帶入ip
     */
    public function testCheckMerchantWithdrawIpLimitWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            150730028
        );

        $query = ['ip' => ''];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->checkIpLimitAction($request, 1);
    }

    /**
     * 測試出款商家檢查ip限制但是出款商家不存在
     */
    public function testCheckMerchantWithdrawIpLimitWithMerchantWithdrawNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150730001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $query = ['ip' => '42.4.0.0'];

        $request = new Request([], $query);
        $controller = new MerchantWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->checkIpLimitAction($request, 99);
    }
}
