<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CheckCashErrorCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryDataForCheckError'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryDataForCheckError'
        ];
        $this->loadFixtures($classnames, 'entry');
        $this->loadFixtures($classnames, 'his');

        $this->loadFixtures([], 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();
    }

    /**
     * 測試金額總計沒錯，不會顯示錯誤
     */
    public function testNormal()
    {
        $params = [
            '--begin' => '2013/10/10 01:00:00',
            '--end'   => '2013/10/10 01:10:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));
        $this->assertCount(2, $results);
    }

    /**
     * 測試掉單
     */
    public function testMissingEntry()
    {
        $params = [
            '--begin' => '2013/10/10 01:00:00',
            '--end'   => '2013/10/10 01:20:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CashError: cashId: 7, userId: 8, balance: 70.0000, amount: 40.0000';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        // 檢查資料庫
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $error = $em->find('BBDurianBundle:CashError', 1);

        $this->assertNotNull($error);
        $this->assertEquals(7, $error->getCashId());
        $this->assertEquals(8, $error->getUserId());
        $this->assertEquals(901, $error->getCurrency());
        $this->assertEquals(70, $error->getBalance());
        $this->assertEquals(40, $error->getTotalAmount());

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有現金差異, 請檢查CashError: cashId: 7, userId: 8, balance: 70.0000, amount: 40.0000';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 測試順序錯誤，且餘額錯誤
     */
    public function testSequenceAndBalanceError()
    {
        $params = [
            '--begin' => '2013/11/11 01:00:00',
            '--end'   => '2013/11/11 01:10:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CashError: cashId: 6, userId: 7, balance: 250.0000, amount: 120.0000';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有現金差異, 請檢查CashError: cashId: 6, userId: 7, balance: 250.0000, amount: 120.0000';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 測試單純明細順序錯誤，餘額無誤
     */
    public function testSequenceError()
    {
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        //新增出順序錯誤的明細資料
        $sql = 'INSERT INTO `cash_entry` (id, at, cash_id, user_id, currency, opcode, created_at, '.
            'amount, memo, balance, ref_id, cash_version) VALUES (15, 20150727010003, 4, 5, 901, 20001, '.
            "'2015-07-27 01:03:03', -10, '', 200, 0, 2),(16, 20150727010003, 4, 5, 901, 20002, '2015-07-27 01:03:03', ".
            "110, '', 210, 0, 1)";
        $conn->executeUpdate($sql);

        $params = [
            '--begin' => '2015/07/27 01:00:00',
            '--end'   => '2015/07/27 01:10:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CheckCashErrorCommand end...';
        $this->assertContains($msg, $results[1]);
        $this->assertCount(2, $results);
    }

    /**
     * 測試檢查區間內只有一筆明細，會往前找明細，並比對無誤
     */
    public function testCheckNoErrorWithOneEntry()
    {
        $params = [
            '--begin' => '2013/10/10 01:03:00',
            '--end'   => '2013/10/10 01:10:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));
        $this->assertCount(2, $results);
    }

    /**
     * 測試檢查區間內只有一筆明細，會往前找明細，並發現掉單
     */
    public function testFoundEntryMissingWithOneEntry()
    {
        $params = [
            '--begin' => '2013/10/10 01:10:00',
            '--end'   => '2013/10/10 01:20:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CashError: cashId: 7, userId: 8, balance: 70.0000, amount: -50.0000';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有現金差異, 請檢查CashError: cashId: 7, userId: 8, balance: 70.0000, amount: -50.0000';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 測試檢查區間內只有一筆明細，會往前找明細，並發現順序錯誤
     */
    public function testFoundEntrySequenceErrorWithOneEntry()
    {
        $params = [
            '--begin' => '2014/11/11 01:10:00',
            '--end'   => '2014/11/11 01:20:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CashError: cashId: 8, userId: 9, balance: 120.0000, amount: 20.0000';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有現金差異, 請檢查CashError: cashId: 8, userId: 9, balance: 120.0000, amount: 20.0000';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 測試檢查區間內只有一筆明細，能根據version找到前一筆時間異常的明細，餘額無誤
     */
    public function testCheckNoErrorWithOneEntryWhenPreviousEntryHasWrongTime()
    {
        $params = [
            '--begin' => '2015/09/30 11:00:00',
            '--end' => '2015/09/30 12:00:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCashErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCashErrorCommand end...', $results[1]);
    }

    /**
     * 測試區間內明細順序異常，餘額無誤
     */
    public function testCheckNoErrorWithVersionSequenceError()
    {
        $params = [
            '--begin' => '2015/09/30 02:00:00',
            '--end' => '2015/09/30 03:00:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCashErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCashErrorCommand end...', $results[1]);
    }

    /**
     * 測試該區間有漏明細，而漏的該筆明細跨小時的情況，餘額無誤
     */
    public function testCheckNoErrorWhenMissingEntryIsCrossHour()
    {
        $params = [
            '--begin' => '2015/09/30 05:00:00',
            '--end' => '2015/09/30 06:00:00'
        ];
        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCashErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCashErrorCommand end...', $results[1]);
    }

    /**
     * 測試該區間有多筆明細，但遺漏最後一筆明細
     */
    public function testCheckLoseLastEntry()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        $key = 'cash_balance_4_901';

        // 將redis版號調整成與DataFixture的Cash的版號、餘額相同
        $redisWallet->hset($key, 'balance', 13000000);
        $redisWallet->hset($key, 'version', 5);

        // 更改cash的最後修改時間與明細
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 3);
        $cash->setBalance(1300);
        $cash->setLastEntryAt(20171201060059);
        $em->flush();

        $params = [
            '--begin' => '2017/12/01 00:00:00',
            '--end' => '2017/12/01 23:00:00',
            '--check-last' => true
        ];

        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有現金差異, 請檢查CashError: cashId: 3, userId: 4, balance: 1300.0000, amount: 1300.0000';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);

        // 確認有一筆錯誤訊息
        $this->assertContains('CheckCashErrorCommand begin...', $results[0]);
        $this->assertContains('LoseLastEntry: userId: 4, redis balance: 1300, max version entry balance: 1180', $results[1]);
        $this->assertContains('CashError: cashId: 3, userId: 4, balance: 1300.0000, amount: 1300.0000', $results[2]);
        $this->assertContains('CheckCashErrorCommand end...', $results[3]);
    }

    /**
     * 測試該使用者只有一筆明細，但沒有撈到這筆明細
     */
    public function testCheckLoseLastEntryAndOnlyOneEntry()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        $key = 'cash_balance_5_901';

        // 將redis版號調整成與DataFixture的Cash的版號、餘額相同
        $redisWallet->hset($key, 'balance', 13000000);
        $redisWallet->hset($key, 'version', 5);

        // 制造該區間應有一筆明細，但沒撈到的情境
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 4);
        $cash->setBalance(1300);
        $cash->setLastEntryAt(20171202060059);
        $em->flush();

        $params = [
            '--begin' => '2017/12/02 00:00:00',
            '--end' => '2017/12/02 23:00:00',
            '--check-last' => true
        ];

        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認有一筆錯誤訊息
        $this->assertContains('CheckCashErrorCommand begin...', $results[0]);
        $this->assertContains('LoseLastEntry: userId: 5, no entry in mysql', $results[1]);
        $this->assertContains('CashError: cashId: 4, userId: 5, balance: 1300.0000, amount: 1300.0000', $results[2]);
        $this->assertContains('CheckCashErrorCommand end...', $results[3]);
    }

    /**
     * 測試該區間是否有遺漏，資料正常
     */
    public function testCheckNoErrorWithParameterCheckLast()
    {
        $params = [
            '--begin' => '2017/12/01 00:00:00',
            '--end' => '2017/12/01 23:00:00',
            '--check-last' => true
        ];

        $output = $this->runCommand('durian:check-cash-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認有一筆錯誤訊息
        $this->assertContains('CheckCashErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCashErrorCommand end...', $results[1]);
    }
}
