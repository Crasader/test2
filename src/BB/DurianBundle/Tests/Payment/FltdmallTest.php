<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Fltdmall;
use Buzz\Message\Response;

class FltdmallTest extends DurianTestCase
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

        $fltdmall = new Fltdmall();
        $fltdmall->getVerifyData();
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

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->getVerifyData();
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
            'orderId' => '2016070250000000001',
            'paymentVendorId' => '100',
            'number' => '123456',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '2016-07-25 14:04:56',
            'username' => 'php1test',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'orderId' => '2016070250000000001',
            'paymentVendorId' => '1',
            'number' => '123456',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '2016-07-25 14:04:56',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $data = $fltdmall->getVerifyData();

        $remark = sprintf(
            '%s_%s',
            $options['merchantId'],
            $options['domain']
        );

        $this->assertEquals($options['orderId'], $data['orderno']);
        $this->assertEquals('20', $data['paytype']);
        $this->assertEquals('001', $data['paycode']);
        $this->assertEquals($options['number'], $data['usercode']);
        $this->assertEquals($options['amount'], $data['value']);
        $this->assertEquals($options['notify_url'], $data['notifyurl']);
        $this->assertEquals($options['notify_url'], $data['returnurl']);
        $this->assertEquals($remark, $data['remark']);
        $this->assertEquals('20160725140456', $data['datetime']);
        $this->assertEquals($options['username'], $data['goodsname']);
        $this->assertEquals('526c86b4da45a8e153f2db0182fd4e7d', $data['sign']);
    }

    /**
     * 測試支付，帶入支付寶二維
     */
    public function testPayWithAlipayQRCode()
    {
        // 支付寶二維 paymentVendorId 為 1092
        $options = [
            'orderId' => '2016070250000000001',
            'paymentVendorId' => '1092',
            'number' => '123456',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '2016-07-25 14:04:56',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $data = $fltdmall->getVerifyData();

        $this->assertEquals('22', $data['paytype']);
        $this->assertEmpty($data['paycode']);
        $this->assertEquals('db2ab6c16ffba08eb0c1b54760ac882c', $data['sign']);
    }

    /**
     * 測試支付，帶入微信二維
     */
    public function testPayWithWeiXinQRCode()
    {
        // 微信支付二維 paymentVendorId 為 1090
        $options = [
            'orderId' => '2016070250000000001',
            'paymentVendorId' => '1090',
            'number' => '123456',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '2016-07-25 14:04:56',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $data = $fltdmall->getVerifyData();

        $this->assertEquals('30', $data['paytype']);
        $this->assertEmpty($data['paycode']);
        $this->assertEquals('c0c3a90b44ec72713656335ffc133cc8', $data['sign']);
    }

    /**
     * 測試支付，帶入QQ二維
     */
    public function testPayWithQQQRCode()
    {
        // QQ二維 paymentVendorId 為 1103
        $options = [
            'orderId' => '2016070250000000001',
            'paymentVendorId' => '1103',
            'number' => '123456',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '2016-07-25 14:04:56',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $data = $fltdmall->getVerifyData();

        $this->assertEquals('23', $data['paytype']);
        $this->assertEmpty($data['paycode']);
        $this->assertEquals('0e062dfbddba4c61796ed8b8e03a80f5', $data['sign']);
    }

    /**
     * 測試支付，帶入QQ手機支付
     */
    public function testPayWithQQWap()
    {
        // QQ手機支付 paymentVendorId 為 1104
        $options = [
            'orderId' => '2016070250000000001',
            'paymentVendorId' => '1104',
            'number' => '123456',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '2016-07-25 14:04:56',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $data = $fltdmall->getVerifyData();

        $this->assertEquals('33', $data['paytype']);
        $this->assertEmpty($data['paycode']);
        $this->assertEquals('dbc27f305bd8325cce9e1a9eb04cd658', $data['sign']);
    }

    /**
     * 測試支付，帶入京東錢包二維
     */
    public function testPayWithJDQRCode()
    {
        // 京東錢包二維 paymentVendorId 為 1107
        $options = [
            'orderId' => '2016070250000000001',
            'paymentVendorId' => '1107',
            'number' => '123456',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'orderCreateDate' => '2016-07-25 14:04:56',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $data = $fltdmall->getVerifyData();

        $this->assertEquals('31', $data['paytype']);
        $this->assertEmpty($data['paycode']);
        $this->assertEquals('3fe5319266ef8e325fc9ea7caab415b8', $data['sign']);
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

        $fltdmall = new Fltdmall();
        $fltdmall->verifyOrderPayment([]);
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

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->verifyOrderPayment([]);
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
            'result' => '1',
            'pay_message' => '',
            'usercode' => 'A134960',
            'plat_billid' => 'X201607251229492125168871',
            'orderno' => '201607250000044752',
            'paytype' => '20',
            'value' => '100',
            'remark' => '35660_6',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->verifyOrderPayment([]);
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
            'result' => '1',
            'pay_message' => '',
            'usercode' => 'A134960',
            'plat_billid' => 'X201607251229492125168871',
            'orderno' => '201607250000044752',
            'paytype' => '20',
            'value' => '100',
            'remark' => '35660_6',
            'sign' => 'fecd699d45d5814a0a18cdf4c5d9398c',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->verifyOrderPayment([]);
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
            'result' => '0',
            'pay_message' => '',
            'usercode' => 'A134960',
            'plat_billid' => 'X201607251229492125168871',
            'orderno' => '201607250000044752',
            'paytype' => '20',
            'value' => '100',
            'remark' => '35660_6',
            'sign' => '8d11ee5a79139b4fceafe4e3319f972e',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->verifyOrderPayment([]);
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
            'result' => '1',
            'pay_message' => '',
            'usercode' => 'A134960',
            'plat_billid' => 'X201607251229492125168871',
            'orderno' => '201607250000044752',
            'paytype' => '20',
            'value' => '100',
            'remark' => '35660_6',
            'sign' => '478986c36d7400e23a3a546b998c5387',
        ];

        $entry = ['id' => '201509140000002475'];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->verifyOrderPayment($entry);
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
            'result' => '1',
            'pay_message' => '',
            'usercode' => 'A134960',
            'plat_billid' => 'X201607251229492125168871',
            'orderno' => '201607250000044752',
            'paytype' => '20',
            'value' => '100',
            'remark' => '35660_6',
            'sign' => '478986c36d7400e23a3a546b998c5387',
        ];

        $entry = [
            'id' => '201607250000044752',
            'amount' => '15.00',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'result' => '1',
            'pay_message' => '',
            'usercode' => 'A134960',
            'plat_billid' => 'X201607251229492125168871',
            'orderno' => '201607250000044752',
            'paytype' => '20',
            'value' => '100',
            'remark' => '35660_6',
            'sign' => '478986c36d7400e23a3a546b998c5387',
        ];

        $entry = [
            'id' => '201607250000044752',
            'amount' => '100',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->verifyOrderPayment($entry);

        $this->assertEquals('ok', $fltdmall->getMsg());
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

        $fltdmall = new Fltdmall();
        $fltdmall->paymentTracking();
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

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->paymentTracking();
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
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
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
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
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
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setResponse($response);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
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
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setResponse($response);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
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
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = "orderno=201607250000044749|plat_billid=X201607251037391739833603|paytype=30|\n" .
            'result=1|value=0.01|pay_message=|sign=1cacce9d46dcf688e97270d7535178f4';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setResponse($response);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
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
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = "usercode=A134960|orderno=201607250000044749|plat_billid=X201607251037391739833603|paytype=30|\n" .
            'result=1|value=0.01|pay_message=';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setResponse($response);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
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
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = "usercode=A134960|orderno=201607250000044749|plat_billid=X201607251037391739833603|paytype=30|\n" .
            'result=1|value=0.01|pay_message=|sign=1cacce9d46dcf688e97270d7535178f4';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setResponse($response);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
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
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = "usercode=A134960|orderno=201607250000044749|plat_billid=X201607251037391739833603|paytype=30|\n" .
            'result=0|value=0.01|pay_message=|sign=026973928d3b7738aa64f6837eeae2f5';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setResponse($response);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = "usercode=A134960|orderno=201607250000044749|plat_billid=X201607251037391739833603|paytype=30|\n" .
            'result=1|value=0.01|pay_message=|sign=eabb84017359ab987ec5873820672381';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setResponse($response);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => 'A134960',
            'orderId' => '201607250000044749',
            'orderCreateDate' => '2016-07-25 10:36:24',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = "usercode=A134960|orderno=201607250000044749|plat_billid=X201607251037391739833603|paytype=30|\n" .
            'result=1|value=0.01|pay_message=|sign=eabb84017359ab987ec5873820672381';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $fltdmall = new Fltdmall();
        $fltdmall->setContainer($this->container);
        $fltdmall->setClient($this->client);
        $fltdmall->setResponse($response);
        $fltdmall->setPrivateKey('test');
        $fltdmall->setOptions($options);
        $fltdmall->paymentTracking();
    }
}
