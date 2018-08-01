<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\LevelTransferCommand;
use BB\DurianBundle\Entity\LevelTransfer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Doctrine\ORM\OptimisticLockException;

class LevelTransferCommandTest extends WebTestCase
{
    /**
     * italking Queue Key
     *
     * @var string
     */
    private $italkingKey = 'italking_message_queue';

    /**
     * 暫存已轉移完成的最大userId的String Key
     *
     * @var string
     */
    private $finishedUserKey = 'transfer_finished_user';

    /**
     * redis
     *
     * @var \Predis\Client
     */
    private $redis;

    /**
     * log檔案路徑
     *
     * @var string
     */
    private $filePath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelTransferData'
        ];

        $this->loadFixtures($classnames);

        $container = $this->getContainer();
        $this->redis = $container->get('snc_redis.default_client');

        $this->redis->rpush('level_transfer_queue', json_encode(['domain' => 3, 'source' => 2, 'target' => 5]));
        $this->redis->rpush('level_transfer_queue', json_encode(['domain' => 10, 'source' => 6, 'target' => 4]));

        $env = $container->get('kernel')->getEnvironment();
        $logsDir = $container->getParameter('kernel.logs_dir');
        $this->filePath = $logsDir . DIRECTORY_SEPARATOR . $env . '/level_transfer.log';
    }

    /**
     * 測試層級轉移，但 redis 不存在未處理的轉移資料
     */
    public function testTransferButUntreatedLevelTransferNotExistInRedis()
    {
        $this->redis->flushdb();

        // 執行轉移背景
        $this->runCommand('durian:level-transfer');

        // 檢查log是否不存在
        $this->assertFileNotExists($this->filePath);
    }

    /**
     * 測試層級轉移，同廳已有其他排程
     */
    public function testTransferButTransferProcessExist()
    {
        $this->redis->hmset(
            'transfer_process_3',
            [
                'source' => 2,
                'target' => 7
            ]
        );

        // 執行轉移背景
        $this->runCommand('durian:level-transfer');

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 5，開始轉移 [] []', $results[0]);
        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 5，同廳有其他正在轉移的層級 [] []', $results[1]);
        $this->assertEmpty($results[2]);

        // 驗證原資料被推回最末端
        $levelTransferQueue = json_decode($this->redis->lpop('level_transfer_queue'), true);
        $this->assertEquals(10, $levelTransferQueue['domain']);

        $levelTransferQueue = json_decode($this->redis->lpop('level_transfer_queue'), true);
        $this->assertEquals(3, $levelTransferQueue['domain']);
    }

    /**
     * 測試層級轉移，但不存在未處理的轉移資料
     */
    public function testTransferButUntreatedLevelTransferNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:LevelTransfer');

        // 驗證 redis queue 數量
        $this->assertEquals(2, $this->redis->llen('level_transfer_queue'));

        // 移除所有需轉移的轉移資料
        $lt3 = $repo->findOneBy(['domain' => 3]);
        $em->remove($lt3);

        $lt10 = $repo->findOneBy(['domain' => 10]);
        $em->remove($lt10);
        $em->flush();

        // 執行轉移背景
        $this->runCommand('durian:level-transfer');

        // 驗證 redis queue 有被推回
        $this->assertEquals(2, $this->redis->llen('level_transfer_queue'));

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 5，開始轉移 [] []', $results[0]);
        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 5，資料庫轉移層級未寫入 [] []', $results[1]);
        $this->assertEmpty($results[2]);
    }

    /**
     * 測試轉移到條件都為0的層級
     */
    public function testTransferToCriteriaIsZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // user4沒有統計資料
        $ul4 = $em->find('BBDurianBundle:UserLevel', 4);
        $this->assertEquals(2, $ul4->getLevelId());

        $ul7 = $em->find('BBDurianBundle:UserLevel', 7);
        $this->assertEquals(2, $ul7->getLevelId());

        $ul8 = $em->find('BBDurianBundle:UserLevel', 8);
        $this->assertEquals(2, $ul8->getLevelId());

        // 將最早的轉移資料的目標層級改成條件都為0的層級
        $levelTransfer = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findOneBy(['domain' => 3]);
        $levelTransfer->setTarget(1);

        $this->redis->lpush('level_transfer_queue', json_encode([
            'domain' => 3, 'source' => $levelTransfer->getSource(), 'target' => 1
        ]));

        // 鎖定user8
        $ul8->locked();
        $em->flush();

        // 第一次轉移
        $this->runCommand('durian:level-transfer');

        $em->refresh($ul4);
        $em->refresh($ul7);
        $em->refresh($ul8);

        // 檢查使用者的層級, user4沒有統計資料仍會轉移, user8被鎖定不會轉移
        $this->assertEquals(1, $ul4->getLevelId());
        $this->assertEquals(1, $ul7->getLevelId());
        $this->assertEquals(2, $ul8->getLevelId());

        // 檢查使用者的前一個層級
        $this->assertEquals(2, $ul4->getLastLevelId());
        $this->assertEquals(2, $ul7->getLastLevelId());
        $this->assertEquals(0, $ul8->getLastLevelId());

        // 檢查log是否存在
        $this->assertFileExists($this->filePath);

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 1，開始轉移 [] []', $results[0]);
        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 1，成功轉移 2 個會員 [] []', $results[1]);
        $this->assertContains('LOGGER.INFO: Currency: 901，成功轉移 2 個會員 [] []', $results[2]);

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 檢查層級人數
        $level1 = $em->find('BBDurianBundle:Level', 1);
        $this->assertEquals(9, $level1->getUserCount());

        $level2 = $em->find('BBDurianBundle:Level', 2);
        $this->assertEquals(98, $level2->getUserCount());

        // 檢查level: 1, currency: 901的統計人數
        $criteria = [
            'levelId' => 1,
            'currency' => 901
        ];
        $levelCurrency1 = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(9, $levelCurrency1->getUserCount());

        // 檢查level: 2, currency: 901的統計人數
        $criteria['levelId'] = 2;
        $levelCurrency2 = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(2, $levelCurrency2->getUserCount());

        // 檢查redis是否紀錄已轉移的最大使用者id
        $this->assertEquals(7, $this->redis->get($this->finishedUserKey . '_3'));

        $em->clear();

        // 檢查levelTransfer資料是否存在
        $levelTransfer = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findOneBy(['domain' => 3]);
        $this->assertNotNull($levelTransfer);

        // 第二次轉移, 已無符合條件的使用者
        $this->runCommand('durian:level-transfer');

        $em->clear();

        // 檢查levelTransfer資料是否已刪除
        $levelTransfer = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findOneBy(['domain' => 3]);
        $this->assertNull($levelTransfer);

        // 檢查redis是否歸零已轉移的最大使用者id
        $this->assertEquals(0, $this->redis->get($this->finishedUserKey . '_3'));

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 1，開始轉移 [] []', $results[3]);
        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 1，成功轉移 0 個會員 [] []', $results[4]);
        $this->assertEmpty($results[5]);
    }

    /**
     * 測試轉移
     */
    public function testTransfer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將user5、user6移到level2
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id IN (5, 6)';
        $em->getConnection()->executeUpdate($sql);

        // 第一次轉移
        $this->runCommand('durian:level-transfer');

        // user4沒有統計資料，所以沒有轉移
        $ul4 = $em->find('BBDurianBundle:UserLevel', 4);
        $this->assertEquals(2, $ul4->getLevelId());
        $this->assertEquals(0, $ul4->getLastLevelId());

        // user5被鎖定，所以沒有轉移
        $ul5 = $em->find('BBDurianBundle:UserLevel', 5);
        $this->assertEquals(2, $ul5->getLevelId());
        $this->assertEquals(0, $ul5->getLastLevelId());

        // 統計資料不符合，所以沒有轉移
        $ul6 = $em->find('BBDurianBundle:UserLevel', 6);
        $this->assertEquals(2, $ul6->getLevelId());
        $this->assertEquals(0, $ul6->getLastLevelId());

        $ul7 = $em->find('BBDurianBundle:UserLevel', 7);
        $this->assertEquals(5, $ul7->getLevelId());
        $this->assertEquals(2, $ul7->getLastLevelId());

        // 統計資料不符合，所以沒有轉移
        $ul8 = $em->find('BBDurianBundle:UserLevel', 8);
        $this->assertEquals(2, $ul8->getLevelId());

        // 檢查log是否存在
        $this->assertFileExists($this->filePath);

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 5，開始轉移 [] []', $results[0]);
        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 5，成功轉移 1 個會員 [] []', $results[1]);
        $this->assertContains('LOGGER.INFO: Currency: 901，成功轉移 1 個會員 [] []', $results[2]);

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 檢查層級人數
        $level1 = $em->find('BBDurianBundle:Level', 2);
        $this->assertEquals(99, $level1->getUserCount());

        $level2 = $em->find('BBDurianBundle:Level', 5);
        $this->assertEquals(1, $level2->getUserCount());

        // 檢查level: 2, currency: 901的統計人數
        $criteria = [
            'levelId' => 2,
            'currency' => 901
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(3, $levelCurrency->getUserCount());

        // 檢查level: 5, currency: 901的統計人數
        $criteria['levelId'] = 5;
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(11, $levelCurrency->getUserCount());

        // 檢查redis是否紀錄已轉移的最大使用者id
        $this->assertEquals(7, $this->redis->get($this->finishedUserKey . '_3'));

        $em->clear();

        // 檢查levelTransfer資料是否存在
        $levelTransfer = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findOneBy(['domain' => 3]);
        $this->assertNotNull($levelTransfer);

        // 第二次轉移, 已無符合條件的使用者
        $this->runCommand('durian:level-transfer');

        $em->clear();

        // 檢查levelTransfer資料是否已刪除
        $levelTransfer = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findOneBy(['domain' => 3]);
        $this->assertNull($levelTransfer);

        // 檢查redis是否歸零已轉移的最大使用者id
        $this->assertEquals(0, $this->redis->get($this->finishedUserKey . '_3'));

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 5，開始轉移 [] []', $results[3]);
        $this->assertContains('LOGGER.INFO: Domain: 3，來源層級ID: 2，目標層級ID: 5，成功轉移 0 個會員 [] []', $results[4]);
        $this->assertEmpty($results[5]);
    }

    /**
     * 測試轉移，更新層級人數時發生 redis 連線超時
     */
    public function testTransferWhenUpdateLevelUserCountButConnectionTimedOut()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');

        $kernel = $container->get('kernel');
        $logsDir = $container->getParameter('kernel.logs_dir');

        $levelTransfer = $this->getMockBuilder('BB\DurianBundle\Entity\LevelTransfer')
            ->disableOriginalConstructor()
            ->getMock();
        $levelTransfer->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);
        $levelTransfer->expects($this->any())
            ->method('getSource')
            ->willReturn(3);
        $levelTransfer->expects($this->any())
            ->method('getTarget')
            ->willReturn(4);

        $userStat = [
            'id' => 1,
            'depositTotal' => 10
        ];

        $mockMethods = [
            'findOneBy',
            'getLevelTransferUser',
            'transferUserTo',
            'getCurrencyUsersBy',
            'findBy'
        ];
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods($mockMethods)
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($levelTransfer);
        $entityRepo->expects($this->any())
            ->method('getLevelTransferUser')
            ->willReturn([$userStat]);
        $entityRepo->expects($this->any())
            ->method('transferUserTo')
            ->willReturn(1);
        $entityRepo->expects($this->any())
            ->method('getCurrencyUsersBy')
            ->willReturn([901 => "1"]);
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $mockRedis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['rpush'])
            ->getMock();

        $mockRedis->expects($this->any())
            ->method('rpush')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getRepository', 'beginTransaction', 'find', 'refresh', 'flush', 'rollback'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($level);

        $logger = $container->get('durian.logger_manager');
        $getMap = [
            ['doctrine.orm.entity_manager', 1, $mockEm],
            ['snc_redis.default_client', 1, $redis],
            ['snc_redis.default', 1, $mockRedis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.monitor.background', 1, $bgMonitor],
            ['kernel', 1, $kernel],
            ['durian.logger_manager', 1, $logger]
        ];

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn($logsDir);

        $command = new LevelTransferCommand();
        $command->setContainer($mockContainer);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log是否存在
        $this->assertFileExists($this->filePath);

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $errorMsg = 'Domain: 2，來源層級ID: 3，目標層級ID: 4，轉移失敗。';
        $errorMsg .= 'ErrorCode: ' . SOCKET_ETIMEDOUT . '，ErrorMsg: 更新層級人數時發生異常，Connection timed out';
        $this->assertContains($errorMsg, $results[1]);
    }

    /**
     * 測試轉移，更新層級幣別人數時發生 redis 連線超時
     */
    public function testTransferWhenUpdateLevelCurrencyUserCountButConnectionTimedOut()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');

        $kernel = $container->get('kernel');
        $logsDir = $container->getParameter('kernel.logs_dir');

        $levelTransfer = $this->getMockBuilder('BB\DurianBundle\Entity\LevelTransfer')
            ->disableOriginalConstructor()
            ->getMock();
        $levelTransfer->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);
        $levelTransfer->expects($this->any())
            ->method('getSource')
            ->willReturn(3);
        $levelTransfer->expects($this->any())
            ->method('getTarget')
            ->willReturn(4);

        $levelCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\LevelCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $levelCurrency->expects($this->any())
            ->method('getCurrency')
            ->willReturn(901);

        $userStat = [
            'id' => 1,
            'depositTotal' => 10
        ];

        $mockMethods = [
            'findOneBy',
            'getLevelTransferUser',
            'transferUserTo',
            'getCurrencyUsersBy',
            'findBy'
        ];
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods($mockMethods)
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($levelTransfer);
        $entityRepo->expects($this->any())
            ->method('getLevelTransferUser')
            ->willReturn([$userStat]);
        $entityRepo->expects($this->any())
            ->method('transferUserTo')
            ->willReturn(1);
        $entityRepo->expects($this->any())
            ->method('getCurrencyUsersBy')
            ->willReturn([901 => "1"]);
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$levelCurrency]);

        $mockRedis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['rpush'])
            ->getMock();

        $mockRedis->expects($this->any())
            ->method('rpush')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getRepository', 'beginTransaction', 'find', 'refresh', 'flush', 'rollback'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($level);

        $logger = $container->get('durian.logger_manager');
        $getMap = [
            ['doctrine.orm.entity_manager', 1, $mockEm],
            ['snc_redis.default_client', 1, $redis],
            ['snc_redis.default', 1, $mockRedis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.monitor.background', 1, $bgMonitor],
            ['kernel', 1, $kernel],
            ['durian.logger_manager', 1, $logger]
        ];

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn($logsDir);

        $command = new LevelTransferCommand();
        $command->setContainer($mockContainer);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log是否存在
        $this->assertFileExists($this->filePath);

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $errorMsg = 'Domain: 2，來源層級ID: 3，目標層級ID: 4，轉移失敗。';
        $errorMsg .= 'ErrorCode: ' . SOCKET_ETIMEDOUT . '，ErrorMsg: 更新層級幣別人數時發生異常，Connection timed out';
        $this->assertContains($errorMsg, $results[1]);
    }

    /**
     * 測試轉移，但發生Exception
     */
    public function testTransferButExceptionOccur()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $bgMonitor = $container->get('durian.monitor.background');
        $italkingOperator = $container->get('durian.italking_operator');

        $kernel = $container->get('kernel');
        $logsDir = $container->getParameter('kernel.logs_dir');

        $levelTransfer = $this->getMockBuilder('BB\DurianBundle\Entity\LevelTransfer')
            ->disableOriginalConstructor()
            ->getMock();
        $levelTransfer->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);
        $levelTransfer->expects($this->any())
            ->method('getSource')
            ->willReturn(3);
        $levelTransfer->expects($this->any())
            ->method('getTarget')
            ->willReturn(4);

        $userStat = [
            'id' => 1,
            'depositTotal' => 10
        ];

        $mockMethods = [
            'findOneBy',
            'getLevelTransferUser',
            'transferUserTo',
            'getCurrencyUsersBy',
            'findBy'
        ];
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods($mockMethods)
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($levelTransfer);
        $entityRepo->expects($this->any())
            ->method('getLevelTransferUser')
            ->willReturn([$userStat]);
        $entityRepo->expects($this->any())
            ->method('transferUserTo')
            ->willReturn(1);
        $entityRepo->expects($this->any())
            ->method('getCurrencyUsersBy')
            ->willReturn([901 => "1"]);
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $mockRedis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['rpush'])
            ->getMock();

        $mockRedis->expects($this->any())
            ->method('rpush')
            ->willReturn([]);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getRepository', 'beginTransaction', 'find', 'refresh', 'flush', 'rollback'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($level);
        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('MySQL server has gone away', 2006));

        $logger = $container->get('durian.logger_manager');
        $getMap = [
            ['doctrine.orm.entity_manager', 1, $mockEm],
            ['snc_redis.default_client', 1, $redis],
            ['snc_redis.default', 1, $mockRedis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.monitor.background', 1, $bgMonitor],
            ['kernel', 1, $kernel],
            ['durian.logger_manager', 1, $logger]
        ];

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn($logsDir);

        $command = new LevelTransferCommand();
        $command->setContainer($mockContainer);

        $application = new Application();
        $application->add($command);

        // 驗證 redis queue 數量
        $this->assertEquals(2, $this->redis->llen('level_transfer_queue'));

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log是否存在
        $this->assertFileExists($this->filePath);

        // 驗證 redis queue 有被推回
        $this->assertEquals(2, $this->redis->llen('level_transfer_queue'));

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $errorMsg = 'Domain: 2，來源層級ID: 3，目標層級ID: 4，轉移失敗。ErrorCode: 2006，ErrorMsg: MySQL server has gone away';
        $this->assertContains($errorMsg, $results[1]);
    }

    /**
     * 測試轉移，但層級計數與幣別計數總和不一致
     */
    public function testTransferButLevelCountAndCurrencyCountIsNotEqual()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $bgMonitor = $container->get('durian.monitor.background');

        $levelTransfer = $this->getMockBuilder('BB\DurianBundle\Entity\LevelTransfer')
            ->disableOriginalConstructor()
            ->getMock();
        $levelTransfer->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);
        $levelTransfer->expects($this->any())
            ->method('getSource')
            ->willReturn(3);
        $levelTransfer->expects($this->any())
            ->method('getTarget')
            ->willReturn(4);

        $userStat = [
            'id' => 1,
            'depositTotal' => 10
        ];

        $mockMethods = [
            'findOneBy',
            'getLevelTransferUser',
            'transferUserTo',
            'getCurrencyUsersBy'
        ];
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods($mockMethods)
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($levelTransfer);
        $entityRepo->expects($this->any())
            ->method('getLevelTransferUser')
            ->willReturn([$userStat]);
        $entityRepo->expects($this->any())
            ->method('transferUserTo')
            ->willReturn(1);
        $entityRepo->expects($this->any())
            ->method('getCurrencyUsersBy')
            ->willReturn([901 => "111"]);

        $mockRedis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['rpush'])
            ->getMock();

        $mockRedis->expects($this->any())
            ->method('rpush')
            ->willReturn([]);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'beginTransaction', 'find', 'rollback'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($level);

        $logger = $container->get('durian.logger_manager');
        $getMap = [
            ['doctrine.orm.entity_manager', 1, $mockEm],
            ['snc_redis.default_client', 1, $redis],
            ['snc_redis.default', 1, $mockRedis],
            ['durian.monitor.background', 1, $bgMonitor],
            ['durian.logger_manager', 1, $logger]
        ];

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $command = new LevelTransferCommand();
        $command->setContainer($mockContainer);

        $application = new Application();
        $application->add($command);

        // 驗證 redis queue 數量
        $this->assertEquals(2, $this->redis->llen('level_transfer_queue'));

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log是否存在
        $this->assertFileExists($this->filePath);

        // 驗證 redis queue 有被推回
        $this->assertEquals(2, $this->redis->llen('level_transfer_queue'));

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $errorMsg = 'Domain: 2，來源層級ID: 3，目標層級ID: 4，轉移失敗。ErrorCode: 0，ErrorMsg: 層級計數與幣別計數總和不一致';
        $this->assertContains($errorMsg, $results[1]);
    }

    /**
     * 刪除相關log
     */
    public function tearDown()
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        parent::tearDown();
    }
}
