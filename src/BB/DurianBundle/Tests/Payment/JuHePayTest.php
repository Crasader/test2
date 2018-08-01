<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JuHePay;
use Buzz\Message\Response;

class JuHePayTest extends DurianTestCase
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

        $juHePay = new JuHePay();
        $juHePay->getVerifyData();
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

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->getVerifyData();
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
            'number' => 'yft2017102300005',
            'orderId' => '201711200000005708',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->getVerifyData();
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
            'number' => 'yft2017102300005',
            'orderId' => '201711200000005708',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => '',
        ];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->getVerifyData();
    }

    /**
     * 測試二維支付時返回code及msg
     */
    public function testQrCodePayReturnCodeAndMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '权限错误',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'yft2017102300005',
            'orderId' => '201711200000005708',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '1017',
            'msg' => '权限错误',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $juHePay = new JuHePay();
        $juHePay->setContainer($this->container);
        $juHePay->setClient($this->client);
        $juHePay->setResponse($response);
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回url
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
            'number' => 'yft2017102300005',
            'orderId' => '201711200000005708',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merid' => 'yft2017102300005',
            'merchantOutOrderNo' => '201711200000005704',
            'orderMoney' => '1.01',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $juHePay = new JuHePay();
        $juHePay->setContainer($this->container);
        $juHePay->setClient($this->client);
        $juHePay->setResponse($response);
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'yft2017102300005',
            'orderId' => '201711200000005708',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merid' => 'yft2017102300005',
            'merchantOutOrderNo' => '201711200000005704',
            'orderMoney' => '1.01',
            'url' => 'http://jh.yizhibank.com/api/pcOrder?code=MjAxNzExMjAwMDA',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $juHePay = new JuHePay();
        $juHePay->setContainer($this->container);
        $juHePay->setClient($this->client);
        $juHePay->setResponse($response);
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $data = $juHePay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('http://jh.yizhibank.com/api/pcOrder?code=MjAxNzExMjAwMDA', $juHePay->getQrcode());
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1102',
            'number' => 'yft2017102300005',
            'orderId' => '201711200000005708',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'postUrl' => 'http://jh.yizhibank.com',
        ];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $data = $juHePay->getVerifyData();

        $this->assertEquals('http://jh.yizhibank.com/api/createQuickOrder', $data['post_url']);
        $this->assertEquals($options['orderId'], $data['params']['merchantOutOrderNo']);
        $this->assertEquals($options['number'], $data['params']['merid']);
        $this->assertEquals($options['username'], $data['params']['noncestr']);
        $this->assertEquals($options['notify_url'], $data['params']['notifyUrl']);
        $this->assertEquals($options['amount'], $data['params']['orderMoney']);
        $this->assertEquals('20170824113232', $data['params']['orderTime']);
        $this->assertEquals('0ebe95c0004c22dd4f4a5fb5a570e6c6', $data['params']['sign']);
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

        $juHePay = new JuHePay();
        $juHePay->verifyOrderPayment([]);
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

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->verifyOrderPayment([]);
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
            'merchantOutOrderNo' => '201711200000005708',
            'merid' => 'yft2017102300005',
            'msg' => '{"payMoney":"1.00"}',
            'noncestr' => 'php1test',
            'orderNo' => '201711200000005708764',
            'payResult' => '1',
        ];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->verifyOrderPayment([]);
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
            'merchantOutOrderNo' => '201711200000005708',
            'merid' => 'yft2017102300005',
            'msg' => '{"payMoney":"1.00"}',
            'noncestr' => 'php1test',
            'orderNo' => '201711200000005708764',
            'payResult' => '1',
            'sign' => '9cc5392f9f3d3cbf4c7ac8fa4969e023',
        ];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->verifyOrderPayment([]);
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
            'merchantOutOrderNo' => '201711200000005708',
            'merid' => 'yft2017102300005',
            'msg' => '{"payMoney":"1.00"}',
            'noncestr' => 'php1test',
            'orderNo' => '201711200000005708764',
            'payResult' => '0',
            'sign' => '50a034ed8fb67ef50b4da7c0b673b681',
        ];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->verifyOrderPayment([]);
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
            'merchantOutOrderNo' => '201711200000005708',
            'merid' => 'yft2017102300005',
            'msg' => '{"payMoney":"1.00"}',
            'noncestr' => 'php1test',
            'orderNo' => '201711200000005708764',
            'payResult' => '1',
            'sign' => '4eda6222d86a8ac8a0792fd4d2fdca5b',
        ];

        $entry = ['id' => '201503220000000555'];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->verifyOrderPayment($entry);
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
            'merchantOutOrderNo' => '201711200000005708',
            'merid' => 'yft2017102300005',
            'msg' => '{"payMoney":"1.00"}',
            'noncestr' => 'php1test',
            'orderNo' => '201711200000005708764',
            'payResult' => '1',
            'sign' => '4eda6222d86a8ac8a0792fd4d2fdca5b',
        ];

        $entry = [
            'id' => '201711200000005708',
            'amount' => '15.00',
        ];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'merchantOutOrderNo' => '201711200000005708',
            'merid' => 'yft2017102300005',
            'msg' => '{"payMoney":"1.00"}',
            'noncestr' => 'php1test',
            'orderNo' => '201711200000005708764',
            'payResult' => '1',
            'sign' => '4eda6222d86a8ac8a0792fd4d2fdca5b',
        ];

        $entry = [
            'id' => '201711200000005708',
            'amount' => '1.00',
        ];

        $juHePay = new JuHePay();
        $juHePay->setPrivateKey('test');
        $juHePay->setOptions($options);
        $juHePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $juHePay->getMsg());
    }
}
