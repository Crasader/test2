<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Joinpay;
use Buzz\Message\Response;

class JoinpayTest extends DurianTestCase
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

        $joinpay = new Joinpay();
        $joinpay->getVerifyData();
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

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->getVerifyData();
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
            'number' => '888100000001792',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201608250000004029',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'username' => 'php1test',
        ];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
            'username' => 'php1test',
        ];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $data = $joinpay->getVerifyData();

        $this->assertEquals($options['number'], $data['p1_MerchantNo']);
        $this->assertEquals($options['orderId'], $data['p2_OrderNo']);
        $this->assertEquals($options['amount'], $data['p3_Amount']);
        $this->assertEquals('1', $data['p4_Cur']);
        $this->assertEquals($options['username'], $data['p5_ProductName']);
        $this->assertEquals('', $data['p6_Mp']);
        $this->assertEquals($options['notify_url'], $data['p7_ReturnUrl']);
        $this->assertEquals($options['notify_url'], $data['p8_NotifyUrl']);
        $this->assertEquals('ICBC_NET_B2C', $data['p9_FrpCode']);
        $this->assertEquals('0', $data['pa_OrderPeriod']);
        $this->assertEquals('9ea1aa0e140a4260b56489a4c1bffd64', $data['hmac']);
    }

    /**
     * 測試支付，帶入微信但沒有帶入verify_url的情況
     */
    public function testPayWithWeixinWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->getVerifyData();
    }

    /**
     * 測試支付，帶入微信取得支付參數失敗
     */
    public function testPayWithWeixinGetPayParametersFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
            'username' => 'php1test',
            'verify_url' => 'payment.https.www.joinpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<html><head><title>Apache Tomcat/7.0.42 - Error report</title>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->getVerifyData();
    }

    /**
     * 測試支付，帶入微信但商戶未開通
     */
    public function testPayWithWeixinPayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'HTTP Status 500 - 商户未开通[网关扫码]业务功能',
            180130
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
            'username' => 'php1test',
            'verify_url' => 'payment.https.www.joinpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<html><head><title>Apache Tomcat/7.0.42 - Error report</title><body>' .
            '<h1>HTTP Status 500 - 商户未开通[网关扫码]业务功能</h1><HR size="1" noshade="noshade"><p>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 500');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->getVerifyData();
    }

    /**
     * 測試支付，帶入微信但返回缺少ra_Status
     */
    public function testPayWithWeixinButReturnNoRaStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
            'username' => 'php1test',
            'verify_url' => 'payment.https.www.joinpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'hmac' => '9D73464A5602B6A83F9279DA8497D479',
            'r1_MerchantNo' => '888100000002021',
            'r2_OrderNo' => '201611240000005263',
            'r3_Amount' => '',
            'r4_ProductName' => '',
            'r5_TrxNo' => '',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->getVerifyData();
    }

    /**
     * 測試支付，帶入微信但返回失敗
     */
    public function testPayWithWeixinButReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验证签名失败',
            180130
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
            'username' => 'php1test',
            'verify_url' => 'payment.https.www.joinpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'hmac' => '9D73464A5602B6A83F9279DA8497D479',
            'r1_MerchantNo' => '888100000002021',
            'r2_OrderNo' => '201611240000005263',
            'r3_Amount' => '',
            'r4_ProductName' => '',
            'r5_TrxNo' => '',
            'ra_Status' => '',
            'rb_Code' => '10080002',
            'rc_CodeMsg' => '验证签名失败',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->getVerifyData();
    }

    /**
     * 測試支付，帶入微信但返回缺少ra_code
     */
    public function testPayWithWeixinButReturnNoRaCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
            'username' => 'php1test',
            'verify_url' => 'payment.https.www.joinpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'hmac' => '9D73464A5602B6A83F9279DA8497D479',
            'r1_MerchantNo' => '888100000002021',
            'r2_OrderNo' => '201611240000005263',
            'r3_Amount' => '',
            'r4_ProductName' => '',
            'r5_TrxNo' => '',
            'ra_Status' => '100',
            'rb_Code' => '100',
            'rc_CodeMsg' => '',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->getVerifyData();
    }

    /**
     * 測試支付，帶入微信
     */
    public function testPayWithWeixin()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'ip' => '127.0.0.1',
            'number' => '888100000001792',
            'username' => 'php1test',
            'verify_url' => 'payment.https.www.joinpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'hmac' => '9D73464A5602B6A83F9279DA8497D479',
            'r1_MerchantNo' => '888100000002021',
            'r2_OrderNo' => '201611240000005263',
            'r3_Amount' => '',
            'r4_ProductName' => '',
            'r5_TrxNo' => '',
            'ra_Status' => '100',
            'ra_code' => 'weixin://wxpay/bizpayurl?pr=bOjzDQo',
            'rb_Code' => '100',
            'rc_CodeMsg' => '',
        ];

        $result = json_encode($data);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $encodeData = $joinpay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=bOjzDQo', $joinpay->getQrcode());
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

        $joinpay = new Joinpay();
        $joinpay->verifyOrderPayment([]);
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

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名數據(hmac)
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'r1_MerchantNo' => '888100000001792',
            'r2_OrderNo' => '201608250000004029',
            'r3_Amount' => '1.00',
            'r4_Cur' => '1',
            'r5_Mp' => '',
            'r6_Status' => '100',
            'r7_TrxNo' => '100216082500248150',
            'r8_BankOrderNo' => '100216082500248150',
            'r9_BankTrxNo' => '2016082533788578',
            'ra_PayTime' => '2016-08-25+13%3A52%3A18',
            'rb_DealTime' => '2016-08-25+13%3A52%3A18',
            'rc_BankCode' => 'ICBC_NET_B2C',
        ];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->verifyOrderPayment([]);
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
            'r1_MerchantNo' => '888100000001792',
            'r2_OrderNo' => '201608250000004029',
            'r3_Amount' => '1.00',
            'r4_Cur' => '1',
            'r5_Mp' => '',
            'r6_Status' => '100',
            'r7_TrxNo' => '100216082500248150',
            'r8_BankOrderNo' => '100216082500248150',
            'r9_BankTrxNo' => '2016082533788578',
            'ra_PayTime' => '2016-08-25+13%3A52%3A18',
            'rb_DealTime' => '2016-08-25+13%3A52%3A18',
            'rc_BankCode' => 'ICBC_NET_B2C',
            'hmac' => '111120f03dbd5521f53989936a0765fa',
        ];


        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->verifyOrderPayment([]);
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
            'r1_MerchantNo' => '888100000001792',
            'r2_OrderNo' => '201608250000004029',
            'r3_Amount' => '1.00',
            'r4_Cur' => '1',
            'r5_Mp' => '',
            'r6_Status' => '101',
            'r7_TrxNo' => '100216082500248150',
            'r8_BankOrderNo' => '100216082500248150',
            'r9_BankTrxNo' => '2016082533788578',
            'ra_PayTime' => '2016-08-25+13%3A52%3A18',
            'rb_DealTime' => '2016-08-25+13%3A52%3A18',
            'rc_BankCode' => 'ICBC_NET_B2C',
            'hmac' => '5f7e264d708f7bf71f2273d95cca613e',
        ];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->verifyOrderPayment([]);
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
            'r1_MerchantNo' => '888100000001792',
            'r2_OrderNo' => '201608250000004029',
            'r3_Amount' => '1.00',
            'r4_Cur' => '1',
            'r5_Mp' => '',
            'r6_Status' => '100',
            'r7_TrxNo' => '100216082500248150',
            'r8_BankOrderNo' => '100216082500248150',
            'r9_BankTrxNo' => '2016082533788578',
            'ra_PayTime' => '2016-08-25+13%3A52%3A18',
            'rb_DealTime' => '2016-08-25+13%3A52%3A18',
            'rc_BankCode' => 'ICBC_NET_B2C',
            'hmac' => '6b8c5073eb57033d82edfcf2fbba5b74',
        ];

        $entry = ['id' => '201509140000002475'];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->verifyOrderPayment($entry);
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
            'r1_MerchantNo' => '888100000001792',
            'r2_OrderNo' => '201608250000004029',
            'r3_Amount' => '1.00',
            'r4_Cur' => '1',
            'r5_Mp' => '',
            'r6_Status' => '100',
            'r7_TrxNo' => '100216082500248150',
            'r8_BankOrderNo' => '100216082500248150',
            'r9_BankTrxNo' => '2016082533788578',
            'ra_PayTime' => '2016-08-25+13%3A52%3A18',
            'rb_DealTime' => '2016-08-25+13%3A52%3A18',
            'rc_BankCode' => 'ICBC_NET_B2C',
            'hmac' => '6b8c5073eb57033d82edfcf2fbba5b74',
        ];

        $entry = [
            'id' => '201608250000004029',
            'amount' => '15.00',
        ];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'r1_MerchantNo' => '888100000001792',
            'r2_OrderNo' => '201608250000004029',
            'r3_Amount' => '1.00',
            'r4_Cur' => '1',
            'r5_Mp' => '',
            'r6_Status' => '100',
            'r7_TrxNo' => '100216082500248150',
            'r8_BankOrderNo' => '100216082500248150',
            'r9_BankTrxNo' => '2016082533788578',
            'ra_PayTime' => '2016-08-25+13%3A52%3A18',
            'rb_DealTime' => '2016-08-25+13%3A52%3A18',
            'rc_BankCode' => 'ICBC_NET_B2C',
            'hmac' => '720020f03dbd5521f53989936a0765fa',
        ];

        $entry = [
            'id' => '201608250000004029',
            'amount' => '1.00',
        ];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('9782b1c4799943cab01d6e6d5d54fe2a');
        $joinpay->setOptions($options);
        $joinpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $joinpay->getMsg());
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

        $joinpay = new Joinpay();
        $joinpay->paymentTracking();
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

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $joinpay = new Joinpay();
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.joinpay.com',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.joinpay.com',
        ];

        $result = '{"hmac":"83F76EC227A4F43FDDCC907C18F268C5","r1_MerchantNo":"888100000001792",' .
            '"r2_OrderNo":"201608250000004029","r3_Amount":1.00,"r4_ProductName":"php1test",' .
            '"r5_TrxNo":"100216082500248150","rb_Code":"100","rc_CodeMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有hmac的情況
     */
    public function testTrackingReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.joinpay.com',
        ];

        $result = '{"r1_MerchantNo":"888100000001792",' .
            '"r2_OrderNo":"201608250000004029","r3_Amount":1.00,"r4_ProductName":"php1test",' .
            '"r5_TrxNo":"100216082500248150","rb_Code":"100","rc_CodeMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608160000003698',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.joinpay.com',
        ];

        $result = '{"hmac":"83F76EC227A4F43FDDCC907C18F12345","r1_MerchantNo":"888100000001792",' .
            '"r2_OrderNo":"201608250000004029","r3_Amount":1.00,"r4_ProductName":"php1test",' .
            '"r5_TrxNo":"100216082500248150","ra_Status":100,"rb_Code":"100","rc_CodeMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
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

        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.joinpay.com',
        ];

        $result = '{"hmac":"d1f6e9a821cd944ea7d6254da31cbb03","r1_MerchantNo":"888100000001792",' .
            '"r2_OrderNo":"201608250000004029","r3_Amount":1.00,"r4_ProductName":"php1test",' .
            '"r5_TrxNo":"100216082500248150","ra_Status":102,"rb_Code":"100","rc_CodeMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
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
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.joinpay.com',
        ];

        $result = '{"hmac":"163a994569203a272decb5ad4c6aeaee","r1_MerchantNo":"888100000001792",' .
            '"r2_OrderNo":"201608250000004029","r3_Amount":1.00,"r4_ProductName":"php1test",' .
            '"r5_TrxNo":"100216082500248150","ra_Status":101,"rb_Code":"100","rc_CodeMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('test');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.joinpay.com',
        ];

        $result = '{"hmac":"83F76EC227A4F43FDDCC907C18F268C5","r1_MerchantNo":"888100000001792",' .
            '"r2_OrderNo":"201608250000004029","r3_Amount":1.00,"r4_ProductName":"php1test",' .
            '"r5_TrxNo":"100216082500248150","ra_Status":100,"rb_Code":"100","rc_CodeMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('9782b1c4799943cab01d6e6d5d54fe2a');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '888100000001792',
            'orderId' => '201608250000004029',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.joinpay.com',
        ];

        $result = '{"hmac":"83F76EC227A4F43FDDCC907C18F268C5","r1_MerchantNo":"888100000001792",' .
            '"r2_OrderNo":"201608250000004029","r3_Amount":1.00,"r4_ProductName":"php1test",' .
            '"r5_TrxNo":"100216082500248150","ra_Status":100,"rb_Code":"100","rc_CodeMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $joinpay = new Joinpay();
        $joinpay->setContainer($this->container);
        $joinpay->setClient($this->client);
        $joinpay->setResponse($response);
        $joinpay->setPrivateKey('9782b1c4799943cab01d6e6d5d54fe2a');
        $joinpay->setOptions($options);
        $joinpay->paymentTracking();
    }
}
