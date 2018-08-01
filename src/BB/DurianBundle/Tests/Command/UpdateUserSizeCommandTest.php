<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\UpdateUserSizeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateUserSizeCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default');

        $redis->rpush('user_size_queue', json_encode(['index' => 2, 'value' => 1]));
        $redis->rpush('user_size_queue', json_encode(['index' => 2, 'value' => -1]));
        $redis->rpush('user_size_queue', json_encode(['index' => 3, 'value' => 1]));
    }

    /**
     * 測試更新表格欄位
     */
    public function testUpdateUserSizeCommand()
    {
        $container = $this->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');

        $user2 = $em->find('BBDurianBundle:User', 2);
        $user3 = $em->find('BBDurianBundle:User', 3);

        $this->assertEquals(3, $user2->getSize());
        $this->assertEquals(1, $user3->getSize());
        $out = $this->runCommand('durian:update-user-size', ['--wait-time' => 500000]);

        $result = explode(PHP_EOL, $out);

        // 時間格式
        $timeRegexp = '/^\[\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\]/';

        $this->assertRegexp($timeRegexp, $result[0]);
        $this->assertContains('userId:2, size:0', $result[0]);
        $this->assertContains('userId:3, size:1', $result[1]);

        $em->refresh($user2);
        $em->refresh($user3);

        $this->assertEquals(3, $user2->getSize());
        $this->assertEquals(2, $user3->getSize());
    }

    /**
     * 測試更新表格欄位時發生例外
     */
    public function testUpdateUserSizeCommandButExceptionOccur()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $application = new Application();
        $command = new UpdateUserSizeCommand();
        $command->setContainer($this->getMockContainer());

        $application->add($command);

        $command = $application->find('durian:update-user-size');
        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--wait-time' => 500000
        ];

        $commandTester->execute($params);
        $result = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertContains('[WARNING]Update user size failed, because Connection timed out', $result[0]);

        $em->clear();

        $user2 = $em->find('BBDurianBundle:User', 2);
        $user3 = $em->find('BBDurianBundle:User', 3);

        $this->assertEquals(3, $user2->getSize());
        $this->assertEquals(1, $user3->getSize());

        $redis = $this->getContainer()->get('snc_redis.default');

        $queue = $redis->lrange('user_size_queue', 0, -1);

        $this->assertEquals(json_encode(['index' => 3, 'value' => 1]), $queue[0]);
        $this->assertEquals(1, $redis->llen('user_size_queue'));
    }

    /**
     * 取得 MockContainer
     *
     * @return \Symfony\Component\DependencyInjection\Container
     */
    private function getMockContainer()
    {
        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->willReturn(true);

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['updateUserSize'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('updateUserSize')
            ->willReturn(0);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'beginTransaction', 'getConnection', 'flush', 'rollback'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockBackground = $this->getContainer()->get('durian.monitor.background');
        $mockRedis = $this->getContainer()->get('snc_redis.default');

        $getMap = [
            ['durian.monitor.background', 1, $mockBackground],
            ['doctrine.orm.default_entity_manager', 1, $mockEm],
            ['snc_redis.default', 1, $mockRedis]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('test');

        return $mockContainer;
    }
}
