<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HeLiBaoScan;
use Buzz\Message\Response;

class HeLiBaoScanTest extends DurianTestCase
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
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'M800029933',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201611220000005110',
            'notify_url' => 'http://two123.comxa.com/',
            'domain' => '6',
            'merchantId' => '3528',
            'ip' => '127.0.0.1',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-22 15:35:24',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->getVerifyData();
    }

    /**
     * 測試支付但返回結果失敗
     */
    public function testPayButReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '失败',
            180130
        );

        $sourceData = [
            'number' => 'M800029933',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201611220000005110',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '127.0.0.1',
            'username' => 'php1test',
            'postUrl' => 'payment.http.api.99juhe.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_payType' => 'SCAN',
            'r5_qrcode' => 'weixin://wxpay/bizpayurl?pr=iykqoYD',
            'r7_amount' => '1.0',
            'r8_currency' => 'CNY',
            'sign' => '7ecd3b8fdc2c1d5473a459aa14c2b54c',
            'retCode' => '0003',
            'retMsg' => '失败',
            'trxType' => 'AppPay',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->getVerifyData();
    }

    /**
     * 測試支付但返回缺少r5_qrcode
     */
    public function testPayButReturnNoQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => 'M800029933',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201611220000005110',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '127.0.0.1',
            'username' => 'php1test',
            'postUrl' => 'payment.http.api.99juhe.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_payType' => 'SCAN',
            'r7_amount' => '1.0',
            'r8_currency' => 'CNY',
            'retCode' => '0000',
            'retMsg' => '成功',
            'sign' => '7ecd3b8fdc2c1d5473a459aa14c2b54c',
            'trxType' => 'AppPay',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => 'M800029933',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201611220000005110',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '127.0.0.1',
            'username' => 'php1test',
            'postUrl' => 'payment.http.api.99juhe.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_payType' => 'SCAN',
            'r5_qrcode' => 'weixin://wxpay/bizpayurl?pr=iykqoYD',
            'r7_amount' => '1.0',
            'r8_currency' => 'CNY',
            'retCode' => '0000',
            'retMsg' => '成功',
            'sign' => '7ecd3b8fdc2c1d5473a459aa14c2b54c',
            'trxType' => 'AppPay',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $encodeData = $heLiBaoScan->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals($data['r5_qrcode'], $heLiBaoScan->getQrcode());
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = ['r1_merchantNo' => ''];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'r6_currency' => 'CNY',
            'r1_merchantNo' => 'M800029933',
            'r3_serialNumber' => '1149164',
            'r5_amount' => '1.0',
            'r4_orderStatus' => 'SUCCESS',
            'r2_orderNumber' => '201611230000005151',
            'r7_timestamp' => '1479888980100',
            'r8_desc' => '',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->verifyOrderPayment([]);
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

        $sourceData = [
            'sign' => 'ca30663d4cde2b4eac132f39eec40a04',
            'r6_currency' => 'CNY',
            'r1_merchantNo' => 'M800029933',
            'r3_serialNumber' => '1149164',
            'r5_amount' => '1.0',
            'r4_orderStatus' => 'SUCCESS',
            'r2_orderNumber' => '201611230000005151',
            'r7_timestamp' => '1479888980100',
            'r8_desc' => '',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->verifyOrderPayment([]);
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

        $sourceData = [
            'sign' => 'e68e66dadff54f7a24f2e134d92c6158',
            'r6_currency' => 'CNY',
            'r1_merchantNo' => 'M800029933',
            'r3_serialNumber' => '1149164',
            'r5_amount' => '1.0',
            'r4_orderStatus' => 'FAIL',
            'r2_orderNumber' => '201611230000005151',
            'r7_timestamp' => '1479888980100',
            'r8_desc' => '',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'sign' => '98b1e28df6c3616cab70acf4d3def502',
            'r6_currency' => 'CNY',
            'r1_merchantNo' => 'M800029933',
            'r3_serialNumber' => '1149164',
            'r5_amount' => '1.0',
            'r4_orderStatus' => 'SUCCESS',
            'r2_orderNumber' => '201611230000005151',
            'r7_timestamp' => '1479888980100',
            'r8_desc' => '',
        ];

        $entry = ['id' => '201606220000002806'];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'sign' => '98b1e28df6c3616cab70acf4d3def502',
            'r6_currency' => 'CNY',
            'r1_merchantNo' => 'M800029933',
            'r3_serialNumber' => '1149164',
            'r5_amount' => '1.0',
            'r4_orderStatus' => 'SUCCESS',
            'r2_orderNumber' => '201611230000005151',
            'r7_timestamp' => '1479888980100',
            'r8_desc' => '',
        ];

        $entry = [
            'id' => '201611230000005151',
            'amount' => '50.0000',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'sign' => '98b1e28df6c3616cab70acf4d3def502',
            'r6_currency' => 'CNY',
            'r1_merchantNo' => 'M800029933',
            'r3_serialNumber' => '1149164',
            'r5_amount' => '1.0',
            'r4_orderStatus' => 'SUCCESS',
            'r2_orderNumber' => '201611230000005151',
            'r7_timestamp' => '1479888980100',
            'r8_desc' => '',
        ];

        $entry = [
            'id' => '201611230000005151',
            'amount' => '1.0000',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $heLiBaoScan->getMsg());
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

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->paymentTracking();
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

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->paymentTracking();
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
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $data = ['trxType' => 'AppPayQuery'];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611300000005303',
            'r5_amount' => '0',
            'r7_desc' => '',
            'retCode' => '0004',
            'retMsg' => '订单不存在',
            'sign' => '84a8bedb2012f562d0ce3cd5e1c9209a',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611280000005288',
            'r9_desc' => '',
            'retCode' => '999',
            'sign' => '4dc9c81b9539a357866e6e981d48ca41',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }

     /**
     * 測試訂單查詢結果沒有返回sign
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_orderStatus' => 'SUCCESS',
            'r5_amount' => '1.00',
            'r6_currency' => 'CNY',
            'r7_desc' => '',
            'retCode' => '0000',
            'retMsg' => '成功',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
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

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_orderStatus' => 'SUCCESS',
            'r5_amount' => '1.00',
            'r6_currency' => 'CNY',
            'r7_desc' => '',
            'retCode' => '0000',
            'retMsg' => '成功',
            'sign' => '80f933cfa950953e0fe68c60b3720506',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_orderStatus' => 'DOING',
            'r5_amount' => '1.00',
            'r6_currency' => 'CNY',
            'r7_desc' => '',
            'retCode' => '0000',
            'retMsg' => '成功',
            'sign' => '17de8f11710b54f06bdc342a8584a4ad',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
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

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_orderStatus' => 'FAIL',
            'r5_amount' => '1.00',
            'r6_currency' => 'CNY',
            'r7_desc' => '',
            'retCode' => '0000',
            'retMsg' => '失败',
            'sign' => 'fa63d88dbc13a0bfdb6a4aa057301b28',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入訂單號不正確
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_orderStatus' => 'SUCCESS',
            'r5_amount' => '1.00',
            'r6_currency' => 'CNY',
            'r7_desc' => '',
            'retCode' => '0000',
            'retMsg' => '成功',
            'sign' => 'fdd7af2469dc86c51eb8dd0d08a6a6ef',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testTrackingReturnWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_orderStatus' => 'SUCCESS',
            'r5_amount' => '1.00',
            'r6_currency' => 'CNY',
            'r7_desc' => '',
            'retCode' => '0000',
            'retMsg' => '成功',
            'sign' => 'fdd7af2469dc86c51eb8dd0d08a6a6ef',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611230000005151',
            'amount' => '999.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $data = [
            'r1_merchantNo' => 'M800029933',
            'r2_orderNumber' => '201611230000005151',
            'r3_serialNumber' => '1149164',
            'r4_orderStatus' => 'SUCCESS',
            'r5_amount' => '1.00',
            'r6_currency' => 'CNY',
            'r7_desc' => '',
            'retCode' => '0000',
            'retMsg' => '成功',
            'sign' => 'fdd7af2469dc86c51eb8dd0d08a6a6ef',
            'trxType' => 'AppPayQuery',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611230000005151',
            'amount' => '1.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBaoScan = new HeLiBaoScan();
        $heLiBaoScan->setContainer($this->container);
        $heLiBaoScan->setClient($this->client);
        $heLiBaoScan->setResponse($response);
        $heLiBaoScan->setPrivateKey('1234');
        $heLiBaoScan->setOptions($sourceData);
        $heLiBaoScan->paymentTracking();
    }
}
