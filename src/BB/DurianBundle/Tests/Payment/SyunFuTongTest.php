<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SyunFuTong;
use Buzz\Message\Response;

class SyunFuTongTest extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $syunFuTong = new SyunFuTong();
        $syunFuTong->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->getVerifyData();
    }

    /**
     * 測試支付加密時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'Mer201612250234',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201701130000001150',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->getVerifyData();
    }

    /**
     * 測試支付時未返回StateCode
     */
    public function testPayNoReturnStateCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"merNo":"Mer201612250234","msg":"提交成功","orderNum":"201701130000001150",' .
            '"qrcodeUrl":"weixin://wxpay/bizpayurl?pr=MnctIET",' .
            '"sign":"EC4BD2C985559E3A44BB6A7272952146"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'Mer201612250234',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201701130000001150',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'postUrl' => 'payment.http.wx.h8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setContainer($this->container);
        $syunFuTong->setClient($this->client);
        $syunFuTong->setResponse($response);
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->getVerifyData();
    }

    /**
     * 測試支付時未返回msg
     */
    public function testPayNoReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"merNo":"Mer201612250234","orderNum":"201701130000001150",' .
            '"qrcodeUrl":"weixin://wxpay/bizpayurl?pr=MnctIET",' .
            '"sign":"EC4BD2C985559E3A44BB6A7272952146","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'Mer201612250234',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201701130000001150',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'postUrl' => 'payment.http.wx.h8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setContainer($this->container);
        $syunFuTong->setClient($this->client);
        $syunFuTong->setResponse($response);
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->getVerifyData();
    }

    /**
     * 測試支付失敗
     */
    public function testPayFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败',
            180130
        );

        $result = '{"stateCode":"99","msg":"交易失败"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'Mer201612250234',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201701130000001150',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'postUrl' => 'payment.http.wx.h8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setContainer($this->container);
        $syunFuTong->setClient($this->client);
        $syunFuTong->setResponse($response);
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->getVerifyData();
    }

    /**
     * 測試支付時未返回qrcode
     */
    public function testPayNoReturnQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"merNo":"Mer201612250234","msg":"提交成功","orderNum":"201701130000001150",' .
            '"sign":"EC4BD2C985559E3A44BB6A7272952146","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'Mer201612250234',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201701130000001150',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.zfb.h8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setContainer($this->container);
        $syunFuTong->setClient($this->client);
        $syunFuTong->setResponse($response);
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = '{"merNo":"Mer201612250234","msg":"提交成功","orderNum":"201701130000001150",' .
            '"qrcodeUrl":"weixin://wxpay/bizpayurl?pr=MnctIET",' .
            '"sign":"EC4BD2C985559E3A44BB6A7272952146","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'Mer201612250234',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201701130000001150',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'postUrl' => 'payment.http.wx.h8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setContainer($this->container);
        $syunFuTong->setClient($this->client);
        $syunFuTong->setResponse($response);
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $data = $syunFuTong->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=MnctIET', $syunFuTong->getQrcode());
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $syunFuTong = new SyunFuTong();
        $syunFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = ['payResult' => ''];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201612250234',
            'netway' => 'WX',
            'orderNum' => '201701130000001150',
            'payResult' => '00',
            'payDate' => '2017-01-13 09:39:56',
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時加密簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201612250234',
            'netway' => 'WX',
            'orderNum' => '201701130000001150',
            'payResult' => '00',
            'payDate' => '2017-01-13 09:39:56',
            'sign' => '0B99034CAEFF71D3E88CC49647C3108A',
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201612250234',
            'netway' => 'WX',
            'orderNum' => '201701130000001150',
            'payResult' => '99',
            'payDate' => '2017-01-13 09:39:56',
            'sign' => '6EECA0C811D7C9EF73B406592594CF44',
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201612250234',
            'netway' => 'WX',
            'orderNum' => '201701130000000000',
            'payResult' => '00',
            'payDate' => '2017-01-13 09:39:56',
            'sign' => 'C5C4DB55FAB6D411C4A00D8DF68D38A3',
        ];

        $entry = ['id' => '201701130000001150'];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'amount' => '1000',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201612250234',
            'netway' => 'WX',
            'orderNum' => '201701130000001150',
            'payResult' => '00',
            'payDate' => '2017-01-13 09:39:56',
            'sign' => '0F1DC570933E76190D3133A29318B05C',
        ];

        $entry = [
            'id' => '201701130000001150',
            'amount' => '1',
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201612250234',
            'netway' => 'WX',
            'orderNum' => '201701130000001150',
            'payResult' => '00',
            'payDate' => '2017-01-13 09:39:56',
            'sign' => '0BAAB843A201C2893CA1481B560C02A0',
        ];

        $entry = [
            'id' => '201701130000001150',
            'amount' => '1',
        ];

        $syunFuTong = new SyunFuTong();
        $syunFuTong->setPrivateKey('test');
        $syunFuTong->setOptions($sourceData);
        $syunFuTong->verifyOrderPayment($entry);

        $this->assertEquals('0', $syunFuTong->getMsg());
    }
}
