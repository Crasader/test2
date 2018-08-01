<?php

namespace BB\DurianBundle\Tests\Oauth;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Oauth\Qq;
use Buzz\Message\Response;

class QqTest extends DurianTestCase
{
    /**
     * 測試取得access token
     */
    public function testGetToken()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = 'access_token=67786062E4238ADF8B4B0FA3CB959BBB&'.
                   'expires_in=7776000&refresh_token=56DC5B6BC290A095758B8516C63EB931';
        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);
        $ret = $oauthProvider->getToken('2BEDCB1020ADBC946790D8927B731A02');

        $this->assertEquals('67786062E4238ADF8B4B0FA3CB959BBB', $ret['access_token']);
    }

    /**
     * 測試取得access token，發request沒噴錯
     */
    public function testGetTokenWithoutException()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = '{"message":"test"}';
        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );

        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);
        $ret = $oauthProvider->getToken('2BEDCB1020ADBC946790D8927B731A02');

        $this->assertNull($ret);
    }

    /**
     * 測試取得access token，AuthorizationCode重複使用觸發例外
     */
    public function testGetTokenWithReusedAuthorizationCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid authorization code',
            150230009
        );

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = '{"error":100020,"error_description":"Reused code"}';
        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);
        $ret = $oauthProvider->getToken('2BEDCB1020ADBC946790D8927B731A02');
    }


    /**
     * 測試利用非法code來取得access token
     */
    public function testGetTokenWithInvalidCode()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid authorization code', 150230009);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = 'callback( {"error":100019,"error_description":"code to access token error"} );';
        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);
        $ret = $oauthProvider->getToken('2BEDCB1020ADBC946790D8927B731A02');

        $this->assertEquals('67786062E4238ADF8B4B0FA3CB959BBB', $ret['access_token']);
    }

    /**
     * 測試取得openid
     */
    public function testGetTokenOpenidByToken()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = 'callback( {"client_id":"100507807","openid":"1A376CE57476A73F2FCA3BE9D5397DFC"} );';
        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);
        $ret = $oauthProvider->getOpenidByToken('67786062E4238ADF8B4B0FA3CB959BBB');

        $this->assertEquals('67786062E4238ADF8B4B0FA3CB959BBB', $ret['access_token']);
        $this->assertEquals('1A376CE57476A73F2FCA3BE9D5397DFC', $ret['openid']);
    }

    /**
     * 測試使用非法的access token來取得openid
     */
    public function testGetOpenIdByTokenWithInvalidToken()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid access token',
            150230010
        );

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $content = '{"error":-23,"error_description":"invalid access token"}';
        $response->setContent($content);

        $oauthProvider = new Qq(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $oauthProvider->getOpenidByToken('31889f6b27487bc17e63d2aebf6173fa');
    }

    /**
     * 測試取得openid，但token檢查有錯誤
     */
    public function testGetOpenIdByTokenWithCheckError()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid access token',
            150230010
        );

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $content = 'callback( {"error":100016,"error_description":"access token check failed"} );';
        $response->setContent($content);

        $oauthProvider = new Qq(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $oauthProvider->getOpenidByToken('31889f6b27487bc17e63d2aebf6173fa');
    }

    /**
     * 測試取得openid，觸發RuntimeException
     */
    public function testGetOpenIdByTokenWithConnectionError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Oauth provider connection error',
            150230011
        );

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $content = 'callback( {"error":10001,"error_description":"connection error"} );';
        $response->setContent($content);

        $oauthProvider = new Qq(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $oauthProvider->getOpenidByToken('31889f6b27487bc17e63d2aebf6173fa');
    }

    /**
     * 測試取得使用者資料
     */
    public function testGetUserProfile()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = '{'.
            '"ret": 0,'.
            '"msg": "",'.
            '"nickname": "2486870235",'.
            '"gender": "男",'.
            '"figureurl": "http:\/\/qzapp.qlogo.cn\/qzapp\/100507807\/1A376CE57476A73F2FCA3BE9D5397DFC\/30",'.
            '"figureurl_1": "http:\/\/qzapp.qlogo.cn\/qzapp\/100507807\/1A376CE57476A73F2FCA3BE9D5397DFC\/50",'.
            '"figureurl_2": "http:\/\/qzapp.qlogo.cn\/qzapp\/100507807\/1A376CE57476A73F2FCA3BE9D5397DFC\/100",'.
            '"figureurl_qq_1": "http:\/\/q.qlogo.cn\/qqapp\/100507807\/1A376CE57476A73F2FCA3BE9D5397DFC\/40",'.
            '"figureurl_qq_2": "http:\/\/q.qlogo.cn\/qqapp\/100507807\/1A376CE57476A73F2FCA3BE9D5397DFC\/100",'.
            '"is_yellow_vip": "0",'.
            '"vip": "0",'.
            '"yellow_vip_level": "0",'.
            '"level": "0",'.
            '"is_yellow_year_vip": "0"'.
        '}';

        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);
        $accessToken = '67786062E4238ADF8B4B0FA3CB959BBB';
        $openid = '1A376CE57476A73F2FCA3BE9D5397DFC';
        $ret = $oauthProvider->getUserProfile($accessToken, $openid);

        $this->assertEquals('qq', $ret['vendor']);
        $this->assertEquals('2486870235', $ret['username']);
        $this->assertEquals('67786062E4238ADF8B4B0FA3CB959BBB', $ret['access_token']);
        $this->assertEquals('1A376CE57476A73F2FCA3BE9D5397DFC', $ret['openid']);
    }

    /**
     * 測試用非法的access token取得使用者資料
     */
    public function testGetUserProfileWithInvalidToken()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid access token', 150230010);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = '{"ret":-23,"msg":"token is invalid"}';

        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);
        $accessToken = '67786062E4238ADF8B4B0FA3CB959BBB11';
        $openid = '1A376CE57476A73F2FCA3BE9D5397DFC';
        $oauthProvider->getUserProfile($accessToken, $openid);
    }

    /**
     * 測試用非法的openid取得使用者資料
     */
    public function testGetUserProfileWithInvalidOpenid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid oauth openid', 150230006);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = '{"ret":-1,"msg":"client request\'s parameters are invalid, invalid openid"}';

        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);
        $accessToken = '67786062E4238ADF8B4B0FA3CB959BBB11';
        $openid = '1A376CE57476A73F2FC';
        $oauthProvider->getUserProfile($accessToken, $openid);
    }

    /**
     * 測試取得使用者資料，觸發RuntimeException
     */
    public function testGetUserProfileWithConnectionError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Oauth provider connection error',
            150230011
        );

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = '{"ret":-2,"msg":"connection error"}';

        $response->setContent($content);

        $oauthProvider = new Qq(
            '100507807',
            '17b85caebc7bab056192e97ab36bf91c',
            'http://playesb.com',
            'graph.qq.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $accessToken = '67786062E4238ADF8B4B0FA3CB959BBB11';
        $openid = '1A376CE57476A73F2FC';
        $oauthProvider->getUserProfile($accessToken, $openid);
    }

    /**
     * 測試利用code取得使用者資料
     */
    public function testGetUserProfileByCode()
    {
        $accessToken = ['access_token' => '67786062E4238ADF8B4B0FA3CB959BBB'];

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $content = [
            'nickname' => '2486870235',
            'openid' => '1A376CE57476A73F2FCA3BE9D5397DFC'
        ];
        $response->setContent(json_encode($content));

        $oauthProvider = $this->getMockBuilder('BB\DurianBundle\Oauth\Qq')
            ->disableOriginalConstructor()
            ->setMethods(['getToken'])
            ->getMock();
        $oauthProvider->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($accessToken));

        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $ret = $oauthProvider->getUserProfileByCode('2BEDCB1020ADBC946790D8927B731A02');

        $this->assertEquals('qq', $ret['vendor']);
        $this->assertEquals('2486870235', $ret['username']);
        $this->assertEquals('67786062E4238ADF8B4B0FA3CB959BBB', $ret['access_token']);
        $this->assertEquals('1A376CE57476A73F2FCA3BE9D5397DFC', $ret['openid']);
    }
}
