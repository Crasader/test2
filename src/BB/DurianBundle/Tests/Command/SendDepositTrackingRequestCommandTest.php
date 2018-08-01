<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\SendDepositTrackingRequestCommand;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\MerchantKey;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;

class SendDepositTrackingRequestCommandTest extends WebTestCase
{
    /**
     * log 檔案路徑
     *
     * @var string
     */
    private $logPath;

    /**
     * Container 的 mock
     *
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $mockContainer;

    /**
     * Payment Operator 的 mock
     *
     * @var \BB\DurianBundle\Payment\Operator
     */
    private $mockPaymentOperator;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData'
        ];
        $this->loadFixtures($classnames);

        $container = $this->getContainer();
        $validator = $this->getContainer()->get('durian.validator');
        $em = $container->get('doctrine.orm.entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $bgMonitor = $container->get('durian.monitor.background');
        $loggerManager = $container->get('durian.logger_manager');

        $this->mockPaymentOperator = $this->getMockBuilder('\BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentTrackingData'])
            ->getMock();

        $getMap = [
            ['durian.validator', 1, $validator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.payment_operator', 1, $this->mockPaymentOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];
        $getParameterMap = [
            ['kue_ip', '127.0.0.1'],
            ['kue_domain', 'www.kue.com']
        ];
        $this->mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();
        $this->mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $this->mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        // 設定 log 檔案路徑
        $env = $container->getParameter('kernel.environment');
        $envDir = $container->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . $env;
        $this->logPath = $envDir . DIRECTORY_SEPARATOR . 'send_deposit_tracking_request.log';
    }

    /**
     * 測試傳送訂單查詢的請求但未帶入起始時間參數
     */
    public function testExecuteWithoutStartTime()
    {
        $params = ['--end' => '2016-01-13 11:46:00'];
        $output = $this->runCommand('durian:send-deposit-tracking-request', $params);

        $error = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $error[2]);
        $this->assertContains('No start or end specified', $error[3]);
    }

    /**
     * 測試傳送訂單查詢的請求但未帶入結束時間參數
     */
    public function testExecuteWithoutEndTime()
    {
        $params = ['--start' => '2016-01-13 11:45:00'];
        $output = $this->runCommand('durian:send-deposit-tracking-request', $params);

        $error = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $error[2]);
        $this->assertContains('No start or end specified', $error[3]);
    }

    /**
     * 測試傳送訂單查詢的請求但無需做查詢的訂單
     */
    public function testExecuteButWithoutTrackingEntry()
    {
        $params = [
            '--start' => '20150610000000',
            '--end' => '20150610001500'
        ];
        $this->runCommand('durian:send-deposit-tracking-request', $params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查帶入參數的 log
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            '背景開始執行。參數: Start: 20150610000000, End: 20150610001500'
        );
        $this->assertContains($message, $results[0]);
    }

    /**
     * 測試傳送訂單查詢的請求，不會新增不支援訂單查詢的資料
     */
    public function testExecuteWithoutUnsupportAutoReopData()
    {
        // 此時間區間入款明細只會有一筆，id為201304280000000001
        $params = [
            '--start' => '20130428120000',
            '--end' => '20130428120500'
        ];
        $this->runCommand('durian:send-deposit-tracking-request', $params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查帶入參數的 log
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            '背景開始執行。參數: Start: 20130428120000, End: 20130428120500'
        );
        $this->assertContains($message, $results[0]);
    }

    /**
     * 測試傳送入款查詢請求的背景，但取得查詢需要的參數時發生Exception
     */
    public function testExecuteButExceptionOccurWhenGetPaymentTrackingData()
    {
        $this->updateCBPayDataFixtures();

        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $responseContent = '[{"message":"job created","id":319}]';
        $response = new Response();
        $response->setContent($responseContent);
        $response->addHeader('HTTP/1.1 200 OK');

        // 調整 mockPaymentOperator 的返回值
        $path = '/receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&' .
            'billNo_md5=092DA21739313A984899EA1558F68D7E';
        $entryParam = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => $path,
            'method' => 'GET',
            'headers' => [
                'Host' => 'payment.https.pay3.chinabank.com.cn'
            ]
        ];
        $this->mockPaymentOperator->expects($this->at(0))
            ->method('getPaymentTrackingData')
            ->willReturn($entryParam);

        $exception = new \RuntimeException('PaymentGateway does not support order tracking', 180074);
        $this->mockPaymentOperator->expects($this->at(1))
            ->method('getPaymentTrackingData')
            ->willThrowException($exception);

        $application = new Application();
        $command = new SendDepositTrackingRequestCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--start' => '2013-04-27 12:00:00',
            '--end' => '2013-05-28 12:01:00',
        ];
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查帶入參數的 log
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            '背景開始執行。參數: Start: 2013-04-27 12:00:00, End: 2013-05-28 12:01:00'
        );
        $this->assertContains($message, $results[0]);

        // 檢查取得參數錯誤 log
        $errorMsg = '取得查詢參數錯誤，Entry Id: 201305280000000001。' .
            'Error: 180074，Message: PaymentGateway does not support order tracking';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            $errorMsg
        );
        $this->assertContains($message, $results[1]);

        // 檢查 curl log
        $request = '[{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201304280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&billNo_md5=092DA21739' .
            '313A984899EA1558F68D7E"}}]';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $request,
            $responseContent
        );
        $this->assertContains($message, $results[2]);
    }

    /**
     * 測試傳送入款查詢請求的背景，但Kue連線異常
     */
    public function testExecuteButKueConnectionError()
    {
        $this->updateCBPayDataFixtures();

        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $exception = new \RuntimeException('Kue connection failure', 150180161);
        $client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        // 調整 mockPaymentOperator 的返回值
        $path = '/receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&' .
            'billNo_md5=092DA21739313A984899EA1558F68D7E';
        $entryParam = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => $path,
            'method' => 'GET',
            'headers' => [
                'Host' => 'payment.https.pay3.chinabank.com.cn'
            ]
        ];
        $this->mockPaymentOperator->expects($this->at(0))
            ->method('getPaymentTrackingData')
            ->willReturn($entryParam);

        $entryParam['path'] = '/receiveorder.jsp';
        $this->mockPaymentOperator->expects($this->at(1))
            ->method('getPaymentTrackingData')
            ->willReturn($entryParam);

        $application = new Application();
        $command = new SendDepositTrackingRequestCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--start' => '2013-04-27 12:00:00',
            '--end' => '2013-05-28 12:01:00',
        ];
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查帶入參數的 log
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            '背景開始執行。參數: Start: 2013-04-27 12:00:00, End: 2013-05-28 12:01:00'
        );
        $this->assertContains($message, $results[0]);

        // 檢查 curl log
        $request = '[{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201304280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&billNo_md5=092DA21739' .
            '313A984899EA1558F68D7E"}},{"type":"req.payment","data":{"method":"GET","headers":' .
            '{"Host":"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201305280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp"}}]';
        $errorMsg = '傳送查詢入款資料請求到Kue發生異常。Error: 150180161，Message: Kue connection failure';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $request,
            $errorMsg
        );
        $this->assertContains($message, $results[1]);

        // 檢查 italking
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_exception_queue';
        $this->assertEquals(1, $redis->llen($key));

        $queueMsg = json_decode($redis->rpop($key), true);
        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals('RuntimeException', $queueMsg['exception']);
        $this->assertContains($errorMsg, $queueMsg['message']);
    }

    /**
     * 測試傳送入款查詢請求的背景，但Kue連線失敗
     */
    public function testExecuteButKueConnectionFailure()
    {
        $this->updateCBPayDataFixtures();

        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $responseContent = '[{"error":"Must provide job type"},{"error":"Must provide job type"}]';
        $response = new Response();
        $response->setContent($responseContent);
        $response->addHeader('HTTP/1.1 499');

        // 調整 mockPaymentOperator 的返回值
        $path = '/receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&' .
            'billNo_md5=092DA21739313A984899EA1558F68D7E';
        $entryParam = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => $path,
            'method' => 'GET',
            'headers' => [
                'Host' => 'payment.https.pay3.chinabank.com.cn'
            ]
        ];
        $this->mockPaymentOperator->expects($this->at(0))
            ->method('getPaymentTrackingData')
            ->willReturn($entryParam);

        $entryParam['path'] = '/receiveorder.jsp';
        $this->mockPaymentOperator->expects($this->at(1))
            ->method('getPaymentTrackingData')
            ->willReturn($entryParam);

        $application = new Application();
        $command = new SendDepositTrackingRequestCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--start' => '2013-04-27 12:00:00',
            '--end' => '2013-05-28 12:01:00',
        ];
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查帶入參數的 log
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            '背景開始執行。參數: Start: 2013-04-27 12:00:00, End: 2013-05-28 12:01:00'
        );
        $this->assertContains($message, $results[0]);

        // 檢查 curl log
        $request = '[{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201304280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&billNo_md5=092DA21739' .
            '313A984899EA1558F68D7E"}},{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201305280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp"}}]';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $request,
            $responseContent
        );
        $this->assertContains($message, $results[1]);

        // 檢查例外 log
        $errorMsg = '傳送查詢入款資料請求到Kue發生異常。Error: 150180161，Message: Kue connection failure';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $request,
            $errorMsg
        );
        $this->assertContains($message, $results[2]);

        // 檢查 italking
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_exception_queue';
        $this->assertEquals(1, $redis->llen($key));

        $queueMsg = json_decode($redis->rpop($key), true);
        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals('RuntimeException', $queueMsg['exception']);
        $this->assertContains($errorMsg, $queueMsg['message']);
    }

    /**
     * 測試傳送入款查詢請求的背景，但Kue建立Job失敗
     */
    public function testExecuteButKueJobCreateFailure()
    {
        $this->updateCBPayDataFixtures();

        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $responseContent = '[{"error":"Must provide job type"},{"error":"Must provide job type"}]';
        $response = new Response();
        $response->setContent($responseContent);
        $response->addHeader('HTTP/1.1 200 OK');

        // 調整 mockPaymentOperator 的返回值
        $path = '/receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&' .
            'billNo_md5=092DA21739313A984899EA1558F68D7E';
        $entryParam = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => $path,
            'method' => 'GET',
            'headers' => [
                'Host' => 'payment.https.pay3.chinabank.com.cn'
            ]
        ];
        $this->mockPaymentOperator->expects($this->at(0))
            ->method('getPaymentTrackingData')
            ->willReturn($entryParam);

        $entryParam['path'] = '/receiveorder.jsp';
        $this->mockPaymentOperator->expects($this->at(1))
            ->method('getPaymentTrackingData')
            ->willReturn($entryParam);

        $application = new Application();
        $command = new SendDepositTrackingRequestCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--start' => '2013-04-27 12:00:00',
            '--end' => '2013-05-28 12:01:00',
        ];
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查帶入參數的 log
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            '背景開始執行。參數: Start: 2013-04-27 12:00:00, End: 2013-05-28 12:01:00'
        );
        $this->assertContains($message, $results[0]);

        // 檢查 curl log
        $request = '[{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201304280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&billNo_md5=092DA21739' .
            '313A984899EA1558F68D7E"}},{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201305280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp"}}]';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $request,
            $responseContent
        );
        $this->assertContains($message, $results[1]);

        // 檢查例外 log
        $errorMsg = '傳送查詢入款資料請求到Kue發生異常。Error: 150180162，Message: Kue Job create failure';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $request,
            $errorMsg
        );
        $this->assertContains($message, $results[2]);

        // 檢查 italking
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_exception_queue';
        $this->assertEquals(1, $redis->llen($key));

        $queueMsg = json_decode($redis->rpop($key), true);
        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals('RuntimeException', $queueMsg['exception']);
        $this->assertContains($errorMsg, $queueMsg['message']);
    }

    /**
     * 測試傳送入款查詢請求的背景
     */
    public function testExecute()
    {
        $container = $this->getContainer();
        $validator = $this->getContainer()->get('durian.validator');
        $em = $container->get('doctrine.orm.entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $bgMonitor = $container->get('durian.monitor.background');
        $loggerManager = $container->get('durian.logger_manager');
        $paymentOperator = $container->get('durian.payment_operator');

        // mock Container
        $getMap = [
            ['durian.validator', 1, $validator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.payment_operator', 1, $paymentOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];

        $paymentIpList = ['172.26.54.42', '172.26.54.41'];
        $getParameterMap = [
            ['kue_ip', '127.0.0.1'],
            ['kue_domain', 'www.kue.com'],
            ['rd5_payment_ip_list', $paymentIpList]
        ];
        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        $paymentOperator->setContainer($mockContainer);

        // 調整 CBPay 商家的相關資料
        $this->updateCBPayDataFixtures();

        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $responseContent = '[{"message":"job created","id":319},{"message":"job created","id":320}]';
        $response = new Response();
        $response->setContent($responseContent);
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SendDepositTrackingRequestCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--start' => '2013-04-27 12:00:00',
            '--end' => '2013-05-28 12:01:00',
        ];
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查帶入參數的 log
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            '背景開始執行。參數: Start: 2013-04-27 12:00:00, End: 2013-05-28 12:01:00'
        );
        $this->assertContains($message, $results[0]);

        $request = '[{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201304280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&billNo_md5=092DA2173' .
            '9313A984899EA1558F68D7E"}},{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201305280000000001","attempt":{"verify_ip":0,"count":1},"url":"http:\/\/172.26.54.42\/' .
            'receiveorder.jsp?v_oid=20130528-12345-201305280000000001&v_mid=12345&v_url=&billNo_md5=90268A6F7' .
            '6B0F13D401AED42664B8AE1"}}]';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $request,
            $responseContent
        );
        $this->assertContains($message, $results[1]);
    }

    /**
     * 調整 CBPay 商家的相關資料
     */
    private function updateCBPayDataFixtures()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, ' .
            'bind_ip, label, removed, withdraw, hot, order_id, upload_key, deposit, mobile, withdraw_url, ' .
            'withdraw_host, random_float) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $params = [
            6, // id
            'CBPay', // code
            '網銀在線', // name
            'http://cbpay.com', //post_url
            1, // auto_reop
            '', // reop_url
            'payment.https.pay3.chinabank.com.cn', // verify_url
            '', // verify_ip
            '', // bind_ip
            'CBPay', // label
            0, // removed
            0, // withdraw
            1, // hot
            1, // order_id
            0, // upload_key
            1, // deposit
            0, // mobile
            '', // withdraw_url
            '', // withdraw_host
            0, // random_float
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        $paymentGateway = $em->getRepository('BBDurianBundle:PaymentGateway')->find('6');

        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');
        $merchant->setPrivateKey('biwgh2iuh98763SS');
        $em->persist($merchant);

        $merchantPublicKey = new MerchantKey($merchant, 'public', 'testPublicKey');
        $em->persist($merchantPublicKey);
        $em->flush();

        // 調整訂單的商家
        $sql = "UPDATE cash_deposit_entry SET merchant_id = 8, merchant_number = '12345' " .
            'WHERE id IN (201304280000000001, 201305280000000001)';
        $em->getConnection()->executeUpdate($sql);
    }

    /**
     * 刪除相關log
     */
    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        parent::tearDown();
    }
}
