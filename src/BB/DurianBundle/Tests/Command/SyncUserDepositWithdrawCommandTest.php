<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Console\Application;
use BB\DurianBundle\Command\SyncUserDepositWithdrawCommand;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;

class SyncUserDepositWithdrawCommandTest extends WebTestCase
{
    /**
     * 初始化
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'];
        $this->loadFixtures($classnames);
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->getContainer()->get('snc_redis.default');
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 測試處理使用者存提款紀錄佇列
     */
    public function testDepositWithdrawQueue()
    {
        $em = $this->getEntityManager();
        $redis = $this->getRedis();

        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';

        $data = [
            'ERRCOUNT' => 0,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 12:00:00'
        ];

        $redis->lpush($depositWithdrawQueue, json_encode($data));

        $this->runCommand('durian:sync-user-deposit-withdraw');

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();

        $this->assertEquals(2, count($depositWithdraws));

        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[0]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertNull($depositWithdraws[0]->getWithdrawAt());
        $this->assertEquals(7, $depositWithdraws[0]->getUserId());
        $this->assertTrue($depositWithdraws[0]->isDeposited());
        $this->assertFalse($depositWithdraws[0]->isWithdrew());
        $this->assertNull($depositWithdraws[0]->getFirstDepositAt());

        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[1]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertNull($depositWithdraws[1]->getWithdrawAt());
        $this->assertEquals(8, $depositWithdraws[1]->getUserId());
        $this->assertTrue($depositWithdraws[1]->isDeposited());
        $this->assertFalse($depositWithdraws[1]->isWithdrew());
        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[1]->getFirstDepositAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試處理重試佇列
     */
    public function testRetryQueue()
    {
        $em = $this->getEntityManager();
        $redis = $this->getRedis();

        $at = new \DateTime('2013-01-01 12:00:00');
        $parent = $em->find('BBDurianBundle:User', 7);
        $parentDepositWithdraw = new UserHasDepositWithdraw($parent, $at, null, false, false);
        $em->persist($parentDepositWithdraw);

        $user = $em->find('BBDurianBundle:User', 8);
        $depositWithdraw = new UserHasDepositWithdraw($user, $at, null, false, false);
        $em->persist($depositWithdraw);

        $em->flush();
        $em->clear();

        $retryQueue = 'cash_deposit_withdraw_retry_queue';

        $data1 = [
            'ERRCOUNT' => 1,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 12:00:00'
        ];

        $data2 = [
            'ERRCOUNT' => 1,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 12:00:00'
        ];

        $data3 = [
            'ERRCOUNT' => 1,
            'user_id' => 8,
            'deposit' => false,
            'withdraw' => true,
            'withdraw_at' => '2016-01-03 12:00:00'
        ];

        $redis->lpush($retryQueue, json_encode($data1));
        $redis->lpush($retryQueue, json_encode($data2));
        $redis->lpush($retryQueue, json_encode($data3));

        $this->runCommand('durian:sync-user-deposit-withdraw');

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();
        $this->assertEquals(2, count($depositWithdraws));

        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[0]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2016-01-03 12:00:00', $depositWithdraws[0]->getWithdrawAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $depositWithdraws[0]->getUserId());
        $this->assertTrue($depositWithdraws[0]->isDeposited());
        $this->assertTrue($depositWithdraws[0]->isWithdrew());
        $this->assertNull($depositWithdraws[0]->getFirstDepositAt());

        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[1]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2016-01-03 12:00:00', $depositWithdraws[1]->getWithdrawAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $depositWithdraws[1]->getUserId());
        $this->assertTrue($depositWithdraws[1]->isDeposited());
        $this->assertTrue($depositWithdraws[1]->isWithdrew());
        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[1]->getFirstDepositAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試處理失敗佇列
     */
    public function testFailedQueue()
    {
        $em = $this->getEntityManager();
        $redis = $this->getRedis();

        $failedQueue = 'cash_deposit_withdraw_failed_queue';

        $data = [
            'ERRCOUNT' => 10,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 12:00:00'
        ];

        $redis->lpush($failedQueue, json_encode($data));

        $this->runCommand('durian:sync-user-deposit-withdraw', ['--recover-fail' => true]);

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();

        $this->assertEquals(2, count($depositWithdraws));

        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[0]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertNull($depositWithdraws[0]->getWithdrawAt());
        $this->assertEquals(7, $depositWithdraws[0]->getUserId());
        $this->assertTrue($depositWithdraws[0]->isDeposited());
        $this->assertFalse($depositWithdraws[0]->isWithdrew());
        $this->assertNull($depositWithdraws[0]->getFirstDepositAt());

        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[1]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertNull($depositWithdraws[1]->getWithdrawAt());
        $this->assertEquals(8, $depositWithdraws[1]->getUserId());
        $this->assertTrue($depositWithdraws[1]->isDeposited());
        $this->assertFalse($depositWithdraws[1]->isWithdrew());
        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[1]->getFirstDepositAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試處理使用者存提款紀錄佇列, 原本沒有首次入款時間
     */
    public function testDepositWithdrawQueueWithoutFirstDepositAt()
    {
        $em = $this->getEntityManager();
        $redis = $this->getRedis();

        $at = new \DateTime('2013-01-01 12:00:00');
        $parent = $em->find('BBDurianBundle:User', 7);
        $parentDepositWithdraw = new UserHasDepositWithdraw($parent, null, $at, false, true);
        $em->persist($parentDepositWithdraw);

        $user = $em->find('BBDurianBundle:User', 8);
        $depositWithdraw = new UserHasDepositWithdraw($user, null, $at, false, true);
        $em->persist($depositWithdraw);

        $em->flush();
        $em->clear();

        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';

        $data = [
            'ERRCOUNT' => 0,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 12:00:00'
        ];

        $redis->lpush($depositWithdrawQueue, json_encode($data));

        $this->runCommand('durian:sync-user-deposit-withdraw');

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();
        $this->assertEquals(2, count($depositWithdraws));

        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[0]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2013-01-01 12:00:00', $depositWithdraws[0]->getWithdrawAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $depositWithdraws[0]->getUserId());
        $this->assertTrue($depositWithdraws[0]->isDeposited());
        $this->assertTrue($depositWithdraws[0]->isWithdrew());
        $this->assertNull($depositWithdraws[0]->getFirstDepositAt());

        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[1]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2013-01-01 12:00:00', $depositWithdraws[1]->getWithdrawAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $depositWithdraws[1]->getUserId());
        $this->assertTrue($depositWithdraws[1]->isDeposited());
        $this->assertTrue($depositWithdraws[1]->isWithdrew());
        $this->assertEquals('2016-01-01 12:00:00', $depositWithdraws[1]->getFirstDepositAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試處理使用者存提款紀錄佇列, 原本首次入款時間>新的入款時間
     */
    public function testDepositWithdrawQueueWithFirstDepositAtGreaterThanDepositAt()
    {
        $em = $this->getEntityManager();
        $redis = $this->getRedis();

        $at = new \DateTime('2016-01-01 12:00:00');
        $parent = $em->find('BBDurianBundle:User', 7);
        $parentDepositWithdraw = new UserHasDepositWithdraw($parent, $at, null, true, false);
        $em->persist($parentDepositWithdraw);

        $user = $em->find('BBDurianBundle:User', 8);
        $depositWithdraw = new UserHasDepositWithdraw($user, $at, null, true, false);
        $depositWithdraw->setFirstDepositAt($at->format('YmdHis'));
        $em->persist($depositWithdraw);

        $em->flush();
        $em->clear();

        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';

        $data = [
            'ERRCOUNT' => 0,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 11:00:00'
        ];

        $redis->lpush($depositWithdrawQueue, json_encode($data));

        $this->runCommand('durian:sync-user-deposit-withdraw');

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();
        $this->assertEquals(2, count($depositWithdraws));

        $this->assertEquals('2016-01-01 11:00:00', $depositWithdraws[0]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertNull($depositWithdraws[0]->getWithdrawAt());
        $this->assertEquals(7, $depositWithdraws[0]->getUserId());
        $this->assertTrue($depositWithdraws[0]->isDeposited());
        $this->assertFalse($depositWithdraws[0]->isWithdrew());
        $this->assertNull($depositWithdraws[0]->getFirstDepositAt());

        $this->assertEquals('2016-01-01 11:00:00', $depositWithdraws[1]->getDepositAt()->format('Y-m-d H:i:s'));
        $this->assertNull($depositWithdraws[1]->getWithdrawAt());
        $this->assertEquals(8, $depositWithdraws[1]->getUserId());
        $this->assertTrue($depositWithdraws[1]->isDeposited());
        $this->assertFalse($depositWithdraws[1]->isWithdrew());
        $this->assertEquals('2016-01-01 11:00:00', $depositWithdraws[1]->getFirstDepositAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試處理佇列，使用者不存在
     */
    public function testQueueWithUserNotExist()
    {
        $em = $this->getEntityManager();
        $redis = $this->getRedis();

        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';

        $data = [
            'ERRCOUNT' => 0,
            'user_id' => 20,
            'deposit' => true,
            'withdraw' => false,
            'at' => '2016-01-01 12:00:00'
        ];

        $redis->lpush($depositWithdrawQueue, json_encode($data));

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();
        $this->assertEquals(0, count($depositWithdraws));

        $em->clear();

        $this->runCommand('durian:sync-user-deposit-withdraw');

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();
        $this->assertEquals(0, count($depositWithdraws));
    }

    /**
     * 測試處理佇列，使用者為代理
     */
    public function testQueueWithUserRoleTwo()
    {
        $em = $this->getEntityManager();
        $redis = $this->getRedis();

        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';

        $data = [
            'ERRCOUNT' => 0,
            'user_id' => 7,
            'deposit' => true,
            'withdraw' => false,
            'at' => '2016-01-01 12:00:00'
        ];

        $redis->lpush($depositWithdrawQueue, json_encode($data));

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();
        $this->assertEquals(0, count($depositWithdraws));

        $em->clear();

        $this->runCommand('durian:sync-user-deposit-withdraw');

        $depositWithdraws = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw')->findAll();
        $this->assertEquals(0, count($depositWithdraws));
    }

    /**
     * 測試處理queue錯誤
     */
    public function testProcessQueueFail()
    {
        $redis = $this->getRedis();

        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';
        $retryQueue = 'cash_deposit_withdraw_retry_queue';

        $data = [
            'ERRCOUNT' => 0,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 12:00:00'
        ];

        $redis->lpush($depositWithdrawQueue, json_encode($data));

        $application = new Application();
        $command = new SyncUserDepositWithdrawCommand();
        $command->setContainer($this->getMockContainer(true));
        $application->add($command);

        $command = $application->find('durian:sync-user-deposit-withdraw');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $result = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertContains('error', $result[3]);
        $this->assertContains('Connection timed out', $result[5]);

        $this->assertEquals(0, $redis->llen($depositWithdrawQueue));
        $this->assertEquals(1, $redis->llen($retryQueue));

        $queue = $redis->lrange($retryQueue, 0, -1);
        $queueContent = json_decode($queue[0], true);

        $this->assertEquals(1, $queueContent['ERRCOUNT']);
        $this->assertEquals('2016-01-01 12:00:00', $queueContent['deposit_at']);
        $this->assertEquals(8, $queueContent['user_id']);
        $this->assertTrue($queueContent['deposit']);
        $this->assertFalse($queueContent['withdraw']);
    }

    /**
     * 測試處理使用者存提款紀錄佇列失敗，推入重試佇列
     */
    public function testPushToRetry()
    {
        $redis = $this->getRedis();

        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';
        $retryQueue = 'cash_deposit_withdraw_retry_queue';

        $data = [
            'ERRCOUNT' => 0,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 12:00:00'
        ];

        $redis->lpush($depositWithdrawQueue, json_encode($data));

        $application = new Application();
        $command = new SyncUserDepositWithdrawCommand();
        $command->setContainer($this->getMockContainer());
        $application->add($command);

        $command = $application->find('durian:sync-user-deposit-withdraw');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $result = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertContains('error', $result[3]);
        $this->assertContains('Connection timed out', $result[5]);
        $this->assertContains('error', $result[11]);
        $this->assertContains('Connection timed out', $result[13]);

        $this->assertEquals(0, $redis->llen($depositWithdrawQueue));
        $this->assertEquals(2, $redis->llen($retryQueue));

        $queue = $redis->lrange($retryQueue, 0, -1);
        $queueContent1 = json_decode($queue[0], true);
        $queueContent2 = json_decode($queue[1], true);

        $this->assertEquals(1, $queueContent1['ERRCOUNT']);
        $this->assertEquals('2016-01-01 12:00:00', $queueContent1['deposit_at']);
        $this->assertEquals(7, $queueContent1['user_id']);
        $this->assertTrue($queueContent1['deposit']);
        $this->assertFalse($queueContent1['withdraw']);

        $this->assertEquals(1, $queueContent2['ERRCOUNT']);
        $this->assertEquals('2016-01-01 12:00:00', $queueContent2['deposit_at']);
        $this->assertEquals(8, $queueContent2['user_id']);
        $this->assertTrue($queueContent2['deposit']);
        $this->assertFalse($queueContent2['withdraw']);
    }

    /**
     * 測試處理重試佇列失敗，推入失敗佇列
     */
    public function testPushToFailed()
    {
        $redis = $this->getRedis();

        $retryQueue = 'cash_deposit_withdraw_retry_queue';
        $failedQueue = 'cash_deposit_withdraw_failed_queue';

        $data = [
            'ERRCOUNT' => 9,
            'user_id' => 8,
            'deposit' => true,
            'withdraw' => false,
            'deposit_at' => '2016-01-01 12:00:00'
        ];

        $redis->lpush($retryQueue, json_encode($data));

        $application = new Application();
        $command = new SyncUserDepositWithdrawCommand();
        $command->setContainer($this->getMockContainer());
        $application->add($command);

        $command = $application->find('durian:sync-user-deposit-withdraw');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $result = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertContains('error', $result[3]);
        $this->assertContains('Connection timed out', $result[5]);
        $this->assertContains('error', $result[11]);
        $this->assertContains('Connection timed out', $result[13]);

        $this->assertEquals(0, $redis->llen($retryQueue));
        $this->assertEquals(2, $redis->llen($failedQueue));

        $queue = $redis->lrange($failedQueue, 0, -1);
        $queueContent1 = json_decode($queue[0], true);
        $queueContent2 = json_decode($queue[1], true);

        $this->assertEquals(10, $queueContent1['ERRCOUNT']);
        $this->assertEquals('2016-01-01 12:00:00', $queueContent1['deposit_at']);
        $this->assertEquals(7, $queueContent1['user_id']);
        $this->assertTrue($queueContent1['deposit']);
        $this->assertFalse($queueContent1['withdraw']);

        $this->assertEquals(10, $queueContent2['ERRCOUNT']);
        $this->assertEquals('2016-01-01 12:00:00', $queueContent2['deposit_at']);
        $this->assertEquals(8, $queueContent2['user_id']);
        $this->assertTrue($queueContent2['deposit']);
        $this->assertFalse($queueContent2['withdraw']);
    }

    /**
     * 測試處理queue錯誤,但queue內容為空
     */
    public function testPushToRetryWithEmptyContent()
    {
        $redis = $this->getRedis();

        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';
        $retryQueue = 'cash_deposit_withdraw_retry_queue';

        $application = new Application();
        $command = new SyncUserDepositWithdrawCommand();
        $command->setContainer($this->getMockContainer(false, true));
        $application->add($command);

        $command = $application->find('durian:sync-user-deposit-withdraw');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $result = explode(PHP_EOL, $commandTester->getDisplay());
        $this->assertContains('error', $result[3]);
        $this->assertContains('Connection timed out', $result[5]);
        $this->assertContains('error', $result[11]);
        $this->assertContains('Connection timed out', $result[13]);

        $this->assertEquals(0, $redis->llen($depositWithdrawQueue));
        $this->assertEquals(0, $redis->llen($retryQueue));
    }

    /**
     * 取得 MockContainer
     *
     * @param boolean $findFail  是否在find時出錯
     * @param boolean $mockRedis 是否在rpop時出錯
     * @return \Symfony\Component\DependencyInjection\Container
     */
    private function getMockContainer($findFail = false, $mockRedis = false)
    {
        $container = $this->getContainer();

        $logger = $container->get('logger');
        $handler = $container->get('monolog.handler.sync_user_deposit_withdraw');
        $sqlLogger = $container->get('durian.logger_sql');
        $redis = $container->get('snc_redis.default_client');
        $bgMonitor = $container->get('durian.monitor.background');

        if ($mockRedis) {
            $redis = $this->getMockBuilder('Predis\Client')
                ->disableOriginalConstructor()
                ->setMethods(['rpop'])
                ->getMock();

            $redis->expects($this->at(0))
                ->method('rpop')
                ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

            $redis->expects($this->at(1))
                ->method('rpop')
                ->will($this->returnValue(null));

            $redis->expects($this->at(2))
                ->method('rpop')
                ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

            $redis->expects($this->at(3))
                ->method('rpop')
                ->will($this->returnValue(null));
        }

        $mockConfig = $this->getMockBuilder('\Doctrine\DBAL\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($mockConfig));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $find = function ($entity, $userId) {
            return $this->getEntityManager()->find($entity, $userId);
        };

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnCallback($find));

        if ($findFail) {
            $mockEm->expects($this->any())
                ->method('find')
                ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));
        }

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['durian.monitor.background', 1, $bgMonitor],
            ['doctrine.dbal.default_connection', 1, $mockConn],
            ['doctrine.orm.default_entity_manager', 1, $mockEm],
            ['logger', 1, $logger],
            ['monolog.handler.sync_user_deposit_withdraw', 1, $handler],
            ['durian.logger_sql', 1, $sqlLogger],
            ['snc_redis.default_client', 1, $redis]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }

    /**
     * 清除產生的檔案
     */
    public function tearDown()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test.sync_user_deposit_withdraw.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        parent::tearDown();
    }
}
