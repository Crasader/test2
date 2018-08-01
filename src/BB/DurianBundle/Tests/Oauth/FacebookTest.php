<?php

namespace BB\DurianBundle\Tests\Oauth;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Oauth\Facebook;
use Buzz\Message\Response;

class FacebookTest extends DurianTestCase
{
    /**
     * 測試取得access token
     */
    public function testGetToken()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = 'access_token=2.00TRSNbC0AvLjnd3f8afc5c4OobWRE&expires=5100409';
        $response->setContent($responseContent);

        $oauthProvider = new Facebook(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'graph.facebook.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $ret = $oauthProvider->getToken('40d73015575407bf5a91a3db32e51a57');

        $this->assertEquals('2.00TRSNbC0AvLjnd3f8afc5c4OobWRE', $ret);
    }

    /**
     * 測試取得access token，觸發RuntimeException
     */
    public function testGetTokenConnectionError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Oauth provider connection error',
            150230011
        );

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'error' => [
                'message' => 'Oauth provider connection error',
                'type'    => 'RuntimeException',
                'code'    => 101
            ]
        ];
        $response->setContent(json_encode($responseContent));

        $oauthProvider = new Facebook(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'graph.facebook.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $ret = $oauthProvider->getToken('40d73015575407bf5a91a3db32e51a57');
    }

    /**
     * 測試取得access token，發request沒噴錯
     */
    public function testGetTokenRequestWithoutException()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = [
            'status' => [
                'message' => 'test'
            ]
        ];
        $response->setContent(json_encode($responseContent));

        $oauthProvider = new Facebook(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'graph.facebook.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $ret = $oauthProvider->getToken('40d73015575407bf5a91a3db32e51a57');

        $this->assertNull($ret);
    }

    /**
     * 測試使用非法的code來取得access token
     */
    public function testGetTokenWithInvalidCode()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid authorization code', 150230009);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $responseContent = array(
            'error' => array(
                'message' => 'This authorization code has expired.',
                'type'    => 'OAuthException',
                'code'    => 100
            )
        );
        $response->setContent(json_encode($responseContent));

        $oauthProvider = new Facebook(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $oauthProvider->getToken('31889f6b27487bc17e63d2aebf6173fa');
    }

    /**
     * 測試取得使用者資料
     */
    public function testGetUserProfile()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $content = array(
            'id'           => '100000129741',
            'name'         => 'Steven Chou',
            'first_name'   => 'Steven',
            'last_name'    => 'Chou',
            'link'         => 'https://www.facebook.com/steven.chou.9527',
            'name'         => 'steven.chou.9527',
            'gender'       => 'male',
            'timezone'     => 8,
            'locale'       => 'zh_TW',
            'verified'     => true,
            'updated_time' => '2013-09-02T04:07:53+0000'
        );

        $response->setContent(json_encode($content));

        $oauthProvider = new Facebook(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $accessToken = '2.00TRSNbC0AvLjnd3f8afc5c4OobWRE';
        $ret = $oauthProvider->getUserProfile($accessToken);

        $this->assertEquals('facebook', $ret['vendor']);
        $this->assertEquals('steven.chou.9527', $ret['username']);
        $this->assertEquals('2.00TRSNbC0AvLjnd3f8afc5c4OobWRE', $ret['access_token']);
        $this->assertEquals('100000129741', $ret['openid']);
    }

    /**
     * 測試利用code取得使用者資料
     */
    public function testGetUserProfileByCode()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = [
            'id' => '100000129741',
            'name' => 'Steven Chou'
        ];
        $response->setContent(json_encode($content));

        $oauthProvider = $this->getMockBuilder('BB\DurianBundle\Oauth\Facebook')
            ->disableOriginalConstructor()
            ->setMethods(['getToken'])
            ->getMock();
        $oauthProvider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue('2.00TRSNbC0AvLjnd3f8afc5c4OobWRE'));
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $ret = $oauthProvider->getUserProfileByCode('40d73015575407bf5a91a3db32e51a57');

        $this->assertEquals('facebook', $ret['vendor']);
        $this->assertEquals($content['name'], $ret['username']);
        $this->assertEquals('2.00TRSNbC0AvLjnd3f8afc5c4OobWRE', $ret['access_token']);
        $this->assertEquals($content['id'], $ret['openid']);
    }

    /**
     * 測試用非法的access token取得使用者資料
     */
    public function testGetUserProfileWithInvalidAccessToken()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid access token', 150230010);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $content = array(
            'error' => array(
                'message' => 'The access token could not be decrypted',
                'type'    => 'OAuthException',
                'code'    => 190
             )
        );
        $response->setContent(json_encode($content));

        $oauthProvider = new Facebook(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $accessToken = '2.00TRSNbC0AvLjnd3f8afc5c4OobWRE';
        $oauthProvider->getUserProfile($accessToken);
    }
}
