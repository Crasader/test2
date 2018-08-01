<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試批次補單
 */
class BatchOpCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cashfake_seq', 2000);
    }

    /**
     * 測試批次補單(快開)
     */
    public function testBatchOpCashFake()
    {
        $input = __DIR__ . '/test.csv';
        $output = __DIR__ . '/out.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId, amount, opcode, refId, memo\n");
        fwrite($handle, "8,100,2001,123456,test-memo\n");
        fclose($handle);

        // 執行
        $params = [
            '--payway' => 'cash_fake',
            '--source' => $input,
            '--output' => $output
        ];
        $this->runCommand('durian:batch-op', $params);

        // 測試檔案
        $ret = file_get_contents($output);
        $out = explode(PHP_EOL, trim($ret));

        $this->assertCount(2, $out);

        $this->assertContains('使用者編號,廳主,廳名,廳主代碼,交易明細編號,幣別,參考編號,交易金額,餘額,備註', $out[0]);
        $this->assertContains('8,company,domain2,cm,2001,CNY,123456,100,100,test-memo', $out[1]);
    }

    /**
     * 測試批次補單沒有帶參數payway
     */
    public function testBatchOpWithoutPayway()
    {
        $params = [
            '--source' => '123.csv',
            '--output' => '321.csv'
        ];
        $out = $this->runCommand('durian:batch-op', $params);

        $this->assertContains('請指定交易方式', $out);
    }

    /**
     * 測試批次補單沒有帶參數source
     */
    public function testBatchOpWithoutSource()
    {
        $params = [
            '--payway' => 'cash_fake',
            '--output' => '321.csv'
        ];
        $out = $this->runCommand('durian:batch-op', $params);

        $this->assertContains('請指定來源 CSV 檔', $out);
    }

    /**
     * 測試批次補單，但來源檔案找不到
     */
    public function testBatchOpButSourceFileNotExist()
    {
        $params = [
            '--payway' => 'cash_fake',
            '--source' => 'no_exists.csv',
            '--output' => '321.csv'
        ];
        $out = $this->runCommand('durian:batch-op', $params);

        $this->assertContains('來源CSV檔不存在', $out);
    }

    /**
     * 測試批次補單沒有帶參數output
     */
    public function testBatchOpWithoutOutput()
    {
        $input = __DIR__ . '/test.csv';

        // 寫一個初始檔案
        $handle = fopen($input, 'w');
        fwrite($handle, "userId,amount,refId,opcode,memo\n");
        fwrite($handle, "8,100,12345,1001,test-memo\n");
        fwrite($handle, "8,-10,54321,1002,memo-test-中文\n");
        fclose($handle);

        $params = [
            '--payway' => 'cash_fake',
            '--source' => $input
        ];
        $out = $this->runCommand('durian:batch-op', $params);

        $this->assertContains('請指定批次補單後的輸出檔', $out);
    }

    public function tearDown()
    {
        // 清除殘留的檔案
        $input = __DIR__ . '/test.csv';
        $output = __DIR__ . '/out.csv';

        if (file_exists($input)) {
            unlink($input);
        }

        if (file_exists($output)) {
            unlink($output);
        }

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logFileArray = [
            'batch-op-cash_fake.log'
        ];

        foreach ($logFileArray as $logFile) {
            $logFile = $logDir . DIRECTORY_SEPARATOR . $logFile;
            if (file_exists($logFile)) {
                unlink($logFile);
            }
        }

        parent::tearDown();
    }
}
