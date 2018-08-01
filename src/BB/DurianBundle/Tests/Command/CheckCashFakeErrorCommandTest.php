<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashFake;

class CheckCashFakeErrorCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDataForCheckError'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures($classnames, 'his');

        $this->loadFixtures([], 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();
    }

    /**
     * 測試快開總計沒錯，不會顯示錯誤
     */
    public function testNormal()
    {
        $params = [
            '--begin' => '2013/10/10 01:00:00',
            '--end'   => '2013/10/10 01:10:00'
        ];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
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
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CashFakeError: cashFakeId: 1, userId: 7, balance: 70.0000, amount: 40.0000';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        // 檢查資料庫
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $error = $em->find('BBDurianBundle:CashFakeError', 1);

        $this->assertNotNull($error);
        $this->assertEquals(1, $error->getCashFakeId());
        $this->assertEquals(7, $error->getUserId());
        $this->assertEquals(156, $error->getCurrency());
        $this->assertEquals(70, $error->getBalance());
        $this->assertEquals(40, $error->getTotalAmount());

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有快開差異, 請檢查CashFakeError: cashFakeId: 1, userId: 7, balance: 70.0000, amount: 40.0000';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 測試順序錯誤,且餘額錯誤
     */
    public function testSequenceAndBalanceError()
    {
        $params = [
            '--begin' => '2013/11/11 01:00:00',
            '--end'   => '2013/11/11 01:10:00'
        ];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CashFakeError: cashFakeId: 2, userId: 8, balance: 250.0000, amount: 120.0000';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有快開差異, 請檢查CashFakeError: cashFakeId: 2, userId: 8, balance: 250.0000, amount: 120.0000';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 測試單純明細順序錯誤，餘額無誤
     */
    public function testSequenceError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        //新增出順序錯誤的明細資料
        $user = $em->find('BBDurianBundle:User', 9);
        $cashFake = new CashFake($user, 156);
        $cashFake->setBalance(100);
        $em->persist($cashFake);
        $em->flush();

        $sql = 'INSERT INTO `cash_fake_entry` (id, at, cash_fake_id, user_id, currency, opcode, created_at, '.
            'amount, memo, balance, ref_id, cash_fake_version) VALUES (24, 20150727010003, 3, 9, 901, 20001, '.
            "'2015-07-27 01:03:03', -10, '', 200, 0, 2),(25, 20150727010003, 3, 9, 901, 20002, '2015-07-27 01:03:03', ".
            "110, '', 210, 0, 1)";
        $conn->executeUpdate($sql);

        $params = [
            '--begin' => '2015/07/27 01:00:00',
            '--end'   => '2015/07/27 01:10:00'
        ];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CheckCashFakeErrorCommand end...';
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
            '--end'   => '2013/10/10 01:10:00',
            '--check-last' => true
        ];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));
        $this->assertCount(2, $results);
    }

    /**
     * 測試區間只有一筆明細，沒有前一筆，沒有CashFakeError
     */
    public function testNoPrevEntry()
    {
        $params = [
            '--begin' => '2013/10/10 01:00:00',
            '--end'   => '2013/10/10 01:01:00'
        ];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
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
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CashFakeError: cashFakeId: 1, userId: 7, balance: 70.0000, amount: -50.0000';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有快開差異, 請檢查CashFakeError: cashFakeId: 1, userId: 7, balance: 70.0000, amount: -50.0000';

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
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CashFakeError: cashFakeId: 1, userId: 7, balance: 120.0000, amount: 20.0000';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有快開差異, 請檢查CashFakeError: cashFakeId: 1, userId: 7, balance: 120.0000, amount: 20.0000';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 測試時間區間錯誤
     */
    public function testTimePeridError()
    {
        //有開始時間沒有結束時間
        $params = ['--begin' => '2014/11/11 01:10:00'];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $this->assertContains('Exception', $results[2]);
        $this->assertContains('需同時指定開始及結束時間', $results[3]);

        //有結束時間沒有開始時間
        $params = ['--end' => '2014/11/11 01:10:00'];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $this->assertContains('Exception', $results[2]);
        $this->assertContains('需同時指定開始及結束時間', $results[3]);

        //時間區間超過一天
        $params = [
            '--begin' => '2014/11/11 00:00:00',
            '--end'   => '2014/11/13 00:00:00'
        ];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $this->assertContains('Exception', $results[2]);
        $this->assertContains('請避免處理超過一天的資料', $results[3]);

        //結束時間小於開始時間
        $params = [
            '--begin' => '2014/11/11 00:00:00',
            '--end'   => '2014/11/10 00:00:00'
        ];
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $this->assertContains('Exception', $results[2]);
        $this->assertContains('無效的開始及結束時間', $results[3]);
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
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCashFakeErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCashFakeErrorCommand end...', $results[1]);
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
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCashFakeErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCashFakeErrorCommand end...', $results[1]);
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
        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCashFakeErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCashFakeErrorCommand end...', $results[1]);
    }

    /**
     * 測試該區間有多筆明細，但遺漏最後一筆明細
     */
    public function testCheckLoseLastEntry()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        $key = 'cash_fake_balance_8_156';

        // 更改cashFake的最後修改時間和餘額，製造該區間有遺失明細的狀況
        $redisWallet->hset($key, 'balance', 4000000);
        $redisWallet->hset($key, 'version', 13);

        // 設定redis值與cashFake相同
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $cashFake = $em->find('BBDurianBundle:CashFake', 2);
        $cashFake->setBalance(400);
        $cashFake->setLastEntryAt(20171201060059);
        $cashFake->setVersion(13);
        $em->flush();

        $params = [
            '--begin' => '2017/12/01 00:00:00',
            '--end' => '2017/12/01 23:00:00',
            '--check-last' => true
        ];

        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有快開差異, 請檢查CashFakeError: cashFakeId: 2, userId: 8, balance: 400.0000, amount: 400.0000';

        $queueMsg = json_decode($redis->rpop($key), true);
        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);

        // 確認有一筆錯誤訊息
        $this->assertContains('CheckCashFakeErrorCommand begin...', $results[0]);
        $this->assertContains('LoseLastEntry: userId: 8, redis balance: 400, max version entry balance: 280', $results[1]);
        $this->assertContains('CashFakeError: cashFakeId: 2, userId: 8, balance: 400.0000, amount: 400.0000', $results[2]);
        $this->assertContains('CheckCashFakeErrorCommand end...', $results[3]);
    }

    /**
     * 測試該區間只有一筆明細，但沒有撈到這筆明細，且明細餘額與cashFake餘額相同
     */
    public function testCheckLoseLastEntryAndOnlyOneEntry()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        $key = 'cash_fake_balance_8_156';

        // 設定最後一次寫入明細的時間，製造最後一筆明細遺失的狀況。
        $redisWallet->hset($key, 'balance', 2800000);
        $redisWallet->hset($key, 'version', 13);

        $params = [
            '--begin' => '2017/12/02 12:00:00',
            '--end' => '2017/12/02 13:00:00',
            '--check-last' => true
        ];

        // 設定cashFake值與redis相同
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $cashFake = $em->find('BBDurianBundle:CashFake', 2);
        $cashFake->setLastEntryAt(20171202120100);
        $cashFake->setVersion(13);
        $em->flush();

        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認有一筆錯誤訊息
        $this->assertContains('CheckCashFakeErrorCommand begin...', $results[0]);
        $this->assertContains('LoseLastEntry: userId: 8, redis balance same as max version entry balance. redis version: 13, entry max version: 12', $results[1]);
        $this->assertContains('CashFakeError: cashFakeId: 2, userId: 8, balance: 280.0000, amount: 280.0000', $results[2]);
        $this->assertContains('CheckCashFakeErrorCommand end...', $results[3]);
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

        $output = $this->runCommand('durian:cronjob:check-cash-fake-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認有一筆錯誤訊息
        $this->assertContains('CheckCashFakeErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCashFakeErrorCommand end...', $results[1]);
    }
}
