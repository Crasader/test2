<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChangLian;
use Buzz\Message\Response;

class ChangLianTest extends DurianTestCase
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

        $changLian = new ChangLian();
        $changLian->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->getVerifyData();
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
            'number' => '123456',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '100',
            'orderId' => '201606060000000001',
            'amount' => '100',
            'username' => 'two',
        ];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201712190000008114',
            'amount' => '100.5',
            'username' => 'two',
            'number' => '123456',
        ];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $data = $changLian->getVerifyData();

        $this->assertEquals('Buy', $data['p0_Cmd']);
        $this->assertEquals('123456', $data['p1_MerId']);
        $this->assertEquals('201712190000008114', $data['p2_Order']);
        $this->assertEquals('CNY', $data['p3_Cur']);
        $this->assertEquals('100.50', $data['p4_Amt']);
        $this->assertEquals('', $data['p5_Pid']);
        $this->assertEquals('', $data['p6_Pcat']);
        $this->assertEquals('', $data['p7_Pdesc']);
        $this->assertEquals('http://154.58.78.54/', $data['p8_Url']);
        $this->assertEquals('two', $data['p9_MP']);
        $this->assertEquals('OnlinePay', $data['pa_FrpId']);
        $this->assertEquals('ICBC', $data['pg_BankCode']);
        $this->assertEquals('2faf6478786c7c6e3077b300528c2261', $data['hmac']);
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
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'orderId' => '201712190000008114',
            'amount' => '100.5',
            'username' => 'two',
            'number' => '123456',
            'verify_url' => '',
        ];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->getVerifyData();
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
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'orderId' => '201712190000008114',
            'amount' => '100.5',
            'username' => 'two',
            'number' => '123456',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"r7_Desc":"该商户此业务配置异常，请联系我们"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $changLian = new ChangLian();
        $changLian->setContainer($this->container);
        $changLian->setClient($this->client);
        $changLian->setResponse($response);
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->getVerifyData();
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
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'orderId' => '201712190000008114',
            'amount' => '100.5',
            'username' => 'two',
            'number' => '123456',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"r0_Cmd":"Buy","p1_MerId":"CHANG1510286859820","r1_Code":"-500","r2_TrxId":"",' .
            '"r3_PayInfo":"","r4_Amt":"0.00","r5_OpenId":"","r6_AuthCode":"",' .
            '"r7_Desc":"该商户此业务配置异常，请联系我们 ","r8_Order":"",' .
            '"hmac":"3ed06e7485c00e417029a31d36167d61"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $changLian = new ChangLian();
        $changLian->setContainer($this->container);
        $changLian->setClient($this->client);
        $changLian->setResponse($response);
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回r3_PayInfo
     */
    public function testQrCodePayReturnWithoutPayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'orderId' => '201712190000008114',
            'amount' => '100.5',
            'username' => 'two',
            'number' => '123456',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = $result = '{"r0_Cmd":"Buy","p1_MerId":"CHANG1510286859820","r1_Code":"1",' .
            '"r2_TrxId":"ORDE2017121913365700009247PS","r4_Amt":"10.00","r5_OpenId":"",' .
            '"r6_AuthCode":"","r7_Desc":"","r8_Order":"201712190000008133",' .
            '"hmac":"eaae9c3ec9d475df7c1f5df6658e473e"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $changLian = new ChangLian();
        $changLian->setContainer($this->container);
        $changLian->setClient($this->client);
        $changLian->setResponse($response);
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1103',
            'orderId' => '201712190000008114',
            'amount' => '100.5',
            'username' => 'two',
            'number' => '123456',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"r0_Cmd":"Buy","p1_MerId":"CHANG1510286859820","r1_Code":"1",' .
            '"r2_TrxId":"ORDE2017121913365700009247PS","r3_PayInfo":"https://qpay.qq.com/qr/67069f6b",' .
            '"r4_Amt":"10.00","r5_OpenId":"","r6_AuthCode":"","r7_Desc":"","r8_Order":"201712190000008133",' .
            '"hmac":"eaae9c3ec9d475df7c1f5df6658e473e"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $changLian = new ChangLian();
        $changLian->setContainer($this->container);
        $changLian->setClient($this->client);
        $changLian->setResponse($response);
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $data = $changLian->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/67069f6b', $changLian->getQrcode());
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

        $changLian = new ChangLian();
        $changLian->verifyOrderPayment([]);
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

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1510286859820',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017121911182600008138PS',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201712190000008114',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
        ];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1510286859820',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017121911182600008138PS',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201712190000008114',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '06061b9efeedc5600aa1daa427e8507c',
        ];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1510286859820',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '9',
            'r2_TrxId' => 'ORDE2017121911182600008138PS',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201712190000008114',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => 'edc30053dab11ea701915f053e4384c5',
        ];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1510286859820',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017121911182600008138PS',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201712190000008114',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '2bd351b971034dd0340cb3f7a7b68c9c',
        ];

        $entry = ['id' => '201509140000002475'];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->verifyOrderPayment($entry);
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
            'p1_MerId' => 'CHANG1510286859820',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017121911182600008138PS',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201712190000008114',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '2bd351b971034dd0340cb3f7a7b68c9c',
        ];

        $entry = [
            'id' => '201712190000008114',
            'amount' => '15.00',
        ];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'p1_MerId' => 'CHANG1510286859820',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017121911182600008138PS',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '',
            'r6_Order' => '201712190000008114',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '2bd351b971034dd0340cb3f7a7b68c9c',
        ];

        $entry = [
            'id' => '201712190000008114',
            'amount' => '0.01',
        ];

        $changLian = new ChangLian();
        $changLian->setPrivateKey('test');
        $changLian->setOptions($options);
        $changLian->verifyOrderPayment($entry);

        $this->assertEquals('success', $changLian->getMsg());
    }
}
