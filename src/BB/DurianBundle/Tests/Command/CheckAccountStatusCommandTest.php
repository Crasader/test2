<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\CheckAccountStatusCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;
use BB\DurianBundle\Entity\CashWithdrawEntry;

class CheckAccountStatusCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelBankInfoData',
        ];

        $this->loadFixtures($classnames);

        $container = $this->getContainer();

        $redis = $container->get('snc_redis.default_client');
        $redis->flushdb();

        // clear log
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_account_status.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    /**
     * 測試缺少帶入時間參數
     */
    public function testExecuteWithoutTimeParameter()
    {
        // 都沒帶入
        $output = $this->runCommand('durian:check-account-status');

        $output = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $output[2]);
        $this->assertContains('No start or end specified', $output[3]);

        // 只帶入 start
        $param = ['--start' => '2015-03-05T14:29:16+0800'];
        $output = $this->runCommand('durian:check-account-status', $param);

        $output = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $output[2]);
        $this->assertContains('No start or end specified', $output[3]);

        // 只帶入 end
        $param = ['--end' => '2015-03-05T15:29:16+0800'];
        $output = $this->runCommand('durian:check-account-status', $param);

        $output = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $output[2]);
        $this->assertContains('No start or end specified', $output[3]);
    }

    /**
     * 測試藉由Account回傳的狀態確認出款功能
     */
    public function testConfirmStatusByAccountResult()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 8]);

        $this->assertEquals(CashWithdrawEntry::UNTREATED, $entry->getStatus());
        $this->assertEmpty($entry->getCheckedUsername());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertNull($userStat);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $ret[8] = [
            'status' => 1,
            'username' => 'ginny完成'
        ];

        $content = json_encode($ret);

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new CheckAccountStatusCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:check-account-status');
        $commandTester = new CommandTester($command);

        $param = [
            '--start' => '2015-03-05T14:29:16+0800',
            '--end' => '2015-03-05T15:29:16+0800'
        ];

        $commandTester->execute($param);

        $em->refresh($entry);

        $this->assertEquals(CashWithdrawEntry::CONFIRM, $entry->getStatus());
        $this->assertEquals('ginny', $entry->getCheckedUsername());

        // check log file
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_account_status.log';

        $this->assertFileExists($logPath);

        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);

        $parameters = [
            'uitype' => 'auto',
            'start_time' => '2015-03-05 14:29:16',
            'end_time' => '2015-03-05 15:29:16'
        ];

        $expect = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $container->getParameter('account_ip'),
            $container->getParameter('account_domain'),
            'GET',
            '/app/tellership/auto_check_tellership.php?' . http_build_query($parameters),
            json_encode($parameters),
            $content
        );

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getWithdrawCount());
        $this->assertEquals(185.0, $userStat->getWithdrawTotal());
        $this->assertEquals(185.0, $userStat->getWithdrawMax());

        $this->assertContains($expect, $results[0]);

        $queue = json_decode($redis->rpop('cash_deposit_withdraw_queue'), true);
        $this->assertEquals(0, $queue['ERRCOUNT']);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertFalse($queue['deposit']);
        $this->assertTrue($queue['withdraw']);
        $this->assertNotNull($queue['withdraw_at']);
    }

    /**
     * 測試藉由Account回傳的狀態確認出款功能(回傳為空陣列)
     */
    public function testConfirmStatusByAccountResultWithResponseIsEmptyArray()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 8]);

        $this->assertEquals(CashWithdrawEntry::UNTREATED, $entry->getStatus());

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $content = json_encode([]);

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new CheckAccountStatusCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:check-account-status');
        $commandTester = new CommandTester($command);

        $param = [
            '--start' => '2015-03-05T14:29:16+0800',
            '--end' => '2015-03-05T15:29:16+0800'
        ];

        $commandTester->execute($param);

        $em->refresh($entry);

        $this->assertEquals(CashWithdrawEntry::UNTREATED, $entry->getStatus());

        // check log file
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_account_status.log';

        $this->assertFileExists($logPath);

        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);

        $parameters = [
            'uitype' => 'auto',
            'start_time' => '2015-03-05 14:29:16',
            'end_time' => '2015-03-05 15:29:16'
        ];

        $expect = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $container->getParameter('account_ip'),
            $container->getParameter('account_domain'),
            'GET',
            '/app/tellership/auto_check_tellership.php?' . http_build_query($parameters),
            json_encode($parameters),
            $content
        );

        $this->assertContains($expect, $results[0]);
    }

    /**
     * 測試藉由Account回傳的狀態確認出款功能(回傳Account狀態不為1)
     */
    public function testConfirmStatusByAccountResultWithAccountStatusNotConfirm()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 8]);

        $this->assertEquals(CashWithdrawEntry::UNTREATED, $entry->getStatus());

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $ret[8] = [
            'status' => 2,
            'username' => 'ginny完成'
        ];

        $content = json_encode($ret);

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new CheckAccountStatusCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:check-account-status');
        $commandTester = new CommandTester($command);

        $param = [
            '--start' => '2015-03-05T14:29:16+0800',
            '--end' => '2015-03-05T15:29:16+0800'
        ];

        $commandTester->execute($param);

        $em->refresh($entry);

        $this->assertEquals(CashWithdrawEntry::UNTREATED, $entry->getStatus());

        // check log file
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_account_status.log';

        $this->assertFileExists($logPath);

        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);

        $parameters = [
            'uitype' => 'auto',
            'start_time' => '2015-03-05 14:29:16',
            'end_time' => '2015-03-05 15:29:16'
        ];

        $expect = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $container->getParameter('account_ip'),
            $container->getParameter('account_domain'),
            'GET',
            '/app/tellership/auto_check_tellership.php?' . http_build_query($parameters),
            json_encode($parameters),
            $content
        );

        $this->assertContains($expect, $results[0]);
    }

    /**
     * 測試藉由Account回傳的狀態確認出款功能Entry已Confirm
     */
    public function testConfirmStatusByAccountResultWithEntryStatusConfirm()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 1]);

        $this->assertEquals(CashWithdrawEntry::CONFIRM, $entry->getStatus());

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $ret[1] = [
            'status' => 1,
            'username' => 'ginny完成'
        ];

        $content = json_encode($ret);

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new CheckAccountStatusCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:check-account-status');
        $commandTester = new CommandTester($command);

        $param = [
            '--start' => '2015-03-05T14:29:16+0800',
            '--end' => '2015-03-05T15:29:16+0800'
        ];

        $commandTester->execute($param);

        $em->refresh($entry);

        $this->assertEquals(CashWithdrawEntry::CONFIRM, $entry->getStatus());

        // check log file
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_account_status.log';

        $this->assertFileExists($logPath);

        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);

        $parameters = [
            'uitype' => 'auto',
            'start_time' => '2015-03-05 14:29:16',
            'end_time' => '2015-03-05 15:29:16'
        ];

        $expect = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $container->getParameter('account_ip'),
            $container->getParameter('account_domain'),
            'GET',
            '/app/tellership/auto_check_tellership.php?' . http_build_query($parameters),
            json_encode($parameters),
            $content
        );

        $this->assertContains($expect, $results[0]);
    }

    /**
     * 測試藉由時間區間到帳戶系統確認出款回傳狀態不為200
     */
    public function testGetConfirmStatusByTimeWithIllegalResponse()
    {
        $container = $this->getContainer();

        $ret[2] = [
            'status' => 1,
            'username' => 'test完成'
        ];

        $content = json_encode($ret);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 499');

        $application = new Application();
        $command = new CheckAccountStatusCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:check-account-status');
        $commandTester = new CommandTester($command);

        $param = [
            '--start' => '2015-03-05T14:29:16+0800',
            '--end' => '2015-03-05T15:29:16+0800'
        ];

        $commandTester->execute($param);

        // check log file
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_account_status.log';

        $this->assertFileExists($logPath);

        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);

        $parameters = [
            'uitype' => 'auto',
            'start_time' => '2015-03-05 14:29:16',
            'end_time' => '2015-03-05 15:29:16'
        ];

        $expect = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $container->getParameter('account_ip'),
            $container->getParameter('account_domain'),
            'GET',
            '/app/tellership/auto_check_tellership.php?' . http_build_query($parameters),
            json_encode($parameters),
            $content
        );

        $this->assertContains($expect, $results[0]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_exception_queue';
        $msg = '檢查帳戶系統(藉由account時間區間)確認出款狀態失敗';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals('RuntimeException', $queueMsg['exception']);
        $this->assertEquals($msg, $queueMsg['message']);
    }

    /**
     * 刪除相關log
     */
    public function tearDown()
    {
        $container = $this->getContainer();
        // clear log
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'check_account_status.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }
}
