<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\LoginLog;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Entity\LoginErrorPerIp;
use BB\DurianBundle\Entity\UserLevel;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserPassword;
use BB\DurianBundle\Controller\LoginController;
use Symfony\Component\HttpFoundation\Request;

class LoginFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLoginLogData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLastLoginData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthUserBindingData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPresetLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelUrlData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadIpBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipVersionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipBlockData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCountryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCityData'
        ];
        $this->loadFixtures($classnames, 'share');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLoginLogData'
        ];
        $this->loadFixtures($classnames, 'his');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('user_seq', 20000000);

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $redis->flushdb();
    }

    /**
     * 測試登入
     */
    public function testUserLogin()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $user->setSub(true);
        $em->flush();

        //原本錯誤次數為2次
        $this->assertEquals(2, $user->getErrNum());

        $em->clear();

        $ipv6 = '2015:0011:1000:AC21:FE02:BEEE:DF02:123C';
        $ua = "Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; es-es) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B360 Safari/531.21.10";

        $parameters = [
            'username' => 'tester',
            'ip' => '42.4.2.168',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'language' => 2,
            'host' => 'esball.com',
            'ipv6' => $ipv6,
            'client_os' => 1,
            'client_browser' => 5,
            'ingress' => 4,
            'device_name' => 'tester的ZenFone 3',
            'brand' => 'ASUS',
            'model' => 'Z017DA',
            'user_agent' => $ua,
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124, 111.111.111.123, 222.222.222.223'
        ];

        // 測試帶入不存在的session,不會噴錯
        $headers['HTTP_VERIFY_SESSION'] = '1';
        $headers['HTTP_SESSION_ID'] = 'test123';
        $client->request('PUT', '/api/login', $parameters, [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->clear();

        // 檢察回傳資料
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['parent']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['all_parents'][0]);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertEquals(0, $output['ret']['login_user']['err_num']); //錯誤次數是否歸零
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查log寫入是否正確
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $logMobile = $em->find('BBDurianBundle:LoginLogMobile', 9);
        $user = $em->getRepository('BB\DurianBundle\Entity\User')
                   ->findOneByUsername('tester');

        $this->assertEquals($user->getId(), $log->getUserId());
        $this->assertEquals('42.4.2.168', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $log->getResult());
        $this->assertEquals($user->getLastLogin(), $log->getAt());
        $this->assertNotNull($log->getSessionId());
        $this->assertEquals($output['ret']['login_user']['session_id'], $log->getSessionId());
        $this->assertEquals(1, $log->getRole());
        $this->assertEquals('tester', $log->getUsername());
        $this->assertEquals('zh-tw', $log->getLanguage());
        $this->assertEquals($ipv6, $log->getIpv6());
        $this->assertEquals('esball.com', $log->getHost());
        $this->assertEquals('Windows', $log->getClientOs());
        $this->assertEquals('', $log->getClientBrowser());
        $this->assertEquals(4, $log->getIngress());
        $this->assertEquals('184.146.232.251', $log->getProxy1());
        $this->assertEquals('184.146.232.251', $log->getProxy2());
        $this->assertEquals('172.16.168.124', $log->getProxy3());
        $this->assertEquals('111.111.111.123', $log->getProxy4());
        $this->assertEquals('中華人民共和國', $log->getCountry());
        $this->assertEquals('北京', $log->getCity());
        $this->assertEquals(3, $log->getEntrance());
        $this->assertTrue($log->isSub());
        $this->assertFalse($log->isOtp());
        $this->assertFalse($log->isSlide());
        $this->assertFalse($log->isTest());
        $this->assertEquals('tester的ZenFone 3', $logMobile->getName());
        $this->assertEquals('ASUS', $logMobile->getBrand());
        $this->assertEquals('Z017DA', $logMobile->getModel());

        // 檢查 Session 資料
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $mapKey = 'session_user_8_map';
        $sessionKey = 'session_' . $redis->lindex($mapKey, 0);
        $cmpSessionKey = sprintf(
            'session_%s',
            $output['ret']['login_user']['session_id']
        );
        $this->assertEquals($sessionKey, $cmpSessionKey);
        $this->assertTrue($redis->exists($sessionKey));

        $sessionData = $redis->hgetall($sessionKey);
        $this->assertEquals(8, $sessionData['user:id']);
        $this->assertEquals('tester', $sessionData['user:username']);
        $this->assertEquals('7,6,5,4,3,2', $sessionData['user:all_parents']);
        $this->assertEquals('Windows', $sessionData['user:client_os']);
        $this->assertEquals(4, $sessionData['user:ingress']);
        $this->assertEquals('42.4.2.168', $sessionData['user:last_login_ip']);

        $ttl = 3600;
        $redis->expire($sessionKey, $ttl);
        $redis->expire($mapKey, $ttl);

        $oldSessionId = $redis->lindex($mapKey, 0);

        // 確認 x_forwarded_for 完整資訊有記在 post log 裡面
        $logPath = $this->getLogfilePath('post.log');
        $this->assertFileExists($logPath);

        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "x_forwarded_for={$parameters['x_forwarded_for']}";

        $this->assertContains($line, $results[0]);
        $this->assertEmpty($results[1]);

        // 檢查最後成功登入
        $last = $em->find('BBDurianBundle:LastLogin', 8);
        $this->assertEquals('42.4.2.168', $last->getIP());
        $this->assertEquals(9, $last->getLoginLogId());

        // 同一使用者再次登入
        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查sessionId是否有更新及建立新的session
        $sessionId = $redis->lindex($mapKey, 0);
        $this->assertNotEquals($oldSessionId, $sessionId);
        $this->assertEquals($sessionId, $output['ret']['login_user']['session_id']);
        $sessionKey = 'session_' . $sessionId;
        $this->assertTrue($redis->exists($sessionKey));

        // 檢查舊的session已被刪掉
        $sessionKey = 'session_' . $oldSessionId;
        $this->assertFalse($redis->exists($sessionKey));
    }

    /**
     * 測試允許重複登入
     */
    public function testUserLoginWithDuplicateLogin()
    {
        $parameters = [
            'username' => 'tester',
            'ip'       => '42.4.2.168',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3',
            'lang'     => 'zh-tw',
            'host'     => 'esball.com',
            'ipv6'     => '2015:0011:1000:AC21:FE02:BEEE:DF02:123C',
            'os'       => 'ios'
        ];

        $output = $this->getResponse('PUT', '/api/login', $parameters);

        // 檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查 Session 資料
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $mapKey = 'session_user_8_map';
        $sessionKey = 'session_' . $redis->lindex($mapKey, 0);
        $oldSessionId = $output['ret']['login_user']['session_id'];
        $oldSessionKey = 'session_' . $oldSessionId ;

        $this->assertEquals($sessionKey, $oldSessionKey);
        $this->assertTrue($redis->exists($sessionKey));

        // 同一使用者再次登入,並設定允許重複登入
        $parameters['duplicate_login'] = 1;
        $output = $this->getResponse('PUT', '/api/login', $parameters);

        // 檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查sessionId是否有更新及建立新的session
        $sessionId = $redis->lindex($mapKey, 0);
        $newSessionId = $output['ret']['login_user']['session_id'];
        $this->assertNotEquals($oldSessionId, $newSessionId);
        $this->assertEquals($sessionId, $newSessionId);
        $newSessionKey = 'session_' . $newSessionId;
        $this->assertTrue($redis->exists($newSessionKey));

        // 檢查舊的session沒有被刪掉
        $this->assertTrue($redis->exists($oldSessionKey));
    }

    /**
     * 測試登入,密碼輸入大寫
     */
    public function testUserLoginWithUpperPwd()
    {
        // 先刪除userId=10的oauth綁定資料
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->findOneBy(['userId' => 10]);

        $em->remove($binding);
        $em->flush();

        $config = new DomainConfig(10, 'domain10', 'zz');
        $emShare->persist($config);
        $emShare->flush();

        $client = $this->createClient();

        $parameters = [
            'username' => 'gaga',
            'ip'       => '42.4.2.168',
            'domain'   => '9',
            'password' => 'GAGAGAGA',
            'entrance' => '2',
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入代碼不存在
     */
    public function testUserLoginWithLoginCodeNotExist()
    {
        $client = $this->createClient();

        $parameters = [
            'ip'       => 'this.is.ip.address',
            'username' => 'tester@gg',
            'password' => '123456',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150250009, $output['code']);
        $this->assertEquals('No login code found', $output['msg']);
    }

    /**
     * 測試登入,試密碼因超出限制,加入封鎖列表中
     */
    public function testUserLoginAndAddBlacklist()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $clientSetConfig = $this->createClient();

        // 廳設定阻擋登入
        $clientSetConfig->request('PUT', '/api/domain/2/config', ['block_login' => 1]);

        $json = $clientSetConfig->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 設定ip 127.0.0.1已登入錯誤次數
        $now = new \DateTime('now');

        $stat = new LoginErrorPerIp('127.0.0.1', $now, 2);
        $stat->addCount(DomainConfig::MAX_ERROR_PWD_TIMES - 1);
        $emShare->persist($stat);
        $emShare->flush();

        $client = $this->createClient();

        $parameters = [
            'username' => 'tester',
            'ip'       => '127.0.0.1',
            'domain'   => '2',
            'password' => '1234567',
            'entrance' => '3'
        ];
        $client->request('PUT', '/api/login', $parameters);

        // 驗證是否有加入封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertEquals(2, $list->getDomain());
        $this->assertEquals('127.0.0.1', $list->getIp());
        $this->assertFalse($list->isCreateUser());
        $this->assertTrue($list->isLoginError());
        $this->assertFalse($list->isRemoved());
        $this->assertEquals('', $list->getOperator());

        // 檢查log operation是否有封鎖列表資料
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $arrLogOp = explode(', ', $logOp->getMessage());
        $this->assertEquals('ip_blacklist', $logOp->getTableName());
        $this->assertEquals('@domain:2', $arrLogOp[0]);
        $this->assertEquals('@ip:127.0.0.1', $arrLogOp[1]);
        $this->assertEquals('@login_error:true', $arrLogOp[2]);
        $this->assertEquals('@removed:false', $arrLogOp[3]);
        $this->assertEquals('@operator:', $arrLogOp[6]);
    }

    /**
     * 測試登入,試密碼因超出限制,且時效內的封鎖列表中已存在該筆ip資料
     */
    public function testUserLoginAndBlacklistHasRecord()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $clientSetConfig = $this->createClient();

        // 廳設定阻擋登入
        $clientSetConfig->request('PUT', '/api/domain/2/config', ['block_login' => 1]);

        $json = $clientSetConfig->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $client = $this->createClient();
        $client->request('DELETE', '/api/domain/ip_blacklist', ['blacklist_id' => 3]);

        // 設定ip 111.235.135.3已登入錯誤次數
        $now = new \DateTime('now');

        $stat = new LoginErrorPerIp('111.235.135.3', $now, 2);
        $stat->addCount(DomainConfig::MAX_ERROR_PWD_TIMES);
        $em->persist($stat);
        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip'       => '111.235.135.3',
            'domain'   => '2',
            'password' => '1234567',
            'entrance' => '3'
        ];
        $client->request('PUT', '/api/login', $parameters);

        $json = $clientSetConfig->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 因封鎖列表已存在該筆ip資料,驗證是否有再次加入封鎖列表
        $list = $em->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertNull($list);
    }

    /**
     * 測試跨廳登入,試密碼因超出限制,封鎖列表已有一筆紀錄,會一併加入黑名單
     */
    public function testUserLoginAndAddIpBlaclklistAndBlacklist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $clientSetConfig = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(9);
        $em->persist($user);
        $em->flush();

        // 廳設定阻擋登入
        $clientSetConfig->request('PUT', '/api/domain/9/config', ['block_login' => 1]);

        $json = $clientSetConfig->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 設定ip 218.26.54.4已登入錯誤次數
        $now = new \DateTime('now');

        $stat = new LoginErrorPerIp('218.26.54.4', $now, 9);
        $stat->addCount(DomainConfig::MAX_ERROR_PWD_TIMES - 1);
        $emShare->persist($stat);
        $emShare->flush();

        $client = $this->createClient();

        $parameters = [
            'username' => 'tester',
            'ip'       => '218.26.54.4',
            'domain'   => '9',
            'password' => '1234567',
            'entrance' => '3'
        ];
        $client->request('PUT', '/api/login', $parameters);

        // 驗證有再次加入封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertEquals('218.26.54.4', $list->getIp());
        $this->assertEquals(9, $list->getDomain());
        $this->assertFalse($list->isCreateUser());
        $this->assertTrue($list->isLoginError());
        $this->assertFalse($list->isRemoved());
        $this->assertEquals('', $list->getOperator());

        // 驗證有加入黑名單
        $blackList = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('218.26.54.4', $blackList->getIp());
        $this->assertTrue($blackList->isWholeDomain());
        $this->assertEmpty($blackList->getDomain());

        // 檢查操作紀錄
        $repo = $emShare->getRepository('BBDurianBundle:LogOperation');
        $logOperation = $repo->findOneBy(['tableName' => 'ip_blacklist']);
        $arrLogOperation = explode(', ', $logOperation->getMessage());
        $this->assertEquals('ip_blacklist', $logOperation->getTableName());
        $this->assertEquals('@domain:9', $arrLogOperation[0]);
        $this->assertEquals('@ip:218.26.54.4', $arrLogOperation[1]);
        $this->assertEquals('@login_error:true', $arrLogOperation[2]);
        $this->assertEquals('@removed:false', $arrLogOperation[3]);
        $this->assertEquals('@operator:', $arrLogOperation[6]);

        $logOperation = $repo->findOneBy(['tableName' => 'blacklist']);
        $arrLogOperation = explode(', ', $logOperation->getMessage());
        $this->assertEquals('blacklist', $logOperation->getTableName());
        $this->assertEquals('@whole_domain:true', $arrLogOperation[0]);
        $this->assertEquals('@ip:218.26.54.4', $arrLogOperation[1]);

        $blackLog = $emShare->find('BBDurianBundle:BlacklistOperationLog', 1);
        $this->assertEquals('system', $blackLog->getCreatedOperator());
        $this->assertEquals('登入密碼錯誤超過限制', $blackLog->getnote());
    }

    /**
     * 測試同分秒同廳同IP登入錯誤的狀況
     */
    public function testLoginErrorWithDuplicateEntry()
    {
        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $mockEmShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepoShare = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConnShare = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepoShare));

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEmShare->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConnShare));

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception(
            'Duplicate entry login_error_per_ip-uni_login_error_ip_at_domain for key 1',
            150250014,
            $pdoExcep
        );

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $parameters = [
            'username' => 'tester',
            'ip'       => '127.0.0.1',
            'domain'   => '2',
            'password' => '1234567',
            'entrance' => '3'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150250014, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);

        // 測試因last_login造成的錯誤狀況
        $exception = new \Exception(
            'Duplicate entry last_login for key 1',
            150250029,
            $pdoExcep
        );

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150250029, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);

        // 測試同分秒新增黑名單
        $exception = new \Exception(
            'Duplicate entry for key uni_blacklist_domain_ip',
            150250032,
            $pdoExcep
        );

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(9, $ret['ret']['login_result']);

        // 測試同分秒新增封鎖列表
        $exception = new \Exception(
            'Duplicate entry for key uni_ip_blacklist_domain_ip_created_date',
            150250033,
            $pdoExcep
        );

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(9, $ret['ret']['login_result']);

        // 檢查沒有寫入message_queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $this->assertEquals(0, $redis->llen('message_queue'));
    }

    /**
     * 測試登入時使用者名稱前後有空白
     */
    public function testLoginWithWhiteSpace()
    {
        $client = $this->createClient();

        $parameters = array(
            'username' => ' tester ',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3'
        );

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('tester', $output['ret']['login_user']['username']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入的帳號不存在
     */
    public function testLoginWhenUserNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        $parameters = array(
            'username' => 'alibaba',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3'
        );

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertNull($log);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']['login_user']);
        $this->assertEquals(LoginLog::RESULT_USERNAME_WRONG, $output['ret']['login_result']);

        $this->assertEquals(0, $redis->llen('login_log_queue'));
    }

    /**
     * 測試從行動裝置登入，但登入的帳號不存在
     */
    public function testLoginOnMobileWhenUserNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'username' => 'alibaba',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'ingress'  => '2',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']['login_user']);
        $this->assertEquals(LoginLog::RESULT_USERNAME_WRONG, $output['ret']['login_result']);

        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $logMobile = $em->find('BBDurianBundle:LoginLogMobile', 9);

        $this->assertNull($log);
        $this->assertNull($logMobile);
        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
    }

    /**
     * 測試無密碼使用者登入
     */
    public function testNoPasswordUserLogin()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id'         => '7',
            'username'          => 'chosen1',
            'password'          => 'chosen1',
            'disabled_password' => true,
            'alias'             => 'chosen',
            'role'              => '1',
            'currency'          => 'TWD'
        );

        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $userPassword = $em->find('BBDurianBundle:UserPassword', $output['ret']['id']);
        $this->assertEquals('', $userPassword->getHash());

        $parameters = array(
            'username' => 'chosen1',
            'domain'   => '2',
            'password' => '123456',
            'ip'       => '127.0.0.1',
            'entrance' => '3'
        );

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(12, $output['ret']['login_result']);
    }

    /**
     * 測試無密碼使用者登入，採用使用者密碼資料表驗證
     */
    public function testNoPasswordUserLoginVerifiedByUserPassword()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id'         => '7',
            'username'          => 'chosen1',
            'password'          => 'chosen1',
            'disabled_password' => true,
            'alias'             => 'chosen',
            'role'              => '1',
            'currency'          => 'TWD'
        ];

        // disabled_password帶true，讓使用者密碼為空
        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $parameters = [
            'username' => 'chosen1',
            'domain'   => '2',
            'password' => '123456',
            'ip'       => '127.0.0.1',
            'entrance' => '3'
        ];

        // 登入測試密碼是否停用
        $client->request('PUT', '/api/login', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(
            LoginLog::RESULT_USER_DISABLED_PASSWORD,
            $output['ret']['login_result']
        );
    }

    /**
     * domain沒設定或設定不阻擋封鎖列表ip,是否可正常登入
     */
    public function testUserLoginUnBlockIp()
    {
        $client = $this->createClient();

        // 測試domain沒有設定要阻擋登入設定
        $parameters = [
            'username' => 'isolate',
            'ip'       => '126.0.0.1',
            'domain'   => '9',
            'password' => '1234567',
            'platform' => 'windows',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 廳設定不阻擋登入
        $clientSetConfig = $this->createClient();
        $clientSetConfig->request('PUT', '/api/domain/2/config', ['block_login' => 0]);
        $json = $clientSetConfig->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 測試domain設定不阻擋登入
        $parameters = [
            'username' => 'tester',
            'ip'       => '126.0.0.1',
            'domain'   => '2',
            'password' => '1234567',
            'platform' => 'windows',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試手勢密碼登入,ip在封鎖列表中,且廳設定阻擋登入,但發生Flush錯誤
     */
    public function testUserLoginWithBlacklistIpButFlushError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        // 設定廳阻擋登入
        $output = $this->getResponse('PUT', '/api/domain/2/config', ['block_login' => 1]);
        $this->assertEquals('ok', $output['result']);

        $user = $em->getRepository('BBDurianBundle:User')
            ->findOneBy(['domain' => 2, 'username' => 'tester']);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockRepo->expects($this->at(0))
            ->method('findOneBy')
            ->will($this->returnValue($user));

        $mockEm->expects($this->at(5))
            ->method('flush')
            ->will($this->throwException(new \RuntimeException('Database is busy', 150010071)));

        $parameters = [
            'username' => 'tester',
            'ip' => '111.235.135.3',
            'domain' => '2',
            'password' => '1234567',
            'ingress' => '4',
            'entrance' => '3',
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        // 檢查是否有寫入login_log
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertNull($log);

        // 檢查是否有寫入login_log_mobile
        $mobileLog = $em->find('BBDurianBundle:LoginLogMobile', 9);
        $this->assertNull($mobileLog);

        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
    }

    /**
     * 測試登入的ip在封鎖列表中,且廳設定阻擋登入,是否有被擋在某廳
     */
    public function testUserLoginWithBlacklistIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);

        // 設定廳阻擋登入
        $client->request('PUT', '/api/domain/2/config', ['block_login' => 1]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 測試輸入封鎖列表內同廳與同IP,應被擋下來
        $parameters = [
            'username' => 'tester',
            'ip' => '111.235.135.3',
            'domain' => '2',
            'password' => '1234567',
            'entrance' => '3',
            'ingress' => 4,
            'device_name' => 'tester的ZenFone 3',
            'brand' => 'ASUS',
            'model' => 'Z017DA',
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['login_user']['id']);
        $this->assertEquals($user->getErrNum(), $output['ret']['login_user']['err_num']);
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST, $output['ret']['login_result']);

        // 檢查是否有寫入login_log
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals(2, $log->getDomain());
        $this->assertEquals('111.235.135.3', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST, $log->getResult());
        $this->assertEquals(8, $log->getUserId());
        $this->assertEquals(1, $log->getRole());
        $this->assertFalse($log->isSub());
        $this->assertEquals('tester', $log->getUsername());
        $this->assertEmpty($log->getSessionId());
        $this->assertEquals('184.146.232.251', $log->getProxy1());
        $this->assertEquals('184.146.232.251', $log->getProxy2());
        $this->assertEquals('172.16.168.124', $log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertEquals('馬來西亞', $log->getCountry());
        $this->assertEquals('吉隆坡', $log->getCity());
        $this->assertEquals(3, $log->getEntrance());
        $this->assertFalse($log->isOtp());
        $this->assertFalse($log->isSlide());
        $this->assertFalse($log->isTest());

        // 檢查是否有寫入login_log_mobile
        $logMobile = $em->find('BBDurianBundle:LoginLogMobile', 9);
        $this->assertEquals('tester的ZenFone 3', $logMobile->getName());
        $this->assertEquals('ASUS', $logMobile->getBrand());
        $this->assertEquals('Z017DA', $logMobile->getModel());

        $this->assertEquals(1, $redis->llen('login_log_queue'));

        // 測試輸入封鎖列表內不同廳同IP,不應被擋下來
        $parameters = [
            'username' => 'isolate',
            'ip'       => '111.235.135.3',
            'domain'   => '9',
            'password' => '1234567',
            'platform' => 'windows',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 測試封鎖列表被移除,不應被擋下來
        $client->request('DELETE', '/api/domain/ip_blacklist', ['blacklist_id' => 3]);

        $parameters = [
            'username' => 'tester',
            'ip'       => '111.235.135.3',
            'domain'   => '2',
            'password' => '1234567',
            'entrance' => '3'
        ];
        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試登入的ip在封鎖列表中，且廳設定阻擋登入，是否有被擋在某廳，但使用者不存在
     */
    public function testUserLoginWithBlacklistIpButUserNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 設定廳阻擋登入
        $client->request('PUT', '/api/domain/2/config', ['block_login' => 1]);

        // 測試輸入封鎖列表內同廳與同IP,應被擋下來
        $parameters = [
            'username' => 'testerrrrrr',
            'ip' => '111.235.135.3',
            'domain' => '2',
            'password' => '1234567',
            'platform' => 'windows',
            'entrance' => '3',
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124'
        ];

        $client->request('PUT', '/api/login', $parameters);

        // 檢查是否有寫入login_log
        $log = $em->find('BBDurianBundle:LoginLog', 9);
                $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST, $log->getResult());
        $this->assertEquals(0, $log->getUserId());
        $this->assertEquals('testerrrrrr', $log->getUsername());
    }

    /**
     * 測試登入錯誤的entrance
     */
    public function testUserLoginWithWrongEntrance()
    {
        $client = $this->createClient();

        // 傳錯entrance
        $parameters = array(
            'username' => 'tester',
            'domain'   => '2',
            'ip'       => 'this.is.ip.address',
            'password' => '123456',
            'entrance' => '1'
        );

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('2', $output['ret']['login_user']['err_num']);
        $this->assertFalse(array_key_exists('last_bank', $output['ret']['login_user']));
        $this->assertFalse(array_key_exists('username', $output['ret']['login_user']));
        $this->assertEquals(
            LoginLog::RESULT_USERNAME_WRONG,
            $output['ret']['login_result']
        );
    }

    /**
     * 測試在阻擋的時間區間內重複登入
     */
    public function testLoginInLimitInterval()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);

        // 設定登入紀錄為5秒前
        $date = new \DateTime();
        $date->modify('-5 sec');
        $user->setLastLogin($date);

        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => 2,
            'password' => '123456',
            'entrance' => 3,
            //設定10秒內判斷為重複登入
            'last_login_interval' => 10
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_DUPLICATED_WITHIN_TIME, $output['ret']['login_result']);
    }

    /**
     * 測試不在阻擋的區間內重複登入
     */
    public function testLoginNotInLimitInterval()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);

        // 設定登入紀錄為15秒前
        $date = new \DateTime();
        $date->modify('-15 sec');
        $user->setLastLogin($date);

        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3',
            //設定10秒內判斷為重複登入
            'last_login_interval' => 10
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試阻擋區間內重複登入但使用者沒有登入過
     */
    public function testLoginNotInLimitIntervalAndUserNeverLogin()
    {
        $client = $this->createClient();

        $parameters = [
            'username' => 'isolate',
            'ip' => 'this.is.ip.address',
            'domain' => '9',
            'password' => '123456',
            'entrance' => '2',
            'last_login_interval' => 10
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('9', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入帶入的 parent_id 非此使用者上層
     */
    public function testLoginNotInHierarchy()
    {
        $client = $this->createClient();

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3',
            //設定非此使用者的上層ID
            'verify_parent_id' => [999]
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_NOT_IN_HIERARCHY, $output['ret']['login_result']);
    }

    /**
     * 測試登入帶入的 parent_id 為此使用者上層
     */
    public function testLoginInHierarchy()
    {
        $client = $this->createClient();

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3',
            //設定此使用者的上層ID
            'verify_parent_id' => [6, 999]
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 帶入非陣列
        $parameters['verify_parent_id'] = 6;

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入驗證層級，使用者層級沒有綁定網址，但帶入的登入網址啟用且有綁定層級
     */
    public function testLoginUserLevelDonotHasUrlButHostIsEnableLevelUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'host' => 'acc.com',
            'verify_level' => true
        ];

        // 修改使用者層級使之沒有對應網址
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(1);
        $em->flush();

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_LEVEL_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試登入驗證層級，帶入的登入網址多帶www
     */
    public function testLoginUserLevellAndHostWithWWW()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'host' => 'www.acc.com',
            'verify_level' => true
        ];

        // 修改使用者層級使之沒有對應網址
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(1);
        $em->flush();

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_LEVEL_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試登入驗證層級，使用者層級沒有綁定網址，但帶入的登入網址非啟用且有綁定層級
     */
    public function testLoginUserLevelDonotHasUrlButHostIsDisableLevelUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'host' => 'acc.net',
            'verify_level' => true
        ];

        // 修改使用者層級使之沒有對應網址
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(1);
        $em->flush();

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_LEVEL_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試登入驗證層級，使用者層級沒有綁定網址，且登入網址沒有綁定層級
     */
    public function testLoginVerifyLevelButUserLevelDonotBindUrlAndHostDonotHaveLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'host' => '789.789',
            'verify_level' => true
        ];

        // 修改使用者層級使之沒有對應網址
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(1);
        $em->flush();

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入驗證層級，使用者層級有綁定網址，與登入網址相符
     */
    public function testLoginVerifyLevelAndUserLevelUrlMatchHostLevelUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'host' => 'cde.cde',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證沒有回傳導向網址
        $this->assertArrayNotHasKey('redirect_url', $output['ret']);
    }

    /**
     * 測試登入驗證層級，使用者層級有綁定網址，但與登入網址不符
     */
    public function testLoginVerifyLevelAndUserLevelUrlDiffWithHostLevelUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'host' => 'acc.com',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證有回傳導向網址
        $this->assertEquals('cde.cde', $output['ret']['redirect_url']);
    }

    /**
     * 測試登入驗證層級，使用者層級有綁定網址，但登入網址沒有綁定層級
     */
    public function testLoginVerifyLevelAndUserLevelUrlButHostDonotHaveLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'host' => '789.789',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證有回傳導向網址
        $this->assertEquals('cde.cde', $output['ret']['redirect_url']);
    }

    /**
     * 測試登入驗證層級，使用者沒有層級
     */
    public function testLoginVerifyLevelButUserDonotHaveLevel()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $config = new DomainConfig(50, 'domain50', 'xx');
        $emShare->persist($config);
        $emShare->flush();

        $client = $this->createClient();

        $parameters = [
            'username' => 'vtester2',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '2',
            'host' => 'acc.com',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('50', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入密碼錯誤
     */
    public function testUserLoginWithErrorPassword()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 原本有兩次登入錯誤記錄, 先清空
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $user->zeroErrNum();
        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);
        $userPassword->zeroErrNum();
        $em->persist($user);
        $em->flush();
        $em->clear();

        // 廳設定阻擋登入
        $clientSetConfig = $this->createClient();
        $clientSetConfig->request('PUT', '/api/domain/2/config', ['block_login' => 1]);

        $json = $clientSetConfig->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 密碼錯誤,試密碼次數未超出限制,不加入封鎖列表
        $parameters = [
            'username' => 'tester',
            'ip'       => '192.157.111.25',
            'domain'   => '2',
            'password' => '456>.^',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('1', $output['ret']['login_user']['err_num']);
        $this->assertFalse(array_key_exists('last_bank', $output['ret']['login_user']));
        $this->assertFalse(array_key_exists('username', $output['ret']['login_user']));
        $this->assertEquals(LoginLog::RESULT_PASSWORD_WRONG, $output['ret']['login_result']);

        // 驗證是否有新增統計資料
        $loginError = $emShare->find('BBDurianBundle:LoginErrorPerIp', 1);
        $time = new \DateTime(date('Y-m-d H:00:00'));

        $this->assertEquals(2, $loginError->getDomain());
        $this->assertEquals('192.157.111.25', long2ip($loginError->getIp()));
        $this->assertEquals(1, $loginError->getCount());
        $this->assertEquals($time->format('YmdH0000'), $loginError->getAt());

        // 驗證是否有加入封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertNull($list);

        // 廳設定不阻擋登入
        $clientSetConfig = $this->createClient();
        $clientSetConfig->request('PUT', '/api/domain/2/config', ['block_login' => 0]);

        $json = $clientSetConfig->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 驗證是否有加入封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertNull($list);
    }

    /**
     * 測試登入採用使用者密碼資料表驗證
     */
    public function testUserLoginByUserPassword()
    {
        $client = $this->createClient();
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');

        // ztester是有使用者密碼資料表的使用者，將其錯誤登入次數歸零
        $user = $em->find('BBDurianBundle:User', 7);
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);
        $user->zeroErrNum();
        $userPassword->zeroErrNum();

        $em->flush();
        $em->clear();

        // 測試正確密碼登入
        $parameters = [
            'username' => 'ztester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '2'
        ];
        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('0', $output['ret']['login_user']['err_num']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['parent']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 輸入錯誤密碼登入
        $parameters = [
            'username' => 'ztester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => 'thankU9527',
            'entrance' => '2'
        ];
        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);

        $this->assertEquals('1', $userPassword->getErrNum());
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals('1', $output['ret']['login_user']['err_num']);
        $this->assertEquals(LoginLog::RESULT_PASSWORD_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試登入成功會同步清空使用者密碼表中的錯誤登入次數
     */
    public function testLoginSuccessAndClearErrNumInUserPassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);

        // 檢查使用者錯誤登入次數
        $this->assertEquals(2, $user->getErrNum());
        $this->assertEquals(2, $userPassword->getErrNum());
        $this->assertFalse($user->isBlock());

        $em->clear();

        $client = $this->createClient();

        // 第3次登入成功，會回傳1，並清空user與userPassword中的errNum
        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $user = $em->find('BBDurianBundle:User', 8);
        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('0', $output['ret']['login_user']['err_num']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertEquals(0, $user->getErrNum());
        $this->assertEquals(0, $userPassword->getErrNum());
    }

    /**
     * 測試登入採用使用者密碼表驗證，並且3次密碼錯誤
     */
    public function testUserLoginVerifiedByUserPasswordWithErrorPasswordIn3Times()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        //ztester是有使用者密碼表的使用者
        $user = $em->find('BBDurianBundle:User', 7);
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);

        //將登入錯誤次數從1加至2
        $user->addErrNum();
        $userPassword->addErrNum();
        $em->flush();

        $this->assertFalse($user->isBlock());

        $client = $this->createClient();

        // 第3次密碼錯誤, 會回傳9 密碼錯誤並凍結此帳號
        $parameters = [
            'username' => 'ztester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => 'MasterYi',
            'entrance' => '2'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->clear();
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);

        $this->assertEquals('3', $userPassword->getErrNum());
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals('3', $output['ret']['login_user']['err_num']);
        $this->assertEquals(
            LoginLog::RESULT_PASSWORD_WRONG_AND_BLOCK,
            $output['ret']['login_result']
        );

        // ztester
        $user = $em->find('BBDurianBundle:User', 7);

        //測試使用者是否被凍結
        $this->assertTrue($user->isBlock());
        $this->assertEquals(3, $user->getErrNum());

        // 第4次密碼錯誤, 會回傳5 帳號已凍結
        $parameters = [
            'username' => 'ztester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => 'bebeisadog',
            'entrance' => '2'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->clear();
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);

        $this->assertEquals('3', $userPassword->getErrNum());
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals('3', $output['ret']['login_user']['err_num']);
        $this->assertEquals(LoginLog::RESULT_USER_IS_BLOCK, $output['ret']['login_result']);

        // 接下來密碼正確, 會回傳5 帳號已凍結
        $parameters = [
            'username' => 'ztester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '2'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->clear();
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);

        $this->assertEquals('3', $userPassword->getErrNum());
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals('3', $output['ret']['login_user']['err_num']);
        $this->assertEquals(LoginLog::RESULT_USER_IS_BLOCK, $output['ret']['login_result']);
    }

    /**
     * 測試登入時使用者已停用
     */
    public function testLoginWithDisabledUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        // 停用使用者
        $user->disable();

        $em->flush();

        $parameters = array(
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3'
        );

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals($user->getErrNum(), $output['ret']['login_user']['err_num']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_USER_IS_DISABLE, $output['ret']['login_result']);
    }

    /**
     * 測試登入時使用者已凍結
     */
    public function testLoginWithBlockedUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        // 凍結使用者
        $user->block();

        $em->flush();

        $parameters = array(
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3',
            'verify_level' => true
        );

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals($user->getErrNum(), $output['ret']['login_user']['err_num']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_USER_IS_BLOCK, $output['ret']['login_result']);
    }

    /**
     * 測試登入的使用者上層不存在
     */
    public function testLoginWithUserHasNoParents()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'username' => 'company',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '2'
        );

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $user = $em->getRepository('BB\DurianBundle\Entity\User')
                ->findOneByUsername('company');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(array(), $output['ret']['login_user']['all_parents']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        $this->assertNull($output['ret']['login_user']['parent']);
    }

    /**
     * 測試登入時，ip在黑名單中，預設檢查黑名單
     */
    public function testLoginWithBlockedIp()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $user = $em->find('BBDurianBundle:User', 8);

        $parameters = [
            'username' => 'tester',
            'ip'       => '115.195.41.247',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['login_user']['id']);
        $this->assertEquals($user->getErrNum(), $output['ret']['login_user']['err_num']);
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST, $output['ret']['login_result']);

        // 檢查登入紀錄
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals(2, $log->getDomain());
        $this->assertEquals('115.195.41.247', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST, $log->getResult());
        $this->assertEquals(8, $log->getUserId());
        $this->assertEquals(1, $log->getRole());
        $this->assertFalse($log->isSub());
        $this->assertEquals('tester', $log->getUsername());
        $this->assertEmpty($log->getSessionId());
        $this->assertNull($log->getProxy1());
        $this->assertNull($log->getProxy2());
        $this->assertNull($log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertNull($log->getCountry());
        $this->assertNull($log->getCity());
        $this->assertEquals(3, $log->getEntrance());
        $this->assertFalse($log->isOtp());
        $this->assertFalse($log->isSlide());
        $this->assertFalse($log->isTest());

        $this->assertEquals(1, $redis->llen('login_log_queue'));
    }

    /**
     * 測試登入時，ip在黑名單中，預設檢查黑名單，但使用者不存在
     */
    public function testLoginWithBlockedIpButUserNotFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'testerrrrrrrrrrrr',
            'ip'       => '115.195.41.247',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        // 檢查登入紀錄
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST, $log->getResult());
        $this->assertEquals(0, $log->getUserId());
        $this->assertEquals('testerrrrrrrrrrrr', $log->getUsername());
        $this->assertEquals(0, $log->getRole());
        $this->assertFalse($log->isSub());
    }

    /**
     * 測試登入,ip在黑名單中，但不檢查ip黑名單
     */
    public function testLoginWithBlockedIpButNotVerify()
    {
        $client = $this->createClient();

        $parameters = [
            'username' => 'tester',
            'ip'       => '115.195.41.247',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3',
            'verify_blacklist' => 0
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試手機登入時，ip在黑名單中，不檢查系統封鎖黑名單
     */
    public function testMobileLoginButNotVerifySystemLock()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'username' => 'tester',
            'ip'       => '115.195.41.247',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3',
            'ingress'  => '2'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入的使用者已存在oauth綁定
     */
    public function testLoginWithUserHasOauthBinding()
    {
        $client = $this->createClient();

        $parameters = array(
            'username' => 'oauthuser',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => 'dd',
            'entrance' => '3'
        );

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(51, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_USER_HAS_OAUTH_BINDING, $output['ret']['login_result']);
    }

    /**
     * 測試oauth登入
     */
    public function testOauthLogin()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'language' => 2,
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124'
        ];

        // 測試帶入不存在的session,不會噴錯
        $headers['HTTP_SESSION_ID'] = 'test123';
        $client->request('PUT', '/api/oauth/login', $parameters, [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢察回傳資料
        $user = $em->getRepository('BBDurianBundle:User')
            ->findOneBy(array('username' => 'oauthuser'));
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['parent']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['all_parents'][0]);
        $this->assertNotNull($output['ret']['login_user']['session_id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 檢查log寫入是否正確
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals($user->getId(), $log->getUserId());
        $this->assertEquals('126.0.0.2', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $log->getResult());
        $this->assertEquals($user->getLastLogin(), $log->getAt());
        $this->assertEmpty($log->getSessionId());
        $this->assertEquals('zh-tw', $log->getLanguage());
        $this->assertEquals('184.146.232.251', $log->getProxy1());
        $this->assertEquals('184.146.232.251', $log->getProxy2());
        $this->assertEquals('172.16.168.124', $log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertEquals('JP', $log->getCountry());
        $this->assertEquals('unKnowCity', $log->getCity());
        $this->assertEquals(3, $log->getEntrance());
        $this->assertFalse($log->isOtp());
        $this->assertFalse($log->isSlide());
        $this->assertFalse($log->isTest());

        // 檢查待同步到歷史資料庫的queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $this->assertEquals(1, $redis->llen('login_log_queue'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));

        // 檢查 Session 資料
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $mapKey = sprintf(
            'session_user_%s_map',
            $user->getId()
        );
        $sessionKey = sprintf(
            'session_%s',
            $redis->lindex($mapKey, 0)
        );

        $cmpSessionKey = sprintf(
            'session_%s',
            $output['ret']['login_user']['session_id']
        );
        $this->assertEquals($sessionKey, $cmpSessionKey);
        $this->assertTrue($redis->exists($sessionKey));

        $sessionData = $redis->hgetall($sessionKey);
        $this->assertEquals($user->getId(), $sessionData['user:id']);
        $this->assertEquals('oauthuser', $sessionData['user:username']);
        $this->assertEquals('7,6,5,4,3,2', $sessionData['user:all_parents']);

        // 確認 x_forwarded_for 完整資訊有記在 post log 裡面
        $logPath = $this->getLogfilePath('post.log');
        $this->assertFileExists($logPath);

        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "x_forwarded_for={$parameters['x_forwarded_for']}";

        $this->assertContains($line, $results[0]);
        $this->assertEmpty($results[1]);

        // 檢查最後成功登入
        $lastLogin = $em->find('BBDurianBundle:LastLogin', $user->getId());
        $this->assertEquals('126.0.0.2', $lastLogin->getIp());
        $this->assertEquals(9, $lastLogin->getLoginLogId());

        // 同一使用者再次登入
        $output = $this->getResponse('PUT', '/api/oauth/login', $parameters);

        // 檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查舊的session已被刪掉
        $this->assertFalse($redis->exists($sessionKey));
    }

    /**
     * 測試oauth允許重複登入
     */
    public function testOauthLoginWithDuplicateLogin()
    {
        $parameters = [
            'oauth_id' => 2,
            'openid'   => '2382158635',
            'ip'       => '127.0.0.1',
            'entrance' => '3',
            'ingress' => 4,
            'brand' => 'ASUS',
            'model' => 'Z017DA',
        ];

        $output = $this->getResponse('PUT', '/api/oauth/login', $parameters);

        // 檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(51, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查待同步到歷史資料庫的queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $this->assertEquals(1, $redis->llen('login_log_queue'));
        $this->assertEquals(1, $redis->llen('login_log_mobile_queue'));

        // 檢查 Session 資料
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $mapKey = 'session_user_51_map';
        $sessionKey = 'session_' . $redis->lindex($mapKey, 0);
        $oldSessionId = $output['ret']['login_user']['session_id'];
        $oldSessionKey = 'session_' . $oldSessionId ;

        $this->assertEquals($sessionKey, $oldSessionKey);
        $this->assertTrue($redis->exists($sessionKey));

        // 同一使用者再次登入,並設定允許重複登入
        $parameters['duplicate_login'] = 1;
        $output = $this->getResponse('PUT', '/api/oauth/login', $parameters);

        // 檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(51, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查sessionId是否有更新及建立新的session
        $sessionId = $redis->lindex($mapKey, 0);
        $newSessionId = $output['ret']['login_user']['session_id'];
        $this->assertNotEquals($oldSessionId, $newSessionId);
        $this->assertEquals($sessionId, $newSessionId);
        $newSessionKey = 'session_' . $newSessionId;
        $this->assertTrue($redis->exists($newSessionKey));

        // 檢查舊的session沒有被刪掉
        $this->assertTrue($redis->exists($oldSessionKey));
    }

    /**
     * 測試oauth登入,但UserRole跟Entrance無法配對
     */
    public function testOauthLoginButUserRoleDoesNotMatchEntrance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '127.0.0.1',
            'entrance' => '2',
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124'
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢察回傳資料
        $user = $em->getRepository('BBDurianBundle:User')
            ->findOneBy(['username' => 'oauthuser']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_USERNAME_WRONG, $output['ret']['login_result']);

        // 檢查log寫入是否正確
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals($user->getId(), $log->getUserId());
        $this->assertEquals('127.0.0.1', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_USERNAME_WRONG, $log->getResult());
        $this->assertEmpty($log->getSessionId());
        $this->assertEquals('184.146.232.251', $log->getProxy1());
        $this->assertEquals('184.146.232.251', $log->getProxy2());
        $this->assertEquals('172.16.168.124', $log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertNull($log->getCountry());
        $this->assertNull($log->getCity());
        $this->assertEquals(2, $log->getEntrance());
    }

    /**
     * 測試在阻擋的時間區間內oauth重複登入
     */
    public function testOauthLoginInLimitInterval()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 51);

        // 設定登入紀錄為5秒前
        $date = new \DateTime();
        $date->modify('-5 sec');
        $user->setLastLogin($date);

        $em->flush();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'language' => 2,
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124',
            'last_login_interval' => 10
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_DUPLICATED_WITHIN_TIME, $output['ret']['login_result']);
    }

    /**
     * 測試不在阻擋的區間內重複登入
     */
    public function testOauthLoginNotInLimitInterval()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 51);

        // 設定登入紀錄為15秒前
        $date = new \DateTime();
        $date->modify('-15 sec');
        $user->setLastLogin($date);

        $em->flush();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'language' => 2,
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124',
            'last_login_interval' => 10
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試阻擋區間內重複登入但使用者沒有登入過
     */
    public function testOauthLoginNotInLimitIntervalAndUserNeverLogin()
    {
        $client = $this->createClient();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'last_login_interval' => 10
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入帶入的 parent_id 非此使用者上層
     */
    public function testOauthLoginNotInHierarchy()
    {
        $client = $this->createClient();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'language' => 2,
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124',
            'verify_parent_id' => [999]
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_NOT_IN_HIERARCHY, $output['ret']['login_result']);
    }

    /**
     * 測試登入帶入的 parent_id 為此使用者上層
     */
    public function testOauthLoginInHierarchy()
    {
        $client = $this->createClient();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'language' => 2,
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124',
            'verify_parent_id' => [2, 999]
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 帶入非陣列
        $parameters['verify_parent_id'] = 2;

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試oauth登入驗證層級，使用者層級沒有綁定網址，但帶入的登入網址啟用且有綁定層級
     */
    public function testOauthLoginUserLevelDonotHasUrlButHostIsEnableLevelUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增使用者層級
        $user = $em->find('BBDurianBundle:User', 51);
        $userLevel = new UserLevel($user, 1);
        $em->persist($userLevel);
        $em->flush();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'host' => 'acc.com',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_LEVEL_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試oauth登入驗證層級，使用者層級沒有綁定網址，但帶入的登入網址非啟用且有綁定層級
     */
    public function testOauthLoginUserLevelDonotHasUrlButHostIsDisableLevelUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增使用者層級
        $user = $em->find('BBDurianBundle:User', 51);
        $userLevel = new UserLevel($user, 1);
        $em->persist($userLevel);
        $em->flush();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'host' => 'acc.net',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_LEVEL_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試oauth登入驗證層級，使用者層級沒有綁定網址，且登入網址沒有綁定層級
     */
    public function testOauthLoginVerifyLevelButUserLevelDonotBindUrlAndHostDonotHaveLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增使用者層級
        $user = $em->find('BBDurianBundle:User', 51);
        $userLevel = new UserLevel($user, 1);
        $em->persist($userLevel);
        $em->flush();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'host' => '789.789',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試oauth登入驗證層級，使用者層級有綁定網址，與登入網址相符
     */
    public function testOauthLoginVerifyLevelAndUserLevelUrlMatchHostLevelUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增使用者層級
        $user = $em->find('BBDurianBundle:User', 51);
        $userLevel = new UserLevel($user, 2);
        $em->persist($userLevel);
        $em->flush();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'host' => 'cde.cde',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證沒有回傳導向網址
        $this->assertArrayNotHasKey('redirect_url', $output['ret']);
    }

    /**
     * 測試oauth登入驗證層級，使用者層級有綁定網址，但與登入網址不符
     */
    public function testOauthLoginVerifyLevelAndUserLevelUrlDiffWithHostLevelUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增使用者層級
        $user = $em->find('BBDurianBundle:User', 51);
        $userLevel = new UserLevel($user, 2);
        $em->persist($userLevel);
        $em->flush();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'host' => 'acc.com',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證有回傳導向網址
        $this->assertEquals('cde.cde', $output['ret']['redirect_url']);
    }

    /**
     * 測試oauth登入驗證層級，使用者層級有綁定網址，但登入網址沒有綁定層級
     */
    public function testOauthLoginVerifyLevelAndUserLevelUrlButHostDonotHaveLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增使用者層級
        $user = $em->find('BBDurianBundle:User', 51);
        $userLevel = new UserLevel($user, 2);
        $em->persist($userLevel);
        $em->flush();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '126.0.0.2',
            'entrance' => '3',
            'host' => 'acc.com',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('51', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證有回傳導向網址
        $this->assertEquals('cde.cde', $output['ret']['redirect_url']);
    }

    /**
     * 測試oauth登入驗證層級，使用者沒有層級
     */
    public function testOauthLoginVerifyLevelButUserDonotHaveLevel()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $config = new DomainConfig(50, 'domain50', 'xx');
        $emShare->persist($config);
        $emShare->flush();

        $client = $this->createClient();

        $parameters = [
            'username' => 'vtester2',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '2',
            'host' => 'acc.com',
            'verify_level' => true
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('50', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試oauth登入,但使用者未啟用
     */
    public function testOauthLoginButUserIsDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '127.0.0.1',
            'entrance' => '3',
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124'
        ];

        $user = $em->find('BBDurianBundle:User', 51);
        $user->disable();
        $em->flush();

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢察回傳資料
        $user = $em->getRepository('BBDurianBundle:User')
            ->findOneBy(['username' => 'oauthuser']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_USER_IS_DISABLE, $output['ret']['login_result']);

        // 檢查log寫入是否正確
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals($user->getId(), $log->getUserId());
        $this->assertEquals('127.0.0.1', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_USER_IS_DISABLE, $log->getResult());
        $this->assertEmpty($log->getSessionId());
        $this->assertEquals('184.146.232.251', $log->getProxy1());
        $this->assertEquals('184.146.232.251', $log->getProxy2());
        $this->assertEquals('172.16.168.124', $log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertNull($log->getCountry());
        $this->assertNull($log->getCity());
        $this->assertEquals(3, $log->getEntrance());
    }

    /**
     * 測試oauth登入,但使用者被凍結
     */
    public function testOauthLoginButUserIsBlocked()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'oauth_id' => 2,
            'openid' => '2382158635',
            'ip' => '127.0.0.1',
            'entrance' => '3',
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124',
            'verify_level' => true
        ];

        $user = $em->find('BBDurianBundle:User', 51);
        $user->block();
        $em->flush();

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢察回傳資料
        $user = $em->getRepository('BBDurianBundle:User')
            ->findOneBy(['username' => 'oauthuser']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_USER_IS_BLOCK, $output['ret']['login_result']);

        // 檢查log寫入是否正確
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals($user->getId(), $log->getUserId());
        $this->assertEquals('127.0.0.1', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_USER_IS_BLOCK, $log->getResult());
        $this->assertEmpty($log->getSessionId());
        $this->assertEquals('184.146.232.251', $log->getProxy1());
        $this->assertEquals('184.146.232.251', $log->getProxy2());
        $this->assertEquals('172.16.168.124', $log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertNull($log->getCountry());
        $this->assertNull($log->getCity());
        $this->assertEquals(3, $log->getEntrance());
    }

    /**
     * 測試oauth登入, 但輸入參數不合法
     */
    public function testOauthLoginWithInvalidParameters()
    {
        $client = $this->createClient();

        // openid沒跟使用者綁定
        $parameters = array(
            'oauth_id' => 2,
            'openid'   => '78787878787878',
            'ip'       => '127.0.0.1',
            'entrance' => '3'
        );
        $client->request('PUT', '/api/oauth/login', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150250012, $output['code']);
        $this->assertEquals('User has no oauth binding', $output['msg']);

        // oauth設定的廳跟要登入的使用者的廳不同
        $parameters = array(
            'oauth_id' => 1,
            'openid'   => '2382158635',
            'ip'       => '127.0.0.1',
            'entrance' => '3'
        );
        $client->request('PUT', '/api/oauth/login', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150250012, $output['code']);
        $this->assertEquals('User has no oauth binding', $output['msg']);

        // oauth設定不存在
        $parameters = array(
            'oauth_id' => 99999,
            'openid'   => '2382158635',
            'ip'       => '127.0.0.1',
            'entrance' => '3'
        );
        $client->request('PUT', '/api/oauth/login', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150250011, $output['code']);
        $this->assertEquals('Invalid oauth id', $output['msg']);
    }

    /**
     * 測試登入時帶入登入代碼及廳Id
     */
    public function testLoginWithCodeAndDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'username' => 'tester@cm',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->clear();

        // 檢查回傳資料
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['parent']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入時帶入登入代碼但未帶入廳id
     */
    public function testLoginWithCodeButWithoutDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'username' => 'tester@cm',
            'ip' => 'this.is.ip.address',
            'password' => '123456',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->clear();

        // 檢查回傳資料
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['parent']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試登入時帶入沒對應的代碼與廳id
     */
    public function testLoginWithDifferentCodeAndDomain()
    {
        $client = $this->createClient();

        $parameters = [
            'username' => 'tester@cm',
            'ip' => 'this.is.ip.address',
            'domain' => '168',
            'password' => '123456',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查回傳資料
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150250002, $output['code']);
        $this->assertEquals('Domain and LoginCode are not matching', $output['msg']);
    }

    /**
     * 測試登出
     */
    public function testLogout()
    {
        $client = $this->createClient();

        // 建立 Session
        $client->request('POST', '/api/user/8/session');

        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        // 取得session_id
        $sessionId = $out['ret']['session']['id'];

        $parameters = ['session_id' => $sessionId];

        $client->request('PUT', '/api/logout', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查是否正常登出
        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試登入,但發生flush錯誤
     */
    public function testLoginWithFlushError()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);

        $mockEmShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'flush', 'clear', 'getConnection', 'beginTransaction',
                'rollback'])
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods([
                'findOneBy',
                'findBy',
                'getUserPayway'
            ])
            ->getMock();

        $mockRepoShare = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods([
                'getCurrentVersion',
                'getBlockByIpAddress',
                'getBlacklistSingleBy'
            ])
            ->getMock();

        $mockConn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepoShare));

        $mockEmShare->expects($this->at(0))
            ->method('find')
            ->will($this->returnValue($config));

        $mockRepoShare->expects($this->any())
            ->method('getCurrentVersion')
            ->will($this->returnValue(31));

        $mockRepoShare->expects($this->any())
            ->method('getBlockByIpAddress')
            ->will($this->returnValue(null));

        $mockRepoShare->expects($this->any())
            ->method('getBlacklistSingleBy')
            ->will($this->returnValue(null));

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEmShare->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \RuntimeException('Database is busy', 150010071)));

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'password' => '123456',
            'entrance' => '3'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試oauth登入,但發生flush錯誤
     */
    public function testOauthLoginWithFlushError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $oauth = $em->find('BBDurianBundle:Oauth', 2);
        $user = $em->find('BBDurianBundle:User', 51);

        $userPassword = $em->find('BBDurianBundle:UserPassword', 51);

        $AncestorRepo = $em->getRepository('BBDurianBundle:UserAncestor');
        $ancestors = $AncestorRepo->findBy(['user' => $user], ['depth' => 'ASC']);

        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->getBindingBy(2, 1, 2382158635);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods([
                'getBindingBy',
                'findBy',
                'getCurrentVersion',
                'getBlockByIpAddress',
                'getUserPayway'
            ])
            ->getMock();

        $mockConn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockRepo->expects($this->any())
            ->method('getBindingBy')
            ->will($this->returnValue($binding));

        $mockEm->expects($this->at(0))
            ->method('find')
            ->will($this->returnValue($oauth));

        $mockEm->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue($user));

        $mockEm->expects($this->at(3))
            ->method('find')
            ->will($this->returnValue($user));

        $mockEm->expects($this->at(5))
            ->method('find')
            ->will($this->returnValue($userPassword));

        $mockRepo->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($ancestors));

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $mockEm->expects($this->at(12))
            ->method('flush')
            ->will($this->throwException(new \RuntimeException('Database is busy', 150010071)));

        $parameters = [
            'oauth_id' => 2,
            'openid'   => '2382158635',
            'ip'       => '127.0.0.1',
            'entrance' => '3'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試oauth登入,但last_login重複發生錯誤
     */
    public function testOauthLoginWithDuplicateEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $oauth = $em->find('BBDurianBundle:Oauth', 2);
        $user = $em->find('BBDurianBundle:User', 51);

        $userPassword = $em->find('BBDurianBundle:UserPassword', 51);

        $AncestorRepo = $em->getRepository('BBDurianBundle:UserAncestor');
        $ancestors = $AncestorRepo->findBy(['user' => $user], ['depth' => 'ASC']);

        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->getBindingBy(2, 1, 2382158635);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods([
                'getBindingBy',
                'findBy',
                'getCurrentVersion',
                'getBlockByIpAddress',
                'getUserPayway'
            ])
            ->getMock();

        $mockConn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockRepo->expects($this->any())
            ->method('getBindingBy')
            ->will($this->returnValue($binding));

        $mockEm->expects($this->at(0))
            ->method('find')
            ->will($this->returnValue($oauth));

        $mockEm->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue($user));

        $mockEm->expects($this->at(3))
            ->method('find')
            ->will($this->returnValue($user));

        $mockEm->expects($this->at(5))
            ->method('find')
            ->will($this->returnValue($userPassword));

        $mockRepo->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($ancestors));

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        // 測試因last_login造成的錯誤狀況
        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception(
            'Duplicate entry last_login for key 1',
            150250030,
            $pdoExcep
        );

        $mockEm->expects($this->at(12))
            ->method('flush')
            ->will($this->throwException($exception));

        $parameters = [
            'oauth_id' => 2,
            'openid'   => '2382158635',
            'ip'       => '127.0.0.1',
            'entrance' => '3'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->request('PUT', '/api/oauth/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150250030, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);
    }

    /**
     * 測試取得登入記錄
     */
    public function testGetLogList()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameter = [
            'start' => '2012-01-01T09:00:00+0800',
            'end' => '2012-01-03T09:00:00+0800',
            'user_id' => 3,
            'username' => 'vtester',
            'domain' => 2,
            'ip' => '127.0.0.1',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret']['0']['id']);
        $this->assertEquals(3, $output['ret']['0']['user_id']);
        $this->assertEquals('vtester', $output['ret']['0']['username']);
        $this->assertEquals(7, $output['ret']['0']['role']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('ipv6', $output['ret'][0]['ipv6']);
        $this->assertEquals('Host', $output['ret'][0]['host']);
        $this->assertEquals('2012-01-01T10:24:55+0800', $output['ret']['0']['at']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret'][0]['result']);
        $this->assertEquals('4893a9e00935a9ea1bc85b5912e5fcc45d265ce0', $output['ret'][0]['session_id']);
        $this->assertEquals('language', $output['ret'][0]['language']);
        $this->assertEquals('123.123.123.123', $output['ret'][0]['proxy1']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['proxy2']);
        $this->assertNull($output['ret'][0]['proxy3']);
        $this->assertNull($output['ret'][0]['proxy4']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 將原本會撈到的其中一筆 user id 設為 0
        $log = $em->find('BBDurianBundle:LoginLog', 7);
        $log->setUserId(0);
        $em->flush();

        $parameter = [
            'start' => '2010-01-01T09:00:00+0800',
            'end' => '2017-01-03T09:00:00+0800',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認原條件所撈到的資料筆數
        $this->assertEquals(6, $output['pagination']['total']);

        // 確認符合條件但 user id 為 null 撈不到
        $log = $em->find('BBDurianBundle:LoginLog', 8);
        $this->assertNull($log->getUserId());
        $this->assertEquals('2012-01-02T10:24:55+0800', $log->getAt()->format(\DateTime::ISO8601));

        // 比對不過濾不存在使用者時，會多兩筆資料
        $parameter['filter_user'] = 0;
        $client->request('GET', '/api/login_log/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(8, $output['pagination']['total']);
    }

    /**
     * 測試取得登入記錄，帶入filter參數
     */
    public function testGetLogListWithFilter()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 新增一筆大球loginLog
        $log = new LoginLog('127.0.0.1', 20000007, LoginLog::RESULT_PASSWORD_WRONG);
        $log->setAt(new \DateTime('2012-01-02 10:23:43'));
        $log->setUserId(3);
        $log->setSessionId('5893a9e00935a9ea1bc85b5912e5fcc45d265ce0');
        $log->setRole(1);
        $log->setHost('Host');
        $log->setUsername('BigBall');
        $log->setLanguage('language');
        $log->setIpv6('ipv6');
        $log->setProxy1('222.222.222.222');
        $log->setProxy2('127.0.0.1');
        $log->setCountry('QQ');
        $log->setCity('Taiwan');
        $log->setClientOs('AndroidOS');
        $log->setClientBrowser('Safari');

        $em->persist($log);
        $em->flush();

        // 回傳整合站資料
        $parameter = [
            'start' => '2012-01-01T09:00:00+0800',
            'end' => '2012-01-03T09:00:00+0800',
            'user_id' => 3,
            'username' => 'vtester',
            'ip' => '127.0.0.1',
            'filter' => 1,
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret']['0']['id']);
        $this->assertEquals(3, $output['ret']['0']['user_id']);
        $this->assertEquals('vtester', $output['ret']['0']['username']);
        $this->assertEquals(7, $output['ret']['0']['role']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('ipv6', $output['ret'][0]['ipv6']);
        $this->assertEquals('Host', $output['ret'][0]['host']);
        $this->assertEquals('2012-01-01T10:24:55+0800', $output['ret']['0']['at']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret'][0]['result']);
        $this->assertEquals('4893a9e00935a9ea1bc85b5912e5fcc45d265ce0', $output['ret'][0]['session_id']);
        $this->assertEquals('language', $output['ret'][0]['language']);
        $this->assertEquals('123.123.123.123', $output['ret'][0]['proxy1']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['proxy2']);
        $this->assertNull($output['ret'][0]['proxy3']);
        $this->assertNull($output['ret'][0]['proxy4']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 回傳大球站資料
        $parameter = [
            'start' => '2012-01-01T09:00:00+0800',
            'end' => '2012-01-03T09:00:00+0800',
            'user_id' => 3,
            'username' => 'BigBall',
            'ip' => '127.0.0.1',
            'filter' => 2,
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['user_id']);
        $this->assertEquals('BigBall', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['role']);
        $this->assertEquals(20000007, $output['ret'][0]['domain']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('ipv6', $output['ret'][0]['ipv6']);
        $this->assertEquals('Host', $output['ret'][0]['host']);
        $this->assertEquals('2012-01-02T10:23:43+0800', $output['ret']['0']['at']);
        $this->assertEquals(3, $output['ret'][0]['result']);
        $this->assertEquals('5893a9e00935a9ea1bc85b5912e5fcc45d265ce0', $output['ret'][0]['session_id']);
        $this->assertEquals('language', $output['ret'][0]['language']);
        $this->assertEquals('222.222.222.222', $output['ret'][0]['proxy1']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['proxy2']);
        $this->assertNull($output['ret'][0]['proxy3']);
        $this->assertNull($output['ret'][0]['proxy4']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試用ip和上層使用者取得登入記錄但是Parent不存在
     */
    public function testGetLogListByIpParentButNoParentFound()
    {
        $client = $this->createClient();

        $parameter = [
            'start' => '2012-01-01T09:00:00+0800',
            'end' => '2012-01-03T09:00:00+0800',
            'ip' => '127.0.0.1',
            'parent_id' => '3345678',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/list_by_ip_parent', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150250028', $output['code']);
        $this->assertEquals('No parent found', $output['msg']);
    }

    /**
     * 測試用ip和上層使用者取得登入記錄
     */
    public function testGetLogListByIpParent()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameter = [
            'start' => '2012-01-01T09:00:00+0800',
            'end' => '2012-01-02T09:00:00+0800',
            'ip' => '127.0.0.1',
            'parent_id' => '3',
            'sort' => 'at',
            'order' => 'desc',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/list_by_ip_parent', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['role']);
        $this->assertEquals('ztester', $output['ret'][0]['username']);
        $this->assertEquals(3, $output['ret'][0]['result']);
        $this->assertEquals('2012-01-01T10:24:55+0800', $output['ret'][0]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('香港', $output['ret'][0]['country']);
        $this->assertEquals('Tsuen Wan', $output['ret'][0]['city']);
        $this->assertEquals('Host', $output['ret'][0]['host']);

        $this->assertEquals(7, $output['ret'][1]['role']);
        $this->assertEquals('vtester', $output['ret'][1]['username']);
        $this->assertEquals(1, $output['ret'][1]['result']);
        $this->assertEquals('2012-01-01T10:24:55+0800', $output['ret'][1]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][1]['ip']);
        $this->assertEquals('MY', $output['ret'][1]['country']);
        $this->assertEquals('Changkat', $output['ret'][1]['city']);
        $this->assertEquals('Host', $output['ret'][1]['host']);

        $this->assertEquals(1, $output['ret'][2]['role']);
        $this->assertEquals('tester', $output['ret'][2]['username']);
        $this->assertEquals('2012-01-01T10:23:43+0800', $output['ret'][2]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][2]['ip']);

        $this->assertEquals(1, $output['ret'][3]['role']);
        $this->assertEquals('tester', $output['ret'][3]['username']);
        $this->assertEquals('2012-01-01T09:33:14+0800', $output['ret'][3]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][3]['ip']);

        $this->assertEquals(1, $output['ret'][4]['role']);
        $this->assertEquals('tester', $output['ret'][4]['username']);
        $this->assertEquals('2012-01-01T09:31:52+0800', $output['ret'][4]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][4]['ip']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(5, $output['pagination']['total']);

        // 將原本會撈到的其中一筆 user id 設為 0
        $log = $em->find('BBDurianBundle:LoginLog', 7);
        $log->setUserId(0);
        $em->flush();

        $parameter = [
            'start' => '2010-01-01T09:00:00+0800',
            'end' => '2013-01-02T09:00:00+0800',
            'ip' => '127.0.0.1',
            'parent_id' => '2',
            'sort' => 'at',
            'order' => 'desc',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/list_by_ip_parent', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認原條件所撈到的資料筆數
        $this->assertEquals(5, $output['pagination']['total']);

        // 確認符合條件但 user id 為 null 撈不到
        $log = $em->find('BBDurianBundle:LoginLog', 8);
        $this->assertNull($log->getUserId());
        $this->assertEquals('2012-01-02T10:24:55+0800', $log->getAt()->format(\DateTime::ISO8601));
        $this->assertEquals('127.0.0.1', $log->getIp());
        $this->assertEquals(2, $log->getDomain());

        // 比對不過濾不存在使用者時，會多兩筆資料
        $parameter['filter_user'] = 0;
        $client->request('GET', '/api/login_log/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(7, $output['pagination']['total']);
    }

    /**
     * 測試依階層取得與使用者相同IP登入的最後登入紀錄
     */
    public function testGetSameIpWithRole()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $client = $this->createClient();

        $parameter = [
            'user_id' => 3,
            'role' => [1],
            'start' => '2012-01-01 00:00:00',
            'end' => '2012-01-02 00:00:00',
            'domain' => 2,
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/same_ip', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['user_id']);
        $this->assertEquals(7, $output['ret'][0]['role']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['result']);
        $this->assertEquals('2012-01-01T10:24:55+0800', $output['ret'][0]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('Host', $output['ret'][0]['host']);
        $this->assertNull($output['ret'][0]['ingress']);
        $this->assertEquals('AndroidOS', $output['ret'][0]['client_os']);
        $this->assertEquals('Safari', $output['ret'][0]['client_browser']);
        $this->assertFalse($output['ret'][0]['is_otp']);
        $this->assertFalse($output['ret'][0]['is_slide']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(1, $output['ret'][1]['role']);
        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals(3, $output['ret'][1]['result']);
        $this->assertEquals('2012-01-01T10:23:43+0800', $output['ret'][1]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][1]['ip']);
        $this->assertEquals('Host', $output['ret'][1]['host']);
        $this->assertEquals(1, $output['ret'][1]['ingress']);
        $this->assertEquals('Windows', $output['ret'][1]['client_os']);
        $this->assertEquals('Chrome', $output['ret'][1]['client_browser']);
        $this->assertFalse($output['ret'][1]['is_slide']);
        $this->assertFalse($output['ret'][1]['is_otp']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);

        // 測試參數 role 非陣列的情況
        $parameter = [
            'user_id' => 3,
            'role' => 1,
            'start' => '2012-01-01 00:00:00',
            'end' => '2012-01-02 00:00:00',
            'domain' => 2,
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/same_ip', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['pagination']['total']);

        // 將原本會撈到的其中一筆 user id 設為 0
        $log = $em->find('BBDurianBundle:LoginLog', 6);
        $log->setUserId(0);
        $em->flush();

        $parameter = [
            'user_id' => 3,
            'start' => '2010-01-01 00:00:00',
            'end' => '2016-01-02 00:00:00',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/same_ip', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認原條件所撈到的資料筆數
        $this->assertEquals(2, $output['pagination']['total']);

        // 確認符合條件但 user id 為 null 撈不到
        $log = $em->find('BBDurianBundle:LoginLog', 8);
        $this->assertNull($log->getUserId());
        $this->assertEquals('2012-01-02T10:24:55+0800', $log->getAt()->format(\DateTime::ISO8601));
        $this->assertEquals($output['ret'][0]['ip'], $log->getIp());

        // 比對不過濾不存在使用者時，會多兩筆資料
        $parameter['filter_user'] = 0;
        $client->request('GET', '/api/login_log/same_ip', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(4, $output['pagination']['total']);
    }

    /**
     * 測試取得與使用者相同IP登入的最後登入紀錄，帶入filter參數
     */
    public function testGetSameIpWithFilter()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $client = $this->createClient();

        // 新增一筆大球loginLog
        $log = new LoginLog('127.0.0.1', 20000007, LoginLog::RESULT_PASSWORD_WRONG);
        $log->setAt(new \DateTime('2012-01-01 10:23:43'));
        $log->setUserId(20000020);
        $log->setUsername('BigBall');
        $log->setRole(1);
        $log->setHost('Host');
        $log->setIngress(1);
        $log->setClientOs('Windows');
        $log->setClientBrowser('Chrome');

        $em->persist($log);
        $em->flush();

        // 回傳整合站資料
        $parameter = [
            'user_id' => 3,
            'role' => [1],
            'filter' => 1,
            'start' => '2012-01-01 00:00:00',
            'end' => '2012-01-02 00:00:00',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/same_ip', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['user_id']);
        $this->assertEquals(7, $output['ret'][0]['role']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['result']);
        $this->assertEquals('2012-01-01T10:24:55+0800', $output['ret'][0]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('Host', $output['ret'][0]['host']);
        $this->assertNull($output['ret'][0]['ingress']);
        $this->assertEquals('AndroidOS', $output['ret'][0]['client_os']);
        $this->assertEquals('Safari', $output['ret'][0]['client_browser']);
        $this->assertFalse($output['ret'][0]['is_otp']);
        $this->assertFalse($output['ret'][0]['is_slide']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(1, $output['ret'][1]['role']);
        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals(3, $output['ret'][1]['result']);
        $this->assertEquals('2012-01-01T10:23:43+0800', $output['ret'][1]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][1]['ip']);
        $this->assertEquals('Host', $output['ret'][1]['host']);
        $this->assertEquals(1, $output['ret'][1]['ingress']);
        $this->assertEquals('Windows', $output['ret'][1]['client_os']);
        $this->assertEquals('Chrome', $output['ret'][1]['client_browser']);
        $this->assertFalse($output['ret'][1]['is_otp']);
        $this->assertFalse($output['ret'][1]['is_slide']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);

        // 回傳大球站資料
        $parameter = [
            'user_id' => 3,
            'role' => [1],
            'filter' => 2,
            'start' => '2012-01-01 00:00:00',
            'end' => '2012-01-02 00:00:00',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/same_ip', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(20000020, $output['ret'][0]['user_id']);
        $this->assertEquals(1, $output['ret'][0]['role']);
        $this->assertEquals(20000007, $output['ret'][0]['domain']);
        $this->assertEquals('BigBall', $output['ret'][0]['username']);
        $this->assertEquals(3, $output['ret'][0]['result']);
        $this->assertEquals('2012-01-01T10:23:43+0800', $output['ret'][0]['at']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('Host', $output['ret'][0]['host']);
        $this->assertEquals(1, $output['ret'][0]['ingress']);
        $this->assertEquals('Windows', $output['ret'][0]['client_os']);
        $this->assertEquals('Chrome', $output['ret'][0]['client_browser']);
        $this->assertFalse($output['ret'][0]['is_otp']);
        $this->assertFalse($output['ret'][0]['is_slide']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得與使用者相同IP登入的最後登入紀錄但沒有符合條件的紀錄
     */
    public function testGetSameIpButNoResult()
    {
        $client = $this->createClient();

        $parameter = [
            'user_id' => 8,
            'start' => '2012-01-01 09:30:10',
            'end' => '2012-01-01 09:30:12',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/login_log/same_ip', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(1, $output['ret'][0]['role']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['result']);
        $this->assertEquals('2012-01-01T09:30:11+0800', $output['ret'][0]['at']);
        $this->assertEquals('192.168.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('', $output['ret'][0]['host']);
        $this->assertNull($output['ret'][0]['ingress']);
        $this->assertEquals('', $output['ret'][0]['client_os']);
        $this->assertEquals('', $output['ret'][0]['client_browser']);
        $this->assertFalse($output['ret'][0]['is_otp']);
        $this->assertFalse($output['ret'][0]['is_slide']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得與使用者相同IP登入的最後登入紀錄但使用者不存在
     */
    public function testGetSameIpButNoSuchUser()
    {
        $client = $this->createClient();

        $parameter = [
            'user_id' => 99999,
            'start' => '2012-01-01 00:00:00',
            'end' => '2012-01-02 00:00:00'
        ];

        $client->request('GET', '/api/login_log/same_ip', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150250024, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試臨時密碼登入成功
     */
    public function testLoginWithOncePassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $output = $this->getResponse('PUT', '/api/user/8/once_password', ['operator' => 'angelabobi']);

        $this->assertEquals('ok', $output['result']);

        $oncePassword = $output['code'];

        //測試原密碼仍可登入
        $parameters = [
            'username' => 'tester',
            'ip' => '42.4.2.168',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3'
        ];

        $output = $this->getResponse('PUT', '/api/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['login_result']);

        //測試用臨時密碼登入
        $parameters = [
            'username' => 'tester',
            'ip' => '42.4.2.168',
            'domain' => '2',
            'password' => $oncePassword,
            'entrance' => '3'
        ];

        $output = $this->getResponse('PUT', '/api/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['login_result']);

        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);
        $this->assertTrue($userPassword->isUsed());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user_password', $logOp->getTableName());
        $this->assertEquals('@user_id:8', $logOp->getMajorKey());
        $this->assertContains('@used:false=>true', $logOp->getMessage());
    }

    /**
     * 測試登入錯誤臨時密碼會凍結帳號
     */
    public function testLoginWithWrongOncePassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);
        $this->assertEquals(2, $userPassword->getErrNum());
        $em->clear();

        $output = $this->getResponse('PUT', '/api/user/8/once_password', ['operator' => 'angelabobi']);

        $this->assertEquals('ok', $output['result']);

        $parameters = [
            'username' => 'tester',
            'ip' => '42.4.2.168',
            'domain' => '2',
            'password' => 'worngpassword',
            'entrance' => '3'
        ];

        $output = $this->getResponse('PUT', '/api/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['login_result']);

        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);
        $this->assertEquals(3, $userPassword->getErrNum());
    }

    /**
     * 測試廳主與子帳號登入驗證OTP失敗
     */
    public function testDomainLoginVerifyOtpWrong()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $container = $this->getContainer();
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->set('disable_otp', 0);

        $radius = $this->getMockBuilder('Dapphp\Radius\Radius')
            ->setMethods(['accessRequest', 'getLastError'])
            ->getMock();
        $radius->expects($this->any())
            ->method('accessRequest')
            ->will($this->returnValue(false));
        $radius->expects($this->any())
            ->method('getLastError')
            ->will($this->returnValue('Access rejected (3)'));

        $_SERVER["SERVER_ADDR"] = '127.0.0.1';

        $parameters = [
            'username' => 'vtester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '2',
            'ignore_verify_otp' => 0,
            'host' => 'acc.com'
        ];

        // 修改廳主要驗證OTP
        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config->setVerifyOtp(true);
        $emShare->flush();

        $request = new Request($_POST, $parameters);
        $controller = new LoginController();
        $controller->setContainer($container);

        $otpWorker = $container->get('durian.otp_worker');
        $otpWorker->setRadius($radius);

        $json = $controller->loginAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('3', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_OTP_WRONG, $output['ret']['login_result']);

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logFile = $logDir . DIRECTORY_SEPARATOR .'domain_radius.log';
        $results = explode(PHP_EOL, file_get_contents($logFile));

        $this->assertStringEndsWith('LOGGER.INFO: User: 3, domain: 2, response: false, error: Access rejected (3) [] []', $results[0]);
        $this->assertTrue(unlink($logFile));

        // 設定廳主子帳號與密碼
        $domain = $em->find('BBDurianBundle:User', 3);
        $user = new User();
        $user->setId(9487)
            ->setUsername('vtester_sub')
            ->setParent($domain)
            ->setAlias('vtester_sub')
            ->setPassword('123456')
            ->setDomain(2)
            ->setRole(7)
            ->setSub(true);
        $em->persist($user);

        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$BjSDFepMqSYf.KHVzteZaec3BlG4GIaaTx/X26Bo13i6SJqKPz54i')
            ->setModifiedAt(new \DateTime('2014-01-01 15:15:15'))
            ->setExpireAt(new \DateTime('2014-06-02 22:45:45'))
            ->setErrNum(0);
        $em->persist($userPassword);
        $em->flush();

        $parameters = [
            'username' => 'vtester_sub',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '2',
            'host' => 'acc.com'
        ];

        $request = new Request($_POST, $parameters);
        $otpWorker->setRadius($radius);

        $json = $controller->LoginAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('9487', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_OTP_WRONG, $output['ret']['login_result']);

        $results = explode(PHP_EOL, file_get_contents($logFile));
        $this->assertStringEndsWith('LOGGER.INFO: User: 9487, domain: 2, response: false, error: Access rejected (3) [] []', $results[0]);
        $this->assertTrue(unlink($logFile));
    }

    /**
     * 測試OTP伺服器異常跳例外
     */
    public function testDomainLoginAndOtpWrong()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $_SERVER["SERVER_ADDR"] = '127.0.0.1';

        $parameters = [
            'username' => 'vtester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '2',
            'ignore_verify_otp' => 0,
            'host' => 'acc.com'
        ];

        // 修改廳主要驗證OTP
        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config->setVerifyOtp(true);
        $emShare->flush();

        $client = $this->createClient();
        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150250032, $output['code']);
        $this->assertEquals('Otp server connection failure', $output['msg']);
    }

    /**
     * 測試OTP登入異常跳例外
     */
    public function testDomainLoginAndOtpException()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config->setVerifyOtp(true);
        $emShare->flush();

        // 測試廳主子帳號
        $otpWorker = $this->getMockBuilder('BB\DurianBundle\Otp\Worker')
            ->setMethods(['getOtpResult'])
            ->getMock();

        $exception = new \Exception('Some error message', 9999);

        $otpWorker->expects($this->any())
            ->method('getOtpResult')
            ->will($this->throwException($exception));

        $_SERVER["SERVER_ADDR"] = '127.0.0.1';

        $parameters = [
            'username' => 'vtester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '2',
            'ignore_verify_otp' => 0,
            'host' => 'acc.com'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('durian.otp_worker', $otpWorker);
        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(9999, $output['code']);
        $this->assertEquals('Some error message',$output['msg']);

        $italkingCount = $redis->llen('italking_exception_queue');
        $this->assertEquals(1, $italkingCount);

        $gmCode = $this->getContainer()->getParameter('italking_gm_code');
        $italkingQueue = $redis->lrange('italking_exception_queue', 0, -1);
        $italkingMsg = json_decode($italkingQueue[0], true);

        $this->assertEquals($gmCode, $italkingMsg['code']);
        $this->assertEquals('acc_system', $italkingMsg['type']);
        $this->assertEquals('Exception', $italkingMsg['exception']);
        $this->assertEquals(
            'Error Code: 150250032 OTP伺服器連線異常，請GM先檢查 OTP機器測試並通知 DCOP，如有異常再請通知 RD5-帳號研發部 工程師檢查。',
            substr($italkingMsg['message'], strpos($italkingMsg['message'], 'Error Code:'))
        );
    }

    /**
     * 測試廳主略過OTP驗證
     */
    public function testDomainLoginIgnoreVerifyOtp()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $_SERVER["SERVER_ADDR"] = '127.0.0.1';

        $parameters = [
            'username' => 'vtester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '2',
            'ignore_verify_otp' => 1,
            'host' => 'acc.com'
        ];

        // 修改廳主要驗證OTP
        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config->setVerifyOtp(true);
        $emShare->flush();

        $client = $this->createClient();
        $client->request('PUT', '/api/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('3', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試以使用者帳號查詢最後成功登入紀錄
     */
    public function testGetLastLoginByUsername()
    {
        $client = $this->createClient();

        $parameters = [
            'username' => 'tester',
            'domain' => '2',
            'first_result' => 0,
            'max_results' => 1
        ];

        $client->request('GET', '/api/user/last_login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['role']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('2012-01-01T09:30:11+0800', $output['ret'][0]['last_login']);
        $this->assertFalse($output['ret'][0]['sub']);
        $this->assertEquals('192.168.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('', $output['ret'][0]['host']);
        $this->assertNull($output['ret'][0]['ingress']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
    }

    /**
     * 清除產生的 log 檔案
     */
    public function tearDown()
    {
        parent::tearDown();

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logFile = $logDir . DIRECTORY_SEPARATOR .'domain_radius.log';

        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }
}
