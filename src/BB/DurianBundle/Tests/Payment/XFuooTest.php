<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XFuoo;
use Buzz\Message\Response;

class XFuooTest extends DurianTestCase
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

        $xFuoo = new XFuoo();
        $xFuoo->getVerifyData();
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

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->getVerifyData();
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
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->getVerifyData();
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
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'verify_url' => '',
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xFuoo = new XFuoo();
        $xFuoo->setContainer($this->container);
        $xFuoo->setClient($this->client);
        $xFuoo->setResponse($response);
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->getVerifyData();
    }

    /**
     * 測試二維支付時返回respCode錯誤但沒有返回respMessage
     */
    public function testQrCodePayReturnNotSuccessWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['respCode' => '200903'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xFuoo = new XFuoo();
        $xFuoo->setContainer($this->container);
        $xFuoo->setClient($this->client);
        $xFuoo->setResponse($response);
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验证数据签名信息未通过',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => '200903',
            'respMessage' => '验证数据签名信息未通过',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xFuoo = new XFuoo();
        $xFuoo->setContainer($this->container);
        $xFuoo->setClient($this->client);
        $xFuoo->setResponse($response);
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['respCode' => 'S0001'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xFuoo = new XFuoo();
        $xFuoo->setContainer($this->container);
        $xFuoo->setClient($this->client);
        $xFuoo->setResponse($response);
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1104',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => 'S0001',
            'codeUrl' => 'https://qpay.qq.com/qr/5870f65a',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xFuoo = new XFuoo();
        $xFuoo->setContainer($this->container);
        $xFuoo->setClient($this->client);
        $xFuoo->setResponse($response);
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $data = $xFuoo->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/5870f65a', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => 'S0001',
            'codeUrl' => 'weixin://wxpay/bizpayurl?pr=gZAFE9q',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xFuoo = new XFuoo();
        $xFuoo->setContainer($this->container);
        $xFuoo->setClient($this->client);
        $xFuoo->setResponse($response);
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $data = $xFuoo->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=gZAFE9q', $xFuoo->getQrcode());
    }

    /**
     * 測試銀聯在線快捷
     */
    public function testQuickPay()
    {
        $options = [
            'postUrl' => 'https://ebank.xfuoo.com/payment/v1/order/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '278',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $data = $xFuoo->getVerifyData();

        $postUrl = 'https://ebank.xfuoo.com/payment/v1/order/100000000002472-201711210000005731';

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals('201711210000005731', $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('QUICKPAY', $data['params']['defaultbank']);
        $this->assertEquals('web', $data['params']['isApp']);
        $this->assertEquals('100000000002472', $data['params']['merchantId']);
        $this->assertEquals('http://pay.in-action.tw/', $data['params']['notifyUrl']);
        $this->assertEquals('201711210000005731', $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('bankPay', $data['params']['paymethod']);
        $this->assertEquals('http://pay.in-action.tw/', $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals('201711210000005731', $data['params']['title']);
        $this->assertEquals('1.01', $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('5387EBC162A7F631BEA43E8EAC9C708A3F6AFE62', $data['params']['sign']);
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'postUrl' => 'https://ebank.xfuoo.com/payment/v1/order/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $data = $xFuoo->getVerifyData();

        $postUrl = $options['postUrl'] . $data['params']['merchantId'] . '-' . $data['params']['orderNo'];

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals('201711210000005731', $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('ICBC', $data['params']['defaultbank']);
        $this->assertEquals('web', $data['params']['isApp']);
        $this->assertEquals($options['number'], $data['params']['merchantId']);
        $this->assertEquals($options['notify_url'], $data['params']['notifyUrl']);
        $this->assertEquals($options['orderId'], $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals($options['notify_url'], $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals('201711210000005731', $data['params']['title']);
        $this->assertEquals($options['amount'], $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('3F5C624FF0E8C8D0A70F302E143494B5011C4A4E', $data['params']['sign']);
    }

    /**
     * 測試京東手機支付
     */
    public function testJDPhonePay()
    {
        $options = [
            'postUrl' => 'https://ebank.xfuoo.com/payment/v1/order/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1108',
            'number' => '100000000002472',
            'orderId' => '201711210000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $data = $xFuoo->getVerifyData();

        $postUrl = $options['postUrl'] . $data['params']['merchantId'] . '-' . $data['params']['orderNo'];

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals('201711210000005731', $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('JDPAY', $data['params']['defaultbank']);
        $this->assertEquals('H5', $data['params']['isApp']);
        $this->assertEquals($options['number'], $data['params']['merchantId']);
        $this->assertEquals($options['notify_url'], $data['params']['notifyUrl']);
        $this->assertEquals($options['orderId'], $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals($options['notify_url'], $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals('201711210000005731', $data['params']['title']);
        $this->assertEquals($options['amount'], $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('1D2471576D0125D7BFA6089A4C06FF09EA3D2628', $data['params']['sign']);
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

        $xFuoo = new XFuoo();
        $xFuoo->verifyOrderPayment([]);
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

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->verifyOrderPayment([]);
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
            'gmt_create' => '2017-11-21 17:29:06',
            'order_no' => '201711210000005731',
            'gmt_payment' => '2017-11-21 17:29:06',
            'seller_email' => '13146',
            'notify_time' => '2017-11-21 17:29:06',
            'quantity' => '1',
            'discount' => '0.00',
            'body' => '201711210000005731',
            'is_success' => 'T',
            'title' => '201711210000005731',
            'gmt_logistics_modify' => '2017-11-21 17:29:06',
            'notify_id' => '0827afd1e9684484847548ae36b11370',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => '0.10',
            'trade_no' => '101711210763095',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002472',
            'is_total_fee_adjust' => '0',
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->verifyOrderPayment([]);
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
            'gmt_create' => '2017-11-21 17:29:06',
            'order_no' => '201711210000005731',
            'gmt_payment' => '2017-11-21 17:29:06',
            'seller_email' => '13146',
            'notify_time' => '2017-11-21 17:29:06',
            'quantity' => '1',
            'sign' => 'noob',
            'discount' => '0.00',
            'body' => '201711210000005731',
            'is_success' => 'T',
            'title' => '201711210000005731',
            'gmt_logistics_modify' => '2017-11-21 17:29:06',
            'notify_id' => '0827afd1e9684484847548ae36b11370',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => '0.10',
            'trade_no' => '101711210763095',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002472',
            'is_total_fee_adjust' => '0',
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->verifyOrderPayment([]);
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
            'gmt_create' => '2017-11-21 17:29:06',
            'order_no' => '201711210000005731',
            'gmt_payment' => '2017-11-21 17:29:06',
            'seller_email' => '13146',
            'notify_time' => '2017-11-21 17:29:06',
            'quantity' => '1',
            'sign' => '67582c5604aa488accc916c3e2fd147f0660787d',
            'discount' => '0.00',
            'body' => '201711210000005731',
            'is_success' => 'T',
            'title' => '201711210000005731',
            'gmt_logistics_modify' => '2017-11-21 17:29:06',
            'notify_id' => '0827afd1e9684484847548ae36b11370',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => 'TRAD',
            'trade_no' => '101711210763095',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002472',
            'is_total_fee_adjust' => '0',
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->verifyOrderPayment([]);
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
            'gmt_create' => '2017-11-21 17:29:06',
            'order_no' => '201711210000005731',
            'gmt_payment' => '2017-11-21 17:29:06',
            'seller_email' => '13146',
            'notify_time' => '2017-11-21 17:29:06',
            'quantity' => '1',
            'sign' => 'd8abe443b95e8aff1080a2e35491ff54a25685bb',
            'discount' => '0.00',
            'body' => '201711210000005731',
            'is_success' => 'T',
            'title' => '201711210000005731',
            'gmt_logistics_modify' => '2017-11-21 17:29:06',
            'notify_id' => '0827afd1e9684484847548ae36b11370',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101711210763095',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002472',
            'is_total_fee_adjust' => '0',
        ];

        $entry = ['id' => '201503220000000555'];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->verifyOrderPayment($entry);
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
            'gmt_create' => '2017-11-21 17:29:06',
            'order_no' => '201711210000005731',
            'gmt_payment' => '2017-11-21 17:29:06',
            'seller_email' => '13146',
            'notify_time' => '2017-11-21 17:29:06',
            'quantity' => '1',
            'sign' => 'd8abe443b95e8aff1080a2e35491ff54a25685bb',
            'discount' => '0.00',
            'body' => '201711210000005731',
            'is_success' => 'T',
            'title' => '201711210000005731',
            'gmt_logistics_modify' => '2017-11-21 17:29:06',
            'notify_id' => '0827afd1e9684484847548ae36b11370',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101711210763095',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002472',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201711210000005731',
            'amount' => '15.00',
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'gmt_create' => '2017-11-21 17:29:06',
            'order_no' => '201711210000005731',
            'gmt_payment' => '2017-11-21 17:29:06',
            'seller_email' => '13146',
            'notify_time' => '2017-11-21 17:29:06',
            'quantity' => '1',
            'sign' => 'd8abe443b95e8aff1080a2e35491ff54a25685bb',
            'discount' => '0.00',
            'body' => '201711210000005731',
            'is_success' => 'T',
            'title' => '201711210000005731',
            'gmt_logistics_modify' => '2017-11-21 17:29:06',
            'notify_id' => '0827afd1e9684484847548ae36b11370',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101711210763095',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002472',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201711210000005731',
            'amount' => '0.1',
        ];

        $xFuoo = new XFuoo();
        $xFuoo->setPrivateKey('test');
        $xFuoo->setOptions($options);
        $xFuoo->verifyOrderPayment($entry);

        $this->assertEquals('success', $xFuoo->getMsg());
    }
}
