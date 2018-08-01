<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\MaintainStatus;

class MaintainFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainStatusData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainWhitelistData'
        );

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $redis = $this->getContainer()->get('snc_redis.cluster');

        // 設定session 維護資訊
        $at = '2000-01-01 00:00:00';
        $redis->hmset('session_maintain', 1, "$at,$at");
        $redis->hmset('session_maintain', 2, "$at,$at");
    }

    /**
     * 測試得真實姓名不等於'測試帳號'的測試帳號
     */
    public function testGetIllegalTester()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BB\DurianBundle\Entity\User', 10);
        $user->setTest(1);
        $em->flush();

        $parameters = array('parent_id' => 9);

        $client->request('GET', '/api/maintain/get_illegal_tester', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $user = $em->find('BB\DurianBundle\Entity\User', 10);
        $detail = $em->find('BB\DurianBundle\Entity\UserDetail', 10);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($user->getId(), $ret['ret'][0]['user_id']);
        $this->assertEquals($detail->getNameReal(), $ret['ret'][0]['name_real']);

        //測試沒有測試帳號時顯示的訊息
        $parameters = array('parent_id' => 2);

        $client->request('GET', '/api/maintain/get_illegal_tester', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('There is no test user', $ret['message']);

        //測試有測試帳號且真實姓名是"測試帳號"時顯示的訊息
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $user->setTest(true);
        $detail = $em->find('BB\DurianBundle\Entity\UserDetail', 8);
        $detail->setNameReal('測試帳號');

        $em->flush();

        $parameters = array('parent_id' => 2);

        $client->request('GET', '/api/maintain/get_illegal_tester', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('No illegal tester found', $ret['message']);
    }

    /**
     * 測試設定遊戲維護時間不送廳主訊息
     */
    public function testSetMaintainByGameWithoutSendingDomainMessage()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')->findBy(['target' => 'domain']);
        $this->assertEmpty($maintainStatus);

        $parameters = [
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-09T00:00:00+0800',
            'msg' => 'testest',
            'operator' => '不告訴你'
        ];

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($parameters['begin_at'], $ret['ret']['begin_at']);
        $this->assertEquals($parameters['end_at'], $ret['ret']['end_at']);
        $this->assertEquals($parameters['msg'], $ret['ret']['msg']);
        $this->assertEquals($parameters['operator'], $ret['ret']['operator']);
        $this->assertFalse($ret['ret']['send_domain_message']);

        $em->clear();

        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')->findBy(['target' => 'domain']);
        $this->assertEmpty($maintainStatus);

        //測試二次操作不帶廳主訊息要刪除狀態
        $parameters = [
            'begin_at' => '2099-03-08T00:00:00+0800',
            'end_at' => '2099-03-08T00:10:00+0800',
            'msg' => 'testest',
            'operator' => '不告訴你',
            'send_domain_message' => 1,
            'notice_interval' => 3
        ];

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertTrue($ret['ret']['send_domain_message']);

        $em->clear();

        //第一次分項維護會先產生發送狀態/
        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')->findBy(['target' => 'domain']);
        $this->assertNotEmpty($maintainStatus);

        $parameters = [
            'begin_at' => '2099-03-08T00:10:00+0800',
            'end_at' => '2099-03-08T00:20:00+0800',
            'msg' => 'testest',
            'operator' => '不告訴你'
        ];

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertFalse($ret['ret']['send_domain_message']);

        $em->clear();

        //第二次設定不送訊息要刪除狀態
        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')->findBy(['target' => 'domain']);
        $this->assertEmpty($maintainStatus);
    }

    /**
     * 測試設定遊戲維護時間
     */
    public function testSetMaintainByGame()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-09T00:00:00+0800'
        ];

        //測試帶入不存在的遊戲代碼
        $client->request('PUT', '/api/maintain/game/10', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150100005', $ret['code']);
        $this->assertEquals('No game code exists', $ret['msg']);

        //測試帶入正確資訊
        $parameters = array(
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-09T00:00:00+0800',
            'msg' => 'testest',
            'operator' => '不告訴你',
            'send_domain_message' => 1
        );

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($parameters['begin_at'], $ret['ret']['begin_at']);
        $this->assertEquals($parameters['end_at'], $ret['ret']['end_at']);
        $this->assertEquals($parameters['msg'], $ret['ret']['msg']);
        $this->assertEquals($parameters['operator'], $ret['ret']['operator']);

        $maintain = $em->find('BBDurianBundle:Maintain', 1);
        $maintainArr = $maintain->toArray();

        $this->assertEquals($parameters['begin_at'], $maintainArr['begin_at']);
        $this->assertEquals($parameters['end_at'], $maintainArr['end_at']);
        $this->assertEquals($parameters['msg'], $maintainArr['msg']);
        $this->assertEquals($parameters['operator'], $maintainArr['operator']);

        // 驗證session 的維護資訊有一並修改
        $redis = $this->getContainer()->get('snc_redis.cluster');
        $maintainData = json_decode($redis->hget('session_maintain', 1), true);
        $this->assertEquals('2013-03-08 00:00:00', $maintainData['begin_at']);
        $this->assertEquals('2013-03-09 00:00:00', $maintainData['end_at']);
        $this->assertEquals('testest', $maintainData['msg']);

        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')
            ->findOneBy(['target' => '3']);

        // 因為設定結束的時間已超過現在時間，所以status=3
        $this->assertEquals(3, $maintainStatus->getStatus());

        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')
                              ->findBy(array('maintain' => 1));

        // 因為設定的target有四組(一,三,Mobile,廳主)，所以count=4
        $this->assertEquals(4, count($maintainStatus));
        $em->clear();

        //測試帶入尚未維護時間
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $beginAt->add(new \DateInterval('PT5M'));
        $endAt->add(new \DateInterval('PT10M'));

        $parameters = [
            'begin_at' => $beginAt->format(\DateTime::ISO8601),
            'end_at' => $endAt->format(\DateTime::ISO8601),
            'msg' => 'testest',
            'operator' => '不告訴你',
            'notice_interval' => 3,
            'send_domain_message' => 1
        ];

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $noticeAt = clone $beginAt;
        $noticeAt = $noticeAt->sub(new \DateInterval('PT3M'));

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($parameters['notice_interval'], $ret['ret']['notice_interval']);

        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')
            ->findOneBy(['target' => '1']);
        // 因為設定的時間尚未超過現在時間，所以status=1
        $this->assertEquals(1, $maintainStatus->getStatus());

        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')
            ->findBy(['target' => 'domain']);
        // 除了維護訊息外還有提醒訊息，所以count=2
        $this->assertEquals(2, count($maintainStatus));

        //測試取消提醒時間
        $parameters = [
            'begin_at' => $beginAt->format(\DateTime::ISO8601),
            'end_at' => $endAt->format(\DateTime::ISO8601),
            'msg' => 'testest',
            'operator' => '不告訴你',
            'send_domain_message' => 1
        ];

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')
            ->findOneBy(['target' => 'domain', 'status' => MaintainStatus::SEND_MAINTAIN_NOTICE]);

        $this->assertNull($maintainStatus);

        $em->clear();

        //測試帶入已經開始維護時間
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $endAt->add(new \DateInterval('PT5M'));

        $parameters = array(
            'begin_at' => $beginAt->format(\DateTime::ISO8601),
            'end_at' => $endAt->format(\DateTime::ISO8601),
            'msg' => 'testest',
            'operator' => '不告訴你',
            'send_domain_message' => 1
        );

        $client->request('PUT', '/api/maintain/game/1', $parameters);

        $maintainStatus = $em->getRepository('BBDurianBundle:MaintainStatus')
            ->findOneBy(['target' => '1']);
        // 因為設定的時間尚介於維護時間，所以status=2
        $this->assertEquals(2, $maintainStatus->getStatus());

        $em->clear();
    }

    /**
     * 測試取得遊戲維護資訊
     */
    public function testGetMaintainByGame()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        //測試帶入不存在的遊戲代碼
        $client->request('GET', '/api/maintain/game/10');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150100005', $ret['code']);
        $this->assertEquals('No game code exists', $ret['msg']);

        //測試此遊戲不在維護中
        $client->request('GET', '/api/maintain/game/1');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('2013-01-04T00:00:00+0800', $ret['ret']['begin_at']);
        $this->assertEquals('2013-01-04T20:13:14+0800', $ret['ret']['end_at']);
        $this->assertEquals('球類', $ret['ret']['msg']);
        $this->assertEquals('hangy', $ret['ret']['operator']);
        $this->assertFalse($ret['ret']['is_maintaining']);

        //測試此遊戲正在維護中
        $beginAt = new \DateTime('now');
        $beginAt->sub(new \DateInterval('PT1H'));
        $endAt = new \DateTime('now');
        $endAt->add(new \DateInterval('PT1H'));

        $maintain = $em->find('BBDurianBundle:Maintain', 1);
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);
        $em->persist($maintain);
        $em->flush();

        $client->request('GET', '/api/maintain/game/1');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $ret['ret']['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $ret['ret']['end_at']);
        $this->assertEquals('球類', $ret['ret']['msg']);
        $this->assertEquals('hangy', $ret['ret']['operator']);
        $this->assertTrue($ret['ret']['is_maintaining']);
        $this->assertFalse($ret['ret']['in_whitelist']);

        // 維護中且ip在白名單中
        $client->request('GET', '/api/maintain/game/1', ['client_ip' => '10.240.22.122']);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $ret['ret']['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $ret['ret']['end_at']);
        $this->assertEquals('球類', $ret['ret']['msg']);
        $this->assertEquals('hangy', $ret['ret']['operator']);
        $this->assertTrue($ret['ret']['is_maintaining']);
        $this->assertTrue($ret['ret']['in_whitelist']);
        $this->assertFalse($ret['ret']['send_domain_message']);

        //測試此遊戲尚未開始維護
        $beginAt = new \DateTime('now');
        $endAt = clone $beginAt;
        $noticeAt = clone $beginAt;
        $beginAt->add(new \DateInterval('PT1H'));
        $endAt->add(new \DateInterval('PT2H'));
        $noticeAt->add(new \DateInterval('PT30M'));

        $maintain = $em->find('BBDurianBundle:Maintain', 1);
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);
        $em->persist($maintain);

        $maintainStatus = new MaintainStatus($maintain, 'domain');
        $maintainStatus->setStatus(MaintainStatus::SEND_MAINTAIN_NOTICE);
        $maintainStatus->setUpdateAt($noticeAt);
        $em->persist($maintainStatus);
        $em->flush();

        $client->request('GET', '/api/maintain/game/1');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $ret['ret']['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $ret['ret']['end_at']);
        $this->assertEquals('球類', $ret['ret']['msg']);
        $this->assertEquals('hangy', $ret['ret']['operator']);
        $this->assertFalse($ret['ret']['is_maintaining']);
        $this->assertNull($ret['ret']['in_whitelist']);
        $this->assertEquals(30, $ret['ret']['notice_interval']);
        $this->assertTrue($ret['ret']['send_domain_message']);
    }

    /**
     * 測試設定遊戲維護時間(for歐博視訊)
     */
    public function testSetMaintainByGameCode22()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-09T00:00:00+0800',
            'msg' => 'tescode22',
            'operator' => 'billy'
        ];

        $client->request('PUT', '/api/maintain/game/22', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($parameters['begin_at'], $ret['ret']['begin_at']);
        $this->assertEquals($parameters['end_at'], $ret['ret']['end_at']);
        $this->assertEquals($parameters['msg'], $ret['ret']['msg']);
        $this->assertEquals($parameters['operator'], $ret['ret']['operator']);

        $maintain = $em->find('BBDurianBundle:Maintain', 22);
        $maintainArr = $maintain->toArray();

        $this->assertEquals($parameters['begin_at'], $maintainArr['begin_at']);
        $this->assertEquals($parameters['end_at'], $maintainArr['end_at']);
        $this->assertEquals($parameters['msg'], $maintainArr['msg']);
        $this->assertEquals($parameters['operator'], $maintainArr['operator']);
    }

    /**
     * 測試取得遊戲維護資訊(for歐博視訊)
     */
    public function testGetMaintainByGameCode22()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/maintain/game/22');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('2013-01-04T00:00:00+0800', $ret['ret']['begin_at']);
        $this->assertEquals('2013-01-04T20:13:14+0800', $ret['ret']['end_at']);
        $this->assertEquals('歐博視訊', $ret['ret']['msg']);
        $this->assertEquals('hangy', $ret['ret']['operator']);
        $this->assertFalse($ret['ret']['is_maintaining']);
    }

    /**
     * 測試設定遊戲維護時間(MG電子)
     */
    public function testSetMaintainByGameCode23()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-09T00:00:00+0800',
            'msg' => 'testcode23',
            'operator' => 'test'
        ];

        $client->request('PUT', '/api/maintain/game/23', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($parameters['begin_at'], $ret['ret']['begin_at']);
        $this->assertEquals($parameters['end_at'], $ret['ret']['end_at']);
        $this->assertEquals($parameters['msg'], $ret['ret']['msg']);
        $this->assertEquals($parameters['operator'], $ret['ret']['operator']);

        $maintain = $em->find('BBDurianBundle:Maintain', 23);
        $maintainArr = $maintain->toArray();

        $this->assertEquals($parameters['begin_at'], $maintainArr['begin_at']);
        $this->assertEquals($parameters['end_at'], $maintainArr['end_at']);
        $this->assertEquals($parameters['msg'], $maintainArr['msg']);
        $this->assertEquals($parameters['operator'], $maintainArr['operator']);
    }

    /**
     * 測試取得遊戲維護資訊(MG電子)
     */
    public function testGetMaintainByGameCode23()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/maintain/game/23');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('2013-01-04T00:00:00+0800', $ret['ret']['begin_at']);
        $this->assertEquals('2013-01-04T20:13:14+0800', $ret['ret']['end_at']);
        $this->assertEquals('MG電子', $ret['ret']['msg']);
        $this->assertEquals('hangy', $ret['ret']['operator']);
        $this->assertFalse($ret['ret']['is_maintaining']);
    }

    /**
     * 測試設定遊戲維護時間(東方視訊)
     */
    public function testSetMaintainByGameCode24()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'begin_at' => '2013-03-08T00:00:00+0800',
            'end_at' => '2013-03-09T00:00:00+0800',
            'msg' => 'testcode24',
            'operator' => 'test'
        ];

        $client->request('PUT', '/api/maintain/game/24', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($parameters['begin_at'], $ret['ret']['begin_at']);
        $this->assertEquals($parameters['end_at'], $ret['ret']['end_at']);
        $this->assertEquals($parameters['msg'], $ret['ret']['msg']);
        $this->assertEquals($parameters['operator'], $ret['ret']['operator']);

        $maintain = $em->find('BBDurianBundle:Maintain', 24);
        $maintainArr = $maintain->toArray();

        $this->assertEquals($parameters['begin_at'], $maintainArr['begin_at']);
        $this->assertEquals($parameters['end_at'], $maintainArr['end_at']);
        $this->assertEquals($parameters['msg'], $maintainArr['msg']);
        $this->assertEquals($parameters['operator'], $maintainArr['operator']);
    }

    /**
     * 測試取得遊戲維護資訊(東方視訊)
     */
    public function testGetMaintainByGameCode24()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/maintain/game/24');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('2013-01-04T00:00:00+0800', $ret['ret']['begin_at']);
        $this->assertEquals('2013-01-04T20:13:14+0800', $ret['ret']['end_at']);
        $this->assertEquals('東方視訊', $ret['ret']['msg']);
        $this->assertEquals('hangy', $ret['ret']['operator']);
        $this->assertFalse($ret['ret']['is_maintaining']);
    }

    /**
     * 測試新增白名單
     */
    public function testCreateWhitelist()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $mobileUrl = $this->getContainer()->getParameter('whitelist_mobile_url');
        $mobileKey = $this->getContainer()->getParameter('whitelist_mobile_key');

        $client->request(
            'POST',
            '/api/maintain/whitelist',
            [
                'ip' => [
                    0 => '10.222.23.14',
                    1 => '10.222.23.15',
                ]
            ]
        );
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('10.222.23.14', $ret['ret'][0]['ip']);
        $this->assertEquals('10.222.23.15', $ret['ret'][1]['ip']);

        $whitelist = $em->find('BBDurianBundle:MaintainWhitelist', 3);
        $this->assertEquals(3, $whitelist->getId());
        $this->assertEquals('10.222.23.14', $whitelist->getIp());

        $whitelist = $em->find('BBDurianBundle:MaintainWhitelist', 4);
        $this->assertEquals(4, $whitelist->getId());
        $this->assertEquals('10.222.23.15', $whitelist->getIp());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('maintain_whitelist', $log->getTableName());
        $this->assertEquals('@ip:10.222.23.14', $log->getMessage());

        $log = $emShare->find('BBDurianBundle:LogOperation', 2);

        $this->assertEquals('maintain_whitelist', $log->getTableName());
        $this->assertEquals('@ip:10.222.23.15', $log->getMessage());

        // 測試是否有在massage_queue中
        $queueMessage = json_decode($redis->rpop('message_immediate_queue'), true);

        $this->assertEquals('rd1_whitelist', $queueMessage['target']);
        $this->assertEquals('POST', $queueMessage['method']);
        $this->assertEquals('/api/index.php?module=MaintainAPI&method=setWhiteList', $queueMessage['url']);
        $this->assertEquals(['Api-Key' => 'akey'], $queueMessage['header']);
        $this->assertEquals('10.240.22.122', $queueMessage['content']['whitelist'][0]);
        $this->assertEquals('10.240.22.123', $queueMessage['content']['whitelist'][1]);
        $this->assertEquals('10.222.23.14', $queueMessage['content']['whitelist'][2]);
        $this->assertEquals('10.222.23.15', $queueMessage['content']['whitelist'][3]);

        // 測試是否有在massage_queue中
        $queueMessage = json_decode($redis->rpop('message_immediate_queue'), true);

        $this->assertEquals('mobile_whitelist', $queueMessage['target']);
        $this->assertEquals('POST', $queueMessage['method']);
        $this->assertEquals($mobileUrl, $queueMessage['url']);
        $this->assertEquals(['Ekey' => $mobileKey], $queueMessage['header']);
        $this->assertEquals('10.240.22.122', $queueMessage['content']['ipList'][0]);
        $this->assertEquals('10.240.22.123', $queueMessage['content']['ipList'][1]);
        $this->assertEquals('10.222.23.14', $queueMessage['content']['ipList'][2]);
        $this->assertEquals('10.222.23.15', $queueMessage['content']['ipList'][3]);

        // 驗證有存到 redis cluster
        $redis = $this->getContainer()->get('snc_redis.cluster');
        $this->assertTrue($redis->sismember('session_whitelist', '10.222.23.14'));
        $this->assertTrue($redis->sismember('session_whitelist', '10.222.23.15'));
    }

    /**
     * 測試新增白名單，ip已經在白名單中
     */
    public function testCreateWhitelistButIpAlreadyIn()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/maintain/whitelist', ['ip' => '10.240.22.122']);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150100014, $ret['code']);
        $this->assertEquals('Ip already exists', $ret['msg']);
    }

    /**
     * 測試刪除白名單
     */
    public function testDeleteWhitelist()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:MaintainWhitelist');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $mobileUrl = $this->getContainer()->getParameter('whitelist_mobile_url');
        $mobileKey = $this->getContainer()->getParameter('whitelist_mobile_key');

        $whitelist = $repo->findOneBy(['ip' => '10.240.22.122']);
        $this->assertNotEmpty($whitelist);

        // 將要刪除的ip放到redis cluster
        $redisCluster = $this->getContainer()->get('snc_redis.cluster');
        $redisCluster->sadd('session_whitelist', '10.240.22.122');

        $em->clear();

        $client->request(
            'DELETE',
            '/api/maintain/whitelist',
            [
                'ip' => [
                    0 => '10.240.22.122',
                    1 => '10.240.22.123',
                ]
            ]
        );
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $whitelist = $repo->findOneBy(['ip' => '10.240.22.122']);
        $this->assertEmpty($whitelist);

        $whitelist = $repo->findOneBy(['ip' => '10.240.22.123']);
        $this->assertEmpty($whitelist);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('maintain_whitelist', $log->getTableName());
        $this->assertEquals('@ip:10.240.22.122', $log->getMessage());

        $log = $emShare->find('BBDurianBundle:LogOperation', 2);

        $this->assertEquals('maintain_whitelist', $log->getTableName());
        $this->assertEquals('@ip:10.240.22.123', $log->getMessage());

        // 測試是否有在massage_queue中
        $queueMessage = json_decode($redis->rpop('message_immediate_queue'), true);

        $this->assertEquals('rd1_whitelist', $queueMessage['target']);
        $this->assertEquals('POST', $queueMessage['method']);
        $this->assertEquals('/api/index.php?module=MaintainAPI&method=setWhiteList', $queueMessage['url']);
        $this->assertEquals(['Api-Key' => 'akey'], $queueMessage['header']);
        $this->assertEmpty($queueMessage['content']['whitelist']);

        // 測試是否有在massage_queue中
        $queueMessage = json_decode($redis->rpop('message_immediate_queue'), true);

        $this->assertEquals('mobile_whitelist', $queueMessage['target']);
        $this->assertEquals('POST', $queueMessage['method']);
        $this->assertEquals($mobileUrl, $queueMessage['url']);
        $this->assertEquals(['Ekey' => $mobileKey], $queueMessage['header']);
        $this->assertEmpty($queueMessage['content']['ipList']);

        // 驗證ip有從 redis cluster 刪除
        $this->assertFalse($redisCluster->sismember('session_whitelist', '10.240.22.122'));
        $this->assertFalse($redisCluster->sismember('session_whitelist', '10.240.22.123'));
    }

    /**
     * 測試刪除白名單，但ip不在白名單中
     */
    public function testDeleteWhitelistButNotInWhitelist()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/maintain/whitelist', ['ip' => '111.111.111.111']);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150100015, $ret['code']);
        $this->assertEquals('No MaintainWhitelist found', $ret['msg']);
    }

    /**
     * 測試取得白名單列表
     */
    public function testGetWhitelist()
    {
        $client = $this->createClient();

        $params = [
            'first_result' => 0,
            'max_results' => 20
        ];

        $client->request('GET', '/api/maintain/whitelist', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals('10.240.22.122', $ret['ret'][0]['ip']);
        $this->assertEquals('10.240.22.123', $ret['ret'][1]['ip']);
        $this->assertEquals(0, $ret['pagination']['first_result']);
        $this->assertEquals(20, $ret['pagination']['max_results']);
        $this->assertEquals(2, $ret['pagination']['total']);
    }

    /**
     * 取得維護中遊戲
     */
    public function testGetMaintainGameList()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $maintain = $em->find('BBDurianBundle:Maintain', 22);
        $begin = new \DateTime('-5 mins');
        $end = new \DateTime('+5 mins');
        $maintain->setBeginAt($begin);
        $maintain->setEndAt($end);
        $em->flush();

        $client->request('GET', '/api/maintain/game_list');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(22, $ret['ret'][0]);
    }

}
