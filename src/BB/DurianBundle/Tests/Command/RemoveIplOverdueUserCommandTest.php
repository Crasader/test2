<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Command\RemoveIplOverdueUserCommand;
use Buzz\Message\Response;
use Buzz\Exception\ClientException;

class RemoveIplOverdueUserCommandTest extends WebTestCase
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
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
        $redis->set('cashfake_seq', 1000);
        $redis->set('user_seq', 20000000);

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'remove_ipl_overdue_user.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試背景隨時間自動中斷功能
     */
    public function testCommandAutoExitByTime()
    {
        $exitTime = new \DateTime('now');
        $exitTime->setTime(12, 30);

        $runTime = new \DateTime('now');
        $runTime->setTime(13, 10);

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Wednesday', $exitTime, $runTime);
        $application->add($command);

        $parameter = ['--auto-interrupt' => true];

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('Command auto exit at', $results[0]);
    }

    /**
     * 測試刪除時沒有需要刪除的帳號
     */
    public function testRemoveUserButNotFoundDeleteUser()
    {
        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Saturday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    /**
     * 測試刪除最後登入時間在指定時間之前的會員帳號
     */
    public function testRemoveUserLastLoginBeforeTime()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.wallet2');
        $user = $em->find('BBDurianBundle:User', 8);
        $time = new \DateTime('2014-12-31 11:59:59');
        $user->setLastLogin($time);

        $user1 = $em->find('BBDurianBundle:User', 51);
        $user1->setLastLogin($time);
        $em->flush();

        $redis->hset('cash_balance_8_901', 'balance', 10000);

        $parameter = [
            '--limit' => 10,
            '--last-login-time' => '2015/01/01 00:00:00'
        ];

        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Saturday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The user 8 is ready to be sent', $results[0]);
        $this->assertContains('The user 51 is ready to be sent', $results[1]);
        $this->assertContains('Success, total users 2 were been sent', $results[2]);
    }

    /**
     * 測試刪除特定廳最後登入時間在指定時間之前的會員帳號
     */
    public function testRemoveSpecificDomainAndUserLastLoginBeforeTime()
    {
        $this->createUserHierarchy();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 20000005);
        $time = new \DateTime('2014-12-31 11:59:59');
        $user->setLastLogin($time);
        $em->flush();

        $parameter = [
            '--limit' => 10,
            '--last-login-time' => '2015/01/01 00:00:00',
            '--domain' => 52
        ];

        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Monday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The user 20000005 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 測試刪除帳號，但發curl連線逾時
     */
    public function testRemoveButSendCurlTimeout()
    {
        $this->createUserHierarchy();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 20000005);
        $time = new \DateTime('2014-12-31 11:59:59');
        $user->setLastLogin($time);
        $em->flush();

        $parameter = [
            '--limit' => 10,
            '--last-login-time' => '2015/01/01 00:00:00',
            '--domain' => 52
        ];

        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $errMsg = 'Operation timed out after 5000 milliseconds with 0 bytes received';
        $client->expects($this->any())
            ->method('send')
            ->will($this->throwException(new ClientException($errMsg)));

        $response = new Response();
        $response->addHeader('HTTP/1.1 408 Request Timeout');
        $response->setContent($errMsg);

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Monday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute($parameter);
        } catch (\Exception $e) {
            $this->assertEquals(
                'Send request failed, StatusCode: 408, ErrorMsg: ' . $errMsg,
                $e->getMessage()
            );
        }

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The user 20000005 is ready to be sent', $results[0]);
        $this->assertContains(
            'Send request failed, because ' . $errMsg,
            $results[1]
        );
    }

    /**
     * 測試刪除最後登入時間在指定時間之前的會員帳號且餘額為0
     */
    public function testRemoveUserLastLoginBeforeTimeAndBalanceIsZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $time = new \DateTime('2014-12-31 11:59:59');

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);

        $cash = $user->getCash();
        $cash->setBalance(0);

        $user1 = $em->find('BBDurianBundle:User', 51);
        $user1->setLastLogin($time);

        $cash1 = $user1->getCash();
        $cash1->setBalance(1);
        $em->flush();

        $parameter = [
            '--last-login-time' => '2015/01/01 00:00:00',
            '--cash-balance-zero' => true
        ];

        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        //使用者8最後登入時間在限制條件之前且餘額為0，所以會刪除
        //使用者51最後登入時間在限制條件之前但餘額非為0，所以不會刪除
        $this->assertContains('The user 8 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 測試刪除指定廳且最後登入時間在指定時間之前的會員帳號且餘額為0
     */
    public function testRemoveUserSpecificDomainAndLastLoginBeforeTimeAndBalanceIsZero()
    {
        $this->createUserHierarchy();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $time = new \DateTime('2014-12-31 11:59:59');

        $user = $em->find('BBDurianBundle:User', 20000005);
        $user->setLastLogin($time);

        $user1 = $em->find('BBDurianBundle:User', 20000006);
        $user1->setLastLogin($time);
        $cash = $user1->getCash();
        $cash->setBalance(1);
        $em->flush();

        $parameter = [
            '--limit' => 10,
            '--last-login-time' => '2015/01/01 00:00:00',
            '--cash-balance-zero' => true,
            '--domain' => 52
        ];

        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The user 20000005 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 測試刪除在指定時間之前從未登入的會員帳號
     */
    public function testRemoveUserNeverLoginBeforeTime()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $time = new \DateTime('2014-12-31 11:59:59');

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCreatedAt($time);

        $user1 = $em->find('BBDurianBundle:User', 51);
        $user1->setCreatedAt($time);
        $em->flush();

        $parameter = ['--never-login-time' => '2015/01/01 00:00:00'];
        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The user 8 is ready to be sent', $results[0]);
        $this->assertContains('The user 51 is ready to be sent', $results[1]);
        $this->assertContains('Success, total users 2 were been sent', $results[2]);
    }

    /**
     * 測試刪除指定廳在指定時間之前從未登入的會員帳號
     */
    public function testRemoveSpecificDomainUserNeverLoginBeforeTime()
    {
        $this->createUserHierarchy();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 20000005);
        $time = new \DateTime('2014-12-31 11:59:59');
        $user->setCreatedAt($time);
        $em->flush();

        $parameter = [
            '--never-login-time' => '2015/01/01 00:00:00',
            '--domain' => 52
        ];
        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The user 20000005 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 測試刪除最後登入時間在指定時間之前的且餘額為0會員帳號
     */
    public function testRemoveUserNeverLoginBeforeTimeAndBalanceIsZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $time = new \DateTime('2014-12-31 11:59:59');
        $user->setCreatedAt($time);

        $cash = $user->getCash();
        $cash->setBalance(0);

        $user1 = $em->find('BBDurianBundle:User', 51);
        $user1->setCreatedAt($time);

        $cash1 = $user1->getCash();
        $cash1->setBalance(1);
        $em->flush();

        $parameter = [
            '--never-login-time' => '2015/01/01 00:00:00',
            '--cash-balance-zero' => true
        ];
        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        //使用者8在限制條件之前從未登入且餘額為0，所以會刪除
        //使用者51在限制條件之從未登入前但餘額非為0，所以不會刪除
        $this->assertContains('The user 8 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 測試刪除指定廳在指定時間之前從未登入且餘額為0的會員帳號
     */
    public function testRemoveUserSpecificDomainNeverLoginBeforeTimeAndBalanceIsZero()
    {
        $this->createUserHierarchy();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $time = new \DateTime('2014-12-31 11:59:59');

        $user = $em->find('BBDurianBundle:User', 20000005);
        $user->setCreatedAt($time);

        $user1 = $em->find('BBDurianBundle:User', 20000006);
        $user1->setCreatedAt($time);

        $cash = $user1->getCash();
        $cash->setBalance(1);
        $em->flush();

        $parameter = [
            '--never-login-time' => '2015/01/01 00:00:00',
            '--cash-balance-zero' => true,
            '--domain' => 52
        ];
        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The user 20000005 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 測試刪除停用廳底下會員帳號
     */
    public function testRemoveDisableDomain()
    {
        $this->createUserHierarchy();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $domain = $em->find('BBDurianBundle:User', 52);
        $domain->disable();
        $date = new \DateTime('2015-01-01');
        $domain->setModifiedAt($date);

        $user = $em->find('BBDurianBundle:User', 20000006);
        $cash = $user->getCash();
        $cash->setBalance(1);
        $em->flush();

        $parameter = [
            '--disable-domain' => true,
            '--begin-id' => 1,
            '--begin-role' => 1,
            '--begin-domain' => 1
        ];
        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('Domain: 52 : role 1 user 20000005 is ready to be sent', $results[0]);
        $this->assertContains('Domain: 52 : role 1 user 20000006 is ready to be sent', $results[1]);
        $this->assertContains('Domain: 52 : role 1 user 20000007 is ready to be sent', $results[2]);
        $this->assertContains('Success, total users 3 were been sent', $results[3]);
        $this->assertEquals('', $results[4]);

        // 一層一層刪到廳主為止
        $user = $em->find('BBDurianBundle:User', 20000005);
        $em->remove($user);
        $user = $em->find('BBDurianBundle:User', 20000006);
        $em->remove($user);
        $user = $em->find('BBDurianBundle:User', 20000007);
        $em->remove($user);
        $em->flush();

        // 每刪完一層 會檢查上層 size 是否歸零
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('There are some user with not zero size in role 2', $results[4]);

        $user = $em->find('BBDurianBundle:User', 20000004);
        $user->subSize();
        $user->subSize();
        $user->subSize();
        $em->flush();

        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('Domain: 52 : role 2 user 20000004 is ready to be sent', $results[5]);
        $this->assertContains('Success, total users 1 were been sent', $results[6]);
        $this->assertEquals('', $results[7]);

        $user = $em->find('BBDurianBundle:User', 20000004);
        $em->remove($user);
        $user = $em->find('BBDurianBundle:User', 20000003);
        $user->subSize();
        $em->flush();

        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('Domain: 52 : role 3 user 20000003 is ready to be sent', $results[7]);
        $this->assertContains('Success, total users 1 were been sent', $results[8]);
        $this->assertEquals('', $results[9]);

        $user = $em->find('BBDurianBundle:User', 20000003);
        $em->remove($user);
        $user = $em->find('BBDurianBundle:User', 20000002);
        $user->subSize();
        $em->flush();

        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('Domain: 52 : role 4 user 20000002 is ready to be sent', $results[9]);
        $this->assertContains('Success, total users 1 were been sent', $results[10]);
        $this->assertEquals('', $results[11]);

        $user = $em->find('BBDurianBundle:User', 20000002);
        $em->remove($user);
        $user = $em->find('BBDurianBundle:User', 20000001);
        $user->subSize();
        $em->flush();

        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('Domain: 52 : role 5 user 20000001 is ready to be sent', $results[11]);
        $this->assertContains('Success, total users 1 were been sent', $results[12]);
        $this->assertEquals('', $results[13]);

        $user = $em->find('BBDurianBundle:User', 20000001);
        $em->remove($user);
        $em->flush();

        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The size of domain 52 is not zero', $results[13]);

        $user = $em->find('BBDurianBundle:User', 52);
        $user->subSize();
        $em->flush();

        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('Domain: 52 : role 7 user 52 is ready to be sent', $results[14]);
        $this->assertContains('Success, total users 1 were been sent', $results[15]);
        $this->assertEquals('', $results[16]);
    }

    /**
     * 測試刪除停用廳底下會員帳號且餘額為0
     */
    public function testRemoveDisableDomainAndBalanceIsZero()
    {
        $this->createUserHierarchy();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $domain = $em->find('BBDurianBundle:User', 52);
        $domain->disable();
        $date = new \DateTime('2015-01-01');
        $domain->setModifiedAt($date);

        $user = $em->find('BBDurianBundle:User', 20000006);
        $cash = $user->getCash();
        $cash->setBalance(1);
        $em->flush();

        $parameter = [
            '--disable-domain' => true,
            '--cash-balance-zero' => true
        ];
        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        //會員20000006額度非為0，所以不會刪除
        $this->assertContains('The user 20000005 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 測試刪除負數假現金使用者
     */
    public function testRemoveUserOfNegativeCashFake()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createUserHierarchy();

        $user = $em->find('BBDurianBundle:User', 20000007);
        $date = new \DateTime('2015-01-01 00:00:00');
        $user->setLastLogin($date);
        $cashFake = $user->getCashFake();
        $cashFake->setBalance(-1);
        $em->flush();
        $em->clear();

        $parameter = [
            '--last-login-time' => '2016/01/01 00:00:00',
            '--domain' => 52
        ];
        $exitTime = new \DateTime('now');
        $exitTime->setTime(5, 15);
        $runTime = new \DateTime('now');
        $runTime->setTime(5, 10);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'message' => 'job created',
            'id' => 123
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new RemoveIplOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setTime('Friday', $exitTime, $runTime);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-ipl-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('The user 20000007 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 建立使用者
     */
    public function createUserHierarchy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'role' => 7,
            'login_code' => 'bab',
            'username' => 'testremove7',
            'password' => 'testremove7',
            'alias' => 'testremove7',
            'name' => 'testremove7',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameter = [
            'domain' => 52,
            'alias' => '未分層',
            'order_strategy' => 0,
            'created_at_start' => '2015-10-13 00:00:00',
            'created_at_end' => '2015-10-13 00:00:00',
            'deposit_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 1000,
            'withdraw_count' => 0,
            'withdraw_total' => 0
        ];

        $client->request('POST', '/api/level', $parameter);

        $parameters = ['level_id' => 1];
        $client->request('POST', '/api/user/52/preset_level', $parameters);

        $parameters = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'testremove5',
            'password' => 'testremove5',
            'alias' => 'testremove5',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'testremove4',
            'password' => 'testremove4',
            'alias' => 'testremove4',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000002,
            'role' => 3,
            'username' => 'testremove3',
            'password' => 'testremove3',
            'alias' => 'testremove3',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000003,
            'role' => 2,
            'username' => 'testremove2',
            'password' => 'testremove2',
            'alias' => 'testremove2',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'testremove1',
            'password' => 'testremove1',
            'alias' => 'testremove1',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'testremove11',
            'password' => 'testremove11',
            'alias' => 'testremove11',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'testremove12',
            'password' => 'testremove12',
            'alias' => 'testremove12',
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $this->runCommand('durian:update-user-size');
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
