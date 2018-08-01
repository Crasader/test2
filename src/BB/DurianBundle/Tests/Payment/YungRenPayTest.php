<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YungRenPay;
use Buzz\Message\Response;

class YungRenPayTest extends DurianTestCase
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

        $yungRenPay = new YungRenPay();
        $yungRenPay->getVerifyData();
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

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->getVerifyData();
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
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '99',
            'ip' => '192.168.0.100',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定platformID
     */
    public function testPayWithoutMerchantExtraPlatformID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1',
            'merchant_extra' => [],
            'ip' => '192.168.0.100',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1',
            'merchant_extra' => ['platformID' => '210001440013524'],
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $requestData = $yungRenPay->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('1.0.0.0', $requestData['apiVersion']);
        $this->assertEquals('210001440013524', $requestData['platformID']);
        $this->assertEquals('210001440013524', $requestData['merchNo']);
        $this->assertEquals('201806260000014664', $requestData['orderNo']);
        $this->assertEquals('20180626', $requestData['tradeDate']);
        $this->assertEquals('100.00', $requestData['amt']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('201806260000014664', $requestData['tradeSummary']);
        $this->assertEquals('62eb7c656ae914742168be7a4c7f7fc4', $requestData['signMsg']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
    }

    /**
     * 測試支付銀行為二維，但沒帶入verify_url
     */
    public function testPayWithQrcodeWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'merchant_extra' => ['platformID' => '210001440013524'],
            'ip' => '192.168.0.100',
            'verify_url' => '',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，但沒返回resultCode
     */
    public function testPayWithQrcodeWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'merchant_extra' => ['platformID' => '210001440013524'],
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"sign":"74EE92B47E302A2741870576643DF150","message":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $yungRenPay = new YungRenPay();
        $yungRenPay->setContainer($this->container);
        $yungRenPay->setClient($this->client);
        $yungRenPay->setResponse($response);
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，但返回失敗
     */
    public function testPayWithQrcodeButReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户未开通该支付方式[商户未开通该支付方式',
            180130
        );

        $options = [
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'merchant_extra' => ['platformID' => '210001440013524'],
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"resultCode":"10","message":"商户未开通该支付方式[商户未开通该支付方式]"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $yungRenPay = new YungRenPay();
        $yungRenPay->setContainer($this->container);
        $yungRenPay->setClient($this->client);
        $yungRenPay->setResponse($response);
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->getVerifyData();
    }

    /**
     * 測試支付銀行為二維失敗，但沒返回message
     */
    public function testPayWithQrcodeFailedWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210001440013524'],
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"resultCode":"10"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $yungRenPay = new YungRenPay();
        $yungRenPay->setContainer($this->container);
        $yungRenPay->setClient($this->client);
        $yungRenPay->setResponse($response);
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，沒返回code
     */
    public function testPayWithQrcodeWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'merchant_extra' => ['platformID' => '210001440013524'],
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"sign":"74EE92B47E302A2741870576643DF150","resultCode":"00","message":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $yungRenPay = new YungRenPay();
        $yungRenPay->setContainer($this->container);
        $yungRenPay->setClient($this->client);
        $yungRenPay->setResponse($response);
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->getVerifyData();
    }

    /**
     * 測試支付銀行為二維
     */
    public function testPayWithQrcode()
    {
        $options = [
            'number' => '210001440013524',
            'orderId' => '201806260000014664',
            'orderCreateDate' => '2018-06-26 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'merchant_extra' => ['platformID' => '210001440013524'],
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"code":"aHR0cHM6Ly9xcGF5LnFxLmNvbS9xci82NjM3NGFjMA==","sign":' .
            '"74EE92B47E302A2741870576643DF150","resultCode":"00","message":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $yungRenPay = new YungRenPay();
        $yungRenPay->setContainer($this->container);
        $yungRenPay->setClient($this->client);
        $yungRenPay->setResponse($response);
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $requestData = $yungRenPay->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('https://qpay.qq.com/qr/66374ac0', $yungRenPay->getQrcode());
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yungRenPay = new YungRenPay();
        $yungRenPay->verifyOrderPayment([]);
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

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180627143157',
            'tradeAmt' => '0.01',
            'merchNo' => '210001440013524',
            'merchParam' => '',
            'orderNo' => '201806260000014664',
            'tradeDate' => '20180626',
            'accNo' => '42052752',
            'accDate' => '20180627',
            'orderStatus' => '1',
            'notifyType' => '1',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180627143157',
            'tradeAmt' => '0.01',
            'merchNo' => '210001440013524',
            'merchParam' => '',
            'orderNo' => '201806260000014664',
            'tradeDate' => '20180626',
            'accNo' => '42052752',
            'accDate' => '20180627',
            'orderStatus' => '1',
            'signMsg' => '6FB91E548552EB6C2B83243CF8BBB975',
            'notifyType' => '1',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單未支付
     */
    public function testReturnButUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180627143157',
            'tradeAmt' => '0.01',
            'merchNo' => '210001440013524',
            'merchParam' => '',
            'orderNo' => '201806260000014664',
            'tradeDate' => '20180626',
            'accNo' => '42052752',
            'accDate' => '20180626',
            'orderStatus' => '0',
            'signMsg' => '727b1308113946ff49c4153a104ea9ce',
            'notifyType' => '1',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180627143157',
            'tradeAmt' => '0.01',
            'merchNo' => '210001440013524',
            'merchParam' => '',
            'orderNo' => '201806260000014664',
            'tradeDate' => '20180626',
            'accNo' => '42052752',
            'accDate' => '20180626',
            'orderStatus' => '2',
            'signMsg' => 'ddb88ac82a29cbaad9d1c19899634736',
            'notifyType' => '1',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180627143157',
            'tradeAmt' => '0.01',
            'merchNo' => '210001440013524',
            'merchParam' => '',
            'orderNo' => '201806260000014664',
            'tradeDate' => '20180626',
            'accNo' => '42052752',
            'accDate' => '20180626',
            'orderStatus' => '1',
            'signMsg' => '530e80eaacb410bc29177840ca251fe0',
            'notifyType' => '1',
        ];

        $entry = ['id' => '201503220000000321'];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->verifyOrderPayment($entry);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180627143157',
            'tradeAmt' => '0.01',
            'merchNo' => '210001440013524',
            'merchParam' => '',
            'orderNo' => '201806260000014664',
            'tradeDate' => '20180626',
            'accNo' => '42052752',
            'accDate' => '20180626',
            'orderStatus' => '1',
            'signMsg' => '530e80eaacb410bc29177840ca251fe0',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201806260000014664',
            'amount' => '10.00',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180627143157',
            'tradeAmt' => '0.01',
            'merchNo' => '210001440013524',
            'merchParam' => '',
            'orderNo' => '201806260000014664',
            'tradeDate' => '20180626',
            'accNo' => '42052752',
            'accDate' => '20180626',
            'orderStatus' => '1',
            'signMsg' => '530e80eaacb410bc29177840ca251fe0',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201806260000014664',
            'amount' => '0.01',
        ];

        $yungRenPay = new YungRenPay();
        $yungRenPay->setPrivateKey('test');
        $yungRenPay->setOptions($options);
        $yungRenPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yungRenPay->getMsg());
    }
}
