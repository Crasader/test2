<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\PointPay;
use Buzz\Message\Response;

class PointPayTest extends DurianTestCase
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

        $pointPay = new PointPay();
        $pointPay->getVerifyData();
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

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->getVerifyData();
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
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '100',
            'number' => '800258',
            'orderId' => '201803260000011401',
            'amount' => '100',
            'orderCreateDate' => '2018-03-26 15:40:00',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $pointPay->getVerifyData();
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '800258',
            'orderId' => '201803260000011401',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-26 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $pointPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少msg
     */
    public function testPayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"code":"00","data":{"merchantCode":"800258","orderId":"P800258451420180326111547004905"' .
            ',"url":"https://qpay.qq.com/qr/507da329","outOrderId":"201803260000011401",' .
            '"sign":"72b75b429c8c4ce5182db341a95f8727"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '800258',
            'orderId' => '201803260000011401',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-26 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setContainer($this->container);
        $pointPay->setClient($this->client);
        $pointPay->setResponse($response);
        $pointPay->setOptions($options);
        $pointPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回結果失敗
     */
    public function testPayReturnButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'arrivalType参数错误',
            180130
        );

        $result = '{"code":10003,"msg":"arrivalType参数错误"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '800258',
            'orderId' => '201803260000011401',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-26 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setContainer($this->container);
        $pointPay->setClient($this->client);
        $pointPay->setResponse($response);
        $pointPay->setOptions($options);
        $pointPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"code":"00","msg":"","data":{"merchantCode":"800258","orderId":"P800258451420180326111547004905"' .
            ',"outOrderId":"201803260000011401","sign":"72b75b429c8c4ce5182db341a95f8727"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '800258',
            'orderId' => '201803260000011401',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-26 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setContainer($this->container);
        $pointPay->setClient($this->client);
        $pointPay->setResponse($response);
        $pointPay->setOptions($options);
        $pointPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '{"code":"00","msg":"","data":{"merchantCode":"800258","orderId":"P800258451420180326111547004905"' .
            ',"url":"https://qpay.qq.com/qr/507da329","outOrderId":"201803260000011401",' .
            '"sign":"72b75b429c8c4ce5182db341a95f8727"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '800258',
            'orderId' => '201803260000011401',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-26 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setContainer($this->container);
        $pointPay->setClient($this->client);
        $pointPay->setResponse($response);
        $pointPay->setOptions($options);
        $verifyData = $pointPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('https://qpay.qq.com/qr/507da329', $pointPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1',
            'number' => '800257',
            'orderId' => '201803230000011380',
            'amount' => '100',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'postUrl' => 'https://api.judzf.com',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $requestData = $pointPay->getVerifyData();

        $this->assertEquals('800257', $requestData['params']['merchantCode']);
        $this->assertEquals('201803230000011380', $requestData['params']['outOrderId']);
        $this->assertEquals('10000.0', $requestData['params']['totalAmount']);
        $this->assertEquals('', $requestData['params']['goodsName']);
        $this->assertEquals('', $requestData['params']['goodsExplain']);
        $this->assertEquals('20180323154000', $requestData['params']['orderCreateTime']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $requestData['params']['merUrl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $requestData['params']['noticeUrl']);
        $this->assertEquals('ICBC', $requestData['params']['bankCode']);
        $this->assertEquals('800', $requestData['params']['bankCardType']);
        $this->assertEquals('', $requestData['params']['ext']);
        $this->assertEquals('47224acab5bf71d64cda383d5ebb7de7', $requestData['params']['sign']);
    }

    /**
     * 測試銀聯在線支付
     */
    public function testQuickPay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '278',
            'number' => '800257',
            'orderId' => '201803230000011380',
            'amount' => '100',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'postUrl' => 'https://api.judzf.com',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $requestData = $pointPay->getVerifyData();

        $this->assertEquals('800257', $requestData['params']['merchantCode']);
        $this->assertEquals('201803230000011380', $requestData['params']['outOrderId']);
        $this->assertEquals('10000.0', $requestData['params']['totalAmount']);
        $this->assertEquals('', $requestData['params']['goodsName']);
        $this->assertEquals('', $requestData['params']['goodsExplain']);
        $this->assertEquals('20180323154000', $requestData['params']['orderCreateTime']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $requestData['params']['merUrl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $requestData['params']['noticeUrl']);
        $this->assertEquals('ICBC', $requestData['params']['bankCode']);
        $this->assertEquals('800', $requestData['params']['bankCardType']);
        $this->assertEquals('', $requestData['params']['ext']);
        $this->assertEquals('41f9efdd7b12340d2e7366216d9ff5de', $requestData['params']['sign']);
        $this->assertEquals('201803230000011380', $requestData['params']['bankCardNo']);
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

        $pointPay = new PointPay();
        $pointPay->verifyOrderPayment([]);
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

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'ext' => '',
            'instructCode' => 'P800257391120180323173717186150',
            'merchantCode' => '800257',
            'outOrderId' => '201803230000011380',
            'totalAmount' => '500',
            'transTime' => '20180323173811',
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $pointPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign錯誤
     */
    public function testReturnWithWrongSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'ext' => '',
            'instructCode' => 'P800257391120180323173717186150',
            'merchantCode' => '800257',
            'outOrderId' => '201803230000011380',
            'totalAmount' => '500',
            'transTime' => '20180323173811',
            'sign' => '705555C41AC3FC6A0C200B8D32FB2E9'
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $pointPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單號不一樣
     */
    public function testReturnWithErrorOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'ext' => '',
            'instructCode' => 'P800257391120180323173717186150',
            'merchantCode' => '800257',
            'outOrderId' => '201803230000011380',
            'totalAmount' => '500',
            'transTime' => '20180323173811',
            'sign' => '8DE4A16EDAB269E2EC6E7B2F758327F4',
        ];

        $entry = ['id' => '201503220000000555'];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $pointPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不一樣
     */
    public function testReturnWithErrorAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'ext' => '',
            'instructCode' => 'P800257391120180323173717186150',
            'merchantCode' => '800257',
            'outOrderId' => '201803230000011380',
            'totalAmount' => '500',
            'transTime' => '20180323173811',
            'sign' => '8DE4A16EDAB269E2EC6E7B2F758327F4',
        ];

        $entry = [
            'id' => '201803230000011380',
            'amount' => '0.1'
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $pointPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'ext' => '',
            'instructCode' => 'P800257391120180323173717186150',
            'merchantCode' => '800257',
            'outOrderId' => '201803230000011380',
            'totalAmount' => '500',
            'transTime' => '20180323173811',
            'sign' => '8DE4A16EDAB269E2EC6E7B2F758327F4',
        ];

        $entry = [
            'id' => '201803230000011380',
            'amount' => '5'
        ];

        $pointPay = new PointPay();
        $pointPay->setPrivateKey('test');
        $pointPay->setOptions($options);
        $pointPay->verifyOrderPayment($entry);
    }
}
