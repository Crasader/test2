<?php

namespace BB\DurianBundle\Tests\Oauth;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Oauth\Weibo;
use Buzz\Message\Response;

class WeiboTest extends DurianTestCase
{
    /**
     * 測試取得access token, openid
     */
    public function testGetTokenAndOpenid()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = array(
            "access_token" => "2.00TRSNbC0AvLjnd3f8afc5c4OobWRE",
            "remind_in"    => "157679999",
            "expires_in"   => "157679999",
            "uid"          => "2382158635"
        );
        $response->setContent(json_encode($responseContent));

        $oauthProvider = new Weibo(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $ret = $oauthProvider->getTokenAndOpenid('40d73015575407bf5a91a3db32e51a57');

        $this->assertEquals('2.00TRSNbC0AvLjnd3f8afc5c4OobWRE', $ret['access_token']);
        $this->assertEquals('2382158635', $ret['openid']);
    }

    /**
     * 測試使用非法的code來取得access token, openid
     */
    public function testGetTokenAndOpenidWithInvalidCode()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid authorization code', 150230009);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $responseContent = array(
            "error"             => "invalid_grant",
            "error_code"        => 21325,
            "request"           => "/oauth2/access_token",
            "error_uri"         => "/oauth2/access_token",
            "error_description" => "invalid authorization code:31889f6b27487bc17e63d2aebf6173fa"
        );
        $response->setContent(json_encode($responseContent));

        $oauthProvider = new Weibo(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $oauthProvider->getTokenAndOpenid('31889f6b27487bc17e63d2aebf6173fa');
    }

    /**
     * 測試取得使用者資料
     */
    public function testGetUserProfile()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $content = '{"id":2382158635,"idstr":"2382158635","class":1,'.
                   '"screen_name":"猛龙esball游戏城","name":"猛龙esball游戏城",'.
                   '"province":"100","city":"1000","location":"其他",'.
                   '"description":"猛龙esball线上游戏城 playesb.com","url":"",'.
                   '"profile_image_url":"http://tp4.sinaimg.cn/2382158635/50/40017692035/1",'.
                   '"profile_url":"playesball","domain":"playesball","weihao":"","gender":"m",'.
                   '"followers_count":261,"friends_count":131,"statuses_count":156,"favourites_count":0,'.
                   '"created_at":"Mon Sep 26 11:58:49 +0800 2011","following":false,"allow_all_act_msg":false,'.
                   '"geo_enabled":true,"verified":false,"verified_type":-1,"remark":"",'.
                   '"status":{"created_at":"Fri Aug 30 19:29:17 +0800 2013","id":3617078408225227,'.
                   '"mid":"3617078408225227","idstr":"3617078408225227",'.
                   '"text":"猛龙esball游戏城_水果大转轮_Fruit   http://t.cn/z8bzV1r (来 @土豆 关注我 http://t.cn/zQaJFHd )",'.
                   '"source":"<a href=\"http://app.weibo.com/t/feed/3icNS9\" rel=\"nofollow\">土豆网推视频</a>",'.
                   '"favorited":false,"truncated":false,"in_reply_to_status_id":"","in_reply_to_user_id":"",'.
                   '"in_reply_to_screen_name":"",'.
                   '"pic_urls":[{"thumbnail_pic":"http://ww3.sinaimg.cn/thumbnail/8dfcdb2bjw1e84wttj4sgj20cg0703zo.jpg"}],'.
                   '"thumbnail_pic":"http://ww3.sinaimg.cn/thumbnail/8dfcdb2bjw1e84wttj4sgj20cg0703zo.jpg",'.
                   '"bmiddle_pic":"http://ww3.sinaimg.cn/bmiddle/8dfcdb2bjw1e84wttj4sgj20cg0703zo.jpg",'.
                   '"original_pic":"http://ww3.sinaimg.cn/large/8dfcdb2bjw1e84wttj4sgj20cg0703zo.jpg","geo":null,'.
                   '"reposts_count":0,"comments_count":0,"attitudes_count":0,'.
                   '"mlevel":0,"visible":{"type":0,"list_id":0}},"ptype":0,'.
                   '"allow_all_comment":true,"avatar_large":"http://tp4.sinaimg.cn/2382158635/180/40017692035/1",'.
                   '"avatar_hd":"http://tp4.sinaimg.cn/2382158635/180/40017692035/1",'.
                   '"verified_reason":"","follow_me":false,'.
                   '"online_status":0,"bi_followers_count":80,"lang":"zh-cn","star":0,'.
                   '"mbtype":0,"mbrank":0,"block_word":0}';
        $response->setContent($content);

        $oauthProvider = new Weibo(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $accessToken = '2.00TRSNbC0AvLjnd3f8afc5c4OobWRE';
        $openid = '2382158635';
        $ret = $oauthProvider->getUserProfile($accessToken, $openid);

        $this->assertEquals('weibo', $ret['vendor']);
        $this->assertEquals('猛龙esball游戏城', $ret['username']);
        $this->assertEquals('2.00TRSNbC0AvLjnd3f8afc5c4OobWRE', $ret['access_token']);
        $this->assertEquals('2382158635', $ret['openid']);
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

        $content = '{"error":"parameter (uid)\'s value invalid,expect (long[1~9223372036854775807]), '.
                   'but get (2382158635a), see doc for more info.","error_code":10017,'.
                   '"request":"/2/users/show.json"}';
        $response->setContent($content);

        $oauthProvider = new Weibo(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $accessToken = '2.00TRSNbC0AvLjnd3f8afc5c4OobWRE';
        $openid = '2382158635';
        $oauthProvider->getUserProfile($accessToken, $openid);
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

        $content = '{"error":"source paramter(appkey) is missing","error_code":10006,'.
                   '"request":"/2/users/show.json"}';
        $response->setContent($content);

        $oauthProvider = new Weibo(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $accessToken = '2.00TRSNbC0AvLjnd3f8afc5c4OobWRE';
        $openid = '2382158635';
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

        $content = '{"error":"connection error","error_code":10}';
        $response->setContent($content);

        $oauthProvider = new Weibo(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );
        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $accessToken = '2.00TRSNbC0AvLjnd3f8afc5c4OobWRE';
        $openid = '2382158635';
        $oauthProvider->getUserProfile($accessToken, $openid);
    }

    /**
     * 測試用code取得使用者資料
     */
    public function testGetUserProfileByCode()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $content = ['name' => 'Steve'];
        $tokenAndId = [
            'access_token' => '2.00TRSNbC0AvLjnd3f8afc5c4OobWRE',
            'openid'       => '2382158635'
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent(json_encode($content));

        $oauthProvider = $this->getMockBuilder('BB\DurianBundle\Oauth\Weibo')
            ->disableOriginalConstructor()
            ->setMethods(['getTokenAndOpenid'])
            ->getMock();
        $oauthProvider->expects($this->any())
            ->method('getTokenAndOpenid')
            ->will($this->returnValue($tokenAndId));

        $oauthProvider->setClient($client);
        $oauthProvider->setResponse($response);

        $ret = $oauthProvider->getUserProfileByCode('40d73015575407bf5a91a3db32e51a57');

        $this->assertEquals('weibo', $ret['vendor']);
        $this->assertEquals('Steve', $ret['username']);
        $this->assertEquals('2.00TRSNbC0AvLjnd3f8afc5c4OobWRE', $ret['access_token']);
        $this->assertEquals('2382158635', $ret['openid']);
    }
}
