<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\DepositTrackingVerifyCommand;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\MerchantExtra;
use BB\DurianBundle\Exception\PaymentException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;

class DepositTrackingVerifyCommandTest extends WebTestCase
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

    /**
     * curl mock
     *
     * @var Buzz\Client\Curl
     */
    private $mockClient;

    /**
     * response mock
     *
     * @var Response
     */
    private $mockResponse;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositOnlineData'
        ];
        $this->loadFixtures($classnames);

        $this->loadFixtures([], 'share');

        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $bgMonitor = $container->get('durian.monitor.background');
        $loggerManager = $container->get('durian.logger_manager');
        $validator = $container->get('durian.validator');

        $mockMethods = [
            'processTrackingResponseEncoding',
            'depositExamineVerify',
            'depositConfirm',
            'getPaymentTrackingData'
        ];
        $this->mockPaymentOperator = $this->getMockBuilder('\BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods($mockMethods)
            ->getMock();

        $getMap = [
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.payment_operator', 1, $this->mockPaymentOperator],
            ['durian.logger_manager', 1, $loggerManager],
            ['durian.validator', 1, $validator],
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

        // mock 對外連線及返回
        $this->mockClient = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $this->mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getStatusCode'])
            ->getMock();
        $this->mockResponse->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        // 設定 log 檔案路徑
        $env = $container->getParameter('kernel.environment');
        $envDir = $container->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . $env;
        $this->logPath = $envDir . DIRECTORY_SEPARATOR . 'deposit_tracking_verify.log';

        $redis = $container->get('snc_redis.sequence');
        $redis->flushdb();
        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試輸出欲處理的 job 數量
     */
    public function testShowStats()
    {
        $this->mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn('{"count":314}');
        $this->mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn('{"count":644}');

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($this->mockClient);
        $command->setResponse($this->mockResponse);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--show-stats' => true,
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $outputStats = explode(PHP_EOL, $commandTester->getDisplay());
        $this->assertEquals(314, $outputStats[0]);
        $this->assertEquals(644, $outputStats[1]);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查取得 failed 數量的 log
        $failedMessage = '127.0.0.1 "GET /jobs/req.payment/failed/stats" "REQUEST: " "RESPONSE: {"count":314}"';
        $this->assertContains($failedMessage, $results[0]);

        $completeMessage = '127.0.0.1 "GET /jobs/req.payment/complete/stats" "REQUEST: " "RESPONSE: {"count":644}"';
        $this->assertContains($completeMessage, $results[1]);
    }

    /**
     * 測試輸出欲處理的 job 數量，但Kue連線異常
     */
    public function testShowStatsButKueConnectionError()
    {
        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $exception = new \RuntimeException('Kue connection failure', 150180161);
        $client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--show-stats' => true,
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $outputStats = explode(PHP_EOL, $commandTester->getDisplay());
        $this->assertEquals(999, $outputStats[0]);
        $this->assertEquals(999, $outputStats[1]);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $errorMsg = 'Kue取得欲處理的 job 數量時異常。Error: 150180161，Message: Kue connection failure';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            '',
            '',
            '',
            $errorMsg
        );
        $this->assertContains($message, $results[0]);

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
     * 測試輸出欲處理的 job 數量，但Kue連線失敗
     */
    public function testShowStatsButKueConnectionFailure()
    {
        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $responseContent = '{"error":"error"}';
        $response = new Response();
        $response->setContent($responseContent);
        $response->addHeader('HTTP/1.1 499');

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--show-stats' => true,
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $outputStats = explode(PHP_EOL, $commandTester->getDisplay());
        $this->assertEquals(999, $outputStats[0]);
        $this->assertEquals(999, $outputStats[1]);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/stats',
            '',
            $responseContent
        );
        $this->assertContains($failedMessage, $results[0]);

        $errorMsg = 'Kue取得欲處理的 job 數量時異常。Error: 150180161，Message: Kue connection failure';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            '',
            '',
            '',
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
     * 測試輸出欲處理的 job 數量，但Kue返回error
     */
    public function testShowStatsButKueReturnError()
    {
        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $responseContent = '{"error":"error"}';
        $response = new Response();
        $response->setContent($responseContent);
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--show-stats' => true,
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $outputStats = explode(PHP_EOL, $commandTester->getDisplay());
        $this->assertEquals(999, $outputStats[0]);
        $this->assertEquals(999, $outputStats[1]);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/stats',
            '',
            $responseContent
        );
        $this->assertContains($failedMessage, $results[0]);

        $errorMsg = 'Kue取得欲處理的 job 數量時異常。Error: 150180163，Message: Kue return error message';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            '',
            '',
            '',
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
     * 測試入款查詢解密驗證背景，但指定欲處理失敗的 job 範圍不合法
     */
    public function testExecuteWithIllegalFailedFrom()
    {
        $params = [
            '--failed-from' => 'failed',
            '--failed-to' => '999',
        ];
        $output = $this->runCommand('durian:deposit-tracking-verify', $params);

        $error = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $error[2]);
        $this->assertContains('Illegal failed-from or failed-to', $error[3]);
    }

    /**
     * 測試入款查詢解密驗證背景，但指定欲處理成功的 job 範圍不合法
     */
    public function testExecuteWithIllegalCompleteTo()
    {
        $params = ['--complete-from' => '0'];
        $output = $this->runCommand('durian:deposit-tracking-verify', $params);

        $error = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $error[2]);
        $this->assertContains('Illegal complete-from or complete-to', $error[3]);
    }

    /**
     * 測試入款查詢解密驗證背景，但Kue連線異常
     */
    public function testExecuteButKueConnectionError()
    {
        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $exception = new \RuntimeException('Kue connection failure', 150180161);
        $client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $errorMsg = 'Kue取得查詢解密驗證異常。Error: 150180161，Message: Kue connection failure';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            '',
            '',
            '',
            $errorMsg
        );
        $this->assertContains($message, $results[0]);

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
     * 測試入款查詢解密驗證背景，但Kue連線失敗
     */
    public function testExecuteButKueConnectionFailure()
    {
        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $responseContent = '{"error":"error"}';
        $response = new Response();
        $response->setContent($responseContent);
        $response->addHeader('HTTP/1.1 499');

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            $responseContent
        );
        $this->assertContains($failedMessage, $results[0]);

        $errorMsg = 'Kue取得查詢解密驗證異常。Error: 150180161，Message: Kue connection failure';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            '',
            '',
            '',
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
     * 測試入款查詢解密驗證背景，但Kue返回error
     */
    public function testExecuteButKueReturnError()
    {
        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $responseContent = '{"error":"error"}';
        $response = new Response();
        $response->setContent($responseContent);
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            $responseContent
        );
        $this->assertContains($failedMessage, $results[0]);

        $errorMsg = 'Kue取得查詢解密驗證異常。Error: 150180163，Message: Kue return error message';
        $message = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            '',
            '',
            '',
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
     * 測試入款查詢解密驗證背景但無需做查詢解密驗證的訂單
     */
    public function testExecuteButWithoutTrackingVerifyEntry()
    {
        // mock 對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->setContent('[]');
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
            '--complete-from' => '0',
            '--complete-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            '[]'
        );
        $this->assertContains($failedMessage, $results[0]);

        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/complete/0..999/asc',
            '',
            '[]'
        );
        $this->assertContains($completeMessage, $results[1]);
    }

    /**
     * 測試入款查詢解密驗證背景，retry 時取查詢需要的參數失敗
     */
    public function testExecuteButGetPaymentTrackingDataFailureWhenRetry()
    {
        $failedResponse = '[{"id":"322","type":"req.payment","data":{"url":"http:\/\/1.2.3.4","method":"POST",' .
            '"form":{"serialID":"2013042800000000010.53455400","signMsg":"2c61c371ea80ad66d4cceebbce746b63"},' .
            '"headers":{"Host":"payment.https.testtest.com"},' .
            '"title":"SendDepositTrackingRequestCommand","entryId":"201304280000000001",' .
            '"attempt":{"verify_ip":0,"count":1}},"priority":0,' .
            '"progress":0,"state":"failed","error":"Error: Error: getaddrinfo ENOTFOUND testtest.com ' .
            'testtest.com:80\n    at Socket.emit (events.js:169:7)","created_at":"1453368095114",' .
            '"promote_at":"1453368095114","updated_at":"1453368095347","failed_at":"1453368095347",' .
            '"started_at":"1453368095118","workerId":"kue:localhost.localdomain:10645:req.payment:2",' .
            '"attempts":{"made":1,"remaining":0,"max":1}}]';
        $this->mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($failedResponse);

        // failed delete 的返回
        $deleteResponse = '{"message":"job 322 removed"}';
        $this->mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($deleteResponse);

        $completeResponse = '[{"id":"352","type":"req.payment","data":{"url":"https:\/\/1.2.3.4\/website\/qu' .
            'eryOrderResult.htm","method":"POST","form":{"serialID":"2013042800000000010.53455400","orderID":' .
            '"201304280000000001","signMsg":"2c61c371ea80ad66d4cceebbce746b63"},' .
            '"headers":{"Host":"payment.https.www.hnapay.com"},"title":"SendDepositTrackingRequest' .
            'Command","entryId":"201304280000000001","attempt":{"verify_ip":0,"count":1},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-length":"198"},"body":"c2VyaWFsSUQ9MjAxMzA0MjgwMDAwMDAwMDAxMC41MzQ1NTQwMCZtb2RlPTEmdHlwZT0xJn' .
            'Jlc3VsdENvZGU9MDAwOSZxdWVyeURldGFpbHNTaXplPTAmcXVlcnlEZXRhaWxzPSZwYXJ0bmVySUQ9MTAwNTYxMzY1NzAmcmVtYXJr' .
            'PXJlbWFyayZjaGFyc2V0PTEmc2lnblR5cGU9MiZzaWduTXNnPWI3YzBiZTNlMjhhZGRhYTFhNWExZmM4NzFhNjVhOTBj"}}}]';
        $this->mockResponse->expects($this->at(4))
            ->method('getContent')
            ->willReturn($completeResponse);

        // complete delete 的返回
        $deleteResponse = '{"message":"job 352 removed"}';
        $this->mockResponse->expects($this->at(6))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // 取查詢需要的參數時發生例外
        $exception = new \RuntimeException('PaymentGateway does not support order tracking', 180074);
        $this->mockPaymentOperator->expects($this->any())
            ->method('getPaymentTrackingData')
            ->willThrowException($exception);

        // 訂單查詢成功後執行確認入款時發生例外
        $exception = new \RuntimeException('No Merchant found', 180006);
        $this->mockPaymentOperator->expects($this->any())
            ->method('depositConfirm')
            ->willThrowException($exception);

        // 設定 operator 轉換編碼後的返回
        $body = 'serialID=2013042800000000010.53455400&mode=1&type=1&resultCode=0009&queryDetailsSize=0&queryDeta' .
            'ils=&partnerID=10056136570&remark=remark&charset=1&signType=2&signMsg=b7c0be3e28addaa1a5a1fc871a65a90c';
        $jobResult = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $body
        ];
        $this->mockPaymentOperator->expects($this->any())
            ->method('processTrackingResponseEncoding')
            ->willReturn($jobResult);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($this->mockClient);
        $command->setResponse($this->mockResponse);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
            '--complete-from' => '0',
            '--complete-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查取得 failed 資料的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            $failedResponse
        );
        $this->assertContains($failedMessage, $results[0]);

        // 檢查 kue 查詢失敗的 log
        $errorMsg = 'Kue訂單查詢失敗，Entry Id: 201304280000000001。Error Message: ' .
            'Error: Error: getaddrinfo ENOTFOUND testtest.com testtest.com:80    at Socket.emit (events.js:169:7)';
        $failedMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.testtest.com',
            'POST',
            'http://1.2.3.4',
            '{"serialID":"2013042800000000010.53455400","signMsg":"2c61c371ea80ad66d4cceebbce746b63"}',
            $errorMsg
        );
        $this->assertContains($failedMessage, $results[1] . $results[2]);

        // 檢查 retry failed 的訂單時發生取得查詢參數錯誤的 log
        $errorMsg = '取得查詢參數錯誤，Entry Id: 201304280000000001。' .
            'Error: 180074，Message: PaymentGateway does not support order tracking';
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            $errorMsg
        );
        $this->assertContains($failedMessage, $results[3]);

        // 檢查 failed 删除 Job 的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/322',
            '',
            '{"message":"job 322 removed"}'
        );
        $this->assertContains($failedMessage, $results[4]);

        // 轉換 job body 內的編碼
        $completeResponse = json_decode($completeResponse, true);
        $completeResponse[0]['data']['job_result'] = $jobResult;

        // 檢查取得 complete 資料的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/complete/0..999/asc',
            '',
            json_encode($completeResponse)
        );
        $this->assertContains($completeMessage, $results[5]);

        // 檢查 confirm 時發生錯誤的 log
        $requestContent = '{"serialID":"2013042800000000010.53455400","orderID":"201304280000000001",' .
            '"signMsg":"2c61c371ea80ad66d4cceebbce746b63"}';
        $responseContent = '查詢解密驗證失敗，Entry Id: 201304280000000001，Error Message: No Merchant found。' .
            json_encode($completeResponse[0]['data']['job_result']);
        $completeMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.www.hnapay.com',
            'POST',
            'https://1.2.3.4/website/queryOrderResult.htm',
            $requestContent,
            $responseContent
        );
        $this->assertContains($completeMessage, $results[6]);

        // 檢查 retry complete 的訂單時發生取得查詢參數錯誤的 log
        $errorMsg = '取得查詢參數錯誤，Entry Id: 201304280000000001。' .
            'Error: 180074，Message: PaymentGateway does not support order tracking';
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '',
            '',
            '',
            '',
            $errorMsg
        );
        $this->assertContains($completeMessage, $results[7]);

        // 檢查 complete 删除 Job 的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/352',
            '',
            '{"message":"job 352 removed"}'
        );
        $this->assertContains($completeMessage, $results[8]);
    }

    /**
     * 測試入款查詢解密驗證背景，retry 成功
     */
    public function testExecuteRetrySuccess()
    {
        // 取得 failed 的兩筆返回
        $failedResponse = '[{"id":"322","type":"req.payment","data":{"url":"http:\/\/1.2.3.4","method":"POST",' .
            '"form":{"serialID":"2013052800000000010.53455400","signMsg":"2c61c371ea80ad66d4cceebbce746b63"},' .
            '"headers":{"Host":"payment.https.testtest.com"},' .
            '"title":"SendDepositTrackingRequestCommand","entryId":"201305280000000001",' .
            '"attempt":{"verify_ip":0,"count":2}},"priority":0,' .
            '"progress":0,"state":"failed","error":"Error: Error: getaddrinfo ENOTFOUND testtest.com ' .
            'testtest.com:80\n    at Socket.emit (events.js:169:7)","created_at":"1453368095114",' .
            '"promote_at":"1453368095114","updated_at":"1453368095347","failed_at":"1453368095347",' .
            '"started_at":"1453368095118","workerId":"kue:localhost.localdomain:10645:req.payment:2",' .
            '"attempts":{"made":1,"remaining":0,"max":1}},' .
            '{"id":"323","type":"req.payment","data":{"url":"http:\/\/1.2.3.4","method":"POST",' .
            '"form":{"serialID":"2013042800000000010.53455400","signMsg":"qw61c37asd12ad66d4ccsevvce7464e3"},' .
            '"headers":{"Host":"payment.https.testtest.com"},' .
            '"title":"SendDepositTrackingRequestCommand","entryId":"201304280000000001",' .
            '"attempt":{"verify_ip":0,"count":3}},"priority":0,' .
            '"progress":0,"state":"failed","error":"Error: Error: getaddrinfo ENOTFOUND testtest.com ' .
            'testtest.com:80\n    at Socket.emit (events.js:169:7)","created_at":"1453368095114",' .
            '"promote_at":"1453368095114","updated_at":"1453368095347","failed_at":"1453368095347",' .
            '"started_at":"1453368095118","workerId":"kue:localhost.localdomain:10645:req.payment:2",' .
            '"attempts":{"made":1,"remaining":0,"max":1}}]';
        $this->mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($failedResponse);

        // failed 第一筆 retry 的返回
        $retryResponse = '{"message":"job created","id": 366}';
        $this->mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($retryResponse);

        // failed 第一筆 delete 的返回
        $deleteResponse = '{"message":"job 322 removed"}';
        $this->mockResponse->expects($this->at(4))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // failed 第二筆 retry 的返回
        $retryResponse = '{"message":"job created","id": 367}';
        $this->mockResponse->expects($this->at(6))
            ->method('getContent')
            ->willReturn($retryResponse);

        // failed 第二筆 delete 的返回
        $deleteResponse = '{"message":"job 323 removed"}';
        $this->mockResponse->expects($this->at(8))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // 取得 complete 的兩筆返回
        $completeResponse = '[{"id":"352","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/website\/qu' .
            'eryOrderResult.htm","method":"POST","form":{"serialID":"2013042800000000010.53455400","orderID":' .
            '"201304280000000001","signMsg":"2c61c371ea80ad66d4cceebbce746b63"},' .
            '"headers":{"Host":"payment.https.www.hnapay.com"},"title":"SendDepositTrackingRequest' .
            'Command","entryId":"201304280000000001","attempt":{"verify_ip":1,"count":1},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-length":"198"},"body":"c2VyaWFsSUQ9MjAxMzA0MjgwMDAwMDAwMDAxMC41MzQ1NTQwMCZtb2RlPTEmdHlwZT0xJn' .
            'Jlc3VsdENvZGU9MDAwOSZxdWVyeURldGFpbHNTaXplPTAmcXVlcnlEZXRhaWxzPSZwYXJ0bmVySUQ9MTAwNTYxMzY1NzAmcmVtYXJr' .
            'PXJlbWFyayZjaGFyc2V0PTEmc2lnblR5cGU9MiZzaWduTXNnPWI3YzBiZTNlMjhhZGRhYTFhNWExZmM4NzFhNjVhOTBj"}}},'.
            '{"id":"353","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/website\/qu' .
            'eryOrderResult.htm","method":"POST","form":{"serialID":"2013052800000000010.53455400","orderID":' .
            '"201305280000000001","signMsg":"1w61qaa1ea80ad66d4cce7bbdcrfe363"},' .
            '"headers":{"Host":"payment.https.www.hnapay.com"},"title":"SendDepositTrackingRequest' .
            'Command","entryId":"201305280000000001","attempt":{"verify_ip":1,"count":2},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-length":"198"},"body":"c2VyaWFsSUQ9MjAxMzA1MjgwMDAwMDAwMDAxMC41MzQ1NTQwMCZtb2RlPTEmdHlwZT0xJn' .
            'Jlc3VsdENvZGU9MDAwOSZxdWVyeURldGFpbHNTaXplPTAmcXVlcnlEZXRhaWxzPSZwYXJ0bmVySUQ9MTAwNTYxMzY1NzAmcmVtYXJr' .
            'PXJlbWFyayZjaGFyc2V0PTEmc2lnblR5cGU9MiZzaWduTXNnPWI3YzBiZTNlMjhhZGRhYTFhNWExZmM4NzFhNjVhOTBj"}}}]';
        $this->mockResponse->expects($this->at(10))
            ->method('getContent')
            ->willReturn($completeResponse);

        // complete 第一筆 retry 的返回
        $retryResponse = '{"message":"job created","id": 368}';
        $this->mockResponse->expects($this->at(12))
            ->method('getContent')
            ->willReturn($retryResponse);

        // complete 第一筆 delete 的返回
        $deleteResponse = '{"message":"job 352 removed"}';
        $this->mockResponse->expects($this->at(14))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // complete 第二筆 retry 的返回
        $retryResponse = '{"message":"job created","id": 369}';
        $this->mockResponse->expects($this->at(16))
            ->method('getContent')
            ->willReturn($retryResponse);

        // complete 第二筆 delete 的返回
        $deleteResponse = '{"message":"job 353 removed"}';
        $this->mockResponse->expects($this->at(18))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // 設定 failed retry 取第一筆查詢需要的參數
        $trackingData = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => '/website/queryOrderResult.htm',
            'method' => 'POST',
            'form' => [
                'serialID' => '2013052800000000010.53455400',
                'orderID' => '201305280000000001',
                'signMsg' => '4a61c371ebf0adqwe2cceebbce746fg4'
            ],
            'headers' => [
                'Host' => 'payment.https.www.hnapay.com'
            ]
        ];
        $this->mockPaymentOperator->expects($this->at(0))
            ->method('getPaymentTrackingData')
            ->willReturn($trackingData);

        // 設定 failed retry 取第二筆查詢需要的參數
        $trackingData = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => '/website/queryOrderResult.htm',
            'method' => 'POST',
            'form' => [
                'serialID' => '2013042800000000010.53455400',
                'orderID' => '201304280000000001',
                'signMsg' => 'qw61c37asd12ad66d4ccsevvce7464e3'
            ],
            'headers' => [
                'Host' => 'payment.https.www.hnapay.com'
            ]
        ];
        $this->mockPaymentOperator->expects($this->at(1))
            ->method('getPaymentTrackingData')
            ->willReturn($trackingData);

        // 設定 complete retry 取第一筆查詢需要的參數
        $trackingData = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => '/website/queryOrderResult.htm',
            'method' => 'POST',
            'form' => [
                'serialID' => '2013042800000000010.53455400',
                'orderID' => '201304280000000001',
                'signMsg' => '2c61c371ea80ad66d4cceebbce746b63'
            ],
            'headers' => [
                'Host' => 'payment.https.www.hnapay.com'
            ]
        ];
        $this->mockPaymentOperator->expects($this->at(6))
            ->method('getPaymentTrackingData')
            ->willReturn($trackingData);

        // 設定 complete retry 取第二筆查詢369需要的參數
        $trackingData = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => '/website/queryOrderResult.htm',
            'method' => 'POST',
            'form' => [
                'serialID' => '2013052800000000010.53455400',
                'orderID' => '201305280000000001',
                'signMsg' => '1w61qaa1ea80ad66d4cce7bbdcrfe363'
            ],
            'headers' => [
                'Host' => 'payment.https.www.hnapay.com'
            ]
        ];
        $this->mockPaymentOperator->expects($this->at(9))
            ->method('getPaymentTrackingData')
            ->willReturn($trackingData);

        // 訂單查詢成功後執行確認入款時發生例外
        $exception = new \RuntimeException('No Merchant found', 180006);
        $this->mockPaymentOperator->expects($this->any())
            ->method('depositConfirm')
            ->willThrowException($exception);

        // 設定第一筆 operator 轉換編碼後的返回
        $body = 'serialID=2013042800000000010.53455400&mode=1&type=1&resultCode=0009&queryDetailsSize=0&queryDeta' .
            'ils=&partnerID=10056136570&remark=remark&charset=1&signType=2&signMsg=b7c0be3e28addaa1a5a1fc871a65a90c';
        $jobResult1 = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $body
        ];
        $this->mockPaymentOperator->expects($this->at(2))
            ->method('processTrackingResponseEncoding')
            ->willReturn($jobResult1);

        // 設定第二筆 operator 轉換編碼後的返回
        $body = 'serialID=2013052800000000010.53455400&mode=1&type=1&resultCode=0009&queryDetailsSize=0&queryDeta' .
            'ils=&partnerID=10056136570&remark=remark&charset=1&signType=2&signMsg=1w61qaa1ea80ad66d4cce7bbdcrfe363';
        $jobResult2 = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $body
        ];
        $this->mockPaymentOperator->expects($this->at(3))
            ->method('processTrackingResponseEncoding')
            ->willReturn($jobResult2);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($this->mockClient);
        $command->setResponse($this->mockResponse);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
            '--complete-from' => '0',
            '--complete-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查取得 failed 資料的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            $failedResponse
        );
        $this->assertContains($failedMessage, $results[0]);

        // 檢查 kue 第一筆查詢失敗的 log
        $errorMsg = 'Kue訂單查詢失敗，Entry Id: 201305280000000001。Error Message: ' .
            'Error: Error: getaddrinfo ENOTFOUND testtest.com testtest.com:80    at Socket.emit (events.js:169:7)';
        $failedMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.testtest.com',
            'POST',
            'http://1.2.3.4',
            '{"serialID":"2013052800000000010.53455400","signMsg":"2c61c371ea80ad66d4cceebbce746b63"}',
            $errorMsg
        );
        $this->assertContains($failedMessage, $results[1] . $results[2]);

        // 檢查 failed retry 第一筆的 log
        $requestContent = '{"type":"req.payment","data":{"method":"POST","form":{"serialID":' .
            '"2013052800000000010.53455400","orderID":"201305280000000001","signMsg":' .
            '"4a61c371ebf0adqwe2cceebbce746fg4"},"headers":{"Host":"payment.https.www.hnapay.com"},' .
            '"title":"DepositTrackingVerifyCommand","entryId":"201305280000000001",' .
            '"attempt":{"verify_ip":0,"count":3},"url":"http:\/\/172.26.54.42\/website\/queryOrderResult.htm"}}';
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $requestContent,
            '{"message":"job created","id":366}'
        );
        $this->assertContains($failedMessage, $results[3]);

        // 檢查 failed 删除第一筆 Job 的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/322',
            '',
            '{"message":"job 322 removed"}'
        );
        $this->assertContains($failedMessage, $results[4]);

        // 檢查 kue 第二筆查詢失敗的 log
        $errorMsg = 'Kue訂單查詢失敗，Entry Id: 201304280000000001。Error Message: ' .
            'Error: Error: getaddrinfo ENOTFOUND testtest.com testtest.com:80    at Socket.emit (events.js:169:7)';
        $failedMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.testtest.com',
            'POST',
            'http://1.2.3.4',
            '{"serialID":"2013042800000000010.53455400","signMsg":"qw61c37asd12ad66d4ccsevvce7464e3"}',
            $errorMsg
        );
        $this->assertContains($failedMessage, $results[5] . $results[6]);

        // 檢查 failed retry 第二筆的 log
        $requestContent = '{"type":"req.payment","data":{"method":"POST","form":{"serialID":' .
            '"2013042800000000010.53455400","orderID":"201304280000000001","signMsg":' .
            '"qw61c37asd12ad66d4ccsevvce7464e3"},"headers":{"Host":"payment.https.www.hnapay.com"},' .
            '"title":"DepositTrackingVerifyCommand","entryId":"201304280000000001",' .
            '"attempt":{"verify_ip":1,"count":1},"url":"http:\/\/172.26.54.41\/website\/queryOrderResult.htm"}}';
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $requestContent,
            '{"message":"job created","id":367}'
        );
        $this->assertContains($failedMessage, $results[7]);

        // 檢查 failed 删除第二筆 Job 的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/323',
            '',
            '{"message":"job 323 removed"}'
        );
        $this->assertContains($failedMessage, $results[8]);

        // 轉換 job body 內第二筆轉編碼成功的編碼
        $completeResponse = json_decode($completeResponse, true);
        $completeResponse[0]['data']['job_result'] = $jobResult1;
        $completeResponse[1]['data']['job_result'] = $jobResult2;

        // 檢查取得 complete 資料的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/complete/0..999/asc',
            '',
            json_encode($completeResponse)
        );
        $this->assertContains($completeMessage, $results[9]);

        // 檢查 complete 第一筆 confirm 時發生錯誤的 log
        $requestContent = '{"serialID":"2013042800000000010.53455400","orderID":"201304280000000001",' .
            '"signMsg":"2c61c371ea80ad66d4cceebbce746b63"}';
        $responseContent = '查詢解密驗證失敗，Entry Id: 201304280000000001，Error Message: No Merchant found。' .
            json_encode($completeResponse[0]['data']['job_result']);
        $completeMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.www.hnapay.com',
            'POST',
            'http://1.2.3.4/website/queryOrderResult.htm',
            $requestContent,
            $responseContent
        );
        $this->assertContains($completeMessage, $results[10]);

        // 檢查 complete retry 第一筆的 log
        $requestContent = '{"type":"req.payment","data":{"method":"POST","form":{"serialID":' .
            '"2013042800000000010.53455400","orderID":"201304280000000001","signMsg":' .
            '"2c61c371ea80ad66d4cceebbce746b63"},"headers":{"Host":"payment.https.www.hnapay.com"},' .
            '"title":"DepositTrackingVerifyCommand","entryId":"201304280000000001",' .
            '"attempt":{"verify_ip":1,"count":2},"url":"http:\/\/172.26.54.41\/website\/queryOrderResult.htm"}}';
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $requestContent,
            '{"message":"job created","id":368}'
        );
        $this->assertContains($completeMessage, $results[11]);

        // 檢查 complete 删除第一筆 Job 的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/352',
            '',
            '{"message":"job 352 removed"}'
        );
        $this->assertContains($completeMessage, $results[12]);

        // 檢查 complete 第二筆 confirm 時發生錯誤的 log
        $requestContent = '{"serialID":"2013052800000000010.53455400","orderID":"201305280000000001",' .
            '"signMsg":"1w61qaa1ea80ad66d4cce7bbdcrfe363"}';
        $responseContent = '查詢解密驗證失敗，Entry Id: 201305280000000001，Error Message: No Merchant found。' .
            json_encode($completeResponse[1]['data']['job_result']);
        $completeMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.www.hnapay.com',
            'POST',
            'http://1.2.3.4/website/queryOrderResult.htm',
            $requestContent,
            $responseContent
        );
        $this->assertContains($completeMessage, $results[13]);

        // 檢查 complete retry 第二筆的 log
        $requestContent = '{"type":"req.payment","data":{"method":"POST","form":{"serialID":' .
            '"2013052800000000010.53455400","orderID":"201305280000000001","signMsg":' .
            '"1w61qaa1ea80ad66d4cce7bbdcrfe363"},"headers":{"Host":"payment.https.www.hnapay.com"},' .
            '"title":"DepositTrackingVerifyCommand","entryId":"201305280000000001",' .
            '"attempt":{"verify_ip":1,"count":3},"url":"http:\/\/172.26.54.41\/website\/queryOrderResult.htm"}}';
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'POST',
            '/job',
            $requestContent,
            '{"message":"job created","id":369}'
        );
        $this->assertContains($completeMessage, $results[14]);

        // 檢查 complete 删除第二筆 Job 的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/353',
            '',
            '{"message":"job 353 removed"}'
        );
        $this->assertContains($completeMessage, $results[15]);
    }

    /**
     * 測試入款查詢解密驗證背景，但 retry 超過三次直接刪除 Job
     */
    public function testExecuteButRetryLargeThanThree()
    {
        // 取得 failed 的兩筆返回
        $failedResponse = '[{"id":"322","type":"req.payment","data":{"url":"http:\/\/1.2.3.4","method":"POST",' .
            '"json":{"serialID":"2013052800000000010.53455400","signMsg":"2c61c371ea80ad66d4cceebbce746b63"},' .
            '"headers":{"Host":"payment.https.testtest.com"},' .
            '"title":"SendDepositTrackingRequestCommand","entryId":"201305280000000001",' .
            '"attempt":{"verify_ip":1,"count":3}},"priority":0,' .
            '"progress":0,"state":"failed","error":"Error: Error: getaddrinfo ENOTFOUND testtest.com ' .
            'testtest.com:80\n    at Socket.emit (events.js:169:7)","created_at":"1453368095114",' .
            '"promote_at":"1453368095114","updated_at":"1453368095347","failed_at":"1453368095347",' .
            '"started_at":"1453368095118","workerId":"kue:localhost.localdomain:10645:req.payment:2",' .
            '"attempts":{"made":1,"remaining":0,"max":1}},' .
            '{"id":"323","type":"req.payment","data":{"url":"http:\/\/1.2.3.4","method":"POST",' .
            '"json":{"serialID":"2013042800000000010.53455400","signMsg":"qw61c37asd12ad66d4ccsevvce7464e3"},' .
            '"headers":{"Host":"payment.https.testtest.com"},' .
            '"title":"SendDepositTrackingRequestCommand","entryId":"201304280000000001",' .
            '"attempt":{"verify_ip":1,"count":3}},"priority":0,' .
            '"progress":0,"state":"failed","error":"Error: Error: getaddrinfo ENOTFOUND testtest.com ' .
            'testtest.com:80\n    at Socket.emit (events.js:169:7)","created_at":"1453368095114",' .
            '"promote_at":"1453368095114","updated_at":"1453368095347","failed_at":"1453368095347",' .
            '"started_at":"1453368095118","workerId":"kue:localhost.localdomain:10645:req.payment:2",' .
            '"attempts":{"made":1,"remaining":0,"max":1}}]';
        $this->mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($failedResponse);

        // failed 第一筆 delete 的返回
        $deleteResponse = '{"message":"job 322 removed"}';
        $this->mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // failed 第二筆 delete 的返回
        $deleteResponse = '{"message":"job 323 removed"}';
        $this->mockResponse->expects($this->at(4))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // 取得 complete 的兩筆返回
        $completeResponse = '[{"id":"352","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/website\/qu' .
            'eryOrderResult.htm","method":"POST","json":{"serialID":"2013042800000000010.53455400","orderID":' .
            '"201304280000000001","signMsg":"2c61c371ea80ad66d4cceebbce746b63"},' .
            '"headers":{"Host":"payment.https.www.hnapay.com"},"title":"SendDepositTrackingRequest' .
            'Command","entryId":"201304280000000001","attempt":{"verify_ip":1,"count":3},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-length":"198"},"body":"c2VyaWFsSUQ9MjAxMzA0MjgwMDAwMDAwMDAxMC41MzQ1NTQwMCZtb2RlPTEmdHlwZT0xJn' .
            'Jlc3VsdENvZGU9MDAwOSZxdWVyeURldGFpbHNTaXplPTAmcXVlcnlEZXRhaWxzPSZwYXJ0bmVySUQ9MTAwNTYxMzY1NzAmcmVtYXJr' .
            'PXJlbWFyayZjaGFyc2V0PTEmc2lnblR5cGU9MiZzaWduTXNnPWI3YzBiZTNlMjhhZGRhYTFhNWExZmM4NzFhNjVhOTBj"}}},'.
            '{"id":"353","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/website\/qu' .
            'eryOrderResult.htm","method":"POST","json":{"serialID":"2013052800000000010.53455400","orderID":' .
            '"201305280000000001","signMsg":"1w61qaa1ea80ad66d4cce7bbdcrfe363"},' .
            '"headers":{"Host":"payment.https.www.hnapay.com"},"title":"SendDepositTrackingRequest' .
            'Command","entryId":"201305280000000001","attempt":{"verify_ip":1,"count":3},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-length":"198"},"body":"c2VyaWFsSUQ9MjAxMzA1MjgwMDAwMDAwMDAxMC41MzQ1NTQwMCZtb2RlPTEmdHlwZT0xJn' .
            'Jlc3VsdENvZGU9MDAwOSZxdWVyeURldGFpbHNTaXplPTAmcXVlcnlEZXRhaWxzPSZwYXJ0bmVySUQ9MTAwNTYxMzY1NzAmcmVtYXJr' .
            'PXJlbWFyayZjaGFyc2V0PTEmc2lnblR5cGU9MiZzaWduTXNnPWI3YzBiZTNlMjhhZGRhYTFhNWExZmM4NzFhNjVhOTBj"}}}]';
        $this->mockResponse->expects($this->at(6))
            ->method('getContent')
            ->willReturn($completeResponse);

        // complete 第一筆 delete 的返回
        $deleteResponse = '{"message":"job 352 removed"}';
        $this->mockResponse->expects($this->at(8))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // complete 第二筆 delete 的返回
        $deleteResponse = '{"message":"job 353 removed"}';
        $this->mockResponse->expects($this->at(10))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // 訂單查詢成功後執行確認入款時發生例外
        $exception = new \RuntimeException('No Merchant found', 180006);
        $this->mockPaymentOperator->expects($this->any())
            ->method('depositConfirm')
            ->willThrowException($exception);

        // 設定第一筆 operator 轉換編碼後的返回
        $body = 'serialID=2013042800000000010.53455400&mode=1&type=1&resultCode=0009&queryDetailsSize=0&queryDeta' .
            'ils=&partnerID=10056136570&remark=remark&charset=1&signType=2&signMsg=b7c0be3e28addaa1a5a1fc871a65a90c';
        $jobResult1 = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $body
        ];
        $this->mockPaymentOperator->expects($this->at(2))
            ->method('processTrackingResponseEncoding')
            ->willReturn($jobResult1);

        // 設定第二筆 operator 轉換編碼後的返回
        $body = 'serialID=2013052800000000010.53455400&mode=1&type=1&resultCode=0009&queryDetailsSize=0&queryDeta' .
            'ils=&partnerID=10056136570&remark=remark&charset=1&signType=2&signMsg=1w61qaa1ea80ad66d4cce7bbdcrfe363';
        $jobResult2 = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $body
        ];
        $this->mockPaymentOperator->expects($this->at(3))
            ->method('processTrackingResponseEncoding')
            ->willReturn($jobResult2);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($this->mockClient);
        $command->setResponse($this->mockResponse);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
            '--complete-from' => '0',
            '--complete-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查取得 failed 資料的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            $failedResponse
        );
        $this->assertContains($failedMessage, $results[0]);

        // 檢查 kue 第一筆查詢失敗的 log
        $errorMsg = 'Kue訂單查詢失敗，Entry Id: 201305280000000001。Error Message: ' .
            'Error: Error: getaddrinfo ENOTFOUND testtest.com testtest.com:80    at Socket.emit (events.js:169:7)';
        $failedMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.testtest.com',
            'POST',
            'http://1.2.3.4',
            '{"serialID":"2013052800000000010.53455400","signMsg":"2c61c371ea80ad66d4cceebbce746b63"}',
            $errorMsg
        );
        $this->assertContains($failedMessage, $results[1] . $results[2]);

        // 檢查 failed 删除第一筆 Job 的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/322',
            '',
            '{"message":"job 322 removed"}'
        );
        $this->assertContains($failedMessage, $results[3]);

        // 檢查 kue 第二筆查詢失敗的 log
        $errorMsg = 'Kue訂單查詢失敗，Entry Id: 201304280000000001。Error Message: ' .
            'Error: Error: getaddrinfo ENOTFOUND testtest.com testtest.com:80    at Socket.emit (events.js:169:7)';
        $failedMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.testtest.com',
            'POST',
            'http://1.2.3.4',
            '{"serialID":"2013042800000000010.53455400","signMsg":"qw61c37asd12ad66d4ccsevvce7464e3"}',
            $errorMsg
        );
        $this->assertContains($failedMessage, $results[4] . $results[5]);

        // 檢查 failed 删除第二筆 Job 的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/323',
            '',
            '{"message":"job 323 removed"}'
        );
        $this->assertContains($failedMessage, $results[6]);

        // 轉換 job body 內的編碼
        $completeResponse = json_decode($completeResponse, true);
        $completeResponse[0]['data']['job_result'] = $jobResult1;
        $completeResponse[1]['data']['job_result'] = $jobResult2;

        // 檢查取得 complete 資料的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/complete/0..999/asc',
            '',
            json_encode($completeResponse)
        );
        $this->assertContains($completeMessage, $results[7]);

        // 檢查 complete 第一筆 confirm 時發生錯誤的 log
        $requestContent = '{"serialID":"2013042800000000010.53455400","orderID":"201304280000000001",' .
            '"signMsg":"2c61c371ea80ad66d4cceebbce746b63"}';
        $responseContent = '查詢解密驗證失敗，Entry Id: 201304280000000001，Error Message: No Merchant found。' .
            json_encode($completeResponse[0]['data']['job_result']);
        $completeMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.www.hnapay.com',
            'POST',
            'http://1.2.3.4/website/queryOrderResult.htm',
            $requestContent,
            $responseContent
        );
        $this->assertContains($completeMessage, $results[8]);

        // 檢查 complete 删除第一筆 Job 的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/352',
            '',
            '{"message":"job 352 removed"}'
        );
        $this->assertContains($completeMessage, $results[9]);

        // 檢查 complete 第二筆 confirm 時發生錯誤的 log
        $requestContent = '{"serialID":"2013052800000000010.53455400","orderID":"201305280000000001",' .
            '"signMsg":"1w61qaa1ea80ad66d4cce7bbdcrfe363"}';
        $responseContent = '查詢解密驗證失敗，Entry Id: 201305280000000001，Error Message: No Merchant found。' .
            json_encode($completeResponse[1]['data']['job_result']);
        $completeMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.www.hnapay.com',
            'POST',
            'http://1.2.3.4/website/queryOrderResult.htm',
            $requestContent,
            $responseContent
        );
        $this->assertContains($completeMessage, $results[10]);

        // 檢查 complete 删除第二筆 Job 的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/353',
            '',
            '{"message":"job 353 removed"}'
        );
        $this->assertContains($completeMessage, $results[11]);
    }

    /**
     * 測試入款查詢解密驗證背景，但刪除確認入款的 Job 時 Kue 返回 error
     */
    public function testExecuteButKueReturnErrorWhenDeleteConfirmedJob()
    {
        // 取得 failed 的返回
        $this->mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn('[]');

        // 取得 complete 的一筆返回
        $completeResponse = '[{"id":"352","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/website\/qu' .
            'eryOrderResult.htm","method":"POST","json":{"serialID":"2013042800000000010.53455400","orderID":' .
            '"201304280000000001","signMsg":"2c61c371ea80ad66d4cceebbce746b63"},' .
            '"headers":{"Host":"payment.https.www.hnapay.com"},"title":"SendDepositTrackingRequest' .
            'Command","entryId":"201304280000000001","attempt":{"verify_ip":0,"count":3},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-length":"198"},"body":"c2VyaWFsSUQ9MjAxMzA0MjgwMDAwMDAwMDAxMC41MzQ1NTQwMCZtb2RlPTEmdHlwZT0xJn' .
            'Jlc3VsdENvZGU9MDAwOSZxdWVyeURldGFpbHNTaXplPTAmcXVlcnlEZXRhaWxzPSZwYXJ0bmVySUQ9MTAwNTYxMzY1NzAmcmVtYXJr' .
            'PXJlbWFyayZjaGFyc2V0PTEmc2lnblR5cGU9MiZzaWduTXNnPWI3YzBiZTNlMjhhZGRhYTFhNWExZmM4NzFhNjVhOTBj"}}}]';
        $this->mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($completeResponse);

        // complete delete kue 返回 error
        $this->mockResponse->expects($this->at(4))
            ->method('getContent')
            ->willReturn('{"error":"error"}');

        // 設定第一筆 operator 轉換編碼後的返回
        $body = 'serialID=2013042800000000010.53455400&mode=1&type=1&resultCode=0009&queryDetailsSize=0&queryDeta' .
            'ils=&partnerID=10056136570&remark=remark&charset=1&signType=2&signMsg=b7c0be3e28addaa1a5a1fc871a65a90c';
        $jobResult = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $body
        ];
        $this->mockPaymentOperator->expects($this->at(0))
            ->method('processTrackingResponseEncoding')
            ->willReturn($jobResult);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($this->mockClient);
        $command->setResponse($this->mockResponse);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
            '--complete-from' => '0',
            '--complete-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查取得 failed 資料的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            '[]'
        );
        $this->assertContains($failedMessage, $results[0]);

        // 轉換 job body 內的編碼
        $completeResponse = json_decode($completeResponse, true);
        $completeResponse[0]['data']['job_result'] = $jobResult;

        // 檢查取得 complete 資料的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/complete/0..999/asc',
            '',
            json_encode($completeResponse)
        );
        $this->assertContains($completeMessage, $results[1]);

        // 檢查 complete 訂單解密驗證成功的 log
        $requestContent = '{"serialID":"2013042800000000010.53455400","orderID":"201304280000000001",' .
            '"signMsg":"2c61c371ea80ad66d4cceebbce746b63"}';
        $completeMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.www.hnapay.com',
            'POST',
            'http://1.2.3.4/website/queryOrderResult.htm',
            $requestContent,
            '解密驗證成功，Entry Id: 201304280000000001。' . json_encode($completeResponse[0]['data']['job_result'])
        );
        $this->assertContains($completeMessage, $results[2]);

        // 檢查 complete 删除 Job 的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/352',
            '',
            '{"error":"error"}'
        );
        $this->assertContains($completeMessage, $results[3]);

        // 檢查 complete 删除 Job 時發生錯誤的 log
        $errorMsg = 'Kue取得查詢解密驗證異常。Error: 150180163，Message: Kue return error message';
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            '',
            '',
            '',
            $errorMsg
        );
        $this->assertContains($completeMessage, $results[4]);

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
     * 測試入款查詢解密驗證背景，但訂單已確認入款直接刪除 Job
     */
    public function testExecuteButDepositEntryConfirmed()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將一張訂單確認入款
        $depositEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201304280000000001]);
        $depositEntry->confirm();
        $em->flush();

        // 取得 failed 的返回
        $this->mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn('[]');

        // 取得 complete 的兩筆返回
        $completeResponse = '[{"id":"352","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/website\/qu' .
            'eryOrderResult.htm","method":"POST","json":{"serialID":"2013042800000000010.53455400","orderID":' .
            '"201304280000000001","signMsg":"2c61c371ea80ad66d4cceebbce746b63"},' .
            '"headers":{"Host":"payment.https.www.hnapay.com"},"title":"SendDepositTrackingRequest' .
            'Command","entryId":"201304280000000001","attempt":{"verify_ip":0,"count":3},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-length":"198"},"body":"c2VyaWFsSUQ9MjAxMzA0MjgwMDAwMDAwMDAxMC41MzQ1NTQwMCZtb2RlPTEmdHlwZT0xJn' .
            'Jlc3VsdENvZGU9MDAwOSZxdWVyeURldGFpbHNTaXplPTAmcXVlcnlEZXRhaWxzPSZwYXJ0bmVySUQ9MTAwNTYxMzY1NzAmcmVtYXJr' .
            'PXJlbWFyayZjaGFyc2V0PTEmc2lnblR5cGU9MiZzaWduTXNnPWI3YzBiZTNlMjhhZGRhYTFhNWExZmM4NzFhNjVhOTBj"}}},'.
            '{"id":"353","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/website\/qu' .
            'eryOrderResult.htm","method":"POST","json":{"serialID":"2013052800000000010.53455400","orderID":' .
            '"201305280000000001","signMsg":"1w61qaa1ea80ad66d4cce7bbdcrfe363"},' .
            '"headers":{"Host":"payment.https.www.hnapay.com"},"title":"SendDepositTrackingRequest' .
            'Command","entryId":"201305280000000001","attempt":{"verify_ip":1,"count":3},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-length":"198"},"body":"c2VyaWFsSUQ9MjAxMzA1MjgwMDAwMDAwMDAxMC41MzQ1NTQwMCZtb2RlPTEmdHlwZT0xJn' .
            'Jlc3VsdENvZGU9MDAwOSZxdWVyeURldGFpbHNTaXplPTAmcXVlcnlEZXRhaWxzPSZwYXJ0bmVySUQ9MTAwNTYxMzY1NzAmcmVtYXJr' .
            'PXJlbWFyayZjaGFyc2V0PTEmc2lnblR5cGU9MiZzaWduTXNnPWI3YzBiZTNlMjhhZGRhYTFhNWExZmM4NzFhNjVhOTBj"}}}]';
        $this->mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($completeResponse);

        // complete 第一筆 delete 的返回
        $deleteResponse = '{"message":"job 352 removed"}';
        $this->mockResponse->expects($this->at(4))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // complete 第二筆 delete 的返回
        $deleteResponse = '{"message":"job 353 removed"}';
        $this->mockResponse->expects($this->at(6))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // 設定 complete retry 取第二筆查詢需要的參數
        $trackingData = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'path' => '/website/queryOrderResult.htm',
            'method' => 'POST',
            'json' => [
                'serialID' => '2013052800000000010.53455400',
                'orderID' => '201305280000000001',
                'signMsg' => '1w61qaa1ea80ad66d4cce7bbdcrfe363'
            ],
            'headers' => [
                'Host' => 'payment.https.www.hnapay.com'
            ]
        ];
        $this->mockPaymentOperator->expects($this->any())
            ->method('getPaymentTrackingData')
            ->willReturn($trackingData);

        // 訂單查詢解密驗證時發生例外
        $exception = new PaymentException('Order Amount error', 180058);
        $this->mockPaymentOperator->expects($this->any())
            ->method('depositExamineVerify')
            ->willThrowException($exception);

        // 設定第一筆 operator 轉換編碼後的返回
        $body = 'serialID=2013042800000000010.53455400&mode=1&type=1&resultCode=0009&queryDetailsSize=0&queryDeta' .
            'ils=&partnerID=10056136570&remark=remark&charset=1&signType=2&signMsg=b7c0be3e28addaa1a5a1fc871a65a90c';
        $jobResult1 = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $body
        ];
        $this->mockPaymentOperator->expects($this->at(0))
            ->method('processTrackingResponseEncoding')
            ->willReturn($jobResult1);

        // 設定第二筆 operator 轉換編碼後的返回
        $body = 'serialID=2013052800000000010.53455400&mode=1&type=1&resultCode=0009&queryDetailsSize=0&queryDeta' .
            'ils=&partnerID=10056136570&remark=remark&charset=1&signType=2&signMsg=1w61qaa1ea80ad66d4cce7bbdcrfe363';
        $jobResult2 = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $body
        ];
        $this->mockPaymentOperator->expects($this->at(1))
            ->method('processTrackingResponseEncoding')
            ->willReturn($jobResult2);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($this->mockContainer);
        $command->setClient($this->mockClient);
        $command->setResponse($this->mockResponse);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
            '--complete-from' => '0',
            '--complete-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查取得 failed 資料的 log
        $failedMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            '[]'
        );
        $this->assertContains($failedMessage, $results[0]);

        // 轉換 job body 內的編碼
        $completeResponse = json_decode($completeResponse, true);
        $completeResponse[0]['data']['job_result'] = $jobResult1;
        $completeResponse[1]['data']['job_result'] = $jobResult2;

        // 檢查取得 complete 資料的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'GET',
            '/jobs/req.payment/complete/0..999/asc',
            '',
            json_encode($completeResponse)
        );
        $this->assertContains($completeMessage, $results[1]);

        // 檢查 complete 删除訂單已確認入款的 Job 的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/352',
            '',
            '{"message":"job 352 removed"}'
        );
        $this->assertContains($completeMessage, $results[2]);

        // 檢查 complete 第二筆解密驗證時發生錯誤的 log
        $requestContent = '{"serialID":"2013052800000000010.53455400","orderID":"201305280000000001",' .
            '"signMsg":"1w61qaa1ea80ad66d4cce7bbdcrfe363"}';
        $responseContent = '查詢解密驗證失敗，Entry Id: 201305280000000001，Error Message: Order Amount error。' .
            json_encode($completeResponse[1]['data']['job_result']);
        $completeMessage = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'payment.https.www.hnapay.com',
            'POST',
            'http://1.2.3.4/website/queryOrderResult.htm',
            $requestContent,
            $responseContent
        );
        $this->assertContains($completeMessage, $results[3]);

        // 檢查 complete 删除第二筆 Job 的 log
        $completeMessage = sprintf(
            '%s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            '127.0.0.1',
            'DELETE',
            '/job/353',
            '',
            '{"message":"job 353 removed"}'
        );
        $this->assertContains($completeMessage, $results[4]);
    }

    /**
     * 測試入款查詢解密驗證背景，訂單查詢解密驗證成功
     */
    public function testExecuteDepositExamineVerifySuccess()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        // 新增 CBPay 的相關資料
        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('CBPay');
        $paymentGateway->setVerifyUrl('payment.https.pay3.chinabank.com.cn');
        $paymentGateway->setVerifyIp('1.2.3.4');
        $em->persist($paymentGateway);

        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');
        $merchant->setPrivateKey('biwgh2iuh98763SS');
        $em->persist($merchant);

        $em->flush();

        // 調整訂單的商家
        $sql = "UPDATE cash_deposit_entry SET merchant_id = 8, merchant_number = '12345' " .
            'WHERE id IN (201304280000000001, 201305280000000001)';
        $em->getConnection()->executeUpdate($sql);

        // 檢查訂單狀態
        $verifyFailedEntry = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $this->assertFalse($verifyFailedEntry->isConfirm());

        $verifySuccessEntry = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $this->assertFalse($verifySuccessEntry->isConfirm());

        // 取得 failed 的返回
        $this->mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn('[]');

        // 將支付平台的返回做編碼
        $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <body>
            <form name="PAResForm" action="" method="post">
            <input type=hidden name="v_oid" value="20130428-12345-201304280000000001">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="100.00" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="FA649F788FD7C16212B52BAB39389C4C">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>
            </form>
            </body>
            </html>';
        $encodedBody = base64_encode(iconv('UTF-8', 'GB2312', $body));

        // 取得 complete 的兩筆返回
        $completeResponse = '[{"id":"352","type":"req.payment","data":{"url":"http://1.2.3.4/' .
            'receiveorder.jsp?v_oid=20130428-12345-201304280000000001&v_mid=12345&v_url=&billNo_md5=' .
            '092DA21739313A984899EA1558F68D7E","method":"GET",' .
            '"headers":{"Host":"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201304280000000001","attempt":{"verify_ip":0,"count":3},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-type":"text/html; charset=GB2312"},"body":"' . $encodedBody . '"}}},' .
            '{"id":"353","type":"req.payment","data":{"url":"http://1.2.3.4/' .
            'receiveorder.jsp?v_oid=20130528-12345-201305280000000001&v_mid=12345&v_url=&billNo_md5=' .
            '092DA21739313A984899EA1558F68D7E","method":"GET",' .
            '"headers":{"Host":"payment.https.pay3.chinabank.com.cn"},"title":"SendDepositTrackingRequestCommand",' .
            '"entryId":"201305280000000001","attempt":{"verify_ip":0,"count":2},' .
            '"job_result":{"header":{"server":"nginx",' .
            '"content-type":"text/html; charset=GB2312"},"body":"' . $encodedBody . '"}}}]';
        $this->mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($completeResponse);

        // complete 第一筆 delete 的返回
        $deleteResponse = '{"message":"job 352 removed"}';
        $this->mockResponse->expects($this->at(4))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // complete 第二筆 retry 的返回
        $retryResponse = '{"message":"job created","id": 369}';
        $this->mockResponse->expects($this->at(6))
            ->method('getContent')
            ->willReturn($retryResponse);

        // complete 第二筆 delete 返回 job 已不存在
        $deleteResponse = '{"error":"job \"353\" doesnt exist"}';
        $this->mockResponse->expects($this->at(8))
            ->method('getContent')
            ->willReturn($deleteResponse);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($container);
        $command->setClient($this->mockClient);
        $command->setResponse($this->mockResponse);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
            '--complete-from' => '0',
            '--complete-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查取得 failed 資料的 log
        $failedMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            '[]'
        );
        $this->assertContains($failedMessage, $results[0]);

        // 轉換 job body 內的編碼
        $completeResponse = json_decode($completeResponse, true);
        $completeResponse[0]['data']['job_result']['body'] = $body;
        $completeResponse[1]['data']['job_result']['body'] = $body;

        // 檢查取得 complete 資料的 log
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'GET',
            '/jobs/req.payment/complete/0..999/asc',
            '',
            json_encode($completeResponse)
        );
        $this->assertContains($completeMessage, $results[1]);

        // 檢查 complete 第一筆訂單解密驗證成功的 log
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $completeResponse[0]['data']['method'],
            $completeResponse[0]['data']['url'],
            '[]',
            '解密驗證成功，Entry Id: 201304280000000001。' . json_encode($completeResponse[0]['data']['job_result'])
        );
        $this->assertContains($completeMessage, $results[2]);

        // 檢查 complete 删除第一筆訂單已確認入款的 Job 的 log
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'DELETE',
            '/job/352',
            '',
            '{"message":"job 352 removed"}'
        );
        $this->assertContains($completeMessage, $results[3]);

        // 檢查 complete 第二筆解密驗證時發生錯誤的 log
        $responseContent = '查詢解密驗證失敗，Entry Id: 201305280000000001，Error Message: Order Amount error。' .
            json_encode($completeResponse[1]['data']['job_result']);
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $completeResponse[1]['data']['method'],
            $completeResponse[1]['data']['url'],
            '[]',
            $responseContent
        );
        $this->assertContains($completeMessage, $results[4]);

        // 檢查 complete retry 第二筆的 log
        $requestContent = '{"type":"req.payment","data":{"method":"GET","headers":{"Host":' .
            '"payment.https.pay3.chinabank.com.cn"},"title":"DepositTrackingVerifyCommand",' .
            '"entryId":"201305280000000001","attempt":{"verify_ip":0,"count":3},"url":"http:\/\/1.2.3.4\/' .
            'receiveorder.jsp?v_oid=20130528-12345-201305280000000001&v_mid=12345&v_url=&billNo_md5=90268' .
            'A6F76B0F13D401AED42664B8AE1"}}';
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'POST',
            '/job',
            $requestContent,
            '{"message":"job created","id":369}'
        );
        $this->assertContains($completeMessage, $results[5]);

        // 檢查 complete 删除第二筆解密驗證錯誤的 Job 的 log
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'DELETE',
            '/job/353',
            '',
            '{"error":"job \"353\" doesnt exist"}'
        );
        $this->assertContains($completeMessage, $results[6]);

        // 檢查訂單狀態
        $em->refresh($verifyFailedEntry);
        $this->assertFalse($verifyFailedEntry->isConfirm());

        $em->refresh($verifySuccessEntry);
        $this->assertTrue($verifySuccessEntry->isConfirm());
    }

    /**
     * 測試入款查詢解密驗證背景，SOAP 的訂單查詢解密驗證成功
     */
    public function testExecuteSoapDepositExamineVerifySuccess()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        // 新增 IPS7 的相關資料
        $paymentGateway = new PaymentGateway('IPS7', '環迅7.0', 'http://ips7.com', 1);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('IPS7');
        $paymentGateway->setVerifyUrl('payment.http.www.ips7.com');
        $paymentGateway->setVerifyIp('1.2.3.4');
        $em->persist($paymentGateway);

        $merchant = new Merchant($paymentGateway, 1, 'IPS7Test', '12345', '6', '156');
        $merchant->setPrivateKey('test');
        $em->persist($merchant);
        $em->flush();

        $merchantExtra = new MerchantExtra($merchant, 'Account', '123456');
        $em->persist($merchantExtra);
        $em->flush();

        // 調整訂單的商家
        $sql = "UPDATE cash_deposit_entry SET merchant_id = 8, merchant_number = '12345' " .
            'WHERE id IN (201304280000000001, 201305280000000001)';
        $em->getConnection()->executeUpdate($sql);

        // 檢查訂單狀態
        $failedEntry = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $this->assertFalse($failedEntry->isConfirm());

        $completeEntry = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $this->assertFalse($completeEntry->isConfirm());

        // 取得 failed 的一筆返回
        $failedResponse = '[{"id":"322","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/psfp-entry\/services' .
            '\/order?wsdl","function":"getOrderByMerBillNo","arguments":{"orderQuery":"<?xml version=\"1.0\" encodi' .
            'ng=\"utf-8\"?><Ips><OrderQueryReq><head><Version>v1.0.0<\/Version><MerCode>165902<\/MerCode><Account>' .
            '1659020018<\/Account><\/head><body><MerBillNo>201601270000006632<\/MerBillNo><Date>20160127<\/Date>' .
            '<Amount>0.01<\/Amount><\/body><\/OrderQueryReq><\/Ips>"},"title":"SendDepositTrackingRequestCommand",' .
            '"headers":{"Host":"payment.http.test.com"},"entryId":"201305280000000001",' .
            '"attempt":{"verify_ip":0,"count":2}},"priority":0,"progress":0,"state":"failed","error":"Error:' .
            ' Error: getaddrinfo ENOTFOUND test.com test.com:80\n    at Socket.emit (events.js:169:7)","created_at"' .
            ':"1453368095114","promote_at":"1453368095114","updated_at":"1453368095347","failed_at":"1453368095347"' .
            ',"started_at":"1453368095118","workerId":"kue:localhost.localdomain:10645:req.payment:2",' .
            '"attempts":{"made":1,"remaining":0,"max":1}}]';
        $this->mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($failedResponse);

        // failed retry 的返回
        $retryResponse = '{"message":"job created","id": 366}';
        $this->mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($retryResponse);

        // failed delete 的返回
        $deleteResponse = '{"message":"job 322 removed"}';
        $this->mockResponse->expects($this->at(4))
            ->method('getContent')
            ->willReturn($deleteResponse);

        // 將支付平台的返回做編碼
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature>523eb9fc960f37ccff2c6e43fa451f31</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201304280000000001</MerBillNo>' .
            '<IpsBillNo>BO20130428122730112862</IpsBillNo>' .
            '<TradeType>1001</TradeType>' .
            '<Currency>156</Currency>' .
            '<Amount>100.00</Amount>' .
            '<MerBillDate>20130410</MerBillDate>' .
            '<IpsBillTime>20130428122730</IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status>Y</Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $body = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $encodedBody = base64_encode($body);

        // 取得 complete 的一筆返回
        $completeResponse = '[{"id":"352","type":"req.payment","data":{"url":"http:\/\/1.2.3.4\/psfp-entry\/' .
            'services\/order?wsdl","function":"getOrderByMerBillNo","arguments":{"orderQuery":"<?xml version=\"1.' .
            '0\" encoding=\"utf-8\"?><Ips><OrderQueryReq><head><Version>v1.0.0<\/Version><MerCode>12345<\/MerCode' .
            '><MerName><\/MerName><Account>123456<\/Account><ReqDate>20130428000000<\/ReqDate><Signature>c685abf3' .
            '65525617e052c52949bf7607<\/Signature><\/head><body><MerBillNo>201304280000000001<\/MerBillNo><Date>2' .
            '0130428<\/Date><Amount>100.00<\/Amount><\/body><\/OrderQueryReq><\/Ips>"},"title":"SendDepositTracki' .
            'ngRequestCommand","headers":{"Host":"payment.http.www.ips7.com"},"entryId":"201304280000000001",' .
            '"attempt":{"verify_ip":0,"count":1},"job_result":{"header":null,"body":"' . $encodedBody . '"}}}]';
        $this->mockResponse->expects($this->at(6))
            ->method('getContent')
            ->willReturn($completeResponse);

        // complete delete 的返回
        $deleteResponse = '{"message":"job 352 removed"}';
        $this->mockResponse->expects($this->at(8))
            ->method('getContent')
            ->willReturn($deleteResponse);

        $application = new Application();
        $command = new DepositTrackingVerifyCommand();
        $command->setContainer($container);
        $command->setClient($this->mockClient);
        $command->setResponse($this->mockResponse);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--failed-from' => '0',
            '--failed-to' => '999',
            '--complete-from' => '0',
            '--complete-to' => '999',
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        // 檢查取得 failed 資料的 log
        $failedMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'GET',
            '/jobs/req.payment/failed/0..999/asc',
            '',
            $failedResponse
        );
        $this->assertContains($failedMessage, $results[0]);

        // 檢查 kue 查詢失敗的 log
        $requestContent = '{"orderQuery":"<?xml version=\"1.0\" encoding=\"utf-8\"?><Ips><OrderQueryReq><head>' .
            '<Version>v1.0.0<\/Version><MerCode>165902<\/MerCode><Account>1659020018<\/Account><\/head><body>' .
            '<MerBillNo>201601270000006632<\/MerBillNo><Date>20160127<\/Date><Amount>0.01<\/Amount><\/body>' .
            '<\/OrderQueryReq><\/Ips>"}';
        $errorMsg = 'Kue訂單查詢失敗，Entry Id: 201305280000000001。Error Message: ' .
            'Error: Error: getaddrinfo ENOTFOUND test.com test.com:80    at Socket.emit (events.js:169:7)';
        $failedMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'SOAP',
            'http://1.2.3.4/psfp-entry/services/order?wsdl',
            $requestContent,
            $errorMsg
        );
        $this->assertContains($failedMessage, $results[1] . $results[2]);

        // 檢查 failed retry 的 log
        $requestContent = '{"type":"req.payment","data":{"function":"getOrderByMerBillNo","arguments":{"orderQuery":' .
            '"<?xml version=\"1.0\" encoding=\"utf-8\"?><Ips><OrderQueryReq><head><Version>v1.0.0<\/Version><MerCode' .
            '>12345<\/MerCode><MerName><\/MerName><Account>123456<\/Account><ReqDate>20130528120000<\/ReqDate><Signa' .
            'ture>e6869c2a63d878d992dff2ad4edb53f7<\/Signature><\/head><body><MerBillNo>201305280000000001<\/MerBill' .
            'No><Date>20130528<\/Date><Amount>1000.00<\/Amount><\/body><\/OrderQueryReq><\/Ips>"},"headers":{"Host":' .
            '"payment.http.www.ips7.com"},"title":"DepositTrackingVerifyCommand","entryId":"201305280000000001","att' .
            'empt":{"verify_ip":0,"count":3},"url":"http:\/\/1.2.3.4\/psfp-entry\/services\/order?wsdl"}}';
        $failedMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'POST',
            '/job',
            $requestContent,
            '{"message":"job created","id":366}'
        );
        $this->assertContains($failedMessage, $results[3]);

        // 檢查 failed 删除 Job 的 log
        $failedMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'DELETE',
            '/job/322',
            '',
            '{"message":"job 322 removed"}'
        );
        $this->assertContains($failedMessage, $results[4]);

        // 轉換 job body 內的編碼
        $completeResponse = json_decode($completeResponse, true);
        $completeResponse[0]['data']['job_result']['body'] = $body;

        // 檢查取得 complete 資料的 log
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'GET',
            '/jobs/req.payment/complete/0..999/asc',
            '',
            json_encode($completeResponse)
        );
        $this->assertContains($completeMessage, $results[5]);

        // 檢查 complete 訂單解密驗證成功的 log
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'SOAP',
            $completeResponse[0]['data']['url'],
            json_encode($completeResponse[0]['data']['arguments']),
            '解密驗證成功，Entry Id: 201304280000000001。' . json_encode($completeResponse[0]['data']['job_result'])
        );
        $this->assertContains($completeMessage, $results[6]);

        // 檢查 complete 删除訂單已確認入款的 Job 的 log
        $completeMessage = sprintf(
            '"%s %s" "REQUEST: %s" "RESPONSE: %s"',
            'DELETE',
            '/job/352',
            '',
            '{"message":"job 352 removed"}'
        );
        $this->assertContains($completeMessage, $results[7]);

        // 檢查訂單狀態
        $em->refresh($failedEntry);
        $this->assertFalse($failedEntry->isConfirm());

        $em->refresh($completeEntry);
        $this->assertTrue($completeEntry->isConfirm());
    }

    /**
     * 測試入款查詢解密驗證背景，但未帶入欲處理的 job 範圍
     */
    public function testExecuteWithoutParams()
    {
        $this->runCommand('durian:deposit-tracking-verify');

        // 檢查 log 是否不存在
        $this->assertFileNotExists($this->logPath);
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
