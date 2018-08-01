<?php

namespace BB\DurianBundle\Oauth2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Server
{
    // 授權碼存活時間 (10 分鐘，單位：秒)
    const AUTHENTICATED_CODE_TTL = 600;

    // 存取碼存活時間 (1 小時，單位：秒)
    const ACCESS_TOKEN_TTL = 3600;

    // 更新碼存活時間 (1 天，單位：秒)
    const REFRESH_TOKEN_TTL = 86400;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Sets the Container associated with this Controller.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * 建立 Oauth2 client
     *
     * @param string $name 名稱
     * @param string $redirectUri 導向網址
     * @return array
     */
    public function createClient($name, $redirectUri, $domain)
    {
        $redis = $this->getContainer()->get('snc_redis.oauth2');

        do {
            $rand = openssl_random_pseudo_bytes(20);
            $clientId = sha1($rand);

            $key = sprintf('client:%s', $clientId);
            $result = $redis->hsetnx($key, 'id', $clientId);
        } while (!$result);

        $rand = openssl_random_pseudo_bytes(20);
        $clientSecret = sha1($rand);

        $client = [
            'id' => $clientId,
            'secret' => $clientSecret,
            'name' => $name,
            'redirect_uri' => $redirectUri,
            'domain' => $domain
        ];
        $redis->hmset($key, $client);
        $redis->sadd('clients', $clientId);

        return $client;
    }

    /**
     * 編輯 Oauth2 client
     *
     * @param string $clientId Client編號
     * @param array $data 要修改的欄位與值
     * @return array
     */
    public function editClient($clientId, $data)
    {
        $redis = $this->getContainer()->get('snc_redis.oauth2');

        $key = sprintf('client:%s', $clientId);
        $redis->hmset($key, $data);

        $client = $redis->hgetall($key);

        unset($client['secret']);

        return $client;
    }

    /**
     * 刪除 Oauth2 client
     *
     * @param string $clientId Client編號
     */
    public function removeClient($clientId)
    {
        $redis = $this->getContainer()->get('snc_redis.oauth2');

        $redis->srem('clients', $clientId);
        $redis->del('client:' . $clientId);
    }

    /**
     * 取得 Oauth2 client
     *
     * @param string $clientId Client編號
     * @param boolean $returnSecret 是否回傳密碼
     * @return array
     */
    public function getClient($clientId, $returnSecret = false)
    {
        $redis = $this->getContainer()->get('snc_redis.oauth2');

        $key = sprintf('client:%s', $clientId);
        $client = $redis->hgetall($key);

        if (!$client) {
            throw new \RuntimeException('No client found', 150810003);
        }

        if (!$returnSecret) {
            unset($client['secret']);
        }

        return $client;
    }

    /**
     * 取得 Oauth2 client 一覽
     *
     * @return array
     */
    public function getClients()
    {
        $redis = $this->getContainer()->get('snc_redis.oauth2');

        $clients = [];
        $clientIdArray = $redis->smembers('clients');
        foreach ($clientIdArray as $clientId) {
            $client = $redis->hgetall('client:' . $clientId);

            if (!$client) {
                $redis->srem('clients', $clientId);

                continue;
            }

            unset($client['secret']);

            $clients[] = $client;
        }

        return $clients;
    }

    /**
     * 採用授權碼模式認證
     *
     * @param Request $request
     * @return array
     */
    public function handleAuthorizationCode(Request $request)
    {
        $query = $request->query;
        $clientId = $query->get('client_id');
        $redirectUri = $query->get('redirect_uri');
        $sessionId = $request->headers->get('session-id');

        if (!$clientId) {
            throw new \InvalidArgumentException('No client_id specified', 150810006);
        }

        if (!$redirectUri) {
            throw new \InvalidArgumentException('No redirect_uri specified', 150810013);
        }

        $redis = $this->getContainer()->get('snc_redis.oauth2');
        $client = $this->getClient($clientId);

        if ($client['redirect_uri'] != $redirectUri) {
            throw new \RuntimeException('Redirect_uri not match', 150810014);
        }

        do {
            $rand = openssl_random_pseudo_bytes(22);
            $authenticatedCode = sha1($rand);

            $key = sprintf('client:%s:authenticated_code:%s', $clientId, $authenticatedCode);
            $result = $redis->setnx($key, $sessionId);
        } while (!$result);

        $redis->expire($key, self::AUTHENTICATED_CODE_TTL);

        return [
            'code' => $authenticatedCode,
            'redirect_uri' => $redirectUri
        ];
    }

    /**
     * 驗證表頭的基本授權(Basic Auth)
     *
     * @param string $clientId Client編號
     * @param string $clientSecret Client密碼
     */
    public function verifyAuthorization($clientId, $clientSecret)
    {
        $client = $this->getClient($clientId, true);

        if ($client['secret'] != $clientSecret) {
            throw new \RuntimeException('ClientSecret not match', 150810015);
        }
    }

