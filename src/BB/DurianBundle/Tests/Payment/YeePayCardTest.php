<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YeePayCard;
use Buzz\Message\Response;

class YeePayCardTest extends DurianTestCase
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->getVerifyData();
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->getVerifyData();
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

        $sourceData = [
            'number' => '67001000024',
            'amount' => '0.01',
            'orderId' => '201701040000005707',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1102',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '67001000024',
            'amount' => '0.01',
            'orderId' => '201701040000005707',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1000',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $encodeData = $yeePayCard->getVerifyData();

        $this->assertEquals('STANDARD', $encodeData['bizType']);
        $this->assertEquals($sourceData['number'], $encodeData['merchantNo']);
        $this->assertEquals($sourceData['orderId'], $encodeData['merchantOrderNo']);
        $this->assertEquals($sourceData['amount'], $encodeData['requestAmount']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['url']);
        $this->assertEquals('MOBILE', $encodeData['cardCode']);
        $this->assertEquals('7e9011c762684025a4c6c17159e78cb1', $encodeData['hmac']);
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->verifyOrderPayment([]);
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->verifyOrderPayment([]);
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
            'bizType' => 'STANDARD',
            'result' => 'SUCCESS',
            'merchantNo' => '10013447657',
            'merchantOrderNo' => '201701040000005707',
            'successAmount' => '10.00',
            'cardCode' => 'MOBILE',
            'noticeType' => '2',
            'extInfo' => '',
            'cardNo' => '16124110588464496',
            'cardStatus' => '0',
            'cardReturnInfo' => '销卡成功',
            'cardIsbalance' => 'false',
            'cardBalance' => '90.00',
            'cardSuccessAmount' => '100.00',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->verifyOrderPayment([]);
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
            'bizType' => 'STANDARD',
            'result' => 'SUCCESS',
            'merchantNo' => '10013447657',
            'merchantOrderNo' => '201701040000005707',
            'successAmount' => '10.00',
            'cardCode' => 'MOBILE',
            'noticeType' => '2',
            'extInfo' => '',
            'cardNo' => '16124110588464496',
            'cardStatus' => '0',
            'cardReturnInfo' => '销卡成功',
            'cardIsbalance' => 'false',
            'cardBalance' => '90.00',
            'cardSuccessAmount' => '100.00',
            'hmac' => '9bed1644508c85490a84b0915ab05c32',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->verifyOrderPayment([]);
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
            'bizType' => 'STANDARD',
            'result' => 'SUCCESS',
            'merchantNo' => '10013447657',
            'merchantOrderNo' => '201701040000005707',
            'successAmount' => '10.00',
            'cardCode' => 'MOBILE',
            'noticeType' => '2',
            'extInfo' => '',
            'cardNo' => '16124110588464496',
            'cardStatus' => '3',
            'cardReturnInfo' => '销卡成功',
            'cardIsbalance' => 'false',
            'cardBalance' => '90.00',
            'cardSuccessAmount' => '100.00',
            'hmac' => 'a0a105345d2e147b60ed4f2dd7aa420a',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'bizType' => 'STANDARD',
            'result' => 'SUCCESS',
            'merchantNo' => '10013447657',
            'merchantOrderNo' => '201701040000005707',
            'successAmount' => '10.00',
            'cardCode' => 'MOBILE',
            'noticeType' => '2',
            'extInfo' => '',
            'cardNo' => '16124110588464496',
            'cardStatus' => '0',
            'cardReturnInfo' => '销卡成功',
            'cardIsbalance' => 'false',
            'cardBalance' => '90.00',
            'cardSuccessAmount' => '100.00',
            'hmac' => '9b9392afbdf51df6d60d1ad3b02917d3',
        ];

        $entry = ['id' => '201611150000000241'];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->verifyOrderPayment($entry);
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
            'bizType' => 'STANDARD',
            'result' => 'SUCCESS',
            'merchantNo' => '10013447657',
            'merchantOrderNo' => '201701040000005707',
            'successAmount' => '10.00',
            'cardCode' => 'MOBILE',
            'noticeType' => '2',
            'extInfo' => '',
            'cardNo' => '16124110588464496',
            'cardStatus' => '0',
            'cardReturnInfo' => '销卡成功',
            'cardIsbalance' => 'false',
            'cardBalance' => '90.00',
            'cardSuccessAmount' => '100.00',
            'hmac' => '9b9392afbdf51df6d60d1ad3b02917d3',
        ];

        $entry = [
            'id' => '201701040000005707',
            'amount' => '0.1',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturn()
    {
        $sourceData = [
            'bizType' => 'STANDARD',
            'result' => 'SUCCESS',
            'merchantNo' => '10013447657',
            'merchantOrderNo' => '201701040000005707',
            'successAmount' => '10.00',
            'cardCode' => 'MOBILE',
            'noticeType' => '2',
            'extInfo' => '',
            'cardNo' => '16124110588464496',
            'cardStatus' => '0',
            'cardReturnInfo' => '销卡成功',
            'cardIsbalance' => 'false',
            'cardBalance' => '90.00',
            'cardSuccessAmount' => '100.00',
            'hmac' => '9b9392afbdf51df6d60d1ad3b02917d3',
        ];

        $entry = [
            'id' => '201701040000005707',
            'amount' => '10.00',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yeePayCard->getMsg());
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->paymentTracking();
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->paymentTracking();
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果缺少code
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
    }

    /**
     * 測試訂單查詢返回缺少data
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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

        $result = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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

        $result = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "48a5d311886d8df98b6c0f2d756904f6",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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

        $result = '{"data" : {"result" : "FAIL","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡失敗","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "c11101aa0fb9e0697234294f4de66ee9",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '67001000024',
            'orderId' => '201701040000005710',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳訂單號錯誤
     */
    public function testTrackingOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "6b0e3807d1da7ba52aa9c450535ecde1",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '67001000024',
            'orderId' => '201701040000005710',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳金額錯誤
     */
    public function testTrackingAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "6b0e3807d1da7ba52aa9c450535ecde1",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'amount' => '999',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "6b0e3807d1da7ba52aa9c450535ecde1",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setContainer($this->container);
        $yeePayCard->setClient($this->client);
        $yeePayCard->setResponse($response);
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTracking();
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->getPaymentTrackingData();
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->getPaymentTrackingData();
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
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($options);
        $yeePayCard->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '67001000024',
            'orderId' => '201701040000005707',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.yeeyk.com',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($options);
        $trackingData = $yeePayCard->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/yeex-iface-app/queryOrder', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.www.yeeyk.com', $trackingData['headers']['Host']);

        $this->assertEquals('67001000024', $trackingData['form']['merchantNo']);
        $this->assertEquals('201701040000005707', $trackingData['form']['merchantOrderNo']);
        $this->assertEquals('71469b117cd02e16a67e2cadac1ec5a8', $trackingData['form']['hmac']);
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

        $yeePayCard = new YeePayCard();
        $yeePayCard->paymentTrackingVerify();
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

        $sourceData = ['content' => ''];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數code
     */
    public function testPaymentTrackingVerifyWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '{"data":null}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為訂單不存在
     */
    public function testPaymentTrackingVerifyButOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = '{"data":null,"code":"120000"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為訂單處理中
     */
    public function testPaymentTrackingVerifyButOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $content = '{"data":null,"code":"120001"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但結果為查詢參數為空或格式錯誤
     */
    public function testPaymentTrackingVerifyButSubmitParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $content = '{"data":null,"code":"100100"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為簽名驗證失敗
     */
    public function testPaymentTrackingVerifyButMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $content = '{"data":null,"code":"100300"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為商戶不存在
     */
    public function testPaymentTrackingVerifyButMerchantNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant is not exist',
            180086
        );

        $content = '{"data":null,"code":"100401"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果code異常
     */
    public function testPaymentTrackingVerifyButCodeFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $content = '{"data":null,"code":"120120"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回结果缺少data
     */
    public function testPaymentTrackingVerifyWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '{"code":"000000"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回结果缺少hmac
     */
    public function testPaymentTrackingVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "48a5d311886d8df98b6c0f2d756904f6",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '{"data" : {"result" : "FAIL","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡失敗","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "c11101aa0fb9e0697234294f4de66ee9",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $sourceData = ['content' => $content];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
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

        $content = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "6b0e3807d1da7ba52aa9c450535ecde1",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $sourceData = [
            'content' => $content,
            'orderId' => '201509140000002475',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
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

        $content = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "6b0e3807d1da7ba52aa9c450535ecde1",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $sourceData = [
            'content' => $content,
            'orderId' => '201701040000005707',
            'amount' => '0.05',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '{"data" : {"result" : "SUCCESS","cardSuccessAmount" : "100.00",' .
            '"cardReturnInfo" : "销卡成功","cardCode" : "MOBILE","cardStatus" : "0",' .
            '"cardNo" : "16124110588464496","cardIsbalance" : "false","hmac" : "6b0e3807d1da7ba52aa9c450535ecde1",' .
            '"merchantOrderNo" : "201701040000005707","bizType" : "STANDARD",' .
            '"extInfo" : "","merchantNo" : "10013447657","successAmount" : "10.00","noticeType" : "1",' .
            '"cardBalance" : "90.00"}, "code" : "000000"}';

        $sourceData = [
            'content' => $content,
            'orderId' => '201701040000005707',
            'amount' => '10',
        ];

        $yeePayCard = new YeePayCard();
        $yeePayCard->setPrivateKey('test');
        $yeePayCard->setOptions($sourceData);
        $yeePayCard->paymentTrackingVerify();
    }
}
