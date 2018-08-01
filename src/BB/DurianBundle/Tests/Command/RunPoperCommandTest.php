<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Consumer\Poper;
use BB\DurianBundle\Consumer\RecoveryPoper;
use BB\DurianBundle\Consumer\SyncHisPoper;

class RunPoperCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData'
        );

        $this->loadFixtures($classnames);

        $classnames = [];

        $this->loadFixtures($classnames, 'entry');
        $this->loadFixtures($classnames, 'his');
    }

    /**
     * test Card Poper,但發生redis connection timed out 的狀況
     */
    public function testCardPoperButRedisTimedOut()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 poper
            $poper = new Poper();
            $poper->runPop($mockContainer, 'card');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Card Poper.processRetryMessage() failed: ' . $e->getMessage();

            $queueMsg = json_decode($redis->rpop($key), true);

            $this->assertEquals('developer_acc', $queueMsg['type']);
            $this->assertEquals('Exception', $queueMsg['exception']);
            $this->assertContains($msg, $queueMsg['message']);
        }
    }

    /**
     * test Card Poper
     */
    public function testCardPoperExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $arrEntry = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'card_entry',
            'ERRCOUNT' => 0,
            'id' => 1001,
            'card_id' => 7,
            'user_id' => 8,
            'opcode' => 9901,
            'amount' => -100,
            'balance' => 1000,
            'created_at' => date('Y-m-d H:i:s'),
            'ref_id' => '',
            'operator' => ''
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'card_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 先改變要寫入的 entry 資料
        $arrEntry['id'] = 1002;
        $arrEntry['balance'] = 900;

        // 新增一筆資料到 retry queue 裡
        $queueName = 'card_retry_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 先確定沒 entry 資料
        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', 1001);
        $this->assertNull($entry);

        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', 1002);
        $this->assertNull($entry);

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-card-poper');
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', 1001);
        $this->assertEquals(1001, $entry->getId());
        $this->assertEquals(9901, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(1000, $entry->getBalance());

        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', 1002);
        $this->assertEquals(1002, $entry->getId());
        $this->assertEquals(9901, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(900, $entry->getBalance());
    }

    /**
     * test Card RecoveryPoper,但發生redis connection timed out 的狀況
     */
    public function testCardRecoveryPoperButRedisTimedOut()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 RecoveryPoper
            $poper = new RecoveryPoper();
            $poper->runPop($mockContainer, 'card');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Card RecoveryPoper failed: ' . $e->getMessage();

            $queueMsg = json_decode($redis->rpop($key), true);

            $this->assertEquals('developer_acc', $queueMsg['type']);
            $this->assertEquals('Exception', $queueMsg['exception']);
            $this->assertContains($msg, $queueMsg['message']);
        }
    }

    /**
     * test Card RecoveryPoper
     */
    public function testCardPoperParamsRecoverFail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $arrEntry = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'card_entry',
            'ERRCOUNT' => 0,
            'id' => 1003,
            'card_id' => 7,
            'user_id' => 8,
            'opcode' => 9901,
            'amount' => -100,
            'balance' => 800,
            'created_at' => date('Y-m-d H:i:s'),
            'ref_id' => '',
            'operator' => ''
        );

        // 新增一筆資料到 failed queue 裡
        $queueName = 'card_failed_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 先確定沒這筆資料
        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', 1003);
        $this->assertNull($entry);

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:run-card-poper', $params);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', 1003);
        $this->assertEquals(1003, $entry->getId());
        $this->assertEquals(9901, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(800, $entry->getBalance());
    }

    /**
     * test Cash Poper,但發生redis connection timed out 的狀況
     */
    public function testCashPoperButRedisTimedOut()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 poper
            $poper = new Poper();
            $poper->runPop($mockContainer, 'cash');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Cash Poper.processRetryMessage() failed: ' . $e->getMessage();

            $queueMsg = json_decode($redis->rpop($key), true);

            $this->assertEquals('developer_acc', $queueMsg['type']);
            $this->assertEquals('Exception', $queueMsg['exception']);
            $this->assertContains($msg, $queueMsg['message']);
        }
    }

    /**
     * test Cash Poper
     */
    public function testCashPoperExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $arrEntry = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'cash_entry',
            'ERRCOUNT' => 0,
            'id' => 1001,
            'cash_id' => 1,
            'user_id' => 2,
            'currency' => 901,
            'opcode' => 1001,
            'amount' => -100,
            'balance' => 1000,
            'at' => date('YmdHis'),
            'created_at' => date('Y-m-d H:i:s'),
            'memo' => '',
            'ref_id' => ''
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 先改變要寫入的 entry 資料
        $arrEntry['id'] = 1002;
        $arrEntry['balance'] = 900;

        // 新增一筆資料到 retry queue 裡
        $queueName = 'cash_retry_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 先確定沒 entry 資料
        $entry1 = $em->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 1001]);

        $this->assertNull($entry1);

        $entry2 = $em->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 1002]);

        $this->assertNull($entry2);

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $entry = $em->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 1001]);

        $this->assertEquals(1001, $entry->getId());
        $this->assertEquals(1, $entry->getCashId());
        $this->assertEquals(2, $entry->getUserId());
        $this->assertEquals(901, $entry->getCurrency());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(1000, $entry->getBalance());

        $entry = $em->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 1002]);

        $this->assertEquals(1002, $entry->getId());
        $this->assertEquals(1, $entry->getCashId());
        $this->assertEquals(2, $entry->getUserId());
        $this->assertEquals(901, $entry->getCurrency());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(900, $entry->getBalance());
    }

    /**
     * test Cash Poper with update
     */
    public function testCashPoperWithUpdate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $queueName = 'cash_queue';

        // 新增一筆 cash_trans 資料到 queue 裡
        $arrData = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'cash_trans',
            'ERRCOUNT' => 0,
            'id' => 1001,
            'cash_id' => 1,
            'user_id' => 2,
            'currency' => 901,
            'opcode' => 1001,
            'created_at' => '2012-01-01 12:00:00',
            'amount' => -100,
            'memo' => '',
            'ref_id' => '',
            'checked' => 0,
            'checked_at' => null
        );

        $redis->lpush($queueName, json_encode($arrData));

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 先確定資料已寫入
        $trans = $em->find('BB\DurianBundle\Entity\CashTrans', 1001);
        $this->assertEquals(1001, $trans->getId());
        $this->assertNull($trans->getCheckedAt());
        $this->assertFalse($trans->isChecked());
        $em->clear();

        // 新增一筆 update 資料到 queue 裡
        $arrUpdate = array(
            'HEAD' => 'UPDATE',
            'TABLE' => 'cash_trans',
            'KEY' => array('id' => 1001),
            'ERRCOUNT' => 0,
            'checked' => 1,
            'checked_at' => date('2012-01-02 12:00:00')
        );

        $redis->lpush($queueName, json_encode($arrUpdate));

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $trans = $em->find('BB\DurianBundle\Entity\CashTrans', 1001);
        $this->assertEquals(1001, $trans->getId());
        $this->assertEquals('2012-01-02 12:00:00', $trans->getCheckedAt()->format('Y-m-d H:i:s'));
        $this->assertTrue($trans->isChecked());
    }

    /**
     * test Cash RecoveryPoper,但發生redis connection timed out 的狀況
     */
    public function testCashRecoveryPoperButRedisTimedOut()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 RecoveryPoper
            $poper = new RecoveryPoper();
            $poper->runPop($mockContainer, 'cash');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Cash RecoveryPoper failed: ' . $e->getMessage();

            $queueMsg = json_decode($redis->rpop($key), true);

            $this->assertEquals('developer_acc', $queueMsg['type']);
            $this->assertEquals('Exception', $queueMsg['exception']);
            $this->assertContains($msg, $queueMsg['message']);
        }
    }

    /**
     * test Cash RecoveryPoper
     */
    public function testCashPoperParamsRecoverFail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $queueName = 'cash_failed_queue';

        // 新增一筆 cash_trans 資料到 failed queue 裡
        $arrData = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'cash_trans',
            'ERRCOUNT' => 0,
            'id' => 1002,
            'cash_id' => 1,
            'user_id' => 2,
            'currency' => 901,
            'opcode' => 1001,
            'created_at' => '2012-01-01 12:00:00',
            'amount' => -100,
            'memo' => '',
            'ref_id' => '',
            'checked' => 0,
            'checked_at' => null
        );

        $redis->lpush($queueName, json_encode($arrData));

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:run-cash-poper', $params);
        $this->assertEquals('', $output);

        // 先確定資料已寫入
        $trans = $em->find('BB\DurianBundle\Entity\CashTrans', 1002);
        $this->assertEquals(1002, $trans->getId());
        $this->assertNull($trans->getCheckedAt());
        $this->assertFalse($trans->isChecked());
        $em->clear();

        // 新增一筆 update 資料到 queue 裡
        $arrUpdate = array(
            'HEAD' => 'UPDATE',
            'TABLE' => 'cash_trans',
            'KEY' => array('id' => 1002),
            'ERRCOUNT' => 0,
            'checked' => 1,
            'checked_at' => date('2012-01-02 12:00:00')
        );

        $redis->lpush($queueName, json_encode($arrUpdate));

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:run-cash-poper', $params);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $trans = $em->find('BB\DurianBundle\Entity\CashTrans', 1002);
        $this->assertEquals(1002, $trans->getId());
        $this->assertEquals('2012-01-02 12:00:00', $trans->getCheckedAt()->format('Y-m-d H:i:s'));
        $this->assertTrue($trans->isChecked());
    }

    /**
     * 測試失敗回復時取到null或空陣列會drop
     */
    public function testPoperRecoverFailButGetNull()
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $queueName = 'cash_failed_queue';

        // 新增一筆null資料到queue裡
        $arrData = null;

        $redis->lpush($queueName, json_encode($arrData));

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:run-cash-poper', $params);
        $this->assertEquals('', $output);

        //確定null並沒有再塞回queue
        $this->assertEquals(0, $redis->llen($queueName));

        // 新增空陣列資料到 queue 裡
        $arrUpdate = array();

        $redis->lpush($queueName, json_encode($arrUpdate));

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:run-cash-poper', $params);
        $this->assertEquals('', $output);

        //確定null並沒有再塞回queue
        $this->assertEquals(0, $redis->llen($queueName));
    }

    /**
     * test SyncHis Poper
     */
    public function testSyncHisPoperExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $arrEntry = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'cash_entry',
            'ERRCOUNT' => 0,
            'id' => 1001,
            'cash_id' => 1,
            'user_id' => 2,
            'currency' => 901,
            'opcode' => 1001,
            'amount' => -100,
            'balance' => 1000,
            'at' => date('YmdHis'),
            'created_at' => date('Y-m-d H:i:s'),
            'memo' => '',
            'ref_id' => ''
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 先確定沒 entry 資料
        $entry = $em->getRepository('BB\DurianBundle\Entity\CashEntry')
                     ->findOneBy(array('id' => 1001));
        $this->assertNull($entry);

        // 先執行一次 cash poper 來產生 cash entry history poper
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:sync-his-poper');
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $entry = $em->getRepository('BB\DurianBundle\Entity\CashEntry')
                     ->findOneBy(array('id' => 1001));

        $this->assertEquals(1001, $entry->getId());
        $this->assertEquals(1, $entry->getCashId());
        $this->assertEquals(2, $entry->getUserId());
        $this->assertEquals(901, $entry->getCurrency());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(1000, $entry->getBalance());
    }

    /**
     * test SyncHis RecoverPoper
     */
    public function testSyncHisPoperParamsRecoverFail()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $arrEntry = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'cash_entry',
            'ERRCOUNT' => 0,
            'id' => 1003,
            'cash_id' => 1,
            'user_id' => 2,
            'currency' => 901,
            'opcode' => 1001,
            'amount' => -100,
            'balance' => 900,
            'at' => date('YmdHis'),
            'created_at' => date('Y-m-d H:i:s'),
            'memo' => '',
            'ref_id' => 0
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 新增一筆資料到 failed queue 裡
        $queueName = 'cash_entry_failed_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 先確定沒 entry 資料
        $entry = $em->getRepository('BB\DurianBundle\Entity\CashEntry')
                     ->findOneBy(array('id' => 1003));
        $this->assertNull($entry);

        // 先執行一次 cash poper 來產生 cash entry history poper
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:sync-his-poper', $params);
        $this->assertEquals('', $output);

        // 檢查資料是否寫入成功
        $entry = $em->getRepository('BB\DurianBundle\Entity\CashEntry')
                     ->findOneBy(array('id' => 1003));

        $this->assertEquals(1003, $entry->getId());
        $this->assertEquals(1, $entry->getCashId());
        $this->assertEquals(2, $entry->getUserId());
        $this->assertEquals(901, $entry->getCurrency());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(900, $entry->getBalance());
    }

    /**
     * test SyncHis poper,但發生redis connection timed out 的狀況
     */
    public function testSyncCashHisPoperButRedisTimedOut()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 SyncHisPoper
            $poper = new SyncHisPoper();
            $poper->runPop($mockContainer, 'cash_entry');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Cash Entry Poper.processRetryMessage() failed: ' . $e->getMessage();

            $queueMsg = json_decode($redis->rpop($key), true);

            $this->assertEquals('developer_acc', $queueMsg['type']);
            $this->assertEquals('Exception', $queueMsg['exception']);
            $this->assertContains($msg, $queueMsg['message']);
        }
    }

    /**
     * 測試失敗回復時取到null或空陣列會drop
     */
    public function testPoperHisRecoverFailButGetNull()
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $queueName = 'cash_entry_failed_queue';

        // 新增一筆null資料到queue裡
        $arrData = null;

        $redis->lpush($queueName, json_encode($arrData));

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:sync-his-poper', $params);
        $this->assertEquals('', $output);

        //確定null並沒有再塞回queue
        $this->assertEquals(0, $redis->llen($queueName));

        // 新增空陣列資料到 queue 裡
        $arrUpdate = array();

        $redis->lpush($queueName, json_encode($arrUpdate));

        // 執行 poper, 並檢查沒有錯誤訊息
        $params = array('--recover-fail' => true);
        $output = $this->runCommand('durian:sync-his-poper', $params);
        $this->assertEquals('', $output);

        //確定null並沒有再塞回queue
        $this->assertEquals(0, $redis->llen($queueName));
    }

    /**
     * test Poper insert fail
     */
    public function testPoperInsertFail()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        // 產生一筆錯誤的資料到 queue, 測試 retry 是否正常
        $arrEntry = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'table_error',
            'ERRCOUNT' => 9,
            'id' => 1002,
            'cash_id' => 1,
            'user_id' => 2,
            'currency' => 901,
            'opcode' => 1001,
            'amount' => -100,
            'balance' => 900,
            'at' => date('YmdHis'),
            'created_at' => date('Y-m-d H:i:s'),
            'memo' => '',
            'ref_id' => ''
        );

        // 新增一筆資料到 retry queue 裡
        $queueName = 'cash_retry_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 先執行一次 poper, 測試 retry 是否正常
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        $failedQueueName = 'cash_failed_queue';
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(0, $queueCount);

        // 先執行一次 poper, 測試 retry 是否正常
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 測試是否有正常推到 failed queue
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(1, $queueCount);
    }

    /**
     * test Poper update fail
     */
    public function testPoperUpdateFail()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        // 產生一筆資料到 retry queue, 測試 retry 是否正常
        $arrUpdate = array(
            'HEAD' => 'UPDATE',
            'TABLE' => 'table_error',
            'KEY' => array('id' => 1003),
            'ERRCOUNT' => 9,
            'checked' => 1,
            'checked_at' => date('2012-01-02 12:00:00')
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_queue';
        $redis->lpush($queueName, json_encode($arrUpdate));

        // 先執行一次 poper, 測試 retry 是否正常
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        $failedQueueName = 'cash_failed_queue';
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(0, $queueCount);

        // 先執行一次 poper, 測試 retry 是否正常
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 測試是否有正常推到 failed queue
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(1, $queueCount);
    }

    /**
     * test Poper insert retry to failed
     */
    public function testPoperInsertRetryToFailed()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        // 產生一筆資料到 retry queue, 測試 retry 是否正常
        $arrEntry = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'cash_entry',
            'ERRCOUNT' => 10,
            'id' => 1002,
            'cash_id' => 1,
            'user_id' => 2,
            'currency' => 901,
            'opcode' => 1001,
            'amount' => -100,
            'balance' => 900,
            'at' => date('YmdHis'),
            'created_at' => date('Y-m-d H:i:s'),
            'memo' => '',
            'ref_id' => ''
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_retry_queue';
        $redis->lpush($queueName, json_encode($arrEntry));

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 確定 retry queue 資料已經處理完畢
        $queueCount = $redis->llen($queueName);
        $this->assertEquals(0, $queueCount);

        // 檢查是否被推到 failed queue
        $failedQueueName = 'cash_failed_queue';
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(1, $queueCount);
    }

    /**
     * test Poper update retry to failed
     */
    public function testPoperUpdateRetryToFailed()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        // 產生一筆資料到 retry queue, 測試 retry 是否正常
        $arrUpdate = array(
            'HEAD' => 'UPDATE',
            'TABLE' => 'cash_trans',
            'KEY' => array('id' => 1003),
            'ERRCOUNT' => 10,
            'checked' => 1,
            'checked_at' => date('2012-01-02 12:00:00')
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_retry_queue';
        $redis->lpush($queueName, json_encode($arrUpdate));

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 確定 retry queue 資料已經處理完畢
        $queueCount = $redis->llen($queueName);
        $this->assertEquals(0, $queueCount);

        // 檢查是否被推到 failed queue
        $failedQueueName = 'cash_failed_queue';
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(1, $queueCount);
    }

    /**
     * test Poper with wrong head
     */
    public function testPoperWithWrongHead()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $key = 'cash_balance_1_901';
        $arrData = array(
            'HEAD' => 'SYNCHRONIZE',
            'KEY' => $key,
            'ERRCOUNT' => 0
        );

        // 新增一筆資料到 queue 裡
        $queueName = 'cash_queue';
        $redis->lpush($queueName, json_encode($arrData));

        // 執行 poper, 並檢查沒有錯誤訊息
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 確定 retry queue 資料已經處理完畢
        $queueCount = $redis->llen($queueName);
        $this->assertEquals(0, $queueCount);

        // 檢查是否被推到 failed queue
        $failedQueueName = 'cash_failed_queue';
        $queueCount = $redis->llen($failedQueueName);
        $this->assertEquals(1, $queueCount);
    }

    /**
     * 測試送到italking的例外訊息包含server ip與時間
     */
    public function testMessageToItalkingContainsIpAndTime()
    {
        $mockContainer = $this->getMockContainer();

        try {
            // 執行 poper
            $poper = new Poper();
            $poper->runPop($mockContainer, 'card');
        } catch (\Exception $e) {
            $redis = $this->getContainer()->get('snc_redis.default');
            $key = 'italking_exception_queue';
            $msg = 'Card Poper.processRetryMessage() failed: ' . $e->getMessage();
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
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();
        $getMap = [
            ['snc_redis.default', 1, $redis],
            ['durian.logger_manager', 1, $logManager],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.his_entity_manager', 1, $emHis],
            ['durian.italking_operator', 1, $italkingOperator]
        ];
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValue('test'));

        return $mockContainer;
    }
}
