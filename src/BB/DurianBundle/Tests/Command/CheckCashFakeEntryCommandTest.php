<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CheckCashFakeEntryCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData'
        );

        $this->loadFixtures($classnames);
        $this->loadFixtures($classnames, 'his');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();
    }

    /**
     * 測試檢查資料差異
     */
    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $now = new \DateTime('now');
        $now = $now->sub(new \DateInterval('PT10M'));
        $nowInt = $now->format('YmdHis');
        $diffTime = clone $now;
        $diffTime = $diffTime->sub(new \DateInterval('PT1S'));
        $diffTimeInt = $now->format('YmdHis');

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);

        // 使用 Entity 新增資料會造成 at 及 created_at 欄位資料不同, 所以改為手動 insert
        $sql = "INSERT INTO cash_fake_entry (id, cash_fake_id, user_id, currency, opcode, created_at, at, ".
               "amount, memo, balance, ref_id, cash_fake_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cashFake->getId(),
            $cashFake->getUser()->getId(),
            $cashFake->getCurrency(),
            1001,
            $now->format('Y-m-d H:i:s'),
            $nowInt,
            100,
            '',
            1100,
            0,
            0
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        $sql = "INSERT INTO cash_fake_entry (id, cash_fake_id, user_id, currency, opcode, created_at, at, ".
               "amount, memo, balance, ref_id, cash_fake_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cashFake->getId(),
            $cashFake->getUser()->getId(),
            $cashFake->getCurrency(),
            1001,
            $diffTime->format('Y-m-d H:i:s'),
            $diffTimeInt,
            100,
            '',
            1100,
            0,
            0
        ];

        $emHis->getConnection()->executeUpdate($sql, $params);

        // 先檢查一次找出差異, 並記錄起來
        $output = $this->runCommand('durian:cronjob:check-cash-fake-entry');

        // 檢查italking
        $key = 'italking_message_queue';
        $msg = "快開明細有差異, CashFakeEntry: id: 21, cashFakeId: 1, userId: 7, currency: 156, opcode: 1001, amount: 100, ".
               "balance: 1100, cash_fake_version: 0";

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);

        // 執行更新差異資料, 並且顯示更新語法
        $params = array(
            '--update' => true,
            '--write-sql' => true
        );
        $output = $this->runCommand('durian:cronjob:check-cash-fake-entry', $params);
        $results = explode(PHP_EOL, $output);
        $expectResult = "UPDATE cash_fake_entry SET created_at = '".$now->format('Y-m-d H:i:s').
                        "', at = '".$now->format('YmdHis')."' WHERE id = 21;";

        // 比對執行結果是否相符
        $this->assertEquals($expectResult, $results[0]);

        // 檢查 his 資料庫資料
        $id = array('id' => 21);
        $entryHis = $emHis->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy($id);
        $createdAt = $entryHis->getCreatedAt()->format('Y-m-d H:i:s');

        // 檢查資料是否被修正
        $this->assertEquals($entryHis->getId(), 21);
        $this->assertEquals($createdAt, $now->format('Y-m-d H:i:s'));
    }

    /**
     * 測試檢查是否有漏資料並自動補回
     */
    public function testInsertDifference()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $now = new \DateTime('now');
        $now = $now->sub(new \DateInterval('PT10M'));
        $nowInt = $now->format('YmdHis');

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);

        // 使用 Entity 新增資料會造成 at 及 created_at 欄位資料不同, 所以改為手動 insert
        $sql = "INSERT INTO cash_fake_entry (id, cash_fake_id, user_id, currency, opcode, created_at, at, ".
               "amount, memo, balance, ref_id, cash_fake_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cashFake->getId(),
            $cashFake->getUser()->getId(),
            $cashFake->getCurrency(),
            1001,
            $now->format('Y-m-d H:i:s'),
            $nowInt,
            100,
            '',
            1100,
            0,
            0
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        // 執行更新差異資料, 並且顯示更新語法
        $params = array(
            '--fill-up' => true,
            '--write-sql' => true
        );
        $output = $this->runCommand('durian:cronjob:check-cash-fake-entry', $params);
        $results = explode(PHP_EOL, $output);
        $expectResult = "INSERT INTO cash_fake_entry (id, cash_fake_id, user_id, currency, opcode, created_at, at, ".
                        "amount, memo, balance, ref_id, cash_fake_version) VALUES ('21', '1', '7', '156', '1001', '".
                        $now->format('Y-m-d H:i:s')."', '".$nowInt."', '100', '', '1100', '0', '0');";

        // 比對執行結果是否相符
        $this->assertEquals($expectResult, $results[0]);

        // 檢查 his 資料庫資料
        $id = array('id' => 21);
        $entryHis = $emHis->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy($id);
        $createdAt = $entryHis->getCreatedAt()->format('Y-m-d H:i:s');

        // 檢查資料是否被修正
        $this->assertEquals($entryHis->getId(), 21);
        $this->assertEquals($createdAt, $now->format('Y-m-d H:i:s'));

        // 檢查italking
        $key = 'italking_message_queue';
        $msg = "快開明細有差異, CashFakeEntry: id: 21, cashFakeId: 1, userId: 7, currency: 156, opcode: 1001, ".
               "amount: 100, balance: 1100, cash_fake_version: 0";

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 測試沒有差異則不傳訊息給italking
     */
    public function testItalkingButNoDifferentBetweenHistroryDatabase()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $now = new \DateTime('now');
        $now = $now->sub(new \DateInterval('PT10M'));
        $nowInt = $now->format('YmdHis');

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);

        // 在現行以及歷史資料庫各新增一筆相同的明細
        // 使用 Entity 新增資料會造成 at 及 created_at 欄位資料不同, 所以改為手動 insert
        $sql = "INSERT INTO cash_fake_entry (id, cash_fake_id, user_id, currency, opcode, created_at, at, ".
               "amount, memo, balance, ref_id, cash_fake_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cashFake->getId(),
            $cashFake->getUser()->getId(),
            $cashFake->getCurrency(),
            1001,
            $now->format('Y-m-d H:i:s'),
            $nowInt,
            100,
            '',
            1100,
            0,
            0
        ];

        $em->getConnection()->executeUpdate($sql, $params);
        $emHis->getConnection()->executeUpdate($sql, $params);

        $output = $this->runCommand('durian:cronjob:check-cash-fake-entry');

        $key = 'italking_message_queue';
        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertNull($queueMsg);
    }
}
