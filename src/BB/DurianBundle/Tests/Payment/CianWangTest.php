<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CianWang;
use Buzz\Message\Response;

class CianWangTest extends DurianTestCase
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

        $cianWang = new CianWang();
        $cianWang->getVerifyData();
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

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->getVerifyData();
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
            'number' => '6550',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->getVerifyData();
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
            'number' => '6550',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://apika.10001000.com/chargebank.aspx',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://apika.10001000.com/chargebank.aspx',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $encodeData = $cianWang->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('e3def41357ca708ecdbc11032c6b75b4', $encodeData['sign']);

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

        $cianWang = new CianWang();
        $cianWang->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '0',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => 'cfe863c91cc417c8a08a43f08b7dfaf8',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'sign' => '11766b323b86588b5f2e923dce79e9dc',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'sign' => '78a33dd3f30c7b93dd408a6c1232b492',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment([]);
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
            'sign' => 'cda7606c9e5a1421ef7f89e701498d31',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment([]);
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
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '49ec9304b9295ede2b2be38f0b27a49e',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = ['id' => '201606220000002806'];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment($entry);
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
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '49ec9304b9295ede2b2be38f0b27a49e',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = [
            'id' => '201609220000004434',
            'amount' => '1.0000',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201609220000004434',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '49ec9304b9295ede2b2be38f0b27a49e',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = [
            'id' => '201609220000004434',
            'amount' => '0.0100',
        ];

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $cianWang->getMsg());
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

        $cianWang = new CianWang();
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setContainer($this->container);
        $cianWang->setClient($this->client);
        $cianWang->setResponse($response);
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setContainer($this->container);
        $cianWang->setClient($this->client);
        $cianWang->setResponse($response);
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setContainer($this->container);
        $cianWang->setClient($this->client);
        $cianWang->setResponse($response);
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setContainer($this->container);
        $cianWang->setClient($this->client);
        $cianWang->setResponse($response);
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
    }

    /**
     * 測試訂單查詢結果商戶訂單號無效
     */
    public function testTrackingReturnWithOrderNotExists()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
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

        $cianWang = new CianWang();
        $cianWang->setContainer($this->container);
        $cianWang->setClient($this->client);
        $cianWang->setResponse($response);
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setContainer($this->container);
        $cianWang->setClient($this->client);
        $cianWang->setResponse($response);
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setContainer($this->container);
        $cianWang->setClient($this->client);
        $cianWang->setResponse($response);
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
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

        $cianWang = new CianWang();
        $cianWang->setContainer($this->container);
        $cianWang->setClient($this->client);
        $cianWang->setResponse($response);
        $cianWang->setPrivateKey('1234');
        $cianWang->setOptions($sourceData);
        $cianWang->paymentTracking();
    }
}
