<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class SyncCreditCommandTest extends WebTestCase
{
    /**
     * 在 Redis 會使用的 Keys
     *
     * @var array
     */
    protected $keys = [
        'creditQueue' => 'credit_queue',        // 更新信用額度資料 (List) (每筆資料放 JSON)
        'periodQueue' => 'credit_period_queue', // 等待同步之累積金額佇列 (List) (每筆資料放 JSON)
        'entryQueue'  => 'credit_entry_queue'   // 交易明細佇列 (List) (每筆資料放 JSON)
    ];

    /**
     * 初始化
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->getContainer()->get("snc_redis.default");
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = "default")
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 測試同步信用額度
     */
    public function testSyncCredit()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $queueKey = $this->keys['creditQueue'];

        $credit = [
            'user_id' => 8,
            'group_num' => 1,
            'line' => 7000,
            'total_line' => 1000,
            'enable' => false,
            'version' => 10
        ];
        $redis->lpush($queueKey, json_encode($credit));

        $credit = [
            'user_id' => 8,
            'group_num' => 2,
            'line' => 6000,
            'total_line' => 20,
            'enable' => true,
            'version' => 23
        ];
        $redis->lpush($queueKey . '_retry', json_encode($credit));

        $credit = [
            'user_id' => 7,
            'group_num' => 1,
            'line' => 9000,
            'total_line' => 7000,
            'enable' => true,
            'version' => 32
        ];
        $jsonCredit = json_encode($credit);
        $redis->lpush($queueKey . '_failed', $jsonCredit);
        $redis->hset($queueKey . '_count', $jsonCredit, 10);

        // 檢查原資料
        $repo = $em->getRepository('BBDurianBundle:Credit');

        $credit = $repo->findOneBy(['user' => 8, 'groupNum' => 1]);
        $this->assertEquals(5000, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());
        $this->assertTrue($credit->isEnable());
        $this->assertEquals(1, $credit->getVersion());

        $credit = $repo->findOneBy(['user' => 8, 'groupNum' => 2]);
        $this->assertEquals(3000, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());
        $this->assertEquals(1, $credit->getVersion());

        $credit = $repo->findOneBy(['user' => 7, 'groupNum' => 1]);
        $this->assertEquals(10000, $credit->getLine());
        $this->assertEquals(5000, $credit->getTotalLine());
        $this->assertEquals(1, $credit->getVersion());

        $em->clear();

        $cmdParams = ['--credit' => 1];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $credit = $repo->findOneBy(['user' => 8, 'groupNum' => 1]);
        $this->assertEquals(7000, $credit->getLine());
        $this->assertEquals(1000, $credit->getTotalLine());
        $this->assertFalse($credit->isEnable());
        $this->assertEquals(10, $credit->getVersion());

        $credit = $repo->findOneBy(['user' => 8, 'groupNum' => 2]);
        $this->assertEquals(6000, $credit->getLine());
        $this->assertEquals(20, $credit->getTotalLine());
        $this->assertEquals(23, $credit->getVersion());

        $cmdParams = [
            '--credit' => 1,
            '--recover-fail' => 1
        ];
         $this->runCommand('durian:sync-credit', $cmdParams);

        $credit = $repo->findOneBy(['user' => 7, 'groupNum' => 1]);
        $this->assertEquals(9000, $credit->getLine());
        $this->assertEquals(7000, $credit->getTotalLine());
        $this->assertEquals(32, $credit->getVersion());
    }

    /**
     * 測試同步信用額度，但版本號較小，不會更新
     */
    public function testSyncCreditButVersionIsLowerThanOriginal()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $queueKey = $this->keys['creditQueue'];

        $credit = [
            'user_id' => 8,
            'group_num' => 1,
            'line' => 7000,
            'total_line' => 1000,
            'enable' => false,
            'version' => 10
        ];
        $redis->lpush($queueKey, json_encode($credit));

        $cmdParams = ['--credit' => 1];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $repo = $em->getRepository('BBDurianBundle:Credit');

        $credit = $repo->findOneBy(['user' => 8, 'groupNum' => 1]);
        $this->assertEquals(7000, $credit->getLine());
        $this->assertEquals(1000, $credit->getTotalLine());
        $this->assertFalse($credit->isEnable());
        $this->assertEquals(10, $credit->getVersion());
        $em->clear();

        // 版本較小
        $credit = [
            'user_id' => 8,
            'group_num' => 1,
            'line' => 6000,
            'total_line' => 20,
            'enable' => true,
            'version' => 5
        ];
        $redis->lpush($queueKey, json_encode($credit));

        $cmdParams = ['--credit' => 1];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $credit = $repo->findOneBy(['user' => 8, 'groupNum' => 1]);
        $this->assertEquals(7000, $credit->getLine());
        $this->assertEquals(1000, $credit->getTotalLine());
        $this->assertFalse($credit->isEnable());
        $this->assertEquals(10, $credit->getVersion());
    }

    /**
     * 測試同步交易明細
     */
    public function testSyncEntry()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $queueKey = $this->keys['entryQueue'];

        $entry = [
            'credit_id' => 5,
            'user_id' => 8,
            'group_num' => 1,
            'opcode' => 1001,
            'at' => '20130102030405',
            'period_at' => '2013-01-02 00:00:00',
            'amount' => -100,
            'balance' => 1000,
            'line' => 7000,
            'total_line' => 1000,
            'ref_id' => 12345,
            'memo' => '我是備註',
            'credit_version' => 1
        ];
        $redis->lpush($queueKey, json_encode($entry));

        $entry = [
            'credit_id' => 5,
            'user_id' => 8,
            'group_num' => 1,
            'opcode' => 1002,
            'at' => '20130102040405',
            'period_at' => '2013-01-02 00:00:00',
            'amount' => -200,
            'balance' => 800,
            'line' => 7000,
            'total_line' => 1000,
            'ref_id' => 54321,
            'memo' => '我是備註2',
            'credit_version' => 2
        ];
        $redis->lpush($queueKey . '_retry', json_encode($entry));

        $entry = [
            'credit_id' => 4,
            'user_id' => 7,
            'group_num' => 2,
            'opcode' => 1003,
            'at' => '20130102050405',
            'period_at' => '2013-01-02 00:00:00',
            'amount' => -100,
            'balance' => 9900,
            'line' => 6000,
            'total_line' => 2000,
            'ref_id' => 56789,
            'memo' => '我是備註3',
            'credit_version' => 3
        ];
        $jsonEntry = json_encode($entry);
        $redis->lpush($queueKey . '_failed', $jsonEntry);
        $redis->hset($queueKey . '_count', $jsonEntry, 10);

        // 檢查原資料
        $repo = $em->getRepository('BBDurianBundle:CreditEntry');

        $entries = $repo->findBy(['id' => [1,2,3]]);
        $this->assertCount(0, $entries);

        $em->clear();

        $cmdParams = ['--entry' => 1];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $entry = $em->find('BBDurianBundle:CreditEntry', 1);
        $this->assertEquals(5, $entry->getCreditId());
        $this->assertEquals(8, $entry->getUserId());
        $this->assertEquals(1, $entry->getGroupNum());
        $this->assertEquals(1002, $entry->getOpcode());
        $this->assertEquals('20130102040405', $entry->getAt());
        $this->assertEquals('2013-01-02 00:00:00', $entry->getPeriodAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(-200, $entry->getAmount());
        $this->assertEquals(800, $entry->getBalance());
        $this->assertEquals(7000, $entry->getLine());
        $this->assertEquals(1000, $entry->getTotalLine());
        $this->assertEquals(54321, $entry->getRefId());
        $this->assertEquals('我是備註2', $entry->getMemo());

        $entry = $em->find('BBDurianBundle:CreditEntry', 2);
        $this->assertEquals(5, $entry->getCreditId());
        $this->assertEquals(8, $entry->getUserId());
        $this->assertEquals(1, $entry->getGroupNum());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals('20130102030405', $entry->getAt());
        $this->assertEquals('2013-01-02 00:00:00', $entry->getPeriodAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(1000, $entry->getBalance());
        $this->assertEquals(7000, $entry->getLine());
        $this->assertEquals(1000, $entry->getTotalLine());
        $this->assertEquals(12345, $entry->getRefId());
        $this->assertEquals('我是備註', $entry->getMemo());

        $cmdParams = [
            '--entry' => 1,
            '--recover-fail' => 1
        ];
         $this->runCommand('durian:sync-credit', $cmdParams);

        $entry = $em->find('BBDurianBundle:CreditEntry', 3);
        $this->assertEquals(4, $entry->getCreditId());
        $this->assertEquals(7, $entry->getUserId());
        $this->assertEquals(2, $entry->getGroupNum());
        $this->assertEquals(1003, $entry->getOpcode());
        $this->assertEquals('20130102050405', $entry->getAt());
        $this->assertEquals('2013-01-02 00:00:00', $entry->getPeriodAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(9900, $entry->getBalance());
        $this->assertEquals(6000, $entry->getLine());
        $this->assertEquals(2000, $entry->getTotalLine());
        $this->assertEquals(56789, $entry->getRefId());
        $this->assertEquals('我是備註3', $entry->getMemo());

        // 新增明細時會更新交易時間
        $credit = $em->find('BBDurianBundle:Credit', 5);
        $this->assertEquals(20130102030405, $credit->getLastEntryAt());
        $credit = $em->find('BBDurianBundle:Credit', 4);
        $this->assertEquals(20130102050405, $credit->getLastEntryAt());
    }

    /**
     * 測試同步累積金額
     */
    public function testSyncPeriod()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $queueKey = $this->keys['periodQueue'];

        $period = [
            'credit_id' => 5,
            'user_id'   => 8,
            'group_num' => 1,
            'amount'    => 1000,
            'at'        => '2013-01-02 00:00:00',
            'version'   => 2
        ];
        $redis->lpush($queueKey, json_encode($period));

        $period = [
            'credit_id' => 3,
            'user_id'   => 7,
            'group_num' => 1,
            'amount'    => 5000,
            'at'        => '2013-03-02 00:00:00',
            'version'   => 12
        ];
        $redis->lpush($queueKey . '_retry', json_encode($period));


        $cron = \Cron\CronExpression::factory('@daily'); //每天午夜
        $at = $cron->getPreviousRunDate(new \DateTime, 0, true);

        $period = [
            'credit_id' => 6,
            'user_id'   => 8,
            'group_num' => 2,
            'amount'    => 5600,
            'at'        => $at->format('Y-m-d H:i:s'),
            'version'   => 9
        ];

        $jsonCredit = json_encode($period);
        $redis->lpush($queueKey . '_failed', $jsonCredit);
        $redis->hset($queueKey . '_count', $jsonCredit, 10);

        // 檢查原資料
        $repo = $em->getRepository('BBDurianBundle:CreditPeriod');

        $criteria = [
            'userId' => 8,
            'groupNum' => 1,
            'at' => new \DateTime('2013-01-02 00:00:00')
        ];
        $period = $repo->findOneBy($criteria);
        $this->assertNull($period);

        $criteria = [
            'userId' => 7,
            'groupNum' => 1,
            'at' => new \DateTime('2013-03-02 00:00:00')
        ];
        $period = $repo->findOneBy($criteria);
        $this->assertNull($period);

        $period = $repo->findOneBy(['userId' => 8, 'groupNum' => 2, 'at' => $at]);
        $this->assertNull($period);

        $em->clear();

        $cmdParams = ['--period' => 1];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $criteria = [
            'userId' => 8,
            'groupNum' => 1,
            'at' => new \DateTime('2013-01-02 00:00:00')
        ];
        $period = $repo->findOneBy($criteria);
        $this->assertEquals(1000, $period->getAmount());
        $this->assertEquals(2, $period->getVersion());

        $criteria = [
            'userId' => 7,
            'groupNum' => 1,
            'at' => new \DateTime('2013-03-02 00:00:00')
        ];
        $period = $repo->findOneBy($criteria);
        $this->assertEquals(5000, $period->getAmount());
        $this->assertEquals(12, $period->getVersion());

        $cmdParams = [
            '--period' => 1,
            '--recover-fail' => 1
        ];
         $this->runCommand('durian:sync-credit', $cmdParams);

        $period = $repo->findOneBy(['userId' => 8, 'groupNum' => 2, 'at' => $at]);
        $this->assertEquals(5600, $period->getAmount());
        $this->assertEquals(9, $period->getVersion());
    }

    /**
     * 測試同步累積金額, 但是版本號較小，不會更新
     */
    public function testSyncBalanceButVersionIsLowerThanOriginal()
    {
        $redis = $this->getRedis();
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CreditPeriod');

        $queueKey = $this->keys['periodQueue'];

        $periodArr = [
            'credit_id' => 5,
            'user_id'   => 8,
            'group_num' => 1,
            'amount'    => 1000,
            'at'        => '2013-01-02 00:00:00',
            'version'   => 13
        ];
        $redis->lpush($queueKey, json_encode($periodArr));

        $cmdParams = ['--period' => 1];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $criteria = [
            'userId' => 8,
            'groupNum' => 1,
            'at' => new \DateTime('2013-01-02 00:00:00')
        ];
        $period = $repo->findOneBy($criteria);
        $this->assertEquals(1000, $period->getAmount());
        $this->assertEquals(13, $period->getVersion());

        // 版號較小，不會更新
        $periodArr = [
            'credit_id' => 3,
            'user_id'   => 8,
            'group_num' => 1,
            'amount'    => 5000,
            'at'        => '2013-03-02 00:00:00',
            'version'   => 2
        ];
        $redis->lpush($queueKey, json_encode($periodArr));

        $cmdParams = ['--period' => 1];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $criteria = [
            'userId' => 8,
            'groupNum' => 1,
            'at' => new \DateTime('2013-01-02 00:00:00')
        ];
        $period = $repo->findOneBy($criteria);
        $this->assertEquals(1000, $period->getAmount());
        $this->assertEquals(13, $period->getVersion());
    }

    /**
     * 測試同步交易明細失敗會放到失敗佇列
     */
    public function testSyncEntryWriteFail()
    {
        $redis = $this->getRedis();
        $queueKey = $this->keys['entryQueue'];

        // 資料不完整
        $entry = [
            'credit_id' => 5,
            'group_num' => 1,
            'opcode' => 1001,
            'at' => '20130102030405',
            'period_at' => '2013-01-02 00:00:00',
            'amount' => -100,
            'balance' => 1000,
            'line' => 7000,
            'total_line' => 1000,
            'ref_id' => 12345,
            'memo' => '我是備註'
        ];
        $entryJson = json_encode($entry);
        $redis->lpush($queueKey, $entryJson);

        $cmdParams = ['--entry' => 1];
        $output = $this->runCommand('durian:sync-credit', $cmdParams);
        $results = explode(PHP_EOL, $output);

        //驗證output有時間
        $time = date('Y-m-d');
        $this->assertContains($time, $results[2]);

        // 第一次出錯
        $this->assertFalse($redis->exists($queueKey));
        $this->assertEquals(1, $redis->llen($queueKey . '_retry'));
        $this->assertEquals($entryJson, $redis->lindex($queueKey . '_retry', 0));
        $this->assertEquals(1, $redis->hget($queueKey . '_retry_count', $entryJson));

        for ($i = 0; $i < 9; $i++) {
            $cmdParams = ['--entry' => 1];
            $this->runCommand('durian:sync-credit', $cmdParams);
        }

        // 累積十次錯誤，會儲存到 failed queue
        $this->assertFalse($redis->exists($queueKey));
        $this->assertFalse($redis->exists($queueKey . '_retry'));
        $this->assertFalse($redis->exists($queueKey . '_retry_count'));
        $this->assertEquals(1, $redis->llen($queueKey . '_failed'));
        $this->assertEquals($entryJson, $redis->lindex($queueKey . '_failed', 0));
    }

    /**
     * 測試同步時queue為null
     */
    public function testSyncButNullQueue()
    {
        $redis = $this->getRedis();

        foreach ($this->keys as $queueKey) {
            $redis->lpush($queueKey, json_encode(null));
        }

        $cmdParams = [
            '--credit' => 1,
            '--entry'  => 1,
            '--period' => 1
        ];
        $output = $this->runCommand('durian:sync-credit', $cmdParams);

        // 檢查未噴錯retry queue應為空
        $this->assertEquals(0, $redis->llen('credit_entry_queue_retry'));

        // 檢查無錯誤訊息
        $this->assertEmpty($output);
    }

    /**
     * 清除log檔
     */
    public function tearDown()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test.sync_credit.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test.sync_credit_entry.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        parent::tearDown();
    }
}
