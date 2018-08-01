<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Foo518;
use Buzz\Message\Response;

class Foo518Test extends DurianTestCase
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

        $foo518 = new Foo518();
        $foo518->getVerifyData();
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

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->getVerifyData();
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
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
        ];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQRcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'verify_url' => '',
        ];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回respCode
     */
    public function testQrCodePayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respMessage":"没有可用渠道"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $foo518 = new Foo518();
        $foo518->setContainer($this->container);
        $foo518->setClient($this->client);
        $foo518->setResponse($response);
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->getVerifyData();
    }

    /**
     * 測試二維支付時返回respCode錯誤但沒有返回respMessage
     */
    public function testQrCodePayReturnNotSuccessWithoutRespMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"F0008"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $foo518 = new Foo518();
        $foo518->setContainer($this->container);
        $foo518->setClient($this->client);
        $foo518->setResponse($response);
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '没有可用渠道',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respMessage":"没有可用渠道","respCode":"F0008"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $foo518 = new Foo518();
        $foo518->setContainer($this->container);
        $foo518->setClient($this->client);
        $foo518->setResponse($response);
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回codeUrl
     */
    public function testQrCodePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $foo518 = new Foo518();
        $foo518->setContainer($this->container);
        $foo518->setClient($this->client);
        $foo518->setResponse($response);
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"codeUrl":"https://qpay.qq.com/qr/67de7750","respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $foo518 = new Foo518();
        $foo518->setContainer($this->container);
        $foo518->setClient($this->client);
        $foo518->setResponse($response);
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $data = $foo518->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/67de7750', $foo518->getQrcode());
    }

    /**
     * 測試QQ手機支付
     */
    public function testQQPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1104',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"codeUrl":"https://qpay.qq.com/qr/67de7750","respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $foo518 = new Foo518();
        $foo518->setContainer($this->container);
        $foo518->setClient($this->client);
        $foo518->setResponse($response);
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $data = $foo518->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/67de7750', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $foo518->getPayMethod());
    }

    /**
     * 測試京東手機支付
     */
    public function testJDPhonePay()
    {
        $options = [
            'postUrl' => 'https://ebank.Foo518.com/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1108',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $data = $foo518->getVerifyData();

        $this->assertEquals('https://ebank.Foo518.com/100000000002224-201801050000008447', $data['post_url']);
        $this->assertEquals('201801050000008447', $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('JDPAY', $data['params']['defaultbank']);
        $this->assertEquals('H5', $data['params']['isApp']);
        $this->assertEquals('100000000002224', $data['params']['merchantId']);
        $this->assertEquals('http://pay.in-action.tw/', $data['params']['notifyUrl']);
        $this->assertEquals('201801050000008447', $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals('http://pay.in-action.tw/', $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals('201801050000008447', $data['params']['title']);
        $this->assertEquals('1.01', $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('9A6F0630F4F1BC7DDB672DD666F2BE39EF799D41', $data['params']['sign']);
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

        $foo518 = new Foo518();
        $foo518->verifyOrderPayment([]);
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

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->verifyOrderPayment([]);
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
            'gmt_create' => '2018-01-05 11:01:40',
            'order_no' => '201801050000008447',
            'gmt_payment' => '2018-01-05 11:01:40',
            'seller_email' => '2672291086@qq.com',
            'notify_time' => '2018-01-05 11:01:40',
            'quantity' => '1',
            'discount' => '0.00',
            'body' => '201801050000008447',
            'is_success' => 'T',
            'title' => '201801050000008447',
            'gmt_logistics_modify' => '2018-01-05 11:01:40',
            'notify_id' => 'e74b57fc4f1540e7a2d9cfb738ea9902',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '1.00',
            'total_fee' => '1.00',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801050127280',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002224',
            'is_total_fee_adjust' => '0',
        ];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->verifyOrderPayment([]);
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
            'gmt_create' => '2018-01-05 11:01:40',
            'order_no' => '201801050000008447',
            'gmt_payment' => '2018-01-05 11:01:40',
            'seller_email' => '2672291086@qq.com',
            'notify_time' => '2018-01-05 11:01:40',
            'quantity' => '1',
            'sign' => '6CC33F449213C1C1F07D706DD5677958FE2828C7',
            'discount' => '0.00',
            'body' => '201801050000008447',
            'is_success' => 'T',
            'title' => '201801050000008447',
            'gmt_logistics_modify' => '2018-01-05 11:01:40',
            'notify_id' => 'e74b57fc4f1540e7a2d9cfb738ea9902',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '1.00',
            'total_fee' => '1.00',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801050127280',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002224',
            'is_total_fee_adjust' => '0',
        ];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->verifyOrderPayment([]);
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
            'gmt_create' => '2018-01-05 11:01:40',
            'order_no' => '201801050000008447',
            'gmt_payment' => '2018-01-05 11:01:40',
            'seller_email' => '2672291086@qq.com',
            'notify_time' => '2018-01-05 11:01:40',
            'quantity' => '1',
            'sign' => '965bb7dd24d1a7cc0eada53b2e2f98fe7cfa9917',
            'discount' => '0.00',
            'body' => '201801050000008447',
            'is_success' => 'T',
            'title' => '201801050000008447',
            'gmt_logistics_modify' => '2018-01-05 11:01:40',
            'notify_id' => 'e74b57fc4f1540e7a2d9cfb738ea9902',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '1.00',
            'total_fee' => '1.00',
            'trade_status' => 'FAILED',
            'trade_no' => '101801050127280',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002224',
            'is_total_fee_adjust' => '0',
        ];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->verifyOrderPayment([]);
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
            'gmt_create' => '2018-01-05 11:01:40',
            'order_no' => '201801050000008447',
            'gmt_payment' => '2018-01-05 11:01:40',
            'seller_email' => '2672291086@qq.com',
            'notify_time' => '2018-01-05 11:01:40',
            'quantity' => '1',
            'sign' => '48cda8c3596e9bd28db499dccbfd9a79ab4c9369',
            'discount' => '0.00',
            'body' => '201801050000008447',
            'is_success' => 'T',
            'title' => '201801050000008447',
            'gmt_logistics_modify' => '2018-01-05 11:01:40',
            'notify_id' => 'e74b57fc4f1540e7a2d9cfb738ea9902',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '1.00',
            'total_fee' => '1.00',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801050127280',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002224',
            'is_total_fee_adjust' => '0',
        ];

        $entry = ['id' => '201503220000000555'];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->verifyOrderPayment($entry);
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
            'gmt_create' => '2018-01-05 11:01:40',
            'order_no' => '201801050000008447',
            'gmt_payment' => '2018-01-05 11:01:40',
            'seller_email' => '2672291086@qq.com',
            'notify_time' => '2018-01-05 11:01:40',
            'quantity' => '1',
            'sign' => '48cda8c3596e9bd28db499dccbfd9a79ab4c9369',
            'discount' => '0.00',
            'body' => '201801050000008447',
            'is_success' => 'T',
            'title' => '201801050000008447',
            'gmt_logistics_modify' => '2018-01-05 11:01:40',
            'notify_id' => 'e74b57fc4f1540e7a2d9cfb738ea9902',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '1.00',
            'total_fee' => '1.00',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801050127280',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002224',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201801050000008447',
            'amount' => '15.00',
        ];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'gmt_create' => '2018-01-05 11:01:40',
            'order_no' => '201801050000008447',
            'gmt_payment' => '2018-01-05 11:01:40',
            'seller_email' => '2672291086@qq.com',
            'notify_time' => '2018-01-05 11:01:40',
            'quantity' => '1',
            'sign' => '48cda8c3596e9bd28db499dccbfd9a79ab4c9369',
            'discount' => '0.00',
            'body' => '201801050000008447',
            'is_success' => 'T',
            'title' => '201801050000008447',
            'gmt_logistics_modify' => '2018-01-05 11:01:40',
            'notify_id' => 'e74b57fc4f1540e7a2d9cfb738ea9902',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '1.00',
            'total_fee' => '1.00',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801050127280',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002224',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201801050000008447',
            'amount' => '1.00',
        ];

        $foo518 = new Foo518();
        $foo518->setPrivateKey('test');
        $foo518->setOptions($options);
        $foo518->verifyOrderPayment($entry);

        $this->assertEquals('success', $foo518->getMsg());
    }
}
