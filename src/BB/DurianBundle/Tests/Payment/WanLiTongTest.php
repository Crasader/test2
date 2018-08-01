<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\WanLiTong;
use Buzz\Message\Response;

class WanLiTongTest extends DurianTestCase
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

        $wanLiTong = new WanLiTong();
        $wanLiTong->getVerifyData();
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

        $sourceData = ['number' => ''];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '796',
            'paymentVendorId' => '20',
            'amount' => '2.00',
            'orderId' => '201611290000000474',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '796',
            'paymentVendorId' => '1',
            'amount' => '2.00',
            'orderId' => '201611290000000474',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $encodeData = $wanLiTong->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['userid']);
        $this->assertEquals('1002', $encodeData['bankid']);
        $this->assertSame('2.00', $encodeData['money']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['url']);
        $this->assertEquals('eb79aca1d043fba19821ce0e4e25077e', $encodeData['sign']);
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

        $wanLiTong = new WanLiTong();
        $wanLiTong->verifyOrderPayment([]);
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

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '796',
            'orderid' => '201611290000000474',
            'money' => '2',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign2
     */
    public function testReturnWithoutSign2()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '796',
            'orderid' => '201611290000000474',
            'money' => '2',
            'sign' => 'ffead463c000b6a4c695166142845747',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '796',
            'orderid' => '201611290000000474',
            'money' => '2',
            'sign' => '0dbd1f331672800100fb0092418614ff',
            'sign2' => '0dbd1f331672800100fb0092418614ff',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign2簽名驗證錯誤
     */
    public function testReturnSignature2VerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '796',
            'orderid' => '201611290000000474',
            'money' => '2',
            'sign' => 'ffead463c000b6a4c695166142845747',
            'sign2' => '0dbd1f331672800100fb0092418614ff',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'returncode' => '0',
            'userid' => '796',
            'orderid' => '201611290000000474',
            'money' => '2',
            'sign' => '643ba25f95e32a4e2de5a2b365a25e1d',
            'sign2' => '070b0638663cbf8f62e43dc87dc944b4',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '796',
            'orderid' => '201611290000000474',
            'money' => '2',
            'sign' => 'ffead463c000b6a4c695166142845747',
            'sign2' => 'dbcf45c018ec567782a57d63862476b4',
        ];

        $entry = ['id' => '201611150000000241'];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '796',
            'orderid' => '201611290000000474',
            'money' => '2',
            'sign' => 'ffead463c000b6a4c695166142845747',
            'sign2' => 'dbcf45c018ec567782a57d63862476b4',
        ];

        $entry = [
            'id' => '201611290000000474',
            'amount' => '0.01',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'returncode' => '1',
            'userid' => '796',
            'orderid' => '201611290000000474',
            'money' => '2',
            'sign' => 'ffead463c000b6a4c695166142845747',
            'sign2' => 'dbcf45c018ec567782a57d63862476b4',
        ];

        $entry = [
            'id' => '201611290000000474',
            'amount' => '2',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->verifyOrderPayment($entry);

        $this->assertEquals('ok', $wanLiTong->getMsg());
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

        $wanLiTong = new WanLiTong();
        $wanLiTong->paymentTracking();
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

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->paymentTracking();
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

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為商戶不存在
     */
    public function testTrackingReturnCodeMerchantNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant is not exist',
            180086
        );

        $result = 'returncode=2&orderid=201612060000000607&paymoney=0&sign=4503d3b82e34859fe2096b3fab94b62d';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為系統錯誤
     */
    public function testTrackingReturnCodeSystemError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'System error, please try again later or contact customer service',
            180076
        );

        $result = 'returncode=5&orderid=201612060000000607&paymoney=0&sign=25c9158ea943d40b8f4ab899c0b72674';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果Sign為空
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'returncode=1&orderid=201612060000000607&paymoney=0.01';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = 'returncode=1&orderid=201612060000000607&paymoney=0.01&sign=2491ff2f8b4fabdc6c7ab1efe35c0120';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單未支付
     */
    public function testTrackingReturnCodeUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = 'returncode=0&orderid=201612060000000607&paymoney=0.01&sign=a043eac0192cdbdc2f424208ada85133';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單不存在
     */
    public function testTrackingReturnCodeOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = 'returncode=3&orderid=201612060000000607&paymoney=0&sign=7c974e32c1d615d307a7673ab420ed81';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為簽名驗證失敗
     */
    public function testTrackingReturnCodeMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $result = 'returncode=4&orderid=201612060000000607&paymoney=0&sign=9f53bf68502831e57dcb44be8b0b0944';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果code異常
     */
    public function testTrackingReturnCodeFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = 'returncode=100&orderid=201612060000000607&paymoney=0&sign=74e21f51b97e9c5153d0c76b567e0f8c';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = 'returncode=1&orderid=201612060000000607&paymoney=0.01&sign=360e29486bf622b8bb1125a5ae23b738';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000606',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = 'returncode=1&orderid=201612060000000607&paymoney=0.01&sign=360e29486bf622b8bb1125a5ae23b738';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = 'returncode=1&orderid=201612060000000607&paymoney=0.01&sign=360e29486bf622b8bb1125a5ae23b738';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '796',
            'orderId' => '201612060000000607',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.wx.aishundianzi.com',
        ];

        $wanLiTong = new WanLiTong();
        $wanLiTong->setContainer($this->container);
        $wanLiTong->setClient($this->client);
        $wanLiTong->setResponse($response);
        $wanLiTong->setPrivateKey('test');
        $wanLiTong->setOptions($sourceData);
        $wanLiTong->paymentTracking();
    }
}
