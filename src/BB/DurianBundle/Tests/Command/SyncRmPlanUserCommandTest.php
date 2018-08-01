<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\SyncRmPlanUserCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;
use BB\DurianBundle\Entity\RmPlanUser;
use BB\DurianBundle\Entity\RmPlan;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\CardEntry;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\CreditEntry;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserLevel;
use BB\DurianBundle\Entity\RmPlanUserExtraBalance;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;
use BB\DurianBundle\Entity\UserHasApiTransferInOut;

class SyncRmPlanUserCommandTest extends WebTestCase
{
    /**
     * log檔的路徑
     *
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasDepositWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasApiTransferInOutData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanLevelData'
        ];

        $this->loadFixtures($classnames, 'share');

        $this->loadFixtures([], 'entry');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'sync_rm_plan_user.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
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

    /**
     * 測試建立待刪除使用者
     */
    public function testSyncRmPlanUser()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $plan = $emShare->find('BBDurianBundle:RmPlan', 1);
        $plan->confirm();
        $plan->queueDone();

        // 檢查使用者最後登入時間為空
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertNull($user->getLastLogin());

        $cash = new Cash($user, 156);
        $cash->setBalance(1234);
        $em->persist($cash);
        $em->flush();

        $msg = [
            'plan_id' => 1,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_1', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $ret['result'] = 'ok';
        $ret['ret']['user_id'] = 8;
        $ret['ret']['balance'] = 10.00;
        $ret['message'] = 'ok';

        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 sync RmPlanUser successfully';
        $this->assertContains($msg, $results[0]);

        // 檢查資料庫
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(1, $rpUser->getPlanId());
        $this->assertEquals(8, $rpUser->getUserId());
        $this->assertEquals('tester', $rpUser->getUsername());
        $this->assertEquals('tester', $rpUser->getAlias());
        $this->assertEquals(1234, $rpUser->getCashBalance());
        $this->assertEquals(156, $rpUser->getCashCurrency());
        $this->assertNull($redis->lpop('rm_plan_user_queue'));

        $plan = $emShare->find('BBDurianBundle:RmPlan', 1);
        $this->assertTrue($plan->isUserCreated());
        $this->assertFalse($redis->exists('rm_plan_1'));

        $balance1 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'ag']);
        $balance2 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'mg']);
        $balance3 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'sabah']);
        $this->assertEquals(10, $balance1->getBalance());
        $this->assertEquals(10, $balance2->getBalance());
        $this->assertNull($balance3);
    }

    /**
     * 測試建立待刪除使用者，使用者有假現金
     */
    public function testSyncRmPlanUserWhenUserHasCashFake()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $cashFake = new CashFake($user, 156);
        $cashFake->setBalance(1234);
        $em->persist($cashFake);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $ret['result'] = 'error';
        $ret['message'] = 'ok';
        $ret['msg'] = 'No such user';

        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 sync RmPlanUser successfully';
        $this->assertContains($msg, $results[0]);

        // 檢查資料庫
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(1234, $rpUser->getCashFakeBalance());
        $this->assertEquals(156, $rpUser->getCashFakeCurrency());
    }

    /**
     * 測試建立待刪除使用者，使用者有信用額度
     */
    public function testSyncRmPlanUserWhenUserHasCredit()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $credit = new Credit($user, 1);
        $credit->setLine(0);
        $em->persist($credit);

        $credit = new Credit($user, 2);
        $credit->setLine(10000);
        $em->persist($credit);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $ret['result'] = true;
        $ret['message'] = 'ok';
        $ret['msg'] = 'No such user';

        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 sync RmPlanUser successfully';
        $this->assertContains($msg, $results[0]);

        // 檢查資料庫
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(10000, $rpUser->getCreditLine());
    }

    /**
     * 測試建立待刪除使用者，因使用者沒登入遊戲所以沒有餘額
     */
    public function testSyncRmPlanUserButUserNeverLoginAndHasNoBalance()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $ret['result'] = true;
        $ret['message'] = 'ok';
        $ret['msg'] = 'No such user';

        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 sync RmPlanUser successfully';
        $this->assertContains($msg, $results[0]);

        // 檢查資料庫
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(2, $rpUser->getPlanId());
        $this->assertEquals(8, $rpUser->getUserId());
        $this->assertEquals('tester', $rpUser->getUsername());
        $this->assertEquals('tester', $rpUser->getAlias());
        $this->assertNull($redis->lpop('rm_plan_user_queue'));

        $plan = $emShare->find('BBDurianBundle:RmPlan', 2);
        $this->assertTrue($plan->isUserCreated());
        $this->assertFalse($redis->exists('rm_plan_2'));

        $balance1 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'ab']);
        $balance2 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'ag']);
        $balance3 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'sabah']);
        $balance4 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'mg']);
        $balance5 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'og']);
        $this->assertNull($balance1);
        $this->assertNull($balance2);
        $this->assertNull($balance3);
        $this->assertNull($balance4);
        $this->assertNull($balance5);
    }

    /**
     * 測試建立待刪除使用者，但刪除計畫已撤銷
     */
    public function testSyncRmPlanUserButPlanCancelled()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.share_entity_manager');

        $msg = [
            'plan_id' => 2,
            'user_id' => 99
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hincrby('rm_plan_2', 'count', 1);
        $redis->hset('rm_plan_2', 'cancel', 1);

        $ret['result'] = true;
        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Plan 2 has been cancelled';
        $this->assertContains($msg, $results[0]);

        $rpUser = $em->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertNull($rpUser);
        $this->assertNull($redis->lpop('rm_plan_user_queue'));
        $this->assertFalse($redis->exists('rm_plan_2'));
    }

    /**
     * 測試建立待刪除使用者，執行五十次後做clear
     */
    public function testSyncRmPlanUserWithClear()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $plan = $emShare->find('BBDurianBundle:RmPlan', 3);
        $plan->queueDone();
        $emShare->flush();

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 3,
            'user_id' => 99
        ];

        // 塞入49筆被撤銷的資料
        $i = 1;
        while ($i<50) {
            $redis->lpush('rm_plan_user_queue', json_encode($msg));
            $redis->hincrby('rm_plan_3', 'count', 1);
            $i++;
        }

        $redis->hset('rm_plan_3', 'cancel', 1);

        // 塞入一筆可正常建立的
        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 5);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $ret['result'] = 'error';
        $ret['message'] = 'ok';
        $ret['msg'] = 'No such user';
        $ret['data'] = [
            'user_id' => 8,
            'quantity' => 0
        ];
        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'Plan 3 has been cancelled';
        $this->assertContains($msg, $results[0]);
        $msg = 'User 8 sync RmPlanUser successfully';
        $this->assertContains($msg, $results[49]);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(2, $rpUser->getPlanId());
        $this->assertEquals(8, $rpUser->getUserId());
        $this->assertNull($redis->lpop('rm_plan_user_queue'));
        $this->assertEquals(4, $redis->hget('rm_plan_2', 'count'));
        $this->assertFalse($redis->exists('rm_plan_3'));

        $balance1 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'ag']);
        $balance2 = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'mg']);
        $this->assertNull($balance1);
        $this->assertNull($balance2);
    }

    /**
     * 測試建立待刪除使用者，帶入不存在的userId
     */
    public function testSyncRmPlanUserWithNotExistUserId()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $plan->queueDone();
        $em->flush();

        $msg = [
            'plan_id' => 1,
            'user_id' => 99
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_1', 'count', 1);

        $rpUser = $em->find('BBDurianBundle:RmPlanUser', 1);
        $em->remove($rpUser);
        $em->flush();

        $ret['result'] = true;
        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 99 does not exist';
        $this->assertContains($msg, $results[0]);

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $this->assertTrue($plan->isFinished());
        $this->assertEquals('沒有建立任何待刪除使用者', $plan->getMemo());
        $this->assertNull($redis->lpop('rm_plan_user_queue'));
    }

    /**
     * 測試建立待刪除使用者，使用者七天內有登入紀錄
     */
    public function testSyncRmPlanUserButUserLoginIn7Days()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $time = new \DateTime('now');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $ret['result'] = true;
        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 確認redis內沒有資料
        $this->assertNull($redis->lpop('rm_plan_user_queue'));

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 has login log last week';
        $this->assertContains($msg, $results[0]);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertNull($rpUser);
    }

    /**
     * 測試建立當月有租卡明細的待刪除使用者
     */
    public function testRemoveUserHasCardEntryThisMonth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        // 新增租卡與明細
        $user = $em->find('BBDurianBundle:User', 8);
        $card = new Card($user);
        $entry = new CardEntry($card, 9901, 3000, 3000, 'company');
        $entry->setId(1);
        $entry->setCreatedAt(new \DateTime());

        $em->persist($card);
        $em->persist($entry);
        $em->flush();

        $this->runCommand('durian:sync-rm-plan-user');

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 has entry this month';
        $this->assertContains($msg, $results[0]);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertNull($rpUser);
    }

    /**
     * 測試建立當月有現金明細的待刪除使用者
     */
    public function testRemoveUserHasCashEntryThisMonth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $now = new \DateTime();
        $cash = new Cash($user, 156);

        $em->persist($cash);
        $em->flush();

        $entry = new CashEntry($cash, 1001, 1000);
        $entry->setId(1);
        $entry->setRefId(1899192299);
        $entry->setCreatedAt($now);
        $entry->setAt($now->format('YmdHis'));

        $emEntry->persist($entry);
        $emEntry->flush();

        $this->runCommand('durian:sync-rm-plan-user');

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 has entry this month';
        $this->assertContains($msg, $results[0]);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertNull($rpUser);
    }

    /**
     * 測試建立當月有假現金明細的待刪除使用者
     */
    public function testRemoveUserHasCashFakeEntryThisMonth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $now = new \DateTime();
        $cashFake = new CashFake($user, 156);

        $em->persist($cashFake);
        $em->flush();

        $entry = new CashFakeEntry($cashFake, 1006, 1000);
        $entry->setId(1);
        $entry->setRefId(1899192299);
        $entry->setCreatedAt($now);
        $entry->setAt($now->format('YmdHis'));

        $em->persist($entry);
        $em->flush();

        $this->runCommand('durian:sync-rm-plan-user');

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 has entry this month';
        $this->assertContains($msg, $results[0]);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertNull($rpUser);
    }

    /**
     * 測試建立當月有信用額度明細的待刪除使用者
     */
    public function testRemoveUserHasCreditEntryThisMonth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $credit = new Credit($user, 1);
        $credit->setLine(10000);
        $now = new \DateTime('now');

        $entry = new CreditEntry(51, 1, 50020, -100, 900, $now);
        $entry->setCreditId(1);
        $entry->setLine($credit->getLine());
        $entry->setTotalLine($credit->getTotalLine());
        $entry->setRefId(1234567);

        $em->persist($credit);
        $em->persist($entry);
        $em->flush();

        $this->runCommand('durian:sync-rm-plan-user');

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 has entry this month';
        $this->assertContains($msg, $results[0]);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertNull($rpUser);
    }

    /**
     * 測試建立30天以前，之後有體育投注明細的待刪除使用者，但回傳結果錯誤
     */
    public function testRemoveUserHasSportEntryFail()
    {
        $container = $this->getContainer();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(20000007);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];

        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'no such user',
            'data' => [
                'user_id' => 8,
                'quantity' => 5
            ]
        ];

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(json_encode($ret)));

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'no such user';
        $this->assertContains($msg, $results[0]);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertNull($rpUser);
    }

    /**
     * 測試建立30天以前，之後有體育投注明細的待刪除使用者
     */
    public function testRemoveUserHasSportEntry()
    {
        $container = $this->getContainer();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];

        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $ret = [
            'message' => 'ok',
            'data' => [
                'user_id' => 8,
                'quantity' => 5
            ]
        ];

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(json_encode($ret)));

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 has sport entry';
        $this->assertContains($msg, $results[0]);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertNull($rpUser);
    }

    /**
     * 測試發生connection timed out的狀況
     */
    public function testConnectionTimedOut()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];

        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods([])
            ->getMock();

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = '[WARNING]User 8 sync RmPlanUser failed, because Connection timed out';
        $this->assertContains($msg, $results[0]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_exception_queue';
        $msg = '建立刪除計畫下的使用者，發生例外: Connection timed out';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals('Exception', $queueMsg['exception']);
        $this->assertContains($msg, $queueMsg['message']);
    }

    /**
     * 測試建立以使用者建立時間為條件的刪除計畫底下的待刪除使用者
     */
    public function testSyncRmPlanUserWithUserCreatedAt()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/sync_rm_plan_user.log';

        $userCreatedAt = new \DateTime('20160101000000');
        $plan = new RmPlan('aaa', 3, 5, $userCreatedAt, null, '測試');
        $plan->confirm();
        $plan->queueDone();

        $emShare->persist($plan);
        $emShare->flush();

        $time = new \DateTime('20151231000000');
        $user8 = $em->find('BBDurianBundle:User', 8);
        $user8->setCreatedAt($time);
        $em->persist($user8);

        $cash8 = new Cash($user8, 156);
        $em->persist($cash8);

        $user51 = $em->find('BBDurianBundle:User', 51);
        $user51->setCreatedAt($time);
        $em->persist($user51);

        // 新增使用者層級
        $user = $em->find('BBDurianBundle:User', 51);
        $userLevel = new UserLevel($user, 2);
        $em->persist($userLevel);

        $cash51 = new Cash($user51, 156);
        $em->persist($cash51);

        $lastLogin = new \DateTime('now');
        $user7 = $em->find('BBDurianBundle:User', 7);

        $user52 = new User();
        $user52->setId(52);
        $user52->setUsername('acctest');
        $user52->setPassword('');
        $user52->setParent($user7);
        $user7->addSize();
        $user52->setAlias('acctest');
        $user52->setDomain(2);
        $user52->setRole(1);
        $user52->setLastLogin($lastLogin);
        $em->persist($user52);

        $user53 = new User();
        $user53->setId(53);
        $user53->setUsername('acctest53');
        $user53->setPassword('');
        $user53->setParent($user7);
        $user7->addSize();
        $user53->setAlias('acctest53');
        $user53->setDomain(2);
        $user53->setRole(1);
        $user53->setCreatedAt($time);
        $em->persist($user53);

        $cashFake53 = new CashFake($user53, 156);
        $em->persist($cashFake53);

        $apiTransferInOut = new UserHasApiTransferInOut(53, true, false);
        $em->persist($apiTransferInOut);

        $em->flush();

        $msg = [
            'plan_id' => 5,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $msg = [
            'plan_id' => 5,
            'user_id' => 51
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $msg = [
            'plan_id' => 5,
            'user_id' => 52
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $msg = [
            'plan_id' => 5,
            'user_id' => 53
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_5', 'count', 4);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $ret['result'] = true;
        $ret['message'] = 'ok';
        $ret['data'] = [
            'user_id' => 8,
            'quantity' => 0
        ];
        $response = new Response();
        $response->setContent(json_encode($ret));
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);

        $msg = 'User 8 has depositWithdraw record';
        $this->assertContains($msg, $results[0]);
        $msg = 'User 51 sync RmPlanUser successfully';
        $this->assertContains($msg, $results[1]);
        $msg = 'User 52 has login log in last two months';
        $this->assertContains($msg, $results[2]);
        $msg = 'User 53 has api transferInOut record';
        $this->assertContains($msg, $results[3]);

        // 檢查資料庫
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(5, $rpUser->getPlanId());
        $this->assertEquals(51, $rpUser->getUserId());
        $this->assertEquals('oauthuser', $rpUser->getUsername());
        $this->assertEquals('oauthuser', $rpUser->getAlias());
        $this->assertEquals(156, $rpUser->getCashCurrency());
        $this->assertEquals(2, $rpUser->getLevel());
        $this->assertEquals('第一層', $rpUser->getLevelAlias());
        $this->assertNull($redis->lpop('rm_plan_user_queue'));

        $plan = $emShare->find('BBDurianBundle:RmPlan', 5);
        $this->assertTrue($plan->isUserCreated());
        $this->assertFalse($redis->exists('rm_plan_5'));
    }

    /**
     * 測試建立待刪除使用者，但使用 RD5 取得外接額度餘額 API，取得失敗
     */
    public function testSyncRmPlanUserButGetRd5ExternalFail()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('\Buzz\Message\Response')
            ->setMethods(['getContent'])
            ->getMock();

        // 此處 response 用於 hasSportEntryThisMonth
        $ret1 = [
            'message' => 'ok',
            'data' => [
                'user_id' => 8,
                'quantity' => 0
            ]
        ];
        $response->expects($this->at(0))
            ->method('getContent')
            ->willReturn(json_encode($ret1));
        $response->expects($this->at(1))
            ->method('getContent')
            ->willReturn(json_encode($ret1));

        // 用於curl RD5 取外接額度 API。
        $ret2 = [
            'result' => 'error',
            'code' => 150850003,
            'msg' => 'Invalid game_code',
        ];
        $response->expects($this->at(2))
            ->method('getContent')
            ->willReturn(json_encode($ret2));

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 get balance failed';
        $this->assertContains($msg, $results[0]);

        // 檢查資料庫
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(2, $rpUser->getPlanId());
        $this->assertEquals(8, $rpUser->getUserId());
        $this->assertTrue($rpUser->isGetBalanceFail());
        $this->assertNull($redis->lpop('rm_plan_user_queue'));

        $plan = $emShare->find('BBDurianBundle:RmPlan', 2);
        $this->assertTrue($plan->isUserCreated());
        $this->assertFalse($redis->exists('rm_plan_2'));

        $balance = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'ag']);
        $this->assertNull($balance);
    }

    /**
     * 測試建立待刪除使用者，但使用 RD5 取得外接額度餘額 API，取MG時回傳No such user
     */
    public function testSyncRmPlanUserButGetRd5ExternalReturnNoSuchUser()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('\Buzz\Message\Response')
            ->setMethods(['getContent'])
            ->getMock();

        // 此處 response 用於 hasSportEntryThisMonth
        $ret1 = [
            'message' => 'ok',
            'data' => [
                'user_id' => 8,
                'quantity' => 0
            ]
        ];
        $response->expects($this->at(0))
            ->method('getContent')
            ->willReturn(json_encode($ret1));
        $response->expects($this->at(1))
            ->method('getContent')
            ->willReturn(json_encode($ret1));

        // 用於curl RD5 API，帶ag的時候
        $ret2 = [
            'result' => 'ok',
            'ret' => [
                'user_id' => 8,
                'external_name' => 8,
                'currency' => 'CNY',
                'balance' => 0.0000
            ]
        ];
        $response->expects($this->at(2))
            ->method('getContent')
            ->willReturn(json_encode($ret2));

        // 用於curl RD5 API，帶mg的時候
        $ret3 = [
            'result' => 'error',
            'code' => 150850000,
            'msg' => 'No such user',
        ];
        $response->expects($this->at(3))
            ->method('getContent')
            ->willReturn(json_encode($ret3));

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log，應為成功
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 sync RmPlanUser successfully';
        $this->assertContains($msg, $results[0]);

        // 檢查資料庫，getBalanceFail應為False
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(2, $rpUser->getPlanId());
        $this->assertEquals(8, $rpUser->getUserId());
        $this->assertFalse($rpUser->isGetBalanceFail());
        $this->assertNull($redis->lpop('rm_plan_user_queue'));

        $plan = $emShare->find('BBDurianBundle:RmPlan', 2);
        $this->assertTrue($plan->isUserCreated());
        $this->assertFalse($redis->exists('rm_plan_2'));

        $balance = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'ag']);
        $this->assertEquals(0, $balance->getBalance());
        $balance = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'mg']);
        $this->assertNull($balance);
    }

    /**
     * 測試建立待刪除使用者，使用 RD5 API 取得外接額度餘額成功
     */
    public function testSyncRmPlanUserWhenGetRd5ExternalSuccess()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $time = new \DateTime('20140101');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin($time);
        $em->flush();

        $msg = [
            'plan_id' => 2,
            'user_id' => 8
        ];
        $redis->lpush('rm_plan_user_queue', json_encode($msg));
        $redis->hset('rm_plan_2', 'count', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = $this->getMockBuilder('\Buzz\Message\Response')
            ->setMethods(['getContent'])
            ->getMock();

        // 此處 response 用於 hasSportEntryThisMonth
        $ret1 = [
            'message' => 'ok',
            'data' => [
                'user_id' => 8,
                'quantity' => 0
            ]
        ];
        $response->expects($this->at(0))
            ->method('getContent')
            ->willReturn(json_encode($ret1));
        $response->expects($this->at(1))
            ->method('getContent')
            ->willReturn(json_encode($ret1));

        // 用於curl RD5 取外接額度 API
        $ret2 = [
            'result' => 'ok',
            'ret' => [
                'user_id' => 8,
                'external_name' => 8,
                'currency' => 'CNY',
                'balance' => 0.0000
            ]
        ];
        $response->expects($this->at(2))
            ->method('getContent')
            ->willReturn(json_encode($ret2));
        $response->expects($this->at(3))
            ->method('getContent')
            ->willReturn(json_encode($ret2));

        $application = new Application();
        $command = new SyncRmPlanUserCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:sync-rm-plan-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 8 sync RmPlanUser successfully';
        $this->assertContains($msg, $results[0]);

        // 檢查資料庫
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 3);
        $this->assertEquals(2, $rpUser->getPlanId());
        $this->assertEquals(8, $rpUser->getUserId());
        $this->assertFalse($rpUser->isGetBalanceFail());
        $this->assertNull($redis->lpop('rm_plan_user_queue'));

        $plan = $emShare->find('BBDurianBundle:RmPlan', 2);
        $this->assertTrue($plan->isUserCreated());
        $this->assertFalse($redis->exists('rm_plan_2'));

        $balance = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'ag']);
        $this->assertEquals(0, $balance->getBalance());
        $balance = $emShare->find('BBDurianBundle:RmPlanUserExtraBalance', ['id' => 3, 'platform' => 'mg']);
        $this->assertEquals(0, $balance->getBalance());
    }
}
