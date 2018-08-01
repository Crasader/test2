<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Command\GenerateRmPlanCommand;
use BB\DurianBundle\Entity\RmPlan;

class GenerateRmPlanCommandTest extends WebTestCase
{
    /**
     * log檔的路徑
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData'
        ];

        $this->loadFixtures($classnames);

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'generate_rm_plan.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test'. DIRECTORY_SEPARATOR .$fileName;
    }

    /**
     * 測試建立刪除計畫
     */
    public function testGenerateRmPlan()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $now = new \DateTime('now');
        $createdAt = $now->format('Y-m-d H:i:s');
        $userCreatedAt = $now->sub(new \DateInterval('P60D'))->format('Y-m-d 00:00:00');

        $time = new \DateTime('2015-11-01 00:00:00');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user2 = $em->find('BBDurianBundle:User', 2);
        $user2->setCreatedAt($time);
        $em->persist($user2);

        $user3 = $em->find('BBDurianBundle:User', 3);
        $user3->setCreatedAt($time);
        $em->persist($user3);

        $user3payway = $em->find('BBDurianBundle:UserPayway', 3);
        $user3payway->enableCashFake();
        $em->persist($user3payway);

        $user9 = $em->find('BBDurianBundle:User', 9);
        $user9->setCreatedAt($time);
        $em->persist($user9);

        $em->flush();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'result' => 'ok',
            'ret' => [
                'id' => 5,
                'creator' => 'RD5',
                'parent_id' => 3,
                'depth' => 5,
                'user_created_at' => $userCreatedAt,
                'last_login' => null,
                'created_at' => $createdAt,
                'modified_at' => null,
                'finish_at' => null,
                'untreated' => null,
                'user_created' => false,
                'confirm' => false,
                'cancel' => false,
                'finished' => false,
                'title' => '刪除現金會員過期帳號',
                'memo' => '',
                'level' => []
            ]
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new GenerateRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:generate-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains(
            '"result":"ok","ret":{"id":5,"creator":"RD5","parent_id":3,"depth":5,"',
            $results[0]
        );

        $command = $application->find('durian:generate-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--cash' => true
            ]
        );

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains(
            '"result":"ok","ret":{"id":5,"creator":"RD5","parent_id":3,"depth":5,"',
            $results[1]
        );

        $command = $application->find('durian:generate-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--cash-fake' => true
            ]
        );

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains(
            '"result":"ok","ret":{"id":5,"creator":"RD5","parent_id":3,"depth":5,"',
            $results[2]
        );
    }

    /**
     * 測試帶入domain，user_createdAt參數建立刪除計畫
     */
    public function testGenerateRmPlanWithDomainAndUserCreatedAt()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $now = new \DateTime('now');
        $createdAt = $now->format('Y-m-d H:i:s');

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'result' => 'ok',
            'ret' => [
                'id' => 5,
                'creator' => 'testCreator',
                'parent_id' => 2,
                'depth' => 5,
                'user_created_at' => '2016-01-01 00:00:00',
                'last_login' => null,
                'created_at' => $createdAt,
                'modified_at' => null,
                'finish_at' => null,
                'untreated' => null,
                'user_created' => false,
                'confirm' => false,
                'cancel' => false,
                'finished' => false,
                'title' => 'testTitle',
                'memo' => '',
                'level' => []
            ]
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new GenerateRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:generate-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--domain' => 2,
                '--user-createdAt' => '2016-06-01 00:00:00',
                '--title' => 'testTitle',
                '--creator' => 'testCreator'
            ]
        );

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains(
            '"result":"ok","ret":{"id":5,"creator":"testCreator","parent_id":2,"depth":5,"',
            $results[0]
        );
    }

    /**
     * 測試建立刪除計畫發生錯誤
     */
    public function testGenerateRmPlanError()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'result' => 'error',
            'code' => 150630015,
            'msg' => 'Cannot create plan when untreated plan exists'
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new GenerateRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:generate-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--domain' => 2
            ]
        );

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains(
            '"result":"error","code":150630015,"msg":"Cannot create plan when untreated plan exists","parameters":"parent_id:2',
            $results[0]
        );
    }

    /**
     * 測試建立刪除計畫，但curl失敗
     */
    public function testGenerateRmPlanButCurlFailed()
    {
        $this->runCommand('durian:generate-rm-plan', ['--domain' => 2]);

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains(
            '"result":"error","code":3,"message":"<url> malformed"',
            $results[0]
        );
    }

    /**
     * 測試確認刪除計畫
     */
    public function testConfirmRmPlan()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $time = new \DateTime('20170101000000');
        $plan = new RmPlan('BBIN', 3, 5, $time, null, 'BBIN 例行刪除測試');
        $plan->userCreated();
        $em->persist($plan);
        $em->flush();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'result' => 'ok',
            'ret' => [
                'id' => 5,
                'creator' => 'BBIN',
                'parent_id' => 2,
                'depth' => 5,
                'user_created_at' => '2016-01-01 00:00:00',
                'last_login' => null,
                'created_at' => '2017-01-01 00:00:00',
                'modified_at' => '2017-01-01 00:00:00',
                'finish_at' => '2017-01-14 00:00:00',
                'untreated' => false,
                'user_created' => true,
                'confirm' => true,
                'cancel' => false,
                'finished' => false,
                'title' => 'BBIN 例行刪除',
                'memo' => '',
                'level' => []
            ]
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new GenerateRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:generate-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--plan-confirm' => true
            ]
        );

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains(
            'plan id: 5 confirm success',
            $results[0]
        );
    }

    /**
     * 測試確認刪除計畫失敗
     */
    public function testConfirmRmPlanFail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $time = new \DateTime('20170101000000');
        $plan = new RmPlan('BBIN', 3, 5, $time, null, 'BBIN 例行刪除測試');
        $plan->userCreated();
        $em->persist($plan);
        $em->flush();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 500');
        $responseContent = [
            'result' => 'error',
            'ret' => [
                'msg' => 'Mysql Connection Timeout'
            ]
        ];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new GenerateRmPlanCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:generate-rm-plan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--plan-confirm' => true
            ]
        );

        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $this->assertContains(
            'plan id: 5 confirm failed',
            $results[0]
        );
        $this->assertContains(
            'Mysql Connection Timeout',
            $results[0]
        );
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
