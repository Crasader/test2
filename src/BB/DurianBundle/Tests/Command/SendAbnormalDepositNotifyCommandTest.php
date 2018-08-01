<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\SendAbnormalDepositNotifyCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;

class SendAbnormalDepositNotifyCommandTest extends WebTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Predis\Client
     */
    private $redis;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAbnormalDepositNotifyEmailData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $this->redis = $this->getContainer()->get('snc_redis.default_client');

        $param = [
            'domain' => '2',
            'confirm_at' => '2016-08-22T12:00:00+0800',
            'user_name' => 'test',
            'opcode' => '1010',
            'operator' => 'operator',
            'amount' => '1000000',
        ];

        $this->redis->rpush('abnormal_deposit_notify_queue', json_encode($param));

        $domainParam = [
            'domain' => '2',
            'at' => '20160822',
        ];

        $this->redis->rpush('domain_abnormal_deposit_notify_queue', json_encode($domainParam));

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $loggerManager = $this->getContainer()->get('durian.logger_manager');
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['durian.logger_manager', 1, $loggerManager],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
            ['snc_redis.default_client', 1, $this->redis],
            ['durian.monitor.background', 1, $bgMonitor],
        ];

        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $this->container->expects($this->any())
            ->method('getParameter')
            ->willReturn('127.0.0.1');

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試寄送異常入款提醒, 但Email Server連線失敗
     */
    public function testSendButEmailServerConnectionFailure()
    {
        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $command = new SendAbnormalDepositNotifyCommand();
        $command->setContainer($this->container);
        $command->setClient($this->client);
        $command->setResponse($response);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '{"result":"error","code":150370059,"message":"Email Server connection failure"}';
        $this->assertContains($msg, $results[0]);
        $this->assertContains($msg, $results[1]);

        // 檢查異常時是否推回redis
        $queue = json_decode($this->redis->rpop('abnormal_deposit_notify_queue'), true);

        $this->assertEquals('2', $queue['domain']);
        $this->assertEquals('2016-08-22T12:00:00+0800', $queue['confirm_at']);
        $this->assertEquals('test', $queue['user_name']);
        $this->assertEquals('1010', $queue['opcode']);
        $this->assertEquals('operator', $queue['operator']);
        $this->assertEquals('1000000', $queue['amount']);

        $domainQueue = json_decode($this->redis->rpop('domain_abnormal_deposit_notify_queue'), true);

        $this->assertEquals('2', $domainQueue['domain']);
        $this->assertEquals('20160822', $domainQueue['at']);
    }

    /**
     * 測試寄送異常入款提醒, 但Email寄送失敗
     */
    public function testSendButSendEmailFailure()
    {
        $response = new Response();
        $response->setContent('{"MailSended":"failure"}');
        $response->addHeader('HTTP/1.1 200 OK');

        $command = new SendAbnormalDepositNotifyCommand();
        $command->setContainer($this->container);
        $command->setClient($this->client);
        $command->setResponse($response);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '{"result":"error","code":150370060,"message":"Send Email failure"}';
        $this->assertContains($msg, $results[0]);
        $this->assertContains($msg, $results[1]);

        // 檢查異常時是否推回redis
        $queue = json_decode($this->redis->rpop('abnormal_deposit_notify_queue'), true);

        $this->assertEquals('2', $queue['domain']);
        $this->assertEquals('2016-08-22T12:00:00+0800', $queue['confirm_at']);
        $this->assertEquals('test', $queue['user_name']);
        $this->assertEquals('1010', $queue['opcode']);
        $this->assertEquals('operator', $queue['operator']);
        $this->assertEquals('1000000', $queue['amount']);

        $domainQueue = json_decode($this->redis->rpop('domain_abnormal_deposit_notify_queue'), true);

        $this->assertEquals('2', $domainQueue['domain']);
        $this->assertEquals('20160822', $domainQueue['at']);
    }

    /**
     * 測試寄送異常入款提醒
     */
    public function testSend()
    {
        $response = new Response();
        $response->setContent('{"MailSended":"success"}');
        $response->addHeader('HTTP/1.1 200 OK');

        $command = new SendAbnormalDepositNotifyCommand();
        $command->setContainer($this->container);
        $command->setClient($this->client);
        $command->setResponse($response);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertContains('2 email sent.', $results[0]);
    }
}
