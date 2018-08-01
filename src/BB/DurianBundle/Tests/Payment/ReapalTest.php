<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Reapal;
use Buzz\Message\Response;

class ReapalTest extends DurianTestCase
{
    /**
     * 此部分用於需要取得MerchantExtra資料的時候
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 此部分用於需要取得MerchantExtra資料的時候
     */
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
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $reapal = new Reapal();
        $reapal->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

        $sourceData = ['number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->getVerifyData();
    }

    /**
     * 測試加密時代入支付平台不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

        $sourceData = [
            'number' => '100000000001486',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'orderId' => '201406040000000001',
            'amount' => '0.01',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $reapal->setOptions($sourceData);
        $reapal->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '100000000001486',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '201406040000000001',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'merchant_extra' => ['seller_email' => 'game211@126.com'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $encodeData = $reapal->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['merchant_ID']);
        $this->assertEquals($notifyUrl, $encodeData['notify_url']);
        $this->assertEquals($notifyUrl, $encodeData['return_url']);
        $this->assertEquals($sourceData['orderId'], $encodeData['order_no']);
        $this->assertEquals('game211@126.com', $encodeData['seller_email']);
        $this->assertSame('0.01', $encodeData['total_fee']);
        $this->assertEquals('ICBC', $encodeData['defaultbank']);
        $this->assertEquals('1bfce31a19001d1978eccdc3c376e31d', $encodeData['sign']);
    }

    /**
     * 測試加密,找不到商家的SellerEmail附加設定值
     */
    public function testGetEncodeDataButNoSellerEmailSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

        $sourceData = [
            'number' => '100000000001486',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'orderId' => '201406040000000001',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'merchantId' => '54321',
            'merchant_extra' => [],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $reapal->setOptions($sourceData);
        $reapal->getVerifyData();
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $reapal = new Reapal();
        $reapal->setPrivateKey('');

        $sourceData = ['notify_id' => 'qwe123'];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳notify_id
     */
    public function testVerifyWithoutNotifyId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

        $sourceData = [
            'pay_system'           => '12345',
            'hallid'               => '6',
            'body'                 => 'body',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'order_no'             => '201406040000000001',
            'buyer_email'          => '',
            'title'                => 'title',
            'total_fee'            => '0.01',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101406045808478',
            'notify_time'          => '2014-06-04 10:58:23',
            'gmt_payment'          => '2014-06-04 10:58:23',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'sign'                 => '6b9dad5ae1a344311686802dd551535a',
            'is_success'           => 'T',
            'gmt_create'           => '2014-06-04 10:58:23',
            'price'                => '0.01',
            'seller_id'            => '100000000001486',
            'seller_email'         => 'game211@126.com',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type'         => '1'
        ];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithouttSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'is_success' => 'T',
            'gmt_create' => '2014-06-04 10:58:23',
            'price' => '0.01',
            'seller_id' => '100000000001486',
            'seller_email' => 'game211@126.com',
            'notify_id' => 'd08ac62fca9f4582bb024021ca2551b7',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
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

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'sign' => '1686802dd551535a6b9dad5ae1a34431',
            'is_success' => 'T',
            'gmt_create' => '2014-06-04 10:58:23',
            'price' => '0.01',
            'seller_id' => '100000000001486',
            'seller_email' => 'game211@126.com',
            'notify_id' => 'd08ac62fca9f4582bb024021ca2551b7',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
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

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
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

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = [
            'merchant_number' => '',
            'id' => '2014052200123'
        ];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

        $sourceData = [
            'pay_system'           => '12345',
            'hallid'               => '6',
            'body'                 => 'body',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'order_no'             => '201406040000000001',
            'buyer_email'          => '',
            'title'                => 'title',
            'total_fee'            => '0.01',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101406045808478',
            'notify_time'          => '2014-06-04 10:58:23',
            'gmt_payment'          => '2014-06-04 10:58:23',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'sign'                 => '6b9dad5ae1a344311686802dd551535a',
            'is_success'           => 'T',
            'gmt_create'           => '2014-06-04 10:58:23',
            'price'                => '0.01',
            'seller_id'            => '100000000001486',
            'seller_email'         => 'game211@126.com',
            'notify_id'            => 'd08ac62fca9f4582bb024021ca2551b7',
            'gmt_logistics_modify' => '2014-06-04 10:59:33',
            'payment_type'         => '1',
            'verify_ip'            => ['172.26.54.42', '172.26.54.41'],
            'verify_url'           => 'www.reapal.com'
        ];

        $entry = [
            'merchant_number' => '',
            'id' => '201406040000000001',
            'amount' => '1'
        ];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $result = 'invalid';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'merchant_number' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試支付時對外返回結果錯誤
     */
    public function testPayConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = 'false';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台連線異常
     */
    public function testExamineReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試訂單查詢支付平台連線失敗
     */
    public function testExamineReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 499');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台回傳結果為空
     */
    public function testReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = ['merchant_number' => ''];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($respone);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');

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
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.reapal.com'
        ];

