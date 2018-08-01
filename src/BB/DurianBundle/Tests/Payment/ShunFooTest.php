<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShunFoo;
use Buzz\Message\Response;

class ShunFooTest extends DurianTestCase
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

        $shunFoo = new ShunFoo();
        $shunFoo->getVerifyData();
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

        $sourceData = ['number' => ''];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1991',
            'paymentVendorId' => '7',
            'amount' => '2.00',
            'orderId' => '201708160000003861',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1991',
            'paymentVendorId' => '1',
            'amount' => '2.00',
            'orderId' => '201708160000003861',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $encodeData = $shunFoo->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertSame('2.00', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('e72d3cd60400828cf40a86211abaf00a', $encodeData['sign']);
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

        $shunFoo = new ShunFoo();
        $shunFoo->verifyOrderPayment([]);
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

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201702090000001338',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1991201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'orderid' => '201702090000001338',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1991201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '8b27dea7edf10f440ad4852b771fd4fb',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnWithInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $sourceData = [
            'orderid' => '201702090000001338',
            'opstate' => '-1',
            'ovalue' => '2',
            'sysorderid' => 'B1991201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '402d6bef126851ab97310e77770ae7c5',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付平台驗證簽名錯誤
     */
    public function testReturnWithPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $sourceData = [
            'orderid' => '201702090000001338',
            'opstate' => '-2',
            'ovalue' => '2',
            'sysorderid' => 'B1991201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '2c87c607ca397d6af3513c8429a2d9ee',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->verifyOrderPayment([]);
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

        $sourceData = [
            'orderid' => '201702090000001338',
            'opstate' => '99',
            'ovalue' => '2',
            'sysorderid' => 'B1991201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '4139f4c535217b05dda8d616e53087ef',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201702090000001338',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1991201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '70dc4e21334ae9698abdeb354b0ebbb4',
        ];

        $entry = ['id' => '201702090000001337'];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201702090000001338',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1991201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '70dc4e21334ae9698abdeb354b0ebbb4',
        ];

        $entry = [
            'id' => '201702090000001338',
            'amount' => '0.01',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201702090000001338',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1991201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '70dc4e21334ae9698abdeb354b0ebbb4',
        ];

        $entry = [
            'id' => '201702090000001338',
            'amount' => '2',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $shunFoo->getMsg());
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

        $shunFoo = new ShunFoo();
        $shunFoo->paymentTracking();
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

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->paymentTracking();
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
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為缺少回傳參數
     */
    public function testTrackingReturnWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'orderid=201702100000001344&opstate=99&sign=d8cb197eecc0c967421fa02a890f16a5';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果Sign為空
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'orderid=201702100000001344&opstate=99&ovalue=0';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢返回簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = 'orderid=201702100000001344&opstate=99&ovalue=0&sign=d8cb197eecc0c967421fa02a890f16a5';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢返回請求參數無效
     */
    public function testTrackingReturnSubmitTheParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $result = 'orderid=201702100000001344&opstate=3&ovalue=0&sign=9a15232b72f557b899d497e4ae57b8b3';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢返回簽名錯誤
     */
    public function testTrackingReturnMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $result = 'orderid=201702100000001344&opstate=2&ovalue=0&sign=fa228cab3aa4206455fe4302baae2e96';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢商戶訂單號無效
     */
    public function testTrackingReturnOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = 'orderid=201702100000001344&opstate=1&ovalue=0&sign=2c3a32a08df57985e94c31b83ab0d918';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢返回支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = 'orderid=201702100000001344&opstate=99&ovalue=0&sign=2cbd59b6f2fbf1fab318a8b5eef8cdcc';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = 'orderid=201702100000001344&opstate=0&ovalue=0&sign=c78c4eda4dd176a303e02e31ab7ab8dc';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702090000001338',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingReturnWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = 'orderid=201702100000001344&opstate=0&ovalue=0.01&sign=a7f330f1bd34d3da7170968152c03dd0';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = 'orderid=201702100000001344&opstate=0&ovalue=0.02&sign=32fe8469eb4b981d1db98f4efd72dd96';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setContainer($this->container);
        $shunFoo->setClient($this->client);
        $shunFoo->setResponse($response);
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少私鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shunFoo = new ShunFoo();
        $shunFoo->getPaymentTrackingData();
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

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->getPaymentTrackingData();
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

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $trackingData = $shunFoo->getPaymentTrackingData();

        $path = '/search.aspx?parter=1991&orderid=201702100000001344&sign=9b7c4b9b1670a8be8b8b8a944ef44231';

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少私鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shunFoo = new ShunFoo();
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'orderid=201702100000001344&opstate=99&sign=d8cb197eecc0c967421fa02a890f16a5';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時Sign為空
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'orderid=201702100000001344&opstate=99&ovalue=0';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = 'orderid=201702100000001344&opstate=99&ovalue=0&sign=d8cb197eecc0c967421fa02a890f16a5';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為請求參數無效
     */
    public function testPaymentTrackingVerifySubmitTheParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $result = 'orderid=201702100000001344&opstate=3&ovalue=0&sign=9a15232b72f557b899d497e4ae57b8b3';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為簽名錯誤
     */
    public function testPaymentTrackingVerifyMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $result = 'orderid=201702100000001344&opstate=2&ovalue=0&sign=fa228cab3aa4206455fe4302baae2e96';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為商戶訂單號無效
     */
    public function testPaymentTrackingVerifyOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = 'orderid=201702100000001344&opstate=1&ovalue=0&sign=2c3a32a08df57985e94c31b83ab0d918';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = 'orderid=201702100000001344&opstate=&ovalue=&sign=66ef36185d0f9a3a261ac4f5a5523c54';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = 'orderid=201702100000001344&opstate=0&ovalue=0&sign=c78c4eda4dd176a303e02e31ab7ab8dc';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702090000001338',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單金額錯誤
     */
    public function testPaymentTrackingVerifyWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = 'orderid=201702100000001344&opstate=0&ovalue=0.01&sign=a7f330f1bd34d3da7170968152c03dd0';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $result = 'orderid=201702100000001344&opstate=0&ovalue=0.02&sign=32fe8469eb4b981d1db98f4efd72dd96';

        $sourceData = [
            'number' => '1991',
            'orderId' => '201702100000001344',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.interface.shunfoo.com',
            'content' => $result,
        ];

        $shunFoo = new ShunFoo();
        $shunFoo->setPrivateKey('test');
        $shunFoo->setOptions($sourceData);
        $shunFoo->paymentTrackingVerify();
    }
}
