<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\LuoBoFu;
use Buzz\Message\Response;

class LuoBoFuTest extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $luoBoFu = new LuoBoFu();
        $luoBoFu->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->getVerifyData();
    }

    /**
     * 測試支付加密時沒有帶入postUrl的情況
     */
    public function testPayEncodeWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '2353',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201705090000006276',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '2353',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201705090000006276',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://gt.luobofu.net/chargebank.aspx',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '2353',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201705090000006276',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://gt.luobofu.net/chargebank.aspx',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $encodeData = $luoBoFu->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('101', $encodeData['type']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('b4d0fbca95f4b1feaf37697254c4a13d', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $data = [];
        $data['parter'] = $encodeData['parter'];
        $data['type'] = $encodeData['type'];
        $data['value'] = $encodeData['value'];
        $data['orderid'] = $encodeData['orderid'];
        $data['callbackurl'] = $encodeData['callbackurl'];
        $data['hrefbackurl'] = $encodeData['hrefbackurl'];
        $data['payerIp'] = $encodeData['payerIp'];
        $data['attach'] = $encodeData['attach'];
        $data['sign'] = $encodeData['sign'];

        $this->assertEquals($sourceData['postUrl'] . '?' . http_build_query($data), $encodeData['act_url']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $luoBoFu = new LuoBoFu();
        $luoBoFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201705090000006276',
            'opstate' => '0',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201705090000006276',
            'opstate' => '0',
            'ovalue' => '0.01',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment([]);
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
            'orderid' => '201705090000006276',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => 'cfe863c91cc417c8a08a43f08b7dfaf8',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $sourceData = [
            'orderid' => '201705090000006276',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'sign' => '518c8a61d1bf706ebc7f63d71ec44890',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付平台驗證簽名錯誤
     */
    public function testReturnPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $sourceData = [
            'orderid' => '201705090000006276',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'sign' => 'f36d068899b5cf4f932c03e2ce81cfec',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment([]);
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
            'orderid' => '201705090000006276',
            'opstate' => '-5',
            'ovalue' => '0.01',
            'sign' => '8ebb503fb3795dd4ab3e1f7b9b0c9605',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201705090000006276',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '1c829eb35a1ce75f102249802d0aacad',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = ['id' => '201606220000002806'];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201705090000006276',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '1c829eb35a1ce75f102249802d0aacad',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = [
            'id' => '201705090000006276',
            'amount' => '1.0000',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201705090000006276',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '1c829eb35a1ce75f102249802d0aacad',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = [
            'id' => '201705090000006276',
            'amount' => '0.0100',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $luoBoFu->getMsg());
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

        $luoBoFu = new LuoBoFu();
        $luoBoFu->paymentTracking();
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

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->paymentTracking();
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
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果沒有sign的情況
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'orderid=201705090000006276&opstate=0&ovalue=1.00';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
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

        $result = 'orderid=201705090000006276&opstate=0&ovalue=1.00&sign=9baa1e07348676e4587af24645e34';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果請求參數無效
     */
    public function testTrackingReturnWithInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $result = 'orderid=201705090000006276&opstate=3&ovalue=1.00&sign=9baa1e07348676e4587af24645e347ba';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名錯誤
     */
    public function testTrackingReturnWithMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $result = 'orderid=201705090000006276&opstate=2&ovalue=1.00&sign=069a8bfe1dc00607d44b33d5bf678780';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果商戶訂單號無效
     */
    public function testTrackingReturnWithOrderNotExists()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = 'orderid=201705090000006276&opstate=1&ovalue=&sign=8a5620c2e83847e6e717f0fe47d2e781';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
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

        $result = 'orderid=201705090000006276&opstate=&ovalue=&sign=79503ec8b3a775a609f0339a6e4895c1';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果帶入訂單號不正確
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = 'orderid=201705090000006276&opstate=0&ovalue=1.00&sign=c1c9ab3f5d0f369562f2f966680874ce';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006275',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果帶入金額不正確
     */
    public function testTrackingReturnWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = 'orderid=201705090000006276&opstate=0&ovalue=1.00&sign=c1c9ab3f5d0f369562f2f966680874ce';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'amount' => '1000.00',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = 'orderid=201705090000006276&opstate=0&ovalue=1.00&sign=c1c9ab3f5d0f369562f2f966680874ce';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'amount' => '1.00',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setContainer($this->container);
        $luoBoFu->setClient($this->client);
        $luoBoFu->setResponse($response);
        $luoBoFu->setPrivateKey('1234');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTracking();
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

        $luoBoFu = new LuoBoFu();
        $luoBoFu->getPaymentTrackingData();
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

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->getPaymentTrackingData();
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
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $trackingData = $luoBoFu->getPaymentTrackingData();

        $path = '/search.aspx?orderid=201705090000006276&parter=2353&sign=c955daba16c9dc40a90f22e8f4447227';

        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
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

        $luoBoFu = new LuoBoFu();
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=99&sign=d8cb197eecc0c967421fa02a890f16a5';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=99&ovalue=0';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=99&ovalue=0&sign=d8cb197eecc0c967421fa02a890f16a5';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=3&ovalue=0&sign=63699cce73b9c43b13b5a807988438ef';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=2&ovalue=0&sign=7c7bf4b407b9d78eb8e3b79b94d40eaf';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=1&ovalue=1&sign=3e9e0a8f609c095ce00886d406d8e061';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=99&ovalue=0&sign=89b72c8e804abeeaaf104adbf49ca807';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=0&ovalue=0&sign=0c7a0de327cd49d08834026644bc0675';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201702090000001338',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
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

        $result = 'orderid=201705090000006276&opstate=0&ovalue=0.01&sign=89efdf9992ad42a62de4d697cdfa789e';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $result = 'orderid=201705090000006276&opstate=0&ovalue=0.02&sign=c272ef5a2d2ede8b8162dc586427cef8';

        $sourceData = [
            'number' => '2353',
            'orderId' => '201705090000006276',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gt.luobofu.net',
            'content' => $result,
        ];

        $luoBoFu = new LuoBoFu();
        $luoBoFu->setPrivateKey('test');
        $luoBoFu->setOptions($sourceData);
        $luoBoFu->paymentTrackingVerify();
    }
}
