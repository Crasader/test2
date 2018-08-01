<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\ExecuteRmPlanCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\RmPlanUser;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RmPlan;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\CreditEntry;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;
use BB\DurianBundle\Entity\UserHasApiTransferInOut;
use Buzz\Exception\ClientException;

class ExecuteRmPlanCommandTest extends WebTestCase
{
    /**
     * log檔的路徑
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanData'
        ];

        $this->loadFixtures($classnames, 'share');

        $this->loadFixtures([], 'entry');

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'execute_rm_plan.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試刪除計畫使用者，成功發curl送出名單
     */
    public function testExecuteRmPlanBySendCurl()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = $em->find('BBDurianBundle:User', 51);
        $cash = new Cash($user, 156);
        $em->persist($cash);

        $cashFake = new CashFake($user, 156);
        $em->persist($cashFake);

        $credit = new Credit($user, 1);
        $em->persist($credit);
        $em->flush();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 0
            ]
        ];

        $ret = json_encode($ret);
        $result = json_encode([
            'message' => 'job created',
            'id' => 123
        ]);

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls($ret, $ret, $result));

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $param = [
            '--limit' => 10,
            '--batch-size' => 1
        ];
        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute($param);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('The user 51 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 2);
        $this->assertTrue($rpUser->isCurlKue());

        // 傳送成功待刪除後計畫才算完成
        $plan = $emShare->find('BBDurianBundle:RmPlan', 2);
        $this->assertFalse($plan->isFinished());
    }

    /**
     * 測試刪除不存在的使用者
     */
    public function testExecuteRmPlanButNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 新增一個不存在的使用者到刪除名單
        $rpUser = new RmPlanUser(2, 99, 'test', 'test');
        $em->persist($rpUser);
        $em->flush();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 0
            ]
        ];

        $ret = json_encode($ret);
        $result = json_encode(['result' => 'ok']);

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls($ret, $ret, $result));

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('User 99 does not exist', $results[1]);

        $em->refresh($rpUser);
        $this->assertEquals('該廳下無此使用者', $rpUser->getMemo());
        $this->assertTrue($rpUser->isCancel());

        $plan = $em->find('BBDurianBundle:RmPlan', 2);
        $this->assertFalse($plan->isFinished());
    }

    /**
     * 測試刪除不同廳的使用者
     */
    public function testExecuteRmPlanNotInSameDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 新增一個不同廳的使用者到刪除名單
        $rpUser = new RmPlanUser(2, 9, 'test', 'test');
        $em->persist($rpUser);
        $em->flush();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 0
            ]
        ];

        $ret = json_encode($ret);
        $result = json_encode(['result' => 'ok']);

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls($ret, $ret, $result));

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('User 9 does not exist', $results[1]);

        $em->refresh($rpUser);
        $this->assertEquals('該廳下無此使用者', $rpUser->getMemo());
        $this->assertTrue($rpUser->isCancel());

        $plan = $em->find('BBDurianBundle:RmPlan', 2);
        $this->assertFalse($plan->isFinished());
    }

    /**
     * 測試刪除已被刪除的不同廳使用者
     */
    public function testExecuteRmPlanWhichIsRemovedAndInDifferentDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = $em->find('BBDurianBundle:User', 9);
        $removedUser = new RemovedUser($user);
        $em->remove($user);
        $emShare->persist($removedUser);
        $em->flush();
        $emShare->flush();

        // 新增一個已被刪除的不同廳使用者到刪除名單
        $rpUser = new RmPlanUser(2, 9, 'test', 'test');
        $emShare->persist($rpUser);
        $emShare->flush();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 0
            ]
        ];

        $ret = json_encode($ret);
        $result = json_encode(['result' => 'ok']);

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls($ret, $ret, $result));

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('User 9 does not exist', $results[1]);

        $emShare->refresh($rpUser);
        $this->assertEquals('該廳下無此使用者', $rpUser->getMemo());
        $this->assertTrue($rpUser->isCancel());

        $plan = $emShare->find('BBDurianBundle:RmPlan', 2);
        $this->assertFalse($plan->isFinished());
    }

    /**
     * 測試刪除已被刪除的使用者
     */
    public function testExecuteRmPlanWhichIsRemoved()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 刪除使用者51
        $user = $em->find('BBDurianBundle:User', 51);
        $removedUser = new RemovedUser($user);
        $em->remove($user);
        $emShare->persist($removedUser);
        $em->flush();
        $emShare->flush();

        // 測試再次刪除使用者51
        $output = $this->runCommand('durian:execute-rm-plan');
        $results = explode(PHP_EOL, $output);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 2);
        $rPlan = $emShare->find('BBDurianBundle:RmPlan', 2);
        $user = $em->find('BBDurianBundle:User', 51);

        $this->assertEquals('User 51 has been removed', $results[0]);
        $this->assertEquals('Plan 2 finished', $results[1]);
        $this->assertEquals('使用者已被刪除', $rpUser->getMemo());
        $this->assertNull($user);
        $this->assertTrue($rpUser->isCancel());
        $this->assertTrue($rPlan->isFinished());
    }

    /**
     * 測試刪除當月有現金明細的使用者
     */
    public function testExecuteRmPlanHasCashEntryThisMonth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime();
        $user = $em->find('BBDurianBundle:User', 51);
        $cash = new Cash($user, 156);
        $cash->setLastEntryAt($now->format('YmdHis'));
        $em->persist($cash);
        $em->flush();

        $entry = new CashEntry($cash, 1001, 1000);
        $entry->setId(1);
        $entry->setRefId(1899192299);
        $entry->setCreatedAt($now);
        $entry->setAt($now->format('YmdHis'));
        $emEntry->persist($entry);
        $emEntry->flush();

        $output = $this->runCommand('durian:execute-rm-plan');
        $results = explode(PHP_EOL, $output);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 2);
        $rPlan = $emShare->find('BBDurianBundle:RmPlan', 2);

        $this->assertEquals('User 51 has entries this month', $results[0]);
        $this->assertEquals('Plan 2 finished', $results[1]);
        $this->assertEquals('使用者當月有下注記錄', $rpUser->getMemo());
        $this->assertTrue($rpUser->isCancel());
        $this->assertTrue($rPlan->isFinished());
    }

    /**
     * 測試刪除當月有假現金明細的使用者
     */
    public function testExecuteRmPlanHasCashFakeEntryThisMonth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime();
        $user = $em->find('BBDurianBundle:User', 51);
        $cashFake = new CashFake($user, 156);
        $cashFake->setLastEntryAt($now->format('YmdHis'));
        $em->persist($cashFake);
        $em->flush();

        $entry = new CashFakeEntry($cashFake, 1006, 1000);
        $entry->setId(1);
        $entry->setRefId(1899192299);
        $entry->setCreatedAt($now);
        $entry->setAt($now->format('YmdHis'));
        $em->persist($entry);
        $em->flush();

        $output = $this->runCommand('durian:execute-rm-plan');
        $results = explode(PHP_EOL, $output);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 2);
        $rPlan = $emShare->find('BBDurianBundle:RmPlan', 2);

        $this->assertEquals('User 51 has entries this month', $results[0]);
        $this->assertEquals('Plan 2 finished', $results[1]);
        $this->assertEquals('使用者當月有下注記錄', $rpUser->getMemo());
        $this->assertTrue($rpUser->isCancel());
        $this->assertTrue($rPlan->isFinished());
    }

    /**
     * 測試刪除當月有信用額度明細的使用者
     */
    public function testExecuteRmPlanHasCreditEntryThisMonth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $user = $em->find('BBDurianBundle:User', 51);
        $credit = new Credit($user, 1);
        $credit->setLine(10000);
        $credit->setLastEntryAt($now->format('YmdHis'));

        $entry = new CreditEntry(51, 1, 50020, -100, 900, $now);
        $entry->setCreditId(1);
        $entry->setLine($credit->getLine());
        $entry->setTotalLine($credit->getTotalLine());
        $entry->setRefId(1234567);

        $em->persist($credit);
        $em->persist($entry);
        $em->flush();

        $output = $this->runCommand('durian:execute-rm-plan');
        $results = explode(PHP_EOL, $output);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 2);
        $rPlan = $emShare->find('BBDurianBundle:RmPlan', 2);

        $this->assertEquals('User 51 has entries this month', $results[0]);
        $this->assertEquals('Plan 2 finished', $results[1]);
        $this->assertEquals('使用者當月有下注記錄', $rpUser->getMemo());
        $this->assertTrue($rpUser->isCancel());
        $this->assertTrue($rPlan->isFinished());
    }

    /**
     * 測試刪除往前30天，之後有體育投注明細的刪除名單，但回傳錯誤
     */
    public function testExecuteRmPlanHasSportEntryFail()
    {
        $container = $this->getContainer();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'no such user',
            'data' => [
                'user_id' => 51,
                'quantity' => 5
            ]
        ];

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(json_encode($ret)));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'no such user';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試刪除往前30天，之後有體育投注明細的刪除名單
     */
    public function testExecuteRmPlanHasSportEntry()
    {
        $container = $this->getContainer();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret1 = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 0
            ]
        ];

        $ret2 = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 5
            ]
        ];

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls(json_encode($ret1), json_encode($ret2)));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $rpUser = $em->find('BBDurianBundle:RmPlanUser', 2);
        $rPlan = $em->find('BBDurianBundle:RmPlan', 2);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 51 has sport entries';
        $this->assertContains($msg, $results[0]);
        $this->assertContains('Plan 2 finished', $results[1]);
        $this->assertTrue($rpUser->isCancel());
        $this->assertTrue($rPlan->isFinished());
    }

    /**
     * 測試刪除使用者，但發curl連線逾時
     */
    public function testExecuteRmPlanButSendCurlTimeout()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $client->expects($this->at(11))
            ->method('send')
            ->will($this->throwException(new ClientException('Operation timed out after 5000 milliseconds with 0 bytes received')));

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 0
            ]
        ];

        $ret = json_encode($ret);
        $msg = 'Operation timed out after 5000 milliseconds with 0 bytes received';

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls($ret, $ret, $msg));

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(408));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute([]);
        } catch (\Exception $e) {
            $this->assertEquals(
                'Operation timed out after 5000 milliseconds with 0 bytes received',
                $e->getMessage()
            );
        }
    }

    /**
     * 測試刪除往前30天，之後有BB體育投注明細的刪除名單，且使用者所在廳為20000007，存在賭場ID對應表中
     */
    public function testExecuteRmPlanHasSportEntryAndUserDomainInCasinoMap()
    {
        $container = $this->getContainer();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $domain = new User();
        $domain->setId(20000007);
        $domain->setDomain(20000007);
        $domain->setUsername('domain20000007');
        $domain->setRole(7);
        $domain->setPassword('123');
        $domain->setAlias('domain20000007');
        $em->persist($domain);

        $user20000500 = new User();
        $user20000500->setId(20000500);
        $user20000500->setDomain(20000007);
        $user20000500->setUsername('u20000500');
        $user20000500->setRole(1);
        $user20000500->setPassword('123');
        $user20000500->setAlias('u20000500');
        $em->persist($user20000500);
        $em->flush();

        $time = new \DateTime('20140101000000');
        $plan = new RmPlan('engineer2', 20000007, 5, null, $time, '測試casino_Map');
        $plan->confirm();
        $plan->queueDone();
        $emShare->persist($plan);

        $rpUser = new RmPlanUser(5, 20000500, 'testCasino', 'testCasino');
        $emShare->persist($rpUser);
        $emShare->flush();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 2
            ]
        ];

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(json_encode($ret)));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg1 = 'User 51 has sport entries';
        $msg2 = 'User 20000500 has sport entries';
        $this->assertContains($msg1, $results[0]);
        $this->assertContains($msg2, $results[1]);
        $this->assertContains('Plan 2 finished', $results[2]);
        $this->assertContains('Plan 5 finished', $results[3]);
    }

    /**
     * 測試刪除往前30天，之後有體育投注明細的刪除名單，但送Curl取得BB體育投注明細超時
     */
    public function testExecuteRmPlanHasSportEntryButSendCurlToRD1TimeoutAtFirstTime()
    {
        $container = $this->getContainer();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $client->expects($this->exactly(1))
            ->method('send')
            ->will($this->throwException(new ClientException('Operation timed out')));

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 1
            ]
        ];

        $ret = json_encode($ret);
        $msg = 'Operation timed out';

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls($msg, $ret));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $application->add($command);

        $rpUser = $em->find('BBDurianBundle:RmPlanUser', 2);
        $rpUser->addTimeoutCount(RmPlanUser::TIMEOUT_THRESHOLD);
        $rPlan = $em->find('BBDurianBundle:RmPlan', 2);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = '[WARNING]Remove user 51 failed, because Operation timed out [] []';
        $this->assertContains($msg, $results[0]);
        $this->assertFalse($rpUser->isCancel());
        $this->assertTrue($rPlan->isFinished());
    }

    /**
     * 測試刪除往前30天，之後有體育投注明細的刪除名單，但送Curl取得體育投注明細超時且回復失敗
     */
    public function testExecuteRmPlanHasSportEntryButSendCurlToRD1TimeoutAtSecondTime()
    {
        $container = $this->getContainer();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $exception = $this->throwException(new ClientException('Operation timed out'));
        $client->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls('', $exception);

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 51,
                'quantity' => 0
            ]
        ];

        $ret = json_encode($ret);
        $msg = 'Operation timed out';

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls($ret, $msg));

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $application = new Application();
        $command = new ExecuteRmPlanCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $rpUser = $em->find('BBDurianBundle:RmPlanUser', 2);
        $rpUser->addTimeoutCount(RmPlanUser::TIMEOUT_THRESHOLD);
        $rPlan = $em->find('BBDurianBundle:RmPlan', 2);

        $command = $application->find('durian:execute-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = '[WARNING]Remove user 51 failed, because Operation timed out [] []';
        $this->assertContains($msg, $results[0]);
        $this->assertFalse($rpUser->isCancel());
        $this->assertTrue($rPlan->isFinished());
    }

    /**
     * 測試以使用者建立時間為條件的刪除計畫使用者，使用者兩個月內有登入紀錄
     */
    public function testExecuteRmPlanWithUserCreatedAtHasLoginRecord()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parent = $em->find('BBDurianBundle:User', 10);
        $user = new User();
        $user->setId(52);
        $user->setUsername('xintest');
        $user->setParent($parent);
        $user->setAlias('xintest');
        $user->setPassword('xintest');
        $user->setDomain(9);
        $user->setCreatedAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setModifiedAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setPasswordExpireAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setLastLogin(new \DateTime('now'));
        $user->setRole(5);
        $em->persist($user);

        $cash = new Cash($user, 156);
        $em->persist($cash);

        $em->flush();

        $userCreatedAt = new \DateTime('2016-04-01 00:00:00');
        $plan = new RmPlan('engineer1', 10, 1, $userCreatedAt, null, '測試1');
        $plan->confirm();
        $plan->queueDone();
        $emShare->persist($plan);

        $plan2 = $emShare->find('BBDurianBundle:RmPlan', 2);
        $plan2->finish();
        $emShare->persist($plan2);

        $rpUser = new RmPlanUser(5, 52, 'xintest', 'test1');
        $emShare->persist($rpUser);

        $emShare->flush();

        $param = [
            '--limit' => 10,
            '--batch-size' => 1
        ];

        $this->runCommand('durian:execute-rm-plan', $param);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('User 52 has login log in last two months', $results[0]);
    }

    /**
     * 測試以使用者建立時間為條件的刪除計畫使用者，使用者有出入款紀錄
     */
    public function testExecuteRmPlanWithUserCreatedAtHasDepositWithdrawRecord()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parent = $em->find('BBDurianBundle:User', 10);
        $user = new User();
        $user->setId(52);
        $user->setUsername('xintest');
        $user->setParent($parent);
        $user->setAlias('xintest');
        $user->setPassword('xintest');
        $user->setDomain(9);
        $user->setCreatedAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setModifiedAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setPasswordExpireAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setRole(5);
        $em->persist($user);

        $cash = new Cash($user, 156);
        $em->persist($cash);

        $at = new \DateTime('now');
        $userHasDepositWithdraw = new UserHasDepositWithdraw($user, $at, null, true, false);
        $em->persist($userHasDepositWithdraw);

        $em->flush();

        $userCreatedAt = new \DateTime('2016-04-01 00:00:00');
        $plan = new RmPlan('engineer1', 10, 1, $userCreatedAt, null, '測試1');
        $plan->confirm();
        $plan->queueDone();
        $emShare->persist($plan);

        $plan2 = $emShare->find('BBDurianBundle:RmPlan', 2);
        $plan2->finish();
        $emShare->persist($plan2);

        $rpUser = new RmPlanUser(5, 52, 'xintest', 'test1');
        $emShare->persist($rpUser);

        $emShare->flush();

        $param = [
            '--limit' => 10,
            '--batch-size' => 1
        ];

        $this->runCommand('durian:execute-rm-plan', $param);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('User 52 has depositWithdraw record', $results[0]);
    }

    /**
     * 測試以使用者建立時間為條件的刪除計畫使用者，使用者有api轉入轉出紀錄
     */
    public function testExecuteRmPlanWithUserCreatedAtHasApiTransferInOutRecord()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parent = $em->find('BBDurianBundle:User', 10);
        $user = new User();
        $user->setId(52);
        $user->setUsername('xintest');
        $user->setParent($parent);
        $user->setAlias('xintest');
        $user->setPassword('xintest');
        $user->setDomain(9);
        $user->setCreatedAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setModifiedAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setPasswordExpireAt(new \DateTime('2016-01-01 00:00:00'));
        $user->setRole(5);
        $em->persist($user);

        $cashFake = new CashFake($user, 156);
        $em->persist($cashFake);

        $at = new \DateTime('now');
        $userHasApiTransferInOut = new UserHasApiTransferInOut(52, true, false);
        $em->persist($userHasApiTransferInOut);

        $em->flush();

        $userCreatedAt = new \DateTime('2016-04-01 00:00:00');
        $plan = new RmPlan('engineer1', 10, 1, $userCreatedAt, null, '測試1');
        $plan->confirm();
        $plan->queueDone();
        $emShare->persist($plan);

        $plan2 = $emShare->find('BBDurianBundle:RmPlan', 2);
        $plan2->finish();
        $emShare->persist($plan2);

        $rpUser = new RmPlanUser(5, 52, 'xintest', 'test1');
        $emShare->persist($rpUser);

        $emShare->flush();

        $param = [
            '--limit' => 10,
            '--batch-size' => 1
        ];

        $this->runCommand('durian:execute-rm-plan', $param);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('User 52 has api transferInOut record', $results[0]);
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
}
