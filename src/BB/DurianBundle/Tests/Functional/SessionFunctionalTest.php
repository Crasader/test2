<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class SessionFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData'
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.cluster');

        // 設定session 白名單資料
        $redis->sadd('session_whitelist', '127.0.0.1');

        $now = new \Datetime('now');
        $at = $now->sub(new \DateInterval('PT1M'));
        $time = $at->format('YmdHis');

        $redis->zadd('onlineList:domain:2', 'CH', $time, '2:1234:lala');
        $redis->zadd('onlineList:domain:0', 'CH', $time, '2:1234:lala');
        $redis->zadd('onlineList:domain:9', 'CH', $time, '9:5678:lala');
        $redis->zadd('onlineList:domain:0', 'CH', $time, '9:5678:lala');
    }

    /**
     * 測試建立 Session
     */
    public function testCreate()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        // 驗證回傳資料
        $this->assertEquals('ok', $out['result']);
        $this->assertEquals(7, $out['ret']['user']['id']);
        $this->assertEquals('ztester', $out['ret']['user']['username']);
        $this->assertEquals(2, $out['ret']['user']['domain']);
        $this->assertEquals('ztester', $out['ret']['user']['alias']);
        $this->assertEquals('', $out['ret']['user']['sub']);
        $this->assertEquals('', $out['ret']['user']['test']);
        $this->assertEquals(2, $out['ret']['user']['size']);
        $this->assertArrayNotHasKey('err_num', $out['ret']['user']);
        $this->assertEquals('TWD', $out['ret']['user']['currency']);
        $this->assertEquals('2013-01-01T11:11:11+0800', $out['ret']['user']['created_at']);
        $this->assertNotNull($out['ret']['user']['modified_at']);
        $this->assertEquals('', $out['ret']['user']['last_login']);
        $this->assertArrayNotHasKey('password_expire_at', $out['ret']['user']);
        $this->assertArrayNotHasKey('password_reset', $out['ret']['user']);
        $this->assertEquals(2, $out['ret']['user']['role']);
        $this->assertEquals(6, $out['ret']['user']['all_parents'][0]);
        $this->assertEquals(5, $out['ret']['user']['all_parents'][1]);
        $this->assertEquals(4, $out['ret']['user']['all_parents'][2]);
        $this->assertEquals(3, $out['ret']['user']['all_parents'][3]);
        $this->assertEquals(2, $out['ret']['user']['all_parents'][4]);
        $this->assertNull($out['ret']['user']['client_os']);
        $this->assertNull($out['ret']['user']['ingress']);
        $this->assertNull($out['ret']['user']['last_login_ip']);
        $this->assertNotNull($out['ret']['session']['id']);
        $this->assertNotNull($out['ret']['session']['created_at']);
        $this->assertEquals('', $out['ret']['session']['modified_at']);
        $this->assertTrue($out['ret']['credit']);
        $this->assertTrue($out['ret']['card']);

        $now = new \DateTime;
        $createddAt = new \DateTime($out['ret']['session']['created_at']);
        $this->assertLessThan(5, abs($createddAt->diff($now)->format('%s')));

        // 驗證 Redis 的資料
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $mapKey = 'session_user_7_map';
        $sessionKey = 'session_' . $redis->lindex($mapKey, 0);
        $this->assertEquals($sessionKey, 'session_' . $out['ret']['session']['id']);

        $sesData = $redis->hgetall($sessionKey);
        $this->assertEquals($sesData['session:id'], $out['ret']['session']['id']);
        $this->assertEquals($sesData['session:created_at'], $out['ret']['session']['created_at']);
        $this->assertEquals($sesData['session:modified_at'], $out['ret']['session']['modified_at']);
        $this->assertEquals($sesData['user:id'], $out['ret']['user']['id']);
        $this->assertEquals($sesData['user:username'], $out['ret']['user']['username']);
        $this->assertEquals($sesData['user:domain'], $out['ret']['user']['domain']);
        $this->assertEquals($sesData['user:alias'], $out['ret']['user']['alias']);
        $this->assertEquals($sesData['user:sub'], $out['ret']['user']['sub']);
        $this->assertEquals($sesData['user:test'], $out['ret']['user']['test']);
        $this->assertEquals($sesData['user:size'], $out['ret']['user']['size']);
        $this->assertArrayNotHasKey('user:err_num', $out['ret']['user']);
        $this->assertArrayNotHasKey('user:err_num', $sesData);
        $this->assertEquals($sesData['cash:currency'], $out['ret']['user']['currency']);
        $this->assertEquals('CNY', $sesData['cash_fake:currency']);
        $this->assertEquals($sesData['user:currency'], $out['ret']['user']['currency']);
        $this->assertEquals($sesData['user:created_at'], $out['ret']['user']['created_at']);
        $this->assertEquals($sesData['user:modified_at'], $out['ret']['user']['modified_at']);
        $this->assertEquals($sesData['user:last_login'], $out['ret']['user']['last_login']);
        $this->assertArrayNotHasKey('user:password_expire_at', $out['ret']['user']);
        $this->assertArrayNotHasKey('user:password_expire_at', $sesData);
        $this->assertArrayNotHasKey('user:password_reset', $out['ret']['user']);
        $this->assertArrayNotHasKey('user:password_reset', $sesData);
        $this->assertEquals($sesData['user:role'], $out['ret']['user']['role']);
        $this->assertEquals($sesData['user:client_os'], $out['ret']['user']['client_os']);
        $this->assertEquals($sesData['user:ingress'], $out['ret']['user']['ingress']);
        $this->assertEquals($sesData['user:last_login_ip'], $out['ret']['user']['last_login_ip']);

        $allParents = implode(',', $out['ret']['user']['all_parents']);
        $this->assertEquals($sesData['user:all_parents'], $allParents);
    }

    /**
     * 測試建立 Session，但使用者不存在
     */
    public function testCreateWithNoSuchUser()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/99123/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330004, $out['code']);
        $this->assertEquals('No such user', $out['msg']);
    }

    /**
     * 測試建立 Session，但 Session 已經存在
     */
    public function testCreateWithSessionAlreadyExists()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        // 第一次會建立
        $this->assertEquals('ok', $out['result']);

        // 第二次會出錯
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals('150330002', $out['code']);
        $this->assertEquals('Session already exists', $out['msg']);
    }

    /**
     * 測試用session id建立 Session
     */
    public function testCreateBySessionId()
    {
        $client = $this->createClient();
        $param = ['user_id' => 7];
        $client->request('POST', '/api/session/620318792ab2389366f7d9c6e0218d1c902564ac', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        // 驗證回傳資料
        $this->assertEquals('ok', $out['result']);
        $this->assertEquals(7, $out['ret']['user']['id']);
        $this->assertEquals('ztester', $out['ret']['user']['username']);
        $this->assertEquals(2, $out['ret']['user']['domain']);
        $this->assertEquals('ztester', $out['ret']['user']['alias']);
        $this->assertEquals('', $out['ret']['user']['sub']);
        $this->assertEquals('', $out['ret']['user']['test']);
        $this->assertEquals(2, $out['ret']['user']['size']);
        $this->assertArrayNotHasKey('err_num', $out['ret']['user']);
        $this->assertEquals('TWD', $out['ret']['user']['currency']);
        $this->assertEquals('2013-01-01T11:11:11+0800', $out['ret']['user']['created_at']);
        $this->assertNotNull($out['ret']['user']['modified_at']);
        $this->assertEquals('', $out['ret']['user']['last_login']);
        $this->assertArrayNotHasKey('password_expire_at', $out['ret']['user']);
        $this->assertArrayNotHasKey('password_reset', $out['ret']['user']);
        $this->assertEquals(2, $out['ret']['user']['role']);
        $this->assertEquals(6, $out['ret']['user']['all_parents'][0]);
        $this->assertEquals(5, $out['ret']['user']['all_parents'][1]);
        $this->assertEquals(4, $out['ret']['user']['all_parents'][2]);
        $this->assertEquals(3, $out['ret']['user']['all_parents'][3]);
        $this->assertEquals(2, $out['ret']['user']['all_parents'][4]);
        $this->assertNull($out['ret']['user']['client_os']);
        $this->assertNull($out['ret']['user']['ingress']);
        $this->assertNull($out['ret']['user']['last_login_ip']);
        $this->assertEquals('620318792ab2389366f7d9c6e0218d1c902564ac', $out['ret']['session']['id']);
        $this->assertNotNull($out['ret']['session']['created_at']);
        $this->assertEquals('', $out['ret']['session']['modified_at']);
        $this->assertTrue($out['ret']['outside']);

        $now = new \DateTime;
        $createddAt = new \DateTime($out['ret']['session']['created_at']);
        $this->assertLessThan(5, abs($createddAt->diff($now)->format('%s')));

        // 驗證 Redis 的資料
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $mapKey = 'session_user_7_map';
        $sessionKey = 'session_' . $redis->lindex($mapKey, 0);
        $this->assertEquals($sessionKey, 'session_' . $out['ret']['session']['id']);

        $sesData = $redis->hgetall($sessionKey);
        $this->assertEquals($sesData['session:id'], $out['ret']['session']['id']);
        $this->assertEquals($sesData['session:created_at'], $out['ret']['session']['created_at']);
        $this->assertEquals($sesData['session:modified_at'], $out['ret']['session']['modified_at']);
        $this->assertEquals($sesData['user:id'], $out['ret']['user']['id']);
        $this->assertEquals($sesData['user:username'], $out['ret']['user']['username']);
        $this->assertEquals($sesData['user:domain'], $out['ret']['user']['domain']);
        $this->assertEquals($sesData['user:alias'], $out['ret']['user']['alias']);
        $this->assertEquals($sesData['user:sub'], $out['ret']['user']['sub']);
        $this->assertEquals($sesData['user:test'], $out['ret']['user']['test']);
        $this->assertEquals($sesData['user:size'], $out['ret']['user']['size']);
        $this->assertArrayNotHasKey('user:err_num', $out['ret']['user']);
        $this->assertArrayNotHasKey('user:err_num', $sesData);
        $this->assertEquals($sesData['user:currency'], $out['ret']['user']['currency']);
        $this->assertEquals($sesData['user:created_at'], $out['ret']['user']['created_at']);
        $this->assertEquals($sesData['user:modified_at'], $out['ret']['user']['modified_at']);
        $this->assertEquals($sesData['user:last_login'], $out['ret']['user']['last_login']);
        $this->assertArrayNotHasKey('user:password_expire_at', $out['ret']['user']);
        $this->assertArrayNotHasKey('user:password_expire_at', $sesData);
        $this->assertArrayNotHasKey('user:password_reset', $out['ret']['user']);
        $this->assertArrayNotHasKey('user:password_reset', $sesData);
        $this->assertEquals($sesData['user:role'], $out['ret']['user']['role']);
        $this->assertEquals($sesData['user:client_os'], $out['ret']['user']['client_os']);
        $this->assertEquals($sesData['user:ingress'], $out['ret']['user']['ingress']);
        $this->assertEquals($sesData['user:last_login_ip'], $out['ret']['user']['last_login_ip']);
        $this->assertEquals('1', $sesData['outside']);

        $allParents = implode(',', $out['ret']['user']['all_parents']);
        $this->assertEquals($sesData['user:all_parents'], $allParents);
    }

    /**
     * 測試用session id建立 Session，但使用者不存在
     */
    public function testCreateBySessionIdWithNoSuchUser()
    {
        $client = $this->createClient();
        $param = ['user_id' => 99123];
        $client->request('POST', '/api/session/620318792ab2389366f7d9c6e0218d1c902564ac', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330011, $out['code']);
        $this->assertEquals('No such user', $out['msg']);
    }

    /**
     * 測試用session id建立 Session，但 Session 已經存在
     */
    public function testCreateBySessionIdWithSessionAlreadyExists()
    {
        $client = $this->createClient();
        $param = ['user_id' => 7];
        $client->request('POST', '/api/session/620318792ab2389366f7d9c6e0218d1c902564ac', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        // 第一次會建立
        $this->assertEquals('ok', $out['result']);

        // 第二次會出錯
        $client->request('POST', '/api/session/620318792ab2389366f7d9c6e0218d1c902564ac', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals('150330012', $out['code']);
        $this->assertEquals('Session already exists', $out['msg']);
    }

    /**
     * 測試根據 SessionId，取得 Session 資料
     */
    public function testGet()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $sessionId = $out['ret']['session']['id'];

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);
        $this->assertEquals(7, $out['ret']['user']['id']);
        $this->assertEquals('ztester', $out['ret']['user']['username']);
        $this->assertEquals(2, $out['ret']['user']['domain']);
        $this->assertEquals('ztester', $out['ret']['user']['alias']);
        $this->assertEquals('', $out['ret']['user']['sub']);
        $this->assertEquals('', $out['ret']['user']['test']);
        $this->assertEquals(2, $out['ret']['user']['size']);
        $this->assertArrayNotHasKey('err_num', $out['ret']['user']);
        $this->assertEquals('TWD', $out['ret']['user']['currency']);
        $this->assertEquals('2013-01-01T11:11:11+0800', $out['ret']['user']['created_at']);
        $this->assertNotNull($out['ret']['user']['modified_at']);
        $this->assertEquals('', $out['ret']['user']['last_login']);
        $this->assertArrayNotHasKey('password_expire_at', $out['ret']['user']);
        $this->assertArrayNotHasKey('password_reset', $out['ret']['user']);
        $this->assertEquals(2, $out['ret']['user']['role']);
        $this->assertEquals(6, $out['ret']['user']['all_parents'][0]);
        $this->assertEquals(5, $out['ret']['user']['all_parents'][1]);
        $this->assertEquals(4, $out['ret']['user']['all_parents'][2]);
        $this->assertEquals(3, $out['ret']['user']['all_parents'][3]);
        $this->assertEquals(2, $out['ret']['user']['all_parents'][4]);
        $this->assertNull($out['ret']['user']['client_os']);
        $this->assertNull($out['ret']['user']['ingress']);
        $this->assertNull($out['ret']['user']['last_login_ip']);
        $this->assertEquals($sessionId, $out['ret']['session']['id']);
        $this->assertNotNull($out['ret']['session']['created_at']);
        $this->assertEquals('', $out['ret']['session']['modified_at']);

        $now = new \DateTime;
        $createddAt = new \DateTime($out['ret']['session']['created_at']);
        $this->assertLessThan(5, abs($createddAt->diff($now)->format('%s')));

        // 驗證不在維護期間內維護資訊白名單為空
        $this->assertEmpty($out['ret']['is_maintaining']);
        $this->assertEmpty($out['ret']['whitelist']);

        // 設定在維護時間內
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $beginAt = new \Datetime('now');
        $endAt = new \Datetime('now');
        $beginAt = $beginAt->modify('-1 hour');
        $endAt = $endAt->modify('+1 hour');
        $sessionBroker->setMaintainInfo(1, $beginAt, $endAt, 'test');

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        // 驗證有回傳維護資訊與白名單
        $this->assertEquals($beginAt->format(\Datetime::ISO8601), $out['ret']['is_maintaining'][1]['begin_at']);
        $this->assertEquals($endAt->format(\Datetime::ISO8601), $out['ret']['is_maintaining'][1]['end_at']);
        $this->assertEquals('test', $out['ret']['is_maintaining'][1]['msg']);
        $this->assertEquals('127.0.0.1',$out['ret']['whitelist'][0]);
    }

    /**
     * 測試根據SessionId，取得 Session 資料，但找不到 Session
     */
    public function testGetWithoutSession()
    {
        $client = $this->createClient();

        $sessionId = 'ThisSessionDidnotExist';

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals('150330001', $out['code']);
        $this->assertEquals('Session not found', $out['msg']);
    }

    /**
     * 測試根據使用者編號取得 Session 資料
     */
    public function testGetByUserId()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $sessionId = $out['ret']['session']['id'];

        $client->request('GET', "/api/user/7/session");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);
        $this->assertEquals(7, $out['ret']['user']['id']);
        $this->assertEquals('ztester', $out['ret']['user']['username']);
        $this->assertEquals(2, $out['ret']['user']['domain']);
        $this->assertEquals('ztester', $out['ret']['user']['alias']);
        $this->assertEquals('', $out['ret']['user']['sub']);
        $this->assertEquals('', $out['ret']['user']['test']);
        $this->assertEquals(2, $out['ret']['user']['size']);
        $this->assertArrayNotHasKey('err_num', $out['ret']['user']);
        $this->assertEquals('TWD', $out['ret']['user']['currency']);
        $this->assertEquals('2013-01-01T11:11:11+0800', $out['ret']['user']['created_at']);
        $this->assertNotNull($out['ret']['user']['modified_at']);
        $this->assertEquals('', $out['ret']['user']['last_login']);
        $this->assertArrayNotHasKey('password_expire_at', $out['ret']['user']);
        $this->assertArrayNotHasKey('password_reset', $out['ret']['user']);
        $this->assertEquals(2, $out['ret']['user']['role']);
        $this->assertEquals(6, $out['ret']['user']['all_parents'][0]);
        $this->assertEquals(5, $out['ret']['user']['all_parents'][1]);
        $this->assertEquals(4, $out['ret']['user']['all_parents'][2]);
        $this->assertEquals(3, $out['ret']['user']['all_parents'][3]);
        $this->assertEquals(2, $out['ret']['user']['all_parents'][4]);
        $this->assertNull($out['ret']['user']['client_os']);
        $this->assertNull($out['ret']['user']['ingress']);
        $this->assertNull($out['ret']['user']['last_login_ip']);
        $this->assertEquals($sessionId, $out['ret']['session']['id']);
        $this->assertNotNull($out['ret']['session']['created_at']);
        $this->assertEquals('', $out['ret']['session']['modified_at']);

        $now = new \DateTime;
        $createddAt = new \DateTime($out['ret']['session']['created_at']);
        $this->assertLessThan(5, abs($createddAt->diff($now)->format('%s')));

        // 驗證不在維護期間內維護資訊白名單為空
        $this->assertEmpty($out['ret']['is_maintaining']);
        $this->assertEmpty($out['ret']['whitelist']);

        // 設定在維護時間內
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $beginAt = new \Datetime('now');
        $endAt = new \Datetime('now');
        $beginAt = $beginAt->modify('-1 hour');
        $endAt = $endAt->modify('+1 hour');
        $sessionBroker->setMaintainInfo(1, $beginAt, $endAt, 'test');

        $client->request('GET', "/api/user/7/session");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        // 驗證有回傳維護資訊與白名單
        $this->assertEquals($beginAt->format(\Datetime::ISO8601), $out['ret']['is_maintaining'][1]['begin_at']);
        $this->assertEquals($endAt->format(\Datetime::ISO8601), $out['ret']['is_maintaining'][1]['end_at']);
        $this->assertEquals('test', $out['ret']['is_maintaining'][1]['msg']);
        $this->assertEquals('127.0.0.1',$out['ret']['whitelist'][0]);
    }

    /**
     * 測試根據使用者編號，取得 Session 資料，但找不到 Session
     */
    public function testGetByUserIdWithoutSession()
    {
        $client = $this->createClient();

        $client->request('GET', "/api/user/7/session");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals('150330001', $out['code']);
        $this->assertEquals('Session not found', $out['msg']);
    }

    /**
     * 測試刪除 Session
     */
    public function testDeleteSession()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $sessionId = $out['ret']['session']['id'];

        $client->request('DELETE', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330001, $out['code']);
        $this->assertEquals('Session not found', $out['msg']);
    }

    /**
     * 測試用使用者刪除 Session
     */
    public function testDeleteSessionByUserId()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $sessionId = $out['ret']['session']['id'];

        $client->request('DELETE', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330001, $out['code']);
        $this->assertEquals('Session not found', $out['msg']);

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $this->assertFalse($redis->exists('session_user_7_map'));
    }

    /**
     * 測試用上層使用者刪除 Session，從parent往下刪depth層
     */
    public function testDeleteSessionByParentWithParentAndDepth()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $sessionId = $out['ret']['session']['id'];

        $param = [
            'parent_id' => 6,
            'depth' => 1
        ];

        $client->request('DELETE', '/api/session', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330001, $out['code']);
        $this->assertEquals('Session not found', $out['msg']);
    }

    /**
     * 測試用上層使用者刪除 Session，刪除parent底下使用者身分為role的session
     */
    public function testDeleteSessionByParentWithParantAndRole()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/6/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $user6SessionId = $out['ret']['session']['id'];

        $client->request('POST', '/api/user/8/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $user8SessionId = $out['ret']['session']['id'];

        // 刪除parent 的底下role = 1的session
        $param = [
            'parent_id' => 2,
            'role' => 1
        ];

        $client->request('DELETE', '/api/session', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);

        $client->request('GET', "/api/session/$user8SessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330001, $out['code']);
        $this->assertEquals('Session not found', $out['msg']);

        // role = 3 的沒有被刪除
        $client->request('GET', "/api/session/$user6SessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals(6, $out['ret']['user']['id']);
    }

    /**
     * 測試用上層使用者刪除 Session，刪除parent底下所有session
     */
    public function testDeleteSessionByParentWithoutDepthAndRole()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $sessionId = $out['ret']['session']['id'];

        $param = ['parent_id' => 6];

        $client->request('DELETE', '/api/session', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330001, $out['code']);
        $this->assertEquals('Session not found', $out['msg']);
    }

    /**
     * 測試用上層使用者刪除 Session，不分廳往下刪5層
     */
    public function testDeleteSessionByParentWithDepth5WithoutParent()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $sessionId = $out['ret']['session']['id'];

        // 從大廳主不分廳往下刪5層，role = 2會被刪除
        $param = ['depth' => 5];

        $client->request('DELETE', '/api/session', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330001, $out['code']);
        $this->assertEquals('Session not found', $out['msg']);
    }

    /**
     * 測試用上層使用者刪除 Session，不分廳刪掉role = 2的使用者session
     */
    public function testDeleteSessionByParentWithRole2WithoutParent()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/6/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $user6SessionId = $out['ret']['session']['id'];

        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $user7SessionId = $out['ret']['session']['id'];

        // 不分廳刪除role = 2的使用者session
        $param = ['role' => 2];

        $client->request('DELETE', '/api/session', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);

        $client->request('GET', "/api/session/$user7SessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330001, $out['code']);
        $this->assertEquals('Session not found', $out['msg']);

        // role = 3 沒有被刪掉
        $client->request('GET', "/api/session/$user6SessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals(6, $out['ret']['user']['id']);
    }

    /**
     * 測試用上層使用者刪除 Session，不分廳刪除所有session
     */
    public function testDeleteSessionByParentWithoutParentAndDepthAndRole()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $sessionId = $out['ret']['session']['id'];

        $client->request('DELETE', '/api/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);

        $client->request('GET', "/api/session/$sessionId");
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330001, $out['code']);
        $this->assertEquals('Session not found', $out['msg']);
    }

    /**
     * 測試用上層使用者刪除 Session，parent 不存在
     */
    public function testDeleteSessionByParentButNoParentFound()
    {
        $client = $this->createClient();

        $param = [
            'parent_id' => 99999
        ];

        $client->request('DELETE', '/api/session', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330006, $out['code']);
        $this->assertEquals('No parent found', $out['msg']);
    }

    /**
     * 測試線上人數列表
     */
    public function testOnlineList()
    {
        $client = $this->createClient();
        $param = ['in_time' => 5];
        $client->request('GET', '/api/online/list', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);
        $this->assertEquals(9, $out['ret'][0]['domain']);
        $this->assertEquals(5678, $out['ret'][0]['user_id']);
        $this->assertEquals('lala', $out['ret'][0]['username']);
        $this->assertEquals(2, $out['ret'][1]['domain']);
        $this->assertEquals(1234, $out['ret'][1]['user_id']);
        $this->assertEquals('lala', $out['ret'][1]['username']);
    }

    /**
     * 測試依據使用者帳號回傳線上人數列表
     */
    public function testOnlineListByUsername()
    {
        $client = $this->createClient();
        $param = ['username' => 'lala'];
        $client->request('GET', '/api/online/list_by_username', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);
        $this->assertEquals(2, $out['ret'][0]['domain']);
        $this->assertEquals(1234, $out['ret'][0]['user_id']);
        $this->assertEquals('lala', $out['ret'][0]['username']);
        $this->assertEquals(9, $out['ret'][1]['domain']);
        $this->assertEquals(5678, $out['ret'][1]['user_id']);
        $this->assertEquals('lala', $out['ret'][1]['username']);
    }

    /**
     * 測試回傳線上人數
     */
    public function testTotalOnline()
    {
        $client = $this->createClient();
        $param = ['in_time' => 5];
        $client->request('GET', '/api/online/total', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);
        $this->assertEquals(2, $out['ret']);
    }

    /**
     * 測試建立一次性Session (One-Time Session)
     */
    public function testCreateOneTimeSession()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $sid = $out['ret']['session']['id'];

        $client->request('POST', '/api/session/' . $sid . '/ots');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $otsId = $out['ret'];

        $this->assertEquals('ok', $out['result']);
        $this->assertNotEmpty($out['ret']);

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $key = 'ots_' . $otsId;

        $this->assertEquals($redis->get($key), $sid);
    }

    /**
     * 測試取得一次性Session (One-Time Session)
     */
    public function testGetOneTimeSession()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $sid = $out['ret']['session']['id'];

        $client->request('POST', '/api/session/' . $sid . '/ots');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $otsId = $out['ret'];

        $client->request('GET', '/api/ots/' . $otsId);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);
        $this->assertEquals($sid, $out['ret']);

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $key = 'ots_' . $otsId;
        $this->assertFalse($redis->exists($key));
    }

    /**
     * 測試取得一次性Session但不存在
     */
    public function testGetOneTimeSessionButOneTimeSessionNotFound()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/ots/no_ots');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals('No one-time session found', $out['msg']);
        $this->assertEquals('150330016', $out['code']);
    }

    /**
     * 測試依據sessionId新增Session的研發資訊
     */
    public function testSetSessionRdInfo()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $sid = $out['ret']['session']['id'];

        $param = [
            'rd_info' => [
                'lobby_switch' => [
                    'key' => [
                        1,
                        2
                    ],
                    'value' => [
                        1,
                        0
                    ]
                ]
            ]
        ];
        $client->request('PUT', '/api/session/' . $sid . '/rd_info', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertTrue($out['ret']['rd_info']['lobby_switch']['1']);
        $this->assertFalse($out['ret']['rd_info']['lobby_switch']['2']);
    }

    /**
     * 測試依據Session Id修改研發Session資訊
     */
    public function testEditSessionRdInfo()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $sid = $out['ret']['session']['id'];

        $param = [
            'rd_info' => [
                'lobby_switch' => [
                    'key' => [
                        1
                    ],
                    'value' => [
                        1
                    ]
                ]
            ]
        ];
        $client->request('PUT', '/api/session/' . $sid . '/rd_info', $param);

        $param = [
            'rd_info' => [
                'lobby_switch' => [
                    'key' => [
                        1
                    ],
                    'value' => [
                        0
                    ]
                ]
            ]
        ];
        $client->request('PUT', '/api/session/' . $sid . '/rd_info', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertFalse($out['ret']['rd_info']['lobby_switch']['1']);
    }

    /**
     * 測試未提供研發session資訊
     */
    public function testNoRdInfoProvided()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $sid = $out['ret']['session']['id'];

        $client->request('PUT', '/api/session/' . $sid . '/rd_info', []);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150330017, $out['code']);
        $this->assertEquals('No rd_info specified', $out['msg']);
    }
}
