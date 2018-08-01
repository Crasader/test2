<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Oauth2\Server as Oauth2Server;

class Oauth2FunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $container = $this->getContainer();

        $redisCluster = $container->get('snc_redis.cluster');
        $redisOauth2 = $container->get('snc_redis.oauth2');
        $redisCluster->flushdb();
        $redisOauth2->flushdb();

        $redisCluster->hmset('session_ses123', ['session:id' => 'ses123']);
        $redisOauth2->sadd('clients', 'abc12345');
        $redisOauth2->hmset('client:abc12345', [
            'secret' => 'secret-test',
            'redirect_uri' => 'abc54321',
            'name' => 'just for test',
            'id' => 'abc12345',
            'domain' => '2'
        ]);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

         $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData'
        ];
        $this->loadFixtures($classnames);
    }

    /**
     * 測試建立 Oauth2 client
     */
    public function testCreate()
    {
        $params = [
            'name' => 'test-name-a-fake',
            'redirect_uri' => 'test-redirect-uri-fake',
            'domain' => '9'
        ];
        $res = $this->getResponse('POST', '/api/oauth2/client', $params);

        $this->assertEquals('ok', $res['result']);
        $this->assertNotEmpty($res['ret']['id']);
        $this->assertNotEmpty($res['ret']['secret']);
        $this->assertEquals('test-name-a-fake', $res['ret']['name']);
        $this->assertEquals('test-redirect-uri-fake', $res['ret']['redirect_uri']);
        $this->assertEquals('9', $res['ret']['domain']);
    }

    /**
     * 測試建立 Oauth2 client ，但輸入不存在的Domain
     */
    public function testCreateWithDomainNotExist()
    {
        $params = [
            'name' => 'test-name-a-fake',
            'redirect_uri' => 'test-redirect-uri-fake',
            'domain' => '777'
        ];
        $res = $this->getResponse('POST', '/api/oauth2/client', $params);

        $this->assertEquals('error', $res['result']);
        $this->assertEquals(150810031, $res['code']);
        $this->assertEquals('Not a domain', $res['msg']);
    }

    /**
     * 測試編輯 Oauth2 client
     */
    public function testEdit()
    {
        $params = [
            'name' => 'new-name',
            'redirect_uri' => 'new-uri'
        ];
        $res = $this->getResponse('PUT', '/api/oauth2/client/abc12345', $params);

        $this->assertEquals('ok', $res['result']);
        $this->assertEquals('abc12345', $res['ret']['id']);
        $this->assertFalse(isset($res['ret']['secret']));
        $this->assertEquals('new-name', $res['ret']['name']);
        $this->assertEquals('new-uri', $res['ret']['redirect_uri']);
    }

    /**
     * 測試刪除 oauth2 client
     */
    public function testRemove()
    {
        $params = [
            'redirect_uri' => 'abc54321',
            'domain' => 2
        ];
        $res = $this->getResponse('DELETE', '/api/oauth2/client/abc12345', $params);

        $this->assertEquals('ok', $res['result']);
    }

    /**
     * 測試刪除 oauth2 client, 但 rediect_uri 比對錯誤
     */
    public function testRemoveWithRedirectUriNotMatch()
    {
        $params = [
            'redirect_uri' => 'abc5432',
            'domain' => 2
        ];
        $res = $this->getResponse('DELETE', '/api/oauth2/client/abc12345', $params);

        $this->assertEquals('error', $res['result']);
        $this->assertEquals(150810029, $res['code']);
        $this->assertEquals('Redirect_uri not match', $res['msg']);
    }

    /**
     * 測試刪除 oauth2 client, 但 domain 比對錯誤
     */
    public function testRemoveWithDomainNotMatch()
    {
        $params = [
            'redirect_uri' => 'abc54321',
            'domain' => 777
        ];
        $res = $this->getResponse('DELETE', '/api/oauth2/client/abc12345', $params);

        $this->assertEquals('error', $res['result']);
        $this->assertEquals(150810032, $res['code']);
        $this->assertEquals('Domain not match', $res['msg']);
    }

    /**
     * 測試取得 oauth2 client
     */
    public function testGetClient()
    {
        $res = $this->getResponse('GET', '/api/oauth2/client/abc12345?redirect_uri=abc54321');

        $this->assertEquals('ok', $res['result']);
        $this->assertEquals('abc12345', $res['ret']['id']);
        $this->assertFalse(isset($res['ret']['secret']));
        $this->assertEquals('just for test', $res['ret']['name']);
        $this->assertEquals('abc54321', $res['ret']['redirect_uri']);
        $this->assertEquals('2', $res['ret']['domain']);
    }

    /**
     * 測試取得 oauth2 client, 但 redirect_uri 比對失敗
     */
    public function testGetClientWithWrongRedirectUri()
    {
        $res = $this->getResponse('GET', '/api/oauth2/client/abc12345?redirect_uri=abc');

        $this->assertEquals('error', $res['result']);
        $this->assertEquals(150810002, $res['code']);
        $this->assertEquals('Redirect_uri not match', $res['msg']);
    }

    /**
     * 測試取得 oauth2 client 一覽
     */
    public function testGetClients()
    {
        $params = [
            'name' => 'a',
            'redirect_uri' => 'b',
            'domain' => '3'
        ];
        $res = $this->getResponse('POST', '/api/oauth2/client', $params);
        $client = $res['ret'];

        $res = $this->getResponse('GET', '/api/oauth2/clients');

        // 排序確保資料順序無誤
        usort($res['ret'], function ($a, $b) {
            if ($a['name'] < $b['name']) {
                return -1;
            }

            return 1;
        });

        $this->assertEquals('ok', $res['result']);
        $this->assertCount(2, $res['ret']);
        $this->assertEquals($client['id'], $res['ret'][0]['id']);
        $this->assertFalse(isset($res['ret'][0]['secret']));
        $this->assertEquals('a', $res['ret'][0]['name']);
        $this->assertEquals('b', $res['ret'][0]['redirect_uri']);
        $this->assertEquals('3', $res['ret'][0]['domain']);

        $this->assertEquals('abc12345', $res['ret'][1]['id']);
        $this->assertFalse(isset($res['ret'][1]['secret']));
        $this->assertEquals('just for test', $res['ret'][1]['name']);
        $this->assertEquals('abc54321', $res['ret'][1]['redirect_uri']);
        $this->assertEquals('2', $res['ret'][1]['domain']);
    }

    /**
     * 測試產生授權碼
     */
    public function testAuthenticate()
    {
        $client = $this->createClient();
        $client->request(
            'GET',
            '/api/oauth2/authenticate?response_type=code&client_id=abc12345&redirect_uri=abc54321&state=aaaa',
            [],
            [],
            ['HTTP_SESSION_ID' => 'ses123']
        );

        $json = $client->getResponse()->getContent();
        $res = json_decode($json, true);

        $this->assertEquals('ok', $res['result']);
        $this->assertEquals('aaaa', $res['ret']['state']);
        $this->assertEquals('abc54321', $res['ret']['redirect_uri']);
        $this->assertNotEmpty($res['ret']['code']);
    }

    /**
     * 測試產生授權碼，但 session id 不存在
     */
    public function testAuthenticateWithWrongSessionId()
    {
        $client = $this->createClient();
        $client->request(
            'GET',
            '/api/oauth2/authenticate?response_type=code&client_id=abc12345&redirect_uri=abc54321&state=aaaa',
            [],
            [],
            ['HTTP_SESSION_ID' => 'ses321']
        );

        $json = $client->getResponse()->getContent();
        $res = json_decode($json, true);

        $this->assertEquals('error', $res['result']);
        $this->assertEquals(150810008, $res['code']);
        $this->assertEquals('Session not found', $res['msg']);
    }

    /**
     * 測試產生存取碼，透過授權碼方式與更新碼方式
     */
    public function testGenerateTokenWithCodeAndRefreshToken()
    {
        $client = $this->createClient();

        $client->request(
            'GET',
            '/api/oauth2/authenticate?response_type=code&client_id=abc12345&redirect_uri=abc54321&state=aaaa',
            [],
            [],
            ['HTTP_SESSION_ID' => 'ses123']
        );

        $json = $client->getResponse()->getContent();
        $res = json_decode($json, true);

        // 授權碼
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'abc54321',
            'code' => $res['ret']['code']
        ];
        $headers = [
            'HTTP_SESSION_ID' => 'ses123',
            'HTTP_AUTHORIZATION' => 'Basic YWJjMTIzNDU6c2VjcmV0LXRlc3Q='
        ];
        $client->request('POST', '/api/oauth2/token', $params, [], $headers);

        $json = $client->getResponse()->getContent();
        $res = json_decode($json, true);

        $this->assertEquals('ok', $res['result']);
        $this->assertNotEmpty($res['ret']['access_token']);
        $this->assertEquals('text', $res['ret']['token_type']);
        $this->assertEquals(Oauth2Server::ACCESS_TOKEN_TTL, $res['ret']['expires_in']);
        $this->assertNotEmpty($res['ret']['refresh_token']);

        $accessToken = $res['ret']['access_token'];
        $refreshToken = $res['ret']['refresh_token'];

        // 更新碼
        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        $headers = [
            'HTTP_SESSION_ID' => 'ses123',
            'HTTP_AUTHORIZATION' => 'Basic YWJjMTIzNDU6c2VjcmV0LXRlc3Q='
        ];
        $client->request('POST', '/api/oauth2/token', $params, [], $headers);

        $json = $client->getResponse()->getContent();
        $res = json_decode($json, true);

        $this->assertEquals('ok', $res['result']);
        $this->assertNotEquals($accessToken, $res['ret']['access_token']);
        $this->assertEquals('text', $res['ret']['token_type']);
        $this->assertEquals(Oauth2Server::ACCESS_TOKEN_TTL, $res['ret']['expires_in']);
        $this->assertEquals($refreshToken, $res['ret']['refresh_token']);
    }

    /**
     * 測試產生存取碼，透過 session id
     */
    public function testGenerateTokenWithSessionId()
    {
        $client = $this->createClient();

        $params = ['grant_type' => 'password'];
        $headers = [
            'HTTP_SESSION_ID' => 'ses123',
            'HTTP_AUTHORIZATION' => 'Basic YWJjMTIzNDU6c2VjcmV0LXRlc3Q='
        ];
        $client->request('POST', '/api/oauth2/token', $params, [], $headers);

        $json = $client->getResponse()->getContent();
        $res = json_decode($json, true);

        $this->assertEquals('ok', $res['result']);
        $this->assertNotEmpty($res['ret']['access_token']);
        $this->assertEquals('text', $res['ret']['token_type']);
        $this->assertEquals(Oauth2Server::ACCESS_TOKEN_TTL, $res['ret']['expires_in']);
        $this->assertNotEmpty($res['ret']['refresh_token']);
    }

    /**
     * 測試由存取碼取得 session data
     */
    public function testGetSessionDataActionByAccessToken()
    {
        //建立 session 資料
        $client = $this->createClient();
        $client->request('POST', '/api/user/7/session');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);
        $sessionId = $out['ret']['session']['id'];

        //產生授權碼
        $client->request(
            'GET',
            '/api/oauth2/authenticate?response_type=code&client_id=abc12345&redirect_uri=abc54321&state=OK',
            [],
            [],
            ['HTTP_SESSION_ID' => $sessionId]
        );

        $json = $client->getResponse()->getContent();
        $res = json_decode($json, true);
        $code = $res['ret']['code'];

        //產生存取碼
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'abc54321',
            'code' => $code
        ];
        $headers = [
            'HTTP_SESSION_ID' => $sessionId,
            'HTTP_AUTHORIZATION' => 'Basic YWJjMTIzNDU6c2VjcmV0LXRlc3Q='
        ];
        $client->request('POST', '/api/oauth2/token', $params, [], $headers);
        $json = $client->getResponse()->getContent();
        $res = json_decode($json, true);
        $this->assertNotNull($res['ret']['access_token']);

        //由存取碼(Access token)取得資料
        $token = $res['ret']['access_token'];
        $client->request('GET', '/api/oauth2/user_by_token?token='.$token.'&client_id=abc12345');
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $res['result']);
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
    }
}
