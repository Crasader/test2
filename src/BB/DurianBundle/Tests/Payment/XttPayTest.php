<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XttPay;
use Buzz\Message\Response;

class XttPayTest extends DurianTestCase
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

        $xttPay = new XttPay();
        $xttPay->getVerifyData();
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

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->getVerifyData();
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
            'number' => '888100000001792',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201608250000004029',
            'amount' => '100',
            'ip' => '127.0.0.1',
        ];

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
        ];

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $data = $xttPay->getVerifyData();

        $this->assertEquals($options['number'], $data['parter']);
        $this->assertEquals($options['orderId'], $data['orderid']);
        $this->assertEquals($options['amount'], $data['value']);
        $this->assertEquals($options['notify_url'], $data['callbackurl']);
        $this->assertEquals('967', $data['type']);
        $this->assertEquals('bf1e89ece50cd6df3f95dae87185ce48', $data['sign']);
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

        $xttPay = new XttPay();
        $xttPay->verifyOrderPayment([]);
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

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名數據(Sign)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'orderid' => '201610040000008381',
            'opstate' => '0',
            'ovalue' => '0.02',
            'systime' => '2016-10-04 11:41:21',
            'sysorderid' => '1610041140411802009',
            'completiontime' => '2016-10-04 11:41:21',
            'attach' => '',
            'msg' => '',
        ];

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->verifyOrderPayment([]);
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
            'orderid' => '201610040000008381',
            'opstate' => '0',
            'ovalue' => '0.02',
            'systime' => '2016-10-04 11:41:21',
            'sysorderid' => '1610041140411802009',
            'completiontime' => '2016-10-04 11:41:21',
            'attach' => '',
            'msg' => '',
            'sign' => '123456789',
        ];


        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->verifyOrderPayment([]);
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
            'orderid' => '201610040000008381',
            'opstate' => '1',
            'ovalue' => '0.02',
            'systime' => '2016-10-04 11:41:21',
            'sysorderid' => '1610041140411802009',
            'completiontime' => '2016-10-04 11:41:21',
            'attach' => '',
            'msg' => '',
            'sign' => '66e5c4be0b26c644620a93763f95fd03',
        ];

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->verifyOrderPayment([]);
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
            'orderid' => '201610040000008381',
            'opstate' => '0',
            'ovalue' => '0.02',
            'systime' => '2016-10-04 11:41:21',
            'sysorderid' => '1610041140411802009',
            'completiontime' => '2016-10-04 11:41:21',
            'attach' => '',
            'msg' => '',
            'sign' => 'ae19a03a7e11da6eeb57792d79d5e47f',
        ];

        $entry = ['id' => '201509140000002475'];

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->verifyOrderPayment($entry);
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
            'orderid' => '201610040000008381',
            'opstate' => '0',
            'ovalue' => '0.02',
            'systime' => '2016-10-04 11:41:21',
            'sysorderid' => '1610041140411802009',
            'completiontime' => '2016-10-04 11:41:21',
            'attach' => '',
            'msg' => '',
            'sign' => 'ae19a03a7e11da6eeb57792d79d5e47f',
        ];

        $entry = [
            'id' => '201610040000008381',
            'amount' => '15.00',
        ];

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'orderid' => '201610040000008381',
            'opstate' => '0',
            'ovalue' => '0.02',
            'systime' => '2016-10-04 11:41:21',
            'sysorderid' => '1610041140411802009',
            'completiontime' => '2016-10-04 11:41:21',
            'attach' => '',
            'msg' => '',
            'sign' => '89238372922bd19d7ae5cb239e3c7bc1',
        ];

        $entry = [
            'id' => '201610040000008381',
            'amount' => '0.02',
        ];

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('ad9f7aac858148c197997b3f7e80db44');
        $xttPay->setOptions($options);
        $xttPay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $xttPay->getMsg());
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

        $xttPay = new XttPay();
        $xttPay->paymentTracking();
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

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xttPay = new XttPay();
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
        ];

        $result = 'orderid=201610040000008389&ovalue=0.02&sign=c37329b51505e3d7fcb3ae17dea94292';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有Sign的情況
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
        ];

        $result = 'orderid=201610040000008389&opstate=0&ovalue=0.02';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
        ];

        $result = 'orderid=201610040000008389&opstate=0&ovalue=0.02&sign=123456';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果請求參數無效
     */
    public function testTrackingReturnSubmitParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
        ];

        $result = 'orderid=201610040000008389&opstate=3&ovalue=0.02&sign=663ad0151471f3c2e7bc531c96dfc7a1';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名錯誤
     */
    public function testTrackingReturnMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
        ];

        $result = 'orderid=201610040000008389&opstate=2&ovalue=0.02&sign=b34c8ccabf7500a247a36f6864e0ea7b';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果商戶訂單號無效
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order does not exist',
            180060
        );

        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
        ];

        $result = 'orderid=201610040000008389&opstate=1&ovalue=0.02&sign=6ac8262605a6e86a19f56b731a91cfc2';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
        ];

        $result = 'orderid=201610040000008389&opstate=99&ovalue=0.02&sign=8c1b883bde177e82dd4248369fdfabb6';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('test');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
            'amount' => '15.00'
        ];

        $result = 'orderid=201610040000008389&opstate=0&ovalue=0.02&sign=c37329b51505e3d7fcb3ae17dea94292';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('ad9f7aac858148c197997b3f7e80db44');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.xttpay.com',
            'amount' => '0.02'
        ];

        $result = 'orderid=201610040000008389&opstate=0&ovalue=0.02&sign=c37329b51505e3d7fcb3ae17dea94292';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xttPay = new XttPay();
        $xttPay->setContainer($this->container);
        $xttPay->setClient($this->client);
        $xttPay->setResponse($response);
        $xttPay->setPrivateKey('9782b1c4799943cab01d6e6d5d54fe2a');
        $xttPay->setOptions($options);
        $xttPay->paymentTracking();
    }
}
