<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\XinMaPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class XinMaPayTest extends DurianTestCase
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
            '180142'
        );

        $xinMaPay = new XinMaPay();
        $xinMaPay->getVerifyData();
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

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     *測試支付時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
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
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     *測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     * 測試支付時未返回sign
     */
    public function testPayNoReturnSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'paySeq' => '10012017081619115100541730',
            'nonceStr' => 'poweiishandsome',
            'orderNo' => 'p2017081819115500122054',
            'resCode' => '00',
            'resDesc' => '成功',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'payUrl' => 'weixin://wxpay/phptest?pr=phptest',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     * 測試支付時返回簽名錯誤
     */
    public function testPayReturnSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'paySeq' => '10012017081619115100541730',
            'nonceStr' => 'poweiishandsome',
            'orderNo' => 'p2017081819115500122054',
            'resCode' => '00',
            'resDesc' => '成功',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'payUrl' => 'weixin://wxpay/phptest?pr=phptest',
            'sign' => 'sohandsome',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     * 測試支付時未返回resultCode
     */
    public function testPayNoReturnResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'paySeq' => '10012017081619115100541730',
            'nonceStr' => 'poweiishandsome',
            'orderNo' => 'p2017081819115500122054',
            'resCode' => '00',
            'resDesc' => '成功',
            'payUrl' => 'weixin://wxpay/phptest?pr=phptest',
            'sign' => '6410E8122B81B4A81D0F009109F8C020',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     * 測試支付時返回resultCode不等於00
     */
    public function testPayReturnResultCodeNotEqualZeroZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付平台連線異常',
            180130
        );

        $result = [
            'paySeq' => '10012017081619115100541730',
            'nonceStr' => 'poweiishandsome',
            'orderNo' => 'p2017081819115500122054',
            'resCode' => '00',
            'resDesc' => '成功',
            'resultCode' => '99',
            'resultDesc' => '支付平台連線異常',
            'payUrl' => 'weixin://wxpay/phptest?pr=phptest',
            'sign' => 'BC0E3CA5A07380AF1A763A908652E3CE',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     * 測試支付時返回resCode不等於00
     */
    public function testPayReturnResCodeNotEqualZeroZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失敗',
            180130
        );

        $result = [
            'paySeq' => '10012017081619115100541730',
            'nonceStr' => 'poweiishandsome',
            'orderNo' => 'p2017081819115500122054',
            'resCode' => '99',
            'resDesc' => '交易失敗',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'payUrl' => 'weixin://wxpay/phptest?pr=phptest',
            'sign' => 'A8E91A62D683393BBB5A132CBF63E378',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     * 測試支付時未返回payUrl
     */
    public function testPayNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'paySeq' => '10012017081619115100541730',
            'nonceStr' => 'poweiishandsome',
            'orderNo' => 'p2017081819115500122054',
            'resCode' => '00',
            'resDesc' => '成功',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'sign' => 'EECBC779CA7C7050D40278C8DF223EAE',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'paySeq' => '10012017081619115100541730',
            'nonceStr' => 'poweiishandsome',
            'orderNo' => 'p2017081819115500122054',
            'resCode' => '00',
            'resDesc' => '成功',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'payUrl' => 'weixin://wxpay/phptest?pr=phptest',
            'sign' => '2B1254BC17C8C2A92A08981503C285D6',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708180000009453',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
        $data = $xinMaPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/phptest?pr=phptest', $xinMaPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPayWithWap()
    {
        $result = [
            'paySeq' => '10012017091111390400072580',
            'nonceStr' => '0NrQqzuYHwo1U1hghE3FhUKRGhxZul0f',
            'orderNo' => 'p2017091111390900085332',
            'resCode' => '00',
            'resDesc' => '成功',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'payUrl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183',
            'sign' => '7D76B0A105C85D339C0E750B0F1A43C8',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1104',
            'amount' => '1.00',
            'orderId' => '201709110000006945',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
        $data = $xinMaPay->getVerifyData();

        $this->assertEquals($result['payUrl'], $data['act_url']);
    }

    /**
     * 測試微信手機支付payUrl格式不正確
     */
    public function testPayWithWeixinWapPayUrlFormatError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'paySeq' => '10012017122210322400384773',
            'nonceStr' => 'bGcSuyzc1IILUnGWwXuyD5vDhLqiGjEG',
            'orderNo' => 'p2017122210322500837361',
            'resCode' => '00',
            'resDesc' => '成功',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'payUrl' => '://api.ulopay.com/',
            'sign' => 'F66DE80F36D535B47A9FED79ED7D0538',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '171200292166',
            'paymentVendorId' => '1097',
            'amount' => '100.5700',
            'orderId' => '201712220738903495',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getVerifyData();
    }

    /**
     * 測試微信手機支付
     */
    public function testPayWithWeixinWap()
    {
        $result = [
            'paySeq' => '10012017122210322400384773',
            'nonceStr' => 'bGcSuyzc1IILUnGWwXuyD5vDhLqiGjEG',
            'orderNo' => 'p2017122210322500837361',
            'resCode' => '00',
            'resDesc' => '成功',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'payUrl' => 'https://api.ulopay.com/pay/jspay?ret=1&prepay_id=45d5f31a3aa00193597358c006986681',
            'sign' => 'A8E761B51224869FAD99D2A13D02FE98',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '171200292166',
            'paymentVendorId' => '1097',
            'amount' => '100.5700',
            'orderId' => '201712220738903495',
            'notify_url' => 'http://yes9527.com',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $data = $xinMaPay->getVerifyData();

        $this->assertEquals('https://api.ulopay.com/pay/jspay', $data['post_url']);
        $this->assertEquals('1', $data['params']['ret']);
        $this->assertEquals('45d5f31a3aa00193597358c006986681', $data['params']['prepay_id']);
        $this->assertEquals('GET', $xinMaPay->getPayMethod());
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

        $xinMaPay = new XinMaPay();
        $xinMaPay->verifyOrderPayment([]);
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

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->verifyOrderPayment([]);
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
            'createTime' => '20170816191151',
            'status' => '02',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment([]);
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
            'createTime' => '20170816191151',
            'status' => '02',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => 'POWEIISSOHANDSOME',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為resultCode不等於00
     */
    public function testReturnResultCodeNotEqualZreoZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付平台連線錯誤',
            180130
        );

        $sourceData = [
            'createTime' => '20170816191151',
            'status' => '00',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '99',
            'resultDesc' => '支付平台連線錯誤',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => '3C45CE57AD7E1F276A3B53EACCBB0C29',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為resCode不等於00
     */
    public function testReturnResCodeNotEqualZreoZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失敗',
            180130
        );

        $sourceData = [
            'createTime' => '20170816191151',
            'status' => '00',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '99',
            'resDesc' => '交易失敗',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => '54865F3BB1C8BD90E937433F7E5BAA8E',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $sourceData = [
            'createTime' => '20170816191151',
            'status' => '00',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => '19BAE568EADD258A583F4C8374718971',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單支付中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $sourceData = [
            'createTime' => '20170816191151',
            'status' => '01',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => '5CEF0F8D48241BEA14F0784A917FEF29',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment([]);
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
            'createTime' => '20170816191151',
            'status' => '99',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => 'D29CFF4194F5DEC8017B88FD37BE11A3',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment([]);
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
            'createTime' => '20170816191151',
            'status' => '02',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => '84151123C74133EACAC2515428318E2B',
        ];

        $entry = ['id' => '201704100000002210'];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment($entry);
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
            'createTime' => '20170816191151',
            'status' => '02',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => '84151123C74133EACAC2515428318E2B',
        ];

        $entry = [
            'id' => '201708180000009453',
            'amount' => '1.01',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'createTime' => '20170816191151',
            'status' => '02',
            'nonceStr' => 'powieishandsome',
            'outTradeNo' => '201708180000009453',
            'productDesc' => 'php1test',
            'orderNo' => 'p2017081619115500122054',
            'branchId' => '9527',
            'resultCode' => '00',
            'resultDesc' => '成功',
            'resCode' => '00',
            'resDesc' => '成功',
            'payType' => '10',
            'orderAmt' => '100',
            'sign' => '84151123C74133EACAC2515428318E2B',
        ];

        $entry = [
            'id' => '201708180000009453',
            'amount' => '1',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->verifyOrderPayment($entry);

        $this->assertEquals('{"resCode":"00","resDesc":"SUCCESS"}', $xinMaPay->getMsg());
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

        $xinMaPay = new XinMaPay();
        $xinMaPay->paymentTracking();
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

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->paymentTracking();
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
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回缺少resultCode
     */
    public function testTrackingReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE",' .
            '"sign":"DB1DEAE50B72042D82A79DC9D1CA4C7C"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');


        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數resultCode不等於00
     */
    public function testTrackingReturnResultCodeNotEqualZeroZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付平台交易不穩定',
            180130
        );

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"支付平台交易不穩定","outTradeNo":"201708180000009453",' .
            '"sign":"021D4F4ABDCAB808213D109A15802C2C","productDesc":"php1test","orderNo":' .
            '"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"99","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數resCode不等於00
     */
    public function testTrackingReturnResCodeNotEqualZeroZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失敗',
            180130
        );

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453",' .
            '"sign":"D0D6405B166C06BEEF8A55673310605B","productDesc":"php1test","orderNo":' .
            '"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"99","attach_content":"","resDesc":"交易失敗","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
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

        $result = '{"status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE",' .
            '"resultCode":"00","resCode":"00","resultDesc":"成功","resDesc":"成功"' .
            '"sign":"A15DA8F0D3FC7F9AF700A82A9FB2D5C7"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');


        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
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

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","productDesc":"php1test",' .
            '"orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
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

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"2EC0336E189FBB9034BC4A8DE39AAF95",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
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

        $result = '{"createTime":"20170816191151","status":"00","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"48468858F12E63794CEDD92A09834180",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單處理中
     */
    public function testTrackingReturnWithOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '{"createTime":"20170816191151","status":"01","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"A59DB67CE2BB714BBD2C13E3368E6105",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
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

        $result = '{"createTime":"20170816191151","status":"05","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"1D0CA427D2A6531D55E97F54EFDB6B5B",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數缺少outTradeNo
     */
    public function testTrackingReturnWithoutOutTradeNo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","sign":"FCEB958BC3407B72D956DF85419605CF",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
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

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"2C287F37CE91E6B407379A997E153B9E",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009454',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單金額錯誤
     */
    public function testTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"2C287F37CE91E6B407379A997E153B9E",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'amount' => 1.2,
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"2C287F37CE91E6B407379A997E153B9E",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'amount' => 1.01,
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setContainer($this->container);
        $xinMaPay->setClient($this->client);
        $xinMaPay->setResponse($response);
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTracking();
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

        $xinMaPay = new XinMaPay();
        $xinMaPay->getPaymentTrackingData();
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

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->getPaymentTrackingData();
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
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $trackingData = $xinMaPay->getPaymentTrackingData();

        $form = json_decode($trackingData['form'], true);

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/jhpayment', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('200003', $form['messageid']);
        $this->assertEquals('9527', $form['branch_id']);
        $this->assertEquals('201708180000009453', $form['out_trade_no']);
        $this->assertTrue(isset($form['nonce_str']));
        $this->assertTrue(isset($form['sign']));
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals(7301, $trackingData['headers']['Port']);
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

        $xinMaPay = new XinMaPay();
        $xinMaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少resultCode
     */
    public function testPaymentTrackingVerifyWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '{"status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE",' .
            '"sign":"DB1DEAE50B72042D82A79DC9D1CA4C7C"}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果回傳參數resultCode不等於00
     */
    public function testTrackingVerifyReturnResultCodeNotEqualZeroZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付平台交易不穩定',
            180130
        );

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"支付平台交易不穩定","outTradeNo":"201708180000009453",' .
            '"sign":"021D4F4ABDCAB808213D109A15802C2C","productDesc":"php1test","orderNo":' .
            '"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"99","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果回傳參數resCode不等於00
     */
    public function testTrackingVerifyReturnResCodeNotEqualZeroZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失敗',
            180130
        );

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453",' .
            '"sign":"D0D6405B166C06BEEF8A55673310605B","productDesc":"php1test","orderNo":' .
            '"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"99","attach_content":"","resDesc":"交易失敗","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
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
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.9527.com',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
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

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","productDesc":"php1test",' .
            '"orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
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

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"2EC0336E189FBB9034BC4A8DE39AAF95",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
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

        $result = '{"createTime":"20170816191151","status":"00","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"48468858F12E63794CEDD92A09834180",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'amount' => '100',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果訂單處理中
     */
    public function testTrackingVerifyReturnWithOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '{"createTime":"20170816191151","status":"01","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"A59DB67CE2BB714BBD2C13E3368E6105",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果支付失敗
     */
    public function testTrackingVerifyReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '{"createTime":"20170816191151","status":"05","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"1D0CA427D2A6531D55E97F54EFDB6B5B",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'amount' => '100',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
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

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"2C287F37CE91E6B407379A997E153B9E",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201707030000000105',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢返回訂單金額錯誤
     */
    public function testTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"2C287F37CE91E6B407379A997E153B9E",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'amount' => '100',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $result = '{"createTime":"20170816191151","status":"02","nonceStr":"ldVoDlFYyJtkwILKYcYJZSFTk2NlRdkE"' .
            ',"resultDesc":"成功","outTradeNo":"201708180000009453","sign":"2C287F37CE91E6B407379A997E153B9E",' .
            '"productDesc":"php1test","orderNo":"p2017081619115500122054","branchId":"9527","resultCode"' .
            ':"00","resCode":"00","attach_content":"","resDesc":"成功","productName":"php1test","orderAmt":101}';

        $sourceData = [
            'number' => '9527',
            'amount' => '1.01',
            'orderId' => '201708180000009453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinMaPay = new XinMaPay();
        $xinMaPay->setPrivateKey('test');
        $xinMaPay->setOptions($sourceData);
        $xinMaPay->paymentTrackingVerify();
    }
}
