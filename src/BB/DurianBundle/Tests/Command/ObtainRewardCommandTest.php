<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Command\ObtainRewardCommand;

class ObtainRewardCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRewardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRewardEntryData'
        ];

        $this->loadFixtures($classnames, 'share');

        $this->loadFixtures([], 'entry');

        $redis = $this->getContainer()->get('snc_redis.reward');
        $redis->flushdb();
    }

    /**
     * 測試同步抽中紅包資訊
     */
    public function testSyncObtainReward()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');

        // redis queue 放須同步的資料
        $syncData = [
            'entry_id' => 3,
            'user_id' => 7,
            'amount' => 10,
            'at' => '2016-04-01 00:00:00'
        ];

        $redis->lpush('reward_sync_queue', json_encode($syncData));

        // 沒有更新前欄位沒有資料
        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $this->assertEmpty($entry->getUserId());
        $this->assertEmpty($entry->getObtainAt());

        $this->runCommand('durian:obtain-reward', ['--sync' => true]);

        $emShare->refresh($entry);

        // 驗證紅包活動及明細都有更新
        $this->assertEquals(7, $entry->getUserId());
        $this->assertEquals('2016-04-01 00:00:00', $entry->getObtainAt()->format('Y-m-d H:i:s'));
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $this->assertEquals(2, $reward->getObtainQuantity());
        $this->assertEquals(20, $reward->getObtainAmount());

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'sync_obtain_reward.log';
        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $rewardMsg = "UPDATE reward SET obtain_amount = '20', obtain_quantity = '2' WHERE id = '2'";
        $entryMsg = "UPDATE reward_entry SET user_id = '7', obtain_at = '2016-04-01 00:00:00' WHERE id = '3'";
        $this->assertContains($rewardMsg, $results[0]);
        $this->assertContains($entryMsg, $results[1]);
    }

    /**
     * 測試同步抽中紅包資訊，發生例外
     */
    public function testSyncObtainRewardWithException()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');

        $logger = $this->getContainer()->get('logger');
        $sqlLogger = $this->getContainer()->get('durian.logger_sql');
        $handler = $this->getContainer()->get('monolog.handler.sync_obtain_reward');
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        // redis queue 放須同步的資料
        $syncData = [
            'entry_id' => 3,
            'user_id' => 7,
            'amount' => 10,
            'at' => '2016-04-01 00:00:00'
        ];

        $redis->lpush('reward_sync_queue', json_encode($syncData));

        // 沒有更新前欄位沒有資料
        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $this->assertEmpty($entry->getUserId());
        $this->assertEmpty($entry->getObtainAt());

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getConnection', 'rollback'])
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['getConfiguration'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $mockConfig = $this->getMockBuilder('Doctrine\DBAL\Configuration')
            ->disableOriginalConstructor()
            ->setMethods(['setSQLLogger'])
            ->getMock();

        $mockConfig->expects($this->any())
            ->method('setSQLLogger')
            ->willReturn(true);

        $mockConn->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($mockConfig);

        $mockEm->expects($this->any())
            ->method('find')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.reward', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.share_entity_manager', 1, $mockEm],
            ['logger', 1, $logger],
            ['monolog.handler.sync_obtain_reward', 1, $handler],
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.logger_sql', 1, $sqlLogger]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $command = new ObtainRewardCommand();
        $command->setContainer($mockContainer);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--sync' => true]);

        $emShare->refresh($entry);
        $this->assertEmpty($entry->getUserId());
        $this->assertEmpty($entry->getObtainAt());

        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $this->assertEquals(1, $reward->getObtainQuantity());
        $this->assertEquals(10, $reward->getObtainAmount());

        $this->assertEquals(1, $redis->llen('reward_sync_queue'));

        // 檢查italking exception queue
        $redisDefault = $this->getContainer()->get('snc_redis.default');
        $exceptionQueue = $redisDefault->lrange('italking_exception_queue', 0, -1);
        $content = json_decode($exceptionQueue[0], true);
        $msg = '同步紅包明細, 發生例外: Connection timed out';
        $this->assertContains($msg, $content['message']);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'sync_obtain_reward.log';
        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Sync rewardEntry failed, because Connection timed out';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試紅包派彩
     */
    public function testObtainRewardOperation()
    {
        $em = $this->getContainer()->get("doctrine.orm.entity_manager");
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get("doctrine.orm.share_entity_manager");
        $redis = $this->getContainer()->get('snc_redis.reward');

        // 先同步明細才可以派彩
        $syncData = [
            'entry_id' => 3,
            'user_id' => 7,
            'amount' => 10,
            'at' => '2016-04-01 00:00:00'
        ];

        $redis->lpush('reward_sync_queue', json_encode($syncData));

        $this->runCommand('durian:obtain-reward', ['--sync' => true]);

        // 驗證明細已同步
        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $this->assertEquals(7, $entry->getUserId());
        $this->assertEquals('2016-04-01 00:00:00', $entry->getObtainAt()->format('Y-m-d H:i:s'));
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $this->assertEquals(2, $reward->getObtainQuantity());
        $this->assertEquals(20, $reward->getObtainAmount());

        // redis 放 op 資料
        unset($syncData['at']);
        $redis->lpush('reward_op_queue', json_encode($syncData));

        $redisSeq = $this->getContainer()->get('snc_redis.sequence');
        $redisSeq->set('cash_seq', 1000);

        $this->runCommand('durian:obtain-reward', ['--do-op' => true]);

        $emShare->refresh($entry);
        $this->assertNotNull($entry->getPayoffAt());

        // 同步餘額及明細
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 1]);
        $this->runCommand('durian:run-cash-poper');

        $cash = $em->find('BBDurianBundle:Cash', 6);
        $this->assertEquals(1010, $cash->getBalance());
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 1001]);
        $this->assertEquals(1010, $cashEntry->getBalance());
        $this->assertEquals(1158, $cashEntry->getOpcode());
        $this->assertEmpty($cashEntry->getMemo());

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'op_obtain_reward.log';
        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Reward Entry 3 operation successfully';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試紅包派彩，但明細尚未同步
     */
    public function testObtainRewardOperationButNotSync()
    {
        $emShare = $this->getContainer()->get("doctrine.orm.share_entity_manager");
        $redis = $this->getContainer()->get('snc_redis.reward');
        $redisDefault = $this->getContainer()->get('snc_redis.default');

        // redis 放 op 資料
        $opData = [
            'entry_id' => 3,
            'user_id' => 7,
            'amount' => 10
        ];

        $redis->lpush('reward_op_queue', json_encode($opData));

        $this->runCommand('durian:obtain-reward', ['--do-op' => true]);

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $this->assertNull($entry->getPayoffAt());

        // redis queue 有推回且沒有cash op的資料
        $this->assertEquals(1, $redis->llen('reward_op_queue'));
        $this->assertEmpty($redisDefault->keys('*cash*'));

        // 檢查italking exception queue
        $exceptionQueue = $redisDefault->lrange('italking_exception_queue', 0, -1);
        $this->assertEmpty($exceptionQueue);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'op_obtain_reward.log';
        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'RewardEntry operation failed, data: {"entry_id":3,"user_id":7,"amount":10}, because Reward entry not sync';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試紅包派彩，但使用者沒有現金
     */
    public function testObtainRewardOperationButUserHasNoCash()
    {
        $emShare = $this->getContainer()->get("doctrine.orm.share_entity_manager");
        $redis = $this->getContainer()->get('snc_redis.reward');
        $redisDefault = $this->getContainer()->get('snc_redis.default');

        // 先同步明細才可以派彩
        $syncData = [
            'entry_id' => 3,
            'user_id' => 10,
            'amount' => 10,
            'at' => '2016-04-01 00:00:00'
        ];

        $redis->lpush('reward_sync_queue', json_encode($syncData));

        $this->runCommand('durian:obtain-reward', ['--sync' => true]);

        // 驗證明細已同步
        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $this->assertEquals(10, $entry->getUserId());
        $this->assertEquals('2016-04-01 00:00:00', $entry->getObtainAt()->format('Y-m-d H:i:s'));
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $this->assertEquals(2, $reward->getObtainQuantity());
        $this->assertEquals(20, $reward->getObtainAmount());

        // redis 放 op 資料
        unset($syncData['at']);
        $redis->lpush('reward_op_queue', json_encode($syncData));

        $this->runCommand('durian:obtain-reward', ['--do-op' => true]);

        $emShare->refresh($entry);
        $this->assertNull($entry->getPayoffAt());

        // redis queue 有推回且沒有cash op的資料
        $this->assertEquals(1, $redis->llen('reward_op_queue'));
        $this->assertEmpty($redisDefault->keys('*cash*'));

        // 檢查italking exception queue
        $exceptionQueue = $redisDefault->lrange('italking_exception_queue', 0, -1);
        $content = json_decode($exceptionQueue[0], true);
        $msg = '紅包派彩 {"entry_id":3,"user_id":10,"amount":10}, 發生例外: User 10 has no cash';
        $this->assertContains($msg, $content['message']);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'op_obtain_reward.log';
        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'RewardEntry operation failed, data: {"entry_id":3,"user_id":10,"amount":10}, because User 10 has no cash';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試抽中紅包派彩，發生timeout
     */
    public function testSyncObtainRewardWithConnectionTimeout()
    {
        $em = $this->getContainer()->get("doctrine.orm.entity_manager");
        $emShare = $this->getContainer()->get("doctrine.orm.share_entity_manager");
        $redis = $this->getContainer()->get('snc_redis.reward');

        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.op_obtain_reward');
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $opService = $this->getContainer()->get('durian.op');

        // 先同步明細才可以派彩
        $syncData = [
            'entry_id' => 3,
            'user_id' => 9,
            'amount' => 10,
            'at' => '2016-04-01 00:00:00'
        ];

        $redis->lpush('reward_sync_queue', json_encode($syncData));

        $this->runCommand('durian:obtain-reward', ['--sync' => true]);

        // 驗證明細已同步
        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $this->assertEquals(9, $entry->getUserId());
        $this->assertEquals('2016-04-01 00:00:00', $entry->getObtainAt()->format('Y-m-d H:i:s'));
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $this->assertEquals(2, $reward->getObtainQuantity());
        $this->assertEquals(20, $reward->getObtainAmount());

        $emShare->clear();

        // redis 放 op 資料
        unset($syncData['at']);
        $redis->lpush('reward_op_queue', json_encode($syncData));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getConnection', 'rollback', 'flush'])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($em->find('BBDurianBundle:User', 9));

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getConnection', 'rollback', 'flush'])
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->willReturn(true);

        $mockEmShare->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $mockEmShare->expects($this->at(0))
            ->method('find')
            ->willReturn($emShare->find('BBDurianBundle:RewardEntry', 3));

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.reward', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.default_entity_manager', 1, $mockEm],
            ['doctrine.orm.share_entity_manager', 1, $mockEmShare],
            ['logger', 1, $logger],
            ['monolog.handler.op_obtain_reward', 1, $handler],
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.op', 1, $opService]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $command = new ObtainRewardCommand();
        $command->setContainer($mockContainer);

        $application = new Application();
        $application->add($command);

        // 設定cash sequence
        $redisSeq = $this->getContainer()->get('snc_redis.sequence');
        $redisSeq->set('cash_seq', 1000);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--do-op' => true]);

        $emShare->clear();

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $this->assertEmpty($entry->getPayoffAt());

        // 已派彩不會再推回redis
        $this->assertEquals(0, $redis->llen('reward_sync_queue'));

        // 檢查italking exception queue
        $redisDefault = $this->getContainer()->get('snc_redis.default');
        $exceptionQueue = $redisDefault->lrange('italking_exception_queue', 0, -1);
        $content = json_decode($exceptionQueue[0], true);
        $msg = '紅包派彩 {"entry_id":3,"user_id":9,"amount":10}, 發生例外: Connection timed out';
        $this->assertContains($msg, $content['message']);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'op_obtain_reward.log';
        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'RewardEntry operation failed, data: {"entry_id":3,"user_id":9,"amount":10}' .
            ', because Connection timed out';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 清除log檔
     */
    public function tearDown()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'sync_obtain_reward.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'op_obtain_reward.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        parent::tearDown();
    }
}

