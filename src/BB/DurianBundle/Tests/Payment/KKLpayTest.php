<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KKLpay;
use Buzz\Message\Response;

class KKLpayTest extends DurianTestCase
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

        $kklPay = new KKLpay();
        $kklPay->getVerifyData();
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

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '25771756',
            'orderId' => '201508060000000203',
            'username' => 'wulai',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '2015-09-11 11:32:32',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $requestData = $kklPay->getVerifyData();

        $this->assertEquals($options['number'], $requestData['merchantCode']);
        $this->assertEquals($options['orderId'], $requestData['outOrderId']);
        $this->assertEquals($options['amount'] * 100, $requestData['totalAmount']);
        $this->assertEquals($notifyUrl, $requestData['merUrl']);
        $this->assertEquals($notifyUrl, $requestData['notifyUrl']);
        $this->assertEquals($options['username'], $requestData['goodsName']);
        $this->assertEquals('20150911113232', $requestData['merchantOrderTime']);
        $this->assertEquals('20150911123232', $requestData['lastPayTime']);
        $this->assertEquals('1DCAA06DF01C2136542B93EADE21FDA4', $requestData['sign']);
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

        $kklPay = new KKLpay();
        $kklPay->verifyOrderPayment([]);
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

        $options = ['p1_md' => '1'];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201509100000000528',
            'transTime' => '20150910121434',
            'totalAmount' => '1'
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign錯誤
     */
    public function testReturnWithWrongSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201509100000000528',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
            'sign' => '705555C41AC3FC6A0C200B8D32FB2E9'
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單號不一樣
     */
    public function testReturnWithErrorOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201509100000000528',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
            'sign' => '4F47741D28E33B4F00E81AD4915F6740'
        ];

        $entry = ['id' => '201503220000000555'];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不一樣
     */
    public function testReturnWithErrorAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201509100000000528',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
            'sign' => '4F47741D28E33B4F00E81AD4915F6740'
        ];

        $entry = [
            'id' => '201509100000000528',
            'amount' => '0.1'
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201509100000000528',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
            'sign' => '4F47741D28E33B4F00E81AD4915F6740'
        ];

        $entry = [
            'id' => '201509100000000528',
            'amount' => '0.01'
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->verifyOrderPayment($entry);
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

        $kklPay = new KKLpay();
        $kklPay->paymentTracking();
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

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->paymentTracking();
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
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
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

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
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
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
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
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台未返回參數code
     */
    public function testTrackingReturnWithNoCodeReturn()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"data":{"merchantCode":"1000000550"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台返回錯誤code
     */
    public function testTrackingReturnWithWrongCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"11","data":{"amount":1,"merchantCode":"1000000550"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單不存在
     */
    public function testTrackingReturnWithOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","data":{},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台未返回data
     */
    public function testTrackingReturnNoDataReturn()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","dataa":{"merchantCode":"1000000550"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
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
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","data":{"amount":1,"merchantCode":"1000000550"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
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
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"11","transTime":"20150910114403","transType":"00200"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
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
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"11","sign":"A50668C687801316EB26F3D635C3EE",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
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
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"11","sign":"A50668C687801316EB26F3D635C35DEE",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入訂單號不正確
     */
    public function testPaymentTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '19822546',
            'orderId' => '201509100000000528',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"00","sign":"FD91861D8C642E416D6F4666B72B8A47",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
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
            'number' => '19822546',
            'amount' => '0.02',
            'orderId' => '201509100000000527',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"00","sign":"FD91861D8C642E416D6F4666B72B8A47",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '19822546',
            'amount' => '0.01',
            'orderId' => '201509100000000527',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"00","sign":"FD91861D8C642E416D6F4666B72B8A47",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kklPay = new KKLpay();
        $kklPay->setContainer($this->container);
        $kklPay->setClient($this->client);
        $kklPay->setResponse($response);
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kklPay = new KKLpay();
        $kklPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入number
     */
    public function testGetPaymentTrackingDataWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['orderId' => '201508060000000201'];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $kklPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.kklPay.com',
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($options);
        $trackingData = $kklPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/ebank/queryOrder.do', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('test', $trackingData['json']['project_id']);
        $this->assertEquals('19822546', $trackingData['json']['param']['merchantCode']);
        $this->assertEquals('201508060000000201', $trackingData['json']['param']['outOrderId']);
        $this->assertEquals('DE94FF8EC92E3AF8E3F16877D9D52EA1', $trackingData['json']['param']['sign']);
        $this->assertEquals('payment.http.www.kklPay.com', $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢但沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kklPay = new KKLpay();
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未返回參數code
     */
    public function testPaymentTrackingVerifyButNoCodeReturn()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = ['content' => '{"data":{"merchantCode":"1000000550"},"msg":"成功"}'];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回錯誤code
     */
    public function testPaymentTrackingVerifyButWrongCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $sourceData = ['content' => '{"code":"11","data":{"amount":1,"merchantCode":"1000000550"},"msg":"成功"}'];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單不存在
     */
    public function testPaymentTrackingVerifyButOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $sourceData = ['content' => '{"code":"00","data":{},"msg":"成功"}'];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未返回data
     */
    public function testPaymentTrackingVerifyButNoDataReturn()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = ['content' => '{"code":"00","dataa":{"merchantCode":"1000000550"},"msg":"成功"}'];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = ['content' => '{"code":"00","data":{"amount":1,"merchantCode":"1000000550"},"msg":"成功"}'];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"11","transTime":"20150910114403","transType":"00200"},"msg":"成功"}';
        $sourceData = ['content' => $content];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但簽名驗證錯誤
     */
    public function testPaymentTrackingVerifyButSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"11","sign":"A50668C687801316EB26F3D635C3EE",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';
        $sourceData = ['content' => $content];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但支付失敗
     */
    public function testPaymentTrackingVerifyButPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"11","sign":"A50668C687801316EB26F3D635C35DEE",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';
        $sourceData = ['content' => $content];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單號不正確
     */
    public function testPaymentTrackingVerifyButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"00","sign":"FD91861D8C642E416D6F4666B72B8A47",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';
        $sourceData = [
            'content' => $content,
            'orderId' => '201509100000000528'
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但金額不正確
     */
    public function testPaymentTrackingVerifyButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"00","sign":"FD91861D8C642E416D6F4666B72B8A47",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';
        $sourceData = [
            'content' => $content,
            'orderId' => '201509100000000527',
            'amount' => '0.02'
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '{"code":"00","data":{"amount":1,"instructCode":"11001200362",' .
            '"merchantCode":"1000000550","outOrderId":"201509100000000527",' .
            '"replyCode":"00","sign":"FD91861D8C642E416D6F4666B72B8A47",' .
            '"transTime":"20150910114403","transType":"00200"},"msg":"成功"}';
        $sourceData = [
            'content' => $content,
            'orderId' => '201509100000000527',
            'amount' => '0.01'
        ];

        $kklPay = new KKLpay();
        $kklPay->setPrivateKey('test');
        $kklPay->setOptions($sourceData);
        $kklPay->paymentTrackingVerify();
    }

    /**
     * 測試轉換訂單查詢支付平台返回的編碼
     */
    public function testProcessTrackingResponseEncoding()
    {
        // 將支付平台的返回做編碼模擬 kue 返回
        $body = '{"code":"00","data":{"amount":500,"instructCode":"11003839084","merchantCode":"1000001687",' .
            '"outOrderId":"201601260161801526","replyCode":"00","sign":"7980ECFE7DA80F65E3DD4F774474B6BD",' .
            '"transTime":"20160126034330","transType":"00200"},"msg":"成功"}';
        $encodedBody = base64_encode($body);

        $encodedResponse = [
            'header' => [
                'server' => 'Apache-Coyote/1.1',
                'content-type' => 'application/json;charset=UTF-8'
            ],
            'body' => $encodedBody
        ];

        $kklPay = new KKLpay();
        $trackingResponse = $kklPay->processTrackingResponseEncoding($encodedResponse);

        $this->assertEquals($encodedResponse['header'], $trackingResponse['header']);
        $this->assertEquals($body, $trackingResponse['body']);
    }
}
