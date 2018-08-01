<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\UserEmail;
use BB\DurianBundle\Entity\UserPassword;
use BB\DurianBundle\Entity\Bank;
use BB\DurianBundle\Entity\LoginLog;
use BB\DurianBundle\Entity\OauthUserBinding;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\UserAncestor;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Entity\ShareUpdateCron;
use BB\DurianBundle\Entity\UserCreatedPerIp;
use BB\DurianBundle\Entity\UserPayway;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Entity\DomainCurrency;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserEmail;
use BB\DurianBundle\Entity\RemovedUserDetail;
use BB\DurianBundle\Exception\ShareLimitNotExists;

class UserFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditPeriodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeDataTwo',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDataTwo',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLoginLogData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthUserBindingData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPresetLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainTotalTestData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasDepositWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLastLoginData'
        ];

        $this->loadFixtures($classnames);

        $hisClassnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData'];
        $this->loadFixtures($hisClassnames, 'his');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadIpBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadSlideBindingData'
        ];
        $this->loadFixtures($classnames, 'share');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 7);
        $cash->setBalance(0);
        $em->flush();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
        $redis->set('cashfake_seq', 1000);
        $redis->set('user_seq', 20000000);
        $redis->set('cash_withdraw_seq', 0);

        $this->clearSensitiveLog();

        $sensitiveData = 'entrance=6&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=UserFunctionsTest.php&operator_id=&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
    }

    /**
     * 測試產生使用者id
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];
        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000001', $output['ret']['user_id']);
    }

    /**
     * 測試產生廳主id
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateDomainId()
    {
        $client = $this->createClient();

        $parameter = ['role' => '7', 'client_ip' => '127.0.0.1'];
        $client->request('GET', '/api/user/id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('52', $output['ret']['user_id']);
    }

    /**
     * 測試產生廳主子帳號id
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateDomainSubId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '3',
            'role' => '7',
            'sub' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];
        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000001', $output['ret']['user_id']);
    }

    /**
     * 測試產生使用者id,但ip在封鎖列表中
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateIdButIpInBlacklist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 廳設定阻擋
        $output = $this->getResponse('PUT', '/api/domain/2/config', ['block_create_user' => 1]);

        $this->assertEquals('ok', $output['result']);

        // 測試新增會員,會被阻擋下來
        $parameters = [
            'parent_id' => '7',
            'role'      => '1',
            'domain'    => 2,
            'client_ip' => '126.0.0.1'
        ];
        $output = $this->getResponse('GET', '/api/user/id', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010091, $output['code']);
        $this->assertEquals('Cannot create user when ip is in ip blacklist', $output['msg']);

        // 測試新增非會員(如:代理),不應被阻擋下來
        $parameters = [
            'parent_id' => 6,
            'role'      => 2,
            'domain'    => 2,
            'client_ip' => '126.0.0.1'
        ];
        $output = $this->getResponse('GET', '/api/user/id', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000001', $output['ret']['user_id']);

        // 測試不檢查阻擋機制,不應被阻擋下來
        $parameters = [
            'parent_id' => 7,
            'role'      => 1,
            'domain'    => 2,
            'client_ip' => '126.0.0.1',
            'verify_ip' => 0
        ];
        $output = $this->getResponse('GET', '/api/user/id', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000002', $output['ret']['user_id']);

        // 測試封鎖列表被移除,不應被擋下來
        $output = $this->getResponse('DELETE', '/api/domain/ip_blacklist', ['blacklist_id' => 1]);
        $this->assertEquals('ok', $output['result']);

        $parameters['verify_ip'] = 1;
        $output = $this->getResponse('GET', '/api/user/id', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000003', $output['ret']['user_id']);
    }

    /**
     * 測試產生使用者id
     */
    public function testGenerateUserId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000001', $output['ret']['user_id']);
    }

    /**
     * 測試產生廳主id
     */
    public function testGenerateDomainUserId()
    {
        $client = $this->createClient();

        $parameter = ['role' => '7', 'client_ip' => '127.0.0.1'];
        $client->request('POST', '/api/user/id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('52', $output['ret']['user_id']);
    }

    /**
     * 測試產生廳主子帳號id
     */
    public function testGenerateDomainSubUserId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '3',
            'role' => '7',
            'sub' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000001', $output['ret']['user_id']);
    }

    /**
     * 測試產生使用者id,但ip在封鎖列表中
     */
    public function testGenerateUserIdButIpInBlacklist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 廳設定阻擋
        $output = $this->getResponse('PUT', '/api/domain/2/config', ['block_create_user' => 1]);

        $this->assertEquals('ok', $output['result']);

        // 測試新增會員,會被阻擋下來
        $parameters = [
            'parent_id' => '7',
            'role'      => '1',
            'domain'    => 2,
            'client_ip' => '126.0.0.1'
        ];
        $output = $this->getResponse('GET', '/api/user/id', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010091, $output['code']);
        $this->assertEquals('Cannot create user when ip is in ip blacklist', $output['msg']);

        // 測試新增非會員(如:代理),不應被阻擋下來
        $parameters = [
            'parent_id' => 6,
            'role'      => 2,
            'domain'    => 2,
            'client_ip' => '126.0.0.1'
        ];
        $output = $this->getResponse('POST', '/api/user/id', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000001', $output['ret']['user_id']);

        // 測試不檢查阻擋機制,不應被阻擋下來
        $parameters = [
            'parent_id' => 7,
            'role'      => 1,
            'domain'    => 2,
            'client_ip' => '126.0.0.1',
            'verify_ip' => 0
        ];
        $output = $this->getResponse('POST', '/api/user/id', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000002', $output['ret']['user_id']);

        // 測試封鎖列表被移除,不應被擋下來
        $output = $this->getResponse('DELETE', '/api/domain/ip_blacklist', ['blacklist_id' => 1]);
        $this->assertEquals('ok', $output['result']);

        $parameters['verify_ip'] = 1;
        $output = $this->getResponse('POST', '/api/user/id', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('20000003', $output['ret']['user_id']);
    }

    /**
     * 測試新增非會員不應算入UserCreatedPerIp統計內
     */
    public function testNewUserNotAddUserCreatedPerIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 檢查目前126.0.0.1在UserCreatedPerIp中沒有資料
        $now = new \DateTime('now');
        $criteria = [
            'ip'     => ip2long('126.0.0.1'),
            'at'     => $now->format('YmdH\0000'),
            'domain' => 2
        ];

        $stat = $em->getRepository('BBDurianBundle:UserCreatedPerIp')
            ->findOneBy($criteria);

        $this->assertNull($stat);

        // 新增代理(非會員)
        $parameters = [
            'parent_id' => 6,
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen123456',
            'role'      => 2,
            'currency'  => 'TWD',
            'client_ip' => '126.0.0.1',
            'sharelimit' => [
                1 => [
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                ]
            ],
            'sharelimit_next' => [
                1 => [
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 40,
                    'parent_lower' => 5
                ]
            ]
        ];

        $output = $this->getResponse('POST', '/api/user', $parameters);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);

        // 驗證是否有產生126.0.0.1的統計
        $em->clear();
        $stat = $em->getRepository('BBDurianBundle:UserCreatedPerIp')
            ->findOneBy($criteria);

        $this->assertNull($stat);
    }

    /**
     * 測試產生非廳主的使用者id，帶入不存在parent_id
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateIdWithNonExistParentId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '1000',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];
        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010023, $output['code']);
        $this->assertEquals('No parent found', $output['msg']);
    }

    /**
     * 測試產生使用者id，帶入的parent_id為會員
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateIdWithParentIsMember()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '8',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];
        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010063, $output['code']);
        $this->assertEquals('Parent can not be member', $output['msg']);
    }

    /**
     * 測試產生子帳號id，帶入錯誤的role
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateSubIdWithWrongRole()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'sub' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];
        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010064, $output['code']);
        $this->assertEquals('Invalid role', $output['msg']);
    }

    /**
     * 測試產生非廳主的使用者id，帶入錯誤的role
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateIdWithWrongRole()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '2',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];
        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010064, $output['code']);
        $this->assertEquals('Invalid role', $output['msg']);
    }

    /**
     * 測試產生非廳主的使用者id，帶入不存在parent_id
     */
    public function testGenerateUserIdWithNonExistParentId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '1000',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010169, $output['code']);
        $this->assertEquals('No parent found', $output['msg']);
    }

    /**
     * 測試產生使用者id，帶入的parent_id為會員
     */
    public function testGenerateUserIdWithParentIsMember()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '8',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010170, $output['code']);
        $this->assertEquals('Parent can not be member', $output['msg']);
    }

    /**
     * 測試產生子帳號id，帶入錯誤的role
     */
    public function testGenerateSubUserIdWithWrongRole()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'sub' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010171, $output['code']);
        $this->assertEquals('Invalid role', $output['msg']);
    }

    /**
     * 測試產生非廳主的使用者id，帶入錯誤的role
     */
    public function testGenerateUserIdWithWrongRole()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '2',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010171, $output['code']);
        $this->assertEquals('Invalid role', $output['msg']);
    }

    /**
     * 測試新增會員
     */
    public function testNewUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 啟用上層payway
        $payway = $em->getRepository('BBDurianBundle:UserPayway')->findOneBy(['userId' => 3]);
        $payway->enableOutside();
        $em->flush();

        $parent = $em->find('BBDurianBundle:User', 7);

        //檢查size
        $this->assertEquals(2, $parent->getSize());

        // 檢查層級人數
        $level = $em->find('BBDurianBundle:Level', 5);
        $this->assertEquals(0, $level->getUserCount());

        // 檢查層級幣別相關資料人數
        $criteria = [
            'levelId' => 5,
            'currency' => 156
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(0, $levelCurrency->getUserCount());

        //廳設定阻擋新增使用者
        $client->request('PUT', '/api/domain/2/config', ['block_create_user' => 1]);

        $parameters = [
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen123456789012345678912345',
            'role'      => '1',
            'currency'  => 'TWD',
            'cash_fake' => [
                'currency' => 'CNY',
                'balance'  => 100,
                'operator' => 'bbin'
            ],
            'cash' => ['currency' => 'CNY'],
            'credit' => [
                1 => ['line' => 100],
                2 => ['line' => 1000]
            ],
            'outside' => 1,
            'rent' => true,
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'nickname' => 'MG149',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '3345678',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian',
                'wechat' => 'abcde123'
            ],
            'client_ip' => '127.0.0.1',
            'entrance' => 3
        ];

        $client->request('POST', '/api/user', $parameters, array(), $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $modifiedAt = new \DateTime($output['ret']['modified_at']);
        $expireAt = new \DateTime($output['ret']['password_expire_at']);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $this->runCommand('durian:update-user-size');
        $em->refresh($parent);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertNull($output['ret']['last_bank']);

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);

        // 操作紀錄檢查
        $logOpUser = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user', $logOpUser->getTableName());
        $userLog = [
            '@username:chosen1',
            '@domain:2',
            '@alias:chosen123456789012345678912345',
            '@sub:false',
            '@enable:true',
            '@block:false',
            '@disabled_password:false',
            '@password:new',
            '@test:false',
            '@currency:TWD',
            '@rent:true',
            '@password_reset:false',
            '@role:1',
            '@entrance:3'
        ];
        $this->assertEquals(implode(', ', $userLog), $logOpUser->getMessage());
        $logOpCash = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('cash', $logOpCash->getTableName());
        $this->assertEquals('@currency:CNY', $logOpCash->getMessage());
        $logOpCashFake = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('cash_fake', $logOpCashFake->getTableName());
        $this->assertEquals('@currency:CNY, @balance:100, @operator:bbin', $logOpCashFake->getMessage());
        $logOpCredit = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('credit', $logOpCredit->getTableName());

        //確認新增使用者密碼操作紀錄
        $logOpPassword = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('user_password', $logOpPassword->getTableName());
        $this->assertEquals('@user_id:20000001', $logOpPassword->getMajorKey());
        $this->assertEquals('@hash:new', $logOpPassword->getMessage());

        $logOpUserDetail = $emShare->find('BBDurianBundle:LogOperation', 7);
        $this->assertEquals('user_detail', $logOpUserDetail->getTableName());
        $this->assertEquals('@user_id:20000001', $logOpUserDetail->getMajorKey());
        $detailLog = [
            '@nickname:MG149',
            '@name_real:DaVanCi',
            '@name_chinese:文希哥',
            '@name_english:DaVanCi',
            '@country:ROC',
            '@passport:PA123456',
            '@identity_card:',
            '@driver_license:',
            '@insurance_card:',
            '@health_card:',
            '@password:new',
            '@birthday:2001-01-01',
            '@telephone:3345678',
            '@qq_num:485163154787',
            '@note:Hello durian',
            '@wechat:abcde123'
        ];
        $this->assertEquals(implode(', ', $detailLog), $logOpUserDetail->getMessage());

        //確認新增使用者信箱操作紀錄
        $logOpEmail = $emShare->find('BBDurianBundle:LogOperation', 8);
        $this->assertEquals('user_email', $logOpEmail->getTableName());
        $this->assertEquals('@user_id:20000001', $logOpEmail->getMajorKey());
        $this->assertEquals('@email:Davanci@yahoo.com', $logOpEmail->getMessage());

        // 檢查新增會員層級操作紀錄
        $logOpUL = $emShare->find('BBDurianBundle:LogOperation', 9);
        $this->assertEquals('user_level', $logOpUL->getTableName());
        $this->assertEquals('@user_id:20000001', $logOpUL->getMajorKey());
        $this->assertEquals('@level_id:5', $logOpUL->getMessage());

        // 基本資料檢查
        $this->assertEquals('ztester', $user->getParent()->getUsername());
        $this->assertEquals('chosen1', $user->getUsername());
        $this->assertEquals('chosen1', $user->getPassword());
        $this->assertEquals('chosen123456789012345678912345', $user->getAlias());
        $this->assertEquals(0, $user->getSize());
        $this->assertEquals(3, $user->getParent()->getSize());
        $this->assertEquals(1, $user->getRole());

        $this->assertEquals($user->getParent()->getDomain(), $user->getDomain());
        $this->assertEquals(901, $user->getCurrency());

        $this->assertFalse($user->isSub());
        $this->assertTrue($user->isEnabled());
        $this->assertFalse($user->isBlock());
        $this->assertFalse($user->isPasswordReset());

        // 詳細資料檢查
        $userEmail = $em->find('BBDurianBundle:UserEmail', 20000001);
        $detail = $em->find('BBDurianBundle:UserDetail', 20000001);
        $this->assertEquals('Davanci@yahoo.com', $userEmail->getEmail());
        $this->assertEquals('DaVanCi', $detail->getNameReal());
        $this->assertEquals('文希哥', $detail->getNameChinese());
        $this->assertEquals('DaVanCi', $detail->getNameEnglish());
        $this->assertEquals('ROC', $detail->getCountry());
        $this->assertEquals('PA123456', $detail->getPassport());
        $this->assertEquals('3345678', $detail->getTelephone());
        $this->assertEquals('485163154787', $detail->getQQNum());
        $this->assertEquals('Hello durian', $detail->getNote());
        $this->assertEquals('2001-01-01', $detail->getBirthday()->format('Y-m-d'));
        $this->assertEquals('123456', $detail->getPassword());

        // cashFake資料檢查
        $cashFake = $user->getCashFake();
        $this->assertEquals(156, $cashFake->getCurrency());
        $this->assertEquals(100, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());
        $this->assertTrue($cashFake->isEnable());

        // 檢查cash_fake_entry
        $cfeRepo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $fakeEntry = $cfeRepo->findOneBy(array('id' => 1002));
        $this->assertEquals(1003, $fakeEntry->getOpcode());
        $this->assertEquals(100, $fakeEntry->getAmount());

        $pFakeEntry = $cfeRepo->findOneBy(array('id' => 1001));
        $this->assertEquals(1003, $pFakeEntry->getOpcode());
        $this->assertEquals(-100, $pFakeEntry->getAmount());

        // 檢查cash_fake_transfer_entry
        $cfteRepo = $em->getRepository('BBDurianBundle:CashFakeTransferEntry');

        $transferEntry = $cfteRepo->findOneBy(array('id' => 1002));
        $this->assertEquals(1003, $transferEntry->getOpcode());
        $this->assertEquals(100, $transferEntry->getAmount());

        $pTransferEntry = $cfteRepo->findOneBy(array('id' => 1001));
        $this->assertEquals(1003, $pTransferEntry->getOpcode());
        $this->assertEquals(-100, $pTransferEntry->getAmount());

        // 檢查cash_fake_entry_operator
        $cfeoRepo = $em->getRepository('BBDurianBundle:CashFakeEntryOperator');

        $entryOperator = $cfeoRepo->findOneBy(array('entryId' => 1002));
        $this->assertEquals('ztester', $entryOperator->getWhom());
        $this->assertEquals(2, $entryOperator->getLevel());
        $this->assertEquals(0, $entryOperator->getTransferOut());
        $this->assertEquals('bbin', $entryOperator->getUsername());

        $entryOperator = $cfeoRepo->findOneBy(array('entryId' => 1001));
        $this->assertEquals('chosen1', $entryOperator->getWhom());
        $this->assertEquals(1, $entryOperator->getLevel());
        $this->assertEquals(1, $entryOperator->getTransferOut());
        $this->assertEquals('bbin', $entryOperator->getUsername());

        // cash資料檢查
        $cash = $user->getCash();
        $this->assertEquals(156, $cash->getCurrency());
        $this->assertEquals(0, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        // 檢查回傳該帳號有啟用外接額度
        $this->assertTrue($output['ret']['outside']);

        // credit資料檢查，輸入台幣轉回人民幣
        $credit = $user->getCredit(2);
        $this->assertEquals(223, $credit->getLine());
        $this->assertTrue($credit->isEnable());

        // 檢查上層totalLine及幣別是否有轉換
        $this->assertEquals(3223, $credit->getParent()->getTotalLine());

        // user ancestor檢查
        $uaRepo = $em->getRepository('BBDurianBundle:UserAncestor');

        $userAncestor = $uaRepo->findOneBy(
            array(
                'user' => 20000001,
                'depth' => 1
            )
        );
        $ancestorId = $userAncestor->getAncestor()->getId();

        $this->assertEquals($ancestorId, 7);

        // 新增使用者IP計數檢查
        $createdHour = $user->getCreatedAt()->format('YmdH\0000');
        $ipNumber = ip2long('127.0.0.1');

        $criteria = array(
            'ip'     => $ipNumber,
            'at'     => $createdHour,
            'domain' => $user->getDomain()
        );

        $stat = $emShare->getRepository('BBDurianBundle:UserCreatedPerIp')
                   ->findOneBy($criteria);

        $this->assertEquals($createdHour, $stat->getAt());
        $this->assertEquals($ipNumber, $stat->getIp());
        $this->assertEquals($user->getDomain(), $stat->getDomain());
        $this->assertEquals(1, $stat->getCount());

        //確認使用者密碼是否新增成功
        $userPassword = $em->find('BBDurianBundle:UserPassword', $output['ret']['id']);
        $this->assertTrue(password_verify('chosen1', $userPassword->getHash()));
        $this->assertEquals($userPassword->getModifiedAt(), $modifiedAt);
        $this->assertEquals($userPassword->getExpireAt(), $expireAt);
        $this->assertEquals($userPassword->isReset(), $user->isPasswordReset());
        $this->assertEquals(0, $userPassword->getErrNum());

        // 檢查會員層級
        $ul = $em->find('BBDurianBundle:UserLevel', 20000001);
        $this->assertEquals(5, $ul->getLevelId());

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');
        $em->refresh($level);
        $em->refresh($levelCurrency);

        // 檢查層級人數
        $this->assertEquals(1, $level->getUserCount());

        // 檢查層級幣別相關資料人數
        $this->assertEquals(1, $levelCurrency->getUserCount());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);

        // 非domain不會新增DomainCurrency資料
        $criteria = array('domain' => $user);
        $currencies = $em->getRepository('BBDurianBundle:DomainCurrency')
                         ->findBy($criteria);
        $this->assertEmpty($currencies);

        //新增次數未超出限制,檢查是否有新增至封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertNull($list);

        // 檢查redis對應表
        $redis = $this->getContainer()->get('snc_redis.map');
        $userId = $output['ret']['id'];
        $domainKey = 'user:{2001}:' . $userId . ':domain';
        $usernameKey = 'user:{2001}:' . $userId . ':username';

        $this->assertEquals($user->getDomain(), $redis->get($domainKey));
        $this->assertEquals($user->getUsername(), $redis->get($usernameKey));
    }

    /**
     * 測試新增太陽城會員，參數帶入cash_fake不會新增cash_fake，會建立outside=true
     */
    public function testNewSuncityUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 新增代理與user ancestor
        $parent = $em->find('BBDurianBundle:User', 6);
        $user = new User();
        $user->setId(71);
        $user->setUsername('ztester1');
        $user->setParent($parent);
        $user->addSize();
        $user->setAlias('ztester1');
        $user->setPassword('123456');
        $user->setCurrency(901); // TWD
        $user->setDomain(2);
        $user->setCreatedAt(new \DateTime('2013-1-1 11:11:11'));
        $user->setRole(2);
        $em->persist($user);

        $user2 = $em->find('BBDurianBundle:User', 2);
        $user3 = $em->find('BBDurianBundle:User', 3);
        $user4 = $em->find('BBDurianBundle:User', 4);
        $user5 = $em->find('BBDurianBundle:User', 5);
        $user6 = $em->find('BBDurianBundle:User', 6);

        $userancestor = new UserAncestor($user, $user2, 5);
        $em->persist($userancestor);
        $userancestor = new UserAncestor($user, $user3, 4);
        $em->persist($userancestor);
        $userancestor = new UserAncestor($user, $user4, 3);
        $em->persist($userancestor);
        $userancestor = new UserAncestor($user, $user5, 2);
        $em->persist($userancestor);
        $userancestor = new UserAncestor($user, $user6, 1);
        $em->persist($userancestor);

        // 啟用上層payway
        $payway = $em->getRepository('BBDurianBundle:UserPayway')->findOneBy(['userId' => 3]);
        $payway->enableOutside();
        $em->flush();

        $parameters = [
            'parent_id' => '71',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen123456789012345678912345',
            'role' => '1',
            'currency' => 'TWD',
            'cash_fake' => [
                'currency' => 'CNY',
                'balance' => 100,
                'operator' => 'bbin'
            ],
            'rent' => false,
            'client_ip' => '127.0.0.1',
            'entrance' => 3
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);

        // 驗證沒有建立cashFake
        $cashFake = $user->getCashFake();
        $this->assertNull($cashFake);

        // 檢查回傳該帳號有啟用外接額度
        $this->assertTrue($output['ret']['outside']);
    }

    /**
     * 測試新增會員但redis_map出錯
     */
    public function testNewUseButRedisConnectionTimedOut()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $users = $em->getRepository('BBDurianBundle:User')->findAll();
        $em->clear();

        $redis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['mset', 'del'])
            ->getMock();

        $redis->expects($this->any())
            ->method('mset')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $parameters = [
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen123456789012345678912345',
            'role'      => '1',
            'currency'  => 'TWD',
            'cash_fake' => [
                'currency' => 'CNY',
                'balance'  => 100,
                'operator' => 'bbin'
            ],
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'nickname' => 'MG149',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '3345678',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian'
            ],
            'client_ip' => '127.0.0.1'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('snc_redis.map', $redis);
        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $output = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(SOCKET_ETIMEDOUT, $output['code']);
        $this->assertEquals('Connection timed out', $output['msg']);

        $newUsers = $em->getRepository('BBDurianBundle:User')->findAll();
        $this->assertEquals(count($users), count($newUsers));
    }

    /**
     * 測試新增會員但資料庫timeout
     */
    public function testNewUseButDbConnectionTimedOut()
    {
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'commit',
                'beginTransaction',
                'getRepository',
                'getConnection',
                'rollback',
                'find',
                'persist',
                'flush',
                'clear'
            ])
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'findAll'])
            ->getMock();

        $mockRepo->expects($this->at(1))
            ->method('findAll')
            ->will($this->returnValue(1));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockSL = $this->getMockBuilder('BB\DurianBundle\Share\ActivateSLNext')
            ->disableOriginalConstructor()
            ->setMethods(['isUpdating', 'hasBeenUpdated'])
            ->getMock();

        $mockSL->expects($this->any())
            ->method('hasBeenUpdated')
            ->will($this->returnValue(true));

        $mockEm->expects($this->any(0))
            ->method('commit')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $parameters = [
            'user_id'    => 101,
            'username'   => 'chosen1',
            'password'   => 'chosen1',
            'alias'      => 'chosen123456789012345678912345',
            'role'       => '7',
            'currency'   => 'TWD',
            'login_code' => 'coo',
            'name'       => 'cho',
            'client_ip'  => '127.0.0.1'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);
        $client->getContainer()->set('durian.activate_sl_next', $mockSL);
        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $output = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(SOCKET_ETIMEDOUT, $output['code']);
        $this->assertEquals('Connection timed out', $output['msg']);

        $redis = $this->getContainer()->get('snc_redis.map');
        $this->assertNull($redis->get('user:{1}:101:domain'));
        $this->assertNull($redis->get('user:{1}:101:username'));
    }

    /**
     * 測試新增會員，同分秒新增發生Duplicate entry
     */
    public function testNewUserWithDuplicateEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction', 'getRepository', 'getConnection', 'find',
                'persist', 'rollback', 'clear'
            ])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($em->find('BBDurianBundle:User', 7));

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'findBy', 'updateUserSize'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->willReturn(true);

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        // 同分秒新增黑名單
        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $pdoExcep->errorInfo[2] = 'Duplicate entry for key uni_blacklist_domain_ip';
        $exception = new \Exception(
            'Duplicate entry for key uni_blacklist_domain_ip',
            150010143,
            $pdoExcep
        );

        $mockEm->expects($this->any())
            ->method('persist')
            ->willThrowException($exception);

        $mockSl = $this->getMockBuilder('BB\DurianBundle\Share\ActiveSLNext')
            ->disableOriginalConstructor()
            ->setMethods(['isUpdating', 'hasBeenUpdated'])
            ->getMock();

        $mockSl->expects($this->any())
            ->method('isUpdating')
            ->willReturn(false);

        $mockSl->expects($this->any())
            ->method('hasBeenUpdated')
            ->willReturn(true);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('durian.activate_sl_next', $mockSl);

        $parameters = [
            'parent_id'        => '7',
            'username'         => 'chosen1',
            'password'         => 'chosen1',
            'alias'            => 'chosen123456789012345678912345',
            'role'             => '1',
            'currency'         => 'TWD',
            'client_ip'        => '127.0.0.1',
            'verify_blacklist' => false
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010143, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        // 同分秒新增封鎖列表
        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $pdoExcep->errorInfo[2] = 'Duplicate entry for key uni_ip_blacklist_domain_ip_created_date';
        $exception = new \Exception(
            'Duplicate entry for key uni_ip_blacklist_domain_ip_created_date',
            150010162,
            $pdoExcep
        );

        $mockEm->expects($this->any())
            ->method('persist')
            ->willThrowException($exception);

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('durian.activate_sl_next', $mockSl);

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010162, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        // 檢查沒有寫入message_queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $this->assertEquals(0, $redis->llen('message_queue'));
    }

    /**
     * 測試新增會員，但Username已存在
     */
    public function testNewUserWithUsernameExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'getRepository', 'getConnection', 'find',
                'persist', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($em->find('BBDurianBundle:User', 7));

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'findBy', 'updateUserSize'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->willReturn(true);

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $pdoExcep->errorInfo[2] = 'Duplicate entry for key uni_username_domain';
        $exception = new \Exception(
            'Duplicate entry for key uni_username_domain',
            150010014,
            $pdoExcep
        );

        $mockEm->expects($this->any())
            ->method('persist')
            ->willThrowException($exception);

        $mockSl = $this->getMockBuilder('BB\DurianBundle\Share\ActiveSLNext')
            ->disableOriginalConstructor()
            ->setMethods(['isUpdating', 'hasBeenUpdated'])
            ->getMock();

        $mockSl->expects($this->any())
            ->method('isUpdating')
            ->willReturn(false);

        $mockSl->expects($this->any())
            ->method('hasBeenUpdated')
            ->willReturn(true);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('durian.activate_sl_next', $mockSl);

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen123456789012345678912345',
            'role' => '1',
            'currency' => 'TWD',
            'client_ip' => '127.0.0.1',
            'verify_blacklist' => false
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010014, $output['code']);
        $this->assertEquals('Username already exist', $output['msg']);
    }

    /**
     * 測試新增會員，同分秒同廳同IP新增的狀況
     */
    public function testNewUserWithSameIpDoman()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'getRepository', 'getConnection', 'find',
                'persist', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($em->find('BBDurianBundle:User', 7));

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'findBy', 'updateUserSize'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->willReturn(true);

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $pdoExcep->errorInfo[2] = 'Duplicate entry for key uni_ip_at_domain and user_created_per_ip';
        $exception = new \Exception(
            'Duplicate entry for key uni_ip_at_domain and user_created_per_ip',
            150010071,
            $pdoExcep
        );

        $mockEm->expects($this->any())
            ->method('persist')
            ->willThrowException($exception);

        $mockSl = $this->getMockBuilder('BB\DurianBundle\Share\ActiveSLNext')
            ->disableOriginalConstructor()
            ->setMethods(['isUpdating', 'hasBeenUpdated'])
            ->getMock();

        $mockSl->expects($this->any())
            ->method('isUpdating')
            ->willReturn(false);

        $mockSl->expects($this->any())
            ->method('hasBeenUpdated')
            ->willReturn(true);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('durian.activate_sl_next', $mockSl);

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen123456789012345678912345',
            'role' => '1',
            'currency' => 'TWD',
            'client_ip' => '127.0.0.1',
            'verify_blacklist' => false
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試新增會員但Amount為零
     */
    public function testNewUserWithAmountZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'getRepository', 'getConnection', 'find',
                'persist', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($em->find('BBDurianBundle:User', 7));

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'findBy', 'updateUserSize'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->willReturn(true);

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $pdoExcep = new \PDOException('Duplicate', 40001);
        $pdoExcep->errorInfo[1] = 1213;
        $pdoExcep->errorInfo[2] = 'Duplicate entry for key Database is busy';
        $exception = new \Exception(
            'Duplicate entry for key Database is busy',
            150010066,
            $pdoExcep
        );

        $mockEm->expects($this->any())
            ->method('persist')
            ->willThrowException($exception);

        $mockSl = $this->getMockBuilder('BB\DurianBundle\Share\ActiveSLNext')
            ->disableOriginalConstructor()
            ->setMethods(['isUpdating', 'hasBeenUpdated'])
            ->getMock();

        $mockSl->expects($this->any())
            ->method('isUpdating')
            ->willReturn(false);

        $mockSl->expects($this->any())
            ->method('hasBeenUpdated')
            ->willReturn(true);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('durian.activate_sl_next', $mockSl);

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen123456789012345678912345',
            'role' => '1',
            'currency' => 'TWD',
            'client_ip' => '127.0.0.1',
            'verify_blacklist' => false
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010066, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試新增會員，但username含有空白
     */
    public function testNewUserAndUsernameContainsBlanks()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username'  => ' chosen1 ',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => '1',
            'currency'  => 'TWD',
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertNull($output['ret']['last_bank']);

        $this->runCommand('durian:update-user-size');

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);

        // 基本資料檢查
        $this->assertEquals('ztester', $user->getParent()->getUsername());
        $this->assertEquals('chosen1', $user->getUsername());
        $this->assertEquals('chosen1', $user->getPassword());
        $this->assertEquals('chosen12345678901234', $user->getAlias());
        $this->assertEquals(0, $user->getSize());
        $this->assertEquals(3, $user->getParent()->getSize());
        $this->assertEquals(1, $user->getRole());
    }

    /**
     * 測試新增使用者同時新增詳細資料，帶入超過一個有值的證件欄位
     */
    public function testCreateUserAndDetailWithMoreThanOneCredentialValues()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen12345678901234',
            'role' => '1',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'health_card' => 'HC123456',
                'birthday' => '2001-01-01',
                'telephone' => '3345678',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian',
                'identity_card' => '8852468',
                'driver_license' => '7878468',
                'insurance_card' => '8787468',
                'health_card' => '7887468'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090011, $output['code']);
        $this->assertEquals('Cannot specify more than one credential fields', $output['msg']);
    }

    /**
     * 測試新增測試帳號同時新增詳細資料，但真實姓名不是Test User
     */
    public function testCreateTestUserAndDetailWithInvalidNameReal()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 7,
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen12345678901234',
            'test' => 1,
            'role' => 1,
            'user_detail' => ['name_real' => 'DaVanCi']
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 新增測試帳號真實姓名自動修改為 Test User
        $detail = $em->find('BBDurianBundle:UserDetail', 20000001);
        $this->assertEquals('Test User', $detail->getNameReal());
    }

    /**
     * 測試建立使用者時，就算沒帶詳細資料參數，也會強制建立空的詳細資料
     */
    public function testCreateUserAndForceCreateDetail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen12345678901234',
            'role' => '1'
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $detail = $em->find('BBDurianBundle:UserDetail', 20000001);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals('chosen1', $output['ret']['username']);
        $this->assertEquals('chosen12345678901234', $output['ret']['alias']);
        $this->assertEquals(20000001, $output['ret']['user_detail']['user_id']);
        $this->assertEquals($detail->getNickname(), $output['ret']['user_detail']['nickname']);
        $this->assertEquals($detail->getNameReal(), $output['ret']['user_detail']['name_real']);
        $this->assertEquals($detail->getNameChinese(), $output['ret']['user_detail']['name_chinese']);
        $this->assertEquals($detail->getNameEnglish(), $output['ret']['user_detail']['name_english']);
        $this->assertEquals($detail->getCountry(), $output['ret']['user_detail']['country']);
        $this->assertEquals($detail->getPassport(), $output['ret']['user_detail']['passport']);
        $this->assertEquals($detail->getIdentityCard(), $output['ret']['user_detail']['identity_card']);
        $this->assertEquals($detail->getDriverLicense(), $output['ret']['user_detail']['driver_license']);
        $this->assertEquals($detail->getInsuranceCard(), $output['ret']['user_detail']['insurance_card']);
        $this->assertEquals($detail->getHealthCard(), $output['ret']['user_detail']['health_card']);
        $this->assertEquals($detail->getPassword(), $output['ret']['user_detail']['password']);
        $this->assertEquals($detail->getTelephone(), $output['ret']['user_detail']['telephone']);
        $this->assertEquals($detail->getQQNum(), $output['ret']['user_detail']['qq_num']);
        $this->assertEquals($detail->getNote(), $output['ret']['user_detail']['note']);
        $this->assertEquals($detail->getBirthday(), $output['ret']['user_detail']['birthday']);

        // 檢查log operation是否有該筆detail資料
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 3);
        $arrLogOperation = explode(', ', $logOperation->getMessage());
        $this->assertEquals('user_detail', $logOperation->getTableName());
        $this->assertEquals('@user_id:20000001', $logOperation->getMajorKey());
        $this->assertEquals('@nickname:', $arrLogOperation[0]);
        $this->assertEquals('@name_real:', $arrLogOperation[1]);
        $this->assertEquals('@name_chinese:', $arrLogOperation[2]);
        $this->assertEquals('@passport:', $arrLogOperation[5]);
    }

    /**
     * 測試新增不會有佔成的使用者
     */
    public function testNewUserCanNotOwnShare()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => '1',
            'currency'  => 'TWD',
            'sharelimit' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('POST', '/api/user', $parameters, array(), $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);

        // 基本資料檢查
        $this->assertEquals('ztester', $user->getParent()->getUsername());
        $this->assertEquals('chosen1', $user->getUsername());
        $this->assertEquals('chosen1', $user->getPassword());
        $this->assertEquals('chosen12345678901234', $user->getAlias());

        // shareLimit資料檢查，在會員角色的user不會有sharelimit，有傳入也會忽略。
        $share = $user->getShareLimit(1);
        $this->assertEmpty($share);

        $shareNext = $user->getShareLimitNext(1);
        $this->assertEmpty($shareNext);
    }

    /**
     * 測試新增無密碼的使用者，並且測試修改密碼時發生例外
     */
    public function testNewUserNoPasswordAndSetPasswordException()
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

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果

        $newUserId = $output['ret']['id'];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $newUserId);
        $this->assertNull($output['ret']['last_bank']);

        // 檢查密碼
        $user = $em->find('BBDurianBundle:User', $newUserId);
        $this->assertEquals('ztester', $user->getParent()->getUsername());
        $this->assertEquals('', $user->getPassword());

        $parameters = array('password' => 'hhyt678');

        $client->request('PUT', "/api/user/$newUserId", $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010072, $output['code']);
        $this->assertEquals('DisabledPassword user cannot change password', $output['msg']);
    }

    /**
     * 測試新增有佔成的使用者
     */
    public function testNewUserCanOwnShare()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => '6',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => '2',
            'currency'  => 'TWD',
            'sharelimit' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 40,
                    'parent_lower' => 5
                )
            ),
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);

        // 基本資料檢查
        $this->assertEquals('ytester', $user->getParent()->getUsername());
        $this->assertEquals('chosen1', $user->getUsername());

        // shareLimit資料檢查
        $share = $user->getShareLimit(1);
        $this->assertEquals(15, $share->getUpper());

        $shareNext = $user->getShareLimitNext(1);
        $this->assertEquals(5, $shareNext->getParentLower());
    }

    /**
     * 測試新增使用者時上層快開額度餘額不足
     */
    public function testNewUserButParentCashfakeNotEnoughBalance()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 7,
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen1',
            'role'      => 1,
            'currency'  => 'TWD',
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance'  => 100000
            ),
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050031, $output['code']);
        $this->assertEquals('Not enough balance', $output['msg']);

        // 驗證無 cash_fake_entry_queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $this->assertEquals(0, $redis->llen('cash_fake_entry_queue'));
    }


    /**
     * 測試新增使用者時上層沒有啟用外接額度
     */
    public function testNewUserButParentNotEnableOutside()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $payway = $em->find('BBDurianBundle:UserPayway', 3);
        $payway->disableOutside();
        $em->flush();

        $parameters = [
            'parent_id' => 7,
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen1',
            'role' => 1,
            'currency' => 'TWD',
            'outside' => 1
        ];

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010173, $output['code']);
        $this->assertEquals('No outside supported', $output['msg']);
    }

    /**
     * 測試新增廳主
     */
    public function testNewDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'name' => 'dodo',
            'role' => 7,
            'currency' => 'CNY',
            'login_code' => 'nga',
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance' => 100
            ),
            'cash' => array('currency' => 'CNY'),
            'credit' => array(
                1 => array('line' => 100),
                2 => array('line' => 1000)
            ),
            'outside' => 1,
            'sharelimit' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            )
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertNull($output['ret']['last_bank']);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);

        $this->assertNull($user->getParent());
        $this->assertEquals('domainator', $user->getUsername());
        $this->assertEquals('domainator', $user->getAlias());
        $this->assertEquals(0, $user->getSize());

        $this->assertEquals($output['ret']['id'], $user->getDomain());
        $this->assertEquals(156, $user->getCurrency());

        // cashFake資料檢查
        $cashFake = $user->getCashFake();
        $this->assertEquals(156, $cashFake->getCurrency());
        $this->assertEquals(100, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());
        $this->assertTrue($cashFake->isEnable());

        // cash資料檢查
        $cash = $user->getCash();
        $this->assertEquals(156, $cash->getCurrency());
        $this->assertEquals(0, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        // credit資料檢查
        $credit = $user->getCredit(2);
        $this->assertEquals(1000, $credit->getLine());
        $this->assertTrue($credit->isEnable());

        // user payway檢查
        $payway = $em->find('BBDurianBundle:UserPayway', $user->getId());
        $this->assertTrue($payway->isOutsideEnabled());

        // outside payway 檢查
        $outsidePayway = $em->find('BBDurianBundle:OutsidePayway', $user->getId());
        $this->assertNotNull($outsidePayway);

        // shareLimit資料檢查
        $share = $user->getShareLimit(1);
        $this->assertEquals(100, $share->getUpper());
        $this->assertEquals(0, $share->getLower());
        $this->assertEquals(100, $share->getParentUpper());
        $this->assertEquals(0, $share->getParentLower());

        $shareNext = $user->getShareLimitNext(1);
        $this->assertEquals(100, $shareNext->getUpper());
        $this->assertEquals(0, $shareNext->getLower());
        $this->assertEquals(100, $shareNext->getParentUpper());
        $this->assertEquals(0, $shareNext->getParentLower());

        // 檢查DomainCurrency DB資料
        $criteria = array('domain' => $user);
        $currencies = $em->getRepository('BBDurianBundle:DomainCurrency')
                         ->findBy($criteria);
        $this->assertEquals(156, $currencies[0]->getCurrency());
        $this->assertFalse(isset($currencies[1]));

        // 檢查DomainCurrency操作紀錄
        $logop = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('domain_currency', $logop->getTableName());
        $this->assertEquals('@domain:52', $logop->getMajorKey());
        $this->assertEquals('@currency:156', $logop->getMessage());

        // 檢查有成功新增DomainTotalTest
        $totalTest2 = $em->find('BBDurianBundle:DomainTotalTest', 52);
        $this->assertEquals(0, $totalTest2->getTotalTest());

        //檢查有成功新增LoginCode
        $this->assertEquals('nga', $output['ret']['login_code']);

        //檢查有成功新增DomainConfig
        $config = $emShare->find('BBDurianBundle:DomainConfig', $user->getId());
        $this->assertEquals('dodo', $config->getName());
        $this->assertEquals('nga', $config->getLoginCode());

        //測試新增廳主信用額度後噴例外是否會有php的信用額度rollback錯誤
        $parameters = array(
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'role' => 7,
            'currency' => 'CNY',
            'name' => 'domainator2',
            'login_code' => 'd2',
            'cash' => array('currency' => 'CNY'),
            'credit' => array(
                1 => array('line' => 100),
                2 => array('line' => 1000)
            ),
            'sharelimit' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 101,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 101,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            )
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Lower can not be set over 100', $output['msg']);
        $this->assertEquals('150080007', $output['code']);
    }

    /**
     * 測試新增廳主未帶廳名
     */
    public function testNewDomainWithoutName()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'role' => 7,
            'currency' => 'CNY',
            'login_code' => 'nga',
            'name' => 'domainator',
            'cash_fake' => [
                'currency' => 'CNY',
                'balance' => 100
            ],
            'cash' => ['currency' => 'CNY'],
            'credit' => [
                1 => ['line' => 100],
                2 => ['line' => 1000]
            ],
            'sharelimit' => [
                1 => [
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                ]
            ],
            'sharelimit_next' => [
                1 => [
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                ]
            ]
        ];

        $client->request('POST', '/api/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 未帶廳名則使用alias當廳名
        $config = $em->find('BBDurianBundle:DomainConfig', $output['ret']['id']);
        $this->assertEquals('domainator', $config->getName());
    }

    /**
     * 測試新增廳主時廳名已存在
     */
    public function testCreateDomainButNameExist()
    {
        $client = $this->createClient();

        $parameter = [
            'user_id' => 888765,
            'role' => 7,
            'name' => 'domain3',
            'login_code' => 'd3',
            'username' => 'invaliddomain',
            'password' => 'newpassword',
            'alias' => '重複廳名'
        ];

        $client->request('POST', '/api/user', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Name already exist', $output['msg']);
        $this->assertEquals(150360017, $output['code']);
    }

    /**
     * 測試新增廳主,廳名與被刪除廳相同
     */
    public function testCreateDomainNameExistButRemove()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $config = new DomainConfig(4, 'domain4', 'dd');
        $config->remove();
        $emShare->persist($config);
        $emShare->flush();

        $parameters = [
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'name' => 'domain4',
            'role' => 7,
            'currency' => 'CNY',
            'login_code' => 'nga',
            'credit' => [
                1 => ['line' => 100]
            ],
            'sharelimit' => [
                1 => [
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                ]
            ],
            'sharelimit_next' => [
                1 => [
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                ]
            ]
        ];

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertNull($output['ret']['last_bank']);

        //檢查有成功新增User
        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);
        $this->assertNull($user->getParent());
        $this->assertEquals('domainator', $user->getUsername());
        $this->assertEquals('domainator', $user->getAlias());
        $this->assertEquals(0, $user->getSize());
        $this->assertEquals($output['ret']['id'], $user->getDomain());
        $this->assertEquals(156, $user->getCurrency());

        //檢查有成功新增LoginCode
        $this->assertEquals('nga', $output['ret']['login_code']);

        //檢查有成功新增DomainConfig
        $config = $emShare->find('BBDurianBundle:DomainConfig', $user->getId());
        $this->assertEquals('domain4', $config->getName());
        $this->assertEquals('nga', $config->getLoginCode());
    }

    /**
     * 測試新增廳主時上層Id代入錯誤
     */
    public function testNewDomainWithWrongParentId()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 7,
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'currency' => 'CNY',
            'name' => 'domainator2',
            'login_code' => 'd2',
            'role' => 7,
            'cash' => array('currency' => 'CNY'),
            'sharelimit' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 101,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            ),
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Domain shall not have parent', $output['msg']);
        $this->assertEquals('150010058', $output['code']);
    }

    /**
     * 測試新增使用者時使用自動產生UserId但重複的情況
     */
    public function testCreateUserWithDuplicateId()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.sequence');

        $parameters = [
            'parent_id' => 7,
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'role' => 1
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $redis->set('user_seq', 20000000);

        $parameters = [
            'parent_id' => 7,
            'username' => 'domainator22',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'role' => 1
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(23000, $output['code']);
        $this->assertEquals('PRIMARY KEY must be unique', $output['msg']);
    }

    /**
     * 測試新增非廳主使用者時指定userID
     */
    public function testCreateUserWithSpecifiedUserId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'

        ];
        $client->request('GET', '/api/user/id', $parameters);

        $parameters = [
            'parent_id' => 7,
            'user_id' => 20000001,
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'role' => 1,
        ];
        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals('domainator2', $output['ret']['username']);
        $this->assertEquals('domainator2', $output['ret']['alias']);
        $this->assertEquals(1, $output['ret']['role']);
    }

    /**
     * 測試新增非廳主使用者時指定不合法的userId
     */
    public function testCreateUserWithInvalidUserId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 7,
            'user_id' => 123456,
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'role' => 1,
        ];

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid user_id', $output['msg']);
        $this->assertEquals('150010055', $output['code']);
    }

    /**
     * 測試新增非廳主使用者時指定的userId非API產生
     */
    public function testCreateUserWithUserIdIsNotGenerateByAPI()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 7,
            'user_id' => 20000001,
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'role' => 1,
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not a generated user_id', $output['msg']);
        $this->assertEquals('150010059', $output['code']);
    }

    /**
     * 測試新增廳主子帳號時指定userId
     */
    public function testCreateDomainSubUserWithSpecifiedUserId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '3',
            'role' => '7',
            'sub' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];
        $client->request('GET', '/api/user/id', $parameters);

        $parameter = [
            'parent_id' => 3,
            'user_id' => 20000001,
            'role' => 7,
            'username' => 'domainsub',
            'password' => 'newpassword',
            'alias' => '測試',
            'sub' => 1
        ];

        $client->request('POST', '/api/user', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['role']);
        $this->assertEquals('domainsub', $output['ret']['username']);
        $this->assertEquals('測試', $output['ret']['alias']);
        $this->assertEquals(1, $output['ret']['sub']);
    }

    /**
     * 測試新增廳主子帳號時指定不合法的userId
     */
    public function testCreateDomainSubUserWithInvalidUserId()
    {
        $client = $this->createClient();

        $parameter = [
            'parent_id' => 3,
            'user_id' => 123456,
            'role' => 7,
            'username' => 'domainsub',
            'password' => 'newpassword',
            'alias' => '測試',
            'sub' => 1
        ];

        $client->request('POST', '/api/user', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid user_id', $output['msg']);
        $this->assertEquals('150010055', $output['code']);
    }

    /**
     * 測試新增廳主子帳號時指定的userId非API產生
     */
    public function testCreateDomainSubUserWithUserIdIsNotGenerateByAPI()
    {
        $client = $this->createClient();

        $parameter = [
            'parent_id' => 3,
            'user_id' => 20000001,
            'role' => 7,
            'username' => 'domainsub',
            'password' => 'newpassword',
            'alias' => '測試',
            'sub' => 1
        ];

        $client->request('POST', '/api/user', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not a generated user_id', $output['msg']);
        $this->assertEquals('150010059', $output['code']);
    }

    /**
     * 測試新增廳主時代碼重覆
     */
    public function testDomainSubWithDuplicateLoginCode()
    {
        $client = $this->createClient();

        $parameter = array(
            'user_id' => 888765,
            'role' => 7,
            'name' => 'domainag',
            'login_code' => 'ag',
            'username' => 'invaliddomain',
            'password' => 'newpassword',
            'alias' => '無效的代碼',
        );

        $client->request('POST', '/api/user', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid login code', $output['msg']);
        $this->assertEquals(150010081, $output['code']);
    }

    /**
     * 測試新增廳主時未帶入廳名
     */
    public function testDomainSubWithoutDomainName()
    {
        $client = $this->createClient();

        $parameter = [
            'user_id' => 888765,
            'role' => 7,
            'login_code' => 'ag',
            'username' => 'invaliddomain',
            'password' => 'newpassword',
            'alias' => '無效的代碼',
        ];

        $client->request('POST', '/api/user', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No domain name specified', $output['msg']);
        $this->assertEquals(150010140, $output['code']);
    }

    /**
     * 測試新增會員時parent亦為會員
     */
    public function testNewUserWithMemberParent()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => '8',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => 1
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Parent can not be member', $output['msg']);
        $this->assertEquals('150010063', $output['code']);
    }

    /**
     * 測試新增會員時role參數不合法
     */
    public function testNewUserWithInvalidRole()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => 2
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid role', $output['msg']);
        $this->assertEquals('150010064', $output['code']);
    }

    /**
     * 測試新增使用者且沒設定預改佔成
     */
    public function testNewUserWithoutShareLimitNext()
    {
        $client = $this->createClient();

        $parameters = array(
            'username' => 'chosen2',
            'password' => 'chosen2',
            'alias' => 'chosen2',
            'role' => 7,
            'name' => 'chosen2',
            'login_code' => 'ch',
            'currency' => 'TWD',
            'sharelimit' => array(
                1 => array(
                    'upper' => 15,
                    'lower' => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            ),
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(52, $output['ret']['id']);
        $this->assertEquals('chosen2', $output['ret']['username']);
        $this->assertEquals('chosen2', $output['ret']['alias']);
        $this->assertEquals('TWD', $output['ret']['currency']);
        $this->assertNull($output['ret']['last_bank']);

        // shareLimit資料檢查
        $this->assertEquals(15, $output['ret']['sharelimit'][1]['upper']);
        $this->assertEquals(15, $output['ret']['sharelimit'][1]['lower']);
        $this->assertEquals(15, $output['ret']['sharelimit'][1]['parent_upper']);
        $this->assertEquals(5, $output['ret']['sharelimit'][1]['parent_lower']);

        // shareLimitNext資料檢查
        $this->assertEquals(15, $output['ret']['sharelimit_next'][1]['upper']);
        $this->assertEquals(15, $output['ret']['sharelimit_next'][1]['lower']);
        $this->assertEquals(15, $output['ret']['sharelimit_next'][1]['parent_upper']);
        $this->assertEquals(5, $output['ret']['sharelimit_next'][1]['parent_lower']);
    }

    /**
     * 測試新增使用者且上層使用者沒設定預改佔成
     */
    public function testNewUserButParentWithoutShareLimitNext()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 3);
        $shareLimitNext = $em->find('BBDurianBundle:ShareLimitNext', $user);
        $em->remove($shareLimitNext);
        $em->flush();

        $parameters = [
            'parent_id' => '3',
            'username' => 'chosen2',
            'password' => 'chosen2',
            'alias' => 'chosen2',
            'role' => 5,
            'name' => 'chosen2',
            'login_code' => 'ch',
            'currency' => 'TWD',
            'sharelimit' => [
                1 => [
                    'upper' => 15,
                    'lower' => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                ]
            ],
        ];
        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010114, $output['code']);
        $this->assertEquals('No parent sharelimit_next found', $output['msg']);
    }

    /**
     * 測試新增使用者失敗後上層金錢回溯皆正確
     */
    public function testNewUserFailedAndMoneyRollBackCorrectly()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $userBefore = $em->find('BBDurianBundle:User', 6);
        $creditOrigin1 = $userBefore->getCredit(1)->getTotalLine();
        $creditOrigin2 = $userBefore->getCredit(2)->getTotalLine();

        $cashFake = new CashFake($userBefore, 156);
        $cashFake->setBalance(101);
        $em->persist($cashFake);
        $em->flush();
        $cashFakeOrigin = $userBefore->getCashFake()->getBalance();
        $em->clear();

        $parameters = array(
            'parent_id' => '6',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen1',
            'currency'  => 'CNY',
            'role'      => '2',
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance'  => 100
            ),
            'cash' => array('currency' => 'CNY'),
            'credit' => array(
                1 => array('line' => 100),
                2 => array('line' => 1000)
            ),
            'sharelimit' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 5,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 5,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            ),
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cmdParams = [
            '--credit' => 1,
            '--entry' => 1,
            '--period' => 1
        ];
        $out = $this->runCommand('durian:sync-credit', $cmdParams);

        // 驗證Response結果，要是佔成的錯誤才能驗證信用額度跟假現金有被回復。
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080018, $output['code']);

        $user = $em->find('BBDurianBundle:User', 6);

        // credit資料檢查
        $this->assertEquals($creditOrigin1, $user->getCredit(1)->getTotalLine());
        $this->assertEquals($creditOrigin2, $user->getCredit(2)->getTotalLine());

        // CashFake資料檢查
        $this->assertEquals($cashFakeOrigin, $user->getCashFake()->getBalance());

        // 驗證無 cash_fake_entry_queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $this->assertEquals(0, $redis->llen('cash_fake_entry_queue'));
    }

    /**
     * 測試連續新增使用者且不會超過最大餘額限制
     */
    public function testNewTwoUserAndCaskfakeBalanceIsLessThanMax()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'user_id' => 335,
            'username' => 'hellscream',
            'password' => 'hellscream',
            'alias' => 'hellscream',
            'currency' => 'CNY',
            'name' => 'hellscream',
            'login_code' => 'hs',
            'role' => 7,
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance' => 9876543210
            ),
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $pId = $output['ret']['id'];
        $user = $em->find('BBDurianBundle:User', $pId);

        // cashFake資料檢查
        $cashFake = $user->getCashFake();
        $this->assertEquals(156, $cashFake->getCurrency());
        $this->assertEquals(9876543210, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        $parameters = array(
            'parent_id' => $pId,
            'username'  => 'hellscream1',
            'password'  => 'hellscream5',
            'alias'     => 'hellscream1',
            'currency'  => 'CNY',
            'role'      => 5,
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance'  => 876543210
            ),
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);

        // cashFake資料檢查
        $cashFake = $user->getCashFake();
        $this->assertEquals(156, $cashFake->getCurrency());
        $this->assertEquals(876543210, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());
    }

    /**
     * 測試新增使用者失敗歸還額度
     */
    public function testNewUserFailedAndPayAmountBackToParent()
    {
        $client = $this->createClient();
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 注意: 因為redis沒有餘額資料, 這裡目的是讓 redis 有餘額資訊
        $parent = $em->find('BBDurianBundle:User', 7);
        $cashFakeId = $parent->getCashFake()->getId();
        $client->request('GET', '/api/cash_fake/' . $cashFakeId);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals(500, $output['ret']['balance']);

        $parameters = array(
            'parent_id' => 7,
            'username'  => 'test',
            'password'  => '15484555',
            'alias'     => 't',
            'role'      => 1,
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance'  => 100
            ),
            'cash' => array('currency' => 'CNY'),
            'credit' => array(
                1 => array('line' => 1000000000000000000),//把credit弄壞
                2 => array('line' => 1000)
            )
        );

        $client->request('POST', '/api/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('error', $output['result']);

        $pKey = 'cash_fake_balance_7_156';

        //歸還後redis餘額應與資料庫原本餘額相同
        $balance = $redisWallet->hget($pKey, 'balance') / 10000;
        $this->assertEquals($parent->getCashFake()->getBalance(), $balance);
    }

    /**
     * 測試新增使用者信用額度不足會歸還Redis的信用額度
     */
    public function testNewUserNotEnoughLineAndPayAmountBackToParent()
    {
        $client = $this->createClient();
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 注意: 因為redis沒有total_line資料, 這裡目的是讓 redis 有total_line資訊
        $parent = $em->find('BBDurianBundle:User', 7);

        $creditId = $parent->getCredit(1)->getId();
        $client->request('GET', '/api/credit/' . $creditId);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $creditId = $parent->getCredit(2)->getId();
        $client->request('GET', '/api/credit/' . $creditId);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        // 修改 total_line, group_num = 1 不變
        $oriTotalLine = $redisWallet->hget('credit_7_1', 'total_line');
        $this->assertEquals(5000, $oriTotalLine);

        $oriTotalLine = $redisWallet->hget('credit_7_2', 'total_line');
        $this->assertEquals(3000, $oriTotalLine);
        $redisWallet->hset('credit_7_2', 'total_line', '1');

        $parameters = [
            'parent_id' => 7,
            'username'  => 'test',
            'password'  => '15484555',
            'alias'     => 't',
            'role'      => 1,
            'credit' => [
                1 => ['line' => 4000],
                2 => ['line' => 3000]  //把credit弄壞
            ]
        ];

        $client->request('POST', '/api/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010177, $output['code']);
        $this->assertEquals('Not enough line to be dispensed', $output['msg']);

        //同步，並檢查資料庫
        $this->runCommand('durian:sync-credit', ['--credit' => true]);

        $em->clear();
        $parent = $em->find('BBDurianBundle:User', 7);
        $this->assertEquals(5000, $parent->getCredit(1)->getTotalLine());
        $this->assertEquals(1, $parent->getCredit(2)->getTotalLine());

        //歸還後redis total_line 應與原本 total_line相同
        $totalLine = $redisWallet->hget('credit_7_1', 'total_line');
        $this->assertEquals(5000, $totalLine);

        $totalLine = $redisWallet->hget('credit_7_2', 'total_line');
        $this->assertEquals(1, $totalLine);
    }

    /**
     * 測試在佔成更新期間新增使用者
     */
    public function testNewUserDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $parameters = array(
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen1',
            'role'      => 1
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Cannot perform this during updating sharelimit', $output['msg']);
    }

    /**
     * 測試在太久沒跑佔成更新的狀況下新增使用者
     */
    public function testNewUserWithoutUpdatingForTooLongTime()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::FINISHED, '2011-10-10 11:59:00');

        $parameters = array(
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen1',
            'role'      => 1
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Cannot perform this due to updating sharelimit is not performed for too long time',
            $output['msg']
        );
    }

    /**
     * 測試新增會員,但UserId大於20000000
     */
    public function testNewUserButUserIdOverLimit()
    {
        $parameters = [
            'user_id' => '20000001',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'name' => 'dodo',
            'role' => '7',
            'cash' => ['currency' => 'CNY'],
            'currency' => 'CNY',
            'login_code' => 'nga',
            'cash_fake' => [
                'currency' => 'CNY',
                'balance' => 100
            ],
            'credit' => [
                1 => ['line' => 100],
                2 => ['line' => 1000]
            ],
        ];

        $client = $this->createClient();
        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010055', $output['code']);
        $this->assertEquals('Invalid user_id', $output['msg']);
    }

    /**
     * 測試新增子帳號
     */
    public function testNewSub()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user8 = $em->find('BBDurianBundle:User', 8);
        //測試上層的下層數量是否為0
        $this->assertEquals(0, $user8->getSize());

        $parameters = array(
            'parent_id' => 7,
            'username'  => 'testerson',
            'password'  => 'tester1on',
            'alias'     => 'testerSon',
            'sub'       => 1,
            'role'      => 2,
            'cash_fake' => [
                'currency' => 'CNY',
                'balance'  => 100
            ],
            'cash' => ['currency' => 'CNY'],
            'credit' => [
                1 => ['line' => 100],
                2 => ['line' => 1000]
            ],
            'sharelimit' => [
                1 => [
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                ]
            ],
            'sharelimit_next' => [
                1 => [
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                ]
            ]
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BBDurianBundle:User', 20000001);

        // 新增子帳號後上層下層數量是否仍為0
        $this->assertEquals(0, $user8->getSize());

        // 基本資料檢查
        $this->assertEquals('ztester', $user->getParent()->getUsername());
        $this->assertEquals('testerson', $user->getUsername());
        $this->assertEquals('tester1on', $user->getPassword());
        $this->assertEquals('testerSon', $user->getAlias());
        $this->assertEquals(2, $user->getRole());

        $this->assertEquals(2, $user->getDomain());

        $this->assertTrue($user->isSub());
        $this->assertTrue($user->isEnabled());
        $this->assertFalse($user->isBlock());

        // cashFake資料檢查
        $this->assertNull($user->getCashFake());

        // credit資料檢查
        $this->assertEquals(0, count($user->getCredits()));

        // shareLimit資料檢查
        $this->assertEquals(0, count($user->getShareLimits()));
        $this->assertEquals(0, count($user->getShareLimitNexts()));
    }

    /**
     * 測試新增非廳主使用者，帶入不存在parent_id
     */
    public function testCreateUserWithNonExistParentId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 1000,
            'username'  => 'noparent',
            'password'  => 'noparent',
            'alias'     => 'noparent',
            'role'      => 1,
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010023', $output['code']);
        $this->assertEquals('No parent found', $output['msg']);
    }

    /**
     * 測試新增子帳號，帶入不存在parent_id
     */
    public function testCreateSubWithNonExistParentId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 1000,
            'username'  => 'noparent',
            'password'  => 'noparent',
            'alias'     => 'noparent',
            'role'      => 7,
            'sub'       => 1
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010023', $output['code']);
        $this->assertEquals('No parent found', $output['msg']);
    }

    /**
     * 測試新增子帳號但role不合法
     */
    public function testNewSubWithInvalidRole()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 3,
            'username'  => 'testerson',
            'password'  => 'tester1on',
            'alias'     => 'testerSon',
            'sub'       => 1,
            'role'      => 4
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid role', $output['msg']);
        $this->assertEquals('150010064', $output['code']);
    }

    /**
     * 測試新增一般使用者(不帶其他資料)
     */
    public function testNewWithOutParameter()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 7,
            'username'  => 'test01',
            'password'  => 'test01',
            'alias'     => 'test01',
            'role'      => 1,
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);

        $user = $em->find('BBDurianBundle:User', 20000001);

        // 基本資料檢查
        $this->assertEquals('ztester', $user->getParent()->getUsername());
        $this->assertEquals('test01', $user->getUsername());
        $this->assertEquals('test01', $user->getPassword());
        $this->assertEquals('test01', $user->getAlias());
        $this->assertEquals(1, $user->getRole());
    }

    /**
     * 測試新增使用者時關閉啟用、凍結、重設密碼且為測試帳號
     */
    public function testNewIsDisableBlockAndTest()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 7,
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen1',
            'enable'    => 0,
            'block'     => 1,
            'test'      => 1,
            'role'      => 1,
            'password_reset' => 1
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BBDurianBundle:User', 20000001);

        $this->assertFalse($user->isEnabled());
        $this->assertTrue($user->isBlock());
        $this->assertTrue($user->isTest());
        $this->assertTrue($user->isPasswordReset());

        // 新增測試帳號沒帶詳細資料，真實姓名為 Test User
        $detail = $em->find('BBDurianBundle:UserDetail', 20000001);
        $this->assertEquals('Test User', $detail->getNameReal());
    }

    /**
     * 測試新增子帳號時關閉啟用、重設密碼且凍結
     */
    public function testNewSubIsDisableAndBlock()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 7,
            'username'  => 'testerson',
            'password'  => 'tester1on',
            'alias'     => 'testerSon',
            'sub'       => 1,
            'enable'    => 0,
            'block'     => 1,
            'role'      => 2,
            'password_reset' => 1
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BBDurianBundle:User', 20000001);

        $this->assertFalse($user->isEnabled());
        $this->assertTrue($user->isBlock());
        $this->assertTrue($user->isPasswordReset());
    }

    /**
     * 測試新增使用者parent為測試帳號
     */
    public function testNewUserWhenParentIsTest()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parentUser = $em->find('BBDurianBundle:User', 7);
        $parentUser->setTest(1);
        $em->flush();
        $em->clear();

        // 取得目前測試帳號數量
        $output = $this->getResponse('PUT', '/api/domain/2/total_test');
        $this->assertEquals('ok', $output['result']);
        $beforeTest = $output['ret']['total_test'];

        $parameters = array(
            'parent_id' => 7,
            'username'  => 'hall',
            'password'  => 'hallpw',
            'alias'     => 'Hall',
            'role'      => 1
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);

        //測試是否使用者為測試帳號
        $this->assertTrue($user->isTest());

        // 檢查廳下層測試帳號是否增加
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals($beforeTest + 1, $totalTest->getTotalTest());
    }

    /**
     * 測試新增測試帳號,同一廳下層會員的測試帳號超過限制數量
     */
    public function testNewTestUserOverTheLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 設定parent為測試帳號
        $parentUser = $em->find('BBDurianBundle:User', 7);
        $parentUser->setTest(1);

        $domain2 = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $domain2->setTotalTest(DomainConfig::MAX_TOTAL_TEST);

        $em->flush();

        $dc = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $dc->setBlockTestUser(true);

        $emShare->flush();

        $parameters = [
            'parent_id' => 7,
            'username'  => 'hall',
            'password'  => 'hallpw',
            'alias'     => 'Hall',
            'role'      => 1
        ];

        $output = $this->getResponse('POST', '/api/user', $parameters);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The number of test exceeds limitation in the same domain', $output['msg']);
        $this->assertEquals(150010136, $output['code']);

        // 設定廳不阻擋測試帳號
        $config2 = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config2->setBlockTestUser(false);
        $emShare->flush();

        // 驗證可正常新增使用者
        $output = $this->getResponse('POST', '/api/user', $parameters);
        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試新增使用者parent為隱藏測試帳號
     */
    public function testNewUserWhenParentIsHiddenTest()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parentUser = $em->find('BBDurianBundle:User', 7);
        $parentUser->setHiddenTest(1);
        $em->flush();
        $em->clear();

        $parameters = [
            'parent_id' => 7,
            'username'  => 'joline',
            'password'  => 'jolinepw',
            'alias'     => 'woho',
            'role'      => 1
        ];

        $client->request('POST', '/api/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);
        $this->assertTrue($user->isHiddenTest());
    }

    /**
     * 測試新增使用者cash但上層payway不支援cash
     */
    public function testNewButParentNotSupportCash()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 10,
            'username'  => 'member',
            'password'  => '123456',
            'alias'     => 'member',
            'role'      => 5,
            'cash'      => ['curry' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No cash supported', $output['msg']);
        $this->assertEquals(150010119, $output['code']);
    }

    /**
     * 測試新增使用者時上層沒有相對應的credit
     */
    public function testNewButParentNotHaveCredit()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'  => '7',
            'username'   => 'member',
            'password'   => '123456',
            'alias'      => 'member',
            'role'       => '1',
            'credit'     => array(3 => array('line' => 0))
            );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No parent credit found', $output['msg']);
        $this->assertEquals(150010112, $output['code']);
    }

    /**
     * 測試新增使用者時上層沒有相對應的佔成
     */
    public function testNewButParentNotHaveShareLimit()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'  => '6',
            'username'   => 'love5566',
            'password'   => '123456',
            'alias'      => 'love5566',
            'role'       => '2',
            'sharelimit' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                ),
                2 => array(
                    'upper' => 0,
                    'lower' => 0,
                    'parent_upper' => 10,
                    'parent_lower' => 10
                )
            )
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No parent sharelimit found', $output['msg']);
        $this->assertEquals(150010113, $output['code']);
    }

    /**
     * 測試新增使用者時上層沒有相對應的預改佔成
     */
    public function testNewButParentNotHaveShareLimitNext()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'  => '6',
            'username'   => 'love5566',
            'password'   => '123456',
            'alias'      => 'love5566',
            'role'       => 2,
            'sharelimit' => array(
                2 => array(
                    'upper' => 0,
                    'lower' => 0,
                    'parent_upper' => 10,
                    'parent_lower' => 10
                )
            ),
            'sharelimit_next' => array(
                2 => array(
                    'upper' => 0,
                    'lower' => 0,
                    'parent_upper' => 10,
                    'parent_lower' =>10
                )
            )
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No parent sharelimit found', $output['msg']);
        $this->assertEquals(150010113, $output['code']);
    }

    /**
     * 測試新增使用者時上層有佔成但卻沒有送
     */
    public function testNewWithoutShareLimitButParentHave()
    {
        $client = $this->createClient();
        $em     = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        //測試沒傳入上層相對應的佔成
        $parameters = array(
            'parent_id' => '6',
            'username'  => 'love5566',
            'password'  => '123456',
            'alias'     => 'love5566',
            'role'      => '2'
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Need to set sharelimit groups when parent have', $output['msg']);
        $this->assertEquals(150010108, $output['code']);

        // 測試新增使用者失敗是否統計會被建立
        $criteria = array('domain' => 2);
        $stat = $em->getRepository('BBDurianBundle:UserCreatedPerIp')
                   ->findOneBy($criteria);

        $this->assertNull($stat);
    }

    /**
     * 測試新增使用者時, 快開額度的balance超過小數點後4位
     */
    public function testNewWithInvalidCashFakeBalance()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => '1',
            'currency'  => 'TWD',
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance'  => 100.00111,
                'operator' => 'bbin'
            )
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610003, $output['code']);
        $this->assertEquals('The decimal digit of amount exceeds limitation', $output['msg']);
    }

    /**
     * 測試新增使用者指定ID
     */
    public function testNewUserWithUserId()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'username' => 'test',
            'password' => 'test123',
            'alias' => 'test',
            'user_id' => '123',
            'role' => 7,
            'name' => 'test',
            'login_code' => 'tt'
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(123, $output['ret']['id']);

        $user = $em->find('BBDurianBundle:User', $output['ret']['id']);

        $this->assertNull($user->getParent());
        $this->assertEquals('test', $user->getUsername());
    }

    /**
     * 測試新增使用者指定的ID為空字串
     */
    public function testNewUserWithUserIdIsEmptyString()
    {
        $client = $this->createClient();

        $parameters = array(
            'username' => 'test',
            'password' => 'test123',
            'alias' => 'test',
            'user_id' => '',
            'role' => 7,
            'name' => 'test',
            'login_code' => 'tt'
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(52, $output['ret']['id']);
    }

    /**
     * 測試新增使用者指定的ID已存在
     */
    public function testNewUserWithUserIdAlreadyExist()
    {
        $client = $this->createClient();

        $parameters = array(
            'username' => 'test',
            'password' => 'test123',
            'alias' => 'test',
            'user_id' => '2',
            'role' => 7,
            'name' => 'test',
            'login_code' => 'tt',
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010055, $output['code']);
        $this->assertEquals('Invalid user_id', $output['msg']);
    }

    /**
     * 測試新增使用者時, 選擇快開額度但沒設定balance(預設為0)
     */
    public function testNewUserWithoutCashFakeBalance()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => '1',
            'currency'  => 'TWD',
            'cash_fake' => array(
                'currency' => 'CNY',
                'operator' => 'bbin'
            )
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_add']);
    }

    /**
     * 測試新增使用者時, 上層使用者假現金為空
     */
    public function testNewUserWithParentCashFakeNull()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '6',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen12345678901234',
            'role' => '2',
            'currency' => 'TWD',
            'cash_fake' => ['currency' => 'CNY'],
            'credit' => [0 => ['line' => 0]]
        ];

        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No parent cashFake found', $output['msg']);
        $this->assertEquals(150010111, $output['code']);
    }

    /**
     * 測試新增使用者時, 信用額度為零
     */
    public function testNewUserWithCreditZero()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen12345678901234',
            'role' => '1',
            'currency' => 'TWD',
            'credit' => [0 => ['line' => 0]]
        ];

        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals('chosen1', $output['ret']['username']);
    }

    /**
     * 測試新增使用者, 帶入oauth參數
     */
    public function testNewUserWithOauthParameter()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id'         => '7',
            'username'          => 'chosen1',
            'password'          => '',
            'role'              => 1,
            'disabled_password' => true,
            'alias'             => 'chosen12345678901234',
            'oid'               => 'old-123',
            'currency'          => 'TWD',
            'oauth_vendor_id'   => 1,
            'oauth_openid'      => 'abcd1234',
            'cash'              => array('currency' => 'CNY')
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['oauth'][0]['vendor_id']);
        $this->assertEquals('abcd1234', $output['ret']['oauth'][0]['openid']);

        // oauth binding
        $userId = $output['ret']['id'];
        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->findOneBy(array('userId' => $userId));
        $this->assertEquals($userId, $binding->getUserId());
        $this->assertEquals('weibo', $binding->getVendor()->getName());
        $this->assertEquals('abcd1234', $binding->getOpenid());

        //確認使用者密碼為空字串
        $userPassword = $em->find('BBDurianBundle:UserPassword', $output['ret']['id']);
        $this->assertEquals('', $userPassword->getHash());
    }

    /**
     * 測試新增使用者, 帶入不合法的oauth參數
     */
    public function testNewUserWithInvalidOauthParameter()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'       => '7',
            'username'        => 'chosen1',
            'role'            => 1,
            'password'        => 'chosen1',
            'alias'           => 'chosen12345678901234',
            'oid'             => 'old-123',
            'currency'        => 'TWD',
            'oauth_vendor_id' => 1,
            'oauth_openid'    => '',
            'cash'            => array('currency' => 'CNY')
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid oauth openid', $output['msg']);
        $this->assertEquals(150010103, $output['code']);
    }

    /**
     * 測試新增使用者, 帶入不存在的oauth vendor
     */
    public function testNewUserWithNonExistOauthVendor()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'       => '7',
            'username'        => 'chosen1',
            'role'            => 1,
            'password'        => 'chosen1',
            'role'            => '1',
            'alias'           => 'chosen12345678901234',
            'oid'             => 'old-123',
            'currency'        => 'TWD',
            'oauth_vendor_id' => 99999,
            'oauth_openid'    => 'abcd123',
            'cash'            => array('currency' => 'CNY')
        );

        $client->request('POST', '/api/user', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid oauth vendor', $output['msg']);
        $this->assertEquals(150010104, $output['code']);
    }

    /**
     * 測試新增廳主會一起新增 payway
     */
    public function testNewUserWithNewUserPayway()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'username'   => 'chosen1',
            'password'   => 'chosen1',
            'alias'      => 'chosen12345678901234',
            'role'       => '7',
            'name'       => 'chosen1',
            'login_code' => 'abc',
            'currency'   => 'TWD',
            'cash'       => ['currency' => 'CNY'],
            'credit'     => [
                1 => ['line' => 100],
                2 => ['line' => 1000]
            ],
            'client_ip'  => '127.0.0.1'
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(52, $output['ret']['id']);
        $this->assertEquals('chosen1', $output['ret']['username']);

        // 驗證 payway
        $payway = $em->find('BBDurianBundle:UserPayway', 52);
        $this->assertNotNull($payway);
        $this->assertTrue($payway->isCashEnabled());
        $this->assertFalse($payway->isCashFakeEnabled());
        $this->assertTrue($payway->isCreditEnabled());

        // 操作紀錄檢查
        $logOpPayway = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('user_payway', $logOpPayway->getTableName());
        $this->assertEquals('@user_id:52', $logOpPayway->getMajorKey());
        $this->assertEquals('@cash:true, @credit:true', $logOpPayway->getMessage());
    }

    /**
     * 測試新增大股東時，廳主為混和廳時則必須新增user_payway
     */
    public function testNewUserWithDomainIsMix()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 先檢查userId=3是否為混和廳
        $payway = $em->find('BBDurianBundle:UserPayway', 3);
        $this->assertTrue($payway->isCashEnabled());
        $this->assertTrue($payway->isCreditEnabled());

        $parameters = [
            'parent_id' => 3,
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => '5',
            'cash'      => ['currency' => 'CNY'],
            'client_ip' => '127.0.0.1'
        ];
        $parameters['sharelimit'][1] = [
            'upper'        => 80,
            'lower'        => 10,
            'parent_upper' => 90,
            'parent_lower' => 10
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals('chosen1', $output['ret']['username']);

        // 驗證 User
        $user = $em->find('BBDurianBundle:User', 20000001);
        $this->assertNotNull($user);

        // 驗證 payway
        $payway = $em->find('BBDurianBundle:UserPayway', 20000001);
        $this->assertTrue($payway->isCashEnabled());
        $this->assertFalse($payway->isCashFakeEnabled());
        $this->assertFalse($payway->isCreditEnabled());
    }

    /**
     * 測試新增大股東時，支援交易方式與廳主相同，不會新增 payway
     */
    public function testNewUserWithSamePayway()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $domainPayway = $em->find('BBDurianBundle:UserPayway', 3);
        $domainPayway->disableCredit();
        $domainPayway->disableOutside();
        $em->flush();

        $parameters = [
            'parent_id' => 3,
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => '5',
            'cash'      => ['currency' => 'CNY'],
            'client_ip' => '127.0.0.1'
        ];
        $parameters['sharelimit'][1] = [
            'upper'        => 80,
            'lower'        => 10,
            'parent_upper' => 90,
            'parent_lower' => 10
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals('chosen1', $output['ret']['username']);

        // 驗證 User
        $user = $em->find('BBDurianBundle:User', 20000001);
        $this->assertNotNull($user);

        // 驗證 payway
        $payway = $em->find('BBDurianBundle:UserPayway', 20000001);
        $this->assertNull($payway);
    }

    /**
     * 測試新增大股東時，支援交易方式與廳主皆不相同新增payway
     */
    public function testNewUserWithDifferentPayway()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $domainPayway = $em->find('BBDurianBundle:UserPayway', 3);
        $domainPayway->enableCashFake();
        $domainPayway->enableCash();
        $em->flush();

        $parameters = [
            'parent_id' => '3',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen123456789012345678912345',
            'role' => '5',
            'currency' => 'TWD',
        ];
        $parameters['sharelimit'][1] = [
            'upper' => 80,
            'lower' => 10,
            'parent_upper' => 90,
            'parent_lower' => 10
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals('chosen1', $output['ret']['username']);

        // 驗證 User
        $user = $em->find('BBDurianBundle:User', 20000001);
        $this->assertNotNull($user);

        // 驗證 payway
        $payway = $em->find('BBDurianBundle:UserPayway', 20000001);
        $this->assertNotNull($payway);
    }

    /**
     * 測試新增使用者時，轉換信用額度後超過可用餘額，會回復原 total_line
     */
    public function testNewUserWithNoEnoughLineAndRecoverTotalLine()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $client = $this->createClient();

        $client->request('GET', '/api/user/7/credit/2');

        $creditKey = 'credit_7_2';
        $totalLine = $redisWallet->hget($creditKey, 'total_line');

        // 先測試原本的 total_line
        $this->assertEquals(3000, $totalLine);

        $parameters = [
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => 'chosen12345678901234',
            'role'      => '1',
            'currency'  => 'TWD',
            'credit' => [
                1 => ['line' => 100],
                2 => ['line' => 25300]
            ],
            'client_ip' => '127.0.0.1'
        ];
        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060041, $output['code']);
        $this->assertEquals('TotalLine is greater than parent credit', $output['msg']);


        $totalLine = $redisWallet->hget($creditKey, 'total_line');

        // 確保不會因為 floor 而多 -1
        $this->assertEquals(3000, $totalLine);
    }

    /**
     * 測試新增使用者,該ip新增使用者超出DomainConfig::LIMITED_CREATED_USER_TIMES,且近期被封鎖過,加入封鎖列表中
     */
    public function testCreateUserExceedBasicWithAddBlackList()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $tenDaysAgo = $now->sub(new \DateInterval('P10D'));

        // 新增一筆近期的封鎖列表
        $data = [
            'id'          => 8,
            'domain'      => 2,
            'ip'          => ip2long('127.0.0.1'),
            'create_user' => 1,
            'login_error' => 0,
            'created_at'  => $tenDaysAgo->format('Y-m-d H:i:s'),
            'modified_at' => $tenDaysAgo->format('Y-m-d H:i:s'),
            'removed'     => 0,
            'operator'    => ''
        ];

        $em->getConnection()->insert('ip_blacklist', $data);

        $client = $this->createClient();

        // 廳設定阻擋
        $client->request('PUT', '/api/domain/2/config', ['block_create_user' => 1]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 設定ip 127.0.0.1已新增使用者28筆
        $now = new \DateTime('now');

        $stat = new UserCreatedPerIp('127.0.0.1', $now, 2);
        $em->persist($stat);
        $em->flush();

        $repo = $em->getRepository('BBDurianBundle:UserCreatedPerIp');
        $repo->increaseCount($stat->getId(), DomainConfig::LIMITED_CREATED_USER_TIMES - 2);

        $getParameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        // 新增第29筆
        $client->request('GET', '/api/user/id', $getParameters);

        $postParameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'client_ip' => '127.0.0.1',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除測試',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $postParameters, [], $this->headerParam);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 驗證沒有加入封鎖列表
        $list = $em->find('BBDurianBundle:IpBlacklist', 9);
        $this->assertNull($list);

        // 測試新增使用者第30筆
        $client->request('GET', '/api/user/id', $getParameters);

        $postParameters['user_id'] = 20000002;
        $postParameters['username'] = 'domainator1';

        $client->request('POST', '/api/user', $postParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 驗證是否有加入封鎖列表
        $list = $em->find('BBDurianBundle:IpBlacklist', 9);
        $this->assertEquals(2, $list->getDomain());
        $this->assertEquals('127.0.0.1', $list->getIp());
    }

    /**
     * 測試新增使用者,該ip新增使用者超出最大設定的限制,加入封鎖列表中
     */
    public function testCreateUserExceedMaxWithAddBlackList()
    {
        $client = $this->createClient();

        // 廳設定阻擋
        $client->request('PUT', '/api/domain/2/config', ['block_create_user' => 1]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 設定ip 127.0.0.1已新增使用者298筆
        $now = new \DateTime('now');
        $stat = new UserCreatedPerIp('127.0.0.1', $now, 2);
        $emShare->persist($stat);
        $emShare->flush();

        $repo = $emShare->getRepository('BBDurianBundle:UserCreatedPerIp');
        $repo->increaseCount($stat->getId(), DomainConfig::MAX_CREATE_USER_TIMES - 2);

        $getParam = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $getParam);

        // 測試新增使用者第299筆,且近期沒有被加入到封鎖列表的紀錄
        $putParam = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'client_ip' => '127.0.0.1',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除測試',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $putParam, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 驗證沒有加入封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertNull($list);

        // 測試新增使用者第300筆
        $client->request('GET', '/api/user/id', $getParam);

        $putParam['user_id'] = 20000002;
        $putParam['username'] = 'domainator1';

        $client->request('POST', '/api/user', $putParam, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 驗證有加入封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $createTime = $list->getCreatedAt()->format('Y:m:d H:00:00');
        $modifiedTime = $list->getModifiedAt()->format('Y:m:d H:00:00');
        $nowStr = $now->format('Y:m:d H:00:00');

        $this->assertEquals(2, $list->getDomain());
        $this->assertEquals('127.0.0.1', $list->getIp());
        $this->assertEquals($nowStr, $createTime);
        $this->assertEquals($nowStr, $modifiedTime);
        $this->assertFalse($list->isRemoved());
        $this->assertEquals('', $list->getOperator());

        // 檢查log operation是否有該筆資料
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 7);
        $arrLogOperation = explode(', ', $logOperation->getMessage());
        $this->assertEquals('ip_blacklist', $logOperation->getTableName());
        $this->assertEquals('@domain:2', $arrLogOperation[0]);
        $this->assertEquals('@ip:127.0.0.1', $arrLogOperation[1]);
        $this->assertEquals('@create_user:true', $arrLogOperation[2]);
        $this->assertEquals('@removed:false', $arrLogOperation[3]);
        $this->assertEquals('@operator:', $arrLogOperation[6]);
    }

    /**
     * 測試新增使用者,該ip新增使用者超出限制,且時效內的封鎖列表已存在該筆ip資料
     */
    public function testCreateUserWithBlackListHasRecord()
    {
        $client = $this->createClient();

        // 廳設定阻擋
        $client->request('PUT', '/api/domain/2/config', ['block_create_user' => 1]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 移除封鎖列表
        $client->request('DELETE', '/api/domain/ip_blacklist', ['blacklist_id' => 1]);

        // 設定ip 126.0.0.1已新增使用者100筆
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $now = new \DateTime('now');

        $stat = new UserCreatedPerIp('126.0.0.1', $now, 2);
        $em->persist($stat);
        $em->flush();

        $repo = $em->getRepository('BBDurianBundle:UserCreatedPerIp');
        $repo->increaseCount($stat->getId(), DomainConfig::MAX_CREATE_USER_TIMES);

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '126.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        // 測試新增使用者第101筆
        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'client_ip' => '126.0.0.1',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除測試',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 因封鎖列表已存在該筆ip資料,驗證是否有再次加入封鎖列表
        $list = $em->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertNull($list);
    }

    /**
     * 測試跨廳新增使用者超過限制,封鎖列表已經有一筆該ip資料，會一併加入黑名單
     */
    public function testCreateUserCrossDomainOverLimitWithIpBlackListHasRecord()
    {
        $client = $this->createClient();

        // 廳設定阻擋
        $client->request('PUT', '/api/domain/9/config', ['block_create_user' => 1]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 設定ip 126.0.0.1已新增使用者10筆
        $now = new \DateTime('now');

        $parent = $em->find('BBDurianBundle:User', 7);
        $parent->setDomain(9);
        $em->flush();

        $stat = new UserCreatedPerIp('126.0.0.1', $now, 9);
        $emShare->persist($stat);
        $emShare->flush();

        $repo = $emShare->getRepository('BBDurianBundle:UserCreatedPerIp');
        $repo->increaseCount($stat->getId(), DomainConfig::MAX_CREATE_USER_TIMES);

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 9,
            'client_ip' => '126.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        // 測試新增使用者第11筆
        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'client_ip' => '126.0.0.1',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除測試',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 驗證有再次加入封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertEquals('126.0.0.1', $list->getIp());
        $this->assertEquals(9, $list->getDomain());
        $this->assertTrue($list->isCreateUser());
        $this->assertFalse($list->isLoginError());
        $this->assertFalse($list->isRemoved());
        $this->assertEquals('', $list->getOperator());

        // 驗證有加入黑名單
        $blackList = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('126.0.0.1', $blackList->getIp());
        $this->assertTrue($blackList->isWholeDomain());
        $this->assertEmpty($blackList->getDomain());

        // 檢查操作紀錄
        $repo = $emShare->getRepository('BBDurianBundle:LogOperation');
        $logOperation = $repo->findOneBy(['tableName' => 'ip_blacklist']);
        $arrLogOperation = explode(', ', $logOperation->getMessage());
        $this->assertEquals('ip_blacklist', $logOperation->getTableName());
        $this->assertEquals('@domain:9', $arrLogOperation[0]);
        $this->assertEquals('@ip:126.0.0.1', $arrLogOperation[1]);
        $this->assertEquals('@create_user:true', $arrLogOperation[2]);
        $this->assertEquals('@removed:false', $arrLogOperation[3]);
        $this->assertEquals('@operator:', $arrLogOperation[6]);

        $logOperation = $repo->findOneBy(['tableName' => 'blacklist']);
        $arrLogOperation = explode(', ', $logOperation->getMessage());
        $this->assertEquals('blacklist', $logOperation->getTableName());
        $this->assertEquals('@whole_domain:true', $arrLogOperation[0]);
        $this->assertEquals('@ip:126.0.0.1', $arrLogOperation[1]);

        $blackLog = $emShare->find('BBDurianBundle:BlacklistOperationLog', 1);
        $this->assertEquals('system', $blackLog->getCreatedOperator());
        $this->assertEquals('註冊使用者超過限制', $blackLog->getnote());
    }

    /**
     * 測試新增使用者電話號碼帶文字
     */
    public function testCreateUserWithTelephoneInputCharacter()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'identity_card' => 'IC123456',
                'driver_license' => 'DL123456',
                'insurance_card' => 'INC123456',
                'health_card' => 'HC123456',
                'birthday' => '2001-01-01',
                'telephone' => '334567x',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid telephone', $output['msg']);
        $this->assertEquals(150610001, $output['code']);
    }

    /**
     * 測試新增使用者電話號碼帶+以外的特殊符號
     */
    public function testCreateUserWithTelephoneInputExceptPlusSpecialCharacter()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'identity_card' => 'IC123456',
                'driver_license' => 'DL123456',
                'insurance_card' => 'INC123456',
                'health_card' => 'HC123456',
                'birthday' => '2001-01-01',
                'telephone' => '33*5678',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid telephone', $output['msg']);
        $this->assertEquals(150610001, $output['code']);
    }

    /**
     * 測試新增使用者電話號碼開頭以外帶+
     */
    public function testCreateUserWithTelephoneInputExceptStartWithPlus()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'identity_card' => 'IC123456',
                'driver_license' => 'DL123456',
                'insurance_card' => 'INC123456',
                'health_card' => 'HC123456',
                'birthday' => '2001-01-01',
                'telephone' => '3345678+',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid telephone', $output['msg']);
        $this->assertEquals(150610001, $output['code']);
    }

    /**
     * 測試新增使用者,該ip新增使用者超出限制,廳沒設定是否阻擋,不加入封鎖列表
     */
    public function testCreateUserButDomainUnsetBlockCreateUser()
    {
        $client = $this->createClient();

        //設定ip 127.0.0.1已新增使用者299筆
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $now = new \DateTime('now');

        $stat = new UserCreatedPerIp('127.0.0.1', $now, 2);
        $em->persist($stat);
        $em->flush();

        $repo = $em->getRepository('BBDurianBundle:UserCreatedPerIp');
        $repo->increaseCount($stat->getId(), DomainConfig::MAX_CREATE_USER_TIMES - 1);

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        // 測試新增使用者第300筆,廳沒有設定是否阻擋,不加入封鎖列表
        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'client_ip' => '127.0.0.1',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除測試',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 驗證是否有加入封鎖列表
        $list = $em->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertNull($list);
    }

    /**
     * 測試新增使用者電話號碼開頭帶超過1個+
     */
    public function testCreateUserWithTelephoneInputStartWithOverOnePlus()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'identity_card' => 'IC123456',
                'driver_license' => 'DL123456',
                'insurance_card' => 'INC123456',
                'health_card' => 'HC123456',
                'birthday' => '2001-01-01',
                'telephone' => '++3345678',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid telephone', $output['msg']);
        $this->assertEquals(150610001, $output['code']);
    }

    /**
     * 測試新增使用者電話號碼開頭帶1個+
     */
    public function testCreateUserWithTelephoneInputStartWithOnePlus()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '+3345678',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('+3345678', $output['ret']['user_detail']['telephone']);
    }

    /**
     * 測試新增使用者,該ip新增使用者超出限制,廳設定不阻擋,不加入封鎖列表
     */
    public function testCreateUserButDomainSetNotBlockCreateUser()
    {
        $client = $this->createClient();

        //廳設定不阻擋
        $client->request('PUT', '/api/domain/2/config', ['block_create_user' => 0]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $now = new \DateTime('now');

        //設定ip 127.0.0.1已新增使用者299筆
        $stat = new UserCreatedPerIp('127.0.0.1', $now, 2);
        $emShare->persist($stat);
        $emShare->flush();

        $repo = $emShare->getRepository('BBDurianBundle:UserCreatedPerIp');
        $repo->increaseCount($stat->getId(), DomainConfig::MAX_CREATE_USER_TIMES - 1);

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        // 測試新增使用者第300筆
        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'client_ip' => '127.0.0.1',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除測試',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 驗證是否有加入封鎖列表
        $list = $emShare->find('BBDurianBundle:IpBlacklist', 8);
        $this->assertNull($list);
    }

    /**
     * 測試新增使用者電話號碼帶純數字
     */
    public function testCreateUserWithTelephoneInputIntegerOnly()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator1',
            'password' => 'domainator',
            'alias' => 'domainator1',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '叮叮',
                'name_chinese' => '人才',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '3345678',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('3345678', $output['ret']['user_detail']['telephone']);
    }

    /**
     * 測試新增使用者電話號碼帶空字串
     */
    public function testCreateUserWithTelephoneInputNull()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $parameters = [
            'parent_id' => '7',
            'user_id' => '20000001',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除測試',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('', $output['ret']['user_detail']['telephone']);
    }

    /**
     * 測試新增使用者並一起建立詳細資料時，真實姓名參數如果有空白會自動過濾空白
     */
    public function testCreateUserWithNameRealContainsBlanks()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除空白     ',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $detail = $em->find('BBDurianBundle:UserDetail', 20000001);
        $this->assertEquals('刪除空白', $detail->getNameReal());
    }

    /**
     * 測試新增使用者並一起建立詳細資料時，詳細資料的密碼參數如果有空白還是可以新增
     */
    public function testCreateUserWithDetailPasswordContainsBlanks()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '刪除空白',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456  ',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $detail = $em->find('BBDurianBundle:UserDetail', 20000001);
        $this->assertEquals('123456  ', $detail->getPassWord());
    }

    /**
     * 測試新增使用者並一起建立詳細資料時，真實姓名參數如果有特殊字元會自動過濾
     */
    public function testCreateUserWithNameRealContainsSpecialCharacter()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'name_real' => '特殊字元',
                'name_chinese' => '測試刪除',
                'name_english' => 'Din',
                'country' => 'ROI',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello  Din Din'
            ]
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $detail = $em->find('BBDurianBundle:UserDetail', 20000001);
        $this->assertEquals('特殊字元', $detail->getNameReal());
    }

    /**
     * 測試刪除使用者
     */
    public function testRemove()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $redis->hset('cash_fake_total_balance_2_156', 'test', 5000000);

        // 改為測試帳號
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setTest(true);
        $em->flush();

        $cashfakeId = $user->getCashFake()->getId();

        //確認UserPassword存在
        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);
        $this->assertNotEmpty($userPassword);

        //確認UserEmail存在
        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);
        $this->assertNotEmpty($userEmail);

        //確認LastLogin存在
        $lastLogin = $em->find('BBDurianBundle:LastLogin', 8);
        $this->assertNotEmpty($lastLogin);

        $em->clear();

        // 檢查層級人數
        $level = $em->find('BBDurianBundle:Level', 2);
        $this->assertEquals(100, $level->getUserCount());

        // 檢查層級幣別的人數
        $params = [
            'levelId' => 2,
            'currency' => 901
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $params);
        $this->assertEquals(4, $levelCurrency->getUserCount());

        $parent = $em->find('BBDurianBundle:User', 7);

        //檢查size
        $this->assertEquals(2, $parent->getSize());

        //驗證刪除前上層佔成min max
        $share = $parent->getShareLimit(1);
        $shareNext = $parent->getShareLimitNext(1);

        $this->assertEquals(200, $share->getMin1());
        $this->assertEquals(0, $share->getMax1());
        $this->assertEquals(0, $share->getMax2());

        $this->assertEquals(200, $shareNext->getMin1());
        $this->assertEquals(0, $shareNext->getMax1());
        $this->assertEquals(0, $shareNext->getMax2());

        $credit1 = $parent->getCredit(1);
        $credit2 = $parent->getCredit(2);

        $this->assertEquals(5000, $credit1->getTotalLine());
        $this->assertEquals(3000, $credit2->getTotalLine());

        $em->clear();

        // 檢查刪除前的測試帳號數量
        $output = $this->getResponse('PUT', '/api/domain/2/total_test');
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['total_test']);

        $parameters = array('operator' => 'bbin');

        //刪除
        $client->request('DELETE', '/api/user/8', $parameters, array(), $this->headerParam);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $cmdParams = array(
            '--entry' => 1,
            '--balance' => 1,
            '--history' => 1,
            '--entry' => 1,
            '--transaction' => 1
        );

        unset($cmdParams['--history']);

        $em->clear();
        //確認刪除使用者後會順便清掉redis資料
        $key = 'cash_fake_balance_8_156';
        $this->assertEmpty($redisWallet->hvals($key));

        //確認該廳會員額度已轉移回上層
        $key = 'cash_fake_total_balance_2_156';
        $this->assertEquals(0, $redis->hget($key, 'test'));

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);

        //資料是否備份
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUser', $removedUser);
        $removedUserEmail = $emShare->find('BBDurianBundle:RemovedUserEmail', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUserEmail', $removedUserEmail);
        $removedUserPassword = $emShare->find('BBDurianBundle:RemovedUserPassword', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUserPassword', $removedUserPassword);

        $em->clear();

        $this->runCommand('durian:update-user-size');

        $parent = $em->find('BBDurianBundle:User', 7);

        //檢查size
        $this->assertEquals(1, $parent->getSize());

        //測試上層佔成是否重新更新min max
        $share = $parent->getShareLimit(1);
        $shareNext = $parent->getShareLimitNext(1);

        $this->assertEquals(200, $share->getMin1());
        $this->assertEquals(0, $share->getMax1());
        $this->assertEquals(0, $share->getMax2());

        $this->assertEquals(200, $shareNext->getMin1());
        $this->assertEquals(0, $shareNext->getMax1());
        $this->assertEquals(0, $shareNext->getMax2());

        //測試上層totalLine 是否回復
        $credit1 = $parent->getCredit(1);
        $credit2 = $parent->getCredit(2);

        $this->assertEquals(0, $credit1->getTotalLine());
        $this->assertEquals(0, $credit2->getTotalLine());

        //資料是否刪除
        $entity = $em->find('BBDurianBundle:Card', 7);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:CashFake', 2);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:Credit', 5);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals(null, $entity);

        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);
        $this->assertNull($userPassword);

        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);
        $this->assertNull($userEmail);

        $lastLogin = $em->find('BBDurianBundle:LastLogin', 8);
        $this->assertNull($lastLogin);

        $bindings = $emShare->getRepository('BBDurianBundle:SlideBinding')->findByUserId(8);
        $this->assertEmpty($bindings);

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 檢查層級人數
        $level = $em->find('BBDurianBundle:Level', 2);
        $this->assertEquals(99, $level->getUserCount());

        // 檢查層級幣別的人數
        $params = [
            'levelId' => '2',
            'currency' => '901'
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $params);
        $this->assertEquals('3', $levelCurrency->getUserCount());

        //測試上層使否有寫操作者
        $entryOperator = $em->getRepository('BBDurianBundle:CashFakeEntryOperator')
                            ->findOneBy(array('entryId' => 1002));

        $this->assertEquals('ztester', $entryOperator->getWhom());
        $this->assertEquals(2, $entryOperator->getLevel());
        $this->assertEquals(1, $entryOperator->getTransferOut());
        $this->assertEquals('bbin', $entryOperator->getUsername());

        // 檢查刪除後廳下層的測試帳號數量是否有更新
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(0, $totalTest->getTotalTest());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試刪除廳主
     */
    public function testRemoveDomain()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $vendor = $em->find('BBDurianBundle:OauthVendor', 1); //weibo

        $user = new User();
        $user->setId(12);
        $user->setUsername('mtester');
        $user->setAlias('mtester');
        $user->setPassword('mtester');
        $user->setDomain(12);
        $user->setRole(7);
        $user->setSub(false);
        $em->persist($user);

        $detail = new UserDetail($user);
        $email = new UserEmail($user);
        $email->setEmail('');
        $password = new UserPassword($user);
        $password->setHash('');
        $em->persist($detail);
        $em->persist($email);
        $em->persist($password);

        $domainCurrency = new DomainCurrency($user, 156);
        $config = new DomainConfig($user, 'mtester', 'test');
        $shareLimit = new ShareLimit($user, 1);
        $payway = new UserPayway($user);
        $oauthUserBinding = new OauthUserBinding('12', $vendor, '123456');

        $em->persist($domainCurrency);
        $em->persist($shareLimit);
        $em->persist($payway);
        $em->persist($oauthUserBinding);
        $em->flush();

        $emShare->persist($config);
        $emShare->flush();
        $emShare->clear();

        $shareLimitNext = new ShareLimitNext($user, 1);
        $em->persist($shareLimitNext);
        $em->flush();

        //刪除廳主
        $client->request('DELETE', '/api/user/12');
        $em->clear();
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        //檢查刪除廳主會順便刪除登入尾碼、幣別、佔成、預改佔成、交易方式、Oauth使用者綁定資料
        $config = $emShare->getRepository('BBDurianBundle:DomainConfig')
            ->find($user->getId());
        $domainCurrency = $em->getRepository('BBDurianBundle:DomainCurrency')
            ->findBy(['domain' => $user->getId()]);
        $shareLimit = $em->getRepository('BBDurianBundle:ShareLimit')
            ->findBy(['user' => $user->getId()]);
        $shareLimitNext = $em->getRepository('BBDurianBundle:ShareLimitNext')
            ->findBy(['user' => $user->getId()]);
        $userPayway = $em->getRepository('BBDurianBundle:UserPayway')
            ->findBy(['userId' => $user->getId()]);
        $oauthUserBinding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->findBy(['userId' => $user->getId()]);

        $this->assertTrue($config->isRemoved());
        $this->assertTrue($domainCurrency[0]->isRemoved());
        $this->assertEmpty($shareLimit);
        $this->assertEmpty($shareLimitNext);
        $this->assertEmpty($userPayway);
        $this->assertEmpty($oauthUserBinding);
        $this->assertNull($em->find('BBDurianBundle:User', 12));
    }

    /**
     * 測試刪除使用者及子帳號
     */
    public function testRemoveUserAndSubUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 新增帳號
        $user = new User();
        $user->setId(30);
        $user->setUsername('mtester');
        $user->setAlias('mtester');
        $user->setPassword('mtester');
        $user->setDomain(30);
        $em->persist($user);

        $detail = new UserDetail($user);
        $email = new UserEmail($user);
        $email->setEmail('');
        $password = new UserPassword($user);
        $password->setHash('');
        $em->persist($detail);
        $em->persist($email);
        $em->persist($password);

        // 新增子帳號
        $subUser = new User();
        $subUser->setId(31);
        $subUser->setParent($user);
        $subUser->setUsername('mtestersub');
        $subUser->setAlias('mtestersub');
        $subUser->setPassword('mtestersub');
        $subUser->setSub(1);
        $subUser->setDomain(30);
        $em->persist($subUser);

        $subDetail = new UserDetail($subUser);
        $subEmail = new UserEmail($subUser);
        $subEmail->setEmail('');
        $subPassword = new UserPassword($subUser);
        $subPassword->setHash('');
        $em->persist($subDetail);
        $em->persist($subEmail);
        $em->persist($subPassword);

        $ancestor = new UserAncestor($subUser, $user, 1);
        $em->persist($ancestor);

        $em->flush();
        $em->clear();

        $subUser = $em->find('BBDurianBundle:User', 31);
        $this->assertEquals(30, $subUser->getParent()->getId());
        $this->assertTrue($subUser->isSub());

        $em->clear();

        $redis = $this->getContainer()->get('snc_redis.map');
        $redis->set('user:{1}:30:domain', 30);
        $redis->set('user:{1}:30:username', 'mtester');

        //刪除
        $client->request('DELETE', '/api/user/30');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);

        //資料是否備份
        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 31);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUser', $removedUser);

        $em->clear();

        //測試帳號是否被刪除
        $user = $em->find('BBDurianBundle:User', 30);
        $this->assertEquals(null, $user);
        $this->assertNull($redis->get('user:{1}:30:domain'));
        $this->assertNull($redis->get('user:{1}:30:username'));

        //測試帳號是否被刪除
        $subUser = $em->find('BBDurianBundle:User', 31);
        $this->assertEquals(null, $subUser);
    }

    /**
     * 測試刪除使用者及下層
     */
    public function testRemoveUserAndChild()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $subUser = $em->find('BBDurianBundle:User', 51);
        $subUser->setSub(1);

        $em->flush();
        $em->clear();

        //刪除
        $client->request('DELETE', '/api/user/7');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010020, $output['code']);
        $this->assertEquals('Can not remove user when user still anothers parent', $output['msg']);

        // 確認下層及子帳號是否還存在
        $childUser = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals(7, $childUser->getParent()->getId());
        $this->assertFalse($childUser->isSub());

        $subUser = $em->find('BBDurianBundle:User', 51);
        $this->assertEquals(7, $subUser->getParent()->getId());
        $this->assertTrue($subUser->isSub());
    }

    /**
     * 測試刪除使用者及oauth綁定
     */
    public function testRemoveUserAndOauthUserBinding()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->findBy(array('userId' => 51));
        $this->assertNotEmpty($binding);

        //刪除
        $client->request('DELETE', '/api/user/51');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 檢查使用者是否已被刪除
        $user = $em->find('BBDurianBundle:User', 51);
        $this->assertEmpty($user);

        // 檢查oauth綁定設定是否已被刪除
        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->findBy(array('userId' => 51));
        $this->assertEmpty($binding);
    }

    /**
     * 測試刪除使用者時其ID已存在 removed_user 資料表
     */
    public function testRemoveUserWithIdAlreadyExistInRemovedUser()
    {
        $client = $this->createClient();
        $client->request('DELETE', '/api/user/50', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010090', $output['code']);
        $this->assertEquals('User id already exists in the removed user list', $output['msg']);
    }

    /**
     * 測試刪除使用者：但資料未同步
     */
    public function testRemoveUserButCashFakeDateUnsyrchronised()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);

        //刪除前變動redis裡的值，讓資料庫與redis不同步
        $key = 'cash_fake_balance_8_156';
        $redisWallet->hincrby($key, 'balance', 200);

        //刪除
        $client->request('DELETE', '/api/user/8');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010048', $output['code']);
        $this->assertEquals('Can not remove user due to unsynchronised cashfake data', $output['msg']);
    }

    /**
     * 測試在佔成更新期間刪除使用者
     */
    public function testRemoveDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        //刪除
        $client->request('DELETE', '/api/user/8');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Cannot perform this during updating sharelimit', $output['msg']);
    }

    /**
     * 測試在太久沒跑佔成更新的狀況下刪除使用者
     */
    public function testRemoveWithoutUpdatingForTooLongTime()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::FINISHED, '2011-10-10 11:59:00');

        //刪除
        $client->request('DELETE', '/api/user/8');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Cannot perform this due to updating sharelimit is not performed for too long time',
            $output['msg']
        );
    }

    /**
     * 測試刪除使用者但信用額度群組2有錯誤跳例外
     */
    public function testRemoveButCreditGroup2ThrowException()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);

        $em->flush();
        $em->clear();

        $parent = $em->find('BBDurianBundle:User', 7);

        $credit1 = $parent->getCredit(1);
        $credit2 = $parent->getCredit(2);

        $this->assertEquals(5000, $credit1->getTotalLine());
        $this->assertEquals(3000, $credit2->getTotalLine());

        $user = $em->find('BBDurianBundle:User', 8);
        $creditId = $user->getCredit(2)->getId();

        $this->assertEquals(5000, $user->getCredit(1)->getLine());
        $this->assertEquals(3000, $user->getCredit(2)->getLine());

        //故意把刪除使用者的信用額度line 增加到比上層totalLine多而跳例外
        $sql = "Update credit SET line = 8000 WHERE id = ?";

        $em->getConnection()->executeUpdate($sql, array($creditId));

        $em->clear();

        $markName = 'credit_in_transfering';

        //測試credit mark是否沒有資料
        $this->assertEquals(0, $redisWallet->scard($markName));

        //刪除
        $client->request('DELETE', '/api/user/8');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('TotalLine can not be negative', $output['msg']);
        $this->assertEquals('150060050', $output['code']);

        // 測試是否有從轉移狀態中移除
        $this->assertEquals(0, $redisWallet->scard($markName));

        $em->clear();

        //測試上層totalLine 是否與原本相同
        $credit1 = $parent->getCredit(1);
        $credit2 = $parent->getCredit(2);

        $this->assertEquals(5000, $credit1->getTotalLine());
        $this->assertEquals(3000, $credit2->getTotalLine());

        $user = $em->find('BBDurianBundle:User', 8);

        //判斷使用者是否還存在
        $this->assertTrue($user instanceof User);

        //測試 line 是否跟原本相同
        $this->assertEquals(5000, $user->getCredit(1)->getLine());

        //此group 2 的因強制修改是否為修改後的8000
        $this->assertEquals(8000, $user->getCredit(2)->getLine());

        //測試 credit validator 是否可以正常 讀取 credit資料
        $parentCreditKey = 'credit_7_1';

        $this->assertFalse($redisWallet3->exists($parentCreditKey));

        $creditInfo = $this->getContainer()->get('durian.credit_op')->getBalanceByRedis(7, 1);

        $this->assertTrue($redisWallet3->exists($parentCreditKey));
        $this->assertEquals($parent->getCredit(1)->getTotalLine(), $creditInfo['total_line']);
    }

    /**
     * 測試刪除使用者及user_payway
     */
    public function testRemoveUserAndUserPayway()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $payway = $em->find('BBDurianBundle:UserPayway', 10);
        $this->assertNotNull($payway);
        $em->clear();

        //刪除
        $client->request('DELETE', '/api/user/10');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);

        // 檢查使用者是否已被刪除
        $user = $em->find('BBDurianBundle:User', 10);
        $this->assertEmpty($user);

        // 檢查user_payway是否已被刪除
        $payway = $em->find('BBDurianBundle:UserPayway', 10);
        $this->assertNull($payway);
    }

    /**
     * 測試回復使用者
     */
    public function testRecoverUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 設定租卡體系
        $user = $em->find('BBDurianBundle:User', 5);
        $user->setRent(true);

        $cash = $user->getCash();
        $cash->setBalance(0);

        // ancestor payway 啟用 cashFake
        $em->getRepository('BBDurianBundle:UserPayway')
            ->findOneBy(['userId' => 3])
            ->enableCashFake();
        $em->flush();

        $parameters = ['operator' => 'bbin'];

        // 刪除使用者
        $client->request('DELETE', '/api/user/8', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $parameters = ['domain' => 2];

        $this->runCommand('durian:update-user-size');
        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 檢查層級人數
        $level = $em->find('BBDurianBundle:Level', 5);
        $this->assertEquals(0, $level->getUserCount());

        // 檢查層級幣別的人數
        $params = [
            'levelId' => 5,
            'currency' => 156
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $params);
        $this->assertEquals(0, $levelCurrency->getUserCount());

        $parent = $em->find('BBDurianBundle:User', 7);

        //檢查size
        $this->assertEquals(1, $parent->getSize());

        // 回復使用者
        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $this->runCommand('durian:update-user-size');
        $em->refresh($parent);

        // 基本資料檢查
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals('ztester', $user->getParent()->getUsername());
        $this->assertEquals('tester', $user->getUsername());
        $this->assertEquals('123456', $user->getPassword());
        $this->assertEquals('tester', $user->getAlias());
        $this->assertEquals(0, $user->getSize());
        $this->assertEquals(2, $user->getParent()->getSize());
        $this->assertEquals(1, $user->getRole());
        $this->assertEquals($user->getParent()->getDomain(), $user->getDomain());
        $this->assertEquals(156, $user->getCurrency());
        $this->assertFalse($user->isSub());
        $this->assertTrue($user->isEnabled());
        $this->assertFalse($user->isBlock());
        $this->assertFalse($user->isPasswordReset());

        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 8);
        $this->assertNull($removedUser);

        // 確認回復使用者操作紀錄檢查
        $logOpUser = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user', $logOpUser->getTableName());
        $userLog = [
            '@username:tester',
            '@domain:2',
            '@alisas:tester',
            '@sub:false',
            '@enable:true',
            '@block:false',
            '@password:recover',
            '@test:false',
            '@currency:CNY',
            '@rent:false',
            '@password_reset:false',
            '@role:1'
        ];
        $this->assertEquals(implode(', ', $userLog), $logOpUser->getMessage());

        // user ancestor檢查
        $uaRepo = $em->getRepository('BBDurianBundle:UserAncestor');
        $userAncestor = $uaRepo->findOneBy([
            'user' => 8,
            'depth' => 6
        ]);
        $ancestorId = $userAncestor->getAncestor()->getId();
        $this->assertEquals($ancestorId, 2);

        // 詳細資料檢查
        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $this->assertEquals('達文西', $detail->getNameReal());
        $this->assertEquals('甲級情報員', $detail->getNameChinese());
        $this->assertEquals('Da Vinci', $detail->getNameEnglish());
        $this->assertEquals('Republic of China', $detail->getCountry());
        $this->assertEquals('PA123456', $detail->getPassport());
        $this->assertEquals('3345678', $detail->getTelephone());
        $this->assertEquals('485163154787', $detail->getQQNum());
        $this->assertEquals('Hello Durian', $detail->getNote());
        $this->assertEquals('', $detail->getPassword());

        $removedUD = $emShare->find('BBDurianBundle:RemovedUserDetail', 8);
        $this->assertNull($removedUD);

        // 確認回復使用者詳細資料操作記錄
        $logOpUserDetail = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('user_detail', $logOpUserDetail->getTableName());
        $this->assertEquals('@user_id:8', $logOpUserDetail->getMajorKey());
        $detailLog = [
            '@nickname:MJ149',
            '@name_real:達文西',
            '@name_chinese:甲級情報員',
            '@name_english:Da Vinci',
            '@country:Republic of China',
            '@passport:PA123456',
            '@identity_card:',
            '@driver_license:',
            '@insurance_card:',
            '@health_card:',
            '@birthday:2000-10-10',
            '@telephone:3345678',
            '@password:',
            '@qq_num:485163154787',
            '@note:Hello Durian'
        ];
        $this->assertEquals(implode(', ', $detailLog), $logOpUserDetail->getMessage());

        // 信箱檢查
        $email = $em->find('BBDurianBundle:UserEmail', 8);
        $this->assertEquals('Davinci@chinatown.com', $email->getEmail());

        $removedEmail = $emShare->find('BBDurianBundle:RemovedUserEmail', 8);
        $this->assertNull($removedEmail);

        // 確認回復使用者信箱操作記錄
        $logOpEmail = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('user_email', $logOpEmail->getTableName());
        $this->assertEquals('@user_id:8', $logOpEmail->getMajorKey());
        $this->assertEquals('@email:Davinci@chinatown.com', $logOpEmail->getMessage());

        // 密碼檢查
        $password = $em->find('BBDurianBundle:UserPassword', 8);
        $this->assertEquals('$2y$10$ElOdE7aZmwmgkqROzuiZROpiWz1G.ZUfhCIbJ0Co7GMx1Va1Yqft6', $password->getHash());
        $this->assertEquals('2010-06-12 12:00:21', $password->getExpireAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(2, $password->getErrNum());

        $removedPassword = $emShare->find('BBDurianBundle:RemovedUserPassword', 8);
        $this->assertNull($removedPassword);

        // 確認回復使用者密碼操作記錄
        $logOpPassword = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('user_password', $logOpPassword->getTableName());
        $this->assertEquals('@user_id:8', $logOpPassword->getMajorKey());
        $this->assertEquals('@hash:recover', $logOpPassword->getMessage());

        // 現金檢查
        $cash = $user->getCash();
        $this->assertEquals(7, $cash->getId());
        $this->assertEquals(901, $cash->getCurrency());
        $this->assertEquals(0, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $removedCash = $emShare->getRepository('BBDurianBundle:RemovedCash')->findOneBy(['removedUser' => 8]);
        $this->assertNull($removedCash);

        // 確認回復現金操作記錄
        $logOpCash = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('cash', $logOpCash->getTableName());
        $this->assertEquals('@user_id:8', $logOpCash->getMajorKey());
        $this->assertEquals('@currency:TWD', $logOpCash->getMessage());

        // 假現金檢查
        $cashFake = $user->getCashFake();
        $this->assertEquals(2, $cashFake->getId());
        $this->assertEquals(156, $cashFake->getCurrency());
        $this->assertEquals(0, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        $removedCashFake = $emShare->getRepository('BBDurianBundle:RemovedCashFake')->findOneBy(['removedUser' => 8]);
        $this->assertNull($removedCashFake);

        // 確認回復假現金操作記錄
        $logOpCash = $emShare->find('BBDurianBundle:LogOperation', 7);
        $this->assertEquals('cashFake', $logOpCash->getTableName());
        $this->assertEquals('@user_id:8', $logOpCash->getMajorKey());
        $this->assertEquals('@currency:CNY', $logOpCash->getMessage());

        // 信用額度檢查
        $credits = $user->getCredits();
        $this->assertNull($credits[0]);
        $this->assertEquals(5, $credits[1]->getId());
        $this->assertEquals(1, $credits[1]->getGroupNum());
        $this->assertEquals(0, $credits[1]->getLine());
        $this->assertEquals(6, $credits[2]->getId());
        $this->assertEquals(2, $credits[2]->getGroupNum());
        $this->assertEquals(0, $credits[2]->getLine());
        $this->assertNull($credits[3]);
        $this->assertNull($credits[4]);
        $this->assertNull($credits[5]);

        $removedCredits = $emShare->getRepository('BBDurianBundle:RemovedCredit')->findBy(['removedUser' => 8]);
        $this->assertEmpty($removedCredits);

        // 確認回復信用額度操作記錄
        $logOpCredit1 = $emShare->find('BBDurianBundle:LogOperation', 8);
        $this->assertEquals('credit', $logOpCredit1->getTableName());
        $this->assertEquals('@user_id:8', $logOpCredit1->getMajorKey());
        $creditLog = [
            '@group_num:1',
            '@line:0'
        ];
        $this->assertEquals(implode(', ', $creditLog), $logOpCredit1->getMessage());

        $logOpCredit2 = $emShare->find('BBDurianBundle:LogOperation', 9);
        $this->assertEquals('credit', $logOpCredit2->getTableName());
        $this->assertEquals('@user_id:8', $logOpCredit2->getMajorKey());
        $creditLog = [
            '@group_num:2',
            '@line:0'
        ];
        $this->assertEquals(implode(', ', $creditLog), $logOpCredit2->getMessage());

        // 租卡檢查
        $card = $user->getCard();
        $this->assertEquals(7, $card->getId());
        $this->assertEquals(0, $card->getEnableNum());
        $this->assertTrue($card->isEnabled());
        $this->assertEquals(0, $card->getBalance());
        $this->assertEquals(0, $card->getLastBalance());

        $removedCard = $emShare->getRepository('BBDurianBundle:RemovedCard')->findBy(['removedUser' => 8]);
        $this->assertEmpty($removedCard);

        // 確認回復租卡操作記錄
        $logOpCard = $emShare->find('BBDurianBundle:LogOperation', 10);
        $this->assertEquals('card', $logOpCard->getTableName());
        $this->assertEquals('@user_id:8', $logOpCard->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOpCard->getMessage());

        // user payway檢查
        $payway = $em->getRepository('BBDurianBundle:UserPayway')
            ->findOneBy(['userId' => 8]);
        $userPayway = $em->getRepository('BBDurianBundle:UserPayway')
            ->getUserPayway($user);
        $this->assertNull($payway);
        $this->assertTrue($userPayway->isCashEnabled());
        $this->assertTrue($userPayway->isCashFakeEnabled());
        $this->assertTrue($userPayway->isCreditEnabled());

        // 檢查會員層級
        $ul = $em->find('BBDurianBundle:UserLevel', 8);
        $this->assertEquals(5, $ul->getLevelId());

        // 檢查新增會員層級操作紀錄
        $logOpUL = $emShare->find('BBDurianBundle:LogOperation', 11);
        $this->assertEquals('user_level', $logOpUL->getTableName());
        $this->assertEquals('@user_id:8', $logOpUL->getMajorKey());
        $this->assertEquals('@level_id:5', $logOpUL->getMessage());

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        $em->refresh($level);
        $em->refresh($levelCurrency);

        // 檢查層級人數
        $this->assertEquals(1, $level->getUserCount());

        // 檢查層級幣別相關資料人數
        $this->assertEquals(1, $levelCurrency->getUserCount());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        // read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);

        // 檢查redis對應表
        $redis = $this->getContainer()->get('snc_redis.map');
        $domainKey = 'user:{1}:8:domain';
        $usernameKey = 'user:{1}:8:username';
        $this->assertEquals($user->getDomain(), $redis->get($domainKey));
        $this->assertEquals($user->getUsername(), $redis->get($usernameKey));
    }

    /**
     * 測試回復使用者但未帶廳主ID
     */
    public function testRecoverUserButNoDomainSpecified()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/8/recover', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010151, $output['code']);
        $this->assertEquals('No domain specified', $output['msg']);
    }

    /**
     * 測試回復使用者但使用者未被移除
     */
    public function testRecoverUserButNoSuchRemovedUser()
    {
        $client = $this->createClient();

        $parameters = ['domain' => 2];

        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010152, $output['code']);
        $this->assertEquals('No such removed user', $output['msg']);
    }

    /**
     * 測試回復使用者但帶入廳主ID不正確
     */
    public function testRecoverUserNotBelongToSpecifiedDomain()
    {
        $client = $this->createClient();

        $parameters = ['operator' => 'bbin'];

        // 刪除使用者
        $client->request('DELETE', '/api/user/8', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $parameters = ['domain' => 9];

        // 回復使用者
        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010152, $output['code']);
        $this->assertEquals('No such removed user', $output['msg']);
    }

    /**
     * 測試回復使用者但已另有使用者使用該帳號
     */
    public function testRecoverUserButUsernameExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = ['operator' => 'bbin'];

        // 刪除使用者8
        $client->request('DELETE', '/api/user/8', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $parameters = ['username' => 'tester'];

        // 修改使用者51
        $client->request('PUT', '/api/user/51', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('tester', $output['ret']['username']);

        $parameters = ['domain' => 2];

        // 回復使用者8
        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010153, $output['code']);
        $this->assertEquals('Can not recover user when its username already used by another', $output['msg']);
    }

    /**
     * 測試回復使用者但上層使用者不存在
     */
    public function testRecoverUserButParentNotExist()
    {
        $client = $this->createClient();

        $parameters = ['operator' => 'bbin'];

        // 刪除使用者51
        $client->request('DELETE', '/api/user/51', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 刪除使用者8
        $client->request('DELETE', '/api/user/8', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 6);
        $cash->setBalance(0);
        $em->flush();

        // 刪除使用者7(使用者8的上層)
        $client->request('DELETE', '/api/user/7', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $parameters = ['domain' => 2];

        // 回復使用者8
        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010154, $output['code']);
        $this->assertEquals('Can not recover user when its parent not exists', $output['msg']);
    }

    /**
     * 測試在佔成更新期間回復使用者
     */
    public function testRecoverUserDuringUpdating()
    {
        $client = $this->createClient();

        $parameters = ['operator' => 'bbin'];

        // 刪除使用者
        $client->request('DELETE', '/api/user/8', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $parameters = ['domain' => 2];

        // 回復使用者
        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010155, $output['code']);
        $this->assertEquals('Cannot perform this during updating sharelimit', $output['msg']);
    }

    /**
     * 測試在太久沒跑佔成更新的狀況下回復使用者
     */
    public function testRecoverUserWithoutUpdatingForTooLongTime()
    {
        $client = $this->createClient();

        $parameters = ['operator' => 'bbin'];

        // 刪除使用者
        $client->request('DELETE', '/api/user/8', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $this->changeUpdateCronState(ShareUpdateCron::FINISHED, '2011-10-10 11:59:00');

        $parameters = ['domain' => 2];

        // 回復使用者
        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010156, $output['code']);
        $this->assertEquals('Cannot perform this due to updating sharelimit is not performed for too long time', $output['msg']);
    }

    /**
     * 測試回復使用者但資料庫連線逾時
     */
    public function testRecoverUserButDbConnectionTimedOut()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.map');

        $parameters = ['operator' => 'bbin'];

        // 刪除使用者
        $client = $this->createClient();
        $client->request('DELETE', '/api/user/8', $parameters, [], $this->headerParam);

        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 8);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'commit',
                'beginTransaction',
                'getConnection',
                'rollback',
                'find',
                'persist',
                'remove',
                'flush',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($removedUser);

        $mockEm->expects($this->at(3))
            ->method('find')
            ->willReturn($emShare->find('BBDurianBundle:RemovedUserDetail', $removedUser));

        $mockEm->expects($this->at(6))
            ->method('find')
            ->willReturn($emShare->find('BBDurianBundle:RemovedUserEmail', $removedUser));

        $mockEm->expects($this->at(9))
            ->method('find')
            ->willReturn($emShare->find('BBDurianBundle:RemovedUserPassword', $removedUser));

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->willReturn(true);

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->willReturn($mockConn);

        $mockEm->expects($this->any(0))
            ->method('flush')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $emShare->clear();
        $em->clear();

        $parameters = ['domain' => 2];

        // 回復使用者
        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);
        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);
        $output = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(SOCKET_ETIMEDOUT, $output['code']);
        $this->assertEquals('Connection timed out', $output['msg']);
        $this->assertNotNull($emShare->find('BBDurianBundle:RemovedUser', 8));
        $this->assertNull($em->find('BBDurianBundle:User', 8));
        $this->assertNull($redis->get('user:{1}:8:domain'));
        $this->assertNull($redis->get('user:{1}:8:username'));
    }

    /**
     * 測試回復使用者但redis連線逾時
     */
    public function testRecoverUserButRedisConnectionTimedOut()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.map');

        $parameters = ['operator' => 'bbin'];

        // 刪除使用者
        $client = $this->createClient();
        $client->request('DELETE', '/api/user/8', $parameters, [], $this->headerParam);

        $mockRedis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['mset', 'del'])
            ->getMock();

        $mockRedis->expects($this->any())
            ->method('mset')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $em->clear();

        $parameters = ['domain' => 2];

        // 回復使用者
        $client = $this->createClient();
        $client->getContainer()->set('snc_redis.map', $mockRedis);
        $client->request('PUT', '/api/user/8/recover', $parameters, [], $this->headerParam);
        $output = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(SOCKET_ETIMEDOUT, $output['code']);
        $this->assertEquals('Connection timed out', $output['msg']);
        $this->assertNotNull($emShare->find('BBDurianBundle:RemovedUser', 8));
        $this->assertNull($em->find('BBDurianBundle:User', 8));
        $this->assertNull($redis->get('user:{1}:8:domain'));
        $this->assertNull($redis->get('user:{1}:8:username'));
    }

    /**
     * 測試凍結與解凍使用者
     */
    public function testBlockAnUnblock()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 使用者原本沒被凍結
        $user = $em->find('BBDurianBundle:User', 7);

        $this->assertFalse($user->isBlock());
        $this->assertEquals(1, $user->getErrNum());
        $lastModifiedAt = $user->getModifiedAt()->getTimeStamp();
        $em->flush();
        $em->clear();

        // 凍結
        $client->request('PUT', '/api/user/7/block', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 確定是否凍結成功
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['id']);
        $this->assertTrue($ret['ret']['block']);

        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        $user = $em->find('BBDurianBundle:User', 7);
        $this->assertTrue($user->isBlock());
        $this->assertEquals(1, $user->getErrNum());
        $em->clear();

        // 解凍
        $client->request('PUT', '/api/user/7/unblock', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 確定是否解凍成功
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['id']);
        $this->assertFalse($ret['ret']['block']);

        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);
        $userPasswordModifiedAt = $userPassword->getModifiedAt()->getTimeStamp();
        $this->assertGreaterThanOrEqual($userPasswordModifiedAt, $modifiedAt);

        $user = $em->find('BBDurianBundle:User', 7);
        $this->assertFalse($user->isBlock());
        $this->assertEquals(0, $user->getErrNum());
        $this->assertEquals(0, $userPassword->getErrNum());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
        $this->assertTrue(strpos($results[1], $line) !== false);
    }

    /**
     * 測試使用者停權開閉
     */
    public function testSetBankruptOnAndOff()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertFalse($user->isBankrupt());
        $lastModifiedAt = $user->getModifiedAt()->getTimeStamp();

        $em->flush();
        $em->clear();

        // 停權
        $client->request('PUT', '/api/user/8/bankrupt/1', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(8, $ret['ret']['id']);
        $this->assertTrue($ret['ret']['bankrupt']);
        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        // 關閉停權
        $client->request('PUT', '/api/user/8/bankrupt/0', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(8, $ret['ret']['id']);
        $this->assertFalse($ret['ret']['bankrupt']);
        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
        $this->assertTrue(strpos($results[1], $line) !== false);
    }

    /**
     * 測試使用者測試帳號開關
     */
    public function testSetTestOnAndOff()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertFalse($user->isTest());
        $lastModifiedAt = $user->getModifiedAt()->getTimeStamp();

        $user8 = $em->find('BBDurianBundle:User', 8);
        $user8->setHiddenTest(true);

        $em->flush();
        $em->clear();

        // 測試
        $client->request('PUT', '/api/user/2/test/1', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret']['id']);
        $this->assertTrue($ret['ret']['test']);
        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        // 測試重複設定測試帳號
        $client->request('PUT', '/api/user/2/test/1', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Can not set test on when user is test user', $ret['msg']);
        $this->assertEquals(150010179, $ret['code']);

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertTrue($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $detail = $em->find('BBDurianBundle:UserDetail', 2);
        $this->assertEquals('Test User', $detail->getNameReal());
        $user = $em->find('BBDurianBundle:User', 3);
        $this->assertTrue($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $detail = $em->find('BBDurianBundle:UserDetail', 3);
        $this->assertEquals('Test User', $detail->getNameReal());
        $user = $em->find('BBDurianBundle:User', 4);
        $this->assertTrue($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $detail = $em->find('BBDurianBundle:UserDetail', 4);
        $this->assertEquals('Test User', $detail->getNameReal());
        $user = $em->find('BBDurianBundle:User', 5);
        $this->assertTrue($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $detail = $em->find('BBDurianBundle:UserDetail', 5);
        $this->assertEquals('Test User', $detail->getNameReal());
        $user = $em->find('BBDurianBundle:User', 6);
        $this->assertTrue($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $detail = $em->find('BBDurianBundle:UserDetail', 6);
        $this->assertEquals('Test User', $detail->getNameReal());
        $user = $em->find('BBDurianBundle:User', 7);
        $this->assertTrue($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $detail = $em->find('BBDurianBundle:UserDetail', 7);
        $this->assertEquals('Test User', $detail->getNameReal());
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertTrue($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $this->assertEquals('Test User', $detail->getNameReal());
        $user = $em->find('BBDurianBundle:User', 51);
        $this->assertTrue($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $detail = $em->find('BBDurianBundle:UserDetail', 51);
        $this->assertEquals('Test User', $detail->getNameReal());

        // 檢查廳下層測試帳號數量是否更新,user51應列入計算
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(1, $totalTest->getTotalTest());

        //測試非下層的是否有被註記為測試帳號
        $user = $em->find('BBDurianBundle:User', 9);
        $this->assertFalse($user->isTest());
        $user = $em->find('BBDurianBundle:User', 10);
        $this->assertFalse($user->isTest());

        //測試取消測試帳號但上層為測試帳號
        $client->request('PUT', '/api/user/7/test/0', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Can not set test off when parent is test user', $ret['msg']);
        $this->assertEquals(150010056, $ret['code']);

        $em->clear();

        // 取消測試
        $client->request('PUT', '/api/user/2/test/0', array(), array(), $this->headerParam);

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertFalse($user->isTest());

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals('ok', $ret['result']);
        $this->assertFalse($ret['ret']['test']);
        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertFalse($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $user = $em->find('BBDurianBundle:User', 3);
        $this->assertFalse($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $user = $em->find('BBDurianBundle:User', 4);
        $this->assertFalse($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $user = $em->find('BBDurianBundle:User', 5);
        $this->assertFalse($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $user = $em->find('BBDurianBundle:User', 6);
        $this->assertFalse($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $user = $em->find('BBDurianBundle:User', 7);
        $this->assertFalse($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertFalse($user->isTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());

        // 檢查廳下層測試帳號數量是否更新
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(0, $totalTest->getTotalTest());

        //測試非下層的是否有被註記為測試帳號
        $user = $em->find('BBDurianBundle:User', 9);
        $this->assertFalse($user->isTest());
        $user = $em->find('BBDurianBundle:User', 10);
        $this->assertFalse($user->isTest());

        $em->clear();

        // 測試重複取消測試帳號
        $client->request('PUT', '/api/user/2/test/0', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Can not set test off when user is not test user', $ret['msg']);
        $this->assertEquals(150010180, $ret['code']);

        // 測試設定會員為測試帳號
        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $redis->hset('cash_total_balance_2_156', 'normal', 1235678);
        $redis->hset('cash_total_balance_2_156', 'test', 0);

        $user = $em->find('BBDurianBundle:User', 51);
        $user->getCash()->setBalance(123.5678);
        $em->flush();

        $client->request('PUT', '/api/user/51/test/1', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // 檢查廳下層測試帳號數量是否更新
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(1, $totalTest->getTotalTest());

        // 檢查Redis內會員總餘額是否更新
        $this->assertEquals(0, $redis->hget('cash_total_balance_2_156', 'normal'));
        $this->assertEquals(1235678, $redis->hget('cash_total_balance_2_156', 'test'));

        $em->clear();

        // 測試設定會員為非測試帳號
        $client->request('PUT', '/api/user/51/test/0', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // 檢查廳下層測試帳號數量是否更新
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(0, $totalTest->getTotalTest());

        // 檢查Redis內會員總餘額是否更新
        $this->assertEquals(1235678, $redis->hget('cash_total_balance_2_156', 'normal'));
        $this->assertEquals(0, $redis->hget('cash_total_balance_2_156', 'test'));

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
        $this->assertTrue(strpos($results[1], $line) !== false);
    }

    /**
     * 測試使用者測試帳號開關,同一廳下層會員的測試帳號超過限制數量
     */
    public function testSetTestOnAndOffOverTheLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $domain2 = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $domain2->setTotalTest(DomainConfig::MAX_TOTAL_TEST + 1);
        $em->flush();

        $dc = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $dc->setBlockTestUser(true);
        $emShare->flush();

        $output = $this->getResponse('PUT', '/api/user/2/test/1');

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The number of test exceeds limitation in the same domain', $output['msg']);
        $this->assertEquals(150010136, $output['code']);

        // 設定廳不阻擋測試帳號
        $config2 = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config2->setBlockTestUser(false);

        $emShare->flush();
        $emShare->clear();

        // 驗證可設定成測試帳號
        $output = $this->getResponse('PUT', '/api/user/2/test/1');
        $this->assertEquals('ok', $output['result']);

        // 設定廳阻擋測試帳號
        $config2 = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config2->setBlockTestUser(true);

        $emShare->flush();

        // 驗證可設定成非測試帳號
        $output = $this->getResponse('PUT', '/api/user/2/test/0');
        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試停啟用使用者
     */
    public function testEnableAndDisableUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 確認使用者原本是啟用
        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertTrue($user->isEnabled());
        $lastModifiedAt = $user->getModifiedAt()->getTimeStamp();

        $em->clear();

         // 停用使用者
        $client->request('PUT', '/api/user/2/disable', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認停用成功
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);
        $modifiedAt = new \DateTime($output['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        //測試下層是否一起停用
        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertFalse($user->isEnabled());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $user3 = $em->find('BBDurianBundle:User', 3);
        $this->assertFalse($user3->isEnabled());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user3->getModifiedAt()->getTimeStamp());
        $user4 = $em->find('BBDurianBundle:User', 4);
        $this->assertFalse($user4->isEnabled());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user4->getModifiedAt()->getTimeStamp());
        $user5 = $em->find('BBDurianBundle:User', 5);
        $this->assertFalse($user5->isEnabled());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user5->getModifiedAt()->getTimeStamp());
        $user6 = $em->find('BBDurianBundle:User', 6);
        $this->assertFalse($user6->isEnabled());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user6->getModifiedAt()->getTimeStamp());
        $user7 = $em->find('BBDurianBundle:User', 7);
        $this->assertFalse($user7->isEnabled());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user7->getModifiedAt()->getTimeStamp());
        $user8 = $em->find('BBDurianBundle:User', 8);
        $this->assertFalse($user8->isEnabled());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user8->getModifiedAt()->getTimeStamp());

        //測試非下層的是否有被停用
        $user9 = $em->find('BBDurianBundle:User', 9);
        $this->assertTrue($user9->isEnabled());
        $user10 = $em->find('BBDurianBundle:User', 10);
        $this->assertTrue($user10->isEnabled());

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $this->assertFalse($domainConfig->isEnabled());

        $em->clear();
        $emShare->clear();

        // 啟用使用者
        $client->request('PUT', '/api/user/2/enable', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認有無啟用成功
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertTrue($user->isEnabled());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        $user3 = $em->find('BBDurianBundle:User', 3);
        $this->assertFalse($user3->isEnabled());
        $user8 = $em->find('BBDurianBundle:User', 8);
        $this->assertFalse($user8->isEnabled());

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $this->assertTrue($domainConfig->isEnabled());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
        $this->assertTrue(strpos($results[1], $line) !== false);
    }

    /**
     * 停用使用者一次數量超過限制10000個
     */
    public function testDisableAtOnceOverTheLimit()
    {
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'clear'])
            ->getMock();

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($mockUser));

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['countEnabledChildOfUser'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockRepo->expects($this->any())
            ->method('countEnabledChildOfUser')
            ->will($this->returnValue(10001));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        // 停用使用者
        $client->request('PUT', '/api/user/7/disable', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Can not disable more than 10000 users', $output['msg']);
        $this->assertEquals('150010089', $output['code']);
    }

    /**
     * 測試啟用母帳號時自動啟用子帳號
     */
    public function testDisableAndEnableSubUser()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 確認使用者原本是啟用
        $parent = $em->find('BBDurianBundle:User', 7);
        $this->assertTrue($parent->isEnabled());

        $subUser = $em->find('BBDurianBundle:User', 51);
        $subUser->setSub(1);
        $em->flush();
        $em->clear();

        // 停用使用者
        $client->request('PUT', '/api/user/7/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認停用成功
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);

        //測試下層是否一起停用
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertFalse($user->isEnabled());
        $user = $em->find('BBDurianBundle:User', 51);
        $this->assertFalse($user->isEnabled());

        $em->clear();

        // 啟用使用者
        $client->request('PUT', '/api/user/7/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認有無啟用成功
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertFalse($user->isEnabled());
        $user = $em->find('BBDurianBundle:User', 51);
        $this->assertTrue($user->isEnabled());
    }

    /**
     * 測試停用子帳號
     */
    public function testDisableSubUser()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 51);
        $user->setSub(1);
        $em->persist($user);
        $em->flush();

        $client->request('PUT', '/api/user/51/disable');

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Sub user can not be disabled', $output['msg']);
        $this->assertTrue($user->isEnabled());
    }

    /**
     * 測試使用者設定取消租卡體系
     */
    public function testSetRentOnAndOff()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertFalse($user->isRent());
        $lastModifiedAt = $user->getModifiedAt()->getTimeStamp();

        $em->flush();
        $em->clear();

        // 測試啟用租卡體系
        $client->request('PUT', '/api/user/2/rent/1', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $user = $em->find('BBDurianBundle:User', 2);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($user->getId(), $ret['ret']['id']);
        $this->assertEquals($user->isRent(), $ret['ret']['rent']);
        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        //測試取消租卡體系
        $client->request('PUT', '/api/user/8/rent/0', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertFalse($ret['ret']['rent']);
        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
        $this->assertTrue(strpos($results[1], $line) !== false);

        $em->clear();

        //測試停用廳主租卡體系
        $client->request('PUT', '/api/user/2/rent/0');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $user2 = $em->find('BBDurianBundle:User', 2);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($user2->isRent(), $ret['ret']['rent']);
    }

    /**
     * 測試開啟重設密碼
     */
    public function testSetPasswordResetOn()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $lastModifiedAt = $user->getModifiedAt()->getTimeStamp();
        $em->clear();

        $client->request('PUT', '/api/user/8/password_reset/1', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $user = $em->find('BBDurianBundle:User', 8);
        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);
        $userId = $user->getId();

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($userId, $output['ret']['id']);
        $this->assertEquals($user->isPasswordReset(), $output['ret']['password_reset']);
        $this->assertTrue($userPassword->isReset());
        $modifiedAt = new \DateTime($output['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        $userPasswordModifiedAt = $userPassword->getModifiedAt()->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $userPasswordModifiedAt);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user', $logOp->getTableName());
        $this->assertEquals("@id:$userId", $logOp->getMajorKey());
        $this->assertEquals('@password_reset:false=>true', $logOp->getMessage());
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user_password', $logOp->getTableName());
        $this->assertEquals("@id:$userId", $logOp->getMajorKey());
        $this->assertEquals('@password_reset:false=>true', $logOp->getMessage());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試關閉重設密碼
     */
    public function testSetPasswordResetOff()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $userBefore = $em->find('BBDurianBundle:User', 8);
        $userBefore->setPasswordReset(true);
        $userPasswordBefore = $em->find('BBDurianBundle:UserPassword', 8);
        $userPasswordBefore->setReset(true);
        $lastModifiedAt = $userBefore->getModifiedAt()->getTimeStamp();
        $em->flush();
        $em->clear();

        $client->request('PUT', '/api/user/8/password_reset/0');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $userAfter = $em->find('BBDurianBundle:User', 8);
        $userPasswordAfter = $em->find('BBDurianBundle:UserPassword', 8);
        $userId = $userAfter->getId();

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($userId, $output['ret']['id']);
        $this->assertEquals($userAfter->isPasswordReset(), $output['ret']['password_reset']);
        $this->assertFalse($userPasswordAfter->isReset());
        $modifiedAt = new \DateTime($output['ret']['modified_at']);
        $modifiedAt = $modifiedAt->getTimeStamp();
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        $userPasswordModifiedAt = $userPasswordAfter->getModifiedAt()->getTimeStamp();
        $this->assertGreaterThanOrEqual($userPasswordModifiedAt, $modifiedAt);

        // 操作記錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user', $logOp->getTableName());
        $this->assertEquals("@id:$userId", $logOp->getMajorKey());
        $this->assertEquals('@password_reset:true=>false', $logOp->getMessage());
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user_password', $logOp->getTableName());
        $this->assertEquals("@id:$userId", $logOp->getMajorKey());
        $this->assertEquals('@password_reset:true=>false', $logOp->getMessage());
    }

    /**
     * 測試檢查密碼
     */
    public function testCheckWrongPassword()
    {
        $client = $this->createClient();

        // 測試密碼正確
        $parameters = ['password' => '123456'];

        $client->request('PUT', '/api/user/9/check_password', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('true', $output['ret']['isValid']);

        // 測試密碼錯誤
        $parameters = ['password' => '1122'];

        $client->request('PUT', '/api/user/9/check_password', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('false', $output['ret']['isValid']);

        // 檢查敏感資訊是否存在
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        // 驗證敏感資訊
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";

        $this->assertTrue(strpos($results[0], $line) !== false);
        $this->assertTrue(strpos($results[1], $line) !== false);
    }

    /**
     * 測試檢查輸入大寫的密碼
     */
    public function testCheckUpperPassword()
    {
        $client = $this->createClient();

        $parameters = ['password' => 'gaGAgaga'];

        $client->request(
            'PUT',
            '/api/user/10/check_password',
            $parameters,
            [],
            $this->headerParam
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('true', $output['ret']['isValid']);
    }

    /**
     * 測試檢查臨時密碼
     */
    public function testCheckPasswordWithOncePassword()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/9/once_password', ['operator' => 'angelabobi']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $oncePassword = $output['code'];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, strlen($oncePassword));

        // 測試原密碼仍檢查正確
        $parameters = ['password' => '123456'];

        $client->request('PUT', '/api/user/9/check_password', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('true', $output['ret']['isValid']);

        // 測試臨時密碼檢查正確
        $parameters = ['password' => $oncePassword];

        $client->request('PUT', '/api/user/9/check_password', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('true', $output['ret']['isValid']);
    }

    /**
     * 測試回傳密碼
     */
    public function testGetPassword()
    {
        $client = $this->createClient();

        // 把操作記錄header改成會驗證的entrance 3,並帶入上層的操作者ID
        $sensitiveData = str_replace('entrance=6&', 'entrance=3&', $this->headerParam);
        $sensitiveData = str_replace('operator_id=&', 'operator_id=9&', $sensitiveData);

        // 要求user id 為10 的密碼
        $client->request('GET', '/api/user/10/password', [], [], $sensitiveData);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('gagagaga', $ret['ret']);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $string = "sensitive-data:{$sensitiveData['HTTP_SENSITIVE_DATA']}";
        $this->assertTrue(strpos($results[0], $string) !== false);

    }

    /**
     * 測試回傳密碼帶入不允許的操作資訊
     */
    public function testGetPasswordWithNotAllowedOperationData()
    {
        $client = $this->createClient();
        $senLogPath = $this->getLogfilePath('sensitive.log');
        $senNotAllowedLogPath = $this->getLogfilePath('sensitive_not_allowed.log');

        // 把操作記錄header改成會驗證的entrance 3
        $sensitiveData = str_replace('entrance=6&', 'entrance=3&', $this->headerParam);

        // 要求user id 為10 的密碼不送操作資訊
        $client->request('GET', '/api/user/10/password');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150240001', $ret['code']);
        $this->assertEquals('The request not allowed without operation data in header', $ret['msg']);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($senLogPath));

        $string = "api/user/10/password sensitive-data: ";
        $this->assertTrue(strpos($results[0], $string) !== false);

        $results = explode(PHP_EOL, file_get_contents($senNotAllowedLogPath));

        $string = "api/user/10/password sensitive-data: result: Without operation data";
        $this->assertTrue(strpos($results[0], $string) !== false);

        // 要求user id 為10 的密碼操作資訊 entrance 未定義
        $headers = str_replace('entrance=3&', '', $sensitiveData);
        $client->request('GET', '/api/user/10/password', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150240002', $ret['code']);
        $this->assertEquals('The request not allowed when operation data not define entrance in header', $ret['msg']);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($senLogPath));

        $string = $headers['HTTP_SENSITIVE_DATA'];
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        $results = explode(PHP_EOL, file_get_contents($senNotAllowedLogPath));
        $string = "api/user/10/password sensitive-data:$string result: Not define entrance";
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        // 要求user id 為10 的密碼操作資訊 operator_id 未定義
        $headers = str_replace('operator_id=&', '', $sensitiveData);
        $client->request('GET', '/api/user/10/password', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150240003', $ret['code']);
        $this->assertEquals('The request not allowed when operation data not define operator_id in header', $ret['msg']);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($senLogPath));

        $string = $headers['HTTP_SENSITIVE_DATA'];
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        $results = explode(PHP_EOL, file_get_contents($senNotAllowedLogPath));
        $string = "api/user/10/password sensitive-data:$string result: Not define operator_id";
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        // 要求user id 為10 的密碼操作資訊 operator_id 未帶
        $headers = str_replace('operator_id=&', 'operator_id=&', $sensitiveData);
        $client->request('GET', '/api/user/10/password', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150240004', $ret['code']);
        $this->assertEquals('The request not allowed whitout operator_id in header', $ret['msg']);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($senLogPath));

        $string = $headers['HTTP_SENSITIVE_DATA'];
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        $results = explode(PHP_EOL, file_get_contents($senNotAllowedLogPath));
        $string = "api/user/10/password sensitive-data:$string result: operator_id is null";
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        // 要求user id 為10 的密碼操作資訊 operator_id 不存在
        $headers = str_replace('operator_id=&', 'operator_id=88&', $sensitiveData);
        $client->request('GET', '/api/user/10/password', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150240005', $ret['code']);
        $this->assertEquals('The request not allowed when operator_id is invalid', $ret['msg']);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($senLogPath));

        $string = $headers['HTTP_SENSITIVE_DATA'];
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        $results = explode(PHP_EOL, file_get_contents($senNotAllowedLogPath));
        $string = "api/user/10/password sensitive-data:$string result: operator_id 88 is invalid";
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        // 要求user id 為10 的密碼操作資訊 operator_id 非上層
        $headers = str_replace('operator_id=&', 'operator_id=8&', $sensitiveData);
        $client->request('GET', '/api/user/7/password', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150240006', $ret['code']);
        $this->assertEquals('The request not allowed when operator is not ancestor', $ret['msg']);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($senLogPath));

        $string = $headers['HTTP_SENSITIVE_DATA'];
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        $results = explode(PHP_EOL, file_get_contents($senNotAllowedLogPath));
        $string = "api/user/7/password sensitive-data:$string result: operator_id 8 test (domain 2 ) is not ancestor";
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        // 要求user id 為10 的密碼操作資訊 operator_id 不同廳 (跳例外會寫LOG)
        $headers = str_replace('operator_id=&', 'operator_id=10&', $sensitiveData);
        $client->request('GET', '/api/user/7/password', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150240007, $ret['code']);
        $this->assertEquals('The request not allowed when operator is not in same domain', $ret['msg']);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($senLogPath));

        $string = $headers['HTTP_SENSITIVE_DATA'];
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);

        $results = explode(PHP_EOL, file_get_contents($senNotAllowedLogPath));
        $string = "api/user/7/password sensitive-data:$string result: operator_id 10 test (domain 9 ) not in same domain 2";
        $this->assertTrue(strpos($results[count($results) - 2], $string) !== false);
    }

    /**
     * 測試取得單一使用者資訊
     */
    public function testGetOneUserInfo()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'fields' => [
                'parent_id',
                'parent',
                'all_parents',
                'alias',
                'created_at',
                'domain',
                'modified_at',
                'last_login',
                'last_login_ip',
                'last_bank',
                'role',
                'password_expire_at',
                'password_reset',
                'username',
                'enable',
                'block',
                'sub',
                'cash',
                'cash_fake',
                'credit',
                'outside',
                'sharelimit',
                'sharelimit_next',
                'oauth',
                'login_code',
                'hidden_test'
            ],
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/user/10', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 一般資訊
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret'][0]['id']);
        $this->assertEquals('9', $output['ret'][0]['all_parents'][0]);
        $this->assertEquals('9', $output['ret'][0]['parent_id']);
        $this->assertEquals('9', $output['ret'][0]['parent']);
        $this->assertEquals('9', $output['ret'][0]['domain']);
        $this->assertEquals('7', $output['ret'][0]['role']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertFalse($output['ret'][0]['block']);
        $this->assertFalse($output['ret'][0]['sub']);
        $this->assertFalse($output['ret'][0]['password_reset']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertNull($output['ret'][0]['last_login']);
        $this->assertEquals('192.168.1.1', $output['ret'][0]['last_login_ip']);
        $this->assertNull($output['ret'][0]['last_bank']);
        $this->assertEquals('ag', $output['ret'][0]['login_code']);
        $this->assertFalse($output['ret'][0]['hidden_test']);

        // 現金
        $this->assertNull($output['ret'][0]['cash']);

        // 假現金/快開額度
        $this->assertEquals('3', $output['ret'][0]['cash_fake']['id']);
        $this->assertEquals($output['ret'][0]['cash_fake']['balance'], '498');
        $this->assertEquals($output['ret'][0]['cash_fake']['pre_sub'], '0');
        $this->assertEquals($output['ret'][0]['cash_fake']['pre_add'], '0');
        $this->assertEquals('CNY', $output['ret'][0]['cash_fake']['currency']);
        $this->assertTrue($output['ret'][0]['cash_fake']['enable']);

        // 信用額度
        $this->assertEquals('5000', $output['ret'][0]['credit'][1]['line']);
        $this->assertEquals('5000.00', $output['ret'][0]['credit'][1]['balance']);
        $this->assertTrue($output['ret'][0]['credit'][1]['enable']);
        $this->assertEquals('3000', $output['ret'][0]['credit'][2]['line']);
        $this->assertEquals('3000.00', $output['ret'][0]['credit'][2]['balance']);
        $this->assertTrue($output['ret'][0]['credit'][2]['enable']);

        // 外接額度
        $this->assertFalse($output['ret'][0]['outside']);

        // 佔成
        $this->assertEquals('0', $output['ret'][0]['sharelimit'][1]['upper']);
        $this->assertEquals('0', $output['ret'][0]['sharelimit'][1]['lower']);
        $this->assertEquals('20', $output['ret'][0]['sharelimit'][1]['parent_upper']);
        $this->assertEquals('20', $output['ret'][0]['sharelimit'][1]['parent_lower']);

        // 預改佔成
        $this->assertEquals('0', $output['ret'][0]['sharelimit_next'][1]['upper']);
        $this->assertEquals('0', $output['ret'][0]['sharelimit_next'][1]['lower']);
        $this->assertEquals('20', $output['ret'][0]['sharelimit_next'][1]['parent_upper']);
        $this->assertEquals('20', $output['ret'][0]['sharelimit_next'][1]['parent_lower']);

        // oauth
        $this->assertEquals('1', $output['ret'][0]['oauth'][0]['vendor_id']);
        $this->assertEquals('123456', $output['ret'][0]['oauth'][0]['openid']);

        //附屬資訊
        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][0]['id']);

        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][0]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][0]['alias']);
        $this->assertEquals(1, count($output['sub_ret']['user']));
    }

    /**
     * 測試取得單一使用者，header verify_session帶1,並帶入session_id
     */
    public function testGetUserAndVerifySession()
    {
        $client = $this->createClient();

        $parameters = ['fields' => ['id']];

        $headers = $this->headerParam;
        $headers['HTTP_VERIFY_SESSION'] = '1';

        // 帶入存在的session id
        $output = $this->getResponse('POST', '/api/user/7/session');
        $sessionId = $output['ret']['session']['id'];
        $headers['HTTP_SESSION_ID'] = $sessionId;

        $client->request('GET', '/api/user/7', $parameters, [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 帶入不存在的session id
        $headers['HTTP_SESSION_ID'] = '123';

        $client->request('GET', '/api/user/7', $parameters, [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150330001, $output['code']);
        $this->assertEquals('Session not found', $output['msg']);
    }

    /**
     * 測試取得單一使用者，使用者沒有上層，sub_ret帶1，不會回傳附屬資訊
     */
    public function testGetUserWithSubRetButNoParentUser()
    {
        $client = $this->createClient();

        $parameters = ['sub_ret' => 1];

        $client->request('GET', '/api/user/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertArrayNotHasKey('sub_ret', $output);
    }

    /**
     * 測試取大量使用者的資訊
     */
    public function testGetMultipleUserInfoObject()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array('users' => array('3', '4', '8', '51'), 'sub_ret' => 1);
        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 一般資訊
        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals(2, $output['ret'][0]['parent']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertNull($output['ret'][0]['last_login']);
        $this->assertNull($output['ret'][0]['last_bank']);
        $this->assertEquals(0, $output['ret'][0]['err_num']);
        $this->assertEquals(7, $output['ret'][0]['role']);

        $this->assertEquals(4, $output['ret'][1]['id']);
        $this->assertEquals('wtester', $output['ret'][1]['username']);
        $this->assertEquals(0, $output['ret'][1]['err_num']);
        $this->assertFalse($output['ret'][1]['block']);
        $this->assertFalse($output['ret'][1]['password_reset']);
        $this->assertEquals(5, $output['ret'][1]['role']);

        $this->assertEquals(8, $output['ret'][2]['id']);
        $this->assertEquals('tester', $output['ret'][2]['username']);
        $this->assertEquals(2, $output['ret'][2]['err_num']);
        $this->assertFalse($output['ret'][2]['sub']);
        $this->assertTrue($output['ret'][2]['enable']);
        $this->assertEquals(1, $output['ret'][2]['role']);

        $this->assertEquals(51, $output['ret'][3]['id']);
        $this->assertEquals('oauthuser', $output['ret'][3]['username']);
        $this->assertFalse($output['ret'][3]['sub']);
        $this->assertTrue($output['ret'][3]['enable']);
        $this->assertEquals(1, $output['ret'][3]['role']);
        $this->assertEquals(1, $output['ret'][3]['oauth'][0]['vendor_id']);
        $this->assertEquals('2382158635', $output['ret'][3]['oauth'][0]['openid']);

        //現金
        $this->assertEquals(2, $output['ret'][0]['cash']['id']);
        $this->assertEquals(3, $output['ret'][0]['cash']['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['cash']['balance']);
        $this->assertEquals(0, $output['ret'][0]['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['cash']['pre_add']);
        $this->assertEquals(3, $output['ret'][1]['cash']['id']);
        $this->assertEquals(4, $output['ret'][1]['cash']['user_id']);
        $this->assertEquals(1000, $output['ret'][1]['cash']['balance']);
        $this->assertEquals(0, $output['ret'][1]['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret'][1]['cash']['pre_add']);
        $this->assertEquals(7, $output['ret'][2]['cash']['id']);
        $this->assertEquals(8, $output['ret'][2]['cash']['user_id']);
        $this->assertEquals(0, $output['ret'][2]['cash']['balance']);
        $this->assertEquals(0, $output['ret'][2]['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret'][2]['cash']['pre_add']);

        // 假現金
        $this->assertEquals('CNY', $output['ret'][0]['cash_fake']['currency']);
        $this->assertEquals(498, $output['ret'][1]['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret'][1]['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret'][1]['cash_fake']['pre_add']);
        $this->assertEquals(500, $output['ret'][2]['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret'][2]['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret'][2]['cash_fake']['pre_add']);

        // 額度
        $this->assertEquals(array(), $output['ret'][0]['credit']);
        $this->assertEquals(array(), $output['ret'][1]['credit']);
        $this->assertEquals('5000.00', $output['ret'][2]['credit'][1]['balance']);

        // 外接額度
        $this->assertFalse($output['ret'][0]['outside']);

        // 佔成
        $this->assertEquals('0', $output['ret'][0]['sharelimit'][1]['lower']);
        $this->assertEquals('90', $output['ret'][1]['sharelimit'][1]['upper']);
        $this->assertEquals(array(), $output['ret'][2]['sharelimit']);

        // 預改佔成
        $this->assertEquals('0', $output['ret'][0]['sharelimit_next'][1]['lower']);
        $this->assertEquals('0', $output['ret'][1]['sharelimit_next'][1]['parent_upper']);
        $this->assertEquals(array(), $output['ret'][2]['sharelimit_next']);

        // 租卡
        $this->assertEquals(2, $output['ret'][0]['card']['id']);
        $this->assertEquals(3, $output['ret'][0]['card']['user_id']);
        $this->assertEquals(0, $output['ret'][0]['card']['balance']);
        $this->assertNull($output['ret'][0]['enabled_card']);
        $this->assertEquals(3, $output['ret'][1]['card']['id']);
        $this->assertEquals(4, $output['ret'][1]['card']['user_id']);
        $this->assertEquals(0, $output['ret'][1]['card']['balance']);
        $this->assertEquals(0, $output['ret'][1]['card']['last_balance']);
        $this->assertNull($output['ret'][1]['enabled_card']);
        $this->assertEquals(7, $output['ret'][2]['card']['id']);
        $this->assertEquals(8, $output['ret'][2]['card']['user_id']);
        $this->assertEquals(0, $output['ret'][2]['card']['balance']);
        $this->assertNull($output['ret'][1]['enabled_card']);

        // user8 佔成體系
        $this->assertEquals(0, $output['ret'][2]['sharelimit_division'][1][0]);
        $this->assertEquals(20, $output['ret'][2]['sharelimit_division'][1][1]);
        $this->assertEquals(30, $output['ret'][2]['sharelimit_division'][1][2]);
        $this->assertEquals(20, $output['ret'][2]['sharelimit_division'][1][3]);
        $this->assertEquals(20, $output['ret'][2]['sharelimit_division'][1][4]);
        $this->assertEquals(10, $output['ret'][2]['sharelimit_division'][1][5]);
        $this->assertEquals(0, $output['ret'][2]['sharelimit_division'][1][6]);
        $this->assertEquals(0, $output['ret'][2]['sharelimit_division'][1][7]);

        $this->assertFalse(isset($output['ret'][2]['sharelimit_division'][2]));

        // user8 預改佔成體系
        $this->assertEquals(0, $output['ret'][2]['sharelimit_next_division'][1][0]);
        $this->assertEquals(30, $output['ret'][2]['sharelimit_next_division'][1][1]);
        $this->assertEquals(20, $output['ret'][2]['sharelimit_next_division'][1][2]);
        $this->assertEquals(10, $output['ret'][2]['sharelimit_next_division'][1][3]);
        $this->assertEquals(10, $output['ret'][2]['sharelimit_next_division'][1][4]);
        $this->assertEquals(0, $output['ret'][2]['sharelimit_next_division'][1][5]);
        $this->assertEquals(0, $output['ret'][2]['sharelimit_next_division'][1][6]);
        $this->assertEquals(30, $output['ret'][2]['sharelimit_next_division'][1][7]);

        $this->assertFalse(isset($output['ret'][2]['sharelimit_next_division'][3]));

        //附屬資訊
        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][0]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][0]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][0]['alias']);

        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][1]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][1]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][1]['alias']);

        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][3]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][3]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][3]['alias']);

        $this->assertEquals(6, count($output['sub_ret']['user']));
    }

    /**
     * 測試取得多使用者，但沒帶參數
     */
    public function testGetMultipleUserWithoutParameters()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/users');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得多使用者，但佔成錯誤
     */
    public function testGetMultipleUserButShareLimitError()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 5);

        $shareLimit = $user->getShareLimit(1);

        $shareLimit->setUpper(90)->setLower(90)
                  ->setParentUpper(90)->setParentLower(20);

        $em->flush();
        $em->clear();

        $parameters = array('users' => array('2', '5', '7'));
        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('error', $output['ret'][1]['sharelimit_division'][1]);
        $this->assertEquals('error', $output['ret'][2]['sharelimit_division'][1]);
        $this->assertEquals('Any child ParentUpper + Lower (min1) can not below parentBelowLower', $output['msg']);
        $this->assertEquals(150080018, $output['code']);
    }

    /**
     * 測試取大量使用者的資訊，並以陣列回傳
     * 只取得一般資訊，回傳是陣列或物件應以parameter中的fields決定。
     * fields中沒有任何關聯物件的話，就會以array回傳使用者基本資料
     */
    public function testGetMultipleUserInfoArray()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $parameters = array(
            'users' => array('2', '3', '4', '8'),
            'fields' => array(
                'id',
                'parent_id',
                'username',
                'domain',
                'alias',
                'sub',
                'enable',
                'block',
                'err_num',
                'created_at',
                'modified_at',
                'last_login',
                'last_bank',
                'currency',
                'size'
            ),
        );

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $user2 = $em->find('BBDurianBundle:User', 2);
        $user3 = $em->find('BBDurianBundle:User', 3);
        $user4 = $em->find('BBDurianBundle:User', 4);
        $user8 = $em->find('BBDurianBundle:User', 8);

        // 一般資訊
        $this->assertEquals('ok', $output['result']);

        $this->assertEquals($user2->getId(), $output['ret'][0]['id']);
        $this->assertNull($output['ret'][0]['parent_id']);

        $this->assertEquals($user3->getId(), $output['ret'][1]['id']);
        $this->assertEquals($user3->getParent()->getId(), $output['ret'][1]['parent_id']);
        $this->assertEquals($user3->getUsername(), $output['ret'][1]['username']);
        $this->assertEquals($user3->getDomain(), $output['ret'][1]['domain']);
        $this->assertEquals($user3->getSize(), $output['ret'][1]['size']);
        $this->assertEquals($user3->getErrNum(), $output['ret'][1]['err_num']);
        $this->assertNull($output['ret'][1]['last_login']);
        $this->assertNull($output['ret'][1]['last_bank']);
        $this->assertEquals('CNY', $output['ret'][1]['currency']);

        $this->assertEquals($user4->getId(), $output['ret'][2]['id']);
        $this->assertEquals($user4->getUsername(), $output['ret'][2]['username']);
        $this->assertEquals($user4->getAlias(), $output['ret'][2]['alias']);
        $this->assertEquals($user4->getErrNum(), $output['ret'][2]['err_num']);
        $this->assertFalse($output['ret'][2]['block']);
        $this->assertEquals('CNY', $output['ret'][2]['currency']);

        $this->assertEquals($user8->getId(), $output['ret'][3]['id']);
        $this->assertEquals($user8->getUsername(), $output['ret'][3]['username']);
        $this->assertEquals($user8->getErrNum(), $output['ret'][3]['err_num']);
        $this->assertFalse($output['ret'][3]['sub']);
        $this->assertTrue($output['ret'][3]['enable']);
        $this->assertEquals('CNY', $output['ret'][3]['currency']);
    }

    /**
     * 測試多使用者為空值
     */
    public function testGetMultipleUserWithEmptyValue()
    {
        $client = $this->createClient();

        $parameters = array('users' => array(''));
        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEmpty($output['ret']);
        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試取大量使用者的資訊
     */
    public function testGetMultipleUserInfoObjectV2()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'users' => [3, 8, 51],
            'sub_ret' => 1
        ];
        $client->request('GET', '/api/v2/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 一般資訊
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['parent']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(7, $output['ret'][0]['role']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);
        $this->assertEquals(0, $output['ret'][0]['err_num']);

        $this->assertEquals(8, $output['ret'][1]['id']);
        $this->assertEquals(7, $output['ret'][1]['parent']);
        $this->assertEquals(1, $output['ret'][1]['role']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals(2, $output['ret'][1]['err_num']);

        $this->assertEquals(51, $output['ret'][2]['id']);
        $this->assertEquals(7, $output['ret'][2]['parent']);
        $this->assertEquals(1, $output['ret'][2]['role']);
        $this->assertEquals('oauthuser', $output['ret'][2]['username']);
        $this->assertEquals(0, $output['ret'][2]['err_num']);
        $this->assertEquals(1, $output['ret'][2]['oauth'][0]['vendor_id']);
        $this->assertEquals('2382158635', $output['ret'][2]['oauth'][0]['openid']);

        // 現金
        $this->assertEquals(2, $output['ret'][0]['cash']['id']);
        $this->assertEquals(3, $output['ret'][0]['cash']['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['cash']['balance']);
        $this->assertEquals(0, $output['ret'][0]['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['cash']['pre_add']);
        $this->assertEquals(7, $output['ret'][1]['cash']['id']);
        $this->assertEquals(8, $output['ret'][1]['cash']['user_id']);
        $this->assertEquals(0, $output['ret'][1]['cash']['balance']);
        $this->assertEquals(0, $output['ret'][1]['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret'][1]['cash']['pre_add']);
        $this->assertEquals(9, $output['ret'][2]['cash']['id']);
        $this->assertEquals(51, $output['ret'][2]['cash']['user_id']);
        $this->assertEquals(0, $output['ret'][2]['cash']['balance']);
        $this->assertEquals(0, $output['ret'][2]['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret'][2]['cash']['pre_add']);

        // 假現金
        $this->assertEquals(5, $output['ret'][0]['cash_fake']['id']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['pre_add']);
        $this->assertEquals(2, $output['ret'][1]['cash_fake']['id']);
        $this->assertEquals(500, $output['ret'][1]['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret'][1]['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret'][1]['cash_fake']['pre_add']);
        $this->assertNull($output['ret'][2]['cash_fake']);

        // 信用
        $this->assertEquals([], $output['ret'][0]['credit']);
        $this->assertEquals(5000, $output['ret'][1]['credit'][1]['balance']);
        $this->assertEquals(3000, $output['ret'][1]['credit'][2]['balance']);
        $this->assertEquals([], $output['ret'][2]['credit']);

        // 外接額度
        $this->assertFalse($output['ret'][0]['outside']);

        // 佔成
        $this->assertEquals(100, $output['ret'][0]['sharelimit'][1]['upper']);
        $this->assertEquals([], $output['ret'][1]['sharelimit']);
        $this->assertEquals([], $output['ret'][2]['sharelimit']);

        // 預改佔成
        $this->assertEquals(90, $output['ret'][0]['sharelimit_next'][1]['upper']);
        $this->assertEquals([], $output['ret'][1]['sharelimit_next']);
        $this->assertEquals([], $output['ret'][2]['sharelimit_next']);

        // 租卡
        $this->assertEquals(2, $output['ret'][0]['card']['id']);
        $this->assertEquals(0, $output['ret'][0]['card']['balance']);
        $this->assertNull($output['ret'][0]['enabled_card']);
        $this->assertEquals(7, $output['ret'][1]['card']['id']);
        $this->assertEquals(0, $output['ret'][1]['card']['balance']);
        $this->assertNull($output['ret'][1]['enabled_card']);
        $this->assertNull($output['ret'][2]['card']);
        $this->assertNull($output['ret'][2]['enabled_card']);

        // user8 佔成體系
        $this->assertEquals(0, $output['ret'][1]['sharelimit_division'][1][0]);
        $this->assertEquals(20, $output['ret'][1]['sharelimit_division'][1][1]);
        $this->assertEquals(30, $output['ret'][1]['sharelimit_division'][1][2]);
        $this->assertEquals(20, $output['ret'][1]['sharelimit_division'][1][3]);
        $this->assertEquals(20, $output['ret'][1]['sharelimit_division'][1][4]);
        $this->assertEquals(10, $output['ret'][1]['sharelimit_division'][1][5]);
        $this->assertEquals(0, $output['ret'][1]['sharelimit_division'][1][6]);
        $this->assertEquals(0, $output['ret'][1]['sharelimit_division'][1][7]);

        // user8 預改佔成體系
        $this->assertEquals(0, $output['ret'][1]['sharelimit_next_division'][1][0]);
        $this->assertEquals(30, $output['ret'][1]['sharelimit_next_division'][1][1]);
        $this->assertEquals(20, $output['ret'][1]['sharelimit_next_division'][1][2]);
        $this->assertEquals(10, $output['ret'][1]['sharelimit_next_division'][1][3]);
        $this->assertEquals(10, $output['ret'][1]['sharelimit_next_division'][1][4]);
        $this->assertEquals(0, $output['ret'][1]['sharelimit_next_division'][1][5]);
        $this->assertEquals(0, $output['ret'][1]['sharelimit_next_division'][1][6]);
        $this->assertEquals(30, $output['ret'][1]['sharelimit_next_division'][1][7]);

        // 附屬資訊
        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][0]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][0]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][0]['alias']);

        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][1]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][1]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][1]['alias']);

        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][2]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][2]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][2]['alias']);

        $this->assertEquals(6, count($output['sub_ret']['user']));
    }

    /**
     * 測試取得多使用者，但沒帶參數
     */
    public function testGetMultipleUserWithoutParametersV2()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/v2/users');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得多使用者，但佔成錯誤
     */
    public function testGetMultipleUserButShareLimitErrorV2()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 5);
        $shareLimit = $user->getShareLimit(1);
        $shareLimit->setUpper(90)->setLower(90)->setParentUpper(90)->setParentLower(20);

        $em->flush();
        $em->clear();

        $parameters = ['users' => [2, 5, 7]];
        $client->request('GET', '/api/v2/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('error', $output['ret'][1]['sharelimit_division'][1]);
        $this->assertEquals('error', $output['ret'][2]['sharelimit_division'][1]);
        $this->assertEquals('Any child ParentUpper + Lower (min1) can not below parentBelowLower', $output['msg']);
        $this->assertEquals(150080018, $output['code']);
    }

    /**
     * 測試取大量使用者的資訊，並以陣列回傳
     * 只取得一般資訊，回傳是陣列或物件應以parameter中的fields決定。
     * fields中沒有任何關聯物件的話，就會以array回傳使用者基本資料
     */
    public function testGetMultipleUserInfoArrayV2()
    {
        $client = $this->createClient();
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'users' => [2, 3, 8],
            'fields' => [
                'id',
                'parent_id',
                'username',
                'domain',
                'alias',
                'sub',
                'enable',
                'size',
                'err_num',
                'created_at',
                'modified_at',
                'last_bank',
                'last_login',
                'currency',
                'role'
            ],
        ];
        $client->request('GET', '/api/v2/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 一般資訊
        $this->assertEquals('ok', $output['result']);

        $user2 = $em->find('BBDurianBundle:User', 2);
        $user3 = $em->find('BBDurianBundle:User', 3);
        $user8 = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals($user2->getId(), $output['ret'][0]['id']);
        $this->assertNull($output['ret'][0]['parent_id']);
        $this->assertEquals($user2->getUsername(), $output['ret'][0]['username']);
        $this->assertEquals($user2->getRole(), $output['ret'][0]['role']);
        $this->assertEquals($user2->getErrNum(), $output['ret'][0]['err_num']);

        $this->assertEquals($user3->getId(), $output['ret'][1]['id']);
        $this->assertEquals($user3->getParent()->getId(), $output['ret'][1]['parent_id']);
        $this->assertEquals($user3->getUsername(), $output['ret'][1]['username']);
        $this->assertEquals($user3->getRole(), $output['ret'][1]['role']);
        $this->assertEquals($user3->getErrNum(), $output['ret'][1]['err_num']);

        $this->assertEquals($user8->getId(), $output['ret'][2]['id']);
        $this->assertEquals($user8->getParent()->getId(), $output['ret'][2]['parent_id']);
        $this->assertEquals($user8->getUsername(), $output['ret'][2]['username']);
        $this->assertEquals($user8->getRole(), $output['ret'][2]['role']);
        $this->assertEquals($user8->getErrNum(), $output['ret'][2]['err_num']);
    }

    /**
     * 測試多使用者為空值
     */
    public function testGetMultipleUserWithEmptyValueV2()
    {
        $client = $this->createClient();

        $parameters = ['users' => ['']];
        $client->request('GET', '/api/v2/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEmpty($output['ret']);
        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試取得資訊不完整時要回傳null或空array
     */
    public function testGetUserInfoWithIncompleteDataV2()
    {
        $client = $this->createClient();

        $parameters = ['users' => [8]];
        $client->request('GET', '/api/v2/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['parent']);
        $this->assertNull($output['ret'][0]['last_bank']);
        $this->assertEquals([], $output['ret'][0]['sharelimit']);
        $this->assertEquals([], $output['ret'][0]['sharelimit_next']);
    }

    /**
     * 測試取得多個使用者，使用者沒有上層，sub_ret帶1，不會回傳附屬資訊
     */
    public function testGetUsersWithSubRetButNoParentUserV2()
    {
        $client = $this->createClient();

        $parameters = [
            'users' => [2],
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/v2/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertArrayNotHasKey('sub_ret', $output);
    }

    /**
     * 測試取得單一使用者，佔成Lower不得超過全部下層的Min1
     */
    public function testGetUserWithShareLimitLowerCanNotLargerThanMin1()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 5);

        $shareLimit = $user->getShareLimit(1);

        $shareLimit->setUpper(90)->setLower(90)->setParentUpper(90)->setParentLower(20);

        $em->flush();
        $em->clear();

        $client->request('GET', '/api/user/5');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080015, $output['code']);
        $this->assertEquals('Lower can not exceed any child ParentUpper + Lower (min1)', $output['msg']);
    }

    /**
     * 測試取得單一使用者，但佔成不存在
     */
    public function testGetUserButShareLimitNotExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 5);

        $mockDealer = $this->getMockBuilder('BB\DurianBundle\Share\Dealer')
            ->disableOriginalConstructor()
            ->setMethods(['toArray'])
            ->getMock();

        $mockDealer->expects($this->any())
            ->method('toArray')
            ->will($this->throwException(new ShareLimitNotExists($user, 1, FALSE)));

        $client->getContainer()->set('durian.share_dealer', $mockDealer);
        $client->request('GET', '/api/user/5');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080028, $output['code']);
        $this->assertEquals('User 5 has no sharelimit of group 1', $output['msg']);
    }

    /**
     * 測試取得資訊不完整時要回傳null或空array
     */
    public function testGetUserInfoWithIncompleteData()
    {
        $client = $this->createClient();

        $parameters = array('users' => array(9));
        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertNull($output['ret'][0]['parent']);
        $this->assertNull($output['ret'][0]['last_login']);

        $this->assertNull($output['ret'][0]['cash_fake']);

        $this->assertEquals(9, $output['ret'][0]['id']);
        $this->assertEquals(array(), $output['ret'][0]['all_parents']);
        $this->assertEquals(array(), $output['ret'][0]['credit']);
    }

    /**
     * 測試取得多個使用者，使用者沒有上層，sub_ret帶1，不會回傳附屬資訊
     */
    public function testGetUsersWithSubRetButNoParentUser()
    {
        $client = $this->createClient();

        $parameters = [
            'users' => [2],
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertArrayNotHasKey('sub_ret', $output);
    }

    /**
     * 測試取得多個使用者，但佔成不存在
     */
    public function testGetUsersWithShareLimitNotExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 2);

        $mockDealer = $this->getMockBuilder('BB\DurianBundle\Share\Dealer')
            ->disableOriginalConstructor()
            ->setMethods(['toArray'])
            ->getMock();

        $mockDealer->expects($this->any())
            ->method('toArray')
            ->will($this->throwException(new ShareLimitNotExists($user, 1, FALSE)));

        $client->getContainer()->set('durian.share_dealer', $mockDealer);
        $parameters = [
            'users' => [2],
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/users', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080028, $output['code']);
        $this->assertEquals('User 2 has no sharelimit of group 1', $output['msg']);
    }

    /**
     * 測試設定使用者一般屬性
     */
    public function testSetUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();

        $parameters = array(
            'alias'              => 'chosen123456789012345678912345',
            'currency'           => 'TWD',
            'password_expire_at' => '2011-12-12 11:11:11',
            'username'           => 'newtester',
            'last_bank'          => 1,
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance' => 300,
                'operator' => 'bbin'
            ),
            'credit' => array(
                1 => array('line' => 100),
                2 => array('line' => 1000)
            ),
        );

        $client->request('PUT', '/api/user/8', $parameters);

        // 操作紀錄檢查
        // 修改cash_fake的紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_fake", $logOperation->getTableName());
        $this->assertEquals("@user_id:8", $logOperation->getMajorKey());
        $this->assertEquals("@balance:500=>300", $logOperation->getMessage());

        // 修改credit的紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:8, @group_num:1", $logOperation->getMajorKey());
        $this->assertEquals("@line:5000=>22.3", $logOperation->getMessage());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:8, @group_num:2", $logOperation->getMajorKey());
        $this->assertEquals("@line:3000=>223", $logOperation->getMessage());

        // 修改user的紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 5);
        $arrLogOperation = explode(', ', $logOperation->getMessage());
        $this->assertEquals("user", $logOperation->getTableName());
        $this->assertEquals("@id:8", $logOperation->getMajorKey());
        $this->assertEquals("@username:tester=>newtester", $arrLogOperation[0]);
        $this->assertEquals("@alias:tester=>chosen123456789012345678912345", $arrLogOperation[1]);
        $this->assertEquals("@currency:CNY=>TWD", $arrLogOperation[3]);
        $this->assertEquals("@last_bank:=>1", $arrLogOperation[4]);

        // 檢查redis對應表
        $redis = $this->getContainer()->get('snc_redis.map');
        $this->assertEquals('newtester', $redis->get('user:{1}:8:username'));

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('newtester', $output['ret']['username']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('chosen123456789012345678912345', $output['ret']['alias']);
        $this->assertFalse($output['ret']['sub']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertFalse($output['ret']['block']);
        $this->assertFalse($output['ret']['bankrupt']);
        $this->assertFalse($output['ret']['test']);
        $this->assertFalse($output['ret']['hidden_test']);
        $this->assertEquals(0, $output['ret']['size']);
        $this->assertEquals(2, $output['ret']['err_num']);
        $this->assertEquals('TWD', $output['ret']['currency']);
        $this->assertEquals('2011-12-12T11:11:11+0800', $output['ret']['password_expire_at']);
        $this->assertFalse($output['ret']['password_reset']);
        $this->assertEquals(1, $output['ret']['last_bank']);
        $this->assertEquals(1, $output['ret']['role']);
        $this->assertEquals(300, $output['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_add']);
        $this->assertEquals(5, $output['ret']['credit'][1]['id']);
        $this->assertEquals(8, $output['ret']['credit'][1]['user_id']);
        $this->assertEquals(1, $output['ret']['credit'][1]['group']);
        $this->assertEquals(1, $output['ret']['credit'][1]['enable']);
        $this->assertEquals(98, $output['ret']['credit'][1]['line']);
        $this->assertEquals(98.65, $output['ret']['credit'][1]['balance']);
        $this->assertEquals(6, $output['ret']['credit'][2]['id']);
        $this->assertEquals(8, $output['ret']['credit'][2]['user_id']);
        $this->assertEquals(2, $output['ret']['credit'][2]['group']);
        $this->assertEquals(1, $output['ret']['credit'][2]['enable']);
        $this->assertEquals(1000, $output['ret']['credit'][2]['line']);
        $this->assertEquals(1000.00, $output['ret']['credit'][2]['balance']);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $cmdParams = [
            '--credit' => 1,
            '--entry' => 1,
            '--period' => 1
        ];
        $out = $this->runCommand('durian:sync-credit', $cmdParams);

        // 一般屬性檢查
        $em->clear();
        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals($user->getLastBank(), $output['ret']['last_bank']);
        $this->assertEquals('chosen123456789012345678912345', $user->getAlias());
        $this->assertEquals(7, $user->getParent()->getId());

        /* 檢查modifiedAt欄位是否有更新, 由於無法得知正確的最後修改時間,
         * 因此只能大概估算修改時間
         */
        $modifiedAt = $user->getModifiedAt()->getTimestamp();
        $now = new \DateTime('now');
        $now = $now->getTimestamp();
        $this->assertLessThanOrEqual(300, abs($now - $modifiedAt));

        $this->assertEquals(new \DateTime('2011-12-12 11:11:11'), $user->getPasswordExpireAt());
        $this->assertEquals('newtester', $user->getUsername());
        $this->assertEquals('123456', $user->getPassword());
        $this->assertEquals(901, $user->getCurrency());

        // cashFake資料檢查
        $this->assertArrayHasKey('cash_fake', $output['ret']);
        $cashFake = $user->getCashFake();
        $this->assertEquals($cashFake->getBalance(), $output['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $output['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $output['ret']['cash_fake']['pre_add']);

        // 檢查有無寫入cash_fake_entry
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');
        $cashFakeEntry = $repo->findOneBy(
            array(
                'cashFakeId' => 2,
                'opcode' => 1003,
                'id' => 1002
            )
        );

        $pCashFakeEntry =$repo->findOneBy(
            array(
                'cashFakeId' => 1,
                'opcode' => 1003,
                'id' => 1001
            )
        );

        $this->assertEquals(300, $cashFakeEntry->getBalance());
        $this->assertEquals(-200, $cashFakeEntry->getAmount());
        $this->assertEquals(700, $pCashFakeEntry->getBalance());
        $this->assertEquals(200, $pCashFakeEntry->getAmount());

        // 檢查有無寫入cash_fake_transfer_entry
        $cfteRepo = $em->getRepository('BBDurianBundle:CashFakeTransferEntry');

        $transferEntry = $cfteRepo->findOneBy([
            'userId' => 8,
            'opcode' => 1003,
            'id' => 1002
        ]);

        $pTransferEntry = $cfteRepo->findOneBy([
            'userId' => 7,
            'opcode' => 1003,
            'id' => 1001
        ]);

        $this->assertEquals(300, $transferEntry->getBalance());
        $this->assertEquals(-200, $transferEntry->getAmount());
        $this->assertEquals(700, $pTransferEntry->getBalance());
        $this->assertEquals(200, $pTransferEntry->getAmount());

        // 檢查有無寫入cash_fake_entry_operator
        $cfeoRepo = $em->getRepository('BBDurianBundle:CashFakeEntryOperator');

        $entryOperator = $cfeoRepo->findOneBy(array('entryId' => 1002));
        $pEntryOperator = $cfeoRepo->findOneBy(array('entryId' => 1001));

        $this->assertEquals('ztester', $entryOperator->getWhom());
        $this->assertEquals(2, $entryOperator->getLevel());
        $this->assertEquals(1, $entryOperator->getTransferOut());
        $this->assertEquals('bbin', $entryOperator->getUsername());
        $this->assertEquals('newtester', $pEntryOperator->getWhom());
        $this->assertEquals(1, $pEntryOperator->getLevel());
        $this->assertEquals(0, $pEntryOperator->getTransferOut());
        $this->assertEquals('bbin', $pEntryOperator->getUsername());

        // credit資料檢查，輸入台幣轉回人民幣
        $this->assertArrayHasKey('credit', $output['ret']);
        $credit = $user->getCredit(2);
        $this->assertEquals(223, $credit->getLine());
    }

    /**
     * 測試設定使用者，但未帶參數
     */
    public function testSetUserWithoutParams()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/user/8', []);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['id']);
        $this->assertEquals('tester', $output['ret']['username']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('tester', $output['ret']['alias']);
        $this->assertFalse($output['ret']['sub']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertFalse($output['ret']['block']);
        $this->assertFalse($output['ret']['bankrupt']);
        $this->assertFalse($output['ret']['test']);
        $this->assertFalse($output['ret']['hidden_test']);
        $this->assertEquals(0, $output['ret']['size']);
        $this->assertEquals(2, $output['ret']['err_num']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals(1, $output['ret']['role']);

        $logOperation = $em->getRepository('BBDurianBundle:LogOperation')->findAll();
        $this->assertEmpty($logOperation);
    }

    /**
     * 測試編輯使用者帶入使用者沒有的信用額度群組
     */
    public function testEditUserWithNotUserCreditGroup()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'credit' => [
                3 => ['line' => 100]
            ]
        ];

        $client->request('PUT', '/api/user/8', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['id']);

        // 無操作紀錄
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試設定使用者一般屬性，但username含有空白
     */
    public function testSetUserAndUsernameContainsBlanks()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $client = $this->createClient();

        $parameters = [
            'username' => ' newtester ',
            'modified_at' => '2011-12-12 11:11:11'
        ];

        $client->request('PUT', '/api/user/8', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('newtester', $output['ret']['username']);
        $this->assertEquals('newtester', $user->getUsername());
    }

    /**
     * 測試設定使用者變更密碼
     */
    public function testSetUserPassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $lastModifyAt = $user->getModifiedAt()->format(\DateTime::ISO8601);

        $parameters = ['password' => 'ok127799'];

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('7', $output['ret']['id']);
        $this->assertGreaterThanOrEqual($lastModifyAt, $output['ret']['modified_at']);

        // 檢查操作紀錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user', $logOp->getTableName());
        $this->assertEquals('@id:7', $logOp->getMajorKey());
        $this->assertEquals('@password:updated', $logOp->getMessage());
    }

    /**
     * 測試設定使用者修改幣別但幣別錯誤
     */
    public function testSetUserWithExchangeReconvButCurrencyError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $user->setCurrency('test');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($user);

        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($em->find('BBDurianBundle:UserPassword', 7));

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = ['credit' => [1 => ['line' => 100]]];
        $client->request('PUT', '/api/user/7', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010106, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);
    }

    /**
     * 測試設定使用者佔成屬性
     */
    public function testSetUserShare()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'sharelimit' => [
                1 => [
                    'upper'        => 100,
                    'lower'        => 0,
                    'parent_upper' => 0,
                    'parent_lower' => 0
                ],
                3 => [
                    'upper'        => 100,
                    'lower'        => 0,
                    'parent_upper' => 0,
                    'parent_lower' => 0
                ]
            ],
            'sharelimit_next' => [
                1 => [
                    'upper'        => 100,
                    'lower'        => 0,
                    'parent_upper' => 0,
                    'parent_lower' => 0
                ],
                3 => [
                    'upper'        => 100,
                    'lower'        => 0,
                    'parent_upper' => 0,
                    'parent_lower' => 0
                ]
            ]
        ];

        $client->request('PUT', '/api/user/2', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BBDurianBundle:User', 2);

        // shareLimit資料檢查
        $this->assertArrayHasKey('sharelimit', $output['ret']);
        $share = $user->getShareLimit(1);
        $this->assertEquals($share->getUpper(), $output['ret']['sharelimit'][1]['upper']);
        $share = $user->getShareLimit(3);
        $this->assertEquals($share->getUpper(), $output['ret']['sharelimit'][3]['upper']);

        $this->assertArrayHasKey('sharelimit_next', $output['ret']);
        $shareNext = $user->getShareLimitNext(1);
        $this->assertEquals(
            $shareNext->getParentLower(),
            $output['ret']['sharelimit_next'][1]['parent_lower']
        );
        $shareNext = $user->getShareLimitNext(3);
        $this->assertEquals(
            $shareNext->getParentLower(),
            $output['ret']['sharelimit_next'][3]['parent_lower']
        );

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $string = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $string) !== false);

        // 修改share_limit的紀錄
        $logShare = $emShare->find('BBDurianBundle:LogOperation', 1);
        $shareLimitRecord = "@parent_upper:100=>0";

        $this->assertEquals("share_limit", $logShare->getTableName());
        $this->assertEquals("@user_id:2, @group_num:1", $logShare->getMajorKey());
        $this->assertEquals($shareLimitRecord, $logShare->getMessage());

        $logShare = $emShare->find('BBDurianBundle:LogOperation', 2);
        $shareLimitRecord = "@parent_upper:100=>0";

        $this->assertEquals("share_limit", $logShare->getTableName());
        $this->assertEquals("@user_id:2, @group_num:3", $logShare->getMajorKey());
        $this->assertEquals($shareLimitRecord, $logShare->getMessage());

        // 修改share_limit_next的紀錄
        $logShareNext = $emShare->find('BBDurianBundle:LogOperation', 3);
        $shareLimitNextRecord = "@parent_upper:100=>0";

        $this->assertEquals("share_limit_next", $logShareNext->getTableName());
        $this->assertEquals("@user_id:2, @group_num:1", $logShareNext->getMajorKey());
        $this->assertEquals($shareLimitNextRecord, $logShareNext->getMessage());

        $logShareNext = $emShare->find('BBDurianBundle:LogOperation', 4);
        $shareLimitNextRecord = "@parent_upper:100=>0";

        $this->assertEquals("share_limit_next", $logShareNext->getTableName());
        $this->assertEquals("@user_id:2, @group_num:3", $logShareNext->getMajorKey());
        $this->assertEquals($shareLimitNextRecord, $logShareNext->getMessage());
    }

    /**
     * 測試設定使用者修改佔成但使用者沒有佔成
     */
    public function testSetUserWithEditShareLimitButNotHaveShareLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $shareLimitNext = $em->find('BBDurianBundle:ShareLimitNext', $user);
        $em->remove($shareLimitNext);
        $em->flush();

        $parameters = [
            'sharelimit' => [
                1 => [
                    'upper' => 15,
                    'lower' => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                ]
            ],
            'sharelimit_next' => [
                1 => [
                    'upper' => 15,
                    'lower' => 15,
                    'parent_upper' => 40,
                    'parent_lower' => 5
                ]
            ]
        ];
        $client->request('PUT', '/api/user/7', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080029, $output['code']);
        $this->assertEquals('User 7 has no sharelimit_next of group 1', $output['msg']);
    }

    /**
     * 測試設定使用者修改佔成但使用者沒有佔成
     */
    public function testSetUserWithEditShareLimitNextButNotHaveShareLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $shareLimitNext = $em->find('BBDurianBundle:ShareLimitNext', $user);
        $em->remove($shareLimitNext);
        $em->flush();

        $parameters = [
            'sharelimit_next' => [
                1 => [
                    'upper' => 15,
                    'lower' => 15,
                    'parent_upper' => 40,
                    'parent_lower' => 5
                ]
            ]
        ];
        $client->request('PUT', '/api/user/7', $parameters, [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080029, $output['code']);
        $this->assertEquals('User 7 has no sharelimit_next of group 1', $output['msg']);
    }

    /**
     * 測試修改使用者失敗後上層金錢回溯皆正確
     */
    public function testSetUserFailedAndMoneyRollBackCorrectly()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //修改前資料
        $userParentBefore = $em->find('BBDurianBundle:User', 6);

        $cashFakeParent = new CashFake($userParentBefore, 156);
        $cashFakeParent->setBalance(500);
        $em->persist($cashFakeParent);
        $em->flush();

        $creditParentOrigin1 = $userParentBefore->getCredit(1)->getTotalLine();
        $creditParentOrigin2 = $userParentBefore->getCredit(2)->getTotalLine();
        $cashFakeParentOrigin = $cashFakeParent->getBalance();

        $userBefore = $em->find('BBDurianBundle:User', 7);
        $creditOrigin1 = $userBefore->getCredit(1)->getTotalLine();
        $creditOrigin2 = $userBefore->getCredit(2)->getTotalLine();
        $cashFakeOrigin = $userBefore->getCashFake()->getBalance();

        $em->clear();

        $parameters = array(
            'cash_fake' => array(
                'currency' => 'CNY',
                'balance'  => 200
            ),
            'cash' => array('currency' => 'CNY'),
            'credit' => array(
                1 => array('line' => 100),
                2 => array('line' => 1000)
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);

        // 測試是否有寫操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $cmdParams = [
            '--credit' => 1,
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        //修改失敗後上層及自己的資料應該與修改前相符
        $userParent = $em->find('BBDurianBundle:User', 6);
        $this->assertEquals($creditParentOrigin1, $userParent->getCredit(1)->getTotalLine());
        $this->assertEquals($creditParentOrigin2, $userParent->getCredit(2)->getTotalLine());
        $this->assertEquals($cashFakeParentOrigin, $userParent->getCashFake()->getBalance());

        // 自己的資料
        $user = $em->find('BBDurianBundle:User', 7);
        $this->assertEquals($creditOrigin1, $user->getCredit(1)->getTotalLine());
        $this->assertEquals($creditOrigin2, $user->getCredit(2)->getTotalLine());
        $this->assertEquals($cashFakeOrigin, $user->getCashFake()->getBalance());
    }

    /**
     * 測試修改使用者，但額度超過範圍最大值
     */
    public function testSetUesrWithLineExceedsTheMax()
    {
        $client = $this->createClient();

        $params = [
            'credit' => [
                '1' => ['line' => Credit::LINE_MAX + 1]
            ]
        ];
        $client->request('PUT', '/api/user/7', $params, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150010139, $out['code']);
        $this->assertEquals('Oversize line given which exceeds the MAX', $out['msg']);
    }

    /**
     * 測試修改使用者，但額度不是整數
     */
    public function testSetUesrWithLineNotInteger()
    {
        $client = $this->createClient();

        $params = ['credit' => [1 => ['line' => 'Integer']]];
        $client->request('PUT', '/api/user/7', $params, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010102, $output['code']);
        $this->assertEquals('Invalid line given', $output['msg']);
    }

    /**
     * 測試修改使用者時但寫入錯誤
     */
    public function testSetUesrWithFlushFailed()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception()));

        $mockCo = $this->getMockBuilder('BB\DurianBundle\Credit')
            ->disableOriginalConstructor()
            ->setMethods(['setLine'])
            ->getMock();

        $mockCo->expects($this->any())
            ->method('setLine')
            ->willReturn(['id' => 5]);

        $client->getContainer()->set('durian.credit_op', $mockCo);
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = ['credit' => [1 => ['line' => 100]]];

        $client->request('PUT', '/api/user/8', $parameters);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(8, $out['code']);
        $this->assertEquals('Undefined index: line', $out['msg']);
    }

    /**
     * 測試修改使用者時但佔成不存在狀況
     */
    public function testSetUesrWithShareLimitNotExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 7);
        $shareLimit = $em->find('BBDurianBundle:ShareLimit', $user);
        $em->remove($shareLimit);
        $em->flush();

        $parameters = [
            'sharelimit' => [
                1 => [
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                ]
            ],
        ];

        $client->request('PUT', '/api/user/7', $parameters);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150080028, $out['code']);
        $this->assertEquals('User 7 has no sharelimit of group 1', $out['msg']);
    }

    /**
     * 測試修改使用者時，信用額度超過上層，會恢復額度
     */
    public function testSetUesrWithNotEnoughLineToBeWithdraw()
    {
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $params = [
            'credit' => [
                '1' => ['line' => 300000]
            ]
        ];
        $client->request('PUT', '/api/user/7', $params, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150060041, $out['code']);
        $this->assertEquals('TotalLine is greater than parent credit', $out['msg']);

        // 檢查資料庫
        $pCredit = $em->find('BBDurianBundle:Credit', 1);
        $this->assertEquals(6, $pCredit->getUser()->getId());
        $this->assertEquals(15000, $pCredit->getLine());
        $this->assertEquals(10000, $pCredit->getTotalLine());

        $credit = $em->find('BBDurianBundle:Credit', 3);
        $this->assertEquals(7, $credit->getUser()->getId());
        $this->assertEquals(10000, $credit->getLine());
        $this->assertEquals(5000, $credit->getTotalLine());

        // 檢查 Redis
        $pKey = 'credit_6_1';
        $key = 'credit_7_1';

        $pRedisCredit = $redisWallet2->hgetall($pKey);
        $redisCredit = $redisWallet3->hgetall($key);

        $this->assertEquals(1, $pRedisCredit['id']);
        $this->assertEquals(15000, $pRedisCredit['line']);
        $this->assertEquals(10000, $pRedisCredit['total_line']);

        $this->assertEquals(3, $redisCredit['id']);
        $this->assertEquals(10000, $redisCredit['line']);
        $this->assertEquals(5000, $redisCredit['total_line']);
    }

    /**
     * 測試設定使用者屬性時帶入錯誤的參數
     */
    public function testSetUserwithIllegalParameters()
    {
        $client = $this->createClient();

        //測試帶入錯誤的last_bank
        $parameters = array('last_bank' => 5);

        $client->request('PUT', '/api/user/10', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010110, $output['code']);
    }

    /**
     * 測試在佔成更新期間設定使用者佔成
     */
    public function testSetUserDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $parameters = array(
            'parent' => 6,
            'sharelimit' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Cannot perform this during updating sharelimit', $output['msg']);
    }

    /**
     * 測試在太久沒跑佔成更新的狀況下設定使用者一般屬性
     */
    public function testSetUserWithoutUpdatingForTooLongTime()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::FINISHED, '2011-10-10 11:59:00');

        $parameters = array(
            'parent' => 6,
            'sharelimit' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 15,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Cannot perform this due to updating sharelimit is not performed for too long time',
            $output['msg']
        );
    }

    /**
     * 測試設定最後一層的佔成
     */
    public function testSetUserWithOutFlagLastShare()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'sharelimit' => array(
                1 => array(
                    'upper'        => 0,
                    'lower'        => 0,
                    'parent_upper' => 10,
                    'parent_lower' => 10
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // shareLimit資料檢查
        $user = $em->find('BBDurianBundle:User', 7);
        $share = $user->getShareLimit(1);
        $this->assertEquals(10, $share->getParentUpper());
        $this->assertEquals(10, $share->getParentLower());
        $this->assertEquals(0, $share->getUpper());
        $this->assertEquals(0, $share->getLower());
        $this->assertEquals(200, $share->getMin1());
        $this->assertEquals(0, $share->getMax1());
        $this->assertEquals(0, $share->getMax2());
    }

    /**
     * 測試修改密碼，使用者密碼表一併更改
     */
    public function testSetUserPasswordSynchronizedEncryption()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //確認使用者userPassword已建立
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);
        $this->assertNotEmpty($userPassword);
        $lastModifiedAt = $userPassword->getModifiedAt();
        $lastExpireAt = $userPassword->getExpireAt();

        $em->clear();

        //測試修改使用者密碼
        $parameters = ['password' => '45677a'];

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['id']);

        //檢查使用者密碼同步修改
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);
        $this->assertTrue(password_verify('45677a', $userPassword->getHash()));
        $this->assertGreaterThan($lastModifiedAt, $userPassword->getModifiedAt());
        $this->assertGreaterThan($lastExpireAt, $userPassword->getExpireAt());

        //確認修改使用者密碼操作紀錄
        $logop = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_password', $logop->getTableName());
        $this->assertEquals('@user_id:7', $logop->getMajorKey());
        $this->assertEquals('@hash:updated', $logop->getMessage());
    }

    /**
     * 測試修改使用者密碼時效
     */
    public function testSetUserTimeStampOfPassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $lastModifiedAt = $user->getModifiedAt();

        //確認使用者userPassword已建立
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);
        $this->assertNotEmpty($userPassword);

        $em->clear();

        //測試修改密碼有效時間
        $parameters = ['password_expire_at' => '2055-12-12 12:22:33'];

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $modifiedAt = new \DateTime($output['ret']['modified_at']);
        $expireAt = new \DateTime($output['ret']['password_expire_at']);
        $timestamp = new \Datetime('2055-12-12 12:22:33');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['id']);
        $this->assertGreaterThan($lastModifiedAt, $modifiedAt);
        $this->assertEquals($timestamp, $expireAt);

        //檢查密碼時效是否同步修改
        $userPassword = $em->find('BBDurianBundle:UserPassword', 7);
        $this->assertEquals($modifiedAt, $userPassword->getModifiedAt());
        $this->assertEquals($timestamp, $userPassword->getExpireAt());

        //確認修改密碼時效操作紀錄
        $logop = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_password', $logop->getTableName());
        $this->assertEquals('@user_id:7', $logop->getMajorKey());
        $this->assertContains('@password_expire_at:', $logop->getMessage());
        $this->assertContains('2055-12-12 12:22:33', $logop->getMessage());
    }

    /**
     * 測試修改使用者密碼時發生同分秒重覆新增使用者密碼的情況
     */
    public function testSetUserWithDuplicatedEntryOfUserPassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 10);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->onConsecutiveCalls($user, null));

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception("Duplicate entry '10' for key 'PRIMARY'", 150010099, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/user/10');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010099, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試由帳號取得體系資料
     */
    public function testGetHierarchy()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 10);

        // make domain:102 has the same username
        $twins = new User();
        $now   = new \Datetime('now');
        $twins->setId(11)
              ->setParent($parent)
              ->setUsername('tester')
              ->setDomain('102')
              ->setAlias('tester')
              ->setPassword('')
              ->setLastLogin($now);

        $em->persist($twins);
        $em->flush();

        $parameters = [
            'username' => 'tester',
            'hidden_test' => 0
        ];

        $client->request('GET', '/api/user/hierarchy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // domain 101 hierarchy
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('tester', $output['ret'][0][0]['username']);
        $this->assertEquals('ztester', $output['ret'][0][1]['alias']);
        $this->assertEquals('6', $output['ret'][0][2]['id']);
        $this->assertTrue($output['ret'][0][3]['enable']);
        $this->assertFalse($output['ret'][0][4]['block']);
        $this->assertFalse($output['ret'][0][5]['sub']);
        $this->assertFalse($output['ret'][0][5]['password_reset']);
        $this->assertFalse($output['ret'][0][5]['hidden_test']);
        $this->assertNull($output['ret'][0][6]['last_login']);

        // domain 102 hierarchy
        $this->assertEquals('tester', $output['ret'][1][0]['username']);
        $this->assertEquals('gaga', $output['ret'][1][1]['username']);
        $this->assertEquals('9', $output['ret'][1][2]['id']);
        $this->assertEquals($now->format(\DateTime::ISO8601), $output['ret'][1][0]['last_login']);
    }

    /**
     * 測試由帳號取得體系資料(模糊搜尋並限制查詢筆數)
     */
    public function testGetHierarchyWithFuzzySearchRecordLimit()
    {
        $client = $this->createClient();

        $parameters = array(
            'domain'       => 2,
            'username'     => '%test%',
            'first_result' => 2,
            'max_results'  => 3
        );

        $client->request('GET', '/api/user/hierarchy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('ytester', $output['ret'][0][0]['username']);
        $this->assertEquals('xtester', $output['ret'][1][0]['username']);
        $this->assertEquals('wtester', $output['ret'][2][0]['username']);

        $this->assertEquals(5, count($output['ret'][0]));
        $this->assertEquals(4, count($output['ret'][1]));
        $this->assertEquals(3, count($output['ret'][2]));
    }

    /**
     * 測試由帳號取得體系資料，但username含有空白(模糊搜尋並限制查詢筆數)
     */
    public function testGetHierarchyWithFuzzySearchRecordLimitAndUsernameContainsBlanks()
    {
        $client = $this->createClient();

        $parameters = [
            'domain'       => 2,
            'username'     => ' %test% ',
            'first_result' => 2,
            'max_results'  => 3
        ];

        $client->request('GET', '/api/user/hierarchy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('ytester', $output['ret'][0][0]['username']);
        $this->assertEquals('xtester', $output['ret'][1][0]['username']);
        $this->assertEquals('wtester', $output['ret'][2][0]['username']);

        $this->assertEquals(5, count($output['ret'][0]));
        $this->assertEquals(4, count($output['ret'][1]));
        $this->assertEquals(3, count($output['ret'][2]));
    }

    /**
     * 測試由帳號取得體系資料(不傳參數)
     */
    public function testGetHierarchyWithOutParameter()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/hierarchy');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']);
    }

    /**
     * 測試由帳號取得體系資料(帳號不存在)
     */
    public function testGetHierarchyWhenUsernameNotExist()
    {
        $client = $this->createClient();

        $parameters = array('username' => "anything' OR 'x'='x");
        $client->request('GET', '/api/user/hierarchy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']);
    }

    /**
     * 測試由帳號取得體系資料
     */
    public function testGetHierarchyV2()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 10);

        // make domain:102 has the same username
        $twins = new User();
        $now   = new \Datetime('now');
        $twins->setId(11)
            ->setParent($parent)
            ->setUsername('tester')
            ->setDomain(102)
            ->setAlias('tester')
            ->setPassword('')
            ->setLastLogin($now);

        $em->persist($twins);
        $em->flush();

        $parameters = [
            'username' => 'tester',
            'hidden_test' => 0
        ];

        $client->request('GET', '/api/v2/user/hierarchy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // domain 2 hierarchy
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0][0]['id']);
        $this->assertEquals('tester', $output['ret'][0][0]['username']);
        $this->assertEquals(7, $output['ret'][0][1]['id']);
        $this->assertEquals('ztester', $output['ret'][0][1]['username']);
        $this->assertEquals(6, $output['ret'][0][2]['id']);
        $this->assertEquals('ytester', $output['ret'][0][2]['username']);
        $this->assertEquals(5, $output['ret'][0][3]['id']);
        $this->assertEquals('xtester', $output['ret'][0][3]['username']);
        $this->assertEquals(4, $output['ret'][0][4]['id']);
        $this->assertEquals('wtester', $output['ret'][0][4]['username']);
        $this->assertEquals(3, $output['ret'][0][5]['id']);
        $this->assertEquals('vtester', $output['ret'][0][5]['username']);
        $this->assertEquals(2, $output['ret'][0][6]['id']);
        $this->assertEquals('company', $output['ret'][0][6]['username']);

        // domain 102 hierarchy
        $this->assertEquals(11, $output['ret'][1][0]['id']);
        $this->assertEquals('tester', $output['ret'][1][0]['username']);
        $this->assertEquals(10, $output['ret'][1][1]['id']);
        $this->assertEquals('gaga', $output['ret'][1][1]['username']);
        $this->assertEquals(9, $output['ret'][1][2]['id']);
        $this->assertEquals('isolate', $output['ret'][1][2]['username']);
    }

    /**
     * 測試由帳號取得體系資料(模糊搜尋並限制查詢筆數)
     */
    public function testGetHierarchyWithFuzzySearchRecordLimitV2()
    {
        $client = $this->createClient();

        $parameters = [
            'username'     => '%test%',
            'first_result' => 2,
            'max_results'  => 3
        ];

        $client->request('GET', '/api/v2/user/hierarchy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('xtester', $output['ret'][0][0]['username']);
        $this->assertEquals('ytester', $output['ret'][1][0]['username']);
        $this->assertEquals('ztester', $output['ret'][2][0]['username']);

        $this->assertEquals(4, count($output['ret'][0]));
        $this->assertEquals(5, count($output['ret'][1]));
        $this->assertEquals(6, count($output['ret'][2]));
    }

    /**
     * 測試由帳號取得體系資料，但username含有空白(模糊搜尋並限制查詢筆數)
     */
    public function testGetHierarchyWithFuzzySearchRecordLimitAndUsernameContainsBlanksV2()
    {
        $client = $this->createClient();

        $parameters = [
            'username'     => ' %test% ',
            'first_result' => 2,
            'max_results'  => 3
        ];

        $client->request('GET', '/api/v2/user/hierarchy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('xtester', $output['ret'][0][0]['username']);
        $this->assertEquals('ytester', $output['ret'][1][0]['username']);
        $this->assertEquals('ztester', $output['ret'][2][0]['username']);

        $this->assertEquals(4, count($output['ret'][0]));
        $this->assertEquals(5, count($output['ret'][1]));
        $this->assertEquals(6, count($output['ret'][2]));
    }

    /**
     * 測試由帳號取得體系資料(不傳參數)
     */
    public function testGetHierarchyWithOutParameterV2()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/v2/user/hierarchy');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
    }

    /**
     * 測試由帳號取得體系資料(帳號不存在)
     */
    public function testGetHierarchyWhenUsernameNotExistV2()
    {
        $client = $this->createClient();

        $parameters = ['username' => "anything' OR 'x'='x"];
        $client->request('GET', '/api/v2/user/hierarchy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
    }

    /**
     * 測試指定廳由帳號取得體系資料
     */
    public function testGetHierarchyByDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 10);

        // make domain:102 has the same username
        $twins = new User();
        $twins->setId(11)
            ->setParent($parent)
            ->setUsername('tester')
            ->setDomain(102)
            ->setAlias('tester')
            ->setPassword('');

        $em->persist($twins);
        $em->flush();

        $config = new DomainConfig(102, 'domain', 'tr');
        $emShare->persist($config);
        $emShare->flush();

        $parameters = [
            'domain' => 102,
            'username' => 'tester',
            'hidden_test' => 0
        ];

        $client->request('GET', '/api/user/hierarchy_by_domain', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(11, $output['ret'][0][0]['id']);
        $this->assertEquals('tester', $output['ret'][0][0]['username']);
        $this->assertEquals(10, $output['ret'][0][1]['id']);
        $this->assertEquals('gaga', $output['ret'][0][1]['username']);
        $this->assertEquals(9, $output['ret'][0][2]['id']);
        $this->assertEquals('isolate', $output['ret'][0][2]['username']);
    }

    /**
     * 測試依廳由帳號取得體系資料，但domain不為廳主
     */
    public function testGetHierarchyByDomainButNotADomain()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 6,
            'username' => 'tester'
        ];

        $client->request('GET', '/api/user/hierarchy_by_domain', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010148, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試取得指定下層帳號
     */
    public function testList()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 2,
            'depth'     => 1,
            'bankrupt'  => 0,
            'sub_ret'   => 1,
            'sub'       => 0,
            'role'      => 7,
            'test'      => 0,
            'rent'      => 0,
            'block'     => 0
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['pagination']['total']);
        $this->assertEquals(2, $output['ret'][0]['parent']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(5, $output['ret'][0]['cash_fake']['id']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals(7, $output['ret'][0]['role']);

        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['block']);
        $this->assertFalse($output['ret'][0]['bankrupt']);
        $this->assertFalse($output['ret'][0]['sub']);

         //附屬資訊
        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][0]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][0]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][0]['alias']);

        $this->assertEquals(1, count($output['sub_ret']['user']));
    }

    /**
     * 測試取得全部下層帳號
     */
    public function testListAllDepth()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 2,
            'bankrupt' => 0,
            'sub_ret' => 1,
            'password_reset' => 0
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // vtester
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['pagination']['total']);
        $this->assertEquals(2, $output['ret'][0]['parent']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(5, $output['ret'][0]['cash_fake']['id']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);

        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['block']);
        $this->assertFalse($output['ret'][0]['bankrupt']);
        $this->assertFalse($output['ret'][0]['sub']);
        $this->assertFalse($output['ret'][0]['password_reset']);

        // tester
        $this->assertEquals(5, $output['ret'][5]['parent']);
        $this->assertEquals(6, $output['ret'][5]['id']);
        $this->assertEquals('ytester', $output['ret'][5]['username']);
        $this->assertEquals(2, $output['ret'][5]['domain']);
        $this->assertEquals(0, $output['ret'][5]['cash_fake']['id']);
        $this->assertEquals('CNY', $output['ret'][5]['currency']);

        $this->assertTrue($output['ret'][5]['enable']);
        $this->assertFalse($output['ret'][5]['block']);
        $this->assertFalse($output['ret'][5]['bankrupt']);
        $this->assertFalse($output['ret'][5]['sub']);

         //附屬資訊
        $user = $em->find('BBDurianBundle:User', $output['sub_ret']['user'][0]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][0]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][0]['alias']);

        $this->assertEquals(6, count($output['sub_ret']['user']));
    }

    /**
     * 測試以隱藏測試帳號為搜尋條件
     */
    public function testListWithHiddenTest()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id'   => 2,
            'hidden_test' => 1
        ];

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals(6, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['hidden_test']);
    }

    /**
     * 測試list的credit enable是否是取上層的enable
     */
    public function testListGetParentCreditEnable()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 8);

        $parameters = [
            'id',
            'parent_id' => 7,
            'fields' => ['credit']
        ];

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證group 1的enable是否跟上層相同
        $enable = $user->getParent()->getCredit(1)->isEnable();
        $this->assertEquals($output['ret'][0]['credit'][1]['enable'], $enable);

        // 驗證group 2的enable是否跟上層相同
        $enable = $user->getParent()->getCredit(2)->isEnable();
        $this->assertEquals($output['ret'][0]['credit'][2]['enable'], $enable);
    }

    /**
     * 測試已停用、無下層、且超過三個月的帳號
     */
    public function testListDisabledAndNoChildrenAndExistsForThreeMonth()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $now = new \DateTime();
        $monthesAgo = $now->sub(new \DateInterval('P5M'));
        $user = $em->find('BBDurianBundle:User', 8);

        $user->setCreatedAt($monthesAgo);
        $user->disable();
        $em->flush();
        $em->clear();

        $threeMonthesAgo = $now->add(new \DateInterval('P2M'));

        $parameters = array(
            'parent_id' => 2,
            'enable' => 0,
            'size'  => 0,
            'end_at' => $threeMonthesAgo->format(\DateTime::ISO8601)
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // tester
        $this->assertEquals(7, $output['ret'][0]['parent']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals(2, $output['ret'][0]['domain']);

        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertEquals(0, $output['ret'][0]['size']);
    }

    /**
     * 測試取得下層的parent_id
     */
    public function testListParentIdOfUser()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 2,
            'fields'    => array('parent_id')
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['parent_id']);
    }

    /**
     * 測試取得時間區間內的下層帳號
     */
    public function testListWithTimePeriod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $date = new \DateTime('2011-03-01');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCreatedAt($date);
        $em->persist($user);
        $em->flush();

        $parameters = array(
            'parent_id' => 2,
            'start_at'  => '2011-1-1 00:00:00',
            'end_at'    => '2012-1-1 00:00:00',
            'fields'    => array('id' , 'parent_id', 'username')
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['parent_id']);
    }

    /**
     * 測試取得修改區間內的下層帳號
     */
    public function testListWithModifiedTimePeriod()
    {
        $client = $this->createClient();

        $fields = array(
            'id',
            'parent_id',
            'username'
        );

        $parameters = array(
            'parent_id' => 9,
            'fields'    => $fields,
            'modified_start_at' => '2011-1-1 00:00:00',
            'modified_end_at'   => '2011-2-1 00:00:00',
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals(10, $output['ret'][0]['id']);
        $this->assertEquals(9, $output['ret'][0]['parent_id']);
    }

    /**
     * 測試以Repo的findChildBy取得修改區間內的下層帳號
     */
    public function testListWithTimePeriodWithRepoFindChildBy()
    {
        $client = $this->createClient();

        $fields = [
            'id',
            'parent_id',
            'username',
            'cash'
        ];

        $parameters = [
            'parent_id' => 2,
            'fields' => $fields,
            'start_at' => '2011-1-1 00:00:00',
            'end_at' => '2012-1-1 00:00:00',
            'modified_start_at' => '2011-1-1 00:00:00',
            'modified_end_at' => '2011-2-1 00:00:00',
        ];

        $client->request('GET', '/api/user/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得停權的下層帳號
     */
    public function testListWithBankrupt()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(true);

        $em->flush();
        $em->clear();

        $parameters = array(
            'parent_id' => 2,
            'bankrupt'  => 1
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertTrue($user->isBankrupt());
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertTrue($output['ret'][0]['bankrupt']);
        $this->assertEquals($user->getId(), $output['ret'][0]['id']);
        $this->assertEquals($user->getUsername(), $output['ret'][0]['username']);
    }

    /**
     * 測試取得指定下層帳號(下層無資料)
     */
    public function testListWhenNoDataBelow()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 8,
            'depth' => 1);

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['total']);
        $this->assertEquals(array(), $output['ret']);
    }

    /**
     * 測試取得指定下層帳號(模糊搜尋)
     */
    public function testListWithFuzzyUsername1()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'    => 2,
            'search_field' => array('username'),
            'search_value' => array('%tester'),
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(6, $output['pagination']['total']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals('wtester', $output['ret'][1]['username']);
        $this->assertEquals('xtester', $output['ret'][2]['username']);
        $this->assertEquals('ytester', $output['ret'][3]['username']);
        $this->assertEquals('ztester', $output['ret'][4]['username']);
        $this->assertEquals('tester', $output['ret'][5]['username']);
        $this->assertFalse(isset($output['ret'][6]));
    }

    /**
     * 測試取得指定下層帳號(模糊搜尋)
     */
    public function testListWithFuzzyUsername2()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'    => 2,
            'search_field' => array('username'),
            'search_value' => array('tester%'),
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試取得下層帳號時搜尋幣別
     */
    public function testListWithCurrency()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'    => 2,
            'search_field' => array('currency'),
            'search_value' => array('CNY'),
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('CNY', $output['ret'][4]['currency']);
        $this->assertEquals(8, $output['pagination']['total']);

        $parameters = array(
            'parent_id'    => 2,
            'search_field' => array('currency'),
            'search_value' => array('EUR'),
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試取得指定下層帳號(模糊搜尋)
     */
    public function testListWithFuzzyUsernameByUsernameParameter()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'    => 2,
            'username' => '%tester',
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(6, $output['pagination']['total']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals('wtester', $output['ret'][1]['username']);
        $this->assertEquals('xtester', $output['ret'][2]['username']);
        $this->assertEquals('ytester', $output['ret'][3]['username']);
        $this->assertEquals('ztester', $output['ret'][4]['username']);
        $this->assertEquals('tester', $output['ret'][5]['username']);
        $this->assertFalse(isset($output['ret'][6]));
    }

    /**
     * 測試取得指定下層帳號，但username含有空白(模糊搜尋)
     */
    public function testListWithFuzzyUsernameByUsernameParameterAndUsernameContainsBlanks()
    {
        $client = $this->createClient();

        //直接帶入username欄位
        $parameters = [
            'parent_id'    => 2,
            'username' => ' %tester ',
        ];

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(6, $output['pagination']['total']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals('wtester', $output['ret'][1]['username']);
        $this->assertEquals('xtester', $output['ret'][2]['username']);
        $this->assertEquals('ytester', $output['ret'][3]['username']);
        $this->assertEquals('ztester', $output['ret'][4]['username']);
        $this->assertEquals('tester', $output['ret'][5]['username']);
        $this->assertFalse(isset($output['ret'][6]));

        //在search_field帶入username
        $parameters = [
            'parent_id'    => 2,
            'search_field' => 'username',
            'search_value' => ' %tester '
        ];

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(6, $output['pagination']['total']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals('wtester', $output['ret'][1]['username']);
        $this->assertEquals('xtester', $output['ret'][2]['username']);
        $this->assertEquals('ytester', $output['ret'][3]['username']);
        $this->assertEquals('ztester', $output['ret'][4]['username']);
        $this->assertEquals('tester', $output['ret'][5]['username']);
        $this->assertFalse(isset($output['ret'][6]));
    }

    /**
     * 測試取得指定詳細資料的下層帳號
     */
    public function testListUserDetail()
    {
        $client = $this->createClient();
        $parameters = array(
            'parent_id'    => 2,
            'search_field' => 'passport',
            'search_value' => 'PA123456',
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試取得指定詳細資料的下層帳號(模糊搜尋)
     */
    public function testListUserDetailWithFuzzySearch()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 測試用資料設定，正確請用"體系轉移"
        $parent = $em->find('BBDurianBundle:User', 2);
        $gaga = $em->find('BBDurianBundle:User', 10);
        $gaga->setParent($parent);
        $ua = new UserAncestor($gaga, $parent, 1);
        $em->persist($ua);
        $em->flush();

        $parameters = array(
            'parent_id'    => 2,
            'search_field' => array('email'),
            'search_value' => array('%@%'),
            'sort'         => 'id',
            'order'        => 'asc'
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(3, $output['pagination']['total']);
        $this->assertEquals('ytester', $output['ret'][0]['username']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals('gaga', $output['ret'][2]['username']);
        $this->assertFalse(isset($output['ret'][3]));
    }

    /**
     * 測試取得指定銀行資訊的下層帳號
     */
    public function testListBankAccount()
    {
        $client = $this->createClient();
        $parameters = array(
            'parent_id'    => 2,
            'search_field' => array('account'),
            'search_value' => array('4'),
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試取得指定銀行資訊的下層帳號(模糊搜尋)
     */
    public function testListBankAccountWithFuzzySearch()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $bank = new Bank($user);
        $bank->setCode(543)
             ->setAccount('44789548125462');
        $em->persist($bank);
        $em->flush();

        $parameters = array(
            'parent_id'    => 2,
            'search_field' => array('account'),
            'search_value' => array('%4%'),
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output['pagination']['total']);
        $this->assertEquals('ztester', $output['ret'][0]['username']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試取得指定email的下層帳號
     */
    public function testListEmail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userEmail = $em->find('BBDurianBundle:UserEmail', 7);
        $userEmail->setEmail('Davinci@chinatown.com');
        $em->flush();

        $client = $this->createClient();
        $parameters = [
            'parent_id'    => 6,
            'search_field' => ['email'],
            'search_value' => ['Davinci@chinatown.com'],
            'fields'       => ['username', 'currency']
        ];

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output['pagination']['total']);
        $this->assertEquals('ztester', $output['ret'][0]['username']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試取得指定父層id，驗證失敗後拋出ShareLimitException
     */
    public function testListButShareLimitNotExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 7);

        $mockDealer = $this->getMockBuilder('BB\DurianBundle\Share\Dealer')
            ->disableOriginalConstructor()
            ->setMethods(['toArray'])
            ->getMock();

        $mockDealer->expects($this->any())
            ->method('toArray')
            ->will($this->throwException(new ShareLimitNotExists($user, 1, FALSE)));

        $client->getContainer()->set('durian.share_dealer', $mockDealer);

        $parameters = ['parent_id' => 6];

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080028, $output['code']);
        $this->assertEquals('User 7 has no sharelimit of group 1', $output['msg']);
    }

    /**
     * 測試用使用者存提款紀錄取得下層帳號
     */
    public function testListUserDepositWithdraw()
    {
        $client = $this->createClient();
        $parameters = [
            'parent_id'    => 7,
            'search_field' => ['deposit'],
            'search_value' => [false],
            'fields' => ['id', 'username', 'deposit_withdraw']
        ];

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertFalse(isset($output['ret'][1]));

        $this->assertEquals(8, $output['ret'][0]['deposit_withdraw']['user_id']);
        $this->assertEquals('2013-01-01T12:00:00+0800', $output['ret'][0]['deposit_withdraw']['deposit_at']);
        $this->assertFalse($output['ret'][0]['deposit_withdraw']['deposit']);
        $this->assertFalse($output['ret'][0]['deposit_withdraw']['withdraw']);
    }

    /**
     * 測試取得使用者資訊但搜尋欄位錯誤
     */
    public function testListUserWithSearthDataFailed()
    {
        $client = $this->createClient();
        $parameters = [
            'parent_id' => 7,
            'search_field' => ['test'],
            'search_value' => ['test']
        ];

        $client->request('GET', '/api/user/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['parent']);
        $this->assertEquals('tester', $output['ret'][0]['alias']);
    }

    /**
     * 測試取得指定下層帳號(傳非關聯欄位)
     * 在User Table有資料可以直接query抓回來的欄位
     * 在CashFake, Credit Table直接從資料庫取得的欄位
     * Cash資料直接從redis取得balance、pre_add、pre_sub
     */
    public function testListNotWithMappingField()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 修改資料庫的Cash, CashFake, Credit值與redis不同
        $cash = $em->find('BBDurianBundle:Cash', 7);
        $redisCashInfo = $this->getContainer()->get('durian.op')->getRedisCashBalance($cash);
        $cash->setBalance($redisCashInfo['balance'] + 100);
        $cash->setBalance($redisCashInfo['pre_add'] + 100);
        $cash->setBalance($redisCashInfo['pre_sub'] + 100);

        $cashFake = $em->find('BBDurianBundle:CashFake', 2);
        $redisCashfakeInfo = $this->getContainer()->get('durian.cashfake_op')
            ->getBalanceByRedis($cashFake->getUser(), $cashFake->getCurrency());
        $cashFake->setBalance($redisCashfakeInfo['balance'] + 100);

        $credit5 = $em->find('BBDurianBundle:Credit', 5);
        $creditBalance5 = $this->getContainer()->get('durian.credit_op')->getBalanceByRedis(8, 1);
        $credit5->setLine($creditBalance5['line'] + 100);

        $credit6 = $em->find('BBDurianBundle:Credit', 6);
        $creditBalance6 = $this->getContainer()->get('durian.credit_op')->getBalanceByRedis(8, 2);
        $credit6->setLine($creditBalance6['line'] + 100);
        $em->flush();

        $em->refresh($cash);
        $em->refresh($cashFake);
        $em->refresh($credit5);
        $em->refresh($credit6);

        $parameters = [
            'parent_id' => 2,
            'fields' => [
                'id',
                'username',
                'domain',
                'alias',
                'sub',
                'enable',
                'block',
                'bankrupt',
                'created_at',
                'modified_at',
                'password_expire_at',
                'last_login',
                'currency',
                'size',
                'cash',
                'cash_fake',
                'credit',
                'outside'
            ],
            'first_result' => 1,
            'max_results' => 20,
            'sort' => 'id',
            'order' => 'asc'
        ];

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['pagination']['total']);
        $this->assertEquals(20, $output['pagination']['max_results']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertEquals(5, $output['ret'][1]['id']);
        $this->assertEquals(6, $output['ret'][2]['id']);
        $this->assertEquals(7, $output['ret'][3]['id']);
        $this->assertEquals(8, $output['ret'][4]['id']);
        $this->assertEquals('wtester', $output['ret'][0]['username']);
        $this->assertEquals('xtester', $output['ret'][1]['username']);
        $this->assertEquals('ytester', $output['ret'][2]['username']);
        $this->assertEquals('ztester', $output['ret'][3]['username']);
        $this->assertEquals('tester', $output['ret'][4]['username']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals('CNY', $output['ret'][1]['currency']);
        $this->assertEquals('CNY', $output['ret'][2]['currency']);
        $this->assertEquals('TWD', $output['ret'][3]['currency']);
        $this->assertEquals('CNY', $output['ret'][4]['currency']);
        $this->assertEquals('1', $output['ret'][0]['size']);
        $this->assertEquals('1', $output['ret'][1]['size']);
        $this->assertEquals('1', $output['ret'][2]['size']);
        $this->assertEquals('2', $output['ret'][3]['size']);
        $this->assertEquals('0', $output['ret'][4]['size']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('wtester', $output['ret'][0]['alias']);
        $this->assertNotNull($output['ret'][0]['created_at']);
        $this->assertNotNull($output['ret'][0]['modified_at']);
        $this->assertNotNull($output['ret'][0]['password_expire_at']);
        $this->assertNull($output['ret'][0]['last_login']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['sub']);
        $this->assertFalse($output['ret'][0]['block']);
        $this->assertFalse($output['ret'][0]['bankrupt']);

        // 驗證user 8 tester 資料庫的CashFake, Credit Table
        $currency = new \BB\DurianBundle\Currency();

        // 測試cash取得redis的balance、pre_sub、pre_add值
        $this->assertEquals($cash->getId(), $output['ret'][4]['cash']['id']);
        $this->assertEquals($cash->getUser()->getId(), $output['ret'][4]['cash']['user_id']);
        $this->assertEquals($redisCashInfo['balance'], $output['ret'][4]['cash']['balance']);
        $this->assertEquals($redisCashInfo['pre_sub'], $output['ret'][4]['cash']['pre_sub']);
        $this->assertEquals($redisCashInfo['pre_add'], $output['ret'][4]['cash']['pre_add']);
        $cashCurrency = $currency->getMappedCode($cash->getCurrency());
        $this->assertEquals($cashCurrency, $output['ret'][4]['cash']['currency']);

        $this->assertEquals($cashFake->getId(), $output['ret'][4]['cash_fake']['id']);
        $this->assertEquals($cashFake->getUser()->getId(), $output['ret'][4]['cash_fake']['user_id']);
        $this->assertEquals($cashFake->getBalance(), $output['ret'][4]['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $output['ret'][4]['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $output['ret'][4]['cash_fake']['pre_add']);
        $cashFakeCurrency = $currency->getMappedCode($cashFake->getCurrency());
        $this->assertEquals($cashFakeCurrency, $output['ret'][4]['cash_fake']['currency']);
        $this->assertEquals($cashFake->isEnable(), $output['ret'][4]['cash_fake']['enable']);

        $this->assertEquals($credit5->getId(), $output['ret'][4]['credit'][1]['id']);
        $this->assertEquals($credit5->getUser()->getId(), $output['ret'][4]['credit'][1]['user_id']);
        $this->assertEquals($credit5->getGroupNum(), $output['ret'][4]['credit'][1]['group']);
        $this->assertEquals($credit5->isEnable(), $output['ret'][4]['credit'][1]['enable']);
        $this->assertEquals($credit5->getLine(), $output['ret'][4]['credit'][1]['line']);
        $this->assertEquals($credit5->getBalance(), $output['ret'][4]['credit'][1]['balance']);

        $this->assertEquals($credit6->getId(), $output['ret'][4]['credit'][2]['id']);
        $this->assertEquals($credit6->getUser()->getId(), $output['ret'][4]['credit'][2]['user_id']);
        $this->assertEquals($credit6->getGroupNum(), $output['ret'][4]['credit'][2]['group']);
        $this->assertEquals($credit6->isEnable(), $output['ret'][4]['credit'][2]['enable']);
        $this->assertEquals($credit6->getLine(), $output['ret'][4]['credit'][2]['line']);
        $this->assertEquals($credit6->getBalance(), $output['ret'][4]['credit'][2]['balance']);

        $this->assertFalse($output['ret'][0]['outside']);
    }

    /**
     * 測試取得指定下層帳號，但下層佔成錯誤
     */
    public function testListButChildShareLimitError()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 5);

        $shareLimit = $user->getShareLimit(1);

        $shareLimit->setUpper(90)->setLower(90)
                  ->setParentUpper(90)->setParentLower(20);

        $em->flush();
        $em->clear();

        $parameters = array(
            'parent_id' => 2,
        );

        $client->request('GET', '/api/user/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('error', $output['ret'][4]['sharelimit_division'][1]);
        $this->assertEquals('error', $output['ret'][5]['sharelimit_division'][1]);
        $this->assertEquals('error', $output['ret'][6]['sharelimit_division'][1]);
        $this->assertEquals('Any child ParentUpper + Lower (min1) can not below parentBelowLower', $output['msg']);
        $this->assertEquals(150080018, $output['code']);
    }

    /**
     * 測試用search_field為字串取得下層帳號
     */
    public function testListWithStringOfSearchField()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 3,
            'depth' => 1,
            'search_field' => 'username',
            'search_value' => 'wtester'
        ];

        $client->request('GET', '/api/user/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][0]['parent']);
        $this->assertEquals('wtester', $output['ret'][0]['username']);
    }

    /**
     * 測試體系轉移
     */
    public function testChangeParent()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $gParent = $em->find('BBDurianBundle:User', 2);
        $parent = $em->find('BBDurianBundle:User', 50);

        $cfRepo = $em->getRepository('BBDurianBundle:CashFake');
        $creditRepo = $em->getRepository('BBDurianBundle:Credit');

        $cashFake1 = $cfRepo->findOneBy(array('user' => $targetParent->getId()));
        $cashFake1->setBalance(500);

        $cashFake2 = $cfRepo->findOneBy(array('user' => $parent->getId()));
        $cashFake2->setBalance(0);

        $pCredit1 = new Credit($parent, 1);
        $pCredit1->setLine(5000);
        $em->persist($pCredit1);

        $pCredit2 = new Credit($parent, 2);
        $pCredit2->setLine(3000);
        $em->persist($pCredit2);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);

        $ua = new UserAncestor($sourceUser, $gParent, 2);
        $ua2 = new UserAncestor($sourceUser, $parent, 1);

        $em->persist($ua);
        $em->persist($ua2);

        $currency = 156; // CNY

        $cashFake3 = new CashFake($sourceUser, $currency);
        $cashFake3->setBalance(500);
        $em->persist($cashFake3);

        $credit = new Credit($targetParent, 1);
        $credit->setLine(30000);
        $em->persist($credit);

        $credit = new Credit($targetParent, 2);
        $credit->setLine(25000);
        $em->persist($credit);

        $credit = new Credit($sourceUser, 1);
        $credit->setLine(3000);
        $em->persist($credit);
        $em->flush();

        $creditRepo->addTotalLine($credit->getParent()->getId(), 3000);
        $em->refresh($pCredit1);

        $credit = new Credit($sourceUser, 2);
        $credit->setLine(2000);
        $em->persist($credit);
        $em->flush();

        $creditRepo->addTotalLine($credit->getParent()->getId(), 2000);
        $em->refresh($pCredit2);

        $share = new ShareLimit($sourceUser, 1);
        $share->setUpper(0)->setLower(0)->setParentUpper(0)
              ->setParentLower(0);
        $em->persist($share);

        $em->flush();

        $shareNext = new ShareLimitNext($sourceUser, 1);
        $shareNext->setUpper(0)->setLower(0)->setParentUpper(0)
                  ->setParentLower(0);
        $em->persist($shareNext);

        $share = $parent->getShareLimit(1);
        $shareNext = $parent->getShareLimitNext(1);

        $share->setMin1(0)->setMax1(0)->setMax2(0);
        $shareNext->setMin1(0)->setMax1(0)->setMax2(0);

        $em->flush();

        //測試轉移前假現金金額是否正確
        $this->assertEquals($cashFake1->getBalance(), 500);
        $this->assertEquals($cashFake1->getPreSub(), 0);
        $this->assertEquals($cashFake1->getPreAdd(), 0);
        $this->assertEquals($cashFake2->getBalance(), 0);
        $this->assertEquals($cashFake2->getPreSub(), 0);
        $this->assertEquals($cashFake2->getPreAdd(), 0);
        $this->assertEquals($cashFake3->getBalance(), 500);
        $this->assertEquals($cashFake3->getPreSub(), 0);
        $this->assertEquals($cashFake3->getPreAdd(), 0);

        $uaRepo = $em->getRepository('BBDurianBundle:UserAncestor');
        $uas = $uaRepo->findOneBy(
            array(
                'user' => $sourceUser->getId(),
                'ancestor' => $gParent->getId()
            )
        );

        $this->assertNotNull($uas);
        $this->assertEquals($uas->getUser(), $sourceUser);
        $this->assertEquals($uas->getAncestor(), $gParent);

        //測試轉移前信用額度是否正確
        $this->assertEquals($parent->getCredit(1)->getTotalLine(), 3000);
        $this->assertEquals($parent->getCredit(2)->getTotalLine(), 2000);
        $this->assertEquals($parent->getCredit(1)->getLine(), 5000);
        $this->assertEquals($parent->getCredit(2)->getLine(), 3000);
        $this->assertEquals($targetParent->getCredit(1)->getTotalLine(), 0);
        $this->assertEquals($targetParent->getCredit(2)->getTotalLine(), 0);
        $this->assertEquals($targetParent->getCredit(1)->getLine(), 30000);
        $this->assertEquals($targetParent->getCredit(2)->getLine(), 25000);
        $this->assertEquals($sourceUser->getCredit(1)->getLine(), 3000);
        $this->assertEquals($sourceUser->getCredit(2)->getLine(), 2000);

        //測試原本上層佔成min max
        $this->assertEquals(0, $share->getMin1());
        $this->assertEquals(0, $share->getMax1());
        $this->assertEquals(0, $share->getMax2());

        $this->assertEquals(0, $shareNext->getMin1());
        $this->assertEquals(0, $shareNext->getMax1());
        $this->assertEquals(0, $shareNext->getMax2());

        //測試新上層原本佔成min max
        $share = $targetParent->getShareLimit(1);
        $shareNext = $targetParent->getShareLimitNext(1);

        $this->assertEquals(90, $share->getMin1());
        $this->assertEquals(90, $share->getMax1());
        $this->assertEquals(100, $share->getMax2());

        $this->assertEquals(0, $shareNext->getMin1());
        $this->assertEquals(0, $shareNext->getMax1());
        $this->assertEquals(90, $shareNext->getMax2());

        $parentId = $parent->getId();
        $sourceUserId = $sourceUser->getId();
        $targetParentId = $targetParent->getId();
        $cash1Id = $cashFake1->getId();
        $cash2Id = $cashFake2->getId();
        $cash3Id = $cashFake3->getId();

        $em->clear();

        $parameters = array('operator' => 'bbin');

        $client->request(
            'PUT',
            "/api/user/$sourceUserId/change_parent/3",
            $parameters,
            array(),
            $this->headerParam
        );

        $this->runCommand('durian:update-user-size');

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $em->clear();

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $parent = $em->find('BBDurianBundle:User', $parentId);
        $sourceUser = $em->find('BBDurianBundle:User', $sourceUserId);
        $targetParent = $em->find('BBDurianBundle:User', $targetParentId);
        $cashFake1 = $em->find('BBDurianBundle:CashFake', $cash1Id);
        $cashFake2 = $em->find('BBDurianBundle:CashFake', $cash2Id);
        $cashFake3 = $em->find('BBDurianBundle:CashFake', $cash3Id);

        //測試轉移體系是否成功
        $this->assertEquals($targetParent, $sourceUser->getParent());

        //測試轉移後假現金金額是否正確
        $this->assertEquals($cashFake1->getBalance(), 0);
        $this->assertEquals($cashFake1->getPreSub(), 0);
        $this->assertEquals($cashFake1->getPreAdd(), 0);
        $this->assertEquals($cashFake2->getBalance(), 500);
        $this->assertEquals($cashFake2->getPreSub(), 0);
        $this->assertEquals($cashFake2->getPreAdd(), 0);
        $this->assertEquals($cashFake3->getBalance(), 500);
        $this->assertEquals($cashFake3->getPreSub(), 0);
        $this->assertEquals($cashFake3->getPreAdd(), 0);

        //測試轉移後假現金明細有沒有寫到歷史資料庫
        $emHis = $this->getContainer()->get("doctrine.orm.his_entity_manager");
        $cfRepoHis = $emHis->getRepository('BBDurianBundle:CashFakeEntry');
        $cashFakeEntry = $cfRepoHis->findBy(
             [
                 'cashFakeId' => $cashFake1->getId(),
                 'opcode' => 1003,
                 'amount' => -500
             ]
        );

        $this->assertNotNull($cashFakeEntry);

        //測試轉移後UserAncestor 是否正確
        $uas = $uaRepo->findBy(array('user' => $sourceUser->getId()));

        $this->assertNotNull($uas);
        $this->assertEquals(count($uas), 2);
        $this->assertEquals($uas[0]->getAncestor()->getId(), 2);
        $this->assertEquals($uas[0]->getDepth(), 2);
        $this->assertEquals($uas[1]->getAncestor()->getId(), 3);
        $this->assertEquals($uas[1]->getDepth(), 1);

        //測試轉移後信用額度是否正確
        $this->assertEquals($parent->getCredit(1)->getTotalLine(), 0);
        $this->assertEquals($parent->getCredit(2)->getTotalLine(), 0);
        $this->assertEquals($parent->getCredit(1)->getLine(), 5000);
        $this->assertEquals($parent->getCredit(2)->getLine(), 3000);
        $this->assertEquals($targetParent->getCredit(1)->getTotalLine(), 3000);
        $this->assertEquals($targetParent->getCredit(2)->getTotalLine(), 2000);
        $this->assertEquals($targetParent->getCredit(1)->getLine(), 30000);
        $this->assertEquals($targetParent->getCredit(2)->getLine(), 25000);
        $this->assertEquals($sourceUser->getCredit(1)->getLine(), 3000);
        $this->assertEquals($sourceUser->getCredit(2)->getLine(), 2000);

        $parent = $em->find('BBDurianBundle:User', 50);

        //測試原本上層佔成min max
        $share = $parent->getShareLimit(1);
        $shareNext = $parent->getShareLimitNext(1);

        $this->assertEquals(200, $share->getMin1());
        $this->assertEquals(0, $share->getMax1());
        $this->assertEquals(0, $share->getMax2());

        $this->assertEquals(200, $shareNext->getMin1());
        $this->assertEquals(0, $shareNext->getMax1());
        $this->assertEquals(0, $shareNext->getMax2());

        //測試新上層佔成min max
        $share = $targetParent->getShareLimit(1);
        $shareNext = $targetParent->getShareLimitNext(1);

        $this->assertEquals(0, $share->getMin1());
        $this->assertEquals(90, $share->getMax1());
        $this->assertEquals(100, $share->getMax2());

        $this->assertEquals(0, $shareNext->getMin1());
        $this->assertEquals(0, $shareNext->getMax1());
        $this->assertEquals(90, $shareNext->getMax2());

        // 檢查cash_fake_entry_operator
        $cfeoRepo = $em->getRepository('BBDurianBundle:CashFakeEntryOperator');

        $entryOperator = $cfeoRepo->findOneBy(array('entryId' => 1001));
        $this->assertEquals('company', $entryOperator->getWhom());
        $this->assertEquals(7, $entryOperator->getLevel());
        $this->assertEquals(1, $entryOperator->getTransferOut());
        $this->assertEquals('bbin', $entryOperator->getUsername());

        $entryOperator = $cfeoRepo->findOneBy(array('entryId' => 1002));
        $this->assertEquals('company', $entryOperator->getWhom());
        $this->assertEquals(7, $entryOperator->getLevel());
        $this->assertEquals(0, $entryOperator->getTransferOut());
        $this->assertEquals('bbin', $entryOperator->getUsername());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試體系轉移但目標上層信用額度totalLine不足
     */
    public function testChangeParentButTargetParentCreditTotalLineNotEnough()
    {
        $redisWallet1 = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $gParent = $em->find('BBDurianBundle:User', 2);
        $parent = $em->find('BBDurianBundle:User', 50);

        $cfRepo = $em->getRepository('BBDurianBundle:CashFake');
        $creditRepo = $em->getRepository('BBDurianBundle:Credit');

        $cashFake1 = $cfRepo->findOneBy(array('user' => $targetParent->getId()));
        $cashFake1->setBalance(500);

        $cashFake2 = $cfRepo->findOneBy(array('user' => $parent->getId()));
        $cashFake2->setBalance(0);

        $pCredit1 = new Credit($parent, 1);
        $pCredit1->setLine(5000);
        $em->persist($pCredit1);

        $pCredit2 = new Credit($parent, 2);
        $pCredit2->setLine(3000);
        $em->persist($pCredit2);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);

        $ua = new UserAncestor($sourceUser, $gParent, 2);
        $ua2 = new UserAncestor($sourceUser, $parent, 1);

        $em->persist($ua);
        $em->persist($ua2);

        $credit = new Credit($targetParent, 1);
        $credit->setLine(30000);
        $em->persist($credit);

        $credit = new Credit($targetParent, 2);
        $credit->setLine(1000);
        $em->persist($credit);

        $credit = new Credit($sourceUser, 1);
        $credit->setLine(3000);
        $em->persist($credit);
        $em->flush();

        $creditRepo->addTotalLine($credit->getParent()->getId(), 3000);
        $em->refresh($pCredit1);

        $credit = new Credit($sourceUser, 2);
        $credit->setLine(2000);
        $em->persist($credit);
        $em->flush();

        $creditRepo->addTotalLine($credit->getParent()->getId(), 2000);
        $em->refresh($pCredit2);

        $em->flush();

        $uaRepo = $em->getRepository('BBDurianBundle:UserAncestor');
        $uas = $uaRepo->findOneBy(
            array(
                'user'=> $sourceUser->getId(),
                'ancestor' => $gParent->getId()
            )
        );

        $this->assertNotNull($uas);
        $this->assertEquals($uas->getUser(), $sourceUser);
        $this->assertEquals($uas->getAncestor(), $gParent);

        //測試轉移前信用額度是否正確
        $this->assertEquals($parent->getCredit(1)->getTotalLine(), 3000);
        $this->assertEquals($parent->getCredit(2)->getTotalLine(), 2000);
        $this->assertEquals($parent->getCredit(1)->getLine(), 5000);
        $this->assertEquals($parent->getCredit(2)->getLine(), 3000);
        $this->assertEquals($targetParent->getCredit(1)->getTotalLine(), 0);
        $this->assertEquals($targetParent->getCredit(2)->getTotalLine(), 0);
        $this->assertEquals($targetParent->getCredit(1)->getLine(), 30000);
        $this->assertEquals($targetParent->getCredit(2)->getLine(), 1000);
        $this->assertEquals($sourceUser->getCredit(1)->getLine(), 3000);
        $this->assertEquals($sourceUser->getCredit(2)->getLine(), 2000);

        $markName = 'credit_in_transfering';

        //測試credit mark是否沒有資料
        $this->assertEquals(0, $redisWallet1->scard($markName));

        //把credit資料讀取進 redis
        $client->request('GET', '/api/user/50/credit/1');
        $client->request('GET', '/api/user/3/credit/1');

        $sourceUserId = $sourceUser->getId();

        $em->clear();

        $parameters = array('operator' => 'bbin');

        $client->request('PUT', "/api/user/$sourceUserId/change_parent/3", $parameters);

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $em->clear();

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not enough line to be dispensed', $output['msg']);
        $this->assertEquals('150060049', $output['code']);

        //測試credit mark是否沒有資料
        $this->assertEquals(0, $redisWallet1->scard($markName));

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $gParent = $em->find('BBDurianBundle:User', 2);
        $parent = $em->find('BBDurianBundle:User', 50);

        //測試轉移失敗後信用額度是否正確
        $this->assertEquals($parent->getCredit(1)->getTotalLine(), 3000);
        $this->assertEquals($parent->getCredit(2)->getTotalLine(), 2000);
        $this->assertEquals($parent->getCredit(1)->getLine(), 5000);
        $this->assertEquals($parent->getCredit(2)->getLine(), 3000);
        $this->assertEquals($targetParent->getCredit(1)->getTotalLine(), 0);
        $this->assertEquals($targetParent->getCredit(2)->getTotalLine(), 0);
        $this->assertEquals($targetParent->getCredit(1)->getLine(), 30000);
        $this->assertEquals($targetParent->getCredit(2)->getLine(), 1000);
        $this->assertEquals($sourceUser->getCredit(1)->getLine(), 3000);
        $this->assertEquals($sourceUser->getCredit(2)->getLine(), 2000);

        //測試是否可以正常讀取 credit資料
        $parentCreditKey = 'credit_50_1';
        $targetParentKey = 'credit_3_1';

        $this->assertFalse($redisWallet2->exists($parentCreditKey));
        $this->assertFalse($redisWallet3->exists($targetParentKey));

        $creditOp = $this->getContainer()->get('durian.credit_op');
        $parentCreditInfo = $creditOp->getBalanceByRedis(50, 1);
        $targetParentCreditInfo = $creditOp->getBalanceByRedis(3, 1);

        $this->assertTrue($redisWallet2->exists($parentCreditKey));
        $this->assertTrue($redisWallet3->exists($targetParentKey));

        $this->assertEquals($parent->getCredit(1)->getTotalLine(), $parentCreditInfo['total_line']);
        $this->assertEquals($targetParent->getCredit(1)->getTotalLine(), $targetParentCreditInfo['total_line']);
    }

    /**
     * 測試在佔成更新期間體系轉移
     */
    public function testChangeParentDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $client->request('PUT', '/api/user/10/change_parent/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Cannot perform this during updating sharelimit', $output['msg']);

    }

    /**
     * 測試在太久沒跑佔成更新的狀況下體系轉移
     */
    public function testChangeParentWithoutUpdatingForTooLongTime()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::FINISHED, '2011-10-10 11:59:00');

        $client->request('PUT', '/api/user/10/change_parent/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Cannot perform this due to updating sharelimit is not performed for too long time',
            $output['msg']
        );
    }

    /**
     * 測試在體系轉移時，但佔成不存在
     */
    public function testChangeParentButShareLimitNotExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 7);

        $mockDealer = $this->getMockBuilder('BB\DurianBundle\User\AncestorManager')
            ->disableOriginalConstructor()
            ->setMethods(['changeParent'])
            ->getMock();

        $mockDealer->expects($this->any())
            ->method('changeParent')
            ->will($this->throwException(new ShareLimitNotExists($user, 1, FALSE)));

        $client->getContainer()->set('durian.ancestor_manager', $mockDealer);
        $client->request('PUT', '/api/user/7/change_parent/6');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080028, $output['code']);
        $this->assertEquals('User 7% has no sharelimit of group 1', $output['msg']);
    }

    /**
     * 測試檢查指定的使用者欄位唯一
     */
    public function testUserCheckUnique()
    {
        $client = $this->createClient();

        // check duplicate
        $parameter = array(
            'domain' => '2',
            'fields' => array('username' => 'tester')
        );

        $client->request('GET', '/api/user/check_unique', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['unique']);

        // check unique
        $parameter = array(
            'domain' => '2',
            'fields' => array('username' => 'alibaba')
        );

        $client->request('GET', '/api/user/check_unique', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['unique']);
    }

    /**
     * 測試檢查指定的使用者欄位唯一，但username含有空白
     */
    public function testUserCheckUniqueAndUsernameContainsBlanks()
    {
        $client = $this->createClient();

        // check duplicate
        $parameter = [
            'domain' => '2',
            'fields' => ['username' => ' tester ']
        ];

        $client->request('GET', '/api/user/check_unique', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['unique']);

        // check unique
        $parameter = [
            'domain' => '2',
            'fields' => ['username' => ' alibaba ']
        ];

        $client->request('GET', '/api/user/check_unique', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['unique']);
    }

    /**
     * 測試取使用者密碼錯誤次數
     */
    public function testGetLoginErrorNumber()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/login_log/error_number');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['err_num']);
    }

    /**
     * 測試無登入記錄的情況下取使用者密碼錯誤次數
     */
    public function testGetLoginErrorNumberWithoutLoginRecord()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/6/login_log/error_number');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']['err_num']);
    }

    /**
     * 測試在無成功登入情況下取得密碼輸入錯誤次數
     */
    public function testGetErrorNumberWithoutLastLogin()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/7/login_log/error_number');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['err_num']);
    }

    /**
     * 測試取得使用者上次登入成功記錄
     */
    public function testGetPreviousLogin()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/login_log/previous');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('127.0.0.1', $output['ret']['previous_login']['ip']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['previous_login']['result']);
        $this->assertEquals(8, $output['ret']['previous_login']['user_id']);
        $this->assertEquals('2011-11-11T11:11:11+0800', $output['ret']['previous_login']['at']);
    }

    /**
     * 測試無登入記錄的情況下取得使用者上次登入成功記錄
     */
    public function testGetPreviousLoginWithoutLoginRecord()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/6/login_log/previous');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']['previous_login']);
    }

    /**
     * 測試只有一次成功登入記錄
     */
    public function testOnlyOneSuccessLog()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $log = $em->find('BBDurianBundle:LoginLog', 1);
        $em->remove($log);
        $em->flush();

        $client->request('GET', '/api/user/8/login_log/previous');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']['previous_login']);
    }

    /**
     * 測試無成功登入記錄
     */
    public function testNoSuccessLog()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/7/login_log/previous');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']['previous_login']);
    }

    /**
     * 測試取得指定的登入記錄
     */
    public function testGetLoginLog()
    {
        $parameters = [
            'username' => 'tester',
            'ip' => '127.0.0.1',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3'
        ];
        $this->getResponse('PUT', '/api/login', $parameters);

        $start     = '2011-11-01T09:00:00+0800';
        $end       = (new \DateTime())->modify('tomorrow')->format('Y-m-d H:i:s');
        $parameters = [
            'result'       => LoginLog::RESULT_SUCCESS,
            'ip'           => '127.0.0.1',
            'sort'         => 'session_id',
            'order'        => 'asc',
            'first_result' => 0,
            'max_results'  => 1,
            'start'        => $start,
            'end'          => $end
        ];
        $output = $this->getResponse('GET', '/api/user/8/login_log', $parameters);
        $at = strtotime($output['ret'][0]['at']);

        $this->assertEquals('ok', $output['result']);
        $this->assertCount(1, $output['ret']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertNotNull($output['ret'][0]['session_id']);
        $this->assertGreaterThanOrEqual(strtotime($start), $at);
        $this->assertLessThanOrEqual(strtotime($end), $at);
        $this->assertEquals(9, $output['ret']['0']['id']);
        $this->assertEquals(8, $output['ret']['0']['user_id']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertFalse($output['ret'][0]['is_otp']);
        $this->assertFalse($output['ret'][0]['is_slide']);
        $this->assertFalse($output['ret'][0]['test']);
    }

    /**
     * 測試查詢登入記錄包含行動裝置資訊
     */
    public function testGetLoginLogWithMobileInfo()
    {
        $parameters = [
            'username' => 'tester',
            'ip' => '42.4.2.168',
            'domain' => '2',
            'password' => '123456',
            'entrance' => '3',
            'ingress' => 2,
            'device_name' => 'tester的ZenFone 3',
            'brand' => 'ASUS',
            'model' => 'Z017DA'
        ];
        $this->getResponse('PUT', '/api/login', $parameters);

        $parameters = [
            'result'       => LoginLog::RESULT_SUCCESS,
            'sort'         => 'id',
            'order'        => 'desc',
            'first_result' => 0,
            'max_results'  => 1,
            'mobile_info'  => 1
        ];
        $output = $this->getResponse('GET', '/api/user/8/login_log', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertCount(1, $output['ret']);
        $this->assertEquals(9, $output['ret'][0]['id']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('42.4.2.168', $output['ret'][0]['ip']);
        $this->assertFalse($output['ret'][0]['is_otp']);
        $this->assertFalse($output['ret'][0]['is_slide']);
        $this->assertFalse($output['ret'][0]['test']);
        $this->assertEquals('tester的ZenFone 3', $output['ret'][0]['mobile']['name']);
        $this->assertEquals('ASUS', $output['ret'][0]['mobile']['brand']);
        $this->assertEquals('Z017DA', $output['ret'][0]['mobile']['model']);
        $this->assertEquals('9', $output['ret'][0]['mobile']['login_log_id']);
    }

    /**
     * 取得廳主對應銀行幣別資料
     */
    public function testGetDomainBank()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/2/bank');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]);
        $this->assertEquals('3', $output['ret'][1]);
    }

    /**
     * 測試取非廳主的銀行幣別資料
     */
    public function testGetUserBankCurrency()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/domain/8/bank');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010051', $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 設定廳主對應銀行幣別資料
     */
    public function testSetDomainBank()
    {
        $client = $this->createClient();
        $parameter = array('banks' => array(1, 2, 4));

        $client->request('PUT', '/api/domain/2/bank', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]);
        $this->assertEquals('1', $output['ret'][1]);

        // 不存在的銀行幣別不會被設定
        $this->assertFalse(isset($output['ret'][3]));
    }

    /**
     * 設定廳主對應銀行幣別資料，但佔成不存在
     */
    public function testSetDomainBankButShareLimitNotExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 5);
        $parameter = ['banks' => 1];

        $mockLog = $this->getMockBuilder('BB\DurianBundle\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['validateAllowedOperator'])
            ->getMock();

        $mockLog->expects($this->any())
            ->method('validateAllowedOperator')
            ->will($this->throwException(new ShareLimitNotExists($user, 1, FALSE)));

        $client->getContainer()->set('durian.sensitive_logger', $mockLog);
        $client->request('PUT', '/api/domain/2/bank', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080028, $output['code']);
        $this->assertEquals('User %userId% has no sharelimit of group %groupNum%', $output['msg']);
    }

    /**
     * 測試取指定廳最後修改列表
     */
    public function testGetModifiedUserByDomain()
    {
        $client = $this->createClient();
        $parameter = array(
            'begin_at'      => '2010-01-01 00:00:00',
            'first_result'  => 0,
            'max_results'   => 100
        );

        $client->request('GET', '/api/domain/2/modified_user', $parameter, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertArrayHasKey('last_modified', $output);

        $ret = $output['ret'];
        $this->assertEquals(10, count($ret));
        $this->assertArrayHasKey('id', $ret[6]);
        $this->assertArrayHasKey('domain', $ret[6]);
        $this->assertArrayHasKey('username', $ret[6]);
        $this->assertArrayHasKey('modified_at', $ret[6]);
        $this->assertArrayHasKey('name_real', $ret[6]);
        $this->assertArrayHasKey('country', $ret[6]);
        $this->assertArrayHasKey('name_english', $ret[6]);
        $this->assertArrayHasKey('currency', $ret[6]);
        $this->assertArrayHasKey('role', $ret[6]);
        $this->assertArrayHasKey('created_at', $ret[6]);
        $this->assertArrayHasKey('last_bank', $ret[6]);
        $this->assertArrayHasKey('size', $ret[6]);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試取指定廳最後修改列表, 分頁區間裡沒有資料
     */
    public function testGetModifiedUserByDomainNoDataInPaging()
    {
        $client = $this->createClient();
        $parameter = [
            'begin_at'     => '2010-01-01 00:00:00',
            'first_result' => 100,
            'max_results'  => 100
        ];

        $client->request('GET', '/api/domain/2/modified_user', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
        $this->assertEquals('2010-01-01 00:00:00', $output['last_modified']);
    }

    /**
     * 測試取指定時間點後刪除的會員
     */
    public function testGetRemovedUserByTime()
    {
        $client = $this->createClient();

        // 先刪除一筆資料
        $client->request('DELETE', '/api/user/51');

        $parameter = [
            'removed_at'   => '2010-01-01 00:00:00',
            'first_result' => 0,
            'max_results'  => 100
        ];

        $client->request('GET', '/api/removed_user_by_time', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(50, $output['ret'][0]['user_id']);
        $this->assertEquals(7, $output['ret'][0]['role']);
        $this->assertGreaterThan('2010-01-01 00:00:00', $output['ret'][0]['removed_at']);
        $this->assertEquals(51, $output['ret'][1]['user_id']);
        $this->assertEquals(1, $output['ret'][1]['role']);
        $this->assertGreaterThan('2010-01-01 00:00:00', $output['ret'][1]['removed_at']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(100, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試取指定時間點後刪除的會員
     */
    public function testGetRemovedUserByTimeV2()
    {
        $client = $this->createClient();

        // 先刪除一筆資料
        $client->request('DELETE', '/api/user/51');

        $parameter = [
            'removed_at'   => '2010-01-01 00:00:00',
            'first_result' => 0,
            'max_results'  => 10
        ];

        $client->request('GET', '/api/v2/removed_user_by_time', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(50, $output['ret'][0]['user_id']);
        $this->assertEquals(7, $output['ret'][0]['role']);
        $this->assertEquals(51, $output['ret'][1]['user_id']);
        $this->assertEquals(1, $output['ret'][1]['role']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試取指定廳刪除的使用者列表
     */
    public function testGetRemovedUserByDomain()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/user/8', [], [], $this->headerParam);

        $parameter = array(
            'begin_at'      => '2010-01-01 00:00:00',
            'first_result'  => 0,
            'max_results'   => 100
        );

        $client->request('GET', '/api/domain/2/removed_user', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $ret = $output['ret'];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret[1]['modified_at'], $output['last_modified']);

        $this->assertEquals(2, count($ret));
        $this->assertEquals(8, $ret[1]['user_id']);
        $this->assertEquals('tester', $ret[1]['username']);
        $this->assertEquals(2, $ret[1]['domain']);
        $this->assertEquals('tester', $ret[1]['alias']);
        $this->assertEmpty($ret[1]['sub']);
        $this->assertEquals(1, $ret[1]['enable']);
        $this->assertEmpty($ret[1]['block']);
        $this->assertEmpty($ret[1]['bankrupt']);
        $this->assertEmpty($ret[1]['test']);
        $this->assertEquals(0, $ret[1]['size']);
        $this->assertEquals(2, $ret[1]['err_num']);
        $this->assertEquals('CNY', $ret[1]['currency']);
        $this->assertEquals('2012-01-01T09:30:11+0800', $ret[1]['last_login']);
        $this->assertEmpty($ret[1]['last_bank']);
        $this->assertEquals(1, $ret[1]['role']);
        $this->assertArrayHasKey('created_at', $ret[1]);
        $this->assertArrayHasKey('modified_at', $ret[1]);

        $this->assertEquals('Davinci@chinatown.com', $ret[1]['email']);
        $this->assertEquals('達文西', $ret[1]['name_real']);
        $this->assertEquals('甲級情報員', $ret[1]['name_chinese']);
        $this->assertEquals('Da Vinci', $ret[1]['name_english']);
        $this->assertEquals('Republic of China', $ret[1]['country']);
        $this->assertEquals('PA123456', $ret[1]['passport']);
        $this->assertEmpty($ret[1]['identity_card']);
        $this->assertEmpty($ret[1]['driver_license']);
        $this->assertEmpty($ret[1]['insurance_card']);
        $this->assertEmpty($ret[1]['health_card']);
        $this->assertEquals('2000-10-10', $ret[1]['birthday']);
        $this->assertEquals(3345678, $ret[1]['telephone']);
        $this->assertEquals(485163154787, $ret[1]['qq_num']);
        $this->assertEquals('Hello Durian', $ret[1]['note']);
    }

    /**
     * 測試取指定廳時間區間內新增會員的詳細相關資訊
     */
    public function testGetMemberDetail()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $opParams = array(
            'opcode' => 1001,
            'amount' => 10000
        );
        $client->request('PUT', '/api/user/8/cash/op', $opParams);

        $withdrawParams = array(
            'bank_id'   => 3,
            'amount'    => -10,
            'level'     => 1,
            'ip'        => '192.168.22.99'
        );
        $client->request('POST', '/api/user/8/cash/withdraw', $withdrawParams, [], $this->headerParam);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/1/lock', ['operator' => 'shit_fixer'], [], $this->headerParam);

        $confirmParams = array(
            'status' => 1,
            'checked_username' => 'shit_fixer'
        );
        $client->request('PUT', '/api/cash/withdraw/1', $confirmParams);

        $startAt = new \DateTime('1911-11-11 12:34:56');
        $endAt = new \DateTime('now');

        $parameters = array(
            'start_at' => $startAt->format(\DateTime::ISO8601),
            'end_at'   => $endAt->format(\DateTime::ISO8601)
        );

        $client->request('GET', '/api/domain/2/member_detail', $parameters, array(), $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('wtester', $output['ret'][1]['sc']);
        $this->assertEquals('xtester', $output['ret'][1]['co']);
        $this->assertEquals('ytester', $output['ret'][1]['sa']);
        $this->assertEquals('ztester', $output['ret'][1]['ag']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals('達文西', $output['ret'][1]['name_real']);
        $this->assertEquals('3345678', $output['ret'][1]['telephone']);
        $this->assertEquals('PA123456', $output['ret'][1]['passport']);
        $this->assertEquals('Davinci@chinatown.com', $output['ret'][1]['email']);
        $this->assertEquals('485163154787', $output['ret'][1]['qq_num']);
        $this->assertEquals('Republic of China', $output['ret'][1]['country']);
        $this->assertEquals('Hello Durian', $output['ret'][1]['note']);
        $this->assertEquals(3, $output['ret'][1]['last_bank']);
        $this->assertEquals(-257.23, $output['ret'][1]['withdraw_total']);
        $this->assertEquals(4, $output['ret'][1]['withdraw_count']);

        // 資料排序
        $banks = [];

        foreach ($output['ret'][1]['banks'] as $bank) {
            $banks[$bank['id']] = $bank;
        }

        //銀行
        $this->assertEquals(5, count($banks));
        $this->assertEquals(15, $banks[2]['code']);
        $this->assertEquals('ddf41d8s69786fd7s54f6ds', $banks[2]['account']);
        $this->assertEquals(null, $banks[2]['province']);
        $this->assertEquals(null, $banks[2]['city']);
        $this->assertEquals(1, $banks[4]['code']);
        $this->assertEquals('3141586254359', $banks[4]['account']);
        $this->assertEquals(null, $banks[4]['province']);
        $this->assertEquals(null, $banks[4]['city']);

        $this->assertTrue(isset($output['ret'][1]['created_at']));
        $this->assertTrue(isset($output['ret'][1]['birthday']));

        //測試會員沒有銀行狀況
        $banks = $em->getRepository('BBDurianBundle:Bank')->findBy(array('user' => 8));

        foreach ($banks as $bank) {
            $em->remove($bank);
        }

        $em->flush();

        $parameters = array(
            'start_at' => $startAt->format(\DateTime::ISO8601),
            'end_at'   => $endAt->format(\DateTime::ISO8601)
        );

        $client->request('GET', '/api/domain/2/member_detail', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('wtester', $output['ret'][1]['sc']);
        $this->assertEquals('xtester', $output['ret'][1]['co']);
        $this->assertEquals('ytester', $output['ret'][1]['sa']);
        $this->assertEquals('ztester', $output['ret'][1]['ag']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals('達文西', $output['ret'][1]['name_real']);
        $this->assertEquals('3345678', $output['ret'][1]['telephone']);

        $this->assertEquals(0, count($output['ret'][1]['banks']));

        //測試會員沒有使用者詳細資料狀況
        $ud = $em->find('BBDurianBundle:UserDetail', 8);
        $em->remove($ud);
        $em->flush();

        $parameters = array(
            'start_at' => $startAt->format(\DateTime::ISO8601),
            'end_at'   => $endAt->format(\DateTime::ISO8601),
            'first_result' => 0,
            'max_results' => 50
        );

        $client->request('GET', '/api/domain/2/member_detail', $parameters, array(), $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('wtester', $output['ret'][1]['sc']);
        $this->assertEquals('xtester', $output['ret'][1]['co']);
        $this->assertEquals('ytester', $output['ret'][1]['sa']);
        $this->assertEquals('ztester', $output['ret'][1]['ag']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals(null, $output['ret'][1]['name_real']);
        $this->assertEquals(null, $output['ret'][1]['telephone']);

        $this->assertEquals(0, count($output['ret'][1]['banks']));

        // 測試domain 沒有user_detail
        $parameters = [
            'start_at' => $startAt->format(\DateTime::ISO8601),
            'end_at'   => $endAt->format(\DateTime::ISO8601)
        ];
        $client->request('GET', '/api/domain/3/member_detail', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
        $this->assertTrue(strpos($results[1], $line) !== false);
    }

    /**
     * 將佔成更新紀錄狀態改成"正在執行"
     */
    private function changeUpdateCronState($state, $date = null)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $updateCron = $em->find('BBDurianBundle:ShareUpdateCron', 1);

        if ($state == ShareUpdateCron::RUNNING) {
            $updateCron->reRun();
        } else {
            $updateCron->finish();
        }

        if ($date) {
            $updateCron->setUpdateAt(new \DateTime($date));
        }

        $em->persist($updateCron);
        $em->flush();
    }

    /**
     * 測試新增廳主子帳號,id會在兩千萬以外
     */
    public function testNewDomainSub()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameter = array(
            'parent_id' => 3,
            'role' => 7,
            'username' => 'domainsub',
            'password' => 'newpassword',
            'alias' => '測試',
            'sub' => 1
        );

        $client->request('POST', '/api/user', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals('domainsub', $output['ret']['username']);
        $this->assertTrue($output['ret']['sub']);

        $criteria = [
            'domain' => $output['ret']['id'],
            'currency' => 156
        ];
        $dc = $em->find('BBDurianBundle:DomainCurrency', $criteria);
        $this->assertNull($dc);
    }

    /**
     * 測試取得使用者是否線上入款過
     */
    public function testGetDeposited()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/7/deposited');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']);
    }

    /**
     * 測試取得使用者是否線上入款過但使用者不存在
     */
    public function testGetDepositedButUserNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/888/deposited');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010029', $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得使用者是否線上入款過但沒有入款過
     */
    public function testGetDepositedButNoDeposited()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposited');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']);
    }

    /**
     * 測試使用者隱藏測試帳號開
     */
    public function testSetHiddenTestOn()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertFalse($user->isHiddenTest());
        $lastModifiedAt = $user->getModifiedAt();
        $em->clear();

        //先取得測試帳號統計數量
        $client->request('PUT', '/api/user/2/test/1', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $output = $this->getResponse('PUT', '/api/domain/2/total_test');
        $this->assertEquals(2, $output['ret']['total_test']);

        //測試設定會員為隱藏測試帳號
        $client->request('PUT', '/api/user/51/hidden_test/1', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //檢查廳下層測試帳號數量,是否扣除user51
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(1, $totalTest->getTotalTest());
        $em->clear();

        //測試設定大廳主為隱藏測試帳號
        $client->request('PUT', '/api/user/2/hidden_test/1', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret']['id']);
        $this->assertTrue($ret['ret']['hidden_test']);
        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertTrue($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());
        $user = $em->find('BBDurianBundle:User', 3);
        $this->assertTrue($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());
        $user = $em->find('BBDurianBundle:User', 4);
        $this->assertTrue($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());
        $user = $em->find('BBDurianBundle:User', 5);
        $this->assertTrue($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());
        $user = $em->find('BBDurianBundle:User', 6);
        $this->assertTrue($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());
        $user = $em->find('BBDurianBundle:User', 7);
        $this->assertTrue($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertTrue($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());

        //測試非下層的是否有被註記為測試帳號
        $user = $em->find('BBDurianBundle:User', 9);
        $this->assertFalse($user->isHiddenTest());
        $user = $em->find('BBDurianBundle:User', 10);
        $this->assertFalse($user->isHiddenTest());

        //檢查測試帳號數量,是否扣除user8
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(0, $totalTest->getTotalTest());
        $em->clear();

        //測試再次設定大廳主為隱藏測試帳號
        $client->request('PUT', '/api/user/2/hidden_test/1', [], [], $this->headerParam);

        //檢查測試帳號數量不變
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(0, $totalTest->getTotalTest());
    }

    /**
     * 測試使用者隱藏測試帳號關
     */
    public function testSetHiddenTestOff()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user2 = $em->find('BBDurianBundle:User', 2);
        $user2->setHiddenTest(true);

        $user4 = $em->find('BBDurianBundle:User', 4);
        $user4->setHiddenTest(true);

        $user5 = $em->find('BBDurianBundle:User', 5);
        $user5->setHiddenTest(true);

        $user8 = $em->find('BBDurianBundle:User', 8);
        $user8->setHiddenTest(true);

        $user51 = $em->find('BBDurianBundle:User', 51);
        $user51->setHiddenTest(true);
        $em->flush();

        $lastModifiedAt = new \DateTime();

        //測試取消隱藏測試帳號但上層為隱藏測試帳號
        $client->request('PUT', '/api/user/5/hidden_test/0', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Can not set hidden test off when parent is hidden test user', $ret['msg']);
        $this->assertEquals(150010098, $ret['code']);

        $em->clear();

        //先取得測試帳號統計數量
        $client->request('PUT', '/api/user/2/test/1', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $output = $this->getResponse('PUT', '/api/domain/2/total_test');
        $this->assertEquals(0, $output['ret']['total_test']);

        //測試取消會員為隱藏測試
        $client->request('PUT', '/api/user/51/hidden_test/0', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //檢查廳下層測試帳號數量,是否加入user51
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(1, $totalTest->getTotalTest());
        $em->clear();

        //測試取消大廳主隱藏測試
        $client->request('PUT', '/api/user/2/hidden_test/0', [], [], $this->headerParam);

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertFalse($user->isHiddenTest());

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals('ok', $ret['result']);
        $this->assertFalse($ret['ret']['hidden_test']);
        $modifiedAt = new \DateTime($ret['ret']['modified_at']);
        $this->assertGreaterThanOrEqual($lastModifiedAt, $modifiedAt);

        $user = $em->find('BBDurianBundle:User', 2);
        $this->assertFalse($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());
        $user = $em->find('BBDurianBundle:User', 4);
        $this->assertFalse($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());
        $user = $em->find('BBDurianBundle:User', 5);
        $this->assertFalse($user->isHiddenTest());
        $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt());

        //測試非下層的是否有被註記為測試帳號
        $user = $em->find('BBDurianBundle:User', 9);
        $this->assertFalse($user->isHiddenTest());
        $user = $em->find('BBDurianBundle:User', 10);
        $this->assertFalse($user->isHiddenTest());

        //檢查測試帳號數量,是否加入user8
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(2, $totalTest->getTotalTest());
    }

    /**
     * 測試取得使用者的上層id
     */
    public function testGetAncestorId()
    {
        $client = $this->createClient();

        // 搜尋全部
        $client->request('GET', '/api/user/8/ancestor_id');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]);
        $this->assertEquals('3', $output['ret'][1]);
        $this->assertEquals('4', $output['ret'][2]);
        $this->assertEquals('5', $output['ret'][3]);
        $this->assertEquals('6', $output['ret'][4]);
        $this->assertEquals('7', $output['ret'][5]);
        $this->assertEquals('6', $output['pagination']['total']);

        // 帶入分頁做搜尋
        $parameter = [
            'first_result' => 0,
            'max_results'  => 3
        ];

        $client->request('GET', '/api/user/8/ancestor_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]);
        $this->assertEquals('3', $output['ret'][1]);
        $this->assertEquals('4', $output['ret'][2]);
        $this->assertEquals('0', $output['pagination']['first_result']);
        $this->assertEquals('3', $output['pagination']['max_results']);
        $this->assertEquals('6', $output['pagination']['total']);

        // 帶入depth做搜尋
        $parameter = [
            'depth'        => 3,
            'first_result' => 0,
            'max_results'  => 5
        ];

        $client->request('GET', '/api/user/8/ancestor_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('5', $output['ret'][0]);
        $this->assertEquals('0', $output['pagination']['first_result']);
        $this->assertEquals('5', $output['pagination']['max_results']);
        $this->assertEquals('1', $output['pagination']['total']);
    }

    /**
     * 測試取得使用者的上層id但使用者不存在
     */
    public function testGetAncestorIdButUserNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/888/ancestor_id');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010029', $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得使用者的下層id
     */
    public function testGetChildrenId()
    {
        $client = $this->createClient();

        // 搜尋全部
        $client->request('GET', '/api/user/6/children_id');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('7', $output['ret'][0]);
        $this->assertEquals('8', $output['ret'][1]);
        $this->assertEquals('51', $output['ret'][2]);
        $this->assertEquals('3', $output['pagination']['total']);

        // 帶入分頁做搜尋
        $parameter = [
            'first_result' => 0,
            'max_results'  => 1
        ];

        $client->request('GET', '/api/user/6/children_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('7', $output['ret'][0]);
        $this->assertEquals('0', $output['pagination']['first_result']);
        $this->assertEquals('1', $output['pagination']['max_results']);
        $this->assertEquals('3', $output['pagination']['total']);

        // 帶入depth做搜尋
        $parameter = [
            'depth'        => 1,
            'first_result' => 0,
            'max_results'  => 5
        ];

        $client->request('GET', '/api/user/6/children_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('7', $output['ret'][0]);
        $this->assertEquals('0', $output['pagination']['first_result']);
        $this->assertEquals('5', $output['pagination']['max_results']);
        $this->assertEquals('1', $output['pagination']['total']);
    }

    /**
     * 測試取得使用者的下層id但使用者不存在
     */
    public function testGetChildrenIdButUserNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/888/children_id');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010029', $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得被移除的使用者資訊
     */
    public function testGetRemovedUserById()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/removed_user/50');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(50, $output['ret']['userId']);
        $this->assertEquals('vtester2', $output['ret']['username']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('vtester2', $output['ret']['alias']);
    }

    /**
     * 測試取得被移除的使用者資訊，但使用者未被移除
     */
    public function testGetRemovedUserButUserNotRemoved()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/removed_user/123');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010126, $output['code']);
        $this->assertEquals('No such removed user', $output['msg']);
    }

    /**
     * 測試修改使用者信箱
     */
    public function testEditUserEmail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);
        $userEmail->setConfirm(true)
            ->setConfirmAt(new \DateTime('2015-01-14 18:00:00'));
        $em->flush();

        $parameter = ['email' => 'test@yahoo.com'];
        $client->request('PUT', '/api/user/8/email', $parameter);

        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals(8, $out['ret']['user_id']);
        $this->assertEquals('test@yahoo.com', $out['ret']['email']);
        $this->assertFalse($out['ret']['confirm']);
        $this->assertNull($out['ret']['confirm_at']);

        $em->clear();

        // 檢查資料庫
        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);
        $this->assertEquals(8, $userEmail->getUser()->getId());
        $this->assertEquals('test@yahoo.com', $userEmail->getEmail());
        $this->assertFalse($userEmail->isConfirm());
        $this->assertNull($userEmail->getConfirmAt());

        // 由於當下時間可能會和寫入DB的時間有小誤差,所以設兩秒的誤差值來判斷
        $now = new \DateTime("now");

        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertLessThanOrEqual(2, $user->getModifiedAt()->getTimestamp() - $now->getTimestamp());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_email', $logOp->getTableName());
        $this->assertEquals('@user_id:8', $logOp->getMajorKey());
        $this->assertContains(
            '@email:Davinci@chinatown.com=>test@yahoo.com, @confirm:true=>false, @confirm_at:2015-01-14 18:00:00=>',
            $logOp->getMessage()
        );

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);

        $this->assertEquals('user', $logOp2->getTableName());
        $this->assertEquals('@id:8', $logOp2->getMajorKey());
        $this->assertContains('@modified_at:', $logOp2->getMessage());
    }

    /**
     * 測試修改使用者信箱為空
     */
    public function testEditUserEmailWithEmpty()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameter = ['email' => ''];
        $client->request('PUT', '/api/user/8/email', $parameter);

        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals(8, $out['ret']['user_id']);
        $this->assertEmpty($out['ret']['email']);
        $this->assertFalse($out['ret']['confirm']);
        $this->assertNull($out['ret']['confirm_at']);

        // 檢查資料庫
        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);
        $this->assertEquals(8, $userEmail->getUser()->getId());
        $this->assertEmpty($userEmail->getEmail());
        $this->assertFalse($userEmail->isConfirm());
        $this->assertNull($userEmail->getConfirmAt());

        // 由於當下時間可能會和寫入DB的時間有小誤差,所以設兩秒的誤差值來判斷
        $now = new \DateTime("now");

        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertLessThanOrEqual(2, $user->getModifiedAt()->getTimestamp() - $now->getTimestamp());
    }

    /**
     * 測試修改使用者信箱前後相同，且不需要驗證
     */
    public function testEditUserEmailWithSameUserEmail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameter = ['email' => 'Davinci@chinatown.com', 'verify' => 0];
        $client->request('PUT', '/api/user/8/email', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('Davinci@chinatown.com', $output['ret']['email']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertNull($output['ret']['confirm_at']);

        // 檢查資料庫
        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);
        $this->assertEquals(8, $userEmail->getUser()->getId());
        $this->assertEquals('Davinci@chinatown.com', $userEmail->getEmail());
        $this->assertFalse($userEmail->isConfirm());
        $this->assertNull($userEmail->getConfirmAt());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOp);
    }

    /**
     * 測試新增使用者未帶userDetail則不檢查黑名單
     */
    public function testCreateUserWithoutUserDetail()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen1234',
            'role' => '1',
            'verify_blacklist' => 1
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試新增使用者但ip在黑名單中
     */
    public function testCreateUserButIpInBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen1234',
            'role' => '1',
            'user_detail' => ['telephone' => '123456'],
            'client_ip' => '115.195.41.247',
            'verify_blacklist' => 1
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650020, $output['code']);
        $this->assertEquals('This ip has been blocked', $output['msg']);
    }

    /**
     * 測試手機新增使用者但ip在系統封鎖黑名單中
     */
    public function testCreateUserByMobileButIpInSystemLockBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen1234',
            'role' => '1',
            'user_detail' => ['telephone' => '123456'],
            'client_ip' => '115.195.41.247',
            'verify_blacklist' => 1,
            'ingress' => 2
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
    }

    /**
     * 測試新增使用者跳過檢查黑名單
     */
    public function testCreateUserWithoutCheckBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen1234',
            'role' => '1',
            'user_detail' => ['email' => 'blackemail@tmail.com'],
            'verify_blacklist' => 0
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['parent']);
        $this->assertEquals('chosen1', $output['ret']['username']);
        $this->assertEquals('chosen1234', $output['ret']['alias']);
        $this->assertEquals(20000001, $output['ret']['user_detail']['user_id']);
        $this->assertEquals('blackemail@tmail.com', $output['ret']['user_detail']['email']);
    }

    /**
     * 測試手機新增使用者，ip在黑名單中但不檢查
     */
    public function testMobileCreateUserButNotVerify()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen1234',
            'role' => '1',
            'user_detail' => ['telephone' => '123456'],
            'client_ip' => '115.195.41.247',
            'ingress' => '2'
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['parent']);
        $this->assertEquals('chosen1', $output['ret']['username']);
        $this->assertEquals('chosen1234', $output['ret']['alias']);
        $this->assertEquals(20000001, $output['ret']['user_detail']['user_id']);
        $this->assertEquals('123456', $output['ret']['user_detail']['telephone']);
    }

    /**
     * 測試新增會員,但上層皆沒有預設層級
     */
    public function testNewUserButPresetLevelNotFound()
    {
        // 刪除使用者上層所有的預設層級
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pl2 = $em->find('BBDurianBundle:PresetLevel', 2);
        $pl3 = $em->find('BBDurianBundle:PresetLevel', 3);
        $pl5 = $em->find('BBDurianBundle:PresetLevel', 5);
        $pl10 = $em->find('BBDurianBundle:PresetLevel', 10);

        $em->remove($pl2);
        $em->remove($pl3);
        $em->remove($pl5);
        $em->remove($pl10);

        $em->flush();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen1234567890123456789',
            'role' => '1',
            'currency' => 'TWD',
            'cash' => ['currency' => 'CNY'],
            'user_detail' => [
                'email' => 'Davanci@yahoo.com',
                'nickname' => 'MG149',
                'name_real' => 'DaVanCi',
                'name_chinese' => '文希哥',
                'name_english' => 'DaVanCi',
                'country' => 'ROC',
                'passport' => 'PA123456',
                'birthday' => '2001-01-01',
                'telephone' => '3345678',
                'qq_num' => '485163154787',
                'password' => '123456',
                'note' => 'Hello durian'
            ],
            'client_ip' => '127.0.0.1'
        ];

        $client = $this->createClient();
        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150010135', $output['code']);
        $this->assertEquals('No PresetLevel found', $output['msg']);
    }

    /**
     * 測試新增會員時，現金幣別代碼錯誤
     */
    public function testNewUserWithWrongCashCurrencyCode()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 7,
            'username' => 'ccurrency',
            'password' => 'ccurrency',
            'alias' => 'ccurrency',
            'role' => 1,
            'currency' => 'CNY',
            'cash' => ['currency' => 'Nothing']
        ];

        $client->request('POST', '/api/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Illegal currency', $output['msg']);
        $this->assertEquals(150010101, $output['code']);
    }

    /**
     * 測試新增會員時，快開額度幣別代碼錯誤
     */
    public function testNewUserWithWrongCashFakeCurrencyCode()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 7,
            'username' => 'cfcurrency',
            'password' => 'cfcurrency',
            'alias' => 'cfcurrency',
            'role' => 1,
            'currency' => 'CNY',
            'cash_fake' => [
                'currency' => 'Nothing',
                'balance' => 100
            ]
        ];

        $client->request('POST', '/api/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Illegal currency', $output['msg']);
        $this->assertEquals(150010101, $output['code']);
    }

    /**
     * 測試新增會員時，現金幣別代碼不存在
     */
    public function testNewUserWithoutCashCurrencyCode()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 7,
            'username' => 'ccurrency',
            'password' => 'ccurrency',
            'alias' => 'ccurrency',
            'role' => 1,
            'currency' => 'CNY',
            'cash' => []
        ];

        $client->request('POST', '/api/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No currency specified', $output['msg']);
        $this->assertEquals(150010141, $output['code']);
    }

    /**
     * 測試新增會員時，快開額度幣別代碼不存在
     */
    public function testNewUserWithoutCashFakeCurrencyCode()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 7,
            'username' => 'cfcurrency',
            'password' => 'cfcurrency',
            'alias' => 'cfcurrency',
            'role' => 1,
            'currency' => 'CNY',
            'cash_fake' => [
                'balance' => 100
            ]
        ];

        $client->request('POST', '/api/user', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No currency specified', $output['msg']);
        $this->assertEquals(150010141, $output['code']);
    }

    /**
     * 測試產生使用者id但ip在黑名單中
     */
    public function testGenerateUserIdButIpIsInBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '115.195.41.247',
            'verify_blacklist' => 1
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010142, $output['code']);
        $this->assertEquals('Cannot create user when ip is in blacklist', $output['msg']);
    }

    /**
     * 測試產生使用者id跳過檢查黑名單
     */
    public function testGenerateUserIdWithoutCheckBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '115.195.41.247',
            'verify_blacklist' => 0
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['user_id']);
    }

    /**
     * 測試手機產生使用者id，不需檢查系統封鎖黑名單
     */
    public function testMobileGenerateUserIdWithoutCheckSystemLockBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'role' => '1',
            'domain' => 2,
            'client_ip' => '115.195.41.247',
            'verify_blacklist' => 1,
            'ingress' => 2
        ];

        $client->request('GET', '/api/user/id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['user_id']);
    }

    /**
     * 測試修改使用者信箱，但想修改的信箱在黑名單中
     */
    public function testEditUserEmailButEmailInBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'email' => 'blackemail@tmail.com',
            'verify_blacklist' => 1
        ];

        $client->request('PUT', '/api/user/8/email', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650019, $output['code']);
        $this->assertEquals('This email has been blocked', $output['msg']);
    }

    /**
     * 測試修改使用者信箱跳過檢查黑名單
     */
    public function testEditUserEmailWithoutCheckBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'email' => 'blackemail@tmail.com',
            'verify_blacklist' => 0
        ];

        $client->request('PUT', '/api/user/8/email', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('blackemail@tmail.com', $output['ret']['email']);
    }

    /**
     * 測試取得使用者信箱
     */
    public function testUserEmail()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/email');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret']['user_id']);
        $this->assertEquals('Davinci@chinatown.com', $output['ret']['email']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertEquals('2015-03-27T09:12:53+0800', $output['ret']['confirm_at']);
    }

    /**
     * 測試取得使用者信箱但使用者不存在
     */
    public function testGetEmailButNoSuchUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/1/email');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010029, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試根據使用者id，取得使用者出入款統計資料
     */
    public function testGetStat()
    {
        $client = $this->createClient();

        $parameters = [
            'user_id' => [6, 6, 7, 8]
        ];

        $client->request('GET', '/api/user/stat', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(6, $output['ret'][0]['user_id']);
        $this->assertEquals(0, $output['ret'][0]['deposit_count']);
        $this->assertEquals(0, $output['ret'][0]['deposit_total']);
        $this->assertEquals(0, $output['ret'][0]['deposit_max']);
        $this->assertEquals(0, $output['ret'][0]['remit_count']);
        $this->assertEquals(0, $output['ret'][0]['remit_total']);
        $this->assertEquals(0, $output['ret'][0]['remit_max']);
        $this->assertEquals(1, $output['ret'][0]['manual_count']);
        $this->assertEquals(50, $output['ret'][0]['manual_total']);
        $this->assertEquals(50, $output['ret'][0]['manual_max']);
        $this->assertEquals(1, $output['ret'][0]['withdraw_count']);
        $this->assertEquals(50, $output['ret'][0]['withdraw_total']);
        $this->assertEquals(50, $output['ret'][0]['withdraw_max']);

        $this->assertEquals(7, $output['ret'][1]['user_id']);
        $this->assertEquals(3, $output['ret'][1]['deposit_count']);
        $this->assertEquals(600, $output['ret'][1]['deposit_total']);
        $this->assertEquals(300, $output['ret'][1]['deposit_max']);
        $this->assertEquals(3, $output['ret'][1]['remit_count']);
        $this->assertEquals(600, $output['ret'][1]['remit_total']);
        $this->assertEquals(300, $output['ret'][1]['remit_max']);
        $this->assertEquals(3, $output['ret'][1]['manual_count']);
        $this->assertEquals(600, $output['ret'][1]['manual_total']);
        $this->assertEquals(300, $output['ret'][1]['manual_max']);
        $this->assertEquals(4, $output['ret'][1]['withdraw_count']);
        $this->assertEquals(423, $output['ret'][1]['withdraw_total']);
        $this->assertEquals(185, $output['ret'][1]['withdraw_max']);

        $this->assertEquals(8, $output['ret'][2]['user_id']);
        $this->assertEquals(0, $output['ret'][2]['deposit_count']);
        $this->assertEquals(0, $output['ret'][2]['deposit_total']);
        $this->assertEquals(0, $output['ret'][2]['deposit_max']);
        $this->assertEquals(0, $output['ret'][2]['remit_count']);
        $this->assertEquals(0, $output['ret'][2]['remit_total']);
        $this->assertEquals(0, $output['ret'][2]['remit_max']);
        $this->assertEquals(0, $output['ret'][2]['manual_count']);
        $this->assertEquals(0, $output['ret'][2]['manual_total']);
        $this->assertEquals(0, $output['ret'][2]['manual_max']);
        $this->assertEquals(3, $output['ret'][2]['withdraw_count']);
        $this->assertEquals(255, $output['ret'][2]['withdraw_total']);
        $this->assertEquals(135, $output['ret'][2]['withdraw_max']);

        $this->assertEquals(3, count($output['ret']));
    }

    /**
     * 測試取得多個被刪除使用者資訊
     */
    public function testGetRemovedUsers()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = $em->find('BBDurianBundle:User', 50);
        $userDetail = $em->find('BBDurianBundle:UserDetail', $user);

        $birth = new \DateTime('2000-10-10');
        $userDetail->setBirthday($birth);

        $removedUser = $em->find('BBDurianBundle:RemovedUser', $user);
        $removedDetail = $em->getRepository('BBDurianBundle:RemovedUserDetail')->findOneBy(['removedUser' => 50]);
        $em->remove($removedDetail);
        $em->flush();

        $removedUserDetail = new RemovedUserDetail($removedUser, $userDetail);

        $em->persist($removedUserDetail);
        $em->flush();

        $parameters = [
            'users' => ['8', '50'],
            'detail' => true
        ];

        $client->request('GET', '/api/removed_users', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(50, $output['ret'][0]['user_id']);
        $this->assertEquals(2, $output['ret'][0]['parent_id']);
        $this->assertEquals('vtester2', $output['ret'][0]['username']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('vtester2', $output['ret'][0]['alias']);
        $this->assertEquals('2000-10-10', $output['ret'][0]['birthday']);
        $this->assertFalse($output['ret'][0]['sub']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['block']);
        $this->assertFalse($output['ret'][0]['bankrupt']);
        $this->assertFalse($output['ret'][0]['test']);
        $this->assertEquals(0, $output['ret'][0]['size']);
        $this->assertEquals(0, $output['ret'][0]['err_num']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertArrayHasKey('created_at', $output['ret'][0]);
        $this->assertArrayHasKey('modified_at', $output['ret'][0]);
        $this->assertEmpty($output['ret'][0]['last_login']);
        $this->assertEmpty($output['ret'][0]['last_bank']);
        $this->assertEquals(7, $output['ret'][0]['role']);

        $this->assertEmpty($output['ret'][0]['email']);
        $this->assertEmpty($output['ret'][0]['name_real']);
        $this->assertEmpty($output['ret'][0]['identity_card']);
        $this->assertEmpty($output['ret'][0]['telephone']);
        $this->assertEmpty($output['ret'][0]['qq_num']);
        $this->assertEmpty($output['ret'][0]['note']);

        // 未刪除使用者則回傳空陣列
        $parameters = [
            'users' => ['9'],
            'detail' => true
        ];
        $client->request('GET', '/api/removed_users', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得多個被刪除使用者資訊，但帶不同廳的使用者id
     */
    public function testGetRemovedUsersActionButIdsNotInSameDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 9);
        $removedUser = new RemovedUser($user);
        $em->persist($removedUser);
        $em->flush();

        $parameters = [
            'users' => ['8', '9', '50'],
            'detail' => true
        ];
        $client->request('GET', '/api/removed_users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('User ids must be in the same domain', $output['msg']);
        $this->assertEquals(150010145, $output['code']);
    }

    /**
     * 測試新增使用者Email長度過長
     */
    public function testCreateUserWithInvalidEmailLength()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '7',
            'username' => 'checklength',
            'password' => 'checklength',
            'alias' => 'chosen1234',
            'role' => '1',
            'user_detail' => ['email' => 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
                . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
                . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
                . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
                . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
                . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com']
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010146, $output['code']);
        $this->assertEquals('Invalid email length given', $output['msg']);
    }

    /**
     * 測試取得使用者名稱
     */
    public function testGetUsername()
    {
        $client = $this->createClient();

        $parameters = [
            'users' => [1, 20000000, 2]
        ];

        $client->request('GET', '/api/users/username', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('company', $output['ret'][0]['username']);
        $this->assertEquals(20000000, $output['ret'][1]['id']);
        $this->assertEquals('domain20m', $output['ret'][1]['username']);
    }

    /**
     * 測試新增使用者但ip在ip封鎖列表中
     */
    public function testCreateUserButIpInIpBlacklist()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config->setBlockCreateUser(true);
        $emShare->flush();

        $parameters = [
            'parent_id' => '7',
            'username' => 'chosen1',
            'password' => 'chosen1',
            'alias' => 'chosen1234',
            'role' => '1',
            'user_detail' => ['telephone' => '123456'],
            'client_ip' => '126.0.0.1',
            'verify_blacklist' => 1,
            'verify_ip' => 1
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010172, $output['code']);
        $this->assertEquals('Cannot create user when ip is in ip blacklist', $output['msg']);
    }

    /**
     * 測試使用者是否為租卡體系
     */
    public function testGetMultipleUserInfoForRent()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $parameters = [
            'users' => ['2', '3', '4', '8'],
            'fields' => ['id', 'rent']
        ];

        $user2 = $em->find('BBDurianBundle:User', 2);
        $user3 = $em->find('BBDurianBundle:User', 3);
        $user4 = $em->find('BBDurianBundle:User', 4);
        $user8 = $em->find('BBDurianBundle:User', 8);

        $this->assertFalse($user2->isRent());
        $this->assertFalse($user3->isRent());
        $this->assertFalse($user4->isRent());
        $this->assertFalse($user8->isRent());

        $user2->setRent(true);
        $em->flush();

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 一般資訊
        $this->assertEquals('ok', $output['result']);

        $this->assertEquals($user2->getId(), $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['rent']);

        $this->assertEquals($user3->getId(), $output['ret'][1]['id']);
        $this->assertTrue($output['ret'][1]['rent']);

        $this->assertEquals($user4->getId(), $output['ret'][2]['id']);
        $this->assertTrue($output['ret'][2]['rent']);

        $this->assertEquals($user8->getId(), $output['ret'][3]['id']);
        $this->assertTrue($output['ret'][3]['rent']);
    }
}
