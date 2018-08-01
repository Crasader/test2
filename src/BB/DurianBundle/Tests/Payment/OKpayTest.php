<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\OKpay;
use Buzz\Message\Response;

class OKpayTest extends DurianTestCase
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
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')
            ->disableOriginalConstructor()
            ->setMethods(['send'])
            ->getMock();
    }

    /**
     * 測試加密時未指定支付參數
     */
    public function testEncodeWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $okpay = new OKpay();

        $sourceData = [
            'number' => '',
            'orderId' => '201410310000000013',
            'amount' => '5',
            'ok_currency' => 'USD'
        ];

        $okpay->setOptions($sourceData);
        $okpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $okpay = new OKpay();

        $sourceData = [
            'number' => 'acctest@gmail.com',
            'orderId' => '201410310000000013',
            'amount' => '5',
            'ok_currency' => 'USD'
        ];

        $okpay->setOptions($sourceData);
        $encodeData = $okpay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['ok_receiver']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ok_item_1_name']);
        $this->assertEquals($sourceData['amount'], $encodeData['ok_item_1_price']);
        $this->assertEquals($sourceData['ok_currency'], $encodeData['ok_currency']);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $okpay = new OKpay();

        $sourceData = [
            'ok_receiver_wallet' => 'OK160023293',
            'ok_txn_currency'    => 'USD',
            'ok_txn_status'      => 'completed',
            'ok_item_1_name'     => '201410280000000108',
            'ok_item_1_amount'   => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果交易種類錯誤
     */
    public function testReturnTransactionKindError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Transaction kind error',
            180085
        );

        $okpay = new OKpay();

        $sourceData = [
            'ok_txn_kind'        => 'payment_link123',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_txn_currency'    => 'USD',
            'ok_txn_status'      => 'completed',
            'ok_item_1_name'     => '201410280000000108',
            'ok_item_1_amount'   => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment([]);
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

        $okpay = new OKpay();

        $sourceData = [
            'ok_txn_kind'        => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_txn_currency'    => 'USD',
            'ok_txn_status'      => 'completed000',
            'ok_item_1_name'     => '201410280000000108',
            'ok_item_1_amount'   => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時代入支付平台商號錯誤
     */
    public function testReturnPaymentGatewayMerchantError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Illegal merchant number',
            180082
        );

        $okpay = new OKpay();

        $sourceData = [
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK000000000',
            'ok_txn_currency' => 'USD',
            'ok_txn_status' => 'completed',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_amount' => '9.00'
        ];

        $entry = ['merchant_number' => 'OK160023293'];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時帶入幣別不合法
     */
    public function testReturnIllegalOrderCurrency()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Illegal Order currency',
            180083
        );

        $okpay = new OKpay();

        $sourceData = [
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_txn_currency' => 'CNY',
            'ok_txn_status' => 'completed',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_amount' => '9.00'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證代入錯誤單號
     */
    public function testVerifyWithErrorOrderId()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $okpay = new OKpay();

        $sourceData = [
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_txn_currency' => 'USD',
            'ok_txn_status' => 'completed',
            'ok_item_1_name' => '201410280000000111',
            'ok_item_1_amount' => '9.00'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證代入錯誤金額
     */
    public function testVerifyWithErrorAmount()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $okpay = new OKpay();

        $sourceData = [
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_txn_currency' => 'USD',
            'ok_txn_status' => 'completed',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_amount' => '9.99'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證沒有VerifyUrl
     */
    public function testVerifyWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $okpay = new OKpay();
        $okpay->setContainer($this->container);

        $sourceData = [
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_txn_currency' => 'USD',
            'ok_txn_status' => 'completed',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_amount' => '9.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台連線異常
     */
    public function testReturnPaymentGatewayConnectionError()
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

        $okpay = new OKpay();
        $okpay->setContainer($this->container);
        $okpay->setClient($this->client);

        $sourceData = [
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_txn_currency' => 'USD',
            'ok_txn_status' => 'completed',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_amount' => '9.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.okpay.com'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台連線失敗
     */
    public function testReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $this->client->expects($this->any())
            ->method('send')
            ->willReturn(null);

        $result = 'TEST';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 499 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $okpay = new OKpay();
        $okpay->setContainer($this->container);
        $okpay->setClient($this->client);
        $okpay->setResponse($response);

        $sourceData = [
            'ok_charset' => 'utf-8',
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_receiver_email' => 'accheidi@gmail.com',
            'ok_receiver_phone' => '123-45678',
            'ok_receiver_id' => '905058611',
            'ok_payer_country' => 'Afghanistan',
            'ok_payer_city' => 'Payer City',
            'ok_payer_country_code' => 'AF',
            'ok_payer_address_name' => 'Legal',
            'ok_payer_state' => 'Payer State',
            'ok_payer_street' => 'Payer Address',
            'ok_payer_zip' => '123456',
            'ok_payer_address_status' => 'confirmed',
            'ok_payer_phone' => '777-777777',
            'ok_payer_first_name' => 'Payer First Name',
            'ok_payer_last_name' => 'Payer Last Name',
            'ok_payer_business_name' => 'Payer Business Name',
            'ok_payer_email' => 'payer@okpay.com',
            'ok_payer_id' => '123456789',
            'ok_payer_reputation' => '100',
            'ok_payer_status' => 'verified',
            'ok_txn_id' => '1',
            'ok_txn_parent_id' => '10',
            'ok_txn_payment_type' => 'instant',
            'ok_txn_gross' => '100.00',
            'ok_txn_net' => '90.00',
            'ok_txn_fee' => '10.00',
            'ok_txn_currency' => 'USD',
            'ok_txn_exchange_rate' => '1.1234',
            'ok_txn_datetime' => '2014-11-14 08:43:51',
            'ok_txn_status' => 'completed',
            'ok_txn_handling' => '100.00',
            'ok_txn_shipping' => '10.00',
            'ok_txn_shipping_method' => 'Payment Shipping Method',
            'ok_txn_tax' => '1.00',
            'ok_txn_comment' => 'Payment Comment',
            'ok_invoice' => 'Payment Invoice',
            'ok_items_count' => '1',
            'ok_item_1_id' => '',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_article' => '',
            'ok_item_1_type' => 'Tangible Good (Request Delivery Address)',
            'ok_item_1_quantity' => '',
            'ok_item_1_gross' => '',
            'ok_item_1_amount' => '9.00',
            'ok_item_1_fee' => '',
            'ok_item_1_handling' => '',
            'ok_item_1_shipping' => '',
            'ok_item_1_tax' => '',
            'ok_item_1_custom_1_title' => '',
            'ok_item_1_custom_1_value' => '',
            'ok_item_1_custom_2_title' => '',
            'ok_item_1_custom_2_value' => '',
            'ok_ipn_test' => '1',
            'ok_ipn_id' => '2316576',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.okpay.com'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證代入都正確
     */
    public function testVerify()
    {
        $this->client->expects($this->any())
            ->method('send')
            ->willReturn(null);

        $result = 'VERIFIED';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $okpay = new OKpay();
        $okpay->setContainer($this->container);
        $okpay->setClient($this->client);
        $okpay->setResponse($response);

        $sourceData = [
            'ok_charset' => 'utf-8',
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_receiver_email' => 'accheidi@gmail.com',
            'ok_receiver_phone' => '123-45678',
            'ok_receiver_id' => '905058611',
            'ok_payer_country' => 'Afghanistan',
            'ok_payer_city' => 'Payer City',
            'ok_payer_country_code' => 'AF',
            'ok_payer_address_name' => 'Legal',
            'ok_payer_state' => 'Payer State',
            'ok_payer_street' => 'Payer Address',
            'ok_payer_zip' => '123456',
            'ok_payer_address_status' => 'confirmed',
            'ok_payer_phone' => '777-777777',
            'ok_payer_first_name' => 'Payer First Name',
            'ok_payer_last_name' => 'Payer Last Name',
            'ok_payer_business_name' => 'Payer Business Name',
            'ok_payer_email' => 'payer@okpay.com',
            'ok_payer_id' => '123456789',
            'ok_payer_reputation' => '100',
            'ok_payer_status' => 'verified',
            'ok_txn_id' => '1',
            'ok_txn_parent_id' => '10',
            'ok_txn_payment_type' => 'instant',
            'ok_txn_gross' => '100.00',
            'ok_txn_net' => '90.00',
            'ok_txn_fee' => '10.00',
            'ok_txn_currency' => 'USD',
            'ok_txn_exchange_rate' => '1.1234',
            'ok_txn_datetime' => '2014-11-14 08:43:51',
            'ok_txn_status' => 'completed',
            'ok_txn_handling' => '100.00',
            'ok_txn_shipping' => '10.00',
            'ok_txn_shipping_method' => 'Payment Shipping Method',
            'ok_txn_tax' => '1.00',
            'ok_txn_comment' => 'Payment Comment',
            'ok_invoice' => 'Payment Invoice',
            'ok_items_count' => '1',
            'ok_item_1_id' => '',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_article' => '',
            'ok_item_1_type' => 'Tangible Good (Request Delivery Address)',
            'ok_item_1_quantity' => '',
            'ok_item_1_gross' => '',
            'ok_item_1_amount' => '9.00',
            'ok_item_1_fee' => '',
            'ok_item_1_handling' => '',
            'ok_item_1_shipping' => '',
            'ok_item_1_tax' => '',
            'ok_item_1_custom_1_title' => '',
            'ok_item_1_custom_1_value' => '',
            'ok_item_1_custom_2_title' => '',
            'ok_item_1_custom_2_value' => '',
            'ok_ipn_test' => '1',
            'ok_ipn_id' => '2316576',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.okpay.com'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $okpay->getMsg());
    }

    /**
     * 測試返回結果為測試模式
     */
    public function testReturnIsTestMode()
    {
        $msg = 'Test mode is enabled, please turn off test mode and try again later';

        $this->setExpectedException('BB\DurianBundle\Exception\PaymentConnectionException', $msg, 180084);

        $this->client->expects($this->any())
            ->method('send')
            ->willReturn(null);

        $result = 'TEST';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $okpay = new OKpay();
        $okpay->setContainer($this->container);
        $okpay->setClient($this->client);
        $okpay->setResponse($response);

        $sourceData = [
            'ok_charset' => 'utf-8',
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_receiver_email' => 'accheidi@gmail.com',
            'ok_receiver_phone' => '123-45678',
            'ok_receiver_id' => '905058611',
            'ok_payer_country' => 'Afghanistan',
            'ok_payer_city' => 'Payer City',
            'ok_payer_country_code' => 'AF',
            'ok_payer_address_name' => 'Legal',
            'ok_payer_state' => 'Payer State',
            'ok_payer_street' => 'Payer Address',
            'ok_payer_zip' => '123456',
            'ok_payer_address_status' => 'confirmed',
            'ok_payer_phone' => '777-777777',
            'ok_payer_first_name' => 'Payer First Name',
            'ok_payer_last_name' => 'Payer Last Name',
            'ok_payer_business_name' => 'Payer Business Name',
            'ok_payer_email' => 'payer@okpay.com',
            'ok_payer_id' => '123456789',
            'ok_payer_reputation' => '100',
            'ok_payer_status' => 'verified',
            'ok_txn_id' => '1',
            'ok_txn_parent_id' => '10',
            'ok_txn_payment_type' => 'instant',
            'ok_txn_gross' => '100.00',
            'ok_txn_net' => '90.00',
            'ok_txn_fee' => '10.00',
            'ok_txn_currency' => 'USD',
            'ok_txn_exchange_rate' => '1.1234',
            'ok_txn_datetime' => '2014-11-14 08:43:51',
            'ok_txn_status' => 'completed',
            'ok_txn_handling' => '100.00',
            'ok_txn_shipping' => '10.00',
            'ok_txn_shipping_method' => 'Payment Shipping Method',
            'ok_txn_tax' => '1.00',
            'ok_txn_comment' => 'Payment Comment',
            'ok_invoice' => 'Payment Invoice',
            'ok_items_count' => '1',
            'ok_item_1_id' => '',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_article' => '',
            'ok_item_1_type' => 'Tangible Good (Request Delivery Address)',
            'ok_item_1_quantity' => '',
            'ok_item_1_gross' => '',
            'ok_item_1_amount' => '9.00',
            'ok_item_1_fee' => '',
            'ok_item_1_handling' => '',
            'ok_item_1_shipping' => '',
            'ok_item_1_tax' => '',
            'ok_item_1_custom_1_title' => '',
            'ok_item_1_custom_1_value' => '',
            'ok_item_1_custom_2_title' => '',
            'ok_item_1_custom_2_value' => '',
            'ok_ipn_test' => '1',
            'ok_ipn_id' => '2316576',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.okpay.com'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付失敗(result回傳為INVALID)
     */
    public function testReturnPaymentFailureWithResultError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->client->expects($this->any())
            ->method('send')
            ->willReturn(null);

        $result = 'INVALID';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $okpay = new OKpay();
        $okpay->setContainer($this->container);
        $okpay->setClient($this->client);
        $okpay->setResponse($response);

        $sourceData = [
            'ok_charset' => 'utf-8',
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_receiver_email' => 'accheidi@gmail.com',
            'ok_receiver_phone' => '123-45678',
            'ok_receiver_id' => '905058611',
            'ok_payer_country' => 'Afghanistan',
            'ok_payer_city' => 'Payer City',
            'ok_payer_country_code' => 'AF',
            'ok_payer_address_name' => 'Legal',
            'ok_payer_state' => 'Payer State',
            'ok_payer_street' => 'Payer Address',
            'ok_payer_zip' => '123456',
            'ok_payer_address_status' => 'confirmed',
            'ok_payer_phone' => '777-777777',
            'ok_payer_first_name' => 'Payer First Name',
            'ok_payer_last_name' => 'Payer Last Name',
            'ok_payer_business_name' => 'Payer Business Name',
            'ok_payer_email' => 'payer@okpay.com',
            'ok_payer_id' => '123456789',
            'ok_payer_reputation' => '100',
            'ok_payer_status' => 'verified',
            'ok_txn_id' => '1',
            'ok_txn_parent_id' => '10',
            'ok_txn_payment_type' => 'instant',
            'ok_txn_gross' => '100.00',
            'ok_txn_net' => '90.00',
            'ok_txn_fee' => '10.00',
            'ok_txn_currency' => 'USD',
            'ok_txn_exchange_rate' => '1.1234',
            'ok_txn_datetime' => '2014-11-14 08:43:51',
            'ok_txn_status' => 'completed',
            'ok_txn_handling' => '100.00',
            'ok_txn_shipping' => '10.00',
            'ok_txn_shipping_method' => 'Payment Shipping Method',
            'ok_txn_tax' => '1.00',
            'ok_txn_comment' => 'Payment Comment',
            'ok_invoice' => 'Payment Invoice',
            'ok_items_count' => '1',
            'ok_item_1_id' => '',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_article' => '',
            'ok_item_1_type' => 'Tangible Good (Request Delivery Address)',
            'ok_item_1_quantity' => '',
            'ok_item_1_gross' => '',
            'ok_item_1_amount' => '9.00',
            'ok_item_1_fee' => '',
            'ok_item_1_handling' => '',
            'ok_item_1_shipping' => '',
            'ok_item_1_tax' => '',
            'ok_item_1_custom_1_title' => '',
            'ok_item_1_custom_1_value' => '',
            'ok_item_1_custom_2_title' => '',
            'ok_item_1_custom_2_value' => '',
            'ok_ipn_test' => '1',
            'ok_ipn_id' => '2316576',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.okpay.com'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
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

        $this->client->expects($this->any())
            ->method('send')
            ->willReturn(null);

        $result = '';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $okpay = new OKpay();
        $okpay->setContainer($this->container);
        $okpay->setClient($this->client);
        $okpay->setResponse($response);

        $sourceData = [
            'ok_charset' => 'utf-8',
            'ok_txn_kind' => 'payment_link',
            'ok_receiver_wallet' => 'OK160023293',
            'ok_receiver_email' => 'accheidi@gmail.com',
            'ok_receiver_phone' => '123-45678',
            'ok_receiver_id' => '905058611',
            'ok_payer_country' => 'Afghanistan',
            'ok_payer_city' => 'Payer City',
            'ok_payer_country_code' => 'AF',
            'ok_payer_address_name' => 'Legal',
            'ok_payer_state' => 'Payer State',
            'ok_payer_street' => 'Payer Address',
            'ok_payer_zip' => '123456',
            'ok_payer_address_status' => 'confirmed',
            'ok_payer_phone' => '777-777777',
            'ok_payer_first_name' => 'Payer First Name',
            'ok_payer_last_name' => 'Payer Last Name',
            'ok_payer_business_name' => 'Payer Business Name',
            'ok_payer_email' => 'payer@okpay.com',
            'ok_payer_id' => '123456789',
            'ok_payer_reputation' => '100',
            'ok_payer_status' => 'verified',
            'ok_txn_id' => '1',
            'ok_txn_parent_id' => '10',
            'ok_txn_payment_type' => 'instant',
            'ok_txn_gross' => '100.00',
            'ok_txn_net' => '90.00',
            'ok_txn_fee' => '10.00',
            'ok_txn_currency' => 'USD',
            'ok_txn_exchange_rate' => '1.1234',
            'ok_txn_datetime' => '2014-11-14 08:43:51',
            'ok_txn_status' => 'completed',
            'ok_txn_handling' => '100.00',
            'ok_txn_shipping' => '10.00',
            'ok_txn_shipping_method' => 'Payment Shipping Method',
            'ok_txn_tax' => '1.00',
            'ok_txn_comment' => 'Payment Comment',
            'ok_invoice' => 'Payment Invoice',
            'ok_items_count' => '1',
            'ok_item_1_id' => '',
            'ok_item_1_name' => '201410280000000108',
            'ok_item_1_article' => '',
            'ok_item_1_type' => 'Tangible Good (Request Delivery Address)',
            'ok_item_1_quantity' => '',
            'ok_item_1_gross' => '',
            'ok_item_1_amount' => '9.00',
            'ok_item_1_fee' => '',
            'ok_item_1_handling' => '',
            'ok_item_1_shipping' => '',
            'ok_item_1_tax' => '',
            'ok_item_1_custom_1_title' => '',
            'ok_item_1_custom_1_value' => '',
            'ok_item_1_custom_2_title' => '',
            'ok_item_1_custom_2_value' => '',
            'ok_ipn_test' => '1',
            'ok_ipn_id' => '2316576',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.okpay.com'
        ];

        $entry = [
            'merchant_number' => 'OK160023293',
            'id' => '201410280000000108',
            'amount' => '9.00'
        ];

        $okpay->setOptions($sourceData);
        $okpay->verifyOrderPayment($entry);
    }
}
