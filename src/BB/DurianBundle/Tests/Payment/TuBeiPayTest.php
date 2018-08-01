<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TuBeiPay;
use Buzz\Message\Response;

class TuBeiPayTest extends DurianTestCase
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

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->getVerifyData();
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

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試支付時代入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'paymentVendorId' => '9999',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
        ];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有return_code的情況
     */
    public function testPrepayReturnWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試返回預付單時return_code不為SUCCESS，有錯誤訊息
     */
    public function testPrepayReturnReturnCodeNotSuccessWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户未申请或系统不支持该支付方式',
            180130
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[FAIL]]></return_code>' .
            '<return_msg><![CDATA[商户未申请或系统不支持该支付方式]]></return_msg>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有result_code的情況
     */
    public function testPrepayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<sign><![CDATA[1DCDFA0BD11D1E4F25C1F100D2D0BC6C]]></sign>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<prepay_id><![CDATA[6aea0b382046255f04a48e8e88074430]]></prepay_id>' .
            '<code_img_url><![CDATA[https://api.tubeipay.com/v1/pay/qrcode?code=' .
            'weixin://wxpay/bizpayurl?pr=buVCruC]]></code_img_url>' .
            '<prepay_url><![CDATA[weixin://wxpay/bizpayurl?pr=buVCruC]]></prepay_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試返回預付單時result_code不為SUCCESS
     */
    public function testPrepayReturnResultCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<sign><![CDATA[1DCDFA0BD11D1E4F25C1F100D2D0BC6C]]></sign>' .
            '<result_code><![CDATA[FAIL]]></result_code>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<prepay_id><![CDATA[6aea0b382046255f04a48e8e88074430]]></prepay_id>' .
            '<code_img_url><![CDATA[https://api.tubeipay.com/v1/pay/qrcode?code=' .
            'weixin://wxpay/bizpayurl?pr=buVCruC]]></code_img_url>' .
            '<prepay_url><![CDATA[weixin://wxpay/bizpayurl?pr=buVCruC]]></prepay_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試返回預付單時取得支付參數失敗
     */
    public function testPrepayReturnWithGetPayParametersFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<sign><![CDATA[1DCDFA0BD11D1E4F25C1F100D2D0BC6C]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<prepay_id><![CDATA[6aea0b382046255f04a48e8e88074430]]></prepay_id>' .
            '<code_img_url><![CDATA[https://api.tubeipay.com/v1/pay/qrcode?code=' .
            'weixin://wxpay/bizpayurl?pr=buVCruC]]></code_img_url>' .
            '<prepay_url><![CDATA[weixin://wxpay/bizpayurl?pr=buVCruC]]></prepay_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有sign的情況
     */
    public function testPrepayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<prepay_id><![CDATA[6aea0b382046255f04a48e8e88074430]]></prepay_id>' .
            '<code_img_url><![CDATA[https://api.tubeipay.com/v1/pay/qrcode?code=' .
            'weixin://wxpay/bizpayurl?pr=buVCruC]]></code_img_url>' .
            '<prepay_url><![CDATA[weixin://wxpay/bizpayurl?pr=buVCruC]]></prepay_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<sign><![CDATA[1DCDFA0BD11D1E4F25C1F100D2D0BC6C]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<prepay_id><![CDATA[6aea0b382046255f04a48e8e88074430]]></prepay_id>' .
            '<code_img_url><![CDATA[https://api.tubeipay.com/v1/pay/qrcode?code=' .
            'weixin://wxpay/bizpayurl?pr=buVCruC]]></code_img_url>' .
            '<prepay_url><![CDATA[weixin://wxpay/bizpayurl?pr=buVCruC]]></prepay_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試手機支付返回預付單時prepay_url格式錯誤
     */
    public function testPhonePayGetEncodeReturnPrepayUrlWithError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1097',
            'number' => '10000',
            'orderId' => '201712120000003060',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<sign><![CDATA[0956AB180EFCA93FEAE8FBDF61BB8CE8]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<trade_type><![CDATA[trade.weixin.h5pay]]></trade_type>' .
            '<prepay_id><![CDATA[808ce97d68822a603e4529fd29978c6d]]></prepay_id>' .
            '<prepay_url><![CDATA[api.ulopay.com/pay/jspay]]>' .
            '</prepay_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'paymentVendorId' => '1097',
            'number' => '10000',
            'orderId' => '201712120000003060',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<sign><![CDATA[18D088BD92DBBA1585149D17A9B58158]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<trade_type><![CDATA[trade.weixin.h5pay]]></trade_type>' .
            '<prepay_id><![CDATA[808ce97d68822a603e4529fd29978c6d]]></prepay_id>' .
            '<prepay_url><![CDATA[https://api.ulopay.com/pay/jspay?ret=1&' .
            'prepay_id=f34f19fc92cacba87974cad08448ecce]]></prepay_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $encodeData = $tuBeiPay->getVerifyData();

        $this->assertEquals('https://api.ulopay.com/pay/jspay', $encodeData['post_url']);
        $this->assertEquals('1', $encodeData['params']['ret']);
        $this->assertEquals('f34f19fc92cacba87974cad08448ecce', $encodeData['params']['prepay_id']);
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'paymentVendorId' => '1090',
            'number' => '10000',
            'orderId' => '201712120000003038',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.tubeipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<sign><![CDATA[D1A9CB4EF1FFA3B4AC3221E70395D3CD]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<prepay_id><![CDATA[6aea0b382046255f04a48e8e88074430]]></prepay_id>' .
            '<code_img_url><![CDATA[https://api.tubeipay.com/v1/pay/qrcode?' .
            'code=weixin://wxpay/bizpayurl?pr=buVCruC]]></code_img_url>' .
            '<prepay_url><![CDATA[weixin://wxpay/bizpayurl?pr=buVCruC]]></prepay_url>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setContainer($this->container);
        $tuBeiPay->setClient($this->client);
        $tuBeiPay->setResponse($response);
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $encodeData = $tuBeiPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=buVCruC', $tuBeiPay->getQrcode());
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

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->verifyOrderPayment([]);
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

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->verifyOrderPayment([]);
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

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少return_code
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

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時return_code不是SUCCESS，有錯誤訊息
     */
    public function testReturnReturnCodeNotSuccessWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature error',
            180130
        );

        $xml = '<xml>' .
            '<return_code><![CDATA[FAIL]]></return_code>' .
            '<return_msg><![CDATA[Signature error]]></return_msg>' .
            '</xml>';

        $options = ['content' => $xml];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '</xml>';

        $options = ['content' => $xml];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時result_code不是SUCCESS
     */
    public function testReturnResultCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<result_code><![CDATA[FAIL]]></result_code>' .
            '</xml>';

        $options = ['content' => $xml];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment([]);
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
            '<status><![CDATA[0]]></status>' .
            '</xml>';

        $options = ['content' => $xml];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<nonce_str><![CDATA[1513049516]]></nonce_str>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<bank_no><![CDATA[35107607520171212112186419]]></bank_no>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<bank_type><![CDATA[WEIXIN_NATIVE]]></bank_type>' .
            '<total_fee><![CDATA[1]]></total_fee>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<cash_fee><![CDATA[1]]></cash_fee>' .
            '<transaction_id><![CDATA[054542017121211314297906001]]></transaction_id>' .
            '<third_trans_id><![CDATA[100217121278964694]]></third_trans_id>' .
            '<out_trade_no><![CDATA[201712120000003038]]></out_trade_no>' .
            '<time_end><![CDATA[20171212113156]]></time_end>' .
            '</xml>';

        $options = ['content' => $xml];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<nonce_str><![CDATA[1513049516]]></nonce_str>' .
            '<sign><![CDATA[A22DA9731E9EB76AF39FF36EE3B124E7]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<bank_no><![CDATA[35107607520171212112186419]]></bank_no>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<bank_type><![CDATA[WEIXIN_NATIVE]]></bank_type>' .
            '<total_fee><![CDATA[1]]></total_fee>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<cash_fee><![CDATA[1]]></cash_fee>' .
            '<transaction_id><![CDATA[054542017121211314297906001]]></transaction_id>' .
            '<third_trans_id><![CDATA[100217121278964694]]></third_trans_id>' .
            '<out_trade_no><![CDATA[201712120000003038]]></out_trade_no>' .
            '<time_end><![CDATA[20171212113156]]></time_end>' .
            '</xml>';

        $options = ['content' => $xml];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment([]);
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
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<nonce_str><![CDATA[1513049516]]></nonce_str>' .
            '<sign><![CDATA[6F47F9112922CB0FB8B2EB527D2D41B8]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<bank_no><![CDATA[35107607520171212112186419]]></bank_no>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<bank_type><![CDATA[WEIXIN_NATIVE]]></bank_type>' .
            '<total_fee><![CDATA[1]]></total_fee>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<cash_fee><![CDATA[1]]></cash_fee>' .
            '<transaction_id><![CDATA[054542017121211314297906001]]></transaction_id>' .
            '<third_trans_id><![CDATA[100217121278964694]]></third_trans_id>' .
            '<out_trade_no><![CDATA[201712120000003038]]></out_trade_no>' .
            '<time_end><![CDATA[20171212113156]]></time_end>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201612190000006951',
            'amount' => '0.01',
        ];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment($entry);
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
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<nonce_str><![CDATA[1513049516]]></nonce_str>' .
            '<sign><![CDATA[6F47F9112922CB0FB8B2EB527D2D41B8]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<bank_no><![CDATA[35107607520171212112186419]]></bank_no>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<bank_type><![CDATA[WEIXIN_NATIVE]]></bank_type>' .
            '<total_fee><![CDATA[1]]></total_fee>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<cash_fee><![CDATA[1]]></cash_fee>' .
            '<transaction_id><![CDATA[054542017121211314297906001]]></transaction_id>' .
            '<third_trans_id><![CDATA[100217121278964694]]></third_trans_id>' .
            '<out_trade_no><![CDATA[201712120000003038]]></out_trade_no>' .
            '<time_end><![CDATA[20171212113156]]></time_end>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201712120000003038',
            'amount' => '100',
        ];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $xml = '<xml>' .
            '<return_code><![CDATA[SUCCESS]]></return_code>' .
            '<mch_id><![CDATA[10000]]></mch_id>' .
            '<nonce_str><![CDATA[1513049516]]></nonce_str>' .
            '<sign><![CDATA[6F47F9112922CB0FB8B2EB527D2D41B8]]></sign>' .
            '<result_code><![CDATA[SUCCESS]]></result_code>' .
            '<bank_no><![CDATA[35107607520171212112186419]]></bank_no>' .
            '<trade_type><![CDATA[trade.weixin.native]]></trade_type>' .
            '<bank_type><![CDATA[WEIXIN_NATIVE]]></bank_type>' .
            '<total_fee><![CDATA[1]]></total_fee>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<cash_fee><![CDATA[1]]></cash_fee>' .
            '<transaction_id><![CDATA[054542017121211314297906001]]></transaction_id>' .
            '<third_trans_id><![CDATA[100217121278964694]]></third_trans_id>' .
            '<out_trade_no><![CDATA[201712120000003038]]></out_trade_no>' .
            '<time_end><![CDATA[20171212113156]]></time_end>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201712120000003038',
            'amount' => '0.01',
        ];

        $tuBeiPay = new TuBeiPay();
        $tuBeiPay->setPrivateKey('test');
        $tuBeiPay->setOptions($options);
        $tuBeiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tuBeiPay->getMsg());
    }
}
