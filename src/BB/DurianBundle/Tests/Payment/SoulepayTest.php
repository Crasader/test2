<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Soulepay;
use Buzz\Message\Response;

class SoulepayTest extends DurianTestCase
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

        $soulepay = new Soulepay();
        $soulepay->getVerifyData();
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

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->getVerifyData();
    }

    /**
     * 測試支付加密時带入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '117072600001',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201708070000006780',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->getVerifyData();
    }

    /**
     * 測試支付加密時缺少商家附加設定值
     */
    public function testPayEncodeWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '117072600001',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201708070000006780',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'merchant_extra' => []
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '117072600001',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201708070000006780',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'merchant_extra' => ['phone' => '13285941365']
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $encodeData = $soulepay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchantId']);
        $this->assertEquals($sourceData['merchant_extra']['phone'], $encodeData['phone']);
        $this->assertSame($sourceData['orderId'], $encodeData['orderNo']);
        $this->assertSame($sourceData['amount'], $encodeData['transAmt']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notifyUrl']);
        $this->assertEquals($sourceData['username'], $encodeData['commodityName']);
        $this->assertEquals($sourceData['username'], $encodeData['commodityDesc']);
        $this->assertSame('ICBC', $encodeData['bankCode']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['returnUrl']);
        $this->assertEquals('2558699c1b044f9afa2ca8848f2bdad7', $encodeData['signature']);
    }

    /**
     * 測試二維支付未帶入verify_url
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '117072600001',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201708070000006780',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'merchant_extra' => ['phone' => '13285941365'],
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->getVerifyData();
    }

    /**
     * 測試二維支付但返回缺少code
     */
    public function testQrcodePayWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '117072600001',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201708070000006780',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'merchant_extra' => ['phone' => '13285941365'],
            'verify_url' => 'payment.http.120.24.233.221',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"orderNo":"201709210000007196","orderId":"ZFB40158_09211010560505020",' .
            '"qRcodeURL":null,"message":"通道维护中，请稍后再试！"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->getVerifyData();
    }

    /**
     * 測試二維支付但返回結果出現錯誤
     */
    public function testQrcodePayButError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '单笔不能低于300元',
            180130
        );

        $sourceData = [
            'number' => '117072600001',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201708070000006780',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'merchant_extra' => ['phone' => '13285941365'],
            'verify_url' => 'payment.http.120.24.233.221',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"code":"0002","message":"单笔不能低于300元","orderNo":"201709210000007193"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->getVerifyData();
    }

    /**
     * 測試二維支付但返回缺少qRcodeUrl
     */
    public function testQrcodePayWithoutQrcodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '117072600001',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201708070000006780',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'merchant_extra' => ['phone' => '13285941365'],
            'verify_url' => 'payment.http.120.24.233.221',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"code":"0000","orderId":"WX40158_09201931379473094",' .
            '"message":"成功","orderNo":"201709200000007189"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $sourceData = [
            'number' => '117072600001',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201708070000006780',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => 'php1test',
            'merchant_extra' => ['phone' => '13285941365'],
            'verify_url' => 'payment.http.120.24.233.221',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"qRcodeURL":"weixin://wxpay/bizpayurl?pr=L0N1PUL","code":"0000",' .
            '"orderId":"WX40158_09201931379473094","message":"成功","orderNo":"201709200000007189"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $encodeData = $soulepay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=L0N1PUL', $soulepay->getQrcode());
    }

    /**
     * 測試返回沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $soulepay = new Soulepay();
        $soulepay->verifyOrderPayment([]);
    }

    /**
     * 測試返回未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = ['orderNo' => '201708070000006780'];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->verifyOrderPayment([]);
    }

    /**
     * 測試返回缺少回傳signature(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderNo' => '201708070000006780',
            'orderId' => 'Net170807143348066',
            'respCode' => '0000',
            'code' => '0000',
            'message' => '成功',
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->verifyOrderPayment([]);
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
            'orderNo' => '201708070000006780',
            'orderId' => 'Net170807143348066',
            'respCode' => '0000',
            'code' => '0000',
            'message' => '成功',
            'signature' => '1a4be806f50520644d0ac61b97182379',
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->verifyOrderPayment([]);
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
            'orderNo' => '201708070000006780',
            'orderId' => 'Net170807143348066',
            'respCode' => '0000',
            'code' => '0002',
            'message' => '失敗',
            'signature' => 'c0722fea83faf984ac9b49ef4410d447',
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->verifyOrderPayment([]);
    }

    /**
     * 測試返回但單號不正確
     */
    public function testReturnButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderNo' => '201708070000006780',
            'orderId' => 'Net170807143348066',
            'respCode' => '0000',
            'code' => '0000',
            'message' => '成功',
            'signature' => '1e1af460503eedb0217503838c9aac30',
        ];

        $entry = ['id' => '201606220000002806'];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'orderNo' => '201708070000006780',
            'orderId' => 'Net170807143348066',
            'respCode' => '0000',
            'code' => '0000',
            'message' => '成功',
            'signature' => '1e1af460503eedb0217503838c9aac30',
        ];

        $entry = ['id' => '201708070000006780'];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->verifyOrderPayment($entry);

        $this->assertEquals('success', $soulepay->getMsg());
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

        $soulepay = new Soulepay();
        $soulepay->paymentTracking();
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

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->paymentTracking();
    }

    /**
     * 測試訂單查詢缺少商家附加設定值
     */
    public function testTrackingWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [],
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
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
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['phone' => '13285941365'],
            'verify_url' => '',
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數Code
     */
    public function testTrackingReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $returnValues = ['message' => '订单不存在'];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'merchant_extra' => ['phone' => '13285941365'],
        ];

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果但查詢失敗
     */
    public function testTrackingReturnButError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单不存在',
            180123
        );

        $returnValues = [
            'message' => '订单不存在',
            'code' => '0002',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'merchant_extra' => ['phone' => '13285941365'],
        ];

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果但缺少返回參數
     */
    public function testTrackingReturnWithoutParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'merchant_extra' => ['phone' => '13285941365'],
        ];

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
            'orderId' => 'Net170807143348066',
            'fee' => '0.0150',
            'orderDate' => '20170807143348',
            'ordercode' => '0',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'merchant_extra' => ['phone' => '13285941365'],
        ];

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
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

        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
            'orderId' => 'Net170807143348066',
            'fee' => '0.0150',
            'orderDate' => '20170807143348',
            'ordercode' => '9',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'merchant_extra' => ['phone' => '13285941365'],
        ];

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
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

        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
            'orderId' => 'Net170807143348066',
            'fee' => '0.0150',
            'orderDate' => '20170807143348',
            'ordercode' => '1',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'merchant_extra' => ['phone' => '13285941365'],
            'amount' => '1000.00',
        ];

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
            'orderId' => 'Net170807143348066',
            'fee' => '0.0150',
            'orderDate' => '20170807143348',
            'ordercode' => '1',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => '117072600001',
            'orderId' => 'Net170807143348066',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'merchant_extra' => ['phone' => '13285941365'],
            'amount' => '0.01',
        ];

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTracking();
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

        $soulepay = new Soulepay();
        $soulepay->getPaymentTrackingData();
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

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少商家附加設定值
     */
    public function testGetPaymentTrackingDataWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [],
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->getPaymentTrackingData();
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
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['phone' => '13285941365'],
            'verify_url' => '',
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'merchant_extra' => ['phone' => '13285941365'],
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $trackingData = $soulepay->getPaymentTrackingData();

        $path = '/api/Pay/ReceivableItem?phone=13285941365&merchantId=117072600001&' .
            'orderNo=201708070000006780&signature=bff6ffbb761c1920c0b1ed4de8757935';

        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參數code
     */
    public function testPaymentTrackingVerifyWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $returnValues = [
            'message' => '查询成功',
        ];

        $result = json_encode($returnValues);

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'content' => $result,
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時查詢失敗
     */
    public function testPaymentTrackingVerifyButError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单不存在',
            180123
        );

        $returnValues = [
            'message' => '订单不存在',
            'code' => '0002',
        ];

        $result = json_encode($returnValues);

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'content' => $result,
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTrackingVerify();
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

        $returnValues = [
            'message' => '查询成功',
            'code' => '0000',
        ];

        $result = json_encode($returnValues);

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'content' => $result,
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
            'orderId' => '201708070000006780',
            'fee' => '0.0150',
            'orderDate' => '20170807143348',
            'ordercode' => '0',
        ];

        $result = json_encode($returnValues);

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'content' => $result,
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTrackingVerify();
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

        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
            'orderId' => '201708070000006780',
            'fee' => '0.0150',
            'orderDate' => '20170807143348',
            'ordercode' => '9',
        ];

        $result = json_encode($returnValues);

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'content' => $result,
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTrackingVerify();
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

        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
            'orderId' => '201708070000006780',
            'fee' => '0.0150',
            'orderDate' => '20170807143348',
            'ordercode' => '1',
        ];

        $result = json_encode($returnValues);

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '201708070000006780',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'content' => $result,
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $returnValues = [
            'message' => '查询成功',
            'amount' => '0.01',
            'code' => '0000',
            'orderId' => 'Net170807143348066',
            'fee' => '0.0150',
            'orderDate' => '20170807143348',
            'ordercode' => '1',
        ];

        $result = json_encode($returnValues);

        $sourceData = [
            'number' => '117072600001',
            'orderId' => 'Net170807143348066',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.24.233.221',
            'content' => $result,
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('test');
        $soulepay->setOptions($sourceData);
        $soulepay->paymentTrackingVerify();
    }

    /**
     * 測試出款沒有帶入privateKey
     */
    public function testWithdrawWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $soulepay = new Soulepay();
        $soulepay->withdrawPayment();
    }

    /**
     * 測試出款未指定出款參數
     */
    public function testWithdrawNoWithdrawParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw parameter specified',
            150180196
        );

        $sourceData = ['account' => ''];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->withdrawPayment();
    }

    /**
     * 測試出款缺少商家附加設定值
     */
    public function testWithdrawWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'withdraw_host' => 'payment.http.withdraw.com',
            'number' => '117072600001',
            'merchant_extra' => [],
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少參數
     */
    public function testWithdrawButNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '117072600001',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['phone' => '123123132'],
        ];

        $result = '{"code":"0003"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果錯誤(餘額不足)
     */
    public function testWithdrawButError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '余额不足',
            180124
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '117072600001',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['phone' => '123123132'],
        ];

        $result = '{"message":"余额不足","code":"0003"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->withdrawPayment();
    }

    /**
     * 測試出款請求成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '117072600001',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['phone' => '123123132'],
        ];

        $result = '{"message":"请求成功","code":"0000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->withdrawPayment();
    }

    /**
     * 測試出款查詢沒有帶入privateKey
     */
    public function testWithdrawTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $soulepay = new Soulepay();
        $soulepay->withdrawTracking();
    }

    /**
     * 測試出款查詢未指定出款查詢參數
     */
    public function testWithdrawTrackingNoWithdrawTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw tracking parameter specified',
            150180199
        );

        $sourceData = ['number' => ''];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->withdrawTracking();
    }

    /**
     * 測試出款查詢缺少商家附加設定值
     */
    public function testWithdrawTrackingWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '112332',
            'merchant_extra' => [],
        ];

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->withdrawTracking();
    }

    /**
     * 測試出款查詢但缺少返回參數(message)
     */
    public function testWithdrawTrackingButReturnWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw tracking return parameter specified',
            150180200
        );

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '112332',
            'merchant_extra' => ['phone' => '123123132'],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"code":"0001"}';

        $response = new Response();
        $response->setContent(($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->withdrawTracking();
    }

    /**
     * 測試出款查詢但查詢結果錯誤
     */
    public function testWithdrawTrackingButWithdrawTrackingError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            '签名错误',
            150180201
        );

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '112332',
            'merchant_extra' => ['phone' => '123123132'],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"message":"签名错误","code":"0001"}';

        $response = new Response();
        $response->setContent(($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->withdrawTracking();
    }

    /**
     * 測試出款查詢但缺少返回參數
     */
    public function testWithdrawTrackingButNoWithdrawTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw tracking return parameter specified',
            150180200
        );

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '112332',
            'merchant_extra' => ['phone' => '123123132'],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"Remark":"成功","amount":294.00,"code":"0000","withdrawcode":1,"fee":2.00,' .
            '"message":"查询成功","withdrawDate":"20170818153125"}';

        $response = new Response();
        $response->setContent(($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->withdrawTracking();
    }

    /**
     * 測試出款查詢但查詢結果失敗
     */
    public function testWithdrawTrackingButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Withdraw tracking failed',
            150180198
        );

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '112332',
            'merchant_extra' => ['phone' => '123123132'],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"Remark":"成功","amount":294.00,"code":"0000","withdrawcode":0,"fee":2.00,' .
            '"message":"查询成功","withdrawDate":"20170818153125","withdrawId":"TX2017081815312441"}';

        $response = new Response();
        $response->setContent(($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->withdrawTracking();
    }

    /**
     * 測試出款查詢但金額不正確
     */
    public function testWithdrawTrackingButOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'number' => '117072600001',
            'orderId' => '112332',
            'auto_withdraw_amount' => '300',
            'merchant_extra' => ['phone' => '123123132'],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"Remark":"成功","amount":294.00,"code":"0000","withdrawcode":1,"fee":2.00,' .
            '"message":"查询成功","withdrawDate":"20170818153125","withdrawId":"TX2017081815312441"}';

        $response = new Response();
        $response->setContent(($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->withdrawTracking();
    }

    /**
     * 測試出款查詢
     */
    public function testWithdrawTracking()
    {
        $sourceData = [
            'number' => '117072600001',
            'orderId' => '112332',
            'auto_withdraw_amount' => '296',
            'merchant_extra' => ['phone' => '123123132'],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"Remark":"成功","amount":294.00,"code":"0000","withdrawcode":1,"fee":2.00,' .
            '"message":"查询成功","withdrawDate":"20170818153125","withdrawId":"TX2017081815312441"}';

        $response = new Response();
        $response->setContent(($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $soulepay = new Soulepay();
        $soulepay->setPrivateKey('1234');
        $soulepay->setOptions($sourceData);
        $soulepay->setContainer($this->container);
        $soulepay->setClient($this->client);
        $soulepay->setResponse($response);
        $soulepay->withdrawTracking();
    }
}
