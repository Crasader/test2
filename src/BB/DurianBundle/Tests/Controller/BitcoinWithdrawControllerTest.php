<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\BitcoinWithdrawController;
use Symfony\Component\HttpFoundation\Request;

class BitcoinWithdrawControllerTest extends ControllerTest
{
    /**
     * 測試新增比特幣出款記錄，未帶入幣別
     */
    public function testCreateWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No currency specified',
            150940001
        );

        $parameters = [
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，未帶入金額
     */
    public function testCreateWithoutAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150940002
        );

        $parameters = [
            'currency' => 'TWD',
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，未帶入比特幣金額
     */
    public function testCreateWithoutBitcoinAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No bitcoin_amount specified',
            150940003
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，未帶入比特幣匯率
     */
    public function testCreateWithoutBitcoinRate()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No bitcoin_rate specified',
            150940004
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，未帶入比特幣匯差
     */
    public function testCreateWithoutRateDifference()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No rate_difference specified',
            150940005
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，未帶入IP
     */
    public function testCreateWithoutIP()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ip specified',
            150940006
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，未帶入出款位址
     */
    public function testCreateWithoutWithdrawAddress()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No withdraw_address specified',
            150940007
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入未支援幣別
     */
    public function testCreateWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150940008
        );

        $parameters = [
            'currency' => 'AAA',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入超額金額
     */
    public function testCreateWithExceedAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'Amount exceed the MAX value',
            150940009
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => 10000000001,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入超過小數4位金額
     */
    public function testCreateWithInvalidAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50.00001,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入超過小數8位比特幣金額
     */
    public function testCreateWithInvalidBitcoinAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.123456789',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入超過小數8位比特幣匯率
     */
    public function testCreateWithInvalidBitcoinRate()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.123456789',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入超過小數8位比特幣匯差
     */
    public function testCreateWithInvalidRateDifference()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.123456789',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入超過小數4位優惠扣除額
     */
    public function testCreateWithInvalidDeduction()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0.12345,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入超過小數4位常態稽核行政費用
     */
    public function testCreateWithInvalidAuditCharge()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0.12345,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入超過小數4位常態稽核手續費
     */
    public function testCreateWithInvalidAuditFee()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0.12345,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入錯誤的金額
     */
    public function testCreateWithInvalidTotalAmount()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Illegal bitcoin amount',
            150940029
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.00000001',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入錯誤的IP
     */
    public function testCreateWithInvalidIP()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid IP',
            150940010
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1.2',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入不存在的使用者
     */
    public function testCreateWithNonExistUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150940022
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，帶入不存在的使用者層級
     */
    public function testCreateWithNonExistUserLevel()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            150940011
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUser);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，找不到user的cash資料
     */
    public function testCreateWithNoCashUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash found',
            150940012
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUserLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUserLevel);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 7);
    }

    /**
     * 測試新增比特幣出款記錄，找不到UserDetail
     */
    public function testCreateWithoutUserDetail()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No detail data found',
            150940028
        );

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];

        $mockCash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUser->expects($this->any())
            ->method('getCash')
            ->willReturn($mockCash);
        $mockUserLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUserLevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，未帶入操作者
     */
    public function testConfirmWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150940013
        );

        $parameters = [
            'control' => 1,
            'manual' => 1,
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，未帶入操作者控管端來源
     */
    public function testConfirmWithoutControl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No control specified',
            150940023
        );

        $parameters = [
            'operator' => 'test',
            'manual' => 1,
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，找不到比特幣出款明細
     */
    public function testConfirmWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin withdraw entry found',
            150940014
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 1,
        ];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，找不到使用者
     */
    public function testConfirmWithNonExistUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150940022
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，訂單已被取消
     */
    public function testConfirmWithCanceledEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been cancelled',
            150940015
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('isCancel')
            ->willReturn(1);
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，訂單已被確認
     */
    public function testConfirmWithConfirmedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been confirme',
            150940016
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(1);
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，訂單尚未鎖定
     */
    public function testConfirmWithUnlockedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry should be locked first',
            150940017
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(0);
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，鎖定與確認操作者不符
     */
    public function testConfirmWithInvalidOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid operator',
            150940018
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('operator2');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，自動出款未帶入比特幣錢包id
     */
    public function testConfirmAutoWithdrawWithoutWalletId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No wallet_id specified',
            150940026
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 0,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('isControl')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('test');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，自動出款找不到比特幣錢包
     */
    public function testConfirmAutoWithdrawWithNonExistBitcoinWallet()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such bitcoin wallet',
            150940027
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 0,
            'bitcoin_wallet_id' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('isControl')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('test');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，自動出款比特幣錢包未設定出款公鑰
     */
    public function testConfirmAutoWithdrawWithBitcoinWalletNonExistXpub()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Withdraw xpub of BitcoinWallet does not exist',
            150940025
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 0,
            'bitcoin_wallet_id' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('isControl')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('test');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinWallet->expects($this->any())
            ->method('getXpub')
            ->willReturn(null);
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockBitcoinWallet);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，自動出款連線API失敗
     */
    public function testConfirmAutoWithdrawWithFailureConnection()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 0,
            'bitcoin_wallet_id' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('isControl')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('test');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinWallet->expects($this->any())
            ->method('getXpub')
            ->willReturn('testXpub');
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockBitcoinWallet);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBlockChain->expects($this->any())
            ->method('makePayment')
            ->willThrowException(new \RuntimeException('Parse data error', 150180204));

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $container->set('durian.block_chain', $mockBlockChain);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試確認比特幣出款，自動出款比特幣錢包設定錯誤第二密碼
     */
    public function testConfirmAutoWithdrawWithErrorSecondPasswordWallet()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Second password incorrect',
            150180202
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 0,
            'bitcoin_wallet_id' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('isControl')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('test');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinWallet->expects($this->any())
            ->method('getXpub')
            ->willReturn('testXpub');
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockBitcoinWallet);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBlockChain->expects($this->any())
            ->method('makePayment')
            ->willThrowException(new \RuntimeException('Second password incorrect', 150180202));

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $container->set('durian.block_chain', $mockBlockChain);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試同分秒確認比特幣出款
     */
    public function testConfirmWithDuplicateEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150940019
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('isControl')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('test');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150900004, $pdoExcep));

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $controller->setContainer($container);

        $controller->confirmAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，未帶入操作者
     */
    public function testCancelWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150940013
        );

        $parameters = ['control' => 1];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，未帶入操作者控管端來源
     */
    public function testCancelWithoutControl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No control specified',
            150940023
        );

        $parameters = ['operator' => 'test'];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，找不到比特幣出款明細
     */
    public function testCancelWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin withdraw entry found',
            150940014
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，訂單已被取消
     */
    public function testCancelWithCanceledEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been cancelled',
            150940015
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('isCancel')
            ->willReturn(1);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，訂單已被確認
     */
    public function testCancelWithConfirmedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been confirme',
            150940016
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(1);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，訂單尚未鎖定
     */
    public function testCancelWithUnlockedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry should be locked first',
            150940017
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(0);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，鎖定與取消操作者不符
     */
    public function testCancelInvalidOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid operator',
            150940018
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('operator2');
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，找不到使用者
     */
    public function testCancelWithNonExistUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150940022
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('isControl')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('test');
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(3))
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試取消比特幣出款，找不到user的cash資料
     */
    public function testCancelWithNoCashUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash found',
            150940012
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('isControl')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('test');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($mockEntry);
        $mockEm->expects($this->at(3))
            ->method('find')
            ->willReturn($mockUser);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 7);
    }

    /**
     * 測試鎖定比特幣出款，未帶入操作者
     */
    public function testLockedWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150940013
        );

        $parameters = ['control' => 1];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->lockedAction($request, 7);
    }

    /**
     * 測試鎖定比特幣出款，未帶入操作者控管端來源
     */
    public function testLockedWithoutControl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No control specified',
            150940023
        );

        $parameters = ['operator' => 'test'];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->lockedAction($request, 7);
    }

    /**
     * 測試鎖定比特幣出款，找不到比特幣出款明細
     */
    public function testLockedWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin withdraw entry found',
            150940014
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->lockedAction($request, 7);
    }

    /**
     * 測試鎖定比特幣出款，訂單已被取消
     */
    public function testLockedWithCanceledEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been cancelled',
            150940015
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('isCancel')
            ->willReturn(1);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->lockedAction($request, 7);
    }

    /**
     * 測試鎖定比特幣出款，訂單已被確認
     */
    public function testLockedWithConfirmedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been confirme',
            150940016
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(1);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->lockedAction($request, 7);
    }

    /**
     * 測試鎖定比特幣出款，訂單已鎖定
     */
    public function testLockedWithLockedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been locked',
            150940020
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->lockedAction($request, 7);
    }

    /**
     * 測試解除鎖定比特幣出款，未帶入操作者
     */
    public function testUnlockedWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150940013
        );

        $parameters = [
            'control' => 1,
            'force' => 0,
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->unlockedAction($request, 7);
    }

    /**
     * 測試解除鎖定比特幣出款，未帶入操作者控管端來源
     */
    public function testUnlockedWithoutControl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No control specified',
            150940023
        );

        $parameters = [
            'operator' => 'test',
            'force' => 0,
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->unlockedAction($request, 7);
    }

    /**
     * 測試解除鎖定比特幣出款，找不到比特幣出款明細
     */
    public function testUnlockedWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin withdraw entry found',
            150940014
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'force' => 0,
        ];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->unlockedAction($request, 7);
    }

    /**
     * 測試解除鎖定比特幣出款，訂單已被取消
     */
    public function testUnlockedWithCanceledEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been cancelled',
            150940015
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'force' => 0,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('isCancel')
            ->willReturn(1);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->unlockedAction($request, 7);
    }

    /**
     * 測試解除鎖定比特幣出款，訂單已被確認
     */
    public function testUnlockedWithConfirmedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry has been confirme',
            150940016
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'force' => 0,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(1);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->unlockedAction($request, 7);
    }

    /**
     * 測試解除鎖定比特幣出款，訂單已未鎖定
     */
    public function testUnlockedWithUnlockedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinWithdrawEntry already unlock',
            150940021
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'force' => 0,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(0);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->unlockedAction($request, 7);
    }

    /**
     * 測試解除鎖定比特幣出款，鎖定與解除鎖定操作者不符
     */
    public function testUnlockedWithInvalidOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid operator',
            150940018
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'force' => 0,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('islocked')
            ->willReturn(1);
        $mockEntry->expects($this->any())
            ->method('getOperator')
            ->willReturn('operator2');
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockEntry);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->unlockedAction($request, 7);
    }

    /**
     * 測試取得不存在的比特幣出款明細
     */
    public function testGetEntryWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin withdraw entry found',
            150940014
        );

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->getEntryAction(123);
    }

    /**
     * 測試修改出款明細(只有備註)，未帶入幣別備註
     */
    public function testSetBitcoinWithdrawEntryMemoWithoutMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No memo specified',
            150940024
        );

        $request = new Request();
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setBitcoinWithdrawEntryMemoAction($request, 123);
    }

    /**
     * 測試修改出款明細(只有備註)，備註輸入非UTF8
     */
    public function testSetBitcoinWithdrawEntryMemoInputNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = ['memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setBitcoinWithdrawEntryMemoAction($request, 123);
    }

    /**
     * 測試修改出款明細(只有備註)，找不到比特幣出款明細
     */
    public function testSetBitcoinWithdrawEntryMemoWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin withdraw entry found',
            150940014
        );

        $parameters = ['memo' => 'testMemo'];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWithdrawController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->setBitcoinWithdrawEntryMemoAction($request, 123);
    }

    /**
     * 測試出款記錄列表，帶入不合法的開始筆數
     */
    public function testListEntryWithInvalidFirstResult()
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
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }

    /**
     * 測試出款記錄列表，帶入不合法的顯示筆數
     */
    public function testListEntryWithInvalidMaxResults()
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
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }

    /**
     * 測試出款記錄列表，帶入未支援幣別
     */
    public function testListEntryWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150940008
        );

        $parameters = ['currency' => 'AAA'];

        $request = new Request($parameters);
        $controller = new BitcoinWithdrawController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }
}
