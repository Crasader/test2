<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\MonitorStatCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MonitorStatCommandTest extends WebTestCase
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
        $fileName = 'monitor_stat.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 測試監控統計背景，記憶體使用量沒有超過上限
     */
    public function testMonitorStatMemory()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->runCommand('durian:monitor-stat', ['--memory' => true]);

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingCount = $redis->llen('italking_message_queue');
        $this->assertEquals(0, $italkingCount);

        // 檢查log
        $this->assertFalse(file_exists($this->logPath));
    }

    /**
     * 測試監控統計背景，執行時間沒有逾期
     */
    public function testMonitorStatCheckOverTime()
    {
        $this->runCommand('durian:monitor-stat', ['--over-time' => true]);

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingCount = $redis->llen('italking_message_queue');
        $this->assertEquals(0, $italkingCount);

        // 檢查log
        $this->assertFalse(file_exists($this->logPath));
    }

    /**
     * 測試監控香港時區統計背景，記憶體使用量沒有超過上限
     */
    public function testMonitorStatHkMemory()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->runCommand('durian:monitor-stat', ['--memory' => true, '--hk' => true]);

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingCount = $redis->llen('italking_message_queue');
        $this->assertEquals(0, $italkingCount);

        // 檢查log
        $this->assertFalse(file_exists($this->logPath));
    }

    /**
     * 測試監控統計背景，記憶體使用量超過上限
     */
    public function testMonitorStatMemoryExceedLimit()
    {
        $command = new MonitorStatCommand();
        $reflector = new \ReflectionClass($command);
        $container = $this->getContainer();

        $mockOutputInterface = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
            ->setMethods([])
            ->getMock();
        $mockOutputInterface->expects($this->any())
            ->method('write')
            ->will($this->returnValue('testing'));

        $property = $reflector->getProperty('output');
        $property->setAccessible(true);
        $property->setValue($command, $mockOutputInterface);

        $mockInputInterface = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
            ->setMethods([])
            ->getMock();

        $mockInputInterface->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(false));

        $property = $reflector->getProperty('input');
        $property->setAccessible(true);
        $property->setValue($command, $mockInputInterface);

        $property = $reflector->getProperty('testExecOut');
        $property->setAccessible(true);
        $property->setValue($command, ['1310720 php app/console durian:stat-cash-opcode']);

        $method = $reflector->getMethod('mointorMemory');
        $method->setAccessible(true);

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $logger = $this->getContainer()->get('logger');

        $idGenerator = $this->getContainer()->get('durian.card_entry_id_generator');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['durian.italking_operator', 1, $italkingOperator],
            ['logger', 1, $logger]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $command->setContainer($container);

        $method->invoke($command);

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingMsg = json_decode($redis->lindex('italking_message_queue', -1), true);
        $msg = '統計背景 durian:stat-cash-opcode，記憶體使用量已達 1280 M';
        $this->assertContains($msg, $italkingMsg['message']);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試監控香港時區統計背景，記憶體使用量超過上限
     */
    public function testMonitorStatHkMemoryExceedLimit()
    {
        $command = new MonitorStatCommand();
        $reflector = new \ReflectionClass($command);
        $container = $this->getContainer();

        $mockOutputInterface = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
            ->setMethods([])
            ->getMock();

        $mockOutputInterface->expects($this->any())
            ->method('write')
            ->will($this->returnValue('testing'));

        $property = $reflector->getProperty('output');
        $property->setAccessible(true);
        $property->setValue($command, $mockOutputInterface);

        $mockInputInterface = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
            ->setMethods([])
            ->getMock();

        $mockInputInterface->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue('hk'));

        $property = $reflector->getProperty('input');
        $property->setAccessible(true);
        $property->setValue($command, $mockInputInterface);

        $property = $reflector->getProperty('testExecOut');
        $property->setAccessible(true);
        $property->setValue($command, ['1310720 php app/console durian:stat-cash-opcode --table-name=stat_cash_opcode_hk']);

        $method = $reflector->getMethod('mointorMemory');
        $method->setAccessible(true);

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $logger = $this->getContainer()->get('logger');

        $idGenerator = $this->getContainer()->get('durian.card_entry_id_generator');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['durian.italking_operator', 1, $italkingOperator],
            ['logger', 1, $logger]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $command->setContainer($container);

        $method->invoke($command);

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingMsg = json_decode($redis->lindex('italking_message_queue', -1), true);
        $msg = '統計背景 durian:stat-cash-opcode-hk，記憶體使用量已達 1280 M';
        $this->assertContains($msg, $italkingMsg['message']);
        $this->assertContains('請通知 RD5-帳號研發部值班人員檢查', $italkingMsg['message']);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試監控統計背景，執行時間逾時
     */
    public function testMonitorStatExecuteOverTime()
    {
        $command = new MonitorStatCommand();
        $reflector = new \ReflectionClass($command);
        $container = $this->getContainer();

        $mockOutputInterface = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
            ->setMethods([])
            ->getMock();
        $mockOutputInterface->expects($this->any())
            ->method('write')
            ->will($this->returnValue('testing'));

        $property = $reflector->getProperty('output');
        $property->setAccessible(true);
        $property->setValue($command, $mockOutputInterface);

        $mockInputInterface = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
            ->setMethods([])
            ->getMock();

        $mockInputInterface->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(false));

        $property = $reflector->getProperty('input');
        $property->setAccessible(true);
        $property->setValue($command, $mockInputInterface);

        $property = $reflector->getProperty('testExecOut');
        $property->setAccessible(true);
        $property->setValue($command, ['durian:stat-cash-opcode']);

        $method = $reflector->getMethod('checkOverTime');
        $method->setAccessible(true);

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $logger = $this->getContainer()->get('logger');

        $idGenerator = $this->getContainer()->get('durian.card_entry_id_generator');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['durian.italking_operator', 1, $italkingOperator],
            ['logger', 1, $logger]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $command->setContainer($container);

        $method->invoke($command);

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingMsg = json_decode($redis->lindex('italking_message_queue', -1), true);
        $msg = '統計背景 durian:stat-cash-opcode，執行時間已逾時，請通知 RD5-帳號研發部值班人員檢查';
        $this->assertContains($msg, $italkingMsg['message']);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試監控香港統計背景，執行時間逾時
     */
    public function testMonitorStatExecuteOverTimeWithHkTimeZone()
    {
        $command = new MonitorStatCommand();
        $reflector = new \ReflectionClass($command);
        $container = $this->getContainer();

        $mockOutputInterface = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
            ->setMethods([])
            ->getMock();
        $mockOutputInterface->expects($this->any())
            ->method('write')
            ->will($this->returnValue('testing'));

        $property = $reflector->getProperty('output');
        $property->setAccessible(true);
        $property->setValue($command, $mockOutputInterface);

        $mockInputInterface = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
            ->setMethods([])
            ->getMock();

        $mockInputInterface->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(false));

        $property = $reflector->getProperty('input');
        $property->setAccessible(true);
        $property->setValue($command, $mockInputInterface);

        $property = $reflector->getProperty('testExecOut');
        $property->setAccessible(true);
        $property->setValue($command, ['durian:stat-cash-opcode --table-name=stat_cash_opcode_hk']);

        $method = $reflector->getMethod('checkOverTime');
        $method->setAccessible(true);

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $logger = $this->getContainer()->get('logger');

        $idGenerator = $this->getContainer()->get('durian.card_entry_id_generator');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['durian.italking_operator', 1, $italkingOperator],
            ['logger', 1, $logger]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $command->setContainer($container);

        $method->invoke($command);

        // 檢查italking queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $italkingMsg = json_decode($redis->lindex('italking_message_queue', -1), true);
        $msg = '統計背景 durian:stat-cash-opcode-hk，執行時間已逾時，請通知 RD5-帳號研發部值班人員檢查';
        $this->assertContains($msg, $italkingMsg['message']);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains($msg, $results[0]);
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
