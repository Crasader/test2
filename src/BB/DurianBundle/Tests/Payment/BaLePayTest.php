<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaLePay;
use Buzz\Message\Response;

class BaLePayTest extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $baLePay = new BaLePay();
        $baLePay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.caimao9.com',
        ];

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setOptions($sourceData);
        $baLePay->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setOptions($sourceData);
        $baLePay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.caimao9.com',
        ];

        $result = [
            'message' => 'test',
            'time' => '1505978683179',
            'orderId' => '201709210000001112',
            'qrCode' => 'http://olewx.goodluckchina.net/op/toOauth.html?model=00&' .
                'custNo=gl00013667&first=y&orderId=3b3eab4a98724cc2902ef7bfa5d743d8',
            'sign' => '442AA15BBA25C92D1719D22785C43D4D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setContainer($this->container);
        $baLePay->setClient($this->client);
        $baLePay->setResponse($response);
        $baLePay->setOptions($sourceData);
        $baLePay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少message
     */
    public function testPayReturnWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.caimao9.com',
        ];

        $result = [
            'status' => -1,
            'time' => '1505978683179',
            'orderId' => '201709210000001112',
            'qrCode' => 'http://olewx.goodluckchina.net/op/toOauth.html?model=00&' .
                'custNo=gl00013667&first=y&orderId=3b3eab4a98724cc2902ef7bfa5d743d8',
            'sign' => '442AA15BBA25C92D1719D22785C43D4D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setContainer($this->container);
        $baLePay->setClient($this->client);
        $baLePay->setResponse($response);
        $baLePay->setOptions($sourceData);
        $baLePay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名错误',
            180130
        );

        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.caimao9.com',
        ];

        $result = [
            'message' => '签名错误',
            'status' => -1,
            'time' => '1505978683179',
            'orderId' => '201709210000001112',
            'qrCode' => 'http://olewx.goodluckchina.net/op/toOauth.html?model=00&' .
                'custNo=gl00013667&first=y&orderId=3b3eab4a98724cc2902ef7bfa5d743d8',
            'sign' => '442AA15BBA25C92D1719D22785C43D4D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setContainer($this->container);
        $baLePay->setClient($this->client);
        $baLePay->setResponse($response);
        $baLePay->setOptions($sourceData);
        $baLePay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少qrcode
     */
    public function testPayReturnWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.caimao9.com',
        ];

        $result = [
            'message' => '',
            'status' => 0,
            'time' => '1505978683179',
            'orderId' => '201709210000001112',
            'sign' => '442AA15BBA25C92D1719D22785C43D4D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setContainer($this->container);
        $baLePay->setClient($this->client);
        $baLePay->setResponse($response);
        $baLePay->setOptions($sourceData);
        $baLePay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.caimao9.com',
        ];

        $qrCode = 'http://olewx.goodluckchina.net/op/toOauth.html?model=00&' .
            'custNo=gl00013667&first=y&orderId=3b3eab4a98724cc2902ef7bfa5d743d8';
        $result = [
            'message' => '',
            'status' => 0,
            'time' => '1505978683179',
            'orderId' => '201709210000001112',
            'qrCode' => $qrCode,
            'sign' => '442AA15BBA25C92D1719D22785C43D4D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setContainer($this->container);
        $baLePay->setClient($this->client);
        $baLePay->setResponse($response);
        $baLePay->setOptions($sourceData);
        $verifyData = $baLePay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame($qrCode, $baLePay->getQrcode());
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

        $baLePay = new BaLePay();
        $baLePay->verifyOrderPayment([]);
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

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->verifyOrderPayment([]);
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
            'orderId' => '201709210000001112',
            'payTime' => '1505978683179',
            'payStatus' => '1',
        ];

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setOptions($sourceData);
        $baLePay->verifyOrderPayment([]);
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
            'orderId' => '201709210000001112',
            'payTime' => '1505978683179',
            'payStatus' => '1',
            'sign' => '0426CE0641D844663BE75933E213A939',
        ];

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setOptions($sourceData);
        $baLePay->verifyOrderPayment([]);
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
            'orderId' => '201709210000001112',
            'payTime' => '1505978683179',
            'payStatus' => '2',
            'sign' => '7452FB0679DC80F1DDE422300BBC1776',
        ];

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setOptions($sourceData);
        $baLePay->verifyOrderPayment([]);
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
            'orderId' => '201709210000001112',
            'payTime' => '1505978683179',
            'payStatus' => '1',
            'sign' => '309E3D2F05B6B8BF4D51F362D9B8AF22',
        ];

        $entry = ['id' => '201709210000001113'];

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setOptions($sourceData);
        $baLePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderId' => '201709210000001112',
            'payTime' => '1505978683179',
            'payStatus' => '1',
            'sign' => '309E3D2F05B6B8BF4D51F362D9B8AF22',
        ];

        $entry = ['id' => '201709210000001112'];

        $baLePay = new BaLePay();
        $baLePay->setPrivateKey('1234');
        $baLePay->setOptions($sourceData);
        $baLePay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $baLePay->getMsg());
    }
}