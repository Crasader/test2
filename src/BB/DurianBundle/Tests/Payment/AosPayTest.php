<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\AosPay;
use Buzz\Message\Response;

class AosPayTest extends DurianTestCase
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

        $aosPay = new AosPay();
        $aosPay->getVerifyData();
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

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->getVerifyData();
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
            'number' => '135325',
            'paymentVendorId' => '7',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->getVerifyData();
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
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><sign>12065FD847D4510CAE2E99E5A7C9924D</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setContainer($this->container);
        $aosPay->setClient($this->client);
        $aosPay->setResponse($response);
        $aosPay->setOptions($sourceData);
        $aosPay->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><desc>交易完成</desc>'.
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPXRUMzV0YUw=</qrCode>'.
            '</detail><sign>12065FD847D4510CAE2E99E5A7C9924D</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setContainer($this->container);
        $aosPay->setClient($this->client);
        $aosPay->setResponse($response);
        $aosPay->setOptions($sourceData);
        $aosPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败（签名错误）',
            180130
        );

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><code>05</code>'.
            '<desc>交易失败（签名错误）</desc></detail><sign>22D4C39A46E4D5A63A1599CBE9890A06</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setContainer($this->container);
        $aosPay->setClient($this->client);
        $aosPay->setResponse($response);
        $aosPay->setOptions($sourceData);
        $aosPay->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><code>00</code>'.
            '<desc>交易完成</desc></detail><sign>12065FD847D4510CAE2E99E5A7C9924D</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setContainer($this->container);
        $aosPay->setClient($this->client);
        $aosPay->setResponse($response);
        $aosPay->setOptions($sourceData);
        $aosPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><code>00</code>'.
            '<desc>交易完成</desc><qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPXRUMzV0YUw=</qrCode>'.
            '</detail><sign>12065FD847D4510CAE2E99E5A7C9924D</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setContainer($this->container);
        $aosPay->setClient($this->client);
        $aosPay->setResponse($response);
        $aosPay->setOptions($sourceData);
        $verifyData = $aosPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('weixin://wxpay/bizpayurl?pr=tT35taL', $aosPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $verifyData = $aosPay->getVerifyData();

        $this->assertEquals('135325', $verifyData['merId']);
        $this->assertEquals('201705090000002599', $verifyData['tradeNo']);
        $this->assertEquals('20170308', $verifyData['tradeDate']);
        $this->assertEquals('1.00', $verifyData['amount']);
        $this->assertEquals('http://two123.comxa.com/', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('123.123.123.123', $verifyData['clientIp']);
        $this->assertEquals('3A89DC0C045AA6E27E2D82C7D1E2F7F6', $verifyData['sign']);
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

        $aosPay = new AosPay();
        $aosPay->verifyOrderPayment([]);
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

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->verifyOrderPayment([]);
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
            'merId' => '2017061844010006',
            'tradeNo' => '201709140000007052',
            'tradeDate' => '20170914',
            'opeNo' => '2633841',
            'opeDate' => '20170914',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170914121058',
            'notifyType' => '1',
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->verifyOrderPayment([]);
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
            'merId' => '2017061844010006',
            'tradeNo' => '201709140000007052',
            'tradeDate' => '20170914',
            'opeNo' => '2633841',
            'opeDate' => '20170914',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170914121058',
            'sign' => '1D2D7C49C2EE92E475326AC776B91856',
            'notifyType' => '1',
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->verifyOrderPayment([]);
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
            'merId' => '2017061844010006',
            'tradeNo' => '201709140000007052',
            'tradeDate' => '20170914',
            'opeNo' => '2633841',
            'opeDate' => '20170914',
            'amount' => '0.01',
            'status' => '0',
            'extra' => '',
            'payTime' => '20170914121058',
            'sign' => 'E90559F870FA6DD6B0B1CABB2345ACE1',
            'notifyType' => '1',
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->verifyOrderPayment([]);
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
            'merId' => '2017061844010006',
            'tradeNo' => '201709140000007052',
            'tradeDate' => '20170914',
            'opeNo' => '2633841',
            'opeDate' => '20170914',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170914121058',
            'sign' => '0782030ECA8593F6FB6029D37334B78E',
            'notifyType' => '1',
        ];

        $entry = ['id' => '201709140000007051'];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->verifyOrderPayment($entry);
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
            'merId' => '2017061844010006',
            'tradeNo' => '201709140000007052',
            'tradeDate' => '20170914',
            'opeNo' => '2633841',
            'opeDate' => '20170914',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170914121058',
            'sign' => '0782030ECA8593F6FB6029D37334B78E',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201709140000007052',
            'amount' => '0.02',
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017061844010006',
            'tradeNo' => '201709140000007052',
            'tradeDate' => '20170914',
            'opeNo' => '2633841',
            'opeDate' => '20170914',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170914121058',
            'sign' => '0782030ECA8593F6FB6029D37334B78E',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201709140000007052',
            'amount' => '0.01',
        ];

        $aosPay = new AosPay();
        $aosPay->setPrivateKey('test');
        $aosPay->setOptions($sourceData);
        $aosPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $aosPay->getMsg());
    }
}
