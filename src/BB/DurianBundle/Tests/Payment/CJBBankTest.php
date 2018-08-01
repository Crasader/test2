<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CJBBank;
use Buzz\Message\Response;

class CJBBankTest extends DurianTestCase
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

        $cJBBank = new CJBBank();
        $cJBBank->getVerifyData();
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

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = ['number' => ''];

        $cJBBank->setOptions($sourceData);
        $cJBBank->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = [
            'number' => '1',
            'amount' => '10000',
            'orderId' => '20140113161012',
            'notify_url' => 'http://CJBBank.returnUrl.php',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $cJBBank->setOptions($sourceData);
        $cJBBank->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '1',
            'amount' => '10000',
            'orderId' => '20140113161012',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '220',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');
        $cJBBank->setOptions($sourceData);
        $encodeData = $cJBBank->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('1', $encodeData['p1_MerId']);
        $this->assertEquals('10000.00', $encodeData['p3_Amt']);
        $this->assertEquals('20140113161012', $encodeData['p2_Order']);
        $this->assertEquals($notifyUrl, $encodeData['p8_Url']);
        $this->assertEquals('HZBANK-KJB-B2C', $encodeData['pd_FrpId']);
        $this->assertEquals('93835478B190DDD1738BA8D7A099F597', $encodeData['hmac']);
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

        $cJBBank = new CJBBank();

        $cJBBank->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數
     */
    public function testVerifyWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = [
            'p1_MerId' => '10036',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '97b252d52ed773eaafe47276d1c99f4f'
        ];

        $cJBBank->setOptions($sourceData);
        $cJBBank->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試hmac:加密簽名)
     */
    public function testVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = [
            'p1_MerId' => '10036',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2'
        ];

        $cJBBank->setOptions($sourceData);
        $cJBBank->verifyOrderPayment([]);
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

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = [
            'p1_MerId' => '10036',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '97b252d52ed773eaafe47276d1c99f4f'
        ];

        $cJBBank->setOptions($sourceData);
        $cJBBank->verifyOrderPayment([]);
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

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = [
            'p1_MerId' => '10036',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '0',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => 'B983F852D22C22A09EDB2E8958B48080'
        ];

        $cJBBank->setOptions($sourceData);
        $cJBBank->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = [
            'p1_MerId' => '10036',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '7FED50FBED40996B676E8D70132CCB4B'
        ];

        $entry = ['id' => '20140102030405006'];

        $cJBBank->setOptions($sourceData);
        $cJBBank->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = [
            'p1_MerId' => '10036',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '7FED50FBED40996B676E8D70132CCB4B'
        ];

        $entry = [
            'id' => '20140113161012',
            'amount' => '12345.6000'
        ];

        $cJBBank->setOptions($sourceData);
        $cJBBank->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $cJBBank = new CJBBank();
        $cJBBank->setPrivateKey('1234');

        $sourceData = [
            'p1_MerId' => '10036',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '7FED50FBED40996B676E8D70132CCB4B'
        ];

        $entry = [
            'id' => '20140113161012',
            'amount' => '1234.5600'
        ];

        $cJBBank->setOptions($sourceData);
        $cJBBank->verifyOrderPayment($entry);

        $this->assertEquals('success', $cJBBank->getMsg());
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

        $cjbBank = new CJBBank();
        $cjbBank->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入number
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->paymentTracking();
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
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數
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

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '78473DB678841A9B0051EB42816A6C7A'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '78473DB678841A9B0051EB42816A6C7A'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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
            'hmac' => '8A8A983B23707F51392F5789D7CCBCA4'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'INIT',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '4C7CC9D0DF95BE62418ED703991B503E'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'ING',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'B8449A8F72654E13922896AA5D388FC3'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => '926167BB1A04066C1CC9EBED4F6E2DE9'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r8_MP' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0',
            'hmac' => 'E363BD199A17FA933F75F5B5E28C41E3'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com',
            'amount' => '100'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = [
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
            'hmac' => 'E363BD199A17FA933F75F5B5E28C41E3'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'gb2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cjbbank.com',
            'amount' => '1234.56'
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setContainer($this->container);
        $cjbBank->setClient($this->client);
        $cjbBank->setResponse($response);
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTracking();
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

        $cjbBank = new CJBBank();
        $cjbBank->getPaymentTrackingData();
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

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->getPaymentTrackingData();
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
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($options);
        $cjbBank->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '10036',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.kjb88.com',
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($options);
        $trackingData = $cjbBank->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/bankinterface/queryOrd', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.www.kjb88.com', $trackingData['headers']['Host']);

        $this->assertEquals('QueryOrdDetail', $trackingData['form']['p0_Cmd']);
        $this->assertEquals('10036', $trackingData['form']['p1_MerId']);
        $this->assertEquals('20140113161012', $trackingData['form']['p2_Order']);
        $this->assertEquals('26E657A78D1241C1D1204EA18B2564C0', $trackingData['form']['hmac']);
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

        $cjbBank = new CJBBank();
        $cjbBank->paymentTrackingVerify();
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

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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
            'hmac' => '78473DB678841A9B0051EB42816A6C7A'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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
            'hmac' => '8A8A983B23707F51392F5789D7CCBCA4'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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
            'hmac' => '4C7CC9D0DF95BE62418ED703991B503E'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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
            'hmac' => 'B8449A8F72654E13922896AA5D388FC3'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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
            'hmac' => '926167BB1A04066C1CC9EBED4F6E2DE9'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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
            'hmac' => 'E363BD199A17FA933F75F5B5E28C41E3'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 100
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
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
            'hmac' => 'E363BD199A17FA933F75F5B5E28C41E3'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 1234.56
        ];

        $cjbBank = new CJBBank();
        $cjbBank->setPrivateKey('1234');
        $cjbBank->setOptions($sourceData);
        $cjbBank->paymentTrackingVerify();
    }
}
