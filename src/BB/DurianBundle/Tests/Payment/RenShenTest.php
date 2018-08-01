<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\RenShen;
use Buzz\Message\Response;

class RenShenTest extends DurianTestCase
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

        $renShen = new RenShen();
        $renShen->getVerifyData();
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

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->getVerifyData();
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
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '999',
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '1.01',
        ];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->getVerifyData();
    }

    /**
     * 測試支付設定回傳成功
     */
    public function testPayParameterSuccess()
    {
        $options = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1092',
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '1.01',
        ];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $requestData = $renShen->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('Rx.online.pay', $requestData['method']);
        $this->assertEquals('1', $requestData['isshow']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals('ALIPAY', $requestData['banktype']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals($options['amount'], $requestData['paymoney']);
        $this->assertEquals('8845e3e0313af13e56c60fd39fe2994c', $requestData['sign']);
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

        $renShen = new RenShen();
        $renShen->verifyOrderPayment([]);
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

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->verifyOrderPayment([]);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1090',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
        ];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->verifyOrderPayment([]);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => '123456789',
        ];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->verifyOrderPayment([]);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '2',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => '3c3e8f5efef185efef607b043665fb67',
        ];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->verifyOrderPayment([]);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => 'b16e5a9f1255dc5666fbb5f3612ea141',
        ];

        $entry = ['id' => '201503220000000555'];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->verifyOrderPayment($entry);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => 'b16e5a9f1255dc5666fbb5f3612ea141',
        ];

        $entry = [
            'id' => '201611110000000104',
            'amount' => '15.00',
        ];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => 'b16e5a9f1255dc5666fbb5f3612ea141',
        ];

        $entry = [
            'id' => '201611110000000104',
            'amount' => '0.01',
        ];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->verifyOrderPayment($entry);

        $this->assertEquals('ok', $renShen->getMsg());
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

        $renShen = new RenShen();
        $renShen->paymentTracking();
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

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201611110000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $renShen = new RenShen();
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201611110000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201611110000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201609050000004640',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"201611110000000104","sysnumber":' .
            '"RX1696916111712064553531000","tradestate":"1","paymoney":"0.01","banktype":"WEIXIN","paytime":' .
            '"2016-11-17 12:06:29","endtime":"2016-11-17 12:06:44","message":"查询成功",' .
            '"sign":"7586cce903e2f3c8e62aaa3e720932de"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $options = [
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"","sysnumber":"","status":"0","tradestate":"",' .
            '"paymoney":"0.00","banktype":"","paytime":"","endtime":"","message":"签名错误","sign":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201609050000004640',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"201611110000000104",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"0.01",' .
            '"banktype":"WEIXIN","message":"查询成功","sign":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
    }

    /**
     * 測試訂單查詢驗簽錯誤
     */
    public function testTrackingReturnWithErrorSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '16969',
            'orderId' => '201609050000004640',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"201611110000000104",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"0.01",' .
            '"banktype":"WEIXIN","paytime":"2016-11-17 12:06:29","endtime":"2016-11-17 12:06:44","message":"查询成功",' .
            '"sign":"1234"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"201611110000000104",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"0","paymoney":"0.01",' .
            '"banktype":"WEIXIN","paytime":"2016-11-17 12:06:29","endtime":"2016-11-17 12:06:44","message":"查询成功",' .
            '"sign":"1fd972068c70b6e024185b1272943ef8"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"201611110000000104",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"2","paymoney":"0.01",' .
            '"banktype":"WEIXIN","paytime":"2016-11-17 12:06:29","endtime":"2016-11-17 12:06:44","message":"查询成功",' .
            '"sign":"52ae499ca31cf8c82ad7dadf3a95674f"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('test');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201611110000000101',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"201611110000000104",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"0.01",' .
            '"banktype":"WEIXIN","paytime":"2016-11-17 12:06:29","endtime":"2016-11-17 12:06:44","message":"查询成功",' .
            '"sign":"7586cce903e2f3c8e62aaa3e720932de"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('029268af05f5402685aa2be9121d1e70');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
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
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '100.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"201611110000000104",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"0.01",' .
            '"banktype":"WEIXIN","paytime":"2016-11-17 12:06:29","endtime":"2016-11-17 12:06:44","message":"查询成功",' .
            '"sign":"7586cce903e2f3c8e62aaa3e720932de"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('029268af05f5402685aa2be9121d1e70');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"version":"1.0","partner":"","ordernumber":"201611110000000104",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"0.01",' .
            '"banktype":"WEIXIN","paytime":"2016-11-17 12:06:29","endtime":"2016-11-17 12:06:44","message":"查询成功",' .
            '"sign":"7586cce903e2f3c8e62aaa3e720932de"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $renShen = new RenShen();
        $renShen->setContainer($this->container);
        $renShen->setClient($this->client);
        $renShen->setResponse($response);
        $renShen->setPrivateKey('029268af05f5402685aa2be9121d1e70');
        $renShen->setOptions($options);
        $renShen->paymentTracking();
    }
}
