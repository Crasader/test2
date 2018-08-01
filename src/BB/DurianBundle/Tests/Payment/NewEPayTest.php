<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewEPay;
use Buzz\Message\Response;

class NewEPayTest extends DurianTestCase
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

        $newEPay = new NewEPay();
        $newEPay->getVerifyData();
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

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->getVerifyData();
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
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '9453',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入 verify_url 的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => '',
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回 msgData
     */
    public function testPayReturnWithoutMsgData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回 respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $msgData = [];
        $encodedMsgData = base64_encode(json_encode($msgData));

        $result = ['msgData' => $encodedMsgData];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試支付時返回 respCode 不為 0000 且有返回 respMsg
     */
    public function testPayReturnWithFailedRespCodeAndRespMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户【10016170503111】，金额:1000支付方式jdpay,路由规则未通过',
            180130
        );

        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $msgData = [
            'respCode' => '2006',
            'respMsg' => '商户【10016170503111】，金额:1000支付方式jdpay,路由规则未通过',
        ];

        $result = ['msgData' => base64_encode(json_encode($msgData))];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試支付時返回 respCode 不為 0000 但沒有返回 respMsg
     */
    public function testPayReturnWithFailedRespCodeButNoRespMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $msgData = ['respCode' => '2006'];

        $result = ['msgData' => base64_encode(json_encode($msgData))];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試支付時沒返回 signData
     */
    public function testPayReturnWithoutSignData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $msgData = ['respCode' => '0000'];

        $result = ['msgData' => base64_encode(json_encode($msgData))];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試支付時返回驗簽失敗
     */
    public function testPayReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $msgData = ['respCode' => '0000'];

        $result = [
            'msgData' => base64_encode(json_encode($msgData)),
            'signData' => '948794kuan',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回 qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $msgData = ['respCode' => '0000'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1104',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $postUrl = 'https://trade.newepay.online/h5pay/wap/V2.0/qqpayH5/2100620180716121819081819752';

        $msgData = [
            'respCode' => '0000',
            'qrCode' => $postUrl,
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $requestData = $newEPay->getVerifyData();

        $this->assertEquals('GET', $newEPay->getPayMethod());
        $this->assertEmpty($requestData['params']);
        $this->assertEquals($postUrl, $requestData['post_url']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'orderId' => '201703220000001397',
            'number' => '80060384',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-06-14 16:47:17',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.trade.newepay.online',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $msgData = [
            'respCode' => '0000',
            'qrCode' => 'https://qr.alipay.com/bax00575zssf17mamgeq2035',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $requestData = $newEPay->getVerifyData();

        $this->assertSame([], $requestData);
        $this->assertSame($msgData['qrCode'], $newEPay->getQrcode());
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

        $newEPay = new NewEPay();
        $newEPay->verifyOrderPayment([]);
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

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回 signData
     */
    public function testReturnWithoutSignData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = ['msgData' => ''];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'msgData' => '',
            'signData' => '',
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒返回 respCode
     */
    public function testReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $msgData = [
            'totalAmount' => '',
            'orderId' => '',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $options = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒返回訂單號
     */
    public function testReturnWithoutOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $msgData = [
            'respCode' => '0000',
            'totalAmount' => '',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $options = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒返回金額
     */
    public function testReturnWithoutTotalAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $msgData = [
            'respCode' => '0000',
            'orderId' => '201707240000002278',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $options = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $msgData = [
            'respCode' => '0001',
            'totalAmount' => '',
            'orderId' => '',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $options = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $msgData = [
            'respCode' => '0000',
            'totalAmount' => '',
            'orderId' => '',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $options = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $entry = ['id' => '201707240000002278'];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $msgData = [
            'respCode' => '0000',
            'totalAmount' => '',
            'orderId' => '201707240000002278',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $options = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $entry = [
            'id' => '201707240000002278',
            'amount' => '1.01',
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回
     */
    public function testReturn()
    {
        $msgData = [
            'respCode' => '0000',
            'totalAmount' => '101.0',
            'orderId' => '201707240000002278',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $options = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $entry = [
            'id' => '201707240000002278',
            'amount' => '1.01',
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->verifyOrderPayment($entry);

        $this->assertSame('000000', $newEPay->getMsg());
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

        $newEPay = new NewEPay();
        $newEPay->paymentTracking();
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

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未帶入商號
     */
    public function testTrackingWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['orderId' => '201707240000002278'];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入 verify_url 的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '10016170503111',
            'orderId' => '201707240000002278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未返回參數
     */
    public function testTrackingReturnNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $response = new Response();
        $response->setContent(json_encode([]));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未返回 signData
     */
    public function testTrackingReturnWithoutSignData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = ['msgData' => ''];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢驗簽失敗
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'msgData' => '',
            'signData' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未返回 respCode
     */
    public function testTrackingReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $msgData = [];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $msgData = ['respCode' => '2001'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $msgData = ['respCode' => '0010'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $msgData = ['respCode' => '9487'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未返回訂單號
     */
    public function testTrackingReturnWithoutOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $msgData = ['respCode' => '0000'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未返回金額
     */
    public function testTrackingReturnWithoutAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $msgData = [
            'respCode' => '0000',
            'orderId' => '9487',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單號錯誤
     */
    public function testTrackingReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $msgData = [
            'respCode' => '0000',
            'orderId' => '9487',
            'totalAmount' => '101.0',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'amount' => '1.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $msgData = [
            'respCode' => '0000',
            'orderId' => '201703220000001397',
            'totalAmount' => '100',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'amount' => '1.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testTrackingSuccess()
    {
        $msgData = [
            'respCode' => '0000',
            'orderId' => '201703220000001397',
            'totalAmount' => '101.0',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $result = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '80060384',
            'orderId' => '201703220000001397',
            'amount' => '1.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setContainer($this->container);
        $newEPay->setClient($this->client);
        $newEPay->setResponse($response);
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newEPay = new NewEPay();
        $newEPay->getPaymentTrackingData();
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

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未帶入商號
     */
    public function testGetPaymentTrackingDataWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['orderId' => '201707240000002278'];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '025000000000039',
            'orderId' => '201707240000002278',
            'verify_url' => '',
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '80060384',
            'orderId' => '201707240000002278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.newepay.online',
        ];

        $newEPay = new NewEPay();
        $newEPay->setOptions($options);
        $newEPay->setPrivateKey('test');
        $trackingData = $newEPay->getPaymentTrackingData();

        $msgData = [
            'orderId' => '201707240000002278',
            'extend1' => '',
            'extend2' => '',
            'extend3' => '',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5(json_encode($msgData) . 'test');

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/pay/v1.0/payQuery', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($options['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals($options['number'], $trackingData['form']['partner']);
        $this->assertEquals('md5', $trackingData['form']['encryptType']);
        $this->assertEquals($encodedMsgData, $trackingData['form']['msgData']);
        $this->assertEquals($signData, $trackingData['form']['signData']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少密鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newEPay = new NewEPay();
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = ['content' => json_encode([])];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳缺少 signData
     */
    public function testPaymentTrackingVerifyWithoutSignData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = ['msgData' => ''];
        $options = ['content' => json_encode($content)];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名錯誤
     */
    public function testTrackingVerifyReturnWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $encodedMsgData = base64_encode(json_encode([]));

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => '',
        ];

        $options = ['content' => json_encode($content)];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果回傳參數缺少 respCode
     */
    public function testTrackingVerifyReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $encodedMsgData = base64_encode(json_encode([]));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = ['content' => json_encode($content)];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果訂單不存在
     */
    public function testTrackingVerifyReturnWithOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $msgData = ['respCode' => '2001'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = ['content' => json_encode($content)];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果訂單處理中
     */
    public function testTrackingVerifyReturnWithOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $msgData = ['respCode' => '0010'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = ['content' => json_encode($content)];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果訂單支付失敗
     */
    public function testTrackingVerifyReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $msgData = ['respCode' => '9487'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = ['content' => json_encode($content)];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果回傳參數缺少訂單號
     */
    public function testTrackingVerifyReturnWithoutOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $msgData = ['respCode' => '0000'];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = ['content' => json_encode($content)];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果回傳參數缺少金額
     */
    public function testTrackingVerifyReturnWithoutAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $msgData = [
            'respCode' => '0000',
            'orderId' => '201707240000002278',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = ['content' => json_encode($content)];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢返回訂單號錯誤
     */
    public function testTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $msgData = [
            'respCode' => '0000',
            'orderId' => '201707240000002277',
            'totalAmount' => '101.0',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = [
            'orderId' => '201707240000002278',
            'content' => json_encode($content),
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢返回金額錯誤
     */
    public function testTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $msgData = [
            'respCode' => '0000',
            'orderId' => '201707240000002278',
            'totalAmount' => '101.0',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = [
            'orderId' => '201707240000002278',
            'amount' => '1.02',
            'content' => json_encode($content),
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $msgData = [
            'respCode' => '0000',
            'orderId' => '201707240000002278',
            'totalAmount' => '101.0',
        ];

        $encodedMsgData = base64_encode(json_encode($msgData));
        $signData = md5($encodedMsgData . 'test');

        $content = [
            'msgData' => $encodedMsgData,
            'signData' => $signData,
        ];

        $options = [
            'orderId' => '201707240000002278',
            'amount' => '1.01',
            'content' => json_encode($content),
        ];

        $newEPay = new NewEPay();
        $newEPay->setPrivateKey('test');
        $newEPay->setOptions($options);
        $newEPay->paymentTrackingVerify();
    }
}
