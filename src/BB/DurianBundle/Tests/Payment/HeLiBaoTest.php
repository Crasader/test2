<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HeLiBao;
use Buzz\Message\Response;

class HeLiBaoTest extends DurianTestCase
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

        $heLiBao = new HeLiBao();
        $heLiBao->getVerifyData();
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

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->getVerifyData();
    }

    /**
     * 測試支付加密時帶入支付平台不支援的銀行
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
            'ip' => '127.0.0.1',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-22 15:35:24',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => 'M800029933',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201611220000005110',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '127.0.0.1',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-22 15:35:24',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $encodeData = $heLiBao->getVerifyData();

        $this->assertEquals('OnlinePay', $encodeData['P1_bizType']);
        $this->assertEquals($sourceData['orderId'], $encodeData['P2_orderId']);
        $this->assertEquals($sourceData['number'], $encodeData['P3_customerNumber']);
        $this->assertSame('0.01', $encodeData['P4_orderAmount']);
        $this->assertEquals('ICBC', $encodeData['P5_bankId']);
        $this->assertEquals('B2C', $encodeData['P6_business']);
        $this->assertEquals('20161122153524', $encodeData['P7_timestamp']);
        $this->assertEquals('php1test', $encodeData['P8_goodsName']);
        $this->assertEquals('7', $encodeData['P9_period']);
        $this->assertEquals('day', $encodeData['P10_periodUnit']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['P11_callbackUrl']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['P12_serverCallbackUrl']);
        $this->assertEquals($sourceData['ip'], $encodeData['P13_orderIp']);
        $this->assertEquals('DEBIT', $encodeData['P14_onlineCardType']);
        $this->assertEquals('', $encodeData['P15_desc']);
        $this->assertEquals('ea11ced46459f2d74d3a28f04f0cb7ce', $encodeData['sign']);
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

        $heLiBao = new HeLiBao();
        $heLiBao->verifyOrderPayment([]);
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

        $sourceData = ['rt2_retCode' => ''];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->verifyOrderPayment([]);
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
            'rt1_bizType' => 'OnlinePay',
            'rt2_retCode' => '0000',
            'rt3_retMsg' => '',
            'rt4_customerNumber' => '9527',
            'rt5_orderId' => '2017080100005278',
            'rt6_orderAmount' => '0.12',
            'rt7_bankId' => 'ICBC',
            'rt8_business' => 'B2C',
            'rt9_timestamp' => '2017-08-01 15:35:24',
            'rt10_completeDate' => '2017-08-01 15:35:54',
            'rt11_orderStatus' => 'SUCCESS',
            'rt12_serialNumber' => 'NET1611221535245H41',
            'rt13_desc' => '',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->verifyOrderPayment([]);
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
            'rt1_bizType' => 'OnlinePay',
            'rt2_retCode' => '0000',
            'rt3_retMsg' => '',
            'rt4_customerNumber' => '9527',
            'rt5_orderId' => '2017080100005278',
            'rt6_orderAmount' => '0.12',
            'rt7_bankId' => 'ICBC',
            'rt8_business' => 'B2C',
            'rt9_timestamp' => '2017-08-01 15:35:24',
            'rt10_completeDate' => '2017-08-01 15:35:54',
            'rt11_orderStatus' => 'SUCCESS',
            'rt12_serialNumber' => 'NET1611221535245H41',
            'rt13_desc' => '',
            'sign' => 'fail',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->verifyOrderPayment([]);
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
            'rt1_bizType' => 'OnlinePay',
            'rt2_retCode' => '1111',
            'rt3_retMsg' => '',
            'rt4_customerNumber' => '9527',
            'rt5_orderId' => '2017080100005278',
            'rt6_orderAmount' => '0.12',
            'rt7_bankId' => 'ICBC',
            'rt8_business' => 'B2C',
            'rt9_timestamp' => '2017-08-01 15:35:24',
            'rt10_completeDate' => '2017-08-01 15:35:54',
            'rt11_orderStatus' => 'SUCCESS',
            'rt12_serialNumber' => 'NET1611221535245H41',
            'rt13_desc' => '',
            'sign' => '0a94838e75ffe64c0b921269de485058',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->verifyOrderPayment([]);
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
            'rt1_bizType' => 'OnlinePay',
            'rt2_retCode' => '0000',
            'rt3_retMsg' => '',
            'rt4_customerNumber' => '9527',
            'rt5_orderId' => '2017080100005278',
            'rt6_orderAmount' => '0.12',
            'rt7_bankId' => 'ICBC',
            'rt8_business' => 'B2C',
            'rt9_timestamp' => '2017-08-01 15:35:24',
            'rt10_completeDate' => '2017-08-01 15:35:54',
            'rt11_orderStatus' => 'SUCCESS',
            'rt12_serialNumber' => 'NET1611221535245H41',
            'rt13_desc' => '',
            'sign' => '247e57565b6a9dcf5a87d317242a8fe1',
        ];

        $entry = ['id' => '201606220000002806'];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->verifyOrderPayment($entry);
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
            'rt1_bizType' => 'OnlinePay',
            'rt2_retCode' => '0000',
            'rt3_retMsg' => '',
            'rt4_customerNumber' => '9527',
            'rt5_orderId' => '2017080100005278',
            'rt6_orderAmount' => '0.12',
            'rt7_bankId' => 'ICBC',
            'rt8_business' => 'B2C',
            'rt9_timestamp' => '2017-08-01 15:35:24',
            'rt10_completeDate' => '2017-08-01 15:35:54',
            'rt11_orderStatus' => 'SUCCESS',
            'rt12_serialNumber' => 'NET1611221535245H41',
            'rt13_desc' => '',
            'sign' => '247e57565b6a9dcf5a87d317242a8fe1',
        ];

        $entry = [
            'id' => '2017080100005278',
            'amount' => '1.0000',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'rt1_bizType' => 'OnlinePay',
            'rt2_retCode' => '0000',
            'rt3_retMsg' => '',
            'rt4_customerNumber' => '9527',
            'rt5_orderId' => '2017080100005278',
            'rt6_orderAmount' => '0.12',
            'rt7_bankId' => 'ICBC',
            'rt8_business' => 'B2C',
            'rt9_timestamp' => '2017-08-01 15:35:24',
            'rt10_completeDate' => '2017-08-01 15:35:54',
            'rt11_orderStatus' => 'SUCCESS',
            'rt12_serialNumber' => 'NET1611221535245H41',
            'rt13_desc' => '',
            'sign' => '247e57565b6a9dcf5a87d317242a8fe1',
        ];

        $entry = [
            'id' => '2017080100005278',
            'amount' => '0.1200',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $heLiBao->getMsg());
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

        $heLiBao = new HeLiBao();
        $heLiBao->paymentTracking();
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

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->paymentTracking();
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

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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

        $data = ['P1_bizType' => 'OnlineQuery'];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => 'M800029933',
            'rt4_orderId' => '201611280000005288',
            'rt5_orderAmount' => '0.01',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => 'M800029933',
            'rt4_orderId' => '201611280000005288',
            'rt5_orderAmount' => '0.01',
            'sign' => 'fail',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '8102',
            'rt3_customerNumber' => 'M800029933',
            'rt4_orderId' => '201611280000005288',
            'rt5_orderAmount' => '0.01',
            'sign' => '5ba5568c359bc8043f5502e03389bac1',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '9999',
            'rt3_customerNumber' => 'M800029933',
            'rt4_orderId' => '201611280000005288',
            'rt5_orderAmount' => '0.01',
            'sign' => '7a51fe29f6024cac1bd7729d1dbccf8f',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'M800029933',
            'orderId' => '201611220000005110',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'INIT',
            'rt11_desc' => '',
            'sign' => '926b6d4934325f70b0f38cdfdb9382f0',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'FAIL',
            'rt11_desc' => '',
            'sign' => '816208e19d7e56a4659024a419183f15',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'SUCCESS',
            'rt11_desc' => '',
            'sign' => 'eb766d4f8a89e4adbba87a50024fc7a4',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201611220000005100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
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
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'SUCCESS',
            'rt11_desc' => '',
            'sign' => 'eb766d4f8a89e4adbba87a50024fc7a4',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'amount' => '999.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'SUCCESS',
            'rt11_desc' => '',
            'sign' => 'eb766d4f8a89e4adbba87a50024fc7a4',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.99juhe.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setContainer($this->container);
        $heLiBao->setClient($this->client);
        $heLiBao->setResponse($response);
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTracking();
    }

    /**
     *  測試取得訂單查詢需要的參數時未帶入密鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $heLiBao = new HeLiBao();
        $heLiBao->getPaymentTrackingData();
    }

    /**
     *  測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒帶入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $trackingData = $heLiBao->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/trx/online/interface.action', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals('OnlineQuery', $trackingData['form']['P1_bizType']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['P2_orderId']);
        $this->assertEquals($sourceData['number'], $trackingData['form']['P3_customerNumber']);
        $this->assertEquals('a42999744f2bb2efb55c7a123cf58175', $trackingData['form']['sign']);
    }

    /**
     *  測試驗證訂單查詢是否成功時缺少密鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $heLiBao = new HeLiBao();
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢是否成功時缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢結果沒有返回sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => 'M800029933',
            'rt4_orderId' => '201611280000005288',
            'rt5_orderAmount' => '0.01',
        ];

        $result = json_encode($data);

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢結果簽名驗證錯誤
     */
    public function testPaymentTrackingVerifyWithSignFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => 'M800029933',
            'rt4_orderId' => '201611280000005288',
            'rt5_orderAmount' => '0.01',
            'sign' => 'fail',
        ];

        $result = json_encode($data);

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢結果回傳訂單不存在
     */
    public function testPaymentTrackingVerifyWithOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '8102',
            'rt3_customerNumber' => 'M800029933',
            'rt4_orderId' => '201611280000005288',
            'rt5_orderAmount' => '0.01',
            'sign' => '5ba5568c359bc8043f5502e03389bac1',
        ];

        $result = json_encode($data);

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢失敗
     */
    public function testPaymentTrackingVerifyWithFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '9999',
            'rt3_customerNumber' => 'M800029933',
            'rt4_orderId' => '201611280000005288',
            'rt5_orderAmount' => '0.01',
            'sign' => '7a51fe29f6024cac1bd7729d1dbccf8f',
        ];

        $result = json_encode($data);

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢結果訂單未支付
     */
    public function testPaymentTrackingVerifyWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'INIT',
            'rt11_desc' => '',
            'sign' => '926b6d4934325f70b0f38cdfdb9382f0',
        ];

        $result = json_encode($data);

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢結果訂單未支付
     */
    public function testPaymentTrackingVerifyWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'FAIL',
            'rt11_desc' => '',
            'sign' => '816208e19d7e56a4659024a419183f15',
        ];

        $result = json_encode($data);

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢結果訂單號不正確
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'SUCCESS',
            'rt11_desc' => '',
            'sign' => 'eb766d4f8a89e4adbba87a50024fc7a4',
        ];

        $result = json_encode($data);

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708010000005279',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }

    /**
     *  測試驗證訂單查詢結果訂單號不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $data = [
            'rt1_bizType' => 'OnlineQuery',
            'rt2_retCode' => '0000',
            'rt3_customerNumber' => '9527',
            'rt4_orderId' => '201708010000005278',
            'rt5_orderAmount' => '0.01',
            'rt6_bankId' => 'ICBC',
            'rt7_business' => 'B2C',
            'rt8_createDate' => '2017-08-01 15:35:24',
            'rt9_completeDate' => '2017-08-01 15:35:28',
            'rt10_orderStatus' => 'SUCCESS',
            'rt11_desc' => '',
            'sign' => 'eb766d4f8a89e4adbba87a50024fc7a4',
        ];

        $result = json_encode($data);

        $sourceData = [
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201708010000005278',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $heLiBao = new HeLiBao();
        $heLiBao->setPrivateKey('1234');
        $heLiBao->setOptions($sourceData);
        $heLiBao->paymentTrackingVerify();
    }
}
