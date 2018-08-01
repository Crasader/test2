<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiChengFu;
use Buzz\Message\Response;

class HuiChengFuTest extends DurianTestCase
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

        $huiChengFu = new HuiChengFu();
        $huiChengFu->getVerifyData();
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

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->getVerifyData();
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
            'number' => '2018010811010007',
            'paymentVendorId' => '9999',
            'amount' => '0.01',
            'orderId' => '201801240000008651',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->getVerifyData();
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
            'number' => '2018010811010007',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201801240000008651',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->getVerifyData();
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
            'number' => '2018010811010007',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201801240000008651',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->getVerifyData();
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
            'number' => '2018010811010007',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201801240000008651',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>00</code>' .
            '<desc>交易完成</desc></detail><sign>CCFC2B948707EE913F4CA9CA3795919E</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2018010811010007',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201801240000008651',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>00</code>' .
            '<desc>交易完成</desc><qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPThGNXhSR1k=</qrCode>' .
            '</detail><sign>CCFC2B948707EE913F4CA9CA3795919E</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2018010811010007',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201801240000008651',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.malljls.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->setOptions($sourceData);
        $verifyData = $huiChengFu->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('weixin://wxpay/bizpayurl?pr=8F5xRGY', $huiChengFu->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '2018010811010007',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201801240000008651',
            'orderCreateDate' => '2017-10-24 17:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $verifyData = $huiChengFu->getVerifyData();

        $this->assertEquals('TRADE.H5PAY', $verifyData['service']);
        $this->assertEquals('2018010811010007', $verifyData['merId']);
        $this->assertEquals('201801240000008651', $verifyData['tradeNo']);
        $this->assertEquals('20171024', $verifyData['tradeDate']);
        $this->assertEquals('0.01', $verifyData['amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('111.235.135.54', $verifyData['clientIp']);
        $this->assertEquals('15156cc15c7955e66b6ceda0f201d86c', $verifyData['sign']);
        $this->assertEquals('2', $verifyData['typeId']);
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

        $huiChengFu = new HuiChengFu();
        $huiChengFu->verifyOrderPayment([]);
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

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->verifyOrderPayment([]);
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
            'merId' => '2018011344010064',
            'tradeNo' => '201801240000008651',
            'tradeDate' => '20180124',
            'opeNo' => '83811',
            'opeDate' => '20180124',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20180124105249',
            'notifyType' => '1',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->verifyOrderPayment([]);
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
            'merId' => '2018011344010064',
            'tradeNo' => '201801240000008651',
            'tradeDate' => '20180124',
            'opeNo' => '83811',
            'opeDate' => '20180124',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20180124105249',
            'sign' => '5027771C69EEB93CA4C67BF08F8A62A0',
            'notifyType' => '1',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->verifyOrderPayment([]);
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
            'merId' => '2018011344010064',
            'tradeNo' => '201801240000008651',
            'tradeDate' => '20180124',
            'opeNo' => '83811',
            'opeDate' => '20180124',
            'amount' => '0.01',
            'status' => '5',
            'extra' => '',
            'payTime' => '20180124105249',
            'sign' => '87a501b5032c5b66607e437790146d94',
            'notifyType' => '1',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->verifyOrderPayment([]);
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
            'merId' => '2018011344010064',
            'tradeNo' => '201801240000008651',
            'tradeDate' => '20180124',
            'opeNo' => '83811',
            'opeDate' => '20180124',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20180124105249',
            'sign' => '27a790471403f6a34dad922bcbe0ec25',
            'notifyType' => '1',
        ];

        $entry = ['id' => '201710050000001417'];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->verifyOrderPayment($entry);
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
            'merId' => '2018011344010064',
            'tradeNo' => '201801240000008651',
            'tradeDate' => '20180124',
            'opeNo' => '83811',
            'opeDate' => '20180124',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20180124105249',
            'sign' => '27a790471403f6a34dad922bcbe0ec25',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201801240000008651',
            'amount' => '1.00',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2018011344010064',
            'tradeNo' => '201801240000008651',
            'tradeDate' => '20180124',
            'opeNo' => '83811',
            'opeDate' => '20180124',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20180124105249',
            'sign' => '27a790471403f6a34dad922bcbe0ec25',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201801240000008651',
            'amount' => '0.01',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $huiChengFu->getMsg());
    }

    /**
     * 測試訂單查詢未指定私鑰
     */
    public function testPaymentTrackingWithNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $huiChengFu = new HuiChengFu();
        $huiChengFu->paymentTracking();
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

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入verify_url
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '201803080000002108',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數sign
     */
    public function testPaymentTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail></detail></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201803080000002108',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.huichengfu.com',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果通詢失敗
     */
    public function testPaymentTrackingResultConnectionFail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '通询失败',
            180130
        );

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<message><detail><code>01</code><desc>通询失败</desc></detail><sign></sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201803080000002108',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.huichengfu.com',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testPaymentTrackingSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<message><detail><code>00</code><desc>交易成功</desc><orderDate>20180308</orderDate>' .
            '<opeDate>20180308</opeDate><tradeNo>201803080000002108</tradeNo><opeNo>442171</opeNo>' .
            '<exchangeRate>0.0000</exchangeRate><status>1</status></detail>' .
            '<sign>9C18AC3E458EE2D354F4289A2CAF497C</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201803080000002108',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.huichengfu.com',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testPaymentTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<message><detail><code>00</code><desc>交易成功</desc><orderDate>20180308</orderDate>' .
            '<opeDate>20180308</opeDate><tradeNo>201803080000002108</tradeNo><opeNo>442171</opeNo>' .
            '<exchangeRate>0.0000</exchangeRate><status>0</status></detail>' .
            '<sign>3028F861099AB416A43745577142C8D6</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201803080000002108',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.huichengfu.com',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單號錯誤
     */
    public function testPaymentTrackingReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<message><detail><code>00</code><desc>交易成功</desc><orderDate>20180308</orderDate>' .
            '<opeDate>20180308</opeDate><tradeNo>201803080000002108</tradeNo><opeNo>442171</opeNo>' .
            '<exchangeRate>0.0000</exchangeRate><status>1</status></detail>' .
            '<sign>32A000B7256DFB317E18F9066B536C50</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201803080000002109',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.huichengfu.com',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單支付成功
     */
    public function testPaymentTracking()
    {
        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<message><detail><code>00</code><desc>交易成功</desc><orderDate>20180308</orderDate>' .
            '<opeDate>20180308</opeDate><tradeNo>201803080000002108</tradeNo><opeNo>442171</opeNo>' .
            '<exchangeRate>0.0000</exchangeRate><status>1</status></detail>' .
            '<sign>32A000B7256DFB317E18F9066B536C50</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201803080000002108',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.huichengfu.com',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->setContainer($this->container);
        $huiChengFu->setClient($this->client);
        $huiChengFu->setResponse($response);
        $huiChengFu->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定私鑰
     */
    public function testGetPaymentTrackingDataWithNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $huiChengFu = new HuiChengFu();
        $huiChengFu->getPaymentTrackingData();
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

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->getPaymentTrackingData();
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
            'number' => '9527',
            'orderId' => '201803080000002108',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $huiChengFu->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201803080000002108',
            'orderCreateDate' => '2018-03-08 14:16:03',
            'amount' => '0.0100',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.huichengfu.com',
        ];

        $huiChengFu = new HuiChengFu();
        $huiChengFu->setPrivateKey('test');
        $huiChengFu->setOptions($sourceData);
        $trackingData = $huiChengFu->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/cooperate/gateway.cgi', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }
}
