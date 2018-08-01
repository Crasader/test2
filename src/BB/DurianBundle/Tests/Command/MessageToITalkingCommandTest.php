<?php
namespace BB\DurianBundle\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\MessageToITalkingCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MessageToITalkingCommandTest extends WebTestCase
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

        $classnames = array();

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $msg = ['type' => 'payment_alarm'];

        $redis->lpush('italking_message_queue', json_encode($msg));

        $msg = ['message' => 'test msg'];

        $redis->lpush('italking_message_queue', json_encode($msg));

        $msg = [
            'type'    => 'payment_alarm',
            'message' => 'test msg',
            'code'    => 1234
        ];

        $redis->lpush('italking_message_queue', json_encode($msg));

        $msg = [
            'type'    => 'payment_alarm',
            'message' => 'test msg',
            'code'    => 1234
        ];

        $redis->lpush('italking_message_queue', json_encode($msg));

        $msg = [
            'type'    => 'developer_acc',
            'message' => 'test',
            'code'    => 1234
        ];

        $redis->lpush('italking_exception_queue', json_encode($msg));

        // 同種例外只送一次(ErrorMessage的內容相同)
        $msg = [
            'type'      => 'developer_acc',
            'exception' => 'Exception',
            'message'   => '[2015-01-29 15:23:00] ErrorMessage: test msg [127.0.0.1]',
            'code'      => 1234
        ];

        $redis->lpush('italking_exception_queue', json_encode($msg));

        $msg = [
            'type'      => 'developer_acc',
            'exception' => 'Exception',
            'message'   => '[2015-01-29 15:23:01] ErrorMessage: test msg [127.0.0.1]',
            'code'      => 1234
        ];

        $redis->lpush('italking_exception_queue', json_encode($msg));

        // command產生的例外訊息
        $msg = [
            'type'      => 'developer_acc',
            'exception' => 'Exception',
            'message'   => '[2015-01-29 15:23:01] test msg',
            'code'      => 1234
        ];

        $redis->lpush('italking_exception_queue', json_encode($msg));

        //queue沒有code
        $msg = [
            'type'      => 'developer_acc',
            'message'   => 'test'
        ];

        $redis->lpush('italking_message_queue', json_encode($msg));

        //queue內容為錯誤內容
        $msg = [
            'type'      => 'developer_acc',
            'exception' => 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
            'message'   => null,
            'code'      => 140502002
        ];

        $redis->lpush('italking_exception_queue', json_encode($msg));

        $dir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'message_to_italking.log';
        $this->logPath = $dir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 測試iTalking連線失敗
     */
    public function testExecute()
    {
        $logPath = $this->getContainer()->getParameter('kernel.logs_dir');

        $italkingOperator = $this->getMockBuilder('BB\DurianBundle\Message\ITalkingOperator')
            ->disableOriginalConstructor()
            ->getMock();
        $italkingOperator->expects($this->any())
            ->method('getITalkingIp')
            ->will($this->returnValue('127.0.0.1'));
        $italkingOperator->expects($this->any())
            ->method('checkITalkingStatus')
            ->will($this->throwException(new \Exception('Fail to send message', SOCKET_ETIMEDOUT)));

        $mockContainer = $this->getMockContainer($italkingOperator);

        $application = new Application();
        $command = new MessageToITalkingCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:message-to-italking');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        // check log file exists
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);

        $results = explode(PHP_EOL, $contents);

        $this->assertStringEndsWith('MessageToITalkingCommand Start. [] []', $results[0]);
        $msg = 'Network not work, background aborted. '.
               'ErrorCode: ' . SOCKET_ETIMEDOUT . ' ErrorMsg: Fail to send message [] []';
        $this->assertStringEndsWith($msg, $results[1]);
        $this->assertStringEndsWith('MessageToITalkingCommand finish. [] []', $results[2]);
    }

    /**
     * 測試ItalkingIp為空則不送訊息
     */
    public function testExecuteWithItalkingIpNull()
    {
        $logPath = $this->getContainer()->getParameter('kernel.logs_dir');

        $italkingOperator = $this->getMockBuilder('BB\DurianBundle\Message\ITalkingOperator')
            ->disableOriginalConstructor()
            ->getMock();
        $italkingOperator->expects($this->any())
            ->method('getITalkingIp')
            ->will($this->returnValue(''));

        $mockContainer = $this->getMockContainer($italkingOperator);

        $application = new Application();
        $command = new MessageToITalkingCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:message-to-italking');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $contents = file_get_contents($this->logPath);

        $results = explode(PHP_EOL, $contents);

        $this->assertStringEndsWith('MessageToITalkingCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith('MessageToITalkingCommand finish. [] []', $results[1]);

        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'italking_message_queue';
        $this->assertEquals(0, $redis->llen($key));

        $key = 'italking_exception_queue';
        $this->assertEquals(0, $redis->llen($key));
    }

    /**
     * 測試queue為空則不送訊息
     */
    public function testExecuteWithEmptyQueue()
    {
        $logPath = $this->getContainer()->getParameter('kernel.logs_dir');

        $italkingOperator = $this->getMockBuilder('BB\DurianBundle\Message\ITalkingOperator')
            ->disableOriginalConstructor()
            ->getMock();
        $italkingOperator->expects($this->any())
            ->method('getITalkingIp')
            ->will($this->returnValue('127.0.0.1'));

        $mockContainer = $this->getMockContainer($italkingOperator);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $application = new Application();
        $command = new MessageToITalkingCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:message-to-italking');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $contents = file_get_contents($this->logPath);

        $results = explode(PHP_EOL, $contents);

        $this->assertStringEndsWith('MessageToITalkingCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith('MessageToITalkingCommand finish. [] []', $results[1]);

        $key = 'italking_message_queue';
        $this->assertEquals(0, $redis->llen($key));

        $key = 'italking_exception_queue';
        $this->assertEquals(0, $redis->llen($key));
    }

    /**
     * 測試發送訊息
     */
    public function testExecuteWithSendMessage()
    {
        $logPath = $this->getContainer()->getParameter('kernel.logs_dir');

        $italkingOperator = $this->getMockBuilder('BB\DurianBundle\Message\ITalkingOperator')
            ->disableOriginalConstructor()
            ->getMock();
        $italkingOperator->expects($this->any())
            ->method('getITalkingIp')
            ->will($this->returnValue('127.0.0.1'));

        $mockContainer = $this->getMockContainer($italkingOperator);

        $application = new Application();
        $command = new MessageToITalkingCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:message-to-italking');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $contents = file_get_contents($this->logPath);

        $results = explode(PHP_EOL, $contents);

        $this->assertStringEndsWith('MessageToITalkingCommand Start. [] []', $results[0]);

        $msg = 'Queue: italking_message_queue Msg: {"type":"payment_alarm"} '.
               'ErrorMsg: Some keys in the message are missing, skipped [] []';
        $this->assertStringEndsWith($msg, $results[1]);

        $msg = 'Queue: italking_message_queue Msg: {"message":"test msg"} '.
               'ErrorMsg: Some keys in the message are missing, skipped [] []';
        $this->assertStringEndsWith($msg, $results[2]);

        //message_queue沒有code
        $msg = 'Queue: italking_message_queue '.
               'Msg: {"type":"developer_acc","message":"test"} '.
               'ErrorMsg: Some keys in the message are missing, skipped [] []';
        $this->assertStringEndsWith($msg, $results[3]);

        $this->assertStringEndsWith(
            '{"type":"payment_alarm","message":"test msg","code":1234} Success  [] []',
            $results[4]
        );

        $this->assertStringEndsWith(
            '{"type":"payment_alarm","message":"test msg","code":1234} Success  [] []',
            $results[5]
        );

        $msg = 'Queue: italking_exception_queue '.
               'Msg: {"type":"developer_acc","message":"test","code":1234} '.
               'ErrorMsg: Some keys in the message are missing, skipped [] []';
        $this->assertStringEndsWith($msg, $results[6]);

        // 同種例外只送一次
        $this->assertStringEndsWith(
            '{"type":"developer_acc","exception":"Exception",' .
            '"message":"[2015-01-29 15:23:01] ErrorMessage: test msg [127.0.0.1]","code":1234} Success  [] []',
            $results[7]
        );

        // command產生的例外訊息
        $this->assertStringEndsWith(
            '{"type":"developer_acc","exception":"Exception",' .
            '"message":"[2015-01-29 15:23:01] test msg","code":1234} Success  [] []',
            $results[8]
        );

        $this->assertStringEndsWith('MessageToITalkingCommand finish. [] []', $results[9]);

        //錯誤訊息不會送出
        $this->assertEquals('', $results[10]);
    }

    /**
     * 測試發送錯誤
     */
    public function testExecuteWithSendMessageFailed()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 第一則訊息無帶message, 第二則訊息無帶type, 第五則無帶code, 正確訊息2則
        $key = 'italking_message_queue';
        $this->assertEquals(5, $redis->llen($key));

        // 第一則訊息無帶exception, 一則錯誤訊息, 2則訊息重複, 一則command訊息
        $key = 'italking_exception_queue';
        $this->assertEquals(5, $redis->llen($key));

        $logPath = $this->getContainer()->getParameter('kernel.logs_dir');

        $italkingOperator = $this->getMockBuilder('BB\DurianBundle\Message\ITalkingOperator')
            ->disableOriginalConstructor()
            ->getMock();
        $italkingOperator->expects($this->any())
            ->method('getITalkingIp')
            ->will($this->returnValue('127.0.0.1'));
        $italkingOperator->expects($this->any())
            ->method('sendMessageToITalking')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockContainer = $this->getMockContainer($italkingOperator);

        $application = new Application();
        $command = new MessageToITalkingCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:message-to-italking');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $contents = file_get_contents($this->logPath);

        $results = explode(PHP_EOL, $contents);

        $this->assertStringEndsWith('MessageToITalkingCommand Start. [] []', $results[0]);

        $msg = 'Queue: italking_message_queue Msg: {"type":"payment_alarm"} '.
               'ErrorMsg: Some keys in the message are missing, skipped [] []';
        $this->assertStringEndsWith($msg, $results[1]);

        $msg = 'Queue: italking_message_queue Msg: {"message":"test msg"} '.
               'ErrorMsg: Some keys in the message are missing, skipped [] []';
        $this->assertStringEndsWith($msg, $results[2]);

        //message_queue沒有code
        $msg = 'Queue: italking_message_queue '.
               'Msg: {"type":"developer_acc","message":"test"} '.
               'ErrorMsg: Some keys in the message are missing, skipped [] []';
        $this->assertStringEndsWith($msg, $results[3]);

        $msg = 'Queue: italking_message_queue '.
               'Msg: {"type":"payment_alarm","message":"test msg","code":1234} '.
               'ErrorCode: ' . SOCKET_ETIMEDOUT . ' ErrorMsg: Connection timed out [] []';
        $this->assertStringEndsWith($msg, $results[4]);

        $msg = 'Queue: italking_message_queue '.
               'Msg: {"type":"payment_alarm","message":"test msg","code":1234} '.
               'ErrorCode: ' . SOCKET_ETIMEDOUT . ' ErrorMsg: Connection timed out [] []';
        $this->assertStringEndsWith($msg, $results[5]);

        $msg = 'Queue: italking_exception_queue '.
               'Msg: {"type":"developer_acc","message":"test","code":1234} '.
               'ErrorMsg: Some keys in the message are missing, skipped [] []';
        $this->assertStringEndsWith($msg, $results[6]);

        // 同種例外只送一次
        $msg = 'Queue: italking_exception_queue '.
               'Msg: {"type":"developer_acc","exception":"Exception",' .
               '"message":"[2015-01-29 15:23:01] ErrorMessage: test msg [127.0.0.1]","code":1234} ' .
               'ErrorCode: ' . SOCKET_ETIMEDOUT . ' ErrorMsg: Connection timed out [] []';
        $this->assertStringEndsWith($msg, $results[7]);

        // command產生的例外訊息
        $msg = 'Queue: italking_exception_queue ' .
               'Msg: {"type":"developer_acc","exception":"Exception",' .
               '"message":"[2015-01-29 15:23:01] test msg","code":1234} ' .
               'ErrorCode: ' . SOCKET_ETIMEDOUT . ' ErrorMsg: Connection timed out [] []';
        $this->assertStringEndsWith($msg, $results[8]);

        $this->assertStringEndsWith('MessageToITalkingCommand finish. [] []', $results[9]);

        //錯誤訊息不會送出
        $this->assertEquals('', $results[10]);

        // 如發送錯誤會把訊息推回
        $key = 'italking_message_queue';

        $this->assertEquals(2, $redis->llen($key));

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('payment_alarm', $queueMsg['type']);
        $this->assertEquals('test msg', $queueMsg['message']);

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('payment_alarm', $queueMsg['type']);
        $this->assertEquals('test msg', $queueMsg['message']);

        $key = 'italking_exception_queue';

        $this->assertEquals(2, $redis->llen($key));

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals('Exception', $queueMsg['exception']);
        $this->assertEquals('[2015-01-29 15:23:01] ErrorMessage: test msg [127.0.0.1]', $queueMsg['message']);

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals('Exception', $queueMsg['exception']);
        $this->assertEquals('[2015-01-29 15:23:01] test msg', $queueMsg['message']);
    }

    /**
     * 取得 MockContainer
     *
     * @param service $italkingOperator
     * @return Container
     */
    private function getMockContainer($italkingOperator)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $handler = $this->getContainer()->get('monolog.handler.message_to_italking');
        $logger = $this->getContainer()->get('logger');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();
        $getMap = [
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.monitor.background', 1, $bgMonitor],
            ['monolog.handler.message_to_italking', 1, $handler],
            ['logger', 1, $logger],
            ['snc_redis.default_client', 1, $redis]
        ];
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValue('test'));

        return $mockContainer;
    }

    /**
     * 清除產生的檔案
     */
    public function tearDown()
    {
        parent::tearDown();

        $dir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'send_exception_message.log';
        $logPath = $dir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($fileName)) {
            unlink($fileName);
        }

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }
}
