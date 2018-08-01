<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\CashFakeEntry;

class CheckBalanceCommandTest extends WebTestCase
{
    /**
     * log 檔的路徑
     */
    private $logPath;

    /**
     * sql 檔的路徑
     */
    private $sqlFilePath;

    /**
     * redis2 檔的路徑
     */
    private $redis2FilePath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashTransData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
        ];

        $this->loadFixtures($classnames);

        $this->loadFixtures([], 'entry');

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'check_balance.log';

        $fileDir = $this->getContainer()->get('kernel')->getRootDir();
        $this->sqlFilePath = $fileDir . '/../sqlOutput.sql';
        $this->redis2FilePath = $fileDir . '/../redis2Output.txt';
    }

    /**
     * 測試檢查餘額，但帶入不合法交易方式
     */
    public function testCheckBalanceWithInvalidPayway()
    {
        $params = ['--pay-way' => 'card'];
        $output = $this->runCommand('durian:check-balance', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('Invalid payway', $output);
    }

    /**
     * 測試檢查餘額，但未指定開始日期
     */
    public function testCheckBalanceWithoutStartDate()
    {
        $params = [
            '--pay-way' => 'cash',
            '--end' => '2013-01-01'
        ];
        $output = $this->runCommand('durian:check-balance', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('需同時指定開始及結束日期', $output);
    }

    /**
     * 測試檢查餘額，但未指定結束日期
     */
    public function testCheckBalanceWithoutEndDate()
    {
        $params = [
            '--pay-way' => 'cash',
            '--start' => '2013-01-01'
        ];
        $output = $this->runCommand('durian:check-balance', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('需同時指定開始及結束日期', $output);
    }

    /**
     * 測試檢查餘額
     */
    public function testCheckBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $redis4 = $this->getContainer()->get('snc_redis.wallet4');
        $redis3 = $this->getContainer()->get('snc_redis.wallet3');
        $redis2 = $this->getContainer()->get('snc_redis.wallet2');

        $cash1 = $em->find('BBDurianBundle:Cash', 1);

        $time = new \DateTime('2013-01-01 12:00:00');
        $entry1 = new CashEntry($cash1, 1001, 300);
        $entry1->setId(1);
        $entry1->setRefId(1);
        $cash1->setBalance(300);
        $entry1->setAt(20130101120000);
        $entry1->setCreatedAt($time);
        $entry1->setCashVersion(3);
        $em->remove($cash1);
        $emEntry->persist($entry1);

        $cash2 = $em->find('BBDurianBundle:Cash', 2);

        $entry2 = new CashEntry($cash2, 1001, 100);
        $entry2->setId(3);
        $entry2->setRefId(3);
        $cash2->setBalance(500);
        $entry2->setAt(20130101120000);
        $entry2->setCreatedAt($time);
        $entry2->setCashVersion(4);
        $emEntry->persist($entry2);

        $em->flush();
        $emEntry->flush();

        $redis2->hset('cash_balance_2_901', 'balance', 3000000);
        $redis2->hset('cash_balance_2_901', 'pre_sub', 0);
        $redis2->hset('cash_balance_2_901', 'pre_add', 0);
        $redis2->hset('cash_balance_2_901', 'version', 3);
        $redis2->hset('cash_balance_2_901', 'last_entry_at', 20130101120000);

        $redis3->hset('cash_balance_3_901', 'balance', 1000000);
        $redis3->hset('cash_balance_3_901', 'pre_sub', 0);
        $redis3->hset('cash_balance_3_901', 'pre_add', 0);
        $redis3->hset('cash_balance_3_901', 'version', 4);
        $redis3->hset('cash_balance_3_901', 'last_entry_at', 20130101120000);

        $params = [
            '--start'   => '2013-01-01',
            '--end'     => '2013-01-31',
            '--pay-way' => 'cash'
        ];
        $output = $this->runCommand('durian:check-balance', $params);
        $results = explode(PHP_EOL, $output);

        $msg = 'CheckBalanceCommand start.';
        $this->assertEquals($msg, $results[0]);
        $ret = 'userId: 3 餘額不正確, 若查看資料庫 cash version 仍低於 4, 請執行:';
        $sql = 'UPDATE cash SET balance = 100, pre_sub = 0, pre_add = 0, last_entry_at = 20130101120000, version = 4 ' .
            'WHERE user_id = 3 AND currency = 901 AND version < 4;';
        $this->assertEquals($ret, $results[1]);
        $this->assertEquals($sql, $results[2]);
        $msg = 'CheckBalanceCommand finish.';
        $this->assertEquals($msg, $results[3]);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains($ret, $results[1]);
        $this->assertContains($sql, $results[2]);

        unlink($this->logPath);

        $fake1 = $em->find('BBDurianBundle:CashFake', 1);

        $entry1 = new CashFakeEntry($fake1, 1001, 300);
        $entry1->setId(1);
        $entry1->setRefId(1);
        $fake1->setBalance(300);
        $entry1->setAt(20130101120000);
        $entry1->setCreatedAt($time);
        $entry1->setCashFakeVersion(3);
        $em->persist($entry1);

        $fake2 = $em->find('BBDurianBundle:CashFake', 2);

        $entry2 = new CashFakeEntry($fake2, 1001, 100);
        $entry2->setId(3);
        $entry2->setRefId(3);
        $fake2->setBalance(500);
        $entry2->setAt(20130101120000);
        $entry2->setCreatedAt($time);
        $entry2->setCashFakeVersion(4);
        $em->persist($entry2);

        $em->flush();

        $redis4->hset('cash_fake_balance_8_156', 'balance', 1000000);
        $redis4->hset('cash_fake_balance_8_156', 'pre_sub', 0);
        $redis4->hset('cash_fake_balance_8_156', 'pre_add', 0);
        $redis4->hset('cash_fake_balance_8_156', 'version', 4);

        $params['--pay-way'] = 'cash_fake';
        $output = $this->runCommand('durian:check-balance', $params);

        $results = explode(PHP_EOL, $output);

        $ret = 'userId: 8 餘額不正確, 若查看資料庫 cash_fake version 仍低於 4, 請執行:';
        $sql = 'UPDATE cash_fake SET balance = 100, pre_sub = 0, pre_add = 0, version = 4 ' .
            'WHERE user_id = 8 AND currency = 156 AND version < 4;';
        $this->assertEquals($ret, $results[1]);
        $this->assertEquals($sql, $results[2]);
    }

    /**
     * 測試檢查預扣/存
     */
    public function testCheckPreSubAndPreAdd()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.wallet2');
        $dbName = $this->getContainer()->getParameter('database_name');

        $cash1 = $em->find('BBDurianBundle:Cash', 1);
        $cash1->addPreSub(1);

        $cash2 = $em->find('BBDurianBundle:Cash', 2);
        $cash2->addPreAdd(999);

        $em->flush();

        $redis->hset('cash_balance_2_901', 'balance', 0);
        $redis->hset('cash_balance_2_901', 'pre_sub', 10000);
        $redis->hset('cash_balance_2_901', 'pre_add', 0);
        $redis->hset('cash_balance_2_901', 'version', 3);
        $redis->hset('cash_balance_2_901', 'last_entry_at', 20130101120000);

        $params = [
            '--start'        => '2013-01-01',
            '--end'          => '2013-01-31',
            '--pay-way'      => 'cash',
            '--check-column' => 'pre_sub'
        ];
        $output = $this->runCommand('durian:check-balance', $params);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $msg = 'CheckBalanceCommand start.';
        $this->assertContains($msg, $results[0]);
        $sqlmsg = 'UPDATE cash SET pre_sub = pre_sub -1, version = 3 WHERE user_id = 2 AND version < 3;';
        $this->assertContains($sqlmsg, $results[1]);
        $msg1 = 'redis-wallet2:';
        $msg2 = 'multi';
        $msg3 = "hincrBy {$dbName}_cash_balance_2_901 pre_sub -10000";
        $msg4 = "hincrBy {$dbName}_cash_balance_2_901 version 1";
        $msg5 = 'exec';
        $this->assertContains($msg1, $results[5]);
        $this->assertContains($msg2, $results[6]);
        $this->assertContains($msg3, $results[7]);
        $this->assertContains($msg4, $results[8]);
        $this->assertContains($msg5, $results[9]);
        $msg = 'CheckBalanceCommand finish.';
        $this->assertContains($msg, $results[11]);

        $content = file_get_contents($this->sqlFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals($sqlmsg, $results[0]);

        $content = file_get_contents($this->redis2FilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals($msg1, $results[0]);
        $this->assertEquals($msg2, $results[1]);
        $this->assertEquals($msg3, $results[2]);
        $this->assertEquals($msg4, $results[3]);
        $this->assertEquals($msg5, $results[4]);

        unlink($this->logPath);
        unlink($this->sqlFilePath);
        unlink($this->redis2FilePath);

        $params['--check-column'] = 'pre_add';
        $output = $this->runCommand('durian:check-balance', $params);

        $content = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $content);

        $sqlmsg = 'UPDATE cash SET pre_add = pre_add -999, version = 3 WHERE user_id = 3 AND version < 3;';
        $this->assertContains($sqlmsg, $results[1]);

        $content = file_get_contents($this->sqlFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals($sqlmsg, $results[0]);
    }

    /**
     * 測試檢查預扣/存且有交易記錄
     */
    public function testCheckPreSubAndPreAddWithTrans()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cash1 = $em->find('BBDurianBundle:Cash', 1);
        $cash1->addPreAdd(101);
        $em->flush();

        $params = [
            '--start'        => '2013-01-01',
            '--end'          => '2013-01-31',
            '--pay-way'      => 'cash',
            '--check-column' => 'pre_add'
        ];
        $output = $this->runCommand('durian:check-balance', $params);
        $results = explode(PHP_EOL, $output);

        $this->assertEquals('CheckBalanceCommand start.', $results[0]);
        $this->assertEquals('CheckBalanceCommand finish.', $results[1]);
    }

    /**
     * 刪除跑完測試後產生的檔案
     */
    public function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        if (file_exists($this->sqlFilePath)) {
            unlink($this->sqlFilePath);
        }

        $fileDir = $this->getContainer()->get('kernel')->getRootDir();

        for ($i = 1; $i <= 4; $i++) {
            if (file_exists($fileDir . "/../redis{$i}Output.txt")) {
                unlink($fileDir . "/../redis{$i}Output.txt");
            }
        }
    }
}
