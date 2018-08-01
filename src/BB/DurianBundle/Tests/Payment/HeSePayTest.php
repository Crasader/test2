<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HeSePay;
use Buzz\Message\Response;

class HeSePayTest extends DurianTestCase
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

        $heSePay = new HeSePay();
        $heSePay->getVerifyData();
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

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->getVerifyData();
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
            'number' => '2017090633010146',
            'paymentVendorId' => '9999',
            'amount' => '1.00',
            'orderId' => '201710050000001416',
            'orderCreateDate' => '2017-10-05 16:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.xiaohongyu.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->getVerifyData();
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
            'number' => '2017090633010146',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710050000001416',
            'orderCreateDate' => '2017-10-05 16:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="utf-8"?><message>' .
            '<sign>1E94471160D376F6FAFC6DCE680037D5</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090633010146',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710050000001416',
            'orderCreateDate' => '2017-10-05 16:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.xiaohongyu.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setContainer($this->container);
        $heSePay->setClient($this->client);
        $heSePay->setResponse($response);
        $heSePay->setOptions($sourceData);
        $heSePay->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><desc>交易完成</desc>' .
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPVVLMTJYNDM=</qrCode></detail>' .
            '<sign>1E94471160D376F6FAFC6DCE680037D5</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090633010146',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710050000001416',
            'orderCreateDate' => '2017-10-05 16:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.xiaohongyu.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setContainer($this->container);
        $heSePay->setClient($this->client);
        $heSePay->setResponse($response);
        $heSePay->setOptions($sourceData);
        $heSePay->getVerifyData();
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
            '<desc>交易失败（交易数据不正确）</desc></detail><sign>1C8B6E00E64C5A8BDCC4ED2E07989F41</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090633010146',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710050000001416',
            'orderCreateDate' => '2017-10-05 16:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.xiaohongyu.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setContainer($this->container);
        $heSePay->setClient($this->client);
        $heSePay->setResponse($response);
        $heSePay->setOptions($sourceData);
        $heSePay->getVerifyData();
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

        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>00</code><desc>交易完成</desc>' .
            '</detail><sign>1E94471160D376F6FAFC6DCE680037D5</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090633010146',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710050000001416',
            'orderCreateDate' => '2017-10-05 16:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.xiaohongyu.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setContainer($this->container);
        $heSePay->setClient($this->client);
        $heSePay->setResponse($response);
        $heSePay->setOptions($sourceData);
        $heSePay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '<?xml version="1.0" encoding="utf-8"?><message><detail><code>00</code><desc>交易完成</desc>' .
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPVVLMTJYNDM=</qrCode></detail>' .
            '<sign>1E94471160D376F6FAFC6DCE680037D5</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '2017090633010146',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710050000001416',
            'orderCreateDate' => '2017-10-05 16:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.xiaohongyu.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setContainer($this->container);
        $heSePay->setClient($this->client);
        $heSePay->setResponse($response);
        $heSePay->setOptions($sourceData);
        $verifyData = $heSePay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('weixin://wxpay/bizpayurl?pr=UK12X43', $heSePay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '2017090633010146',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201710050000001416',
            'orderCreateDate' => '2017-10-05 16:00:44',
            'username' => 'php1test',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.gate.xiaohongyu.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $verifyData = $heSePay->getVerifyData();

        $this->assertEquals('2017090633010146', $verifyData['merId']);
        $this->assertEquals('201710050000001416', $verifyData['tradeNo']);
        $this->assertEquals('20171005', $verifyData['tradeDate']);
        $this->assertEquals('1.00', $verifyData['amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notifyUrl']);
        $this->assertEquals('php1test', $verifyData['summary']);
        $this->assertEquals('111.235.135.54', $verifyData['clientIp']);
        $this->assertEquals('20ced1f43563bd0682462644d0ba93a9', $verifyData['sign']);
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

        $heSePay = new HeSePay();
        $heSePay->verifyOrderPayment([]);
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

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->verifyOrderPayment([]);
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
            'service' => 'TRADE.SCANPAY',
            'merId' => '2017090633010146',
            'tradeNo' => '201710050000001416',
            'tradeDate' => '20171005',
            'opeNo' => '147504',
            'opeDate' => '20171005',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171005160044',
            'notifyType' => '1',
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->verifyOrderPayment([]);
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
            'service' => 'TRADE.SCANPAY',
            'merId' => '2017090633010146',
            'tradeNo' => '201710050000001416',
            'tradeDate' => '20171005',
            'opeNo' => '147504',
            'opeDate' => '20171005',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171005160044',
            'sign' => 'B8BDBE87E6CF4F8B4AAA09E9DBD51D30',
            'notifyType' => '1',
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->verifyOrderPayment([]);
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
            'service' => 'TRADE.SCANPAY',
            'merId' => '2017090633010146',
            'tradeNo' => '201710050000001416',
            'tradeDate' => '20171005',
            'opeNo' => '147504',
            'opeDate' => '20171005',
            'amount' => '0.01',
            'status' => '0',
            'extra' => '',
            'payTime' => '20171005160044',
            'sign' => '5e8fa24155b94fb461111cafc5f786d8',
            'notifyType' => '1',
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->verifyOrderPayment([]);
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
            'service' => 'TRADE.SCANPAY',
            'merId' => '2017090633010146',
            'tradeNo' => '201710050000001416',
            'tradeDate' => '20171005',
            'opeNo' => '147504',
            'opeDate' => '20171005',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171005160044',
            'sign' => '007cf72fb1f921504d5542e1391957a4',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '2017090633010147',
            'amount' => '0.01',
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->verifyOrderPayment($entry);
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
            'service' => 'TRADE.SCANPAY',
            'merId' => '2017090633010146',
            'tradeNo' => '201710050000001416',
            'tradeDate' => '20171005',
            'opeNo' => '147504',
            'opeDate' => '20171005',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171005160044',
            'sign' => '007cf72fb1f921504d5542e1391957a4',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201710050000001416',
            'amount' => '1.00',
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'service' => 'TRADE.SCANPAY',
            'merId' => '2017090633010146',
            'tradeNo' => '201710050000001416',
            'tradeDate' => '20171005',
            'opeNo' => '147504',
            'opeDate' => '20171005',
            'amount' => '0.01',
            'status' => '1',
            'extra' => '',
            'payTime' => '20171005160044',
            'sign' => '007cf72fb1f921504d5542e1391957a4',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201710050000001416',
            'amount' => '0.01',
        ];

        $heSePay = new HeSePay();
        $heSePay->setPrivateKey('test');
        $heSePay->setOptions($sourceData);
        $heSePay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $heSePay->getMsg());
    }
}