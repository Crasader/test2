<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\AuditCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;

class AuditCommandTest extends WebTestCase
{
    /**
     * 初始化設定
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/audit.log';

        // 如果log檔已經存在就移除
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    /**
     * 測試通知稽核
     */
    public function testExecute()
    {
        // mock對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->setContent('{"result":"ok"}');
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new AuditCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 測試當沒有需要稽核的資料可以正常執行
        $commandTester->execute(['command' => $command->getName()]);

        // 因沒有需要稽核的資料，所以不會產生log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/audit.log';
        $this->assertFileNotExists($logPath);

        // 測試成功通知稽核
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'cash_deposit_entry_id' => 201501140000121212,
            'user_id' => 35660,
            'balance' => 288.0000,
            'amount' => 100.0000,
            'offer' => 10.0000,
            'fee' => -5.0000
        ];
        $redis->lpush('audit_queue', json_encode($params));

        $commandTester->execute(['command' => $command->getName()]);
        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);

        $results = explode(PHP_EOL, $contents);

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $this->getContainer()->getParameter('audit_ip'),
            '127.0.0.1',
            'POST',
            '/api/payment/audit/post.json',
            http_build_query(['Audit' => json_encode([$params])]),
            '{"result":"ok"}'
        );

        $this->assertContains($logContent, $results[0]);
    }

    /**
     * 測試通知稽核對外連線timeout
     */
    public function testExecuteCrulTimeout()
    {
        // mock對外連線及返回
        $exceptionMsg = 'Operation timed out after 5000 milliseconds with 0 bytes received';
        $client = $this
            ->getMockBuilder('Buzz\Client\Curl')
            ->setMethods(['send'])
            ->getMock();
        $client->expects($this->any())
            ->method('send')
            ->will($this->throwException(new \Exception($exceptionMsg, 28)));

        $response = new Response();

        $application = new Application();
        $command = new AuditCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'cash_deposit_entry_id' => 201501140000121212,
            'user_id' => 35660,
            'balance' => 288.0000,
            'amount' => 100.0000,
            'offer' => 10.0000,
            'fee' => -5.0000
        ];
        $redis->lpush('audit_queue', json_encode($params));

        $commandTester->execute(['command' => $command->getName()]);

        // 檢查是否紀錄retry
        $newMsg = json_decode($redis->rpop('audit_queue'), true);
        $params['retry'] = 1;

        $this->assertEquals($params, $newMsg);
    }

    /**
     * 測試通知稽核對外連線結果為非200
     */
    public function testExecuteCurlHasNotSucceeded()
    {
        // mock對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AuditCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'cash_deposit_entry_id' => 201501140000121212,
            'user_id' => 35660,
            'balance' => 288.0000,
            'amount' => 100.0000,
            'offer' => 10.0000,
            'fee' => -5.0000
        ];
        $redis->lpush('audit_queue', json_encode($params));

        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/audit.log';
        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);

        $results = explode(PHP_EOL, $contents);

        $message = sprintf(
            "ErrorCode: %s，ErrorMsg: %s",
            '370052',
            'StatusCode: 502，ErrorMsg: <h1>502 Bad Gateway</h1>'
        );

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $this->getContainer()->getParameter('audit_ip'),
            '127.0.0.1',
            'POST',
            '/api/payment/audit/post.json',
            http_build_query(['Audit' => json_encode([$params])]),
            $message
        );

        $this->assertContains($logContent, $results[0]);

        // 檢查是否紀錄retry
        $newMsg = json_decode($redis->rpop('audit_queue'), true);
        $params['retry'] = 1;

        $this->assertEquals($params, $newMsg);
    }

    /**
     * 測試連線失敗十次會送訊息給客服的italking
     */
    public function testExecuteCurlHasNotSucceededTenTimes()
    {
        // mock對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AuditCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'cash_deposit_entry_id' => 201501140000121212,
            'user_id' => 35660,
            'balance' => 288.0000,
            'amount' => 100.0000,
            'offer' => 10.0000,
            'fee' => -5.0000
        ];
        $redis->lpush('audit_queue', json_encode($params));

        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);

        $iTalkingMsg = json_decode($redis->rpop('italking_exception_queue'), true);

        $this->assertEquals('acc_system', $iTalkingMsg['type']);
        $this->assertContains('通知稽核失敗', $iTalkingMsg['message']);
        $this->assertEquals(1, $iTalkingMsg['code']);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/audit.log';
        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);

        $message = sprintf(
            "ErrorCode: %s，ErrorMsg: %s",
            '370052',
            'StatusCode: 502，ErrorMsg: <h1>502 Bad Gateway</h1>'
        );

        $params['retry'] = 9;

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $this->getContainer()->getParameter('audit_ip'),
            '127.0.0.1',
            'POST',
            '/api/payment/audit/post.json',
            http_build_query(['Audit' => json_encode([$params])]),
            $message
        );

        $this->assertContains($logContent, $contents);
    }

    /**
     * 測試通知稽核回傳結果沒有參數result
     */
    public function testExecuteResponseWithoutResult()
    {
        // mock對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->setContent('{"test":"ok"}');
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new AuditCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'cash_deposit_entry_id' => 201501140000121212,
            'user_id' => 35660,
            'balance' => 288.0000,
            'amount' => 100.0000,
            'offer' => 10.0000,
            'fee' => -5.0000
        ];
        $redis->lpush('audit_queue', json_encode($params));

        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/audit.log';
        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);

        $results = explode(PHP_EOL, $contents);

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $this->getContainer()->getParameter('audit_ip'),
            '127.0.0.1',
            'POST',
            '/api/payment/audit/post.json',
            http_build_query(['Audit' => json_encode([$params])]),
            '{"test":"ok"}'
        );

        $this->assertContains($logContent, $results[0]);

        // 檢查是否紀錄retry
        $newMsg = json_decode($redis->rpop('audit_queue'), true);
        $params['retry'] = 1;

        $this->assertEquals($params, $newMsg);
    }

    /**
     * 測試通知稽核回傳結果result不為ok
     */
    public function testExecuteResponseWithoutResultOk()
    {
        // mock對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->setContent('{"result":"success"}');
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new AuditCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'cash_deposit_entry_id' => 201501140000121212,
            'user_id' => 35660,
            'balance' => 288.0000,
            'amount' => 100.0000,
            'offer' => 10.0000,
            'fee' => -5.0000
        ];
        $redis->lpush('audit_queue', json_encode($params));

        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/audit.log';
        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);

        $results = explode(PHP_EOL, $contents);

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $this->getContainer()->getParameter('audit_ip'),
            '127.0.0.1',
            'POST',
            '/api/payment/audit/post.json',
            http_build_query(['Audit' => json_encode([$params])]),
            '{"result":"success"}'
        );

        $this->assertContains($logContent, $results[0]);

        // 檢查是否紀錄retry
        $newMsg = json_decode($redis->rpop('audit_queue'), true);
        $params['retry'] = 1;

        $this->assertEquals($params, $newMsg);
    }

    /**
     * 清除產生的檔案
     */
    public function tearDown()
    {
        parent::tearDown();

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/audit.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }
}
