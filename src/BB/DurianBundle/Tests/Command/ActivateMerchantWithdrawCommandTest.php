<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\MerchantWithdraw;

class ActivateMerchantWithdrawCommandTest extends WebTestCase
{
    /**
     * log檔案路徑
     *
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData'];
        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logName = 'ActivateMerchantWithdraw.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . $logName;

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 刪除產生的log檔
     */
    public function tearDown() {
        parent::tearDown();

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 測試每天自動恢復出款商家額度，並寫紀錄進 MerchantWithdrawRecord
     */
    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $merchantWithdraw1 = $em->find('BBDurianBundle:MerchantWithdraw', 1);
        $merchantWithdraw1->suspend();
        $this->assertTrue($merchantWithdraw1->isSuspended());

        $merchantWithdraw2 = $em->find('BBDurianBundle:MerchantWithdraw', 3);
        $merchantWithdraw2->enable();
        $merchantWithdraw2->suspend();
        $this->assertTrue($merchantWithdraw2->isSuspended());

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);

        // domain=6
        $merchantWithdraw3 = new MerchantWithdraw($paymentGateway, 'EZPAY', '1234567890', 6, 156);
        $em->persist($merchantWithdraw3);
        $merchantWithdraw3->enable();
        $merchantWithdraw3->suspend();
        $this->assertTrue($merchantWithdraw3->isSuspended());

        // domain=98
        $merchantWithdraw4 = new MerchantWithdraw($paymentGateway, 'EZPAY', '1234567890', 98, 156);
        $em->persist($merchantWithdraw4);
        $merchantWithdraw4->enable();
        $merchantWithdraw4->suspend();
        $this->assertTrue($merchantWithdraw4->isSuspended());

        $em->flush();

        // 恢復出款商家額度
        $this->runCommand('durian:cronjob:activate-merchant-withdraw');

        $em->refresh($merchantWithdraw1);
        $this->assertFalse($merchantWithdraw1->isSuspended());

        $em->refresh($merchantWithdraw2);
        $this->assertFalse($merchantWithdraw2->isSuspended());

        $em->refresh($merchantWithdraw3);
        $this->assertFalse($merchantWithdraw3->isSuspended());

        $em->refresh($merchantWithdraw4);
        $this->assertFalse($merchantWithdraw4->isSuspended());

        // 檢查訊息
        $merchantWithdrawRecord1 = $em->find('BBDurianBundle:MerchantWithdrawRecord', 1);
        $msg = '因跨天額度重新計算, 出款商家編號:(1, 3), 回復初始設定';
        $this->assertEquals(1, $merchantWithdrawRecord1->getDomain());
        $this->assertEquals($msg, $merchantWithdrawRecord1->getMsg());

        $merchantWithdrawRecord2 = $em->find('BBDurianBundle:MerchantWithdrawRecord', 2);
        $msg = '因跨天額度重新計算, 出款商家編號:(6), 回復初始設定';
        $this->assertEquals(6, $merchantWithdrawRecord2->getDomain());
        $this->assertEquals($msg, $merchantWithdrawRecord2->getMsg());

        $merchantWithdrawRecord3 = $em->find('BBDurianBundle:MerchantWithdrawRecord', 3);
        $msg = '因跨天額度重新計算, 出款商家編號:(7), 回復初始設定';
        $this->assertEquals(98, $merchantWithdrawRecord3->getDomain());
        $this->assertEquals($msg, $merchantWithdrawRecord3->getMsg());

        $merchantWithdrawRecord4 = $em->find('BBDurianBundle:MerchantWithdrawRecord', 4);
        $this->assertNull($merchantWithdrawRecord4);

        // 檢查 italking queue 內容
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';

        $this->assertEquals(2, $redis->llen($key));

        // domain = 6, 驗證payment_alarm，送到eaball
        $msg = '因跨天額度重新計算, 出款商家編號:(6), 回復初始設定';
        $queueMsg = json_decode($redis->rpop($key), true);
        $code = $this->getContainer()->getParameter('italking_esball_code');
        $this->assertEquals('payment_alarm', $queueMsg['type']);
        $this->assertStringEndsWith($msg, $queueMsg['message']);
        $this->assertEquals($code, $queueMsg['code']);

        // domain = 98, 驗證payment_alarm，送到博九
        $msg = '因跨天額度重新計算, 出款商家編號:(7), 回復初始設定';
        $queueMsg = json_decode($redis->rpop($key), true);
        $code = $this->getContainer()->getParameter('italking_bet9_code');
        $this->assertEquals('payment_alarm', $queueMsg['type']);
        $this->assertStringEndsWith($msg, $queueMsg['message']);
        $this->assertEquals($code, $queueMsg['code']);

        // 檢查log
        $this->assertFileExists($this->logPath);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $serverIp = $italkingOperator->getServerIp();

        $logContent1 = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $serverIp,
            '',
            '',
            '',
            '',
            "Processing domain 1, merchantWithdraw: 1, 3."
        );
        $this->assertContains($logContent1, $results[0]);

        $logContent2 = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $serverIp,
            '',
            '',
            '',
            '',
            "Processing domain 6, merchantWithdraw: 6."
        );
        $this->assertContains($logContent2, $results[1]);

        $logContent3 = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $serverIp,
            '',
            '',
            '',
            '',
            "Processing domain 98, merchantWithdraw: 7."
        );
        $this->assertContains($logContent3, $results[2]);
    }
}
