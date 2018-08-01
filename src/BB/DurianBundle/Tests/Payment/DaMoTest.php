<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DaMo;
use Buzz\Message\Response;

class DaMoTest extends DurianTestCase
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

        $daMo = new DaMo();
        $daMo->getVerifyData();
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

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->getVerifyData();
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
            'number' => '1000001810',
            'orderId' => '201804230000012383',
            'amount' => '100',
            'orderCreateDate' => '2018-04-23 15:40:00',
            'ip' => '111.235.135.54',
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setOptions($options);
        $daMo->getVerifyData();
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

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '1000001810',
            'orderId' => '201804230000012383',
            'amount' => '100',
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-04-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setOptions($options);
        $daMo->getVerifyData();
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

        $result = '{"data":{"sign":"A24C964DF011FBB71B13BA877D7AF3E6","merchantCode":"1000001810",' .
            '"url":"https://qr.95516.com/00010000/62372373877859864703261647018673","orderId":"922' .
            '180423000007","outOrderId":"201804230000012384"},"code":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '1000001810',
            'orderId' => '201804230000012383',
            'amount' => '100',
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-04-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setContainer($this->container);
        $daMo->setClient($this->client);
        $daMo->setResponse($response);
        $daMo->setOptions($options);
        $daMo->getVerifyData();
    }

    /**
     * 測試支付時返回結果失敗
     */
    public function testPayReturnButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验签失败',
            180130
        );

        $result = '{"code":"09003","msg":"验签失败"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '1000001810',
            'orderId' => '201804230000012383',
            'amount' => '100',
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-04-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setContainer($this->container);
        $daMo->setClient($this->client);
        $daMo->setResponse($response);
        $daMo->setOptions($options);
        $daMo->getVerifyData();
    }

    /**
     * 測試支付時返回缺少url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"data":{"sign":"A24C964DF011FBB71B13BA877D7AF3E6","merchantCode":"1000001810",' .
            '"orderId":"922180423000007","outOrderId":"201804230000012384"},"code":"00","msg":"\u6210\u529f"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '1000001810',
            'orderId' => '201804230000012383',
            'amount' => '100',
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-04-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setContainer($this->container);
        $daMo->setClient($this->client);
        $daMo->setResponse($response);
        $daMo->setOptions($options);
        $daMo->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = '{"data":{"sign":"A24C964DF011FBB71B13BA877D7AF3E6","merchantCode":"1000001810",' .
            '"url":"https://qr.95516.com/00010000/62372373877859864703261647018673","orderId":"922' .
            '180423000007","outOrderId":"201804230000012384"},"code":"00","msg":"\u6210\u529f"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '1000001810',
            'orderId' => '201804230000012383',
            'amount' => '100',
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-04-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setContainer($this->container);
        $daMo->setClient($this->client);
        $daMo->setResponse($response);
        $daMo->setOptions($options);
        $verifyData = $daMo->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('https://qr.95516.com/00010000/62372373877859864703261647018673', $daMo->getQrcode());
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

        $daMo = new DaMo();
        $daMo->verifyOrderPayment([]);
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

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->verifyOrderPayment([]);
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
            'instructCode' => '922180423000006',
            'transTime' => '20180423113826',
            'totalAmount' => '100',
            'merchantCode' => '1000001810',
            'ext' => '',
            'outOrderId' => '201804230000012383',
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setOptions($options);
        $daMo->verifyOrderPayment([]);
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
            'sign' => '3B2D4FC47E29F7F03E6661644827CCC7',
            'instructCode' => '922180423000006',
            'transTime' => '20180423113826',
            'totalAmount' => '100',
            'merchantCode' => '1000001810',
            'ext' => '',
            'outOrderId' => '201804230000012383',
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setOptions($options);
        $daMo->verifyOrderPayment([]);
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
            'sign' => '6440A1B466F434F54613AC2F2AE202E3',
            'instructCode' => '922180423000006',
            'transTime' => '20180423113826',
            'totalAmount' => '100',
            'merchantCode' => '1000001810',
            'ext' => '',
            'outOrderId' => '201804230000012383',
        ];

        $entry = ['id' => '201503220000000555'];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setOptions($options);
        $daMo->verifyOrderPayment($entry);
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
            'sign' => '6440A1B466F434F54613AC2F2AE202E3',
            'instructCode' => '922180423000006',
            'transTime' => '20180423113826',
            'totalAmount' => '100',
            'merchantCode' => '1000001810',
            'ext' => '',
            'outOrderId' => '201804230000012383',
        ];

        $entry = [
            'id' => '201804230000012383',
            'amount' => '0.1'
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setOptions($options);
        $daMo->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'sign' => '6440A1B466F434F54613AC2F2AE202E3',
            'instructCode' => '922180423000006',
            'transTime' => '20180423113826',
            'totalAmount' => '100',
            'merchantCode' => '1000001810',
            'ext' => '',
            'outOrderId' => '201804230000012383',
        ];

        $entry = [
            'id' => '201804230000012383',
            'amount' => '1'
        ];

        $daMo = new DaMo();
        $daMo->setPrivateKey('test');
        $daMo->setOptions($options);
        $daMo->verifyOrderPayment($entry);
    }
}
