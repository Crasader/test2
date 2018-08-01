<?php
namespace BB\DurianBundle\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Console\Application;
use BB\DurianBundle\Command\PopFailedMessageCommand;
use Symfony\Component\Console\Tester\CommandTester;

class PopFailedMessageCommandTest extends WebTestCase
{
    /**
     * log 檔路徑
     *
     * @var string
     */
    private $logPath;

    /**
     * 初始化設定
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([]);

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'pop_failed_message.log';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試清除傳送失敗的訊息
     */
    public function testPopFailedMessage()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue_failed';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'user' => 'lala',
            'password' => 1234,
            'type' => 'developer_acc',
            'message' => 'gagawa',
            'code' => 3
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $output = $this->runCommand('durian:pop-failed-message');
        $results = explode(PHP_EOL, $output);

        $this->assertEquals('PopFailedMessage start.', $results[0]);
        $this->assertEquals("Pop success, $encodeData", $results[1]);
        $this->assertEquals('Total count: 1', $results[2]);
        $this->assertEquals('PopFailedMessage finish.', $results[3]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('PopFailedMessage start.', $results[0]);
        $this->assertContains("Pop success, $encodeData", $results[1]);
        $this->assertContains('Total count: 1', $results[2]);
        $this->assertContains('PopFailedMessage finish.', $results[3]);

        $message = $redis->rpop($key);
        $this->assertEmpty($message);
    }

    /**
     * 測試重新推入傳送失敗的訊息
     */
    public function testPopFailedMessageWithRepush()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_immediate_queue_failed';
        $data = [
            'target' => 'maintain_3',
            'error_count' => 1,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [],
            'timeout' => 30
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $output = $this->runCommand('durian:pop-failed-message', ['--immediate' => true, '--repush' => true]);
        $results = explode(PHP_EOL, $output);

        $this->assertEquals('PopFailedMessage start.', $results[0]);
        $this->assertEquals("Pop success, $encodeData", $results[1]);
        $this->assertEquals('Total count: 1', $results[2]);
        $this->assertEquals('PopFailedMessage finish.', $results[3]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('PopFailedMessage start.', $results[0]);
        $this->assertContains("Pop success, $encodeData", $results[1]);
        $this->assertContains('Total count: 1', $results[2]);
        $this->assertContains('PopFailedMessage finish.', $results[3]);

        // 訊息會重新推入 retry queue
        $key = 'message_immediate_queue_retry';
        $message = $redis->rpop($key);

        $this->assertEquals($encodeData, $message);
    }

    /**
     * 測試查看傳送失敗的訊息
     */
    public function testPopFailedMessageWithDryRun()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue_failed';
        $data = [
            'target' => 'italking',
            'error_count' => 11,
            'user' => 'lala',
            'password' => 1234,
            'type' => 'developer_acc',
            'message' => 'gagawa',
            'code' => 3
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $output = $this->runCommand('durian:pop-failed-message', ['--dry-run' => true]);
        $results = explode(PHP_EOL, $output);

        $this->assertEquals('PopFailedMessage start.', $results[0]);
        $this->assertEquals("Pop success, $encodeData", $results[1]);
        $this->assertEquals('Total count: 1', $results[2]);
        $this->assertEquals('PopFailedMessage finish.', $results[3]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('PopFailedMessage start.', $results[0]);
        $this->assertContains("Pop success, $encodeData", $results[1]);
        $this->assertContains('Total count: 1', $results[2]);
        $this->assertContains('PopFailedMessage finish.', $results[3]);

        // 訊息仍存於 failed queue
        $message = $redis->rpop($key);

        $this->assertEquals($encodeData, $message);
    }

    /**
     * 測試重新推入傳送失敗的訊息但發生 timeout
     */
    public function testPopFailedMessageWithRepushButTimeout()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_queue_failed';
        $data = [
            'target' => 'italking',
            'error_count' => 0,
            'user' => 'lala',
            'password' => 1234,
            'type' => 'developer_acc',
            'message' => 'gagawa',
            'code' => 3
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $mockContainer = $this->getMockContainer();
        $application = new Application();
        $command = new PopFailedMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:pop-failed-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--repush' => true]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('PopFailedMessage start.', $results[0]);
        $timeoutMessage = sprintf(
            'Pop failed, QueueName: %s Message: %s ErrorCode: %d ErrorMessage: Connection timed out',
            $key,
            $encodeData,
            SOCKET_ETIMEDOUT
        );
        $this->assertContains($timeoutMessage, $results[1]);
        $this->assertContains('Total count: 0', $results[2]);
        $this->assertContains('PopFailedMessage finish.', $results[3]);

        // 訊息會重新推入 retry queue
        $key = 'message_queue_retry';
        $message = $redis->rpop($key);

        $this->assertEquals($encodeData, $message);
    }

    /**
     * 測試查看傳送失敗的訊息但發生 timeout
     */
    public function testPopFailedMessageWithDryRunButTimeout()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'message_immediate_queue_failed';
        $data = [
            'target' => 'maintain_3',
            'error_count' => 1,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [],
            'timeout' => 30
        ];
        $encodeData = json_encode($data);
        $redis->lpush($key, $encodeData);

        $mockContainer = $this->getMockContainer();
        $application = new Application();
        $command = new PopFailedMessageCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:pop-failed-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--immediate' => true, '--dry-run' => true]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('PopFailedMessage start.', $results[0]);
        $timeoutMessage = sprintf(
            'Pop failed, QueueName: %s Message: %s ErrorCode: %d ErrorMessage: Connection timed out',
            $key,
            $encodeData,
            SOCKET_ETIMEDOUT
        );
        $this->assertContains($timeoutMessage, $results[1]);
        $this->assertContains('Total count: 0', $results[2]);
        $this->assertContains('PopFailedMessage finish.', $results[3]);

        // 訊息仍存於 failed queue
        $message = $redis->rpop($key);

        $this->assertEquals($encodeData, $message);
   }

    /**
     * 取得 MockContainer
     *
     * @return Container
     */
    private function getMockContainer()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.pop_failed_message');

        $mockRedis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['llen', 'rpop', 'lpush'])
            ->getMock();

        $mockRedis->expects($this->any())
            ->method('llen')
            ->will($this->returnValue(1));

        $mockRedis->expects($this->any())
            ->method('rpop')
            ->will($this->returnCallback(function ($arg1) {
                $redis = $this->getContainer()->get('snc_redis.default_client');
                $message = $redis->rpop($arg1);
                return $message;
            }));

        $mockRedis->expects($this->at(2))
            ->method('lpush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockRedis->expects($this->at(3))
            ->method('lpush')
            ->will($this->returnCallback(function ($arg1, $arg2) {
                $redis = $this->getContainer()->get('snc_redis.default_client');
                $redis->lpush($arg1, $arg2);
            }));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['logger', 1, $logger],
            ['monolog.handler.pop_failed_message', 1, $handler],
            ['snc_redis.default_client', 1, $mockRedis]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }

    /**
     * 清除產生的檔案
     */
    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }
}
