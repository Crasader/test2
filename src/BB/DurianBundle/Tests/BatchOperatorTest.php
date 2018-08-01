<?php

namespace BB\DurianBundle\Tests;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class BatchOperatorTest extends WebTestCase
{
    /**
     * 初始化資料表
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cashfake_seq', 1005);

        // 移除log file
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'test';
        $logFileAry = [
            '/batch-op-cash_fake.log',
            '/background_process/sync_cash_fake_balance.log',
            '/background_process/sync_cash_fake_entry.log'
        ];

        foreach ($logFileAry as $logFile) {
            $logFile = $logDir . $logFile;
            if (file_exists($logFile)) {
                unlink($logFile);
            }
        }
    }

    /**
     * 測試快開批次補單
     */
    public function testRunCashFakeBatchOp()
    {
        $input = 'test.csv';
        $output = 'out.csv';

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cashFake = $em->find('BBDurianBundle:CashFake', 2);
        $cashFake->setBalance(30);
        $em->flush();
        $em->clear();

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId, amount, opcode, refId, memo\n");
        fwrite($handle, "8,100,2001,123456,test-memo\n");
        fwrite($handle, "8,-10,1002,251462,memo-test-中文\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(false);
        $ret = $operator->runByCsv('cash_fake', $input, $output);

        // 測試回傳值
        $this->assertEquals('SUCCESS', $ret);

        // 測試檔案
        $dataArray = file($output);

        $this->assertEquals(3, count($dataArray));
        $this->assertEquals(pack('CCC', 0xef, 0xbb, 0xbf) . "使用者編號,廳主,廳名,廳主代碼,交易明細編號,幣別,參考編號,交易金額,餘額,備註\n", $dataArray[0]);
        $this->assertEquals("8,company,domain2,cm,1006,CNY,123456,100,130,test-memo\n", $dataArray[1]);
        $this->assertEquals("8,company,domain2,cm,1007,CNY,251462,-10,120,memo-test-中文\n", $dataArray[2]);

        unlink($input);
        unlink($output);

        // 執行背景
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        // 測試資料庫
        $em->clear();
        $cashFake = $em->find('BBDurianBundle:CashFake', 2);
        $this->assertEquals(120, $cashFake->getBalance());

        $entry = $em->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 1006]);
        $this->assertEquals(8, $entry->getUserId());
        $this->assertEquals(123456, $entry->getRefId());
        $this->assertEquals(100, $entry->getAmount());
        $this->assertEquals(130, $entry->getBalance());
        $this->assertEquals('test-memo', $entry->getMemo());

