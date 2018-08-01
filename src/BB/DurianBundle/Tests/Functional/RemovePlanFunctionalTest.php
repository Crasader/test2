<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\RmPlan;
use BB\DurianBundle\Entity\RmPlanLevel;
use BB\DurianBundle\Entity\UserLevel;
use BB\DurianBundle\Entity\Level;
use Buzz\Message\Response;

class RemovePlanFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanUserExtraBalanceData'
        ];

        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->set('user_seq', 20000000);
    }

    /**
     * 測試新增刪除計畫
     */
    public function testCreatePlan()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = $em->find('BBDurianBundle:User', 10);
        $user->setLastLogin(new \DateTime('20140101000000'));

        // 建立domain 9 的層級
        $level = new Level(9, '未分層', 0, 1);
        $level->setCreatedAtStart(new \DateTime('2005-09-30 16:20:12'));
        $level->setCreatedAtEnd(new \DateTime('2035-09-30 16:20:12'));
        $em->persist($level);

        $level = new Level(9, '第一層', 0, 2);
        $level->setCreatedAtStart(new \DateTime('2005-09-30 16:20:12'));
        $level->setCreatedAtEnd(new \DateTime('2035-09-30 16:20:12'));
        $em->persist($level);

        // 建立使用者層級
        $userLevel = new UserLevel($user, 9);
        $em->persist($userLevel);

        $em->flush();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 9,
            'depth'      => 1,
            'level_id'   => [9, 10],
            'last_login' => '20150101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret']['id']);
        $this->assertEquals('a', $output['ret']['creator']);
        $this->assertNull($output['ret']['modified_at']);
        $this->assertTrue($output['ret']['untreated']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertFalse($output['ret']['finished']);
        $this->assertEquals('測試', $output['ret']['title']);
        $this->assertEquals(9, $output['ret']['level'][0]['level_id']);
        $this->assertEquals('未分層', $output['ret']['level'][0]['level_alias']);
        $this->assertEquals(10, $output['ret']['level'][1]['level_id']);
        $this->assertEquals('第一層', $output['ret']['level'][1]['level_alias']);

        // 驗證plan table是否有新增資料
        $plan = $emShare->find('BBDurianBundle:RmPlan', 5);
        $createdAt = $plan->getCreatedAt()->format(\DateTime::ISO8601);
        $this->assertEquals('a', $plan->getCreator());
        $this->assertEquals(9, $plan->getParentId());
        $this->assertEquals(1, $plan->getDepth());
        $this->assertEquals('2015-01-01T00:00:00+0800', $plan->getLastLogin()->format(\DateTime::ISO8601));
        $this->assertEquals($output['ret']['created_at'], $createdAt);
        $this->assertNull($plan->getModifiedAt());
        $this->assertTrue($plan->isUntreated());
        $this->assertFalse($plan->isConfirm());
        $this->assertFalse($plan->isCancel());
        $this->assertFalse($plan->isFinished());
        $this->assertEquals('測試', $plan->getTitle());

        // 驗證plan level是否有新增資料
        $critera = ['planId' => 5];
        $levels = $emShare->getRepository('BBDurianBundle:RmPlanLevel')->findBy($critera);

        $this->assertEquals(3, $levels[0]->getId());
        $this->assertEquals(5, $levels[0]->getPlanId());
        $this->assertEquals(9, $levels[0]->getLevelId());
        $this->assertEquals('未分層', $levels[0]->getLevelAlias());
        $this->assertEquals(4, $levels[1]->getId());
        $this->assertEquals(5, $levels[1]->getPlanId());
        $this->assertEquals(10, $levels[1]->getLevelId());
        $this->assertEquals('第一層', $levels[1]->getLevelAlias());

        // 操作紀錄檢查
        $createdAt = $plan->getCreatedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan', $logOperation->getTableName());
        $this->assertEquals('@id:5', $logOperation->getMajorKey());
        $this->assertEquals("@creator:a, @created_at:$createdAt, @title:測試", stripslashes($logOperation->getMessage()));

        $ret = $this->runCommand('durian:generate-rm-plan-user');

        $queue = json_decode($redis->rpop('rm_plan_user_queue'), true);
        $this->assertEquals(5, $queue['plan_id']);
        $this->assertEquals(10, $queue['user_id']);

        $this->assertEquals(1, $redis->hget('rm_plan_5', 'count'));
    }

    /**
     * 測試新增使用者建立時間為條件的刪除計畫，但同一廳已存在未處理的計畫
     */
    public function testCreatePlanButUntreatedPlanExistsInSameDomain()
    {
        $client = $this->createClient();
        $this->createUserHierarchy();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 52,
            'depth'      => 5,
            'created_at' => '20160101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 20000001,
            'depth'      => 4,
            'created_at' => '20160101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630015, $output['code']);
        $this->assertEquals('Cannot create plan when untreated plan exists', $output['msg']);
    }

    /**
     * 測試新增使用者建立時間為條件的刪除計畫，但同一廳已存確認但尚未完成的計畫
     */
    public function testCreatePlanButConfirmPlanExistsInSameDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $this->createUserHierarchy();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 52,
            'depth'      => 5,
            'created_at' => '20160101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $plan = $em->find('BBDurianBundle:RmPlan', 5);
        $plan->confirm();

        $em->flush();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 20000001,
            'depth'      => 4,
            'created_at' => '20160101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630016, $output['code']);
        $this->assertEquals('Cannot create plan when confirm plan exists', $output['msg']);
    }

    /**
     * 測試使用使用者建立時間新增刪除計畫
     */
    public function testCreatePlanWithCreatedAt()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $this->createUserHierarchy();

        $user1 = $em->find('BBDurianBundle:User', 20000005);
        $user1->setCreatedAt(new \DateTime('2015-09-30 16:20:12'));
        $em->persist($user1);

        $user2 = $em->find('BBDurianBundle:User', 20000006);
        $user2->setCreatedAt(new \DateTime('2015-09-30 16:20:12'));
        $em->persist($user2);

        $user3 = $em->find('BBDurianBundle:User', 20000007);
        $user3->setCreatedAt(new \DateTime('2015-09-30 16:20:12'));
        $em->persist($user3);

        $em->flush();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 52,
            'depth'      => 5,
            'created_at' => '20160101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret']['id']);
        $this->assertEquals('a', $output['ret']['creator']);
        $this->assertNull($output['ret']['modified_at']);
        $this->assertTrue($output['ret']['untreated']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertFalse($output['ret']['finished']);
        $this->assertEquals('測試', $output['ret']['title']);

        // 驗證plan table是否有新增資料
        $plan = $emShare->find('BBDurianBundle:RmPlan', 5);
        $createdAt = $plan->getCreatedAt()->format(\DateTime::ISO8601);
        $this->assertEquals('a', $plan->getCreator());
        $this->assertEquals(52, $plan->getParentId());
        $this->assertEquals(5, $plan->getDepth());
        $this->assertEquals('2016-01-01T00:00:00+0800', $plan->getUserCreatedAt()->format(\DateTime::ISO8601));
        $this->assertEquals($output['ret']['created_at'], $createdAt);
        $this->assertNull($plan->getModifiedAt());
        $this->assertTrue($plan->isUntreated());
        $this->assertFalse($plan->isConfirm());
        $this->assertFalse($plan->isCancel());
        $this->assertFalse($plan->isFinished());
        $this->assertEquals('測試', $plan->getTitle());

        $planQueue = $emShare->find('BBDurianBundle:RmPlanQueue', 5);
        $this->assertEquals(5, $planQueue->getPlanId());

        $ret = $this->runCommand('durian:generate-rm-plan-user');

        $queue = json_decode($redis->rpop('rm_plan_user_queue'), true);
        $this->assertEquals(5, $queue['plan_id']);
        $this->assertEquals(20000005, $queue['user_id']);

        $queue = json_decode($redis->rpop('rm_plan_user_queue'), true);
        $this->assertEquals(5, $queue['plan_id']);
        $this->assertEquals(20000006, $queue['user_id']);

        $queue = json_decode($redis->rpop('rm_plan_user_queue'), true);
        $this->assertEquals(5, $queue['plan_id']);
        $this->assertEquals(20000007, $queue['user_id']);

        $this->assertEquals(3, $redis->hget('rm_plan_5', 'count'));
    }

    /**
     * 測試新增刪除計畫，帶入不存在的層級
     */
    public function testCreatePlanWithNotExistsLevel()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 10);
        $user->setLastLogin(new \DateTime('20140101000000'));
        $em->flush();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 9,
            'depth'      => 1,
            'level_id'   => [1, 10],
            'last_login' => '20150101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630017, $output['code']);
        $this->assertEquals('No such level', $output['msg']);
    }

    /**
     * 測試新增刪除計畫，當最後登入時間為空
     */
    public function testCreatePlanWhenLastLoginIsNull()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 檢查最後登入時間為空，並且建立時間小於需求的最後登入時間
        $user = $em->find('BBDurianBundle:User', 10);
        $this->assertNull($user->getLastLogin());
        $this->assertLessThan(20150101000000, $user->getCreatedAt()->format('YmdHis'));

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 9,
            'depth'      => 1,
            'last_login' => '20150101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret']['id']);
        $this->assertEquals('a', $output['ret']['creator']);
        $this->assertNull($output['ret']['modified_at']);
        $this->assertTrue($output['ret']['untreated']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertFalse($output['ret']['finished']);
        $this->assertEquals('測試', $output['ret']['title']);

        // 驗證plan table是否有新增資料
        $plan = $emShare->find('BBDurianBundle:RmPlan', 5);
        $createdAt = $plan->getCreatedAt()->format(\DateTime::ISO8601);
        $this->assertEquals('a', $plan->getCreator());
        $this->assertEquals(9, $plan->getParentId());
        $this->assertEquals(1, $plan->getDepth());
        $this->assertEquals('2015-01-01T00:00:00+0800', $plan->getLastLogin()->format(\DateTime::ISO8601));
        $this->assertEquals($output['ret']['created_at'], $createdAt);
        $this->assertNull($plan->getModifiedAt());
        $this->assertTrue($plan->isUntreated());
        $this->assertFalse($plan->isConfirm());
        $this->assertFalse($plan->isCancel());
        $this->assertFalse($plan->isFinished());
        $this->assertEquals('測試', $plan->getTitle());

        // 操作紀錄檢查
        $createdAt = $plan->getCreatedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan', $logOperation->getTableName());
        $this->assertEquals('@id:5', $logOperation->getMajorKey());
        $this->assertEquals("@creator:a, @created_at:$createdAt, @title:測試", stripslashes($logOperation->getMessage()));

        $ret = $this->runCommand('durian:generate-rm-plan-user');

        $queue = json_decode($redis->rpop('rm_plan_user_queue'), true);
        $this->assertEquals(5, $queue['plan_id']);
        $this->assertEquals(10, $queue['user_id']);
    }

    /**
     * 測試新增刪除計畫，刪除的帳號有hidden_test的使用者
     */
    public function testCreatePlanContainsHiddenTestUsers()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將該使用者hidden_test設為true
        $user = $em->find('BBDurianBundle:User', 10);
        $user->setHiddenTest(true);
        $em->flush();

        $this->assertLessThan(20150101000000, $user->getCreatedAt()->format('YmdHis'));

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 9,
            'depth'      => 1,
            'last_login' => '20150101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 驗證沒有將hidden_test的使用者寫入redis
        $this->assertEmpty($redis->llen('rm_plan_user_queue'));
    }

    /**
     * 測試新增刪除計畫，帶入不存在的parentId
     */
    public function testCreatePlanButNoSuchParent()
    {
        $client = $this->createClient();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 99,
            'depth'      => 5,
            'last_login' => '20150101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630007, $output['code']);
        $this->assertEquals('No parent found', $output['msg']);
    }

    /**
     * 測試新增刪除計畫，但已存在未處理的計畫
     */
    public function testCreatePlanButUntreatedPlanExists()
    {
        $client = $this->createClient();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 3,
            'depth'      => 5,
            'last_login' => '20150101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630015, $output['code']);
        $this->assertEquals('Cannot create plan when untreated plan exists', $output['msg']);
    }

    /**
     * 測試新增刪除計畫，但存在已確認的計畫
     */
    public function testCreatePlanButConfirmPlanExists()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $plan->confirm();
        $em->flush();

        $parameters = [
            'creator'    => 'a',
            'parent_id'  => 3,
            'depth'      => 5,
            'last_login' => '20150101000000',
            'title'      => '測試'
        ];

        $client->request('POST', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630016, $output['code']);
        $this->assertEquals('Cannot create plan when confirm plan exists', $output['msg']);
    }

    /**
     * 測試撤銷刪除使用者
     */
    public function testCancelPlanUser()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = ['users' => [51]];
        $client->request('PUT', '/api/remove_plan/1/user/cancel', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['plan_id']);
        $this->assertEquals(51, $output['ret'][0]['user_id']);
        $this->assertFalse($output['ret'][0]['remove']);
        $this->assertTrue($output['ret'][0]['cancel']);
        $this->assertEquals(0, $output['ret'][0]['ab_balance']);
        $this->assertEquals(1, $output['ret'][0]['sabah_balance']);

        // 驗證刪除使用者
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 1);
        $modifiedAt = $rpUser->getModifiedAt()->format(\DateTime::ISO8601);
        $this->assertTrue($rpUser->isCancel());
        $this->assertEquals($output['ret'][0]['modified_at'], $modifiedAt);

        // 驗證當計劃內所有使用者都撤銷時，計畫是否撤銷
        $rPlan = $emShare->find('BBDurianBundle:RmPlan', 1);
        $this->assertTrue($rPlan->isCancel());

        // 操作紀錄檢查
        $modifiedAt = $rPlan->getModifiedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals("@untreated:true=>false, @cancel:false=>true, @modifiedAt:$modifiedAt", stripslashes($logOperation->getMessage()));

        $modifiedAt = $rpUser->getModifiedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remove_plan_user', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals("@cancel:false=>true, @modifiedAt:$modifiedAt", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試撤銷刪除使用者，但計畫不存在
     */
    public function testCancelPlanUserWithPlanNotExist()
    {
        $client = $this->createClient();

        $parameters = ['users' => [51]];
        $client->request('PUT', '/api/remove_plan/100/user/cancel', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630011, $output['code']);
        $this->assertEquals('No removePlan found', $output['msg']);
    }

    /**
     * 測試撤銷刪除使用者，但Connection Timed Out
     */
    public function testCancelPlanUserButConnectionTimedOut()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $rpuRepo = $em->getRepository('BBDurianBundle:RmPlanUser');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getRepository', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($plan));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($rpuRepo));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);

        $parameters = ['users' => [51]];
        $client->request('PUT', '/api/remove_plan/1/user/cancel', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Connection timed out', $output['msg']);
    }

    /**
     * 測試撤銷刪除使用者，但使用者已經是撤銷刪除的狀態
     */
    public function testCancelPlanUserButHasBeenCancelled()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $rpUser = $em->find('BBDurianBundle:RmPlanUser', 1);
        $rpUser->cancel();

        $em->flush();

        $parameters = ['users' => [51]];
        $client->request('PUT', '/api/remove_plan/1/user/cancel', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630013, $output['code']);
        $this->assertEquals('This user has been cancelled', $output['msg']);
    }

    /**
     * 測試撤銷刪除使用者，但使用者不存在
     */
    public function testCancelPlanUserButNoUserFound()
    {
        $client = $this->createClient();

        $parameters = ['users' => [99]];
        $client->request('PUT', '/api/remove_plan/1/user/cancel', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630008, $output['code']);
        $this->assertEquals('No removePlanUser found', $output['msg']);
    }

    /**
     * 測試撤銷刪除使用者，但計畫已被確認
     */
    public function testCancelPlanUserButPlanConfirmed()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $plan->confirm();
        $em->flush();

        $parameters = ['users' => [51]];
        $client->request('PUT', '/api/remove_plan/1/user/cancel', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630009, $output['code']);
        $this->assertEquals('This plan has been confirmed', $output['msg']);
    }

    /**
     * 測試撤銷刪除使用者，但計畫已被撤銷
     */
    public function testCancelPlanUserButPlanCancelled()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $plan->cancel();
        $em->flush();

        $parameters = ['users' => [51]];
        $client->request('PUT', '/api/remove_plan/1/user/cancel', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630010, $output['code']);
        $this->assertEquals('This plan has been cancelled', $output['msg']);
    }

    /**
     * 測試撤銷刪除使用者計畫
     */
    public function testCancelPlan()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $rPlan = $emShare->find('BBDurianBundle:RmPlan', 1);
        $rPlan->queueDone();
        $emShare->flush();

        $redis->hset('rm_plan_1', 'count', 1);

        $client->request('PUT', '/api/remove_plan/1/cancel');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('engineer1', $output['ret']['creator']);
        $this->assertFalse($output['ret']['untreated']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertTrue($output['ret']['cancel']);
        $this->assertFalse($output['ret']['finished']);
        $this->assertEquals('測試1', $output['ret']['title']);
        $this->assertEquals(1, $output['ret']['level'][0]['level_id']);
        $this->assertEquals('未分層', $output['ret']['level'][0]['level_alias']);
        $this->assertEquals(2, $output['ret']['level'][1]['level_id']);
        $this->assertEquals('第一層', $output['ret']['level'][1]['level_alias']);

        // 驗證刪除使用者計畫與使用者皆撤銷
        $emShare->refresh($rPlan);
        $modifiedAt = $rPlan->getModifiedAt()->format(\DateTime::ISO8601);

        $this->assertTrue($rPlan->isCancel());
        $this->assertEquals($output['ret']['modified_at'], $modifiedAt);

        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 1);
        $this->assertTrue($rpUser->isCancel());

        $this->assertEquals(1, $redis->hget('rm_plan_1', 'cancel'));

        // 操作紀錄檢查
        $modifiedAt = $rPlan->getModifiedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals("@untreated:true=>false, @cancel:false=>true, @modifiedAt:$modifiedAt", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試撤銷刪除使用者計畫，但Connection Timed Out
     */
    public function testCancelPlanButConnectionTimedOut()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $rpuRepo = $em->getRepository('BBDurianBundle:RmPlanUser');
        $plan->queueDone();
        $em->flush();

        $em->refresh($plan);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getRepository', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($plan));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($rpuRepo));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);

        $client->request('PUT', '/api/remove_plan/1/cancel');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Connection timed out', $output['msg']);
    }

    /**
     * 測試撤銷刪除使用者計畫例外
     */
    public function testCancelPlanException()
    {
        $client = $this->createClient();

        // 帶入不存在的申請單編號
        $client->request('PUT', '/api/remove_plan/10/cancel');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630011, $output['code']);
        $this->assertEquals('No removePlan found', $output['msg']);

        // 帶入已確認的申請單編號
        $client->request('PUT', '/api/remove_plan/2/cancel');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630009, $output['code']);
        $this->assertEquals('This plan has been confirmed', $output['msg']);

        // 帶入已撤銷的申請單編號
        $client->request('PUT', '/api/remove_plan/3/cancel');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630010, $output['code']);
        $this->assertEquals('This plan has been cancelled', $output['msg']);

        // Queue尚未建立完成
        $client->request('PUT', '/api/remove_plan/1/cancel');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630021, $output['code']);
        $this->assertEquals('This plan queue is not done', $output['msg']);
    }

    /**
     * 測試確認通過刪除計畫
     */
    public function testConfirmPlan()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/remove_plan/1/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('engineer1', $output['ret']['creator']);
        $this->assertFalse($output['ret']['untreated']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertFalse($output['ret']['finished']);
        $this->assertEquals('測試1', $output['ret']['title']);
        $this->assertEquals(1, $output['ret']['level'][0]['level_id']);
        $this->assertEquals('未分層', $output['ret']['level'][0]['level_alias']);
        $this->assertEquals(2, $output['ret']['level'][1]['level_id']);
        $this->assertEquals('第一層', $output['ret']['level'][1]['level_alias']);

        // 驗證申請單
        $rPlan = $emShare->find('BBDurianBundle:RmPlan', 1);
        $modifiedAt = $rPlan->getModifiedAt();
        $finishAt = clone $modifiedAt;
        $finishAt->add(new \DateInterval('P14D'));

        $this->assertTrue($rPlan->isConfirm());
        $this->assertEquals($output['ret']['modified_at'], $modifiedAt->format(\DateTime::ISO8601));
        $this->assertEquals($output['ret']['finish_at'], $finishAt->format(\DateTime::ISO8601));

        // 操作紀錄檢查
        $modifiedAt = $rPlan->getModifiedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals("@untreated:true=>false, @confirm:false=>true, @modifiedAt:$modifiedAt", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試確認刪除計畫例外
     */
    public function testConfirmPlanException()
    {
        $client = $this->createClient();

        // 帶入不存在的申請單編號
        $client->request('PUT', '/api/remove_plan/10/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630011, $output['code']);
        $this->assertEquals('No removePlan found', $output['msg']);

        // 帶入已確認的申請單編號
        $client->request('PUT', '/api/remove_plan/2/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630009, $output['code']);
        $this->assertEquals('This plan has been confirmed', $output['msg']);

        // 帶入已撤銷的申請單編號
        $client->request('PUT', '/api/remove_plan/3/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630010, $output['code']);
        $this->assertEquals('This plan has been cancelled', $output['msg']);
    }

    /**
     * 測試取得刪除使用者
     */
    public function testGetPlanUser()
    {
        $client = $this->createClient();

        $parameters = [
            'sort'         => ['id'],
            'order'        => ['asc'],
            'first_result' => 0,
            'max_results'  => 20
        ];
        $client->request('GET', '/api/remove_plan/1/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['plan_id']);
        $this->assertEquals(51, $output['ret'][0]['user_id']);
        $this->assertNull($output['ret'][0]['modified_at']);
        $this->assertFalse($output['ret'][0]['remove']);
        $this->assertFalse($output['ret'][0]['cancel']);
        $this->assertEmpty($output['ret'][0]['memo']);
        $this->assertEquals(0, $output['ret'][0]['ab_balance']);
        $this->assertEquals(1, $output['ret'][0]['sabah_balance']);

        $this->assertEquals(1, $output['sub_total']['untreated']);
        $this->assertEquals(0, $output['sub_total']['remove']);
        $this->assertEquals(0, $output['sub_total']['cancel']);
        $this->assertEquals(0, $output['sub_total']['recover_fail']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(20, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 測試取得空的刪除使用者
        $client->request('GET', '/api/remove_plan/4/user');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得申請刪除使用者例外
     */
    public function testGetPlanUserException()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remove_plan/10/user');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630011, $output['code']);
        $this->assertEquals('No removePlan found', $output['msg']);
    }

    /**
     * 測試取得刪除計畫
     */
    public function testGetPlan()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $createdAt = $plan->getCreatedAt()->format(\DateTime::ISO8601);
        $lastLogin = $plan->getLastLogin()->format(\DateTime::ISO8601);

        $parameters = [
            'plan_id'      => 1,
            'parent_id'    => 3,
            'depth'        => 5,
            'level_id'     => 1,
            'last_login'   => $lastLogin,
            'creator'      => 'engineer1',
            'untreated'    => 1,
            'user_created' => 0,
            'confirm'      => 0,
            'cancel'       => 0,
            'finished'     => 0,
            'sort'         => ['id'],
            'order'        => ['asc'],
            'first_result' => 0,
            'max_results'  => 20
        ];

        $client->request('GET', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][0]['parent_id']);
        $this->assertEquals(5, $output['ret'][0]['depth']);
        $this->assertEquals($lastLogin, $output['ret'][0]['last_login']);
        $this->assertEquals('engineer1', $output['ret'][0]['creator']);
        $this->assertEquals($createdAt, $output['ret'][0]['created_at']);
        $this->assertNull($output['ret'][0]['modified_at']);
        $this->assertTrue($output['ret'][0]['untreated']);
        $this->assertFalse($output['ret'][0]['user_created']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['cancel']);
        $this->assertFalse($output['ret'][0]['finished']);
        $this->assertEquals('測試1', $output['ret'][0]['title']);
        $this->assertEquals(1, $output['ret'][0]['level'][0]['level_id']);
        $this->assertEquals('未分層', $output['ret'][0]['level'][0]['level_alias']);
        $this->assertEquals(2, $output['ret'][0]['level'][1]['level_id']);
        $this->assertEquals('第一層', $output['ret'][0]['level'][1]['level_alias']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(20, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 測試查無刪除計畫
        $parameters = ['plan_id' => 10];

        $client->request('GET', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEmpty($output['ret']);

        //測試用使用者建立時間當條件搜尋刪除計畫
        $parameters = ['created_at' => '2015-01-01 00:00:00'];

        $client->request('GET', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得刪除計畫，levle_id帶空字串
     */
    public function testGetPlanAndLevelIdIsEmptyString()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $lastLogin = $plan->getLastLogin()->format(\DateTime::ISO8601);

        $parameters = [
            'plan_id'      => 1,
            'parent_id'    => 3,
            'depth'        => 5,
            'level_id'     => '',
            'last_login'   => $lastLogin,
            'creator'      => 'engineer1',
            'untreated'    => 1,
            'user_created' => 0,
            'confirm'      => 0,
            'cancel'       => 0,
            'finished'     => 0,
            'sort'         => ['id'],
            'order'        => ['asc'],
            'first_result' => 0,
            'max_results'  => 20
        ];

        $client->request('GET', '/api/remove_plan', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試檢查刪除使用者計畫是否完成
     */
    public function testCheckPlanFinishByPlanUser()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $plan = $em->find('BBDurianBundle:RmPlan', 1);
        $plan->confirm();
        $plan->queueDone();

        $em->flush();

        $parameters = ['plan_user_id' => 1];

        $client->request('GET', '/api/remove_plan/check_finish', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['plan_id']);
        $this->assertFalse($output['ret']['finish']);
    }

    /**
     * 測試檢查刪除使用者計畫是否完成，使用者不存在
     */
    public function testCheckPlanFinishUserNotExist()
    {
        $client = $this->createClient();

        $parameters = ['plan_user_id' => 3];

        $client->request('GET', '/api/remove_plan/check_finish', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630008, $output['code']);
        $this->assertEquals('No removePlanUser found', $output['msg']);
    }

    /**
     * 測試完成刪除使用者計畫
     */
    public function testFinishPlan()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/remove_plan/2/finish');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals('engineer2', $output['ret']['creator']);
        $this->assertEquals(3, $output['ret']['parent_id']);
        $this->assertFalse($output['ret']['untreated']);
        $this->assertFalse($output['ret']['user_created']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertTrue($output['ret']['finished']);

        // 操作紀錄檢查
        $rPlan = $emShare->find('BBDurianBundle:RmPlan', 2);
        $finishAt = $rPlan->getFinishAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals("@finished:false=>true, @finishAt:$finishAt", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試完成刪除使用者計畫，計畫不存在
     */
    public function testFinishPlanButPlanNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remove_plan/10/finish');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630011, $output['code']);
        $this->assertEquals('No removePlan found', $output['msg']);
    }

    /**
     * 測試完成刪除使用者計畫，計畫已完成
     */
    public function testFinishPlanButPlanFinished()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remove_plan/4/finish');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630019, $output['code']);
        $this->assertEquals('This plan has been finished', $output['msg']);
    }

    /**
     * 測試完成刪除使用者計畫，計畫已取消
     */
    public function testFinishPlanButPlanCancelled()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remove_plan/3/finish');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630010, $output['code']);
        $this->assertEquals('This plan has been cancelled', $output['msg']);
    }

    /**
     * 測試完成刪除使用者計畫，計畫尚未通過
     */
    public function testFinishPlanButPlanNotConfirmed()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remove_plan/1/finish');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630020, $output['code']);
        $this->assertEquals('This plan has not been confirmed', $output['msg']);
    }

    /**
     * 測試完成刪除使用者計畫，計畫佇列尚未產生完成
     */
    public function testFinishPlanButPlanQueueNotDone()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $rPlan = $em->find('BBDurianBundle:RmPlan', 1);
        $rPlan->confirm();

        $em->flush();

        $client->request('PUT', '/api/remove_plan/1/finish');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150630021, $output['code']);
        $this->assertEquals('This plan queue is not done', $output['msg']);
    }

    /**
     * 建立使用者
     */
    public function createUserHierarchy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'role' => 7,
            'login_code' => 'bab',
            'username' => 'testremove7',
            'password' => 'testremove7',
            'alias' => 'testremove7',
            'name' => 'testremove7',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameter = [
            'domain' => 52,
            'alias' => '未分層',
            'order_strategy' => 0,
            'created_at_start' => '2015-10-13 00:00:00',
            'created_at_end' => '2015-10-13 00:00:00',
            'deposit_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 1000,
            'withdraw_count' => 0,
            'withdraw_total' => 0
        ];

        $client->request('POST', '/api/level', $parameter);

        $parameters = ['level_id' => 9];
        $client->request('POST', '/api/user/52/preset_level', $parameters);

        $parameters = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'testremove5',
            'password' => 'testremove5',
            'alias' => 'testremove5',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'testremove4',
            'password' => 'testremove4',
            'alias' => 'testremove4',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000002,
            'role' => 3,
            'username' => 'testremove3',
            'password' => 'testremove3',
            'alias' => 'testremove3',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000003,
            'role' => 2,
            'username' => 'testremove2',
            'password' => 'testremove2',
            'alias' => 'testremove2',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'testremove1',
            'password' => 'testremove1',
            'alias' => 'testremove1',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'testremove11',
            'password' => 'testremove11',
            'alias' => 'testremove11',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        $parameters = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'testremove12',
            'password' => 'testremove12',
            'alias' => 'testremove12',
            'cash_fake' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);
    }
}
