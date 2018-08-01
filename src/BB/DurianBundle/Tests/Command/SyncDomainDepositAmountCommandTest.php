<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\SyncDomainDepositAmountCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncDomainDepositAmountCommandTest extends WebTestCase
{
    /**
     * @var \Predis\Client
     */
    private $redis;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatDomainDepositAmountData',
        ];

        $this->loadFixtures($classnames);

        $this->redis = $this->getContainer()->get('snc_redis.default_client');
    }

    /**
     * 測試同步廳的當日入款總金額, 發生timeout
     */
    public function testSyncWithConnectionTimeout()
    {
        $this->redis = $this->getContainer()->get('snc_redis.default_client');
        $loggerManager = $this->getContainer()->get('durian.logger_manager');
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');

        $deposit = [
            'domain' => 6,
            'confirm_at' => '2016-08-08T12:00:00+0800',
            'amount' => '100',
        ];
        $this->redis->rpush('stat_domain_deposit_queue', json_encode($deposit));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'persist', 'flush'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $mockRepo = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['snc_redis.default_client', 1, $this->redis],
            ['doctrine.orm.entity_manager', 1, $mockEm],
            ['durian.logger_manager', 1, $loggerManager],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $command = new SyncDomainDepositAmountCommand();
        $command->setContainer($mockContainer);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $results = explode(PHP_EOL, $commandTester->getDisplay());
        $this->assertContains('Connection timed out', $results[0]);

        // 檢查異常時是否推回redis
        $queue = json_decode($this->redis->rpop('stat_domain_deposit_queue'), true);

        $this->assertEquals('6', $queue['domain']);
        $this->assertEquals('2016-08-08T12:00:00+0800', $queue['confirm_at']);
        $this->assertEquals('100', $queue['amount']);
    }

    /**
     * 測試同步廳的當日入款總金額, 沒有當日的統計資料, 金額未達寄送門檻
     */
    public function testSyncWithNoStatNoSend()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $deposit = [
            'domain' => 2,
            'confirm_at' => '2016-08-08T12:00:00+0800',
            'amount' => '100',
        ];
        $this->redis->rpush('stat_domain_deposit_queue', json_encode($deposit));

        $output = $this->runCommand('durian:sync-domain-deposit-amount');

        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('1 queue processed.', $results[0]);

        // 檢查統計資料
        $criteria = [
            'domain' => 2,
            'at' => '20160808',
        ];
        $stat = $em->getRepository('BBDurianBundle:StatDomainDepositAmount')->findOneBy($criteria);

        $this->assertEquals('2', $stat->getId());
        $this->assertEquals('2', $stat->getDomain());
        $this->assertEquals('20160808', $stat->getAt());
        $this->assertEquals('100', $stat->getAmount());

        // 檢查沒有推到寄送異常入款queue中
        $domainQueue = json_decode($this->redis->rpop('domain_abnormal_deposit_notify_queue'), true);

        $this->assertNull($domainQueue);
    }

    /**
     * 測試同步廳的當日入款總金額, 沒有當日的統計資料, 金額達寄送門檻
     */
    public function testSyncWithNoStat()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $deposit = [
            'domain' => 2,
            'confirm_at' => '2016-08-08T12:00:00+0800',
            'amount' => '5000000',
        ];
        $this->redis->rpush('stat_domain_deposit_queue', json_encode($deposit));

        $output = $this->runCommand('durian:sync-domain-deposit-amount');

        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('1 queue processed.', $results[0]);

        // 檢查統計資料
        $criteria = [
            'domain' => 2,
            'at' => '20160808',
        ];
        $stat = $em->getRepository('BBDurianBundle:StatDomainDepositAmount')->findOneBy($criteria);

        $this->assertEquals('2', $stat->getId());
        $this->assertEquals('2', $stat->getDomain());
        $this->assertEquals('20160808', $stat->getAt());
        $this->assertEquals('5000000', $stat->getAmount());

        // 檢查推到寄送異常入款queue中
        $domainQueue = json_decode($this->redis->rpop('domain_abnormal_deposit_notify_queue'), true);

        $this->assertEquals('2', $domainQueue['domain']);
        $this->assertEquals('20160808', $domainQueue['at']);
    }

    /**
     * 測試同步廳的當日入款總金額, 有當日的統計資料, 金額未達寄送門檻
     */
    public function testSyncWithNoSend()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $deposit = [
            'domain' => 6,
            'confirm_at' => '2016-08-08T12:00:00+0800',
            'amount' => '200000',
        ];
        $this->redis->rpush('stat_domain_deposit_queue', json_encode($deposit));

        $output = $this->runCommand('durian:sync-domain-deposit-amount');

        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('1 queue processed.', $results[0]);

        // 檢查統計資料
        $criteria = [
            'domain' => 6,
            'at' => '20160808',
        ];
        $stat = $em->getRepository('BBDurianBundle:StatDomainDepositAmount')->findOneBy($criteria);

        $this->assertEquals('1', $stat->getId());
        $this->assertEquals('6', $stat->getDomain());
        $this->assertEquals('20160808', $stat->getAt());
        $this->assertEquals('700000', $stat->getAmount());

        // 檢查沒有推到寄送異常入款queue中
        $domainQueue = json_decode($this->redis->rpop('domain_abnormal_deposit_notify_queue'), true);

        $this->assertNull($domainQueue);
    }

    /**
     * 測試同步廳的當日入款總金額, 有當日的統計資料, 金額達寄送門檻
     */
    public function testSync()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $deposit = [
            'domain' => 6,
            'confirm_at' => '2016-08-08T12:00:00+0800',
            'amount' => '4500000',
        ];
        $this->redis->rpush('stat_domain_deposit_queue', json_encode($deposit));

        $output = $this->runCommand('durian:sync-domain-deposit-amount');

        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('1 queue processed.', $results[0]);

        // 檢查統計資料
        $criteria = [
            'domain' => 6,
            'at' => '20160808',
        ];
        $stat = $em->getRepository('BBDurianBundle:StatDomainDepositAmount')->findOneBy($criteria);

        $this->assertEquals('1', $stat->getId());
        $this->assertEquals('6', $stat->getDomain());
        $this->assertEquals('20160808', $stat->getAt());
        $this->assertEquals('5000000', $stat->getAmount());

        // 檢查推到寄送異常入款queue中
        $domainQueue = json_decode($this->redis->rpop('domain_abnormal_deposit_notify_queue'), true);

        $this->assertEquals('6', $domainQueue['domain']);
        $this->assertEquals('20160808', $domainQueue['at']);
    }
}
