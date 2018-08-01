<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\YuanBao;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class YuanBaoTest extends DurianTestCase
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

        $yuanBao = new YuanBao();
        $yuanBao->getVerifyData();
    }

    /**
     * 測試支付時沒有指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\Durianbundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '99',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'amount' => '1.00',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->getVerifyData();
    }

    /**
     * 測試支付成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1',
            'number' => '9527',
            'orderId' => '201707030000000104',
            'amount' => '1.00',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $requestData = $yuanBao->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('Boh.online.interface', $requestData['method']);
        $this->assertEquals('9527', $requestData['partner']);
        $this->assertEquals('ICBC', $requestData['banktype']);
        $this->assertEquals('1.00', $requestData['paymoney']);
        $this->assertEquals('201707030000000104', $requestData['ordernumber']);
        $this->assertEquals('http://orz.com/', $requestData['callbackurl']);
        $this->assertEquals('', $requestData['hrefbackurl']);
        $this->assertEquals('', $requestData['attach']);
        $this->assertEquals('0', $requestData['isshow']);
        $this->assertEquals('80bc8a924c9d0311becaa70eafcf1200', $requestData['sign']);
    }

    /**
     * 測試二維支付時沒帶入verify_url
     */
    public function testPayWithQrcodeWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1103',
            'number' => '9527',
            'orderId' => '201709110000006960',
            'amount' => '15.60',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->getVerifyData();
    }

    /**
     * 測試二維支付但取得返回參數失敗
     */
    public function testPayWithQrcodeButGetPayParametrsFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"version":"3.0", "qrurl":""}';

        $sourceData = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1103',
            'number' => '9527',
            'orderId' => '201709110000006960',
            'amount' => '15.60',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->getVerifyData();
    }

    /**
     * 測試二維支付但返回狀態失敗
     */
    public function testPayWithQrcodeButStatusFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'ERROR',
            180130
        );

        $result = '{"version":"3.0","status":"0","message":"ERROR","ordernumber":"201709110000006960",' .
            '"paymoney":"15.60","qrurl":""}';

        $sourceData = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1103',
            'number' => '9527',
            'orderId' => '201709110000006960',
            'amount' => '15.60',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->getVerifyData();
    }

    /**
     * 測試二維支付但缺少qrurl
     */
    public function testPayWithQrcodeWithoutQrurl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"version":"3.0","status":"1","message":"","ordernumber":"201709110000006960",' .
            '"paymoney":"15.60","qrurl":""}';

        $sourceData = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1103',
            'number' => '9527',
            'orderId' => '201709110000006960',
            'amount' => '15.60',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->getVerifyData();
    }

    /**
     * 測試二維支付成功
     */
    public function testPayWithQrcodeSuccess()
    {
        $result = '{"version":"3.0","status":"1","message":"","ordernumber":"201709110000006960",' .
            '"paymoney":"15.60","qrurl":"https://qpay.qq.com/qr/57827a0e"}';

        $sourceData = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1103',
            'number' => '9527',
            'orderId' => '201709110000006960',
            'amount' => '15.60',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $encodeData = $yuanBao->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('https://qpay.qq.com/qr/57827a0e', $yuanBao->getQrcode());
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

        $yuanBao = new YuanBao();
        $yuanBao->verifyOrderPayment([]);
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

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->verifyOrderPayment([]);
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
            'partner' => '9527',
            'ordernumber' => '201707030000000104',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'attach' => '',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->verifyOrderPayment([]);
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
            'partner' => '9527',
            'ordernumber' => '201707030000000104',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'attach' => '',
            'sign' => 'yes9527',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->verifyOrderPayment([]);
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
            'partner' => '9527',
            'ordernumber' => '201707030000000104',
            'orderstatus' => '9',
            'paymoney' => '1.0000',
            'attach' => '',
            'sign' => 'abfe913b6068faebe35a20a51a986917',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->verifyOrderPayment([]);
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
            'partner' => '9527',
            'ordernumber' => '201707030000000104',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'attach' => '',
            'sign' => '43542ed5c7e4b9aa1e52bf617254729f',
        ];

        $entry = ['id' => '201707030000000105'];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->verifyOrderPayment($entry);
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
            'partner' => '9527',
            'ordernumber' => '201707030000000104',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'attach' => '',
            'sign' => '43542ed5c7e4b9aa1e52bf617254729f',
        ];

        $entry = [
            'id' => '201707030000000104',
            'amount' => '2.0000',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'partner' => '9527',
            'ordernumber' => '201707030000000104',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'attach' => '',
            'sign' => '43542ed5c7e4b9aa1e52bf617254729f',
        ];

        $entry = [
            'id' => '201707030000000104',
            'amount' => '1.0000',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yuanBao->getMsg());
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

        $yuanBao = new YuanBao();
        $yuanBao->paymentTracking();
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

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->paymentTracking();
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
            'number' => '9527',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"YB1680000017062717144669419050","status":"9","tradestate":"1"' .
            ',"paymoney":"1.00","banktype":"ICBC","paytime":"2017-06-27 05:15:06","endtime' .
            '":"2017-06-27 05:15:41","message":"查询成功","sign":"a8a46904aa42c2c0b9f439e6d6bdf876"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTracking();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"YB1680000017062717144669419050","status":"1","tradestate":"1"' .
            ',"paymoney":"1.00","banktype":"ICBC","paytime":"2017-06-27 05:15:06","endtime' .
            '":"2017-06-27 05:15:41","message":"查询成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTracking();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"YB1680000017062717144669419050","status":"1","tradestate":"1"' .
            ',"paymoney":"1.00","banktype":"ICBC","paytime":"2017-06-27 05:15:06","endtime' .
            '":"2017-06-27 05:15:41","message":"查询成功","sign":"yes9527"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTracking();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"0","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"8b675b6ddadca701b9eee5f2f54cc97f"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $options = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($options);
        $yuanBao->paymentTracking();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"99","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"34a5af9cf60aff45c860ebc0062c8a0b"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $options = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($options);
        $yuanBao->paymentTracking();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"eab8c96f28d59483120decf48ff25b3d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $options = [
            'number' => '9527',
            'orderId' => '201706270000002532',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($options);
        $yuanBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\Durianbundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );
        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"eab8c96f28d59483120decf48ff25b3d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $options = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($options);
        $yuanBao->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"eab8c96f28d59483120decf48ff25b3d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $options = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setContainer($this->container);
        $yuanBao->setClient($this->client);
        $yuanBao->setResponse($response);
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($options);
        $yuanBao->paymentTracking();
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

        $yuanBao = new YuanBao();
        $yuanBao->getPaymentTrackingData();
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

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->getPaymentTrackingData();
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
            'number' => '9527',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $trackingData = $yuanBao->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/online/gateway', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少密鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yuanBao = new YuanBao();
        $yuanBao->paymentTrackingVerify();
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
            'number' => '9527',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢失敗
     */
    public function testPaymentTrackingVerifyFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"YB1680000017062717144669419050","status":"9","tradestate":"1"' .
            ',"paymoney":"1.00","banktype":"ICBC","paytime":"2017-06-27 05:15:06","endtime' .
            '":"2017-06-27 05:15:41","message":"查询成功","sign":"a8a46904aa42c2c0b9f439e6d6bdf876"}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"YB1680000017062717144669419050","status":"1","tradestate":"1"' .
            ',"paymoney":"1.00","banktype":"ICBC","paytime":"2017-06-27 05:15:06","endtime' .
            '":"2017-06-27 05:15:41","message":"查询成功"}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"YB1680000017062717144669419050","status":"1","tradestate":"1"' .
            ',"paymoney":"1.00","banktype":"ICBC","paytime":"2017-06-27 05:15:06","endtime' .
            '":"2017-06-27 05:15:41","message":"查询成功","sign":"9527"}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"0","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"8b675b6ddadca701b9eee5f2f54cc97f"}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"99","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"34a5af9cf60aff45c860ebc0062c8a0b"}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"eab8c96f28d59483120decf48ff25b3d"}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201706270000002532',
            'amount' => '1.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
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

        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"eab8c96f28d59483120decf48ff25b3d"}';

        $sourceData = [
            'number' => '9527',
            'amount' => '12.00',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $result = '{"version":"1.0","partner":"","ordernumber":"201706270000002531",' .
            '"sysnumber":"RX1696916111712064553531000","status":"1","tradestate":"1","paymoney":"1.00",' .
            '"banktype":"ICBC","paytime":"2017-06-27 12:06:29","endtime":"2017-06-27 12:06:44","message":"查询成功",' .
            '"sign":"eab8c96f28d59483120decf48ff25b3d"}';

        $sourceData = [
            'number' => '9527',
            'amount' => '1.00',
            'orderId' => '201706270000002531',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $yuanBao = new YuanBao();
        $yuanBao->setPrivateKey('test');
        $yuanBao->setOptions($sourceData);
        $yuanBao->paymentTrackingVerify();
    }
}
