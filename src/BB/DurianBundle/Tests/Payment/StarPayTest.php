<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\StarPay;
use Buzz\Message\Response;

class StarPayTest extends DurianTestCase
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
            ->will($this->returnValue(null));

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

        $starPay = new StarPay();
        $starPay->getVerifyData();
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

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '9999',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => '',
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回detail
     */
    public function testQrCodePayReturnWithoutDetail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8"?><message><sign>42D4674C74C01BC72914C72FAFF0</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $starPay = new StarPay();
        $starPay->setContainer($this->container);
        $starPay->setClient($this->client);
        $starPay->setResponse($response);
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回code
     */
    public function testQrCodePayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><desc>交易完成</desc>'.
            '<qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPXRUMzV0YUw=</qrCode>'.
            '</detail><sign>12065FD847D4510CAE2E99E5A7C9924D</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $starPay = new StarPay();
        $starPay->setContainer($this->container);
        $starPay->setClient($this->client);
        $starPay->setResponse($response);
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败（签名错误）',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><code>05</code>'.
            '<desc>交易失败（签名错误）</desc></detail><sign>22D4C39A46E4D5A63A1599CBE9890A06</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $starPay = new StarPay();
        $starPay->setContainer($this->container);
        $starPay->setClient($this->client);
        $starPay->setResponse($response);
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回qrCode
     */
    public function testQrCodePayReturnWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><code>00</code>'.
            '<desc>交易完成</desc></detail><sign>12065FD847D4510CAE2E99E5A7C9924D</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $starPay = new StarPay();
        $starPay->setContainer($this->container);
        $starPay->setClient($this->client);
        $starPay->setResponse($response);
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><message><detail><code>00</code>'.
            '<desc>交易完成</desc><qrCode>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPXRUMzV0YUw=</qrCode>'.
            '</detail><sign>12065FD847D4510CAE2E99E5A7C9924D</sign></message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $starPay = new StarPay();
        $starPay->setContainer($this->container);
        $starPay->setClient($this->client);
        $starPay->setResponse($response);
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $data = $starPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=tT35taL', $starPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1097',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $data = $starPay->getVerifyData();

        $this->assertEquals('TRADE.H5PAY', $data['service']);
        $this->assertEquals('1.0.0.0', $data['version']);
        $this->assertEquals($options['number'], $data['merId']);
        $this->assertEquals($options['orderId'], $data['tradeNo']);
        $this->assertEquals('20170824', $data['tradeDate']);
        $this->assertEquals($options['amount'], $data['amount']);
        $this->assertEquals($options['notify_url'], $data['notifyUrl']);
        $this->assertEquals('', $data['extra']);
        $this->assertEquals($options['username'], $data['summary']);
        $this->assertEquals('', $data['expireTime']);
        $this->assertEquals($options['ip'], $data['clientIp']);
        $this->assertEquals('6928aeaa0c63d51f1123c5223f924a1a', $data['sign']);
        $this->assertEquals('2', $data['typeId']);
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '2017080712010002',
            'orderId' => '201709200000004758',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $data = $starPay->getVerifyData();

        $this->assertEquals('TRADE.B2C', $data['service']);
        $this->assertEquals('1.0.0.0', $data['version']);
        $this->assertEquals($options['number'], $data['merId']);
        $this->assertEquals($options['orderId'], $data['tradeNo']);
        $this->assertEquals('20170824', $data['tradeDate']);
        $this->assertEquals($options['amount'], $data['amount']);
        $this->assertEquals($options['notify_url'], $data['notifyUrl']);
        $this->assertEquals('', $data['extra']);
        $this->assertEquals($options['username'], $data['summary']);
        $this->assertEquals('', $data['expireTime']);
        $this->assertEquals($options['ip'], $data['clientIp']);
        $this->assertEquals('04d3b8d224708ece1257c782b1bcc082', $data['sign']);
        $this->assertEquals('ICBC', $data['bankId']);
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

        $starPay = new StarPay();
        $starPay->verifyOrderPayment([]);
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

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017080712010002',
            'tradeNo' => '201709200000004758',
            'tradeDate' => '20170920',
            'opeNo' => '3800043',
            'opeDate' => '20170920',
            'amount' => '0.10',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170920103224',
            'notifyType' => '1',
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->verifyOrderPayment([]);
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
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017080712010002',
            'tradeNo' => '201709200000004758',
            'tradeDate' => '20170920',
            'opeNo' => '3800043',
            'opeDate' => '20170920',
            'amount' => '0.10',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170920103224',
            'sign' => '07FDD1B215C6BD14C37BD0C541B5F4F9',
            'notifyType' => '1',
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->verifyOrderPayment([]);
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

        $options = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017080712010002',
            'tradeNo' => '201709200000004758',
            'tradeDate' => '20170920',
            'opeNo' => '3800043',
            'opeDate' => '20170920',
            'amount' => '0.10',
            'status' => '2',
            'extra' => '',
            'payTime' => '20170920103224',
            'sign' => 'E6B161087D8AAFECF9201702CD1CA928',
            'notifyType' => '1',
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->verifyOrderPayment([]);
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
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017080712010002',
            'tradeNo' => '201709200000004758',
            'tradeDate' => '20170920',
            'opeNo' => '3800043',
            'opeDate' => '20170920',
            'amount' => '0.10',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170920103224',
            'sign' => '28F8DA6DA75351C31A2AAFE2749932F3',
            'notifyType' => '1',
        ];

        $entry = ['id' => '201503220000000555'];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->verifyOrderPayment($entry);
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
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017080712010002',
            'tradeNo' => '201709200000004758',
            'tradeDate' => '20170920',
            'opeNo' => '3800043',
            'opeDate' => '20170920',
            'amount' => '0.10',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170920103224',
            'sign' => '28F8DA6DA75351C31A2AAFE2749932F3',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201709200000004758',
            'amount' => '15.00',
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '2017080712010002',
            'tradeNo' => '201709200000004758',
            'tradeDate' => '20170920',
            'opeNo' => '3800043',
            'opeDate' => '20170920',
            'amount' => '0.10',
            'status' => '1',
            'extra' => '',
            'payTime' => '20170920103224',
            'sign' => '28F8DA6DA75351C31A2AAFE2749932F3',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201709200000004758',
            'amount' => '0.1',
        ];

        $starPay = new StarPay();
        $starPay->setPrivateKey('test');
        $starPay->setOptions($options);
        $starPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $starPay->getMsg());
    }
}
