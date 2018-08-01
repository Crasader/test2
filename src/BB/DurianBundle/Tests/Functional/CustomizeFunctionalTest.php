<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;

class CustomizeFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDataForCustomizeController',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigDataForCustomizeController',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $classnames = [];
        $this->loadFixtures($classnames, 'entry');
        $this->loadFixtures($classnames, 'his');

        $this->clearSensitiveLog();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);

        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=UserFunctionsTest.php&operator_id=75&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
    }

    /**
     * 測試取得大股東列表
     */
    public function testGetSupremeShareholderList()
    {
        $client = $this->createClient();
        $parameters = [
            'first_result' => 0,
            'max_results' => 20
        ];
        $client->request('GET', '/api/customize/supreme_shareholder/list', $parameters);

        $json = $client->getResponse()->getContent();
        $result = json_decode($json, true);

        $this->assertEquals('ok', $result['result']);

        $this->assertEquals(0, $result['pagination']['first_result']);
        $this->assertEquals(20, $result['pagination']['max_results']);
        $this->assertEquals(4, $result['pagination']['total']);

        $this->assertEquals(4, $result['ret'][0]['id']);
        $this->assertEquals('wtester', $result['ret'][0]['username']);
        $this->assertEquals(2, $result['ret'][0]['domain']);
        $this->assertEquals('wtester', $result['ret'][0]['alias']);
        $this->assertFalse($result['ret'][0]['test']);
        $this->assertFalse($result['ret'][0]['hidden_test']);

        $this->assertEquals(100, $result['ret'][1]['id']);
        $this->assertEquals('test100', $result['ret'][1]['username']);
        $this->assertEquals(75, $result['ret'][1]['domain']);
        $this->assertEquals('test100', $result['ret'][1]['alias']);
        $this->assertFalse($result['ret'][1]['test']);
        $this->assertFalse($result['ret'][1]['hidden_test']);

        $this->assertEquals(101, $result['ret'][2]['id']);
        $this->assertEquals('test101', $result['ret'][2]['username']);
        $this->assertEquals(75, $result['ret'][2]['domain']);
        $this->assertEquals('test101', $result['ret'][2]['alias']);
        $this->assertTrue($result['ret'][2]['test']);
        $this->assertFalse($result['ret'][2]['hidden_test']);

        $this->assertEquals(102, $result['ret'][3]['id']);
        $this->assertEquals('test102', $result['ret'][3]['username']);
        $this->assertEquals(84, $result['ret'][3]['domain']);
        $this->assertEquals('test102', $result['ret'][3]['alias']);
        $this->assertFalse($result['ret'][3]['test']);
        $this->assertTrue($result['ret'][3]['hidden_test']);
    }

    /**
     * 測試取得指定廳的大股東列表
     */
    public function testGetSupremeShareholderListWithDoamin()
    {
        $client = $this->createClient();
        $parameters = ['domain' => '84'];
        $client->request('GET', '/api/customize/supreme_shareholder/list', $parameters);

        $json = $client->getResponse()->getContent();
        $result = json_decode($json, true);

        $this->assertEquals('ok', $result['result']);
        $this->assertEquals(1, $result['pagination']['total']);

        $this->assertEquals(102, $result['ret'][0]['id']);
        $this->assertEquals('test102', $result['ret'][0]['username']);
        $this->assertEquals(84, $result['ret'][0]['domain']);
        $this->assertEquals('test102', $result['ret'][0]['alias']);
        $this->assertFalse($result['ret'][0]['test']);
        $this->assertTrue($result['ret'][0]['hidden_test']);
    }

    /**
     * 測試取得測試帳號大股東列表
     */
    public function testGetSupremeShareholderListWithTest()
    {
        $client = $this->createClient();

        // 取得測試帳號
        $parameters = ['test' => '1'];
        $client->request('GET', '/api/customize/supreme_shareholder/list', $parameters);

        $json = $client->getResponse()->getContent();
        $result = json_decode($json, true);

        $this->assertEquals('ok', $result['result']);
        $this->assertEquals(1, $result['pagination']['total']);

        $this->assertEquals(101, $result['ret'][0]['id']);
        $this->assertEquals('test101', $result['ret'][0]['username']);
        $this->assertEquals(75, $result['ret'][0]['domain']);
        $this->assertEquals('test101', $result['ret'][0]['alias']);
        $this->assertTrue($result['ret'][0]['test']);
        $this->assertFalse($result['ret'][0]['hidden_test']);

        // 取得非測試帳號
        $parameters = ['test' => '0'];
        $client->request('GET', '/api/customize/supreme_shareholder/list', $parameters);

        $json = $client->getResponse()->getContent();
        $result = json_decode($json, true);

        $this->assertEquals('ok', $result['result']);
        $this->assertEquals(3, $result['pagination']['total']);

        $this->assertEquals(4, $result['ret'][0]['id']);
        $this->assertEquals('wtester', $result['ret'][0]['username']);
        $this->assertEquals(2, $result['ret'][0]['domain']);
        $this->assertEquals('wtester', $result['ret'][0]['alias']);
        $this->assertFalse($result['ret'][0]['test']);
        $this->assertFalse($result['ret'][0]['hidden_test']);

        $this->assertEquals(100, $result['ret'][1]['id']);
        $this->assertEquals('test100', $result['ret'][1]['username']);
        $this->assertEquals(75, $result['ret'][1]['domain']);
        $this->assertEquals('test100', $result['ret'][1]['alias']);
        $this->assertFalse($result['ret'][1]['test']);
        $this->assertFalse($result['ret'][1]['hidden_test']);

        $this->assertEquals(102, $result['ret'][2]['id']);
        $this->assertEquals('test102', $result['ret'][2]['username']);
        $this->assertEquals(84, $result['ret'][2]['domain']);
        $this->assertEquals('test102', $result['ret'][2]['alias']);
        $this->assertFalse($result['ret'][2]['test']);
        $this->assertTrue($result['ret'][2]['hidden_test']);
    }

    /**
     * 測試取得隱藏測試帳號大股東列表
     */
    public function testGetSupremeShareholderListWithHiddenTest()
    {
        $client = $this->createClient();

        // 取得隱藏測試帳號
        $parameters = ['hidden_test' => '1'];
        $client->request('GET', '/api/customize/supreme_shareholder/list', $parameters);

        $json = $client->getResponse()->getContent();
        $result = json_decode($json, true);

        $this->assertEquals('ok', $result['result']);
        $this->assertEquals(1, $result['pagination']['total']);

        $this->assertEquals(102, $result['ret'][0]['id']);
        $this->assertEquals('test102', $result['ret'][0]['username']);
        $this->assertEquals(84, $result['ret'][0]['domain']);
        $this->assertEquals('test102', $result['ret'][0]['alias']);
        $this->assertFalse($result['ret'][0]['test']);
        $this->assertTrue($result['ret'][0]['hidden_test']);

        // 取得非隱藏測試帳號
        $parameters = ['hidden_test' => '0'];
        $client->request('GET', '/api/customize/supreme_shareholder/list', $parameters);

        $json = $client->getResponse()->getContent();
        $result = json_decode($json, true);

        $this->assertEquals('ok', $result['result']);
        $this->assertEquals(3, $result['pagination']['total']);

        $this->assertEquals(4, $result['ret'][0]['id']);
        $this->assertEquals('wtester', $result['ret'][0]['username']);
        $this->assertEquals(2, $result['ret'][0]['domain']);
        $this->assertEquals('wtester', $result['ret'][0]['alias']);
        $this->assertFalse($result['ret'][0]['test']);
        $this->assertFalse($result['ret'][0]['hidden_test']);

        $this->assertEquals(100, $result['ret'][1]['id']);
        $this->assertEquals('test100', $result['ret'][1]['username']);
        $this->assertEquals(75, $result['ret'][1]['domain']);
        $this->assertEquals('test100', $result['ret'][1]['alias']);
        $this->assertFalse($result['ret'][1]['test']);
        $this->assertFalse($result['ret'][1]['hidden_test']);

        $this->assertEquals(101, $result['ret'][2]['id']);
        $this->assertEquals('test101', $result['ret'][2]['username']);
        $this->assertEquals(75, $result['ret'][2]['domain']);
        $this->assertEquals('test101', $result['ret'][2]['alias']);
        $this->assertTrue($result['ret'][2]['test']);
        $this->assertFalse($result['ret'][2]['hidden_test']);
    }

    /**
     * 測試取得指定廳內會員的詳細資訊
     */
    public function testGetUserDetailByDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $domain = $em->find('BBDurianBundle:User', 3);
        $cashfake = new CashFake($domain, 156);
        $em->persist($cashfake);

        $user = $em->find('BBDurianBundle:User', 8);
        $date = new \Datetime('2015-01-02 00:00:00');

        $user->setCreatedAt($date);
        $em->persist($user);

        $depositWithdraw = new UserHasDepositWithdraw($user, null, $date, false, true);
        $em->persist($depositWithdraw);
        $em->flush();

        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=UserFunctionsTest.php&operator_id=858278&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $client = $this->createClient();

        $startAt = new \DateTime('2015-01-01 00:00:00');
        $endAt = new \DateTime('2015-01-03 00:00:00');

        $parameters = [
            'domain' => 3,
            'start_at' => $startAt->format('Y-m-d'),
            'end_at' => $endAt->format('Y-m-d'),
            'usernames' => 'tester',
            'has_deposit' => 0,
            'has_withdraw' => 1,
            'sort' => 'user',
            'order' => 'ASC',
            'first_result' => 0,
            'max_results' => 20
        ];

        $client->request('GET', '/api/customize/user_detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $result = json_decode($json, true);

        $this->assertEquals('ok', $result['result']);
        $this->assertEquals(0, $result['pagination']['first_result']);
        $this->assertEquals(20, $result['pagination']['max_results']);
        $this->assertEquals(1, $result['pagination']['total']);

        $this->assertEquals(8, $result['ret'][0]['id']);
        $this->assertEquals($date->format(\DateTime::ISO8601), $result['ret'][0]['created_at']);
        $this->assertEquals('tester', $result['ret'][0]['username']);
        $this->assertEquals('達文西', $result['ret'][0]['name_real']);
        $this->assertEquals('TWD', $result['ret'][0]['cash_currency']);
        $this->assertEquals(1000, $result['ret'][0]['cash_balance']);
        $this->assertEquals('CNY', $result['ret'][0]['cash_fake_currency']);
        $this->assertEquals(0, $result['ret'][0]['cash_fake_balance']);
        $this->assertEquals('Republic of China', $result['ret'][0]['country']);
        $this->assertEquals('3345678', $result['ret'][0]['telephone']);
        $this->assertEquals('Davinci@chinatown.com', $result['ret'][0]['email']);
        $this->assertEquals('485163154787', $result['ret'][0]['qq_num']);
        $this->assertEquals('2000-10-10', $result['ret'][0]['birthday']);
        $this->assertEquals(true, $result['ret'][0]['enable']);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $this->assertRegExp('/entrance=3/', $results[0]);
        $this->assertRegExp('/operator=test/', $results[0]);
        $this->assertRegExp('/client_ip=127.0.0.1/', $results[0]);
        $this->assertRegExp('/run_php=UserFunctionsTest.php/', $results[0]);
        $this->assertRegExp('/operator_id=858278/', $results[0]);
        $this->assertRegExp('/vendor=acc/', $results[0]);
    }

    /**
     * 測試複製管理層帳號
     */
    public function testCopyUserOfManagement()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $oldCash = $em->getRepository('BBDurianBundle:Cash')->findOneBy(['user' => 4]);
        $oldCash->setBalance(-10);
        $oldCash->setNegative(true);
        $em->persist($oldCash);
        $em->flush();

        $targetPayway = $em->find('BBDurianBundle:UserPayway', 10);
        $targetPayway->enableCash();
        $targetPayway->enableCredit();
        $em->flush();

        $targetDomain = $em->find('BBDurianBundle:User', 10);
        $this->assertEquals(0, $targetDomain->getSize());

        $parameters = [
            'old_user_id' => 4,
            'new_user_id' => 20000001,
            'new_parent_id' => 10,
            'username' => 'wtesteraa',
            'source_domain' => 2,
            'target_domain' => 10,
            'role' => 5
        ];

        $client->request('POST', '/api/customize/user/copy', $parameters);

        $user = $em->find('BBDurianBundle:User', 20000001);
        $this->assertEquals('wtesteraa', $user->getUsername());
        $this->assertEquals('10', $user->getParent()->getId());
        $this->assertEquals('10', $user->getDomain());
        $this->assertEquals('5', $user->getRole());
        $this->assertEquals('wtester', $user->getAlias());

        $ancestor = $em->getRepository('BBDurianBundle:UserAncestor')->findOneBy(['user' => 20000001]);
        $this->assertEquals(10, $ancestor->getAncestor()->getId());
        $this->assertEquals(1, $ancestor->getDepth());

        $detail = $em->find('BBDurianBundle:UserDetail', 20000001);
        $this->assertNotEmpty($detail);

        $email = $em->find('BBDurianBundle:UserEmail', 20000001);
        $this->assertNotEmpty($email);

        $password = $em->find('BBDurianBundle:UserPassword', 20000001);
        $this->assertEquals('$2y$10$uqsxGUzgFlvkToSqwROyeuhUAi0NVmUfkGUrNttuyemZN617f.lL.', $password->getHash());

        $cash = $em->getRepository('BBDurianBundle:Cash')->findOneBy(['user' => 20000001]);
        $this->assertEquals(0, $cash->getBalance());
        $this->assertFalse($cash->getNegative());
        $this->assertEquals(901, $cash->getCurrency());

        $shareLimit = $em->getRepository('BBDurianBundle:ShareLimit')->findOneBy(['user' => 20000001, 'groupNum' => 1]);
        $this->assertEquals(90, $shareLimit->getUpper());

        $shareLimitNext = $em->getRepository('BBDurianBundle:ShareLimitNext')->findOneBy(['user' => 20000001, 'groupNum' => 1]);
        $this->assertEquals(90, $shareLimitNext->getUpper());
        $this->assertEquals($shareLimit->getId(), $shareLimitNext->getId());

        $payway = $em->find('BBDurianBundle:UserPayway', 20000001);
        $this->assertTrue($payway->isCashEnabled());
        $this->assertFalse($payway->isCashFakeEnabled());
        $this->assertFalse($payway->isCreditEnabled());

        // 驗證redis對應表
        $redis = $this->getContainer()->get('snc_redis.map');

        $this->assertEquals(10, $redis->get('user:{2001}:20000001:domain'));
        $this->assertEquals('wtesteraa', $redis->get('user:{2001}:20000001:username'));

        $this->runCommand('durian:update-user-size');

        // 對應上層廳主 size + 1
        $em->refresh($targetDomain);
        $this->assertEquals(1, $targetDomain->getSize());
    }

    /**
     * 測試複製管理層帳號時 cash 不存在
     */
    public function testCopyUserOfManagementButCashNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $oldCash = $em->getRepository('BBDurianBundle:Cash')->findOneBy(['user' => 4]);
        $em->remove($oldCash);
        $em->flush();

        $parameters = [
            'old_user_id' => 4,
            'new_user_id' => 20000001,
            'new_parent_id' => 10,
            'username' => 'wtesteraa',
            'source_domain' => 2,
            'target_domain' => 10,
            'role' => 5
        ];

        $client->request('POST', '/api/customize/user/copy', $parameters);

        $out = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('ok', $out['result']);

        $cash = $em->getRepository('BBDurianBundle:Cash')->findOneBy(['user' => 20000001]);
        $this->assertNull($cash);
    }

    /**
     * 測試複製會員帳號
     */
    public function testCopyUserOfMember()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $oldCash = $em->getRepository('BBDurianBundle:Cash')->findOneBy(['user' => 8]);
        $oldCash->setBalance(10);
        $em->persist($oldCash);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastBank(1);

        $em->persist($user);
        $em->flush();

        $parameters = [
            'old_user_id' => 4,
            'new_user_id' => 20000001,
            'new_parent_id' => 10,
            'username' => 'wtesteraa',
            'source_domain' => 2,
            'target_domain' => 10,
            'role' => 5
        ];

        $client->request('POST', '/api/customize/user/copy', $parameters);

        $parameters = [
            'old_user_id' => 5,
            'new_user_id' => 20000002,
            'new_parent_id' => 20000001,
            'username' => 'xtesteraa',
            'source_domain' => 2,
            'target_domain' => 10,
            'role' => 4
        ];

        $client->request('POST', '/api/customize/user/copy', $parameters);

        $parameters = [
            'old_user_id' => 6,
            'new_user_id' => 20000003,
            'new_parent_id' => 20000002,
            'username' => 'ytesteraa',
            'source_domain' => 2,
            'target_domain' => 10,
            'role' => 3
        ];

        $client->request('POST', '/api/customize/user/copy', $parameters);

        $parameters = [
            'old_user_id' => 7,
            'new_user_id' => 20000004,
            'new_parent_id' => 20000003,
            'username' => 'ztesteraa',
            'source_domain' => 2,
            'target_domain' => 10,
            'role' => 2
        ];

        // 管理層(代理)原先不是隱藏測試帳號及原先size數量
        $user = $em->find('BBDurianBundle:User', 7);
        $this->assertFalse($user->isHiddenTest());
        $this->assertEquals(2, $user->getSize());

        $client->request('POST', '/api/customize/user/copy', $parameters);

        // 管理層(代理)複寫成隱藏測試帳號及size更改為0
        $user = $em->find('BBDurianBundle:User', 20000004);
        $this->assertTrue($user->isHiddenTest());
        $this->assertEquals(0, $user->getSize());

        $parameters = [
            'old_user_id' => 8,
            'new_user_id' => 20000005,
            'new_parent_id' => 20000004,
            'username' => 'testeraa',
            'source_domain' => 2,
            'target_domain' => 10,
            'role' => 1,
            'preset_level' => 4
        ];

        // 會員原先非隱藏測試帳號
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertFalse($user->isHiddenTest());

        $client->request('POST', '/api/customize/user/copy', $parameters);

        // 將現金明細同步到資料庫中
        $output = $this->runCommand('durian:run-cash-poper');
        $this->assertEquals('', $output);

        // 將現金同步到資料庫中
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 1]);

        $user = $em->find('BBDurianBundle:User', 20000005);
        $this->assertEquals('testeraa', $user->getUsername());
        $this->assertEquals(20000004, $user->getParent()->getId());
        $this->assertEquals(10, $user->getDomain());
        $this->assertEquals(1, $user->getRole());
        $this->assertEquals('tester', $user->getAlias());

        // 會員複寫後跟原本一樣非隱藏測試帳號
        $this->assertFalse($user->isHiddenTest());

        $ancestor = $em->find('BBDurianBundle:UserAncestor', ['user' => 20000005, 'ancestor' => 20000004]);
        $this->assertEquals(20000004, $ancestor->getAncestor()->getId());
        $this->assertEquals(1, $ancestor->getDepth());

        $ancestor = $em->find('BBDurianBundle:UserAncestor', ['user' => 20000005, 'ancestor' => 20000003]);
        $this->assertEquals(20000003, $ancestor->getAncestor()->getId());
        $this->assertEquals(2, $ancestor->getDepth());

        $ancestor = $em->find('BBDurianBundle:UserAncestor', ['user' => 20000005, 'ancestor' => 20000002]);
        $this->assertEquals(20000002, $ancestor->getAncestor()->getId());
        $this->assertEquals(3, $ancestor->getDepth());

        $ancestor = $em->find('BBDurianBundle:UserAncestor', ['user' => 20000005, 'ancestor' => 20000001]);
        $this->assertEquals(20000001, $ancestor->getAncestor()->getId());
        $this->assertEquals(4, $ancestor->getDepth());

        $ancestor = $em->find('BBDurianBundle:UserAncestor', ['user' => 20000005, 'ancestor' => 10]);
        $this->assertEquals(10, $ancestor->getAncestor()->getId());
        $this->assertEquals(5, $ancestor->getDepth());

        $detail = $em->find('BBDurianBundle:UserDetail', 20000005);
        $this->assertEquals('MJ149', $detail->getNickname());
        $this->assertEquals('達文西', $detail->getNamereal());

        $email = $em->find('BBDurianBundle:UserEmail', 20000005);
        $this->assertEquals('Davinci@chinatown.com', $email->getEmail());

        $password = $em->find('BBDurianBundle:UserPassword', 20000005);
        $this->assertEquals('$2y$10$ElOdE7aZmwmgkqROzuiZROpiWz1G.ZUfhCIbJ0Co7GMx1Va1Yqft6', $password->getHash());

        $cash = $em->getRepository('BBDurianBundle:Cash')->findOneBy(['user' => 20000005]);
        $this->assertEquals(10, $cash->getBalance());
        $this->assertEquals(901, $cash->getCurrency());

        $userLevel = $em->find('BBDurianBundle:UserLevel', 20000005);
        $this->assertEquals(4, $userLevel->getLevelId());
        $this->assertEquals(4, $userLevel->getLastLevelId());
        $this->assertFalse($userLevel->isLocked());

        $bank = $em->getRepository('BBDurianBundle:Bank')->findOneBy(['user' => 20000005]);
        $this->assertEquals(6, $bank->getId());
        $this->assertEquals(11, $bank->getCode());
        $this->assertEquals(6221386170003601228, $bank->getAccount());

        $user = $em->find('BBDurianBundle:User', 20000005);
        $this->assertEquals(6, $user->getLastBank());

        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy(['userId' => 20000005]);
        $this->assertEquals(1023, $cashEntry->getOpcode());
        $this->assertEquals('Copy-user 複寫體系', $cashEntry->getMemo());
        $this->assertEquals(10, $cashEntry->getAmount());
        $this->assertEquals(10, $cashEntry->getBalance());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')->findOneBy(['userId' => 20000005]);
        $this->assertEquals(1023, $pdwEntry->getOpcode());
        $this->assertEquals('Copy-user 複寫體系', $pdwEntry->getMemo());
        $this->assertEquals(10, $pdwEntry->getAmount());
        $this->assertEquals(10, $pdwEntry->getBalance());

        // 將現金明細同步到歷史資料庫中
        $output = $this->runCommand('durian:sync-his-poper');
        $this->assertEquals('', $output);

        $cashEntryHis = $emHis->getRepository('BBDurianBundle:CashEntry')->findOneBy(['userId' => 20000005]);
        $this->assertEquals(1023, $cashEntryHis->getOpcode());
        $this->assertEquals('Copy-user 複寫體系', $cashEntryHis->getMemo());
        $this->assertEquals(10, $cashEntryHis->getAmount());
        $this->assertEquals(10, $cashEntryHis->getBalance());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('user', $logOperation->getTableName());
        $this->assertEquals('@id:20000005', $logOperation->getMajorKey());
        $this->assertEquals('POST', $logOperation->getMethod());
        $this->assertEquals(
            '@username:testeraa, @domain:10, @alisas:tester, @sub:false, @enable:true, @block:false, @password:new, '.
            '@test:false, @currency:156, @rent:false, @password_reset:false, @role:1',
            $logOperation->getMessage()
        );

        // 將下層數量同步到資料庫中
        $this->runCommand('durian:update-user-size');

        $user = $em->find('BBDurianBundle:User', 20000004);
        $em->refresh($user);
        $this->assertEquals(1, $user->getSize());
    }

    /**
     * 測試複製體系api rollback
     */
    public function testCopyUserRollback()
    {
        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'beginTransaction', 'find', 'rollback', 'clear'])
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['copyUser'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $parameters = [
            'old_user_id' => 4,
            'new_user_id' => 20000001,
            'new_parent_id' => 10,
            'username' => 'wtesteraa',
            'source_domain' => 2,
            'target_domain' => 10,
            'role' => 5
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->request('POST', '/api/customize/user/copy', $parameters);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 20000001);
        $this->assertNull($user);

        $result = $client->getResponse()->getContent();
        $this->assertContains('Connection timed out', $result);
    }

    /**
     * 測試回傳廳指定時間後未登入總會員數帶入不是廳主
     */
    public function testGetTotalNotLoginWithNotDomain()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 1,
            'date' => '2012-01-01T00:00:00+0800'
        ];
        $client->request('GET', '/api/customize/domain/inactivated_user', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150460020, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試回傳廳指定時間後未登入總會員數
     */
    public function testGetTotalNotLogin()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setLastLogin(new \DateTime('2013-1-1 11:11:11'));
        $em->flush();
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'date' => '2012-01-01T00:00:00+0800'
        ];
        $client->request('GET', '/api/customize/domain/inactivated_user', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']);
    }
}
