<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\RemitEntry;
use BB\DurianBundle\Entity\DepositPayStatusError;

class DepositPayStatusErrorCommandTest extends WebTestCase
{
    /**
     * 初始化設定
     */
    public function setUp()
    {
        parent::setUp();

        $classnames =[
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData',
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        // clear log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'deposit_pay_status_error.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    /**
     * 測試將異常入款錯誤寫入DB
     */
    public function testExecute()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cdeEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201304280000000001']);
        $cdeEntry->confirm();
        $em->flush();

        // 新增資料到 deposit_pay_status_error_queue
        $params = [
            'entry_id' => '201304280000000001',
            'deposit' => 1,
            'card' => 0,
            'remit' => 0,
            'duplicate_count' => 0,
            'auto_remit_id' => 0,
            'payment_gateway_id' => '1',
            'code' => '180060',
        ];
        $redis->lpush('deposit_pay_status_error_queue', json_encode($params));

        // 執行背景
        $this->runCommand('durian:deposit-pay-status-error');

        // 檢查queue裡面已經沒有資料
        $this->assertEquals(0, $redis->llen('deposit_pay_status_error_queue'));

        // 檢查寫入 deposit_pay_status_error 的資料
        $payError = $em->find('BBDurianBundle:DepositPayStatusError', 1);
        $payErrorArray = $payError->toArray();

        $this->assertEquals(1, $payErrorArray['id']);
        $this->assertEquals($cdeEntry->getId(), $payErrorArray['entry_id']);
        $this->assertEquals($cdeEntry->getDomain(), $payErrorArray['domain']);
        $this->assertEquals($cdeEntry->getUserId(), $payErrorArray['user_id']);
        $this->assertEquals($cdeEntry->getConfirmAt()->format(\DateTime::ISO8601), $payErrorArray['confirm_at']);
        $this->assertEquals('1', $payErrorArray['payment_gateway_id']);
        $this->assertEquals('180060', $payErrorArray['code']);
        $this->assertFalse($payErrorArray['checked']);
    }

    /**
     * 測試將線上入款異常錯誤寫入DB時找不到入款明細
     */
    public function testExecuteButCashDepositEntryNotFound()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cdeEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201304280000000001']);
        $cdeEntry->confirm();
        $em->flush();

        // 第一筆資料沒有對應的入款明細
        $params1 = [
            'entry_id' => '201304280000000000',
            'deposit' => 1,
            'card' => 0,
            'remit' => 0,
            'duplicate_count' => 0,
            'auto_remit_id' => 0,
            'payment_gateway_id' => '1',
            'code' => '180060',
        ];
        $redis->lpush('deposit_pay_status_error_queue', json_encode($params1));

        // 第二筆為正常的資料
        $params2 = [
            'entry_id' => '201304280000000001',
            'deposit' => 1,
            'card' => 0,
            'remit' => 0,
            'duplicate_count' => 0,
            'auto_remit_id' => 0,
            'payment_gateway_id' => '1',
            'code' => '180060',
        ];
        $redis->lpush('deposit_pay_status_error_queue', json_encode($params2));

        // 執行背景
        $this->runCommand('durian:deposit-pay-status-error');

        // 檢查寫入 deposit_pay_status_error 的資料
        $payErrors = $em->getRepository('BBDurianBundle:DepositPayStatusError')->findAll();

        // 確認DB只有寫入一筆資料且queue裡面已經沒有資料
        $this->assertEquals(1, count($payErrors));
        $this->assertEquals(0, $redis->llen('deposit_pay_status_error_queue'));

        // 檢查寫入DB的資料為第二筆
        $this->assertEquals($params2['entry_id'], $payErrors[0]->getEntryId());

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'deposit_pay_status_error.log';
        $this->assertFileExists($logPath);

        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $expect = 'Error: No cash deposit entry found, data: ' . json_encode($params1);
        $this->assertContains($expect, $results[0]);
    }

    /**
     * 測試將租卡入款異常錯誤寫入DB時找不到入款明細
     */
    public function testExecuteButCardDepositEntryNotFound()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cdeEntry = $em->getRepository('BBDurianBundle:CardDepositEntry')
            ->findOneBy(['id' => '201502010000000001']);
        $cdeEntry->confirm();
        $em->flush();

        // 第一筆資料沒有對應的入款明細
        $params1 = [
            'entry_id' => '201304280000000000',
            'deposit' => 0,
            'card' => 1,
            'remit' => 0,
            'duplicate_count' => 2,
            'auto_remit_id' => 0,
            'payment_gateway_id' => 1,
            'code' => '150370070',
        ];
        $redis->lpush('deposit_pay_status_error_queue', json_encode($params1));

        // 第二筆為正常的資料
        $params2 = [
            'entry_id' => '201502010000000001',
            'deposit' => 0,
            'card' => 1,
            'remit' => 0,
            'duplicate_count' => 2,
            'auto_remit_id' => 0,
            'payment_gateway_id' => 1,
            'code' => '150370070',
        ];
        $redis->lpush('deposit_pay_status_error_queue', json_encode($params2));

        // 執行背景
        $this->runCommand('durian:deposit-pay-status-error');

        // 檢查寫入 deposit_pay_status_error 的資料
        $payErrors = $em->getRepository('BBDurianBundle:DepositPayStatusError')->findAll();

