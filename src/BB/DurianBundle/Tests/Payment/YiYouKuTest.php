<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiYouKu;
use Buzz\Message\Response;

class YiYouKuTest extends DurianTestCase
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

        $yiYouKu = new YiYouKu();
        $yiYouKu->getVerifyData();
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

        $options = ['number' => ''];

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('30819f300d06092a864886f70d01010');

        $sourceData = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'amount' => '0.01',
            'notify_url' => 'http://fpay.yeeyk.com/pay/return.php',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.fpay.yeeyk.com',
            'paymentVendorId' => '9999',
        ];

        $yiYouKu->setOptions($sourceData);
        $yiYouKu->getVerifyData();
    }

    /**
     * 測試支付未返回code
     */
    public function testPayNoReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"message":"下单成功","hmac":"f890ee77867b5cbc9ecdbdcc2ac94445",' .
            '"merchantOrderno":"201611180000000262","merchantNo":"70001000016",' .
            '"payUrl":"weixin://wxpay/bizpayurl?pr=tPBIquQ"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'amount' => '0.01',
            'notify_url' => 'http://fpay.yeeyk.com/pay/return.php',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.fpay.yeeyk.com',
            'paymentVendorId' => '1092',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->getVerifyData();
    }

    /**
     * 測試支付未返回message
     */
    public function testPayNoReturnMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"hmac":"f890ee77867b5cbc9ecdbdcc2ac94445","merchantOrderno":"201611180000000262",' .
            '"merchantNo":"70001000016","payUrl":"weixin://wxpay/bizpayurl?pr=tPBIquQ","code":"000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.fpay.yeeyk.com',
            'paymentVendorId' => '1092',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->getVerifyData();
    }
    /**
     * 測試支付返回code不為000
     */
    public function testPayReturnCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '参数异常',
            180130
        );

        $result = '{"message":"参数异常","hmac":"f890ee77867b5cbc9ecdbdcc2ac94445",' .
            '"merchantOrderno":"201611180000000262","merchantNo":"70001000016",' .
            '"payUrl":"weixin://wxpay/bizpayurl?pr=tPBIquQ","code":"110"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.fpay.yeeyk.com',
            'paymentVendorId' => '1092',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->getVerifyData();
    }

    /**
     * 測試支付未返回payUrl
     */
    public function testPayNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"message":"下单成功","hmac":"f890ee77867b5cbc9ecdbdcc2ac94445",' .
            '"merchantOrderno":"201611180000000262","merchantNo":"70001000016","code":"000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.fpay.yeeyk.com',
            'paymentVendorId' => '1092',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPayData()
    {
        $result = '{"message":"下单成功","hmac":"f890ee77867b5cbc9ecdbdcc2ac94445",' .
            '"merchantOrderno":"201611180000000262","merchantNo":"70001000016",' .
            '"payUrl":"weixin://wxpay/bizpayurl?pr=tPBIquQ","code":"000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.fpay.yeeyk.com',
            'paymentVendorId' => '1092',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $encodeData = $yiYouKu->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=tPBIquQ', $yiYouKu->getQrcode());
    }

    /**
     * 測試手機支付加密
     */
    public function testPayDataWithWap()
    {
        $result = '{"message":"下单成功","hmac":"c11fd5ae43eaa1cdcdbe3b16da189572",' .
            '"merchantOrderno":"201709040000006888","merchantNo":"70001000007","payUrl":' .
            '"http://fpay.yeeyk.com/fourth-app/sim/fetchWxWap?payNo=2017090411401929589912","code":"000"}';

        $options = [
            'number' => '70001000007',
            'orderId' => '201709040000006888',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.fpay.yeeyk.com',
            'paymentVendorId' => '1097',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $encodeData = $yiYouKu->getVerifyData();

        $actUrl = 'http://fpay.yeeyk.com/fourth-app/sim/fetchWxWap?payNo=2017090411401929589912';
        $this->assertEquals($actUrl, $encodeData['act_url']);
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

        $yiYouKu = new YiYouKu();
        $yiYouKu->verifyOrderPayment([]);
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

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少hmac
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'reCode' => '1',
            'merchantNo' => '70001000016',
            'merchantOrderno' => '201611160000000262',
            'result' => 'SUCCESS',
            'payType' => 'WX',
            'memberGoods' => 'php1test',
            'amount' => '0.01',
        ];

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->verifyOrderPayment([]);
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

        $options = [
            'reCode' => '1',
            'merchantNo' => '70001000016',
            'merchantOrderno' => '201611160000000262',
            'result' => 'SUCCESS',
            'payType' => 'WX',
            'memberGoods' => 'php1test',
            'amount' => '0.01',
            'hmac' => 'ae4a4b7ba1e423ac0f71ec5d94715ce6',
        ];

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->verifyOrderPayment([]);
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

        $options = [
            'reCode' => '0',
            'merchantNo' => '70001000016',
            'merchantOrderno' => '201611160000000262',
            'result' => 'SUCCESS',
            'payType' => 'WX',
            'memberGoods' => 'php1test',
            'amount' => '0.01',
            'hmac' => '9b30a49d31dbc5ec5426d39ddeaa7a58',
        ];

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'reCode' => '1',
            'merchantNo' => '70001000016',
            'merchantOrderno' => '201611160000000262',
            'result' => 'SUCCESS',
            'payType' => 'WX',
            'memberGoods' => 'php1test',
            'amount' => '0.01',
            'hmac' => '49b2ff74412c04a0e2c42defbc6142cd',
        ];

        $entry = ['id' => '201611160000000261'];

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->verifyOrderPayment($entry);
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

        $options = [
            'reCode' => '1',
            'merchantNo' => '70001000016',
            'merchantOrderno' => '201611160000000262',
            'result' => 'SUCCESS',
            'payType' => 'WX',
            'memberGoods' => 'php1test',
            'amount' => '0.01',
            'hmac' => '49b2ff74412c04a0e2c42defbc6142cd',
        ];

        $entry = [
            'id' => '201611160000000262',
            'amount' => '100.00',
        ];

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'reCode' => '1',
            'merchantNo' => '70001000016',
            'merchantOrderno' => '201611160000000262',
            'result' => 'SUCCESS',
            'payType' => 'WX',
            'memberGoods' => 'php1test',
            'amount' => '0.01',
            'hmac' => '49b2ff74412c04a0e2c42defbc6142cd',
        ];

        $entry = [
            'id' => '201611160000000262',
            'amount' => '0.01',
        ];

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yiYouKu->getMsg());
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

        $yiYouKu = new YiYouKu();
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->paymentTracking();
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

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yiYouKu = new YiYouKu();
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳缺少code
     */
    public function testTrackingReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"data":{"result":"SUCCESS","memberGoods":"php1test","hmac":"bec9d47b60631ba7a870ea91a038b244",' .
            '"payType":"WX","merchantOrderno":"201611160000000262","merchantNo":"70001000016","reCode":"1"}}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢時提交的參數錯誤
     */
    public function testTrackingReturnSubmitTheParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $result = '{"data":null,"code":"100100"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢時簽名校驗失敗
     */
    public function testTrackingReturnPaymentGatewayErrorMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $result = '{"data":null,"code":"100300"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢時商號不存在
     */
    public function testTrackingReturnPaymentGatewayErrorMerchantIsNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant is not exist',
            180086
        );

        $result = '{"data":null,"code":"100401"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢時訂單號不存在
     */
    public function testTrackingReturnOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = '{"data":null,"code":"120000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢時訂單正在處理中
     */
    public function testTrackingReturnCodeOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '{"data":null,"code":"120001"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '{"data":{"result":"SUCCESS","memberGoods":"php1test","hmac":"a73df5237b9ea0fd3209f6d1405a48bd",' .
            '"payType":"WX","merchantOrderno":"201611160000000262","merchantNo":"70001000016","reCode":"1"},' .
            '"code":"999999"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳缺少data
     */
    public function testTrackingReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"code":"000000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'hmac' => '00433778b90908b3f1a094c20838103f',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳缺少hmac
     */
    public function testTrackingReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"data":{"result":"SUCCESS","memberGoods":"php1test",' .
            '"payType":"WX","merchantOrderno":"201611160000000262","merchantNo":"70001000016","reCode":"1"},' .
            '"code":"000000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
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

        $result = '{"data":{"result":"SUCCESS","memberGoods":"php1test","hmac":"bec9d47b60631ba7a870ea91a0000000",' .
            '"payType":"WX","merchantOrderno":"201611160000000262","merchantNo":"70001000016","reCode":"1"},' .
            '"code":"000000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳訂單號錯誤
     */
    public function testTrackingReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '{"data":{"result":"SUCCESS","memberGoods":"php1test","hmac":"e88a20958fb19521df956ff8636a85c8",' .
            '"payType":"WX","merchantOrderno":"201611160000000261","merchantNo":"70001000016","reCode":"1"},' .
            '"code":"000000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '{"data":{"result":"DEAL","memberGoods":"php1test","hmac":"ce9aea8c65cb1efab43773c5fbd641a7",' .
            '"payType":"WX","merchantOrderno":"201611160000000262","merchantNo":"70001000016","reCode":"1"},' .
            '"code":"000000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳reCode不為1
     */
    public function testTrackingReturnReCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '{"data":{"result":"SUCCESS","memberGoods":"php1test","hmac":"d1468125e8514117efaf7e2fdbba5c7f",' .
            '"payType":"WX","merchantOrderno":"201611160000000262","merchantNo":"70001000016","reCode":"0"},' .
            '"code":"000000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳result不為SUCCESS
     */
    public function testTrackingReturnResultNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '{"data":{"result":"GGGG","memberGoods":"php1test","hmac":"69d3fd287f50f6b41e7b87c7bc44ca9d",' .
            '"payType":"WX","merchantOrderno":"201611160000000262","merchantNo":"70001000016","reCode":"1"},' .
            '"code":"000000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = '{"data":{"result":"SUCCESS","memberGoods":"php1test","hmac":"a73df5237b9ea0fd3209f6d1405a48bd",' .
            '"payType":"WX","merchantOrderno":"201611160000000262","merchantNo":"70001000016","reCode":"1"},' .
            '"code":"000000"}';

        $options = [
            'number' => '70001000016',
            'orderId' => '201611160000000262',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.fpay.yeeyk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiYouKu = new YiYouKu();
        $yiYouKu->setContainer($this->container);
        $yiYouKu->setClient($this->client);
        $yiYouKu->setResponse($response);
        $yiYouKu->setPrivateKey('test');
        $yiYouKu->setOptions($options);
        $yiYouKu->paymentTracking();
    }
}
