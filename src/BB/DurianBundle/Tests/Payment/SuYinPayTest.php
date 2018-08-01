<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SuYinPay;
use Buzz\Message\Response;

class SuYinPayTest extends DurianTestCase
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

        $suYinPay = new SuYinPay();
        $suYinPay->getVerifyData();
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

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->getVerifyData();
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
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '9999',
            'notify_url' => 'http://orz.zz/',
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->getVerifyData();
    }

    /**
     * 測試QQ手機支付時缺少verify_url
     */
    public function testPhonePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => '',
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->getVerifyData();
    }

    /**
     * 測試QQ手機支付時返回缺少respCode
     */
    public function testPhonePayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $suYinPay = new SuYinPay();
        $suYinPay->setContainer($this->container);
        $suYinPay->setClient($this->client);
        $suYinPay->setResponse($response);
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->getVerifyData();
    }

    /**
     * 測試QQ手機支付時返回失敗卻缺少respMessage
     */
    public function testPhonePayReturnFailedWithoutRespMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['respCode' => 'F1004'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $suYinPay = new SuYinPay();
        $suYinPay->setContainer($this->container);
        $suYinPay->setClient($this->client);
        $suYinPay->setResponse($response);
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->getVerifyData();
    }

    /**
     * 測試QQ手機支付時返回提交失敗
     */
    public function testPhonePayReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '系统处理该请求异常',
            180130
        );

        $options = [
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'respCode' => 'F1004',
            'respMessage' => '系统处理该请求异常',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $suYinPay = new SuYinPay();
        $suYinPay->setContainer($this->container);
        $suYinPay->setClient($this->client);
        $suYinPay->setResponse($response);
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->getVerifyData();
    }

    /**
     * 測試QQ手機支付時返回缺少codeUrl
     */
    public function testPhonePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['respCode' => 'S0001'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $suYinPay = new SuYinPay();
        $suYinPay->setContainer($this->container);
        $suYinPay->setClient($this->client);
        $suYinPay->setResponse($response);
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->getVerifyData();
    }

    /**
     * 測試QQ手機支付
     */
    public function testQQPhonePay()
    {
        $result = [
            'respCode' => 'S0001',
            'codeUrl' => 'https://qpay.qq.com/qr/5ecf7c7e',
        ];

        $options = [
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $suYinPay = new SuYinPay();
        $suYinPay->setContainer($this->container);
        $suYinPay->setClient($this->client);
        $suYinPay->setResponse($response);
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $verifyData = $suYinPay->getVerifyData();

        $this->assertEquals('GET', $suYinPay->getPayMethod());
        $this->assertEquals('https://qpay.qq.com/qr/5ecf7c7e', $verifyData['post_url']);
        $this->assertEmpty($verifyData['params']);
    }

    /**
     * 測試京東手機支付
     */
    public function testJDPay()
    {
        $options = [
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '1108',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'postUrl' => 'https://ebank.huidpay.com/payment/v1/order/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $verifyData = $suYinPay->getVerifyData();

        $postUrl = 'https://ebank.huidpay.com/payment/v1/order/100000000002784-201806010000011644';

        $this->assertEquals($postUrl, $verifyData['post_url']);
        $this->assertEquals('201806010000011644', $verifyData['params']['body']);
        $this->assertEquals('', $verifyData['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $verifyData['params']['charset']);
        $this->assertEquals('JDPAY', $verifyData['params']['defaultbank']);
        $this->assertEquals('H5', $verifyData['params']['isApp']);
        $this->assertEquals('100000000002784', $verifyData['params']['merchantId']);
        $this->assertEquals('http://orz.zz/', $verifyData['params']['notifyUrl']);
        $this->assertEquals('201806010000011644', $verifyData['params']['orderNo']);
        $this->assertEquals('1', $verifyData['params']['paymentType']);
        $this->assertEquals('directPay', $verifyData['params']['paymethod']);
        $this->assertEquals('http://orz.zz/', $verifyData['params']['returnUrl']);
        $this->assertEquals('', $verifyData['params']['riskItem']);
        $this->assertEquals('online_pay', $verifyData['params']['service']);
        $this->assertEquals('201806010000011644', $verifyData['params']['title']);
        $this->assertEquals('1.01', $verifyData['params']['totalFee']);
        $this->assertEquals('SHA', $verifyData['params']['signType']);
        $this->assertEquals('001586F042F223F0B460C44B5177D63E95727A89', $verifyData['params']['sign']);
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'number' => '100000000002784',
            'orderId' => '201806010000011644',
            'amount' => '1.01',
            'paymentVendorId' => '1',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'postUrl' => 'https://ebank.huidpay.com/payment/v1/order/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $verifyData = $suYinPay->getVerifyData();

        $postUrl = 'https://ebank.huidpay.com/payment/v1/order/100000000002784-201806010000011644';

        $this->assertEquals($postUrl, $verifyData['post_url']);
        $this->assertEquals('201806010000011644', $verifyData['params']['body']);
        $this->assertEquals('', $verifyData['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $verifyData['params']['charset']);
        $this->assertEquals('ICBC', $verifyData['params']['defaultbank']);
        $this->assertEquals('web', $verifyData['params']['isApp']);
        $this->assertEquals('100000000002784', $verifyData['params']['merchantId']);
        $this->assertEquals('http://orz.zz/', $verifyData['params']['notifyUrl']);
        $this->assertEquals('201806010000011644', $verifyData['params']['orderNo']);
        $this->assertEquals('1', $verifyData['params']['paymentType']);
        $this->assertEquals('directPay', $verifyData['params']['paymethod']);
        $this->assertEquals('http://orz.zz/', $verifyData['params']['returnUrl']);
        $this->assertEquals('', $verifyData['params']['riskItem']);
        $this->assertEquals('online_pay', $verifyData['params']['service']);
        $this->assertEquals('201806010000011644', $verifyData['params']['title']);
        $this->assertEquals('1.01', $verifyData['params']['totalFee']);
        $this->assertEquals('SHA', $verifyData['params']['signType']);
        $this->assertEquals('6B93379BA8F553E7DB31262DE17507A0FD902374', $verifyData['params']['sign']);
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

        $suYinPay = new SuYinPay();
        $suYinPay->verifyOrderPayment([]);
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

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->verifyOrderPayment([]);
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
            'gmt_create' => '2018-06-01 18:01:19',
            'order_no' => '201806010000011644',
            'gmt_payment' => '2018-06-01 18:01:19',
            'seller_email' => 'gagawulala2@protonmail.com',
            'notify_time' => '2018-06-01 18:01:19',
            'quantity' => '1',
            'discount' => '0.00',
            'body' => '201806010000011644',
            'is_success' => 'T',
            'title' => '201806010000011644',
            'gmt_logistics_modify' => '2018-06-01 18:01:19',
            'notify_id' => '99c7f42c41e7426481a57f7b65d60887',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '1.01',
            'total_fee' => '1.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801181602312',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000008888',
            'is_total_fee_adjust' => '0',
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->verifyOrderPayment([]);
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
            'gmt_create' => '2018-06-01 18:01:19',
            'order_no' => '201806010000011644',
            'gmt_payment' => '2018-06-01 18:01:19',
            'seller_email' => 'gagawulala2@protonmail.com',
            'notify_time' => '2018-06-01 18:01:19',
            'quantity' => '1',
            'sign' => '123456789',
            'discount' => '0.00',
            'body' => '201806010000011644',
            'is_success' => 'T',
            'title' => '201806010000011644',
            'gmt_logistics_modify' => '2018-06-01 18:01:19',
            'notify_id' => '99c7f42c41e7426481a57f7b65d60887',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '1.01',
            'total_fee' => '1.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801181602312',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000008888',
            'is_total_fee_adjust' => '0',
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->verifyOrderPayment([]);
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
            'gmt_create' => '2018-06-01 18:01:19',
            'order_no' => '201806010000011644',
            'gmt_payment' => '2018-06-01 18:01:19',
            'seller_email' => 'gagawulala2@protonmail.com',
            'notify_time' => '2018-06-01 18:01:19',
            'quantity' => '1',
            'sign' => '5A80E460DC560DB76E6E30404D036E4112A1146B',
            'discount' => '0.00',
            'body' => '201806010000011644',
            'is_success' => 'T',
            'title' => '201806010000011644',
            'gmt_logistics_modify' => '2018-06-01 18:01:19',
            'notify_id' => '99c7f42c41e7426481a57f7b65d60887',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '1.01',
            'total_fee' => '1.01',
            'trade_status' => '',
            'trade_no' => '101801181602312',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000008888',
            'is_total_fee_adjust' => '0',
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->verifyOrderPayment([]);
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
            'gmt_create' => '2018-06-01 18:01:19',
            'order_no' => '201806010000011644',
            'gmt_payment' => '2018-06-01 18:01:19',
            'seller_email' => 'gagawulala2@protonmail.com',
            'notify_time' => '2018-06-01 18:01:19',
            'quantity' => '1',
            'sign' => '0CFFCDB3404C181E5E523E64419473746CF82442',
            'discount' => '0.00',
            'body' => '201806010000011644',
            'is_success' => 'T',
            'title' => '201806010000011644',
            'gmt_logistics_modify' => '2018-06-01 18:01:19',
            'notify_id' => '99c7f42c41e7426481a57f7b65d60887',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '1.01',
            'total_fee' => '1.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801181602312',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000008888',
            'is_total_fee_adjust' => '0',
        ];

        $entry = ['id' => '301806010000011644'];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->verifyOrderPayment($entry);
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
            'gmt_create' => '2018-06-01 18:01:19',
            'order_no' => '201806010000011644',
            'gmt_payment' => '2018-06-01 18:01:19',
            'seller_email' => 'gagawulala2@protonmail.com',
            'notify_time' => '2018-06-01 18:01:19',
            'quantity' => '1',
            'sign' => '0CFFCDB3404C181E5E523E64419473746CF82442',
            'discount' => '0.00',
            'body' => '201806010000011644',
            'is_success' => 'T',
            'title' => '201806010000011644',
            'gmt_logistics_modify' => '2018-06-01 18:01:19',
            'notify_id' => '99c7f42c41e7426481a57f7b65d60887',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '1.01',
            'total_fee' => '1.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801181602312',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000008888',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201806010000011644',
            'amount' => '15.00',
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'gmt_create' => '2018-06-01 18:01:19',
            'order_no' => '201806010000011644',
            'gmt_payment' => '2018-06-01 18:01:19',
            'seller_email' => 'gagawulala2@protonmail.com',
            'notify_time' => '2018-06-01 18:01:19',
            'quantity' => '1',
            'sign' => '0CFFCDB3404C181E5E523E64419473746CF82442',
            'discount' => '0.00',
            'body' => '201806010000011644',
            'is_success' => 'T',
            'title' => '201806010000011644',
            'gmt_logistics_modify' => '2018-06-01 18:01:19',
            'notify_id' => '99c7f42c41e7426481a57f7b65d60887',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'BANKPAY',
            'price' => '1.01',
            'total_fee' => '1.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101801181602312',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000008888',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201806010000011644',
            'amount' => '1.01',
        ];

        $suYinPay = new SuYinPay();
        $suYinPay->setPrivateKey('test');
        $suYinPay->setOptions($options);
        $suYinPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $suYinPay->getMsg());
    }
}