        $entry = $em->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 1007]);
        $this->assertEquals(8, $entry->getUserId());
        $this->assertEquals(251462, $entry->getRefId());
        $this->assertEquals(-10, $entry->getAmount());
        $this->assertEquals(120, $entry->getBalance());
        $this->assertEquals('memo-test-中文', $entry->getMemo());

        // 測試 Log 檔
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logFile = $logDir . '/test/batch-op-cash_fake.log';

        $this->assertFileExists($logFile);

        $data = file($logFile);
        $this->assertEquals(5, count($data));

        // Log 檔會記錄時間，故要略過 22 個字元
        $this->assertEquals("LOGGER.INFO: 使用者編號,廳主,廳名,廳主代碼,交易明細編號,幣別,參考編號,交易金額,餘額,備註 [] []\n", substr($data[0], 22));
        $this->assertEquals("LOGGER.INFO: ReOpCommand Start. [] []\n", substr($data[1], 22));
        $this->assertEquals("LOGGER.INFO: 8,company,domain2,cm,1006,CNY,123456,100,130,test-memo [] []\n", substr($data[2], 22));
        $this->assertEquals("LOGGER.INFO: 8,company,domain2,cm,1007,CNY,251462,-10,120,memo-test-中文 [] []\n", substr($data[3], 22));
        $this->assertEquals("LOGGER.INFO: ReOpCommand Finish. [] []\n", substr($data[4], 22));
    }

    /**
     * 測試快開批次補單，使用者沒有快開額度
     */
    public function testRunCashFakeBatchOpButUserHasNoCashFake()
    {
        $errMsg = "已處理 0 筆資料完成，正在處理第 1 筆資料，但發生錯誤：\n".
                  "Data: 6,100,2001,123456,test-memo\n".
                  "Code: 150170026\n".
                  "Message: The user does not have cashFake\n";
        $this->setExpectedException('Exception', $errMsg, 150170026);

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId, amount, opcode, refId, memo\n");
        fwrite($handle, "6,100,2001,123456,test-memo\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(false);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 測試快開批次補單，使用者同時有現金與快開額度
     */
    public function testRunCashFakeBatchOpButUserHasBothCashAndCashFake()
    {
        $errMsg = "已處理 0 筆資料完成，正在處理第 1 筆資料，但發生錯誤：\n".
                  "Data: 52,100,2001,123456,test-memo\n".
                  "Code: 150170002\n".
                  "Message: This user has both cash and cashFake\n";
        $this->setExpectedException('Exception', $errMsg, 150170002);

        $input = 'test.csv';
        $output = 'out.csv';

        $client = $this->createClient();

        $parameters = [
            'username' => 'allnewone',
            'password' => 'all_new_one',
            'alias' => 'AllNewOne23',
            'role' => 7,
            'login_code' => 'no',
            'name' => 'all',
            'currency' => 'TWD',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => [
                'currency' => 'CNY',
                'balance'  => 100,
            ],
        ];

        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $userId = $ret['ret']['id'];

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId, amount, opcode, refId, memo\n");
        fwrite($handle, "$userId,100,2001,123456,test-memo\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(false);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 測試快開補單，乾跑
     */
    public function testRunCashFakeBatchOpWithDryRun()
    {
        $input = 'test.csv';
        $output = 'out.csv';

        $handle = fopen($input, 'w');
        fwrite($handle, "userId, amount, opcode, refId, memo\n");
        fwrite($handle, "8,100,2001,123456,test-memo\n");
        fclose($handle);

        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(true);
        $ret = $operator->runByCsv('cash_fake', $input, $output);

        // 檢查輸出
        $ret = file_get_contents($output);
        $out = explode(PHP_EOL, trim($ret));

        $this->assertCount(2, $out);
        $this->assertContains('使用者編號,廳主,廳名,廳主代碼,交易明細編號,幣別,參考編號,交易金額,餘額,備註', $out[0]);
        $this->assertContains('8,company,domain2,cm,0,156,123456,100,0,test-mem', $out[1]);

        // 檢查log檔
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logFile = $logDir . '/test/batch-op-cash_fake.log';

        $content = file_get_contents($logFile);
        $log = explode(PHP_EOL, $content);

        $this->assertContains('使用者編號,廳主,廳名,廳主代碼,交易明細編號,幣別,參考編號,交易金額,餘額,備註', $log[0]);
        $this->assertContains('ReOpCommand Start', $log[1]);
        $this->assertContains('8,company,domain2,cm,0,156,123456,100,0,test-memo', $log[2]);
        $this->assertContains('ReOpCommand Finish.', $log[3]);
    }

    /**
     * 測試快開批次補單，沒有此使用者
     */
    public function testRunCashFakeBatchOpButNoSuchUser()
    {
        $errMsg = "已處理 0 筆資料完成，正在處理第 1 筆資料，但發生錯誤：\n".
                  "Data: 2000,100,2001,123456,test-memo\n".
                  "Code: 150010029\n".
                  "Message: No such user\n";
        $this->setExpectedException('Exception', $errMsg, 150010029);

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId, amount, opcode, refId, memo\n");
        fwrite($handle, "2000,100,2001,123456,test-memo\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(false);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 測試批次補單，但payway不合法
     */
    public function testRunBatchOpWithInvalidPayway()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid payway', 150170006);

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId,amount,refId,opcode,memo\n");
        fwrite($handle, "8,10,12345,1001,test-memo\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(true);
        $operator->runByCsv('wrong_payway', $input, $output);
    }

    /**
     * 測試批次補單，但檔案表頭不合法
     */
    public function testRunCashFakeBatchOpWithIncorrentFileHeader()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The contents of the file is incorrect',
            150170001
        );

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "amount,refId,opcode,memo\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(true);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 測試批次補單，但檔案為空
     */
    public function testRunCashFakeBatchOpWithEmptyFile()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The contents of the file is empty',
            150170005
        );

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId,amount,refId,opcode,memo\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(true);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 測試批次補單，檔案資料內容錯誤
     */
    public function testRunCashFakeBatchOpWithErrorFile()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The contents of the file is incorrect',
            150170001
        );

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId,amount,refId,opcode,memo\n");
        fwrite($handle, "8,10\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(true);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 測試批次補單，資料超出5000行上限
     */
    public function testRunCashFakeBatchOpWithOver5000RowsFile()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Exceeded the permitted execution lines',
            150170004
        );

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId,amount,refId,opcode,memo\n");
        for ($i = 0; $i <= 5000; $i++) {
            fwrite($handle, "8,100,12345,1001,test-memo\n");
        }
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(true);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 測試批次補單，但必要欄位沒有設定
     */
    public function testRunCashFakeBatchOpWithInvalidHeaderData()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid headers',
            150170007
        );

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId,amount,refId,opcode,memo\n");
        fwrite($handle, ",10,12345,1001,test-memo\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(true);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 測試批次補單，但扣款時餘額不足
     */
    public function testRunCashFakeBatchOpWithBalanceIsNotEnough()
    {
        $errMsg = "已處理 1 筆資料完成，正在處理第 2 筆資料，但發生錯誤：\n".
                  "Data: 8,-50,1002,12345,test-memo\n".
                  "Code: 150050031\n".
                  "Message: Not enough balance\n";
        $this->setExpectedException('Exception', $errMsg, 150050031);

        $input = 'test.csv';
        $output = 'out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId,amount,refId,opcode,memo\n");
        fwrite($handle, "8,10,12345,1001,test-memo\n");
        fwrite($handle, "8,-50,12345,1002,test-memo\n");
        fclose($handle);

        // 執行
        $operator = $this->getContainer()->get('durian.batch_op');
        $operator->setDryRun(false);
        $operator->runByCsv('cash_fake', $input, $output);
    }

    /**
     * 清除產生的 CSV 檔案
     */
    public function tearDown()
    {
        if (file_exists('test.csv')) {
            unlink('test.csv');
        }

        if (file_exists('out.csv')) {
            unlink('out.csv');
        }

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'test';
        $logFileAry = [
            '/batch-op-cash_fake.log',
            '/background_process/sync_cash_fake_balance.log',
            '/background_process/sync_cash_fake_entry.log'
        ];

        foreach ($logFileAry as $logFile) {
            $logFile = $logDir . $logFile;
            if (file_exists($logFile)) {
                unlink($logFile);
            }
        }

        parent::tearDown();
    }
}
