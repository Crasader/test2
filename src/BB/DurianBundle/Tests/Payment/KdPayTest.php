<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KdPay;
use Buzz\Message\Response;

class KdPayTest extends DurianTestCase
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
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kdPay = new KdPay();
        $kdPay->getVerifyData();
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

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->getVerifyData();
    }

    /**
     * 測試支付時代入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
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
            'username' => 'php1test',
            'postUrl' => '',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->getVerifyData();
    }

    /**
     * 測試支付（微信）時缺少verify_url
     */
    public function testPayWithWeiXinWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_url' => '',
            'shop_url' => 'http://pay.abc/pay/',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->getVerifyData();
    }

    /**
     * 測試支付（微信）時缺少shop_url
     */
    public function testPayWithWeiXinWithoutShopUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No shop_url specified',
            180157
        );

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->getVerifyData();
    }

    /**
     * 測試支付（微信）時返回缺少errcode
     */
    public function testPayWithWeiXinReturnWithoutErrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'errmsg' => 'success',
            'qrcode' => 'weixin://wxpay/bizpayurl?pr=0V3ISA0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'shop_url' => 'http://pay.abc/pay/',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setOptions($sourceData);
        $kdPay->getVerifyData();
    }

    /**
     * 測試支付（微信）時返回提交失敗
     */
    public function testPayWithWeiXinReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '未知错误',
            180130
        );

        $result = [
            'errcode' => '109',
            'errmsg' => '未知错误',
            'qrcode' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'shop_url' => 'http://pay.abc/pay/',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setOptions($sourceData);
        $kdPay->getVerifyData();
    }

    /**
     * 測試支付（微信）時返回缺少qrcode
     */
    public function testPayWithWeiXinReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'errcode' => '0',
            'errmsg' => 'success',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'shop_url' => 'http://pay.abc/pay/',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setOptions($sourceData);
        $kdPay->getVerifyData();
    }

    /**
     * 測試支付（網銀）
     */
    public function testPayWithBank()
    {
        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1',
            'amount' => '0.0100',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'postUrl' => '',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $encodeData = $kdPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['P_UserId']);
        $this->assertEquals('10001', $encodeData['P_Description']);
        $this->assertSame('0.01', $encodeData['P_FaceValue']);
        $this->assertSame('0.01', $encodeData['P_Price']);
        $this->assertEquals($sourceData['orderId'], $encodeData['P_OrderId']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['P_Result_URL']);
        $this->assertEquals($sourceData['username'], $encodeData['P_Subject']);
        $this->assertEquals('1', $encodeData['P_ChannelId']);
        $this->assertEquals('851951c667e93fe8857e1b002056a704', $encodeData['P_PostKey']);
    }

    /**
     * 測試支付（微信）
     */
    public function testPayWithWeiXin()
    {
        $result = [
            'errcode' => '0',
            'errmsg' => 'success',
            'qrcode' => 'weixin://wxpay/bizpayurl?pr=0V3ISA0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1090',
            'amount' => '0.0100',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'postUrl' => '',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'shop_url' => 'http://pay.abc/pay/',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setOptions($sourceData);
        $encodeData = $kdPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=0V3ISA0', $kdPay->getQrcode());
    }

    /**
     * 測試支付（支付寶）
     */
    public function testPayWithAlipay()
    {
        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1092',
            'amount' => '0.0100',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $encodeData = $kdPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['P_UserId']);
        $this->assertEquals('', $encodeData['P_Description']);
        $this->assertSame('0.01', $encodeData['P_FaceValue']);
        $this->assertSame('0.01', $encodeData['P_Price']);
        $this->assertEquals($sourceData['orderId'], $encodeData['P_OrderId']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['P_Result_URL']);
        $this->assertEquals($sourceData['username'], $encodeData['P_Subject']);
        $this->assertEquals('2', $encodeData['P_ChannelId']);
        $this->assertEquals('fb7d539d3c00f70f48dfd84bf80204e7', $encodeData['P_PostKey']);
    }

    /**
     * 測試支付（微信_手機支付）
     */
    public function testPayWithWeiXinWap()
    {
        $result = [
            'errcode' => '0',
            'errmsg' => 'success',
            'qrcode' => 'weixin://wxpay/bizpayurl?pr=0V3ISA0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1097',
            'amount' => '0.0100',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'shop_url' => 'http://pay.abc/pay/',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setOptions($sourceData);
        $encodeData = $kdPay->getVerifyData();

        $this->assertEquals('weixin://wxpay/bizpayurl?pr=0V3ISA0', $encodeData['act_url']);
    }

    /**
     * 測試支付（支付寶_手機支付）
     */
    public function testPayWithAlipayWap()
    {
        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1098',
            'amount' => '0.0100',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $encodeData = $kdPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['P_UserId']);
        $this->assertEquals('', $encodeData['P_Description']);
        $this->assertSame('0.01', $encodeData['P_FaceValue']);
        $this->assertSame('0.01', $encodeData['P_Price']);
        $this->assertEquals($sourceData['orderId'], $encodeData['P_OrderId']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['P_Result_URL']);
        $this->assertEquals($sourceData['username'], $encodeData['P_Subject']);
        $this->assertEquals('36', $encodeData['P_ChannelId']);
        $this->assertEquals('c4b7556ab79de781ef87991142150b75', $encodeData['P_PostKey']);
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kdPay = new KdPay();
        $kdPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'P_PostKey' => '',
            'P_ErrCode' => '',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutPPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'P_UserId' => '6550',
            'P_OrderId' => '201609220000004434',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->verifyOrderPayment([]);
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
            'P_UserId' => '6550',
            'P_OrderId' => '201609220000004434',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '',
            'P_PayMoney' => '',
            'P_ErrCode' => '',
            'P_PostKey' => '',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有回傳 P_ErrCode
     */
    public function testReturnWithoutPErrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '1',
            'P_PostKey' => '219f2f433efe90e74dfcc0bb0f914b4e',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->verifyOrderPayment([]);
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
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '1',
            'P_PostKey' => 'f92e4503e274dce493e6dd5ad3058d89',
            'P_ErrCode' => '1',
            'P_PayMoney' => '',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '1',
            'P_PostKey' => '5e85afeaa615a25a830a92f2af34e950',
            'P_ErrCode' => '0',
            'P_PayMoney' => '0.01',
        ];

        $entry = ['id' => '622000000081116102'];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.01',
            'P_PayMoney' => '1.00',
            'P_ChannelId' => '1',
            'P_PostKey' => 'ac28a9e8ca2db3db0331172c045aff85',
            'P_ErrCode' => '0',
        ];

        $entry = [
            'id' => '201611180000000226',
            'amount' => '1.00',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $sourceData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.01',
            'P_PayMoney' => '0.01',
            'P_ChannelId' => '1',
            'P_PostKey' => '5e85afeaa615a25a830a92f2af34e950',
            'P_ErrCode' => '0',
        ];

        $entry = [
            'id' => '201611180000000226',
            'amount' => '0.01',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->verifyOrderPayment($entry);

        $this->assertEquals('errCode=0', $kdPay->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kdPay = new KdPay();
        $kdPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定參數
     */
    public function testTrackingWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->paymentTracking();
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

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'amount' => '0.01',
            'verify_url' => '',
        ];

        $kdPay = new KdPay();
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果沒有 P_PostKey 的情況
     */
    public function testTrackingReturnWithoutPPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '1',
            'P_CardId' => '',
            'P_payMoney' => '0.0000',
            'P_flag' => '0',
            'P_status' => '0',
            'P_ErrMsg' => '',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
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

        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '1',
            'P_CardId' => '',
            'P_payMoney' => '0.0000',
            'P_flag' => '0',
            'P_status' => '0',
            'P_ErrMsg' => '',
            'P_PostKey' => 'b88df75c2b898865c6164b7d458fc51c',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '6550',
            'orderId' => '201609220000004434',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '1',
            'P_CardId' => '',
            'P_payMoney' => '0.0000',
            'P_flag' => '0',
            'P_status' => '0',
            'P_ErrMsg' => '',
            'P_PostKey' => 'ab09c6bcc187af6e5b90c26ca92ee6db',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '1002433',
            'orderId' => '201611180000000226',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
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

        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '1',
            'P_CardId' => '',
            'P_payMoney' => '0.0000',
            'P_flag' => '2',
            'P_status' => '2',
            'P_ErrMsg' => '',
            'P_PostKey' => '310fd6baa7092ea1d6f47567bd9c6249',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '1002433',
            'orderId' => '201611180000000226',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單號錯誤
     */
    public function testTrackingReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => 'abcd87d',
            'P_ChannelId' => '1',
            'P_CardId' => '',
            'P_payMoney' => '0.0000',
            'P_flag' => '1',
            'P_status' => '1',
            'P_ErrMsg' => '',
            'P_PostKey' => 'b6b4e16ce38fb47e215c4634325d678f',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '1002433',
            'orderId' => '201611180000000226',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
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

        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '1',
            'P_CardId' => '',
            'P_payMoney' => '0.0000',
            'P_flag' => '1',
            'P_status' => '1',
            'P_ErrMsg' => '',
            'P_PostKey' => 'cee7b916a2d56d182b1693afce7d918f',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '1002433',
            'orderId' => '201611180000000226',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '21',
            'P_CardId' => '',
            'P_payMoney' => '0.01',
            'P_flag' => '1',
            'P_status' => '1',
            'P_ErrMsg' => '',
            'P_PostKey' => 'd536e96ccf60c1fc6ec777b8ac34db53',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '1002433',
            'orderId' => '201611180000000226',
            'amount' => '0.01',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功（支付寶）
     */
    public function testTrackingAlipaySuccess()
    {
        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '2',
            'P_CardId' => '',
            'P_payMoney' => '0.01',
            'P_flag' => '1',
            'P_status' => '1',
            'P_ErrMsg' => '',
            'P_PostKey' => 'dbe510c80b167a9314e86bccd12b9dcc',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '1002433',
            'orderId' => '201611180000000226',
            'amount' => '0.01',
            'paymentVendorId' => '1092',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功（微信_手機支付）
     */
    public function testTrackingWeiXinWapSuccess()
    {
        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '33',
            'P_CardId' => '',
            'P_payMoney' => '0.01',
            'P_flag' => '1',
            'P_status' => '1',
            'P_ErrMsg' => '',
            'P_PostKey' => '3f9c8c6365437d7b0106572a864ee840',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '1002433',
            'orderId' => '201611180000000226',
            'amount' => '0.01',
            'paymentVendorId' => '1097',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功（支付寶_手機支付）
     */
    public function testTrackingAlipayWapSuccess()
    {
        $returnData = [
            'P_UserId' => '1002433',
            'P_OrderId' => '201611180000000226',
            'P_ChannelId' => '36',
            'P_CardId' => '',
            'P_payMoney' => '0.01',
            'P_flag' => '1',
            'P_status' => '1',
            'P_ErrMsg' => '',
            'P_PostKey' => '5acf73ef13b753143a80331edae2a420',
        ];

        $result = http_build_query($returnData);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html; charset=utf-8');

        $sourceData = [
            'number' => '1002433',
            'orderId' => '201611180000000226',
            'amount' => '0.01',
            'paymentVendorId' => '1098',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.duqee.com',
        ];

        $kdPay = new KdPay();
        $kdPay->setContainer($this->container);
        $kdPay->setClient($this->client);
        $kdPay->setResponse($response);
        $kdPay->setPrivateKey('1234');
        $kdPay->setOptions($sourceData);
        $kdPay->paymentTracking();
    }
}