        // 確認DB只有寫入一筆資料且queue裡面已經沒有資料
        $this->assertEquals(1, count($payErrors));
        $this->assertEquals(0, $redis->llen('deposit_pay_status_error_queue'));

        // 檢查寫入DB的資料為第二筆
        $this->assertEquals($params2['entry_id'], $payErrors[0]->getEntryId());

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'deposit_pay_status_error.log';
        $this->assertFileExists($logPath);

        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $expect = 'Error: No CardDepositEntry found, data: ' . json_encode($params1);
        $this->assertContains($expect, $results[0]);
    }

    /**
     * 測試將公司入款異常錯誤寫入DB時找不到入款明細
     */
    public function testExecuteButRemitEntryNotFound()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $reEntry = $em->getRepository('BBDurianBundle:RemitEntry')
            ->findOneBy(['orderNumber' => '2012010100002459']);
        $reEntry->setStatus(RemitEntry::CONFIRM);
        $em->flush();

        // 第一筆資料沒有對應的入款明細
        $params1 = [
            'entry_id' => '201304280000000000',
            'deposit' => 0,
            'card' => 0,
            'remit' => 1,
            'duplicate_count' => 2,
            'auto_remit_id' => 0,
            'payment_gateway_id' => 0,
            'code' => '150370068',
        ];
        $redis->lpush('deposit_pay_status_error_queue', json_encode($params1));

        // 第二筆為正常的資料
        $params2 = [
            'entry_id' => '2012010100002459',
            'deposit' => 0,
            'card' => 0,
            'remit' => 1,
            'duplicate_count' => 2,
            'auto_remit_id' => 0,
            'payment_gateway_id' => 0,
            'code' => '150370068',
        ];
        $redis->lpush('deposit_pay_status_error_queue', json_encode($params2));

        // 執行背景
        $this->runCommand('durian:deposit-pay-status-error');

        // 檢查寫入 deposit_pay_status_error 的資料
        $payErrors = $em->getRepository('BBDurianBundle:DepositPayStatusError')->findAll();

        // 確認DB只有寫入一筆資料且queue裡面已經沒有資料
        $this->assertEquals(1, count($payErrors));
        $this->assertEquals(0, $redis->llen('deposit_pay_status_error_queue'));

        // 檢查寫入DB的資料為第二筆
        $this->assertEquals($params2['entry_id'], $payErrors[0]->getEntryId());

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'deposit_pay_status_error.log';
        $this->assertFileExists($logPath);

        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);
        $expect = 'Error: No RemitEntry found, data: ' . json_encode($params1);
        $this->assertContains($expect, $results[0]);
    }

    /**
     * 測試將異常入款錯誤寫入DB時Queue有相同訂單號
     */
    public function testExecuteButEntryIdExistInQueue()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cdeEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201304280000000001']);
        $cdeEntry->confirm();
        $em->flush();

        $params = [
            'entry_id' => '201304280000000001',
            'deposit' => 1,
            'card' => 0,
            'remit' => 0,
            'duplicate_count' => 2,
            'auto_remit_id' => 0,
            'payment_gateway_id' => '1',
            'code' => '150370069',
        ];

        // 推入兩筆重複訂單號的資料
        for ($i = 0; $i < 2; $i++) {
            $redis->lpush('deposit_pay_status_error_queue', json_encode($params));
        }

        // 執行背景
        $this->runCommand('durian:deposit-pay-status-error');

        // 檢查寫入 deposit_pay_status_error 的資料
        $payErrors = $em->getRepository('BBDurianBundle:DepositPayStatusError')->findAll();

        // 確認DB只有寫入一筆資料且queue裡面已經沒有資料
        $this->assertCount(1, $payErrors);
        $this->assertEquals(0, $redis->llen('deposit_pay_status_error_queue'));

        // 檢查寫入DB的資料
        $this->assertEquals($params['entry_id'], $payErrors[0]->getEntryId());
    }

    /**
     * 測試將異常入款錯誤寫入DB時DB有相同訂單號
     */
    public function testExecuteButEntryIdExistInDB()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cdeEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201304280000000001']);
        $cdeEntry->confirm();

        $domain = $cdeEntry->getDomain();
        $userId = $cdeEntry->getUserId();
        $confirmAt = $cdeEntry->getConfirmAt();

        $error = new DepositPayStatusError(201304280000000001, $domain, $userId, $confirmAt, '150370069');
        $error->setDeposit(true);
        $error->setPaymentGatewayId(1);

        $em->persist($error);
        $em->flush();

        $params = [
            'entry_id' => '201304280000000001',
            'deposit' => 1,
            'card' => 0,
            'remit' => 0,
            'duplicate_count' => 2,
            'auto_remit_id' => 0,
            'payment_gateway_id' => '1',
            'code' => '150370069',
        ];

        $redis->lpush('deposit_pay_status_error_queue', json_encode($params));

        // 執行背景
        $this->runCommand('durian:deposit-pay-status-error');

        // 檢查寫入 deposit_pay_status_error 的資料
        $payErrors = $em->getRepository('BBDurianBundle:DepositPayStatusError')->findAll();

        // 確認DB只有一筆資料且queue裡面已經沒有資料
        $this->assertCount(1, $payErrors);
        $this->assertEquals(0, $redis->llen('deposit_pay_status_error_queue'));
    }

    /**
     * 刪除相關log
     */
    public function tearDown()
    {
        // clear log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'deposit_pay_status_error.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }
}
