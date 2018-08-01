<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\JuXinPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class JuXinPayTest extends DurianTestCase
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
            ->will($this->returnValue(null));

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
    public function testPayWithPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $juXinPay = new JuXinPay();
        $juXinPay->getVerifyData();
    }

    /**
     *  測試支付時沒有指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->getVerifyData();
    }

    /**
     *  測試支付時帶入不支援銀行
     */
    public function testPayWithoutSupportedBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '201707280000000104',
            'paymentVendorId' => '99',
            'amount' => '1.00',
            'orderCreateDate' => '2017-07-28 21:25:29',
            'ip' => '192.111.666.888',
            'notify_url' => 'http://9527.com/',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->getVerifyData();
    }

    /**
     *  測試支付成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201707280000000104',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderCreateDate' => '2017-07-28 21:25:29',
            'ip' => '192.111.666.888',
            'notify_url' => 'http://9527.com/',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $requestData = $juXinPay->getVerifyData();

        $this->assertEquals('1.0', $requestData['version']);
        $this->assertEquals('9527', $requestData['agentId']);
        $this->assertEquals('201707280000000104', $requestData['agentOrderId']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
        $this->assertEquals('1.00', $requestData['payAmt']);
        $this->assertEquals('20170728212529', $requestData['orderTime']);
        $this->assertEquals('192.111.666.888', $requestData['payIp']);
        $this->assertEquals('http://9527.com/', $requestData['notifyUrl']);
        $this->assertEquals('', $requestData['noticePage']);
        $this->assertEquals('', $requestData['remark']);
        $this->assertEquals('9109bf97691b5f7aba438e5ea23c6076', $requestData['sign']);
    }

    /**
     *  測試支付成功(微信)
     */
    public function testPaySuccessWeChat()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201707280000000104',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderCreateDate' => '2017-07-28 21:25:29',
            'ip' => '192.111.666.888',
            'notify_url' => 'http://9527.com/',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $requestData = $juXinPay->getVerifyData();

        $this->assertEquals('1.0', $requestData['version']);
        $this->assertEquals('9527', $requestData['agentId']);
        $this->assertEquals('201707280000000104', $requestData['agentOrderId']);
        $this->assertEquals('1.00', $requestData['payAmt']);
        $this->assertEquals('20170728212529', $requestData['orderTime']);
        $this->assertEquals('192.111.666.888', $requestData['payIp']);
        $this->assertEquals('http://9527.com/', $requestData['notifyUrl']);
        $this->assertEquals('', $requestData['noticePage']);
        $this->assertEquals('', $requestData['remark']);
        $this->assertEquals('038e818a4da0cc20d03b9a5144126d97', $requestData['sign']);
        $this->assertArrayNotHasKey('bankCode', $requestData);
    }

    /**
     *  測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $juXinPay = new JuXinPay();
        $juXinPay->verifyOrderPayment([]);
    }

    /**
     *測試返回時未指定返回參數
     */
    public function testReturnWithReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->verifyOrderPayment([]);
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
            'version' => '1.0',
            'agentId' => '9527',
            'agentOrderId' => '201707280000000104',
            'jnetOrderId' => '123456789',
            'payAmt' => '1.00',
            'payResult' => 'SUCCESS',
            'payMessage' => '',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->verifyOrderPayment([]);
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
            'version' => '1.0',
            'agentId' => '9527',
            'agentOrderId' => '201707280000000104',
            'jnetOrderId' => '123456789',
            'payAmt' => '1.00',
            'payResult' => 'SUCCESS',
            'payMessage' => '',
            'sign' => 'yes9527',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->verifyOrderPayment([]);
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
            'version' => '1.0',
            'agentId' => '9527',
            'agentOrderId' => '201707280000000104',
            'jnetOrderId' => '123456789',
            'payAmt' => '1.00',
            'payResult' => 'FAIL',
            'payMessage' => '',
            'sign' => '4f697c9a16e6c2f3c68597103767b82d',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'version' => '1.0',
            'agentId' => '9527',
            'agentOrderId' => '201707280000000104',
            'jnetOrderId' => '123456789',
            'payAmt' => '1.00',
            'payResult' => 'SUCCESS',
            'payMessage' => '',
            'sign' => 'ea976be54e2e33ae540d16ed59f72d27',
        ];

        $entry = ['id' => '201707030000000105'];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->verifyOrderPayment($entry);
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

        $sourceData = [
            'version' => '1.0',
            'agentId' => '9527',
            'agentOrderId' => '201707280000000104',
            'jnetOrderId' => '123456789',
            'payAmt' => '1.00',
            'payResult' => 'SUCCESS',
            'payMessage' => '',
            'sign' => 'ea976be54e2e33ae540d16ed59f72d27',
        ];

        $entry = [
            'id' => '201707280000000104',
            'amount' => '2.0000',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testVerifyOrderPayment()
    {
        $sourceData = [
            'version' => '1.0',
            'agentId' => '9527',
            'agentOrderId' => '201707280000000104',
            'jnetOrderId' => '123456789',
            'payAmt' => '1.00',
            'payResult' => 'SUCCESS',
            'payMessage' => '',
            'sign' => 'ea976be54e2e33ae540d16ed59f72d27',
        ];

        $entry = [
            'id' => '201707280000000104',
            'amount' => '1.00',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $juXinPay->getMsg());
    }

    /**
     * 測試訂單查詢時缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            '180142'
        );

        $juXinPay = new JuXinPay();
        $juXinPay->paymentTracking();
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

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->paymentTracking();
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

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數缺少sign
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payMessage":"网银支付成功","payResult":"SUCCESS"' .
            ',"version":"1.0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setContainer($this->container);
        $juXinPay->setClient($this->client);
        $juXinPay->setResponse($response);
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢簽名錯誤
     */
    public function testTrackingReturnWithErrorSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payMessage":"网银支付成功","payResult":"SUCCESS"' .
            ',"sign":"fail","version":"1.0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setContainer($this->container);
        $juXinPay->setClient($this->client);
        $juXinPay->setResponse($response);
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單支付中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"TREATMENT"' .
            ',"sign":"2065d28393df081a7e35a8adbaaf1029","version":"1.0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setContainer($this->container);
        $juXinPay->setClient($this->client);
        $juXinPay->setResponse($response);
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTracking();
    }

    /**
     * 測試訂單結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"FAIL"' .
            ',"sign":"d6250355bd0037fb6a8edb73bec06e28","version":"1.0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setContainer($this->container);
        $juXinPay->setClient($this->client);
        $juXinPay->setResponse($response);
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單號錯誤
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"SUCCESS"' .
            ',"sign":"a43865e4cb5ebfac845d849133026f37","version":"1.0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000105',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setContainer($this->container);
        $juXinPay->setClient($this->client);
        $juXinPay->setResponse($response);
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"SUCCESS"' .
            ',"sign":"a43865e4cb5ebfac845d849133026f37","version":"1.0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'amount' => '2.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setContainer($this->container);
        $juXinPay->setClient($this->client);
        $juXinPay->setResponse($response);
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"SUCCESS"' .
            ',"sign":"a43865e4cb5ebfac845d849133026f37","version":"1.0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setContainer($this->container);
        $juXinPay->setClient($this->client);
        $juXinPay->setResponse($response);
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時未帶入密鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $juXinPay = new JuXinPay();
        $juXinPay->getPaymentTrackingData();
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

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒帶入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $trackingData = $juXinPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/gateway/query', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals($sourceData['version'], $trackingData['form']['version']);
        $this->assertEquals($sourceData['number'], $trackingData['form']['agentId']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['agentOrderId']);
        $this->assertEquals('b5536f1f1d3cd10e9956cc3f3bb850a5', $trackingData['form']['sign']);
    }

    /**
     *  測試驗證訂單查詢是否成功時缺少密鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $juXinPay = new JuXinPay();
        $juXinPay->paymentTrackingVerify();
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

        $result = '';

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少參數sign
     */
    public function testPaymentTrackingWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payMessage":"网银支付成功","payResult":"SUCCESS"' .
            ',"version":"1.0"}';

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payMessage":"网银支付成功","payResult":"SUCCESS"' .
            ',"sign":"fail","version":"1.0"}';

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單支付中
     */
    public function testPaymentTrackingVerifyWithOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"TREATMENT"' .
            ',"sign":"2065d28393df081a7e35a8adbaaf1029","version":"1.0"}';

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"FAIL"' .
            ',"sign":"d6250355bd0037fb6a8edb73bec06e28","version":"1.0"}';

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"SUCCESS"' .
            ',"sign":"a43865e4cb5ebfac845d849133026f37","version":"1.0"}';

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201707030000000105',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單金額錯誤
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"SUCCESS"' .
            ',"sign":"a43865e4cb5ebfac845d849133026f37","version":"1.0"}';

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'amount' => '2.00',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $result = '{"agentId":"9527","agentOrderId":"201707030000000104","jnetOrderId":"123456789"' .
            ',"payAmt":"1.00","payResult":"SUCCESS"' .
            ',"sign":"a43865e4cb5ebfac845d849133026f37","version":"1.0"}';

        $sourceData = [
            'version' => '1.0',
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $juXinPay = new JuXinPay();
        $juXinPay->setPrivateKey('test');
        $juXinPay->setOptions($sourceData);
        $juXinPay->paymentTrackingVerify();
    }
}

