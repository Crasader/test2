<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\GPay;
use Buzz\Message\Response;

class GPayTest extends DurianTestCase
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

        $gPay = new GPay();
        $gPay->getVerifyData();
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

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->getVerifyData();
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
            'number' => '002440373720003',
            'amount' => '100',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://pay.return/',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '002440373720003',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => '',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'number' => '002440373720003',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=cLys8Hm","merchno":"002440373720003","message":"下单成功",' .
            '"refno":"02170322000081213163","traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易金额必须大于0',
            180130
        );

        $options = [
            'number' => '002440373720003',
            'amount' => '0.0001',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"002440373720003","message":"交易金额必须大于0","refno":"02170324000081299151",' .
            '"respCode":"0001","traceno":"201703240000001427"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'number' => '002440373720003',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"002440373720003","message":"下单成功","refno":"02170322000081213163","respCode":"00",' .
            '"traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testWxPay()
    {
        $options = [
            'number' => '002440373720003',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=cLys8Hm","merchno":"002440373720003","message":"下单成功",' .
            '"refno":"02170322000081213163","respCode":"00","traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $data = $gPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=cLys8Hm', $gPay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '002440373720003',
            'amount' => '1',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'notify_url' => 'http://pay.return/',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $encodeData = $gPay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['merchno']);
        $this->assertEquals($options['amount'], $encodeData['amount']);
        $this->assertEquals($options['orderId'], $encodeData['traceno']);
        $this->assertEquals('2', $encodeData['channel']);
        $this->assertEquals('3002', $encodeData['bankCode']);
        $this->assertEquals('2', $encodeData['settleType']);
        $this->assertEquals('ea56ec1549d5f3894a37d828107d2b55', $encodeData['signature']);
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

        $gPay = new GPay();
        $gPay->verifyOrderPayment([]);
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

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'amount' => '1.00',
            'merchno' => '002440373720003',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'channelOrderno' => 'null',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->verifyOrderPayment([]);
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
            'amount' => '1.00',
            'merchno' => '002440373720003',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => '9453',
            'channelOrderno' => 'null',
            'vendor_id' => '1',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->verifyOrderPayment([]);
    }

    /**
     * 測試網銀返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'amount' => '1.00',
            'merchno' => '002440373720003',
            'status' => '3',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => 'FB987B6BAA9351BB0146A71AA6B71136',
            'channelOrderno' => 'null',
        ];

        $entry = [
            'id' => '201703220000001397',
            'amount' => '1.00',
            'payment_method_id' => '1',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->verifyOrderPayment($entry);
    }

    /**
     * 測試二維返回時支付失敗
     */
    public function testReturnWithWxPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'amount' => '0.01',
            'channelOrderno' => '52170322001730918503',
            'channelTraceno' => '100075161770',
            'merchName' => 'ESBALL',
            'merchno' => '002440373720003',
            'orderno' => '02170322000081213163',
            'payType' => '2',
            'signature' => '7E080EA8265B776F3338AB063F315DDC',
            'status' => '2',
            'traceno' => '201703220000001407',
            'transDate' => '2017-03-22',
            'transTime' => '14:33:49',
        ];

        $entry = [
            'id' => '201703220000001407',
            'amount' => '0.01',
            'payment_method_id' => '8',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->verifyOrderPayment($entry);
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
            'amount' => '1.00',
            'merchno' => '002440373720003',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => 'DD1BCA817E15E4338CFFEDF8BC14C156',
            'channelOrderno' => 'null',
            'vendor_id' => '1',
        ];

        $entry = [
            'id' => '9453',
            'payment_method_id' => '1',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'amount' => '1.00',
            'merchno' => '002440373720003',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => 'DD1BCA817E15E4338CFFEDF8BC14C156',
            'channelOrderno' => 'null',
            'vendor_id' => '1',
        ];

        $entry = [
            'id' => '201703220000001397',
            'amount' => '0.1',
            'payment_method_id' => '1',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'amount' => '1.00',
            'merchno' => '002440373720003',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => 'DD1BCA817E15E4338CFFEDF8BC14C156',
            'channelOrderno' => 'null',
            'vendor_id' => '1',
        ];

        $entry = [
            'id' => '201703220000001397',
            'amount' => '1.00',
            'payment_method_id' => '1',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $gPay->getMsg());
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

        $gPay = new GPay();
        $gPay->paymentTracking();
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

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->paymentTracking();
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
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
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
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
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
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
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
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
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
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試訂單查詢異常
     */
    public function testTrackingReturnWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '找不到交易',
            180123
        );

        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"25","message":"找不到交易","traceno":"201703220000001397",' .
            '"orderno":"03170322000010488763","channelOrderno":"null"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試二維訂單查詢異常
     */
    public function testTrackingReturnWithErrorMessageQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '找不到交易',
            180123
        );

        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"找不到交易","respCode":"3","payType":"2","scanType":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢結果缺少回傳參數
     */
    public function testTrackingReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"未支付","respCode":"0"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試二維訂單查詢結果訂單未支付
     */
    public function testTrackingWxReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"未支付","respCode":"0","payType":"2","scanType":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"未支付","respCode":"00","status":"1"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試二維訂單查詢結果支付失敗
     */
    public function testTrackingWxReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"支付失败","respCode":"2","payType":"2","scanType":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"支付失败","respCode":"00","status":"3"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回缺少訂單號
     */
    public function testTrackingWithoutOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"002440373720003","amount":"1.00",' .
            '"orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
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
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"002440373720003","amount":"1.00",' .
            '"traceno":"9453","orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
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
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"002440373720003","amount":"9453.00",' .
            '"traceno":"9453","orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"002440373720003","amount":"1.00",' .
            '"traceno":"201703220000001397","orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gPay = new GPay();
        $gPay->setContainer($this->container);
        $gPay->setClient($this->client);
        $gPay->setResponse($response);
        $gPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $gPay->setOptions($options);
        $gPay->paymentTracking();
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

        $gPay = new GPay();
        $gPay->getPaymentTrackingData();
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

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->getPaymentTrackingData();
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
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42','172.26.54.41'],
            'verify_url' => '',
        ];

        $gPay = new GPay();
        $gPay->setPrivateKey('test');
        $gPay->setOptions($options);
        $gPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42','172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $gPay = new GPay();
        $gPay->setOptions($options);
        $gPay->setPrivateKey('test');
        $trackingData = $gPay->getPaymentTrackingData();

        $path = '/gateway.do?m=query';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($options['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試取得二維訂單查詢需要的參數
     */
    public function testGetPaymentTrackingDataQrcode()
    {
        $options = [
            'number' => '002440373720003',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42','172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $gPay = new GPay();
        $gPay->setOptions($options);
        $gPay->setPrivateKey('test');
        $trackingData = $gPay->getPaymentTrackingData();

        $path = '/qrcodeQuery';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($options['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試訂單查詢支付平台返回的編碼
     */
    public function testProcessTrackingResponseEncoding()
    {
        $rawResponse = 'eyJtZXNzYWdlIjoisunRr7PJuaYiLCJvcmRlcm5vIjoiMDIxNzAzMjgxMDAwMDAwMDE4MzYiLCJwYXlUeXBlIjoiMiIsI' .
            'nJlc3BDb2RlIjoiMCIsInNjYW5UeXBlIjoiMiIsInRyYWNlbm8iOiIyMDE3MDMyODAwMDAwMDE0NTAifQ==';
        $response['body'] = $rawResponse;

        $encodingType = mb_detect_encoding($response['body'], ['CP936'], true);
        $this->assertEquals('CP936', $encodingType);

        $gPay = new GPay();
        $processedResponse = $gPay->processTrackingResponseEncoding($response);
        $encodingType = mb_detect_encoding($processedResponse['body'], ['UTF-8'], true);

        $this->assertEquals('UTF-8', $encodingType);
    }
}
