<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CaptchaFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $redis->flushdb();
    }

    /**
     * 測試建立混合驗證碼
     */
    public function testCreateCaptcha()
    {
        $client = $this->createClient();
        $length = 6;
        $params = [
            'identifier' => 0,
            'length' => $length
        ];

        $client->request('POST', '/api/user/8/captcha', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($length, strlen($output['ret']));
        $this->assertTrue(ctype_alnum($output['ret']));
    }

    /**
     * 測試建立驗證碼未帶入識別符
     */
    public function testCreateWithNoIdentifier()
    {
        $client = $this->createClient();
        $params = ['length' => 4];

        $client->request('POST', '/api/user/8/captcha', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400002', $output['code']);
        $this->assertEquals('No identifier specified', $output['msg']);
    }

    /**
     * 測試建立驗證碼帶入不合法的識別符
     */
    public function testCreateWithInvalidIdentifier()
    {
        $client = $this->createClient();
        $params = [
            'identifier' => -9,
            'length' => 4
        ];

        $client->request('POST', '/api/user/8/captcha', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400004', $output['code']);
        $this->assertEquals('Invalid identifier', $output['msg']);
    }

    /**
     * 測試建立驗證碼未帶入長度
     */
    public function testCreateWithNoLength()
    {
        $client = $this->createClient();
        $params = ['identifier' => 1];

        $client->request('POST', '/api/user/8/captcha', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400003', $output['code']);
        $this->assertEquals('Invalid length', $output['msg']);
    }

    /**
     * 測試建立驗證碼帶入長度不為整數
     */
    public function testCreateWithLengthNotInteger()
    {
        $client = $this->createClient();
        $params = [
            'identifier' => 1,
            'length' => 3.3
        ];

        $client->request('POST', '/api/user/8/captcha', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400003', $output['code']);
        $this->assertEquals('Invalid length', $output['msg']);
    }

    /**
     * 測試建立驗證碼帶入長度太短
     */
    public function testCreateWithLengthTooShort()
    {
        $client = $this->createClient();
        $params = [
            'identifier' => 1,
            'length' => 0
        ];

        $client->request('POST', '/api/user/8/captcha', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400003', $output['code']);
        $this->assertEquals('Invalid length', $output['msg']);
    }

    /**
     * 測試建立驗證碼帶入的使用者不存在
     */
    public function testCreateWithUserNotExist()
    {
        $client = $this->createClient();
        $params = [
            'identifier' => 1,
            'length' => 4
        ];

        $client->request('POST', '/api/user/888/captcha', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150400007, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試建立重複的驗證碼
     */
    public function testCreateDuplicateCaptcha()
    {
        $client = $this->createClient();
        $params = [
            'identifier' => 1,
            'length' => 4
        ];

        $client->request('POST', '/api/user/8/captcha', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 新增OK
        $this->assertEquals('ok', $output['result']);

        $client->request('POST', '/api/user/8/captcha', $params);
        $secondJson = $client->getResponse()->getContent();
        $secondOutput = json_decode($secondJson, true);

        // 重複新增會噴錯
        $this->assertEquals('error', $secondOutput['result']);
        $this->assertEquals('150400001', $secondOutput['code']);
        $this->assertEquals('Captcha already exists', $secondOutput['msg']);
    }

    /**
     * 測試Captcha驗證成功
     */
    public function testVerifySuccess()
    {
        $client = $this->createClient();
        $captchaGenie = $this->getContainer()->get('durian.captcha_genie');

        $userId = 8;
        $identifier = 1;
        $params = [
            'identifier' => $identifier,
            'length' => 4
        ];

        // 新增
        $client->request('POST', "/api/user/$userId/captcha", $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        unset($params['length']);
        $params['captcha'] = $output['ret'];

        // 驗證
        $client->request('GET', "/api/user/$userId/captcha/verify", $params);
        $verifyJson = $client->getResponse()->getContent();
        $verifyOutput = json_decode($verifyJson, true);

        $this->assertEquals('ok', $verifyOutput['result']);
        $this->assertTrue($verifyOutput['ret']);

        // 驗證成功後則刪除
        $captchAfter = $captchaGenie->get($userId, $identifier);
        $this->assertNull($captchAfter);
    }

    /**
     * 測試Captcha驗證失敗
     */
    public function testVerifyFailure()
    {
        $client = $this->createClient();
        $captchaGenie = $this->getContainer()->get('durian.captcha_genie');
        $userId = 8;
        $identifier = 1;
        $params = [
            'identifier' => $identifier,
            'length' => 4
        ];

        // 新增
        $client->request('POST', "/api/user/$userId/captcha", $params);
        $result = json_decode($client->getResponse()->getContent(), true);
        $captcha = $result['ret'];

        // verify
        unset($params['length']);
        $client->request('GET', "/api/user/$userId/captcha/verify", $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400006', $output['code']);
        $this->assertEquals('Verify failed', $output['msg']);

        // 驗證失敗不會刪除
        $captchAfter = $captchaGenie->get($userId, $identifier);
        $this->assertEquals($captcha, $captchAfter);
    }

    /**
     * 測試驗證Captcha未帶入識別符
     */
    public function testVerifyWithNoIdentifier()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/8/captcha/verify');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400002', $output['code']);
        $this->assertEquals('No identifier specified', $output['msg']);
    }

    /**
     * 測試驗證Captcha帶入不合法的識別符
     */
    public function testVerifyWithInvalidIdentifier()
    {
        $client = $this->createClient();
        $params = ['identifier' => 'abc'];

        $client->request('GET', '/api/user/8/captcha/verify', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400004', $output['code']);
        $this->assertEquals('Invalid identifier', $output['msg']);
    }

    /**
     * 測試驗證Captcha帶入Captcha不存在
     */
    public function testVerifyWithCaptchaNotExist()
    {
        $client = $this->createClient();
        $params = ['identifier' => 1];

        $client->request('GET', '/api/user/8/captcha/verify', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150400005', $output['code']);
        $this->assertEquals('Captcha not exists', $output['msg']);
    }
}
