<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Command\UpdateCrawlerRunTurnOffCommand;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCrawlerRunTurnOffCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
        ]);
    }

    /**
     * 測試更新爬蟲執行狀態
     */
    public function testUpdateCrawlerRunCommand()
    {
        $container = $this->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');

        $remitAccount7 = $em->find('BBDurianBundle:RemitAccount', 7);
        $remitAccount8 = $em->find('BBDurianBundle:RemitAccount', 8);
        $remitAccount9 = $em->find('BBDurianBundle:RemitAccount', 9);

        $now = new \DateTime();
        $overTime = (clone $now)->modify('- 350 seconds');

        // 設定爬蟲最後執行時間不超過update-time
        $remitAccount7->setCrawlerUpdate($now);
        $remitAccount7->setCrawlerRun(true);
        $remitAccount7->setAutoRemitId(2);

        $remitAccount9->setCrawlerUpdate($now);

        // 設定爬蟲最後執行時間超過update-time
        $remitAccount8->setCrawlerUpdate($overTime);
        $remitAccount8->setCrawlerRun(true);
        $remitAccount8->setAutoConfirm(true);
        $remitAccount8->setAutoRemitId(2);

        $em->flush();

        $this->assertTrue($remitAccount7->isCrawlerRun());
        $this->assertTrue($remitAccount8->isCrawlerRun());
        $this->assertTrue($remitAccount9->isCrawlerRun());

        $output = $this->runCommand('durian:update-crawler-run-turn-off', ['--idle-time' => 300]);
        $result = explode(PHP_EOL, $output);

        $this->assertEquals('UpdateCrawlerRunTurnOffCommand Start.', $result[0]);

        $pattern = "/Change crawler_run to false when crawler_update less than \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/";
        $this->assertRegExp($pattern, $result[1]);

        $pattern = "/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] Remit Account Ids: 8/";
        $this->assertRegExp($pattern, $result[2]);
        $this->assertEquals('UpdateCrawlerRunTurnOffCommand Finished.', $result[3]);

        $em->refresh($remitAccount7);
        $em->refresh($remitAccount8);
        $em->refresh($remitAccount9);

        $this->assertTrue($remitAccount7->isCrawlerRun());
        $this->assertFalse($remitAccount8->isCrawlerRun());
        $this->assertTrue($remitAccount9->isCrawlerRun());
    }

    /**
     * 測試自動認款帳號為同略雲且爬蟲執行狀態為執行中
     */
    public function testUpdateCrawlerRunCommandWhenTongLueYunAndCrawlerRunIsTrue()
    {
        $container = $this->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');

        $remitAccount8 = $em->find('BBDurianBundle:RemitAccount', 8);
        $remitAccount9 = $em->find('BBDurianBundle:RemitAccount', 9);

        $now = new \DateTime();
        $overTime = $now->modify('- 350 seconds');

        // 設定爬蟲最後執行時間超過update-time
        $remitAccount8->setCrawlerUpdate($overTime);
        $remitAccount8->setCrawlerRun(true);
        $remitAccount8->setAutoConfirm(true);
        $remitAccount8->setAutoRemitId(2);

        $remitAccount9->setAutoRemitId(1);

        $em->flush();

        $output = $this->runCommand('durian:update-crawler-run-turn-off', ['--idle-time' => 300]);
        $result = explode(PHP_EOL, $output);

        $this->assertEquals('UpdateCrawlerRunTurnOffCommand Start.', $result[0]);

        $pattern = "/Change crawler_run to false when crawler_update less than \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/";
        $this->assertRegExp($pattern, $result[1]);

        $pattern = "/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] Remit Account Ids: 8/";
        $this->assertRegExp($pattern, $result[2]);
        $this->assertEquals('UpdateCrawlerRunTurnOffCommand Finished.', $result[3]);

        $em->refresh($remitAccount8);
        $em->refresh($remitAccount9);

        $this->assertFalse($remitAccount8->isCrawlerRun());
        $this->assertTrue($remitAccount9->isCrawlerRun());
    }

    /**
     * 測試自動認款帳號為秒付通且爬蟲執行狀態為執行中
     */
    public function testUpdateCrawlerRunCommandWhenMiaoFuTongAndCrawlerRunIsTrue()
    {
        $container = $this->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');

        $remitAccount8 = $em->find('BBDurianBundle:RemitAccount', 8);
        $remitAccount9 = $em->find('BBDurianBundle:RemitAccount', 9);

        $now = new \DateTime();
        $overTime = $now->modify('- 350 seconds');

        // 設定爬蟲最後執行時間超過update-time
        $remitAccount8->setCrawlerUpdate($overTime);
        $remitAccount8->setCrawlerRun(true);
        $remitAccount8->setAutoConfirm(true);
        $remitAccount8->setAutoRemitId(2);

        $remitAccount9->setAutoRemitId(3);

        $em->flush();

        $output = $this->runCommand('durian:update-crawler-run-turn-off', ['--idle-time' => 300]);
        $result = explode(PHP_EOL, $output);

        $this->assertEquals('UpdateCrawlerRunTurnOffCommand Start.', $result[0]);

        $pattern = "/Change crawler_run to false when crawler_update less than \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/";
        $this->assertRegExp($pattern, $result[1]);

        $pattern = "/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] Remit Account Ids: 8/";
        $this->assertRegExp($pattern, $result[2]);
        $this->assertEquals('UpdateCrawlerRunTurnOffCommand Finished.', $result[3]);

        $em->refresh($remitAccount8);
        $em->refresh($remitAccount9);

        $this->assertFalse($remitAccount8->isCrawlerRun());
        $this->assertTrue($remitAccount9->isCrawlerRun());
    }

    /**
     * 測試更新爬蟲執行狀態時發生例外
     */
    public function testUpdateCrawlerRunExceptionHappen()
    {
        $application = new Application();
        $command = new UpdateCrawlerRunTurnOffCommand();
        $command->setContainer($this->getMockContainer());

        $application->add($command);

        $command = $application->find('durian:update-crawler-run-turn-off');
        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--idle-time' => 300,
        ];

        $commandTester->execute($params);
        $result = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('Update crawler_run failed, Error Message: Connection timed out', $result[2]);
    }

    /**
     * 取得 MockContainer
     *
     * @return \Symfony\Component\DependencyInjection\Container
     */
    private function getMockContainer()
    {
        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getRemitAccounts'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('getRemitAccounts')
            ->willReturn([]);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'beginTransaction', 'flush', 'rollback'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockBackground = $this->getContainer()->get('durian.monitor.background');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['doctrine.orm.entity_manager', 1, $mockEm],
            ['durian.monitor.background', 1, $mockBackground],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }
}
