<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Xifpay;
use Buzz\Message\Response;

class XifpayTest extends DurianTestCase
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

        $xifpay = new Xifpay();
        $xifpay->getVerifyData();
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

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->getVerifyData();
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
            'username' => 'php1test',
        ];

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->getVerifyData();
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
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->getVerifyData();
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
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respMessage":"没有可用支付渠道"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xifpay = new Xifpay();
        $xifpay->setContainer($this->container);
        $xifpay->setClient($this->client);
        $xifpay->setResponse($response);
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"F0008"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xifpay = new Xifpay();
        $xifpay->setContainer($this->container);
        $xifpay->setClient($this->client);
        $xifpay->setResponse($response);
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '没有可用支付渠道',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respMessage":"没有可用支付渠道","respCode":"F0008"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xifpay = new Xifpay();
        $xifpay->setContainer($this->container);
        $xifpay->setClient($this->client);
        $xifpay->setResponse($response);
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->getVerifyData();
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
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xifpay = new Xifpay();
        $xifpay->setContainer($this->container);
        $xifpay->setClient($this->client);
        $xifpay->setResponse($response);
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->getVerifyData();
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
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"codeUrl":"https://qpay.qq.com/qr/67de7750","respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xifpay = new Xifpay();
        $xifpay->setContainer($this->container);
        $xifpay->setClient($this->client);
        $xifpay->setResponse($response);
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $data = $xifpay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/67de7750', $xifpay->getQrcode());
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
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"codeUrl":"https://qpay.qq.com/qr/67de7750","respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xifpay = new Xifpay();
        $xifpay->setContainer($this->container);
        $xifpay->setClient($this->client);
        $xifpay->setResponse($response);
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $data = $xifpay->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/67de7750', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'postUrl' => 'https://ebank.xifpay.com/payment/v1/order/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $data = $xifpay->getVerifyData();


        $postUrl = $options['postUrl'] . $data['params']['merchantId'] . '-' . $data['params']['orderNo'];

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals($options['username'], $data['params']['body']);
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
        $this->assertEquals($options['username'], $data['params']['title']);
        $this->assertEquals($options['amount'], $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('4EC81D9CA79D13D013E7C6EA950B80F88D0E3A56', $data['params']['sign']);
    }

    /**
     * 測試銀聯在線
     */
    public function testQuickPay()
    {
        $options = [
            'postUrl' => 'https://ebank.xifpay.com/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '278',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $data = $xifpay->getVerifyData();


        $postUrl = $options['postUrl'] . $data['params']['merchantId'] . '-' . $data['params']['orderNo'];

        $this->assertEquals('https://ebank.xifpay.com/100000000002224-201801050000008447', $data['post_url']);
        $this->assertEquals('php1test', $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('QUICKPAY', $data['params']['defaultbank']);
        $this->assertEquals('web', $data['params']['isApp']);
        $this->assertEquals('100000000002224', $data['params']['merchantId']);
        $this->assertEquals('http://pay.in-action.tw/', $data['params']['notifyUrl']);
        $this->assertEquals('201801050000008447', $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('bankPay', $data['params']['paymethod']);
        $this->assertEquals('http://pay.in-action.tw/', $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals('php1test', $data['params']['title']);
        $this->assertEquals('1.01', $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('062C5B698E4CA573FA5DF5223EA198A411EA34BE', $data['params']['sign']);
    }

    /**
     * 測試京東手機支付
     */
    public function testJDPhonePay()
    {
        $options = [
            'postUrl' => 'https://ebank.xifpay.com/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1108',
            'number' => '100000000002224',
            'orderId' => '201801050000008447',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $data = $xifpay->getVerifyData();

        $this->assertEquals('https://ebank.xifpay.com/100000000002224-201801050000008447', $data['post_url']);
        $this->assertEquals('php1test', $data['params']['body']);
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
        $this->assertEquals('php1test', $data['params']['title']);
        $this->assertEquals('1.01', $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('B4D3136DDAAE4C2B52705907DA4E547226640E67', $data['params']['sign']);
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

        $xifpay = new Xifpay();
        $xifpay->verifyOrderPayment([]);
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

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->verifyOrderPayment([]);
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
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
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

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->verifyOrderPayment([]);
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
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
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

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->verifyOrderPayment([]);
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
            'sign' => '6841da3fba9bc287e8ffb4ad40bf4bd75ab0603e',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
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

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->verifyOrderPayment([]);
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
            'sign' => 'fe71195025f1e0befb388fe9cf9d61e9174a79d4',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
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

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->verifyOrderPayment($entry);
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
            'sign' => 'fe71195025f1e0befb388fe9cf9d61e9174a79d4',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
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

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->verifyOrderPayment($entry);
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
            'sign' => 'fe71195025f1e0befb388fe9cf9d61e9174a79d4',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
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

        $xifpay = new Xifpay();
        $xifpay->setPrivateKey('test');
        $xifpay->setOptions($options);
        $xifpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $xifpay->getMsg());
    }
}