    /**
     * 透過授權碼給予存取碼
     *
     * @param Request $request
     * @param string $clientId Client編號
     * @return array
     */
    public function grantByAuthorizationCode(Request $request, $clientId)
    {
        $request = $request->request;
        $redirectUri = $request->get('redirect_uri');
        $code = $request->get('code');

        if (!$redirectUri) {
            throw new \InvalidArgumentException('No redirect_uri specified', 150810016);
        }

        if (!$code) {
            throw new \InvalidArgumentException('No code specified', 150810017);
        }

        $redis = $this->getContainer()->get('snc_redis.oauth2');
        $client = $this->getClient($clientId);

        if ($client['redirect_uri'] != $redirectUri) {
            throw new \RuntimeException('Redirect_uri not match', 150810018);
        }

        $key = sprintf('client:%s:authenticated_code:%s', $clientId, $code);
        $sessionId = $redis->get($key);

        if (!$sessionId) {
            throw new \RuntimeException('Unauthorized client', 150810019);
        }

        $result = $redis->del($key);

        // 代表已經給過 access token (同分秒)
        if (!$result) {
            throw new \RuntimeException('AccessToken is already given', 150810020);
        }

        $accessToken = null;
        $refreshToken = null;

        do {
            $rand = openssl_random_pseudo_bytes(22);
            $accessToken = sha1($rand);

            $accessKey = sprintf('client:%s:access_token:%s', $clientId, $accessToken);
            $result = $redis->setnx($accessKey, $sessionId);
        } while (!$result);

        do {
            $rand = openssl_random_pseudo_bytes(22);
            $refreshToken = sha1($rand);

            $refreshKey = sprintf('client:%s:refresh_token:%s', $clientId, $refreshToken);
            $result = $redis->setnx($refreshKey, $sessionId);
        } while (!$result);

        $redis->expire($accessKey, self::ACCESS_TOKEN_TTL);
        $redis->expire($refreshKey, self::REFRESH_TOKEN_TTL);

        return [
            'access_token' => $accessToken,
            'token_type' => 'text',
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'refresh_token' => $refreshToken
        ];
    }

    /**
     * 透過更新碼取得存取碼
     *
     * @param Request $request
     * @param string $clientId Client編號
     * @return array
     */
    public function grantByRefreshCode(Request $request, $clientId)
    {
        $refreshToken = $request->request->get('refresh_token');

        if (!$refreshToken) {
            throw new \InvalidArgumentException('No refresh_token specified', 150810021);
        }

        $redis = $this->getContainer()->get('snc_redis.oauth2');

        $key = sprintf('client:%s:refresh_token:%s', $clientId, $refreshToken);
        $sessionId = $redis->get($key);

        if (!$sessionId) {
            throw new \RuntimeException('No such refresh_token', 150810022);
        }

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $exists = $sessionBroker->existsBySessionId($sessionId);

        if (!$exists) {
            throw new \RuntimeException('Session not found', 150810023);
        }

        do {
            $rand = openssl_random_pseudo_bytes(22);
            $accessToken = sha1($rand);

            $key = sprintf('client:%s:access_token:%s', $clientId, $accessToken);
            $result = $redis->setnx($key, $sessionId);
        } while (!$result);

        $redis->expire($key, self::ACCESS_TOKEN_TTL);

        return [
            'access_token' => $accessToken,
            'token_type' => 'text',
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'refresh_token' => $refreshToken
        ];
    }

    /**
     * 透過使用者SessionId取得存取碼
     *
     * @param Request $request
     * @param string $clientId Client編號
     * @return array
     */
    public function grantBySession(Request $request, $clientId)
    {
        $sessionId = $request->headers->get('session-id');

        if (!$sessionId) {
            throw new \InvalidArgumentException('No session-id specified', 150810024);
        }

        $sessionBroker = $this->getContainer()->get('durian.session_broker');
        $exists = $sessionBroker->existsBySessionId($sessionId);

        if (!$exists) {
            throw new \RuntimeException('Session not found', 150810025);
        }

        $redis = $this->getContainer()->get('snc_redis.oauth2');

        do {
            $rand = openssl_random_pseudo_bytes(22);
            $accessToken = sha1($rand);

            $accessKey = sprintf('client:%s:access_token:%s', $clientId, $accessToken);
            $result = $redis->setnx($accessKey, $sessionId);
        } while (!$result);

        do {
            $rand = openssl_random_pseudo_bytes(22);
            $refreshToken = sha1($rand);

            $refreshKey = sprintf('client:%s:refresh_token:%s', $clientId, $refreshToken);
            $result = $redis->setnx($refreshKey, $sessionId);
        } while (!$result);

        $redis->expire($accessKey, self::ACCESS_TOKEN_TTL);
        $redis->expire($refreshKey, self::REFRESH_TOKEN_TTL);

        return [
           'access_token' => $accessToken,
           'token_type' => 'text',
           'expires_in' => self::ACCESS_TOKEN_TTL,
           'refresh_token' => $refreshToken
        ];
    }

    /**
     * 透過存取碼取得 session
     *
     * @param string $clientId Client編號
     * @param string $token Access Token
     * @return string
     */
    public function getSessionByToken($clientId, $token)
    {
        $redis = $this->getContainer()->get('snc_redis.oauth2');

        $key = sprintf('client:%s', $clientId);
        $client = $redis->hgetall($key);

        if (!$client) {
            throw new \RuntimeException('No client found', 150810035);
        }

        $tokenKey = sprintf('client:%s:access_token:%s', $clientId, $token);
        $session = $redis->get($tokenKey);

        if (!$session) {
            throw new \RuntimeException('Access token not match', 150810036);
        }

        return $session;
    }
}
