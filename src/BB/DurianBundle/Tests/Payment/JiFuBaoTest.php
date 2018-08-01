<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JiFuBao;
use Buzz\Message\Response;

class JiFuBaoTest extends DurianTestCase
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

        $jiFuBao = new JiFuBao();
        $jiFuBao->getVerifyData();
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

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->getVerifyData();
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
            'number' => '201710240000007375',
            'paymentVendorId' => '9999',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '201710240000007375',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->getVerifyData();
    }

    /**
     * 測試支付時返回缺少code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><desc>通讯成功</desc>' .
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPUp3Q1lmOWY=</qrCode></detail>' .
            '<sign>C73B8AB30DE61E67F476084EF663D47A</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '201710240000007375',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setContainer($this->container);
        $jiFuBao->setClient($this->client);
        $jiFuBao->setResponse($response);
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '该银行正在维护，暂停使用[银行通道维护，请稍后重试！]',
            180130
        );

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>30</code>' .
            '<desc>该银行正在维护，暂停使用[银行通道维护，请稍后重试！]</desc><qrCode/></detail>' .
            '<sign>0C2C77A61CAE220CAFB9F5D478906CD7</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '201710240000007375',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setContainer($this->container);
        $jiFuBao->setClient($this->client);
        $jiFuBao->setResponse($response);
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->getVerifyData();
    }

    /**
     * 測試支付時返回缺少qrCode
     */
    public function testPayReturnWithoutQRCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail>00<desc>交易完成</desc></detail>' .
            '<sign>52E296DAD346D794883C17C533634130</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '201710240000007375',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setContainer($this->container);
        $jiFuBao->setClient($this->client);
        $jiFuBao->setResponse($response);
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>00</code>' .
            '<desc>交易完成</desc><qrCode>aHR0cHM6Ly9xcGF5LnFxLmNvbS9xci82ODkwYzNiNg==</qrCode></detail>' .
            '<sign>A5551B60CC42CC900EDBB6C2E6383F64</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '201710240000007375',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setContainer($this->container);
        $jiFuBao->setClient($this->client);
        $jiFuBao->setResponse($response);
        $jiFuBao->setOptions($sourceData);
        $verifyData = $jiFuBao->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('https://qpay.qq.com/qr/6890c3b6', $jiFuBao->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '201710240000007375',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $verifyData = $jiFuBao->getVerifyData();

        $this->assertEquals('TRADE.H5PAY', $verifyData['service']);
        $this->assertEquals('201710240000007375', $verifyData['merId']);
        $this->assertEquals('201710160000001536', $verifyData['tradeNo']);
        $this->assertEquals('20171024', $verifyData['tradeDate']);
        $this->assertEquals('0.01', $verifyData['amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('111.235.135.54', $verifyData['clientIp']);
        $this->assertEquals('9f7535f5c6691aad9705c2e1a54d866a', $verifyData['sign']);
        $this->assertEquals('2', $verifyData['typeId']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '201710240000007375',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $verifyData = $jiFuBao->getVerifyData();

        $this->assertEquals('201710240000007375', $verifyData['merId']);
        $this->assertEquals('201710160000001536', $verifyData['tradeNo']);
        $this->assertEquals('20171024', $verifyData['tradeDate']);
        $this->assertEquals('0.01', $verifyData['amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('111.235.135.54', $verifyData['clientIp']);
        $this->assertEquals('cfd5eaac70f39d3f4159cb85b5729a7e', $verifyData['sign']);
        $this->assertEquals('ICBC', $verifyData['bankId']);
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

        $jiFuBao = new JiFuBao();
        $jiFuBao->verifyOrderPayment([]);
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

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017092611010003',
            'tradeNo' => '201710240000007375',
            'tradeDate' => '20171024',
            'opeNo' => '16533',
            'opeDate' => '20171024',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171024170551',
            'notifyType' => '1',
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017092611010003',
            'tradeNo' => '201710240000007375',
            'tradeDate' => '20171024',
            'opeNo' => '16533',
            'opeDate' => '20171024',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171024170551',
            'sign' => '67E3B4BFCC4F3FC1F2D53710D5A74723',
            'notifyType' => '1',
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->verifyOrderPayment([]);
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
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017092611010003',
            'tradeNo' => '201710240000007375',
            'tradeDate' => '20171024',
            'opeNo' => '16533',
            'opeDate' => '20171024',
            'amount' => '0.01',
            'status' => '0',
            'extra' => '',
            'payTime' => '20171024170551',
            'sign' => '71a2fa5016ac4c7197e77e690dcb836d',
            'notifyType' => '1',
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->verifyOrderPayment([]);
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
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017092611010003',
            'tradeNo' => '201710240000007375',
            'tradeDate' => '20171024',
            'opeNo' => '16533',
            'opeDate' => '20171024',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171024170551',
            'sign' => 'ed0a35abbc4990ec8c9be6aa48cb0f90',
            'notifyType' => '1',
        ];

        $entry = ['id' => '201710050000001417'];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->verifyOrderPayment($entry);
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
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017092611010003',
            'tradeNo' => '201710240000007375',
            'tradeDate' => '20171024',
            'opeNo' => '16533',
            'opeDate' => '20171024',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171024170551',
            'sign' => 'ed0a35abbc4990ec8c9be6aa48cb0f90',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201710240000007375',
            'amount' => '1.00',
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017092611010003',
            'tradeNo' => '201710240000007375',
            'tradeDate' => '20171024',
            'opeNo' => '16533',
            'opeDate' => '20171024',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171024170551',
            'sign' => 'ed0a35abbc4990ec8c9be6aa48cb0f90',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201710240000007375',
            'amount' => '0.01',
        ];

        $jiFuBao = new JiFuBao();
        $jiFuBao->setPrivateKey('test');
        $jiFuBao->setOptions($sourceData);
        $jiFuBao->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jiFuBao->getMsg());
    }
}