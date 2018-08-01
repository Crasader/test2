<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\CashFakeEntry;
use Buzz\Message\Form\FormRequest;
use Symfony\Component\HttpFoundation\Request;
use Buzz\Message\Response;
use BB\DurianBundle\Controller\ToolsController;
use BB\DurianBundle\Entity\User;

class ToolsFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentDepositWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeTransferEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainWhitelistData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];
        $this->loadFixtures($classnames, 'entry');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData'
        ];
        $this->loadFixtures($classnames, 'his');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashErrorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeErrorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardErrorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserCreatedPerIpData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLoginErrorPerIpData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadIpBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipVersionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipBlockData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCountryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCityData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadSlideDeviceData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadSlideBindingData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試新增佔成
     */
    public function testNewShareLimit()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //假設佔成無錯誤
        $parameters = array(
            'depth'  => 1,
            'domain' => 2);

        $client->request('GET', '/api/validate_share_limit', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $expectedMsg = "No error";

        $this->assertEquals($expectedMsg, $output["Result"]);

        unset($expectedMsg);
        unset($output);

        //假設佔成有錯誤
        $share = $em->find('BB\DurianBundle\Entity\shareLimit', 3);
        $share->setUpper(90);
        $em->flush();
        $em->clear();

        $parameters = array(
            'depth'  => 1,
            'domain' => 2);

        $client->request('GET', '/api/validate_share_limit', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $expectedMsg[] = "UserId: 3 Id:3 group_num: 1 error: 150080017";

        $this->assertEquals($expectedMsg, $output["Result"]);

        unset($expectedMsg);
        unset($msg);

        //假設disable User佔成有錯誤
        $disableUser = $em->find('BB\DurianBundle\Entity\User', 3);
        $disableUser->disable();
        $em->flush();
        $em->clear();

        $share = $em->find('BB\DurianBundle\Entity\shareLimit', 3);
        $share->setUpper(90);
        $em->flush();
        $em->clear();

        $parameters = array(
            'depth'  => 1,
            'domain' => 2,
            'disable' => true);

        $client->request('GET', '/api/validate_share_limit', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $expectedMsg[] = "UserId: 3 Id:3 group_num: 1 error: 150080017";

        $this->assertEquals($expectedMsg, $output["Result"]);

        unset($expectedMsg);
        unset($msg);

        $parameters = array(
            'depth'  => 1,
            'domain' => 2);

        $client->request('GET', '/api/validate_share_limit', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $expectedMsg = "No error";

        $this->assertEquals($expectedMsg, $output["Result"]);

        unset($expectedMsg);
        unset($output);

        $disableUser = $em->find('BB\DurianBundle\Entity\User', 3);
        $disableUser->enable();
        $em->flush();
        $em->clear();

        //假設預改佔成無錯誤
        $parameters = array(
            'depth'  => 1,
            'domain' => 2,
            'next' => true);

        $client->request('GET', '/api/validate_share_limit', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $expectedMsg = "No error";

        $this->assertEquals($expectedMsg, $output["Result"]);

        unset($expectedMsg);
        unset($output);

        //假設預改佔成有錯誤
        $share = $em->find('BB\DurianBundle\Entity\shareLimitNext', 3);
        $share->setUpper(120);
        $em->flush();
        $em->clear();

        $parameters = array(
            'depth'  => 1,
            'domain' => 2,
            'next' => true);

        $client->request('GET', '/api/validate_share_limit', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $expectedMsg[] = "UserId: 3 Id:3 group_num: 1 error: 150080005";

        $this->assertEquals($expectedMsg, $output["Result"]);

        unset($expectedMsg);
        unset($msg);

        //假設disable User預改佔成有錯誤
        $disableUser = $em->find('BB\DurianBundle\Entity\User', 3);
        $disableUser->disable();
        $em->flush();
        $em->clear();

        $share = $em->find('BB\DurianBundle\Entity\shareLimitNext', 3);
        $share->setUpper(190);
        $em->flush();
        $em->clear();

        $parameters = array(
            'depth'  => 1,
            'domain' => 2,
            'disable' => true,
            'next' => true);

        $client->request('GET', '/api/validate_share_limit', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $expectedMsg[] = "UserId: 3 Id:3 group_num: 1 error: 150080005";

        $this->assertEquals($expectedMsg, $output["Result"]);

        unset($expectedMsg);
        unset($msg);

        $parameters = array(
            'depth'  => 1,
            'domain' => 2,
            'next' => true);

        $client->request('GET', '/api/validate_share_limit', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $expectedMsg = "No error";

        $this->assertEquals($expectedMsg, $output["Result"]);

        unset($expectedMsg);
        unset($output);

        $disableUser = $em->find('BB\DurianBundle\Entity\User', 3);
        $disableUser->enable();
        $em->flush();
        $em->clear();

        // 刪除產生的csv跟log檔案
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logsFile = $logsDir . DIRECTORY_SEPARATOR . 'validate_sl.log';
        $csvFile = $logsDir . DIRECTORY_SEPARATOR . 'validate_sharelimit_output.csv';

        if (file_exists($logsFile)) {
            unlink($logsFile);
        }

        if (file_exists($csvFile)) {
            unlink($csvFile);
        }
    }

    /**
     * 測試OTP SERVER連線
     */
    public function testOtpServerConnection()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $container = $this->getContainer();

        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config->setVerifyOtp(true);
        $emShare->flush();

        $radius = $this->getMockBuilder('Dapphp\Radius\Radius')
            ->setMethods(['accessRequest'])
            ->getMock();
        $radius->expects($this->any())
            ->method('accessRequest')
            ->will($this->returnValue(true));

        $_SERVER["SERVER_ADDR"] = '127.0.0.1';

        $parameters = [
            'domain' => 2,
            'otp_token' => 'test123'
        ];

        $request = new Request($parameters);
        $controller = new ToolsController();
        $controller->setContainer($container);

        $otpWorker = $container->get('durian.otp_worker');
        $otpWorker->setRadius($radius);

        $json = $controller->otpServerConnectionAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['password_verify']);
        $this->assertEquals('OTP 伺服器連線正常，回應訊息為: 帳密正確', $output['ret']['msg']);

        // 測試token錯誤
        $radius = $this->getMockBuilder('Dapphp\Radius\Radius')
            ->setMethods(['accessRequest', 'getLastError'])
            ->getMock();
        $radius->expects($this->any())
            ->method('accessRequest')
            ->will($this->returnValue(false));
        $radius->expects($this->any())
            ->method('getLastError')
            ->will($this->returnValue('Access rejected (3)'));

        $parameters = [
            'domain' => 2,
            'otp_token' => 'bbb'
        ];

        $request = new Request($parameters);
        $otpWorker->setRadius($radius);
        $json = $controller->otpServerConnectionAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['password_verify']);
        $this->assertEquals('OTP 伺服器連線正常，回應訊息為: 帳密錯誤', $output['ret']['msg']);

        // 測試廳主子帳號
        $radius = $this->getMockBuilder('Dapphp\Radius\Radius')
            ->setMethods(['accessRequest', 'getLastError'])
            ->getMock();
        $radius->expects($this->any())
            ->method('accessRequest')
            ->will($this->returnValue(false));
        $radius->expects($this->any())
            ->method('getLastError')
            ->will($this->returnValue('Failure with receiving network data (56)'));

        $parameters = [
            'domain' => '2',
            'sub' => 1,
            'otp_token' => 'test123'
        ];

        $request = new Request($parameters);
        $otpWorker->setRadius($radius);
        $json = $controller->otpServerConnectionAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150170034, $output['code']);
        $this->assertEquals('OTP 伺服器連線異常，回應訊息為: Failure with receiving network data (56)', $output['msg']);

        // 測試靜態帳密，不用輸入otp
        $radius = $this->getMockBuilder('Dapphp\Radius\Radius')
            ->setMethods(['accessRequest'])
            ->getMock();
        $radius->expects($this->any())
            ->method('accessRequest')
            ->will($this->returnValue(true));

        $parameters = ['otp_free' => 1];

        $request = new Request($parameters);
        $otpWorker->setRadius($radius);

        $json = $controller->otpServerConnectionAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['password_verify']);
        $this->assertEquals('OTP 伺服器連線正常，回應訊息為: 帳密正確', $output['ret']['msg']);
    }

    /**
     * 測試列出裝置所有綁定使用者，但裝置沒有綁定過
     */
    public function testListBindingUsersByDeviceButDeviceNotBind()
    {
        $parameters = ['app_id' => 'teshigawara'];
        $output = $this->getResponse('GET', '/api/tools/device/users', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been bound', $output['msg']);
        $this->assertEquals(150170036, $output['code']);
    }

    /**
     * 測試列出裝置所有綁定使用者
     */
    public function testListBindingUsersByDevice()
    {
        $list = [
            [
                'user_id' => 8,
                'username' => 'tester',
                'domain' => 2
            ],
            [
                'user_id' => 9,
                'username' => 'isolate',
                'domain' => 9
            ],
            [
                'user_id' => 50,
                'username' => 'vtester2',
                'domain' => 2
            ]
        ];

        $parameters = ['app_id' => 'mitsuha'];
        $output = $this->getResponse('GET', '/api/tools/device/users', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($list, $output['ret']);
    }

    /**
     * 測試DomainMap
     */
    public function testDomainMap()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 測試搜尋所有的廳主
        $parameters = ['enable' => -1];
        $server = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        $client->request('GET', '/tools/domain_map', $parameters, [], $server);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output["domain_map"][0]["id"]);
        $this->assertEquals('company', $output["domain_map"][0]["username"]);
        $this->assertEquals('domain2', $output["domain_map"][0]["name"]);
        $this->assertEquals(1, $output["domain_map"][0]["enable"]);
        $this->assertEquals('cm', $output["domain_map"][0]["loginCode"]);
        $this->assertEquals(9, $output["domain_map"][1]["id"]);
        $this->assertEquals('isolate', $output["domain_map"][1]["username"]);
        $this->assertEquals('蓝田', $output["domain_map"][1]["name"]);
        $this->assertEquals(1, $output["domain_map"][1]["enable"]);
        $this->assertEquals('ag', $output["domain_map"][1]["loginCode"]);
        $this->assertEquals(2, $output["total"]);

        // 測試用name搜尋廳主
        $parameters = ['domainName' => 'domain2'];

        $client->request('GET', '/tools/domain_map', $parameters, [], $server);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output["domain_map"][0]["id"]);
        $this->assertEquals('domain2', $output["domain_map"][0]["name"]);
        $this->assertEquals('company', $output["domain_map"][0]["username"]);
        $this->assertEquals(1, $output["domain_map"][0]["enable"]);
        $this->assertEquals('cm', $output["domain_map"][0]["loginCode"]);
        $this->assertEquals(1, $output["total"]);

        // 測試用loginCode搜尋廳主
        $parameters = ['domainLoginCode' => 'cm'];

        $client->request('GET', '/tools/domain_map', $parameters, [], $server);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output["domain_map"][0]["id"]);
        $this->assertEquals('domain2', $output["domain_map"][0]["name"]);
        $this->assertEquals('company', $output["domain_map"][0]["username"]);
        $this->assertEquals(1, $output["domain_map"][0]["enable"]);
        $this->assertEquals('cm', $output["domain_map"][0]["loginCode"]);
        $this->assertEquals(1, $output["total"]);

        $domain = $em->find('BBDurianBundle:User', 2);
        $domain->disable();
        $em->flush();

        // 測試預設搜尋啟用的廳主
        $client->request('GET', '/tools/domain_map', [], [], $server);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(9, $output["domain_map"][0]["id"]);
        $this->assertEquals('蓝田', $output["domain_map"][0]["name"]);
        $this->assertEquals('isolate', $output["domain_map"][0]["username"]);
        $this->assertEquals(1, $output["domain_map"][0]["enable"]);
        $this->assertEquals('ag', $output["domain_map"][0]["loginCode"]);
        $this->assertEquals(1, $output["total"]);

        // 測試搜尋停用的廳主
        $parameters = ['enable' => 0];
        $client->request('GET', '/tools/domain_map', $parameters, [], $server);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output["domain_map"][0]["id"]);
        $this->assertEquals('domain2', $output["domain_map"][0]["name"]);
        $this->assertEquals('company', $output["domain_map"][0]["username"]);
        $this->assertEquals(0, $output["domain_map"][0]["enable"]);
        $this->assertEquals('cm', $output["domain_map"][0]["loginCode"]);
        $this->assertEquals(1, $output["total"]);

        $domain = $em->find('BBDurianBundle:User', 2);
        $domain->enable();
        $domain->setAlias('马后炮');
        $em->flush();

        //測試繁會轉簡
        $parameters = ['domainName' => '藍田'];
        $client->request('GET', '/tools/domain_map', $parameters, [], $server);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(9, $output["domain_map"][0]["id"]);
        $this->assertEquals('蓝田', $output["domain_map"][0]["name"]);
        $this->assertEquals('isolate', $output["domain_map"][0]["username"]);
        $this->assertEquals(1, $output["domain_map"][0]["enable"]);
        $this->assertEquals('ag', $output["domain_map"][0]["loginCode"]);
        $this->assertEquals(1, $output["total"]);
        $this->assertEquals(1, $output["total_page"]);
    }

    /**
     * 測試DomainMap傳入空資料頁數
     */
    public function testDomainMapWithEmptyPage()
    {
        $client = $this->createClient();

        $parameters = [
            'domainAlias' => 'company',
            'page' => 10
        ];
        $server = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        $client->request('GET', '/tools/domain_map', $parameters, [], $server);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEmpty($output['domain_map']);
    }

    /**
     * 測試CheckActivateSLNext
     */
    public function testCheckActivateSLNext()
    {
        $client = $this->createClient();

        //未執行更新佔成
        $client->request('POST', '/tools/check');

        $content = $client->getResponse()->getContent();

        $this->assertContains('尚未執行更新佔成', $content);

        //已執行更新佔成
        $this->runCommand('durian:cronjob:activate-sl-next');
        $client->request('POST', '/tools/check');

        $content = $client->getResponse()->getContent();

        $this->assertNotContains('尚未執行更新佔成', $content);

        // 刪除產生的log檔案
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logsFile = $logsDir . DIRECTORY_SEPARATOR . 'activate_sl_next.log';

        if (file_exists($logsFile)) {
            unlink($logsFile);
        }
    }

    /**
     * 測試CheckRedisSeq
     */
    public function testCheckRedisSeq()
    {
        $client = $this->createClient();

        $redisSeq = $this->getContainer()->get('snc_redis.sequence');

        //未產生redis的seq key
        $client->request('POST', '/tools/check');

        $content = $client->getResponse()->getContent();

        $this->assertContains('無法產生現金明細id', $content);
        $this->assertContains('無法產生快開額度明细id', $content);
        $this->assertContains('無法產生租卡明細id', $content);
        $this->assertContains('無法產生現金出款明細id', $content);
        $this->assertContains('無法產生使用者id', $content);
        $this->assertContains('無法產生紅包明細id', $content);
        $this->assertContains('無法產生外接額度明細id', $content);

        //已產生redis的seq key
        $redisSeq->set('cash_seq', 0);
        $redisSeq->set('cashfake_seq', 0);
        $redisSeq->set('card_seq', 0);
        $redisSeq->set('cash_withdraw_seq', 0);
        $redisSeq->set('user_seq', 0);
        $redisSeq->set('reward_seq', 0);
        $redisSeq->set('outside_seq', 0);

        $client->request('POST', '/tools/check');

        $content = $client->getResponse()->getContent();

        $this->assertNotContains('無法產生現金明細id', $content);
        $this->assertNotContains('無法產生快開額度明细id', $content);
        $this->assertNotContains('無法產生租卡明細id', $content);
        $this->assertNotContains('無法產生現金出款明細id', $content);
        $this->assertNotContains('無法產生使用者id', $content);
        $this->assertNotContains('無法產生點數明細id', $content);
        $this->assertNotContains('無法產生紅包明細id', $content);
    }

    /**
     * 測試CheckRedisInvalidSeq
     */
    public function testCheckRedisInvalidSeq()
    {
        $client = $this->createClient();

        $redisSeq = $this->getContainer()->get('snc_redis.sequence');

        //產生小於CashEntry的seq key
        $redisSeq->set('cash_withdraw_seq', 0);
        $redisSeq->set('cash_seq', 0);

        $client->request('POST', '/tools/check');
        $content = $client->getResponse()->getContent();

        $this->assertContains('CashWithdrawEntry Sequence 異常，須重新設定 cash_withdraw_seq', $content);
        $this->assertContains('CashEntry Sequence 異常，須重新設定 cash_seq', $content);

        //產生redis的seq key
        $redisSeq->set('cash_withdraw_seq', 11);
        $redisSeq->set('cash_seq', 11);

        $client->request('POST', '/tools/check');
        $content = $client->getResponse()->getContent();

        $this->assertNotContains('CashWithdrawEntry Sequence 異常，須重新設定 cash_withdraw_seq', $content);
        $this->assertNotContains('CashEntry Sequence 異常，須重新設定 cash_seq', $content);
    }

    /**
     * 測試CheckSessionMaintain
     */
    public function testCheckSessionMaintain()
    {
        $client = $this->createClient();

        $redis = $this->getContainer()->get('snc_redis.cluster');

        // session沒有維護資訊
        $client->request('POST', '/tools/check');
        $content = $client->getResponse()->getContent();

        $msg = '資料庫與session的維護資訊不一致，請執行durian:create-session-info --maintain';
        $this->assertContains($msg, $content);

        // 跑command 建立session的維護資訊
        $this->runCommand('durian:create-session-info', ['--maintain' => true]);

        $client->request('POST', '/tools/check');
        $content = $client->getResponse()->getContent();

        $this->assertNotContains($msg, $content);

        // 刪掉一個維護資訊使session的維護資訊數量與資料庫不相符
        $redis->hdel('session_maintain', 1);

        $client->request('POST', '/tools/check');
        $content = $client->getResponse()->getContent();

        $this->assertContains($msg, $content);

        // 跑command 建立session的維護資訊
        $this->runCommand('durian:create-session-info', ['--maintain' => true]);

        $client->request('POST', '/tools/check');
        $content = $client->getResponse()->getContent();

        $this->assertNotContains($msg, $content);
    }

    /**
     * 測試CheckSessionWhitelist
     */
    public function testCheckSessionWhitelist()
    {
        $client = $this->createClient();

        $redis = $this->getContainer()->get('snc_redis.cluster');

        // session沒有白名單資料
        $client->request('POST', '/tools/check');
        $content = $client->getResponse()->getContent();

        $msg = '資料庫與session的白名單資訊不一致，請執行durian:create-session-info --whitelist';
        $this->assertContains($msg, $content);

        // 跑command 建立session的白名單資訊
        $this->runCommand('durian:create-session-info', ['--whitelist' => true]);

        $client->request('POST', '/tools/check');
        $content = $client->getResponse()->getContent();

        $this->assertNotContains($msg, $content);
    }

    /**
     * 測試CheckSpeed
     */
    public function testCheckSpeed()
    {
        $client = $this->createClient();
        $client->request('POST', '/tools/check_speed');
        $content = $client->getResponse()->getContent();

        $pattern = '/execution_time: (\d+) ms/';
        $isMatch = preg_match($pattern, $content, $match);

        $this->assertEquals(1, $isMatch);
        $this->assertTrue(is_numeric($match[1]));

        $serverName = gethostname();
        $this->assertContains("server_name: $serverName", $content);
    }

    /**
     * 測試取得修正背景執行數量工具
     */
    public function testRenderToGetBgProcessNum()
    {
        $client = $this->createClient();
        $client->request('GET', '/tools/display_background_process_name');
        $content = $client->getResponse()->getContent();

        $this->assertContains('activate-sl-next', $content);
        $this->assertContains('check-card-error', $content);
        $this->assertContains('check-cash-entry', $content);
        $this->assertContains('check-cash-error', $content);
        $this->assertContains('check-cash-fake-error', $content);
        $this->assertContains('check-account-status', $content);
        $this->assertContains('run-card-poper', $content);
        $this->assertContains('run-card-sync', $content);
        $this->assertContains('run-cashfake-poper', $content);
        $this->assertContains('run-cashfake-sync', $content);
        $this->assertContains('run-cash-poper', $content);
        $this->assertContains('run-cash-sync', $content);
        $this->assertContains('run-credit-poper', $content);
        $this->assertContains('run-credit-sync', $content);
        $this->assertContains('sync-his-poper', $content);
        $this->assertContains('toAccount', $content);
    }

    /**
     * 測試修正背景執行數量與啟用狀態
     */
    public function testSetBgProcess()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 調整執行數量
        $process = $em->find('BBDurianBundle:BackgroundProcess', 'activate-sl-next');
        $process->setNum(5);
        $process = $em->find('BBDurianBundle:BackgroundProcess', 'check-cash-entry');
        $process->setNum(-1);
        $em->flush();

        $parameters = [
            'process' => [
                'activate-sl-next',
                'check-cash-entry'
            ],
            'num' => [
                -4,
                2
            ],
            'enable' => [
                '0',
                '0'
            ]
        ];

        $client->request('PUT', '/tools/set_background_process', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('activate-sl-next num：1', $output['ret'][0]);
        $this->assertEquals('activate-sl-next enable：0', $output['ret'][1]);
        $this->assertEquals('check-cash-entry num：1', $output['ret'][2]);
        $this->assertEquals('check-cash-entry enable：0', $output['ret'][3]);
    }

    /**
     * 測試例外 當修正執行數量時
     */
    public function testExceptionWhenSetBgProcess()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 參數都是空字串，回傳空值
        $parameters = [
            'process' => ['', ''],
            'num' => ['', ''],
            'enable' => ['', '']
        ];

        $client->request('PUT', '/tools/set_background_process', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertTrue($output['profile']['execution_time'] > 0);
        $this->assertFalse(isset($output['result']));

        // 調整後的執行數量小於0
        $parameters = [
            'process' => ['activate-sl-next'],
            'num' => [-10]
        ];

        $client->request('PUT', '/tools/set_background_process', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150170010, $output['code']);
        $this->assertEquals('Process number can not be set below 0', $output['msg']);
    }

    /**
     * 測試發生rollback情況(exception 1062)，執行數量是否有更新
     */
    public function testUpdateProcessNumWithDuplicateRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;

        $mockBackground = $this->getMockBuilder('BB\DurianBundle\Monitor\Background')
            ->setMethods(['setBgProcessNum'])
            ->getMock();

        $mockBackground->expects($this->any())
            ->method('setBgProcessNum')
            ->will($this->throwException(new \Exception('Database is busy', 150010071, $pdoExcep)));

        $parameters = [
            'process' => ['activate-sl-next'],
            'num' => [1]
        ];

        $client = $this->createClient();
        $client->getContainer()->set('durian.monitor.background', $mockBackground);
        $client->request('PUT', '/tools/set_background_process', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $process = $em->find('BBDurianBundle:BackgroundProcess', 'activate-sl-next');

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150780001, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
        $this->assertEquals(0, $process->getNum());
    }

    /**
     * 測試取得額度不符工具介面
     */
    public function testReviseEntryPage()
    {
        $client = $this->createClient();
        $client->request('GET', '/tools/revise_entry');
        $result = $client->getResponse()->getContent();

        $this->assertContains('額度不符清單', $result);


        // 刪除額度不符後再次連結工具介面
        $client->request('POST', '/tools/error/remove');
        $client->request('GET', '/tools/revise_entry');
        $result = $client->getResponse()->getContent();

        $this->assertContains('無額度不符', $result);
    }

    /**
     * 測試修改現金明細建立時間
     */
    public function testReviseCashEntryAt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $ceRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $ceHisRepo = $emHis->getRepository('BBDurianBundle:CashEntry');
        $client = $this->createClient();

        // 新增一筆opcode大於9890的現金明細到資料庫與歷史資料庫
        $time = new \DateTime('2013-01-01 12:00:00');
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $entry = new CashEntry($cash, 40001, 1000);
        $entry->setId(11);
        $entry->setRefId(224466881);
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);
        $emEntry->persist($entry);
        $emEntry->flush();

        $entry = new CashEntry($cash, 40001, 1000);
        $entry->setId(11);
        $entry->setRefId(224466881);
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);
        $emHis->persist($entry);
        $emHis->flush();

        // 修改現金明細建立時間，此明細opcode = 40001
        $parameters = [
            'entry_id' => '11',
            'at' => '2013-01-01T12:00:00+0800',
            'new_at' => '2013-12-31T07:59:59+0800'
        ];
        $client->request('PUT', '/tools/cash_entry/revise', $parameters);

        // 檢查操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_entry", $logOperation->getTableName());
        $this->assertEquals("@id:11", $logOperation->getMajorKey());
        $this->assertEquals(
            "@at:20130101120000=>20131231075959, @created_at:2013-01-01 12:00:00=>2013-12-31 07:59:59",
            $logOperation->getMessage()
        );

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 檢查回傳值
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(11, $ret['ret']['entry']['id']);
        $this->assertEquals(1, $ret['ret']['entry']['cash_id']);
        $this->assertEquals(2, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals(40001, $ret['ret']['entry']['opcode']);
        $this->assertEquals('2013-12-31T07:59:59+0800', $ret['ret']['entry']['created_at']);
        $this->assertEquals(1000, $ret['ret']['entry']['amount']);
        $this->assertEquals('', $ret['ret']['entry']['memo']);
        $this->assertEquals(224466881, $ret['ret']['entry']['ref_id']);
        $this->assertEquals(2000, $ret['ret']['entry']['balance']);

        // 檢查現金明細建立時間是否修改
        $cashEntry = $ceRepo->findOneBy(['id' => '11']);
        $this->assertEquals('20131231075959', $cashEntry->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashEntry->getCreatedAt()->format('Y-m-d H:i:s'));

        // 檢查歷史資料庫現金明細建立時間是否修改
        $cashEntryHis = $ceHisRepo->findOneBy(['id' => '11']);
        $this->assertEquals('20131231075959', $cashEntryHis->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashEntryHis->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試修改現金明細建立時間，同時更新金流交易記錄的建立時間
     */
    public function testReviseCashEntryAtAndPaymentDepositWithdrawEntryAt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $ceRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $pdweRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $ceHisRepo = $emHis->getRepository('BBDurianBundle:CashEntry');
        $client = $this->createClient();

        // 修改現金明細建立時間，此明細opcode = 1001，小於9890
        $parameters = [
            'entry_id' => '1',
            'at' => '2013-01-01T12:00:00+0800',
            'new_at' => '2013-12-31T07:59:59+0800'
        ];
        $client->request('PUT', '/tools/cash_entry/revise', $parameters);

        // 檢查金流交易記錄的操作紀錄
        $logOperation2 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("payment_deposit_withdraw_entry", $logOperation2->getTableName());
        $this->assertEquals("@id:1", $logOperation2->getMajorKey());
        $this->assertEquals("@at:20130101120000=>20131231075959", $logOperation2->getMessage());

        // 檢查現金明細操作紀錄
        $logOperation3 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("cash_entry", $logOperation3->getTableName());
        $this->assertEquals("@id:1", $logOperation3->getMajorKey());
        $this->assertEquals(
            "@at:20130101120000=>20131231075959, @created_at:2013-01-01 12:00:00=>2013-12-31 07:59:59",
            $logOperation3->getMessage()
        );

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 檢查回傳值(金流交易記錄)
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['ret']['payment_deposit_withdraw']['id']);
        $this->assertEquals(2, $ret['ret']['payment_deposit_withdraw']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['payment_deposit_withdraw']['currency']);
        $this->assertEquals(1001, $ret['ret']['payment_deposit_withdraw']['opcode']);
        $this->assertEquals('2013-12-31T07:59:59+0800', $ret['ret']['payment_deposit_withdraw']['at']);
        $this->assertEquals(1000, $ret['ret']['payment_deposit_withdraw']['amount']);
        $this->assertEquals('', $ret['ret']['payment_deposit_withdraw']['memo']);
        $this->assertEquals(238030097, $ret['ret']['payment_deposit_withdraw']['ref_id']);
        $this->assertEquals(2000, $ret['ret']['payment_deposit_withdraw']['balance']);

        // 檢查回傳值(現金明細)
        $this->assertEquals(1, $ret['ret']['entry']['id']);
        $this->assertEquals(1, $ret['ret']['entry']['cash_id']);
        $this->assertEquals(2, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals(1001, $ret['ret']['entry']['opcode']);
        $this->assertEquals('2013-12-31T07:59:59+0800', $ret['ret']['entry']['created_at']);
        $this->assertEquals(1000, $ret['ret']['entry']['amount']);
        $this->assertEquals('', $ret['ret']['entry']['memo']);
        $this->assertEquals(238030097, $ret['ret']['entry']['ref_id']);
        $this->assertEquals(2000, $ret['ret']['entry']['balance']);

        // 檢查現金明細建立時間是否修改
        $cashEntry = $ceRepo->findOneBy(['id' => '1']);
        $this->assertEquals('20131231075959', $cashEntry->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashEntry->getCreatedAt()->format('Y-m-d H:i:s'));

        $pdweEntry = $pdweRepo->findOneBy(['id' => '1']);
        $this->assertEquals('2013-12-31 07:59:59', $pdweEntry->getAt()->format('Y-m-d H:i:s'));

        // 檢查歷史資料庫現金明細建立時間是否修改
        $cashEntryHis = $ceHisRepo->findOneBy(['id' => '1']);
        $this->assertEquals('20131231075959', $cashEntryHis->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashEntryHis->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試修改假現金明細建立時間
     */
    public function testReviseCashFakeEntryAt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $cfeRepo = $em->getRepository('BBDurianBundle:CashFakeEntry');
        $cfeHisRepo = $emHis->getRepository('BBDurianBundle:CashFakeEntry');
        $client = $this->createClient();

        // 新增一筆opcode大於9890的假現金明細至資料庫與歷史資料庫
        $time = new \DateTime('2013-01-01 12:00:00');
        $cashFake = $em->find('BBDurianBundle:CashFake', 1);
        $entry = new CashFakeEntry($cashFake, 40000, 1000);
        $entry->setId(8);
        $entry->setRefId(224466881);
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);
        $em->persist($entry);
        $em->flush();

        $entry = new CashFakeEntry($cashFake, 40000, 1000);
        $entry->setId(8);
        $entry->setRefId(224466881);
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);
        $emHis->persist($entry);
        $emHis->flush();

        // 修改假現金明細建立時間，此明細opcode = 40000
        $parameters = [
            'entry_id' => '8',
            'at' => '2013-01-01T12:00:00+0800',
            'new_at' => '2013-12-31T07:59:59+0800'
        ];
        $client->request('PUT', '/tools/cashfake_entry/revise', $parameters);

        // 檢查操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals("cash_fake_entry", $logOperation->getTableName());
        $this->assertEquals("@id:8", $logOperation->getMajorKey());
        $this->assertEquals(
            "@at:20130101120000=>20131231075959, @created_at:2013-01-01 12:00:00=>2013-12-31 07:59:59",
            $logOperation->getMessage()
        );

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 檢查回傳值
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(8, $ret['ret']['entry']['id']);
        $this->assertEquals(1, $ret['ret']['entry']['cash_fake_id']);
        $this->assertEquals(7, $ret['ret']['entry']['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry']['currency']);
        $this->assertEquals(40000, $ret['ret']['entry']['opcode']);
        $this->assertEquals('2013-12-31T07:59:59+0800', $ret['ret']['entry']['created_at']);
        $this->assertEquals(1000, $ret['ret']['entry']['amount']);
        $this->assertEquals(1500, $ret['ret']['entry']['balance']);
        $this->assertEquals(224466881, $ret['ret']['entry']['ref_id']);
        $this->assertEquals('', $ret['ret']['entry']['memo']);

        // 檢查假現金明細建立時間是否修改
        $cashFakeEntry = $cfeRepo->findOneBy(['id' => '8']);
        $this->assertEquals('20131231075959', $cashFakeEntry->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashFakeEntry->getCreatedAt()->format('Y-m-d H:i:s'));

        // 檢查歷史資料庫假現金明細建立時間是否修改
        $cashFakeEntryHis = $cfeHisRepo->findOneBy(['id' => '8']);
        $this->assertEquals('20131231075959', $cashFakeEntryHis->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashFakeEntryHis->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試修改假現金明細建立時間，同時更新假現金轉帳交易記錄的建立時間
     */
    public function testReviseCashFakeEntryAtAndCashFakeTransferEntryAt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $cfteRepo = $em->getRepository('BBDurianBundle:CashFakeTransferEntry');
        $cfeRepo = $em->getRepository('BBDurianBundle:CashFakeEntry');
        $cfeHisRepo = $emHis->getRepository('BBDurianBundle:CashFakeEntry');
        $client = $this->createClient();

        // 修改假現金明細建立時間，此明細opcode = 1006，小於9890
        $parameters = [
            'entry_id' => '1',
            'at' => '2013-01-01T12:00:00+0800',
            'new_at' => '2013-12-31T07:59:59+0800'
        ];
        $client->request('PUT', '/tools/cashfake_entry/revise', $parameters);

        // 檢查假現金轉帳交易記錄操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_fake_transfer_entry", $logOperation->getTableName());
        $this->assertEquals("@id:1", $logOperation->getMajorKey());
        $this->assertEquals(
            "@at:20130101120000=>20131231075959, @created_at:2013-01-01 12:00:00=>2013-12-31 07:59:59",
            $logOperation->getMessage()
        );

        // 檢查假現金明細操作紀錄
        $logOperation2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("cash_fake_entry", $logOperation2->getTableName());
        $this->assertEquals("@id:1", $logOperation2->getMajorKey());
        $this->assertEquals(
            "@at:20130101120000=>20131231075959, @created_at:2013-01-01 12:00:00=>2013-12-31 07:59:59",
            $logOperation2->getMessage()
        );

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 檢查回傳值(假現金轉帳交易記錄)
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['ret']['transfer']['id']);
        $this->assertEquals(7, $ret['ret']['transfer']['user_id']);
        $this->assertEquals(2, $ret['ret']['transfer']['domain']);
        $this->assertEquals('CNY', $ret['ret']['transfer']['currency']);
        $this->assertEquals(1006, $ret['ret']['transfer']['opcode']);
        $this->assertEquals('2013-12-31T07:59:59+0800', $ret['ret']['transfer']['created_at']);
        $this->assertEquals(1000, $ret['ret']['transfer']['amount']);
        $this->assertEquals(1000, $ret['ret']['transfer']['balance']);
        $this->assertEquals(5150840307, $ret['ret']['transfer']['ref_id']);
        $this->assertEquals('', $ret['ret']['transfer']['memo']);

        // 檢查回傳值(假現金明細)
        $this->assertEquals(1, $ret['ret']['entry']['id']);
        $this->assertEquals(1, $ret['ret']['entry']['cash_fake_id']);
        $this->assertEquals(7, $ret['ret']['entry']['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry']['currency']);
        $this->assertEquals(1006, $ret['ret']['entry']['opcode']);
        $this->assertEquals('2013-12-31T07:59:59+0800', $ret['ret']['entry']['created_at']);
        $this->assertEquals(1000, $ret['ret']['entry']['amount']);
        $this->assertEquals(1000, $ret['ret']['entry']['balance']);
        $this->assertEquals(5150840307, $ret['ret']['entry']['ref_id']);
        $this->assertEquals('', $ret['ret']['entry']['memo']);

        // 檢查假現金轉帳交易記錄建立時間是否修改
        $cashFakeTransferEntry = $cfteRepo->findOneBy(['id' => '1']);
        $this->assertEquals('20131231075959', $cashFakeTransferEntry->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashFakeTransferEntry->getCreatedAt()->format('Y-m-d H:i:s'));

        // 檢查假現金明細建立時間是否修改
        $cashFakeEntry = $cfeRepo->findOneBy(['id' => '1']);
        $this->assertEquals('20131231075959', $cashFakeEntry->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashFakeEntry->getCreatedAt()->format('Y-m-d H:i:s'));

        // 檢查歷史資料庫假現金明細建立時間是否修改
        $cashFakeEntryHis = $cfeHisRepo->findOneBy(['id' => '1']);
        $this->assertEquals('20131231075959', $cashFakeEntryHis->getAt());
        $this->assertEquals('2013-12-31 07:59:59', $cashFakeEntryHis->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試修改明細建立時間，發生錯誤rollback
     */
    public function testReivseEntryButRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->setMethods(['create'])
            ->getMock();

        $operationLogger->expects($this->any())
            ->method('create')
            ->will($this->throwException(new \Exception('Database is busy', 150010071, $pdoExcep)));

        $parameters = [
            'entry_id' => '1',
            'at' => '2013-01-01T12:00:00+0800',
            'new_at' => '2013-12-31T07:59:59+0800'
        ];

        // 測試修改現金明細
        $client = $this->createClient();
        $client->getContainer()->set('durian.operation_logger', $operationLogger);
        $client->request('PUT', '/tools/cash_entry/revise', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150780001, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        $entry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')->findOneBy(['id' => 1, 'at' => '20131231075959']);
        $this->assertNull($entry);

        // 測試修改假現金明細
        $client = $this->createClient();
        $client->getContainer()->set('durian.operation_logger', $operationLogger);
        $client->request('PUT', '/tools/cashfake_entry/revise', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150780001, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        $entry = $em->getRepository('BBDurianBundle:CashFakeTransferEntry')->findOneBy(['id' => 1, 'at' => '20131231075959']);
        $this->assertNull($entry);
    }

    /**
     * 測試刪除額度不符
     */
    public function testRemoveError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 刪除額度不符
        $client->request('POST', '/tools/error/remove');

        // 檢查操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_error", $logOperation->getTableName());
        $this->assertEquals("@id:1", $logOperation->getMajorKey());
        $this->assertEquals('@id:1', $logOperation->getMessage());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("cash_error", $logOperation->getTableName());
        $this->assertEquals("@id:2", $logOperation->getMajorKey());
        $this->assertEquals('@id:2', $logOperation->getMessage());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals("cash_fake_error", $logOperation->getTableName());
        $this->assertEquals("@id:1", $logOperation->getMajorKey());
        $this->assertEquals('@id:1', $logOperation->getMessage());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals("cash_fake_error", $logOperation->getTableName());
        $this->assertEquals("@id:2", $logOperation->getMajorKey());
        $this->assertEquals('@id:2', $logOperation->getMessage());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals("card_error", $logOperation->getTableName());
        $this->assertEquals("@id:1", $logOperation->getMajorKey());
        $this->assertEquals('@id:1', $logOperation->getMessage());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals("card_error", $logOperation->getTableName());
        $this->assertEquals("@id:2", $logOperation->getMajorKey());
        $this->assertEquals('@id:2', $logOperation->getMessage());

        // 檢查額度不符是否刪除
        $cashError = $emShare->find('BBDurianBundle:CashError', 1);
        $this->assertNull($cashError);
        $cashError = $emShare->find('BBDurianBundle:CashError', 2);
        $this->assertNull($cashError);
        $cashFakeError = $emShare->find('BBDurianBundle:CashFakeError', 1);
        $this->assertNull($cashFakeError);
        $cashFakeError = $emShare->find('BBDurianBundle:CashFakeError', 2);
        $this->assertNull($cashFakeError);
        $cardError = $emShare->find('BBDurianBundle:CardError', 1);
        $this->assertNull($cardError);
        $cardError = $emShare->find('BBDurianBundle:CardError', 2);
        $this->assertNull($cardError);
    }

    /**
     * 測試連結修正現金明細差異頁面
     */
    public function testConnetRepairEntryPage()
    {
        $client = $this->createClient();
        $client->request('GET', '/tools/repair_entry_page');
        $result = $client->getResponse()->getContent();

        $this->assertContains('<h1>修正差異明細</h1>', $result);
    }

    /**
     * 測試顯示明細差異
     */
    public function testShowEntryDiff()
    {
        $id = ['id' => 1];
        $time = new \DateTime('2013/1/2 12:00:00');

        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 更改cashEntry歷史明細的時間
        $entryHis = $emHis->getRepository('BBDurianBundle:CashEntry')->findOneBy($id);
        $entryHis->setCreatedAt($time);
        $emHis->flush();

        $params = [
            '--starttime' => "2013/1/1 12:00:00",
            '--endtime'   => "2013/1/1 12:01:00"
        ];

        // 找出cashEntry差異
        $this->runCommand('durian:cronjob:check-cash-entry', $params);

        $params = ['entry_type' => 'cash'];

        $client->request('GET', '/tools/show_entry', $params);
        $json = $client->getResponse()->getContent();
        $json = json_decode($json, true);

        // 比對cash現行明細
        $this->assertEquals($json['contents'][0]['id'], '1');
        $this->assertEquals($json['contents'][0]['cash_id'], 1);
        $this->assertEquals($json['contents'][0]['user_id'], 2);
        $this->assertEquals($json['contents'][0]['currency'], 'TWD');
        $this->assertEquals($json['contents'][0]['opcode'], 1001);
        $this->assertEquals($json['contents'][0]['created_at'], '2013-01-01T12:00:00+0800');
        $this->assertEquals($json['contents'][0]['amount'], '1000');
        $this->assertEquals($json['contents'][0]['memo'], '');
        $this->assertEquals($json['contents'][0]['ref_id'], '238030097');
        $this->assertEquals($json['contents'][0]['balance'], '2000');
        $this->assertEquals($json['contents'][0]['at'], '20130101120000');

        // 比對cash歷史明細
        $this->assertEquals($json['contents'][1]['id'], '1');
        $this->assertEquals($json['contents'][1]['cash_id'], 1);
        $this->assertEquals($json['contents'][1]['user_id'], 2);
        $this->assertEquals($json['contents'][1]['currency'], 'TWD');
        $this->assertEquals($json['contents'][1]['opcode'], 1001);
        $this->assertEquals($json['contents'][1]['created_at'], '2013-01-02T12:00:00+0800');
        $this->assertEquals($json['contents'][1]['amount'], '1000');
        $this->assertEquals($json['contents'][1]['memo'], '');
        $this->assertEquals($json['contents'][1]['ref_id'], '238030097');
        $this->assertEquals($json['contents'][1]['balance'], '2000');
        $this->assertEquals($json['contents'][1]['at'], '20130101120000');

        $params = [
            '--starttime' => "2013/1/1 12:00:00",
            '--endtime'   => "2013/1/1 12:01:00"
        ];

        // 更改cashFakeEntry歷史明細的時間
        $entryHis = $emHis->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy($id);
        $entryHis->setCreatedAt($time);
        $emHis->flush();

        $params = [
            '--starttime' => "2013/1/1 12:00:00",
            '--endtime'   => "2013/1/1 12:01:00"
        ];

        // 找出cashFakeEntry差異
        $this->runCommand('durian:cronjob:check-cash-fake-entry', $params);

        $params = ['entry_type' => 'cashFake'];

        $client->request('GET', '/tools/show_entry', $params);
        $json = $client->getResponse()->getContent();
        $json = json_decode($json, true);

        // 比對cashFake現行明細
        $this->assertEquals($json['contents'][0]['id'], '1');
        $this->assertEquals($json['contents'][0]['cash_fake_id'], 1);
        $this->assertEquals($json['contents'][0]['user_id'], 7);
        $this->assertEquals($json['contents'][0]['currency'], 'CNY');
        $this->assertEquals($json['contents'][0]['opcode'], 1006);
        $this->assertEquals($json['contents'][0]['created_at'], '2013-01-01T12:00:00+0800');
        $this->assertEquals($json['contents'][0]['amount'], '1000');
        $this->assertEquals($json['contents'][0]['balance'], '1000');
        $this->assertEquals($json['contents'][0]['ref_id'], '5150840307');
        $this->assertEquals($json['contents'][0]['memo'], '');
        $this->assertEquals($json['contents'][0]['at'], '20130101120000');

        // 比對cashFake歷史明細
        $this->assertEquals($json['contents'][1]['id'], '1');
        $this->assertEquals($json['contents'][1]['cash_fake_id'], 1);
        $this->assertEquals($json['contents'][1]['user_id'], 7);
        $this->assertEquals($json['contents'][1]['currency'], 'CNY');
        $this->assertEquals($json['contents'][1]['opcode'], 1006);
        $this->assertEquals($json['contents'][1]['created_at'], '2013-01-02T12:00:00+0800');
        $this->assertEquals($json['contents'][1]['amount'], '1000');
        $this->assertEquals($json['contents'][1]['balance'], '1000');
        $this->assertEquals($json['contents'][1]['ref_id'], '5150840307');
        $this->assertEquals($json['contents'][1]['memo'], '');
        $this->assertEquals($json['contents'][1]['at'], '20130101120000');
    }

    /**
     * 測試修正現金明細差異
     */
    public function testRepairCashEntry()
    {
        $id = ['id' => 1];
        $time = new \DateTime('2013/1/2 12:00:00');

        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 更改歷史明細的時間
        $entryHis = $emHis->getRepository('BBDurianBundle:CashEntry')->findOneBy($id);
        $entryHis->setCreatedAt($time);
        $emHis->flush();

        $params = [
            '--starttime' => "2013/1/1 12:00:00",
            '--endtime'   => "2013/1/1 12:01:00"
        ];

        // 找出差異
        $this->runCommand('durian:cronjob:check-cash-entry', $params);

        // 檢查cash-entry-diff
        $entryDiff = $em->find('BBDurianBundle:CashEntryDiff', 1);
        $this->assertNotNull($entryDiff);

        $params = ['entry_type' => 'cash'];

        // 修正現金明細
        $client->request('PUT', '/tools/execute_repair_entry', $params);

        $json = $client->getResponse()->getContent();
        $json = json_decode($json, true);

        // 檢查回傳值是否被修正
        $this->assertEquals($json['logs'][0], '現金明細修正成功');
        $this->assertEquals($json['logs'][1], '已修正明細編號:1');

        // 檢查資料是否被修正
        $entry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($id);
        $createdAt = $entry->getCreatedAt()->format('Y-m-d H:i:s');

        $emHis->refresh($entryHis);
        $createdAtHis = $entryHis->getCreatedAt()->format('Y-m-d H:i:s');

        $this->assertEquals($entryHis->getId(), 1);
        $this->assertEquals($createdAt, $createdAtHis);
    }

    /**
     * 測試修正假現金明細差異
     */
    public function testRepairCashFakeEntry()
    {
        $id = ['id' => 1];
        $time = new \DateTime('2013/1/2 12:00:00');

        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 更改歷史明細的時間
        $entryHis = $emHis->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy($id);
        $entryHis->setCreatedAt($time);
        $emHis->flush();

        $params = [
            '--starttime' => "2013/1/1 12:00:00",
            '--endtime'   => "2013/1/1 12:01:00"
        ];

        // 找出差異
        $this->runCommand('durian:cronjob:check-cash-fake-entry', $params);

        // 檢查cash-fake-entry_diff
        $entryDiff = $em->find('BBDurianBundle:CashFakeEntryDiff', 1);
        $this->assertNotNull($entryDiff);

        $params = ['entry_type' => 'cashFake'];

        // 修正假現金明細
        $client->request('PUT', '/tools/execute_repair_entry', $params);

        $json = $client->getResponse()->getContent();
        $json = json_decode($json, true);

        // 檢查回傳值是否被修正
        $this->assertEquals($json['logs'][0], '假現金明細修正成功');
        $this->assertEquals($json['logs'][1], '已修正明細編號:1');

        // 檢查資料是否被修正
        $entry = $em->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy($id);
        $createdAt = $entry->getCreatedAt()->format('Y-m-d H:i:s');

        $emHis->refresh($entryHis);
        $createdAtHis = $entryHis->getCreatedAt()->format('Y-m-d H:i:s');

        $this->assertEquals($entryHis->getId(), 1);
        $this->assertEquals($createdAt, $createdAtHis);
    }

    /**
     * 測試連結監控IP封鎖列表資訊頁面
     */
    public function testConnetDisplayIpBlacklistPage()
    {
        $client = $this->createClient();
        $client->request('GET', '/tools/display_ip_blacklist');
        $content = $client->getResponse()->getContent();

        $this->assertContains('<h1>監控IP封鎖列表資訊頁面</h1>', $content);

        // 驗證IP封鎖列表資料(需保留原html輸出格式來比對)
        $total = '<strong>IP封鎖列表: </strong>共 6 筆 (單頁最多顯示50筆資料, 可點擊欄位以顯示統計資料)';
        $this->assertContains($total, $content);

        $removedTotal = '<strong>時間內已移除的IP封鎖列表: </strong>共 1 筆';
        $this->assertContains($removedTotal, $content);

        // 完整ip來源
        $result1 = '<td>111.235.135.3</td>
                        <td>馬來西亞 吉隆坡</td>';

        $this->assertContains($result1, $content);

        // ip來源未知城市
        $result2 = '<td>218.26.54.4</td>
                        <td>JP</td>';

        $this->assertContains($result2, $content);

        // ip來源未定義
        $result3 = '<td>128.0.0.1</td>
                        <td>未定義</td>';

        $this->assertContains($result3, $content);

        // ip來源沒有中文翻譯
        $result4 = '<td>126.0.0.1</td>
                        <td>JP unKnowCity</td>';

        $this->assertContains($result4, $content);

        // 已刪除的IP封鎖列表(顯示操作者)
        $result5 = '<td>123.123.123.123</td>
                        <td>tester</td>';

        $this->assertContains($result5, $content);
    }

    /**
     * 測試連結監控IP封鎖列表資訊頁面時帶入ip
     */
    public function testConnetDisplayIpBlacklistPageWithIp()
    {
        $client = $this->createClient();
        $client->request('GET', '/tools/display_ip_blacklist?ip=111.235.135.3');
        $content = $client->getResponse()->getContent();

        $this->assertContains('<h1>監控IP封鎖列表資訊頁面</h1>', $content);

        // 驗證IP封鎖列表資料(需保留原html輸出格式來比對)
        $total = '<strong>IP封鎖列表: </strong>共 1 筆 (單頁最多顯示50筆資料, 可點擊欄位以顯示統計資料)';
        $this->assertContains($total, $content);

        $removedTotal = '<strong>時間內已移除的IP封鎖列表: </strong>共 0 筆';
        $this->assertContains($removedTotal, $content);

        // 驗證資料 完整ip來源
        $result = '<td>111.235.135.3</td>
                        <td>馬來西亞 吉隆坡</td>';

        $this->assertContains($result, $content);
    }

    /**
     * 測試取得ip登入及註冊時間計次資料
     */
    public function testGetIpActivityRecord()
    {
        $client = $this->createClient();

        // 測試撈取建立使用者IP統計資料
        $params = ['ip_blacklist_id' => '5'];

        $client->request('GET', '/tools/get_ip_activity_record', $params);
        $json = $client->getResponse()->getContent();
        $content = json_decode($json, true);

        $this->assertEquals('5', $content['ip_blacklist_id']);
        $this->assertEquals(2, $content['domain']);
        $this->assertEquals('127.0.111.1', $content['ip']);

        // IP封鎖列表原因相關
        $this->assertEquals(1, $content['reasonTotal']);
        $this->assertEquals(4, $content['reasonRecord'][0]['id']);
        $this->assertEquals('127.0.111.1', $content['reasonRecord'][0]['ip']);
        $this->assertEquals(2, $content['reasonRecord'][0]['domain']);
        $this->assertEquals(1, $content['reasonRecord'][0]['count']);

        // 沒有其他異常統計資料
        $this->assertEquals(0, $content['otherTotal']);

        // 測試撈取登入錯誤IP統計資料
        $params = ['ip_blacklist_id' => '3'];

        $client->request('GET', '/tools/get_ip_activity_record', $params);
        $json = $client->getResponse()->getContent();
        $content = json_decode($json, true);

        $this->assertEquals('3', $content['ip_blacklist_id']);
        $this->assertEquals(2, $content['domain']);
        $this->assertEquals('111.235.135.3', $content['ip']);

        // IP封鎖列表原因相關
        $this->assertEquals(1, $content['reasonTotal']);
        $this->assertEquals(1, $content['reasonRecord'][0]['id']);
        $this->assertEquals('111.235.135.3', $content['reasonRecord'][0]['ip']);
        $this->assertEquals(2, $content['reasonRecord'][0]['domain']);
        $this->assertEquals(1, $content['reasonRecord'][0]['count']);

        // 其他異常統計資料
        $this->assertEquals(1, $content['otherTotal']);
        $this->assertEquals(3, $content['otherRecord'][0]['id']);
        $this->assertEquals('111.235.135.3', $content['otherRecord'][0]['ip']);
        $this->assertEquals(2, $content['otherRecord'][0]['domain']);
        $this->assertEquals(2, $content['otherRecord'][0]['count']);
    }

    /**
     * 測試顯示kue數量
     */
    public function testDisplayKueJob()
    {
        $client = $this->createClient();
        $client->request('GET', '/tools/display_kue_job');
        $content = $client->getResponse()->getContent();

        $this->assertContains('尚未執行 (Queued): <span> 0 </span>', $content);
        $this->assertContains('執行中 (Active): <span> 0 </span>', $content);
        $this->assertContains('失敗 (Failed): <span> 0 </span>', $content);
        $this->assertContains('已完成 (Complete): <span> 0 </span>', $content);

        $redis = $this->getContainer()->get('snc_redis.kue');
        $redis->zadd('jobs:complete', 0, '01|01');

        $client = $this->createClient();
        $client->request('GET', '/tools/display_kue_job');
        $content = $client->getResponse()->getContent();

        $this->assertContains('尚未執行 (Queued): <span> 0 </span>', $content);
        $this->assertContains('執行中 (Active): <span> 0 </span>', $content);
        $this->assertContains('失敗 (Failed): <span> 0 </span>', $content);
        $this->assertContains('已完成 (Complete): <span> 1 </span>', $content);
    }

    /**
     * 測試取得job失敗
     */
    public function testGetJobsFailed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Get kue job failed',
            150170031
        );

        $client = $this->createClient();

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $mockResponse = $this->getMockBuilder('Buzz\Message\Form\FormRequest')
            ->setMethods(['getStatusCode'])
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(404);

        $container = $this->getContainer();

        $param = [
            'type' => 'test',
            'status' => 'complete',
            'from' => 0,
            'to' => 0,
            'order' => 'ASC'
        ];

        $request = new Request([], $param);

        $kueManager = $container->get('durian.kue_manager');
        $kueManager->setClient($mockClient);
        $kueManager->setResponse($mockResponse);

        $kueManager->getJobs($param);
    }

    /**
     * 測試刪除kue job
     */
    public function testDeleteKueJob()
    {
        $container = $this->getContainer();

        $param = [
            'type' => 'test',
            'status' => 'complete'
        ];

        $request = new Request([], $param);
        $controller = new ToolsController();
        $controller->setContainer($container);

        $this->setKueManager($container);

        $json = $controller->deleteKueJobAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']['nums']['inactive']);
        $this->assertEquals(0, $output['ret']['nums']['active']);
        $this->assertEquals(0, $output['ret']['nums']['failed']);
        $this->assertEquals(0, $output['ret']['nums']['complete']);
        $this->assertEquals(1, $output['ret']['success_count']);
        $this->assertEquals(0, $output['ret']['failed_count']);
    }

    /**
     * 測試刪除kue job，呼叫刪除kue job api失敗
     */
    public function testDeleteKueJobButDeleteJobApiFailed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Delete kue job failed',
            150170032
        );

        $container = $this->getContainer();

        $param = [
            'type' => 'test',
            'status' => 'complete'
        ];

        $request = new Request([], $param);
        $controller = new ToolsController();
        $controller->setContainer($container);

        $this->setKueManager($container, true, false);

        $controller->deleteKueJobAction($request);
    }

    /**
     * 測試刪除kue job，刪除kue job失敗
     */
    public function testDeleteKueJobButDeleteJobFailed()
    {
        $container = $this->getContainer();

        $param = [
            'type' => 'test',
            'status' => 'complete'
        ];

        $request = new Request([], $param);
        $controller = new ToolsController();
        $controller->setContainer($container);

        $this->setKueManager($container, false, true);

        $json = $controller->deleteKueJobAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals(0, $output['ret']['success_count']);
        $this->assertEquals(1, $output['ret']['failed_count']);
    }

    /**
     * 測試重新執行kue job
     */
    public function testRedoKueJob()
    {
        $container = $this->getContainer();

        $param = [
            'type' => 'test',
            'status' => 'complete'
        ];

        $request = new Request([], $param);
        $controller = new ToolsController();
        $controller->setContainer($container);

        $this->setKueManager($container);

        $json = $controller->redoKueJobAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']['nums']['inactive']);
        $this->assertEquals(0, $output['ret']['nums']['active']);
        $this->assertEquals(0, $output['ret']['nums']['failed']);
        $this->assertEquals(0, $output['ret']['nums']['complete']);
        $this->assertEquals(1, $output['ret']['success_count']);
        $this->assertEquals(0, $output['ret']['failed_count']);
    }

    /**
     * 測試重新執行kue job，呼叫重新執行kue job api失敗
     */
    public function testRedoKueJobButRedoJobApiFailed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Redo kue job failed',
            150170033
        );

        $container = $this->getContainer();

        $param = [
            'type' => 'test',
            'status' => 'complete'
        ];

        $request = new Request([], $param);
        $controller = new ToolsController();
        $controller->setContainer($container);

        $this->setKueManager($container, true, false);

        $controller->redoKueJobAction($request);
    }

    /**
     * 測試重新執行kue job，重新執行kue job失敗
     */
    public function testRedoKueJobButRedoJobFailed()
    {
        $container = $this->getContainer();

        $param = [
            'type' => 'test',
            'status' => 'complete'
        ];

        $request = new Request([], $param);
        $controller = new ToolsController();
        $controller->setContainer($container);

        $this->setKueManager($container, false, true);

        $json = $controller->redoKueJobAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals(0, $output['ret']['success_count']);
        $this->assertEquals(1, $output['ret']['failed_count']);
    }

    /**
     * 設定Kue Manager
     *
     * @param Container $container
     * @param boolean $statusCodeFail 呼叫api是否出錯
     * @param boolean $contentFail    回傳值是否為失敗
     */
    public function setKueManager($container, $statusCodeFail = false, $contentFail = false)
    {
        $ret = [
            'id' => 1,
            'type' => 'test',
            'data' => [
                'title' => 'test',
                'user_id' => 1,
                'site' => 'bb'
            ]
        ];

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $mockResponse = $this->getMockBuilder('Buzz\Message\Form\FormRequest')
            ->setMethods(['getStatusCode', 'getContent'])
            ->getMock();

        $mockResponse->expects($this->at(0))
            ->method('getStatusCode')
            ->willReturn(200);

        $mockResponse->expects($this->at(1))
            ->method('getContent')
            ->willReturn(json_encode([$ret]));

        if (!$statusCodeFail) {
            $mockResponse->expects($this->at(2))
            ->method('getStatusCode')
            ->willReturn(200);

            if ($contentFail) {
                $kueError = ['error' => 'error'];

                $mockResponse->expects($this->at(3))
                    ->method('getContent')
                    ->willReturn(json_encode($kueError));
            }
        } else {
            $mockResponse->expects($this->at(2))
                ->method('getStatusCode')
                ->willReturn(404);
        }

        $kueManager = $container->get('durian.kue_manager');
        $kueManager->setClient($mockClient);
        $kueManager->setResponse($mockResponse);
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
