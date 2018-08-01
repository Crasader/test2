<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\DepositTracking;

class CheckDepositTrackingCommandTest extends WebTestCase
{
    /**
     * log檔的路徑
     *
     * @var string
     */
    private $logPath;

    /**
     * 初始化設定
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData'
        ];

        $this->loadFixtures($classnames);

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'check_deposit_tracking.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;

        // 如果log檔已經存在就移除
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 測試檢查入款查詢資料
     */
    public function testExecute()
    {
        $params = [
            '--start' => '20150610000000',
            '--end' => '20150610001500'
        ];

        $output = $this->runCommand('durian:check-deposit-tracking', $params);
        $result = explode(PHP_EOL, $output);

        $this->assertEquals('0 data insert.', $result[1]);

        // 因沒有新增資料，所以不會產生log
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試檢查入款查詢資料，不會新增不支援訂單查詢的資料
     */
    public function testExecuteWithoutUnsupportAutoReopData()
    {
        // 此時間區間入款明細只會有一筆，id為201304280000000001
        $params = [
            '--start' => '20130428120000',
            '--end' => '20130428120500'
        ];

        $output = $this->runCommand('durian:check-deposit-tracking', $params);
        $result = explode(PHP_EOL, $output);

        $this->assertEquals('0 data insert.', $result[1]);

        // 因沒有新增資料，所以不會產生log
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試檢查入款查詢資料帶入時間區間，可正常新增資料
     */
    public function testExecuteWithTime()
    {
        // 調整支付平台為支援訂單查詢
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setReopUrl('http://www.payment.cn/tracking');
        $em->flush();

        // 將訂單的商家改為不支援平行發送的支付平台商家
        $sql = 'UPDATE cash_deposit_entry SET merchant_id = 4 WHERE id = 201304280000000001';
        $em->getConnection()->executeUpdate($sql);

        // 此時間區間應取得入款明細只會有一筆，id為201304280000000001
        $params = [
            '--start' => '20130428120000',
            '--end' => '20130428120500'
        ];

        $output = $this->runCommand('durian:check-deposit-tracking', $params);
        $result = explode(PHP_EOL, $output);

        $this->assertEquals('1 data insert.', $result[1]);

        // 因有新增資料，檢查log
        $contents = file_get_contents($this->logPath);

        $insertSql = "INSERT INTO deposit_tracking " .
            "(entry_id, payment_gateway_id, merchant_id, retry) " .
            "VALUES ('201304280000000001', '2', '4', '0');";
        $this->assertContains($insertSql, $contents);
    }

    /**
     * 測試檢查入款查詢資料，已存在資料將不會新增
     */
    public function testExecuteWithExistDataWillNotInsert()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setReopUrl('http://www.payment.cn/tracking');

        // 將預期會撈出的資料先新增到DB
        $depositTracking = new DepositTracking(201304280000000001, 20130428120000, 1, 1);
        $em->persist($depositTracking);
        $em->flush();

        // 此時間區間應取得入款明細只會有一筆，id為201304280000000001
        $params = [
            '--start' => '20130428120000',
            '--end' => '20130428120500'
        ];

        $output = $this->runCommand('durian:check-deposit-tracking', $params);
        $result = explode(PHP_EOL, $output);

        $this->assertEquals('0 data insert.', $result[1]);

        // 因沒有新增資料，所以不會產生log
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試檢查入款查詢資料沒有帶入起始時間參數
     */
    public function testExecuteWithoutStartTime()
    {
        $params = ['--end' => '20150201120500'];

        $output = $this->runCommand('durian:check-deposit-tracking', $params);

        $error = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $error[2]);
        $this->assertContains('No start or end specified', $error[3]);
    }

    /**
     * 測試檢查入款查詢資料沒有帶入結束時間參數
     */
    public function testExecuteWithoutEndTime()
    {
        $params = ['--start' => '20150201115500'];

        $output = $this->runCommand('durian:check-deposit-tracking', $params);

        $error = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $error[2]);
        $this->assertContains('No start or end specified', $error[3]);
    }
}