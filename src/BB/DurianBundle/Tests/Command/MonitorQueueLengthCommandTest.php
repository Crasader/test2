<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\MonitorQueueLengthCommand;

class MonitorQueueLengthCommandTest extends WebTestCase
{
    /**
     * log檔的路徑
     *
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([]);

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'monitor_queue_length.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試監控CashQueue長度，沒有超過長度上限
     */
    public function testMonitorCashQueueLength()
    {
        $this->runCommand('durian:monitor-queue-length');

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingCount = $redis->llen('italking_message_queue');
        $this->assertEquals(0, $italkingCount);

        // 檢查log
        $this->assertFalse(file_exists($this->logPath));
    }

    /**
     * 測試監控CashQueue長度，超過長度上限
     */
    public function testMonitorCashQueueLengthExceedLimit()
    {
        $command = new MonitorQueueLengthCommand();
        $reflector = new \ReflectionClass($command);

        $mockOutputInterface = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
            ->setMethods([])
            ->getMock();

        $property = $reflector->getProperty('output');
        $property->setAccessible(true);
        $property->setValue($command, $mockOutputInterface);

        $mockRedis = $this->getMockBuilder('Predis\Client')
            ->setMethods(['llen'])
            ->getMock();

        $mockRedis->method('llen')
            ->willReturn(80000);

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.monitor_queue_length');

        $map = [
            ['durian.italking_operator', 1, $italkingOperator],
            ['logger', 1, $logger],
            ['monolog.handler.monitor_queue_length', 1, $handler],
            ['snc_redis.default_client', 1, $mockRedis]
        ];

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockContainer->method('get')
            ->willReturnMap($map);

        $command->setContainer($mockContainer);

        $method = $reflector->getMethod('monitorQueueLength');
        $method->setAccessible(true);
        $method->invoke($command);

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingCount = $redis->llen('italking_message_queue');
        $this->assertEquals(1, $italkingCount);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('現金明細同步至資料庫有堆積情形，請通知 RD5-帳號研發部值班人員檢查。', $results[0]);
        $this->assertEmpty($results[1]);
    }

    /**
     * 清除log檔
     */
    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        parent::tearDown();
    }
}
