<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Consumer\SyncRecoveryPoper;

class RunSyncCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * test Card SyncPoper
     */
    public function testCardSyncPoperExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        $key = 'card_balance_2';
        $arrEntry = array(
            'HEAD' => 'SYNCHRONIZE',
            'KEY' => $key,
            'ERRCOUNT' => 0
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'card_sync_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 改變 redis 中的 balance, last_balance, version
        $redisWallet->hsetnx($key, 'balance', 1000);
        $redisWallet->hsetnx($key, 'last_balance', 1000);
        $redisWallet->hsetnx($key, 'version', 2);

        // 先檢查修改前的資料
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $card = $user->getCard()->toArray();

        $this->assertEquals(1, $card['id']);
        $this->assertEquals(2, $card['user_id']);
        $this->assertEquals(0, $card['balance']);
        $this->assertEquals(0, $card['last_balance']);
        $this->assertEquals(0, $card['percentage']);

        // 清空暫存資料
        $em->clear();
        $card = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-card-sync');
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $card = $user->getCard()->toArray();

        $this->assertEquals(1, $card['id']);
        $this->assertEquals(2, $card['user_id']);
        $this->assertEquals(1000, $card['balance']);
        $this->assertEquals(1000, $card['last_balance']);
        $this->assertEquals(100, $card['percentage']);
    }

    /**
     * test Card SyncPoper Retry
     */
    public function testCardSyncPoperRetry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        $key = 'card_balance_2';
        $arrEntry = array(
            'HEAD' => 'SYNCHRONIZE',
            'KEY' => $key,
            'ERRCOUNT' => 0
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'card_sync_retry_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 改變 redis 中的 balance, last_balance, version
        $redisWallet->hsetnx($key, 'balance', 900);
        $redisWallet->hsetnx($key, 'last_balance', 1000);
        $redisWallet->hsetnx($key, 'version', 2);

        // 先檢查修改前的資料
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $card = $user->getCard()->toArray();

        $this->assertEquals(1, $card['id']);
        $this->assertEquals(2, $card['user_id']);
        $this->assertEquals(0, $card['balance']);
        $this->assertEquals(0, $card['last_balance']);
        $this->assertEquals(0, $card['percentage']);

        // 清空暫存資料
        $em->clear();
        $card = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-card-sync');
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $card = $user->getCard()->toArray();

        $this->assertEquals(1, $card['id']);
        $this->assertEquals(2, $card['user_id']);
        $this->assertEquals(900, $card['balance']);
        $this->assertEquals(1000, $card['last_balance']);
        $this->assertEquals(90, $card['percentage']);
    }

    /**
     * test Card RecoveryPoper
     */
    public function testCardSyncPoperParamsRecoverFail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        $key = 'card_balance_2';
        $arrEntry = array(
            'HEAD' => 'SYNCHRONIZE',
            'KEY' => $key,
            'ERRCOUNT' => 0
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'card_sync_failed_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 改變 redis 中的 balance, last_balance, version
        $redisWallet->hsetnx($key, 'balance', 800);
        $redisWallet->hsetnx($key, 'last_balance', 1000);
        $redisWallet->hsetnx($key, 'version', 2);

        // 先檢查修改前的資料
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $card = $user->getCard()->toArray();

        $this->assertEquals(1, $card['id']);
        $this->assertEquals(2, $card['user_id']);
        $this->assertEquals(0, $card['balance']);
        $this->assertEquals(0, $card['last_balance']);
        $this->assertEquals(0, $card['percentage']);

        // 清空暫存資料
        $em->clear();
        $card = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:run-card-sync', $params);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $card = $user->getCard()->toArray();

        $this->assertEquals(1, $card['id']);
        $this->assertEquals(2, $card['user_id']);
        $this->assertEquals(800, $card['balance']);
        $this->assertEquals(1000, $card['last_balance']);
        $this->assertEquals(80, $card['percentage']);
    }

    /**
     * test Cash SyncPoper
     */
    public function testCashSyncPoperExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        $key = 'cash_balance_2_901';
        $arrEntry = [
            'HEAD' => 'CASHSYNCHRONIZE',
            'KEY' => $key,
            'ERRCOUNT' => 0,
            'id' => '1',
            'user_id' => '2',
            'balance' => 1000,
            'pre_sub' => 20,
            'pre_add' => 10,
            'version' => 3,
            'currency' => '901'
        ];

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_sync_queue0';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 改變 redis 中的 balance, pre_add, pre_sub, version
        $redisWallet->hsetnx($key, 'balance', 10000000);
        $redisWallet->hsetnx($key, 'pre_add', 100000);
        $redisWallet->hsetnx($key, 'pre_sub', 200000);
        $redisWallet->hsetnx($key, 'version', 2);

        // 先檢查修改前的資料
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(1000, $cash['balance']);
        $this->assertEquals(0, $user->getCash()->getPreAdd());
        $this->assertEquals(0, $user->getCash()->getPreSub());
        $this->assertFalse($negative);

        // 清空暫存資料
        $em->clear();
        $cash = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(980, $cash['balance']);
        $this->assertEquals(10, $user->getCash()->getPreAdd());
        $this->assertEquals(20, $user->getCash()->getPreSub());
        $this->assertFalse($negative);

        // 新增一筆資料到 queue 裡，測試餘額負數
        $arrEntry['balance'] = -1;
        $arrEntry['pre_sub'] = 0;
        $arrEntry['pre_add'] = 0;
        $arrEntry['version'] = 4;
        $redis->lpush($queueName, json_encode($arrEntry));

        $redisWallet->hset($key, 'balance', -10000);
        $redisWallet->hset($key, 'pre_add', 0);
        $redisWallet->hset($key, 'pre_sub', 0);
        $redisWallet->hset($key, 'version', 3);

        // 清空暫存資料
        $em->clear();
        $cash = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $user = $em->find('BBDurianBundle:User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(-1, $cash['balance']);
        $this->assertEquals(0, $user->getCash()->getPreAdd());
        $this->assertEquals(0, $user->getCash()->getPreSub());
        $this->assertTrue($negative);
    }

    /**
     * test Cash SyncPoper Retry
     */
    public function testCashSyncPoperRetry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        $key = 'cash_balance_2_901';
        $arrEntry = [
            'HEAD' => 'CASHSYNCHRONIZE',
            'KEY' => $key,
            'ERRCOUNT' => 0,
            'id' => '1',
            'user_id' => '2',
            'balance' => 900,
            'pre_sub' => 20,
            'pre_add' => 10,
            'version' => 3,
            'currency' => '901'
        ];

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_sync_retry_queue0';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 改變 redis 中的 balance, pre_add, pre_sub, version
        $redisWallet->hsetnx($key, 'balance', 9000000);
        $redisWallet->hsetnx($key, 'pre_add', 100000);
        $redisWallet->hsetnx($key, 'pre_sub', 200000);
        $redisWallet->hsetnx($key, 'version', 2);

        // 先檢查修改前的資料
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(1000, $cash['balance']);
        $this->assertEquals(0, $user->getCash()->getPreAdd());
        $this->assertEquals(0, $user->getCash()->getPreSub());
        $this->assertFalse($negative);

        // 清空暫存資料
        $em->clear();
        $cash = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(880, $cash['balance']);
        $this->assertEquals(10, $user->getCash()->getPreAdd());
        $this->assertEquals(20, $user->getCash()->getPreSub());
        $this->assertFalse($negative);

        // 新增一筆資料到 queue 裡，測試餘額負數
        $arrEntry['balance'] = -1;
        $arrEntry['pre_sub'] = 0;
        $arrEntry['pre_add'] = 0;
        $arrEntry['version'] = 4;
        $redis->lpush($queueName, json_encode($arrEntry));

        $redisWallet->hset($key, 'balance', -10000);
        $redisWallet->hset($key, 'pre_add', 0);
        $redisWallet->hset($key, 'pre_sub', 0);
        $redisWallet->hset($key, 'version', 3);

        // 清空暫存資料
        $em->clear();
        $cash = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $user = $em->find('BBDurianBundle:User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(-1, $cash['balance']);
        $this->assertEquals(0, $user->getCash()->getPreAdd());
        $this->assertEquals(0, $user->getCash()->getPreSub());
        $this->assertTrue($negative);
    }

    /**
     * test Cash RecoveryPoper
     */
    public function testCashSyncPoperParamsRecoverFail()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        $key = 'cash_balance_2_901';
        $arrEntry = [
            'HEAD' => 'CASHSYNCHRONIZE',
            'KEY' => $key,
            'ERRCOUNT' => 0,
            'id' => '1',
            'user_id' => '2',
            'balance' => 800,
            'pre_sub' => 20,
            'pre_add' => 10,
            'version' => 3,
            'currency' => '901'
        ];

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_sync_failed_queue0';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 改變 redis 中的 balance, pre_add, pre_sub, version
        $redisWallet->hsetnx($key, 'balance', 8000000);
        $redisWallet->hsetnx($key, 'pre_add', 100000);
        $redisWallet->hsetnx($key, 'pre_sub', 200000);
        $redisWallet->hsetnx($key, 'version', 2);

        // 先檢查修改前的資料
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(1000, $cash['balance']);
        $this->assertEquals(0, $user->getCash()->getPreAdd());
        $this->assertEquals(0, $user->getCash()->getPreSub());
        $this->assertFalse($negative);

        // 清空暫存資料
        $em->clear();
        $cash = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = ['--executeQueue' => 0, '--recover-fail' => true];
        $output = $this->runCommand('durian:run-cash-sync', $params);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(780, $cash['balance']);
        $this->assertEquals(10, $user->getCash()->getPreAdd());
        $this->assertEquals(20, $user->getCash()->getPreSub());
        $this->assertFalse($negative);

        // 新增一筆資料到 queue 裡，測試餘額負數
        $arrEntry['balance'] = -1;
        $arrEntry['pre_sub'] = 0;
        $arrEntry['pre_add'] = 0;
        $arrEntry['version'] = 4;
        $redis->lpush($queueName, json_encode($arrEntry));

        $redisWallet->hset($key, 'balance', -10000);
        $redisWallet->hset($key, 'pre_add', 0);
        $redisWallet->hset($key, 'pre_sub', 0);
        $redisWallet->hset($key, 'version', 3);

        // 清空暫存資料
        $em->clear();
        $cash = null;

        // 執行 poper, 並檢查沒有錯誤訊息
        $this->runCommand('durian:run-cash-sync', $params);

        // 檢查資料是否寫入成功
        $user = $em->find('BBDurianBundle:User', 2);
        $cash = $user->getCash()->toArray();
        $negative = $user->getCash()->getNegative();

        $this->assertEquals(1, $cash['id']);
        $this->assertEquals(2, $cash['user_id']);
        $this->assertEquals(-1, $cash['balance']);
        $this->assertEquals(0, $user->getCash()->getPreAdd());
        $this->assertEquals(0, $user->getCash()->getPreSub());
        $this->assertTrue($negative);
    }

    /**
     * 測試失敗回復時取到null或空陣列會drop
     */
    public function testSyncPoperRecoverFailButGetNull()
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $queueName = 'cash_sync_failed_queue0';

        // 新增一筆null資料到queue裡
        $arrData = null;

        $redis->lpush($queueName, json_encode($arrData));

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = ['--executeQueue' => 0, '--recover-fail' => true];
        $output = $this->runCommand('durian:run-cash-sync', $params);
        $this->assertEquals('', $output);

        //確定null並沒有再塞回queue
        $this->assertEquals(0, $redis->llen($queueName));

        // 新增空陣列資料到 queue 裡
        $arrUpdate = array();

        $redis->lpush($queueName, json_encode($arrUpdate));

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-sync', $params);
        $this->assertEquals('', $output);

        //確定null並沒有再塞回queue
        $this->assertEquals(0, $redis->llen($queueName));
    }

    /**
     * test SyncPoper retry to failed
     */
    public function testSyncPoperRetryToFailed()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $key = 'cash_balance_2_901';
        $arrEntry = [
            'HEAD' => 'CASHSYNCHRONIZE',
            'KEY' => $key,
            'ERRCOUNT' => 10,
            'id' => '1',
            'user_id' => '2',
            'balance' => -1,
            'pre_sub' => 0,
            'pre_add' => 0,
            'version' => 3,
            'currency' => '901'
        ];

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_sync_retry_queue0';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);
        $this->assertEquals('', $output);

        // 確定 retry queue 資料已經處理完畢
        $queueCount = $redis->llen($queueName);
        $this->assertEquals(0, $queueCount);

        // 檢查是否被推到 failed queue
        $failedQueueName = 'cash_sync_failed_queue0';
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(1, $queueCount);
    }

    /**
     * test SyncPoper with wrong head
     */
    public function testSyncPoperWithWrongHead()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        // 產生一筆錯誤的資料到 queue, 測試 retry 是否正常
        $arrEntry = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'cash_entry',
            'ERRCOUNT' => 0,
            'id' => 1002,
            'cash_id' => 1,
            'opcode' => 1001,
            'amount' => -100,
            'balance' => 900,
            'at' => date('YmdHis'),
            'created_at' => date('Y-m-d H:i:s'),
            'memo' => '',
            'ref_id' => ''
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_sync_queue0';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);
        $this->assertEquals('', $output);

        // 確定 retry queue 資料已經處理完畢
        $queueCount = $redis->llen($queueName);
        $this->assertEquals(0, $queueCount);

        // 檢查是否被推到 failed queue
        $failedQueueName = 'cash_sync_failed_queue0';
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(1, $queueCount);
    }

    /**
     * test Card SyncRecoveryPoper,但發生redis connection timed out 的狀況
     */
    public function testCardSyncPoperRecoverFailButRedisTimedOut()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 SyncRecoveryPoper
            $poper = new SyncRecoveryPoper();
            $poper->runPop($mockContainer, 'card');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Card SyncRecoveryPoper failed: ' . $e->getMessage();

            $queueMsg = json_decode($redis->rpop($key), true);

            $this->assertEquals('developer_acc', $queueMsg['type']);
            $this->assertEquals('Exception', $queueMsg['exception']);
            $this->assertContains($msg, $queueMsg['message']);
        }
    }

    /**
     * test Cash SyncRecoveryPoper,但發生redis connection timed out 的狀況
     */
    public function testCashSyncPoperRecoverFailButRedisTimedOut()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 SyncRecoveryPoper
            $poper = new SyncRecoveryPoper();
            $poper->runPop($mockContainer, 'cash');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Cash SyncRecoveryPoper failed: ' . $e->getMessage();

            $queueMsg = json_decode($redis->rpop($key), true);

            $this->assertEquals('developer_acc', $queueMsg['type']);
            $this->assertEquals('Exception', $queueMsg['exception']);
            $this->assertContains($msg, $queueMsg['message']);
        }
    }

    /**
     * 測試送到italking的例外訊息包含server ip與時間
     */
    public function testMessageToItalkingContainsIpAndTime()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 SyncRecoveryPoper
            $poper = new SyncRecoveryPoper();
            $poper->runPop($mockContainer, 'card');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Card SyncRecoveryPoper failed: ' . $e->getMessage();
            $msg = preg_quote($msg, '/');

            // 檢查server ip與時間格式 ex:[] [2014-11-12 10:50:23]
            $pattern = "/\[\S*\] \[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] $msg/";

            $queueMsg = json_decode($redis->rpop($key), true);

            $this->assertEquals('developer_acc', $queueMsg['type']);
            $this->assertEquals('Exception', $queueMsg['exception']);
            $this->assertRegExp($pattern, $queueMsg['message']);
        }
    }

    /**
     * 取得 MockContainer
     *
     * @return Container
     */
    private function getMockContainer()
    {
        $redis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['llen', 'rpop'])
            ->getMock();

        $redis->expects($this->any())
            ->method('llen')
            ->will($this->returnValue(10));

        $redis->expects($this->any())
            ->method('rpop')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $logManager = $this->getContainer()->get('durian.logger_manager');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $getMap = [
            ['snc_redis.default', 1, $redis],
            ['durian.logger_manager', 1, $logManager],
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.italking_operator', 1, $italkingOperator]
        ];
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }
}
