<?php

namespace BB\DurianBundle\Tests\Login;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Login\Validator;

class ValidatorTest extends WebTestCase
{
    /**
     * 測試解析客戶端資訊時作業系統為 Windows Phone
     */
    public function testParseClientInfoWhenOsIsWindowsPhone()
    {
        $parameters = [
            $clientOs = '',
            $clientBrowser = '',
            $ingress = '',
            $language = '',
            $userAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows Phone OS 7.0; Trident/3.1; IEMobile/7.0; Nokia;N70)',
        ];

        $controller = new Validator();

        $reflection = new \ReflectionClass('BB\DurianBundle\Login\Validator');
        $method = $reflection->getMethod('parseClientInfo');
        $method->setAccessible(true);

        $info = $method->invokeArgs($controller, $parameters);

        $this->assertEquals('Windows Phone', $info['os']);
        $this->assertEquals('IE', $info['browser']);
    }

    /**
     * 測試解析客戶端資訊時由 user agent 解析瀏覽器為特例瀏覽器
     */
    public function testParseClientInfoWhenCustomBrowser()
    {
        $controller = new Validator();

        $reflection = new \ReflectionClass('BB\DurianBundle\Login\Validator');
        $method = $reflection->getMethod('parseClientInfo');
        $method->setAccessible(true);

        $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/538.1 (KHTML, like Gecko) ' .
            'UB/2.0.21.1604 Safari/538.1';

        $info = $method->invokeArgs($controller, ['', '', '', '', $userAgent]);
        $this->assertEquals('寰宇瀏覽器', $info['browser']);

        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_0_2 like Mac OS X) AppleWebKit/602.1.50 ' .
            '(KHTML, like Gecko) Mobile/14A456 UBiOS/1.0.3';

        $info = $method->invokeArgs($controller, ['', '', '', '', $userAgent]);
        $this->assertEquals('寰宇瀏覽器', $info['browser']);

        $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.34 (KHTML, like Gecko) ' .
            'BBBrowser/2.6.3.1540 Safari/534.34';

        $info = $method->invokeArgs($controller, ['', '', '', '', $userAgent]);
        $this->assertEquals('BB瀏覽器', $info['browser']);

        $userAgent = 'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; .NET4.0E; .NET4.0C; .NET CLR' .
            ' 3.5.30729; .NET CLR 2.0.50727; .NET CLR 3.0.30729; Zune 4.7; QQBrowser/7.7.24562.400; rv:11.0) like Gecko';

        $info = $method->invokeArgs($controller, ['', '', '', '', $userAgent]);
        $this->assertEquals('QQ', $info['browser']);

        $userAgent = 'UC Browser 10.6 on Android (KitKat) - UCWEB/2.0 (MIDP-2.0; U; Adr 4.4.4; en-US;' .
            ' XT1022) U2/1.0.0 UCBrowser/10.6.0.706 U2/1.0.0 Mobile';

        $info = $method->invokeArgs($controller, ['', '', '', '', $userAgent]);
        $this->assertEquals('UC', $info['browser']);

        $userAgent = 'Mozilla/5.0 (Linux; U; Android 4.4.2; zh-cn; OB-OPPO R7005 Build/KVT49L)' .
            ' AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Mobile Safari/537.36 OppoBrowser/4.0.8';

        $info = $method->invokeArgs($controller, ['', '', '', '', $userAgent]);
        $this->assertEquals('Oppo', $info['browser']);
    }

    /**
     * 測試解析客戶端資訊時登入來源為BB瀏覽器(code = 6)
     */
    public function testParseClientInfoWhenIngressIs6()
    {
        $parameters = [
            $clientOs = '',
            $clientBrowser = '',
            $ingress = 6,
            $language = '',
            $userAgent = ''
        ];

        $controller = new Validator();

        $reflection = new \ReflectionClass('BB\DurianBundle\Login\Validator');
        $method = $reflection->getMethod('parseClientInfo');
        $method->setAccessible(true);

        $info = $method->invokeArgs($controller, $parameters);

        $this->assertEquals('', $info['os']);
        $this->assertEquals('BB瀏覽器', $info['browser']);
    }

    /**
     * 測試解析客戶端資訊時登入來源為寰宇瀏覽器(code = 7)
     */
    public function testParseClientInfoWhenIngressIs7()
    {
        $parameters = [
            $clientOs = '',
            $clientBrowser = '',
            $ingress = 7,
            $language = '',
            $userAgent = ''
        ];

        $controller = new Validator();

        $reflection = new \ReflectionClass('BB\DurianBundle\Login\Validator');
        $method = $reflection->getMethod('parseClientInfo');
        $method->setAccessible(true);

        $info = $method->invokeArgs($controller, $parameters);

        $this->assertEquals('', $info['os']);
        $this->assertEquals('寰宇瀏覽器', $info['browser']);
    }

    /**
     * 測試解析客戶端資訊時作業系統及瀏覽器解析結果為other
     */
    public function testParseClientInfoWithOtherResults()
    {
        $parameters = [
            $clientOs = '',
            $clientBrowser = '',
            $ingress = '',
            $language = '',
            $userAgent = 'Seamonkey-1.1.13-1(X11; U; GNU Fedora fc 10) Gecko/20081112',
        ];
        ;
        $controller = new Validator();

        $reflection = new \ReflectionClass('BB\DurianBundle\Login\Validator');
        $method = $reflection->getMethod('parseClientInfo');
        $method->setAccessible(true);

        $info = $method->invokeArgs($controller, $parameters);

        $this->assertEquals('other', $info['os']);
        $this->assertEquals('other', $info['browser']);
    }

    /**
     * 測試解析客戶端資訊時帶入非法參數
     */
    public function testParseClientInfoWithIllegalParameters()
    {
        $parameters = [
            $clientOs = 'illegal',
            $clientBrowser = 'illegal',
            $ingress = 'illegal',
            $language = 'illegal',
            $userAgent = '',
        ];

        $controller = new Validator();

        $reflection = new \ReflectionClass('BB\DurianBundle\Login\Validator');
        $method = $reflection->getMethod('parseClientInfo');
        $method->setAccessible(true);

        $info = $method->invokeArgs($controller, $parameters);

        $this->assertEquals('', $info['os']);
        $this->assertEquals('', $info['browser']);
        $this->assertEquals('', $info['language']);
        $this->assertNull($info['ingress']);
    }
}
