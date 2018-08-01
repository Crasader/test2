<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShunFu;
use Buzz\Message\Response;

class ShunFuTest extends DurianTestCase
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

        $shunFu = new ShunFu();
        $shunFu->getVerifyData();
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

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setOptions($sourceData);
        $shunFu->getVerifyData();
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
            'number' => 'SF170428143821195',
            'paymentVendorId' => '7',
            'amount' => '2.00',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setOptions($sourceData);
        $shunFu->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => 'SF170428143821195',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setOptions($sourceData);
        $shunFu->getVerifyData();
    }

    /**
     * 測試支付時返回缺少參數resultCode
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'resultMsg' => '不能低于:1元',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'SF170428143821195',
            'paymentVendorId' => '1090',
            'amount' => '0.1',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setContainer($this->container);
        $shunFu->setClient($this->client);
        $shunFu->setResponse($response);
        $shunFu->setOptions($sourceData);
        $shunFu->getVerifyData();
    }

    /**
     * 測試支付時返回結果失敗
     */
    public function testPayReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '不能低于:1元',
            180130
        );

        $result = [
            'resultCode' => '99',
            'resultMsg' => '不能低于:1元',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'SF170428143821195',
            'paymentVendorId' => '1090',
            'amount' => '0.1',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setContainer($this->container);
        $shunFu->setClient($this->client);
        $shunFu->setResponse($response);
        $shunFu->setOptions($sourceData);
        $shunFu->getVerifyData();
    }

    /**
     * 測試支付時返回缺少參數qrcodeInfo
     */
    public function testPayReturnWithoutQrcodeInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => 'SF170428143821195',
            'orderNo' => '201703210000001931',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => 'B219D715176D0A9FDE9E3A22506727D3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => 'SF170428143821195',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setContainer($this->container);
        $shunFu->setClient($this->client);
        $shunFu->setResponse($response);
        $shunFu->setOptions($sourceData);
        $shunFu->getVerifyData();
    }

    /**
     * 測試手機支付時返回qrcodeInfo缺少Path
     */
    public function testPhonePayGetEncodeReturnQrcodeInfoWithoutPath()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => 'SF170428143821195',
            'paymentVendorId' => '1097',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merNo' => 'SF170428143821195',
            'orderNo' => '201703210000001931',
            'qrcodeInfo' => 'https://qr.alipay.com',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => 'B219D715176D0A9FDE9E3A22506727D3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setContainer($this->container);
        $shunFu->setClient($this->client);
        $shunFu->setResponse($response);
        $shunFu->setOptions($sourceData);
        $shunFu->getVerifyData();
    }

    /**
     * 測試QQ_手機支付
     */
    public function testQQPhone()
    {
        $sourceData = [
            'number' => 'SF170428143821195',
            'paymentVendorId' => '1104',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merNo' => 'SF170428143821195',
            'orderNo' => '201703210000001931',
            'qrcodeInfo' => 'http://orangepaycenter.com:8888/sifang-pay/hy/toRedirectUrl?orderNo=201812141005161074',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => 'B219D715176D0A9FDE9E3A22506727D3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setContainer($this->container);
        $shunFu->setClient($this->client);
        $shunFu->setResponse($response);
        $shunFu->setOptions($sourceData);
        $requestData = $shunFu->getVerifyData();

        $this->assertEquals('http://orangepaycenter.com:8888/sifang-pay/hy/toRedirectUrl', $requestData['post_url']);
        $this->assertEquals('201812141005161074', $requestData['params']['orderNo']);
        $this->assertEquals('GET', $shunFu->getPayMethod());
    }

    /**
     * 測試微信_手機支付返回的qrcodeInfo有query
     */
    public function testPayPhoneGetEncodeReturnQrcodeInfoWithQuery()
    {
        $sourceData = [
            'number' => 'SF170428143821195',
            'paymentVendorId' => '1097',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $qrcodeInfo = 'https://www.joinpay.com/trade/uniPayApi.action?p0_Version=1.0&p1_MerchantNo=888100048161720';
        $result = [
            'merNo' => 'SF170428143821195',
            'orderNo' => '201703210000001931',
            'qrcodeInfo' => $qrcodeInfo,
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => 'B219D715176D0A9FDE9E3A22506727D3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setContainer($this->container);
        $shunFu->setClient($this->client);
        $shunFu->setResponse($response);
        $shunFu->setOptions($sourceData);
        $requestData = $shunFu->getVerifyData();

        $this->assertEquals('https://www.joinpay.com/trade/uniPayApi.action', $requestData['post_url']);
        $this->assertEquals('1.0', $requestData['params']['p0_Version']);
        $this->assertEquals('888100048161720', $requestData['params']['p1_MerchantNo']);
    }

    /**
     * 測試微信條碼
     */
    public function testScan()
    {
        $sourceData = [
            'number' => 'SF171226174423542',
            'paymentVendorId' => '1115',
            'amount' => '1',
            'orderId' => '201805030000005097',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merNo' => 'SF171226174423542',
            'orderNo' => '201805030000005097',
            'qrcodeInfo' => 'http://go.rmasak.top/pay/code/3F88496/2000',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => '3F1FF85DBBEC2C0BB42905CF1F4A98BF',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setContainer($this->container);
        $shunFu->setClient($this->client);
        $shunFu->setResponse($response);
        $shunFu->setOptions($sourceData);
        $requestData = $shunFu->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('http://go.rmasak.top/pay/code/3F88496/2000', $shunFu->getQrcode());
    }

    /**
     * 測試微信二維支付
     */
    public function testAlipay()
    {
        $sourceData = [
            'number' => 'SF170428143821195',
            'paymentVendorId' => '1090',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merNo' => 'SF170428143821195',
            'orderNo' => '201703210000001931',
            'qrcodeInfo' => 'https://qr.alipay.com/bax03444e5pme2vyz6te4013',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => 'B219D715176D0A9FDE9E3A22506727D3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->setContainer($this->container);
        $shunFu->setClient($this->client);
        $shunFu->setResponse($response);
        $shunFu->setOptions($sourceData);
        $requestData = $shunFu->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('https://qr.alipay.com/bax03444e5pme2vyz6te4013', $shunFu->getQrcode());
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

        $shunFu = new ShunFu();
        $shunFu->verifyOrderPayment([]);
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

        $shunFu = new ShunFu();
        $shunFu->setPrivateKey('test');
        $shunFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'merNo' => 'SF170428143821195',
            'payNetway' => 'WX',
            'amount' => '100',
            'goodsName' => 'php1test',
            'orderNo' => '201706020000003112',
            'payDate' => '2017-06-02 16:01:52',
            'resultCode' => '00',
        ];

        $shunFu = new ShunFu();
        $shunFu->setOptions($sourceData);
        $shunFu->setPrivateKey('test');
        $shunFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'merNo' => 'SF170428143821195',
            'payNetway' => 'WX',
            'amount' => '100',
            'goodsName' => 'php1test',
            'orderNo' => '201706020000003112',
            'payDate' => '2017-06-02 16:01:52',
            'resultCode' => '00',
            'sign' => 'A76CA5A84579EEC3627C940563E8BF88',
        ];

        $shunFu = new ShunFu();
        $shunFu->setOptions($sourceData);
        $shunFu->setPrivateKey('test');
        $shunFu->verifyOrderPayment([]);
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
            'merNo' => 'SF170428143821195',
            'payNetway' => 'WX',
            'amount' => '100',
            'goodsName' => 'php1test',
            'orderNo' => '201706020000003112',
            'payDate' => '2017-06-02 16:01:52',
            'resultCode' => '99',
            'sign' => '52E9249DF1B2A0A40FE54681FED58810',
        ];

        $shunFu = new ShunFu();
        $shunFu->setOptions($sourceData);
        $shunFu->setPrivateKey('test');
        $shunFu->verifyOrderPayment([]);
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
            'merNo' => 'SF170428143821195',
            'payNetway' => 'WX',
            'amount' => '100',
            'goodsName' => 'php1test',
            'orderNo' => '201706020000003112',
            'payDate' => '2017-06-02 16:01:52',
            'resultCode' => '00',
            'sign' => '0E5BBCE487AB7E1DDBFD0C15D88DD01B',
        ];

        $entry = ['id' => '201703090000001811'];

        $shunFu = new ShunFu();
        $shunFu->setOptions($sourceData);
        $shunFu->setPrivateKey('test');
        $shunFu->verifyOrderPayment($entry);
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
            'merNo' => 'SF170428143821195',
            'payNetway' => 'WX',
            'amount' => '100',
            'goodsName' => 'php1test',
            'orderNo' => '201706020000003112',
            'payDate' => '2017-06-02 16:01:52',
            'resultCode' => '00',
            'sign' => '0E5BBCE487AB7E1DDBFD0C15D88DD01B',
        ];

        $entry = [
            'id' => '201706020000003112',
            'amount' => '0.02',
        ];

        $shunFu = new ShunFu();
        $shunFu->setOptions($sourceData);
        $shunFu->setPrivateKey('test');
        $shunFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'merNo' => 'SF170428143821195',
            'payNetway' => 'WX',
            'amount' => '100',
            'goodsName' => 'php1test',
            'orderNo' => '201706020000003112',
            'payDate' => '2017-06-02 16:01:52',
            'resultCode' => '00',
            'sign' => '0E5BBCE487AB7E1DDBFD0C15D88DD01B',
        ];

        $entry = [
            'id' => '201706020000003112',
            'amount' => '1',
        ];

        $shunFu = new ShunFu();
        $shunFu->setOptions($sourceData);
        $shunFu->setPrivateKey('test');
        $shunFu->verifyOrderPayment($entry);

        $this->assertEquals('000000', $shunFu->getMsg());
    }
}
