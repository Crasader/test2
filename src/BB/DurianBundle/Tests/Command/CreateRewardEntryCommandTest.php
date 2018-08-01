<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Command\CreateRewardEntryCommand;
use BB\DurianBundle\Entity\RewardEntry;

class CreateRewardEntryCommandTest extends WebTestCase
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

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData'
        ];

        $this->loadFixtures($classnames);

        $this->loadFixtures([], 'share');

        $redis = $this->getContainer()->get('snc_redis.reward');
        $redis->flushdb();

        $redisSeq = $this->getContainer()->get('snc_redis.sequence');
        $redisSeq->set('reward_seq', 1000);

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'create_reward_entry.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 測試產生紅包明細，紅包數量較多
     */
    public function testExecuteWithMoreQuantity()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 999,
            'quantity' => 100,
            'min_amount' => 1,
            'max_amount' => 10,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $this->assertFalse($reward->isEntryCreated());

        $this->runCommand('durian:create-reward-entry');

        // 驗證redis 的明細與mysql 相同
        $redisEntries = $redis->smembers("reward_id_1_entry");
        $totalAmount = 0;

        foreach ($redisEntries as $entry) {
            $entryData = json_decode($entry, true);
            $dbEntry = $emShare->find('BBDurianBundle:RewardEntry', $entryData['id']);

            $this->assertEquals($entryData['id'], $dbEntry->getId());
            $this->assertEquals($entryData['amount'], $dbEntry->getAmount());
            $this->assertNull($dbEntry->getUserId());
            $this->assertNull($dbEntry->getPayOffAt());

            $totalAmount += $dbEntry->getAmount();
        }

        $this->assertEquals(999, $totalAmount);
        $this->assertEquals(100, count($redisEntries));
        $this->assertTrue($redis->sismember('reward_available', 1));
        $this->assertEquals(1, $redis->hget('reward_id_1', 'entry_created'));
        $this->assertEquals(0, $redis->hget('reward_id_1', 'obtain_amount'));
        $this->assertEquals(0, $redis->hget('reward_id_1', 'obtain_quantity'));

        // 驗證一定有一包最大值紅包
        $maxEntry = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['amount' => 10]);
        $this->assertNotNull($maxEntry);

        // 檢查TTL 有設定
        $this->assertNotEquals($redis->ttl('reward_id_1'), -1);
        $this->assertNotEquals($redis->ttl('reward_id_1_entry'), -1);
        $this->assertNotEquals($redis->ttl('reward_id_1_attended_user'), -1);

        // mysql 部分
        $emShare->clear();
        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $this->assertTrue($reward->isEntryCreated());

        $entries = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['rewardId' => 1]);
        $this->assertEquals(100, count($entries));

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Reward 1 created entry successfully';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試產生紅包明細，紅包數量較少
     */
    public function testExecuteWithFewQuantity()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $this->assertFalse($reward->isEntryCreated());

        $this->runCommand('durian:create-reward-entry');

        // 驗證redis 的明細與mysql 相同
        $redisEntries = $redis->smembers("reward_id_1_entry");
        $totalAmount = 0;

        foreach ($redisEntries as $entry) {
            $entryData = json_decode($entry, true);
            $dbEntry = $emShare->find('BBDurianBundle:RewardEntry', $entryData['id']);

            $this->assertEquals($entryData['id'], $dbEntry->getId());
            $this->assertEquals($entryData['amount'], $dbEntry->getAmount());
            $this->assertNull($dbEntry->getUserId());
            $this->assertNull($dbEntry->getPayOffAt());

            $totalAmount += $dbEntry->getAmount();
        }

        $this->assertEquals(100, $totalAmount);
        $this->assertEquals(10, count($redisEntries));
        $this->assertTrue($redis->sismember('reward_available', 1));
        $this->assertEquals(1, $redis->hget('reward_id_1', 'entry_created'));
        $this->assertEquals(0, $redis->hget('reward_id_1', 'obtain_amount'));
        $this->assertEquals(0, $redis->hget('reward_id_1', 'obtain_quantity'));

        // 驗證一定有一包最大值紅包
        $maxEntry = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['amount' => 20]);
        $this->assertNotNull($maxEntry);

        // 檢查TTL 有設定
        $this->assertNotEquals($redis->ttl('reward_id_1'), -1);
        $this->assertNotEquals($redis->ttl('reward_id_1_entry'), -1);
        $this->assertNotEquals($redis->ttl('reward_id_1_attended_user'), -1);

        // mysql 部分
        $emShare->clear();
        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $this->assertTrue($reward->isEntryCreated());

        $entries = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['rewardId' => 1]);
        $this->assertEquals(10, count($entries));

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Reward 1 created entry successfully';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試產生紅包明細，只有一包紅包
     */
    public function testExecuteWithOneQuantity()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 10,
            'quantity' => 1,
            'min_amount' => 1,
            'max_amount' => 10,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $this->runCommand('durian:create-reward-entry');

        $redisEntries = $redis->smembers('reward_id_1_entry');
        $emEntries = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['rewardId' => 1]);
        $redisData = json_decode($redisEntries[0],true);

        // 只有一包最大值紅包
        $this->assertEquals(1, count($emEntries));
        $this->assertEquals(1, count($redisEntries));
        $this->assertEquals(10, $emEntries[0]->getAmount());
        $this->assertEquals(10, $redisData['amount']);
        $this->assertEquals($emEntries[0]->getId(), $redisData['id']);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Reward 1 created entry successfully';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試產生紅包明細，但沒有活動需建立明細
     */
    public function testCreateRewardEntryButCreatedListIsEmpty()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');

        $this->runCommand('durian:create-reward-entry');

        $this->assertEmpty($redis->llen('reward_entry_created_queue'));

        // 檢查log
        $this->assertFalse(file_exists($this->logPath));
    }

    /**
     * 測試產生紅包明細，但redis有之前建立失敗的明細
     */
    public function testCreateRewardEntryButRedisHasEntryBefore()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        // 寫入錯誤明細到redis
        $redis->sadd('reward_id_1_entry', 'wrong_data');
        $this->runCommand('durian:create-reward-entry');

        // 驗證redis 的明細與mysql 相同，沒有錯誤的明細資料
        $redisEntries = $redis->smembers("reward_id_1_entry");
        $totalAmount = 0;

        foreach ($redisEntries as $entry) {
            $entryData = json_decode($entry, true);
            $dbEntry = $emShare->find('BBDurianBundle:RewardEntry', $entryData['id']);

            $this->assertEquals($entryData['id'], $dbEntry->getId());
            $this->assertEquals($entryData['amount'], $dbEntry->getAmount());
            $this->assertNull($dbEntry->getUserId());
            $this->assertNull($dbEntry->getPayOffAt());

            $totalAmount += $dbEntry->getAmount();
        }

        $this->assertEquals(100, $totalAmount);
        $this->assertEquals(10, count($redisEntries));
        $this->assertTrue($redis->sismember('reward_available', 1));
        $this->assertEquals(1, $redis->hget('reward_id_1', 'entry_created'));
        $this->assertEquals(0, $redis->hget('reward_id_1', 'obtain_amount'));
        $this->assertEquals(0, $redis->hget('reward_id_1', 'obtain_quantity'));

        // 驗證一定有一包最大值紅包
        $maxEntry = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['amount' => 20]);
        $this->assertNotNull($maxEntry);

        // 檢查TTL 有設定
        $this->assertNotEquals($redis->ttl('reward_id_1'), -1);
        $this->assertNotEquals($redis->ttl('reward_id_1_entry'), -1);
        $this->assertNotEquals($redis->ttl('reward_id_1_attended_user'), -1);

        // mysql 部分
        $emShare->clear();
        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $this->assertTrue($reward->isEntryCreated());

        $entries = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['rewardId' => 1]);
        $this->assertEquals(10, count($entries));

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Reward 1 created entry successfully';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試產生紅包明細，但資料庫有之前建立失敗的明細
     */
    public function testCreateRewardEntryButDbHasEntryBefore()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        // 寫入錯誤明細到mysql
        $entry = new RewardEntry(1, 10);
        $entry->setId(1);
        $emShare->persist($entry);
        $emShare->flush();

        $this->runCommand('durian:create-reward-entry');

        // 驗證redis 的明細與mysql 相同，沒有錯誤的明細資料
        $redisEntries = $redis->smembers("reward_id_1_entry");
        $totalAmount = 0;

        foreach ($redisEntries as $entry) {
            $entryData = json_decode($entry, true);
            $dbEntry = $emShare->find('BBDurianBundle:RewardEntry', $entryData['id']);

            $this->assertEquals($entryData['id'], $dbEntry->getId());
            $this->assertEquals($entryData['amount'], $dbEntry->getAmount());
            $this->assertNull($dbEntry->getUserId());
            $this->assertNull($dbEntry->getPayOffAt());

            $totalAmount += $dbEntry->getAmount();
        }

        $this->assertEquals(100, $totalAmount);
        $this->assertEquals(10, count($redisEntries));
        $this->assertTrue($redis->sismember('reward_available', 1));
        $this->assertEquals(1, $redis->hget('reward_id_1', 'entry_created'));
        $this->assertEquals(0, $redis->hget('reward_id_1', 'obtain_amount'));
        $this->assertEquals(0, $redis->hget('reward_id_1', 'obtain_quantity'));

        // 驗證一定有一包最大值紅包
        $maxEntry = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['amount' => 20]);
        $this->assertNotNull($maxEntry);

        // 檢查TTL 有設定
        $this->assertNotEquals($redis->ttl('reward_id_1'), -1);
        $this->assertNotEquals($redis->ttl('reward_id_1_entry'), -1);
        $this->assertNotEquals($redis->ttl('reward_id_1_attended_user'), -1);

        // mysql 部分
        $emShare->clear();
        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $this->assertTrue($reward->isEntryCreated());

        $entries = $emShare->getRepository('BBDurianBundle:RewardEntry')->findBy(['rewardId' => 1]);
        $this->assertEquals(10, count($entries));

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Reward 1 created entry successfully';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試產生紅包明細，但沒有sequence
     */
    public function testCreateRewardEntryButNoSequenceId()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        // 刪除seq
        $redisSeq = $this->getContainer()->get('snc_redis.sequence');
        $redisSeq->del('reward_seq');

        $this->runCommand('durian:create-reward-entry');

        // redis 部分
        $createdQueue = $redis->lrange('reward_entry_created_queue', 0 , -1);

        $this->assertEquals(0, $redis->hget('reward_id_1', 'entry_created'));
        $this->assertEquals(1, $createdQueue[0]);
        $this->assertEmpty($redis->sismember('reward_available', 1));

        // mysql 部分
        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $this->assertFalse($reward->isEntryCreated());

        $rewardEntry = $emShare->find('BBDurianBundle:RewardEntry', 1);
        $this->assertNull($rewardEntry);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $msg = 'Created rewardList rewardId 1 failed, because Cannot generate reward sequence id';
        $this->assertContains($msg, $results[0]);

        // 檢查italking exception queue
        $redisDefault = $this->getContainer()->get('snc_redis.default_client');
        $exceptionQueue = $redisDefault->lrange('italking_exception_queue', 0, -1);
        $msg = json_decode($exceptionQueue[0], true);
        $this->assertContains('建立紅包明細，發生例外: Cannot generate reward sequence id', $msg['message']);
    }

    /**
     * 測試產生紅包明細，但活動已取消
     */
    public function testCreateRewardEntryButRewardIsCancel()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $client->request('PUT', '/api/reward/1/cancel');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['cancel']);

        $this->runCommand('durian:create-reward-entry');

        $this->assertEmpty($redis->llen('reward_entry_created_queue'));

        // 檢查log
        $this->assertFalse(file_exists($this->logPath));
    }

    /**
     * 測試產生紅包明細，發生例外
     */
    public function testCreateRewardEntryButThrowException()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $redisSeq = $this->getContainer()->get('snc_redis.sequence');
        $client = $this->createClient();

        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.create_reward_entry');
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 10,
            'quantity' => 2,
            'min_amount' => 4,
            'max_amount' => 6,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'persist', 'flush',
                'rollback', 'commit', 'getConnection', 'clear', 'getRepository'])
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->willReturn(true);

        $mockRepo = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(false);

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $reward = $emShare->find('BBDurianBundle:Reward', 1);

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($reward);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.reward', 1, $redis],
            ['snc_redis.sequence', 1, $redisSeq],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.share_entity_manager', 1, $mockEm],
            ['logger', 1, $logger],
            ['monolog.handler.create_reward_entry', 1, $handler],
            ['durian.monitor.background', 1, $bgMonitor]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $command = new CreateRewardEntryCommand();
        $command->setContainer($mockContainer);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        // redis 部分
        $createdQueue = $redis->lrange('reward_entry_created_queue', 0 , -1);

        $this->assertEquals(0, $redis->hget('reward_id_1', 'entry_created'));
        $this->assertEquals(1, $createdQueue[0]);
        $this->assertEmpty($redis->sismember('reward_available', 1));

        // mysql 部分
        $emShare->refresh($reward);
        $this->assertFalse($reward->isEntryCreated());

        $rewardEntry = $emShare->find('BBDurianBundle:RewardEntry', 1);
        $this->assertNull($rewardEntry);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $msg = 'Created rewardList rewardId 1 failed, because Connection timed out';
        $this->assertContains($msg, $results[0]);

        // 檢查italking exception queue
        $redisDefault = $this->getContainer()->get('snc_redis.default_client');
        $exceptionQueue = $redisDefault->lrange('italking_exception_queue', 0, -1);
        $msg = json_decode($exceptionQueue[0], true);
        $this->assertContains('建立紅包明細，發生例外: Connection timed out', $msg['message']);
    }

    /**
     * 刪除產生的log檔
     */
    public function tearDown() {
        parent::tearDown();

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'test.create_reward_entry.log';
        $filePath = $logsDir . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
