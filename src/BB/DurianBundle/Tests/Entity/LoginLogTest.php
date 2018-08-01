<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\LoginLog;

class LoginLogTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasicGet()
    {
        $ip = '192.168.1.1';
        $domain = 101;
        $result = LoginLog::RESULT_SUCCESS;
        $sessionId = '1mg3r1ccirkujb02pho913g2b1';
        $ipv6 = '2015:0011:1000:AC21:FE02:BEEE:DF02:123C';
        $host = 'esball.com';
        $lang = 'zh-tw';
        $role = 1;
        $username = 'hrhr';
        $clientOs = 'Windows';
        $clientBrowser = 'IE';
        $ingress = 1;
        $proxy1 = '184.146.232.251';
        $proxy2 = '172.16.168.124';
        $proxy3 = '223.104.25.167';
        $proxy4 = null;
        $country = '台灣';
        $city = '台中';
        $entrance = 2;

        $log = new LoginLog($ip, $domain, $result);
        $now = new \DateTime('now');
        $log->setAt($now);

        $this->assertNull($log->getId());
        $this->assertNull($log->getUserId());
        $this->assertNull($log->getSessionId());
        $this->assertEquals($domain, $log->getDomain());
        $this->assertEquals($ip, $log->getIP());
        $this->assertEquals($now, $log->getAt());
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $log->getResult());
        $this->assertEquals('', $log->getLanguage());
        $this->assertEquals('', $log->getClientOs());
        $this->assertEquals('', $log->getClientBrowser());
        $this->assertNull($log->getEntrance());
        $this->assertNull($log->getIngress());
        $this->assertNull($log->getProxy1());
        $this->assertNull($log->getProxy2());
        $this->assertNull($log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertNull($log->getCountry());
        $this->assertNull($log->getCity());
        $this->assertFalse($log->isSub());
        $this->assertFalse($log->isOtp());
        $this->assertFalse($log->isSlide());
        $this->assertFalse($log->isTest());

        // 設定各種屬性
        $log->setUserId(9527);
        $log->setSessionId($sessionId);
        $log->setUsername($username);
        $log->setRole($role);
        $log->setHost($host);
        $log->setIpv6($ipv6);
        $log->setLanguage($lang);
        $log->setClientOs($clientOs);
        $log->setClientBrowser($clientBrowser);
        $log->setIngress($ingress);
        $log->setProxy1($proxy1);
        $log->setProxy2($proxy2);
        $log->setProxy3($proxy3);
        $log->setProxy4($proxy4);
        $log->setCountry($country);
        $log->setCity($city);
        $log->setEntrance($entrance);
        $log->setSub(true);
        $log->setOtp(true);
        $log->setSlide(true);
        $log->setTest(true);

        $array = $log->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertEquals(9527, $array['user_id']);
        $this->assertEquals($domain, $array['domain']);
        $this->assertEquals($ip, $array['ip']);
        $this->assertEquals($now, new \DateTime($array['at']));
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $array['result']);
        $this->assertEquals($sessionId, $array['session_id']);
        $this->assertEquals($role, $array['role']);
        $this->assertTrue($array['sub']);
        $this->assertEquals($host, $array['host']);
        $this->assertEquals($ipv6, $array['ipv6']);
        $this->assertEquals($lang, $array['language']);
        $this->assertEquals($username, $array['username']);
        $this->assertEquals($clientOs, $array['client_os']);
        $this->assertEquals($clientBrowser, $array['client_browser']);
        $this->assertEquals($ingress, $array['ingress']);
        $this->assertEquals($proxy1, $array['proxy1']);
        $this->assertEquals($proxy2, $array['proxy2']);
        $this->assertEquals($proxy3, $array['proxy3']);
        $this->assertNull($array['proxy4']);
        $this->assertEquals($country, $array['country']);
        $this->assertEquals($city, $array['city']);
        $this->assertEquals($entrance, $array['entrance']);
        $this->assertTrue($array['is_otp']);
        $this->assertTrue($array['is_slide']);
        $this->assertTrue($array['test']);

        $info = $log->getInfo();

        $this->assertEquals(0, $info['id']);
        $this->assertEquals(9527, $info['user_id']);
        $this->assertEquals($domain, $info['domain']);
        $this->assertEquals(ip2long($ip), $info['ip']);
        $this->assertEquals($now, new \DateTime($info['at']));
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $info['result']);
        $this->assertEquals($sessionId, $info['session_id']);
        $this->assertEquals($role, $info['role']);
        $this->assertTrue($info['sub']);
        $this->assertEquals($host, $info['host']);
        $this->assertEquals($ipv6, $info['ipv6']);
        $this->assertEquals($lang, $info['language']);
        $this->assertEquals($username, $info['username']);
        $this->assertEquals($clientOs, $info['client_os']);
        $this->assertEquals($clientBrowser, $info['client_browser']);
        $this->assertEquals($ingress, $info['ingress']);
        $this->assertEquals(ip2long($proxy1), $info['proxy1']);
        $this->assertEquals(ip2long($proxy2), $info['proxy2']);
        $this->assertEquals(ip2long($proxy3), $info['proxy3']);
        $this->assertNull($info['proxy4']);
        $this->assertEquals($country, $info['country']);
        $this->assertEquals($city, $info['city']);
        $this->assertEquals($entrance, $info['entrance']);
        $this->assertTrue($info['is_otp']);
        $this->assertTrue($info['is_slide']);
        $this->assertTrue($info['test']);
    }
}
