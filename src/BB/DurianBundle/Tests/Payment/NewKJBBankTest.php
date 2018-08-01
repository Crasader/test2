<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewKJBBank;
use Buzz\Message\Response;

class NewKJBBankTest extends DurianTestCase
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

        $newKJBBank = new NewKJBBank();
        $newKJBBank->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = ['number' => ''];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->getVerifyData();
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

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'amount' => '0.05',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=32768&hallid=6',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'amount' => '0.05',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1', //1 => "ICBC-KJB-B2C"
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');
        $newKJBBank->setOptions($sourceData);
        $encodeData = $newKJBBank->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertSame('0.05', $encodeData['p3_Amt']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertEquals($notifyUrl, $encodeData['p8_Url']);
        $this->assertEquals('ICBC-KJB-B2C', $encodeData['pd_FrpId']);
        $this->assertEquals('F9579919A6D8AFEA10299DE30424E5F9', $encodeData['hmac']);
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

        $newKJBBank = new NewKJBBank();

        $newKJBBank->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testVerifyWithoutTrxId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = [
            'p1_MerId'       => '60006',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r3_Amt'         => '0.05',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '',
            'r6_Order'       => '201404210000000003',
            'r7_Uid'         => '0',
            'r8_MP'          => '',
            'r9_BType'       => '1',
            'rb_bankid'      => 'ICBC-KJB-B2C',
            'ro_bankorderid' => '20140421110854891371',
            'rp_paydate'     => '1398049759',
            'rq_cardno'      => '',
            'ru_trxtime'     => '1398049807',
            'hmac'           => '22F93F78FDC960A1609083E7E54178E1'
        ];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證沒有必要的參數(測試hmac)
     */
    public function testVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = [
            'p1_MerId'       => '60006',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '2014042111091921060006898',
            'r3_Amt'         => '0.05',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '',
            'r6_Order'       => '201404210000000003',
            'r7_Uid'         => '0',
            'r8_MP'          => '',
            'r9_BType'       => '1',
            'rb_bankid'      => 'ICBC-KJB-B2C',
            'ro_bankorderid' => '20140421110854891371',
            'rp_paydate'     => '1398049759',
            'rq_cardno'      => '',
            'ru_trxtime'     => '1398049807'
        ];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->verifyOrderPayment([]);
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

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = [
            'p1_MerId'       => '60006',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '2014042111091921060006898',
            'r3_Amt'         => '0.05',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '',
            'r6_Order'       => '201404210000000003',
            'r7_Uid'         => '0',
            'r8_MP'          => '',
            'r9_BType'       => '1',
            'rb_bankid'      => 'ICBC-KJB-B2C',
            'ro_bankorderid' => '20140421110854891371',
            'rp_paydate'     => '1398049759',
            'rq_cardno'      => '',
            'ru_trxtime'     => '1398049807',
            'hmac'           => '97B252D52ED773EAAFE47276D1C99F4F'
        ];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->verifyOrderPayment([]);
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

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = [
            'p1_MerId'       => '60006',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '0',
            'r2_TrxId'       => '2014042111091921060006898',
            'r3_Amt'         => '0.05',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '',
            'r6_Order'       => '201404210000000003',
            'r7_Uid'         => '0',
            'r8_MP'          => '',
            'r9_BType'       => '1',
            'rb_bankid'      => 'ICBC-KJB-B2C',
            'ro_bankorderid' => '20140421110854891371',
            'rp_paydate'     => '1398049759',
            'rq_cardno'      => '',
            'ru_trxtime'     => '1398049807',
            'hmac'           => '86B24E481CCF2239A52E2D6D3ADB1977'
        ];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = [
            'p1_MerId'       => '60006',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '2014042111091921060006898',
            'r3_Amt'         => '0.05',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '',
            'r6_Order'       => '201404210000000003',
            'r7_Uid'         => '0',
            'r8_MP'          => '',
            'r9_BType'       => '1',
            'rb_bankid'      => 'ICBC-KJB-B2C',
            'ro_bankorderid' => '20140421110854891371',
            'rp_paydate'     => '1398049759',
            'rq_cardno'      => '',
            'ru_trxtime'     => '1398049807',
            'hmac'           => '22F93F78FDC960A1609083E7E54178E1'
        ];

        $entry = ['id' => '20140102030405006'];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = [
            'p1_MerId'       => '60006',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '2014042111091921060006898',
            'r3_Amt'         => '0.05',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '',
            'r6_Order'       => '201404210000000003',
            'r7_Uid'         => '0',
            'r8_MP'          => '',
            'r9_BType'       => '1',
            'rb_bankid'      => 'ICBC-KJB-B2C',
            'ro_bankorderid' => '20140421110854891371',
            'rp_paydate'     => '1398049759',
            'rq_cardno'      => '',
            'ru_trxtime'     => '1398049807',
            'hmac'           => '22F93F78FDC960A1609083E7E54178E1'
        ];

        $entry = [
            'id' => '201404210000000003',
            'amount' => '12345.6000'
        ];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('uYWRygwssTdLCXeD24inaiCSZgVsY3qY4fRKLWqD0KQZhJlEyvEXjaXKuj0mz0ZF');

        $sourceData = [
            'p1_MerId'       => '60006',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '2014042111091921060006898',
            'r3_Amt'         => '0.05',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '',
            'r6_Order'       => '201404210000000003',
            'r7_Uid'         => '0',
            'r8_MP'          => '',
            'r9_BType'       => '1',
            'rb_bankid'      => 'ICBC-KJB-B2C',
            'ro_bankorderid' => '20140421110854891371',
            'rp_paydate'     => '1398049759',
            'rq_cardno'      => '',
            'ru_trxtime'     => '1398049807',
            'hmac'           => '22F93F78FDC960A1609083E7E54178E1'
        ];

        $entry = [
            'id' => '201404210000000003',
            'amount' => '0.0500'
        ];

        $newKJBBank->setOptions($sourceData);
        $newKJBBank->verifyOrderPayment($entry);

        $this->assertEquals('success', $newKJBBank->getMsg());
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

        $newKJBBank = new NewKJBBank();
        $newKJBBank->paymentTracking();
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

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->paymentTracking();
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
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
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

        $response = new Response();
        $response->setContent('null');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數hmac
     */
    public function testPaymentTrackingResultWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
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

        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '55223A6F0B59D786493CB51AEC9E4B52'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
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

        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '50',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '6E3842DF763DD1D89522D230AE72DF0B'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
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
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '99',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'DE9AF969807ACD07F136F46EB85F9C33'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
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

        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'INIT',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '8C6D4ED208940E8232DBD98833C40681'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
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
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'ING',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '92BC89BC032AEBBD64EB7FE8796D64F4'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單已取消
     */
    public function testTrackingReturnOrderHasBeenCancelled()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order has been cancelled',
            180063
        );

        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'A72AE2F77A79314ACCA79DF443F992F6'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗(回傳PayStatus非Success)
     */
    public function testTrackingReturnPaymentFailureWithPayStatusError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'FAILED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'EC3AC5A651CE2DC718D2DE038AE2C16E'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
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
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'C448E2DBCF9FCD9DAB90BAB9D80D88A0'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com',
            'amount' => '1.234'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'C448E2DBCF9FCD9DAB90BAB9D80D88A0'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newkjbbank.com',
            'amount' => '0.05'
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setContainer($this->container);
        $newKJBBank->setClient($this->client);
        $newKJBBank->setResponse($response);
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newKJBBank = new NewKJBBank();
        $newKJBBank->getPaymentTrackingData();
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

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->getPaymentTrackingData();
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

        $options = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($options);
        $newKJBBank->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '60006',
            'orderId' => '201404210000000003',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.kjb99.com',
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($options);
        $trackingData = $newKJBBank->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/bankinterface/queryOrd', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.www.kjb99.com', $trackingData['headers']['Host']);

        $this->assertEquals('QueryOrdDetail', $trackingData['form']['p0_Cmd']);
        $this->assertEquals('60006', $trackingData['form']['p1_MerId']);
        $this->assertEquals('201404210000000003', $trackingData['form']['p2_Order']);
        $this->assertEquals('D11DDEA312882451D72C181F224207C6', $trackingData['form']['hmac']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newKJBBank = new NewKJBBank();
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = ['content' => ''];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數hmac
     */
    public function testPaymentTrackingVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '7FED50FBED40996B676E8D70132CCB4B',
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳訂單不存在
     */
    public function testPaymentTrackingVerifyOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '50',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '8AE80C72FEDE2C36CADD8E84969CE0BC'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '0',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '02F9B2B614E9B06BCB9ADF8981FC0539'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'INIT',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '65F4FE82EB11EFF74848AC55D082881D'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳訂單處理中
     */
    public function testPaymentTrackingVerifyOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'ING',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'F65FC3B2DEE51B1AA3120303DEC2D961'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單已取消
     */
    public function testPaymentTrackingVerifyOrderHasBeenCancelled()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order has been cancelled',
            180063
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '6119146D06701BDCFC86A6D5598093F6'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳支付失敗(回傳PayStatus非Success)
     */
    public function testPaymentTrackingVerifyWithPayStatusError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '2014042111091921060006898',
            'r3_Amt' => '0.05',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201404210000000003',
            'r8_MP' => '',
            'rb_PayStatus' => 'FAILED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'EC3AC5A651CE2DC718D2DE038AE2C16E'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '73C7CB5A15A77F54186F79CE5C5F88D8'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 100
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '73C7CB5A15A77F54186F79CE5C5F88D8'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 1234.56
        ];

        $newKJBBank = new NewKJBBank();
        $newKJBBank->setPrivateKey('fdsiojosdgdjioioj');
        $newKJBBank->setOptions($sourceData);
        $newKJBBank->paymentTrackingVerify();
    }
}
