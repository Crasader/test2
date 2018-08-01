<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Eypal;
use Buzz\Message\Response;

class EypalTest extends DurianTestCase
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

        $eypal = new Eypal();
        $eypal->getVerifyData();
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

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->getVerifyData();
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
            'number' => '123456',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '100',
            'orderId' => '201606060000000001',
            'amount' => '100',
            'ip' => '127.0.0.1',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'ip' => '127.0.0.1',
            'number' => '123456',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $data = $eypal->getVerifyData();

        $remark = sprintf(
            '%s_%s',
            $options['merchantId'],
            $options['domain']
        );

        $this->assertEquals('1.0', $data['version']);
        $this->assertEquals($options['number'], $data['partner']);
        $this->assertEquals($options['orderId'], $data['orderid']);
        $this->assertEquals($options['amount'], $data['payamount']);
        $this->assertEquals($options['ip'], $data['payip']);
        $this->assertEquals($options['notify_url'], $data['notifyurl']);
        $this->assertEquals($options['notify_url'], $data['returnurl']);
        $this->assertEquals('ICBC', $data['paytype']);
        $this->assertEquals($remark, $data['remark']);
        $this->assertEquals('8062e8788cdf6ab7637a9bf16880ce20', $data['sign']);
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

        $eypal = new Eypal();
        $eypal->verifyOrderPayment([]);
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

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->verifyOrderPayment([]);
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
            'version' => '1.0',
            'partner' => '1639',
            'orderid' => '201605250000003835',
            'payamount' => '0.91',
            'opstate' => '2',
            'orderno' => 'B5636326325139667262',
            'eypaltime' => '19000101000000',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '12345_6',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->verifyOrderPayment([]);
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
            'version' => '1.0',
            'partner' => '1639',
            'orderid' => '201605250000003835',
            'payamount' => '0.91',
            'opstate' => '2',
            'orderno' => 'B5636326325139667262',
            'eypaltime' => '19000101000000',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '12345_6',
            'sign' => '',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'version' => '1.0',
            'partner' => '1639',
            'orderid' => '201605250000003835',
            'payamount' => '0.01',
            'opstate' => '0',
            'orderno' => 'B5636326325139667262',
            'eypaltime' => '19000101000000',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '12345_6',
            'sign' => '967e0ddcfad15eb4c151b31f5f64f8c8',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->verifyOrderPayment([]);
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
            'version' => '1.0',
            'partner' => '1639',
            'orderid' => '201605250000003835',
            'payamount' => '0.01',
            'opstate' => '1',
            'orderno' => 'B5636326325139667262',
            'eypaltime' => '19000101000000',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '12345_6',
            'sign' => 'fb8a74a2f8cbcec91157e844e185bad9',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->verifyOrderPayment([]);
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
            'version' => '1.0',
            'partner' => '1639',
            'orderid' => '201605250000003835',
            'payamount' => '0.01',
            'opstate' => '2',
            'orderno' => 'B5636326325139667262',
            'eypaltime' => '19000101000000',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '12345_6',
            'sign' => '8a432bf1c81d67c395bf9e0de8d15661',
        ];

        $entry = ['id' => '201509140000002475'];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->verifyOrderPayment($entry);
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
            'version' => '1.0',
            'partner' => '1639',
            'orderid' => '201605250000003835',
            'payamount' => '0.01',
            'opstate' => '2',
            'orderno' => 'B5636326325139667262',
            'eypaltime' => '19000101000000',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '12345_6',
            'sign' => '8a432bf1c81d67c395bf9e0de8d15661',
        ];

        $entry = [
            'id' => '201605250000003835',
            'amount' => '15.00',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'version' => '1.0',
            'partner' => '1639',
            'orderid' => '201605250000003835',
            'payamount' => '0.01',
            'opstate' => '2',
            'orderno' => 'B5636326325139667262',
            'eypaltime' => '19000101000000',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '12345_6',
            'sign' => '8a432bf1c81d67c395bf9e0de8d15661',
        ];

        $entry = [
            'id' => '201605250000003835',
            'amount' => '0.01',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->verifyOrderPayment($entry);

        $this->assertEquals('success', $eypal->getMsg());
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

        $eypal = new Eypal();
        $eypal->paymentTracking();
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

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->paymentTracking();
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
            'number' => '123456',
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $eypal = new Eypal();
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
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
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setResponse($response);
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setResponse($response);
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
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
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = 'orderid=201605270000003865|payamount=0.91|opstate=2|orderno=B5025164780291325961|ey' .
            'paltime=20160527130226|message=success|paytype=ICBC|remark=50504_6|sign=eb645f6b70c419ee64e1bccd1a7ff288';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setResponse($response);
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
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
            'amount' => '0.91',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = 'partner=1639|orderid=201605270000003865|payamount=0.91|opstate=2|orderno=B5025164780291325961|ey' .
            'paltime=20160527130226|message=success|paytype=ICBC|remark=50504_6';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setResponse($response);
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
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
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = 'partner=1639|orderid=201605270000003865|payamount=0.91|opstate=2|orderno=B5025164780291325961|ey' .
            'paltime=20160527130226|message=success|paytype=ICBC|remark=50504_6|sign=eb645f6b70c419ee64e1bccd1a7ff288';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setResponse($response);
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201605270000003865',
            'amount' => '0.91',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = 'partner=1639|orderid=201605270000003865|payamount=0.91|opstate=0|orderno=B5025164780291325961|ey' .
            'paltime=20160527130226|message=success|paytype=ICBC|remark=50504_6|sign=9a12243f42989ce6480011a85f493ee4';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setResponse($response);
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
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
            'orderId' => '201605270000003865',
            'amount' => '0.91',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = 'partner=1639|orderid=201605270000003865|payamount=0.91|opstate=1|orderno=B5025164780291325961|ey' .
            'paltime=20160527130226|message=success|paytype=ICBC|remark=50504_6|sign=b1a12b54d8106968f0b772e3c2b8d23d';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setResponse($response);
        $eypal->setPrivateKey('test');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201509140000002473',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = 'partner=1639|orderid=201605270000003865|payamount=0.91|opstate=2|orderno=B5025164780291325961|ey' .
            'paltime=20160527130226|message=success|paytype=ICBC|remark=50504_6|sign=411807b7b6a60460bc4aacd22bb4052e';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $eypal = new Eypal();
        $eypal->setContainer($this->container);
        $eypal->setClient($this->client);
        $eypal->setResponse($response);
        $eypal->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $eypal->setOptions($options);
        $eypal->paymentTracking();
    }
}
