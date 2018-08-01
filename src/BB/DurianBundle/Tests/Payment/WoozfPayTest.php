<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\WoozfPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class WoozfPayTest extends DurianTestCase
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

        $woozfPay = new WoozfPay();
        $woozfPay->getVerifyData();
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

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->getVerifyData();
    }

    /**
     * 測試支付設定時帶入不支援銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'notify_url' => 'http://192.168.242.141/',
            'paymentVendorId' => '100',
            'number' => '9527',
            'orderId' => '201706260000009453',
            'amount' => '100',
            'orderCreateDate' => '20170626105012',
            'ip' => '127.0.0.1',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->getVerifyData();
    }

    /**
     * 測試支付設定時帶入錯誤的notify_url
     */
    public function testPayInvalidNotifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Invalid notify_url',
            180146
        );

        $sourceData = [
            'notify_url' => 'test',
            'paymentVendorId' => '1',
            'number' => '9527',
            'orderId' => '201706260000009453',
            'amount' => '100',
            'orderCreateDate' => '20170626105012',
            'ip' => '127.0.0.1',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $sourceData = [
            'notify_url' => 'http://192.168.242.141/',
            'paymentVendorId' => '1',
            'number' => '9527',
            'orderId' => '201706260000009453',
            'amount' => '100',
            'orderCreateDate' => '2017-06-26 15:35:24',
            'ip' => '127.0.0.1',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $requestData = $woozfPay->getVerifyData();

        $this->assertEquals('UTF-8', $requestData['input_charset']);
        $this->assertEquals('http://192.168.242.141/', $requestData['notify_url']);
        $this->assertEquals('1', $requestData['pay_type']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
        $this->assertEquals('9527', $requestData['merchant_code']);
        $this->assertEquals('201706260000009453', $requestData['order_no']);
        $this->assertEquals('100', $requestData['order_amount']);
        $this->assertEquals('2017-06-26 15:35:24', $requestData['order_time']);
        $this->assertEquals('127.0.0.1', $requestData['customer_ip']);
        $this->assertEquals('d152d54c24f093e43fece2641bd5df8a', $requestData['sign']);
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

        $woozfPay = new WoozfPay();
        $woozfPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回指定參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\Durianbundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201706260000002073',
            'order_amount' => '100',
            'order_time' => '2017-06-26 14:22:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-06-26 14:24:24',
            'trade_status' => 'success',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\Durianbundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201706260000002073',
            'order_amount' => '100',
            'order_time' => '2017-06-26 14:22:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-06-26 14:24:24',
            'trade_status' => 'success',
            'sign' => 'signerror',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->verifyOrderPayment([]);
    }

    /**
     *測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\Durianbundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201706260000002073',
            'order_amount' => '100',
            'order_time' => '2017-06-26 14:22:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-06-26 14:24:24',
            'trade_status' => 'failed',
            'sign' => 'd781bff5d2661c5cd0c952546fc4351a',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\Durianbundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201706260000002073',
            'order_amount' => '100',
            'order_time' => '2017-06-26 14:22:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-06-26 14:24:24',
            'trade_status' => 'success',
            'sign' => 'c7568dfa99248093e0e11e64e0989bb1',
        ];

        $entry = ['id' => '201706260000002074'];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            '180058'
        );

        $sourceData = [
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201706260000002073',
            'order_amount' => '100',
            'order_time' => '2017-06-26 14:22:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-06-26 14:24:24',
            'trade_status' => 'success',
            'sign' => 'c7568dfa99248093e0e11e64e0989bb1',
        ];

        $entry = [
            'id' => '201706260000002073',
            'amount' => 15.00,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201706260000002073',
            'order_amount' => '100',
            'order_time' => '2017-06-26 14:22:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-06-26 14:24:24',
            'trade_status' => 'success',
            'sign' => 'c7568dfa99248093e0e11e64e0989bb1',
        ];

        $entry = [
            'id' => '201706260000002073',
            'amount' => 100.00,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $woozfPay->getMsg());
    }

    /**
     * 測試訂單查詢沒帶入privateKey
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $woozfPay = new WoozfPay();
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $sourceData = ['number' => '19822546'];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有response的情況
     */
    public function testTrackingReturnWithoutResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><pay></pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果is_success為FALSE
     */
    public function testTrackingReturnIsSuccessFalse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '参数order_no的值201711230000002766不存在',
            180130
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>FALSE</is_success>' .
            '<error_msg>参数order_no的值201711230000002766不存在</error_msg>' .
            '</response></pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response><is_success></is_success></response></pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>FALSE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有sign的情況
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>123456789</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果交易中
     */
    public function testTrackingReturnPaymentOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>paying</trade_status>' .
            '<sign>48bc1edc220d8278296ed96f7817669d</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>fail</trade_status>' .
            '<sign>74d6955e93da7c688fc4eb7a9ccfba8e</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢單號不正確
     */
    public function testPaymentTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>d8aa0cf6fa5772721af9066c12efd82b</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002074',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>d8aa0cf6fa5772721af9066c12efd82b</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'amount' => '400.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>d8aa0cf6fa5772721af9066c12efd82b</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setContainer($this->container);
        $woozfPay->setClient($this->client);
        $woozfPay->setResponse($response);
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒帶入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $woozfPay = new WoozfPay();
        $woozfPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $sourceData = ['number' => '9527'];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $trackingData = $woozfPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/query', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.pay.woozf.com', $trackingData['headers']['Host']);
        $this->assertEquals('UTF-8', $trackingData['form']['input_charset']);
        $this->assertEquals('9527', $trackingData['form']['merchant_code']);
        $this->assertEquals('2f08ce3a7fc44a5075ec01ba94f3b019', $trackingData['form']['sign']);
        $this->assertEquals('201706260000002073', $trackingData['form']['order_no']);
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數response
     */
    public function testPaymentTrackingVerifyWithoutResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?><pay></pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢結果is_success為FALSE
     */
    public function testPaymentTrackingVerifyWithIsSuccessFalse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '参数order_no的值201711230000002766不存在',
            180130
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response><is_success>FALSE</is_success>' .
            '<error_msg>参数order_no的值201711230000002766不存在</error_msg>' .
            '</response></pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response><is_success></is_success></response></pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testPaymentTrackingVerifyWithPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>FALSE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '</response>' .
            '</pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢結果驗證沒有sign的情況
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '</response>' .
            '</pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果簽名驗證錯誤
     */
    public function testPaymentTrackingVerifyWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>123456789</sign>' .
            '</response>' .
            '</pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果交易中
     */
    public function testPaymentTrackingVerifyWithPaymentOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>paying</trade_status>' .
            '<sign>48bc1edc220d8278296ed96f7817669d</sign>' .
            '</response>' .
            '</pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果支付失敗
     */
    public function testPaymentTrackingVerifyWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>fail</trade_status>' .
            '<sign>74d6955e93da7c688fc4eb7a9ccfba8e</sign>' .
            '</response>' .
            '</pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢單號不正確
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>d8aa0cf6fa5772721af9066c12efd82b</sign>' .
            '</response>' .
            '</pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002074',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>d8aa0cf6fa5772721af9066c12efd82b</sign>' .
            '</response>' .
            '</pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'amount' => '400.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay><response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_no>201706260000002073</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2017-06-26 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2017-06-26 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>d8aa0cf6fa5772721af9066c12efd82b</sign>' .
            '</response>' .
            '</pay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706260000002073',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.pay.woozf.com',
            'content' => $result,
        ];

        $woozfPay = new WoozfPay();
        $woozfPay->setPrivateKey('test');
        $woozfPay->setOptions($sourceData);
        $woozfPay->paymentTrackingVerify();
    }
}
