<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DuoBao;
use Buzz\Message\Response;

class DuoBaoTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

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

        $duoBao = new DuoBao();
        $duoBao->getVerifyData();
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

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->getVerifyData();
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
            'number' => '987107',
            'paymentVendorId' => '9487',
            'amount' => '100',
            'orderId' => '201702090000000978',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'username' => 'php1test',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '987107',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderId' => '201702090000000978',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'username' => 'php1test',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $requestData = $duoBao->getVerifyData();

        $this->assertEquals($options['number'], $requestData['parter']);
        $this->assertEquals('1004', $requestData['type']);
        $this->assertEquals($options['amount'], $requestData['value']);
        $this->assertEquals($options['orderId'], $requestData['orderid']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('', $requestData['hrefbackurl']);
        $this->assertEquals('', $requestData['onlyqr']);
        $this->assertEquals($options['username'], $requestData['attach']);
        $this->assertEquals('4aef0bbb4fe070ecf2e734a29a06d860', $requestData['sign']);
    }

    /**
     * 測試返回缺少密鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $duoBao = new DuoBao();
        $duoBao->verifyOrderPayment([]);
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

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'orderid' => '201703070000001306',
            'opstate' => '0',
            'ovalue' => '1',
            'systime' => '2017/03/07 12:08:43',
            'sysorderid' => '17030712081394020854',
            'completiontime' => '2017/03/07 12:08:43',
            'attach' => 'php1test',
            'msg' => '',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回簽名驗證錯誤
     */
    public function testReturnWithSignatureVerificationFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'orderid' => '201703070000001306',
            'opstate' => '0',
            'ovalue' => '1',
            'systime' => '2017/03/07 12:08:43',
            'sysorderid' => '17030712081394020854',
            'completiontime' => '2017/03/07 12:08:43',
            'attach' => 'php1test',
            'msg' => '',
            'sign' => '12345678901234567890',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'orderid' => '201703070000001306',
            'opstate' => '9487',
            'ovalue' => '1',
            'systime' => '2017/03/07 12:08:43',
            'sysorderid' => '17030712081394020854',
            'completiontime' => '2017/03/07 12:08:43',
            'attach' => 'php1test',
            'msg' => '',
            'sign' => '961dbcf748bf0dbd9e1644e6962c83ee',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->verifyOrderPayment([]);
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
            'orderid' => '201703070000001306',
            'opstate' => '0',
            'ovalue' => '1',
            'systime' => '2017/03/07 12:08:43',
            'sysorderid' => '17030712081394020854',
            'completiontime' => '2017/03/07 12:08:43',
            'attach' => 'php1test',
            'msg' => '',
            'sign' => 'e9ba61270c8f4292c5613df60294a7f2',
        ];

        $entry = ['id' => '201612280000000000'];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->verifyOrderPayment($entry);
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
            'orderid' => '201703070000001306',
            'opstate' => '0',
            'ovalue' => '1',
            'systime' => '2017/03/07 12:08:43',
            'sysorderid' => '17030712081394020854',
            'completiontime' => '2017/03/07 12:08:43',
            'attach' => 'php1test',
            'msg' => '',
            'sign' => 'e9ba61270c8f4292c5613df60294a7f2',
        ];

        $entry = [
            'id' => '201703070000001306',
            'amount' => '9487',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'orderid' => '201703070000001306',
            'opstate' => '0',
            'ovalue' => '1',
            'systime' => '2017/03/07 12:08:43',
            'sysorderid' => '17030712081394020854',
            'completiontime' => '2017/03/07 12:08:43',
            'attach' => 'php1test',
            'msg' => '',
            'sign' => 'e9ba61270c8f4292c5613df60294a7f2',
        ];

        $entry = [
            'id' => '201703070000001306',
            'amount' => '1',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $duoBao->getMsg());
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

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
        ];

        $duoBao = new DuoBao();
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
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

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入 verify_url 的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有 sign
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gwbb69.169.cc',
        ];

        $response = new Response();
        $response->setContent('orderid=201703070000001306&ovalue=1.00');
        $response->addHeader('HTTP/1.1 200 OK');

        $duoBao = new DuoBao();
        $duoBao->setContainer($this->container);
        $duoBao->setClient($this->client);
        $duoBao->setResponse($response);
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回缺少必要參數
     */
    public function testTrackingReturnWithoutTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gwbb69.169.cc',
        ];

        $response = new Response();
        $response->setContent('orderid=201703070000001306&ovalue=1.00&sign=c2d4a34961a10b5f49658933417de114');
        $response->addHeader('HTTP/1.1 200 OK');

        $duoBao = new DuoBao();
        $duoBao->setContainer($this->container);
        $duoBao->setClient($this->client);
        $duoBao->setResponse($response);
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
    }

    /**
     * 測試訂單查詢簽名錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gwbb69.169.cc',
        ];

        $response = new Response();
        $response->setContent('orderid=201703070000001306&opstate=0&ovalue=1.00&sign=123');
        $response->addHeader('HTTP/1.1 200 OK');

        $duoBao = new DuoBao();
        $duoBao->setContainer($this->container);
        $duoBao->setClient($this->client);
        $duoBao->setResponse($response);
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
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
            'number' => '987107',
            'orderId' => '201703070000001306',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gwbb69.169.cc',
        ];

        $response = new Response();
        $response->setContent('orderid=201703070000001306&opstate=4&ovalue=&sign=94bdcc689e9e01e8bb92c56cabfe302a');
        $response->addHeader('HTTP/1.1 200 OK');

        $duoBao = new DuoBao();
        $duoBao->setContainer($this->container);
        $duoBao->setClient($this->client);
        $duoBao->setResponse($response);
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回單號錯誤
     */
    public function testTrackingReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gwbb69.169.cc',
        ];


        $response = new Response();
        $response->setContent('orderid=123&opstate=0&ovalue=1.00&sign=4da673d9c7ca439e599243e4b2251b22');
        $response->addHeader('HTTP/1.1 200 OK');

        $duoBao = new DuoBao();
        $duoBao->setContainer($this->container);
        $duoBao->setClient($this->client);
        $duoBao->setResponse($response);
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'amount' => '11',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gwbb69.169.cc',
        ];

        $response = new Response();
        $response->setContent('orderid=201703070000001306&opstate=0&ovalue=1.00&sign=bd07bb4fd68a6dfb5621528a1e15677c');
        $response->addHeader('HTTP/1.1 200 OK');

        $duoBao = new DuoBao();
        $duoBao->setContainer($this->container);
        $duoBao->setClient($this->client);
        $duoBao->setResponse($response);
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gwbb69.169.cc',
        ];

        $response = new Response();
        $response->setContent('orderid=201703070000001306&opstate=0&ovalue=1.00&sign=bd07bb4fd68a6dfb5621528a1e15677c');
        $response->addHeader('HTTP/1.1 200 OK');

        $duoBao = new DuoBao();
        $duoBao->setContainer($this->container);
        $duoBao->setClient($this->client);
        $duoBao->setResponse($response);
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'amount' => '0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $duoBao = new DuoBao();
        $duoBao->setOptions($options);
        $duoBao->getPaymentTrackingData();
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

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'amount' => '0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $duoBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '987107',
            'orderId' => '201703070000001306',
            'amount' => '0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $duoBao = new DuoBao();
        $duoBao->setPrivateKey('test');
        $duoBao->setOptions($options);
        $trackingData = $duoBao->getPaymentTrackingData();

        $path = '/interface/search.aspx?orderid=201703070000001306&parter=987107&sign=9e1d1afbfe9265653f69d7df501cf832';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals($options['verify_url'], $trackingData['headers']['Host']);
    }
}
