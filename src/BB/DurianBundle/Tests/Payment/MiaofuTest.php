<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Miaofu;
use Buzz\Message\Response;

class MiaofuTest extends DurianTestCase
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

        $miaofu = new Miaofu();
        $miaofu->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->getVerifyData();
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
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '100',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $requestData = $miaofu->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $reqReferer = parse_url($options['notify_url']);

        $this->assertEquals($options['number'], $requestData['merchant_code']);
        $this->assertEquals($options['orderId'], $requestData['order_no']);
        $this->assertEquals('100.00', $requestData['order_amount']);
        $this->assertEquals($notifyUrl, $requestData['notify_url']);
        $this->assertEquals('2015-03-16 09:45:11', $requestData['order_time']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
        $this->assertEquals($options['ip'], $requestData['customer_ip']);
        $this->assertEquals($reqReferer['host'], $requestData['req_referer']);
        $this->assertEquals('a3c84c5ce8ec259e18c16cb8ea4aa726', $requestData['sign']);
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

        $miaofu = new Miaofu();
        $miaofu->verifyOrderPayment([]);
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

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->verifyOrderPayment([]);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
        ];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->verifyOrderPayment([]);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
            'sign' => '123456789',
        ];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->verifyOrderPayment([]);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'fail',
            'sign' => 'cdaa5de75cf6f387e6ea00f7b6104589',
        ];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->verifyOrderPayment([]);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
            'sign' => 'b160427a1f80f99cd39a0cc6ee074b7c',
        ];

        $entry = ['id' => '201503220000000555'];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->verifyOrderPayment($entry);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
            'sign' => 'b160427a1f80f99cd39a0cc6ee074b7c',
        ];

        $entry = [
            'id' => '201506100000002073',
            'amount' => '15.00',
        ];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
            'sign' => 'b160427a1f80f99cd39a0cc6ee074b7c',
        ];

        $entry = [
            'id' => '201506100000002073',
            'amount' => '100.00',
        ];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->verifyOrderPayment($entry);

        $this->assertEquals('success', $miaofu->getMsg());
    }

    /**
     * 測試訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $miaofu = new Miaofu();
        $miaofu->paymentTracking();
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

        $options = ['number' => '19822546'];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
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
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $miaofu = new Miaofu();
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線異常
     */
    public function testTrackingReturnPaymentGatewayConnectionError()
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
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線失敗
     */
    public function testTrackingReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳結果為空
     */
    public function testTrackingReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有response的情況
     */
    public function testTrackingReturnWithoutResponse()
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
            'verify_url' => 'abc.123',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果訂單不存在
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
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>FALSE</is_success>' .
            '<error_msg>参数order_no的值201511230000002766不存在</error_msg>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>FALSE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '</response>'.
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有sign的情況
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
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>123456789</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果交易中
     */
    public function testTrackingReturnPaymentOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>paying</trade_status>' .
            '<sign>d477a0ed2af54abdd9bb8df9096ffcb7</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>fail</trade_status>' .
            '<sign>6f4395ee2c06b5958e4899d1e29e8c1d</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
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
            'amount' => '400.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>1acd91e8c32c16b96560cea579afdfa9</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>1acd91e8c32c16b96560cea579afdfa9</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $miaofu = new Miaofu();
        $miaofu->setContainer($this->container);
        $miaofu->setClient($this->client);
        $miaofu->setResponse($response);
        $miaofu->setPrivateKey('test');
        $miaofu->setOptions($options);
        $miaofu->paymentTracking();
    }
}
