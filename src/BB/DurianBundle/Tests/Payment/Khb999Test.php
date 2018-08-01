<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Khb999;
use Buzz\Message\Response;

class Khb999Test extends DurianTestCase
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

        $khb = new Khb999();
        $khb->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->getVerifyData();
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

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'amount' => '1',
            'notify_url' => 'http://103.240.216.201/pay/return.php?pay_system=1234&hallid=6',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'amount' => '1',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $encodeData = $khb->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('1234', $encodeData['p1_MerId']);
        $this->assertEquals('201502110000000123', $encodeData['p2_Order']);
        $this->assertEquals('1.00', $encodeData['p3_Amt']);
        $this->assertEquals($notifyUrl, $encodeData['p8_Url']);
        $this->assertEquals('ICBC', $encodeData['pd_FrpId']);
        $this->assertEquals('941F84E7F93AA0A74E0A5B1EDDD5997F', $encodeData['hmac']);
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

        $khb = new Khb999();

        $khb->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithoutMerId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');

        $khb->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳hmac
     */
    public function testVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'p1_MerId' => '1234',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201502111223554468011353',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201502110000000123',
            'r7_Uid' => '35660',
            'r8_MP' => 'php1test',
            'r9_BType' => '2'
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->verifyOrderPayment([]);
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
            'p1_MerId' => '1234',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201502111223554468011353',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201502110000000123',
            'r7_Uid' => '35660',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => '54a6c92742df718b92c1ef216cb1a2d4'
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->verifyOrderPayment([]);
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
            'p1_MerId' => '1234',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '0',
            'r2_TrxId' => '201502111223554468011353',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201502110000000123',
            'r7_Uid' => '35660',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => 'EB536674115EF08CD6354FB5CDECFA2A'
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'p1_MerId' => '1234',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201502111223554468011353',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201502110000000123',
            'r7_Uid' => '35660',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => 'CE3B98177EDBA33E92D0D50885E430E0'
        ];

        $entry = ['id' => '201502110000000321'];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'p1_MerId' => '1234',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201502111223554468011353',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201502110000000123',
            'r7_Uid' => '35660',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => 'CE3B98177EDBA33E92D0D50885E430E0'
        ];

        $entry = [
            'id' => '201502110000000123',
            'amount' => '9900.0000'
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'p1_MerId' => '1234',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201502111223554468011353',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201502110000000123',
            'r7_Uid' => '35660',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => 'CE3B98177EDBA33E92D0D50885E430E0'
        ];

        $entry = [
            'id' => '201502110000000123',
            'amount' => '1.0000'
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->verifyOrderPayment($entry);
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

        $khb = new Khb999();
        $khb->paymentTracking();
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

        $khb = new Khb999();
        $khb->setPrivateKey('123456789');
        $khb->paymentTracking();
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
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=123456789";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=50\n" .
            "r2_TrxId=\n" .
            "r3_Amt=\n" .
            "r4_Cur=\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=\n" .
            "r8_MP=\n" .
            "rb_PayStatus=\n" .
            "rc_RefundCount=\n" .
            "rd_RefundAmt=\n" .
            "hmac=25757625831F32938B9664E57D123B2E";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=2\n" .
            "r2_TrxId=\n" .
            "r3_Amt=\n" .
            "r4_Cur=\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=\n" .
            "r8_MP=\n" .
            "rb_PayStatus=\n" .
            "rc_RefundCount=\n" .
            "rd_RefundAmt=\n" .
            "hmac=4D3034333038E68302796CE92AC9CC67";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=INIT\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=26CAFECB85280699F15D88FADD4F2BAC";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=ING\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=705BC50BEBE4D7B7F693CFF233C65375";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=CANCELED\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=1785EF95E9EB1E7646650985D7F3CC1D";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=FAILED\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=6B506A643CA6D170CF6AA07800C76857";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=71C6D1BF688EB2170576E8C4242B6E17";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com',
            'amount' => '1000.00'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=71C6D1BF688EB2170576E8C4242B6E17";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.khb999.com',
            'amount' => '1.00'
        ];

        $khb = new Khb999();
        $khb->setContainer($this->container);
        $khb->setClient($this->client);
        $khb->setResponse($response);
        $khb->setPrivateKey('123456789');
        $khb->setOptions($sourceData);
        $khb->paymentTracking();
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

        $khb = new Khb999();
        $khb->getPaymentTrackingData();
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

        $khb = new Khb999();
        $khb->setPrivateKey('123456789');
        $khb->getPaymentTrackingData();
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
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('123456789');
        $khb->setOptions($options);
        $khb->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '1234',
            'orderId' => '201502110000000123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.szzfkh.com',
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('123456789');
        $khb->setOptions($options);
        $trackingData = $khb->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/bankinterface/queryOrd', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.payment.szzfkh.com', $trackingData['headers']['Host']);

        $this->assertEquals('QueryOrdDetail', $trackingData['form']['p0_Cmd']);
        $this->assertEquals('1234', $trackingData['form']['p1_MerId']);
        $this->assertEquals('201502110000000123', $trackingData['form']['p2_Order']);
        $this->assertEquals('70D38149047C7377D8FCA801472C227F', $trackingData['form']['hmac']);
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

        $khb = new Khb999();
        $khb->paymentTrackingVerify();
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

        $khb = new Khb999();
        $khb->setPrivateKey('1234');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0";
        $sourceData = ['content' => $result];

        $khb = new Khb999();
        $khb->setPrivateKey('1234');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=123456789";
        $sourceData = ['content' => $result];

        $khb = new Khb999();
        $khb->setPrivateKey('1234');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=50\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=94788EC2FDD07D828B004459055F57BD";
        $sourceData = ['content' => $result];

        $khb = new Khb999();
        $khb->setPrivateKey('1234');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=99\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=4055153FF8F5C02219D0842AF46C85AC";
        $sourceData = ['content' => $result];

        $khb = new Khb999();
        $khb->setPrivateKey('1234');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=914420747652951D\n" .
            "r3_Amt=1.0\n" .
            "r4_Cur=CNY\n" .
            "r5_Pid=\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=php1test\n" .
            "rb_PayStatus=INIT\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0\n" .
            "hmac=37945FFE016E13DB90828480AEA466F8";
        $sourceData = ['content' => $result];

        $khb = new Khb999();
        $khb->setPrivateKey('1234');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=201502111223554468011353\n" .
            "r3_Amt=0.01\n" .
            "r4_Cur=RMB\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=rabbit\n" .
            "rb_PayStatus=ING\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0000\n" .
            "hmac=72E3162722D662438312FE0F5C7563A4";
        $sourceData = ['content' => $result];

        $khb = new Khb999();
        $khb->setPrivateKey('1234');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=201502111223554468011353\n" .
            "r3_Amt=0.01\n" .
            "r4_Cur=RMB\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=rabbit\n" .
            "rb_PayStatus=CANCELED\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0000\n" .
            "hmac=CE780CC9997967B44D075A90BBD85D3D";
        $sourceData = ['content' => $result];

        $khb = new Khb999();
        $khb->setPrivateKey('1234');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢付失敗(回傳PayStatus非Success)
     */
    public function testPaymentTrackingVerifyPaymentFailureWithPayStatusError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=201502111223554468011353\n" .
            "r3_Amt=0.01\n" .
            "r4_Cur=RMB\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=rabbit\n" .
            "rb_PayStatus=FAILED\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0000\n" .
            "hmac=DBAC6E47F09C531A632B288F05FB4E18";
        $sourceData = ['content' => $result];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
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

        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=201502111223554468011353\n" .
            "r3_Amt=0.01\n" .
            "r4_Cur=RMB\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=rabbit\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0000\n" .
            "hmac=E8603560E8B83F40D692D1B3D69F380D";
        $sourceData = [
            'content' => $result,
            'amount' => 100
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $result = "r0_Cmd=QueryOrdDetail\n" .
            "r1_Code=1\n" .
            "r2_TrxId=201502111223554468011353\n" .
            "r3_Amt=0.01\n" .
            "r4_Cur=RMB\n" .
            "r5_Pid=\n" .
            "r6_Order=201502110000000123\n" .
            "r8_MP=rabbit\n" .
            "rb_PayStatus=SUCCESS\n" .
            "rc_RefundCount=0\n" .
            "rd_RefundAmt=0.0000\n" .
            "hmac=E8603560E8B83F40D692D1B3D69F380D";
        $sourceData = [
            'content' => $result,
            'amount' => 0.01
        ];

        $khb = new Khb999();
        $khb->setPrivateKey('1234567890');
        $khb->setOptions($sourceData);
        $khb->paymentTrackingVerify();
    }
}
