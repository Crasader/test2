<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YeePayWeiXin;
use Buzz\Message\Response;

class YeePayWeiXinTest extends DurianTestCase
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

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->getVerifyData();
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

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '49',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201611180000000310',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $encodeData = $yeePayWeiXin->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchantNo']);
        $this->assertEquals('WX', $encodeData['payType']);
        $this->assertSame('0.01', $encodeData['requestAmount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['merchantOrderno']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['noticeSysaddress']);
        $this->assertEquals('4c975b7ad582cf50b5a02f284a07bac8', $encodeData['hmac']);
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

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->verifyOrderPayment([]);
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

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳hmac(加密簽名)
     */
    public function testReturnVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'merchantOrderno' => '201611150000000242',
            'payType' => 'WX',
            'amount' => '0.01',
            'result' => 'SUCCESS',
            'merchantNo' => '70001000007',
            'memberGoods' => 'php1test',
            'reCode' => '1',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->verifyOrderPayment([]);
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
            'merchantOrderno' => '201611150000000242',
            'payType' => 'WX',
            'amount' => '0.01',
            'result' => 'SUCCESS',
            'merchantNo' => '70001000007',
            'memberGoods' => 'php1test',
            'reCode' => '1',
            'hmac' => '4c975b7ad582cf50b5a02f284a07bac8',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->verifyOrderPayment([]);
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
            'merchantOrderno' => '201611150000000242',
            'payType' => 'WX',
            'amount' => '0.01',
            'result' => 'FAIL',
            'merchantNo' => '70001000007',
            'memberGoods' => 'php1test',
            'reCode' => '1',
            'hmac' => '4b6ab1bf7c5f519910feed183da8afc7',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->verifyOrderPayment([]);
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
            'merchantOrderno' => '201611150000000242',
            'payType' => 'WX',
            'amount' => '0.01',
            'result' => 'SUCCESS',
            'merchantNo' => '70001000007',
            'memberGoods' => 'php1test',
            'reCode' => '1',
            'hmac' => '5aa2b5d809a248e857d9efc2feba9276',
        ];

        $entry = ['id' => '201611150000000241'];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->verifyOrderPayment($entry);
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
            'merchantOrderno' => '201611150000000242',
            'payType' => 'WX',
            'amount' => '0.01',
            'result' => 'SUCCESS',
            'merchantNo' => '70001000007',
            'memberGoods' => 'php1test',
            'reCode' => '1',
            'hmac' => '5aa2b5d809a248e857d9efc2feba9276',
        ];

        $entry = [
            'id' => '201611150000000242',
            'amount' => '0.1',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'merchantOrderno' => '201611150000000242',
            'payType' => 'WX',
            'amount' => '0.01',
            'result' => 'SUCCESS',
            'merchantNo' => '70001000007',
            'memberGoods' => 'php1test',
            'reCode' => '1',
            'hmac' => '5aa2b5d809a248e857d9efc2feba9276',
        ];

        $entry = [
            'id' => '201611150000000242',
            'amount' => '0.01',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yeePayWeiXin->getMsg());
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

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->paymentTracking();
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

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->paymentTracking();
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
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果code為空
     */
    public function testTrackingReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"data":null}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單處理中
     */
    public function testTrackingReturnCodeOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '{"data":null,"code":"120001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單不存在
     */
    public function testTrackingReturnCodeOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = '{"data":null,"code":"120000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為查詢參數為空或格式錯誤
     */
    public function testTrackingReturnCodeSubmitParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $result = '{"data":null,"code":"100100"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為簽名驗證失敗
     */
    public function testTrackingReturnCodeMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $result = '{"data":null,"code":"100300"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為商戶不存在
     */
    public function testTrackingReturnCodeMerchantNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant is not exist',
            180086
        );

        $result = '{"data":null,"code":"100401"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果code異常
     */
    public function testTrackingReturnCodeFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '{"data":null,"code":"120120"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果data為空
     */
    public function testTrackingReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"code":"000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果hmac為空
     */
    public function testTrackingReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"data":{"result":"SUCCESS", "memberGoods":"php1test", "payType":"WX", ' .
            '"merchantOrderno":"201611150000000242", "merchantNo":"70001000007", "reCode":"1"}, "code":"000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
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

        $result = '{"data":{"result":"SUCCESS", "memberGoods":"php1test", "hmac":"65042934adb0f2a157c6de8bdc4825dd", ' .
            '"payType":"WX", "merchantOrderno":"201611150000000242", "merchantNo":"70001000007", "reCode":"1"}, ' .
            '"code":"000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '{"data":{"result":"DEAL", "memberGoods":"php1test", "hmac":"c0c421b4db01cb344f5cfd62c06987cd", ' .
            '"payType":"WX", "merchantOrderno":"201611150000000242", "merchantNo":"70001000007", "reCode":"1"}, ' .
            '"code":"000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '{"data":{"result":"FAIL", "memberGoods":"php1test", "hmac":"04480042338f3631e326b1369faeebe5", ' .
            '"payType":"WX", "merchantOrderno":"201611150000000242", "merchantNo":"70001000007", "reCode":"1"}, ' .
            '"code":"000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = '{"data":{"result":"SUCCESS", "memberGoods":"php1test", "hmac":"164ad1ac3b6aef5c3b0014ef95e5d23c", ' .
            '"payType":"WX", "merchantOrderno":"201611150000000242", "merchantNo":"70001000007", "reCode":"1"}, ' .
            '"code":"000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '70001000007',
            'orderId' => '201611150000000242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $yeePayWeiXin = new YeePayWeiXin();
        $yeePayWeiXin->setContainer($this->container);
        $yeePayWeiXin->setClient($this->client);
        $yeePayWeiXin->setResponse($response);
        $yeePayWeiXin->setPrivateKey('test');
        $yeePayWeiXin->setOptions($sourceData);
        $yeePayWeiXin->paymentTracking();
    }
}
