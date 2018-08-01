<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ChingYiPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class ChingYiPayTest extends DurianTestCase
{
    /**
     * @var  \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var  \Buzz\Client\Curl
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
     * 測試時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            '180142'
        );

        $chingYiPay = new ChingYiPay();
        $chingYiPay->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithoutPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
    }

    /**
     *測試支付時帶入支付平台不支援的銀行
     */
    public function testPayWithoutSupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '9999',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
    }

    /**
     * 測試支付時未返回StateCode
     */
    public function testPayNoReturnStateCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => '9527',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'qrcodeUrl' => 'weixin://wxpay/bizpayurl?pr=S5V6QpL',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
    }

    /**
     * 測試支付失敗
     */
    public function testPayFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '不能低于:1元',
            180130
        );

        $result = [
            'stateCode' => '99',
            'msg' => '不能低于:1元',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
    }

    /**
     * 測試支付時未返回qrcode
     */
    public function testPayNoReturnQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => '9527',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
    }

    /**
     * 測試微信WAP支付對外返回缺少query
     */
    public function testWxWapPayReturnWithoutQuery()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => '9527',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
    }

    /**
     * 測試微信WAP支付對外返回提交網址格式錯誤
     */
    public function testWxWapPayReturnQueryFormatError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => '9527',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'qrcodeUrl' => 'https://statecheck.swiftpass.cn',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
    }

    /**
     * 測試微信手機支付
     */
    public function testPayWithWeixinWap()
    {
        $result = [
            'merNo' => '9527',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'qrcodeUrl' => 'https://statecheck.swiftpass.cn/pay/wappay?token_id=1ec50a7dcec0c32b8b96359021e5ac3ab&service=pay.weixin.wappayv2',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
        $data = $chingYiPay->getVerifyData();

        $this->assertEquals('https://statecheck.swiftpass.cn/pay/wappay', $data['post_url']);
        $this->assertEquals('1ec50a7dcec0c32b8b96359021e5ac3ab', $data['params']['token_id']);
        $this->assertEquals('pay.weixin.wappayv2', $data['params']['service']);
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'merNo' => '9527',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'qrcodeUrl' => 'weixin://wxpay/bizpayurl?pr=S5V6QpL',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
        $data = $chingYiPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=S5V6QpL', $chingYiPay->getQrcode());
    }

    /**
     * 測試WAP支付
     */
    public function testWapPay()
    {
        $result = [
            'merNo' => '9527',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'qrcodeUrl' => 'http://9527.com',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1104',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
        $data = $chingYiPay->getVerifyData();

        $this->assertEquals('http://9527.com', $data['act_url']);
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

        $chingYiPay = new ChingYiPay();
        $chingYiPay->verifyOrderPayment([]);
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

        $sourceData = ['payResult' => ''];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => '9527',
            'netway' => 'WX',
            'orderNum' => '201707190000009453',
            'payResult' => '00',
            'payDate' => '2017-07-19 14:25:38',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時加密簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => '9527',
            'netway' => 'WX',
            'orderNum' => '201707190000009453',
            'payResult' => '00',
            'payDate' => '2017-07-19 14:25:38',
            'sign' => '0B99034CAEFF71D3E88CC49647C3108A',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201703300214',
            'netway' => 'WX',
            'orderNum' => '201704100000002214',
            'payResult' => '99',
            'payDate' => '2017-04-10 14:25:38',
            'sign' => 'E98E05789459270C590F37D876A3F2C3',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201703300214',
            'netway' => 'WX',
            'orderNum' => '201704100000002214',
            'payResult' => '00',
            'payDate' => '2017-04-10 14:25:38',
            'sign' => '941715695987D91A1A320C2D98D98ABD',
        ];

        $entry = ['id' => '201704100000002210'];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->verifyOrderPayment($entry);
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
            'amount' => '200',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201703300214',
            'netway' => 'WX',
            'orderNum' => '201704100000002214',
            'payResult' => '00',
            'payDate' => '2017-04-10 14:25:38',
            'sign' => 'BB88B86D206A39A4D734CC072B263FFA',
        ];

        $entry = [
            'id' => '201704100000002214',
            'amount' => '1',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'amount' => '200',
            'goodsName' => 'php1test',
            'merNo' => 'Mer201703300214',
            'netway' => 'WX',
            'orderNum' => '201704100000002214',
            'payResult' => '00',
            'payDate' => '2017-04-10 14:25:38',
            'sign' => 'BB88B86D206A39A4D734CC072B263FFA',
        ];

        $entry = [
            'id' => '201704100000002214',
            'amount' => '2',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->verifyOrderPayment($entry);

        $this->assertEquals('0', $chingYiPay->getMsg());
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

        $chingYiPay = new ChingYiPay();
        $chingYiPay->paymentTracking();
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

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->paymentTracking();
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
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回缺少指定參數
     */
    public function testTrackingReturnWithoutSpecifiedParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"stateCode":"99"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查询出错',
            180130
        );

        $result = '{"stateCode":"99","msg":"查询出错"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
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

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"01","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
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

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"01","sign":"ABCDE","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數缺少payStateCode
     */
    public function testTrackingReturnWithoutPayStateCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"stateCode":"00","sign":"FC7BF34750311A095AE1663CFBF83AA3"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"99","sign":"F7C851A0E37BF0603301FC5962D4A1B9","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
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

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"22","sign":"F7C7A563D5EB1C60679D89929BF587D4","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數缺少orderNum
     */
    public function testTrackingReturnWithoutUOrderNum()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"merNo":"9527","msg":"查询成功",' .
            ',"payStateCode":"00","sign":"D6A0494420AF106E1C260FE0D34A6A6C","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
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

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"00","sign":"1D4602DC1EDC61071FDED870045BB0A2","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000105',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"00","sign":"1D4602DC1EDC61071FDED870045BB0A2","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTracking();
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

        $chingYiPay = new ChingYiPay();
        $chingYiPay->getPaymentTrackingData();
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

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->getPaymentTrackingData();
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
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $trackingData = $chingYiPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/api/queryPayResult.action', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals(90, $trackingData['headers']['Port']);
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

        $chingYiPay = new ChingYiPay();
        $chingYiPay->paymentTrackingVerify();
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
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢失敗
     */
    public function testPaymentTrackingVerifyWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查询出错',
            180130
        );

        $result = '{"stateCode":"99","msg":"查询出错"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果回傳參數缺少sign
     */
    public function testTrackingVerifyReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"01","stateCode":"00"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名錯誤
     */
    public function testTrackingVerifyReturnWithErrorSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"01","sign":"ABCDE","stateCode":"00"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果回傳參數缺少payStateCode
     */
    public function testTrackingVerifyReturnWithoutPayStateCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"stateCode":"00","sign":"FC7BF34750311A095AE1663CFBF83AA3"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果訂單未支付
     */
    public function testTrackingVerifyReturnWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"99","sign":"F7C851A0E37BF0603301FC5962D4A1B9","stateCode":"00"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單結果支付失敗
     */
    public function testTrackingVerifyReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"22","sign":"F7C7A563D5EB1C60679D89929BF587D4","stateCode":"00"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果回傳參數缺少orderNum
     */
    public function testTrackingVerifyReturnWithoutOrderNum()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"merNo":"9527","msg":"查询成功"' .
            ',"payStateCode":"00","stateCode":"00","sign":"D6A0494420AF106E1C260FE0D34A6A6C"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢返回訂單號錯誤
     */
    public function testTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"00","sign":"1D4602DC1EDC61071FDED870045BB0A2","stateCode":"00"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000105',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $result = '{"merNo":"9527","msg":"查询成功","orderNum":"201707030000000104"' .
            ',"payStateCode":"00","sign":"1D4602DC1EDC61071FDED870045BB0A2","stateCode":"00"}';

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'orderCreateDate' => '2017-07-19 14:25:38',
            'orderId' => '201707030000000104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $chingYiPay = new ChingYiPay();
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->paymentTrackingVerify();
    }
}
