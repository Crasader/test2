<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChangHuei;
use Buzz\Message\Response;

class ChangHueiTest extends DurianTestCase
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

        $changHuei = new ChangHuei();
        $changHuei->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->getVerifyData();
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
            'number' => 'CHANG1520480403960',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '100',
            'orderId' => '201803160000004390',
            'amount' => '100',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQrCodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => 'CHANG1520480403960',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1103',
            'orderId' => '201803160000004390',
            'amount' => '100',
            'verify_url' => '',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回r1_Code
     */
    public function testQrCodePayReturnWithoutR1Code()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'CHANG1520480403960',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1103',
            'orderId' => '201803160000004390',
            'amount' => '100',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['r7_Desc' => '该商户此业务配置异常，请联系我们'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $changHuei = new ChangHuei();
        $changHuei->setContainer($this->container);
        $changHuei->setClient($this->client);
        $changHuei->setResponse($response);
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '该商户此业务配置异常，请联系我们',
            180130
        );

        $options = [
            'number' => 'CHANG1520480403960',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1103',
            'orderId' => '201803160000004390',
            'amount' => '100',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'r0_Cmd' => 'Buy',
            'p1_MerId' => 'CHANG1520480403960',
            'r1_Code' => '-500',
            'r2_TrxId' => '',
            'r3_PayInfo' => '',
            'r4_Amt' => '0.00',
            'r5_OpenId' => '',
            'r6_AuthCode' => '',
            'r7_Desc' => '该商户此业务配置异常，请联系我们',
            'r8_Order' => '',
            'hmac' => '175a226e867a068333131264d0dce387',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $changHuei = new ChangHuei();
        $changHuei->setContainer($this->container);
        $changHuei->setClient($this->client);
        $changHuei->setResponse($response);
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回r3_PayInfo
     */
    public function testQrCodePayReturnWithoutR3PayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'CHANG1520480403960',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1103',
            'orderId' => '201803160000004390',
            'amount' => '100',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'r0_Cmd' => 'Buy',
            'p1_MerId' => 'CHANG1520480403960',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2018031611562100040557ZF',
            'r4_Amt' => '0.01',
            'r5_OpenId' => '',
            'r6_AuthCode' => '',
            'r7_Desc' => '',
            'r8_Order' => '201803160000004391',
            'hmac' => '3d7a50c6b959d2ba3244591e0bbbcb65',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $changHuei = new ChangHuei();
        $changHuei->setContainer($this->container);
        $changHuei->setClient($this->client);
        $changHuei->setResponse($response);
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => 'CHANG1520480403960',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1103',
            'orderId' => '201803160000004390',
            'amount' => '100',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'r0_Cmd' => 'Buy',
            'p1_MerId' => 'CHANG1520480403960',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2018031611562100040557ZF',
            'r3_PayInfo' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?t=5Va9',
            'r4_Amt' => '0.01',
            'r5_OpenId' => '',
            'r6_AuthCode' => '',
            'r7_Desc' => '',
            'r8_Order' => '201803160000004391',
            'hmac' => '3d7a50c6b959d2ba3244591e0bbbcb65',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $changHuei = new ChangHuei();
        $changHuei->setContainer($this->container);
        $changHuei->setClient($this->client);
        $changHuei->setResponse($response);
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $data = $changHuei->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html?t=5Va9', $changHuei->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => 'CHANG1520480403960',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1104',
            'orderId' => '201803160000004390',
            'amount' => '100',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $data = $changHuei->getVerifyData();

        $this->assertEquals('Buy', $data['p0_Cmd']);
        $this->assertEquals('CHANG1520480403960', $data['p1_MerId']);
        $this->assertEquals('201803160000004390', $data['p2_Order']);
        $this->assertEquals('CNY', $data['p3_Cur']);
        $this->assertEquals('100', $data['p4_Amt']);
        $this->assertEquals('', $data['p5_Pid']);
        $this->assertEquals('', $data['p6_Pcat']);
        $this->assertEquals('', $data['p7_Pdesc']);
        $this->assertEquals('http://pay.my/pay/return.php', $data['p8_Url']);
        $this->assertEquals('201803160000004390', $data['p9_MP']);
        $this->assertEquals('QQWAP', $data['pa_FrpId']);
        $this->assertEquals('', $data['pg_BankCode']);
        $this->assertEquals('12b6156f235f72cac8bd295fe91bc8d2', $data['hmac']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => 'CHANG1520480403960',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'orderId' => '201803160000004390',
            'amount' => '100',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $data = $changHuei->getVerifyData();

        $this->assertEquals('Buy', $data['p0_Cmd']);
        $this->assertEquals('CHANG1520480403960', $data['p1_MerId']);
        $this->assertEquals('201803160000004390', $data['p2_Order']);
        $this->assertEquals('CNY', $data['p3_Cur']);
        $this->assertEquals('100', $data['p4_Amt']);
        $this->assertEquals('', $data['p5_Pid']);
        $this->assertEquals('', $data['p6_Pcat']);
        $this->assertEquals('', $data['p7_Pdesc']);
        $this->assertEquals('http://pay.my/pay/return.php', $data['p8_Url']);
        $this->assertEquals('201803160000004390', $data['p9_MP']);
        $this->assertEquals('OnlinePay', $data['pa_FrpId']);
        $this->assertEquals('ICBC', $data['pg_BankCode']);
        $this->assertEquals('5ca77908e7241759d19d42ee7c7d92f0', $data['hmac']);
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

        $changHuei = new ChangHuei();
        $changHuei->verifyOrderPayment([]);
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

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'p1_MerId' => 'CHANG1520480403960',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2018031611201100040031ZF',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201803160000004390',
            'r8_MP' => '201803160000004390',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1520480403960',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2018031611201100040031ZF',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201803160000004390',
            'r8_MP' => '201803160000004390',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '2342629f38b9d6a9819bd50b3ad2cdfe',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1520480403960',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '2',
            'r2_TrxId' => 'ORDE2018031611201100040031ZF',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201803160000004390',
            'r8_MP' => '201803160000004390',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '935245e10a9a5b620dcc42796105ba2e',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1520480403960',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2018031611201100040031ZF',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201803160000004390',
            'r8_MP' => '201803160000004390',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => 'a8609e4ce98a0748ae2a83177cd77343',
        ];

        $entry = ['id' => '201803160000004391'];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->verifyOrderPayment($entry);
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
            'p1_MerId' => 'CHANG1520480403960',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2018031611201100040031ZF',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201803160000004390',
            'r8_MP' => '201803160000004390',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => 'a8609e4ce98a0748ae2a83177cd77343',
        ];

        $entry = [
            'id' => '201803160000004390',
            'amount' => '1',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'p1_MerId' => 'CHANG1520480403960',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2018031611201100040031ZF',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201803160000004390',
            'r8_MP' => '201803160000004390',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => 'a8609e4ce98a0748ae2a83177cd77343',
        ];

        $entry = [
            'id' => '201803160000004390',
            'amount' => '0.01',
        ];

        $changHuei = new ChangHuei();
        $changHuei->setPrivateKey('test');
        $changHuei->setOptions($options);
        $changHuei->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $changHuei->getMsg());
    }
}
