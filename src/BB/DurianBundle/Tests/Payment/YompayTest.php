<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Yompay;
use Buzz\Message\Response;

class YompayTest extends DurianTestCase
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

        $yompay = new Yompay();
        $yompay->getVerifyData();
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

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->getVerifyData();
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
            'number' => '16972',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201609290000004496',
            'amount' => '0.10',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->getVerifyData();
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
            'amount' => '1.00',
            'number' => '16972',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $encodeData = $yompay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $referer = parse_url($options['notify_url']);

        $this->assertEquals('V2.0', $encodeData['VERSION']);
        $this->assertEquals('UTF-8', $encodeData['INPUT_CHARSET']);
        $this->assertEquals($notifyUrl, $encodeData['RETURN_URL']);
        $this->assertEquals($notifyUrl, $encodeData['NOTIFY_URL']);
        $this->assertEquals('ICBC', $encodeData['BANK_CODE']);
        $this->assertEquals($options['number'], $encodeData['MER_NO']);
        $this->assertEquals($options['orderId'], $encodeData['ORDER_NO']);
        $this->assertEquals($options['amount'], $encodeData['ORDER_AMOUNT']);
        $this->assertEquals($referer['host'], $encodeData['REFERER']);
        $this->assertEquals('de169062754e73114231a3ef555c8b28', $encodeData['SIGN']);
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

        $yompay = new Yompay();
        $yompay->verifyOrderPayment([]);
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

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'pay_system' => '3482',
            'hallid' => '6',
            'mer_no' => '10999',
            'order_no' => '10999161013160611',
            'order_amount' => '1.000',
            'trade_params' => '',
            'trade_no' => '201610130000004666',
            'trade_time' => '1476344316',
            'trade_status' => 'success',
        ];

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->verifyOrderPayment([]);
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
            'pay_system' => '3482',
            'hallid' => '6',
            'mer_no' => '10999',
            'order_no' => '10999161013160611',
            'order_amount' => '1.000',
            'trade_params' => '',
            'trade_no' => '201610130000004666',
            'trade_time' => '1476344316',
            'trade_status' => 'success',
            'sign' => 'c68e4983e38592f18a1b27ef02be5268',
        ];

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->verifyOrderPayment([]);
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
            'pay_system' => '3482',
            'hallid' => '6',
            'mer_no' => '10999',
            'order_no' => '10999161013160611',
            'order_amount' => '1.000',
            'trade_params' => '',
            'trade_no' => '201610130000004666',
            'trade_time' => '1476344316',
            'trade_status' => 'fail',
            'sign' => '652a44d7a584ccf4bdb7dca2a7a6a7f0',
        ];

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->verifyOrderPayment([]);
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
            'pay_system' => '3482',
            'hallid' => '6',
            'mer_no' => '10999',
            'order_no' => '10999161013160611',
            'order_amount' => '1.000',
            'trade_params' => '',
            'trade_no' => '201610130000004666',
            'trade_time' => '1476344316',
            'trade_status' => 'success',
            'sign' => '123da721e8712807cdc4a2f62ae76437',
        ];

        $entry = ['id' => '201509140000002475'];

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->verifyOrderPayment($entry);
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
            'pay_system' => '3482',
            'hallid' => '6',
            'mer_no' => '10999',
            'order_no' => '10999161013160611',
            'order_amount' => '1.000',
            'trade_params' => '',
            'trade_no' => '201610130000004666',
            'trade_time' => '1476344316',
            'trade_status' => 'success',
            'sign' => '123da721e8712807cdc4a2f62ae76437',
        ];

        $entry = [
            'id' => '201610130000004666',
            'amount' => '15.00',
        ];

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'pay_system' => '3482',
            'hallid' => '6',
            'mer_no' => '10999',
            'order_no' => '10999161013160611',
            'order_amount' => '1.000',
            'trade_params' => '',
            'trade_no' => '201610130000004666',
            'trade_time' => '1476344316',
            'trade_status' => 'success',
            'sign' => 'c68e4983e38592f18a1b27ef02be5268',
        ];

        $entry = [
            'id' => '201610130000004666',
            'amount' => '1.00',
        ];

        $yompay = new Yompay();
        $yompay->setPrivateKey('idvrff04pvh9fzqs73e4w8jlc4e9moiext52iphhfpuk2wr561fpnl3t5zeix1z4');
        $yompay->setOptions($options);
        $yompay->verifyOrderPayment($entry);

        $this->assertEquals('success', $yompay->getMsg());
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

        $yompay = new Yompay();
        $yompay->paymentTracking();
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

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->paymentTracking();
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
            'number' => '10999',
            'orderId' => '201610130000004666',
            'orderCreateDate' => '20161013153832',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yompay = new Yompay();
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->paymentTracking();
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
            'number' => '10999',
            'orderId' => '201610130000004666',
            'orderCreateDate' => '20161013153832',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.yompay.com',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $yompay = new Yompay();
        $yompay->setContainer($this->container);
        $yompay->setClient($this->client);
        $yompay->setResponse($response);
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->paymentTracking();
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
            'number' => '10999',
            'orderId' => '201610130000004666',
            'amount' => '10.00',
            'orderCreateDate' => '20161013153832',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.yompay.com',
        ];

        $result = '{"content":{"orderStatus":"success","orderNo":"201610130000004666",' .
            '"orderAmount":"1.000","orderTime":"2016-10-13 15:38:36","bankCode":"ICBC"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $yompay = new Yompay();
        $yompay->setContainer($this->container);
        $yompay->setClient($this->client);
        $yompay->setResponse($response);
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->paymentTracking();
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

        $options = [
            'number' => '10999',
            'orderId' => '201610130000004666',
            'amount' => '1.00',
            'orderCreateDate' => '20161013153832',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.yompay.com',
        ];

        $result = '{"status":10005,"content":{"orderStatus":"paying","orderNo":"201610130000004666",' .
            '"orderAmount":"1.000","orderTime":"2016-10-13 15:38:36","bankCode":"ICBC"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $yompay = new Yompay();
        $yompay->setContainer($this->container);
        $yompay->setClient($this->client);
        $yompay->setResponse($response);
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->paymentTracking();
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
            'number' => '10999',
            'orderId' => '201610130000004666',
            'amount' => '1.00',
            'orderCreateDate' => '20161013153832',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.yompay.com',
        ];

        $result = '{"status":99999,"content":{"orderStatus":"fail","orderNo":"201610130000004666",' .
            '"orderAmount":"1.000","orderTime":"2016-10-13 15:38:36","bankCode":"ICBC"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $yompay = new Yompay();
        $yompay->setContainer($this->container);
        $yompay->setClient($this->client);
        $yompay->setResponse($response);
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->paymentTracking();
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
            'number' => '10999',
            'orderId' => '201610130000004666',
            'amount' => '0.01',
            'orderCreateDate' => '20161013153832',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.yompay.com',
        ];

        $result = '{"status":10000,"content":{"orderStatus":"success","orderNo":"201610130000004666",' .
            '"orderAmount":"1.000","orderTime":"2016-10-13 15:38:36","bankCode":"ICBC"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $yompay = new Yompay();
        $yompay->setContainer($this->container);
        $yompay->setClient($this->client);
        $yompay->setResponse($response);
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '10999',
            'orderId' => '201610130000004666',
            'amount' => '1.00',
            'orderCreateDate' => '20161013153832',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.yompay.com',
        ];

        $result = '{"status":10000,"content":{"orderStatus":"success","orderNo":"201610130000004666",' .
            '"orderAmount":"1.000","orderTime":"2016-10-13 15:38:36","bankCode":"ICBC"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $yompay = new Yompay();
        $yompay->setContainer($this->container);
        $yompay->setClient($this->client);
        $yompay->setResponse($response);
        $yompay->setPrivateKey('test');
        $yompay->setOptions($options);
        $yompay->paymentTracking();
    }
}
