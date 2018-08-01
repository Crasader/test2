<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Heepay;
use Buzz\Message\Response;

class HeepayTest extends DurianTestCase
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
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $heepay = new Heepay();
        $heepay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceButNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');

        $sourceData = [
            'paymentVendorId' => '1',
            'number' => ''
        ];

        $heepay->setOptions($sourceData);
        $heepay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $heepay = new Heepay();
        $heepay->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'paymentVendorId' => '7',
            'number' => '1234567',
            'orderId' => '20100225132210',
            'amount' => '12.34',
            'notify_url' => 'http://www.xxx.com/heepay1.aspx',
            'ip' => '127.127.12.12',
            'orderCreateDate' => '2010-02-25 10:20:00',
            'username' => 'php1test',
        ];

        $heepay->setOptions($sourceData);
        $heepay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $heepay = new Heepay();
        $heepay->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'paymentVendorId' => '1',
            'number' => '1234567',
            'orderId' => '20100225132210',
            'amount' => '12.34',
            'notify_url' => 'http://www.xxx.com/heepay1.aspx',
            'ip' => '127.127.12.12',
            'orderCreateDate' => '2010-02-25 10:20:00',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $heepay->setOptions($sourceData);
        $encodeData = $heepay->getVerifyData();

        $this->assertEquals('1', $encodeData['version']);
        $this->assertEquals('20', $encodeData['pay_type']);
        $this->assertEquals('001', $encodeData['pay_code']);
        $this->assertEquals('1234567', $encodeData['agent_id']);
        $this->assertEquals('20100225132210', $encodeData['agent_bill_id']);
        $this->assertEquals('12.34', $encodeData['pay_amt']);
        $this->assertEquals('http://www.xxx.com/heepay1.aspx', $encodeData['notify_url']);
        $this->assertEquals('http://www.xxx.com/heepay1.aspx', $encodeData['return_url']);
        $this->assertEquals('127_127_12_12', $encodeData['user_ip']);
        $this->assertEquals('20100225102000', $encodeData['agent_bill_time']);
        $this->assertEquals('php1test', $encodeData['goods_name']);
        $this->assertEquals('35660_6', $encodeData['remark']);
        $this->assertEquals('43c3095c00adbbe0c32f42e9ae2253f7', $encodeData['sign']);
    }

    /**
     * 測試加密，帶入支付寶二維
     */
    public function testGetEncodeDataWithAlipayQRCode()
    {
        $heepay = new Heepay();
        $heepay->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        // 支付寶二維 paymentVendorId 為 1092
        $sourceData = [
            'paymentVendorId' => '1092',
            'number' => '1234567',
            'orderId' => '20100225132210',
            'amount' => '12.34',
            'notify_url' => 'http://www.xxx.com/heepay1.aspx',
            'ip' => '127.127.12.12',
            'orderCreateDate' => '2010-02-25 10:20:00',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $heepay->setOptions($sourceData);
        $encodeData = $heepay->getVerifyData();

        $this->assertEquals('22', $encodeData['pay_type']);
        $this->assertEmpty($encodeData['pay_code']);
        $this->assertEquals('4f9f8f5102099b7a47e1505a7257c736', $encodeData['sign']);
    }

    /**
     * 測試加密，帶入微信二維
     */
    public function testGetEncodeDataWithWeiXinQRCode()
    {
        $heepay = new Heepay();
        $heepay->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        // 微信支付二維 paymentVendorId 為 1090
        $sourceData = [
            'paymentVendorId' => '1090',
            'number' => '1234567',
            'orderId' => '20100225132210',
            'amount' => '12.34',
            'notify_url' => 'http://www.xxx.com/heepay1.aspx',
            'ip' => '127.127.12.12',
            'orderCreateDate' => '2010-02-25 10:20:00',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $heepay->setOptions($sourceData);
        $encodeData = $heepay->getVerifyData();

        $this->assertEquals('30', $encodeData['pay_type']);
        $this->assertEmpty($encodeData['pay_code']);
        $this->assertEquals('f4c934122ad642c5b913644457dee743', $encodeData['sign']);
    }

    /**
     * 測試支付銀行為微信WAP
     */
    public function testPayWithWeiXinWAP()
    {
        $sourceData = [
            'paymentVendorId' => '1097',
            'number' => '1234567',
            'orderId' => '201707260001',
            'amount' => '12.34',
            'notify_url' => 'http://www.xxx.com/heepay1.aspx',
            'ip' => '127.127.12.12',
            'orderCreateDate' => '2017-07-26 10:20:00',
            'username' => 'php1test',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $metaOption = [
            's' => 'WAP',
            'n' => 'php1test',
            'id' => 'http://www.xxx.com',
        ];

        $metaOptionEncode = urlencode(iconv('utf-8', 'gb2312', base64_encode(json_encode($metaOption))));

        $heepay = new Heepay();
        $heepay->setPrivateKey('test');
        $heepay->setOptions($sourceData);
        $requestData = $heepay->getVerifyData();

        $this->assertEquals('1', $requestData['version']);
        $this->assertEquals('1', $requestData['is_phone']);
        $this->assertEquals('0', $requestData['is_frame']);
        $this->assertEquals('30', $requestData['pay_type']);
        $this->assertEquals('', $requestData['pay_code']);
        $this->assertEquals('1234567', $requestData['agent_id']);
        $this->assertEquals('201707260001', $requestData['agent_bill_id']);
        $this->assertEquals('12.34', $requestData['pay_amt']);
        $this->assertEquals('http://www.xxx.com/heepay1.aspx', $requestData['notify_url']);
        $this->assertEquals('http://www.xxx.com/heepay1.aspx', $requestData['return_url']);
        $this->assertEquals('127_127_12_12', $requestData['user_ip']);
        $this->assertEquals('20170726102000', $requestData['agent_bill_time']);
        $this->assertEquals('php1test', $requestData['goods_name']);
        $this->assertEquals('35660_6', $requestData['remark']);
        $this->assertEquals('c04c74b0844404574ea51150a21a3525', $requestData['sign']);
        $this->assertEquals($metaOptionEncode, $requestData['meta_option']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $heepay = new Heepay();
        $heepay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithouResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'pay_message' => '',
            'agent_id' => '1234567',
            'jnet_bill_no' => 'B20100225132210',
            'agent_bill_id' => '20100225132210',
            'pay_type' => '20',
            'pay_amt' => '12.34',
            'remark' => '35660_6',
            'sign' => '227ba9432b4fab05853263c5b2f7d410',
        ];

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳sign(加密簽名)
     */
    public function testVerifyWithouSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'result' => '1',
            'pay_message' => '',
            'agent_id' => '1234567',
            'jnet_bill_no' => 'B20100225132210',
            'agent_bill_id' => '20100225132210',
            'pay_type' => '20',
            'pay_amt' => '12.34',
            'remark' => '35660_6',
        ];

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台但簽名驗證錯誤
     */
    public function testVerifyButSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'result' => '1',
            'pay_message' => '',
            'agent_id' => '1234567',
            'jnet_bill_no' => 'B20100225132209',
            'agent_bill_id' => '20100225132209',
            'pay_type' => '20',
            'pay_amt' => '12.34',
            'remark' => '35660_6',
            'sign' => '227ba9432b4fab05853263c5b2f7d410',
        ];

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台但支付失敗
     */
    public function testVerifyButPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'result' => '0',
            'pay_message' => '支付未成功',
            'agent_id' => '1234567',
            'jnet_bill_no' => 'B20100225132210',
            'agent_bill_id' => '20100225132210',
            'pay_type' => '20',
            'pay_amt' => '12.34',
            'remark' => '35660_6',
            'sign' => '64cdbfd9af9830a9a1e37185dcb48827',
        ];

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台但訂單號錯誤
     */
    public function testVerifyButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'result' => '1',
            'pay_message' => '',
            'agent_id' => '1234567',
            'jnet_bill_no' => 'B20100225132210',
            'agent_bill_id' => '20100225132210',
            'pay_type' => '20',
            'pay_amt' => '12.34',
            'remark' => '35660_6',
            'sign' => '227ba9432b4fab05853263c5b2f7d410',
        ];

        $entry = ['id' => '20100225132209'];

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證支付平台但支付金額錯誤
     */
    public function testVerifyButOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'result' => '1',
            'pay_message' => '',
            'agent_id' => '1234567',
            'jnet_bill_no' => 'B20100225132210',
            'agent_bill_id' => '20100225132210',
            'pay_type' => '20',
            'pay_amt' => '12.34',
            'remark' => '35660_6',
            'sign' => '227ba9432b4fab05853263c5b2f7d410',
        ];

        $entry = [
            'id' => '20100225132210',
            'amount' => '12.3'
        ];

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證支付平台成功
     */
    public function testVerifySuccess()
    {
        $sourceData = [
            'result' => '1',
            'pay_message' => '',
            'agent_id' => '1234567',
            'jnet_bill_no' => 'B20100225132210',
            'agent_bill_id' => '20100225132210',
            'pay_type' => '20',
            'pay_amt' => '12.34',
            'remark' => '35660_6',
            'sign' => '227ba9432b4fab05853263c5b2f7d410',
        ];

        $entry = [
            'id' => '20100225132210',
            'amount' => '12.34'
        ];

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $heepay->getMsg());
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

        $heepay = new Heepay();
        $heepay->paymentTracking();
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

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->paymentTracking();
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
            'number' => '1234567',
            'orderId' => '201002251422231234',
            'orderCreateDate' => '2009-11-12 10:20:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $heepay = new Heepay();
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數 jnet_bill_no
     */
    public function testPaymentTrackingResultWithoutJnetBillNo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'agent_id=1234567|agent_bill_id=201002251422231234|pay_type=20|' .
            'result=1|pay_amt=0.01|pay_message=|remark=|sign=d4f65e081dddb069c19cbf0c2698c46e';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '1234567',
            'orderId' => '201002251422231234',
            'orderCreateDate' => '2009-11-12 10:20:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.heepay.com',
        ];

        $heepay = new Heepay();
        $heepay->setContainer($this->container);
        $heepay->setClient($this->client);
        $heepay->setResponse($response);
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數 sign
     */
    public function testPaymentTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'agent_id=1234567|agent_bill_id=201002251422231234|jnet_bill_no=H1605035133406AW|pay_type=20|' .
            'result=1|pay_amt=0.01|pay_message=|remark=';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '1234567',
            'orderId' => '201002251422231234',
            'orderCreateDate' => '2009-11-12 10:20:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.heepay.com',
        ];

        $heepay = new Heepay();
        $heepay->setContainer($this->container);
        $heepay->setClient($this->client);
        $heepay->setResponse($response);
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->paymentTracking();
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

        $result = 'agent_id=1234567|agent_bill_id=201002251422231234|jnet_bill_no=H1605035133406AW|pay_type=20|' .
            'result=1|pay_amt=0.01|pay_message=|remark=|sign=d4f65e081dddb069c19cbf0c2698c46e';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '1234567',
            'orderId' => '201002251422231234',
            'orderCreateDate' => '2009-11-12 10:20:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.heepay.com',
        ];

        $heepay = new Heepay();
        $heepay->setContainer($this->container);
        $heepay->setClient($this->client);
        $heepay->setResponse($response);
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->paymentTracking();
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

        $result = 'agent_id=1234567|agent_bill_id=201002251422231234|jnet_bill_no=H1605035133406AW|pay_type=20|' .
            'result=0|pay_amt=0.00|pay_message=|remark=|sign=477ab2cde0c1e6fd2e51da71d96d3c6b';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '1234567',
            'orderId' => '201002251422231234',
            'orderCreateDate' => '2009-11-12 10:20:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.heepay.com',
        ];

        $heepay = new Heepay();
        $heepay->setContainer($this->container);
        $heepay->setClient($this->client);
        $heepay->setResponse($response);
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->paymentTracking();
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

        $result = 'agent_id=1234567|agent_bill_id=201002251422231234|jnet_bill_no=H1605035133406AW|pay_type=20|' .
            'result=1|pay_amt=12.34|pay_message=|remark=|sign=bb49b5a859f0c2179d4ad5161fac3d10';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '1234567',
            'orderId' => '201002251422231234',
            'orderCreateDate' => '2009-11-12 10:20:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.heepay.com',
            'amount' => '1000.00',
        ];

        $heepay = new Heepay();
        $heepay->setContainer($this->container);
        $heepay->setClient($this->client);
        $heepay->setResponse($response);
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = 'agent_id=1234567|agent_bill_id=201002251422231234|jnet_bill_no=H1605035133406AW|pay_type=20|' .
            'result=1|pay_amt=12.34|pay_message=|remark=|sign=bb49b5a859f0c2179d4ad5161fac3d10';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '1234567',
            'orderId' => '201002251422231234',
            'orderCreateDate' => '2009-11-12 10:20:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.heepay.com',
            'amount' => '12.34',
        ];

        $heepay = new Heepay();
        $heepay->setContainer($this->container);
        $heepay->setClient($this->client);
        $heepay->setResponse($response);
        $heepay->setPrivateKey('CC08C5E3E69F4E6B85F1DC0B');
        $heepay->setOptions($sourceData);
        $heepay->paymentTracking();
    }
}
