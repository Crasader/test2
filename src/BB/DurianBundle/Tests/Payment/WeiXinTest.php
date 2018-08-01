<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\WeiXin;
use Buzz\Message\Response;

class WeiXinTest extends DurianTestCase
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
            ->willReturn(null);

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

        $weixin = new WeiXin();
        $weixin->getVerifyData();
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

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->getVerifyData();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => '',
        ];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試對外支付返回預付單不是xml格式
     */
    public function testPrepayReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有return_code的情況
     */
    public function testPrepayReturnWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試返回預付單時return_code不為SUCCESS
     */
    public function testPrepayReturnReturnCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户号mch_id或sub_mch_id不存在',
            180130
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[FAIL]]></return_code>' .
            '<return_msg><![CDATA[商户号mch_id或sub_mch_id不存在]]></return_msg>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試返回預付單時未指定返回參數
     */
    public function testPrepayReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試返回預付單時result_code不為SUCCESS
     */
    public function testPrepayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get prepay_id failure',
            180135
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>wx2421b1c4370ec43b</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<result_code>FAIL</result_code>' .
            '<trade_type>APP</trade_type>' .
            '<prepay_id>wx201411101639507cbf6ffd8b0779950874</prepay_id>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有sign的情況
     */
    public function testPrepayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>wx2421b1c4370ec43b</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<result_code>SUCCESS</result_code>' .
            '<trade_type>APP</trade_type>' .
            '<prepay_id>wx201411101639507cbf6ffd8b0779950874</prepay_id>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試返回預付單時解密驗證錯誤
     */
    public function testPayRuturnDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>wx2421b1c4370ec43b</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<result_code>SUCCESS</result_code>' .
            '<trade_type>APP</trade_type>' .
            '<prepay_id>wx201411101639507cbf6ffd8b0779950874</prepay_id>' .
            '<sign>40644F0F8DB6F879D1483E8152AE9DE7</sign>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100.855',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<return_msg>OK</return_msg>' .
            '<appid>wx2421b1c4370ec43b</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<sign>40644F0F8DB6F879D1483E8152AE9DE7</sign>' .
            '<result_code>SUCCESS</result_code>' .
            '<prepay_id>wx201411101639507cbf6ffd8b0779950874</prepay_id>' .
            '<trade_type>APP</trade_type>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $package = $weixin->getVerifyData();

        $this->assertEquals('wx2421b1c4370ec43b', $package['appid']);
        $this->assertEquals('de8c5f787fd340f4be06d9abc651a4f4', $package['noncestr']);
        $this->assertEquals('Sign=WXPay', $package['package']);
        $this->assertEquals('20130809', $package['partnerid']);
        $this->assertEquals('wx201411101639507cbf6ffd8b0779950874', $package['prepayid']);
        $this->assertNotNull($package['timestamp']);
        $this->assertNotNull($package['sign']);
    }

    /**
     * 測試二維加密時沒有code_url的情況
     */
    public function testQRCodePayWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100.855',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<return_msg>OK</return_msg>' .
            '<appid>wx2421b1c4370ec43b</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<sign>6BFECA69FC7E5836B0C8902003B66DF9</sign>' .
            '<result_code>SUCCESS</result_code>' .
            '<prepay_id>wx201411101639507cbf6ffd8b0779950874</prepay_id>' .
            '<trade_type>NATIVE</trade_type>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getVerifyData();
    }

    /**
     * 測試二維加密
     */
    public function testQRCodePay()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100.855',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => '商品'],
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<return_msg>OK</return_msg>' .
            '<appid>wx2421b1c4370ec43b</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<sign>2E30DA486B15A06526EE4EBEB33DA4E2</sign>' .
            '<result_code>SUCCESS</result_code>' .
            '<prepay_id>wx201411101639507cbf6ffd8b0779950874</prepay_id>' .
            '<trade_type>NATIVE</trade_type>' .
            '<code_url>weixin://wxpay/s/An4baqw</code_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $package = $weixin->getVerifyData();

        $this->assertEquals([], $package);
        $this->assertEquals('weixin://wxpay/s/An4baqw', $weixin->getQrcode());
    }

    /**
     * 測試remark為空仍可以正常加密(過渡期，待都有資料後拔除)
     */
    public function testPayWithRemarkEmpty()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100.855',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2015-03-16 04:07:10',
            'notify_url' => 'http://www.weixin.cn/return.php',
            'verify_url' => 'api.mch.weixin.qq.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['appid' => '123456', 'remark' => ''],
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<return_msg>OK</return_msg>' .
            '<appid>wx2421b1c4370ec43b</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<sign>40644F0F8DB6F879D1483E8152AE9DE7</sign>' .
            '<result_code>SUCCESS</result_code>' .
            '<prepay_id>wx201411101639507cbf6ffd8b0779950874</prepay_id>' .
            '<trade_type>APP</trade_type>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $package = $weixin->getVerifyData();

        $this->assertEquals('wx2421b1c4370ec43b', $package['appid']);
        $this->assertEquals('de8c5f787fd340f4be06d9abc651a4f4', $package['noncestr']);
        $this->assertEquals('Sign=WXPay', $package['package']);
        $this->assertEquals('20130809', $package['partnerid']);
        $this->assertEquals('wx201411101639507cbf6ffd8b0779950874', $package['prepayid']);
        $this->assertNotNull($package['timestamp']);
        $this->assertNotNull($package['sign']);
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

        $weixin = new WeiXin();
        $weixin->verifyOrderPayment([]);
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

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->verifyOrderPayment([]);
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

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少<return_code>
     */
    public function testReturnWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml></xml>';

        $options = ['content' => $xml];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時return_code不是SUCCESS
     */
    public function testReturnReturnCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名错误',
            180130
        );

        $xml = '<xml>' .
            '<return_code><![CDATA[FAIL]]></return_code>' .
            '<return_msg><![CDATA[签名错误]]></return_msg>' .
            '</xml>';

        $options = ['content' => $xml];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '</xml>';

        $options = ['content' => $xml];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少<sign>
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<openid>oUpF8uMEb4qRXf22hE3X68TekukE</openid>' .
            '<result_code>SUCCESS</result_code>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CFT</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1004400740201409030005092168</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20140903131540</time_end>' .
            '</xml>';

        $options = ['content' => $xml];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時result_code不為SUCCESS
     */
    public function testReturnResultCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<openid>oUpF8uMEb4qRXf22hE3X68TekukE</openid>' .
            '<result_code>FAIL</result_code>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CFT</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1004400740201409030005092168</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20140903131540</time_end>' .
            '<sign>20140903131540</sign>' .
            '</xml>';

        $options = ['content' => $xml];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時解密驗證錯誤
     */
    public function testReturnDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $xml = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<openid>oUpF8uMEb4qRXf22hE3X68TekukE</openid>' .
            '<result_code>SUCCESS</result_code>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CFT</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1004400740201409030005092168</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20140903131540</time_end>' .
            '<sign>20140903131540</sign>' .
            '</xml>';

        $options = ['content' => $xml];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<openid>oUpF8uMEb4qRXf22hE3X68TekukE</openid>' .
            '<result_code>SUCCESS</result_code>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CFT</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1004400740201409030005092168</transaction_id>' .
            '<out_trade_no>201503220000000321</out_trade_no>' .
            '<time_end>20140903131540</time_end>' .
            '<sign>0374950F77BF95A582CBFAF374D3ECC5</sign>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = ['id' => '201503220000000123'];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment($entry);
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

        $xml = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<openid>oUpF8uMEb4qRXf22hE3X68TekukE</openid>' .
            '<result_code>SUCCESS</result_code>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CFT</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1004400740201409030005092168</transaction_id>' .
            '<out_trade_no>201503220000000321</out_trade_no>' .
            '<time_end>20140903131540</time_end>' .
            '<sign>0374950F77BF95A582CBFAF374D3ECC5</sign>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201503220000000321',
            'amount' => '100',
        ];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $xml = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<openid>oUpF8uMEb4qRXf22hE3X68TekukE</openid>' .
            '<result_code>SUCCESS</result_code>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CFT</bank_type>' .
            '<total_fee>100</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1004400740201409030005092168</transaction_id>' .
            '<out_trade_no>201503220000000321</out_trade_no>' .
            '<time_end>20140903131540</time_end>' .
            '<coupon_count>1</coupon_count>' .
            '<sign>FE4872C8B46B289BAA38827EFC0B205C</sign>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201503220000000321',
            'amount' => '1',
        ];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->verifyOrderPayment($entry);

        $this->assertEquals('<xml><return_code>SUCCESS</return_code></xml>', $weixin->getMsg());
    }

    /**
     * 測試訂單查詢加密缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $weixin = new WeiXin();
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢加密缺少商家額外的參數設定appid
     */
    public function testTrackingWithoutMerchantExtraAppid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => [],
        ];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_url' => '',
        ];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢對外連線發生例外
     */
    public function testTrackingConnectException()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付沒有回傳
     */
    public function testTrackingWithoutResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付返回不是xml格式
     */
    public function testTrackingReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回return_code
     */
    public function testTrackingReturnWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果return_code不為Success
     */
    public function testTrackingReturnCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名错误',
            180123
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[FAIL]]></return_code>' .
            '<return_msg><![CDATA[签名错误]]></return_msg>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單不存在
     */
    public function testTrackingReturnOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>FAIL</result_code>' .
            '<err_code>ORDERNOTEXIST</err_code>' .
            '<err_code_des>order not exist</err_code_des>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢系統異常
     */
    public function testTrackingReturnSystemError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'System error, please try again later or contact customer service',
            180076
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>FAIL</result_code>' .
            '<err_code>SYSTEMERROR</err_code>' .
            '<err_code_des>系统错误</err_code_des>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付返回未指定返回參數
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回result_code
     */
    public function testTrackingReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果異常
     */
    public function testTrackingReturnOrderFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>FAIL</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>SUCCESS</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付返回沒有sign的情況
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>SUCCESS</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢解密驗證錯誤
     */
    public function testTrackingReturnDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>SUCCESS</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '<sign>BDF0099C15FF7BC6B1585FBB110AB635</sign>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢交易狀態為訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>NOTPAY</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '<sign>9F279D325D2CB314A9288C7C03DB042C</sign>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢交易狀態不為SUCCESS則代表支付失敗
     */
    public function testTrackingReturnOrderPaymentfailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>PAYERROR</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '<sign>47CADBE0C322EF98C81178DBBE0BBC98</sign>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testExamineSuccess()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'api.mch.weixin.qq.com',
        ];

        $result = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>SUCCESS</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>請付款</trade_state_desc>' .
            '<coupon_count>1</coupon_count>' .
            '<sign>6F116C5694D41B6D51DEE443340E6652</sign>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $weixin = new WeiXin();
        $weixin->setContainer($this->container);
        $weixin->setClient($this->client);
        $weixin->setResponse($response);
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $weixin = new WeiXin();
        $weixin->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數缺少商家額外的參數設定appid
     */
    public function testGetPaymentTrackingDataWithoutMerchantExtraAppid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => [],
            'reopUrl' => ''
        ];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $weixin->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'merchant_extra' => ['appid' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.mch.weixin.qq.com',
        ];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($options);
        $trackingData = $weixin->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/pay/orderquery', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.api.mch.weixin.qq.com', $trackingData['headers']['Host']);

        $xml = '<?xml version="1.0"?><xml><appid>123456</appid><mch_id>20130809</mch_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>';
        $this->assertContains($xml, $trackingData['form']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $weixin = new WeiXin();
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢返回不是xml格式
     */
    public function testPaymentTrackingVerifyButNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $content = '不是XML';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢返回沒有return_code
     */
    public function testPaymentTrackingVerifyWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<xml></xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢return_code不為Success
     */
    public function testPaymentTrackingVerifyReturnCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名错误',
            180123
        );

        $content = '<xml>' .
            '<return_code><![CDATA[FAIL]]></return_code>' .
            '<return_msg><![CDATA[签名错误]]></return_msg>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單不存在
     */
    public function testPaymentTrackingVerifyOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>FAIL</result_code>' .
            '<err_code>ORDERNOTEXIST</err_code>' .
            '<err_code_des>order not exist</err_code_des>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢系統異常
     */
    public function testPaymentTrackingVerifySystemError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'System error, please try again later or contact customer service',
            180076
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>FAIL</result_code>' .
            '<err_code>SYSTEMERROR</err_code>' .
            '<err_code_des>系统错误</err_code_des>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果沒有返回result_code
     */
    public function testPaymentTrackingVerifyWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果異常
     */
    public function testPaymentTrackingVerifyOrderFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>FAIL</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>SUCCESS</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付返回沒有sign的情況
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>SUCCESS</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢解密驗證錯誤
     */
    public function testPaymentTrackingVerifyDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>SUCCESS</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '<sign>BDF0099C15FF7BC6B1585FBB110AB635</sign>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢交易狀態為訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>NOTPAY</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '<sign>9F279D325D2CB314A9288C7C03DB042C</sign>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢交易狀態不為SUCCESS則代表支付失敗
     */
    public function testPaymentTrackingVerifyOrderPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>PAYERROR</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>支付失敗</trade_state_desc>' .
            '<sign>47CADBE0C322EF98C81178DBBE0BBC98</sign>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<xml>' .
            '<return_code>SUCCESS</return_code>' .
            '<result_code>SUCCESS</result_code>' .
            '<appid>123456</appid>' .
            '<mch_id>20130809</mch_id>' .
            '<nonce_str>de8c5f787fd340f4be06d9abc651a4f4</nonce_str>' .
            '<trade_state>SUCCESS</trade_state>' .
            '<openid>oUpF8uN95-Ptaags6E_roPHg7AG0</openid>' .
            '<is_subscribe>Y</is_subscribe>' .
            '<trade_type>APP</trade_type>' .
            '<bank_type>CCB</bank_type>' .
            '<total_fee>1</total_fee>' .
            '<cash_fee>1</cash_fee>' .
            '<transaction_id>1008450740201411110005820873</transaction_id>' .
            '<out_trade_no>201503160000002219</out_trade_no>' .
            '<time_end>20150316170043</time_end>' .
            '<trade_state_desc>請付款</trade_state_desc>' .
            '<coupon_count>1</coupon_count>' .
            '<sign>6F116C5694D41B6D51DEE443340E6652</sign>' .
            '</xml>';
        $sourceData = ['content' => $content];

        $weixin = new WeiXin();
        $weixin->setPrivateKey('test');
        $weixin->setOptions($sourceData);
        $weixin->paymentTrackingVerify();
    }
}
