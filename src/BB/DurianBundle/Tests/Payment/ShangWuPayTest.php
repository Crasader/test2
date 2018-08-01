<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShangWuPay;
use Buzz\Message\Response;

class ShangWuPayTest extends DurianTestCase
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

        $shangWuPay = new ShangWuPay();
        $shangWuPay->getVerifyData();
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

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->getVerifyData();
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
            'number' => '62',
            'paymentVendorId' => '9999',
            'amount' => '10',
            'orderId' => '201804160000012183',
            'orderCreateDate' => '2018-04-16 12:26:41',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->getVerifyData();
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
            'number' => '62',
            'paymentVendorId' => '1090',
            'amount' => '10',
            'orderId' => '201804160000012183',
            'orderCreateDate' => '2018-04-16 12:26:41',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'verify_url' => '',
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->getVerifyData();
    }

    /**
     * 測試支付時返回error
     */
    public function testPayReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '当前支付不可用，请联系客服',
            180130
        );

        $result = ['error' => '当前支付不可用，请联系客服'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '62',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201804160000012183',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderCreateDate' => '2018-04-16 12:26:41',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setContainer($this->container);
        $shangWuPay->setClient($this->client);
        $shangWuPay->setResponse($response);
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->getVerifyData();
    }

    /**
     * 測試支付時未返回token
     */
    public function testPayNoReturnToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '62',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201804160000012183',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderCreateDate' => '2018-04-16 12:26:41',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setContainer($this->container);
        $shangWuPay->setClient($this->client);
        $shangWuPay->setResponse($response);
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->getVerifyData();
    }

    /**
     * 測試支付時token不為數字
     */
    public function testPayTokenNotNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['token' => '当前支付不可用，请联系客服'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '62',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201804160000012183',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderCreateDate' => '2018-04-16 12:26:41',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setContainer($this->container);
        $shangWuPay->setClient($this->client);
        $shangWuPay->setResponse($response);
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->getVerifyData();
    }

    /**
     * 測試支付token不為19個數字
     */
    public function testPayTokenNotEqual19()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['token' => '123456'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '62',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201804160000012183',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderCreateDate' => '2018-04-16 12:26:41',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setContainer($this->container);
        $shangWuPay->setClient($this->client);
        $shangWuPay->setResponse($response);
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = ['token' => '8314941523927684942'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '62',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201804160000012183',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderCreateDate' => '2018-04-16 12:26:41',
            'verify_url' => 'payment.http.test',
            'postUrl' => 'https://ebank.DuoYuanBao.com/payment/v1/order/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setContainer($this->container);
        $shangWuPay->setClient($this->client);
        $shangWuPay->setResponse($response);
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->getVerifyData();
        $data = $shangWuPay->getVerifyData();

        $postUrl = 'http://https://ebank.DuoYuanBao.com/payment/v1/order//pay/paystate/8314941523927684942';

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $shangWuPay->getPayMethod());
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

        $shangWuPay = new ShangWuPay();
        $shangWuPay->verifyOrderPayment([]);
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

        $sourceData = ['payResult' => ''];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳hash(加密簽名)
     */
    public function testReturnWithoutHash()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'id' => '201804160000012183',
            'money' => '10',
            'token' => '3601661523871422633',
            'time' => '1523871464976',
            // 'hash' => '1F51E1B446AC9654E91AEE160C430289',
            'state' => '1',
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->verifyOrderPayment([]);
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
            'id' => '201804160000012183',
            'money' => '10',
            'token' => '3601661523871422633',
            'time' => '1523871464976',
            'hash' => '123',
            'state' => '1',
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->verifyOrderPayment([]);
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
            'id' => '201804160000012183',
            'money' => '10',
            'token' => '3601661523871422633',
            'time' => '1523871464976',
            'hash' => '79069D10E00DEB082E624E2551B88A89',
            'state' => '0',
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->verifyOrderPayment([]);
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
            'id' => '201804160000012183',
            'money' => '10',
            'token' => '3601661523871422633',
            'time' => '1523871464976',
            'hash' => '79069D10E00DEB082E624E2551B88A89',
            'state' => '1',
        ];

        $entry = ['id' => '201704100000002210'];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->verifyOrderPayment($entry);
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
            'id' => '201804160000012183',
            'money' => '10',
            'token' => '3601661523871422633',
            'time' => '1523871464976',
            'hash' => '79069D10E00DEB082E624E2551B88A89',
            'state' => '1',
        ];

        $entry = [
            'id' => '201804160000012183',
            'amount' => '1',
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'id' => '201804160000012183',
            'money' => '10',
            'token' => '3601661523871422633',
            'time' => '1523871464976',
            'hash' => '79069D10E00DEB082E624E2551B88A89',
            'state' => '1',
        ];

        $entry = [
            'id' => '201804160000012183',
            'amount' => '10',
        ];

        $shangWuPay = new ShangWuPay();
        $shangWuPay->setPrivateKey('test');
        $shangWuPay->setOptions($sourceData);
        $shangWuPay->verifyOrderPayment($entry);

        $this->assertEquals('{"message":"成功"}', $shangWuPay->getMsg());
    }
}
