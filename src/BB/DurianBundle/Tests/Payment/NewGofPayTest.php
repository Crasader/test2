<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewGofPay;
use Buzz\Message\Response;

class NewGofPayTest extends DurianTestCase
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

        $newGofPay = new NewGofPay();
        $newGofPay->getVerifyData();
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

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verifyUrl的情況
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
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'username' => 'test',
            'paymentVendorId' => '1',
            'verify_url' => '',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->getVerifyData();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'username' => 'test',
            'paymentVendorId' => '99',
            'verify_url' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線失敗
     */
    public function testPayPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'username' => 'test',
            'paymentVendorId' => '1',
            'verify_url' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->getVerifyData();
    }

    /**
     * 測試加密時支付平台回傳結果為空
     */
    public function testPayEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'username' => 'test',
            'paymentVendorId' => '1',
            'verify_url' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'username' => 'test',
            'paymentVendorId' => '1',
            'verify_url' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['virCardNoIn' => '0000000001000000584'],
            'merchantId' => '12345',
            'domain' => '6',
            'postUrl' => 'https://gateway.gopay.com.cn',
        ];

        $result = '20150408141953';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $requestData = $newGofPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $this->assertEquals('https://gateway.gopay.com.cn/Trans/WebClientAction.do', $requestData['post_url']);
        $this->assertEquals('2.1', $requestData['params']['version']);
        $this->assertEquals($options['number'], $requestData['params']['merchantID']);
        $this->assertEquals($options['orderId'], $requestData['params']['merOrderNum']);
        $this->assertEquals('100.00', $requestData['params']['tranAmt']);
        $this->assertEquals($notifyUrl, $requestData['params']['backgroundMerUrl']);
        $this->assertEquals('test', $requestData['params']['buyerName']);
        $this->assertEquals('20150408141953', $requestData['params']['gopayServerTime']);
        $this->assertEquals('ICBC', $requestData['params']['bankCode']);
    }

    /**
     * 測試手機支付加密
     */
    public function testWapPay()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'username' => 'test',
            'paymentVendorId' => '1003',
            'verify_url' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['virCardNoIn' => '0000000001000000584'],
            'merchantId' => '12345',
            'domain' => '6',
            'postUrl' => 'https://gateway.gopay.com.cn',
        ];

        $result = '20150408141953';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $requestData = $newGofPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $this->assertEquals('https://gateway.gopay.com.cn/Trans/MobileClientAction.do', $requestData['post_url']);
        $this->assertEquals('2.2', $requestData['params']['version']);
        $this->assertEquals($options['number'], $requestData['params']['merchantID']);
        $this->assertEquals($options['orderId'], $requestData['params']['merOrderNum']);
        $this->assertEquals('100.00', $requestData['params']['tranAmt']);
        $this->assertEquals($notifyUrl, $requestData['params']['backgroundMerUrl']);
        $this->assertEquals('MWEB', $requestData['params']['buyerName']);
        $this->assertEquals('20150408141953', $requestData['params']['gopayServerTime']);
        $this->assertEquals('ICBC', $requestData['params']['bankCode']);
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

        $newGofPay = new NewGofPay();
        $newGofPay->verifyOrderPayment([]);
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

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'version' => '2.1',
            'tranCode' => '8888',
            'merchantID' => '0000001304',
            'merOrderNum' => '201503220000000321',
            'tranAmt' => '10.00',
            'feeAmt' => '0',
            'tranDateTime' => '20151107094626',
            'backgroundMerUrl' => 'http://154.58.78.54/test?pay_system=14903',
            'orderId' => '2011092200001',
            'gopayOutOrderId' => '2011092300000001',
            'tranIP' => '127.0.0.1',
            'respCode' => '0000',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->verifyOrderPayment([]);
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
            'version' => '2.1',
            'tranCode' => '8888',
            'merchantID' => '0000001304',
            'merOrderNum' => '201503220000000321',
            'tranAmt' => '10.00',
            'feeAmt' => '0',
            'tranDateTime' => '20151107094626',
            'hallid' => '6',
            'backgroundMerUrl' => 'http://154.58.78.54/test?pay_system=14903',
            'orderId' => '2011092200001',
            'gopayOutOrderId' => '2011092300000001',
            'tranIP' => '127.0.0.1',
            'respCode' => '0000',
            'signValue' => '123456789',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->verifyOrderPayment([]);
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
            'version' => '2.1',
            'tranCode' => '8888',
            'merchantID' => '0000001304',
            'merOrderNum' => '201503220000000321',
            'tranAmt' => '10.00',
            'feeAmt' => '0',
            'tranDateTime' => '20151107094626',
            'frontMerUrl' => 'http://154.58.78.54/',
            'backgroundMerUrl' => 'http://154.58.78.54/test?pay_system=14903',
            'orderId' => '2011092200001',
            'gopayOutOrderId' => '2011092300000001',
            'tranIP' => '127.0.0.1',
            'respCode' => '9999',
            'gopayServerTime' => '20151107094626',
            'signValue' => 'ac0c56e0175d118f6607ed5a2574ba8f',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->verifyOrderPayment([]);
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
            'version' => '2.1',
            'tranCode' => '8888',
            'merchantID' => '0000001304',
            'merOrderNum' => '201503220000000321',
            'tranAmt' => '10.00',
            'feeAmt' => '0',
            'tranDateTime' => '20151107094626',
            'frontMerUrl' => 'http://154.58.78.54/',
            'backgroundMerUrl' => 'http://154.58.78.54/test?pay_system=14903',
            'orderId' => '2011092200001',
            'gopayOutOrderId' => '2011092300000001',
            'tranIP' => '127.0.0.1',
            'respCode' => '0000',
            'gopayServerTime' => '20151107094626',
            'signValue' => 'e58aee7ceeb3830b6ac8eed6450994a8',
        ];

        $entry = ['id' => '201503220000000555'];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->verifyOrderPayment($entry);
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
            'version' => '2.1',
            'tranCode' => '8888',
            'merchantID' => '0000001304',
            'merOrderNum' => '201503220000000321',
            'tranAmt' => '10.00',
            'feeAmt' => '0',
            'tranDateTime' => '20151107094626',
            'frontMerUrl' => 'http://154.58.78.54/',
            'backgroundMerUrl' => 'http://154.58.78.54/test?pay_system=14903',
            'orderId' => '2011092200001',
            'gopayOutOrderId' => '2011092300000001',
            'tranIP' => '127.0.0.1',
            'respCode' => '0000',
            'gopayServerTime' => '20151107094626',
            'signValue' => 'e58aee7ceeb3830b6ac8eed6450994a8',
        ];

        $entry = [
            'id' => '201503220000000321',
            'amount' => '15.00',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'version' => '2.1',
            'tranCode' => '8888',
            'merchantID' => '0000001304',
            'merOrderNum' => '201503220000000321',
            'tranAmt' => '10.00',
            'feeAmt' => '0',
            'tranDateTime' => '20151107094626',
            'frontMerUrl' => 'http://154.58.78.54/',
            'backgroundMerUrl' => 'http://154.58.78.54/test?pay_system=14903',
            'orderId' => '2011092200001',
            'gopayOutOrderId' => '2011092300000001',
            'tranIP' => '127.0.0.1',
            'respCode' => '0000',
            'gopayServerTime' => '20151107094626',
            'signValue' => 'e58aee7ceeb3830b6ac8eed6450994a8',
        ];

        $entry = [
            'id' => '201503220000000321',
            'amount' => '10.00',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->verifyOrderPayment($entry);

        $this->assertEquals('RespCode=0000|JumpURL=', $newGofPay->getMsg());
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

        $newGofPay = new NewGofPay();
        $newGofPay->paymentTracking();
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

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->paymentTracking();
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
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果訂單不存在
     */
    public function testTrackingReturnWithOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '<errMessage>订单不存在</errMessage>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有respCode的情況
     */
    public function testTrackingReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '<respCode>1234</respCode>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
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
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '<respCode>0000</respCode>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有signValue的情況
     */
    public function testTrackingReturnWithoutSignValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '<respCode>0000</respCode>'.
            '<tranCode>4020</tranCode>'.
            '<merchantID>20130809</merchantID>'.
            '<merOrderNum>201503160000002219</merOrderNum>'.
            '<tranAmt></tranAmt>'.
            '<feeAmt></feeAmt>'.
            '<currencyType></currencyType>'.
            '<merURL></merURL>'.
            '<customerEMail></customerEMail>'.
            '<tranDateTime>20150316094511</tranDateTime>'.
            '<virCardNo></virCardNo>'.
            '<virCardNoIn></virCardNoIn>'.
            '<tranIP>3</tranIP>'.
            '<msgExt>success</msgExt>'.
            '<orgtranDateTime>20150316094511</orgtranDateTime>'.
            '<orgTxnStat>20000</orgTxnStat>'.
            '<orgOrderNum>201503160000002219</orgOrderNum>'.
            '<orgTxnType>8888</orgTxnType>'.
            '<orgtranAmt>10.00</orgtranAmt>'.
            '<authID></authID>'.
            '<isLocked></isLocked>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '<respCode>0000</respCode>'.
            '<tranCode>4020</tranCode>'.
            '<merchantID>20130809</merchantID>'.
            '<merOrderNum>201503160000002219</merOrderNum>'.
            '<tranAmt></tranAmt>'.
            '<feeAmt></feeAmt>'.
            '<currencyType></currencyType>'.
            '<merURL></merURL>'.
            '<customerEMail></customerEMail>'.
            '<tranDateTime>20150316094511</tranDateTime>'.
            '<virCardNo></virCardNo>'.
            '<virCardNoIn></virCardNoIn>'.
            '<tranIP>3</tranIP>'.
            '<msgExt>success</msgExt>'.
            '<orgtranDateTime>20150316094511</orgtranDateTime>'.
            '<orgTxnStat>20000</orgTxnStat>'.
            '<orgOrderNum>201503160000002219</orgOrderNum>'.
            '<orgTxnType>8888</orgTxnType>'.
            '<orgtranAmt>10.00</orgtranAmt>'.
            '<authID></authID>'.
            '<isLocked></isLocked>'.
            '<signValue>123456</signValue>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '<respCode>0000</respCode>'.
            '<tranCode>4020</tranCode>'.
            '<merchantID>20130809</merchantID>'.
            '<merOrderNum>201503160000002219</merOrderNum>'.
            '<tranAmt></tranAmt>'.
            '<feeAmt></feeAmt>'.
            '<currencyType></currencyType>'.
            '<merURL></merURL>'.
            '<customerEMail></customerEMail>'.
            '<tranDateTime>20150316094511</tranDateTime>'.
            '<virCardNo></virCardNo>'.
            '<virCardNoIn></virCardNoIn>'.
            '<tranIP>3</tranIP>'.
            '<msgExt>success</msgExt>'.
            '<orgtranDateTime>20150316094511</orgtranDateTime>'.
            '<orgTxnStat>100</orgTxnStat>'.
            '<orgOrderNum>201503160000002219</orgOrderNum>'.
            '<orgTxnType>8888</orgTxnType>'.
            '<orgtranAmt>10.00</orgtranAmt>'.
            '<authID></authID>'.
            '<isLocked></isLocked>'.
            '<signValue>b8182800634b48e7eeb3129dce3607da</signValue>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '100.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '<respCode>0000</respCode>'.
            '<tranCode>4020</tranCode>'.
            '<merchantID>20130809</merchantID>'.
            '<merOrderNum>201503160000002219</merOrderNum>'.
            '<tranAmt></tranAmt>'.
            '<feeAmt></feeAmt>'.
            '<currencyType></currencyType>'.
            '<merURL></merURL>'.
            '<customerEMail></customerEMail>'.
            '<tranDateTime>20150316094511</tranDateTime>'.
            '<virCardNo></virCardNo>'.
            '<virCardNoIn></virCardNoIn>'.
            '<tranIP>3</tranIP>'.
            '<msgExt>success</msgExt>'.
            '<orgtranDateTime>20150316094511</orgtranDateTime>'.
            '<orgTxnStat>20000</orgTxnStat>'.
            '<orgOrderNum>201503160000002219</orgOrderNum>'.
            '<orgTxnType>8888</orgTxnType>'.
            '<orgtranAmt>10.00</orgtranAmt>'.
            '<authID></authID>'.
            '<isLocked></isLocked>'.
            '<signValue>94634b223cb1b4ea203287270fbfa9a1</signValue>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<GopayTranRes>'.
            '<respCode>0000</respCode>'.
            '<tranCode>4020</tranCode>'.
            '<merchantID>20130809</merchantID>'.
            '<merOrderNum>201503160000002219</merOrderNum>'.
            '<tranAmt></tranAmt>'.
            '<feeAmt></feeAmt>'.
            '<currencyType></currencyType>'.
            '<merURL></merURL>'.
            '<customerEMail></customerEMail>'.
            '<tranDateTime>20150316094511</tranDateTime>'.
            '<virCardNo></virCardNo>'.
            '<virCardNoIn></virCardNoIn>'.
            '<tranIP>3</tranIP>'.
            '<msgExt>success</msgExt>'.
            '<orgtranDateTime>20150316094511</orgtranDateTime>'.
            '<orgTxnStat>20000</orgTxnStat>'.
            '<orgOrderNum>201503160000002219</orgOrderNum>'.
            '<orgTxnType>8888</orgTxnType>'.
            '<orgtranAmt>10.00</orgtranAmt>'.
            '<authID></authID>'.
            '<isLocked></isLocked>'.
            '<signValue>94634b223cb1b4ea203287270fbfa9a1</signValue>'.
            '</GopayTranRes>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newGofPay = new NewGofPay();
        $newGofPay->setContainer($this->container);
        $newGofPay->setClient($this->client);
        $newGofPay->setResponse($response);
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->paymentTracking();
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

        $newGofPay = new NewGofPay();
        $newGofPay->getPaymentTrackingData();
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

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->getPaymentTrackingData();
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
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $newGofPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.gopay.com.cn',
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($options);
        $trackingData = $newGofPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/Trans/WebClientAction.do', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.gateway.gopay.com.cn', $trackingData['headers']['Host']);

        $this->assertEquals('4020', $trackingData['form']['tranCode']);
        $this->assertEquals('20150316094511', $trackingData['form']['tranDateTime']);
        $this->assertEquals('201503160000002219', $trackingData['form']['merOrderNum']);
        $this->assertEquals('20130809', $trackingData['form']['merchantID']);
        $this->assertEquals('201503160000002219', $trackingData['form']['orgOrderNum']);
        $this->assertEquals('10.00', $trackingData['form']['orgtranAmt']);
        $this->assertEquals('32e449bea476e45c019d075a38cf945d', $trackingData['form']['signValue']);
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

        $newGofPay = new NewGofPay();
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單不存在
     */
    public function testPaymentTrackingVerifyWithOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '<errMessage>订单不存在</errMessage>' .
            '</GopayTranRes>';
        $sourceData = ['content' => $content];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢沒有respCode的情況
     */
    public function testPaymentTrackingVerifyWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '</GopayTranRes>';
        $sourceData = ['content' => $content];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢失敗
     */
    public function testPaymentTrackingVerifyPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '<respCode>1234</respCode>' .
            '</GopayTranRes>';
        $sourceData = ['content' => $content];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '<respCode>0000</respCode>' .
            '</GopayTranRes>';
        $sourceData = ['content' => $content];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果驗證沒有signValue的情況
     */
    public function testPaymentTrackingVerifyWithoutSignValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '<respCode>0000</respCode>' .
            '<tranCode>4020</tranCode>' .
            '<merchantID>20130809</merchantID>' .
            '<merOrderNum>201503160000002219</merOrderNum>' .
            '<tranAmt></tranAmt>' .
            '<feeAmt></feeAmt>' .
            '<currencyType></currencyType>' .
            '<merURL></merURL>' .
            '<customerEMail></customerEMail>' .
            '<tranDateTime>20150316094511</tranDateTime>' .
            '<virCardNo></virCardNo>' .
            '<virCardNoIn></virCardNoIn>' .
            '<tranIP>3</tranIP>' .
            '<msgExt>success</msgExt>' .
            '<orgtranDateTime>20150316094511</orgtranDateTime>' .
            '<orgTxnStat>20000</orgTxnStat>' .
            '<orgOrderNum>201503160000002219</orgOrderNum>' .
            '<orgTxnType>8888</orgTxnType>' .
            '<orgtranAmt>10.00</orgtranAmt>' .
            '<authID></authID>' .
            '<isLocked></isLocked>' .
            '</GopayTranRes>';
        $sourceData = ['content' => $content];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '<respCode>0000</respCode>' .
            '<tranCode>4020</tranCode>' .
            '<merchantID>20130809</merchantID>' .
            '<merOrderNum>201503160000002219</merOrderNum>' .
            '<tranAmt></tranAmt>' .
            '<feeAmt></feeAmt>' .
            '<currencyType></currencyType>' .
            '<merURL></merURL>' .
            '<customerEMail></customerEMail>' .
            '<tranDateTime>20150316094511</tranDateTime>' .
            '<virCardNo></virCardNo>' .
            '<virCardNoIn></virCardNoIn>' .
            '<tranIP>3</tranIP>' .
            '<msgExt>success</msgExt>' .
            '<orgtranDateTime>20150316094511</orgtranDateTime>' .
            '<orgTxnStat>20000</orgTxnStat>' .
            '<orgOrderNum>201503160000002219</orgOrderNum>' .
            '<orgTxnType>8888</orgTxnType>' .
            '<orgtranAmt>10.00</orgtranAmt>' .
            '<authID></authID>' .
            '<isLocked></isLocked>' .
            '<signValue>123456</signValue>' .
            '</GopayTranRes>';

        $sourceData = ['content' => $content];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '<respCode>0000</respCode>' .
            '<tranCode>4020</tranCode>' .
            '<merchantID>20130809</merchantID>' .
            '<merOrderNum>201503160000002219</merOrderNum>' .
            '<tranAmt></tranAmt>' .
            '<feeAmt></feeAmt>' .
            '<currencyType></currencyType>' .
            '<merURL></merURL>' .
            '<customerEMail></customerEMail>' .
            '<tranDateTime>20150316094511</tranDateTime>' .
            '<virCardNo></virCardNo>' .
            '<virCardNoIn></virCardNoIn>' .
            '<tranIP>3</tranIP>' .
            '<msgExt>success</msgExt>' .
            '<orgtranDateTime>20150316094511</orgtranDateTime>' .
            '<orgTxnStat>100</orgTxnStat>' .
            '<orgOrderNum>201503160000002219</orgOrderNum>' .
            '<orgTxnType>8888</orgTxnType>' .
            '<orgtranAmt>10.00</orgtranAmt>' .
            '<authID></authID>' .
            '<isLocked></isLocked>' .
            '<signValue>b8182800634b48e7eeb3129dce3607da</signValue>' .
            '</GopayTranRes>';

        $sourceData = ['content' => $content];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '<respCode>0000</respCode>' .
            '<tranCode>4020</tranCode>' .
            '<merchantID>20130809</merchantID>' .
            '<merOrderNum>201503160000002219</merOrderNum>' .
            '<tranAmt></tranAmt>' .
            '<feeAmt></feeAmt>' .
            '<currencyType></currencyType>' .
            '<merURL></merURL>' .
            '<customerEMail></customerEMail>' .
            '<tranDateTime>20150316094511</tranDateTime>' .
            '<virCardNo></virCardNo>' .
            '<virCardNoIn></virCardNoIn>' .
            '<tranIP>3</tranIP>' .
            '<msgExt>success</msgExt>' .
            '<orgtranDateTime>20150316094511</orgtranDateTime>' .
            '<orgTxnStat>20000</orgTxnStat>' .
            '<orgOrderNum>201503160000002219</orgOrderNum>' .
            '<orgTxnType>8888</orgTxnType>' .
            '<orgtranAmt>10.00</orgtranAmt>' .
            '<authID></authID>' .
            '<isLocked></isLocked>' .
            '<signValue>94634b223cb1b4ea203287270fbfa9a1</signValue>' .
            '</GopayTranRes>';

        $sourceData = [
            'content' => $content,
            'amount' => '100.00'
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<GopayTranRes>' .
            '<respCode>0000</respCode>' .
            '<tranCode>4020</tranCode>' .
            '<merchantID>20130809</merchantID>' .
            '<merOrderNum>201503160000002219</merOrderNum>' .
            '<tranAmt></tranAmt>' .
            '<feeAmt></feeAmt>' .
            '<currencyType></currencyType>' .
            '<merURL></merURL>' .
            '<customerEMail></customerEMail>' .
            '<tranDateTime>20150316094511</tranDateTime>' .
            '<virCardNo></virCardNo>' .
            '<virCardNoIn></virCardNoIn>' .
            '<tranIP>3</tranIP>' .
            '<msgExt>success</msgExt>' .
            '<orgtranDateTime>20150316094511</orgtranDateTime>' .
            '<orgTxnStat>20000</orgTxnStat>' .
            '<orgOrderNum>201503160000002219</orgOrderNum>' .
            '<orgTxnType>8888</orgTxnType>' .
            '<orgtranAmt>10.00</orgtranAmt>' .
            '<authID></authID>' .
            '<isLocked></isLocked>' .
            '<signValue>94634b223cb1b4ea203287270fbfa9a1</signValue>' .
            '</GopayTranRes>';

        $sourceData = [
            'content' => $content,
            'amount' => '10.00'
        ];

        $newGofPay = new NewGofPay();
        $newGofPay->setPrivateKey('test');
        $newGofPay->setOptions($sourceData);
        $newGofPay->paymentTrackingVerify();
    }
}
