<?php
namespace BB\DurianBundle\Oauth;

use BB\DurianBundle\Oauth\AbstractOauthProvider;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;

class Facebook extends AbstractOauthProvider
{
    /* facebook error code */
    const INVALID_AUTHORIZATION_CODE = 100;

    const INVALID_ACCESS_TOKEN = 190;

    /**
     * 取得access token
     * @param  string $code
     * @return Array
     */
    public function getToken($code)
    {
        /* 準備request參數, 取得access token */
        $parameters = array(
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

        $request = new FormRequest('GET', '/v2.0/oauth/access_token', $this->ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->domain}");

        $client->send($request, $response);

        $responseContent = $response->getContent();

        $isFailed = preg_match('/{.*}/', $responseContent, $match);
        if ($isFailed) {
            $ret = json_decode($match[0], true);
            $this->checkResponse($ret);

            return;
        }
        parse_str($response->getContent(), $ret);

        return $ret['access_token'];
    }

    /**
     * 取得使用者資料
     * @param  string $accessToken
     * @return Array
     */
    public function getUserProfile($accessToken)
    {
        // 若facebook app啟用"App Secret Proof for Server API calls"選項
        // 則需多送加密參數appsecret_proof, 以確保安全性
        $secretProof = hash_hmac('sha256', $accessToken, $this->appKey);
        $parameters = [
            'access_token' => $accessToken,
            'appsecret_proof' => $secretProof
        ];

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

        $request = new FormRequest('GET', '/v2.0/me', $this->ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->domain}");
        $client->send($request, $response);

        $ret = json_decode($response->getContent(), true);
        $this->checkResponse($ret);
        $this->response = null;

        /* 回傳值取要用的資料 */
        return array(
            'vendor'       => 'facebook',
            'username'     => $ret['name'],
            'access_token' => $accessToken,
            'openid'       => $ret['id']
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
        $token = $this->getToken($code);

        return $this->getUserProfile($token);
    }

    /**
     * 檢查發request有無噴錯
     * @param  Array $ret
     */
    private function checkResponse($ret)
    {
        if (isset($ret['error'])) {
            if ($ret['error']['code'] == self::INVALID_AUTHORIZATION_CODE) {
                throw new \InvalidArgumentException('Invalid authorization code', 150230009);
            }

            if ($ret['error']['code'] == self::INVALID_ACCESS_TOKEN) {
                throw new \InvalidArgumentException('Invalid access token', 150230010);
            }

            throw new \RuntimeException('Oauth provider connection error', 150230011);
        }
    }
}
