<?php
namespace BB\DurianBundle\Oauth;

use BB\DurianBundle\Oauth\AbstractOauthProvider;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;

class Qq extends AbstractOauthProvider
{
    /* QQ error code */
    const ACCESS_TOKEN_CHECK_ERROR = 100016;

    const INVALID_AUTHORIZATION_CODE = 100019;

    const AUTHORIZATION_CODE_REUSED = 100020;

    const INVALID_ACCESS_TOKEN = -23;

    const INVALID_UID = -1;

    /**
     * 取得accesstoken
     * @param  string $code
     * @return Array
     */
    public function getToken($code)
    {
        /* 準備request參數, 取得access token */
        $parameters = array(
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->appId,
            'client_secret' => $this->appKey,
            'redirect_uri'  => $this->redirectUrl,
            'code'          => $code
        );

        if ($this->client) {
            $client = $this->client;
        } else {
            $client = new Curl();
        }

        if ($this->response) {
            $response = $this->response;
        } else {
            $response = new Response();
        }

        $request = new FormRequest('GET', '/oauth2.0/token', $this->ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->domain}");
        $client->send($request, $response);

        $match = null;
        $responseContent = $response->getContent();

        $isFailed = preg_match('/{.*}/', $responseContent, $match);
        if ($isFailed) {
            $ret = json_decode($match[0], true);
            $this->checkResponse($ret);

            return;
        }

        $arrayRet = null;
        parse_str($responseContent, $arrayRet);

        return array('access_token' => $arrayRet['access_token']);
    }

    /**
     * 取得openid
     * @param  string $token
     * @return Array
     */
    public function getOpenidByToken($token)
    {
        $parameters = array('access_token' => $token);

        if ($this->client) {
            $client = $this->client;
        } else {
            $client = new Curl();
        }

        if ($this->response) {
            $response = $this->response;
        } else {
            $response = new Response();
        }

        $request = new FormRequest('GET', '/oauth2.0/me', $this->ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->domain}");
        $client->send($request, $response);

        $match = null;
        $responseContent = $response->getContent();

        preg_match('/{.*}/', $responseContent, $match);
        $ret = json_decode($match[0], true);

        $this->checkResponse($ret);

        return array(
            'access_token' => $token,
            'openid' => $ret['openid'],
        );
    }

    /**
     * 取得使用者資料
     * @param  string $accessToken
     * @param  string $openid
     * @return Array
     */
    public function getUserProfile($accessToken, $openid)
    {
        $parameters = array(
            'access_token'       => $accessToken,
            'oauth_consumer_key' => $this->appId,
            'openid'             => $openid
        );

        if ($this->client) {
            $client = $this->client;
        } else {
            $client = new Curl();
        }

        if ($this->response) {
            $response = $this->response;
        } else {
            $response = new Response();
        }

        $request = new FormRequest('GET', '/user/get_user_info', $this->ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->domain}");
        $client->send($request, $response);

        $ret = json_decode($response->getContent(), true);

        $this->checkResponse($ret);

        /* 回傳值取要用的資料 */
        return array(
            'vendor'       => 'qq',
            'username'     => $ret['nickname'],
            'access_token' => $accessToken,
            'openid'       => $openid
        );
    }

    /**
     * 利用code取得使用者資料
     * @param  string $code
     * @return Array
     */
    public function getUserProfileByCode($code)
    {
        /* 準備request參數, 取得accesstoken */
        $ret = $this->getToken($code);
        $token = $ret['access_token'];

        $ret = $this->getOpenidByToken($token);

        return $this->getUserProfile($token, $ret['openid']);
    }

    /**
     * 檢查發request有無噴錯
     * @param  Array $ret
     */
    private function checkResponse($ret)
    {
        if (isset($ret['error'])) {
            if ($ret['error'] == self::INVALID_AUTHORIZATION_CODE) {
                throw new \InvalidArgumentException('Invalid authorization code', 150230009);
            }

            if ($ret['error'] == self::AUTHORIZATION_CODE_REUSED) {
                throw new \InvalidArgumentException('Invalid authorization code', 150230009);
            }

            if ($ret['error'] == self::INVALID_ACCESS_TOKEN) {
                throw new \InvalidArgumentException('Invalid access token', 150230010);
            }

            if ($ret['error'] == self::ACCESS_TOKEN_CHECK_ERROR) {
                throw new \InvalidArgumentException('Invalid access token', 150230010);
            }

            throw new \RuntimeException('Oauth provider connection error', 150230011);
        }

        if (isset($ret['ret']) && $ret['ret'] != 0) {
            if ($ret['ret'] == self::INVALID_ACCESS_TOKEN) {
                throw new \InvalidArgumentException('Invalid access token', 150230010);
            }

            if ($ret['ret'] == self::INVALID_UID) {
                throw new \InvalidArgumentException('Invalid oauth openid', 150230006);
            }

            throw new \RuntimeException('Oauth provider connection error', 150230011);
        }
    }
}
