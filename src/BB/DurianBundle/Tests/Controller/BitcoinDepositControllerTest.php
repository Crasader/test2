<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\BitcoinDepositController;
use Symfony\Component\HttpFoundation\Request;

class BitcoinDepositControllerTest extends ControllerTest
{
    /**
     * 測試新增比特幣入款記錄，未帶入金額
     */
    public function testCreateWithoutAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150920001
        );

        $parameters = [
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，未帶入比特幣金額
     */
    public function testCreateWithoutBitcoinAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No bitcoin_amount specified',
            150920002
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，未帶入比特幣匯率
     */
    public function testCreateWithoutBitcoinRate()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No bitcoin_rate specified',
            150920003
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，未帶入比特幣匯差
     */
    public function testCreateWithoutRateDifference()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No rate_difference specified',
            150920004
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，未帶入幣別
     */
    public function testCreateWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No currency specified',
            150920005
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入超過小數4位金額
     */
    public function testCreateWithInvalidAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'amount' => '100.00001',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入超過小數8位比特幣金額
     */
    public function testCreateWithInvalidBitcoinAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.123456789',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入超過小數8位比特幣匯率
     */
    public function testCreateWithInvalidBitcoinRate()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.123456789',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入超過小數8位比特幣匯差
     */
    public function testCreateWithInvalidRateDifference()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.123456789',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入超額金額
     */
    public function testCreateWithExceedAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'Amount exceed the MAX value',
            150920007
        );

        $parameters = [
            'amount' => '10000000001',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入錯誤的金額
     */
    public function testCreateWithInvalidTotalAmount()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Illegal bitcoin amount',
            150920027
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '6.00000001',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入未支援幣別
     */
    public function testCreateWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150920011
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'AAA',
            'memo' => '第一次入款'
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入不存在的使用者
     */
    public function testCreateWithNonExistUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150920023
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入不存在的使用者層級
     */
    public function testCreateWithNonExistUserLevel()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            150920012
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，帶入不存在的比特幣位址
     */
    public function testCreateWithNonExistBitcoinAddress()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No BitcoinAddress found',
            150920014
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUserLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUserLevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，找不到入款幣別轉換匯率
     */
    public function testCreateWithCurrencyInvalidExchange()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No such exchange',
            150920015
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUserLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinAddress = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinAddress')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockBitcoinAddress);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUserLevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockEntityRepoShare = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepoShare);

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('doctrine.orm.share_entity_manager', $mockEmShare);
        $controller->setContainer($container);

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，找不到使用者幣別轉換匯率
     */
    public function testCreateWithPaywayCurrencyInvalidExchange()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No such exchange',
            150920015
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUserLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinAddress = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinAddress')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockBitcoinAddress);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUserLevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockExchange = $this->getMockBuilder('BB\DurianBundle\Entity\Exchange')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepoShare = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findByCurrencyAt'])
            ->getMock();
        $mockEntityRepoShare->expects($this->at(0))
            ->method('findByCurrencyAt')
            ->willReturn($mockExchange);
        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepoShare);

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('doctrine.orm.share_entity_manager', $mockEmShare);
        $controller->setContainer($container);

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，無法產生比特幣入款明細id
     */
    public function testCreateCannotGenerateEntryId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot generate bitcoin deposit sequence id',
            150930001
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUserLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinAddress = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinAddress')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockBitcoinAddress);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUserLevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockExchange = $this->getMockBuilder('BB\DurianBundle\Entity\Exchange')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepoShare = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findByCurrencyAt'])
            ->getMock();
        $mockEntityRepoShare->expects($this->any())
            ->method('findByCurrencyAt')
            ->willReturn($mockExchange);
        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepoShare);

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('doctrine.orm.share_entity_manager', $mockEmShare);
        $controller->setContainer($container);

        $controller->createAction($request, 4);
    }

    /**
     * 測試新增比特幣入款記錄，flush出現錯誤
     */
    public function testCreateWithFlushException()
    {
        $this->setExpectedException(
            'Exception',
            'SQLSTATE[28000] [1045]',
            0
        );

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUserLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinAddress = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinAddress')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockBitcoinAddress);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUserLevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);
        $mockEm->expects($this->at(5))
            ->method('flush')
            ->willThrowException(new \Exception('SQLSTATE[28000] [1045]'));

        $mockExchange = $this->getMockBuilder('BB\DurianBundle\Entity\Exchange')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepoShare = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findByCurrencyAt'])
            ->getMock();
        $mockEntityRepoShare->expects($this->any())
            ->method('findByCurrencyAt')
            ->willReturn($mockExchange);
        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepoShare);

        $mockIdGenerator = $this->getMockBuilder('BB\DurianBundle\Bitcoin\Deposit\Entry\IdGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('doctrine.orm.share_entity_manager', $mockEmShare);
        $container->set('durian.bitcoin_deposit_entry_id_generator', $mockIdGenerator);
        $controller->setContainer($container);

        $controller->createAction($request, 4);
    }

    /**
     * 測試確認比特幣入款，未帶入操作者
     */
    public function testConfirmWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150920016
        );

        $parameters = ['control' => 1];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->confirmAction($request, 4);
    }

    /**
     * 測試確認比特幣入款，未帶入操作者控管端來源
     */
    public function testConfirmWithoutControl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No control specified',
            150920025
        );

        $parameters = ['operator' => 'test'];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->confirmAction($request, 4);
    }

    /**
     * 測試確認比特幣入款，找不到比特幣入款明細
     */
    public function testConfirmWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin deposit entry found',
            150920024
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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 4);
    }

    /**
     * 測試確認比特幣入款，訂單已被取消
     */
    public function testConfirmWithCanceledEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinDepositEntry has been cancelled',
            150920017
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinDepositEntry')
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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 4);
    }

    /**
     * 測試確認比特幣入款，訂單已被確認
     */
    public function testConfirmWithConfirmedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinDepositEntry has been confirmed',
            150920018
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinDepositEntry')
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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 4);
    }

    /**
     * 測試確認比特幣入款，找不到使用者
     */
    public function testConfirmWithNonExistUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150920023
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinDepositEntry')
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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 4);
    }

    /**
     * 測試確認比特幣入款，找不到user的cash資料
     */
    public function testConfirmWithNoCashUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash found',
            150920020
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinDepositEntry')
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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->confirmAction($request, 4);
    }

    /**
     * 測試確認入款時出現flush錯誤
     */
    public function testConfirmWithFlushException()
    {
        $this->setExpectedException(
            'Exception',
            'SQLSTATE[28000] [1045]',
            0
        );

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockCash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUser->expects($this->any())
            ->method('getCash')
            ->willReturn($mockCash);
        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinDepositEntry')
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
        $mockEm->expects($this->at(3))
            ->method('flush')
            ->willThrowException(new \Exception('SQLSTATE[28000] [1045]'));

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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $controller->setContainer($container);

        $controller->confirmAction($request, 4);
    }

    /**
     * 測試取消比特幣入款，未帶入操作者
     */
    public function testCancelWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No operator specified',
            150920016
        );

        $parameters = ['control' => 1];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->cancelAction($request, 4);
    }

    /**
     * 測試取消比特幣入款，未帶入操作者控管端來源
     */
    public function testCancelWithoutControl()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No control specified',
            150920025
        );

        $parameters = ['operator' => 'test'];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->cancelAction($request, 4);
    }

    /**
     * 測試取消比特幣入款，找不到比特幣入款明細
     */
    public function testCancelWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin deposit entry found',
            150920024
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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 4);
    }

    /**
     * 測試取消比特幣入款，訂單已被取消
     */
    public function testCancelWithCanceledEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinDepositEntry has been cancelled',
            150920017
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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 4);
    }

    /**
     * 測試取消比特幣入款，訂單已被確認
     */
    public function testCancelWithConfirmedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BitcoinDepositEntry has been confirmed',
            150920018
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
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->cancelAction($request, 4);
    }

    /**
     * 測試取得不存在的比特幣入款明細
     */
    public function testGetEntryWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin deposit entry found',
            150920024
        );

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->getEntryAction(123);
    }

    /**
     * 測試修改入款明細(只有備註)，未帶入備註
     */
    public function testSetBitcoinDepositEntryMemoWithoutMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No memo specified',
            150920026
        );

        $request = new Request();
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setBitcoinDepositEntryMemoAction($request, 123);
    }

    /**
     * 測試修改入款明細(只有備註)，備註輸入非UTF8
     */
    public function testSetBitcoinDepositEntryMemoInputNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = ['memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setBitcoinDepositEntryMemoAction($request, 123);
    }

    /**
     * 測試修改入款明細(只有備註)，找不到比特幣入款明細
     */
    public function testSetBitcoinDepositEntryMemoWithNonExistEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No bitcoin deposit entry found',
            150920024
        );

        $parameters = ['memo' => 'testMemo'];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinDepositController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->setBitcoinDepositEntryMemoAction($request, 123);
    }

    /**
     * 測試入款記錄列表，帶入不合法的開始筆數
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
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }

    /**
     * 測試入款記錄列表，帶入不合法的顯示筆數
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
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }

    /**
     * 測試入款記錄列表，帶入未支援幣別
     */
    public function testListEntryWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150920011
        );

        $parameters = ['currency' => 'AAA'];

        $request = new Request($parameters);
        $controller = new BitcoinDepositController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listEntryAction($request);
    }
}
