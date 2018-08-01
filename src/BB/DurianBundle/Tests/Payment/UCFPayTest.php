<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\UCFPay;
use Buzz\Message\Response;

class UCFPayTest extends DurianTestCase
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

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

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
    }

    /**
     * 測試加密時沒有帶入privateKey的情況
     */
    public function testEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $uCFPay = new UCFPay();
        $uCFPay->getVerifyData();

    }

    /**
     * 測試加密時未指定支付參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = ['number' => ''];

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試加密時帶入不支援的銀行
     */
    public function testEncodeWithouttSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '999',
            'amount' => '55',
            'username' => 'hello',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試加密時取得支付參數失敗
     */
    public function testPayGetParametersFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '1',
            'amount' => '55',
            'username' => 'hello',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
        ];

        $res = [
            'code' => 12000,
            'info' => 'SUCCESS'
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試取得token時補上編碼設定
     */
    public function testGetTokenSetCharset()
    {
        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '1',
            'amount' => '55',
            'username' => 'hello',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
        ];

        $res = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS'
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;");

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試取得token時支付平台缺少對外url
     */
    public function testGetTokenWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '1',
            'amount' => '55',
            'username' => 'hello',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
        ];

        $res = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS'
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線異常
     */
    public function testPayPaymentGatewayConnectionError()
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

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '1',
            'amount' => '55',
            'username' => 'hello',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線失敗
     */
    public function testPayPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '1',
            'amount' => '55',
            'username' => 'hello',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
        ];

        $res = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS'
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試加密時支付平台回傳結果為空
     */
    public function testPayEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '1',
            'amount' => '55',
            'username' => 'hello',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試支付時對外返回結果錯誤
     */
    public function testPayConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '{"errorCode":99020,"info":"SIGN_FAILURE"}',
            180130
        );

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '1',
            'amount' => '55',
            'username' => 'hello',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
        ];

        $res = [
            'errorCode' => 99020,
            'info' => 'SIGN_FAILURE'
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $uCFPay->setOptions($sourceData);
        $uCFPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '1',
            'amount' => '55',
            'username' => 'hello',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $res = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS'
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');
        $uCFPay->setOptions($sourceData);
        $encodeData = $uCFPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchantId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['merchantNo']);
        $this->assertEquals('ICBC', $encodeData['bankId']);
        $this->assertEquals($sourceData['amount'] * 100, $encodeData['amount']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['noticeUrl']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['returnUrl']);
        $this->assertEquals('c52d9c002287d47a360c4d1c5fdb556e', $encodeData['sign']);
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

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340',
            'sign' => '77b96aeac62de14b18aea510a4a5002e'
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment([]);
    }

    /**
     * 測試支付驗證時缺少參數 sign
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340'
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment([]);
    }

    /**
     * 測試支付驗證時缺少參數 key
     */
    public function testVerifyWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340',
            'sign' => '77b960eac62de14b18aea510a4a5002e'
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340',
            'sign' => 'ff5d43d78bcaf23848f35d88bad8d890'
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果回傳訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('c41c1193bf73593ca9d71d08c0bc6ea0');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'I',
            'memo' => '',
            'tradeTime' => '20140922165340',
            'sign' => 'dbd506cdd0193cd9c808dd18e06eadba'
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment([]);
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

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('c41c1193bf73593ca9d71d08c0bc6ea0');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'F',
            'memo' => '',
            'tradeTime' => '20140922165340',
            'sign' => '1119b47f4376e1a59d1806ab1f71e096'
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證訂單號不正確
     */
    public function testVerifyOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('c41c1193bf73593ca9d71d08c0bc6ea0');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '20140922000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340',
            'sign' => 'd5326d6c649e6f35b2fa4536c04cae0b'
        ];

        $entry = ['id' => '201409220000000173'];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證 Amount 錯誤
     */
    public function testVerifyOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('c41c1193bf73593ca9d71d08c0bc6ea0');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '123',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340',
            'sign' => '04dde0b4f88382c4dc2b07337f727d85'
        ];

        $entry = [
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('c41c1193bf73593ca9d71d08c0bc6ea0');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340',
            'sign' => '77b960eac62de14b18aea510a4a5002e'
        ];

        $entry = [
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $uCFPay->setOptions($sourceData);
        $uCFPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $uCFPay->getMsg());
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

        $uCFPay = new UCFPay();
        $uCFPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入verifyUrl
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => 'M100000260',
            'orderId' => '201409220000000173',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $uCFPay = new UCFPay();
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->setOptions($sourceData);
        $uCFPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外返回結果錯誤
     */
    public function testTrackingReturnConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '{"errorCode":99020,"info":"SIGN_FAILURE"}',
            180123
        );

        $params = [
            'errorCode' => 99020,
            'info' => 'SIGN_FAILURE'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M100000260',
            'orderId' => '201409220000000173',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com'
        ];

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->setOptions($sourceData);
        $uCFPay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得支付參數失敗
     */
    public function testTrackingGetParametersFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $params = [
            'code' => 12000,
            'info' => 'SUCCESS'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M100000260',
            'orderId' => '201409220000000173',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com'
        ];

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->setOptions($sourceData);
        $uCFPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果未指定返回參數
     */
    public function testPaymentTrackingResultWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M100000260',
            'orderId' => '201409220000000173',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com'
        ];

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->setOptions($sourceData);
        $uCFPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $params = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS',
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'I',
            'memo' => '',
            'tradeTime' => '20140922165340'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M100000260',
            'orderId' => '201409220000000173',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com'
        ];

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->setOptions($sourceData);
        $uCFPay->paymentTracking();
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

        $params = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS',
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'F',
            'memo' => '',
            'tradeTime' => '20140922165340'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M100000260',
            'orderId' => '201409220000000173',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com'
        ];

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->setOptions($sourceData);
        $uCFPay->paymentTracking();
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

        $params = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS',
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M100000260',
            'orderId' => '201409220000000173',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'amount' => '1.234'
        ];

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->setOptions($sourceData);
        $uCFPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $params = [
            'result' => 'q10S1StlZRxu6bJ9bZzvw26Z3YdsXyXi',
            'code' => 12000,
            'info' => 'SUCCESS',
            'pay_system' => '12345',
            'hallid' => '6',
            'tradeNo' => '201409221653401031610000002049',
            'merchantId' => 'M100000260',
            'merchantNo' => '201409220000000173',
            'amount' => '1',
            'transCur' => '156',
            'status' => 'S',
            'memo' => '',
            'tradeTime' => '20140922165340'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M100000260',
            'orderId' => '201409220000000173',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.mapi.ucfpay.com',
            'amount' => '0.01'
        ];

        $uCFPay = new UCFPay();
        $uCFPay->setContainer($this->container);
        $uCFPay->setClient($this->client);
        $uCFPay->setResponse($response);
        $uCFPay->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $uCFPay->setOptions($sourceData);
        $uCFPay->paymentTracking();
    }
}
