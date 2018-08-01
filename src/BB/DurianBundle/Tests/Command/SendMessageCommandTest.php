<?php
namespace BB\DurianBundle\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;
use Symfony\Component\Console\Application;
use BB\DurianBundle\Command\SendMessageCommand;
use Symfony\Component\Console\Tester\CommandTester;

class SendMessageCommandTest extends WebTestCase
{
    /**
     * log 檔路徑
     *
     * @var string
     */
    private $logPath;

    /**
     * 送訊息 log 檔路徑
     *
     * @var string
     */
    private $httpLogPath;

    /**
     * 初始化設定
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([]);

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'send_message.log';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
        $fileName = 'send_message_http_detail.log';
        $this->httpLogPath = $logDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試傳送訊息
     */
    public function testSendMessage()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.italking_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['code' => 0];
        $response->setContent(json_encode($responseContent));
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains("Send success, $encodeData", $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);
    }

    /**
     * 測試傳送訊息失敗
     */
    public function testSendMessageFailed()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.italking_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 150660016 ErrorMessage: Send italking message failed', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 傳送失敗會將訊息推入 retry queue
        $key = 'message_queue_retry';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 測試傳送即時訊息
     */
    public function testSendImmediateMessage()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_immediate_queue';
        $beginAt = new \DateTime('2015-08-10 00:00:00');
        $endAt = new \DateTime('2015-08-15 00:00:00');
        $data = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'POST',
            'url' => 'httpCurl/url',
            'content' => [
                'code' => 1,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => '123',
                'is_maintaining' => 'false'
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.rd1_maintain_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'ok'];
        $response->setContent(json_encode($responseContent));
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--immediate' => true]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains("Send success, $encodeData", $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);
    }

    /**
     * 測試傳送即時訊息失敗
     */
    public function testSendImmediateMessageFailed()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_immediate_queue';
        $beginAt = new \DateTime('2015-08-10 00:00:00');
        $endAt = new \DateTime('2015-08-15 00:00:00');
        $data = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'POST',
            'url' => 'httpCurl/url',
            'content' => [
                'code' => 1,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => '123',
                'is_maintaining' => 'false'
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.rd1_maintain_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--immediate' => true]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 150660029 ErrorMessage: Send RD1 maintain message failed', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 傳送失敗會將訊息推入 retry queue
        $key = 'message_immediate_queue_retry';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 測試傳送 italking api 例外訊息重複
     */
    public function testSendDuplicateITalkingApiMessage()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'ErrorMessage: No route found for gagawa [',
                'code' => 3,
                'exception' => 'DBALException'
            ]
        ];
        $encodeData = json_encode($data);

        $redis->lpush($key, $encodeData);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.italking_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['code' => 0];
        $response->setContent(json_encode($responseContent));
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        // italking 例外訊息重複只會送一次
        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains("Send success, $encodeData", $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);
    }

    /**
     * 測試傳送 italking command 例外訊息重複
     */
    public function testSendDuplicateITalkingCommandMessage()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3,
                'exception' => 'DBALException'
            ]
        ];
        $encodeData = json_encode($data);

        $redis->lpush($key, $encodeData);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.italking_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['code' => 0];
        $response->setContent(json_encode($responseContent));
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        // italking 例外訊息重複只會送一次
        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains("Send success, $encodeData", $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);
    }

    /**
     * 測試 retry queue 的訊息會先送
     */
    public function testSendRetryMessagePriority()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3,
                'exception' => 'DBALException'
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $key = 'message_queue_retry';
        $retryData = [
            'target' => 'italking',
            'error_count' => 1,
            'content' => [
                'user' => 'gaga',
                'password' => 5678,
                'type' => 'account_fail',
                'message' => 'doremi',
                'code' => 6
            ]
        ];
        $encodeRetryData = json_encode($retryData);
        $redis->lpush($key, $encodeRetryData);

        $worker = $this->getContainer()->get('durian.italking_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['code' => 0];
        $response->setContent(json_encode($responseContent));
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains("Send success, $encodeRetryData", $results[1]);
        $this->assertContains("Send success, $encodeData", $results[2]);
        $this->assertContains('SendMessageCommand finish.', $results[3]);
    }

    /**
     * 測試傳送訊息帶入間隔時間
     */
    public function testSendMessageWithInterval()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ],
            'interval' => 0.0000001
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.italking_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['code' => 0];
        $response->setContent(json_encode($responseContent));
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains("Send success, $encodeData", $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);
    }

    /**
     * 測試傳送訊息帶入允許失敗次數
     */
    public function testSendMessageWithAllowedTimes()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ],
            'allowed_times' => 0
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.italking_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 150660016 ErrorMessage: Send italking message failed', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 超過允許失敗次數會將訊息推入 failed queue
        $key = 'message_queue_failed';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 測試傳送訊息帶入允許失敗次數為無限
     */
    public function testSendMessageWithInfiniteAllowedTimes()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_immediate_queue';
        $beginAt = new \DateTime('2015-08-10 00:00:00');
        $endAt = new \DateTime('2015-08-15 00:00:00');
        $data = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'POST',
            'url' => 'httpCurl/url',
            'content' => [
                'code' => 1,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => '123',
                'is_maintaining' => 'false'
            ],
            'allowed_times' => -1
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.rd1_maintain_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $worker->setResponse($response);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--immediate' => true]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 150660029 ErrorMessage: Send RD1 maintain message failed', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 允許失敗次數為無限時會將訊息推入 retry queue
        $key = 'message_immediate_queue_retry';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 測試傳送訊息帶入連線時限
     */
    public function testSendMessageWithTimeout()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_immediate_queue';
        $beginAt = new \DateTime('2015-08-10 00:00:00');
        $endAt = new \DateTime('2015-08-15 00:00:00');
        $data = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'POST',
            'url' => 'httpCurl/url',
            'content' => [
                'code' => 1,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => '123',
                'is_maintaining' => 'false'
            ],
            'timeout' => 0.001
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.rd1_maintain_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->setMethods(['send'])
            ->getMock();
        $client->expects($this->any())
            ->method('send')
            ->will($this->throwException(new \Exception('Timeout was reached', 28)));
        $worker->setClient($client);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--immediate' => true]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 28 ErrorMessage: Timeout was reached', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 時限內未送達會將訊息推入 retry queue
        $key = 'message_immediate_queue_retry';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 測試傳送訊息但無訊息可送
     */
    public function testSendMessageButNoMessage()
    {
        $worker = $this->getContainer()->get('durian.italking_worker');
        $mockContainer = $this->getMockContainer($worker, true);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--immediate' => true]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('SendMessageCommand finish.', $results[1]);
    }

    /**
     * 測試傳送訊息, 未指定 method
     */
    public function testSendMessageWithoutMethod()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'error_count' => 0,
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'message' => 'gagawa'
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.http_curl_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 150660001 ErrorMessage: No method specified', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 傳送失敗會將訊息推入 retry queue
        $key = 'message_queue_retry';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 測試傳送訊息, 未指定 url
     */
    public function testSendMessageWithoutUrl()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'error_count' => 0,
            'method' => 'GET',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'message' => 'gagawa'
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.http_curl_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 150660002 ErrorMessage: No url specified', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 傳送失敗會將訊息推入 retry queue
        $key = 'message_queue_retry';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 測試傳送訊息, 未指定 ip
     */
    public function testSendMessageWithoutIp()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'domain' => 'httpCurl',
            'content' => [
                'message' => 'gagawa'
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.http_curl_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 150660003 ErrorMessage: No ip specified', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 傳送失敗會將訊息推入 retry queue
        $key = 'message_queue_retry';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 測試傳送訊息, 未指定 domain
     */
    public function testSendMessageWithoutDomain()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue';
        $data = [
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'content' => [
                'message' => 'gagawa'
            ]
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $worker = $this->getContainer()->get('durian.http_curl_worker');
        $mockContainer = $this->getMockContainer($worker);
        $worker->setContainer($mockContainer);

        $application = new Application();
        $command = new SendMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:send-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('SendMessageCommand start.', $results[0]);
        $this->assertContains('ErrorCode: 150660004 ErrorMessage: No domain specified', $results[1]);
        $this->assertContains('SendMessageCommand finish.', $results[2]);

        // 傳送失敗會將訊息推入 retry queue
        $key = 'message_queue_retry';
        $message = $redis->rpop($key);
        $data['error_count']++;
        $encodeData = json_encode($data);

        $this->assertEquals($message, $encodeData);
    }

    /**
     * 取得 MockContainer
     *
     * @param Service $worker
     * @param boolean $mockRedis
     * @return Container
     */
    private function getMockContainer($worker, $mockRedis = false)
    {
        $monitor = $this->getContainer()->get('durian.monitor.background');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $logger = $this->getContainer()->get('logger');
        $msgLogger = $this->getContainer()->get('monolog.logger.msg');
        $handler = $this->getContainer()->get('monolog.handler.send_message');
        $httpWorker = $this->getContainer()->get('monolog.handler.send_message_http_detail');

        if ($mockRedis) {
            $redis = $this->getMockBuilder('Predis\Client')
                ->disableOriginalConstructor()
                ->setMethods(['llen', 'rpop'])
                ->getMock();

            $redis->expects($this->any())
                ->method('llen')
                ->will($this->returnValue(1));
        }

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $monitor],
            ['snc_redis.default_client', 1, $redis],
            ['durian.http_curl_worker', 1, $worker],
            ['durian.italking_worker', 1, $worker],
            ['durian.rd1_maintain_worker', 1, $worker],
            ['durian.rd1_worker', 1, $worker],
            ['durian.rd2_worker', 1, $worker],
            ['durian.rd3_maintain_worker', 1, $worker],
            ['durian.rd3_worker', 1, $worker],
            ['logger', 1, $logger],
            ['monolog.logger.msg', 1, $msgLogger],
            ['monolog.handler.send_message', 1, $handler],
            ['monolog.handler.send_message_http_detail', 1, $httpWorker]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $getParameterMap = [
            ['italking_method', 'POST'],
            ['italking_url', 'italking/url'],
            ['italking_ip', '127.0.0.1'],
            ['italking_domain', 'italking'],
            ['rd1_ip', '127.0.0.1'],
            ['rd1_domain', 'maintain1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        return $mockContainer;
    }

    /**
     * 清除產生的檔案
     */
    public function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        if (file_exists($this->httpLogPath)) {
            unlink($this->httpLogPath);
        }
    }
}
