<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\GlobalPay;
use Buzz\Message\Response;

class GlobalPayTest extends DurianTestCase
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
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $globalPay = new GlobalPay();
        $globalPay->getVerifyData();
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

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions([]);
        $globalPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $option = [
            'number' => '9527',
            'paymentVendorId' => '999',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->getVerifyData();
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

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
            'verify_url' => '',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少auth_result
     */
    public function testPayReturnWithoutAuthResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'trade_result' => '2',
            'error_msg' => '缺少參數',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setContainer($this->container);
        $globalPay->setClient($this->client);
        $globalPay->setResponse($response);
        $globalPay->setOptions($option);
        $globalPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回auth_result不等於SUCCESS,未返回error_msg
     */
    public function testPayReturnAuthResultNotEqualSuccessAndWithoutErrorMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'auth_result' => 'Fail',
            'trade_result' => '3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setContainer($this->container);
        $globalPay->setClient($this->client);
        $globalPay->setResponse($response);
        $globalPay->setOptions($option);
        $globalPay->getVerifyData();
    }

    /**
     * 測試支付時返回auth_result不等於SUCCESS
     */
    public function testPayReturnAuthResultNotEqualSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '參數錯誤',
            180130
        );

        $result = [
            'auth_result' => 'Fail',
            'trade_result' => '3',
            'error_msg' => '參數錯誤',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setContainer($this->container);
        $globalPay->setClient($this->client);
        $globalPay->setResponse($response);
        $globalPay->setOptions($option);
        $globalPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少trade_return_msg
     */
    public function testPayReturnWithoutTradeReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'auth_result' => 'SUCCESS',
            'trade_result' => '3',
            'error_msg' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setContainer($this->container);
        $globalPay->setClient($this->client);
        $globalPay->setResponse($response);
        $globalPay->setOptions($option);
        $globalPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $result = [
            'mer_no' => '9527',
            'mer_order_no' => '201801162100009527',
            'auth_result' => 'SUCCESS',
            'trade_result' => '3',
            'error_msg' => '',
            'trade_return_msg' => 'http://www.qqpay.com?abc',
            'mer_return_msg' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setContainer($this->container);
        $globalPay->setClient($this->client);
        $globalPay->setResponse($response);
        $globalPay->setOptions($option);
        $encodeData = $globalPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('http://www.qqpay.com?abc', $globalPay->getQrcode());
    }

    /**
     * 測試快捷支付
     */
    public function testQuickPay()
    {
        $option = [
            'number' => '9527',
            'paymentVendorId' => '1088',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $encodeData = $globalPay->getVerifyData();

        $this->assertEquals('9527', $encodeData['mer_no']);
        $this->assertEquals('201801162100009527', $encodeData['mer_order_no']);
        $this->assertEquals('1.00', $encodeData['trade_amount']);
        $this->assertEquals('quick-web', $encodeData['service_type']);
        $this->assertEquals('2018-01-16 12:30:30', $encodeData['order_date']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['page_url']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['back_url']);
        $this->assertEquals('MD5', $encodeData['sign_type']);
        $this->assertEquals('F57718AD20B28168C8F01EB27C8FECF8', $encodeData['sign']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $option = [
            'number' => '9527',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201801162100009527',
            'notify_url' => 'http://www.seafood.help/',
            'orderCreateDate' => '2018-01-16 12:30:30',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $encodeData = $globalPay->getVerifyData();

        $this->assertEquals('9527', $encodeData['mer_no']);
        $this->assertEquals('201801162100009527', $encodeData['mer_order_no']);
        $this->assertEquals('ICBC', $encodeData['channel_code']);
        $this->assertEquals('1.00', $encodeData['trade_amount']);
        $this->assertEquals('b2c', $encodeData['service_type']);
        $this->assertEquals('2018-01-16 12:30:30', $encodeData['order_date']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['page_url']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['back_url']);
        $this->assertEquals('MD5', $encodeData['sign_type']);
        $this->assertEquals('8B8CA4627FD0D2F0C98129C1DF6AD379', $encodeData['sign']);
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $globalPay = new GlobalPay();
        $globalPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions([]);
        $globalPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $option = [
            'order_no' => '9527',
            'notify_type' => 'back_notify',
            'order_date' => '2018-01-15 16:06:28',
            'pay_date' => '2018-01-15 16:07:14',
            'trade_result' => '1',
            'mer_no' => '9527',
            'trade_amount' => '0.01',
            'currency' => 'CNY',
            'sign_type' => 'MD5',
            'mer_order_no' => '201710202100009527',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->verifyOrderPayment([]);
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

        $option = [
            'order_no' => '9527',
            'notify_type' => 'back_notify',
            'order_date' => '2018-01-15 16:06:28',
            'pay_date' => '2018-01-15 16:07:14',
            'trade_result' => '1',
            'mer_no' => '9527',
            'trade_amount' => '0.01',
            'currency' => 'CNY',
            'sign_type' => 'MD5',
            'mer_order_no' => '201710202100009527',
            'sign' => 'SeafoodIsGood',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $option = [
            'order_no' => '9527',
            'notify_type' => 'back_notify',
            'order_date' => '2018-01-15 16:06:28',
            'pay_date' => '2018-01-15 16:07:14',
            'trade_result' => '0',
            'mer_no' => '9527',
            'trade_amount' => '0.01',
            'currency' => 'CNY',
            'sign_type' => 'MD5',
            'mer_order_no' => '201801162100009527',
            'sign' => 'C8F663D7160C1BBC081A2B8F7DBA45E5',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $option = [
            'order_no' => '9527',
            'notify_type' => 'back_notify',
            'order_date' => '2018-01-15 16:06:28',
            'pay_date' => '2018-01-15 16:07:14',
            'trade_result' => '99',
            'mer_no' => '9527',
            'trade_amount' => '0.01',
            'currency' => 'CNY',
            'sign_type' => 'MD5',
            'mer_order_no' => '201801162100009527',
            'sign' => '16AEEB5CAEE53254E3BE99B123FC4C37',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $option = [
            'order_no' => '9527',
            'notify_type' => 'back_notify',
            'order_date' => '2018-01-15 16:06:28',
            'pay_date' => '2018-01-15 16:07:14',
            'trade_result' => '1',
            'mer_no' => '9527',
            'trade_amount' => '0.01',
            'currency' => 'CNY',
            'sign_type' => 'MD5',
            'mer_order_no' => '201801162100009527',
            'sign' => '33194B5A565181E420047DBC8368D87B',
        ];

        $entry = ['id' => '201709220000009528'];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $option = [
            'order_no' => '9527',
            'notify_type' => 'back_notify',
            'order_date' => '2018-01-15 16:06:28',
            'pay_date' => '2018-01-15 16:07:14',
            'trade_result' => '1',
            'mer_no' => '9527',
            'trade_amount' => '0.01',
            'currency' => 'CNY',
            'sign_type' => 'MD5',
            'mer_order_no' => '201801162100009527',
            'sign' => '33194B5A565181E420047DBC8368D87B',
        ];

        $entry = [
            'id' => '201801162100009527',
            'amount' => '1.00',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $option = [
            'order_no' => '9527',
            'notify_type' => 'back_notify',
            'order_date' => '2018-01-15 16:06:28',
            'pay_date' => '2018-01-15 16:07:14',
            'trade_result' => '1',
            'mer_no' => '9527',
            'trade_amount' => '0.01',
            'currency' => 'CNY',
            'sign_type' => 'MD5',
            'mer_order_no' => '201801162100009527',
            'sign' => '33194B5A565181E420047DBC8368D87B',
        ];

        $entry = [
            'id' => '201801162100009527',
            'amount' => '0.01',
        ];

        $globalPay = new GlobalPay();
        $globalPay->setPrivateKey('test');
        $globalPay->setOptions($option);
        $globalPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $globalPay->getMsg());
    }
}
