<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShuenFuTung;
use Buzz\Message\Response;

class ShuenFuTungTest extends DurianTestCase
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

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->getVerifyData();
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

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->getVerifyData();
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
            'number' => '2017110311010068',
            'paymentVendorId' => '9999',
            'amount' => '0.01',
            'orderId' => '201711140000002288',
            'orderCreateDate' => '2017-11-14 10:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->getVerifyData();
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
            'number' => '2017110311010068',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201711140000002288',
            'orderCreateDate' => '2017-11-14 10:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<message><detail><desc>交易完成</desc>' .
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPUFGTmcyQUo=</qrCode>' .
            '</detail><sign>3935B3CA1CCAAD4F3BBBF5B05A5C5AB6</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017110311010068',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201711140000002288',
            'orderCreateDate' => '2017-11-14 10:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.trade.666666pay.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setContainer($this->container);
        $shuenFuTung->setClient($this->client);
        $shuenFuTung->setResponse($response);
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->getVerifyData();
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


        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><code>05</code>' .
            '<desc>交易失败（交易数据不正确）</desc></detail><sign>B751BFD3EDB030863A6FC900A23D0378</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017110311010068',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201711140000002288',
            'orderCreateDate' => '2017-11-14 10:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.trade.666666pay.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setContainer($this->container);
        $shuenFuTung->setClient($this->client);
        $shuenFuTung->setResponse($response);
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<message><detail><code>00</code><desc>交易完成</desc>' .
            '</detail><sign>3935B3CA1CCAAD4F3BBBF5B05A5C5AB6</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017110311010068',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201711140000002288',
            'orderCreateDate' => '2017-11-14 10:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.trade.666666pay.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setContainer($this->container);
        $shuenFuTung->setClient($this->client);
        $shuenFuTung->setResponse($response);
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<message><detail><code>00</code><desc>交易完成</desc>' .
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPUFGTmcyQUo=</qrCode>' .
            '</detail><sign>3935B3CA1CCAAD4F3BBBF5B05A5C5AB6</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017110311010068',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201711140000002288',
            'orderCreateDate' => '2017-11-14 10:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.trade.666666pay.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setContainer($this->container);
        $shuenFuTung->setClient($this->client);
        $shuenFuTung->setResponse($response);
        $shuenFuTung->setOptions($sourceData);
        $verifyData = $shuenFuTung->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('weixin://wxpay/bizpayurl?pr=AFNg2AJ', $shuenFuTung->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '2017110311010068',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201711140000002288',
            'orderCreateDate' => '2017-11-14 10:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $verifyData = $shuenFuTung->getVerifyData();

        $this->assertEquals('TRADE.H5PAY', $verifyData['service']);
        $this->assertEquals('2017110311010068', $verifyData['merId']);
        $this->assertEquals('201711140000002288', $verifyData['tradeNo']);
        $this->assertEquals('20171114', $verifyData['tradeDate']);
        $this->assertEquals('0.01', $verifyData['amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('111.235.135.54', $verifyData['clientIp']);
        $this->assertEquals('4ccda5c83af1cb56dba7ddf0328b4e3d', $verifyData['sign']);
        $this->assertEquals('2', $verifyData['typeId']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '2017110311010068',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201711140000002288',
            'orderCreateDate' => '2017-11-14 10:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $verifyData = $shuenFuTung->getVerifyData();

        $this->assertEquals('TRADE.B2C', $verifyData['service']);
        $this->assertEquals('2017110311010068', $verifyData['merId']);
        $this->assertEquals('201711140000002288', $verifyData['tradeNo']);
        $this->assertEquals('20171114', $verifyData['tradeDate']);
        $this->assertEquals('0.01', $verifyData['amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('111.235.135.54', $verifyData['clientIp']);
        $this->assertEquals('514ec10f62ba2d2cd023d90c8373c955', $verifyData['sign']);
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

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->verifyOrderPayment([]);
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

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->verifyOrderPayment([]);
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
            'merId' => '2017110311010068',
            'tradeNo' => '201711140000002288',
            'tradeDate' => '20171114',
            'opeNo' => '83845',
            'opeDate' => '20171114',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171114152349',
            'notifyType' => '1',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->verifyOrderPayment([]);
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
            'merId' => '2017110311010068',
            'tradeNo' => '201711140000002288',
            'tradeDate' => '20171114',
            'opeNo' => '83845',
            'opeDate' => '20171114',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171114152349',
            'sign' => 'C1DEF0AE3498FA0DFF1D7FA9E404C1CA',
            'notifyType' => '1',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->verifyOrderPayment([]);
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
            'merId' => '2017110311010068',
            'tradeNo' => '201711140000002288',
            'tradeDate' => '20171114',
            'opeNo' => '83845',
            'opeDate' => '20171114',
            'amount' => '0.01',
            'status' => '0',
            'extra' => '',
            'payTime' => '20171114152349',
            'sign' => '6801ba60a1f5eeb55e79ec832cf3f257',
            'notifyType' => '1',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->verifyOrderPayment([]);
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
            'merId' => '2017110311010068',
            'tradeNo' => '201711140000002288',
            'tradeDate' => '20171114',
            'opeNo' => '83845',
            'opeDate' => '20171114',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171114152349',
            'sign' => 'f8d1c8b449f1f5158852d2ea9fb347cc',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201711140000002289',
            'amount' => '0.01',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->verifyOrderPayment($entry);
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
            'merId' => '2017110311010068',
            'tradeNo' => '201711140000002288',
            'tradeDate' => '20171114',
            'opeNo' => '83845',
            'opeDate' => '20171114',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171114152349',
            'sign' => 'f8d1c8b449f1f5158852d2ea9fb347cc',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201711140000002288',
            'amount' => '1',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017110311010068',
            'tradeNo' => '201711140000002288',
            'tradeDate' => '20171114',
            'opeNo' => '83845',
            'opeDate' => '20171114',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171114152349',
            'sign' => 'f8d1c8b449f1f5158852d2ea9fb347cc',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201711140000002288',
            'amount' => '0.01',
        ];

        $shuenFuTung = new ShuenFuTung();
        $shuenFuTung->setPrivateKey('test');
        $shuenFuTung->setOptions($sourceData);
        $shuenFuTung->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $shuenFuTung->getMsg());
    }
}
