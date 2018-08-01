<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Okfpay;
use Buzz\Message\Response;

class OkfpayTest extends DurianTestCase
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

        $okfpay = new Okfpay();
        $okfpay->getVerifyData();
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

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->getVerifyData();
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
            'number' => '1632',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201606060000000001',
            'amount' => '100',
            'ip' => '127.0.0.1',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'orderId' => '201608160000003698',
            'amount' => '0.02',
            'ip' => '127.0.0.1',
            'number' => '1632',
            'merchantId' => '35660',
            'domain' => '6',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $data = $okfpay->getVerifyData();

        $this->assertEquals('1.0', $data['version']);
        $this->assertEquals($options['number'], $data['partner']);
        $this->assertEquals($options['orderId'], $data['orderid']);
        $this->assertEquals($options['amount'], $data['payamount']);
        $this->assertEquals($options['ip'], $data['payip']);
        $this->assertEquals($options['notify_url'], $data['notifyurl']);
        $this->assertEquals($options['notify_url'], $data['returnurl']);
        $this->assertEquals('ICBC', $data['paytype']);
        $this->assertEquals('', $data['remark']);
        $this->assertEquals('0ab88d69b342b75488c0faab52223332', $data['sign']);
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

        $okfpay = new Okfpay();
        $okfpay->verifyOrderPayment([]);
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

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->verifyOrderPayment([]);
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
            'version' => '1.0',
            'partner' => '1632',
            'orderid' => '201605250000003835',
            'payamount' => '0.02',
            'opstate' => '2',
            'orderno' => '1608161730069190501',
            'okfpaytime' => '2016/08/16 17:30:38',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '35660_6',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->verifyOrderPayment([]);
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
            'version' => '1.0',
            'partner' => '1632',
            'orderid' => '201608160000003698',
            'payamount' => '0.02',
            'opstate' => '2',
            'orderno' => '1608161730069190501',
            'okfpaytime' => '2016/08/16 17:30:38',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '35660_6',
            'sign' => '',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'version' => '1.0',
            'partner' => '1632',
            'orderid' => '201605250000003835',
            'payamount' => '0.02',
            'opstate' => '0',
            'orderno' => '1608161730069190501',
            'okfpaytime' => '2016/08/16 17:30:38',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '35660_6',
            'sign' => '7d21b62a374bb045bd7d558aef973ee4',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->verifyOrderPayment([]);
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

        $options = [
            'version' => '1.0',
            'partner' => '1632',
            'orderid' => '201605250000003835',
            'payamount' => '0.02',
            'opstate' => '4',
            'orderno' => '1608161730069190501',
            'okfpaytime' => '2016/08/16 17:30:38',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '35660_6',
            'sign' => '1c2cfb1f7161f814b9fdaffe081ec56e',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->verifyOrderPayment([]);
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
            'version' => '1.0',
            'partner' => '1632',
            'orderid' => '201605250000003835',
            'payamount' => '0.02',
            'opstate' => '2',
            'orderno' => '1608161730069190501',
            'okfpaytime' => '2016/08/16 17:30:38',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '35660_6',
            'sign' => 'b2ce40a81f4e81ed58684a0b90436602',
        ];

        $entry = ['id' => '201509140000002475'];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->verifyOrderPayment($entry);
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
            'version' => '1.0',
            'partner' => '1632',
            'orderid' => '201605250000003835',
            'payamount' => '0.02',
            'opstate' => '2',
            'orderno' => '1608161730069190501',
            'okfpaytime' => '2016/08/16 17:30:38',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '35660_6',
            'sign' => 'b2ce40a81f4e81ed58684a0b90436602',
        ];

        $entry = [
            'id' => '201605250000003835',
            'amount' => '15.00',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'version' => '1.0',
            'partner' => '1632',
            'orderid' => '201605250000003835',
            'payamount' => '0.02',
            'opstate' => '2',
            'orderno' => '1608161730069190501',
            'okfpaytime' => '2016/08/16 17:30:38',
            'message' => 'success',
            'paytype' => 'ICBC',
            'remark' => '35660_6',
            'sign' => 'b2ce40a81f4e81ed58684a0b90436602',
        ];

        $entry = [
            'id' => '201605250000003835',
            'amount' => '0.02',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $okfpay->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $okfpay = new Okfpay();
        $okfpay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '1632',
            'orderId' => '201608160000003698',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $okfpay = new Okfpay();
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳結果為空
     */
    public function testTrackingReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '1632',
            'orderId' => '201608160000003698',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
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

        $options = [
            'number' => '1632',
            'orderId' => '201608160000003698',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $result = 'orderid=201608160000003698|payamount=0.02|opstate=2|orderno=1608161730069190501|' .
            'okfpaytime=2016-08-16 17:30:38|message=支付成功|paytype=WECHAT';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
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

        $options = [
            'number' => '1632',
            'orderId' => '201608160000003698',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $result = 'partner=1632|orderid=201608160000003698|payamount=0.02|opstate=2|' .
            'orderno=1608161730069190501|okfpaytime=2016-08-16 17:30:38|message=支付成功' .
            '|paytype=WECHAT|remark=remark';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
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

        $options = [
            'number' => '1632',
            'orderId' => '201608160000003698',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $result = 'partner=1632|orderid=201608160000003698|payamount=0.02|opstate=2|' .
            'orderno=1608161730069190501|okfpaytime=2016-08-16 17:30:38|message=支付成功' .
            '|paytype=WECHAT|remark=remark|sign=9a12243f42989ce6480011a85f493ee4';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'number' => '1632',
            'orderId' => '201605270000003865',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $result = 'partner=1632|orderid=201608160000003698|payamount=0.02|opstate=0|' .
            'orderno=1608161730069190501|okfpaytime=2016-08-16 17:30:38|message=支付成功' .
            '|paytype=WECHAT|remark=remark|sign=512c9403df7cc8869fa4bcc03242e941';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
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

        $options = [
            'number' => '1632',
            'orderId' => '201605270000003865',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $result = 'partner=1632|orderid=201608160000003698|payamount=0.02|opstate=4|' .
            'orderno=1608161730069190501|okfpaytime=2016-08-16 17:30:38|message=支付失敗' .
            '|paytype=WECHAT|remark=remark|sign=5d72883f6255bef2a58683b25bd67268';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單號錯誤
     */
    public function testTrackingReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '1632',
            'orderId' => '201605270000003865',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $result = 'partner=1632|orderid=201608160000003698|payamount=0.02|opstate=2|' .
            'orderno=1608161730069190501|okfpaytime=2016-08-16 17:30:38|message=支付成功' .
            '|paytype=WECHAT|remark=remark|sign=2122292287ebbd2c47139c13d434739a';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '1632',
            'orderId' => '201608160000003698',
            'amount' => '1000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $result = 'partner=1632|orderid=201608160000003698|payamount=0.02|opstate=2|' .
            'orderno=1608161730069190501|okfpaytime=2016-08-16 17:30:38|message=支付成功' .
            '|paytype=WECHAT|remark=remark|sign=2122292287ebbd2c47139c13d434739a';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('test');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '1632',
            'orderId' => '201608160000003698',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gateway.okfpay.com',
        ];

        $result = 'partner=1632|orderid=201608160000003698|payamount=0.02|opstate=2|' .
            'orderno=1608161730069190501|okfpaytime=2016-08-16 17:30:38|message=支付成功' .
            '|paytype=WECHAT|remark=remark|sign=fec4d1a5b1077dc8b21badd4ebf81c2a';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $okfpay = new Okfpay();
        $okfpay->setContainer($this->container);
        $okfpay->setClient($this->client);
        $okfpay->setResponse($response);
        $okfpay->setPrivateKey('df97b6afb8f54099bbd46ca36433260a');
        $okfpay->setOptions($options);
        $okfpay->paymentTracking();
    }
}