        $entry = [
            'merchant_number' => '',
            'id' => '201406040000000001',
            'amount' => '0.0100'
        ];

        $reapal->setOptions($sourceData);
        $reapal->verifyOrderPayment($entry);

        $this->assertEquals('success', $reapal->getMsg());
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

        $reapal = new Reapal();
        $reapal->paymentTracking();
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

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->paymentTracking();
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

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
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
            'verify_url' => 'www.reapal.com'
        ];

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($response);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
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
            'verify_url' => 'www.reapal.com'
        ];

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($response);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
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
            'verify_url' => 'www.reapal.com'
        ];

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($response);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
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
            'verify_url' => 'www.reapal.com'
        ];

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($response);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
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

        $result = '<ebank>'.
            '<is_success>T</is_success>'.
            '<trade>'.
            '<status>wait</status>'.
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
            'verify_url' => 'www.reapal.com'
        ];

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($response);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
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

        $result = '<ebank>'.
            '<is_success>T</is_success>'.
            '<trade>'.
            '<status>failed</status>'.
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
            'verify_url' => 'www.reapal.com'
        ];

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($response);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
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
            'verify_url' => 'www.reapal.com',
            'amount' => '1.234'
        ];

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($response);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
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
            'verify_url' => 'www.reapal.com',
            'amount' => '0.01'
        ];

        $reapal = new Reapal();
        $reapal->setContainer($this->container);
        $reapal->setClient($this->client);
        $reapal->setResponse($response);
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $reapal = new Reapal();
        $reapal->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($options);
        $reapal->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '100000000001486',
            'orderId' => '201406040000000001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.reapal.com',
        ];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($options);
        $trackingData = $reapal->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/query/payment', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.interface.reapal.com', $trackingData['headers']['Host']);

        $this->assertEquals('100000000001486', $trackingData['form']['merchant_ID']);
        $this->assertEquals('201406040000000001', $trackingData['form']['order_no']);
        $this->assertEquals('MD5', $trackingData['form']['sign_type']);
        $this->assertEquals('ed630014a4dee522cc0d1854d244cd6e', $trackingData['form']['sign']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $reapal = new Reapal();
        $reapal->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數is_success
     */
    public function testPaymentTrackingVerifyWithoutIsSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<ebank></ebank>';
        $sourceData = ['content' => $content];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單不存在
     */
    public function testPaymentTrackingVerifyOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = '<ebank><is_success>F</is_success><result_code>TRADE_NOT_EXIST</result_code></ebank>';
        $sourceData = ['content' => $content];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單查詢失敗
     */
    public function testPaymentTrackingVerifyPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $content = '<ebank><is_success>F</is_success></ebank>';
        $sourceData = ['content' => $content];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數status
     */
    public function testPaymentTrackingVerifyWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<ebank><is_success>T</is_success></ebank>';
        $sourceData = ['content' => $content];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $content = '<ebank>' .
            '<is_success>T</is_success>' .
            '<trade>' .
            '<status>wait</status>' .
            '</trade>' .
            '</ebank>';
        $sourceData = ['content' => $content];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '<ebank>' .
            '<is_success>T</is_success>' .
            '<trade>' .
            '<status>failed</status>' .
            '</trade>' .
            '</ebank>';
        $sourceData = ['content' => $content];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = '<ebank>' .
            '<is_success>T</is_success>' .
            '<result_code>SUCCESS</result_code>' .
            '<timestamp>2014-06-04 17:29:48</timestamp>' .
            '<trade>' .
            '<trade_no>101406045808478</trade_no>' .
            '<out_trade_no>201406040000000001</out_trade_no>' .
            '<trade_type>payment</trade_type>' .
            '<amount>0.01</amount>' .
            '<fee_amount>0.00</fee_amount>' .
            '<subject>title</subject>' .
            '<trade_date>20140604</trade_date>' .
            '<created_time>2014-06-04 10:58:23</created_time>' .
            '<status>completed</status>' .
            '</trade>' .
            '</ebank>';
        $sourceData = [
            'content' => $content,
            'amount' => '1.234'
        ];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<ebank>' .
            '<is_success>T</is_success>' .
            '<result_code>SUCCESS</result_code>' .
            '<timestamp>2014-06-04 17:29:48</timestamp>' .
            '<trade>' .
            '<trade_no>101406045808478</trade_no>' .
            '<out_trade_no>201406040000000001</out_trade_no>' .
            '<trade_type>payment</trade_type>' .
            '<amount>0.01</amount>' .
            '<fee_amount>0.00</fee_amount>' .
            '<subject>title</subject>' .
            '<trade_date>20140604</trade_date>' .
            '<created_time>2014-06-04 10:58:23</created_time>' .
            '<status>completed</status>' .
            '</trade>' .
            '</ebank>';
        $sourceData = [
            'content' => $content,
            'amount' => '0.01'
        ];

        $reapal = new Reapal();
        $reapal->setPrivateKey('4ca781f941bfccb591285b70a3g000c99bd0586dce0d4375ba279f8a7gd85571');
        $reapal->setOptions($sourceData);
        $reapal->paymentTrackingVerify();
    }
}
