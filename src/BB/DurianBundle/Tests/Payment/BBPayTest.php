<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BBPay;
use Buzz\Message\Response;

class BBPayTest extends DurianTestCase
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

        $bbPay = new BBPay();
        $bbPay->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->getVerifyData();
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
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '100',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'ip' => '127.0.0.1',
            'number' => '123456',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $requestData = $bbPay->getVerifyData();

        $data = json_decode(urldecode($requestData['data']), true);

        $this->assertEquals($options['number'], $requestData['merchantaccount']);
        $this->assertEquals('10050', $data['amount']);
        $this->assertEquals($notifyUrl, $data['areturl']);
        $this->assertEquals($options['orderId'], $data['order']);
        $this->assertEquals('30018', $data['pnc']);
        $this->assertEquals($options['ip'], $data['userip']);
        $this->assertNotNull($data['transtime']);
        $this->assertNotNull($data['sign']);
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

        $bbPay = new BBPay();
        $bbPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回data
     */
    public function testReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->verifyOrderPayment([]);
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

        $options = [
            'merchantaccount' => 'BB01000000221',
            'encryptkey' => '1',
            'data' => '%7B%22amount%22%3A1%2C',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->verifyOrderPayment([]);
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
            'merchantaccount' => 'BB01000000221',
            'encryptkey' => '1',
            'data' => '%7B%22amount%22%3A1%2C%22bborderid%22%3A%2213181812660695' .
                '04%22%2C%22identityid%22%3A%220%22%2C%22identitytype%22%3A%220%' .
                '22%2C%22merid%22%3A%22BB01000000221%22%2C%22merrmk%22%3A%22%22%' .
                '2C%22order%22%3A%22201509140000002473%22%2C%22status%22%3A%221%' .
                '22%7D',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->verifyOrderPayment([]);
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
            'merchantaccount' => 'BB01000000221',
            'encryptkey' => '1',
            'data' => '%7B%22amount%22%3A1%2C%22bborderid%22%3A%2213181812660695' .
                '04%22%2C%22identityid%22%3A%220%22%2C%22identitytype%22%3A%220%' .
                '22%2C%22merid%22%3A%22BB01000000221%22%2C%22merrmk%22%3A%22%22%' .
                '2C%22order%22%3A%22201509140000002473%22%2C%22sign%22%3A%222355' .
                '93b3ab1c00b73a06ff355eaffd31%22%2C%22status%22%3A%221%22%7D',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->verifyOrderPayment([]);
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
            'merchantaccount' => 'BB01000000221',
            'encryptkey' => '1',
            'data' => '%7B%22amount%22%3A1%2C%22bborderid%22%3A%2213181812660695' .
                '04%22%2C%22identityid%22%3A%220%22%2C%22identitytype%22%3A%220%' .
                '22%2C%22merid%22%3A%22BB01000000221%22%2C%22merrmk%22%3A%22%22%' .
                '2C%22order%22%3A%22201509140000002473%22%2C%22sign%22%3A%22f2ff' .
                'dd39ea61960e7c53e058d8672f29%22%2C%22status%22%3A%222%22%7D',
        ];

        $entry = ['id' => '201509140000002473'];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->verifyOrderPayment($entry);
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
            'merchantaccount' => 'BB01000000221',
            'encryptkey' => '1',
            'data' => '%7B%22amount%22%3A1%2C%22bborderid%22%3A%2213181812660695' .
                '04%22%2C%22identityid%22%3A%220%22%2C%22identitytype%22%3A%220%' .
                '22%2C%22merid%22%3A%22BB01000000221%22%2C%22merrmk%22%3A%22%22%' .
                '2C%22order%22%3A%22201509140000002473%22%2C%22sign%22%3A%227022' .
                'fca5652a4239538eeb4b313138eb%22%2C%22status%22%3A%221%22%7D',
        ];

        $entry = ['id' => '201509140000002475'];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->verifyOrderPayment($entry);
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
            'merchantaccount' => 'BB01000000221',
            'encryptkey' => '1',
            'data' => '%7B%22amount%22%3A1%2C%22bborderid%22%3A%2213181812660695' .
                '04%22%2C%22identityid%22%3A%220%22%2C%22identitytype%22%3A%220%' .
                '22%2C%22merid%22%3A%22BB01000000221%22%2C%22merrmk%22%3A%22%22%' .
                '2C%22order%22%3A%22201509140000002473%22%2C%22sign%22%3A%227022' .
                'fca5652a4239538eeb4b313138eb%22%2C%22status%22%3A%221%22%7D',
        ];

        $entry = [
            'id' => '201509140000002473',
            'amount' => '15.00',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchantaccount' => 'BB01000000221',
            'encryptkey' => '1',
            'data' => '%7B%22amount%22%3A1%2C%22bborderid%22%3A%2213181812660695' .
                '04%22%2C%22identityid%22%3A%220%22%2C%22identitytype%22%3A%220%' .
                '22%2C%22merid%22%3A%22BB01000000221%22%2C%22merrmk%22%3A%22%22%' .
                '2C%22order%22%3A%22201509140000002473%22%2C%22sign%22%3A%227022' .
                'fca5652a4239538eeb4b313138eb%22%2C%22status%22%3A%221%22%7D',
        ];

        $entry = [
            'id' => '201509140000002473',
            'amount' => '0.01',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->verifyOrderPayment($entry);
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

        $bbPay = new BBPay();
        $bbPay->paymentTracking();
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

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->paymentTracking();
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

        $options = [
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線異常
     */
    public function testTrackingReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線失敗
     */
    public function testTrackingReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未返回data
     */
    public function testTrackingReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"merchantaccount":"60002001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"data":"60002001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢異常
     */
    public function testTrackingReturnPaymentTrackingError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易金额错误!',
            180123
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"error_code":"60004001", "error_msg":"\u4ea4\u6613\u91d1\u989d\u9519\u8bef!"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22%22%3A%220c362324de09' .
            '6c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptkey":' .
            '"1","merchantaccount":"BB01000000221"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22sign%22%3A%220c362224' .
            'de096c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('test');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131825466' .
            '7651072%22%2C%22closetime%22%3A%221442285373000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002476%22' .
            '%2C%22ordertime%22%3A%221442213621000%22%2C%22sign%22%3A%223c2f581a' .
            '8af1a8f9edc3f3c5cb75dae6%22%2C%22status%22%3A%2202%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入訂單號不正確
     */
    public function testPaymentTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201509140000002475',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22sign%22%3A%220c362324' .
            'de096c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
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

        $options = [
            'number' => '20130809',
            'orderId' => '201509140000002473',
            'amount' => '0.05',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22sign%22%3A%220c362324' .
            'de096c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201509140000002473',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22sign%22%3A%220c362324' .
            'de096c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $bbPay = new BBPay();
        $bbPay->setContainer($this->container);
        $bbPay->setClient($this->client);
        $bbPay->setResponse($response);
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($options);
        $bbPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $bbPay = new BBPay();
        $bbPay->getPaymentTrackingData();
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

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->getPaymentTrackingData();
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

        $options = [
            'orderId' => '201509140000002473',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($options);
        $bbPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'orderId' => '201509140000002473',
            'number' => '20130809',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.bbPay.com',
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($options);
        $trackingData = $bbPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/bbpayapi/api/query/queryOrder', $trackingData['path']);
        $this->assertEquals('payment.http.www.bbPay.com', $trackingData['headers']['Host']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('20130809', $trackingData['form']['merchantaccount']);
        $this->assertEquals('1', $trackingData['form']['encryptkey']);

        $data = '%7B%22bborderid%22%3A%22%22%2C%22orderid%22%3A%22201509140000002473%22%2C%22sign%22%3A%22' .
            '58810cb0cc21bf5bd63b48b809ba9722%22%7D';
        $this->assertEquals($data, $trackingData['form']['data']);
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

        $bbPay = new BBPay();
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單查詢異常
     */
    public function testPaymentTrackingVerifyButPaymentTrackingError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单号不存在!',
            180123
        );

        $sourceData = ['content' => '{"error_code":"60004001","error_msg":"订单号不存在!"}'];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢結果未返回data
     */
    public function testPaymentTrackingVerifyWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = ['content' => '{"merchantaccount":"60002001"}'];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = ['content' => '{"data":"60002001"}'];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22%22%3A%220c362324de09' .
            '6c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptkey":' .
            '"1","merchantaccount":"BB01000000221"}';
        $sourceData = ['content' => $content];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但簽名驗證錯誤
     */
    public function testPaymentTrackingVerifyButSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22sign%22%3A%220c362224' .
            'de096c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';
        $sourceData = ['content' => $content];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但支付失敗
     */
    public function testPaymentTrackingVerifyButPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131825466' .
            '7651072%22%2C%22closetime%22%3A%221442285373000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002476%22' .
            '%2C%22ordertime%22%3A%221442213621000%22%2C%22sign%22%3A%223c2f581a' .
            '8af1a8f9edc3f3c5cb75dae6%22%2C%22status%22%3A%2202%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';
        $sourceData = ['content' => $content];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單號不正確
     */
    public function testPaymentTrackingVerifyButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22sign%22%3A%220c362324' .
            'de096c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';
        $sourceData = [
            'content' => $content,
            'orderId' => '201509140000002475'
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但金額不正確
     */
    public function testPaymentTrackingVerifyButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22sign%22%3A%220c362324' .
            'de096c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';
        $sourceData = [
            'content' => $content,
            'orderId' => '201509140000002473',
            'amount' => '0.05'
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '{"data":"%7B%22amount%22%3A1%2C%22bborderid%22%3A%22131818126' .
            '6069504%22%2C%22closetime%22%3A%221442209217000%22%2C%22merchantNo%' .
            '22%3A%22BB01000000221%22%2C%22orderid%22%3A%22201509140000002473%22' .
            '%2C%22ordertime%22%3A%221442209141000%22%2C%22sign%22%3A%220c362324' .
            'de096c4ca2a21ac23b53ede2%22%2C%22status%22%3A%2203%22%7D","encryptk' .
            'ey":"1","merchantaccount":"BB01000000221"}';
        $sourceData = [
            'content' => $content,
            'orderId' => '201509140000002473',
            'amount' => '0.01'
        ];

        $bbPay = new BBPay();
        $bbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $bbPay->setOptions($sourceData);
        $bbPay->paymentTrackingVerify();
    }
}
