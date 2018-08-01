<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\DomainController;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Entity\OutsidePayway;
use Buzz\Message\Response;

class DomainFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainTotalTestData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadIpBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('user_seq', 20000000);

        $this->clearSensitiveLog();

        $sensitiveData = 'entrance=6&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=UserFunctionsTest.php&operator_id=&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
    }

    /**
     * 測試取得廳主列表
     */
    public function testGetDomainList()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('company', $output['ret'][0]['username']);
        $this->assertEquals('company', $output['ret'][0]['alias']);
        $this->assertEquals(true, $output['ret'][0]['enable']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);

        $this->assertEquals(9, $output['ret'][1]['id']);
        $this->assertEquals('isolate', $output['ret'][1]['username']);
        $this->assertEquals('isolate', $output['ret'][1]['alias']);
        $this->assertEquals(true, $output['ret'][1]['enable']);
        $this->assertEquals('ag', $output['ret'][1]['login_code']);

        // 新增一筆大球廳主
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $userBb = new User();
        $userBb->setId(20000007);
        $userBb->setUsername('obama');
        $userBb->setAlias('obama');
        $userBb->setPassword('123456');
        $userBb->setDomain(20000007);
        $userBb->setRole(7);
        $em->persist($userBb);

        $config = new DomainConfig($userBb, 'domainbb', 'bb');
        $emShare->persist($config);

        // 新增一筆停用的廳主
        $user11 = new User();
        $user11->setId(11);
        $user11->setUsername('mesarati');
        $user11->setAlias('mesarati');
        $user11->setPassword('123456');
        $user11->setDomain(11);
        $user11->setRole(7);
        $user11->disable();
        $em->persist($user11);

        $config = new DomainConfig($user11, 'mesarati', 'ms');
        $emShare->persist($config);

        $em->flush();
        $emShare->flush();

        // enable 0, 取停用
        $params = ['enable' => 0];
        $client->request('GET', '/api/domain', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(11, $output['ret'][0]['id']);
        $this->assertEquals('mesarati', $output['ret'][0]['username']);
        $this->assertEquals('mesarati', $output['ret'][0]['alias']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertEquals('ms', $output['ret'][0]['login_code']);

        // filter 1, 只抓整合, 濾掉大球
        $params = ['filter' => 1];
        $client->request('GET', '/api/domain', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('company', $output['ret'][0]['username']);
        $this->assertEquals('company', $output['ret'][0]['alias']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);

        $this->assertEquals(9, $output['ret'][1]['id']);
        $this->assertEquals('isolate', $output['ret'][1]['username']);
        $this->assertEquals('isolate', $output['ret'][1]['alias']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertEquals('ag', $output['ret'][1]['login_code']);

        // filter 2, 只抓大球
        $params = ['filter' => 2];
        $client->request('GET', '/api/domain', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000007, $output['ret'][3]['id']);
        $this->assertEquals('obama', $output['ret'][3]['username']);
        $this->assertEquals('obama', $output['ret'][3]['alias']);
        $this->assertTrue($output['ret'][3]['enable']);
        $this->assertEquals('bb', $output['ret'][3]['login_code']);
    }

    /**
     * 測試domain可用的幣別
     */
    public function testDomainGetCurrency()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/domain/2/currency');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertTrue($output['ret'][0]['preset']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertFalse($output['ret'][0]['is_virtual']);
        $this->assertEquals('HKD', $output['ret'][1]['currency']);
        $this->assertEquals('USD', $output['ret'][2]['currency']);
        $this->assertEquals('TWD', $output['ret'][3]['currency']);
        $this->assertFalse(isset($output['ret'][4]));
    }

    /**
     * 測試取得廳主列表時遺失登入代碼與廳名會傳空字串
     */
    public function testDomainListMissingLoginCodeAndName()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $domainConfig = $em->find('BBDurianBundle:DomainConfig', 9);
        $em->remove($domainConfig);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);
        $this->assertEquals('domain2', $output['ret'][0]['name']);

        $this->assertEquals(9, $output['ret'][1]['id']);
        $this->assertEquals('', $output['ret'][1]['login_code']);
        $this->assertEquals('', $output['ret'][1]['name']);
    }

    /**
     * 測試設定domain可用的幣別
     */
    public function testDomainSetCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $currency = $this->getContainer()->get('durian.currency');

        $params = [
            'currencies' => [
                'EUR',
                'HKD',
                'IDR',
                'JPY',
                'SGD'
            ]
        ];
        $client->request('PUT', '/api/domain/2/currency', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查DB資料
        $dc = $em->getRepository('BBDurianBundle:DomainCurrency')
                 ->findBy(array('domain' => 2));

        $code0 = $currency->getMappedCode($dc[0]->getCurrency());
        $this->assertEquals($code0, $output['ret'][0]['currency']);
        $code2 = $currency->getMappedCode($dc[2]->getCurrency());
        $this->assertEquals($code2, $output['ret'][2]['currency']);
        $this->assertFalse(isset($output['ret'][5]));
        $this->assertFalse(isset($dc[5]));

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_currency', $logOp->getTableName());
        $this->assertEquals('@domain:2', $logOp->getMajorKey());
        $this->assertEquals(
            '@currency:156, 344, 840, 901=>344, 360, 392, 702, 978',
            $logOp->getMessage()
        );
    }

    /**
     * 測試設定幣別domain不存在
     */
    public function testDomainSetCurrencyWithEmptyDomain()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = array(
            'currencies' => array()
        );
        $client->request('PUT', '/api/domain/5566/currency', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360006, $output['code']);
        $this->assertEquals('No such user', $output['msg']);

        // 噴錯不寫操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試設定幣別傳入不是domain
     */
    public function testDomainSetCurrencyWithNotDomain()
    {
        $client = $this->createClient();

        $params = array(
            'currencies' => array()
        );
        $client->request('PUT', '/api/domain/3/currency', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360007, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試domain設定一樣的幣別
     */
    public function testDomainSetSameCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $currency = $this->getContainer()->get('durian.currency');

        // 先取出原本的設定
        $dc = $em->getRepository('BBDurianBundle:DomainCurrency')
                 ->findBy(array('domain' => 2));

        $params = [
            'currencies' => [
                'HKD',
                'TWD',
                'USD',
                'CNY'
            ]
        ];
        $client->request('PUT', '/api/domain/2/currency', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查DB資料與原本設定一樣未被修改
        $code0 = $currency->getMappedCode($dc[0]->getCurrency());
        $this->assertEquals($code0, $output['ret'][0]['currency']);
        $code2 = $currency->getMappedCode($dc[2]->getCurrency());
        $this->assertEquals($code2, $output['ret'][2]['currency']);
        $this->assertFalse(isset($output['ret'][4]));
        $this->assertFalse(isset($dc[4]));

        // 未變動不寫操作紀錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試domain設定預設幣別
     */
    public function testDomainSetCurrencyPreset()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/domain/2/currency/TWD/preset');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('TWD', $output['ret']['currency']);
        $this->assertTrue($output['ret']['preset']);
        $this->assertFalse($output['ret']['is_virtual']);

        // 檢查DB資料，原本的預設幣別要被關閉。
        $criteria = array(
            'domain' => 2,
            'currency' => 156
        );
        $dc = $em->getRepository('BBDurianBundle:DomainCurrency')->findOneBy($criteria);
        $this->assertFalse($dc->isPreset());

        // 操作紀錄檢查
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_currency', $logOp1->getTableName());
        $this->assertEquals('@domain:2, @currency:156', $logOp1->getMajorKey());
        $this->assertEquals('@preset:true=>false', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('domain_currency', $logOp2->getTableName());
        $this->assertEquals('@domain:2, @currency:901', $logOp2->getMajorKey());
        $this->assertEquals('@preset:false=>true', $logOp2->getMessage());
    }

    /**
     * 測試domain設定預設幣別系統不支援
     */
    public function testDomainSetCurrencyPresetDurianNotSupport()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/domain/2/currency/TTB/preset');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360009, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);

        // 噴錯不寫操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試domain設定預設幣別domain不支援
     */
    public function testDomainSetCurrencyPresetDomainNotSupport()
    {
        $client = $this->createClient();
        $client->request('PUT', '/api/domain/2/currency/EUR/preset');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360010, $output['code']);
        $this->assertEquals('Domain not support this currency', $output['msg']);
    }

    /**
     * 測試取得單一廳主
     */
    public function testGetDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals('company', $output['ret']['username']);
        $this->assertEquals('company', $output['ret']['alias']);
        $this->assertEquals(true, $output['ret']['enable']);
        $this->assertEquals('domain2', $output['ret']['name']);
    }

    /**
     * 測試取得單一廳主時，使用者不存在
     */
    public function testGetDomainNoUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360006, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得單一廳主時，廳的設定不存在
     */
    public function testGetDomainNoConfig()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $config = $em->find('BBDurianBundle:DomainConfig', 2);
        $em->remove($config);
        $em->flush();

        $client->request('GET', '/api/domain/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360004, $output['code']);
        $this->assertEquals('No domain config found', $output['msg']);
    }

    /**
     * 測試取得單一廳主時，不是廳主
     */
    public function testGetDomainNotDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360007, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試取得單一登入代碼
     */
    public function testGetLoginCode()
    {
        $client = $this->createClient();

        $params = ['domain' => 2];
        $client->request('GET', '/api/domain/login_code', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('cm', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['removed']);
    }

    /**
     * 測試取得單一登入代碼
     */
    public function testGetLoginCodeWithCode()
    {
        $client = $this->createClient();

        $params = ['code' => 'cm'];
        $client->request('GET', '/api/domain/login_code', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('cm', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['removed']);
    }

    /**
     * 測試取得所有登入代碼
     */
    public function testGetAllLoginCode()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/login_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('cm', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertEquals('th', $output['ret'][1]['code']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertEquals(9, $output['ret'][2]['domain']);
        $this->assertEquals('ag', $output['ret'][2]['code']);
        $this->assertFalse($output['ret'][0]['removed']);
    }

    /**
     * 測試取得登入代碼時代入無效的domain
     */
    public function testGetLoginCodeWithInvalidDomain()
    {
        $client = $this->createClient();

        $params = ['domain' => 55688];
        $client->request('GET', '/api/domain/login_code', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得單一登入代碼
     */
    public function testGetDomainWithLoginCode()
    {
        $client = $this->createClient();

        $params = ['login_code' => 'cm'];
        $client->request('GET', '/api/domain/login_code', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('cm', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['removed']);
    }

    /**
     * 測試修改登入代碼
     */
    public function testSetLoginCode()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = ['code' => 'mc'];
        $client->request('PUT', '/api/domain/2/login_code', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('mc', $output['ret']['code']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_config', $logOp->getTableName());
        $this->assertEquals('@domain:2', $logOp->getMajorKey());
        $this->assertEquals('@login_code:cm=>mc', $logOp->getMessage());
    }

    /**
     * 測試修改登入代碼已重複
     */
    public function testSetLoginCodeAlreadyExist()
    {
        $client = $this->createClient();

        $params = ['code' => 'cm'];
        $client->request('PUT', '/api/domain/2/login_code', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360011, $output['code']);
        $this->assertEquals('Login code already exists', $output['msg']);
    }

    /**
     * 測試修改登入代碼不存在
     */
    public function testSetLoginCodeNotDomain()
    {
        $client = $this->createClient();

        $params = ['code' => 'mm'];
        $client->request('PUT', '/api/domain/1/login_code', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360004, $output['code']);
        $this->assertEquals('No domain config found', $output['msg']);
    }

    /**
     * 測試設定Domain相關設定
     */
    public function testSetConfig()
    {
        $client = $this->createClient();

        // 測試資料庫已存在該筆domain資料
        $params = [
            'block_create_user' => '1',
            'block_login'       => '1',
            'block_test_user'   => '1',
            'login_code'        => 'as',
            'name'              => 'lala',
            'verify_otp'        => '1'
        ];
        $client->request('PUT', '/api/domain/2/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertTrue($output['ret']['block_create_user']);
        $this->assertTrue($output['ret']['block_login']);
        $this->assertTrue($output['ret']['block_test_user']);
        $this->assertEquals('as', $output['ret']['login_code']);
        $this->assertEquals('lala', $output['ret']['name']);
        $this->assertTrue($output['ret']['verify_otp']);

        // 操作紀錄檢查
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_config', $logOp->getTableName());
        $this->assertEquals('@domain:2', $logOp->getMajorKey());
        $logMessage = '@block_create_user:false=>true, @block_login:false=>true, '
            . '@block_test_user:false=>true, @login_code:cm=>as, @name:domain2=>lala, '
            . '@verify_otp:false=>true';
        $this->assertEquals($logMessage, $logOp->getMessage());

        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $this->assertEquals('lala', $config->getName());
        $this->assertEquals('as', $config->getLoginCode());
        $this->assertTrue($config->isBlockCreateUser());
        $this->assertTrue($config->isBlockTestUser());
        $this->assertTrue($config->isBlockLogin());
        $this->assertTrue($config->isVerifyOtp());
    }

    /**
     * 測試設定不存在的Domain
     */
    public function testSetNonExistConfig()
    {
        $client = $this->createClient();

        $params = ['login_code' => 'as'];
        $client->request('PUT', '/api/domain/100/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360004, $output['code']);
        $this->assertEquals('No domain config found', $output['msg']);
    }

    /**
     * 測試回傳Domain相關設定
     */
    public function testGetConfig()
    {
        $client = $this->createClient();

        $params = [
            'domain' => '2',
            'block_create_user' => '0',
            'block_login' => '0',
            'block_test_user' => '0',
            'login_code' => 'cm',
            'name' => 'domain2',
            'verify_otp' => 0,
            'enable' => 1,
            'filter' => 1
        ];
        $client->request('GET', '/api/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertFalse($output['ret'][0]['block_create_user']);
        $this->assertFalse($output['ret'][0]['block_login']);
        $this->assertFalse($output['ret'][0]['block_test_user']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);
        $this->assertEquals('domain2', $output['ret'][0]['name']);
        $this->assertFalse($output['ret'][0]['verify_otp']);
        $this->assertTrue($output['ret'][0]['enable']);
    }

    /**
     * 測試指定回傳大球Domain相關設定
     */
    public function testGetConfigWithBigBallDomain()
    {
        $client = $this->createClient();

        $params = ['filter' => 2];

        $client->request('GET', '/api/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試依blockCreateUser回傳Domain相關設定
     */
    public function testGetConfigWithBlockCreateUser()
    {
        $client = $this->createClient();

        $params = ['block_create_user' => '0'];
        $client->request('GET', '/api/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertFalse($output['ret'][0]['block_create_user']);
        $this->assertFalse($output['ret'][0]['block_login']);
        $this->assertFalse($output['ret'][0]['block_test_user']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);

        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertFalse($output['ret'][1]['block_create_user']);
        $this->assertFalse($output['ret'][1]['block_login']);
        $this->assertFalse($output['ret'][1]['block_test_user']);
        $this->assertEquals('th', $output['ret'][1]['login_code']);

        $this->assertEquals(9, $output['ret'][2]['domain']);
        $this->assertFalse($output['ret'][2]['block_create_user']);
        $this->assertFalse($output['ret'][2]['block_login']);
        $this->assertFalse($output['ret'][2]['block_test_user']);
        $this->assertEquals('ag', $output['ret'][2]['login_code']);
    }

    /**
     * 測試依blockLogin回傳Domain相關設定
     */
    public function testGetConfigWithBlockLogin()
    {
        $client = $this->createClient();

        $params = ['block_login' => '0'];
        $client->request('GET', '/api/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertFalse($output['ret'][0]['block_create_user']);
        $this->assertFalse($output['ret'][0]['block_login']);
        $this->assertFalse($output['ret'][0]['block_test_user']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);

        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertFalse($output['ret'][1]['block_create_user']);
        $this->assertFalse($output['ret'][1]['block_login']);
        $this->assertFalse($output['ret'][1]['block_test_user']);
        $this->assertEquals('th', $output['ret'][1]['login_code']);

        $this->assertEquals(9, $output['ret'][2]['domain']);
        $this->assertFalse($output['ret'][2]['block_create_user']);
        $this->assertFalse($output['ret'][2]['block_login']);
        $this->assertFalse($output['ret'][2]['block_test_user']);
        $this->assertEquals('ag', $output['ret'][2]['login_code']);
    }

    /**
     * 測試依blockTestUser回傳Domain相關設定
     */
    public function testGetConfigWithBlockTestUser()
    {
        $client = $this->createClient();

        $params = ['block_test_user' => '0'];
        $client->request('GET', '/api/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertFalse($output['ret'][0]['block_create_user']);
        $this->assertFalse($output['ret'][0]['block_login']);
        $this->assertFalse($output['ret'][0]['block_test_user']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);

        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertFalse($output['ret'][1]['block_create_user']);
        $this->assertFalse($output['ret'][1]['block_login']);
        $this->assertFalse($output['ret'][1]['block_test_user']);
        $this->assertEquals('th', $output['ret'][1]['login_code']);

        $this->assertEquals(9, $output['ret'][2]['domain']);
        $this->assertFalse($output['ret'][2]['block_create_user']);
        $this->assertFalse($output['ret'][2]['block_login']);
        $this->assertFalse($output['ret'][2]['block_test_user']);
        $this->assertEquals('ag', $output['ret'][2]['login_code']);
    }

    /**
     * 測試依loginCode回傳Domain相關設定
     */
    public function testGetConfigWithLoginCode()
    {
        $client = $this->createClient();

        $params = ['login_code' => 'ag'];
        $client->request('GET', '/api/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret'][0]['domain']);
        $this->assertFalse($output['ret'][0]['block_create_user']);
        $this->assertFalse($output['ret'][0]['block_login']);
        $this->assertFalse($output['ret'][0]['block_test_user']);
        $this->assertEquals('ag', $output['ret'][0]['login_code']);
    }

    /**
     * 測試回傳Domain相關設定
     */
    public function testGetConfigV2()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = new User();
        $user->setId(20000010);
        $user->setUsername('lala');
        $user->setAlias('lala');
        $user->setPassword('123456');
        $user->setDomain(20000010);
        $user->setRole(7);
        $em->persist($user);
        $user->enable();
        $em->flush();

        $config = new DomainConfig(20000010, 'domain10', 'vm');
        $emShare->persist($config);
        $emShare->flush();

        $params = [
            'block_create_user' => 0,
            'block_login' => 0,
            'block_test_user' => 0,
            'login_code' => 'cm',
            'name' => 'domain2',
            'verify_otp' => 0,
            'enable' => 1
        ];
        $client->request('GET', '/api/v2/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('domain2', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['block_create_user']);
        $this->assertFalse($output['ret'][0]['block_login']);
        $this->assertFalse($output['ret'][0]['block_test_user']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);

        $params['filter'] = 2;
        $client->request('GET', '/api/v2/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $params = [
            'block_create_user' => 0,
            'block_login' => 0,
            'block_test_user' => 0,
            'login_code' => 'vm',
            'name' => 'domain10',
            'verify_otp' => 0,
            'enable' => 1,
            'filter' => 1
        ];
        $client->request('GET', '/api/v2/domain/config', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試回傳指定Domain相關設定
     */
    public function testGetConfigByDomain()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = new User();
        $user->setId(20000010);
        $user->setUsername('lala');
        $user->setAlias('lala');
        $user->setPassword('123456');
        $user->setDomain(20000010);
        $user->setRole(7);
        $em->persist($user);
        $user->enable();
        $em->flush();

        $config = new DomainConfig(20000010, 'domain10', 'vm');
        $emShare->persist($config);
        $emShare->flush();

        $params = [
            'domain' => 2,
            'block_create_user' => 0,
            'block_login' => 0,
            'block_test_user' => 0,
            'login_code' => 'cm',
            'name' => 'domain2',
            'verify_otp' => 0,
        ];
        $client->request('GET', '/api/domain/config_by_domain', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('domain2', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['block_create_user']);
        $this->assertFalse($output['ret'][0]['block_login']);
        $this->assertFalse($output['ret'][0]['block_test_user']);
        $this->assertEquals('cm', $output['ret'][0]['login_code']);
    }

    /**
     * 測試回傳指定Domain相關設定，但domain不為廳主
     */
    public function testGetConfigByDomainButNotADomain()
    {
        $client = $this->createClient();

        $params = ['domain' => 6];
        $client->request('GET', '/api/domain/config_by_domain', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360024, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試回傳IP封鎖列表
     */
    public function testGetIpBlacklist()
    {
        $client = $this->createClient();

        $params = [
            'domain' => [2, 999],
            'ip' => '128.0.0.1',
            'removed' => '0',
            'start' => '2013-01-01T11:00:00+0800',
            'end' => new \DateTime('now'),
            'create_user' => '1',
            'login_error' => '0',
            'sort' => ['ip'],
            'order' => ['DESC'],
            'first_result' => 0,
            'max_results' => 20
        ];
        $client->request('GET', '/api/domain/ip_blacklist', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('128.0.0.1', $output['ret'][0]['ip']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertTrue($output['ret'][0]['create_user']);
        $this->assertFalse($output['ret'][0]['login_error']);
        $this->assertEquals('1', $output['pagination']['total']);
    }

    /**
     * 測試移除IP封鎖列表
     */
    public function testRemoveIpBlacklist()
    {
        $client = $this->createClient();

        $params = [
            'blacklist_id' => '1',
            'operator' => 'test'
        ];
        $client->request('DELETE', '/api/domain/ip_blacklist', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('126.0.0.1', $output['ret']['ip']);
        $this->assertTrue($output['ret']['removed']);
        $this->assertTrue($output['ret']['create_user']);
        $this->assertFalse($output['ret']['login_error']);
        $this->assertEquals('test', $output['ret']['operator']);

        // 操作紀錄檢查
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('ip_blacklist', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@removed:false=>true', $logOp->getMessage());
    }

    /**
     * 測試移除IP封鎖列表,但IP封鎖列表id帶入錯誤
     */
    public function testRemoveIpBlacklistButInvalidId()
    {
        $client = $this->createClient();

        $params = [
            'blacklist_id' => '55',
            'operator' => 'test'
        ];
        $client->request('DELETE', '/api/domain/ip_blacklist', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150360001', $output['code']);
        $this->assertEquals('No ipBlacklist found', $output['msg']);
    }

    /**
     * 測試移除IP封鎖列表,但IP封鎖列表早已被移除
     */
    public function testRemoveIpBlacklistButIdAlreadyRemoved()
    {
        $client = $this->createClient();

        $params = [
            'blacklist_id' => '1',
            'operator' => 'test'
        ];
        $client->request('DELETE', '/api/domain/ip_blacklist', $params);

        // 測試欲移除的IP封鎖列表早已被移除
        $client->request('DELETE', '/api/domain/ip_blacklist', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150360002', $output['code']);
        $this->assertEquals('Blacklist_id already removed', $output['msg']);
    }

    /**
     * 測試停用廳主及子帳號
     *
     * @author ruby 2014.10.24
     */
    public function testDisableDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 新增廳主帳號
        $parameters = [
            'user_id' => 199,
            'username' => 'domain123',
            'password' => 'domain456',
            'alias' => '廳主帳號',
            'name' => 'domain789',
            'login_code' => 'kk',
            'role' => 7
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $lastModifiedAt = $output['ret']['modified_at'];

        // 確認廳主帳號有無新增成功，並已啟用
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(199, $output['ret']['id']);
        $this->assertEquals('domain123', $output['ret']['username']);
        $this->assertEquals(199, $output['ret']['domain']);
        $this->assertEquals('廳主帳號', $output['ret']['alias']);
        $this->assertEquals(7, $output['ret']['role']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertFalse($output['ret']['sub']);

        // 新增廳主子帳號
        $parameters = [
            'parent_id' => 199,
            'username' => 'subdomain',
            'password' => 'subdomain',
            'alias' => '廳主子帳號',
            'role' => 7,
            'sub' => 1
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $subId = $output['ret']['id'];

        // 確認子帳號有無新增成功，並已啟用
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20000001, $output['ret']['id']);
        $this->assertEquals('subdomain', $output['ret']['username']);
        $this->assertEquals(199, $output['ret']['parent']);
        $this->assertEquals('廳主子帳號', $output['ret']['alias']);
        $this->assertEquals(7, $output['ret']['role']);
        $this->assertEquals(199, $output['ret']['domain']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($output['ret']['sub']);

        // 停用廳主帳號及子帳號
        $client->request('PUT', '/api/domain/199/disable', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認廳主停用成功
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(199, $output['ret']['id']);
        $this->assertEquals('domain789', $output['ret']['name']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertGreaterThanOrEqual($lastModifiedAt, new \DateTime($output['ret']['modified_at']));

        // 確認子帳號停用成功
        $subdomain = $em->find('BBDurianBundle:User', $subId);
        $this->assertFalse($subdomain->isEnabled());

        // 檢查操作紀錄
        $logOp = $emShare->getRepository('BBDurianBundle:LogOperation')->findAll();
        $this->assertEquals('user', $logOp[9]->getTableName());
        $this->assertEquals("@id:199", $logOp[9]->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOp[9]->getMessage());
        $this->assertEquals('user', $logOp[10]->getTableName());
        $this->assertEquals("@id:20000001", $logOp[10]->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOp[10]->getMessage());

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 199);
        $this->assertFalse($domainConfig->isEnabled());

        // 確認紀錄敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
        $this->assertTrue(strpos($results[1], $line) !== false);
    }

    /**
     * 測試停用廳主，但輸入的ID不為廳主
     *
     * @author ruby 2014.10.24
     */
    public function testDisableDomainButNotADomain()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/domain/6/disable', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360019, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試停用廳主但未帶操作者資訊
     *
     * @author ruby 2014.10.24
     */
    public function testDisableDomainButWithoutOperation()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/domain/3/disable', [], []);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240001', $output['code']);
        $this->assertEquals('The request not allowed without operation data in header', $output['msg']);
    }

    /**
     * 測試廳主先前已被停用，則不會存操作紀錄
     *
     * @author ruby 2014.10.24
     */
    public function testDisableDomainButAlreadyDidabled()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 新增廳主帳號
        $parameters = [
            'user_id' => 199,
            'username' => 'domain123',
            'password' => 'domain456',
            'alias' => '廳主帳號',
            'login_code' => 'kk',
            'name' => 'domain789',
            'enable' => false,
            'role' => 7
        ];

        $client->request('POST', '/api/user', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $lastModifiedAt = $output['ret']['modified_at'];

        // 確認廳主帳號有無新增成功，且為停用狀態
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(199, $output['ret']['id']);
        $this->assertEquals('domain123', $output['ret']['username']);
        $this->assertEquals(199, $output['ret']['domain']);
        $this->assertEquals('廳主帳號', $output['ret']['alias']);
        $this->assertEquals(7, $output['ret']['role']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertFalse($output['ret']['sub']);

        // 停用廳主帳號
        $client->request('PUT', '/api/domain/199/disable', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查操作紀錄，$logOp[0]、[1]、[2]和[3]為創廳主帳號時所產生操作紀錄
        $logOp = $em->getRepository('BBDurianBundle:LogOperation')->findAll();
        $this->assertEquals('user', $logOp[0]->getTableName());
        $this->assertEquals("@id:199", $logOp[0]->getMajorKey());
        $this->assertEquals('@username:domain123, @domain:199, @alias:廳主帳號, @sub:false, @enable:false, @block:false, @disabled_password:false, @password:new, @test:false, @currency:CNY, @rent:false, @password_reset:false, @role:7', $logOp[0]->getMessage());
        $this->assertEquals('domain_currency', $logOp[1]->getTableName());
        $this->assertEquals("@domain:199", $logOp[1]->getMajorKey());
        $this->assertEquals('@currency:156', $logOp[1]->getMessage());
        $this->assertEquals('user_password', $logOp[2]->getTableName());
        $this->assertEquals("@user_id:199", $logOp[2]->getMajorKey());
        $this->assertEquals('@hash:new', $logOp[2]->getMessage());
        $this->assertEquals('user_email', $logOp[4]->getTableName());
        $this->assertEquals("@user_id:199", $logOp[4]->getMajorKey());
        $this->assertEquals('@email:NULL', $logOp[4]->getMessage());
        $this->assertFalse(isset($logOp[5]));
    }

    /**
     * 測試取得單一廳主下所有的層級
     */
    public function testGetDomainLevels()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/domain/10/levels');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3, $ret['ret'][0]['level_id']);
        $this->assertEquals('未分層', $ret['ret'][0]['level_alias']);
        $this->assertEquals(4, $ret['ret'][1]['level_id']);
        $this->assertEquals('第一層', $ret['ret'][1]['level_alias']);
        $this->assertEquals(6, $ret['ret'][2]['level_id']);
        $this->assertEquals('第二層', $ret['ret'][2]['level_alias']);
    }

    /**
     * 測試更新廳下層會員的測試帳號數量
     */
    public function testUpdateTotalTest()
    {
        // domain 2下新增一筆測試帳號
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setTest(true);

        $em->flush();

        $output = $this->getResponse('PUT', '/api/domain/2/total_test');

        // 檢查資料庫
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(1, $totalTest->getTotalTest());

        // 檢查操作紀錄
        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_total_test', $log->getTableName());
        $this->assertEquals('@total_test:0=>1', $log->getMessage());

        // 檢查輸出資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['total_test']);
        $this->assertEquals($totalTest->getAt()->format(\DateTime::ISO8601), $output['ret']['at']);
    }

    /**
     * 測試取得廳主下層會員的測試帳號數量記錄
     */
    public function testGetDomainTotalTest()
    {
        $client = $this->createClient();

        // 取得所有廳
        $client->request('GET', '/api/domain/total_test');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(0, $output['ret'][0]['total_test']);

        $this->assertEquals(9, $output['ret'][1]['domain']);
        $this->assertEquals(0, $output['ret'][1]['total_test']);

        // 取得單一廳
        $client->request('GET', '/api/domain/total_test', ['domain' => 9]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret'][0]['domain']);
        $this->assertEquals(0, $output['ret'][0]['total_test']);
    }

    /**
     * 測試回傳廳時間區間內會員的建立數量
     */
    public function testDomainCountMemberCreated()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user8 = $em->find('BBDurianBundle:User', 8);
        $user8->setCreatedAt(new \DateTime('20120102120000'));
        $user51 = $em->find('BBDurianBundle:User', 51);
        $user51->setCreatedAt(new \DateTime('20120104090000'));
        $em->flush();

        $parameters = [
            'start' => '2012-01-01T00:00:00-0400',
            'end' => '2012-01-05T00:00:00-0400'
        ];

        $client->request('GET', '/api/domain/2/count_member_created', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $ret = [
            [
                'date' => '2012-01-01',
                'count' => 0
            ],
            [
                'date' => '2012-01-02',
                'count' => 1
            ],
            [
                'date' => '2012-01-03',
                'count' => 1
            ],
            [
                'date' => '2012-01-04',
                'count' => 0
            ],
            [
                'date' => '2012-01-05',
                'count' => 0
            ],
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret, $output['ret']);
    }

    /**
     * 測試回傳網址列表
     */
    public function testGetUrlList()
    {
        $ret = [
            'result' => 'ok',
            'ret' => [],
            'profile' => []
        ];

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode','getContent'])
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($ret));

        $container = $this->getContainer();

        $request = new Request(['group' => 5]);
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setClient($mockClient);
        $controller->setResponse($response);

        $json = $controller->getUrlListAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試回傳網址列表，但curl失敗
     */
    public function testGetUrlListButCurlFailed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Curl getUrlList api failed',
            150360012
        );

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode'])
            ->getMock();

        $response->expects($this->at(0))
            ->method('getStatusCode')
            ->willReturn(500);

        $container = $this->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->getUrlListAction($request);
    }

    /**
     * 測試回傳網址狀態
     */
    public function testGetUrlStatus()
    {
        $ret = [
            'result' => 'ok',
            'ret' => [],
            'profile' => []
        ];

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode','getContent'])
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($ret));

        $container = $this->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setClient($mockClient);
        $controller->setResponse($response);

        $json = $controller->getUrlStatusAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試回傳網址狀態，但curl失敗
     */
    public function testGetUrlStatusButCurlFailed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Curl getUrlStatus api failed',
            150360013
        );

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode'])
            ->getMock();

        $response->expects($this->at(0))
            ->method('getStatusCode')
            ->willReturn(500);

        $container = $this->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->getUrlStatusAction($request);
    }

    /**
     * 測試回傳網址站別列表
     */
    public function testGetUrlSite()
    {
        $ret = [
            'result' => 'ok',
            'ret' => [],
            'profile' => []
        ];

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode','getContent'])
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($ret));

        $container = $this->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setClient($mockClient);
        $controller->setResponse($response);

        $json = $controller->getUrlSiteAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試回傳網址站別列表，但curl失敗
     */
    public function testGetUrlSiteButCurlFailed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Curl getUrlSite api failed',
            150360014
        );

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode'])
            ->getMock();

        $response->expects($this->at(0))
            ->method('getStatusCode')
            ->willReturn(500);

        $container = $this->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setClient($mockClient);
        $controller->setResponse($response);
        $controller->getUrlSiteAction($request);
    }

    /**
     * 測試停用廳主商家
     */
    public function testDisableDomainMerchants()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $domain = $em->find('BBDurianBundle:User', 2);
        $domain->disable();
        $em->flush();

        // 停用廳主帳號及子帳號
        $client->request('PUT', '/api/domain/2/merchants/disable', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認廳主商家停用成功
        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $emShare->getRepository('BBDurianBundle:LogOperation')->findAll();
        $this->assertEquals('merchant', $logOp[0]->getTableName());
        $this->assertEquals('@id:2', $logOp[0]->getMajorKey());
        $this->assertEquals('@private_key:=>, @shop_url:=>', $logOp[0]->getMessage());
        $this->assertEquals('merchant_card', $logOp[2]->getTableName());
        $this->assertEquals('@id:1', $logOp[2]->getMajorKey());
        $this->assertEquals('@private_key:1x2x3x4x5x=>, @shop_url:http://ezshop.com/shop=>', $logOp[2]->getMessage());
        $this->assertEquals('merchant_withdraw', $logOp[10]->getTableName());
        $this->assertEquals('@id:5', $logOp[10]->getMajorKey());
        $this->assertEquals('@private_key:=>, @shop_url:=>', $logOp[10]->getMessage());

        // 確認紀錄敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試停用廳主商家但未帶操作者資訊
     */
    public function testDisableDomainMerchantsButWithoutOperation()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $domain = $em->find('BBDurianBundle:User', 2);
        $domain->disable();
        $em->flush();

        $client->request('PUT', '/api/domain/2/merchants/disable', [], []);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240001', $output['code']);
        $this->assertEquals('The request not allowed without operation data in header', $output['msg']);
    }

    /**
     * 測試設定廳主外接額度，但廳主沒有設定外接額度
     */
    public function testSetDomainOutsidePaywayButDomainHasNoOutside()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/domain/2/outside/payway', ['bodog' => 1]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150360026', $output['code']);
        $this->assertEquals('No outside supported', $output['msg']);
    }

    /**
     * 測試設定廳主外接額度
     */
    public function testSetDomainOutsidePayway()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $outPay = new OutsidePayway(2);
        $em->persist($outPay);
        $em->flush();

        $client->request('PUT', '/api/domain/2/outside/payway', ['bodog' => 1]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(true, $output['ret']['bodog']);

        // 檢查操作紀錄
        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('outside_payway', $log->getTableName());
        $this->assertEquals('@domain:2', $log->getMajorKey());
        $this->assertEquals('@bodog:false=>true', $log->getMessage());

        // 設定為0
        $client->request('PUT', '/api/domain/2/outside/payway', ['bodog' => 0]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(false, $output['ret']['bodog']);

        // 檢查操作紀錄
        $log = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('outside_payway', $log->getTableName());
        $this->assertEquals('@domain:2', $log->getMajorKey());
        $this->assertEquals('@bodog:true=>false', $log->getMessage());
    }

    /**
     * 測試回傳單一IP封鎖列表
     */
    public function testGetIpBlacklistById()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/ip_blacklist/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('126.0.0.1', $output['ret']['ip']);
        $this->assertTrue($output['ret']['create_user']);
        $this->assertFalse($output['ret']['login_error']);
        $this->assertFalse($output['ret']['removed']);
        $this->assertNotNull($output['ret']['created_at']);
        $this->assertNotNull($output['ret']['modified_at']);
        $this->assertEquals('', $output['ret']['operator']);
    }

    /**
     * 測試回傳單一IP封鎖列表，但IP封鎖列表不存在
     */
    public function testGetIpBlacklistByIdButNoSuchIpBlacklist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/ip_blacklist/778899632145');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150360027, $output['code']);
        $this->assertEquals('No ipBlacklist found', $output['msg']);
    }
}
