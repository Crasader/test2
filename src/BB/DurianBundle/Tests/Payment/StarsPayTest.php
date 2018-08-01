<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\StarsPay;
use Buzz\Message\Response;

class StarsPayTest extends DurianTestCase
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

        $starsPay = new StarsPay();
        $starsPay->getVerifyData();
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

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
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
            'number' => '2017090650010192',
            'paymentVendorId' => '9999',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
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
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
    }

    /**
     * 測試支付時返回html格式但沒有node
     */
    public function testPrepayReturnhtmlWithoutNode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<html></html>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setContainer($this->container);
        $starsPay->setClient($this->client);
        $starsPay->setResponse($response);
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
    }

    /**
     * 測試支付時返回html格式且沒有錯誤訊息
     */
    public function testPrepayReturnhtmlWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<html>' .
            '<div class="alert alert-bg-error">' .
            '<div class="alert-title">' .
            '<div class="alert-icon">' .
            '<i class="iconfont icon-error1 alert-failed"></i><span>' .
            '</div></div></div></html>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setContainer($this->container);
        $starsPay->setClient($this->client);
        $starsPay->setResponse($response);
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
    }

    /**
     * 測試支付時返回html格式
     */
    public function testPrepayReturnhtml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败[交易未开通]',
            180130
        );

        $result = '<html>' .
            '<div class="alert alert-bg-error">' .
            '<div class="alert-title">' .
            '<div class="alert-icon">' .
            '<i class="iconfont icon-error1 alert-failed"></i><span>交易失败[交易未开通]' .
            '</div></div></div></html>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setContainer($this->container);
        $starsPay->setClient($this->client);
        $starsPay->setResponse($response);
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少detail
     */
    public function testPayReturnWithoutDetail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="utf-8"?><message><sign>5CD261C19F44BE693859FC58BA43452C</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setContainer($this->container);
        $starsPay->setClient($this->client);
        $starsPay->setResponse($response);
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><desc>交易成功</desc>' .
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPU55ektFWks=</qrCode></detail>' .
            '<sign>5CD261C19F44BE693859FC58BA43452C</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setContainer($this->container);
        $starsPay->setClient($this->client);
        $starsPay->setResponse($response);
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败（交易数据不正确）',
            180130
        );

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>05</code>' .
            '<desc>交易失败（交易数据不正确）</desc></detail><sign>57B61B06E94D21AE6036AEEAAA0B3450</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setContainer($this->container);
        $starsPay->setClient($this->client);
        $starsPay->setResponse($response);
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>00</code><desc>交易成功</desc>' .
            '</detail><sign>5CD261C19F44BE693859FC58BA43452C</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setContainer($this->container);
        $starsPay->setClient($this->client);
        $starsPay->setResponse($response);
        $starsPay->setOptions($sourceData);
        $starsPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>00</code><desc>交易成功</desc>' .
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPU55ektFWks=</qrCode></detail>' .
            '<sign>5CD261C19F44BE693859FC58BA43452C</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.starspay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setContainer($this->container);
        $starsPay->setClient($this->client);
        $starsPay->setResponse($response);
        $starsPay->setOptions($sourceData);
        $verifyData = $starsPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('weixin://wxpay/bizpayurl?pr=NyzKEZK', $starsPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $verifyData = $starsPay->getVerifyData();

        $this->assertEquals('2017090650010192', $verifyData['merId']);
        $this->assertEquals('201709290000001337', $verifyData['tradeNo']);
        $this->assertEquals('20170929', $verifyData['tradeDate']);
        $this->assertEquals('1.00', $verifyData['amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('111.235.135.54', $verifyData['clientIp']);
        $this->assertEquals('1a20234aacaf4fcd13c2003858325568', $verifyData['sign']);
        $this->assertEquals('2', $verifyData['typeId']);
        $this->assertArrayNotHasKey('bankId', $verifyData);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '2017090650010192',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201709290000001337',
            'orderCreateDate' => '2017-09-29 11:45:55',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $verifyData = $starsPay->getVerifyData();

        $this->assertEquals('2017090650010192', $verifyData['merId']);
        $this->assertEquals('201709290000001337', $verifyData['tradeNo']);
        $this->assertEquals('20170929', $verifyData['tradeDate']);
        $this->assertEquals('1.00', $verifyData['amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('111.235.135.54', $verifyData['clientIp']);
        $this->assertEquals('2cd379fb87902c84457724b3b7560b63', $verifyData['sign']);
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

        $starsPay = new StarsPay();
        $starsPay->verifyOrderPayment([]);
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

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->verifyOrderPayment([]);
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
            'merId' => '2017090650010192',
            'tradeNo' => '201709290000001337',
            'tradeDate' => '20170929',
            'opeNo' => '3964624',
            'opeDate' => '20170929',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170929105850',
            'notifyType' => '1',
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->verifyOrderPayment([]);
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
            'merId' => '2017090650010192',
            'tradeNo' => '201709290000001337',
            'tradeDate' => '20170929',
            'opeNo' => '3964624',
            'opeDate' => '20170929',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170929105850',
            'sign' => '59BB7C3CF406C892AA42F31D0E262E0B',
            'notifyType' => '1',
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->verifyOrderPayment([]);
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
            'merId' => '2017090650010192',
            'tradeNo' => '201709290000001337',
            'tradeDate' => '20170929',
            'opeNo' => '3964624',
            'opeDate' => '20170929',
            'amount' => '0.01',
            'status' => '0',
            'extra' => '',
            'payTime' => '20170929105850',
            'sign' => 'C97A51EECAD6F40831CD975EB7B2AC0B',
            'notifyType' => '1',
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->verifyOrderPayment([]);
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
            'merId' => '2017090650010192',
            'tradeNo' => '201709290000001337',
            'tradeDate' => '20170929',
            'opeNo' => '3964624',
            'opeDate' => '20170929',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170929105850',
            'sign' => '3EF778C65AD9AE2011C4953E56DF5333',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201709290000001338',
            'amount' => '0.01',
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->verifyOrderPayment($entry);
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
            'merId' => '2017090650010192',
            'tradeNo' => '201709290000001337',
            'tradeDate' => '20170929',
            'opeNo' => '3964624',
            'opeDate' => '20170929',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170929105850',
            'sign' => '3EF778C65AD9AE2011C4953E56DF5333',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201709290000001337',
            'amount' => '10.00',
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017090650010192',
            'tradeNo' => '201709290000001337',
            'tradeDate' => '20170929',
            'opeNo' => '3964624',
            'opeDate' => '20170929',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170929105850',
            'sign' => '3EF778C65AD9AE2011C4953E56DF5333',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201709290000001337',
            'amount' => '0.01',
        ];

        $starsPay = new StarsPay();
        $starsPay->setPrivateKey('test');
        $starsPay->setOptions($sourceData);
        $starsPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $starsPay->getMsg());
    }
}