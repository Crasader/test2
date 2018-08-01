<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Ehking;
use Buzz\Message\Response;

class EhkingTest extends DurianTestCase
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

        $ehking = new Ehking();
        $ehking->getVerifyData();
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

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = ['number' => ''];

        $ehking->setOptions($sourceData);
        $ehking->getVerifyData();
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

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php?pay_system=48542&hallid=206',
            'username' => 'php1test',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ehking->setOptions($sourceData);
        $ehking->getVerifyData();
    }

    /**
     * 測試加密時PrivateKey長度超過64
     */
    public function testGetEncodeDataWithPrivateKeyLength()
    {
        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de12345asjhldjnxiosuouj1mkljoi4u30948');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'merchantId' => '48542',
            'domain' => '206',
        ];

        $ehking->setOptions($sourceData);
        $encodeData = $ehking->getVerifyData();

        $url = 'http://pay.rasfasl.com/pay/pay_response.php?pay_system=48542&hallid=206';

        $this->assertEquals('3171112543353101', $encodeData['p1_MerId']);
        $this->assertEquals('201411141317192331', $encodeData['p2_Order']);
        $this->assertSame('1.00', $encodeData['p4_Amt']);
        $this->assertEquals($url, $encodeData['p8_Url']);
        $this->assertEquals('php1test', $encodeData['p9_MP']);
        $this->assertEquals('ICBC-NET-B2C', $encodeData['pa_FrpId']);
        $this->assertEquals('975a41b353ef380512491e02b0969369', $encodeData['hmac']);
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'amount' => '1',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $encodeData = $ehking->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('3171112543353101', $encodeData['p1_MerId']);
        $this->assertEquals('201411141317192331', $encodeData['p2_Order']);
        $this->assertSame('1.00', $encodeData['p4_Amt']);
        $this->assertEquals($notifyUrl, $encodeData['p8_Url']);
        $this->assertEquals('php1test', $encodeData['p9_MP']);
        $this->assertEquals('ICBC-NET-B2C', $encodeData['pa_FrpId']);
        $this->assertEquals('bdcccb5283d48fb3e0e7686602ed99b6', $encodeData['hmac']);
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

        $ehking = new Ehking();

        $ehking->verifyOrderPayment([]);
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

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $ehking->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳hmac(加密簽名)
     */
    public function testVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'p1_MerId'       => '3171112543353101',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'CNY',
            'r5_Pid'         => '',
            'r6_Order'       => '201411141317192331',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ro_BankOrderId' => '24351736611405',
            'rp_PayDate'     => '' // r9_BType = 2時, rp_PayDate為空字串
        ];

        $ehking->setOptions($sourceData);
        $ehking->verifyOrderPayment([]);
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

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'p1_MerId'       => '3171112543353101',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'CNY',
            'r5_Pid'         => '',
            'r6_Order'       => '201411141317192331',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ro_BankOrderId' => '24351736611405',
            'rp_PayDate'     => '',
            'hmac'           => '54a6c92742df718b92c1ef216cb1a2d4'
        ];

        $ehking->setOptions($sourceData);
        $ehking->verifyOrderPayment([]);
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

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'p1_MerId'       => '3171112543353101',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '0',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'CNY',
            'r5_Pid'         => '',
            'r6_Order'       => '201411141317192331',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ro_BankOrderId' => '24351736611405',
            'rp_PayDate'     => '',
            'hmac'           => '0bda17eb558561c9442bd5e14cf3621a'
        ];

        $ehking->setOptions($sourceData);
        $ehking->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'p1_MerId'       => '3171112543353101',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'CNY',
            'r5_Pid'         => '',
            'r6_Order'       => '201411141317192331',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ro_BankOrderId' => '24351736611405',
            'rp_PayDate'     => '',
            'hmac'           => '5cd007c4732ec537cd70f37f6f993530'
        ];

        $entry = ['id' => '201405020016748610'];

        $ehking->setOptions($sourceData);
        $ehking->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'p1_MerId'       => '3171112543353101',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'CNY',
            'r5_Pid'         => '',
            'r6_Order'       => '201411141317192331',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ro_BankOrderId' => '24351736611405',
            'rp_PayDate'     => '',
            'hmac'           => '5cd007c4732ec537cd70f37f6f993530'
        ];

        $entry = [
            'id' => '201411141317192331',
            'amount' => '9900.0000'
        ];

        $ehking->setOptions($sourceData);
        $ehking->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'p1_MerId'       => '3171112543353101',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'CNY',
            'r5_Pid'         => '',
            'r6_Order'       => '201411141317192331',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ro_BankOrderId' => '24351736611405',
            'rp_PayDate'     => '',
            'hmac'           => '5cd007c4732ec537cd70f37f6f993530'
        ];

        $entry = [
            'id' => '201411141317192331',
            'amount' => '1.0000'
        ];

        $ehking->setOptions($sourceData);
        $ehking->verifyOrderPayment($entry);

        $this->assertEquals('success', $ehking->getMsg());
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

        $ehking = new Ehking();
        $ehking->paymentTracking();
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

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->paymentTracking();
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
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '123456789'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '50',
            'r2_TrxId' => '',
            'r3_Amt' => '',
            'r4_Cur' => '',
            'r6_Order' => '',
            'r8_MP' => '',
            'rb_PayStatus' => '',
            'rc_RefundCount' => '',
            'rd_RefundAmt' => '',
            'hmac' => '7109f15b4cb0c3eec317eec638a3d6b1'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '2',
            'r2_TrxId' => '',
            'r3_Amt' => '',
            'r4_Cur' => '',
            'r6_Order' => '',
            'r8_MP' => '',
            'rb_PayStatus' => '',
            'rc_RefundCount' => '',
            'rd_RefundAmt' => '',
            'hmac' => '90da9d97a61b84a377d9a1023a6e912d'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'INIT',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '47dc9673539c95b08f55eee78168cb23'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '142d95319c5edf8aa708459a87dda450'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗(回傳PayStatus非Success)
     */
    public function testTrackingReturnPaymentFailureWithPayStatusNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'p1_MerId' => '3171112543353101',
            'r1_Code'  => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'FAILED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '2944fb912474f93f4ea24e084530120c'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '037869d886cc86fc7e17037e275e228a'
        ];
        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com',
            'amount' => '1000.00'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '037869d886cc86fc7e17037e275e228a'
        ];
        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ehking.com',
            'amount' => '1.00'
        ];

        $ehking = new Ehking();
        $ehking->setContainer($this->container);
        $ehking->setClient($this->client);
        $ehking->setResponse($response);
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTracking();
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

        $ehking = new Ehking();
        $ehking->getPaymentTrackingData();
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

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->getPaymentTrackingData();
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
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($options);
        $ehking->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.ehkpay.ehking.com',
        ];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($options);
        $trackingData = $ehking->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/gateway/controller.action', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.ehkpay.ehking.com', $trackingData['headers']['Host']);

        $this->assertEquals('QueryOrdDetail', $trackingData['form']['p0_Cmd']);
        $this->assertEquals('3171112543353101', $trackingData['form']['p1_MerId']);
        $this->assertEquals('201411141317192331', $trackingData['form']['p2_Order']);
        $this->assertEquals('1282c1c6f4cb47bc7159cde47762c4f2', $trackingData['form']['hmac']);
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

        $ehking = new Ehking();
        $ehking->paymentTrackingVerify();
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

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '123456789'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '50',
            'r2_TrxId' => '',
            'r3_Amt' => '',
            'r4_Cur' => '',
            'r6_Order' => '',
            'r8_MP' => '',
            'rb_PayStatus' => '',
            'rc_RefundCount' => '',
            'rd_RefundAmt' => '',
            'hmac' => '7109f15b4cb0c3eec317eec638a3d6b1'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code'  => '2',
            'r2_TrxId' => '',
            'r3_Amt' => '',
            'r4_Cur' => '',
            'r6_Order' => '',
            'r8_MP' => '',
            'rb_PayStatus' => '',
            'rc_RefundCount' => '',
            'rd_RefundAmt' => '',
            'hmac' => '90da9d97a61b84a377d9a1023a6e912d'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'INIT',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '47dc9673539c95b08f55eee78168cb23'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '142d95319c5edf8aa708459a87dda450'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗(回傳PayStatus非Success)
     */
    public function testPaymentTrackingVerifyPaymentFailureWithPayStatusNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'p1_MerId' => '3171112543353101',
            'r1_Code'  => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'FAILED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '2944fb912474f93f4ea24e084530120c'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
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
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '037869d886cc86fc7e17037e275e228a'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 100
        ];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'p1_MerId' => '3171112543353101',
            'r1_Code' => '1',
            'r2_TrxId' => '914420747652951D',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'CNY',
            'r6_Order' => '201411141317192331',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '037869d886cc86fc7e17037e275e228a'
        ];
        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 1.0
        ];

        $ehking = new Ehking();
        $ehking->setPrivateKey('d330e490553f4f6ea1604856938b43de');
        $ehking->setOptions($sourceData);
        $ehking->paymentTrackingVerify();
    }
}
