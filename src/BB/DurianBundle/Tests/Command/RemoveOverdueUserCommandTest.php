<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\UserEmail;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Command\RemoveOverdueUserCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;
use Buzz\Exception\ClientException;

class RemoveOverdueUserCommandTest extends WebTestCase
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
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
        ];

        $this->loadFixtures($classnames);

        $this->loadFixtures([], 'entry');
        $this->loadFixtures([], 'his');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData'
        ];
        $this->loadFixtures($classnames, 'share');

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'remove_overdue_user.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試刪除過期使用者，成功發curl送出名單
     */
    public function testRemoveOverdueUserBySendCurl()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $this->createUser();
        $cash = new Cash($user, 156);
        $em->persist($cash);

        $cashFake = new CashFake($user, 156);
        $em->persist($cashFake);

        $credit = new Credit($user, 1);
        $em->persist($credit);
        $em->flush();

        $parameter = [
            '--limit' => 20,
            '--batch-size' => 1,
            '--begin-user-id' => 20000010,
            '--bb-domain' => true
        ];

        //測試刪除，有餘額會直接發curl送名單
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();
        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 20000010,
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
        $command = new RemoveOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('The user 20000010 is ready to be sent', $results[0]);
        $this->assertContains('Success, total users 1 were been sent', $results[1]);
    }

    /**
     * 測試乾跑刪除過期使用者
     */
    public function testDryrunRemoveOverdueUserBySendCurl()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $this->createUser();
        $cash = new Cash($user, 156);
        $em->persist($cash);

        $cashFake = new CashFake($user, 156);
        $em->persist($cashFake);

        $credit = new Credit($user, 1);
        $em->persist($credit);
        $em->flush();

        $parameter = [
            '--limit' => 20,
            '--batch-size' => 1,
            '--begin-user-id' => 20000010,
            '--bb-domain' => true,
            '--dry-run' =>true
        ];

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();
        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 20000010,
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
        $command = new RemoveOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('The user 20000010 is ready to be sent', $results[0]);

        // 確認輸出名單
        $path = $this->getContainer()->get('kernel')->getRootDir() . "/../overdueUserList.csv";
        $contents = file_get_contents($path);
        $lists = explode(PHP_EOL, $contents);

        $this->assertEquals('userId, username, domain ', $lists[0]);
        $this->assertEquals('20000010, bigballtest, test', $lists[1]);
    }

    /**
     * 測試刪除過期使用者，但發curl連線逾時
     */
    public function testRemoveOverdueUserButSendCurlTimeout()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $this->createUser();
        $em->flush();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $client->expects($this->any())
            ->method('send')
            ->will($this->throwException(new ClientException('Operation timed out after 5000 milliseconds with 0 bytes received')));

        $response = new Response();
        $response->addHeader('HTTP/1.1 408 Request Timeout');
        $responseContent = 'Operation timed out after 5000 milliseconds with 0 bytes received';
        $response->setContent($responseContent);

        $application = new Application();
        $command = new RemoveOverdueUserCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-overdue-user');
        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute([]);
        } catch (\Exception $e) {
            $this->assertEquals(
                'Send request failied, StatusCode: 408, ErrorMsg: Operation timed out after 5000 milliseconds with 0 bytes received',
                $e->getMessage()
            );
        }
    }

    /**
     * 測試90天內有現金明細則不會刪除使用者
     */
    public function testRemoveOverdueUserButHasCashEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');

        $now = new \DateTime('now');
        $at = $now->format('YmdHis');
        $user = $this->createUser();
        $cash = new Cash($user, 156);
        $cash->setLastEntryAt($at);
        $em->persist($cash);
        $em->flush();
        $em->clear();

        $cash = $em->getRepository('BBDurianBundle:Cash')->findOneBy(['user' => 20000010]);
        $entry = new CashEntry($cash, 1001, 1000);
        $entry->setId(1918);
        $entry->setRefId(654321);
        $entry->setCreatedAt($now);
        $entry->setAt($now->format('YmdHis'));
        $emEntry->persist($entry);
        $emEntry->flush();

        //測試有現金明細不會直接刪除使用者帳號
        $output = $this->runCommand('durian:remove-overdue-user');
        $results = explode(PHP_EOL, $output);
        $this->assertContains('This user 20000010 has entries during the last 90 days', $results[0]);
        $contents = file_get_contents($this->logPath);
        $this->assertContains('This user 20000010 has entries during the last 90 days', $contents);

        //測試該筆明細被rollback，但交易時間仍有紀錄
        $emEntry->remove($entry);
        $emEntry->flush();

        $output = $this->runCommand('durian:remove-overdue-user');
        $results = explode(PHP_EOL, $output);
        $this->assertContains('This user 20000010 has entries during the last 90 days', $results[0]);
    }

    /**
     * 測試90天內有假現金明細則不會刪除使用者
     */
    public function testRemoveOverdueUserButHasCashFakeEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $this->createUser();
        $cashFake = new CashFake($user, 156);
        $cashFake->setLastEntryAt((new \DateTime('now'))->format('YmdHis'));
        $em->persist($cashFake);
        $em->flush();

        //測試有假現金明細不會直接刪除細使用者帳號
        $output = $this->runCommand('durian:remove-overdue-user');
        $results = explode(PHP_EOL, $output);
        $this->assertContains('This user 20000010 has entries during the last 90 days', $results[0]);
        $contents = file_get_contents($this->logPath);
        $this->assertContains('This user 20000010 has entries during the last 90 days', $contents);
    }

    /**
     * 測試90天內有信用額度明細則不會刪除使用者
     */
    public function testRemoveOverdueUserButHasCreditEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $this->createUser();
        $credit = new Credit($user, 1);
        $credit->setLastEntryAt((new \DateTime('now'))->format('YmdHis'));
        $em->persist($credit);
        $em->flush();

        //測試有信用額度明細不會直接刪除使用者帳號
        $output = $this->runCommand('durian:remove-overdue-user');
        $results = explode(PHP_EOL, $output);
        $this->assertContains('This user 20000010 has entries during the last 90 days', $results[0]);
        $contents = file_get_contents($this->logPath);
        $this->assertContains('This user 20000010 has entries during the last 90 days', $contents);
    }

    /**
     * 測試有未來信用額度明細則不會刪除使用者
     */
    public function testRemoveOverdueUserButHasFutureCreditEntry()
    {
        $this->createUserWithoutDomainInCasinoMap();

        $container = $this->getContainer();
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();
        $ret1 = [
            'message' => 'ok',
            'data' => [
                'user_id' => 20000012,
                'quantity' => 0
            ]
        ];
        $ret2 = [
            'message' => 'ok',
            'data' => [
                'user_id' => 20000012,
                'quantity' => 5
            ]
        ];
        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls(json_encode($ret1), json_encode($ret2)));

        $parameter = [
            '--limit' => 20,
            '--batch-size' => 1,
            '--begin-user-id' => 20000012,
            '--bb-domain' => true
        ];

        $application = new Application();
        $command = new RemoveOverdueUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('This user 20000012 has entries in future', $results[0]);
    }

    /**
     * 測試取得未來信用額度明細超時
     */
    public function testRemoveOverdueUserButSendCurlToRD1TimeoutAtFirstTime()
    {
        $this->createUserWithoutDomainInCasinoMap();

        $container = $this->getContainer();
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
                'user_id' => 20000012,
                'quantity' => 1
            ]
        ];
        $ret = json_encode($ret);
        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls('Operation timed out', $ret));

        $parameter = [
            '--limit' => 20,
            '--batch-size' => 1,
            '--begin-user-id' => 20000012,
            '--bb-domain' => true
        ];

        $application = new Application();
        $command = new RemoveOverdueUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = '[WARNING]Sending request to check future entries failed, because Operation timed out [] []';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試取得未來信用額度明細超時且回復失敗
     */
    public function testRemoveOverdueUserButSendCurlToRD1TimeoutAtSecondTime()
    {
        $this->createUserWithoutDomainInCasinoMap();

        $container = $this->getContainer();
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
                'user_id' => 20000012,
                'quantity' => 0
            ]
        ];
        $ret = json_encode($ret);
        $response->expects($this->any())
            ->method('getContent')
            ->will($this->onConsecutiveCalls($ret, 'Operation timed out'));
        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $parameter = [
            '--limit' => 20,
            '--batch-size' => 1,
            '--begin-user-id' => 20000012,
            '--bb-domain' => true
        ];

        $application = new Application();
        $command = new RemoveOverdueUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = '[WARNING]Sending request to check future entries failed, because Operation timed out [] []';
        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試有未來信用額度明細，且使用者所在廳為20000007，存在賭場ID對應表中
     */
    public function testRemoveOverdueUserButHasFutureCreditEntryAndUserDomainInCasinoMap()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $this->createUser();
        $em->flush();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();
        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 20000010,
                'quantity' => 2
            ]
        ];
        $response->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(json_encode($ret)));

        $parameter = [
            '--limit' => 20,
            '--batch-size' => 1,
            '--begin-user-id' => 20000010,
            '--bb-domain' => true
        ];

        $application = new Application();
        $command = new RemoveOverdueUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('This user 20000010 has entries in future', $results[0]);
    }

    /**
     * 測試取得未來信用額度明細，但回傳錯誤
     */
    public function testRemoveOverdueUserButHasFutureCreditEntryFail()
    {
        $this->createUserWithoutDomainInCasinoMap();

        $container = $this->getContainer();
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();
        $ret = [
            'message' => 'no such user',
            'data' => [
                'user_id' => 20000012,
                'quantity' => 5
            ]
        ];
        $response->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(json_encode($ret)));

        $parameter = [
            '--bb-domain' => true
        ];

        $application = new Application();
        $command = new RemoveOverdueUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:remove-overdue-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute($parameter);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains('no such user', $results[0]);
    }

    /**
     * 測試有下層則不會被丟入刪除名單
     */
    public function testRemoveOverdueUserButHasSon()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parent = $em->find('BBDurianBundle:User', 7);
        $parent->disable();
        $parent->setModifiedAt(new \DateTime('2013-1-1 11:11:11'));
        $em->flush();

        $output = $this->runCommand('durian:remove-overdue-user', ['--begin-user-id' => 7]);
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('Remove failed, this user 7 still has son', $results[0]);
        $contents = file_get_contents($this->logPath);
        $this->assertContains('Remove failed, this user 7 still has son', $contents);
    }

    /**
     * 新增測試使用者
     *
     * @return User
     */
    private function createUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $domain = new User();
        $domain->setId(20000007);
        $domain->setUsername('testdomain');
        $domain->setAlias('testdomain');
        $domain->setPassword('testdomain');
        $domain->setDomain(20000007);
        $domain->setRole(7);
        $em->persist($domain);

        $domainDetail = new UserDetail($domain);
        $domainEmail = new UserEmail($domain);
        $domainEmail->setEmail('');
        $em->persist($domainDetail);
        $em->persist($domainEmail);
        $em->flush();

        $domain = $em->find('BBDurianBundle:User', 20000007);
        $config = new DomainConfig($domain, 'test', 'qoo');
        $emShare->persist($config);

        // 停用超過90天
        $date = new \DateTime('now');
        $date->sub(new \DateInterval('P91D'));

        $user = new User();
        $user->setId(20000010);
        $user->setUsername('bigballtest');
        $user->setParent($domain);
        $user->setAlias('bigballtest');
        $user->setPassword('123');
        $user->setDomain(20000007);
        $user->setRole(5);
        $user->disable();
        $user->setModifiedAt($date);
        $em->persist($user);

        $detail = new UserDetail($user);
        $email = new UserEmail($user);
        $email->setEmail('');
        $em->persist($detail);
        $em->persist($email);

        $em->flush();
        $emShare->flush();

        return $user;
    }

    /**
     * 新增所在廳不存在賭場ID對應表中的測試使用者
     */
    private function createUserWithoutDomainInCasinoMap()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $domain = new User();
        $domain->setId(20000011);
        $domain->setUsername('testdomain');
        $domain->setAlias('testdomain');
        $domain->setPassword('testdomain');
        $domain->setDomain(20000011);
        $domain->setRole(7);
        $em->persist($domain);

        $domainDetail = new UserDetail($domain);
        $domainEmail = new UserEmail($domain);
        $domainEmail->setEmail('');
        $em->persist($domainDetail);
        $em->persist($domainEmail);

        $config = new DomainConfig($domain, 'test2', 'lbj');
        $emShare->persist($config);

        // 停用超過90天
        $date = new \DateTime('now');
        $date->sub(new \DateInterval('P91D'));

        $user = new User();
        $user->setId(20000012);
        $user->setUsername('bigballtest');
        $user->setParent($domain);
        $user->setAlias('bigballtest');
        $user->setPassword('123');
        $user->setDomain(20000011);
        $user->setRole(5);
        $user->disable();
        $user->setModifiedAt($date);
        $em->persist($user);

        $detail = new UserDetail($user);
        $email = new UserEmail($user);
        $email->setEmail('');
        $em->persist($detail);
        $em->persist($email);

        $em->flush();
        $emShare->flush();
    }

    /**
     * 刪除產生的log檔
     */
    public function tearDown() {
        parent::tearDown();

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        $path = $this->getContainer()->get('kernel')->getRootDir() . "/../overdueUserList.csv";

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
