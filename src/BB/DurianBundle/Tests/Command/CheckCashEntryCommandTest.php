<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\CheckCashEntryCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CheckCashEntryCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryDiffData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];

        $this->loadFixtures($classnames, 'entry');
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
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $now = new \DateTime('now');
        $startAt = clone $now;
        $now = $now->sub(new \DateInterval('PT10M'));
        $nowInt = $now->format('YmdHis');
        $diffTime = clone $now;
        $diffTime = $diffTime->sub(new \DateInterval('PT1S'));
        $diffTimeInt = $now->format('YmdHis');

        $cash = $em->find('BBDurianBundle:Cash', 7);

        // 使用 Entity 新增資料會造成 at 及 created_at 欄位資料不同, 所以改為手動 insert
        $sql = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
               "amount, memo, balance, ref_id, cash_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cash->getId(),
            $cash->getUser()->getId(),
            $cash->getCurrency(),
            1001,
            $now->format('Y-m-d H:i:s'),
            $nowInt,
            100,
            '',
            1100,
            0,
            0
        ];

        $emEntry->getConnection()->executeUpdate($sql, $params);

        $sql = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
               "amount, memo, balance, ref_id, cash_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cash->getId(),
            $cash->getUser()->getId(),
            $cash->getCurrency(),
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

        // startTime大於endTime，則$queueMsg回傳null
        $params = [
            '--starttime' => $startAt->format('Y-m-d H:i:s'),
            '--endtime' => $now->format('Y-m-d H:i:s')
        ];

        $output = $this->runCommand('durian:cronjob:check-cash-entry', $params);

        $this->assertEmpty($output);

        // startTime和endTime相差小於1分鐘
        $endAt = clone $now;
        $endAt = $endAt->add(new \DateInterval('PT59S'));
        $params = [
            '--starttime' => $now->format('Y-m-d H:i:s'),
            '--endtime' => $endAt->format('Y-m-d H:i:s')
        ];

        $output = $this->runCommand('durian:cronjob:check-cash-entry', $params);

        $key = 'italking_message_queue';
        $msg = "現金明細有差異, CashEntry: id: 21, cashId: 7, userId: 8, currency: 901, opcode: 1001, amount: 100, ".
               "balance: 1100, cash_version: 0";

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);

        // 先檢查一次找出差異, 並記錄起來
        $output = $this->runCommand('durian:cronjob:check-cash-entry');

        // 檢查italking
        $key = 'italking_message_queue';
        $msg = "現金明細有差異, CashEntry: id: 21, cashId: 7, userId: 8, currency: 901, opcode: 1001, amount: 100, ".
               "balance: 1100, cash_version: 0";

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);

        // 執行更新差異資料, 並且顯示更新語法
        $params = array(
            '--update' => true,
            '--write-sql' => true
        );
        $output = $this->runCommand('durian:cronjob:check-cash-entry', $params);
        $results = explode(PHP_EOL, $output);
        $expectResult = "UPDATE cash_entry SET created_at = '".$now->format('Y-m-d H:i:s').
                        "', at = '".$now->format('YmdHis')."' WHERE id = 21;";

        // 比對執行結果是否相符
        $this->assertEquals($expectResult, $results[0]);

        $id = array('id' => 21);
        $entryHis = $emHis->getRepository('BBDurianBundle:CashEntry')->findOneBy($id);
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
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $now = new \DateTime('now');
        $endAt = clone $now;
        $now = $now->sub(new \DateInterval('PT10M'));
        $nowInt = $now->format('YmdHis');

        $cash = $em->find('BBDurianBundle:Cash', 7);

        // 使用 Entity 新增資料會造成 at 及 created_at 欄位資料不同, 所以改為手動 insert
        $sql = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
               "amount, memo, balance, ref_id, cash_version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cash->getId(),
            $cash->getUser()->getId(),
            $cash->getCurrency(),
            1001,
            $now->format('Y-m-d H:i:s'),
            $nowInt,
            100,
            '',
            1100,
            0,
            0
        ];

        $emEntry->getConnection()->executeUpdate($sql, $params);

        // 執行更新差異資料, 並且顯示更新語法
        $params = [
            '--fill-up' => true,
            '--write-sql' => true,
            '--starttime' => $now->format('Y-m-d H:i:s'),
            '--endtime' => $endAt->format('Y-m-d H:i:s')
        ];
        $output = $this->runCommand('durian:cronjob:check-cash-entry', $params);
        $results = explode(PHP_EOL, $output);
        $expectResult = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
                        "amount, memo, balance, ref_id, cash_version) VALUES ('21', '7', '8', '901', '1001', '".
                        $now->format('Y-m-d H:i:s')."', '".$nowInt."', '100', '', '1100', '0', '0');";

        // 比對執行結果是否相符
        $this->assertEquals($expectResult, $results[0]);

        $id = array('id' => 21);
        $entryHis = $emHis->getRepository('BBDurianBundle:CashEntry')->findOneBy($id);
        $createdAt = $entryHis->getCreatedAt()->format('Y-m-d H:i:s');

        // 檢查資料是否被修正
        $this->assertEquals($entryHis->getId(), 21);
        $this->assertEquals($createdAt, $now->format('Y-m-d H:i:s'));

        // 檢查italking
        $key = 'italking_message_queue';
        $msg = "現金明細有差異, CashEntry: id: 21, cashId: 7, userId: 8, currency: 901, opcode: 1001, amount: 100, ".
               "balance: 1100, cash_version: 0";

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
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $now = new \DateTime('now');
        $now = $now->sub(new \DateInterval('PT10M'));
        $nowInt = $now->format('YmdHis');

        $cash = $em->find('BBDurianBundle:Cash', 7);

        // 在現行以及歷史資料庫各新增一筆相同的明細
        // 使用 Entity 新增資料會造成 at 及 created_at 欄位資料不同, 所以改為手動 insert
        $sql = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
               "amount, memo, balance, ref_id, cash_version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cash->getId(),
            $cash->getUser()->getId(),
            $cash->getCurrency(),
            1001,
            $now->format('Y-m-d H:i:s'),
            $nowInt,
            100,
            '',
            1100,
            0,
            0
        ];

        $emEntry->getConnection()->executeUpdate($sql, $params);
        $emHis->getConnection()->executeUpdate($sql, $params);

        $output = $this->runCommand('durian:cronjob:check-cash-entry');

        $key = 'italking_message_queue';
        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertNull($queueMsg);
    }

    /**
     * 測試取得區間參數時，發生的例外
     */
    public function testExceptionWhenGetOpt()
    {
        // starttime或endtime參數與update參數同時使用
        $params = [
            '--endtime' => '2015-01-01 12:00:00',
            '--update' => true
        ];

        // 比對例外的字串
        $output = $this->runCommand('durian:cronjob:check-cash-entry', $params);
        $errorStr = '--starttime 及 --endtime 參數不可同時與 --update 一起使用';
        $this->assertTrue(strpos($output, $errorStr) > 0);

        // 指定結束時間，而沒有指定開始時間
        $params = ['--endtime' => '2015-01-01 12:00:00'];

        $output = $this->runCommand('durian:cronjob:check-cash-entry', $params);
        $errorStr = '需同時指定開始及結束時間';
        $this->assertTrue(strpos($output, $errorStr) > 0);
    }

    /**
     * 測試cash_entry_diff無資料時，則回傳null
     */
    public function testSkipCheckWhenUpdate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 清空cash_entry_diff
        $sql = 'DELETE FROM cash_entry_diff';
        $em->getConnection()->executeUpdate($sql);

        $params = ['--update' => true];

        $output = $this->runCommand('durian:cronjob:check-cash-entry', $params);

        $key = 'italking_message_queue';
        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertNull($queueMsg);
    }

    /**
     * 測試當check_cash_entry.log不存在，則建置log檔
     */
    public function testIfLogFileDontExist()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_cash_entry.log';

        $this->assertFalse(file_exists($logPath));

        $output = $this->runCommand('durian:cronjob:check-cash-entry');

        $this->assertTrue(file_exists($logPath));
    }

    /**
     * 測試資料庫連線
     */
    public function testConnectToDatabase()
    {
        $refClass = new \ReflectionClass('BB\DurianBundle\Command\CheckCashEntryCommand');
        $ChkCashEntry = new CheckCashEntryCommand();

        // 調整$conn的預設值
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        $refConn = $refClass->getProperty('conn');
        $refConn->setAccessible(true);
        $refConn->setValue($ChkCashEntry, $conn);

        $method = $refClass->getMethod('getConnection');
        $method->setAccessible(true);

        $result = $method->invoke($ChkCashEntry);

        $this->assertEquals($conn, $result);

        // 調整$historyConn的預設值
        $historyConn = $this->getContainer()->get('doctrine.dbal.his_connection');

        $refConn = $refClass->getProperty('historyConn');
        $refConn->setAccessible(true);
        $refConn->setValue($ChkCashEntry, $historyConn);

        $method = $refClass->getMethod('getHistoryConnection');
        $method->setAccessible(true);

        $result = $method->invoke($ChkCashEntry);

        $this->assertEquals($historyConn, $result);
    }

    /**
     * 測試setInsertSql()，並指定$columns為null
     */
    public function testSetInsertSqlByNullColumns()
    {
        $refClass = new \ReflectionClass('BB\DurianBundle\Command\CheckCashEntryCommand');
        $ChkCashEntry = new CheckCashEntryCommand();

        // 測試setInsertSql()
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sql = 'INSERT INTO cash_entry (test) VALUES (null);';

        $refEm = $refClass->getProperty('em');
        $refEm->setAccessible(true);
        $refEm->setValue($ChkCashEntry, $em);

        $method = $refClass->getMethod('setInsertSql');
        $method->setAccessible(true);

        $result = $method->invoke(
            $ChkCashEntry,
            'cash_entry',
            ['test'],
            [['test' => null]]
        );

        $this->assertEquals($sql, $result);
    }

    /**
     * 測試setUpdateSql()，並指定$columns為null
     */
    public function testSetUpdateSqlByNullColumns()
    {
        $refClass = new \ReflectionClass('BB\DurianBundle\Command\CheckCashEntryCommand');
        $ChkCashEntry = new CheckCashEntryCommand();

        // 測試setUpdateSql()
        $sql = "UPDATE cash_entry_diff SET test = null, id = '1234' WHERE id = 1234;";

        $method = $refClass->getMethod('setUpdateSql');
        $method->setAccessible(true);

        $result = $method->invoke(
            $ChkCashEntry,
            'cash_entry_diff',
            ['test' ,'id'],
            [['test' => null, 'id' => '1234']]
        );

        $this->assertEquals($sql, $result);
    }

    /**
     * 測試isAllowTable()的例外，帶入錯誤的資料表名稱
     */
    public function testIsAllowTableByInvalidTablename()
    {
        $refClass = new \ReflectionClass('BB\DurianBundle\Command\CheckCashEntryCommand');
        $ChkCashEntry = new CheckCashEntryCommand();

        // 測試isAllowTable()的例外
        $this->setExpectedException('Exception', 'Not allowed table');

        $method = $refClass->getMethod('isAllowTable');
        $method->setAccessible(true);
        $method->invoke($ChkCashEntry, ['testName']);
    }

    /**
     * 測試insert的例外
     */
    public function testExceptionWhenInsert()
    {
        // 建立測試資料
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $sql = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
            "amount, memo, balance, ref_id, cash_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cash->getId(),
            $cash->getUser()->getId(),
            $cash->getCurrency(),
            1001,
            '2015-01-01 12:05:01',
            '20150101120501',
            100,
            '',
            1100,
            0,
            0
        ];

        $emEntry->getConnection()->executeUpdate($sql, $params);

        $sql = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
            "amount, memo, balance, ref_id, cash_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cash->getId(),
            $cash->getUser()->getId(),
            $cash->getCurrency(),
            1001,
            '2015-01-01 12:05:00',
            '20150101120500',
            100,
            '',
            1100,
            0,
            0
        ];

        $emHis->getConnection()->executeUpdate($sql, $params);

        // mock
        $mockContainer = $this->getMockContainer('insert');
        $application = new Application();
        $command = new CheckCashEntryCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:cronjob:check-cash-entry');
        $commandTester = new CommandTester($command);

        // insert failed
        $this->setExpectedException('Exception', 'Insert failed.');
        $commandTester->execute([
            'command' => $command->getName(),
            '--starttime' => '2015-01-01 12:05:00',
            '--endtime' => '2015-01-01 12:06:00',
            '--fill-up' => true
        ]);
    }

    /**
     * 測試update的例外
     */
    public function testExceptionWhenUpdate()
    {
        // 建立測試資料
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $sql = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
            "amount, memo, balance, ref_id, cash_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cash->getId(),
            $cash->getUser()->getId(),
            $cash->getCurrency(),
            1001,
            '2015-01-01 12:05:01',
            '20150101120501',
            100,
            '',
            1100,
            0,
            0
        ];

        $emEntry->getConnection()->executeUpdate($sql, $params);

        $sql = "INSERT INTO cash_entry (id, cash_id, user_id, currency, opcode, created_at, at, ".
            "amount, memo, balance, ref_id, cash_version) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $params = [
            21,
            $cash->getId(),
            $cash->getUser()->getId(),
            $cash->getCurrency(),
            1001,
            '2015-01-01 12:05:00',
            '20150101120500',
            100,
            '',
            1100,
            0,
            0
        ];

        $emHis->getConnection()->executeUpdate($sql, $params);

        // mock
        $mockContainer = $this->getMockContainer();
        $application = new Application();
        $command = new CheckCashEntryCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:cronjob:check-cash-entry');
        $commandTester = new CommandTester($command);

        // update failed
        $this->setExpectedException('Exception', 'Update differences failed.');
        $commandTester->execute([
            'command' => $command->getName(),
            '--update' => true
        ]);
    }

    /**
     * 取得 MockContainer
     *
     * @param string $params 判斷參數
     * @return Container
     */
    private function getMockContainer($params = '')
    {
        $hisResults = [
            'id' => 21,
            'created_at' => '2015-01-01 12:05:00',
            'at' => '20150101120500',
            'cash_id' => 7,
            'user_id' => 8,
            'currency' => '901',
            'opcode' => '1001',
            'amount' => 100,
            'memo' => '',
            'balance' => 1100,
            'ref_id' => '0',
            'cash_version' => '0'
        ];

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        if ($params == 'insert') {
            $mockConn->expects($this->at(0))
                ->method('fetchAll')
                ->will($this->returnValue([$hisResults]));
            $mockConn->expects($this->at(1))
                ->method('fetchAll')
                ->will($this->returnValue([]));
        }

        $mockConn->expects($this->any())
            ->method('executeUpdate')
            ->will($this->returnValue(1234));

        // map
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $connEntry = $this->getContainer()->get('doctrine.dbal.entry_connection');
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $loggerManager = $this->getContainer()->get('durian.logger_manager');

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['doctrine.dbal.default_connection', 1, $conn],
            ['doctrine.dbal.entry_connection', 1, $connEntry],
            ['doctrine.dbal.his_connection', 1, $mockConn],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.entry_entity_manager', 1, $emEntry],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValue($logDir));

        return $mockContainer;
    }

    /**
     * 清除產生的檔案
     */
    public function tearDown()
    {
        // check_cash_entry.log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_cash_entry.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        parent::tearDown();
    }
}
