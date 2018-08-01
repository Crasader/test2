<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JeanPay;
use Buzz\Message\Response;

class JeanPayTest extends DurianTestCase
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

        $jeanPay = new JeanPay();
        $jeanPay->getVerifyData();
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

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->getVerifyData();
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
            'number' => '80060384',
            'amount' => '100',
            'orderId' => '201703220000001397',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://pay.return/',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQRcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '80060384',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => '',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回respCode
     */
    public function testQRcodePayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '80060384',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['respMessage' => '请求失败'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '暂不支持该银行或者银行网关维护中',
            180130
        );

        $options = [
            'number' => '80060384',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => 'NOT_SUPPORT_GATEWAY',
            'respMessage' => '暂不支持该银行或者银行网关维护中',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回qrcode_url
     */
    public function testQRcodePayReturnWithoutQRCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '80060384',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'out_trade_no' => '1707121642534993670',
                'order_time' => '2017-06-14 16:47:17',
                'order_sn' => '1707121642538501154"',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->getVerifyData();
    }

    /**
     * 測試微信二維
     */
    public function testWeiXinPay()
    {
        $options = [
            'number' => '80060384',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'qrcode_url' => 'weixin://wxpay/bizpayurl?pr=G2d79xX',
                'out_trade_no' => '1707121642534993670',
                'order_time' => '2017-06-14 16:47:17',
                'order_sn' => '1707121642538501154"',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $data = $jeanPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=G2d79xX', $jeanPay->getQrcode());
    }

    /**
     * 測試支付寶二維
     */
    public function testAliPay()
    {
        $options = [
            'number' => '80060384',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'qrcode_url' => 'https://qr.alipay.com/bax02153fbefafuftg4k6084',
                'out_trade_no' => '1707121642534993670',
                'order_time' => '2017-06-14 16:47:17',
                'order_sn' => '1707121642538501154"',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $data = $jeanPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.alipay.com/bax02153fbefafuftg4k6084', $jeanPay->getQrcode());
    }

    /**
     * 測試QQ二維
     */
    public function testQQPay()
    {
        $options = [
            'number' => '80060384',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'qrcode_url' => 'https://qpay.qq.com/qr/58cd4679',
                'out_trade_no' => '1707121642534993670',
                'order_time' => '2017-06-14 16:47:17',
                'order_sn' => '1707121642538501154"',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $data = $jeanPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/58cd4679', $jeanPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'number' => '80060384',
            'amount' => '100',
            'orderId' => '201703220000001397',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1',
            'notify_url' => 'http://pay.return/',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $encodeData = $jeanPay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['partner_id']);
        $this->assertEquals('PTY_ONLINE_PAY', $encodeData['service_name']);
        $this->assertEquals('UTF-8', $encodeData['input_charset']);
        $this->assertEquals('V4.0.1', $encodeData['version']);
        $this->assertEquals('MD5', $encodeData['sign_type']);
        $this->assertEquals($options['amount'], $encodeData['order_amount']);
        $this->assertEquals($options['orderId'], $encodeData['out_trade_no']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['out_trade_time']);
        $this->assertEquals('BANK_ICBC', $encodeData['bank_code']);
        $this->assertEquals('BANK_PAY', $encodeData['pay_type']);
        $this->assertEquals($options['notify_url'], $encodeData['return_url']);
        $this->assertEquals($options['notify_url'], $encodeData['notify_url']);
        $this->assertEquals('8A3F758BCF01F4BD13AE984AF69B3C00', $encodeData['sign']);
    }

    /**
     * 測試快捷支付
     */
    public function testQuickPay()
    {
        $options = [
            'number' => '80060384',
            'amount' => '100',
            'orderId' => '201703220000001397',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'paymentVendorId' => '1088',
            'notify_url' => 'http://pay.return/',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $encodeData = $jeanPay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['partner_id']);
        $this->assertEquals('PTY_ONLINE_PAY', $encodeData['service_name']);
        $this->assertEquals('UTF-8', $encodeData['input_charset']);
        $this->assertEquals('V4.0.1', $encodeData['version']);
        $this->assertEquals('MD5', $encodeData['sign_type']);
        $this->assertEquals($options['amount'], $encodeData['order_amount']);
        $this->assertEquals($options['orderId'], $encodeData['out_trade_no']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['out_trade_time']);
        $this->assertEquals('QPAY_UNIONPAY', $encodeData['bank_code']);
        $this->assertEquals('QUICK_PAY', $encodeData['pay_type']);
        $this->assertEquals($options['notify_url'], $encodeData['return_url']);
        $this->assertEquals($options['notify_url'], $encodeData['notify_url']);
        $this->assertEquals('2C9EE8A2B1356D01311E837E94E95B57', $encodeData['sign']);
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

        $jeanPay = new JeanPay();
        $jeanPay->verifyOrderPayment([]);
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

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'trade_time' => '2017-06-13 17:21:23',
            'order_time' => '2017-06-13 17:21:19',
            'notify_type' => 'async_notify',
            'partner_id' => '80061532',
            'out_trade_no' => '201706130000002845',
            'order_amount' => '0.01',
            'order_status' => '1',
            'extend_param' => '',
            'sign_type' => 'MD5',
            'order_sn' => '1706131721231761574',
            'input_charset' => 'UTF-8',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->verifyOrderPayment([]);
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
            'trade_time' => '2017-06-13 17:21:23',
            'sign' => '93BF5E92197CBEEF1CBE591802174CB5',
            'order_time' => '2017-06-13 17:21:19',
            'notify_type' => 'async_notify',
            'partner_id' => '80061532',
            'out_trade_no' => '201706130000002845',
            'order_amount' => '0.01',
            'order_status' => '1',
            'extend_param' => '',
            'sign_type' => 'MD5',
            'order_sn' => '1706131721231761574',
            'input_charset' => 'UTF-8',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'trade_time' => '2017-06-13 17:21:23',
            'sign' => '6D1D5DB2A8E7B502ABDD141ADE1D1042',
            'order_time' => '2017-06-13 17:21:19',
            'notify_type' => 'async_notify',
            'partner_id' => '80061532',
            'out_trade_no' => '201706130000002845',
            'order_amount' => '0.01',
            'order_status' => '2',
            'extend_param' => '',
            'sign_type' => 'MD5',
            'order_sn' => '1706131721231761574',
            'input_charset' => 'UTF-8',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->verifyOrderPayment([]);
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
            'trade_time' => '2017-06-13 17:21:23',
            'sign' => '69C3CDC78A1852D7B43E28D9DBCEC9B1',
            'order_time' => '2017-06-13 17:21:19',
            'notify_type' => 'async_notify',
            'partner_id' => '80061532',
            'out_trade_no' => '201706130000002845',
            'order_amount' => '0.01',
            'order_status' => '1',
            'extend_param' => '',
            'sign_type' => 'MD5',
            'order_sn' => '1706131721231761574',
            'input_charset' => 'UTF-8',
        ];

        $entry = [
            'id' => '9453',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'trade_time' => '2017-06-13 17:21:23',
            'sign' => '69C3CDC78A1852D7B43E28D9DBCEC9B1',
            'order_time' => '2017-06-13 17:21:19',
            'notify_type' => 'async_notify',
            'partner_id' => '80061532',
            'out_trade_no' => '201706130000002845',
            'order_amount' => '0.01',
            'order_status' => '1',
            'extend_param' => '',
            'sign_type' => 'MD5',
            'order_sn' => '1706131721231761574',
            'input_charset' => 'UTF-8',
        ];

        $entry = [
            'id' => '201706130000002845',
            'amount' => '0.1',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'trade_time' => '2017-06-13 17:21:23',
            'sign' => '69C3CDC78A1852D7B43E28D9DBCEC9B1',
            'order_time' => '2017-06-13 17:21:19',
            'notify_type' => 'async_notify',
            'partner_id' => '80061532',
            'out_trade_no' => '201706130000002845',
            'order_amount' => '0.01',
            'order_status' => '1',
            'extend_param' => '',
            'sign_type' => 'MD5',
            'order_sn' => '1706131721231761574',
            'input_charset' => 'UTF-8',
        ];

        $entry = [
            'id' => '201706130000002845',
            'amount' => '0.01',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jeanPay->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jeanPay = new JeanPay();
        $jeanPay->paymentTracking();
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

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為未返回參數respCode
     */
    public function testTrackingReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '0',
                'sign' => 'SEAFOODHAPPY',
                'sign_type' => 'MD5',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為交易訂單不存在
     */
    public function testTrackingReturnOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '当前请求交易订单不存在',
            180123
        );

        $result = [
            'respCode' => 'PARTNER_ID_NOT_EXIST',
            'respMessage' => '当前请求交易订单不存在',
            'respResult' => [],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為未指定返回參數
     */
    public function testTrackingReturnNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'sign_type' => 'MD5',
                'order_status' => '2',
                'sign' => '6B3B8E9470F5CC3E8035A7E418B78703',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果未返回簽名
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '0',
                'sign_type' => 'MD5',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果簽名錯誤
     */
    public function testTrackingReturnWithSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '0',
                'sign' => 'SEAFOODHAPPY',
                'sign_type' => 'MD5',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '0',
                'sign' => '4CB5DF2B850AE819F13354B126F4663D',
                'sign_type' => 'MD5',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '3',
                'sign' => '25FAF3852EC3874ED99E0A703A9C62E6',
                'sign_type' => 'MD5',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '1',
                'sign' => '21D82A60D18A35C389423306914BFF29',
                'sign_type' => 'MD5',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002846',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '1',
                'sign' => '21D82A60D18A35C389423306914BFF29',
                'sign_type' => 'MD5',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'amount' => '10',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '1',
                'sign' => '21D82A60D18A35C389423306914BFF29',
                'sign_type' => 'MD5',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setContainer($this->container);
        $jeanPay->setClient($this->client);
        $jeanPay->setResponse($response);
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jeanPay = new JeanPay();
        $jeanPay->getPaymentTrackingData();
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

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($options);
        $jeanPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'seafood.help',
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setOptions($options);
        $jeanPay->setPrivateKey('test');
        $trackingData = $jeanPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/gateway/query', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.query.seafood.help', $trackingData['headers']['Host']);
        $this->assertEquals($options['number'], $trackingData['form']['partner_id']);
        $this->assertEquals('PTY_TRADE_QUERY', $trackingData['form']['service_name']);
        $this->assertEquals('UTF-8', $trackingData['form']['input_charset']);
        $this->assertEquals('V4.0.1', $trackingData['form']['version']);
        $this->assertEquals('MD5', $trackingData['form']['sign_type']);
        $this->assertEquals($options['orderId'], $trackingData['form']['out_trade_no']);
        $this->assertEquals('887E25D20EBF9C328B29171586A90AC4', $trackingData['form']['sign']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少私鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jeanPay = new JeanPay();
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為未返回參數respCode
     */
    public function testPaymentTrackingVerifyWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '0',
                'sign' => 'SEAFOODHAPPY',
                'sign_type' => 'MD5',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為交易訂單不存在
     */
    public function testPaymentTrackingVerifyWithOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '当前请求交易订单不存在',
            180123
        );

        $result = [
            'respCode' => 'PARTNER_ID_NOT_EXIST',
            'respMessage' => '当前请求交易订单不存在',
            'respResult' => [],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為未指定返回參數
     */
    public function testPaymentTrackingVerifyNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'sign_type' => 'MD5',
                'order_status' => '2',
                'sign' => '6B3B8E9470F5CC3E8035A7E418B78703',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為未返回簽名
     */
    public function testPaymentTrackingVerifyNoTrackingReturnSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '0',
                'sign_type' => 'MD5',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為簽名錯誤
     */
    public function testPaymentTrackingVerifyNoTrackingReturnSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '0',
                'sign' => 'SEAFOODHAPPY',
                'sign_type' => 'MD5',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單處理中
     */
    public function testPaymentTrackingVerifyOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '0',
                'sign' => '4CB5DF2B850AE819F13354B126F4663D',
                'sign_type' => 'MD5',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '3',
                'sign' => '25FAF3852EC3874ED99E0A703A9C62E6',
                'sign_type' => 'MD5',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '1',
                'sign' => '21D82A60D18A35C389423306914BFF29',
                'sign_type' => 'MD5',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002846',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單金額錯誤
     */
    public function testPaymentTrackingVerifyWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '1',
                'sign' => '21D82A60D18A35C389423306914BFF29',
                'sign_type' => 'MD5',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'amount' => '10',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $result = [
            'respCode' => 'RESPONSE_SUCCESS',
            'respMessage' => '请求响应成功',
            'respResult' => [
                'partner_id' => '80060384',
                'out_trade_no' => '201706130000002845',
                'order_sn' => '1707121642085981986',
                'order_amount' => '0.01',
                'order_time' => '2017-07-12 16:42:09',
                'order_status' => '1',
                'sign' => '21D82A60D18A35C389423306914BFF29',
                'sign_type' => 'MD5',
            ],
        ];

        $sourceData = [
            'number' => '80060384',
            'orderId' => '201706130000002845',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => json_encode($result),
        ];

        $jeanPay = new JeanPay();
        $jeanPay->setPrivateKey('test');
        $jeanPay->setOptions($sourceData);
        $jeanPay->paymentTrackingVerify();
    }
}
