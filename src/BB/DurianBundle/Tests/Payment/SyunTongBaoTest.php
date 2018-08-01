<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SyunTongBao;
use Buzz\Message\Response;

class SyunTongBaoTest extends DurianTestCase
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

        $syunTongBao = new SyunTongBao();
        $syunTongBao->getVerifyData();
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

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->getVerifyData();
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
            'number' => '2072',
            'paymentVendorId' => '7',
            'amount' => '0.01',
            'orderId' => '201705150000001335',
            'notify_url' => 'http://pay.my/',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '2072',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201705150000001335',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $encodeData = $syunTongBao->getVerifyData();

        $this->assertEquals('Buy', $encodeData['p0_Cmd']);
        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertEquals($sourceData['amount'], $encodeData['p3_Amt']);
        $this->assertEquals('CNY', $encodeData['p4_Cur']);
        $this->assertEquals('', $encodeData['p5_Pid']);
        $this->assertEquals('', $encodeData['p6_Pcat']);
        $this->assertEquals('', $encodeData['p7_Pdesc']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['p8_Url']);
        $this->assertEquals('0', $encodeData['p9_SAF']);
        $this->assertEquals('', $encodeData['pa_MP']);
        $this->assertEquals('ICBC', $encodeData['pd_FrpId']);
        $this->assertEquals('1', $encodeData['pr_NeedResponse']);
        $this->assertEquals('50f18849aaaac2aa079d7d388bb04f9e', $encodeData['hmac']);
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

        $syunTongBao = new SyunTongBao();
        $syunTongBao->verifyOrderPayment([]);
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

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳Hmac
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'p1_MerId' => '2187',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '2017061416535102807984',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201706140000002391',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '6/14/2017 4:54:54 PM',
            'rp_PayDate' => '1',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時hmac簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'p1_MerId' => '2187',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '2017061416535102807984',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201706140000002391',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '6/14/2017 4:54:54 PM',
            'rp_PayDate' => '1',
            'hmac' => 'testwronghmac',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->verifyOrderPayment([]);
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
            'p1_MerId' => '2187',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '99',
            'r2_TrxId' => '2017061416535102807984',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201706140000002391',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '6/14/2017 4:54:54 PM',
            'hmac' => '37bb5ea0af97c9e6ff075dd263d37be4',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->verifyOrderPayment([]);
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
            'p1_MerId' => '2187',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '2017061416535102807984',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201706140000002391',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '6/14/2017 4:54:54 PM',
            'hmac' => '911a9c3499910535ac2c880e48c0af86',
        ];

        $entry = ['id' => '2017061416535102807985'];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->verifyOrderPayment($entry);
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
            'p1_MerId' => '2187',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '2017061416535102807984',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201706140000002391',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '6/14/2017 4:54:54 PM',
            'hmac' => '911a9c3499910535ac2c880e48c0af86',
        ];

        $entry = [
            'id' => '201706140000002391',
            'amount' => '0.05',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'p1_MerId' => '2187',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '2017061416535102807984',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201706140000002391',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '6/14/2017 4:54:54 PM',
            'hmac' => '911a9c3499910535ac2c880e48c0af86',
        ];

        $entry = [
            'id' => '201706140000002391',
            'amount' => '0.01',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $syunTongBao->getMsg());
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

        $syunTongBao = new SyunTongBao();
        $syunTongBao->paymentTracking();
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

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->paymentTracking();
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
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為缺少回傳參數
     */
    public function testTrackingReturnWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果Hmac為空
     */
    public function testTrackingReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
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

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>559c6cbfa859a3c8366752146cf684c8</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回請求參數錯誤
     */
    public function testTrackingReturnSubmitTheParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>0</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>6c846dc248f226f709d17071cc828f03</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢商戶訂單號無效
     */
    public function testTrackingReturnOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>50</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>da84ed618f1f68bb0379a4976446eef4</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
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

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>99</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>FAIL</rb_PayStatus><hmac>53fa3c51e64b58d0ff41c3f1bff2dc07</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單未支付
     */
    public function testTrackingReturnWithUnpaid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>INIT</rb_PayStatus><hmac>4012510bdd55fd2b1cf472d8d46d6f37</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為付款失敗
     */
    public function testTrackingReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>ERROR</rb_PayStatus><hmac>5df6162c8119e58ace86afd7be9d8256</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>eecb6ae7ae9c3fedac4bbfef209ed78b</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002392',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingReturnWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>eecb6ae7ae9c3fedac4bbfef209ed78b</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.05,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>eecb6ae7ae9c3fedac4bbfef209ed78b</hmac></QueryOrdDetail>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setContainer($this->container);
        $syunTongBao->setClient($this->client);
        $syunTongBao->setResponse($response);
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少私鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $syunTongBao = new SyunTongBao();
        $syunTongBao->getPaymentTrackingData();
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

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
        ];

        $result = [
            'p0_Cmd' => 'QueryOrdDetail',
            'p1_MerId' => '2187',
            'p2_Order' => '201706140000002391',
            'hmac' => 'e65e817a2d7f1fb1535325fd4861cfde',
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $trackingData = $syunTongBao->getPaymentTrackingData();

        $this->assertEquals($result, $trackingData['form']);
        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/GateWay/ReceiveOrderSelect.aspx', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少私鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $syunTongBao = new SyunTongBao();
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少Hmac
     */
    public function testPaymentTrackingVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>ghjghjghjghjghjghj</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為請求參數錯誤
     */
    public function testPaymentTrackingVerifySubmitTheParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>0</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>6c846dc248f226f709d17071cc828f03</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為商戶訂單號無效
     */
    public function testPaymentTrackingVerifyOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>50</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>da84ed618f1f68bb0379a4976446eef4</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>99</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>FAIL</rb_PayStatus><hmac>53fa3c51e64b58d0ff41c3f1bff2dc07</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單未支付
     */
    public function testPaymentTrackingVerifyWithUnpaid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>INIT</rb_PayStatus><hmac>4012510bdd55fd2b1cf472d8d46d6f37</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單未支付
     */
    public function testPaymentTrackingVerifyPaymentFail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>ERROR</rb_PayStatus><hmac>5df6162c8119e58ace86afd7be9d8256</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>eecb6ae7ae9c3fedac4bbfef209ed78b</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002392',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單金額錯誤
     */
    public function testPaymentTrackingVerifyWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>eecb6ae7ae9c3fedac4bbfef209ed78b</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.05,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $result = '<QueryOrdDetail><r0_Cmd>QueryOrdDetail</r0_Cmd><r1_Code>1</r1_Code>' .
            '<r2_TrxId>2017061416535102807984</r2_TrxId><r3_Amt>0.01</r3_Amt><r4_Cur>RMB</r4_Cur>' .
            '<r5_Pid></r5_Pid><r6_Order>201706140000002391</r6_Order><r8_MP></r8_MP>' .
            '<rb_PayStatus>SUCCESS</rb_PayStatus><hmac>eecb6ae7ae9c3fedac4bbfef209ed78b</hmac></QueryOrdDetail>';

        $sourceData = [
            'number' => '2187',
            'orderId' => '201706140000002391',
            'amount' => 0.01,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.master-egg.cn',
            'content' => $result,
        ];

        $syunTongBao = new SyunTongBao();
        $syunTongBao->setPrivateKey('test');
        $syunTongBao->setOptions($sourceData);
        $syunTongBao->paymentTrackingVerify();
    }
}
