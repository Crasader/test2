<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\OF;
use Buzz\Message\Response;

class OFTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->will($this->returnValue(null));

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $oF = new OF();
        $oF->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '999',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->getVerifyData();
    }

    /**
     * 測試支付時沒有返回result
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"message":"OFParametersError"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $oF = new OF();
        $oF->setContainer($this->container);
        $oF->setClient($this->client);
        $oF->setResponse($response);
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'OFParametersError',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"result":"fail","message":"OFParametersError"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $oF = new OF();
        $oF->setContainer($this->container);
        $oF->setClient($this->client);
        $oF->setResponse($response);
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->getVerifyData();
    }

    /**
     * 測試支付時沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchant_id":"spade","message":"success","order_no":"201801300000008718",' .
            '"out_trade_no":"22a7fadd27a487a3a5","result":"success","total_fee":100,' .
            '"sign":"C07E5326B0647BE1BFB3A550120B135A"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $oF = new OF();
        $oF->setContainer($this->container);
        $oF->setClient($this->client);
        $oF->setResponse($response);
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->getVerifyData();
    }

    /**
     * 測試二維支付缺少form
     */
    public function testQrcodePayWithoutForm()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $url = "&lt;!DOCTYPE html PUBLIC &quot;-//W3C//DTD XHTML 1.0 Transitional//EN&quot; &quot;http://www.w3.org" .
            "/TR/xhtml1/DTD/xhtml1-transitional.dtd&quot;&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n\t&lt;meta http-eq" .
            "uiv=&quot;Content-Type&quot; content=&quot;text/html; charset=utf-8&quot;&gt;\r\n\t&lt;title&gt;支付宝&" .
            "lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;input type='hidden' name='biz_content' value='{&quot;product_cod" .
            "e&quot;:&quot;FAST_INSTANT_TRADE_PAY&quot;,&quot;body&quot;:&quot;华富天成_商品&quot;,&quot;subject&quot" .
            ";:&quot;华富天成_商品&quot;,&quot;total_amount&quot;:&quot;1&quot;,&quot;out_trade_no&quot;:&quot;10072_" .
            "1525954660494&quot;}'/&gt;&lt;input type='hidden' name='app_id' value='2018040402504232'/&gt;&lt;input" .
            " type='hidden' name='version' value='1.0'/&gt;&lt;input type='hidden' name='format' value='json'/&gt;&" .
            "lt;input type='hidden' name='sign_type' value='RSA2'/&gt;&lt;input type='hidden' name='method' value='" .
            "alipay.trade.page.pay'/&gt;&lt;input type='hidden' name='timestamp' value='2018-05-10 20:17:40'/&gt;&l" .
            "t;input type='hidden' name='alipay_sdk' value='alipay-sdk-php-20161101'/&gt;&lt;input type='hidden' na" .
            "me='notify_url' value='http://hftc8.com/payapi/ali/notify.php'/&gt;&lt;input type='hidden' name='retur" .
            "n_url' value='http://hftc8.com/payapi/ali/return.php'/&gt;&lt;input type='hidden' name='charset' value" .
            "='UTF-8'/&gt;&lt;input type='hidden' name='qr_pay_mode' value='1'/&gt;&lt;input type='hidden' name='qr" .
            "code_width' value='4'/&gt;&lt;input type='hidden' name='sign' value='OtgO GI99gpsk1Lx2rljtE8G3ikbDMJZj" .
            "3RUVEqMYgDUCmXvk19z 7FR17f98RSTLr6LzQcMqB9K/N4zE4kPOiZL4t/VhL70uPE1/kHAXfiswVzl8mJhJ4zVS4JOluxaeauKHZT" .
            "AAcbogy9tQupsxFlnhHvRcaAF5R1W4D2d4lCUblUWuryG195oKf5dC66uOfLdGE3osKiZbmID2iBtMDmVnnRUVGA0C rnprjwDb6ik" .
            "RwdyJ3sCx9Pq 7rOf mpXyAZYQbvjAFmj4x7YfLmeMTN83JEYNRXuZ14KZ3MucC N4SuK/WpmF3mpK0fftfjgdDSKtXZOhtcpJqwxR" .
            "6YQ=='/&gt;&lt;input type='submit' value='ok' style='display:none;''&gt;&lt;script&gt;document.forms['" .
            "alipaysubmit'].submit();&lt;/script&gt;&lt;/body&t;\r\n&lt;/html&gt";

        $res = [
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'result' => 'success',
            'total_fee' => 100,
            'url' => $url,
            'sign' => 'ED9217C6167ECA2C9BFB6958689AFBAC',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $oF = new OF();
        $oF->setContainer($this->container);
        $oF->setClient($this->client);
        $oF->setResponse($response);
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->getVerifyData();
    }

    /**
     * 測試二維支付缺少action
     */
    public function testQrcodePayWithoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $url = "&lt;!DOCTYPE html PUBLIC &quot;-//W3C//DTD XHTML 1.0 Transitional//EN&quot; &quot;http://www.w3.org" .
            "/TR/xhtml1/DTD/xhtml1-transitional.dtd&quot;&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n\t&lt;meta http-eq" .
            "uiv=&quot;Content-Type&quot; content=&quot;text/html; charset=utf-8&quot;&gt;\r\n\t&lt;title&gt;支付宝&" .
            "lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;form id='alipaysubmit' name='alipaysubmit' method='POST'&gt;\r\n" .
            "&lt;input type='hidden' name='biz_content' value='{&quot;product_code&quot;:&quot;FAST_INSTANT_TRADE_P" .
            "AY&quot;,&quot;body&quot;:&quot;华富天成_商品&quot;,&quot;subject&quot;:&quot;华富天成_商品&quot;,&quot;to" .
            "tal_amount&quot;:&quot;1&quot;,&quot;out_trade_no&quot;:&quot;10072_1525954660494&quot;}'/&gt;&lt;inpu" .
            "t type='hidden' name='app_id' value='2018040402504232'/&gt;&lt;input type='hidden' name='version' valu" .
            "e='1.0'/&gt;&lt;input type='hidden' name='format' value='json'/&gt;&lt;input type='hidden' name='sign_" .
            "type' value='RSA2'/&gt;&lt;input type='hidden' name='method' value='alipay.trade.page.pay'/&gt;&lt;inp" .
            "ut type='hidden' name='timestamp' value='2018-05-10 20:17:40'/&gt;&lt;input type='hidden' name='alipay" .
            "_sdk' value='alipay-sdk-php-20161101'/&gt;&lt;input type='hidden' name='notify_url' value='http://hftc" .
            "8.com/payapi/ali/notify.php'/&gt;&lt;input type='hidden' name='return_url' value='http://hftc8.com/pay" .
            "api/ali/return.php'/&gt;&lt;input type='hidden' name='charset' value='UTF-8'/&gt;&lt;input type='hidde" .
            "n' name='qr_pay_mode' value='1'/&gt;&lt;input type='hidden' name='qrcode_width' value='4'/&gt;&lt;inpu" .
            "t type='hidden' name='sign' value='OtgO GI99gpsk1Lx2rljtE8G3ikbDMJZj3RUVEqMYgDUCmXvk19z 7FR17f98RSTLr6" .
            "LzQcMqB9K/N4zE4kPOiZL4t/VhL70uPE1/kHAXfiswVzl8mJhJ4zVS4JOluxaeauKHZTAAcbogy9tQupsxFlnhHvRcaAF5R1W4D2d4" .
            "lCUblUWuryG195oKf5dC66uOfLdGE3osKiZbmID2iBtMDmVnnRUVGA0C rnprjwDb6ikRwdyJ3sCx9Pq 7rOf mpXyAZYQbvjAFmj4" .
            "x7YfLmeMTN83JEYNRXuZ14KZ3MucC N4SuK/WpmF3mpK0fftfjgdDSKtXZOhtcpJqwxR6YQ=='/&gt;&lt;input type='submit'" .
            " value='ok' style='display:none;''&gt;&lt;/form&gt;&lt;script&gt;document.forms['alipaysubmit'].submit" .
            "();&lt;/script&gt;&lt;/body&gt;\r\n&lt;/html&gt;";

        $res = [
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'result' => 'success',
            'total_fee' => 100,
            'url' => $url,
            'sign' => 'ED9217C6167ECA2C9BFB6958689AFBAC',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $oF = new OF();
        $oF->setContainer($this->container);
        $oF->setClient($this->client);
        $oF->setResponse($response);
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->getVerifyData();
    }

    /**
     * 測試二維支付action為空
     */
    public function testQrcodePayButActionIsEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $url = "&lt;!DOCTYPE html PUBLIC &quot;-//W3C//DTD XHTML 1.0 Transitional//EN&quot; &quot;http://www.w3.org" .
            "/TR/xhtml1/DTD/xhtml1-transitional.dtd&quot;&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n\t&lt;meta http-eq" .
            "uiv=&quot;Content-Type&quot; content=&quot;text/html; charset=utf-8&quot;&gt;\r\n\t&lt;title&gt;支付宝&" .
            "lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;form id='alipaysubmit' name='alipaysubmit' action='' method='POS" .
            "T'&gt;\r\n&lt;input type='hidden' name='biz_content' value='{&quot;product_code&quot;:&quot;FAST_INSTA" .
            "NT_TRADE_PAY&quot;,&quot;body&quot;:&quot;华富天成_商品&quot;,&quot;subject&quot;:&quot;华富天成_商品&quot" .
            ";,&quot;total_amount&quot;:&quot;1&quot;,&quot;out_trade_no&quot;:&quot;10072_1525954660494&quot;}'/&g" .
            "t;&lt;input type='hidden' name='app_id' value='2018040402504232'/&gt;&lt;input type='hidden' name='ver" .
            "sion' value='1.0'/&gt;&lt;input type='hidden' name='format' value='json'/&gt;&lt;input type='hidden' n" .
            "ame='sign_type' value='RSA2'/&gt;&lt;input type='hidden' name='method' value='alipay.trade.page.pay'/&" .
            "gt;&lt;input type='hidden' name='timestamp' value='2018-05-10 20:17:40'/&gt;&lt;input type='hidden' na" .
            "me='alipay_sdk' value='alipay-sdk-php-20161101'/&gt;&lt;input type='hidden' name='notify_url' value='h" .
            "ttp://hftc8.com/payapi/ali/notify.php'/&gt;&lt;input type='hidden' name='return_url' value='http://hft" .
            "c8.com/payapi/ali/return.php'/&gt;&lt;input type='hidden' name='charset' value='UTF-8'/&gt;&lt;input t" .
            "ype='hidden' name='qr_pay_mode' value='1'/&gt;&lt;input type='hidden' name='qrcode_width' value='4'/&g" .
            "t;&lt;input type='hidden' name='sign' value='OtgO GI99gpsk1Lx2rljtE8G3ikbDMJZj3RUVEqMYgDUCmXvk19z 7FR1" .
            "7f98RSTLr6LzQcMqB9K/N4zE4kPOiZL4t/VhL70uPE1/kHAXfiswVzl8mJhJ4zVS4JOluxaeauKHZTAAcbogy9tQupsxFlnhHvRcaA" .
            "F5R1W4D2d4lCUblUWuryG195oKf5dC66uOfLdGE3osKiZbmID2iBtMDmVnnRUVGA0C rnprjwDb6ikRwdyJ3sCx9Pq 7rOf mpXyAZ" .
            "YQbvjAFmj4x7YfLmeMTN83JEYNRXuZ14KZ3MucC N4SuK/WpmF3mpK0fftfjgdDSKtXZOhtcpJqwxR6YQ=='/&gt;&lt;input typ" .
            "e='submit' value='ok' style='display:none;''&gt;&lt;/form&gt;&lt;script&gt;document.forms['alipaysubmi" .
            "t'].submit();&lt;/script&gt;&lt;/body&gt;\r\n&lt;/html&gt;";

        $res = [
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'result' => 'success',
            'total_fee' => 100,
            'url' => $url,
            'sign' => 'ED9217C6167ECA2C9BFB6958689AFBAC',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $oF = new OF();
        $oF->setContainer($this->container);
        $oF->setClient($this->client);
        $oF->setResponse($response);
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $url = "&lt;!DOCTYPE html PUBLIC &quot;-//W3C//DTD XHTML 1.0 Transitional//EN&quot; &quot;http://www.w3.org" .
            "/TR/xhtml1/DTD/xhtml1-transitional.dtd&quot;&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n\t&lt;meta http-eq" .
            "uiv=&quot;Content-Type&quot; content=&quot;text/html; charset=utf-8&quot;&gt;\r\n\t&lt;title&gt;支付宝&" .
            "lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;form id='alipaysubmit' name='alipaysubmit' action='http://hftc8." .
            "com/payapi/ali/pay.php?charset=UTF-8' method='POST'&gt;\r\n&lt;input type='hidden' name='biz_content' " .
            "value='{&quot;product_code&quot;:&quot;FAST_INSTANT_TRADE_PAY&quot;,&quot;body&quot;:&quot;华富天成_商品" .
            "&quot;,&quot;subject&quot;:&quot;华富天成_商品&quot;,&quot;total_amount&quot;:&quot;1&quot;,&quot;out_tr" .
            "ade_no&quot;:&quot;10072_1525954660494&quot;}'/&gt;&lt;input type='hidden' name='app_id' value='201804" .
            "0402504232'/&gt;&lt;input type='hidden' name='version' value='1.0'/&gt;&lt;input type='hidden' name='f" .
            "ormat' value='json'/&gt;&lt;input type='hidden' name='sign_type' value='RSA2'/&gt;&lt;input type='hidd" .
            "en' name='method' value='alipay.trade.page.pay'/&gt;&lt;input type='hidden' name='timestamp' value='20" .
            "18-05-10 20:17:40'/&gt;&lt;input type='hidden' name='alipay_sdk' value='alipay-sdk-php-20161101'/&gt;&" .
            "lt;input type='hidden' name='notify_url' value='http://hftc8.com/payapi/ali/notify.php'/&gt;&lt;input " .
            "type='hidden' name='return_url' value='http://hftc8.com/payapi/ali/return.php'/&gt;&lt;input type='hid" .
            "den' name='charset' value='UTF-8'/&gt;&lt;input type='hidden' name='qr_pay_mode' value='1'/&gt;&lt;inp" .
            "ut type='hidden' name='qrcode_width' value='4'/&gt;&lt;input type='hidden' name='sign' value='OtgO GI9" .
            "9gpsk1Lx2rljtE8G3ikbDMJZj3RUVEqMYgDUCmXvk19z 7FR17f98RSTLr6LzQcMqB9K/N4zE4kPOiZL4t/VhL70uPE1/kHAXfiswV" .
            "zl8mJhJ4zVS4JOluxaeauKHZTAAcbogy9tQupsxFlnhHvRcaAF5R1W4D2d4lCUblUWuryG195oKf5dC66uOfLdGE3osKiZbmID2iBt" .
            "MDmVnnRUVGA0C rnprjwDb6ikRwdyJ3sCx9Pq 7rOf mpXyAZYQbvjAFmj4x7YfLmeMTN83JEYNRXuZ14KZ3MucC N4SuK/WpmF3mp" .
            "K0fftfjgdDSKtXZOhtcpJqwxR6YQ=='/&gt;&lt;input type='submit' value='ok' style='display:none;''&gt;&lt;/" .
            "form&gt;&lt;script&gt;document.forms['alipaysubmit'].submit();&lt;/script&gt;&lt;/body&gt;\r\n&lt;/htm" .
            "l&gt;";

        $res = [
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'result' => 'success',
            'total_fee' => 100,
            'url' => $url,
            'sign' => 'ED9217C6167ECA2C9BFB6958689AFBAC',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $oF = new OF();
        $oF->setContainer($this->container);
        $oF->setClient($this->client);
        $oF->setResponse($response);
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $data = $oF->getVerifyData();

        $bizContent = '{"product_code":"FAST_INSTANT_TRADE_PAY","body":"华富天成_商品","subject":"华富天成_商品",' .
            '"total_amount":"1","out_trade_no":"10072_1525954660494"}';
        $sign = 'OtgO GI99gpsk1Lx2rljtE8G3ikbDMJZj3RUVEqMYgDUCmXvk19z 7FR17f98RSTLr6LzQcMqB9K/N4zE4kPOiZL4t/V' .
            'hL70uPE1/kHAXfiswVzl8mJhJ4zVS4JOluxaeauKHZTAAcbogy9tQupsxFlnhHvRcaAF5R1W4D2d4lCUblUWuryG195oKf5d' .
            'C66uOfLdGE3osKiZbmID2iBtMDmVnnRUVGA0C rnprjwDb6ikRwdyJ3sCx9Pq 7rOf mpXyAZYQbvjAFmj4x7YfLmeMTN83J' .
            'EYNRXuZ14KZ3MucC N4SuK/WpmF3mpK0fftfjgdDSKtXZOhtcpJqwxR6YQ==';

        $this->assertEquals('http://hftc8.com/payapi/ali/pay.php?charset=UTF-8', $data['post_url']);
        $this->assertEquals($bizContent, $data['params']['biz_content']);
        $this->assertEquals('2018040402504232', $data['params']['app_id']);
        $this->assertEquals('1.0', $data['params']['version']);
        $this->assertEquals('json', $data['params']['format']);
        $this->assertEquals('RSA2', $data['params']['sign_type']);
        $this->assertEquals('alipay.trade.page.pay', $data['params']['method']);
        $this->assertEquals('2018-05-10 20:17:40', $data['params']['timestamp']);
        $this->assertEquals('alipay-sdk-php-20161101', $data['params']['alipay_sdk']);
        $this->assertEquals('http://hftc8.com/payapi/ali/notify.php', $data['params']['notify_url']);
        $this->assertEquals('http://hftc8.com/payapi/ali/return.php', $data['params']['return_url']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('1', $data['params']['qr_pay_mode']);
        $this->assertEquals('4', $data['params']['qrcode_width']);
        $this->assertEquals($sign, $data['params']['sign']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => 'spade33',
            'orderId' => '201805100000046231',
            'amount' => '1.00',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $url = "&lt;!DOCTYPE html PUBLIC &quot;-//W3C//DTD XHTML 1.0 Transitional//EN&quot; &quot;http://www.w3.org" .
            "/TR/xhtml1/DTD/xhtml1-transitional.dtd&quot;&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n\t&lt;meta http-eq" .
            "uiv=&quot;Content-Type&quot; content=&quot;text/html; charset=utf-8&quot;&gt;\r\n\t&lt;title&gt;一麻袋&" .
            "lt;/title&gt;\r\n\t&lt;script&gt;window.onload=function(){document.E_FORM.submit();}&lt;/script&gt;" .
            "\r\n&lt;/head&gt;\r\n&lt;html&gt;&lt;head&gt;&lt;title&gt;跳转......&lt;/title&gt;&lt;meta http-equiv=&" .
            "quot;content-Type&quot; content=&quot;text/html; charset=utf-8&quot; /&gt;&lt;/head&gt;&lt;body&gt;&lt" .
            ";form action=&quot;http://game.zq-hd.com/payapi/ymd/pay.php&quot; method=&quot;post&quot; id=&quot;frm" .
            "1&quot;&gt;&lt;input type=&quot;hidden&quot; name=&quot;MerNo&quot; value=&quot;44405&quot;&gt;&lt;inp" .
            "ut type=&quot;hidden&quot; name=&quot;BillNo&quot; value=&quot;10045_1525954930418&quot;&gt;&lt;input " .
            "type=&quot;hidden&quot; name=&quot;Amount&quot; value=&quot;1&quot;&gt;&lt;input type=&quot;hidden&quo" .
            "t; name=&quot;ReturnURL&quot; value=&quot;http://game.zq-hd.com/payapi/ymd/return.php&quot;&gt;&lt;inp" .
            "ut type=&quot;hidden&quot; name=&quot;AdviceURL&quot; value=&quot;http://game.zq-hd.com/payapi/ymd/not" .
            "ify.php&quot;&gt;&lt;input type=&quot;hidden&quot; name=&quot;OrderTime&quot; value=&quot;201805102022" .
            "10&quot;&gt;&lt;input type=&quot;hidden&quot; name=&quot;defaultBankNumber&quot; value=&quot;ICBC&quot" .
            ";&gt;&lt;input type=&quot;hidden&quot; name=&quot;payType&quot; value=&quot;B2CDebit&quot;&gt;&lt;inpu" .
            "t type=&quot;hidden&quot; name=&quot;SignInfo&quot; value=&quot;686409CB1B9F6B34568532BC4ACABF0D&quot;" .
            "&gt;&lt;input type=&quot;hidden&quot; name=&quot;Remark&quot; value=&quot;正式账号1_商品&quot;&gt;&lt;in" .
            "put type=&quot;hidden&quot; name=&quot;products&quot; value=&quot;正式账号1_商品&quot;&gt;&lt;/form&gt;&" .
            "lt;script language=&quot;javascript&quot;&gt;document.getElementById(&quot;frm1&quot;).submit();&lt;/s" .
            "cript&gt;&lt;/body&gt;&lt;/html&gt;";

        $res = [
            'merchant_id' => 'spade33',
            'message' => 'success',
            'order_no' => '201805100000046231',
            'out_trade_no' => '4329bc6108eb1613f940',
            'result' => 'success',
            'total_fee' => 100,
            'url' => $url,
            'sign' => 'CEE3CE2CD3FA750E3544718D2F85C2B6',
        ];
        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $oF = new OF();
        $oF->setContainer($this->container);
        $oF->setClient($this->client);
        $oF->setResponse($response);
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $data = $oF->getVerifyData();

        $this->assertEquals('http://game.zq-hd.com/payapi/ymd/pay.php', $data['post_url']);
        $this->assertEquals('44405', $data['params']['MerNo']);
        $this->assertEquals('10045_1525954930418', $data['params']['BillNo']);
        $this->assertEquals('1', $data['params']['Amount']);
        $this->assertEquals('http://game.zq-hd.com/payapi/ymd/return.php', $data['params']['ReturnURL']);
        $this->assertEquals('http://game.zq-hd.com/payapi/ymd/notify.php', $data['params']['AdviceURL']);
        $this->assertEquals('20180510202210', $data['params']['OrderTime']);
        $this->assertEquals('ICBC', $data['params']['defaultBankNumber']);
        $this->assertEquals('B2CDebit', $data['params']['payType']);
        $this->assertEquals('686409CB1B9F6B34568532BC4ACABF0D', $data['params']['SignInfo']);
        $this->assertEquals('正式账号1_商品', $data['params']['Remark']);
        $this->assertEquals('正式账号1_商品', $data['params']['products']);
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $oF = new OF();
        $oF->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'is_paid' => 'true',
            'merchant_id' => 'spade99',
            'nonce_str' => '31d51b8babfeff1ce01c02c44b2b4dfb',
            'notify_time' => '2018-02-12 10:05:38',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'service' => 'OF_Alipay_QR',
            'total_fee' => '100',
        ];

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'is_paid' => 'true',
            'merchant_id' => 'spade99',
            'nonce_str' => '31d51b8babfeff1ce01c02c44b2b4dfb',
            'notify_time' => '2018-02-12 10:05:38',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'service' => 'OF_Alipay_QR',
            'total_fee' => '100',
            'sign' => 'FA577C0DE8B981557C20F92BE4FADB3C',
        ];

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'is_paid' => 'false',
            'merchant_id' => 'spade99',
            'nonce_str' => '31d51b8babfeff1ce01c02c44b2b4dfb',
            'notify_time' => '2018-02-12 10:05:38',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'service' => 'OF_Alipay_QR',
            'total_fee' => '100',
            'sign' => '30D15C3C90C7C4AFE782C12825AC3986',
        ];

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'is_paid' => 'true',
            'merchant_id' => 'spade99',
            'nonce_str' => '31d51b8babfeff1ce01c02c44b2b4dfb',
            'notify_time' => '2018-02-12 10:05:38',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'service' => 'OF_Alipay_QR',
            'total_fee' => '100',
            'sign' => '5B20D8C3439F06DAA625DE394546A80C',
        ];

        $entry = ['id' => '201503220000000555'];

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'is_paid' => 'true',
            'merchant_id' => 'spade99',
            'nonce_str' => '31d51b8babfeff1ce01c02c44b2b4dfb',
            'notify_time' => '2018-02-12 10:05:38',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'service' => 'OF_Alipay_QR',
            'total_fee' => '100',
            'sign' => '5B20D8C3439F06DAA625DE394546A80C',
        ];

        $entry = [
            'id' => '201802120000009012',
            'amount' => '15.00',
        ];

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'is_paid' => 'true',
            'merchant_id' => 'spade99',
            'nonce_str' => '31d51b8babfeff1ce01c02c44b2b4dfb',
            'notify_time' => '2018-02-12 10:05:38',
            'order_no' => '201802120000009012',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'service' => 'OF_Alipay_QR',
            'total_fee' => '100',
            'sign' => '5B20D8C3439F06DAA625DE394546A80C',
        ];

        $entry = [
            'id' => '201802120000009012',
            'amount' => '1.00',
        ];

        $oF = new OF();
        $oF->setPrivateKey('test');
        $oF->setOptions($options);
        $oF->verifyOrderPayment($entry);

        $this->assertEquals('success', $oF->getMsg());
    }
}
