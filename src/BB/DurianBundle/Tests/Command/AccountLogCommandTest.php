<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\AccountLogCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;
use BB\DurianBundle\Entity\AccountLog;
use BB\DurianBundle\Entity\DomainConfig;

class AccountLogCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAccountLogData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashWithdrawEntryData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();
    }

    /**
     * 發出款資訊到Account主要在BB\DurianBundle\Tests\Withdraw\HelperTest測試
     * 這邊主要是測CI
     */
    public function testExecute()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->setMethods(['send'])
            ->getMock();

        $client->expects($this->any())
            ->method('send')
            ->willThrowException(new \Exception('<url> malformed', 3));

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $application->add($command);

        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'AccountLog.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        //此背景主要都是紀錄於log中，並不會有任何頁面輸出
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $output = $commandTester->getDisplay();

        // check result
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('', $results[0]);
        // check log file exists
        $this->assertFileExists($logPath);

        //read log to check content
        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);

        $this->assertStringEndsWith('LOGGER.INFO: AccountLogCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith('LOGGER.INFO: id:1 Error 3 Msg: <url> malformed [] []', $results[1]);
        $this->assertStringEndsWith('LOGGER.INFO: AccountLogCommand finish. [] []', $results[2]);

        unlink($logPath);
    }

    /**
     * 發出款資訊到Account不紀錄Log
     */
    public function testExecuteWithDisableLog()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'AccountLog.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        //此背景主要都是紀錄於log中，並不會有任何頁面輸出
        $commandTester = new CommandTester($command);

        $params = [
            'command' => $command->getName(),
            '--disable-log' => true
        ];

        $commandTester->execute($params);
        $output = $commandTester->getDisplay();

        // check result
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('', $results[0]);
        // check log file exists
        $this->assertFileExists($logPath);

        //read log to check content
        $content = file_get_contents($logPath);

        $this->assertEquals('', $content);

        unlink($logPath);
    }

    /**
     * 發出款資訊到Account回傳失敗
     */
    public function testExecuteWithRequestAccountLogFailed()
    {
        $container = $this->getContainer();
        $logsDir = $container->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/AccountLog.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $dirPath = $logsDir . DIRECTORY_SEPARATOR . 'test/account';
        $filePath = $dirPath . DIRECTORY_SEPARATOR . 'toAcc.log';

        if (file_exists($dirPath)) {
            $this->delDir($dirPath);
        }

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $em = $container->get('doctrine.orm.entity_manager');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.logger_manager', 1, $loggerManager],
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.currency', 1, $currencyOperator]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $respone = new Response();
        $respone->setContent('{"result":"fail"}');
        $respone->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($respone);
        $application->add($command);

        $command = $application->find('durian:toAccount');
        $commandTester = new CommandTester($command);

        //此背景主要都是紀錄於log中，並不會有任何頁面輸出
        $output = $commandTester->execute(['command' => $command->getName()]);

        // check result
        $results = explode(PHP_EOL, $output);

        $this->assertEquals(0, $results[0]);
        // check log file exists
        $this->assertFileExists($logPath);

        //read log to check content
        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);

        $this->assertStringEndsWith('LOGGER.INFO: AccountLogCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith('LOGGER.INFO: id:1 Fail  [] []', $results[1]);
        $this->assertStringEndsWith('LOGGER.INFO: AccountLogCommand finish. [] []', $results[2]);

        unlink($logPath);

        // check log file exists
        $this->assertFileExists($filePath);

        //read log to check content
        $handle = fopen($filePath, "rb");
        $contents = fread($handle, filesize($filePath));
        fclose($handle);

        $results = explode("\r\n", $contents);

        $this->assertStringEndsWith('HTTP/1.1 200 OK', trim($results[3]));
        $this->assertStringEndsWith("{\"result\":\"fail\"}", trim($results[5]));

        unlink($filePath);
    }

    /**
     * 測試出款失敗三次會發送訊息至GM的italking
     */
    public function testSendAccountFailedThreeTimesWillSendMessageToGMItalking()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 執行三次後檢查要送去iTalking的訊息
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';

        $gmMsg = "出款傳送至Account失敗\n" .
            "① 請客服測試登入 test.account.net 是否正常。如果無法正常登入，請致電 RD2-應用技術部-Nate 上線查看。\n" .
            '② 如果正常，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常';

        $msg = sprintf(
            '%s, ID: %s, %s。',
            'domain2@cm',
            1,
            $gmMsg
        );

        $this->assertEquals(1, $redis->llen($key));

        $gmMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $gmMsg['type']);
        $this->assertContains($msg, $gmMsg['message']);
        $this->assertEquals(1, $gmMsg['code']);
    }

    /**
     * 測試出款失敗三次且對應entry的domain = 6，會發送訊息至Esball的italking
     */
    public function testSendAccountFailedThreeTimesWillSendMessageToEsballItalking()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qb->set('cwe.domain', 6);
        $qb->where('cwe.id = :id');
        $qb->setParameter('id', 1);
        $qb->getQuery()->execute();

        $user = $em->find('BBDurianBundle:User', 6);

        $config = new DomainConfig($user, 'domain6', 'dd');
        $emShare->persist($config);
        $emShare->flush();

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 執行三次後檢查要送去iTalking的訊息
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = sprintf(
            '%s, ID: %s, %s。%s。',
            'domain6@dd',
            1,
            '出款傳送至Account失敗，請至現金記錄(ACC記錄)重送',
            '如發生多筆，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常'
        );

        $gmMsg = "出款傳送至Account失敗\n" .
            "① 請客服測試登入 test.account.net 是否正常。如果無法正常登入，請致電 RD2-應用技術部-Nate 上線查看。\n" .
            '② 如果正常，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常';

        $msgToGm = sprintf(
            '%s, ID: %s, %s。',
            'domain6@dd',
            1,
            $gmMsg
        );

        $this->assertEquals(2, $redis->llen($key));

        // 一筆送給GM
        $gmMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $gmMsg['type']);
        $this->assertContains($msgToGm, $gmMsg['message']);
        $this->assertEquals(1, $gmMsg['code']);

        // 一筆送給esball
        $esballMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $esballMsg['type']);
        $this->assertContains($msg, $esballMsg['message']);
        $this->assertEquals(6, $esballMsg['code']);
    }

    /**
     * 測試出款失敗三次且對應entry的domain = 98，會發送訊息至博九的italking
     */
    public function testSendAccountFailedThreeTimesWillSendMessageToBet9Italking()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $qbEntry = $em->createQueryBuilder();
        $qbEntry->update('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qbEntry->set('cwe.domain', 98);
        $qbEntry->where('cwe.id = :id');
        $qbEntry->setParameter('id', 1);
        $qbEntry->getQuery()->execute();

        $qbUser = $em->createQueryBuilder();
        $qbUser->update('BBDurianBundle:User', 'u');
        $qbUser->set('u.id', 98);
        $qbUser->where('u.id = :id');
        $qbUser->setParameter('id', 6);
        $qbUser->getQuery()->execute();

        $user = $em->find('BBDurianBundle:User', 98);

        $config = new DomainConfig($user, 'domain98', 'dd');
        $emShare->persist($config);
        $emShare->flush();

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 執行三次後檢查要送去iTalking的訊息
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = sprintf(
            '%s, ID: %s, %s。%s。',
            'domain98@dd',
            1,
            '出款傳送至Account失敗，請至現金記錄(ACC記錄)重送',
            '如發生多筆，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常'
        );

        $gmMsg = "出款傳送至Account失敗\n" .
            "① 請客服測試登入 test.account.net 是否正常。如果無法正常登入，請致電 RD2-應用技術部-Nate 上線查看。\n" .
            '② 如果正常，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常';

        $msgToGm = sprintf(
            '%s, ID: %s, %s。',
            'domain98@dd',
            1,
            $gmMsg
        );

        $this->assertEquals(2, $redis->llen($key));

        // 一筆送給GM
        $gmMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $gmMsg['type']);
        $this->assertContains($msgToGm, $gmMsg['message']);
        $this->assertEquals(1, $gmMsg['code']);

        // 一筆送給bet9
        $bet9Msg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $bet9Msg['type']);
        $this->assertContains($msg, $bet9Msg['message']);
        $this->assertEquals(98, $bet9Msg['code']);
    }

    /**
     * 測試出款失敗三次且對應 entry 的 domain = 3820175，會發送訊息至 kresball 的 italking
     */
    public function testSendAccountFailedThreeTimesWillSendMessageToKresballItalking()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $qbEntry = $em->createQueryBuilder();
        $qbEntry->update('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qbEntry->set('cwe.domain', 3820175);
        $qbEntry->where('cwe.id = :id');
        $qbEntry->setParameter('id', 1);
        $qbEntry->getQuery()->execute();

        $qbUser = $em->createQueryBuilder();
        $qbUser->update('BBDurianBundle:User', 'u');
        $qbUser->set('u.id', 3820175);
        $qbUser->where('u.id = :id');
        $qbUser->setParameter('id', 6);
        $qbUser->getQuery()->execute();

        $user = $em->find('BBDurianBundle:User', 3820175);

        $config = new DomainConfig($user, 'kresball', 'kr');
        $emShare->persist($config);
        $emShare->flush();

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1'],
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 執行三次後檢查要送去iTalking的訊息
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = sprintf(
            '%s, ID: %s, %s。%s。',
            'kresball@kr',
            1,
            '出款傳送至Account失敗，請至現金記錄(ACC記錄)重送',
            '如發生多筆，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常'
        );

        $gmMsg = "出款傳送至Account失敗\n" .
            "① 請客服測試登入 test.account.net 是否正常。如果無法正常登入，請致電 RD2-應用技術部-Nate 上線查看。\n" .
            '② 如果正常，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常';

        $msgToGm = sprintf(
            '%s, ID: %s, %s。',
            'kresball@kr',
            1,
            $gmMsg
        );

        $this->assertEquals(2, $redis->llen($key));

        // 一筆送給GM
        $gmMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $gmMsg['type']);
        $this->assertContains($msgToGm, $gmMsg['message']);
        $this->assertEquals(1, $gmMsg['code']);

        // 一筆送給 kresball
        $kresballMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail_kr', $kresballMsg['type']);
        $this->assertContains($msg, $kresballMsg['message']);
        $this->assertEquals(140502001, $kresballMsg['code']);
    }

    /**
     * 測試出款失敗三次且對應 entry 的 domain = 3819935，會發送訊息至 esball global 的 italking
     */
    public function testSendAccountFailedThreeTimesWillSendMessageToEsballGlobalItalking()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $qbEntry = $em->createQueryBuilder();
        $qbEntry->update('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qbEntry->set('cwe.domain', 3819935);
        $qbEntry->where('cwe.id = :id');
        $qbEntry->setParameter('id', 1);
        $qbEntry->getQuery()->execute();

        $qbUser = $em->createQueryBuilder();
        $qbUser->update('BBDurianBundle:User', 'u');
        $qbUser->set('u.id', 3819935);
        $qbUser->where('u.id = :id');
        $qbUser->setParameter('id', 6);
        $qbUser->getQuery()->execute();

        $user = $em->find('BBDurianBundle:User', 3819935);

        $config = new DomainConfig($user, 'esballglobal', 'egl');
        $emShare->persist($config);
        $emShare->flush();

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1'],
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 執行三次後檢查要送去iTalking的訊息
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = sprintf(
            '%s, ID: %s, %s。%s。',
            'esballglobal@egl',
            1,
            '出款傳送至Account失敗，請至現金記錄(ACC記錄)重送',
            '如發生多筆，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常'
        );

        $gmMsg = "出款傳送至Account失敗\n" .
            "① 請客服測試登入 test.account.net 是否正常。如果無法正常登入，請致電 RD2-應用技術部-Nate 上線查看。\n" .
            '② 如果正常，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常';

        $msgToGm = sprintf(
            '%s, ID: %s, %s。',
            'esballglobal@egl',
            1,
            $gmMsg
        );

        $this->assertEquals(2, $redis->llen($key));

        // 一筆送給GM
        $gmMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $gmMsg['type']);
        $this->assertContains($msgToGm, $gmMsg['message']);
        $this->assertEquals(1, $gmMsg['code']);

        // 一筆送給 esball global
        $esballGlobalMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $esballGlobalMsg['type']);
        $this->assertContains($msg, $esballGlobalMsg['message']);
        $this->assertEquals(141023001, $esballGlobalMsg['code']);
    }

    /**
     * 測試出款失敗三次且對應 entry 的 domain = 3820190，會發送訊息至 eslot 的 italking
     */
    public function testSendAccountFailedThreeTimesWillSendMessageToEslotItalking()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $qbEntry = $em->createQueryBuilder();
        $qbEntry->update('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qbEntry->set('cwe.domain', 3820190);
        $qbEntry->where('cwe.id = :id');
        $qbEntry->setParameter('id', 1);
        $qbEntry->getQuery()->execute();

        $qbUser = $em->createQueryBuilder();
        $qbUser->update('BBDurianBundle:User', 'u');
        $qbUser->set('u.id', 3820190);
        $qbUser->where('u.id = :id');
        $qbUser->setParameter('id', 6);
        $qbUser->getQuery()->execute();

        $user = $em->find('BBDurianBundle:User', 3820190);

        $config = new DomainConfig($user, 'eslot', 'Pro');
        $emShare->persist($config);
        $emShare->flush();

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1'],
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 執行三次後檢查要送去iTalking的訊息
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = sprintf(
            '%s, ID: %s, %s。%s。',
            'eslot@Pro',
            1,
            '出款傳送至Account失敗，請至現金記錄(ACC記錄)重送',
            '如發生多筆，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常'
        );

        $gmMsg = "出款傳送至Account失敗\n" .
            "① 請客服測試登入 test.account.net 是否正常。如果無法正常登入，請致電 RD2-應用技術部-Nate 上線查看。\n" .
            '② 如果正常，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常';

        $msgToGm = sprintf(
            '%s, ID: %s, %s。',
            'eslot@Pro',
            1,
            $gmMsg
        );

        $this->assertEquals(2, $redis->llen($key));

        // 一筆送給GM
        $gmMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $gmMsg['type']);
        $this->assertContains($msgToGm, $gmMsg['message']);
        $this->assertEquals(1, $gmMsg['code']);

        // 一筆送給 eslot
        $eslotMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $eslotMsg['type']);
        $this->assertContains($msg, $eslotMsg['message']);
        $this->assertEquals(160810001, $eslotMsg['code']);
    }
    /**
     * 測試發送出款資訊到帳號出款系統
     */
    public function testRequestAccountLog()
    {
        $container = $this->getContainer();
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'AccountLog.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $dirPath = $logsDir . DIRECTORY_SEPARATOR . 'account';
        $filePath = $dirPath . DIRECTORY_SEPARATOR . 'toAcc.log';

        if (file_exists($dirPath)) {
            $this->delDir($dirPath);
        }

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $em = $container->get('doctrine.orm.entity_manager');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $accountLog = $em->find('BBDurianBundle:AccountLog', 1);

        $this->assertEquals(0, $accountLog->getCount());
        $this->assertEquals(AccountLog::UNTREATED, $accountLog->getStatus());

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.logger_manager', 1, $loggerManager],
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.currency', 1, $currencyOperator]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $respone = new Response();
        $respone->setContent('{"result":"success"}');
        $respone->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($respone);
        $application->add($command);

        $command = $application->find('durian:toAccount');
        $commandTester = new CommandTester($command);

        //此背景主要都是紀錄於log中，並不會有任何頁面輸出
        $output = $commandTester->execute(['command' => $command->getName()]);

        // check result
        $results = explode(PHP_EOL, $output);

        $this->assertEquals(0, $results[0]);
        // check log file exists
        $this->assertFileExists($logPath);

        //read log to check content
        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);

        $this->assertStringEndsWith('LOGGER.INFO: AccountLogCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith('LOGGER.INFO: id:1 Success  [] []', $results[1]);
        $this->assertStringEndsWith('LOGGER.INFO: AccountLogCommand finish. [] []', $results[2]);

        unlink($logPath);

        // check log file exists
        $this->assertFileExists($filePath);

        //read log to check content
        $handle = fopen($filePath, "rb");
        $contents = fread($handle, filesize($filePath));
        fclose($handle);

        $results = explode("\r\n", $contents);

        $this->assertStringEndsWith('HTTP/1.1 200 OK', trim($results[3]));
        $this->assertStringEndsWith("{\"result\":\"success\"}", trim($results[5]));
        $this->assertContains('domain=6', $results[0]);

        unlink($filePath);

        // 確認狀態已修改
        $this->assertEquals(AccountLog::SENT, $accountLog->getStatus());
        $this->assertEquals(1, $accountLog->getCount());
    }

    /**
     * 發出款資訊到Account回傳不為success或duplicate
     */
    public function testExecuteWithRequestAccountLogNeitherSuccessNorDuplicate()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $logsDir = $container->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'AccountLog.log';

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qb->set('cwe.domain', 6);
        $qb->where('cwe.id = :id');
        $qb->setParameter('id', 1);
        $qb->getQuery()->execute();

        $user = $em->find('BBDurianBundle:User', 6);

        $config = new DomainConfig($user, 'domain6', 'dd');
        $emShare->persist($config);
        $emShare->flush();

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $dirPath = $logsDir . DIRECTORY_SEPARATOR . 'account';

        if (file_exists($dirPath)) {
            $this->delDir($dirPath);
        }

        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');
        $currencyOperator = $container->get('durian.currency');
        $loggerManager = $container->get('durian.logger_manager');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.italking_operator', 1, $italkingOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
            ['durian.currency', 1, $currencyOperator],
            ['durian.logger_manager', 1, $loggerManager]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $getParameterMap = [
            ['account_domain', 'test.account.net'],
            ['account_ip', 'http://127.0.0.1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($getParameterMap);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $respone = new Response();
        $respone->setContent('{"result":"invalid_web"}');
        $respone->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new AccountLogCommand();
        $command->setContainer($mockContainer);
        $command->setClient($client);
        $command->setResponse($respone);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 執行三次後檢查要送去iTalking的訊息
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);
        $commandTester->execute(['command' => $command->getName()]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';
        $msg = sprintf(
            '%s, ID: %s, %s。%s。',
            'domain6@dd',
            1,
            'Account回傳訊息異常，訊息為: invalid_web',
            '請通知RD2-應用技術部-Nate及RD5-電子商務部檢查'
        );

        $gmMsg = "出款傳送至Account失敗\n" .
            "① 請客服測試登入 test.account.net 是否正常。如果無法正常登入，請致電 RD2-應用技術部-Nate 上線查看。\n" .
            '② 如果正常，請聯絡DC-OP-維護組檢查 127.0.0.1 到 test.account.net 的線路及Account機器是否正常';

        $msgToGm = sprintf(
            '%s, ID: %s, %s。',
            'domain6@dd',
            1,
            $gmMsg
        );

        $this->assertEquals(2, $redis->llen($key));

        // 一筆送給GM
        $gmMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $gmMsg['type']);
        $this->assertContains($msgToGm, $gmMsg['message']);
        $this->assertEquals(1, $gmMsg['code']);

        // 一筆送給esball
        $esballMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('account_fail', $esballMsg['type']);
        $this->assertContains($msg, $esballMsg['message']);
        $this->assertEquals(6, $esballMsg['code']);
    }

    /**
     * 刪除路徑
     *
     * @param string $dir
     * @return bool
     */
    private function delDir($dir) {

        // 列出當前路徑所包含路徑及檔案
        $files = array_diff(scandir($dir), ['.','..']);

        foreach ($files as $file) {

            // 如果還是資料夾則遞迴往下刪除
            if (is_dir("$dir/$file")) {
                delDir("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }

        return rmdir($dir);
    }
}
