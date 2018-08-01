<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Controller\OtpController;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\GlobalIp;

class OtpFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGlobalIpData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->set('disable_otp', 0);
    }

    /**
     * 測試取得 otp 驗證結果但使用者不存在
     */
    public function testVerifyButUserNotFound()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'username' => 'vtesterrrr',
            'otp_token' => 'test123'
        ];

        $client->request('GET', '/api/otp/verify', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150800004, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得 otp 驗證結果但使用者不須驗證 otp
     */
    public function testVerifyButDoesNotNeedToVerifyOtp()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'username' => 'vtester',
            'otp_token' => 'test123'
        ];

        $client->request('GET', '/api/otp/verify', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150800006, $output['code']);
        $this->assertEquals('This user does not need to verify otp', $output['msg']);
    }

    /**
     * 測試取得 otp 驗證結果成功
     */
    public function testVerifyOtpSuccess()
    {
        $container = $this->getContainer();
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $_SERVER["SERVER_ADDR"] = '127.0.0.1';

        // 修改廳主要驗證OTP
        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config->setVerifyOtp(true);
        $emShare->flush();

        $radius = $this->getMockBuilder('Dapphp\Radius\Radius')
            ->setMethods(['accessRequest'])
            ->getMock();
        $radius->expects($this->any())
            ->method('accessRequest')
            ->will($this->returnValue(true));

        $otpWorker = $container->get('durian.otp_worker');
        $otpWorker->setRadius($radius);

        $parameters = [
            'domain' => 2,
            'username' => 'vtester',
            'otp_token' => 'test123'
        ];

        $request = new Request($parameters);
        $controller = new OtpController();
        $controller->setContainer($container);

        $json = $controller->verifyAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('ok', $output['result']);

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logFile = $logDir . DIRECTORY_SEPARATOR .'domain_radius.log';
        $results = explode(PHP_EOL, file_get_contents($logFile));

        $this->assertStringEndsWith(
            'LOGGER.INFO: User: 3, domain: 2, response: true, error:  [] []',
            $results[0]
        );
    }

    /**
     * 測試取得 otp 驗證結果失敗
     */
    public function testVerifyOtpWrong()
    {
        $container = $this->getContainer();
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $_SERVER["SERVER_ADDR"] = '127.0.0.1';

        // 修改廳主要驗證OTP
        $config = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $config->setVerifyOtp(true);
        $emShare->flush();

        // 測試帳密錯誤
        $radius = $this->getMockBuilder('Dapphp\Radius\Radius')
            ->setMethods(['accessRequest', 'getLastError'])
            ->getMock();
        $radius->expects($this->any())
            ->method('accessRequest')
            ->will($this->returnValue(false));
        $radius->expects($this->any())
            ->method('getLastError')
            ->will($this->returnValue('Access rejected (3)'));

        $otpWorker = $container->get('durian.otp_worker');
        $otpWorker->setRadius($radius);

        $parameters = [
            'domain' => 2,
            'username' => 'vtester',
            'otp_token' => 'test123'
        ];

        $request = new Request($parameters);
        $controller = new OtpController();
        $controller->setContainer($container);

        $json = $controller->verifyAction($request);
        $output = json_decode($json->getContent(), true);

        $this->assertEquals('error', $output['result']);

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logFile = $logDir . DIRECTORY_SEPARATOR .'domain_radius.log';
        $results = explode(PHP_EOL, file_get_contents($logFile));

        $this->assertStringEndsWith(
            'LOGGER.INFO: User: 3, domain: 2, response: false, error: Access rejected (3) [] []',
            $results[0]
        );
    }

    /**
     * 測試驗證全域IP
     */
    public function testVerifyGlobalIp()
    {
        $client = $this->createClient();

        $parameters = ['ip' => '127.0.0.1'];

        $client->request('GET', '/api/global_ip/verify', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']);

        $parameters = ['ip' => '6.0.22.9'];

        $client->request('GET', '/api/global_ip/verify', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']);
    }

    /**
     * 測試新增全域IP，但IP已存在
     */
    public function testCreateGlobalIpButAlreadyExists()
    {
        $client = $this->createClient();

        $parameters = ['ip' => '127.0.0.1'];

        $client->request('POST', '/api/global_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150800009, $output['code']);
        $this->assertEquals('Global ip already exists', $output['msg']);
    }

    /**
     * 測試新增全域IP
     */
    public function testCreateGlobalIp()
    {
        $container = $this->getContainer();
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['ip' => '9.4.8.7', 'memo' => 'test123'];

        $client->request('POST', '/api/global_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('9.4.8.7', $output['ret']['ip']);
        $this->assertEquals('test123', $output['ret']['memo']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('global_ip', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals('@ip:9.4.8.7, @memo:test123', $logOperation->getMessage());
        $this->assertEquals('POST', $logOperation->getMethod());
    }

    /**
     * 測試網段是否含有全域IP
     */
    public function testGlobalIpCheck()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $gIp = new GlobalIp('127.0.0.3');
        $em->persist($gIp);
        $em->flush();

        $parameters = [
            'ip_start' => '127.0.0.1',
            'ip_end' => '127.0.0.255'
        ];

        $client->request('GET', '/api/global_ip/check', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals('127.0.0.3', $output['ret'][1]['ip']);

        $parameters = [
            'ip_start' => '1.2.3.0',
            'ip_end' => '1.2.3.255'
        ];

        $client->request('GET', '/api/global_ip/check', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試移除全域IP，但IP不存在
     */
    public function testRemoveGlobalIpButNoSuchGlobalIp()
    {
        $client = $this->createClient();

        $parameters = ['ip' => '9.4.8.7'];

        $client->request('DELETE', '/api/global_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150800011, $output['code']);
        $this->assertEquals('No such global ip', $output['msg']);
    }

    /**
     * 測試刪除全域IP
     */
    public function testRemoveGlobalIp()
    {
        $container = $this->getContainer();
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['ip' => '127.0.0.1'];

        $client->request('DELETE', '/api/global_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('global_ip', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@ip:127.0.0.1', $logOperation->getMessage());
        $this->assertEquals('DELETE', $logOperation->getMethod());
    }

    /**
     * 測試設定OTP總開關
     */
    public function testSetOtp()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default');

        $parameters = ['disable' => 1];
        $client->request('PUT', '/api/otp/set', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $redis->get('disable_otp'));

        $parameters = ['disable' => 0];
        $client->request('PUT', '/api/otp/set', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $redis->get('disable_otp'));
    }

    /**
     * 測試編輯全域IP，但IP不存在
     */
    public function testEditGlobalIpButNoSuchGlobalIp()
    {
        $client = $this->createClient();

        $parameters = ['ip' => '9.4.9.9'];

        $client->request('PUT', '/api/global_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150800013, $output['code']);
        $this->assertEquals('No such global ip', $output['msg']);
    }

    /**
     * 測試編輯全域IP
     */
    public function testEditGlobalIp()
    {
        $container = $this->getContainer();
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['ip' => '127.0.0.1', 'memo' => 'abcd'];

        $client->request('PUT', '/api/global_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('127.0.0.1', $output['ret']['ip']);
        $this->assertEquals('abcd', $output['ret']['memo']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('global_ip', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@memo:abcd', $logOperation->getMessage());
        $this->assertEquals('PUT', $logOperation->getMethod());
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
