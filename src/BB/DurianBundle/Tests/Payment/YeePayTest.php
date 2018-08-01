<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YeePay;
use Buzz\Message\Response;

class YeePayTest extends DurianTestCase
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

        $yeePay = new YeePay();
        $yeePay->getVerifyData();
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = ['number' => ''];

        $yeePay->setOptions($sourceData);
        $yeePay->getVerifyData();
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php?pay_system=48542&hallid=206',
            'paymentVendorId' => '999',
            'username' => 'php1test',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $yeePay->setOptions($sourceData);
        $yeePay->getVerifyData();
    }

    /**
     * 測試加密時PrivateKey長度超過64
     */
    public function testGetEncodeDataWithPrivateKeyLength()
    {
        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j12345');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php?pay_system=48542&hallid=206',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $yeePay->setOptions($sourceData);
        $yeePay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $encodeData = $yeePay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertSame('1.00', $encodeData['p3_Amt']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertEquals($notifyUrl, $encodeData['p8_Url']);
        $this->assertEquals('ICBC-NET-B2C', $encodeData['pd_FrpId']);
        $this->assertEquals('8fec252727c5799d206c8b06ee170d37', $encodeData['hmac']);
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

        $yeePay = new YeePay();

        $yeePay->verifyOrderPayment([]);
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId'       => '10012150139',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '-',
            'r6_Order'       => '201405120018316114',
            'r7_Uid'         => '',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ru_Trxtime'     => '20140512093026',
            'ro_BankOrderId' => '24351736611405',
            'rb_BankId'      => 'ICBC-NET-B2C',
            'rp_PayDate'     => '20140512092956',
            'rq_CardNo'      => '',
            'rq_SourceFee'   => '0.0',
            'rq_TargetFee'   => '0.01',
            'hmac'           => '2c1ef216cb1a2d454a6c92742df718b9'
        ];

        $yeePay->setOptions($sourceData);
        $yeePay->verifyOrderPayment([]);
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId'       => '10012150139',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '-',
            'r6_Order'       => '201405120018316114',
            'r7_Uid'         => '',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ru_Trxtime'     => '20140512093026',
            'ro_BankOrderId' => '24351736611405',
            'rb_BankId'      => 'ICBC-NET-B2C',
            'rp_PayDate'     => '20140512092956',
            'rq_CardNo'      => '',
            'rq_SourceFee'   => '0.0',
            'rq_TargetFee'   => '0.01'
        ];

        $yeePay->setOptions($sourceData);
        $yeePay->verifyOrderPayment([]);
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId'       => '10012150139',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '-',
            'r6_Order'       => '201405120018316114',
            'r7_Uid'         => '',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ru_Trxtime'     => '20140512093026',
            'ro_BankOrderId' => '24351736611405',
            'rb_BankId'      => 'ICBC-NET-B2C',
            'rp_PayDate'     => '20140512092956',
            'rq_CardNo'      => '',
            'rq_SourceFee'   => '0.0',
            'rq_TargetFee'   => '0.01',
            'hmac'           => '54a6c92742df718b92c1ef216cb1a2d4'
        ];

        $yeePay->setOptions($sourceData);
        $yeePay->verifyOrderPayment([]);
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId'       => '10012150139',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '0',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '-',
            'r6_Order'       => '201405120018316114',
            'r7_Uid'         => '',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ru_Trxtime'     => '20140512093026',
            'ro_BankOrderId' => '24351736611405',
            'rb_BankId'      => 'ICBC-NET-B2C',
            'rp_PayDate'     => '20140512092956',
            'rq_CardNo'      => '',
            'rq_SourceFee'   => '0.0',
            'rq_TargetFee'   => '0.01',
            'hmac'           => '1888fa6622b01e322134c99cbc44dbd3'
        ];

        $yeePay->setOptions($sourceData);
        $yeePay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId'       => '10012150139',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '-',
            'r6_Order'       => '201405120018316114',
            'r7_Uid'         => '',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ru_Trxtime'     => '20140512093026',
            'ro_BankOrderId' => '24351736611405',
            'rb_BankId'      => 'ICBC-NET-B2C',
            'rp_PayDate'     => '20140512092956',
            'rq_CardNo'      => '',
            'rq_SourceFee'   => '0.0',
            'rq_TargetFee'   => '0.01',
            'hmac'           => '2c1ef216cb1a2d454a6c92742df718b9'
        ];

        $entry = ['id' => '201405020016748610'];

        $yeePay->setOptions($sourceData);
        $yeePay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId'       => '10012150139',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '-',
            'r6_Order'       => '201405120018316114',
            'r7_Uid'         => '',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ru_Trxtime'     => '20140512093026',
            'ro_BankOrderId' => '24351736611405',
            'rb_BankId'      => 'ICBC-NET-B2C',
            'rp_PayDate'     => '20140512092956',
            'rq_CardNo'      => '',
            'rq_SourceFee'   => '0.0',
            'rq_TargetFee'   => '0.01',
            'hmac'           => '2c1ef216cb1a2d454a6c92742df718b9'
        ];

        $entry = [
            'id' => '201405120018316114',
            'amount' => '9900.0000'
        ];

        $yeePay->setOptions($sourceData);
        $yeePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId'       => '10012150139',
            'r0_Cmd'         => 'Buy',
            'r1_Code'        => '1',
            'r2_TrxId'       => '914292209794231I',
            'r3_Amt'         => '1.0',
            'r4_Cur'         => 'RMB',
            'r5_Pid'         => '-',
            'r6_Order'       => '201405120018316114',
            'r7_Uid'         => '',
            'r8_MP'          => 'php1test',
            'r9_BType'       => '2',
            'ru_Trxtime'     => '20140512093026',
            'ro_BankOrderId' => '24351736611405',
            'rb_BankId'      => 'ICBC-NET-B2C',
            'rp_PayDate'     => '20140512092956',
            'rq_CardNo'      => '',
            'rq_SourceFee'   => '0.0',
            'rq_TargetFee'   => '0.01',
            'hmac'           => '2c1ef216cb1a2d454a6c92742df718b9'
        ];

        $entry = [
            'id' => '201405120018316114',
            'amount' => '1.0000'
        ];

        $yeePay->setOptions($sourceData);
        $yeePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $yeePay->getMsg());
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

        $yeePay = new YeePay();
        $yeePay->paymentTracking();
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->paymentTracking();
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
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '4f7f1b49196ef4a3d0880c69b872f531'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '1e52b5f49059ba5df9283b313fe18328'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '010964f586956faf3892bae73b6cbd84'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'INIT',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '0d67f2f3b09f8279767315943186fdc1'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'AUTHORIZED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => 'f513751bb087f7438b754a85fb6f0a23'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => 'b1d4c2e1b101aac5c62e0602ead5b9ca'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'FAILED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => 'e3630dc4d2ca2dbe3db533bd90be7064'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '21aa2bca9528496d7e16c25f3d89925f'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com',
            'amount' => '1.234'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '21aa2bca9528496d7e16c25f3d89925f'
        ];
        $result = http_build_query($params, '', "\n");

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.yeepay.com',
            'amount' => '1.00'
        ];

        $yeePay = new YeePay();
        $yeePay->setContainer($this->container);
        $yeePay->setClient($this->client);
        $yeePay->setResponse($response);
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTracking();
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

        $yeePay = new YeePay();
        $yeePay->getPaymentTrackingData();
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->getPaymentTrackingData();
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
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($options);
        $yeePay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.cha.yeepay.com',
        ];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($options);
        $trackingData = $yeePay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/app-merchant-proxy/command', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.cha.yeepay.com', $trackingData['headers']['Host']);

        $this->assertEquals('QueryOrdDetail', $trackingData['form']['p0_Cmd']);
        $this->assertEquals('10012150139', $trackingData['form']['p1_MerId']);
        $this->assertEquals('201405120018316114', $trackingData['form']['p2_Order']);
        $this->assertEquals('d977c4188694548fce844a2670415c52', $trackingData['form']['hmac']);
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

        $yeePay = new YeePay();
        $yeePay->paymentTrackingVerify();
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

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數signMsg
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('fdsiojosdgdjioioj');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '4f7f1b49196ef4a3d0880c69b872f531'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單不存在
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '9e3afd732bb0b584b5267a06a5a6ecbc'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
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
            'r1_Code' => '2',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => 'ab76ebc95469a454c9d5c3e671deedba'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'INIT',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '1b29216015be08721163c9d1a8776e51'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單處理中
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'AUTHORIZED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => 'db4cd9bff473a4c41febbcaffa9c635f'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
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
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'CANCELED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '9a996727eabf0ec742ec15cdc678d082'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗(回傳PayStatus非Success)
     */
    public function testPaymentTrackingVerifyPaymentFailureWithPayStatusError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'FAILED',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => 'e7906e997824d079780676304e4dc2d2'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = ['content' => $encodeContent];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但金額不正確
     */
    public function testPaymentTrackingVerifyButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '0880c69b872f5314f7f1b49196ef4a3d'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 500
        ];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = [
            'r0_Cmd' => 'QueryOrdDetail',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r8_MP' => 'php1test',
            'rw_RefundRequestID' => '',
            'rx_CreateTime' => '',
            'ry_FinshTime' => '',
            'rz_RefundAmount' => '',
            'rb_PayStatus' => 'SUCCESS',
            'rc_RefundCount' => '0',
            'rd_RefundAmt' => '0.0',
            'hmac' => '0880c69b872f5314f7f1b49196ef4a3d'
        ];

        $encodeContent = urlencode(http_build_query($content, '', "\n"));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 1.0
        ];

        $yeePay = new YeePay();
        $yeePay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $yeePay->setOptions($sourceData);
        $yeePay->paymentTrackingVerify();
    }
}
