<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\SyncLoginLogCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncLoginLogCommandTest extends WebTestCase
{
    /**
     * 初始化
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([]);
        $this->loadFixtures([], 'his');

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $this->logPath = $logsDir . '/test/sync_login_log.log';

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->getContainer()->get('snc_redis.default');
    }

    /**
     * 測試同步login_log
     */
    public function testSyncLoginLog()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getRedis();

        $log = [
            'id' => 1,
            'user_id' => 1,
            'username' => 'lobo',
            'role' => 1,
            'sub' => true,
            'domain' => 1,
            'ip' => ip2long('127.0.0.1'),
            'ipv6' => '',
            'host' =>'',
            'at' => '2016-11-05 00:00:00',
            'result' => 3,
            'session_id' => '',
            'language' => '',
            'client_os' => '',
            'client_browser' => '',
            'ingress' => null,
            'proxy1' => null,
            'proxy2' => null,
            'proxy3' => null,
            'proxy4' => null,
            'country' => null,
            'city' => null,
            'entrance' => 3,
            'is_otp' => false,
            'is_slide' => false,
            'test' => true
        ];

        // 放在佇列
        $redis->lpush('login_log_queue', json_encode($log));

        $log['id'] = 2;
        $log['result'] = 13;

        // 放在重試佇列
        $redis->lpush('login_log_queue_retry', json_encode($log));

        // 同步
        $this->runCommand('durian:sync-login-log', ['queue' => 'default']);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals(3, count($results));

        $log1 = $em->find('BBDurianBundle:LoginLog', 1);
        $this->assertEquals(1, $log1->getId());
        $this->assertEquals(1, $log1->getUserId());
        $this->assertEquals('lobo', $log1->getUsername());
        $this->assertEquals(1, $log1->getRole());
        $this->assertTrue($log1->isSub());
        $this->assertEquals(1, $log1->getDomain());
        $this->assertEquals('127.0.0.1', $log1->getIp());
        $this->assertEquals('', $log1->getIpv6());
        $this->assertEquals('', $log1->getHost());
        $this->assertEquals('2016-11-05 00:00:00', $log1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(3, $log1->getResult());
        $this->assertEquals('', $log1->getSessionId());
        $this->assertEquals('', $log1->getLanguage());
        $this->assertEquals('', $log1->getClientOs());
        $this->assertEquals('', $log1->getClientBrowser());
        $this->assertNull($log1->getIngress());
        $this->assertNull($log1->getProxy1());
        $this->assertNull($log1->getProxy2());
        $this->assertNull($log1->getProxy3());
        $this->assertNull($log1->getProxy4());
        $this->assertNull($log1->getCountry());
        $this->assertNull($log1->getCity());
        $this->assertEquals(3, $log1->getEntrance());
        $this->assertFalse($log1->isOtp());
        $this->assertFalse($log1->isSlide());
        $this->assertTrue($log1->isTest());

        $log2 = $em->find('BBDurianBundle:LoginLog', 2);
        $this->assertEquals(2, $log2->getId());
        $this->assertEquals(1, $log2->getUserId());
        $this->assertEquals('lobo', $log2->getUsername());
        $this->assertEquals(1, $log2->getRole());
        $this->assertTrue($log2->isSub());
        $this->assertEquals(1, $log2->getDomain());
        $this->assertEquals('127.0.0.1', $log2->getIp());
        $this->assertEquals('', $log2->getIpv6());
        $this->assertEquals('', $log2->getHost());
        $this->assertEquals('2016-11-05 00:00:00', $log2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(13, $log2->getResult());
        $this->assertEquals('', $log2->getSessionId());
        $this->assertEquals('', $log2->getLanguage());
        $this->assertEquals('', $log2->getClientOs());
        $this->assertEquals('', $log2->getClientBrowser());
        $this->assertNull($log2->getIngress());
        $this->assertNull($log2->getProxy1());
        $this->assertNull($log2->getProxy2());
        $this->assertNull($log2->getProxy3());
        $this->assertNull($log2->getProxy4());
        $this->assertNull($log2->getCountry());
        $this->assertNull($log2->getCity());
        $this->assertEquals(3, $log2->getEntrance());
        $this->assertFalse($log2->isOtp());
        $this->assertFalse($log2->isSlide());
        $this->assertTrue($log2->isTest());
    }

    /**
     * 測試同步login_log失敗
     */
    public function testSyncLoginLogButInsertFail()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getRedis();

        $log = [
            'id' => 1,
            'user_id' => 1,
            'username' => 'lobo',
            'role' => 1,
            'domain' => 1,
            'ip' => ip2long('127.0.0.1'),
            'ipv6' => '',
            'host' =>'',
            'at' => '2016-11-05 00:00:00',
            'result' => 3,
            'session_id' => '',
            'language' => '',
            'client_os' => '',
            'client_browser' => '',
            'ingress' => null,
            'proxy1' => null,
            'proxy2' => null,
            'proxy3' => null,
            'proxy4' => null,
            'country' => null,
            'city' => null,
            'entrance' => 3,
            'is_otp' => false,
            'is_slide' => false,
            'test' => true
        ];

        // 重複放在佇列
        $redis->lpush('login_log_queue', json_encode($log));

        $log['user_id'] = 2;
        $redis->lpush('login_log_queue', json_encode($log));

        $this->assertEquals(2, $redis->llen('login_log_queue'));

        $this->runCommand('durian:sync-login-log', ['queue' => 'default']);

        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(2, $redis->llen('login_log_queue_retry'));

        // 執行超過 10 次，會放進失敗佇列
        for ($i = 0; $i < 10; $i++) {
            $this->runCommand('durian:sync-login-log', ['queue' => 'default']);
        }

        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(0, $redis->llen('login_log_queue_retry'));
        $this->assertEquals(2, $redis->llen('login_log_queue_failed'));

        // 重新執行失敗佇列
        $redis->del('login_log_queue_failed');
        $redis->lpush('login_log_queue_failed', json_encode($log));

        $parameters = [
            'queue' => 'default',
            '--recover-fail' => true
        ];
        $this->runCommand('durian:sync-login-log', $parameters);

        $this->assertEquals(0, $redis->llen('login_log_queue_failed'));

        $log2 = $em->find('BBDurianBundle:LoginLog', 1);
        $this->assertEquals(1, $log2->getId());
        $this->assertEquals(2, $log2->getUserId());
        $this->assertEquals('lobo', $log2->getUsername());
        $this->assertEquals(1, $log2->getRole());
        $this->assertEquals(1, $log2->getDomain());
        $this->assertEquals('127.0.0.1', $log2->getIp());
        $this->assertEquals('', $log2->getIpv6());
        $this->assertEquals('', $log2->getHost());
        $this->assertEquals('2016-11-05 00:00:00', $log2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(3, $log2->getResult());
        $this->assertEquals('', $log2->getSessionId());
        $this->assertEquals('', $log2->getLanguage());
        $this->assertEquals('', $log2->getClientOs());
        $this->assertEquals('', $log2->getClientBrowser());
        $this->assertNull($log2->getIngress());
        $this->assertNull($log2->getProxy1());
        $this->assertNull($log2->getProxy2());
        $this->assertNull($log2->getProxy3());
        $this->assertNull($log2->getProxy4());
        $this->assertNull($log2->getCountry());
        $this->assertNull($log2->getCity());
        $this->assertEquals(3, $log2->getEntrance());
        $this->assertFalse($log2->isOtp());
        $this->assertFalse($log2->isSlide());
        $this->assertTrue($log2->isTest());
    }

    /**
     * 測試同步login_log但筆數與執行結果不同
     */
    public function testSyncLoginLogButCountDifferentFromResult()
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $sqlLogger = $this->getContainer()->get('durian.logger_sql');
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.sync_login_log');
        $redis = $this->getRedis();

        $mockConfig = $this->getMockBuilder('Doctrine\DBAL\Configuration')
            ->disableOriginalConstructor()
            ->setMethods(['setSQLLogger'])
            ->getMock();

        $mockConfig->expects($this->any())
            ->method('setSQLLogger')
            ->willReturn(true);

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['getConfiguration', 'executeUpdate'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($mockConfig);

        $mockConn->expects($this->any())
            ->method('executeUpdate')
            ->will($this->returnValue(0));

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.logger_sql', 1, $sqlLogger],
            ['logger', 1, $logger],
            ['monolog.handler.sync_login_log', 1, $handler],
            ['snc_redis.default', 1, $redis],
            ['doctrine.orm.his_entity_manager', 1, $mockEm]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValue('test'));

        $log = [
            'id' => 1,
            'user_id' => 1,
            'username' => 'lobo',
            'role' => 1,
            'domain' => 1,
            'ip' => ip2long('127.0.0.1'),
            'ipv6' => '',
            'host' =>'',
            'at' => '2016-11-05 00:00:00',
            'result' => 3,
            'session_id' => '',
            'language' => '',
            'client_os' => '',
            'client_browser' => '',
            'ingress' => null,
            'proxy1' => null,
            'proxy2' => null,
            'proxy3' => null,
            'proxy4' => null,
            'country' => null,
            'city' => null,
            'entrance' => 3,
            'is_otp' => false,
            'is_slide' => false,
            'test' => true
        ];

        // 放在佇列
        $redis->lpush('login_log_queue', json_encode($log));

        $log['id'] = 2;
        $log['result'] = 13;

        // 放在重試佇列
        $redis->lpush('login_log_queue_retry', json_encode($log));

        $application = new Application();
        $command = new SyncLoginLogCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-login-log');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'queue' => 'default'
        ]);

        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(2, $redis->llen('login_log_queue_retry'));
    }

    /**
     * 測試同步login_log但佇列為空
     */
    public function testSyncLoginLogWithNullQueue()
    {
        $redis = $this->getRedis();

        // 放在重試佇列
        $redis->lpush('login_log_queue_retry', null);

        $this->runCommand('durian:sync-login-log', ['queue' => 'default']);

        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(0, $redis->llen('login_log_queue_retry'));
        $this->assertEquals(0, $redis->llen('login_log_queue_failed'));

        // 放在失敗佇列
        $redis->lpush('login_log_queue_failed', null);

        $parameters = [
            'queue' => 'default',
            '--recover-fail' => true
        ];
        $this->runCommand('durian:sync-login-log', $parameters);

        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(0, $redis->llen('login_log_queue_retry'));
        $this->assertEquals(0, $redis->llen('login_log_queue_failed'));
    }

    /**
     * 測試同步login_log_mobile
     */
    public function testSyncLoginLogMobile()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getRedis();

        $log = [
            'login_log_id' => 1,
            'name' => null,
            'brand' => 'apple',
            'model' => 'iPhone 5'
        ];

        // 放在佇列
        $redis->lpush('login_log_mobile_queue', json_encode($log));

        $log['login_log_id'] = 2;
        $log['model'] = 'iPhone 6s Plus';

        // 放在重試佇列
        $redis->lpush('login_log_mobile_queue_retry', json_encode($log));

        // 同步
        $this->runCommand('durian:sync-login-log', ['queue' => 'mobile']);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals(3, count($results));

        $log1 = $em->find('BBDurianBundle:LoginLogMobile', 1);

        $this->assertEquals(1, $log1->getLoginLogId());
        $this->assertNull($log1->getName());
        $this->assertEquals('apple', $log1->getBrand());
        $this->assertEquals('iPhone 5', $log1->getModel());

        $log2 = $em->find('BBDurianBundle:LoginLogMobile', 2);
        $this->assertEquals(2, $log2->getLoginLogId());
        $this->assertNull($log2->getName());
        $this->assertEquals('apple', $log2->getBrand());
        $this->assertEquals('iPhone 6s Plus', $log2->getModel());
    }

    /**
     * 測試同步login_log_mobile失敗
     */
    public function testSyncLoginLogMobileButInsertFail()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getRedis();

        $log = [
            'login_log_id' => 1,
            'name' => null,
            'brand' => 'apple',
            'model' => 'iPhone 5'
        ];

        // 重複放在佇列
        $redis->lpush('login_log_mobile_queue', json_encode($log));

        $log['model'] = 'iPhone 6s Plus';
        $redis->lpush('login_log_mobile_queue', json_encode($log));

        $this->assertEquals(2, $redis->llen('login_log_mobile_queue'));

        $this->runCommand('durian:sync-login-log', ['queue' => 'mobile']);

        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
        $this->assertEquals(2, $redis->llen('login_log_mobile_queue_retry'));

        // 執行超過 10 次，會放進失敗佇列
        for ($i = 0; $i < 10; $i++) {
            $this->runCommand('durian:sync-login-log', ['queue' => 'mobile']);
        }

        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue_retry'));
        $this->assertEquals(2, $redis->llen('login_log_mobile_queue_failed'));

        // 重新執行失敗佇列
        $redis->del('login_log_mobile_queue_failed');
        $redis->lpush('login_log_mobile_queue_failed', json_encode($log));

        $parameters = [
            'queue' => 'mobile',
            '--recover-fail' => true
        ];
        $this->runCommand('durian:sync-login-log', $parameters);

        $this->assertEquals(0, $redis->llen('login_log_mobile_queue_failed'));

        $log2 = $em->find('BBDurianBundle:LoginLogMobile', 1);
        $this->assertEquals(1, $log2->getLoginLogId());
        $this->assertNull($log2->getName());
        $this->assertEquals('apple', $log2->getBrand());
        $this->assertEquals('iPhone 6s Plus', $log2->getModel());
    }

    /**
     * 測試同步login_log_mobile但筆數與執行結果不同
     */
    public function testSyncLoginLogMobileButCountDifferentFromResult()
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $sqlLogger = $this->getContainer()->get('durian.logger_sql');
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.sync_login_log');
        $redis = $this->getRedis();

        $mockConfig = $this->getMockBuilder('Doctrine\DBAL\Configuration')
            ->disableOriginalConstructor()
            ->setMethods(['setSQLLogger'])
            ->getMock();

        $mockConfig->expects($this->any())
            ->method('setSQLLogger')
            ->willReturn(true);

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['getConfiguration', 'executeUpdate'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($mockConfig);

        $mockConn->expects($this->any())
            ->method('executeUpdate')
            ->will($this->returnValue(0));

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.logger_sql', 1, $sqlLogger],
            ['logger', 1, $logger],
            ['monolog.handler.sync_login_log', 1, $handler],
            ['snc_redis.default', 1, $redis],
            ['doctrine.orm.his_entity_manager', 1, $mockEm]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValue('test'));

        $log = [
            'login_log_id' => 1,
            'name' => null,
            'brand' => 'apple',
            'model' => 'iPhone 5'
        ];

        // 放在佇列
        $redis->lpush('login_log_mobile_queue', json_encode($log));

        $log['login_log_id'] = 2;
        $log['model'] = 'iPhone 6s Plus';

        // 放在重試佇列
        $redis->lpush('login_log_mobile_queue_retry', json_encode($log));

        $application = new Application();
        $command = new SyncLoginLogCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-login-log');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'queue' => 'mobile'
        ]);

        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
        $this->assertEquals(2, $redis->llen('login_log_mobile_queue_retry'));
    }

    /**
     * 測試同步login_log_mobile但佇列為空
     */
    public function testSyncLoginLogMobileWithNullQueue()
    {
        $redis = $this->getRedis();

        // 放在重試佇列
        $redis->lpush('login_log_mobile_queue_retry', null);

        $this->runCommand('durian:sync-login-log', ['queue' => 'mobile']);

        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue_retry'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue_failed'));

        // 放在失敗佇列
        $redis->lpush('login_log_mobile_queue_failed', null);

        $parameters = [
            'queue' => 'mobile',
            '--recover-fail' => true
        ];
        $this->runCommand('durian:sync-login-log', $parameters);

        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue_retry'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue_failed'));
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
