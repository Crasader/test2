<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\LiFuTong;
use Buzz\Message\Response;

class LiFuTongTest extends DurianTestCase
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

        $liFuTong = new LiFuTong();
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => '',
            'paymentVendorId' => '999',
        ];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
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
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => '',
            'paymentVendorId' => '1090',
        ];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付對外返回不是xml格式
     */
    public function testPayReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有status的情況
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付對外返回時status不為0
     */
    public function testPayReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '该商户尚未开通支付宝扫码支付',
            180130
        );

        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1092',
        ];

        $result = '<xml><charset>utf-8</charset>' .
            '<message>该商户尚未开通支付宝扫码支付</message>' .
            '<service>pay.alipay.nativepay</service>' .
            '<sign>6193F12B4806B19FAD142700EB29566E</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0002</status>' .
            '<version>1.0</version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有result_code的情況
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml><charset>utf-8</charset>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>G461W91McF70YpRlYU39rf99719rzr6n</nonce_str>' .
            '<pay_info>{"codeUrl":"weixin://wxpay/bizpayurl?pr=2ERkUWg"}</pay_info>' .
            '<service>pay.weixin.nativepay</service>' .
            '<sign>59C95F049F924A1516BF86D6CDA14A2F</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<version>1.0</version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付對外返回時result_code不為0，且有錯誤訊息
     */
    public function testPayReturnResultCodeNotZeroWithErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户未开通[pay.tenpay.native]支付类型',
            180130
        );

        $options = [
            'number' => '103560018443',
            'orderId' => '201705190000006351',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1103',
        ];

        $result = '<xml><charset>utf-8</charset>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>G461W91McF70YpRlYU39rf99719rzr6n</nonce_str>' .
            '<pay_info>{"codeUrl":"weixin://wxpay/bizpayurl?pr=2ERkUWg"}</pay_info>' .
            '<result_code>1</result_code>' .
            '<err_msg>商户未开通[pay.tenpay.native]支付类型</err_msg>' .
            '<service>pay.weixin.nativepay</service>' .
            '<sign>59C95F049F924A1516BF86D6CDA14A2F</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<version>1.0</version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付對外返回時result_code不為0
     */
    public function testPayReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml><charset>utf-8</charset>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>G461W91McF70YpRlYU39rf99719rzr6n</nonce_str>' .
            '<pay_info>{"codeUrl":"weixin://wxpay/bizpayurl?pr=2ERkUWg"}</pay_info>' .
            '<result_code>1</result_code>' .
            '<service>pay.weixin.nativepay</service>' .
            '<sign>59C95F049F924A1516BF86D6CDA14A2F</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<version>1.0</version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有pay_info的情況
     */
    public function testPayReturnWithoutPayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml><charset>utf-8</charset>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>G461W91McF70YpRlYU39rf99719rzr6n</nonce_str>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.nativepay</service>' .
            '<sign>59C95F049F924A1516BF86D6CDA14A2F</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<version>1.0</version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試支付對外返回時pay_info內沒有codeUrl情況
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml><charset>utf-8</charset>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>G461W91McF70YpRlYU39rf99719rzr6n</nonce_str>' .
            '<pay_info>{}</pay_info>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.nativepay</service>' .
            '<sign>59C95F049F924A1516BF86D6CDA14A2F</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<version>1.0</version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '657508313993041972',
            'orderId' => '201711080000005428',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml><charset>utf-8</charset>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>G461W91McF70YpRlYU39rf99719rzr6n</nonce_str>' .
            '<pay_info>{"codeUrl":"weixin://wxpay/bizpayurl?pr=2ERkUWg"}</pay_info>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.nativepay</service>' .
            '<sign>59C95F049F924A1516BF86D6CDA14A2F</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<version>1.0</version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $encodeData = $liFuTong->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=2ERkUWg', $liFuTong->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => '653515740816337922',
            'orderId' => '201801170000008417',
            'username' => 'php1test',
            'amount' => '1008',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.test.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1097',
        ];

        $result = '<xml><charset>utf-8</charset>' .
            '<mch_id>653515740816337922</mch_id>' .
            '<nonce_str>7WxYv52d2ev245Q90i6s3zy4u7t0KG07</nonce_str>' .
            '<pay_info><![CDATA[{"codeUrl":' .
            '"https://zhongxin.junka.com/WxPay/H5.aspx?stid=H1801171340919AS_77' .
            '7e5c0bc09bd1ceba498f87f62e2a80&amp;metaOption=' .
            '{\'s\':\'WAP\',\'n\':\'lifutong\',\'id\':\'http://xxgcw8.com/\'}"}]]>' .
            '</pay_info>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.nativepay</service>' .
            '<sign>06EC07FB02D064748C97B0F45D84CF6E</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<version>1.0</version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $liFuTong = new LiFuTong();
        $liFuTong->setContainer($this->container);
        $liFuTong->setClient($this->client);
        $liFuTong->setResponse($response);
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $encodeData = $liFuTong->getVerifyData();

        $post_url = 'https%3A%2F%2Fzhongxin.junka.com%2FWxPay%2FH5.aspx%3Fsti' .
            'd%3DH1801171340919AS_777e5c0bc09bd1ceba498f87f62e2a80%26amp%3Bme' .
            'taOption%3D%7B%27s%27%3A%27WAP%27%2C%27n%27%3A%27lifutong%27%2C%' .
            '27id%27%3A%27http%3A%2F%2Fxxgcw8.com%2F%27%7D';

        $this->assertEquals($post_url, $encodeData['post_url']);
        $this->assertEquals([], $encodeData['params']);
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

        $liFuTong = new LiFuTong();
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少content
     */
    public function testReturnWithoutContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回不是xml格式
     */
    public function testReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $xml = '不是xml';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少status
     */
    public function testReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml></xml>';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時status不是0
     */
    public function testReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature error',
            180130
        );

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign>87C7377C567280451E92114D114257EF</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>1</status>' .
            '<message>Signature error</message>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少result_code
     */
    public function testReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign>87C7377C567280451E92114D114257EF</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時result_code不是0
     */
    public function testReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<result_code>1</result_code>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign>87C7377C567280451E92114D114257EF</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<result_code>0</result_code>' .
            '<sign>87C7377C567280451E92114D114257EF</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
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

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign>87C7377C567280451E92114D114257EF</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時pay_result不是0
     */
    public function testReturnPayResultNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>1</pay_result>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign>DB2F5EC002C6FBAAA210E028688FD982</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment([]);
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

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign>2B83C7A7F7A3C7A523F2720374F49443</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = ['id' => '201509140000002475'];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment($entry);
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

        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign>2B83C7A7F7A3C7A523F2720374F49443</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201711080000005428',
            'amount' => '15.00',
        ];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $xml = '<xml><charset>UTF-8</charset>' .
            '<fee_type>CNY</fee_type>' .
            '<is_subscribe>N</is_subscribe>' .
            '<mch_id>657508313993041972</mch_id>' .
            '<nonce_str>l88q422y4ll5h9u2y1j5ut0fMI860AyZ</nonce_str>' .
            '<out_trade_no>201711080000005428</out_trade_no>' .
            '<pay_result>0</pay_result>' .
            '<result_code>0</result_code>' .
            '<service>pay.weixin.jspay</service>' .
            '<sign>2B83C7A7F7A3C7A523F2720374F49443</sign>' .
            '<sign_type>MD5</sign_type>' .
            '<status>0</status>' .
            '<time_end>20171108094852</time_end>' .
            '<total_fee>200</total_fee>' .
            '<trade_type>pay.weixin.jspay</trade_type>' .
            '<transaction_id>653510105691594585</transaction_id>' .
            '<version>1.0</version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201711080000005428',
            'amount' => '2',
        ];

        $liFuTong = new LiFuTong();
        $liFuTong->setPrivateKey('test');
        $liFuTong->setOptions($options);
        $liFuTong->verifyOrderPayment($entry);

        $this->assertEquals('success', $liFuTong->getMsg());
    }
}
