<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HaoHuiPay;
use Buzz\Message\Response;

class HaoHuiPayTest extends DurianTestCase
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

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->getVerifyData();
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

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->getVerifyData();
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
            'number' => 'esball',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'ip' => '111.235.135.54',
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->getVerifyData();
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
            'number' => 'esball',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
            'ip' => '111.235.135.54',
            'merchant_extra' => ['Token' => '123456'],
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->getVerifyData();
    }

    /**
     * 測試支付時未返回retCode
     */
    public function testPayNoReturnRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'uuid' => '5b02b1e803335',
            'qrContent' => 'HTTPS://QR.ALIPAY.COM/FKX04444TRRTEFXZKRJ07F?t=1525850885935',
            'realCharge' => '1.00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'esball',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'merchant_extra' => ['Token' => '123456'],
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setContainer($this->container);
        $haoHuiPay->setClient($this->client);
        $haoHuiPay->setResponse($response);
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '未開放',
            180130
        );

        $result = [
            'retCode' => '-101',
            'retMsg' => '未開放',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'esball',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'merchant_extra' => ['Token' => '123456'],
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setContainer($this->container);
        $haoHuiPay->setClient($this->client);
        $haoHuiPay->setResponse($response);
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且未回傳retMsg
     */
    public function testPayReturnNotSuccessAndNoReturRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = ['retCode' => '-101'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'esball',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'merchant_extra' => ['Token' => '123456'],
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setContainer($this->container);
        $haoHuiPay->setClient($this->client);
        $haoHuiPay->setResponse($response);
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->getVerifyData();
    }

    /**
     * 測試支付時未返回redirectURL
     */
    public function testPayNoReturnRedirectURL()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'retCode' => '0',
            'uuid' => '5b02b1e803335',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'esball',
            'paymentVendorId' => '1098',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'merchant_extra' => ['Token' => '123456'],
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setContainer($this->container);
        $haoHuiPay->setClient($this->client);
        $haoHuiPay->setResponse($response);
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->getVerifyData();
    }

    /**
     * 測試支付時未返回qrContent
     */
    public function testPayNoReturnQrContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'retCode' => '0',
            'uuid' => '5b02b1e803335',
            'realCharge' => '1.00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'esball',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'merchant_extra' => ['Token' => '123456'],
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setContainer($this->container);
        $haoHuiPay->setClient($this->client);
        $haoHuiPay->setResponse($response);
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = [
            'retCode' => '0',
            'uuid' => '5b02b1e803335',
            'redirectURL' => 'https://www.honor6868.com/pay/route/version/h5/7297b53c',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'esball',
            'paymentVendorId' => '1098',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'merchant_extra' => ['Token' => '123456'],
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setContainer($this->container);
        $haoHuiPay->setClient($this->client);
        $haoHuiPay->setResponse($response);
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $data = $haoHuiPay->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals('https://www.honor6868.com/pay/route/version/h5/7297b53c', $data['post_url']);
        $this->assertEquals('GET', $haoHuiPay->getPayMethod());
    }

    /**
     * 測試掃碼支付
     */
    public function testQrcodePay()
    {
        $result = [
            'retCode' => '0',
            'uuid' => '5b02b1e803335',
            'qrContent' => 'HTTPS://QR.ALIPAY.COM/FKX04444TRRTEFXZKRJ07F?t=1525850885935',
            'realCharge' => '1.00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'esball',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'merchant_extra' => ['Token' => '123456'],
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setContainer($this->container);
        $haoHuiPay->setClient($this->client);
        $haoHuiPay->setResponse($response);
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $data = $haoHuiPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertSame('HTTPS://QR.ALIPAY.COM/FKX04444TRRTEFXZKRJ07F?t=1525850885935', $haoHuiPay->getQrcode());
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

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->verifyOrderPayment([]);
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

        $sourceData = [];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->verifyOrderPayment([]);
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
            'account' => 'esball',
            'nonceStr' => 'd043701998028f22e1838136eb1202d6a99a18fdbb859168562372c09001e212',
            'orderNo' => '201805210000013094',
            'payMoney' => '1.00',
            'payStatus' => 'success',
            'uuid' => '5b02b1e803335',
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->verifyOrderPayment([]);
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
            'account' => 'esball',
            'nonceStr' => 'd043701998028f22e1838136eb1202d6a99a18fdbb859168562372c09001e212',
            'orderNo' => '201805210000013094',
            'payMoney' => '1.00',
            'payStatus' => 'success',
            'uuid' => '5b02b1e803335',
            'sign' => '123',
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->verifyOrderPayment([]);
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
            'account' => 'esball',
            'nonceStr' => 'd043701998028f22e1838136eb1202d6a99a18fdbb859168562372c09001e212',
            'orderNo' => '201805210000013094',
            'payMoney' => '1.00',
            'payStatus' => 'timeout',
            'realCharge' => '1.00',
            'uuid' => '5b02b1e803335',
            'sign' => '0dd7cd9c4148c72a67af09f7565317c2950447564599139b82c9b0206ebbb875',
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'account' => 'esball',
            'nonceStr' => 'd043701998028f22e1838136eb1202d6a99a18fdbb859168562372c09001e212',
            'orderNo' => '201805210000013094',
            'payMoney' => '1.00',
            'payStatus' => 'success',
            'uuid' => '5b02b1e803335',
            'sign' => 'e6b10af5ed5e93207435c8fc1394c308f7405f83132f252d43c679ccea2e142a',
        ];

        $entry = ['id' => '201704100000002210'];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->verifyOrderPayment($entry);
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

        $sourceData = [
            'account' => 'esball',
            'nonceStr' => 'd043701998028f22e1838136eb1202d6a99a18fdbb859168562372c09001e212',
            'orderNo' => '201805210000013094',
            'payMoney' => '1.00',
            'payStatus' => 'success',
            'uuid' => '5b02b1e803335',
            'sign' => 'e6b10af5ed5e93207435c8fc1394c308f7405f83132f252d43c679ccea2e142a',
        ];

        $entry = [
            'id' => '201805210000013094',
            'amount' => '100',
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'account' => 'esball',
            'nonceStr' => 'd043701998028f22e1838136eb1202d6a99a18fdbb859168562372c09001e212',
            'orderNo' => '201805210000013094',
            'payMoney' => '1.00',
            'payStatus' => 'success',
            'uuid' => '5b02b1e803335',
            'sign' => 'e6b10af5ed5e93207435c8fc1394c308f7405f83132f252d43c679ccea2e142a',
        ];

        $entry = [
            'id' => '201805210000013094',
            'amount' => '1.00',
        ];

        $haoHuiPay = new HaoHuiPay();
        $haoHuiPay->setPrivateKey('test');
        $haoHuiPay->setOptions($sourceData);
        $haoHuiPay->verifyOrderPayment($entry);

        $this->assertEquals('888888', $haoHuiPay->getMsg());
    }
}
