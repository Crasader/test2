<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\Reward;
use BB\DurianBundle\Entity\Cash;

class RewardFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRewardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRewardEntryData'
        ];

        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.reward');
        $redis->flushdb();

        // 新增一筆session資料，幣別為人民幣
        $broker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 8);
        $cash = new Cash($user, 156);
        $em->persist($cash);
        $em->flush();

        $sessionId = $broker->create($user);

        $this->headerParam = ['HTTP_SESSION_ID' => $sessionId];
    }

    /**
     * 測試建立抽紅包活動
     */
    public function testCreateReward()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end,
            'memo' => 'test'
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $reward = $emShare->find('BBDurianBundle:Reward', $output['ret']['id']);

        $this->assertEquals($reward->getId(), $output['ret']['id']);
        $this->assertEquals($reward->getName(), $output['ret']['name']);
        $this->assertEquals($reward->getDomain(), $output['ret']['domain']);
        $this->assertEquals($reward->getAmount(), $output['ret']['amount']);
        $this->assertEquals($reward->getQuantity(), $output['ret']['quantity']);
        $this->assertEquals($reward->getMinAmount(), $output['ret']['min_amount']);
        $this->assertEquals($reward->getMaxAmount(), $output['ret']['max_amount']);
        $this->assertEquals($reward->getBeginAt()->format(\DateTime::ISO8601), $output['ret']['begin_at']);
        $this->assertEquals($reward->getEndAt()->format(\DateTime::ISO8601), $output['ret']['end_at']);
        $this->assertEquals($reward->isEntryCreated(), $output['ret']['entry_created']);
        $this->assertEquals($reward->getMemo(), $output['ret']['memo']);

        $redisReward = $redis->hgetall('reward_id_4');

        $this->assertEquals($redisReward['name'], $output['ret']['name']);
        $this->assertEquals($redisReward['domain'], $output['ret']['domain']);
        $this->assertEquals($redisReward['amount'], $output['ret']['amount']);
        $this->assertEquals($redisReward['quantity'], $output['ret']['quantity']);
        $this->assertEquals($redisReward['min_amount'], $output['ret']['min_amount']);
        $this->assertEquals($redisReward['max_amount'], $output['ret']['max_amount']);
        $this->assertEquals($redisReward['begin_at'], $reward->getBeginAt()->format(\DateTime::ISO8601));
        $this->assertEquals($redisReward['end_at'], $reward->getEndAt()->format(\DateTime::ISO8601));
        $this->assertEquals(0, $redisReward['obtain_amount']);
        $this->assertEquals(0, $redisReward['obtain_quantity']);
        $this->assertEmpty($redisReward['entry_created']);
        $this->assertEmpty($redisReward['cancel']);
        $this->assertEquals('test', $redisReward['memo']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('reward', $logOperation->getTableName());
        $this->assertEquals('@id:4', $logOperation->getMajorKey());
        $msg = "@name:test, @domain:2, @amount:100, @quantity:10, @min_amount:5, @max_amount:20, @memo:test";
        $this->assertEquals($msg, stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試建立抽紅包活動，但沒有該domain
     */
    public function testCreateRewardButNoSuchDomain()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760011, $output['code']);
        $this->assertEquals('No such domain', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試建立抽紅包活動，domain有parent
     */
    public function testCreateRewardButDomainHasParent()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 3,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760011, $output['code']);
        $this->assertEquals('No such domain', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試建立抽紅包活動，但domain 不支援 cash
     */
    public function testCreateRewardButDomainNotSupportCash()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        // 將domain的現金disable
        $payway = $em->find('BBDurianBundle:UserPayway', 2);
        $payway->disableCash();
        $em->persist($payway);
        $em->flush();

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760012, $output['code']);
        $this->assertEquals('Domain not support cash', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試取消紅包活動
     */
    public function testCancelReward()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        // 新增活動
        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);

        $client->request('PUT', '/api/reward/4/cancel');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);
        $this->assertTrue($output['ret']['cancel']);

        $reward = $emShare->find('BBDurianBundle:Reward', 4);
        $this->assertTrue($reward->isCancel());

        // redis 相關資料有刪除
        $this->assertEmpty($redis->exists('reward_id_4'));
        $this->assertEmpty($redis->exists('reward_id_4_attended_user'));
        $this->assertEmpty($redis->sismember('reward_available', 4));
        $this->assertFalse(in_array(4, $redis->lrange('reward_entry_created_queue', 0, -1)));

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('reward', $logOperation->getTableName());
        $this->assertEquals('@id:4', $logOperation->getMajorKey());
        $this->assertEquals("@cancel:false=>true", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試取消紅包活動，但沒有該活動
     */
    public function testCancelRewardButNoSuchReward()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $json = $client->request('PUT', '/api/reward/99/cancel');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760015, $output['code']);
        $this->assertEquals('No such reward', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試取消紅包活動，但活動已取消
     */
    public function testCancelRewardButRewardHasBeenCancel()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $reward->cancel();
        $emShare->flush();

        $client->request('PUT', '/api/reward/1/cancel');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760023, $output['code']);
        $this->assertEquals('Reward has been cancelled', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOperation);
    }

    /**
     * 測試取消紅包活動，但活動已開始
     */
    public function testCancelRewardButRewardIsBegin()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $now = new \DateTime('now');
        $start = $now->sub(new \DateInterval('P1D'))->format(\DateTime::ISO8601);
        $end = $now->add(new \DateInterval('P5D'))->format(\DateTime::ISO8601);

        $reward = new Reward('test', 2, 10, 2, 1, 6, $start, $end);
        $emShare->persist($reward);
        $emShare->flush();

        $client->request('PUT', '/api/reward/4/cancel');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760026, $output['code']);
        $this->assertEquals('Can not cancel when reward start', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOperation);
    }

    /**
     * 測試取消紅包活動，但活動已結束
     */
    public function testCancelRewardButRewardIsEnd()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $time = new \DateTime('2000-01-01 00:00:00');
        $start = $time->format(\DateTime::ISO8601);
        $end = $time->add(new \DateInterval('P4D'))->format(\DateTime::ISO8601);

        $reward = new Reward('test', 2, 10, 2, 1, 6, $start, $end);
        $emShare->persist($reward);
        $emShare->flush();

        $client->request('PUT', '/api/reward/4/cancel');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760032, $output['code']);
        $this->assertEquals('Past reward cannot be cancelled', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOperation);
    }

    /**
     * 測試取消紅包活動，發生timeout
     */
    public function testCancelRewardWithConnectionTimeout()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 新增活動
        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'persist','flush', 'rollback', 'clear', 'getConnection'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($emShare->find('BBDurianBundle:Reward', 4)));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);

        $client->request('PUT', '/api/reward/4/cancel');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Connection timed out', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOperation);
    }

    /**
     * 測試取得使用者可以參加的活動
     */
    public function testGetAvailableReward()
    {
       $redis = $this->getContainer()->get('snc_redis.reward');
       $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
       $client = $this->createClient();

        // redis新增進行中與非進行中的reward
        $reward1 = $emShare->find('BBDurianBundle:Reward', 1);
        $reward2 = $emShare->find('BBDurianBundle:Reward', 2);

        $redis->hmset('reward_id_1', $reward1->toArray());
        $redis->hmset('reward_id_2', $reward2->toArray());
        $redis->sadd('reward_available', 1);
        $redis->sadd('reward_available', 2);

        $param = [
            'domain' => 2,
            'user_id' => 1
        ];

        $client->request('GET', '/api/reward/available', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals($reward2->getId(), $output['ret'][0]['id']);
        $this->assertEquals($reward2->getName(), $output['ret'][0]['name']);
        $this->assertEquals($reward2->getDomain(), $output['ret'][0]['domain']);
        $this->assertEquals($reward2->getAmount(), $output['ret'][0]['amount']);
        $this->assertEquals($reward2->getQuantity(), $output['ret'][0]['quantity']);
        $this->assertEquals($reward2->getMinAmount(), $output['ret'][0]['min_amount']);
        $this->assertEquals($reward2->getMaxAmount(), $output['ret'][0]['max_amount']);
        $this->assertEquals($reward2->isEntryCreated(), $output['ret'][0]['entry_created']);
        $this->assertEquals($reward2->getMemo(), $output['ret'][0]['memo']);
        $this->assertFalse($output['ret'][0]['attended']);

        // 有把非進行中的活動刪掉
        $this->assertEquals(0, $redis->sismember('reward_available', 1));
    }

    /**
     * 測試取得使用者可以參加的活動，使用者參加過該活動
     */
    public function testGetAvailableRewardUserAttendedReward()
    {
       $redis = $this->getContainer()->get('snc_redis.reward');
       $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
       $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);

        $redis->hmset('reward_id_2', $reward->toArray());
        $redis->sadd('reward_available', 2);
        $redis->sadd('reward_id_2_attended_user', 1);

        $param = [
            'domain' => 2,
            'user_id' => 1
        ];

        $client->request('GET', '/api/reward/available', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals($reward->getId(), $output['ret'][0]['id']);
        $this->assertEquals($reward->getName(), $output['ret'][0]['name']);
        $this->assertEquals($reward->getDomain(), $output['ret'][0]['domain']);
        $this->assertEquals($reward->getAmount(), $output['ret'][0]['amount']);
        $this->assertEquals($reward->getQuantity(), $output['ret'][0]['quantity']);
        $this->assertEquals($reward->getMinAmount(), $output['ret'][0]['min_amount']);
        $this->assertEquals($reward->getMaxAmount(), $output['ret'][0]['max_amount']);
        $this->assertEquals($reward->isEntryCreated(), $output['ret'][0]['entry_created']);
        $this->assertEquals($reward->getMemo(), $output['ret'][0]['memo']);
        $this->assertTrue($output['ret'][0]['attended']);
    }

    /**
     * 測試取得使用者可以參加的活動，沒有相符的活動
     */
    public function testGetAvailableRewardButNoAvailableReward()
    {
       $redis = $this->getContainer()->get('snc_redis.reward');
       $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
       $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);

        $redis->hmset('reward_id_2', $reward->toArray());
        $redis->sadd('reward_available', 2);
        $redis->sadd('reward_id_2_attended_user', 1);

        $param = [
            'domain' => 3,
            'user_id' => 1
        ];

        $client->request('GET', '/api/reward/available', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得進行中的活動
     */
    public function testGetActiveReward()
    {
       $redis = $this->getContainer()->get('snc_redis.reward');
       $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
       $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);

        $redis->hmset('reward_id_2', $reward->toArray());
        $redis->sadd('reward_available', 2);

        $client->request('GET', '/api/reward/2/active');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($reward->getId(), $output['ret']['id']);
        $this->assertEquals($reward->getName(), $output['ret']['name']);
        $this->assertEquals($reward->getDomain(), $output['ret']['domain']);
        $this->assertEquals($reward->getAmount(), $output['ret']['amount']);
        $this->assertEquals($reward->getQuantity(), $output['ret']['quantity']);
        $this->assertEquals($reward->getMinAmount(), $output['ret']['min_amount']);
        $this->assertEquals($reward->getMaxAmount(), $output['ret']['max_amount']);
        $this->assertEquals($reward->isEntryCreated(), $output['ret']['entry_created']);
        $this->assertEquals($reward->getMemo(), $output['ret']['memo']);
    }

    /**
     * 測試取得進行中的活動，沒有該活動
     */
    public function testGetActiveRewardButNoSuchReward()
    {
       $client = $this->createClient();

        $client->request('GET', '/api/reward/1/active');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760027, $output['code']);
        $this->assertEquals('No such reward or not in active time', $output['msg']);
    }

    /**
     * 測試取得進行中的活動，活動已結束
     */
    public function testGetActiveRewardButRewardIsEnd()
    {
       $redis = $this->getContainer()->get('snc_redis.reward');
       $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
       $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 1);

        $redis->hmset('reward_id_1', $reward->toArray());

        $client->request('GET', '/api/reward/1/active');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760027, $output['code']);
        $this->assertEquals('No such reward or not in active time', $output['msg']);
    }

    /**
     * 測試搶紅包
     */
    public function testObtainReward()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();


        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $redis->hmset('reward_id_2', $reward->toArray());

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $entryData = [
            'id' => $entry->getId(),
            'amount' => $entry->getAmount()
        ];
        $redis->sadd('reward_id_2_entry', json_encode($entryData));

        $param = [
            'reward_id' => 2,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['reward_id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(10, $output['ret']['amount']);
        $this->assertNotNull($output['ret']['obtain_at']);

        //驗證redis
        $reward = $redis->hgetall('reward_id_2');
        $this->assertEquals(20, $reward['obtain_amount']);
        $this->assertEquals(2, $reward['obtain_quantity']);
        $this->assertTrue($redis->sismember('reward_id_2_attended_user', 8));
        $this->assertTrue($redis->exists('reward_sync_queue'));
        $this->assertTrue($redis->exists('reward_op_queue'));
        $this->assertFalse($redis->exists('reward_id_2_entry'));
    }

    /**
     * 測試搶紅包，沒有該活動
     */
    public function testObtainRewardButNoSuchReweard()
    {
        $client = $this->createClient();

        $param = [
            'reward_id' => 999,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760015, $output['code']);
        $this->assertEquals('No such reward', $output['msg']);
    }

    /**
     * 測試搶紅包，不在活動期間
     */
    public function testObtainRewardButNotInActiveTime()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // redis新增活動，將開始時間修改為現在之後
        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $redisReward = $reward->toArray();

        $now = new \DateTime('now');
        $beginAt = $now->add(new \DateInterval('P1D'))->format(\DateTime::ISO8601);

        $redisReward['begin_at'] = $beginAt;
        $redis->hmset('reward_id_1', $redisReward);

        $param = [
            'reward_id' => 1,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760016, $output['code']);
        $this->assertEquals('Not in active time', $output['msg']);
    }

    /**
     * 測試搶紅包，活動已結束
     */
    public function testObtainRewardButTimeisUp()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 1);

        $redis->hmset('reward_id_1', $reward->toArray());

        $param = [
            'reward_id' => 1,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760038, $output['code']);
        $this->assertEquals('Time is up', $output['msg']);
    }

    /**
     * 測試搶紅包，明細尚未建立
     */
    public function testObtainRewardButEntryNotCreated()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $rewardData = $reward->toArray();
        $rewardData['entry_created'] = false;
        $redis->hmset('reward_id_2', $rewardData);

        $param = [
            'reward_id' => 2,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760017, $output['code']);
        $this->assertEquals('Reward entry not created', $output['msg']);
    }

    /**
     * 測試搶紅包，使用者已參加過該活動
     */
    public function testObtainRewardButUserAttendedReward()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $redis->hmset('reward_id_2', $reward->toArray());
        $redis->sadd('reward_id_2_attended_user', 8);

        $param = [
            'reward_id' => 2,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760018, $output['code']);
        $this->assertEquals('User has attended reward', $output['msg']);
    }

    /**
     * 測試搶紅包，活動沒有明細
     */
    public function testObtainRewardButRewardHasNoEntry()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $redis->hmset('reward_id_2', $reward->toArray());

        $param = [
            'reward_id' => 2,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760019, $output['code']);
        $this->assertEquals('There is no reward entry', $output['msg']);
    }

    /**
     * 測試搶紅包，沒有帶session id
     */
    public function testObtainRewardWithoutSessionId()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $redis->hmset('reward_id_2', $reward->toArray());

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $entryData = [
            'id' => $entry->getId(),
            'amount' => $entry->getAmount()
        ];
        $redis->lpush('reward_id_2_entry', json_encode($entryData));

        $param = [
            'reward_id' => 2,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Session not found', $output['msg']);
        $this->assertEquals(150760033, $output['code']);
    }

    /**
     * 測試搶紅包，帶入不存在的session id
     */
    public function testObtainRewardWithNotExistsSessionId()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $redis->hmset('reward_id_2', $reward->toArray());

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $entryData = [
            'id' => $entry->getId(),
            'amount' => $entry->getAmount()
        ];
        $redis->lpush('reward_id_2_entry', json_encode($entryData));

        $param = [
            'reward_id' => 2,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], ['HTTP_SESSION_ID' => 'test']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Session not found', $output['msg']);
        $this->assertEquals(150760033, $output['code']);
    }

    /**
     * 測試搶紅包，帶入的session id 不屬於該使用者
     */
    public function testObtainRewardButSessionNotBelongToUser()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $redis->hmset('reward_id_2', $reward->toArray());

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $entryData = [
            'id' => $entry->getId(),
            'amount' => $entry->getAmount()
        ];
        $redis->lpush('reward_id_2_entry', json_encode($entryData));

        $param = [
            'reward_id' => 2,
            'user_id' => 2
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Session not belong to this user', $output['msg']);
        $this->assertEquals(150760034, $output['code']);
    }

    /**
     * 測試搶紅包，使用者沒有cash
     */
    public function testObtainRewardButUserHasNoCash()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $validator = $this->getContainer()->get('durian.validator');
        $client = $this->createClient();

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $redis->hmset('reward_id_2', $reward->toArray());

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $entryData = [
            'id' => $entry->getId(),
            'amount' => $entry->getAmount()
        ];
        $redis->lpush('reward_id_2_entry', json_encode($entryData));

        // 移除session的cash資訊
        $redis = $this->getContainer()->get('snc_redis.cluster');
        $sessionId = $this->headerParam['HTTP_SESSION_ID'];
        $redis->hdel("session_{$sessionId}", 'cash:currency');

        $param = [
            'reward_id' => 2,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The user does not have cash', $output['msg']);
        $this->assertEquals(150760035, $output['code']);
    }

    /**
     * 測試搶紅包，使用者非人民幣別
     */
    public function testObtainRewardButUserNotCNY()
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $validator = $this->getContainer()->get('durian.validator');
        $client = $this->createClient();

        // 設定session 內容
        $redisCluster = $this->getContainer()->get('snc_redis.cluster');
        $sessionId = $this->headerParam['HTTP_SESSION_ID'];
        $redisCluster->hset("session_{$sessionId}", 'cash:currency', 881);

        // redis新增活動
        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $redis->hmset('reward_id_2', $reward->toArray());

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 3);
        $entryData = [
            'id' => $entry->getId(),
            'amount' => $entry->getAmount()
        ];
        $redis->lpush('reward_id_2_entry', json_encode($entryData));

        $param = [
            'reward_id' => 2,
            'user_id' => 8
        ];

        $client->request('PUT', '/api/reward/obtain', $param, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Currency not support', $output['msg']);
        $this->assertEquals(150760036, $output['code']);
    }

    /**
     * 測試取得單筆紅包活動
     */
    public function testGetReward()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/reward/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $reward = $emShare->find('BBDurianBundle:Reward', 1);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($reward->getId(), $output['ret']['id']);
        $this->assertEquals($reward->getName(), $output['ret']['name']);
        $this->assertEquals($reward->getDomain(), $output['ret']['domain']);
        $this->assertEquals($reward->getAmount(), $output['ret']['amount']);
        $this->assertEquals($reward->getQuantity(), $output['ret']['quantity']);
        $this->assertEquals($reward->getMinAmount(), $output['ret']['min_amount']);
        $this->assertEquals($reward->getMaxAmount(), $output['ret']['max_amount']);
        $this->assertEquals($reward->isEntryCreated(), $output['ret']['entry_created']);
        $this->assertEquals($reward->getMemo(), $output['ret']['memo']);
    }

    /**
     * 測試取單筆紅包活動時，此筆明細不存在的情況
     */
    public function testGetRewardButNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/reward/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760015, $output['code']);
        $this->assertEquals('No such reward', $output['msg']);
    }

    /**
     * 測試取得紅包活動列表
     */
    public function testGetRewardList()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $time = new \DateTime('2014-12-31 00:00:00');
        $start = $time->format(\DateTime::ISO8601);
        $now = new \DateTime('now');

        $parameters = [
            'start'         => $start,
            'end'           => $now,
            'entry_created' => 1,
            'first_result'  => 0,
            'max_results'   => 10
        ];

        $client->request('GET', '/api/reward/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $reward1 = $emShare->find('BBDurianBundle:Reward', 1);
        $reward2 = $emShare->find('BBDurianBundle:Reward', 2);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);

        $this->assertEquals($reward1->getId(), $output['ret'][0]['id']);
        $this->assertEquals($reward1->getName(), $output['ret'][0]['name']);
        $this->assertEquals($reward1->getDomain(), $output['ret'][0]['domain']);
        $this->assertEquals($reward1->getAmount(), $output['ret'][0]['amount']);
        $this->assertEquals($reward1->getQuantity(), $output['ret'][0]['quantity']);
        $this->assertEquals($reward1->getMinAmount(), $output['ret'][0]['min_amount']);
        $this->assertEquals($reward1->getMaxAmount(), $output['ret'][0]['max_amount']);
        $this->assertEquals($reward1->isEntryCreated(), $output['ret'][0]['entry_created']);
        $this->assertEquals($reward1->getMemo(), $output['ret'][0]['memo']);

        $this->assertEquals($reward2->getId(), $output['ret'][1]['id']);
        $this->assertEquals($reward2->getName(), $output['ret'][1]['name']);
        $this->assertEquals($reward2->getDomain(), $output['ret'][1]['domain']);
        $this->assertEquals($reward2->getAmount(), $output['ret'][1]['amount']);
        $this->assertEquals($reward2->getQuantity(), $output['ret'][1]['quantity']);
        $this->assertEquals($reward2->getMinAmount(), $output['ret'][1]['min_amount']);
        $this->assertEquals($reward2->getMaxAmount(), $output['ret'][1]['max_amount']);
        $this->assertEquals($reward2->isEntryCreated(), $output['ret'][1]['entry_created']);
        $this->assertEquals($reward2->getMemo(), $output['ret'][1]['memo']);
    }

    /**
     * 測試取得單筆紅包明細
     */
    public function testGetRewardEntry()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/reward/entry/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 1);
        $createdAt = $entry->getCreatedAt()->format(\DateTime::ISO8601);
        $obtainAt = $entry->getObtainAt()->format(\DateTime::ISO8601);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($entry->getId(), $output['ret']['id']);
        $this->assertEquals($entry->getRewardId(), $output['ret']['reward_id']);
        $this->assertEquals($entry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($entry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($createdAt, $output['ret']['created_at']);
        $this->assertEquals($obtainAt, $output['ret']['obtain_at']);
        $this->assertNull($output['ret']['payoff_at']);
    }

    /**
     * 測試取單筆紅包明細時，此筆明細不存在的情況
     */
    public function testGetRewardEntryButNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/reward/entry/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760024, $output['code']);
        $this->assertEquals('No reward entry found', $output['msg']);
    }

    /**
     * 測試取得紅包明細資料
     */
    public function testGetRewardEntries()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $now = new \DateTime('now');

        $start = $now->sub(new \DateInterval('P1D'))->format(\DateTime::ISO8601);
        $end = $now->add(new \DateInterval('P2D'))->format(\DateTime::ISO8601);

        $parameters = [
            'user_id'      => 8,
            'obtain'       => 1,
            'payoff'       => 0,
            'start'        => $start,
            'end'          => $end,
            'sort'         => 'user_id',
            'order'        => 'asc',
            'first_result' => 0,
            'max_results'  => 5
        ];

        $client->request('GET', '/api/reward/1/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 1);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(5, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        $this->assertEquals($entry->getId(), $output['ret'][0]['id']);
        $this->assertEquals($entry->getRewardId(), $output['ret'][0]['reward_id']);
        $this->assertEquals($entry->getUserId(), $output['ret'][0]['user_id']);
        $this->assertEquals($entry->getAmount(), $output['ret'][0]['amount']);
    }

    /**
     * 測試依使用者id取得紅包明細資料
     */
    public function testGetRewardEntriesByUserId()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $now = new \DateTime('now');

        $start = $now->sub(new \DateInterval('P1D'))->format(\DateTime::ISO8601);
        $end = $now->add(new \DateInterval('P2D'))->format(\DateTime::ISO8601);

        $parameters = [
            'start'        => $start,
            'end'          => $end,
            'payoff'       => 0,
            'sort'         => 'user_id',
            'order'        => 'asc',
            'first_result' => 0,
            'max_results'  => 5
        ];

        $client->request('GET', '/api/user/8/reward/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 1);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(5, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        $this->assertEquals($entry->getId(), $output['ret'][0]['id']);
        $this->assertEquals($entry->getRewardId(), $output['ret'][0]['reward_id']);
        $this->assertEquals($entry->getUserId(), $output['ret'][0]['user_id']);
        $this->assertEquals($entry->getAmount(), $output['ret'][0]['amount']);
    }

    /**
     * 測試提前結束紅包活動
     */
    public function testEndReward()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        $json = $client->request('PUT', '/api/reward/2/end');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $now = new \DateTime;
        $endAt = $reward->getEndAt();

        $this->assertLessThan(5, abs($endAt->diff($now)->format('%s')));
        $this->assertEquals($output['ret'], $reward->toArray());

        // redis 資料有修改
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $redis->hget('reward_id_2', 'end_at'));

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('reward', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals("@end_at:{$endAt->format('Y-m-d H:i:s')}", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試提前結束紅包活動，但沒有該活動
     */
    public function testEndRewardButNoSuchReward()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $json = $client->request('PUT', '/api/reward/99/end');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760015, $output['code']);
        $this->assertEquals('No such reward', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試提前結束紅包活動，但活動已取消
     */
    public function testEndRewardButRewardHasBeenCancel()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $reward->cancel();
        $emShare->flush();

        $client->request('PUT', '/api/reward/1/end');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760023, $output['code']);
        $this->assertEquals('Reward has been cancelled', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試提前結束紅包活動，但活動非進行中
     */
    public function testEndRewardButRewardNotActive()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 新增活動
        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end
        ];

        $client->request('POST', '/api/reward', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);

        $client->request('PUT', '/api/reward/4/end');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150760016, $output['code']);
        $this->assertEquals('Not in active time', $output['msg']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOperation);
    }

    /**
     * 測試提前結束紅包活動，發生timeout
     */
    public function testEndRewardWithConnectionTimeout()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.reward');
        $client = $this->createClient();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'persist','flush', 'rollback', 'clear', 'getConnection'])
            ->getMock();

        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $endAt = $reward->getEndAt();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($reward));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);

        $client->request('PUT', '/api/reward/2/end');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Connection timed out', $output['msg']);

        $emShare->refresh($reward);

        // 驗證資料沒有被修改
        $this->assertEquals($endAt, $reward->getEndAt());
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $redis->hget('reward_id_2', 'end_at'));
        $this->assertTrue($redis->sismember('reward_available', 2));

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }
}
