<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Command\SendAutoWithdrawRequestCommand;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class SendAutoWithdrawRequestCommandTest extends WebTestCase
{
    /**
     * log檔案路徑
     *
     * @var array
     */
    private $filePath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadWithdrawErrorData',
        ];
        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $this->filePath = $logsDir . DIRECTORY_SEPARATOR . $env . '/send_auto_withdraw_request.log';
    }

    /**
     * 測試出款請求出款明細不存在
     */
    public function testExecuteWithdrawEntryNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->rpush('auto_withdraw_queue', 456123);

        $this->runCommand('durian:send-auto-withdraw-request');

        $withdrawError = $em->find('BBDurianBundle:WithdrawError', 2);

        $this->assertEquals(380001, $withdrawError->getErrorCode());
        $this->assertEquals('No such withdraw entry', $withdrawError->getErrorMessage());

        // 驗證資料是否有推進queue
        $setWithdrawStatusQueue = json_decode($redis->rpop('set_withdraw_status_queue'), true);
        $this->assertEquals(456123, $setWithdrawStatusQueue['entry_id']);
        $this->assertEquals(6, $setWithdrawStatusQueue['status']);
    }

    /**
     * 測試出款請求出款商家不存在
     */
    public function testExecuteMerchantWithdrawNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->rpush('auto_withdraw_queue', 1);

        $this->runCommand('durian:send-auto-withdraw-request');

        $withdrawError = $em->find('BBDurianBundle:WithdrawError', 2);

        $this->assertEquals(150380029, $withdrawError->getErrorCode());
        $this->assertEquals('No MerchantWithdraw found', $withdrawError->getErrorMessage());

        // 驗證資料是否有推進queue
        $setWithdrawStatusQueue = json_decode($redis->rpop('set_withdraw_status_queue'), true);
        $this->assertEquals(1, $setWithdrawStatusQueue['entry_id']);
        $this->assertEquals(6, $setWithdrawStatusQueue['status']);
    }

    /**
     * 測試出款請求成功
     */
    public function testExecuteSuccess()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->rpush('auto_withdraw_queue', 8);

        $this->runCommand('durian:send-auto-withdraw-request');

        $withdrawError = $em->find('BBDurianBundle:WithdrawError', 2);

        $this->assertNull($withdrawError);
        $this->assertFileExists($this->filePath);

        // 驗證資料是否有推進queue
        $setWithdrawStatusQueue = json_decode($redis->rpop('set_withdraw_status_queue'), true);
        $this->assertEquals(8, $setWithdrawStatusQueue['entry_id']);
        $this->assertEquals(1, $setWithdrawStatusQueue['status']);
    }

    /**
     * 刪除相關log
     */
    public function tearDown()
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        parent::tearDown();
    }
}
