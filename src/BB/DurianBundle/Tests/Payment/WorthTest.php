<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Worth;
use Buzz\Message\Response;

class WorthTest extends DurianTestCase
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

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $worth = new Worth();
        $worth->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->getVerifyData();
    }

    /**
     * 測試支付未带入支援银行
     */
    public function testPayButPaymentVendorIsNotSupportedByPaymentGateway()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '100000000001486',
            'notify_url' => 'http://118.232.50.208/return/return.php',
            'orderId' => '201406040000000001',
            'amount' => '0.01',
            'paymentVendorId' => '999',
            'username' => 'php1test',
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->getVerifyData();
    }

    /**
     * 測試支付沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '100000000001486',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '201406040000000001',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'merchant_extra' => ['seller_email' => 'game211@126.com'],
            'postUrl' => '',
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '100000000001486',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '201406040000000001',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'merchant_extra' => ['seller_email' => 'game211@126.com'],
            'postUrl' => 'http://www.worh.com/',
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $encodeData = $worth->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchant_ID']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notify_url']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['return_url']);
        $this->assertEquals($sourceData['orderId'], $encodeData['order_no']);
        $this->assertEquals('game211@126.com', $encodeData['seller_email']);
        $this->assertSame('0.01', $encodeData['total_fee']);
        $this->assertEquals('ICBC', $encodeData['defaultbank']);
        $this->assertEquals('5f42dcd6a08741af258f931dd5ff180b', $encodeData['sign']);
    }

    /**
     * 測試支付找不到商家的SellerEmail附加設定值
     */
    public function testPayButNoSellerEmailSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '100000000001486',
            'notify_url' => 'http://118.232.50.208/return/return.php',
            'orderId' => '201406040000000001',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'merchantId' => '54321',
            'merchant_extra' => [],
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->getVerifyData();
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

        $worth = new Worth();
        $worth->setPrivateKey('');
        $worth->setOptions([]);
        $worth->verifyOrderPayment([]);
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

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions([]);
        $worth->verifyOrderPayment([]);
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

        $sourceData = [
            'body' => 'body',
            'ext_param2' => 'ICBC',
            'sign_type' => 'MD5',
            'is_total_fee_adjust' => '0',
            'notify_type' => 'WAIT_TRIGGER',
            'order_no' => '201610260000008430',
            'buyer_email' => '',
            'title' => 'title',
            'total_fee' => '0.01',
            'seller_actions' => 'SEND_GOODS',
            'quantity' => '1',
            'buyer_id' => '',
            'trade_no' => '101610260136139',
            'notify_time' => '2016-10-26 16:42:39',
            'gmt_payment' => '2016-10-26 16:42:39',
            'trade_status' => 'TRADE_FINISHED',
            'discount' => '0.00',
            'is_success' => 'T',
            'gmt_create' => '2016-10-26 16:42:39',
            'price' => '0.01',
            'seller_id' => '100000000002049',
            'seller_email' => 'ytyt789@21cn.com',
            'notify_id' => 'daa9fbbf336343d18601ae1b85c2a495',
            'gmt_logistics_modify' => '2016-10-26 16:43:06',
            'payment_type' => '1',
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->verifyOrderPayment([]);
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

        $sourceData = [
            'body' => 'body',
            'ext_param2' => 'ICBC',
            'sign_type' => 'MD5',
            'is_total_fee_adjust' => '0',
            'notify_type' => 'WAIT_TRIGGER',
            'order_no' => '201610260000008430',
            'buyer_email' => '',
            'title' => 'title',
            'total_fee' => '0.01',
            'seller_actions' => 'SEND_GOODS',
            'quantity' => '1',
            'buyer_id' => '',
            'trade_no' => '101610260136139',
            'notify_time' => '2016-10-26 16:42:39',
            'gmt_payment' => '2016-10-26 16:42:39',
            'trade_status' => 'TRADE_FINISHED',
            'discount' => '0.00',
            'sign' => '12345678',
            'is_success' => 'T',
            'gmt_create' => '2016-10-26 16:42:39',
            'price' => '0.01',
            'seller_id' => '100000000002049',
            'seller_email' => 'ytyt789@21cn.com',
            'notify_id' => 'daa9fbbf336343d18601ae1b85c2a495',
            'gmt_logistics_modify' => '2016-10-26 16:43:06',
            'payment_type' => '1',
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->verifyOrderPayment([]);
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

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'body' => 'body',
            'sign_type' => 'MD5',
            'is_total_fee_adjust' => '0',
            'notify_type' => 'WAIT_TRIGGER',
            'order_no' => '201406040000000001',
            'buyer_email' => '',
            'title' => 'title',
            'total_fee' => '0.01',
            'seller_actions' => 'SEND_GOODS',
            'quantity' => '1',
            'buyer_id' => '',
            'trade_no' => '101406045808478',
            'notify_time' => '2014-06-04 10:58:23',
            'gmt_payment' => '2014-06-04 10:58:23',
            'trade_status' => 'WAIT_BUYER_PAY',
            'discount' => '0.00',
            'sign' => 'bbfaf81961eeb5465c67bceda5c30952',
            'is_success' => 'F',
            'gmt_create' => '2014-06-04 10:58:23',
            'price' => '0.01',
            'seller_id' => '100000000001486',
            'seller_email' => 'game211@126.com',
            'notify_id' => 'd08ac62fca9f4582bb024021ca2551b7',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type' => '1',
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->verifyOrderPayment([]);
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

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'body' => 'body',
            'sign_type' => 'MD5',
            'is_total_fee_adjust' => '0',
            'notify_type' => 'WAIT_TRIGGER',
            'order_no' => '201406040000000001',
            'buyer_email' => '',
            'title' => 'title',
            'total_fee' => '0.01',
            'seller_actions' => 'SEND_GOODS',
            'quantity' => '1',
            'buyer_id' => '',
            'trade_no' => '101406045808478',
            'notify_time' => '2014-06-04 10:58:23',
            'gmt_payment' => '2014-06-04 10:58:23',
            'trade_status' => 'TRADE_FAILURE',
            'discount' => '0.00',
            'sign' => 'f9605af3ed4f8b9a7ac88bc7b2dd9083',
            'is_success' => 'F',
            'gmt_create' => '2014-06-04 10:58:23',
            'price' => '0.01',
            'seller_id' => '100000000001486',
            'seller_email' => 'game211@126.com',
            'notify_id' => 'd08ac62fca9f4582bb024021ca2551b7',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type' => '1',
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'body' => 'body',
            'sign_type' => 'MD5',
            'is_total_fee_adjust' => '0',
            'notify_type' => 'WAIT_TRIGGER',
            'order_no' => '201406040000000001',
            'buyer_email' => '',
            'title' => 'title',
            'total_fee' => '0.01',
            'seller_actions' => 'SEND_GOODS',
            'quantity' => '1',
            'buyer_id' => '',
            'trade_no' => '101406045808478',
            'notify_time' => '2014-06-04 10:58:23',
            'gmt_payment' => '2014-06-04 10:58:23',
            'trade_status' => 'TRADE_FINISHED',
            'discount' => '0.00',
            'sign' => '6b9dad5ae1a344311686802dd551535a',
            'is_success' => 'T',
            'gmt_create' => '2014-06-04 10:58:23',
            'price' => '0.01',
            'seller_id' => '100000000001486',
            'seller_email' => 'game211@126.com',
            'notify_id' => 'd08ac62fca9f4582bb024021ca2551b7',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type' => '1',
        ];

        $entry = ['id' => '2014052200123'];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'body' => 'body',
            'sign_type' => 'MD5',
            'is_total_fee_adjust' => '0',
            'notify_type' => 'WAIT_TRIGGER',
            'order_no' => '201406040000000001',
            'buyer_email' => '',
            'title' => 'title',
            'total_fee' => '0.01',
            'seller_actions' => 'SEND_GOODS',
            'quantity' => '1',
            'buyer_id' => '',
            'trade_no' => '101406045808478',
            'notify_time' => '2014-06-04 10:58:23',
            'gmt_payment' => '2014-06-04 10:58:23',
            'trade_status' => 'TRADE_FINISHED',
            'discount' => '0.00',
            'sign' => '6b9dad5ae1a344311686802dd551535a',
            'is_success' => 'T',
            'gmt_create' => '2014-06-04 10:58:23',
            'price' => '0.01',
            'seller_id' => '100000000001486',
            'seller_email' => 'game211@126.com',
            'notify_id' => 'd08ac62fca9f4582bb024021ca2551b7',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type' => '1',
        ];

        $entry = [
            'id' => '201406040000000001',
            'amount' => '1'
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'body' => 'body',
            'sign_type' => 'MD5',
            'is_total_fee_adjust' => '0',
            'notify_type' => 'WAIT_TRIGGER',
            'order_no' => '201406040000000001',
            'buyer_email' => '',
            'title' => 'title',
            'total_fee' => '0.01',
            'seller_actions' => 'SEND_GOODS',
            'quantity' => '1',
            'buyer_id' => '',
            'trade_no' => '101406045808478',
            'notify_time' => '2014-06-04 10:58:23',
            'gmt_payment' => '2014-06-04 10:58:23',
            'trade_status' => 'TRADE_FINISHED',
            'discount' => '0.00',
            'sign' => '6b9dad5ae1a344311686802dd551535a',
            'is_success' => 'T',
            'gmt_create' => '2014-06-04 10:58:23',
            'price' => '0.01',
            'seller_id' => '100000000001486',
            'seller_email' => 'game211@126.com',
            'notify_id' => 'd08ac62fca9f4582bb024021ca2551b7',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type' => '1',
        ];

        $entry = [
            'id' => '201406040000000001',
            'amount' => '0.0100'
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->verifyOrderPayment($entry);

        $this->assertEquals('success', $worth->getMsg());
    }

    /**
     * 測試訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $worth = new Worth();
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入verifyUrl
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $worth = new Worth();
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數is_success
     */
    public function testPaymentTrackingResultWithoutIsSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<ebank></ebank>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.worth.com'
        ];

        $worth = new Worth();
        $worth->setContainer($this->container);
        $worth->setClient($this->client);
        $worth->setResponse($response);
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單不存在
     */
    public function testTrackingReturnOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = '<ebank><is_success>F</is_success><result_code>TRADE_NOT_EXIST</result_code></ebank>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.worth.com'
        ];

        $worth = new Worth();
        $worth->setContainer($this->container);
        $worth->setClient($this->client);
        $worth->setResponse($response);
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '<ebank><is_success>F</is_success></ebank>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.worth.com'
        ];

        $worth = new Worth();
        $worth->setContainer($this->container);
        $worth->setClient($this->client);
        $worth->setResponse($response);
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數status
     */
    public function testPaymentTrackingResultWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<ebank><is_success>T</is_success></ebank>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.worth.com'
        ];

        $worth = new Worth();
        $worth->setContainer($this->container);
        $worth->setClient($this->client);
        $worth->setResponse($response);
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = '<ebank><is_success>T</is_success><trade><status>wait</status></trade></ebank>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.worth.com'
        ];

        $worth = new Worth();
        $worth->setContainer($this->container);
        $worth->setClient($this->client);
        $worth->setResponse($response);
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '<ebank><is_success>T</is_success><trade><status>failed</status></trade></ebank>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.worth.com'
        ];

        $worth = new Worth();
        $worth->setContainer($this->container);
        $worth->setClient($this->client);
        $worth->setResponse($response);
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '<ebank>'.
            '<is_success>T</is_success>'.
            '<result_code>SUCCESS</result_code>'.
            '<timestamp>2014-06-04 17:29:48</timestamp>'.
            '<trade>'.
            '<trade_no>101406045808478</trade_no>'.
            '<out_trade_no>201406040000000001</out_trade_no>'.
            '<trade_type>payment</trade_type>'.
            '<amount>0.01</amount>'.
            '<fee_amount>0.00</fee_amount>'.
            '<subject>title</subject>'.
            '<trade_date>20140604</trade_date>'.
            '<created_time>2014-06-04 10:58:23</created_time>'.
            '<status>completed</status>'.
            '</trade>'.
            '</ebank>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.worth.com',
            'amount' => '1.234'
        ];

        $worth = new Worth();
        $worth->setContainer($this->container);
        $worth->setClient($this->client);
        $worth->setResponse($response);
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = '<ebank>'.
            '<is_success>T</is_success>'.
            '<result_code>SUCCESS</result_code>'.
            '<timestamp>2014-06-04 17:29:48</timestamp>'.
            '<trade>'.
            '<trade_no>101406045808478</trade_no>'.
            '<out_trade_no>201406040000000001</out_trade_no>'.
            '<trade_type>payment</trade_type>'.
            '<amount>0.01</amount>'.
            '<fee_amount>0.00</fee_amount>'.
            '<subject>title</subject>'.
            '<trade_date>20140604</trade_date>'.
            '<created_time>2014-06-04 10:58:23</created_time>'.
            '<status>completed</status>'.
            '</trade>'.
            '</ebank>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.worth.com',
            'amount' => '0.01'
        ];

        $worth = new Worth();
        $worth->setContainer($this->container);
        $worth->setClient($this->client);
        $worth->setResponse($response);
        $worth->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $worth->setOptions($sourceData);
        $worth->paymentTracking();
    }
}
