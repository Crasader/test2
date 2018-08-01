<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiHsinPay;
use Buzz\Message\Response;

class HuiHsinPayTest extends DurianTestCase
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

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->getVerifyData();
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

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getVerifyData();
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
            'number' => 'M10000757',
            'paymentVendorId' => '20',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'orderId' => '201703090000001810',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'orderId' => '201703090000001810',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $encodeData = $huiHsinPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merId']);
        $this->assertEquals('1000021', $encodeData['tranChannel']);
        $this->assertEquals(200, $encodeData['amt']);
        $this->assertEquals($sourceData['orderId'], $encodeData['merchOrderId']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['retUrl']);
        $this->assertEquals('paygate.directgatewaypay', $encodeData['svcName']);
        $this->assertEquals('CNY', $encodeData['ccy']);
        $this->assertEquals('20170308 15:45:55', $encodeData['tranTime']);
        $this->assertEquals($sourceData['username'], $encodeData['pName']);
        $this->assertEquals('DFED06D2F23EF9B9339CFF21151B298F', $encodeData['md5value']);
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '192.168.121.1',
            'orderId' => '201703090000001810',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少retCode
     */
    public function testQrcodePayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'md5value' => '1EC6BE9EF50B1DDED88FA620B4099BAD',
            'merchOrderId' => '201703090000001812',
            'retMsg' => '',
            'orderTime' => '20170309 11:39:18',
            'prepayId' => 'weixin://wxpay/bizpayurl?pr=DL7IUhK',
            'orderId' => 'I201703090023536405',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '192.168.121.1',
            'orderId' => '201703090000001812',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQrcodePayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '微信支付必须上送商品描述，请检查！',
            180130
        );

        $result = [
            'retCode' => '111111',
            'retMsg' => '微信支付必须上送商品描述，请检查！',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '192.168.121.1',
            'orderId' => '201703090000001812',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少md5value
     */
    public function testQrcodePayReturnWithoutMd5value()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'retCode' => '000000',
            'merchOrderId' => '201703090000001812',
            'retMsg' => '',
            'orderTime' => '20170309 11:39:18',
            'prepayId' => 'weixin://wxpay/bizpayurl?pr=DL7IUhK',
            'orderId' => 'I201703090023536405',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '192.168.121.1',
            'orderId' => '201703090000001812',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回簽名驗證錯誤
     */
    public function testQrcodePayReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'retCode' => '000000',
            'md5value' => '1EC6BE9EF50B1DDED88FA620B4099BAD',
            'merchOrderId' => '201703090000001812',
            'retMsg' => '',
            'orderTime' => '20170309 11:39:20',
            'prepayId' => 'weixin://wxpay/bizpayurl?pr=DL7IUhK',
            'orderId' => 'I201703090023536405',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '192.168.121.1',
            'orderId' => '201703090000001812',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少prepayId
     */
    public function testQrcodePayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'retCode' => '000000',
            'md5value' => '49C31B3091582F61CD375355CF41AD50',
            'merchOrderId' => '201703090000001812',
            'retMsg' => '',
            'orderTime' => '20170309 11:39:18',
            'orderId' => 'I201703090023536405',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '192.168.121.1',
            'orderId' => '201703090000001812',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付(微信)
     */
    public function testWeixinQrcodePay()
    {
        $result = [
            'retCode' => '000000',
            'md5value' => 'CFE73E6F4FB55D41CD46AD92484EE62E',
            'merchOrderId' => '201703090000001812',
            'retMsg' => '',
            'orderTime' => '20170309 11:39:18',
            'prepayId' => 'weixin://wxpay/bizpayurl?pr=DL7IUhK',
            'orderId' => 'I201703090023536405',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '192.168.121.1',
            'orderId' => '201703090000001812',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setOptions($sourceData);
        $data = $huiHsinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertSame('weixin://wxpay/bizpayurl?pr=DL7IUhK', $huiHsinPay->getQrcode());
    }

    /**
     * 測試二維支付(支付寶)
     */
    public function testAlipayQrcodePay()
    {
        $result = [
            'retCode' => '000000',
            'md5value' => 'CD6DE2BBC87FD3D0094FC25702E2F118',
            'merchOrderId' => '201703090000001812',
            'retMsg' => '',
            'orderTime' => '20170309 11:39:18',
            'prepayId' => 'https://qr.alipay.com/bax04522pixibibnqyz06093',
            'orderId' => 'I201703090023536405',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'paymentVendorId' => '1092',
            'amount' => '2',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '192.168.121.1',
            'orderId' => '201703090000001812',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setOptions($sourceData);
        $data = $huiHsinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertSame('https://qr.alipay.com/bax04522pixibibnqyz06093', $huiHsinPay->getQrcode());
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

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->getVerifyData();
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

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳md5value
     */
    public function testReturnWithoutMd5value()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'tranTime' => '20170307 09:35:46',
            'amt' => '20',
            'merData' => '',
            'status' => '0',
            'merchOrderId' => '201703090000001810',
            'orderStatusMsg' => '支付成功',
            'orderId' => 'I201703070023294830',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時md5value簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'tranTime' => '20170307 09:35:46',
            'amt' => '20',
            'md5value' => '153D53988F34ED10F77D4BD2DCCB41A6',
            'merData' => '',
            'status' => '0',
            'merchOrderId' => '201703090000001810',
            'orderStatusMsg' => '支付成功',
            'orderId' => 'I201703070023294831',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->verifyOrderPayment([]);
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
            'tranTime' => '20170307 09:35:46',
            'amt' => '20',
            'md5value' => 'E791D915A3834255FCDE8DEB4548E300',
            'merData' => '',
            'status' => '1',
            'merchOrderId' => '201703090000001810',
            'orderStatusMsg' => '支付失败',
            'orderId' => 'I201703070023294831',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->verifyOrderPayment([]);
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
            'tranTime' => '20170307 09:35:46',
            'amt' => '20',
            'md5value' => '4388379DDA9FD2F2EB1B65D184FB2811',
            'merData' => '',
            'status' => '0',
            'merchOrderId' => '201703090000001810',
            'orderStatusMsg' => '支付失败',
            'orderId' => 'I201703070023294831',
        ];

        $entry = ['id' => '2016121517150712345'];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->verifyOrderPayment($entry);
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
            'tranTime' => '20170307 09:35:46',
            'amt' => '20',
            'md5value' => '4388379DDA9FD2F2EB1B65D184FB2811',
            'merData' => '',
            'status' => '0',
            'merchOrderId' => '201703090000001810',
            'orderStatusMsg' => '支付失败',
            'orderId' => 'I201703070023294831',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '0.01',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'tranTime' => '20170307 09:35:46',
            'amt' => '2000',
            'md5value' => 'A31C134FA2593A792E830B8944015280',
            'merData' => '',
            'status' => '0',
            'merchOrderId' => '201703090000001810',
            'orderStatusMsg' => '支付失败',
            'orderId' => 'I201703070023294831',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '20',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $huiHsinPay->getMsg());
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

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->paymentTracking();
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

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->paymentTracking();
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
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001812',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為請求處理失敗
     */
    public function testTrackingReturnRequestError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '请求处理失败',
            180123
        );

        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'amt' => '10',
            'retCode' => '111111',
            'md5value' => 'F21C0B48AEEFA0DAD4CAF2F2E3F14C16',
            'status' => '9',
            'merchOrderId' => '201703090000001813',
            'retMsg' => '请求处理失败',
            'tranName' => '',
            'orderStatusMsg' => '处理中',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為請求處理失敗沒有錯誤訊息
     */
    public function testTrackingReturnRequestErrorWithoutRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'amt' => '10',
            'retCode' => '111111',
            'md5value' => 'F21C0B48AEEFA0DAD4CAF2F2E3F14C16',
            'status' => '9',
            'merchOrderId' => '201703090000001813',
            'tranName' => '',
            'orderStatusMsg' => '处理中',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果但缺少md5value
     */
    public function testTrackingReturnWithoutMd5value()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'amt' => '10',
            'retCode' => '000000',
            'status' => '9',
            'retMsg' => '订单查询成功',
            'merchOrderId' => '201703090000001813',
            'tranName' => '',
            'orderStatusMsg' => '处理中',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
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

        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'md5value' => 'F21C0B48AEEFA0DAD4CAF2F2E3F14C16',
            'amt' => '10',
            'retCode' => '000000',
            'status' => '9',
            'retMsg' => '订单查询成功',
            'merchOrderId' => '201703090000001813',
            'tranName' => '',
            'orderStatusMsg' => '处理中',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'md5value' => '8C604E75228E19BFC0C6F1EEA7AEB747',
            'amt' => '10',
            'retCode' => '000000',
            'status' => '9',
            'retMsg' => '订单查询成功',
            'merchOrderId' => '201703090000001813',
            'tranName' => '',
            'orderStatusMsg' => '处理中',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'md5value' => 'EABFD918AD4B81AA6346DF956D861627',
            'amt' => '10',
            'retCode' => '000000',
            'status' => '1',
            'retMsg' => '订单查询成功',
            'merchOrderId' => '201703090000001813',
            'tranName' => '',
            'orderStatusMsg' => '支付失敗',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'md5value' => 'E8954060042199F3BDA74FA6DCEEC690',
            'amt' => '10',
            'retCode' => '000000',
            'status' => '0',
            'retMsg' => '订单查询成功',
            'merchOrderId' => '201703090000001814',
            'tranName' => '',
            'orderStatusMsg' => '支付成功',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'md5value' => '634AD4E1BBC13F1D3D420388A1B2F619',
            'amt' => '10',
            'retCode' => '000000',
            'status' => '0',
            'retMsg' => '订单查询成功',
            'merchOrderId' => '201703090000001813',
            'tranName' => '',
            'orderStatusMsg' => '支付成功',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'amount' => '10',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = [
            'tranTime' => '20170309 11:50:16',
            'ccy' => 'CNY',
            'md5value' => '0F5DD95BBBAE380D73C70E6AD2BC41CF',
            'amt' => '1000',
            'retCode' => '000000',
            'status' => '0',
            'retMsg' => '订单查询成功',
            'merchOrderId' => '201703090000001813',
            'tranName' => '',
            'orderStatusMsg' => '支付成功',
            'orderId' => 'I201703090023537093',
        ];

        $response = new Response();
        $response->setContent(urldecode(http_build_query($result)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'amount' => '10',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setContainer($this->container);
        $huiHsinPay->setClient($this->client);
        $huiHsinPay->setResponse($response);
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->paymentTracking();
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

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->getPaymentTrackingData();
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

        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $huiHsinPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => 'M10000757',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'orderId' => '201703090000001813',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $huiHsinPay = new HuiHsinPay();
        $huiHsinPay->setPrivateKey('test');
        $huiHsinPay->setOptions($sourceData);
        $trackingData = $huiHsinPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/fm/', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals('paygate.resultqry', $trackingData['form']['svcName']);
        $this->assertEquals($sourceData['number'], $trackingData['form']['merId']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['merchOrderId']);
        $this->assertEquals('20170308 15:45:55', $trackingData['form']['tranTime']);
        $this->assertEquals('283B494DE4779FAA835346AA91A11B24', $trackingData['form']['md5value']);
    }
}
