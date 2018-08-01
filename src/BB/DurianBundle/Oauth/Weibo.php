<?php
namespace BB\DurianBundle\Oauth;

use BB\DurianBundle\Oauth\AbstractOauthProvider;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;

class Weibo extends AbstractOauthProvider
{
    /* 微博error code */
    const INVALID_AUTHORIZATION_CODE = 21325;

    const INVALID_ACCESS_TOKEN = 10006;

    const INVALID_UID = 10017;

    /**
     * 取得access token跟openid
     * @param  string $code
     * @return Array
     */
    public function getTokenAndOpenid($code)
    {
        /* 準備request參數, 取得access token */
        $parameters = array(
            'client_id'     => $this->appId,
            'client_secret' => $this->appKey,
            'grant_type'    => 'authorization_code',
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

        $request = new FormRequest('POST', '/oauth2/access_token', $this->ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->domain}");

        $client->send($request, $response);
        $ret = json_decode($response->getContent(), true);
        $this->checkResponse($ret);
        $this->response = null;

        return array(
            'access_token' => $ret['access_token'],
            'openid'       => $ret['uid']
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
            'access_token' => $accessToken,
            'uid'          => $openid
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

        $request = new FormRequest('GET', '/2/users/show.json', $this->ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->domain}");
        $client->send($request, $response);

        $ret = json_decode($response->getContent(), true);
        $this->checkResponse($ret);
        $this->response = null;

        /* 回傳值取要用的資料 */
        return array(
            'vendor'       => 'weibo',
            'username'     => $ret['name'],
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
        $ret = $this->getTokenAndOpenid($code);
        $token = $ret['access_token'];
        $openid = $ret['openid'];

        return $this->getUserProfile($token, $openid);
    }

    /**
     * 檢查發request有無噴錯
     * @param  Array $ret
     */
    private function checkResponse($ret)
    {
        if (isset($ret['error'])) {
            if ($ret['error_code'] == self::INVALID_AUTHORIZATION_CODE) {
                throw new \InvalidArgumentException('Invalid authorization code', 150230009);
            }

            if ($ret['error_code'] == self::INVALID_ACCESS_TOKEN) {
                throw new \InvalidArgumentException('Invalid access token', 150230010);
            }

            if ($ret['error_code'] == self::INVALID_UID) {
                throw new \InvalidArgumentException('Invalid oauth openid', 150230006);
            }

            throw new \RuntimeException('Oauth provider connection error', 150230011);
        }
    }
}
