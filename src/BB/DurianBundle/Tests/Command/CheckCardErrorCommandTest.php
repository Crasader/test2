<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CheckCardErrorCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardEntryDataForCheckError'
        ];

        $this->loadFixtures($classnames);
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
        $output = $this->runCommand('durian:check-card-error', $params);
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
        $output = $this->runCommand('durian:check-card-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CardError: cardId: 7, userId: 8, balance: 70, amount: 40';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        // 檢查資料庫
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $error = $em->find('BBDurianBundle:CardError', 1);

        $this->assertNotNull($error);
        $this->assertEquals(7, $error->getCardId());
        $this->assertEquals(8, $error->getUserId());
        $this->assertEquals(70, $error->getBalance());
        $this->assertEquals(40, $error->getTotalAmount());

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有租卡差異, 請檢查CardError: cardId: 7, userId: 8, balance: 70, amount: 40';

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
        $output = $this->runCommand('durian:check-card-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CardError: cardId: 6, userId: 7, balance: 250, amount: 120';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有租卡差異, 請檢查CardError: cardId: 6, userId: 7, balance: 250, amount: 120';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
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
        $output = $this->runCommand('durian:check-card-error', $params);
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
        $output = $this->runCommand('durian:check-card-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CardError: cardId: 7, userId: 8, balance: 70, amount: -50';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有租卡差異, 請檢查CardError: cardId: 7, userId: 8, balance: 70, amount: -50';

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
        $output = $this->runCommand('durian:check-card-error', $params);
        $results = explode(PHP_EOL, trim($output));

        $msg = 'CardError: cardId: 5, userId: 6, balance: 120, amount: 20';
        $this->assertEquals($msg, $results[1]);
        $this->assertCount(3, $results);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = '有租卡差異, 請檢查CardError: cardId: 5, userId: 6, balance: 120, amount: 20';

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
        $output = $this->runCommand('durian:check-card-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCardErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCardErrorCommand end...', $results[1]);
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
        $output = $this->runCommand('durian:check-card-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCardErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCardErrorCommand end...', $results[1]);
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
        $output = $this->runCommand('durian:check-card-error', $params);
        $results = explode(PHP_EOL, trim($output));

        // 確認開始結束間沒有其他錯誤訊息
        $this->assertContains('CheckCardErrorCommand begin...', $results[0]);
        $this->assertContains('CheckCardErrorCommand end...', $results[1]);
    }
}
