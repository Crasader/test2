<?php

namespace BB\DurianBundle\Tests\Oauth2;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Oauth2\Server as Oauth2Server;
use Symfony\Component\HttpFoundation\Request;

class ServerTest extends WebTestCase
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
        $redisOauth2->set('client:abc12345:access_token:testtoken', 'ses123');
    }

    /**
     * 測試建立 Oauth2 client
     */
    public function testCreateClient()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $client = $server->createClient('test-name', 'fake-uri', '2');

        $this->assertNotEmpty($client['id']);
        $this->assertNotEmpty($client['secret']);
        $this->assertEquals('test-name', $client['name']);
        $this->assertEquals('fake-uri', $client['redirect_uri']);
        $this->assertEquals('2', $client['domain']);

        $redis = $container->get('snc_redis.oauth2');

        $data = $redis->hgetall(sprintf('client:%s', $client['id']));
        $this->assertEquals($client['id'], $data['id']);
        $this->assertEquals($client['secret'], $data['secret']);
        $this->assertEquals('test-name', $data['name']);
        $this->assertEquals('fake-uri', $data['redirect_uri']);
        $this->assertEquals('2', $data['domain']);

        $this->assertTrue($redis->sismember('clients', $client['id']));
    }

    /**
     * 測試編輯 Oauth2 client
     */
    public function testEditClient()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $data = [
            'name' => 'new-name',
            'redirect_uri' => 'new-uri'
        ];
        $client = $server->editClient('abc12345', $data);

        $this->assertEquals('abc12345', $client['id']);
        $this->assertFalse(isset($client['secret']));
        $this->assertEquals('new-name', $client['name']);
        $this->assertEquals('new-uri', $client['redirect_uri']);

        $redis = $container->get('snc_redis.oauth2');

        $client = $redis->hgetall(sprintf('client:%s', $client['id']));
        $this->assertEquals('abc12345', $client['id']);
        $this->assertEquals('secret-test', $client['secret']);
        $this->assertEquals('new-name', $client['name']);
        $this->assertEquals('new-uri', $client['redirect_uri']);
    }

    /**
     * 測試刪除 Oauth2 client
     */
    public function testRemoveClient()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $server->removeClient('abc12345');

        $redis = $container->get('snc_redis.oauth2');

        $this->assertFalse($redis->exists('client:abc12345'));
        $this->assertFalse($redis->sismember('clients', 'abc12345'));
    }

    /**
     * 測試取得 Oauth2 client
     */
    public function testGetClient()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $client = $server->getClient('abc12345');

        $this->assertEquals('abc12345', $client['id']);
        $this->assertFalse(isset($client['secret']));
        $this->assertEquals('just for test', $client['name']);
        $this->assertEquals('abc54321', $client['redirect_uri']);

        $client = $server->getClient('abc12345', true);

        $this->assertEquals('abc12345', $client['id']);
        $this->assertEquals('secret-test', $client['secret']);
        $this->assertEquals('just for test', $client['name']);
        $this->assertEquals('abc54321', $client['redirect_uri']);
    }

    /**
     * 測試取得 Oauth2 client, 但找不到 client
     */
    public function testGetClientWithNoClientFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No client found',
            150810003
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $server->getClient('abc12345111');
    }

    /**
     * 測試取得 Oauth2 client 一覽
     */
    public function testGetClients()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');
        $redis = $container->get('snc_redis.oauth2');

        $this->assertEquals(1, $redis->scard('clients'));

        // 故意加入不存在的 client, 不會回傳
        $redis->sadd('clients', 'abc');

        $client = $server->createClient('a', 'b', '9');

        $this->assertEquals(3, $redis->scard('clients'));

        $clients = $server->getClients();

        // 排序確保資料順序無誤
        usort($clients, function ($a, $b) {
            if ($a['name'] < $b['name']) {
                return -1;
            }

            return 1;
        });

        $this->assertCount(2, $clients);
        $this->assertEquals($client['id'], $clients[0]['id']);
        $this->assertFalse(isset($clients[0]['secret']));
        $this->assertEquals('a', $clients[0]['name']);
        $this->assertEquals('b', $clients[0]['redirect_uri']);
        $this->assertEquals('9', $clients[0]['domain']);
        $this->assertEquals('abc12345', $clients[1]['id']);
        $this->assertFalse(isset($clients[1]['secret']));
        $this->assertEquals('just for test', $clients[1]['name']);
        $this->assertEquals('abc54321', $clients[1]['redirect_uri']);
        $this->assertEquals('2', $clients[1]['domain']);

        $this->assertEquals(2, $redis->scard('clients'));
    }

    /**
     * 測試採用授權碼模式認證
     */
    public function testHandleAuthorizationCode()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $query = [
            'client_id' => 'abc12345',
            'redirect_uri' => 'abc54321'
        ];
        $header = ['HTTP_SESSION_ID' => 'ses123'];
        $request = new Request($query, [], [], [], [], $header);
        $ret = $server->handleAuthorizationCode($request);

        $this->assertEquals('abc54321', $ret['redirect_uri']);
        $this->assertNotEmpty($ret['code']);

        $redis = $container->get('snc_redis.oauth2');
        $key = sprintf('client:%s:authenticated_code:%s', 'abc12345', $ret['code']);
        $this->assertEquals('ses123', $redis->get($key));
    }

    /**
     * 測試採用授權碼模式認證, 但未帶入 client_id
     */
    public function testHandleAuthorizationCodeWithInvalidClientId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No client_id specified',
            150810006
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $query = ['redirect_uri' => 'abc12345'];
        $header = ['HTTP_SESSION_ID' => 'ses123'];
        $request = new Request($query, [], [], [], [], $header);
        $server->handleAuthorizationCode($request);
    }

    /**
     * 測試採用授權碼模式認證, 但未帶入 redirect_uri
     */
    public function testHandleAuthorizationCodeWithInvalidRedirectUri()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No redirect_uri specified',
            150810013
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $query = ['client_id' => 'abc12345'];
        $header = ['HTTP_SESSION_ID' => 'ses123'];
        $request = new Request($query, [], [], [], [], $header);
        $server->handleAuthorizationCode($request);
    }

    /**
     * 測試採用授權碼模式認證, 但 redirect_uri 比對不符合
     */
    public function testHandleAuthorizationCodeButRedirectUriNotMatch()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Redirect_uri not match',
            150810014
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $query = [
            'client_id' => 'abc12345',
            'redirect_uri' => 'abc5432'
        ];
        $header = ['HTTP_SESSION_ID' => 'ses123'];
        $request = new Request($query, [], [], [], [], $header);
        $server->handleAuthorizationCode($request);
    }

    /**
     * 測試驗證表頭的基本授權
     */
    public function testVerifyAuthorization()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');
        $ret = $server->verifyAuthorization('abc12345', 'secret-test');

        $this->assertNull($ret);
    }

    /**
     * 測試驗證表頭的基本授權, 但 secret 比對不符合
     */
    public function testVerifyAuthorizationButSecretNotMatch()
    {
        $this->setExpectedException(
            'RuntimeException',
            'ClientSecret not match',
            150810015
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');
        $server->verifyAuthorization('abc12345', 'secret-test2');
    }

    /**
     * 測試透過授權碼給予存取碼
     */
    public function testGrantByAuthorizationCode()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $redis = $container->get('snc_redis.oauth2');
        $redis->set('client:abc12345:authenticated_code:aaaaaabbbbbb', 'ses123');

        $params = [
            'code' => 'aaaaaabbbbbb',
            'redirect_uri' => 'abc54321'
        ];
        $request = new Request([], $params);
        $ret = $server->grantByAuthorizationCode($request, 'abc12345');

        $this->assertNotEmpty($ret['access_token']);
        $this->assertEquals('text', $ret['token_type']);
        $this->assertEquals(Oauth2Server::ACCESS_TOKEN_TTL, $ret['expires_in']);
        $this->assertNotEmpty($ret['refresh_token']);

        $key = 'client:abc12345:access_token:' . $ret['access_token'];
        $sid = $redis->get($key);
        $ttl = $redis->ttl($key);
        $this->assertEquals('ses123', $sid);
        $this->assertLessThanOrEqual(Oauth2Server::ACCESS_TOKEN_TTL, $ttl);

        $key = 'client:abc12345:refresh_token:' . $ret['refresh_token'];
        $sid = $redis->get($key);
        $ttl = $redis->ttl($key);
        $this->assertEquals('ses123', $sid);
        $this->assertLessThanOrEqual(Oauth2Server::REFRESH_TOKEN_TTL, $ttl);
    }

    /**
     * 測試透過授權碼給予存取碼, 但未帶入 redirect_uri
     */
    public function testGrantByAuthorizationCodeWithInvalidRedirectUri()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No redirect_uri specified',
            150810016
        );

        $server = new Oauth2Server;

        $params = ['code' => 'aaaaaabbbbbb'];
        $request = new Request([], $params);
        $server->grantByAuthorizationCode($request, 'abc12345');
    }

    /**
     * 測試透過授權碼給予存取碼, 但未帶入 code
     */
    public function testGrantByAuthorizationCodeWithInvalidCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No code specified',
            150810017
        );

        $server = new Oauth2Server;

        $params = ['redirect_uri' => 'abc54321'];
        $request = new Request([], $params);
        $server->grantByAuthorizationCode($request, 'abc12345');
    }

    /**
     * 測試透過授權碼給予存取碼, 但 redirect_uri 比對不符合
     */
    public function testGrantByAuthorizationCodeButRedirectUriNotMatch()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Redirect_uri not match',
            150810018
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $params = [
            'code' => 'aaaaabbbbb',
            'redirect_uri' => 'abc5432'
        ];
        $request = new Request([], $params);
        $server->grantByAuthorizationCode($request, 'abc12345');
    }

    /**
     * 測試透過授權碼給予存取碼, 但尚未授權
     */
    public function testGrantByAuthorizationCodeWithUnauthorizedClient()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Unauthorized client',
            150810019
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $params = [
            'code' => 'i am not exists',
            'redirect_uri' => 'abc54321'
        ];
        $request = new Request([], $params);
        $server->grantByAuthorizationCode($request, 'abc12345');
    }

    /**
     * 測試透過更新碼取得存取碼
     */
    public function testGrantByRefreshCode()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $redis = $container->get('snc_redis.oauth2');
        $redis->set('client:abc12345:refresh_token:tokenfortest', 'ses123');

        $params = ['refresh_token' => 'tokenfortest'];
        $request = new Request([], $params);
        $ret = $server->grantByRefreshCode($request, 'abc12345');

        $this->assertNotEmpty($ret['access_token']);
        $this->assertEquals('text', $ret['token_type']);
        $this->assertEquals(Oauth2Server::ACCESS_TOKEN_TTL, $ret['expires_in']);
        $this->assertEquals('tokenfortest', $ret['refresh_token']);

        $key = 'client:abc12345:access_token:' . $ret['access_token'];
        $sid = $redis->get($key);
        $ttl = $redis->ttl($key);
        $this->assertEquals('ses123', $sid);
        $this->assertLessThanOrEqual(Oauth2Server::ACCESS_TOKEN_TTL, $ttl);
    }

    /**
     * 測試透過更新碼取得存取碼, 但未帶入 refresh_token
     */
    public function testGrantByRefreshCodeWithInvalidRefreshToken()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No refresh_token specified',
            150810021
        );

        $server = new Oauth2Server;
        $server->grantByRefreshCode(new Request, 'abc12345');
    }

    /**
     * 測試透過更新碼取得存取碼, 但找不到 refresh_token
     */
    public function testGrantByRefreshCodeWithNoSuchRefreshToken()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such refresh_token',
            150810022
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $params = ['refresh_token' => '123'];
        $request = new Request([], $params);
        $server->grantByRefreshCode($request, 'abc12345');
    }

    /**
     * 測試透過更新碼取得存取碼, 但 session 已經逾期
     */
    public function testGrantByRefreshCodeWithSessionNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Session not found',
            150810023
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $redis = $container->get('snc_redis.oauth2');
        $redis->set('client:abc12345:refresh_token:tokenfortest', 'sesnotfound');

        $params = ['refresh_token' => 'tokenfortest'];
        $request = new Request([], $params);
        $server->grantByRefreshCode($request, 'abc12345');
    }

    /**
     * 測試透過使用者SessionId取得存取碼
     */
    public function testGrantBySession()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $headers = ['HTTP_SESSION_ID' => 'ses123'];
        $request = new Request([], [], [], [], [], $headers);
        $ret = $server->grantBySession($request, 'abc12345');

        $this->assertNotEmpty($ret['access_token']);
        $this->assertEquals('text', $ret['token_type']);
        $this->assertEquals(Oauth2Server::ACCESS_TOKEN_TTL, $ret['expires_in']);
        $this->assertNotEmpty($ret['refresh_token']);

        $redis = $container->get('snc_redis.oauth2');
        $key = 'client:abc12345:access_token:' . $ret['access_token'];
        $sid = $redis->get($key);
        $ttl = $redis->ttl($key);
        $this->assertEquals('ses123', $sid);
        $this->assertLessThanOrEqual(Oauth2Server::ACCESS_TOKEN_TTL, $ttl);

        $key = 'client:abc12345:refresh_token:' . $ret['refresh_token'];
        $sid = $redis->get($key);
        $ttl = $redis->ttl($key);
        $this->assertEquals('ses123', $sid);
        $this->assertLessThanOrEqual(Oauth2Server::REFRESH_TOKEN_TTL, $ttl);
    }

    /**
     * 測試透過使用者SessionId取得存取碼, 但未帶入 session id
     */
    public function testGrantBySessionWithInvalidSessionId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No session-id specified',
            150810024
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $server->grantBySession(new Request, 'abc12345');
    }

    /**
     * 測試透過使用者SessionId取得存取碼, 但 session 不存在
     */
    public function testGrantBySessionWithSessionNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Session not found',
            150810025
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $headers = ['HTTP_SESSION_ID' => 'ses1234'];
        $request = new Request([], [], [], [], [], $headers);
        $server->grantBySession($request, 'abc12345');
    }

    /**
     * 測試透過存取碼取得 sessionId
     */
    public function testGetSessionByToken()
    {
        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $clientId = 'abc12345';
        $token = 'testtoken';

        $sid = $server->getSessionByToken($clientId, $token);

        $this->assertEquals('ses123', $sid);
    }

    /**
     * 測試透過存取碼取得 sessionId , 輸入不存在的 client_id
     */
    public function testGetSessionByTokenWithUnexistClientId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No client found',
            150810035
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $clientId = 'abc';
        $token = 'testtoken';

        $server->getSessionByToken($clientId, $token);
    }

    /**
     * 測試透過存取碼取得 sessionId , 但 access token 驗證錯誤
     */
    public function testGetSessionByTokenWithAccessTokenNotMatch()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Access token not match',
            150810036
        );

        $container = $this->getContainer();
        $server = $container->get('durian.oauth2_server');

        $clientId = 'abc12345';
        $token = 'testtokenerror';

        $server->getSessionByToken($clientId, $token);
    }
}
