<?php

namespace BB\DurianBundle\Tests\Session;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class BrokerTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試根據SessionId來檢查使用者是否有 Session
     */
    public function testExistsBySessionId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        $user = $em->find('BBDurianBundle:User', 8);

        // 一開始沒有 Session
        $this->assertFalse($sessionBroker->existsByUserId($user->getId()));

        // 建立 Session
        $client->request('POST', '/api/user/8/session');

        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $this->assertEquals('ok', $out['result']);

        $sessionId = $out['ret']['session']['id'];
        $this->assertTrue($sessionBroker->existsBySessionId($sessionId));

        // 刪除 Session
        $sessionBroker->remove($sessionId);

        $this->assertFalse($sessionBroker->existsBySessionId($sessionId));
    }

    /**
     * 測試根據SessionId來檢查使用者是否有 Session，刪除的清單有資料
     */
    public function testExistsBySessionIdButRemoveListHasData()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        // 建立session前先用parent往下刪一層，建立後判斷該session應存在
        $redis = $this->getContainer()->get('snc_redis.cluster');
        $redis->lpush('session_remove_queue', '6,1,,2015-09-08 12:00:00');

        $parent = $em->find('BBDurianBundle:User', 6);
        $user = $em->find('BBDurianBundle:User', 7);
        $parentSessionId = $sessionBroker->create($parent);
        $userSessionId = $sessionBroker->create($user);

        $this->assertTrue($sessionBroker->existsBySessionId($parentSessionId));
        $this->assertTrue($sessionBroker->existsBySessionId($userSessionId));

        // 建立session後再用parent往下刪，parent與該使用者session不應存在
        $sessionBroker->pushToRemoveList(6, 1, null);
        $this->assertFalse($sessionBroker->existsBySessionId($parentSessionId));
        $this->assertFalse($sessionBroker->existsBySessionId($userSessionId));
    }

    /**
     * 測試根據使用者編號來檢查使用者是否有 Session
     */
    public function testExistsByUserId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        $user = $em->find('BBDurianBundle:User', 8);

        // 一開始沒有 Session
        $this->assertFalse($sessionBroker->existsByUserId($user->getId()));

        // 使用者登入成功，會建立 Session
        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3'
        ];
        $this->createClient()->request('PUT', '/api/login', $parameters);

        $this->assertTrue($sessionBroker->existsByUserId($user->getId()));
    }

    /**
     * 測試建立 Session
     */
    public function testCreate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        //啟用上層外接額度payway
        $parentPayway = $em->find('BBDurianBundle:UserPayway', 3);
        $parentPayway->enableOutside();
        $em->flush();

        $user = $em->find('BBDurianBundle:User', 7);
        $user->setSub(true);
        $user->subSize();
        $user->subSize();

        $this->assertFalse($sessionBroker->existsByUserId($user->getId()));

        $loginInfo['client_os']     = 'Windows';
        $loginInfo['ingress']       = 1;
        $loginInfo['last_login_ip'] = '127.0.0.1';

        $retSessionId = $sessionBroker->create($user, false, $loginInfo);

        $redis = $this->getContainer()->get('snc_redis.cluster');

        $mapKey = 'session_user_7_map';
        $sessionId = $redis->lindex($mapKey, 0);

        $domainKey = 'session_domain_2';
        $sessionKey = 'session_' . $sessionId;

        $this->assertEquals($sessionId, $retSessionId);

        $sesData = $redis->hgetall($sessionKey);
        $this->assertEquals($sessionId, $sesData['session:id']);
        $this->assertNotNull($sesData['session:created_at']);
        $this->assertEquals('', $sesData['session:modified_at']);

        $now = new \DateTime;
        $createddAt = new \DateTime($sesData['session:created_at']);
        $this->assertLessThan(5, abs($createddAt->diff($now)->format('%s')));

        $this->assertEquals(7, $sesData['user:id']);
        $this->assertEquals('ztester', $sesData['user:username']);
        $this->assertEquals(2, $sesData['user:domain']);
        $this->assertEquals('ztester', $sesData['user:alias']);
        $this->assertEquals('1', $sesData['user:sub']);
        $this->assertEquals('', $sesData['user:test']);
        $this->assertEquals('', $sesData['user:hidden_test']);
        $this->assertEquals('0', $sesData['user:size']);
        $this->assertArrayNotHasKey('user:err_num', $sesData);
        $this->assertEquals('TWD', $sesData['user:currency']);
        $this->assertEquals('2013-01-01T11:11:11+0800', $sesData['user:created_at']);
        $this->assertNotNull($sesData['user:modified_at']);
        $this->assertEquals('', $sesData['user:last_login']);
        $this->assertArrayNotHasKey('user:password_expire_at', $sesData);
        $this->assertArrayNotHasKey('user:password_reset', $sesData);
        $this->assertEquals(2, $sesData['user:role']);
        $this->assertEquals('6,5,4,3,2', $sesData['user:all_parents']);
        $this->assertEquals('TWD', $sesData['cash:currency']);
        $this->assertEquals('CNY', $sesData['cash_fake:currency']);
        $this->assertEquals(1, $sesData['credit']);
        $this->assertEquals(1, $sesData['card']);
        $this->assertEquals(1, $sesData['outside']);
        $this->assertEquals('Windows', $sesData['user:client_os']);
        $this->assertEquals(1, $sesData['user:ingress']);
        $this->assertEquals('127.0.0.1', $sesData['user:last_login_ip']);
    }

    /**
     * 測試建立 Session，但已存在 Session
     */
    public function testCreateWithSessionAlreadyExists()
    {
        $this->setExpectedException('RuntimeException', 'Session already exists', 150330002);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        $user = $em->find('BBDurianBundle:User', 7);
        $sessionBroker->create($user);

        // 重複建立
        $sessionBroker->create($user);
    }

    /**
     * 測試強制建立 Session，但已存在 Session會建立一個新的session
     */
    public function testForceCreateButSessionAlreadyExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        $user = $em->find('BBDurianBundle:User', 7);
        $sessionBroker->create($user);

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $mapKey = 'session_user_7_map';
        $oldSessionId = $redis->lindex($mapKey, 0);
        $oldSessionKey = 'session_' . $oldSessionId;

        // 強制重複建立session
        $sessionBroker->create($user, true);
        $newSessionId = $redis->lindex($mapKey, 0);
        $newSessionKey = 'session_' . $newSessionId;

        // 驗證有新建立session，且舊的session沒有被移除
        $this->assertNotEquals($oldSessionId, $newSessionId);
        $this->assertNotEmpty($redis->hgetall($oldSessionKey));
        $this->assertNotEmpty($redis->hgetall($newSessionKey));
    }

    /**
     * 測試根據 SessionId 取得 Session
     */
    public function testGetBySessionId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        $user = $em->find('BBDurianBundle:User', 7);
        $user->setSub(true);

        $sessionId = $sessionBroker->create($user);

        $ret = $sessionBroker->getBySessionId($sessionId);

        $this->assertEquals($sessionId, $ret['session']['id']);
        $this->assertNotNull($ret['session']['created_at']);
        $this->assertNull($ret['session']['modified_at']);

        $now = new \DateTime;
        $createddAt = new \DateTime($ret['session']['created_at']);
        $this->assertLessThan(5, abs($createddAt->diff($now)->format('%s')));

        $this->assertEquals(7, $ret['user']['id']);
        $this->assertEquals('ztester', $ret['user']['username']);
        $this->assertEquals(2, $ret['user']['domain']);
        $this->assertEquals('ztester', $ret['user']['alias']);
        $this->assertTrue($ret['user']['sub']);
        $this->assertFalse($ret['user']['test']);
        $this->assertFalse($ret['user']['hidden_test']);
        $this->assertEquals(2, $ret['user']['size']);
        $this->assertArrayNotHasKey('err_num', $ret['user']);
        $this->assertEquals('TWD', $ret['user']['currency']);
        $this->assertEquals('2013-01-01T11:11:11+0800', $ret['user']['created_at']);
        $this->assertNotNull($ret['user']['modified_at']);
        $this->assertNull($ret['user']['last_login']);
        $this->assertArrayNotHasKey('password_expire_at', $ret['user']);
        $this->assertArrayNotHasKey('password_reset', $ret['user']);
        $this->assertEquals(2, $ret['user']['role']);
        $this->assertEquals([6, 5, 4, 3, 2], $ret['user']['all_parents']);
        $this->assertNull($ret['user']['client_os']);
        $this->assertNull($ret['user']['ingress']);
        $this->assertNull($ret['user']['last_login_ip']);
    }

    /**
     * 測試根據 SessionId 取得 Session，但 Session 不存在
     */
    public function testGetBySessionIdWithoutSession()
    {
        $this->setExpectedException('RuntimeException', 'Session not found', 150330001);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $sessionBroker->getBySessionId('TheSessionIdDoesnotExist');
    }

    /**
     * 測試根據使用者編號取得 Session，但該session在移除清單中
     */
    public function testGetBySessionIdButSessionInRemoveList()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);
        $ret = $sessionBroker->getBySessionId($sessionId);

        $this->assertEquals($sessionId, $ret['session']['id']);
        $this->assertNotNull($ret['session']['created_at']);
        $this->assertEquals('', $ret['session']['modified_at']);

        $this->setExpectedException('RuntimeException', 'Session not found', 150330001);

        // 用parent往下刪一層的使用者
        $sessionBroker->pushToRemoveList(6, 1, null);
        $sessionBroker->getBySessionId($sessionId);
    }

    /**
     * 測試根據 UserId 取得 Session
     */
    public function testGetByUserId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        $user = $em->find('BBDurianBundle:User', 7);
        $user->setSub(true);

        $sessionId = $sessionBroker->create($user);

        $ret = $sessionBroker->getByUserId(7);

        $this->assertEquals($sessionId, $ret['session']['id']);
        $this->assertNotNull($ret['session']['created_at']);
        $this->assertNull($ret['session']['modified_at']);

        $now = new \DateTime;
        $createddAt = new \DateTime($ret['session']['created_at']);
        $this->assertLessThan(5, abs($createddAt->diff($now)->format('%s')));

        $this->assertEquals(7, $ret['user']['id']);
        $this->assertEquals('ztester', $ret['user']['username']);
        $this->assertEquals(2, $ret['user']['domain']);
        $this->assertEquals('ztester', $ret['user']['alias']);
        $this->assertTrue($ret['user']['sub']);
        $this->assertFalse($ret['user']['test']);
        $this->assertFalse($ret['user']['hidden_test']);
        $this->assertEquals(2, $ret['user']['size']);
        $this->assertArrayNotHasKey('err_num', $ret['user']);
        $this->assertEquals('TWD', $ret['user']['currency']);
        $this->assertEquals('2013-01-01T11:11:11+0800', $ret['user']['created_at']);
        $this->assertNotNull($ret['user']['modified_at']);
        $this->assertNull($ret['user']['last_login']);
        $this->assertArrayNotHasKey('password_expire_at', $ret['user']);
        $this->assertArrayNotHasKey('password_reset', $ret['user']);
        $this->assertEquals(2, $ret['user']['role']);
        $this->assertEquals([6, 5, 4, 3, 2], $ret['user']['all_parents']);
        $this->assertNull($ret['user']['client_os']);
        $this->assertNull($ret['user']['ingress']);
        $this->assertNull($ret['user']['last_login_ip']);
    }

    /**
     * 測試根據 UserId 取得 Session，但 Session 不存在
     */
    public function testGetByUserIdWithoutSession()
    {
        $this->setExpectedException('RuntimeException', 'Session not found', 150330001);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $sessionBroker->getByUserId(7);
    }

    /**
     * 測試刪除 Session 資料
     */
    public function testRemove()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $this->assertTrue($sessionBroker->existsByUserId($user->getId()));

        $sessionBroker->remove($sessionId);

        $redis = $this->getContainer()->get('snc_redis.cluster');


        $mapKey = 'session_user_7_map';
        $sessionKey = 'session_' . $sessionId;

        $this->assertTrue($redis->exists($mapKey));
        $this->assertFalse($redis->exists($sessionKey));
    }

    /**
     * 測試刪除使用者的 Session 資料
     */
    public function testRemoveByUserId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $this->assertTrue($sessionBroker->existsByUserId($user->getId()));

        $sessionBroker->removeByUserId(7);

        $mapKey = 'session_user_7_map';
        $sessionKey = 'session_' . $sessionId;

        $this->assertFalse($redis->exists($mapKey));
        $this->assertFalse($redis->exists($sessionKey));
    }

    /**
     * 測試建立刪除清單
     */
    public function testPushToRemoveList()
    {
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $sessionBroker->pushToRemoveList(7, 1, null);

        // 檢查有將要刪除的parnet、depth、role放進redis
        $deleteMsg = $redis->lindex('session_remove_queue', 0);
        $value = explode(',', $deleteMsg);
        $this->assertEquals(7,$value[0]);
        $this->assertEquals(1,$value[1]);
         $this->assertEmpty($value[2]);

        $now = new \DateTime;
        $createddAt = new \DateTime($value[3]);
        $this->assertLessThan(5, abs($createddAt->diff($now)->format('%s')));
    }

    /**
     * 測試設定 Session 維護資訊
     */
    public function testSetMaintainInfo()
    {
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $beginAt = new \Datetime('2015-12-12 00:00:00');
        $endAt = new \Datetime('2015-12-12 01:00:00');
        $msg = 'test';

        $sessionBroker->setMaintainInfo(1, $beginAt, $endAt, $msg);

        $maintainData = json_decode($redis->hget('session_maintain', 1), true);

        $this->assertEquals('2015-12-12 00:00:00', $maintainData['begin_at']);
        $this->assertEquals('2015-12-12 01:00:00', $maintainData['end_at']);
        $this->assertEquals('test', $maintainData['msg']);
    }

    /**
     * 測試取得 session 的維護資訊
     */
    public function testGetMaintainInfo()
    {
        $sessionBroker = $this->getContainer()->get('durian.session_broker');

        // 沒有在維護時間內回傳空陣列
        $maintainInfo = $sessionBroker->getMaintainInfo();
        $this->assertEmpty($maintainInfo);

        $beginAt = new \Datetime('now');
        $endAt = new \Datetime('now');
        $beginAt = $beginAt->modify('-1 hour');
        $endAt = $endAt->modify('+1 hour');

        // 設定維護時間驗證在維護時間內回傳維護資訊
        $sessionBroker->setMaintainInfo(1, $beginAt, $endAt, 'test');
        $maintainInfo = $sessionBroker->getMaintainInfo();
        $this->assertNotEmpty($maintainInfo[1]);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $maintainInfo[1]['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $maintainInfo[1]['end_at']);
        $this->assertEquals('test', $maintainInfo[1]['msg']);
    }

    /**
     * 測試新增 Session 白名單ip
     */
    public function testAddWhitelistIp()
    {
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $sessionBroker->addWhitelistIp('127.0.0.1');

        $this->assertTrue($redis->sismember('session_whitelist', '127.0.0.1'));
    }

    /**
     * 測試刪除 session 的白名單ip
     */
    public function testRemoveWhitelistIp()
    {
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $sessionBroker->addWhitelistIp('127.0.0.1');
        $sessionBroker->addWhitelistIp('127.0.0.2');

        $sessionBroker->removeWhitelistIp('127.0.0.1');

        $this->assertFalse($redis->sismember('session_whitelist', '127.0.0.1'));
        $this->assertTrue($redis->sismember('session_whitelist', '127.0.0.2'));
    }

    /**
     * 測試根據sessionId自訂研發資訊
     */
    public function testSetSessionRdInfo()
    {
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);

        $sessionId = $sessionBroker->create($user);
        $rdInfo = [
            'lobby_switch' => [
                'key' => [
                    1
                ],
                'value' => [
                    1
                ]
            ]
        ];
        $ret = $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);

        $this->assertTrue($ret['rd_info']['lobby_switch']['1']);

        // 變更資訊
        $rdInfo = [
            'lobby_switch' => [
                'key' => [
                    1
                ],
                'value' => [
                    0
                ]
            ]
        ];
        $ret = $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);

        $this->assertFalse($ret['rd_info']['lobby_switch']['1']);
    }

    /**
     * 測試根據sessionId自訂研發資訊但Session不存在
     */
    public function testSetSessionRdInfoButSessionNotFound()
    {
        $this->setExpectedException('RuntimeException', 'Session not found', 150330001);

        $rdInfo = [
            'lobby_switch' => [
                'key' => [
                    1
                ],
                'value' => [
                    0
                ]
            ]
        ];

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $sessionBroker->setSessionRdInfo('Invalid Session Id', $rdInfo);
    }

    /**
     * 測試驗證大廳開關資訊長度不合法
     */
    public function testValidateRdInfoLobbySwitchOverflow()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid lobby_switch length given', 150330019);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $lobby_switch['lobby_switch']['key'] = [100];
        $lobby_switch['lobby_switch']['value'] = [0];
        for ($count = 101; $count < 200; $count++) {
            array_push($lobby_switch['lobby_switch']['key'], $count);
            array_push($lobby_switch['lobby_switch']['value'], 0);
        }

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $lobby_switch);
    }

    /**
     * 測試大廳開關資訊鍵與值長度不合
     */
    public function testRdInfoLobbySwitchKeyValueLengthNotMatch()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid lobby_switch key value length given', 150330020);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rdInfo = [
            'lobby_switch' => [
                'key' => [
                    1,
                    2
                ],
                'value' => [
                    1
                ]
            ]
        ];

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);
    }

    /**
     * 測試大廳開關資訊鍵錯誤格式
     */
    public function testHandleWrongRdInfoLobbySwitchKey()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid lobby_switch key given', 150330022);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rdInfo = [
            'lobby_switch' => [
                'key' => [
                    'a'
                ],
                'value' => [
                    1
                ]
            ]
        ];

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);
    }

    /**
     * 測試大廳開關資訊鍵未提供
     */
    public function testRdInfoLobbySwitchKeyNotSpecified()
    {
        $this->setExpectedException('InvalidArgumentException', 'No lobby_switch key specified', 150330027);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rdInfo = [
            'lobby_switch' => [
                'key' => [
                    ''
                ],
                'value' => [
                    1
                ]
            ]
        ];

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);
    }

    /**
     * 測試大廳開關資訊值未提供
     */
    public function testHandleWrongRdInfoLobbySwitchValue()
    {
        $this->setExpectedException('InvalidArgumentException', 'No lobby_switch value specified', 150330023);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rdInfo = [
            'lobby_switch' => [
                'key' => [
                    1
                ],
                'value' => [
                    ''
                ]
            ]
        ];

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);
    }

    /**
     * 測試自訂客端主Domain資訊
     */
    public function testSetMemDomain()
    {
        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);

        $sessionId = $sessionBroker->create($user);
        $rdInfo = [
            'mem_domain' => 'https://bbin.com'
        ];
        $ret = $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);

        $this->assertEquals('https://bbin.com', $ret['rd_info']['mem_domain']);

        // 變更資訊
        $rdInfo = [
            'mem_domain' => 'https://bbos.com'
        ];
        $ret = $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);

        $this->assertEquals('https://bbos.com', $ret['rd_info']['mem_domain']);
    }

    /**
     * 測試客端主Domain資訊未提供
     */
    public function testMemDomainNotSpecified()
    {
        $this->setExpectedException('InvalidArgumentException', 'No mem_domain specified', 150330025);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rdInfo = [
            'mem_domain' => ''
        ];

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);
    }

    /**
     * 測試驗證自訂客端主Domain資訊長度不合法
     */
    public function testValidateMemDomainOverflow()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid mem_domain length given', 150330024);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $text = 'abcdefghijklmnopqrstuvwxyz';
        for ($count = 0; $count < 3; $count++) {
            $text = $text . $text;
        }

        $rdInfo = [
            'mem_domain' => 'https://bbin.' . $text . '.com'
        ];

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);
    }

    /**
     * 測試驗證客端主Domain資訊格式不合法
     */
    public function testValidateMemDomainFormat()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid mem_domain given', 150330026);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rdInfo = [
            'mem_domain' => 'test'
        ];

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);
    }

    /**
     * 測試驗證客端主Domain資訊包含空格
     */
    public function testMemDomainHasSpace()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid mem_domain given', 150330026);

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rdInfo = [
            'mem_domain' => 'http://test bb'
        ];

        // 建立session
        $user = $em->find('BBDurianBundle:User', 7);
        $sessionId = $sessionBroker->create($user);

        $sessionBroker->setSessionRdInfo($sessionId, $rdInfo);
    }
}
