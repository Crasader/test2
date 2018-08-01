<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DuoYuanBao;
use Buzz\Message\Response;

class DuoYuanBaoTest extends DurianTestCase
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

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->getVerifyData();
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

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->getVerifyData();
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
            'paymentVendorId' => '9999',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
            'amount' => '1.01',
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verify_url的情況
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
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
            'amount' => '1.01',
            'verify_url' => '',
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setContainer($this->container);
        $duoYuanBao->setClient($this->client);
        $duoYuanBao->setResponse($response);
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->getVerifyData();
    }

    /**
     * 測試支付時返回respCode錯誤但沒有返回respMessage
     */
    public function testPayReturnNotSuccessWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['respCode' => '200903'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setContainer($this->container);
        $duoYuanBao->setClient($this->client);
        $duoYuanBao->setResponse($response);
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验证数据签名信息未通过',
            180130
        );

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
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

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setContainer($this->container);
        $duoYuanBao->setClient($this->client);
        $duoYuanBao->setResponse($response);
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回codeUrl
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['respCode' => 'S0001'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setContainer($this->container);
        $duoYuanBao->setClient($this->client);
        $duoYuanBao->setResponse($response);
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1104',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
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

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setContainer($this->container);
        $duoYuanBao->setClient($this->client);
        $duoYuanBao->setResponse($response);
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $data = $duoYuanBao->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/5870f65a', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $duoYuanBao->getPayMethod());
    }

    /**
     * 測試QQ二維支付
     */
    public function testQQScanPay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
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

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setContainer($this->container);
        $duoYuanBao->setClient($this->client);
        $duoYuanBao->setResponse($response);
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $data = $duoYuanBao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=gZAFE9q', $duoYuanBao->getQrcode());
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'postUrl' => 'https://ebank.DuoYuanBao.com/payment/v1/order/',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $data = $duoYuanBao->getVerifyData();

        $postUrl = 'https://ebank.DuoYuanBao.com/payment/v1/order/100000000002204-201804120000005731';

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals('201804120000005731', $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('ICBC', $data['params']['defaultbank']);
        $this->assertEquals('web', $data['params']['isApp']);
        $this->assertEquals('100000000002204', $data['params']['merchantId']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['params']['notifyUrl']);
        $this->assertEquals('201804120000005731', $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals('201804120000005731', $data['params']['title']);
        $this->assertEquals('1.01', $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('C2BB7D6CEE12B337B7B9C1F6E41E15D2EA4B141B', $data['params']['sign']);
    }

    /**
     * 測試京東手機支付
     */
    public function testJDPhonePay()
    {
        $options = [
            'postUrl' => 'https://ebank.DuoYuanBao.com/payment/v1/order/',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1108',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $data = $duoYuanBao->getVerifyData();

        $postUrl = 'https://ebank.DuoYuanBao.com/payment/v1/order/100000000002204-201804120000005731';

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals('201804120000005731', $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('JDPAY', $data['params']['defaultbank']);
        $this->assertEquals('H5', $data['params']['isApp']);
        $this->assertEquals('100000000002204', $data['params']['merchantId']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['params']['notifyUrl']);
        $this->assertEquals('201804120000005731', $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals('201804120000005731', $data['params']['title']);
        $this->assertEquals('1.01', $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('85539ADB0900E44A29745B1C3DF1C74DCCA320E0', $data['params']['sign']);
    }

    /**
     * 測試銀聯二維支付
     */
    public function testUnionScanPay()
    {
        $options = [
            'postUrl' => 'https://ebank.DuoYuanBao.com/payment/v1/order/',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '100000000002204',
            'orderId' => '201804120000005731',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $data = $duoYuanBao->getVerifyData();

        $postUrl = 'https://ebank.DuoYuanBao.com/payment/v1/order/100000000002204-201804120000005731';

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals('201804120000005731', $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('UNIONQRPAY', $data['params']['defaultbank']);
        $this->assertEquals('app', $data['params']['isApp']);
        $this->assertEquals('100000000002204', $data['params']['merchantId']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['params']['notifyUrl']);
        $this->assertEquals('201804120000005731', $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals('201804120000005731', $data['params']['title']);
        $this->assertEquals('1.01', $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('692744E5AF9783A1C8B18B9DF53D09B4C5F83E90', $data['params']['sign']);
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

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->verifyOrderPayment([]);
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

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->verifyOrderPayment([]);
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
            'gmt_create' => '2018-04-12 20:00:48',
            'order_no' => '201804110000011977',
            'gmt_payment' => '2018-04-12 20:01:19',
            'seller_email' => '3273359794@qq.com',
            'notify_time' => '2018-04-12 20:01:19',
            'quantity' => '1',
            'discount' => '0.00',
            'body' => '201804110000011977',
            'is_success' => 'T',
            'title' => '201804110000011977',
            'gmt_logistics_modify' => '2018-04-12 20:01:19',
            'notify_id' => '618303e6b6d94ab3bc0cecddeeccecb8',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => '0.10',
            'trade_no' => '101804120680618',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002204',
            'is_total_fee_adjust' => '0',
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->verifyOrderPayment([]);
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
            'gmt_create' => '2018-04-12 20:00:48',
            'order_no' => '201804110000011977',
            'gmt_payment' => '2018-04-12 20:01:19',
            'seller_email' => '3273359794@qq.com',
            'notify_time' => '2018-04-12 20:01:19',
            'quantity' => '1',
            'sign' => '46C1C0C215934E9F1D1B2788A7FC6551DED8236C',
            'discount' => '0.00',
            'body' => '201804110000011977',
            'is_success' => 'T',
            'title' => '201804110000011977',
            'gmt_logistics_modify' => '2018-04-12 20:01:19',
            'notify_id' => '618303e6b6d94ab3bc0cecddeeccecb8',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => '0.10',
            'trade_no' => '101804120680618',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002204',
            'is_total_fee_adjust' => '0',
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->verifyOrderPayment([]);
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
            'gmt_create' => '2018-04-12 20:00:48',
            'order_no' => '201804110000011977',
            'gmt_payment' => '2018-04-12 20:01:19',
            'seller_email' => '3273359794@qq.com',
            'notify_time' => '2018-04-12 20:01:19',
            'quantity' => '1',
            'sign' => 'bc0dd87841492cc9fd639ea9587897931ff7b0bd',
            'discount' => '0.00',
            'body' => '201804110000011977',
            'is_success' => 'T',
            'title' => '201804110000011977',
            'gmt_logistics_modify' => '2018-04-12 20:01:19',
            'notify_id' => '618303e6b6d94ab3bc0cecddeeccecb8',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => '0.10',
            'trade_no' => '101804120680618',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002204',
            'is_total_fee_adjust' => '0',
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->verifyOrderPayment([]);
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
            'gmt_create' => '2018-04-12 20:00:48',
            'order_no' => '201804110000011977',
            'gmt_payment' => '2018-04-12 20:01:19',
            'seller_email' => '3273359794@qq.com',
            'notify_time' => '2018-04-12 20:01:19',
            'quantity' => '1',
            'sign' => '160e6662de985c3b7adf95df8073849bada446aa',
            'discount' => '0.00',
            'body' => '201804110000011977',
            'is_success' => 'T',
            'title' => '201804110000011977',
            'gmt_logistics_modify' => '2018-04-12 20:01:19',
            'notify_id' => '618303e6b6d94ab3bc0cecddeeccecb8',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101804120680618',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002204',
            'is_total_fee_adjust' => '0',
        ];

        $entry = ['id' => '201503220000000555'];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->verifyOrderPayment($entry);
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
            'gmt_create' => '2018-04-12 20:00:48',
            'order_no' => '201804110000011977',
            'gmt_payment' => '2018-04-12 20:01:19',
            'seller_email' => '3273359794@qq.com',
            'notify_time' => '2018-04-12 20:01:19',
            'quantity' => '1',
            'sign' => '160e6662de985c3b7adf95df8073849bada446aa',
            'discount' => '0.00',
            'body' => '201804110000011977',
            'is_success' => 'T',
            'title' => '201804110000011977',
            'gmt_logistics_modify' => '2018-04-12 20:01:19',
            'notify_id' => '618303e6b6d94ab3bc0cecddeeccecb8',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101804120680618',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002204',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201804110000011977',
            'amount' => '15.00',
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'gmt_create' => '2018-04-12 20:00:48',
            'order_no' => '201804110000011977',
            'gmt_payment' => '2018-04-12 20:01:19',
            'seller_email' => '3273359794@qq.com',
            'notify_time' => '2018-04-12 20:01:19',
            'quantity' => '1',
            'sign' => '160e6662de985c3b7adf95df8073849bada446aa',
            'discount' => '0.00',
            'body' => '201804110000011977',
            'is_success' => 'T',
            'title' => '201804110000011977',
            'gmt_logistics_modify' => '2018-04-12 20:01:19',
            'notify_id' => '618303e6b6d94ab3bc0cecddeeccecb8',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '0.10',
            'total_fee' => '0.10',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101804120680618',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002204',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201804110000011977',
            'amount' => '0.1',
        ];

        $duoYuanBao = new DuoYuanBao();
        $duoYuanBao->setPrivateKey('test');
        $duoYuanBao->setOptions($options);
        $duoYuanBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $duoYuanBao->getMsg());
    }
}
