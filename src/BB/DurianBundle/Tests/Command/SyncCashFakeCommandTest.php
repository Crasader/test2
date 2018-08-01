<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\SyncCashFakeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncCashFakeCommandTest extends WebTestCase
{
    /**
     * 在 Redis 會使用的 Keys
     *
     * @var Array
     */
    protected $keys = [
        'balance' => 'cash_fake_balance', // 快開餘額 (Hash)
        'balanceQueue' => 'cash_fake_balance_queue', // 等待同步之餘額佇列 (List) (每筆資料放 JSON)
        'entryQueue' => 'cash_fake_entry_queue', // 明細佇列 (List) (每筆資料放 JSON)
        'transferQueue' => 'cash_fake_transfer_queue', // 轉帳佇列 (List) (每筆資料放 JSON)
        'operatorQueue' => 'cash_fake_operator_queue', // 操作者佇列 (List) (每筆資料放 JSON)
        'historyQueue' => 'cash_fake_history_queue', // 歷史資料庫佇列 (List) (每筆資料放 JSON)
        'transactionQueue' => 'cash_fake_trans_queue', // 交易資料佇列 (List) (每筆放 JSON)
        'transUpdateQueue' => 'cash_fake_trans_update_queue', // 交易佇列 (List) (每筆資料放 JSON)
        'apiTransferInOutQueue' => 'cash_fake_api_transfer_in_out_queue' // 假現金使用者api轉入轉出統計佇列 (List) (每筆資料放 JSON)
    ];

    /**
     * 初始化
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasApiTransferInOutData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures($classnames, 'his');

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $this->entryQueueLogPath = $logsDir . '/test/queue/sync_cash_fake_entry_queue.log';
        $this->queueLogPath = $logsDir . '/test/queue/sync_cash_fake_queue.log';

        if (file_exists($this->entryQueueLogPath)) {
            unlink($this->entryQueueLogPath);
        }

        if (file_exists($this->queueLogPath)) {
            unlink($this->queueLogPath);
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
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 測試同步交易明細、同步歷史資料庫
     */
    public function testSyncEntryAndHistory()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');

        $entryQueueKey = $this->keys['entryQueue'];

        // 新增資料
        $at = date('YmdHis');

        $data = [
            'id'                => 10001,
            'at'                => $at,
            'cash_fake_id'      => 43,
            'user_id'           => 7,
            'currency'          => 156,
            'opcode'            => 1001,
            'created_at'        => date('Y-m-d H:i:s'),
            'amount'            => -100,
            'memo'              => 'test-memo',
            'balance'           => 1000,
            'ref_id'            => 9,
            'cash_fake_version' => 1
        ];

        // 放在佇列
        $queue1 = json_encode($data);
        $redis->lpush($entryQueueKey, $queue1);

        $data['id'] = 10002;
        $data['memo'] = 'memo';

        // 放在重試佇列
        $queue2 = json_encode($data);
        $redis->lpush($entryQueueKey . '_retry', $queue2);

        // 確保資料庫沒有
        $entry1 = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNull($entry1);

        $entry2 = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10002, 'at' => $at]);
        $this->assertNull($entry2);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true, '--history' => true]);

        $content = file_get_contents($this->entryQueueLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains($queue1, $results[0]);
        $this->assertContains($queue2, $results[1]);

        // 檢查
        $entry1 = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNotNull($entry1);
        $this->assertEquals(7, $entry1->getUserId());
        $this->assertEquals(1001, $entry1->getOpcode());
        $this->assertEquals(-100, $entry1->getAmount());
        $this->assertEquals(1000, $entry1->getBalance());
        $this->assertEquals('test-memo', $entry1->getMemo());
        $this->assertEquals(9, $entry1->getRefId());

        $entry2 = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10002, 'at' => $at]);
        $this->assertNotNull($entry2);
        $this->assertEquals(7, $entry2->getUserId());
        $this->assertEquals(1001, $entry2->getOpcode());
        $this->assertEquals(-100, $entry2->getAmount());
        $this->assertEquals(1000, $entry2->getBalance());
        $this->assertEquals('memo', $entry2->getMemo());
        $this->assertEquals(9, $entry2->getRefId());

        $entry1 = $emHis->find('BBDurianBundle:CashFakeEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNotNull($entry1);
        $this->assertEquals(7, $entry1->getUserId());
        $this->assertEquals(1001, $entry1->getOpcode());
        $this->assertEquals(-100, $entry1->getAmount());
        $this->assertEquals(1000, $entry1->getBalance());
        $this->assertEquals('test-memo', $entry1->getMemo());
        $this->assertEquals(9, $entry1->getRefId());

        $entry2 = $emHis->find('BBDurianBundle:CashFakeEntry', ['id' => 10002, 'at' => $at]);
        $this->assertNotNull($entry2);
        $this->assertEquals(7, $entry2->getUserId());
        $this->assertEquals(1001, $entry2->getOpcode());
        $this->assertEquals(-100, $entry2->getAmount());
        $this->assertEquals(1000, $entry2->getBalance());
        $this->assertEquals('memo', $entry2->getMemo());
        $this->assertEquals(9, $entry2->getRefId());
    }

    /**
     * 測試同步交易明細失敗
     */
    public function testSyncEntryButInsertFail()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $entryQueueKey = $this->keys['entryQueue'];

        // 新增資料
        $at = date('YmdHis');

        $data = [
            'id'                => 10001,
            'at'                => $at,
            'cash_fake_id'      => 43,
            'user_id'           => 7,
            'currency'          => 156,
            'opcode'            => 1001,
            'created_at'        => date('Y-m-d H:i:s'),
            'amount'            => -100,
            'memo'              => 'test-memo',
            'balance'           => 1000,
            'ref_id'            => 9,
            'cash_fake_version' => 1
        ];

        // 放在佇列
        $redis->lpush($entryQueueKey, json_encode($data));

        $data['id'] = 10002;

        $redis->lpush($entryQueueKey, json_encode($data));
        $redis->lpush($entryQueueKey, json_encode($data));  // 重複、會出錯

        // 確保資料庫沒有
        $entry1 = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNull($entry1);

        $entry2 = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10002, 'at' => $at]);
        $this->assertNull($entry2);

        // 執行，會出錯
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        $entry1 = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNull($entry1);

        $entry2 = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10002, 'at' => $at]);
        $this->assertNull($entry2);

        $this->assertEquals(0, $redis->llen($entryQueueKey));
        $this->assertEquals(3, $redis->llen($entryQueueKey . '_retry'));

        $entry1 = json_decode($redis->rpop($entryQueueKey . '_retry'), true);
        $entry2 = json_decode($redis->rpop($entryQueueKey . '_retry'), true);
        $entry3 = json_decode($redis->rpop($entryQueueKey . '_retry'), true);

        $this->assertEquals(10001, $entry1['id']);
        $this->assertEquals(10002, $entry2['id']);
        $this->assertEquals(10002, $entry3['id']);

        // 重新進行，執行超過 10 次，會放進失敗佇列
        $redis->lpush($entryQueueKey, json_encode($data));

        $data['memo'] = 'memo';

        $redis->lpush($entryQueueKey, json_encode($data));

        for ($i = 0; $i < 10; $i++) {
            $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);
        }

        $this->assertEquals(0, $redis->llen($entryQueueKey));
        $this->assertEquals(0, $redis->llen($entryQueueKey . '_retry'));
        $this->assertEquals(2, $redis->llen($entryQueueKey . '_failed'));

        $entry1 = json_decode($redis->rpop($entryQueueKey . '_failed'), true);
        $entry2 = json_decode($redis->rpop($entryQueueKey . '_failed'), true);

        $this->assertEquals(10002, $entry1['id']);
        $this->assertEquals(10002, $entry2['id']);

        // 重新進行，執行recover
        $redis->lpush($entryQueueKey . '_failed', json_encode($data));

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true, '--recover-fail' => true]);

        // 檢查
        $entry = $em->find('BBDurianBundle:CashFakeEntry', ['id' => 10002, 'at' => $at]);
        $this->assertNotNull($entry);
        $this->assertEquals(7, $entry->getUserId());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(1000, $entry->getBalance());
        $this->assertEquals('memo', $entry->getMemo());
        $this->assertEquals(9, $entry->getRefId());
    }

    /**
     * 測試同步交易明細但筆數與執行結果不同
     */
    public function testSyncEntryButCountDifferentFromResult()
    {
        $redis = $this->getRedis();

        $entryQueueKey = $this->keys['entryQueue'];

        // 新增資料
        $data = [
            'id'                => 10001,
            'at'                => date('YmdHis'),
            'cash_fake_id'      => 43,
            'user_id'           => 7,
            'currency'          => 156,
            'opcode'            => 1001,
            'created_at'        => date('Y-m-d H:i:s'),
            'amount'            => -100,
            'memo'              => 'test-memo',
            'balance'           => 1000,
            'ref_id'            => 9,
            'cash_fake_version' => 1
        ];

        // 放在佇列
        $redis->lpush($entryQueueKey, json_encode($data));

        $data['id'] = 10002;

        // 放在重試佇列
        $redis->lpush($entryQueueKey . '_retry', json_encode($data));

        $mockContainer = $this->getMockContainer('sync_cash_fake_entry');

        $application = new Application();
        $command = new SyncCashFakeCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-cash-fake');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--entry' => true]);

        $this->assertEquals(0, $redis->llen($entryQueueKey));
        $this->assertEquals(2, $redis->llen($entryQueueKey . '_retry'));

        $entry1 = json_decode($redis->rpop($entryQueueKey . '_retry'), true);
        $entry2 = json_decode($redis->rpop($entryQueueKey . '_retry'), true);

        $this->assertEquals(10002, $entry1['id']);
        $this->assertEquals(10001, $entry2['id']);
    }

    /**
     * 測試同步交易明細至歷史資料庫失敗
     */
    public function testSyncHistoryButInsertFail()
    {
        $redis = $this->getRedis();
        $emHis = $this->getEntityManager('his');

        $historyQueueKey = $this->keys['historyQueue'];

        // 新增不完全資料(缺少balance)
        $at = date('YmdHis');

        $data = [
            'id'                => 10001,
            'at'                => $at,
            'cash_fake_id'      => 43,
            'user_id'           => 7,
            'currency'          => 156,
            'opcode'            => 1001,
            'created_at'        => date('Y-m-d H:i:s'),
            'amount'            => -100,
            'memo'              => 'test-memo',
            'ref_id'            => 9,
            'cash_fake_version' => 1
        ];

        // 放在佇列
        $redis->lpush($historyQueueKey, json_encode($data));

        // 確保資料庫沒有
        $entry = $emHis->find('BBDurianBundle:CashFakeEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNull($entry);

        // 執行，會出錯
        $this->runCommand('durian:sync-cash-fake', ['--history' => true]);

        $entry = $emHis->find('BBDurianBundle:CashFakeEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNull($entry);

        $this->assertEquals(0, $redis->llen($historyQueueKey));
        $this->assertEquals(1, $redis->llen($historyQueueKey . '_retry'));

        $entry = json_decode($redis->rpop($historyQueueKey . '_retry'), true);

        $this->assertEquals(10001, $entry['id']);

        // 重新進行，執行超過 10 次，會放進失敗佇列
        $redis->lpush($historyQueueKey, json_encode($data));

        for ($i = 0; $i < 10; $i++) {
            $this->runCommand('durian:sync-cash-fake', ['--history' => true]);
        }

        $this->assertEquals(0, $redis->llen($historyQueueKey));
        $this->assertEquals(0, $redis->llen($historyQueueKey . '_retry'));
        $this->assertEquals(1, $redis->llen($historyQueueKey . '_failed'));

        $entry = json_decode($redis->rpop($historyQueueKey . '_failed'), true);

        $this->assertEquals(10001, $entry['id']);

        // 補齊資料，重新進行，執行recover
        $data['balance'] = 1000;

        $redis->lpush($historyQueueKey . '_failed', json_encode($data));

        $this->runCommand('durian:sync-cash-fake', ['--history' => true, '--recover-fail' => true]);

        // 檢查
        $entry = $emHis->find('BBDurianBundle:CashFakeEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNotNull($entry);
        $this->assertEquals(7, $entry->getUserId());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(1000, $entry->getBalance());
        $this->assertEquals('test-memo', $entry->getMemo());
        $this->assertEquals(9, $entry->getRefId());
    }

    /**
     * 測試同步交易明細至歷史資料庫但筆數與執行結果不同
     */
    public function testSyncHistoryButCountDifferentFromResult()
    {
        $redis = $this->getRedis();

        $historyQueueKey = $this->keys['historyQueue'];

        // 新增資料
        $data = [
            'id'                => 10001,
            'at'                => date('YmdHis'),
            'cash_fake_id'      => 43,
            'user_id'           => 7,
            'currency'          => 156,
            'opcode'            => 1001,
            'created_at'        => date('Y-m-d H:i:s'),
            'amount'            => -100,
            'memo'              => 'test-memo',
            'balance'           => 1000,
            'ref_id'            => 9,
            'cash_fake_version' => 1
        ];

        // 放在佇列
        $redis->lpush($historyQueueKey, json_encode($data));

        $data['id'] = 10002;

        // 放在重試佇列
        $redis->lpush($historyQueueKey . '_retry', json_encode($data));

        $mockContainer = $this->getMockContainer('sync_cash_fake_history');

        $application = new Application();
        $command = new SyncCashFakeCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-cash-fake');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--history' => true]);

        $this->assertEquals(0, $redis->llen($historyQueueKey));
        $this->assertEquals(2, $redis->llen($historyQueueKey . '_retry'));

        $entry1 = json_decode($redis->rpop($historyQueueKey . '_retry'), true);
        $entry2 = json_decode($redis->rpop($historyQueueKey . '_retry'), true);

        $this->assertEquals(10002, $entry1['id']);
        $this->assertEquals(10001, $entry2['id']);
    }

    /**
     * 測試同步交易明細但佇列為空
     */
    public function testSyncEntryWithNullQueue()
    {
        $redis = $this->getRedis();

        $entryQueueKey = $this->keys['entryQueue'];

        // 放在重試佇列
        $redis->lpush($entryQueueKey . '_retry', null);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        $this->assertEquals(0, $redis->llen($entryQueueKey));
        $this->assertEquals(0, $redis->llen($entryQueueKey . '_retry'));
        $this->assertEquals(0, $redis->llen($entryQueueKey . '_failed'));

        // 放在失敗佇列
        $redis->lpush($entryQueueKey . '_failed', null);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true, '--recover-fail' => true]);

        $this->assertEquals(0, $redis->llen($entryQueueKey));
        $this->assertEquals(0, $redis->llen($entryQueueKey . '_retry'));
        $this->assertEquals(0, $redis->llen($entryQueueKey . '_failed'));
    }

    /**
     * 測試同步轉帳明細
     */
    public function testSyncTransfer()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $transferQueueKey = $this->keys['transferQueue'];

        // 新增資料
        $at = date('YmdHis');

        $data = [
            'id'           => 10001,
            'at'           => $at,
            'user_id'      => 7,
            'domain'       => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'created_at'   => date('Y-m-d H:i:s'),
            'amount'       => -100,
            'memo'         => 'test-memo',
            'balance'      => 1000,
            'ref_id'       => 9
        ];

        // 放在佇列
        $redis->lpush($transferQueueKey, json_encode($data));

        // 確保資料庫沒有
        $transfer = $em->find('BBDurianBundle:CashFakeTransferEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNull($transfer);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        // 檢查
        $transfer = $em->find('BBDurianBundle:CashFakeTransferEntry', ['id' => 10001, 'at' => $at]);
        $this->assertNotNull($transfer);
        $this->assertEquals(7, $transfer->getUserId());
        $this->assertEquals(2, $transfer->getDomain());
        $this->assertEquals(1001, $transfer->getOpcode());
        $this->assertEquals(-100, $transfer->getAmount());
        $this->assertEquals(1000, $transfer->getBalance());
        $this->assertEquals('test-memo', $transfer->getMemo());
        $this->assertEquals(9, $transfer->getRefId());
    }

    /**
     * 測試同步轉帳明細但筆數與執行結果不同
     */
    public function testSyncTransferButCountDifferentFromResult()
    {
        $redis = $this->getRedis();

        $transferQueueKey = $this->keys['transferQueue'];

        // 新增資料
        $data = [
            'id'           => 10001,
            'at'           => date('YmdHis'),
            'user_id'      => 7,
            'domain'       => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'created_at'   => date('Y-m-d H:i:s'),
            'amount'       => -100,
            'memo'         => 'test-memo',
            'balance'      => 1000,
            'ref_id'       => 9
        ];

        // 放在轉帳明細佇列
        $redis->lpush($transferQueueKey, json_encode($data));

        // 放在轉帳明細重試佇列
        $data['id'] = 10002;
        $redis->lpush($transferQueueKey . '_retry', json_encode($data));

        $mockContainer = $this->getMockContainer('sync_cash_fake_entry');

        $application = new Application();
        $command = new SyncCashFakeCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-cash-fake');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--entry' => true]);

        $this->assertEquals(0, $redis->llen($transferQueueKey));
        $this->assertEquals(2, $redis->llen($transferQueueKey . '_retry'));

        $entry1 = json_decode($redis->rpop($transferQueueKey . '_retry'), true);
        $entry2 = json_decode($redis->rpop($transferQueueKey . '_retry'), true);

        $this->assertEquals(10002, $entry1['id']);
        $this->assertEquals(10001, $entry2['id']);
    }

    /**
     * 測試同步明細操作者
     */
    public function testSyncOperator()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $operatorQueueKey = $this->keys['operatorQueue'];

        // 新增資料
        $data = [
            'entry_id'     => 10001,
            'username'     => 'tester',
            'transfer_out' => 1,
            'whom'         => 'tester',
            'level'        => 1
        ];

        // 放在佇列
        $redis->lpush($operatorQueueKey, json_encode($data));

        // 確保資料庫沒有
        $operator = $em->find('BBDurianBundle:CashFakeEntryOperator', ['entryId' => 10001]);
        $this->assertNull($operator);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        // 檢查
        $operator = $em->find('BBDurianBundle:CashFakeEntryOperator', ['entryId' => 10001]);
        $this->assertNotNull($operator);
        $this->assertEquals('tester', $operator->getUsername());
        $this->assertEquals(1, $operator->getTransferOut());
        $this->assertEquals('tester', $operator->getWhom());
        $this->assertEquals(1, $operator->getLevel());
    }

    /**
     * 測試同步明細操作者但筆數與執行結果不同
     */
    public function testSyncOperatorButCountDifferentFromResult()
    {
        $redis = $this->getRedis();

        $operatorQueueKey = $this->keys['operatorQueue'];

        // 新增資料
        $data = [
            'entry_id'     => 10001,
            'username'     => 'tester',
            'transfer_out' => 1,
            'whom'         => 'tester',
            'level'        => 1
        ];

        // 放在佇列
        $redis->lpush($operatorQueueKey, json_encode($data));

        $data['entry_id'] = 10002;

        // 放在重試佇列
        $redis->lpush($operatorQueueKey . '_retry', json_encode($data));

        $mockContainer = $this->getMockContainer('sync_cash_fake_entry');

        $application = new Application();
        $command = new SyncCashFakeCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-cash-fake');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--entry' => true]);

        $this->assertEquals(0, $redis->llen($operatorQueueKey));
        $this->assertEquals(2, $redis->llen($operatorQueueKey . '_retry'));

        $entry1 = json_decode($redis->rpop($operatorQueueKey . '_retry'), true);
        $entry2 = json_decode($redis->rpop($operatorQueueKey . '_retry'), true);

        $this->assertEquals(10002, $entry1['entry_id']);
        $this->assertEquals(10001, $entry2['entry_id']);
    }

    /**
     * 測試批次同步餘額、預存、預扣
     */
    public function testBatchSyncBalance()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $balanceQueueKey = $this->keys['balanceQueue'];

        // 初始化資料
        $em->createQueryBuilder()
            ->update('BBDurianBundle:CashFake', 'cf')
            ->set('cf.user', 7)
            ->set('cf.balance', 0)
            ->set('cf.preSub', 0)
            ->set('cf.preAdd', 0)
            ->set('cf.version', 1)
            ->where('cf.id = 1')
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->update('BBDurianBundle:CashFake', 'cf')
            ->set('cf.user', 8)
            ->set('cf.balance', 0)
            ->set('cf.preSub', 0)
            ->set('cf.preAdd', 0)
            ->set('cf.version', 1)
            ->where('cf.id = 2')
            ->getQuery()
            ->execute();

        // 新增資料
        $data = [
            'user_id'  => 7,
            'balance'  => 3,
            'pre_sub'  => 2,
            'pre_add'  => 1,
            'version'  => 20,
            'currency' => 156,
            'last_entry_at' => 20150808235911
        ];
        $queue1 = json_encode($data);
        $redis->lpush($balanceQueueKey, $queue1);

        // 測試最後一筆版號較大的未帶last_entry_at，還是會記錄時間
        $data = [
            'user_id'  => 7,
            'balance'  => 5,
            'pre_sub'  => 2,
            'pre_add'  => 1,
            'version'  => 21,
            'currency' => 156
        ];
        $queue2 = json_encode($data);
        $redis->lpush($balanceQueueKey, $queue2);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        $content = file_get_contents($this->queueLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains($queue1, $results[0]);
        $this->assertContains($queue2, $results[1]);

        // 檢查
        $cashfake1 = $em->find('BBDurianBundle:CashFake', 1);

        $this->assertEquals(5, $cashfake1->getBalance());
        $this->assertEquals(2, $cashfake1->getPreSub());
        $this->assertEquals(1, $cashfake1->getPreAdd());
        $this->assertEquals(21, $cashfake1->getVersion());
        $this->assertEquals(20150808235911, $cashfake1->getLastEntryAt());
        $em->clear();

        // 測試第一筆版號較大的未帶last_entry_at，還是會記錄時間
        $data['balance'] = 25;
        $data['version'] = 25;
        $queue3 = json_encode($data);
        $redis->lpush($balanceQueueKey, $queue3);

        $data['balance'] = 22;
        $data['version'] = 22;
        $data['last_entry_at'] = 29990101235911;
        $queue4 = json_encode($data);
        $redis->lpush($balanceQueueKey, $queue4);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        $content = file_get_contents($this->queueLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains($queue3, $results[2]);
        $this->assertContains($queue4, $results[3]);

        // 檢查
        $cashfake1 = $em->find('BBDurianBundle:CashFake', 1);

        $this->assertEquals(25, $cashfake1->getBalance());
        $this->assertEquals(2, $cashfake1->getPreSub());
        $this->assertEquals(1, $cashfake1->getPreAdd());
        $this->assertEquals(25, $cashfake1->getVersion());
        $this->assertEquals(29990101235911, $cashfake1->getLastEntryAt());
        $em->clear();

        $data['user_id'] = 8;
        $data['version'] = 2;
        $queue5 = json_encode($data);
        $redis->lpush($balanceQueueKey, $queue5);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        $content = file_get_contents($this->queueLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains($queue5, $results[4]);

        // 檢查
        $cashfake2 = $em->find('BBDurianBundle:CashFake', 2);

        $this->assertEquals(22, $cashfake2->getBalance());
        $this->assertEquals(2, $cashfake2->getPreSub());
        $this->assertEquals(1, $cashfake2->getPreAdd());
        $this->assertEquals(2, $cashfake2->getVersion());
        $this->assertEquals(29990101235911, $cashfake2->getLastEntryAt());
    }

    /**
     * 測試批次同步餘額、預存、預扣但使用者正在被更新
     */
    public function testBatchSyncBalanceButUserIsToBeUpdated()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $balanceQueueKey = $this->keys['balanceQueue'];

        // 初始化資料
        $em->createQueryBuilder()
            ->update('BBDurianBundle:CashFake', 'cf')
            ->set('cf.user', 7)
            ->set('cf.balance', 0)
            ->set('cf.preSub', 0)
            ->set('cf.preAdd', 0)
            ->set('cf.version', 1)
            ->where('cf.id = 1')
            ->getQuery()
            ->execute();

        // 新增資料
        $data = [
            'user_id'  => 7,
            'balance'  => 3,
            'pre_sub'  => 2,
            'pre_add'  => 1,
            'version'  => 20,
            'currency' => 156
        ];
        $queue1 = json_encode($data);
        $redis->lpush($balanceQueueKey, $queue1);
        $redis->sadd('sync_cash_fake', 7);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        $this->assertEquals(0, $redis->llen($balanceQueueKey));
        $this->assertEquals(1, $redis->llen($balanceQueueKey . '_retry'));

        $balance = json_decode($redis->rpop($balanceQueueKey . '_retry'), true);

        $this->assertEquals(7, $balance['user_id']);
    }

    /**
     * 測試同步餘額、預存、預扣
     */
    public function testSyncBalance()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $balanceQueueKey = $this->keys['balanceQueue'];

        // 初始化資料
        $em->createQueryBuilder()
            ->update('BBDurianBundle:CashFake', 'cf')
            ->set('cf.user', 7)
            ->set('cf.balance', 0)
            ->set('cf.preSub', 0)
            ->set('cf.preAdd', 0)
            ->set('cf.version', 1)
            ->where('cf.id = 1')
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->update('BBDurianBundle:CashFake', 'cf')
            ->set('cf.user', 8)
            ->set('cf.balance', 0)
            ->set('cf.preSub', 0)
            ->set('cf.preAdd', 0)
            ->set('cf.version', 1)
            ->where('cf.id = 2')
            ->getQuery()
            ->execute();

        // 新增資料
        $data = [
            'user_id'  => 7,
            'balance'  => 3,
            'pre_sub'  => 2,
            'pre_add'  => 1,
            'version'  => 20,
            'currency' => 156,
            'last_entry_at' => 20150808235911
        ];

        // 放在重試佇列
        $redis->lpush($balanceQueueKey . '_retry', json_encode($data));

        // 測試最後一筆版號較大的未帶last_entry_at，還是會記錄時間
        $data = [
            'user_id'  => 7,
            'balance'  => 5,
            'pre_sub'  => 2,
            'pre_add'  => 1,
            'version'  => 21,
            'currency' => 156
        ];

        $redis->lpush($balanceQueueKey . '_retry', json_encode($data));

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        // 檢查
        $cashfake1 = $em->find('BBDurianBundle:CashFake', 1);

        $this->assertEquals(5, $cashfake1->getBalance());
        $this->assertEquals(2, $cashfake1->getPreSub());
        $this->assertEquals(1, $cashfake1->getPreAdd());
        $this->assertEquals(21, $cashfake1->getVersion());
        $this->assertEquals(20150808235911, $cashfake1->getLastEntryAt());
        $em->clear();

        // 測試第一筆版號較大的未帶last_entry_at，還是會記錄時間
        $data['balance'] = 25;
        $data['version'] = 25;
        $redis->lpush($balanceQueueKey . '_retry', json_encode($data));

        $data['balance'] = 22;
        $data['version'] = 22;
        $data['last_entry_at'] = 29990101235911;
        $redis->lpush($balanceQueueKey . '_retry', json_encode($data));

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        // 檢查
        $cashfake1 = $em->find('BBDurianBundle:CashFake', 1);

        $this->assertEquals(25, $cashfake1->getBalance());
        $this->assertEquals(2, $cashfake1->getPreSub());
        $this->assertEquals(1, $cashfake1->getPreAdd());
        $this->assertEquals(25, $cashfake1->getVersion());
        $this->assertEquals(29990101235911, $cashfake1->getLastEntryAt());
        $em->clear();

        $data['user_id'] = 8;
        $data['version'] = 2;

        $redis->lpush($balanceQueueKey . '_retry', json_encode($data));

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        // 檢查
        $cashfake2 = $em->find('BBDurianBundle:CashFake', 2);

        $this->assertEquals(22, $cashfake2->getBalance());
        $this->assertEquals(2, $cashfake2->getPreSub());
        $this->assertEquals(1, $cashfake2->getPreAdd());
        $this->assertEquals(2, $cashfake2->getVersion());
        $this->assertEquals(29990101235911, $cashfake2->getLastEntryAt());
    }

    /**
     * 測試同步餘額過濾失敗會放入重試佇列
     */
    public function testSyncBalanceButFail()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $balanceQueueKey = $this->keys['balanceQueue'];

        // 初始化資料
        $em->createQueryBuilder()
            ->update('BBDurianBundle:CashFake', 'cf')
            ->set('cf.user', 7)
            ->set('cf.balance', 0)
            ->set('cf.preSub', 0)
            ->set('cf.preAdd', 0)
            ->set('cf.version', 1)
            ->where('cf.id = 1')
            ->getQuery()
            ->execute();

        // 新增不完全資料，缺少version，SyncBalance會出錯
        $data = [
            'user_id'  => 7,
            'currency' => 156,
            'balance'  => 3,
            'pre_sub'  => 2,
            'pre_add'  => 1
        ];

        // 放在佇列
        $redis->lpush($balanceQueueKey, json_encode($data));

        // 執行，會出錯
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        $this->assertEquals(0, $redis->llen($balanceQueueKey));
        $this->assertEquals(1, $redis->llen($balanceQueueKey . '_retry'));

        $balance = json_decode($redis->rpop($balanceQueueKey . '_retry'), true);

        $this->assertEquals(7, $balance['user_id']);

        // 重新進行，執行超過 10 次，會放進失敗佇列
        $redis->lpush($balanceQueueKey, json_encode($data));

        for ($i = 0; $i < 10; $i++) {
            $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);
        }

        $this->assertEquals(0, $redis->llen($balanceQueueKey));
        $this->assertEquals(0, $redis->llen($balanceQueueKey . '_retry'));
        $this->assertEquals(1, $redis->llen($balanceQueueKey . '_failed'));

        $cashfake = json_decode($redis->rpop($balanceQueueKey . '_failed'), true);

        $this->assertEquals(7, $cashfake['user_id']);

        // 補齊資料，重新進行，執行recover
        $data['version'] = 20;

        $redis->lpush($balanceQueueKey . '_failed', json_encode($data));

        $this->runCommand('durian:sync-cash-fake', ['--balance' => true, '--recover-fail' => true]);

        // 檢查
        $cashfake = $em->find('BBDurianBundle:CashFake', 1);

        $this->assertEquals(3, $cashfake->getBalance());
        $this->assertEquals(2, $cashfake->getPreSub());
        $this->assertEquals(1, $cashfake->getPreAdd());
        $this->assertEquals(20, $cashfake->getVersion());
    }

    /**
     * 測試同步餘額失敗會放入重試佇列
     */
    public function testSyncBalanceButUpdateBalanceFail()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $balanceQueueKey = $this->keys['balanceQueue'];

        // 初始化資料
        $em->createQueryBuilder()
            ->update('BBDurianBundle:CashFake', 'cf')
            ->set('cf.user', 7)
            ->set('cf.balance', 0)
            ->set('cf.preSub', 0)
            ->set('cf.preAdd', 0)
            ->set('cf.version', 1)
            ->where('cf.id = 1')
            ->getQuery()
            ->execute();

        // 新增不完全資料，缺少balance，updateBalance會出錯
        $data = [
            'user_id'  => 7,
            'currency' => 156,
            'version'  => 20,
            'pre_sub'  => 2,
            'pre_add'  => 1
        ];

        // 放在佇列
        $redis->lpush($balanceQueueKey, json_encode($data));

        // 執行，會出錯
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        $this->assertEquals(0, $redis->llen($balanceQueueKey));
        $this->assertEquals(1, $redis->llen($balanceQueueKey . '_retry'));

        $balance = json_decode($redis->rpop($balanceQueueKey . '_retry'), true);

        $this->assertEquals(7, $balance['user_id']);

        // 重新進行，執行超過 10 次，會放進失敗佇列
        $redis->lpush($balanceQueueKey, json_encode($data));

        for ($i = 0; $i < 10; $i++) {
            $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);
        }

        $this->assertEquals(0, $redis->llen($balanceQueueKey));
        $this->assertEquals(0, $redis->llen($balanceQueueKey . '_retry'));
        $this->assertEquals(1, $redis->llen($balanceQueueKey . '_failed'));

        $cashfake = json_decode($redis->rpop($balanceQueueKey . '_failed'), true);

        $this->assertEquals(7, $cashfake['user_id']);

        // 補齊資料，重新進行，執行recover
        $data['balance'] = 3;

        $redis->lpush($balanceQueueKey . '_failed', json_encode($data));

        $this->runCommand('durian:sync-cash-fake', ['--balance' => true, '--recover-fail' => true]);

        // 檢查
        $cashfake = $em->find('BBDurianBundle:CashFake', 1);

        $this->assertEquals(3, $cashfake->getBalance());
        $this->assertEquals(2, $cashfake->getPreSub());
        $this->assertEquals(1, $cashfake->getPreAdd());
        $this->assertEquals(20, $cashfake->getVersion());
    }

    /**
     * 測試同步餘額但佇列為空
     */
    public function testSyncBalanceWithNullQueue()
    {
        $redis = $this->getRedis();

        $balanceQueueKey = $this->keys['balanceQueue'];

        // 放在重試佇列
        $redis->lpush($balanceQueueKey . '_retry', null);

        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        $this->assertEquals(0, $redis->llen($balanceQueueKey));
        $this->assertEquals(0, $redis->llen($balanceQueueKey . '_retry'));
        $this->assertEquals(0, $redis->llen($balanceQueueKey . '_failed'));

        // 放在失敗佇列
        $redis->lpush($balanceQueueKey . '_failed', null);

        $this->runCommand('durian:sync-cash-fake', ['--balance' => true, '--recover-fail' => true]);

        $this->assertEquals(0, $redis->llen($balanceQueueKey));
        $this->assertEquals(0, $redis->llen($balanceQueueKey . '_retry'));
        $this->assertEquals(0, $redis->llen($balanceQueueKey . '_failed'));
    }

    /**
     * 測試同步餘額, 但是版本號較小，不會更新
     */
    public function testSyncBalanceButVersionIsLowerThanOriginal()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $balanceQueueKey = $this->keys['balanceQueue'];

        // 初始化資料
        $em->createQueryBuilder()
            ->update('BBDurianBundle:CashFake', 'cf')
            ->set('cf.user', 7)
            ->set('cf.balance', 3)
            ->set('cf.preSub', 2)
            ->set('cf.preAdd', 1)
            ->set('cf.version', 11)
            ->where('cf.id = 1')
            ->getQuery()
            ->execute();

        // 新增資料
        $data = [
            'user_id'  => 7,
            'balance'  => 20,
            'pre_sub'  => 9,
            'pre_add'  => 6,
            'version'  => 10,
            'currency' => 156
        ];

        // 放在佇列
        $redis->lpush($balanceQueueKey, json_encode($data));

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--balance' => true]);

        // 檢查
        $cashfake = $em->find('BBDurianBundle:CashFake', 1);
        $this->assertEquals(3, $cashfake->getBalance());
        $this->assertEquals(2, $cashfake->getPreSub());
        $this->assertEquals(1, $cashfake->getPreAdd());
        $this->assertEquals(11, $cashfake->getVersion());
    }

    /**
     * 測試同步兩段交易(Transaction)
     */
    public function testSyncTransactionAndTransactionBalance()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $transactionQueueKey = $this->keys['transactionQueue'];
        $transUpdateQueueKey = $this->keys['transUpdateQueue'];

        // 新增資料
        $caretedAt = date('Y-m-d H:i:s');

        $data = [
            'id'           => 10001,
            'cash_fake_id' => 43,
            'user_id'      => 7,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => -100,
            'ref_id'       => 9,
            'created_at'   => $caretedAt,
            'checked'      => 0,
            'checked_at'   => null,
            'commited'     => 0,
            'memo'         => 'test-memo'
        ];

        // 放在佇列
        $redis->lpush($transactionQueueKey, json_encode($data));

        $data['id'] = 10002;

        // 放在重試佇列
        $redis->lpush($transactionQueueKey . '_retry', json_encode($data));

        // 確保資料庫沒有
        $trans1 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $this->assertNull($trans1);

        $trans2 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10002]);
        $this->assertNull($trans2);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        // 檢查
        $trans1 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $this->assertNotNull($trans1);
        $this->assertEquals(7, $trans1->getUserId());
        $this->assertEquals(1001, $trans1->getOpcode());
        $this->assertEquals(-100, $trans1->getAmount());
        $this->assertEquals(9, $trans1->getRefId());
        $this->assertFalse($trans1->isChecked());
        $this->assertNull($trans1->getCheckedAt());
        $this->assertFalse($trans1->isCommited());
        $this->assertEquals('test-memo', $trans1->getMemo());

        $trans2 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10002]);
        $this->assertNotNull($trans2);
        $this->assertEquals(7, $trans2->getUserId());
        $this->assertEquals(1001, $trans2->getOpcode());
        $this->assertEquals(-100, $trans2->getAmount());
        $this->assertEquals(9, $trans2->getRefId());
        $this->assertFalse($trans2->isChecked());
        $this->assertNull($trans2->getCheckedAt());
        $this->assertFalse($trans2->isCommited());
        $this->assertEquals('test-memo', $trans2->getMemo());

        $em->clear();

        // 測試更新資料
        $data = [
            'id'         => 10001,
            'checked'    => 1,
            'checked_at' => $caretedAt,
            'commited'   => 1
        ];

        $redis->lpush($transUpdateQueueKey, json_encode($data));

        $data['id'] = 10002;
        $data['commited'] = 0;

        $redis->lpush($transUpdateQueueKey . '_retry', json_encode($data));

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        // 檢查
        $trans1 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $this->assertTrue($trans1->isChecked());
        $this->assertEquals($caretedAt, $trans1->getCheckedAt()->format('Y-m-d H:i:s'));
        $this->assertTrue($trans1->isCommited());

        $trans2 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10002]);
        $this->assertTrue($trans2->isChecked());
        $this->assertEquals($caretedAt, $trans2->getCheckedAt()->format('Y-m-d H:i:s'));
        $this->assertFalse($trans2->isCommited());
    }

    /**
     * 測試同步兩段交易(Transaction)失敗
     */
    public function testSyncTransactionButUpdateFail()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $transactionQueueKey = $this->keys['transactionQueue'];
        $transUpdateQueueKey = $this->keys['transUpdateQueue'];

        // 新增資料
        $caretedAt = date('Y-m-d H:i:s');

        $data = [
            'id'           => 10001,
            'cash_fake_id' => 43,
            'user_id'      => 7,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => -100,
            'ref_id'       => 9,
            'created_at'   => $caretedAt,
            'checked'      => 0,
            'checked_at'   => null,
            'commited'     => 0,
            'memo'         => 'test-memo'
        ];

        // 放在佇列
        $redis->lpush($transactionQueueKey, json_encode($data));

        // 確保資料庫沒有
        $trans = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $this->assertNull($trans);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        // 檢查
        $trans = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $this->assertNotNull($trans);
        $this->assertEquals(7, $trans->getUserId());
        $this->assertEquals(1001, $trans->getOpcode());
        $this->assertEquals(-100, $trans->getAmount());
        $this->assertEquals(9, $trans->getRefId());
        $this->assertFalse($trans->isChecked());
        $this->assertNull($trans->getCheckedAt());
        $this->assertFalse($trans->isCommited());
        $this->assertEquals('test-memo', $trans->getMemo());

        $em->clear();

        // 測試更新不完全資料
        $updateData['id'] = 10001;

        $redis->lpush($transUpdateQueueKey . '_retry', json_encode($updateData));

        // 同步，會出錯
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        // 檢查
        $trans = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $this->assertFalse($trans->isChecked());
        $this->assertNull($trans->getCheckedAt());
        $this->assertFalse($trans->isCommited());

        $this->assertEquals(0, $redis->llen($transUpdateQueueKey));
        $this->assertEquals(1, $redis->llen($transUpdateQueueKey . '_retry'));

        $trans = json_decode($redis->rpop($transUpdateQueueKey . '_retry'), true);

        $this->assertEquals(10001, $trans['id']);

        // 重新進行，執行超過 10 次，會放進失敗佇列
        $redis->lpush($transUpdateQueueKey, json_encode($updateData));

        for ($i = 0; $i < 10; $i++) {
            $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);
        }

        $this->assertEquals(0, $redis->llen($transUpdateQueueKey));
        $this->assertEquals(0, $redis->llen($transUpdateQueueKey . '_retry'));
        $this->assertEquals(1, $redis->llen($transUpdateQueueKey . '_failed'));

        $trans = json_decode($redis->rpop($transUpdateQueueKey . '_failed'), true);

        $this->assertEquals(10001, $trans['id']);

        // 補齊資料，重新進行，執行recover
        $updateData['checked'] = true;
        $updateData['checked_at'] = $caretedAt;
        $updateData['commited'] = true;

        $redis->lpush($transUpdateQueueKey . '_failed', json_encode($updateData));

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true, '--recover-fail' => true]);

        // 檢查
        $trans = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $em->refresh($trans);
        $this->assertTrue($trans->isChecked());
        $this->assertEquals($caretedAt, $trans->getCheckedAt()->format('Y-m-d H:i:s'));
        $this->assertTrue($trans->isCommited());
    }

    /**
     * 測試同步兩段交易(Transaction)明細但筆數與執行結果不同
     */
    public function testSyncTransactionButCountDifferentFromResult()
    {
        $redis = $this->getRedis();

        $transactionQueueKey = $this->keys['transactionQueue'];

        // 新增資料
        $data = [
            'id'           => 10001,
            'cash_fake_id' => 43,
            'user_id'      => 7,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => -100,
            'ref_id'       => 9,
            'created_at'   => date('Y-m-d H:i:s'),
            'checked'      => 0,
            'checked_at'   => null,
            'commited'     => 0,
            'memo'         => 'test-memo'
        ];

        // 放在佇列
        $redis->lpush($transactionQueueKey, json_encode($data));

        $data['id'] = 10002;

        // 放在重試佇列
        $redis->lpush($transactionQueueKey . '_retry', json_encode($data));

        $mockContainer = $this->getMockContainer('sync_cash_fake_entry');

        $application = new Application();
        $command = new SyncCashFakeCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-cash-fake');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--entry' => true]);

        $this->assertEquals(0, $redis->llen($transactionQueueKey));
        $this->assertEquals(2, $redis->llen($transactionQueueKey . '_retry'));

        $entry1 = json_decode($redis->rpop($transactionQueueKey . '_retry'), true);
        $entry2 = json_decode($redis->rpop($transactionQueueKey . '_retry'), true);

        $this->assertEquals(10002, $entry1['id']);
        $this->assertEquals(10001, $entry2['id']);
    }

    /**
     * 測試同步兩段交易(Transaction)狀態但筆數與執行結果不同
     */
    public function testSyncTransactionStatusButCountDifferentFromResult()
    {
        $redis = $this->getRedis();
        $em    = $this->getEntityManager();

        $transactionQueueKey = $this->keys['transactionQueue'];
        $transUpdateQueueKey = $this->keys['transUpdateQueue'];

        // 新增明細更新資料，但明細尚未新增
        $caretedAt = date('Y-m-d H:i:s');

        $updateData = [
            'id'         => 10001,
            'checked'    => true,
            'checked_at' => $caretedAt,
            'commited'   => 1
        ];

        // 放在佇列
        $redis->lpush($transUpdateQueueKey, json_encode($updateData));

        $updateData['id'] = 10002;
        $updateData['commited'] = 0;

        // 放在重試佇列
        $redis->lpush($transUpdateQueueKey . '_retry', json_encode($updateData));

        // 確保資料庫沒有
        $trans1 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $this->assertNull($trans1);

        $trans2 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10002]);
        $this->assertNull($trans2);

        // 同步
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        $this->assertEquals(0, $redis->llen($transUpdateQueueKey));
        $this->assertEquals(2, $redis->llen($transUpdateQueueKey . '_retry'));

        $transUpdateQueue = $redis->lrange($transUpdateQueueKey . '_retry', 0, 1);
        $transUpdate1 = json_decode($transUpdateQueue[0], true);
        $transUpdate2 = json_decode($transUpdateQueue[1], true);

        $this->assertEquals(10001, $transUpdate1['id']);
        $this->assertEquals(10002, $transUpdate2['id']);

        $em->clear();

        // 新增明細資料
        $data = [
            'id'           => 10001,
            'cash_fake_id' => 43,
            'user_id'      => 7,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => -100,
            'ref_id'       => 9,
            'created_at'   => $caretedAt,
            'checked'      => 0,
            'checked_at'   => null,
            'commited'     => 0,
            'memo'         => 'test-memo'
        ];

        // 放在佇列
        $redis->lpush($transactionQueueKey, json_encode($data));

        $data['id'] = 10002;

        // 放在重試佇列
        $redis->lpush($transactionQueueKey . '_retry', json_encode($data));

        // 重新進行
        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        // 檢查
        $trans1 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10001]);
        $em->refresh($trans1);
        $this->assertTrue($trans1->isChecked());
        $this->assertEquals($caretedAt, $trans1->getCheckedAt()->format('Y-m-d H:i:s'));
        $this->assertTrue($trans1->isCommited());

        $trans2 = $em->find('BBDurianBundle:CashFakeTrans', ['id' => 10002]);
        $em->refresh($trans2);
        $this->assertTrue($trans2->isChecked());
        $this->assertEquals($caretedAt, $trans2->getCheckedAt()->format('Y-m-d H:i:s'));
        $this->assertFalse($trans2->isCommited());
    }

    /**
     * 測試同步兩段交易(Transaction)狀態但佇列為空
     */
    public function testSyncTransactionUpdateWithNullQueue()
    {
        $redis = $this->getRedis();

        $transUpdateQueueKey = $this->keys['transUpdateQueue'];

        // 放在重試佇列
        $redis->lpush($transUpdateQueueKey. '_retry', null);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);

        $this->assertEquals(0, $redis->llen($transUpdateQueueKey));
        $this->assertEquals(0, $redis->llen($transUpdateQueueKey . '_retry'));
        $this->assertEquals(0, $redis->llen($transUpdateQueueKey . '_failed'));

        // 放在失敗佇列
        $redis->lpush($transUpdateQueueKey. '_failed', null);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true, '--recover-fail' => true]);

        $this->assertEquals(0, $redis->llen($transUpdateQueueKey));
        $this->assertEquals(0, $redis->llen($transUpdateQueueKey . '_retry'));
        $this->assertEquals(0, $redis->llen($transUpdateQueueKey . '_failed'));
    }

    /**
     * 測試同步api轉入轉出記錄到mysql
     */
    public function testSyncApiTransferInOut()
    {
        $redis = $this->getRedis();
        $em = $this->getEntityManager();

        $queueKey = $this->keys['apiTransferInOutQueue'];

        $data = [
            'user_id' => 8,
            'api_transfer_in' => true
        ];

        $data = json_encode($data);
        $redis->lpush($queueKey, $data);

        $this->runCommand('durian:sync-cash-fake', ['--api-transfer-in-out' => true]);

        //測試寫入新資料
        $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', 8);

        $this->assertNotNull($userHasApiTransferInOut);
        $this->assertTrue($userHasApiTransferInOut->isApiTransferIn());

        //測試更新舊有資料
        $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', 7);
        $this->assertFalse($userHasApiTransferInOut->isApiTransferIn());
        $em->clear();

        $data = [
            'user_id' => 7,
            'api_transfer_in' => true
        ];

        $data = json_encode($data);
        $redis->lpush($queueKey, $data);

        $this->runCommand('durian:sync-cash-fake', ['--api-transfer-in-out' => true]);

        $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', 7);
        $this->assertTrue($userHasApiTransferInOut->isApiTransferIn());
        $em->clear();

        $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', 7);
        $this->assertFalse($userHasApiTransferInOut->isApiTransferOut());
        $em->clear();

        $data = [
            'user_id' => 7,
            'api_transfer_out' => true
        ];

        $data = json_encode($data);
        $redis->lpush($queueKey, $data);

        $this->runCommand('durian:sync-cash-fake', ['--api-transfer-in-out' => true]);

        $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', 7);
        $this->assertTrue($userHasApiTransferInOut->isApiTransferOut());
    }

    /**
     * 測試同步api轉入轉出記錄失敗，並進retry queue
     */
    public function testSyncApiTransferInOutFail()
    {
        $redis = $this->getRedis();
        $em = $this->getEntityManager();

        $queueKey = $this->keys['apiTransferInOutQueue'];

        $data = [
            'user_id' => null,
            'api_transfer_in' => true
        ];
        $data = json_encode($data);
        $redis->lpush($queueKey, $data);

        $this->runCommand('durian:sync-cash-fake', ['--api-transfer-in-out' => true]);

        $queue = json_decode($redis->rpop($queueKey . '_retry'), true);

        $this->assertNull($queue['user_id']);
        $this->assertTrue($queue['api_transfer_in']);

        $count = $redis->hget($queueKey . '_retry_count', $data);
        $this->assertEquals(1, $count);
    }

    /**
     * 測試處理同步api轉入轉出fail queue
     */
    public function testSyncApiTransferInOutRecoverFail()
    {
        $redis = $this->getRedis();
        $em = $this->getEntityManager();

        $queueKey = $this->keys['apiTransferInOutQueue'] . '_failed';

        $data = [
            'user_id' => 8,
            'api_transfer_in' => true
        ];

        $data = json_encode($data);
        $redis->lpush($queueKey, $data);

        $this->runCommand(
            'durian:sync-cash-fake',
            [
                '--api-transfer-in-out' => true,
                '--recover-fail' => true
            ]
        );

        $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', 8);

        $this->assertNotNull($userHasApiTransferInOut);
        $this->assertTrue($userHasApiTransferInOut->isApiTransferIn());
    }

    /**
     * 取得 MockContainer
     *
     * @param string $log log名稱
     * @return Container
     */
    private function getMockContainer($log)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $sqlLogger = $this->getContainer()->get('durian.logger_sql');
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get("monolog.handler.$log");
        $redis = $this->getContainer()->get('snc_redis.default');
        $managerLogger = $this->getContainer()->get('durian.logger_manager');

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
            ["monolog.handler.$log", 1, $handler],
            ['snc_redis.default', 1, $redis],
            ['durian.logger_manager', 1, $managerLogger],
            ['doctrine.orm.default_entity_manager', 1, $mockEm],
            ['doctrine.orm.his_entity_manager', 1, $mockEm]
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
     * 清除log檔
     */
    public function tearDown()
    {
        if (file_exists($this->entryQueueLogPath)) {
            unlink($this->entryQueueLogPath);
        }

        if (file_exists($this->queueLogPath)) {
            unlink($this->queueLogPath);
        }

        parent::tearDown();
    }
}
