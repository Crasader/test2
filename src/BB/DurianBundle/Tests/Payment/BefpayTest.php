<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Befpay;
use Buzz\Message\Response;

class BefpayTest extends DurianTestCase
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

        $befpay = new Befpay();
        $befpay->getVerifyData();
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

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->getVerifyData();
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
            'number' => '25771756',
            'orderId' => '201508060000000203',
            'username' => 'wulai',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->getVerifyData();
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
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $requestData = $befpay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $this->assertEquals($options['orderId'], $requestData['p2_xn']);
        $this->assertEquals($options['number'], $requestData['p3_bn']);
        $this->assertEquals($options['username'], $requestData['p5_name']);
        $this->assertEquals($options['amount'], $requestData['p6_amount']);
        $this->assertEquals($notifyUrl, $requestData['p9_url']);
        $this->assertEquals(10018, $requestData['p4_pd']);
        $this->assertEquals('640bb65c37c401664b8cc7e4a0c9192f', $requestData['sign']);
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified'
        );

        $befpay = new Befpay();
        $befpay->verifyOrderPayment([]);
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

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->verifyOrderPayment([]);
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
            'p1_md' => 1,
            'p2_sn' => '201508060003398759',
            'p3_xn' => '201508060000000203',
            'p4_amt' => '0.01',
            'p5_ex' => '',
            'p6_pd' => '10018',
            'p7_st' => 'success',
            'p8_reply' => 1
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->verifyOrderPayment([]);
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
            'p1_md' => 1,
            'p2_sn' => '201508060003398759',
            'p3_xn' => '201508060000000203',
            'p4_amt' => '0.01',
            'p5_ex' => '',
            'p6_pd' => '10018',
            'p7_st' => 'success',
            'p8_reply' => 1,
            'sign' => 'B0854F43058D7DF3ACAF56A3F'
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時狀態非成功
     */
    public function testReturnWithoutSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'p1_md' => 1,
            'p2_sn' => '201508060003398759',
            'p3_xn' => '201508060000000203',
            'p4_amt' => '0.01',
            'p5_ex' => '',
            'p6_pd' => '10018',
            'p7_st' => '?',
            'p8_reply' => 1,
            'sign' => '87a0faab41ddfb2884c12c53cec9b311'
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->verifyOrderPayment([]);
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
            'p1_md' => 1,
            'p2_sn' => '201508060003398759',
            'p3_xn' => '201508060000000201',
            'p4_amt' => '0.01',
            'p5_ex' => '',
            'p6_pd' => '10018',
            'p7_st' => 'success',
            'p8_reply' => 1,
            'sign' => 'd2a85c91f115592090d7810ff857cad7'
        ];

        $entry = ['id' => '201503220000000555'];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->verifyOrderPayment($entry);
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
            'p1_md' => 1,
            'p2_sn' => '201508060003398759',
            'p3_xn' => '201508060000000201',
            'p4_amt' => '0.01',
            'p5_ex' => '',
            'p6_pd' => '10018',
            'p7_st' => 'success',
            'p8_reply' => 1,
            'sign' => 'd2a85c91f115592090d7810ff857cad7'
        ];

        $entry = [
            'id' => '201508060000000201',
            'amount' => 0.1
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'p1_md' => 1,
            'p2_sn' => '201508060003398759',
            'p3_xn' => '201508060000000201',
            'p4_amt' => '0.01',
            'p5_ex' => '',
            'p6_pd' => '10018',
            'p7_st' => 'success',
            'p8_reply' => 1,
            'sign' => 'd2a85c91f115592090d7810ff857cad7'
        ];

        $entry = [
            'id' => '201508060000000201',
            'amount' => '0.01'
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $befpay->getMsg());
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

        $befpay = new Befpay();
        $befpay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->paymentTracking();
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
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
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
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.123'
        ];

        $befpay = new Befpay();
        $befpay->setContainer($this->container);
        $befpay->setClient($this->client);
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
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
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.123'
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $befpay = new Befpay();
        $befpay->setContainer($this->container);
        $befpay->setClient($this->client);
        $befpay->setResponse($response);
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
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
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.123'
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $befpay = new Befpay();
        $befpay->setContainer($this->container);
        $befpay->setClient($this->client);
        $befpay->setResponse($response);
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
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
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.123'
        ];

        $result = 'SN:201508060003398759';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $befpay = new Befpay();
        $befpay->setContainer($this->container);
        $befpay->setClient($this->client);
        $befpay->setResponse($response);
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '"AT:2015-8-6 16:49:54|SN:201508060003189863|PName:befpay|'.
            'XN:201508060000000201|SP:1|Fee:0.0000|Amt:0.0100|ST:1"';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $befpay = new Befpay();
        $befpay->setContainer($this->container);
        $befpay->setClient($this->client);
        $befpay->setResponse($response);
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
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
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => '201508060000000203',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '"AT:2015-8-6 16:49:54|SN:201508060003189863|PName:befpay|'.
            'XN:201508060000000201|SP:1|Fee:0.0000|Amt:0.0100|ST:3"';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $befpay = new Befpay();
        $befpay->setContainer($this->container);
        $befpay->setClient($this->client);
        $befpay->setResponse($response);
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
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
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'amount' => '400.00',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '"AT:2015-8-6 16:49:54|SN:201508060003189863|PName:befpay|'.
            'XN:201508060000000201|SP:1|Fee:0.0000|Amt:0.0100|ST:3"';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $befpay = new Befpay();
        $befpay->setContainer($this->container);
        $befpay->setClient($this->client);
        $befpay->setResponse($response);
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'amount' => '0.01',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1'
        ];

        $result = '"AT:2015-8-6 16:49:54|SN:201508060003189863|PName:befpay|'.
            'XN:201508060000000201|SP:1|Fee:0.0000|Amt:0.0100|ST:3"';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $befpay = new Befpay();
        $befpay->setContainer($this->container);
        $befpay->setClient($this->client);
        $befpay->setResponse($response);
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->paymentTracking();
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

        $befpay = new Befpay();
        $befpay->getPaymentTrackingData();
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

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->getPaymentTrackingData();
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
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $befpay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'orderCreateDate' => '20150813114648',
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.5dd.com',
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('test');
        $befpay->setOptions($options);
        $trackingData = $befpay->getPaymentTrackingData();

        $path = '/frontpage/OrderInfo?BN=19822546&XN=201508060000000201&DATE=2015-08-13&' .
            'SIGN=1b68d5acfc8f9d50ce9f48de63204719';

        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('payment.http.www.5dd.com', $trackingData['headers']['Host']);
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

        $befpay = new Befpay();
        $befpay->paymentTrackingVerify();
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

        $sourceData = ['content' => ''];

        $befpay = new Befpay();
        $befpay->setPrivateKey('1234');
        $befpay->setOptions($sourceData);
        $befpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '"AT:2015-8-6 16:49:54|SN:201508060003189863|PName:befpay|'.
            'XN:201508060000000201|SP:1|Fee:0.0000|Amt:0.0100|ST:1"';
        $sourceData = ['content' => $result];

        $befpay = new Befpay();
        $befpay->setPrivateKey('1234');
        $befpay->setOptions($sourceData);
        $befpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入訂單號不正確
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '"AT:2015-8-6 16:49:54|SN:201508060003189863|PName:befpay|'.
            'XN:201508060000000201|SP:1|Fee:0.0000|Amt:0.0100|ST:3"';
        $sourceData = [
            'content' => $result,
            'orderId' => '201508060000000203'
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('1234');
        $befpay->setOptions($sourceData);
        $befpay->paymentTrackingVerify();
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

        $result = '"AT:2015-8-6 16:49:54|SN:201508060003189863|PName:befpay|'.
            'XN:201508060000000201|SP:1|Fee:0.0000|Amt:0.0100|ST:3"';
        $sourceData = [
            'content' => $result,
            'orderId' => '201508060000000201',
            'amount' => 400
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('1234');
        $befpay->setOptions($sourceData);
        $befpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $result = '"AT:2015-8-6 16:49:54|SN:201508060003189863|PName:befpay|'.
            'XN:201508060000000201|SP:1|Fee:0.0000|Amt:0.0100|ST:3"';
        $sourceData = [
            'content' => $result,
            'orderId' => '201508060000000201',
            'amount' => 0.01
        ];

        $befpay = new Befpay();
        $befpay->setPrivateKey('1234');
        $befpay->setOptions($sourceData);
        $befpay->paymentTrackingVerify();
    }
}
