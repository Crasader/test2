<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\UpdateLevelCountCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateLevelCountCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default');

        $redis->rpush('level_user_count_queue', json_encode(['index' => 1, 'value' => 1]));
        $redis->rpush('level_user_count_queue', json_encode(['index' => 1, 'value' => -1]));
        $redis->rpush('level_user_count_queue', json_encode(['index' => 2, 'value' => 1]));
    }

    /**
     * 更新層級會員人數
     */
    public function testUpdateLevelCountCommand()
    {
        $container = $this->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');

        $level1 = $em->find('BBDurianBundle:Level', 1);
        $level2 = $em->find('BBDurianBundle:Level', 2);

        $this->assertEquals(7, $level1->getUserCount());
        $this->assertEquals(100, $level2->getUserCount());
        $out = $this->runCommand('durian:update-level-count', ['--wait-time' => 500000]);

        $result = explode(PHP_EOL, $out);

        // 時間格式
        $timeRegexp = '/^\[\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\]/';

        $this->assertRegexp($timeRegexp, $result[0]);
        $this->assertContains('levelId:1, changeCount:0', $result[0]);
        $this->assertContains('levelId:2, changeCount:1', $result[1]);

        $em->refresh($level1);
        $em->refresh($level2);

        $this->assertEquals(7, $level1->getUserCount());
        $this->assertEquals(101, $level2->getUserCount());
    }

    /**
     * 更新層級會員人數時發生例外
     */
    public function testUpdateLevelCountCommandButExceptionOccur()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $application = new Application();
        $command = new UpdateLevelCountCommand();
        $command->setContainer($this->getMockContainer());

        $application->add($command);

        $command = $application->find('durian:update-level-count');
        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--wait-time' => 500000
        ];

        $commandTester->execute($params);
        $result = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertContains('[WARNING]Update level user count failed, because Connection timed out', $result[0]);

        $em->clear();

        $level1 = $em->find('BBDurianBundle:Level', 1);
        $level2 = $em->find('BBDurianBundle:Level', 2);

        $this->assertEquals(7, $level1->getUserCount());
        $this->assertEquals(100, $level2->getUserCount());

        $redis = $this->getContainer()->get('snc_redis.default');

        $queue = $redis->lrange('level_user_count_queue', 0, -1);

        $this->assertEquals(json_encode(['index' => 2, 'value' => 1]), $queue[0]);
        $this->assertEquals(1, $redis->llen('level_user_count_queue'));
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
            ->setMethods(['updateLevelCount'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('updateLevelCount')
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
