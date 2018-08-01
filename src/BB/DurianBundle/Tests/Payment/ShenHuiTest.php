<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShenHui;
use Buzz\Message\Response;

class ShenHuiTest extends DurianTestCase
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

        $shenHui = new ShenHui();
        $shenHui->getVerifyData();
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

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->getVerifyData();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'amount' => '1.01',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->getVerifyData();
    }

    /**
     * 測試支付設定回傳成功
     */
    public function testPaySuccess()
    {
        $options = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1090',
            'number' => '667001',
            'orderId' => '201705030000006225',
            'amount' => '1.01',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $requestData = $shenHui->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('Xh.online.pay', $requestData['method']);
        $this->assertEquals('1', $requestData['isshow']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals('WEIXIN', $requestData['banktype']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals($options['amount'], $requestData['paymoney']);
        $this->assertEquals('0d841b7f33d361672d8c479ef8236154', $requestData['sign']);
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

        $shenHui = new ShenHui();
        $shenHui->verifyOrderPayment([]);
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

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->verifyOrderPayment([]);
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
            'partner' => '667001',
            'ordernumber' => '201705030000006225',
            'orderstatus' => '1090',
            'paymoney' => '0.010',
            'sysnumber' => 'XH17050314541612291',
            'attach' => '',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->verifyOrderPayment([]);
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
            'partner' => '667001',
            'ordernumber' => '201705030000006225',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'XH17050314541612291',
            'attach' => '',
            'sign' => '123456789',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->verifyOrderPayment([]);
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
            'partner' => '667001',
            'ordernumber' => '201705030000006225',
            'orderstatus' => '2',
            'paymoney' => '0.010',
            'sysnumber' => 'XH17050314541612291',
            'attach' => '',
            'sign' => 'fd2f9d77d2a078ea7b2255d72c122dd5',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->verifyOrderPayment([]);
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
            'partner' => '667001',
            'ordernumber' => '201705030000006225',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'XH17050314541612291',
            'attach' => '',
            'sign' => 'c809b12e3a699356fdbe766ffb05cf29',
        ];

        $entry = ['id' => '201503220000000555'];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->verifyOrderPayment($entry);
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
            'partner' => '667001',
            'ordernumber' => '201705030000006225',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'XH17050314541612291',
            'attach' => '',
            'sign' => 'c809b12e3a699356fdbe766ffb05cf29',
        ];

        $entry = [
            'id' => '201705030000006225',
            'amount' => '15.00',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '667001',
            'ordernumber' => '201705030000006225',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'XH17050314541612291',
            'attach' => '',
            'sign' => 'c809b12e3a699356fdbe766ffb05cf29',
        ];

        $entry = [
            'id' => '201705030000006225',
            'amount' => '0.01',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->verifyOrderPayment($entry);

        $this->assertEquals('ok', $shenHui->getMsg());
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

        $shenHui = new ShenHui();
        $shenHui->paymentTracking();
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

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
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

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
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

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
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

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
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

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
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

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
            'status' => '1',
            'tradestate' => '9',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => 'd9c0c487c8407e57f4287083c5651dd0',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201611110000000101',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '13d039f8f08f2dc69b70c7c6f6a0e3d9',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('029268af05f5402685aa2be9121d1e70');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'amount' => '100.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '13d039f8f08f2dc69b70c7c6f6a0e3d9',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('029268af05f5402685aa2be9121d1e70');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '667001',
            'orderId' => '201705030000006225',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => '13d039f8f08f2dc69b70c7c6f6a0e3d9',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $shenHui = new ShenHui();
        $shenHui->setContainer($this->container);
        $shenHui->setClient($this->client);
        $shenHui->setResponse($response);
        $shenHui->setPrivateKey('029268af05f5402685aa2be9121d1e70');
        $shenHui->setOptions($options);
        $shenHui->paymentTracking();
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

        $shenHui = new ShenHui();
        $shenHui->getPaymentTrackingData();
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

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->getPaymentTrackingData();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $trackingData = $shenHui->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/trade/query', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);

        $this->assertEquals('1.0', $trackingData['form']['version']);
        $this->assertEquals('667001', $trackingData['form']['partner']);
        $this->assertEquals('201705030000006225', $trackingData['form']['ordernumber']);
        $this->assertEquals('', $trackingData['form']['sysnumber']);
        $this->assertEquals('580f62782b9f8105a4b81baaadd0a6d4', $trackingData['form']['sign']);
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

        $shenHui = new ShenHui();
        $shenHui->paymentTrackingVerify();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
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
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
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
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
            'status' => '0',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
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
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
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
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
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
            'number' => '667001',
            'orderId' => '201705030000006225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付金額錯誤
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
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => 'ec82586e6a5c2b28409ba73a43f6da3e',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '667001',
            'orderId' => '201705040000006242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
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
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => 'ec82586e6a5c2b28409ba73a43f6da3e',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '667001',
            'orderId' => '201705030000006225',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $params = [
            'version' => '1.0',
            'partner' => '',
            'ordernumber' => '201705030000006225',
            'sysnumber' => 'XH17050314541612291',
            'status' => '1',
            'tradestate' => '1',
            'paymoney' => '1.00',
            'banktype' => 'WEIXIN',
            'paytime' => '2017-05-03 02:54:09',
            'endtime' => '2017-05-03 02:54:55',
            'message' => '查询成功',
            'sign' => 'ec82586e6a5c2b28409ba73a43f6da3e',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '667001',
            'orderId' => '201705030000006225',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $shenHui = new ShenHui();
        $shenHui->setPrivateKey('test');
        $shenHui->setOptions($sourceData);
        $shenHui->paymentTrackingVerify();
    }
}
