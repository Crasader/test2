<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZTuoPay;
use Buzz\Message\Response;

class ZTuoPayTest extends DurianTestCase
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

        $zTuoPay = new ZTuoPay();
        $zTuoPay->getVerifyData();
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

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->getVerifyData();
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
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '999',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->getVerifyData();
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
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1107',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'verify_url' => '',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回status
     */
    public function testQRcodePayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1107',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'version' => '3.0',
            'message' => '',
            'ordernumber' => '201708180000003896',
            'paymoney' => '2.00',
            'qrurl' => 'https://h5pay.jd.com/code?c=5vx29zdeismh14',
            'sign' => 'efa17ed9a8ad7230305b181618aa33f0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result,  JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付金额不可低于1元',
            180130
        );

        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1107',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'version' => '3.0',
            'status' => '0',
            'message' => '支付金额不可低于1元',
            'ordernumber' => '201708180000003910',
            'paymoney' => '0.01',
            'qrurl' => '',
            'sign' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result,  JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗但沒有Message
     */
    public function testQRcodePayReturnNotSuccessWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1107',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'version' => '3.0',
            'status' => '0',
            'ordernumber' => '201708180000003910',
            'paymoney' => '0.01',
            'qrurl' => '',
            'sign' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result,  JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回sign
     */
    public function testQRcodePayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1107',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'version' => '3.0',
            'status' => '1',
            'message' => '',
            'ordernumber' => '201708180000003896',
            'paymoney' => '2.00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result,  JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回驗簽失敗
     */
    public function testQRcodePayReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1107',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'version' => '3.0',
            'status' => '1',
            'message' => '',
            'ordernumber' => '201708180000003896',
            'paymoney' => '2.00',
            'qrurl' => 'https://h5pay.jd.com/code?c=5vx29zdeismh14',
            'sign' => '1f4cc5b2bb80cfbe39688e95f3f69d2',
        ];

        $response = new Response();
        $response->setContent(json_encode($result,  JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回qrurl
     */
    public function testQRcodePayReturnWithoutQrurl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1107',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'version' => '3.0',
            'status' => '1',
            'message' => '',
            'ordernumber' => '201708180000003896',
            'paymoney' => '2.00',
            'sign' => '1f4cc5b2bb80cfbe39688e95f3f69d2e',
        ];

        $response = new Response();
        $response->setContent(json_encode($result,  JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQRcodePay()
    {
        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1107',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'version' => '3.0',
            'status' => '1',
            'message' => '',
            'ordernumber' => '201708180000003896',
            'paymoney' => '2.00',
            'qrurl' => 'https://h5pay.jd.com/code?c=5vx29zdeismh14',
            'sign' => '1f4cc5b2bb80cfbe39688e95f3f69d2e',
        ];

        $response = new Response();
        $response->setContent(json_encode($result,  JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $data = $zTuoPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://h5pay.jd.com/code?c=5vx29zdeismh14', $zTuoPay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1',
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $requestData = $zTuoPay->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('ZT.online.interface', $requestData['method']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals('ICBC', $requestData['banktype']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals($options['amount'], $requestData['paymoney']);
        $this->assertEquals('a5083d355c9945bafedeee109698e06b', $requestData['sign']);
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

        $zTuoPay = new ZTuoPay();
        $zTuoPay->verifyOrderPayment([]);
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

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->verifyOrderPayment([]);
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
            'partner' => '880800056',
            'ordernumber' => '201708180000003931',
            'orderstatus' => '1090',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->verifyOrderPayment([]);
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
            'partner' => '880800056',
            'ordernumber' => '201708180000003931',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => '123456789',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'partner' => '880800056',
            'ordernumber' => '201708180000003931',
            'orderstatus' => '2',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => '71116de4948da4c726b5f3f7d416aa0b',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->verifyOrderPayment([]);
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
            'partner' => '880800056',
            'ordernumber' => '201708180000003931',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => '25d758abd205d4402e355d83941225d5',
        ];

        $entry = ['id' => '201503220000000555'];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->verifyOrderPayment($entry);
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
            'partner' => '880800056',
            'ordernumber' => '201708180000003931',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => '25d758abd205d4402e355d83941225d5',
        ];

        $entry = [
            'id' => '201708180000003931',
            'amount' => '15.00',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '880800056',
            'ordernumber' => '201708180000003931',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => '25d758abd205d4402e355d83941225d5',
        ];

        $entry = [
            'id' => '201708180000003931',
            'amount' => '0.01',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $zTuoPay->getMsg());
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

        $zTuoPay = new ZTuoPay();
        $zTuoPay->paymentTracking();
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

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->paymentTracking();
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
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數缺少status
     */
    public function testTrackingReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201609050000004640',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705040000006242',
            'sysnumber' => 'XH170504161428815758',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗有Message
     */
    public function testTrackingReturnWithPaymentTrackingFailedHasMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单不存在',
            180123
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '',
            'sysnumber' => '',
            'status' => '0',
            'tradestate' => '',
            'paymoney' => '0.00',
            'banktype' => '',
            'paytime' => '',
            'endtime' => '',
            'message' => '订单不存在',
            'sign' => '',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnWithPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705040000006242',
            'sysnumber' => 'XH170504161428815758',
            'status' => '0',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數缺少sign
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201609050000004640',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705040000006242',
            'sysnumber' => 'XH170504161428815758',
            'status' => '1',
            'tradestate' => '0',
            'paymoney' => '0.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-04 04:14:52',
            'endtime' => '',
            'message' => '查询成功',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢驗簽錯誤
     */
    public function testTrackingReturnWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201609050000004640',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705040000006242',
            'sysnumber' => 'XH170504161428815758',
            'status' => '1',
            'tradestate' => '0',
            'paymoney' => '0.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-04 04:14:52',
            'endtime' => '',
            'message' => '查询成功',
            'sign' => '1456',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單支付中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705040000006242',
            'sysnumber' => 'XH170504161428815758',
            'status' => '1',
            'tradestate' => '0',
            'paymoney' => '0.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-04 04:14:52',
            'endtime' => '',
            'message' => '查询成功',
            'sign' => 'd7736b513d8286755a15b3ca209c4a88',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '9',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '505e2b1791f6567afaa4ca49e7a6e750',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單號錯誤
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201611110000000101',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '51dfb9821ba57012ebf72ad72ef7a867',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '100.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '51dfb9821ba57012ebf72ad72ef7a867',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '51dfb9821ba57012ebf72ad72ef7a867',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setContainer($this->container);
        $zTuoPay->setClient($this->client);
        $zTuoPay->setResponse($response);
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($options);
        $zTuoPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少私鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zTuoPay = new ZTuoPay();
        $zTuoPay->getPaymentTrackingData();
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

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->getPaymentTrackingData();
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

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $trackingData = $zTuoPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/online/gateway.html', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);

        $this->assertEquals('3.0', $trackingData['form']['version']);
        $this->assertEquals('880800056', $trackingData['form']['partner']);
        $this->assertEquals('201708180000003931', $trackingData['form']['ordernumber']);
        $this->assertEquals('', $trackingData['form']['sysnumber']);
        $this->assertEquals('910b94807e982b5f5b488807f1562a83', $trackingData['form']['sign']);
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

        $zTuoPay = new ZTuoPay();
        $zTuoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少status
     */
    public function testPaymentTrackingVerifyWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時查詢結果失敗有Message
     */
    public function testPaymentTrackingVerifyButTrackingFailedHasMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单不存在',
            180123
        );

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '',
            'sysnumber' => '',
            'status' => '0',
            'tradestate' => '',
            'paymoney' => '0.00',
            'banktype' => '',
            'paytime' => '',
            'endtime' => '',
            'message' => '订单不存在',
            'sign' => '',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時查詢結果失敗
     */
    public function testPaymentTrackingVerifyButTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '0',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'version' => '1.0',
            'partner' => '',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '123',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
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

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705040000006242',
            'sysnumber' => 'XH170504161428815758',
            'status' => '1',
            'tradestate' => '0',
            'paymoney' => '0.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-04 04:14:52',
            'endtime' => '',
            'message' => '查询成功',
            'sign' => 'd7736b513d8286755a15b3ca209c4a88',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
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

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705040000006242',
            'sysnumber' => 'XH170504161428815758',
            'status' => '1',
            'tradestate' => '9',
            'paymoney' => '0.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-04 04:14:52',
            'endtime' => '',
            'message' => '查询成功',
            'sign' => '481de37296b084671b2d0505e5609d39',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
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

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '51dfb9821ba57012ebf72ad72ef7a867',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201705040000006242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付金額錯誤
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '51dfb9821ba57012ebf72ad72ef7a867',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201708180000003931',
            'sysnumber' => 'ZT170818144153725214',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '51dfb9821ba57012ebf72ad72ef7a867',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '880800056',
            'orderId' => '201708180000003931',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $zTuoPay = new ZTuoPay();
        $zTuoPay->setPrivateKey('test');
        $zTuoPay->setOptions($sourceData);
        $zTuoPay->paymentTrackingVerify();
    }
}
