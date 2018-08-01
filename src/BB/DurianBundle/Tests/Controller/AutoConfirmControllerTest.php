<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\AutoConfirmController;
use Symfony\Component\HttpFoundation\Request;

class AutoConfirmControllerTest extends ControllerTest
{
    /**
     * 測試檢查帳號狀態時，沒有帶入後置碼
     */
    public function testCheckStatusWithoutLoginCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No login code parameter given',
            150830001
        );

        $params = [];

        $request = new Request($params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->checkStatusAction($request);
    }

    /**
     * 測試檢查帳號狀態時，沒有帶入銀行帳號
     */
    public function testCheckStatusWithoutAccount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No account parameter given',
            150830002
        );

        $params = ['login_code' => 'xix'];

        $request = new Request($params);
        $controller = new AutoConfirmController();
        $controller->setcontainer(static::$kernel->getContainer());

        $controller->checkStatusAction($request);
    }

    /**
     * 測試檢查帳號狀態時，找不到廳
     */
    public function testCheckStatusButNoDomainConfigFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No domain config found',
            150830003
        );

        $params = [
            'login_code' => 'xix',
            'account' => '1234567890987654321',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->checkStatusAction($request);
    }

    /**
     * 測試檢查帳號狀態時，找不到公司入款帳號
     */
    public function testCheckStatusButNoRemitAccountFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No RemitAccount found',
            150830004
        );

        $params = [
            'login_code' => 'xix',
            'account' => '1234567890987654321',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $domainConfig->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->checkStatusAction($request);
    }

    /**
     * 測試檢查帳號狀態時，爬蟲未啟用
     */
    public function testCheckStatusButCrawlerIsNotEnable()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Crawler is not enabled',
            150830005
        );

        $params = [
            'login_code' => 'xix',
            'account' => '1234567890987654321',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $domainConfig->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('isCrawlerOn')
            ->willReturn(false);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->checkStatusAction($request);
    }

    /**
     * 測試檢查帳號狀態時，網銀密碼錯誤
     */
    public function testCheckStatusButWebBankPasswordError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Web bank password error',
            150830006
        );

        $params = [
            'login_code' => 'xix',
            'account' => '1234567890987654321',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $domainConfig->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('isCrawlerOn')
            ->willReturn(true);
        $remitAccount->expects($this->any())
            ->method('isPasswordError')
            ->willReturn(true);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->checkStatusAction($request);
    }

    /**
     * 測試檢查帳號狀態時，找不到廳的相關設定
     */
    public function testCheckStatusButNoAutoConfirmConfigFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No DomainAutoRemit found',
            150830007
        );

        $params = [
            'login_code' => 'xix',
            'account' => '1234567890987654321',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $domainConfig->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('isCrawlerOn')
            ->willReturn(true);
        $remitAccount->expects($this->any())
            ->method('isPasswordError')
            ->willReturn(false);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(3))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount, null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request($params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->checkStatusAction($request);
    }

    /**
     * 測試鎖定網銀密碼錯誤時，沒有帶入後置碼
     */
    public function testLockPasswordErrorWithoutLoginCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No login code parameter given',
            150830026
        );

        $params = [];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->lockPasswordErrorAction($request, '1234567890987654321');
    }

    /**
     * 測試鎖定網銀密碼錯誤時，找不到廳
     */
    public function testLockPasswordErrorButNoDomainConfigFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No domain config found',
            150830027
        );

        $params = ['login_code' => 'xix'];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->lockPasswordErrorAction($request, '1234567890987654321');
    }

    /**
     * 測試鎖定網銀密碼錯誤時，找公司入款帳號
     */
    public function testLockPasswordErrorButNoRemitAccountFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No RemitAccount found',
            150830028
        );

        $params = ['login_code' => 'xix'];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $domainConfig->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturn($domainConfig, null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->lockPasswordErrorAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，沒有帶入後置碼
     */
    public function testCreateWithoutLonigCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No login code parameter given',
            150830008
        );

        $params = [];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，沒有帶入匯款資料
     */
    public function testCreateWithoutData()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No data parameter given',
            150830009
        );

        $params = ['login_code' => 'xix'];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，沒有帶入餘額
     */
    public function testCreateWithoutBalance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No balance parameter given',
            150830010
        );

        $params = [
            'login_code' => 'xix',
            'data' => '{}',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，餘額不是數字
     */
    public function testCreateWithBalanceNotNumeric()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Balance must be numeric',
            150830022
        );

        $params = [
            'login_code' => 'xix',
            'data' => '{}',
            'balance' => '10,000.24',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細金額
     */
    public function testCreateWithoutEntryAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry amount parameter given',
            150830011
        );

        $data = [];
        $data1 = [];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細手續費
     */
    public function testCreateWithoutEntryFee()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry fee parameter given',
            150830012
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細餘額
     */
    public function testCreateWithoutEntryBalance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry balance parameter given',
            150830013
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細帳號
     */
    public function testCreateWithoutEntryAccount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry account parameter given',
            150830014
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細姓名
     */
    public function testCreateWithoutEntryName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry name parameter given',
            150830015
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細時間
     */
    public function testCreateWithoutEntryTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry time parameter given',
            150830016
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細附言
     */
    public function testCreateWithoutEntryMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry memo parameter given',
            150830017
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細收支信息
     */
    public function testCreateWithoutEntryMessage()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry message parameter given',
            150830018
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細匯款方式
     */
    public function testCreateWithoutEntryMethod()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry method parameter given',
            150830019
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細金額非數字
     */
    public function testCreateButEntryAmountNotNumeric()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount must be numeric',
            150830020
        );

        $data = [];
        $data1 = [
            'amount' => 'abc',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細手續費非數字
     */
    public function testCreateButEntryFeeNotNumeric()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Fee must be numeric',
            150830021
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => 'abc',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細餘額非數字
     */
    public function testCreateButEntryBalanceNotNumeric()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Balance must be numeric',
            150830022
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => 'abc',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到廳
     */
    public function testCreateButNoDomainConfigFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No domain config found',
            150830023
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到帳號
     */
    public function testCreateButNoRemitAccountFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No RemitAccount found',
            150830024
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $domainConfig->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createAction($request, '999');
    }

    /**
     * 測試新增單一筆匯款資料時，沒有帶入外部訂單ID
     */
    public function testCreateSingleWithoutRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry ref_id parameter given',
            150830050
        );

        $params = [];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增單一筆匯款資料時，訂單已存在
     */
    public function testCreateSingleButAutoConfirmEntryAlreadyExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'AutoConfirmEntry already exists',
            150830051
        );

        $params = ['ref_id' => 'thisisrefid'];

        $autoConfirmEntry = $this->getMockBuilder('BB\DurianBundle\Entity\AutoConfirmEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($autoConfirmEntry);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增單一筆匯款資料時，沒有帶入後置碼
     */
    public function testCreateSingleWithoutLonigCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No login code parameter given',
            150830008
        );

        $params = ['ref_id' => 'thisisrefid'];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細金額
     */
    public function testCreateSingleWithoutEntryAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry amount parameter given',
            150830011
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細手續費
     */
    public function testCreateSingleWithoutEntryFee()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry fee parameter given',
            150830012
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細餘額
     */
    public function testCreateSingleWithoutEntryBalance()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry balance parameter given',
            150830013
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細帳號
     */
    public function testCreateSingleWithoutEntryAccount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry account parameter given',
            150830014
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細姓名
     */
    public function testCreateSingleWithoutEntryName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry name parameter given',
            150830015
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細時間
     */
    public function testCreateSingleWithoutEntryTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry time parameter given',
            150830016
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細附言
     */
    public function testCreateSingleWithoutEntryMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry memo parameter given',
            150830017
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細收支信息
     */
    public function testCreateSingleWithoutEntryMessage()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry message parameter given',
            150830018
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細匯款方式
     */
    public function testCreateSingleWithoutEntryMethod()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No entry method parameter given',
            150830019
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細金額非數字
     */
    public function testCreateSingleButEntryAmountNotNumeric()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount must be numeric',
            150830020
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => 'abc',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細手續費非數字
     */
    public function testCreateSingleButEntryFeeNotNumeric()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Fee must be numeric',
            150830021
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => 'abc',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，缺少匯款明細餘額非數字
     */
    public function testCreateSingleButEntryBalanceNotNumeric()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Balance must be numeric',
            150830022
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => 'abc',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，時間格式不正確
     */
    public function testCreateSingleButTimeInvalid()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid time given',
            150830052
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '1',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到廳
     */
    public function testCreateSingleButNoDomainConfigFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No domain config found',
            150830023
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到帳號
     */
    public function testCreateSingleButNoRemitAccountFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No RemitAccount found',
            150830024
        );

        $params = [
            'ref_id' => 'thisisrefid',
            'login_code' => 'xix',
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(3))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls(null, $domainConfig, null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->createSingleAction($request, '1234567890987654321');
    }

    /**
     * 測試取得一筆匯款記錄時，找不到匯款明細
     */
    public function testGetEntryButNoAutoConfirmEntryFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoConfirmEntry found',
            150830025
        );

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getEntryAction('999');
    }

    /**
     * 測試新增匯款資料時，找不到公司入款訂單號
     */
    public function testCreateButNoRemitEntryFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No RemitEntry found',
            150830038
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到現金
     */
    public function testCreateButNoCashFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash found',
            150830048
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn(null);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(2))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到會員
     */
    public function testCreateButNoSuchUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150830042
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(null);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(2))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, null));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到會員層級
     */
    public function testCreateButNoUserLevelFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            150830043
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(3))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user, null));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，幣別不合法
     */
    public function testCreateButIllegalCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150830044
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(3))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user, $userLevel));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn(null);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $container->set('durian.currency', $chelper);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到層級幣別
     */
    public function testCreateButNoLevelCurrencyFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No LevelCurrency found',
            150830045
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(3))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount, null));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(3))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user, $userLevel));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $container->set('durian.currency', $chelper);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到收費相關設定
     */
    public function testCreateButNoPaymentChargeFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentCharge found',
            150830049
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);

        $levelCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\LevelCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $levelCurrency->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn(null);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(4))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount, $levelCurrency, null));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(3))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user, $userLevel));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $container->set('durian.currency', $chelper);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，找不到公司入款設定
     */
    public function testCreateButNoDepositCompanyFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No DepositCompany found',
            150830046
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);

        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentCharge->expects($this->any())
            ->method('getDepositCompany')
            ->willReturn(null);

        $levelCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\LevelCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $levelCurrency->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(3))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount, $levelCurrency));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(3))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user, $userLevel));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $container->set('durian.currency', $chelper);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，但超過三天不可再編輯狀態
     */
    public function testCreateButCanNotModifyStatusOfExpiredEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not modify status of expired entry',
            150830040
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->sub(new \DateInterval('P4D')));
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);

        $depositCompany = $this->getMockBuilder('BB\DurianBundle\Entity\DepositCompany')
            ->disableOriginalConstructor()
            ->getMock();
        $depositCompany->expects($this->any())
            ->method('toArray')
            ->willReturn([
                'discount' => '2',
                'discount_amount' => '0.5',
                'discount_percent' => '1',
                'discount_limit' => '10',
            ]);

        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentCharge->expects($this->any())
            ->method('getDepositCompany')
            ->willReturn($depositCompany);

        $levelCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\LevelCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $levelCurrency->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(3))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount, $levelCurrency));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(3))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user, $userLevel));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $container->set('durian.currency', $chelper);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料時，但無法編輯已確認公司訂單
     */
    public function testCreateButCanNotModifyConfirmedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'RemitEntry not unconfirm',
            150830039
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(1);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->any())
            ->method('find')
            ->willReturn($remitEntry);

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('TWD');
        $chelper->expects($this->any())
            ->method('isAvailable')
            ->willReturn(true);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.currency', $chelper);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料確認時，找不到現金
     */
    public function testCreateButNoCashFoundInConfirmRemitEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash found',
            150830041
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->sub(new \DateInterval('P1D')));
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->exactly(2))
            ->method('getCash')
            ->will($this->onConsecutiveCalls($cash, null));

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);

        $depositCompany = $this->getMockBuilder('BB\DurianBundle\Entity\DepositCompany')
            ->disableOriginalConstructor()
            ->getMock();
        $depositCompany->expects($this->any())
            ->method('toArray')
            ->willReturn([
                'discount' => '2',
                'discount_amount' => '0.5',
                'discount_percent' => '1',
                'discount_limit' => '10',
            ]);

        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentCharge->expects($this->any())
            ->method('getDepositCompany')
            ->willReturn($depositCompany);

        $levelCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\LevelCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $levelCurrency->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(3))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount, $levelCurrency));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(4))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user, $userLevel, $user));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->exactly(1))
            ->method('create')
            ->will($this->onConsecutiveCalls($remitLog));

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $container->set('durian.currency', $chelper);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試新增匯款資料確認時，找不到公司入款訂單
     */
    public function testCreateButNoRemitEntryFoundInAutoConfirmRemitEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No RemitEntry found',
            150830047
        );

        $data = [];
        $data1 = [
            'amount' => '10.00',
            'fee' => '0.00',
            'balance' => '35.00',
            'account' => '1234554321',
            'name' => '匯款人姓名',
            'time' => '2017-01-01T00:00:00+0800',
            'memo' => '2017052400193240',
            'message' => '山西分行轉',
            'method' => '電子匯入',
        ];
        array_push($data, $data1);

        $params = [
            'login_code' => 'xix',
            'data' => json_encode($data),
            'balance' => '35.00',
        ];

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $remitAccount->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $remitAccount->expects($this->any())
            ->method('getBankLimit')
            ->willReturn(0);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $remitEntry->expects($this->any())
            ->method('isAbandonDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getOtherDiscount')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->sub(new \DateInterval('P1D')));
        $remitEntry->expects($this->any())
            ->method('getConfirmAt')
            ->willReturn((new \DateTime())->sub(new \DateInterval('P1D')));
        $remitEntry->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->getMock();
        $cash->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getCash')
            ->willReturn($cash);

        $remitLog = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $remitLog->expects($this->any())
            ->method('addMessage');

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $userLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);

        $depositCompany = $this->getMockBuilder('BB\DurianBundle\Entity\DepositCompany')
            ->disableOriginalConstructor()
            ->getMock();
        $depositCompany->expects($this->any())
            ->method('toArray')
            ->willReturn([
                'discount' => '2',
                'discount_amount' => '0.5',
                'discount_percent' => '1',
                'discount_limit' => '10',
            ]);

        $paymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentCharge->expects($this->any())
            ->method('getDepositCompany')
            ->willReturn($depositCompany);

        $levelCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\LevelCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $levelCurrency->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($paymentCharge);

        $userStat = $this->getMockBuilder('BB\DurianBundle\Entity\UserStat')
            ->disableOriginalConstructor()
            ->getMock();
        $userStat->expects($this->any())
            ->method('getRemitCount')
            ->willReturn(1);
        $userStat->expects($this->any())
            ->method('getRemitTotal')
            ->willReturn(1);
        $userStat->expects($this->any())
            ->method('setRemitCount');
        $userStat->expects($this->any())
            ->method('setRemitTotal');
        $userStat->expects($this->any())
            ->method('getRemitMax')
            ->willReturn(3);
        $userStat->expects($this->any())
            ->method('setRemitMax');
        $userStat->expects($this->any())
            ->method('getFirstDepositAt')
            ->willReturn(new \DateTime());
        $userStat->expects($this->any())
            ->method('getModifiedAt')
            ->willReturn(new \DateTime());
        $userStat->expects($this->any())
            ->method('setModifiedAt');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'countEntriesBy', 'increaseCount', 'updateIncome', 'isBankLimitReached'])
            ->getMock();
        $entityRepo->expects($this->exactly(4))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainConfig, $remitAccount, $levelCurrency, null));
        $entityRepo->expects($this->any())
            ->method('countEntriesBy')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('increaseCount');
        $entityRepo->expects($this->any())
            ->method('updateIncome');
        $entityRepo->expects($this->any())
            ->method('isBankLimitReached');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->exactly(6))
            ->method('find')
            ->will($this->onConsecutiveCalls($remitEntry, $user, $userLevel, $user, $userStat, null));

        $acmk = $this->getMockBuilder('BB\DurianBundle\AutoConfirm\MatchMaker')
            ->disableOriginalConstructor()
            ->getMock();
        $acmk->expects($this->any())
            ->method('autoConfirmMatchRemitEntry')
            ->willReturn(1);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->any())
            ->method('create')
            ->willReturn($remitLog);

        $chelper = $this->getMockBuilder('BB\DurianBundle\Currency')
            ->disableOriginalConstructor()
            ->getMock();
        $chelper->expects($this->any())
            ->method('getMappedCode')
            ->willReturn('CNY');

        $operate = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->disableOriginalConstructor()
            ->getMock();
        $operate->expects($this->any())
            ->method('cashDirectOpByRedis')
            ->willReturn([
                'entry' => [
                    'id' => '1',
                    'user_id' => '1',
                    'balance' => 2,
                    'currency' => 'CNY',
                    'opcode' => 1037,
                    'memo' => 'memo',
                    'ref_id' => '1',
                    'tag' => '1',
                    'remit_account_id' => '1',
                    'amount' => 1.00,
                    'created_at'=> 'now'
                ],
            ]);
        $operate->expects($this->any())
            ->method('insertcashEntryByRedis');

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.auto_confirm_match_maker', $acmk);
        $container->set('durian.operation_logger', $operationLogger);
        $container->set('durian.currency', $chelper);
        $container->set('durian.op', $operate);
        $controller->setContainer($container);

        $controller->createAction($request, '1234567890987654321');
    }

    /**
     * 測試人工匹配訂單，沒有指定操作者
     */
    public function testManualMatchWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid operator specified',
            150830029
        );

        $params = [];

        $request = new Request($params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->manualMatchAction($request, 1);
    }

    /**
     * 測試人工匹配訂單，沒有訂單號
     */
    public function testManualMatchWithoutOrderNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order number specified',
            150830030
        );

        $params = ['operator' => 'crawler'];

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->manualMatchAction($request, 1);
    }

    /**
     * 測試人工匹配訂單，匯款明細已匹配
     */
    public function testManualMatchButAutoConfirmEntryWasMatched()
    {
        $this->setExpectedException(
            'RuntimeException',
            'AutoConfirmEntry was confirmed',
            150830031
        );

        $params = [
            'operator' => 'BB',
            'order_number' => '201701010000000000',
        ];

        $autoConfirmEntry = $this->getMockBuilder('BB\DurianBundle\Entity\AutoConfirmEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $autoConfirmEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(true);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoConfirmEntry);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualMatchAction($request, 1);
    }

    /**
     * 測試人工匹配訂單，找不到公司入款訂單
     */
    public function testManualMatchButNoRemitEntryFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No RemitEntry found',
            150830032
        );

        $params = [
            'operator' => 'crawler',
            'order_number' => '201701010000000000',
        ];

        $autoConfirmEntry = $this->getMockBuilder('BB\DurianBundle\Entity\AutoConfirmEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $autoConfirmEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(false);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoConfirmEntry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualMatchAction($request, 1);
    }

    /**
     * 測試人工匹配訂單，但公司入款訂單已確認
     */
    public function testManualMatchButRemitEntryWasConfirmed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'RemitEntry was confirmed',
            150830033
        );

        $params = [
            'operator' => 'crawler',
            'order_number' => '201701010000000000',
        ];

        $autoConfirmEntry = $this->getMockBuilder('BB\DurianBundle\Entity\AutoConfirmEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $autoConfirmEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(false);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(1);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($remitEntry);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoConfirmEntry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualMatchAction($request, 1);
    }

    /**
     * 測試人工匹配訂單，但公司入款訂單已取消
     */
    public function testManualMatchButRemitEntryWasCancelled()
    {
        $this->setExpectedException(
            'RuntimeException',
            'RemitEntry was cancelled',
            150830034
        );

        $params = [
            'operator' => 'crawler',
            'order_number' => '201701010000000000',
        ];

        $autoConfirmEntry = $this->getMockBuilder('BB\DurianBundle\Entity\AutoConfirmEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $autoConfirmEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(false);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(2);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($remitEntry);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoConfirmEntry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualMatchAction($request, 1);
    }

    /**
     * 測試人工匹配訂單，公司入款訂單非自動認款
     */
    public function testManualMatchButNotAutoConfirmOrder()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not Auto Confirm Order',
            150830035
        );

        $params = [
            'operator' => 'crawler',
            'order_number' => '201701010000000000',
        ];

        $autoConfirmEntry = $this->getMockBuilder('BB\DurianBundle\Entity\AutoConfirmEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $autoConfirmEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(false);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('isAutoConfirm')
            ->willReturn(false);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($remitEntry);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoConfirmEntry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualMatchAction($request, 1);
    }

    /**
     * 測試人工匹配訂單，公司入款訂單金額不符
     */
    public function testManualMatchButOrdorWasError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Order Amount error',
            150830037
        );

        $params = [
            'operator' => 'crawler',
            'order_number' => '201701010000000000',
        ];

        $autoConfirmEntry = $this->getMockBuilder('BB\DurianBundle\Entity\AutoConfirmEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $autoConfirmEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(false);
        $autoConfirmEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(0.01);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('isAutoConfirm')
            ->willReturn(true);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($remitEntry);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoConfirmEntry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualMatchAction($request, 1);
    }

    /**
     * 測試人工匹配訂單，公司入款收款卡號與匯款紀錄收款卡號不符
     */
    public function testManualMatchButRemitAccountWasError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Order RemitAccount error',
            150830050
        );

        $params = [
            'operator' => 'crawler',
            'order_number' => '201701010000000000',
        ];

        $autoConfirmEntry = $this->getMockBuilder('BB\DurianBundle\Entity\AutoConfirmEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $autoConfirmEntry->expects($this->any())
            ->method('isConfirm')
            ->willReturn(false);
        $autoConfirmEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $autoConfirmEntry->expects($this->any())
            ->method('getRemitAccountId')
            ->willReturn('1');

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getStatus')
            ->willReturn(0);
        $remitEntry->expects($this->any())
            ->method('isAutoConfirm')
            ->willReturn(true);
        $remitEntry->expects($this->any())
            ->method('getAmount')
            ->willReturn(1.00);
        $remitEntry->expects($this->any())
            ->method('getRemitAccountId')
            ->willReturn('2');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($remitEntry);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoConfirmEntry);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoConfirmController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->manualMatchAction($request, 1);
    }
}
