<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XunBao;
use Buzz\Message\Response;

class XunBaoTest extends DurianTestCase
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
     * 測試支付時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xunBao = new XunBao();
        $xunBao->getVerifyData();
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

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->getVerifyData();
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
            'number' => '6550',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://apika.10001000.com/chargebank.aspx',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://apika.10001000.com/chargebank.aspx',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $encodeData = $xunBao->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('e3def41357ca708ecdbc11032c6b75b4', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $data = [
            'parter' => $encodeData['parter'],
            'type' => $encodeData['type'],
            'value' => $encodeData['value'],
            'orderid' => $encodeData['orderid'],
            'callbackurl' => $encodeData['callbackurl'],
            'hrefbackurl' => $encodeData['hrefbackurl'],
            'payerIp' => $encodeData['payerIp'],
            'attach' => $encodeData['attach'],
            'sign' => $encodeData['sign'],
            'agent' => '',
        ];

        $this->assertEquals($sourceData['postUrl'] . '?' . http_build_query($data), $encodeData['act_url']);
    }

    /**
     * 測試返回時缺少privateKey
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xunBao = new XunBao();
        $xunBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201609220000004434',
            'opstate' => '0',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2016-06-22 15:58:08',
            'sysorderid' => 'B5184851026645924661',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => 'cfe863c91cc417c8a08a43f08b7dfaf8',
            'systime' => '2016-06-22 15:58:08',
            'sysorderid' => 'B5184851026645924661',
            'attach' => '',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'sign' => '3625e7e1b69e8bad5ddba560b6b4b28a',
            'systime' => '2016-06-22 15:58:08',
            'sysorderid' => 'B5184851026645924661',
            'attach' => '',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'sign' => '1ba94475e1b9d54a4aa32ce6d0b5c0ab',
            'systime' => '2016-06-22 15:58:08',
            'sysorderid' => 'B5184851026645924661',
            'attach' => '',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '-5',
            'ovalue' => '0.01',
            'sign' => '92020e103487788fea94ced9c4262cf8',
            'systime' => '2016-06-22 15:58:08',
            'sysorderid' => 'B5184851026645924661',
            'attach' => '',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付單號不正確
     */
    public function testReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '0144ab681a1c29fa54fb0b8174cc7b7f',
            'systime' => '2016-06-22 15:58:08',
            'sysorderid' => 'B5184851026645924661',
            'attach' => '',
        ];

        $entry = ['id' => '201606220000002806'];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付金額不正確
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '0144ab681a1c29fa54fb0b8174cc7b7f',
            'systime' => '2016-06-22 15:58:08',
            'sysorderid' => 'B5184851026645924661',
            'attach' => '',
        ];

        $entry = [
            'id' => '201609220000004434',
            'amount' => '1.0000',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '0144ab681a1c29fa54fb0b8174cc7b7f',
            'systime' => '2016-06-22 15:58:08',
            'sysorderid' => 'B5184851026645924661',
            'attach' => '',
        ];

        $entry = [
            'id' => '201609220000004434',
            'amount' => '0.0100',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $xunBao->getMsg());
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

        $xunBao = new XunBao();
        $xunBao->paymentTracking();
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

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->paymentTracking();
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
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
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

        $result = 'orderid=201609220000004434&opstate=0&ovalue=1.00';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.apika.10001000.com',
        ];

        $xunBao = new XunBao();
        $xunBao->setContainer($this->container);
        $xunBao->setClient($this->client);
        $xunBao->setResponse($response);
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
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

        $result = 'orderid=201609220000004434&opstate=0&ovalue=1.00&sign=d8e01c44502b6aa2312b5b149a412345';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.apika.10001000.com',
        ];

        $xunBao = new XunBao();
        $xunBao->setContainer($this->container);
        $xunBao->setClient($this->client);
        $xunBao->setResponse($response);
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
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

        $result = 'orderid=201609220000004434&opstate=3&ovalue=1.00&sign=1187241dfef1a3a992df6dccf6a4b738';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.apika.10001000.com',
        ];

        $xunBao = new XunBao();
        $xunBao->setContainer($this->container);
        $xunBao->setClient($this->client);
        $xunBao->setResponse($response);
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
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

        $result = 'orderid=201609220000004434&opstate=2&ovalue=1.00&sign=76f1edb7ebb9e3899622bfb0dd10eb55';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.apika.10001000.com',
        ];

        $xunBao = new XunBao();
        $xunBao->setContainer($this->container);
        $xunBao->setClient($this->client);
        $xunBao->setResponse($response);
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
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

        $result = 'orderid=201609220000004434&opstate=1&ovalue=&sign=885460daf809e8f5c73b04d470c0e229';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.apika.10001000.com',
        ];

        $xunBao = new XunBao();
        $xunBao->setContainer($this->container);
        $xunBao->setClient($this->client);
        $xunBao->setResponse($response);
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
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

        $result = 'orderid=201609220000004434&opstate=&ovalue=&sign=753479179603b9657362b06e90e75475';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.apika.10001000.com',
        ];

        $xunBao = new XunBao();
        $xunBao->setContainer($this->container);
        $xunBao->setClient($this->client);
        $xunBao->setResponse($response);
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
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

        $result = 'orderid=201609220000004434&opstate=0&ovalue=1.00&sign=aa8d7a70d5b89b26c5d9d9ca78bf7979';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.apika.10001000.com',
            'amount' => '1000.00',
        ];

        $xunBao = new XunBao();
        $xunBao->setContainer($this->container);
        $xunBao->setClient($this->client);
        $xunBao->setResponse($response);
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = 'orderid=201609220000004434&opstate=0&ovalue=1.00&sign=aa8d7a70d5b89b26c5d9d9ca78bf7979';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=gb2312');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'orderCreateDate' => '2016-06-22 15:58:08',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.apika.10001000.com',
            'amount' => '1.00',
        ];

        $xunBao = new XunBao();
        $xunBao->setContainer($this->container);
        $xunBao->setClient($this->client);
        $xunBao->setResponse($response);
        $xunBao->setPrivateKey('1234');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTracking();
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

        $xunBao = new XunBao();
        $xunBao->getPaymentTrackingData();
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

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入number
     */
    public function testGetPaymentTrackingDataWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['orderId' => '201508060000000201'];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($options);
        $xunBao->getPaymentTrackingData();
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

        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($options);
        $xunBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($options);
        $trackingData = $xunBao->getPaymentTrackingData();

        $path = '/Search.aspx?orderid=201612270000000453&parter=DM20161216000010&sign=08a2132bf92ebcf0b821581f08b45d47';
        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('payment.http.api.pk767.com', $trackingData['headers']['Host']);
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

        $xunBao = new XunBao();
        $xunBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = 'orderid=201609220000004434&ovalue=1.00&sign=aa8d7a70d5b89b26c5d9d9ca78bf7979';

        $sourceData = ['content' => $content];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
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

        $content = 'orderid=201609220000004434&opstate=0&ovalue=1.00';

        $sourceData = ['content' => $content];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
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

        $content = 'orderid=201609220000004434&opstate=0&ovalue=1.00&sign=1234';

        $sourceData = ['content' => $content];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢請求參數無效
     */
    public function testPaymentTrackingVerifyWithInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $content = 'orderid=201609220000004434&opstate=3&ovalue=1.00&sign=e41b8c2b5cbc0bd87aa0fa913a1711cd';

        $sourceData = [
            'content' => $content,
            'amount' => 0.01,
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名錯誤
     */
    public function testPaymentTrackingVerifyWithMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $content = 'orderid=201609220000004434&opstate=2&ovalue=1.00&sign=ecc2ab00620c01dcad121de36f196218';

        $sourceData = [
            'content' => $content,
            'amount' => 0.01,
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢商戶訂單號無效
     */
    public function testPaymentTrackingVerifyWithOrderNotExists()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );
        $content = 'orderid=201609220000004434&opstate=1&ovalue=0.01&sign=06e2654e454ccc0dd202b3e7e86a44af';

        $sourceData = [
            'content' => $content,
            'amount' => 0.01,
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢失敗
     */
    public function testPaymentTrackingVerifyButPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = 'orderid=201609220000004434&opstate=5&ovalue=0.01&sign=347d2f70a791dd3476a4449e480c6b4c';

        $sourceData = [
            'content' => $content,
            'amount' => 0.01,
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
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

        $content = 'orderid=201609220000004434&opstate=0&ovalue=0.01&sign=cc1c21995090080939dda67a1f81f7a4';

        $sourceData = [
            'content' => $content,
            'amount' => '400.00',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = 'orderid=201609220000004434&opstate=0&ovalue=0.01&sign=cc1c21995090080939dda67a1f81f7a4';

        $sourceData = [
            'content' => $content,
            'amount' => '0.01',
        ];

        $xunBao = new XunBao();
        $xunBao->setPrivateKey('test');
        $xunBao->setOptions($sourceData);
        $xunBao->paymentTrackingVerify();
    }
}
