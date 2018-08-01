<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DuoLaBao;
use Buzz\Message\Response;

class DuoLaBaoTest extends DurianTestCase
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

        $duolabao = new DuoLaBao();
        $duolabao->getVerifyData();
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

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('test');
        $duolabao->getVerifyData();
    }

    /**
     * 測試支付時未指定商家附加設定值
     */
    public function testPayWithNoMerchantExtraValueSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '123456',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201608120112180092',
            'paymentGatewayId' => '121',
            'merchant_extra' => [],
        ];

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201608120112180092',
            'paymentGatewayId' => '121',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_url' => '',
        ];

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回result
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201608120112180092',
            'paymentGatewayId' => '121',
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
        ];

        $result = '{"data":{"url":"https://order.duolabao.cn/active/c?state=2016081800000045API"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->getVerifyData();
    }

    /**
     * 測試支付時返回提交異常返回錯誤訊息
     */
    public function testPayReturnWithAccessKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'accessKeyError',
            180130
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201608120112180092',
            'paymentGatewayId' => '121',
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
        ];

        $result = '{"error":{"errorCode":"accessKeyError","errorMsg":"exception id : 739897DD96E44646A9C21768DDF019EF' .
            ',accessKey:123 is invialed"},"result":"fail"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201608120112180092',
            'paymentGatewayId' => '121',
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
        ];

        $result = '{"data":{"url":"https://order.duolabao.cn/active/c?state=2016081800000045API"},"result":"error"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201608120112180092',
            'paymentGatewayId' => '121',
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
        ];

        $result = '{"data":{},"result":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201608120112180092',
            'paymentGatewayId' => '121',
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
        ];

        $result = '{"data":{"url":"https://order.duolabao.cn/active/c?state=2016081800000045API"},"result":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $data = $duolabao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://order.duolabao.cn/active/c?state=2016081800000045API', $duolabao->getQrcode());
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

        $duolabao = new DuoLaBao();
        $duolabao->verifyOrderPayment([]);
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

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('test');
        $duolabao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時token驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'payment_id' => '121',
            'status' => 'SUCCESS',
            'requestNum' => '201608150000004473',
            'orderNum' => '10021014712437525719722',
            'completeTime' => '2016-08-15 14:49:17',
            'token' => '153739CC298E57ACAB97635C266CF66C43E2ACA9',
            'timestamp' => '1471243757912',
        ];

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->verifyOrderPayment([]);
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
            'payment_id' => '121',
            'status' => 'INIT',
            'requestNum' => '201608150000004473',
            'orderNum' => '10021014712437525719722',
            'completeTime' => '2016-08-15 14:49:17',
            'token' => '153739CC298E57ACAB97635C266CF66C43E2ACA9',
            'timestamp' => '1471243757912',
        ];

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('f60eac434d294a49b05b79b4f148daa62b7c7c6f');
        $duolabao->setOptions($options);
        $duolabao->verifyOrderPayment([]);
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
            'payment_id' => '121',
            'status' => 'SUCCESS',
            'requestNum' => '201608150000004473',
            'orderNum' => '10021014712437525719722',
            'completeTime' => '2016-08-15 14:49:17',
            'token' => '153739CC298E57ACAB97635C266CF66C43E2ACA9',
            'timestamp' => '1471243757912',
        ];

        $entry = ['id' => '201608150000004475'];

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('f60eac434d294a49b05b79b4f148daa62b7c7c6f');
        $duolabao->setOptions($options);
        $duolabao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'payment_id' => '121',
            'status' => 'SUCCESS',
            'requestNum' => '201608150000004473',
            'orderNum' => '10021014712437525719722',
            'completeTime' => '2016-08-15 14:49:17',
            'token' => '153739CC298E57ACAB97635C266CF66C43E2ACA9',
            'timestamp' => '1471243757912',
        ];

        $entry = [
            'id' => '201608150000004473',
            'amount' => '0.01',
        ];

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('f60eac434d294a49b05b79b4f148daa62b7c7c6f');
        $duolabao->setOptions($options);
        $duolabao->verifyOrderPayment($entry);

        $this->assertEquals('success', $duolabao->getMsg());
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

        $duolabao = new DuoLaBao();
        $duolabao->paymentTracking();
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

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('test');
        $duolabao->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定商家附加設定值
     */
    public function testTrackingWithNoMerchantExtraValueSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => [],
        ];

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
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

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $duolabao = new DuoLaBao();
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
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

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
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

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
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

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有Result的情況
     */
    public function testTrackingReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢驗證錯誤
     */
    public function testTrackingReturnWithTokenError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'tokenError',
            180123
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{"error":{"errorCode":"tokenError","errorMsg":"exception id : B4B151E360F04BEAB504AD8BCC7A6B7A,Ver' .
            'ify token failed, params message:1471243528, /v1/customer/order/payresult/10001114694284706322864/100012' .
            '14694289483074058/with/201608150000004473, 76DED1864BE75CEB1AF156A39873F36F8B7D177B"},"result":"fail"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢失敗
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{"data":{},"result":"error"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有Data的情況
     */
    public function testTrackingReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{"result":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{"data":{"bussinessType":"QRCODE_TRAD","completeTime":"2016-08-15 13:47:13","customerName":"大台商' .
            '贸","orderAmount":"0.01","orderNum":"10021014712400221968962","payRecordList":[{"amount":"0.01","bankReq' .
            'uestNum":"10031114712400222913953","payWay":"WFTWX"}],"refundTime":"","requestNum":"201608150000004470",' .
            '"source":"API","status":"INIT","type":"SALES"},"result":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
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

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{"data":{"bussinessType":"QRCODE_TRAD","completeTime":"2016-08-15 13:47:13","customerName":"大台商' .
            '贸","orderAmount":"0.01","orderNum":"10021014712400221968962","payRecordList":[{"amount":"0.01","bankReq' .
            'uestNum":"10031114712400222913953","payWay":"WFTWX"}],"refundTime":"","requestNum":"201608150000004470",' .
            '"source":"API","status":"CANCEL","type":"SALES"},"result":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('test');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
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

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608120112180092',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{"data":{"bussinessType":"QRCODE_TRAD","completeTime":"2016-08-15 13:47:13","customerName":"大台商' .
            '贸","orderAmount":"0.01","orderNum":"10021014712400221968962","payRecordList":[{"amount":"0.01","bankReq' .
            'uestNum":"10031114712400222913953","payWay":"WFTWX"}],"refundTime":"","requestNum":"201608150000004470",' .
            '"source":"API","status":"SUCCESS","type":"SALES"},"result":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
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

        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608150000004470',
            'amount' => '0.02',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{"data":{"bussinessType":"QRCODE_TRAD","completeTime":"2016-08-15 13:47:13","customerName":"大台商' .
            '贸","orderAmount":"0.01","orderNum":"10021014712400221968962","payRecordList":[{"amount":"0.01","bankReq' .
            'uestNum":"10031114712400222913953","payWay":"WFTWX"}],"refundTime":"","requestNum":"201608150000004470",' .
            '"source":"API","status":"SUCCESS","type":"SALES"},"result":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $merchantExtra = [
            'accessKey' => 'qwertyuiop123456789',
            'ownerNum' => '123456789123456789',
        ];

        $options = [
            'number' => '123456',
            'orderId' => '201608150000004470',
            'amount' => '0.01',
            'merchant_extra' => $merchantExtra,
            'orderCreateDate' => '2016-08-23 15:45:55',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.duolabao',
        ];

        $result = '{"data":{"bussinessType":"QRCODE_TRAD","completeTime":"2016-08-15 13:47:13","customerName":"大台商' .
            '贸","orderAmount":"0.01","orderNum":"10021014712400221968962","payRecordList":[{"amount":"0.01","bankReq' .
            'uestNum":"10031114712400222913953","payWay":"WFTWX"}],"refundTime":"","requestNum":"201608150000004470",' .
            '"source":"API","status":"SUCCESS","type":"SALES"},"result":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $duolabao = new DuoLaBao();
        $duolabao->setContainer($this->container);
        $duolabao->setClient($this->client);
        $duolabao->setResponse($response);
        $duolabao->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $duolabao->setOptions($options);
        $duolabao->paymentTracking();
    }
}
