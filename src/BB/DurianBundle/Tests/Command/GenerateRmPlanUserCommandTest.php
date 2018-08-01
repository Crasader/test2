<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\RmPlan;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\RmPlanQueue;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserAncestor;
use BB\DurianBundle\Entity\CashFake;

class GenerateRmPlanUserCommandTest extends WebTestCase
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
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanQueueData',
        ];

        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'generate_rm_plan_user.log';
        $this->logPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試產生以使用者登入時間為條件的刪除計畫使用者的佇列
     */
    public function testGenerateRmPlanUserWithLastLogin()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');

        $em = $container->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin(new \DateTime('20130101000000'));
        $user->setCreatedAt(new \DateTime('20130101000000'));
        $em->flush();

        $params = [
            '--batch-size' => 1,
            '--wait-time' => 500000
        ];

        $this->runCommand('durian:generate-rm-plan-user', $params);

        $queue = $redis->lrange('rm_plan_user_queue', 0, 10);
        $count = $redis->hget('rm_plan_1', 'count');

        $this->assertEquals(1, $count);
        $this->assertEquals('{"plan_id":1,"user_id":8}', $queue[0]);
        $this->assertCount(1, $queue);

        //測試用代理當parent_id下去建立廳主刪除計畫
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $time = new \DateTime('20170101000000');
        $rmPlan = new RmPlan('rd5', 7, 1, null, $time, '測試');
        $emShare->persist($rmPlan);
        $emShare->flush();

        $plan = $emShare->find('BBDurianBundle:RmPlan', 5);
        $rmPlanQueue = new RmPlanQueue($plan);
        $emShare->persist($rmPlanQueue);
        $emShare->flush();

        $time = new \DateTime('20160101000000');
        $user8 = $em->find('BBDurianBundle:User', 8);
        $user8->setLastLogin($time);

        $user51 = $em->find('BBDurianBundle:User', 51);
        $user51->setCreatedAt($time);
        $em->flush();

        $this->runCommand('durian:generate-rm-plan-user', $params);

        $queue = $redis->lrange('rm_plan_user_queue', 0, 10);
        $count = $redis->hget('rm_plan_5', 'count');

        $this->assertEquals(2, $count);
        $this->assertEquals('{"plan_id":5,"user_id":51}', $queue[0]);
        $this->assertEquals('{"plan_id":5,"user_id":8}', $queue[1]);
    }

    /**
     * 測試產生以使用者建立時間為條件的刪除計畫使用者的佇列
     */
    public function testGenerateRmPlanUserWithUserCreatedAt()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');

        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');

        $tme = new \DateTime('20160101000000');
        $rmPlan = new RmPlan('rd5', 9, 1, $tme, null, '測試');
        $emShare->persist($rmPlan);
        $emShare->flush();

        $plan = $emShare->find('BBDurianBundle:RmPlan', 5);
        $rmPlanQueue = new RmPlanQueue($plan);
        $emShare->persist($rmPlanQueue);
        $emShare->flush();

        $user = $em->find('BBDurianBundle:User', 10);
        $cash = new Cash($user, 156);
        $em->persist($cash);

        $parent = $em->find('BBDurianBundle:User', 9);
        $fakeUser = new User();
        $fakeUser->setId(52);
        $fakeUser->setUsername('cashfake');
        $fakeUser->setParent($parent);
        $fakeUser->addSize();
        $fakeUser->setAlias('cashfake');
        $fakeUser->setPassword('cashfake');
        $fakeUser->setDomain(9);
        $fakeUser->setCreatedAt(new \DateTime('2011-1-1 11:11:11'));
        $fakeUser->setModifiedAt(new \DateTime('2011-1-1 11:12:11'));
        $fakeUser->setPasswordExpireAt(new \DateTime('2011-12-1 11:11:11'));
        $fakeUser->setRole(7);
        $em->persist($fakeUser);

        $ua = new UserAncestor($fakeUser, $parent, 1);
        $em->persist($ua);

        $fake2 = new CashFake($fakeUser, 156);
        $em->persist($fake2);
        $em->flush();

        $params = [
            '--batch-size' => 1,
            '--wait-time' => 500000
        ];

        $this->runCommand('durian:generate-rm-plan-user', $params);

        $queue = $redis->lrange('rm_plan_user_queue', 0, 10);
        $count = $redis->hget('rm_plan_5', 'count');

        $this->assertEquals(2, $count);
        $this->assertEquals('{"plan_id":5,"user_id":52}', $queue[0]);
        $this->assertEquals('{"plan_id":5,"user_id":10}', $queue[1]);
        $this->assertCount(2, $queue);

        // 檢查log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $msg = 'User 10 generate RmPlanUser successfully';
        $this->assertContains($msg, $results[0]);
        $msg = 'User 52 generate RmPlanUser successfully';
        $this->assertContains($msg, $results[1]);
    }

    /**
     * 測試產生刪除使用者的佇列，但使用者不在對應刪除層級內
     */
    public function testGenerateButUserNotInRmPlanLevel()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $em = $container->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin(new \DateTime('20130101000000'));
        $user->setCreatedAt(new \DateTime('20130101000000'));

        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(10);
        $em->flush();

        $params = [
            '--batch-size' => 1,
            '--wait-time' => 500000
        ];

        $this->runCommand('durian:generate-rm-user', $params);

        $queue = $redis->lrange('rm_user_list_queue', 0, 10);
        $count = $redis->hget('rm_plan_1', 'count');

        $this->assertEmpty($queue);
        $this->assertNull($count);
    }

    /**
     * 測試產生刪除使用者的佇列，但沒有使用者
     */
    public function testGenerateWithoutUser()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');

        $this->runCommand('durian:generate-rm-plan-user');

        $this->assertEquals(0, $redis->llen('rm_plan_user_queue'));
        $this->assertEquals(0, $redis->hget('rm_plan_1', 'count'));

        $em = $container->get('doctrine.orm.share_entity_manager');
        $plan = $em->find('BBDurianBundle:RmPlan', 1);

        $this->assertTrue($plan->isConfirm());
        $this->assertTrue($plan->isFinished());
        $this->assertEquals('沒有建立任何待刪除使用者', $plan->getMemo());
    }

    /**
     * 測試產生刪除使用者的佇列，但沒有計畫
     */
    public function testGenerateWithoutPlan()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');

        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $plan = $emShare->find('BBDurianBundle:RmPlan', 1);
        $emShare->remove($plan);
        $emShare->flush();

        $em = $container->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin(new \DateTime('20130101000000'));
        $user->setCreatedAt(new \DateTime('20130101000000'));
        $em->flush();

        $this->runCommand('durian:generate-rm-plan-user');

        $queue = $redis->lrange('rm_plan_user_queue', 0, 10);
        $count = $redis->hget('rm_plan_1', 'count');

        $this->assertEquals(0, $count);
        $this->assertEmpty($queue);
    }

    /**
     * 測試產生刪除使用者的佇列，但上層使用者不存在
     */
    public function testGenerateButParentNotFound()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');

        $em = $container->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 3);
        $em->remove($user);
        $em->flush();

        $this->runCommand('durian:generate-rm-plan-user');

        $queue = $redis->lrange('rm_plan_user_queue', 0, 10);
        $count = $redis->hget('rm_plan_1', 'count');

        $this->assertEquals(0, $count);
        $this->assertEmpty($queue);
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
