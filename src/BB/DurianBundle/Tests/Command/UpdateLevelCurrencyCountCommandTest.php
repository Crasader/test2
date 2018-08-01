<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\UpdateLevelCurrencyCountCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateLevelCurrencyCountCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default');

        $redis->rpush('level_currency_user_count_queue', json_encode(['index' => '2_156', 'value' => 1]));
        $redis->rpush('level_currency_user_count_queue', json_encode(['index' => '2_156', 'value' => -1]));
        $redis->rpush('level_currency_user_count_queue', json_encode(['index' => '5_901', 'value' => 1]));
    }

    /**
     * 更新層級幣別會員人數
     */
    public function testUpdateLevelCurrencyCountCommand()
    {
        $container = $this->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository('BBDurianBundle:LevelCurrency');

        $levelCurrency1 = $repo->findOneBy(['levelId' => 2, 'currency' => 156]);
        $levelCurrency2 = $repo->findOneBy(['levelId' => 5, 'currency' => 901]);

        $this->assertEquals(8, $levelCurrency1->getUserCount());
        $this->assertEquals(10, $levelCurrency2->getUserCount());
        $out = $this->runCommand('durian:update-level-currency-count', ['--wait-time' => 500000]);

        $result = explode(PHP_EOL, $out);

        // 時間格式
        $timeRegexp = '/^\[\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\]/';

        $this->assertRegexp($timeRegexp, $result[0]);
        $this->assertContains('levelId:2, currency:156, changeCount:0', $result[0]);
        $this->assertContains('levelId:5, currency:901, changeCount:1', $result[1]);

        $em->refresh($levelCurrency1);
        $em->refresh($levelCurrency2);

        $this->assertEquals(8, $levelCurrency1->getUserCount());
        $this->assertEquals(11, $levelCurrency2->getUserCount());
    }

    /**
     * 測試更新表格欄位時發生例外
     */
    public function testUpdateLevelCurrencyCountCommandButExceptionOccur()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $application = new Application();
        $command = new UpdateLevelCurrencyCountCommand();
        $command->setContainer($this->getMockContainer());

        $application->add($command);

        $command = $application->find('durian:update-level-currency-count');
        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--wait-time' => 500000
        ];

        $commandTester->execute($params);
        $result = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertContains('[WARNING]Update level user count failed, because Connection timed out', $result[0]);

        $em->clear();

        $repo = $em->getRepository('BBDurianBundle:LevelCurrency');

        $levelCurrency1 = $repo->findOneBy(['levelId' => 2, 'currency' => 156]);
        $levelCurrency2 = $repo->findOneBy(['levelId' => 5, 'currency' => 901]);

        $this->assertEquals(8, $levelCurrency1->getUserCount());
        $this->assertEquals(10, $levelCurrency2->getUserCount());

        $redis = $this->getContainer()->get('snc_redis.default');

        $queue = $redis->lrange('level_currency_user_count_queue', 0, -1);

        $this->assertEquals(json_encode(['index' => '5_901', 'value' => 1]), $queue[0]);
        $this->assertEquals(1, $redis->llen('level_currency_user_count_queue'));
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
