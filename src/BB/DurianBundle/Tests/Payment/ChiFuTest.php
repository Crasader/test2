<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChiFu;
use Buzz\Message\Response;

class ChiFuTest extends DurianTestCase
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

        $chiFu = new ChiFu();
        $chiFu->getVerifyData();
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

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->getVerifyData();
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
            'number' => 'Mer201703300214',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201704100000002214',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
        ];

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->getVerifyData();
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
            'number' => 'Mer201703300214',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201704100000002214',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->getVerifyData();
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
            'merNo' => 'Mer201703300214',
            'msg' => '提交成功',
            'orderNum' => '201704110000002221',
            'qrcodeUrl' => 'weixin://wxpay/bizpayurl?pr=S5V6QpL',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'Mer201703300214',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201704100000002214',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chiFu = new ChiFu();
        $chiFu->setContainer($this->container);
        $chiFu->setClient($this->client);
        $chiFu->setResponse($response);
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->getVerifyData();
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
            'number' => 'Mer201703300214',
            'paymentVendorId' => '1090',
            'amount' => '0.1',
            'orderId' => '201704100000002214',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chiFu = new ChiFu();
        $chiFu->setContainer($this->container);
        $chiFu->setClient($this->client);
        $chiFu->setResponse($response);
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->getVerifyData();
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
            'merNo' => 'Mer201703300214',
            'msg' => '提交成功',
            'orderNum' => '201704110000002221',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'Mer201703300214',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201704100000002214',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chiFu = new ChiFu();
        $chiFu->setContainer($this->container);
        $chiFu->setClient($this->client);
        $chiFu->setResponse($response);
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'merNo' => 'Mer201703300214',
            'msg' => '提交成功',
            'orderNum' => '201704110000002221',
            'qrcodeUrl' => 'weixin://wxpay/bizpayurl?pr=S5V6QpL',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'Mer201703300214',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201704100000002214',
            'notify_url' => 'http://kai0517.netii.net/',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chiFu = new ChiFu();
        $chiFu->setContainer($this->container);
        $chiFu->setClient($this->client);
        $chiFu->setResponse($response);
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->getVerifyData();
        $data = $chiFu->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=S5V6QpL', $chiFu->getQrcode());
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

        $chiFu = new ChiFu();
        $chiFu->verifyOrderPayment([]);
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

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->verifyOrderPayment([]);
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
            'merNo' => 'Mer201703300214',
            'netway' => 'WX',
            'orderNum' => '201704100000002214',
            'payResult' => '00',
            'payDate' => '2017-04-10 14:25:38',
        ];

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->verifyOrderPayment([]);
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
            'merNo' => 'Mer201703300214',
            'netway' => 'WX',
            'orderNum' => '201704100000002214',
            'payResult' => '00',
            'payDate' => '2017-04-10 14:25:38',
            'sign' => '0B99034CAEFF71D3E88CC49647C3108A',
        ];

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->verifyOrderPayment([]);
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

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->verifyOrderPayment([]);
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

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->verifyOrderPayment($entry);
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

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->verifyOrderPayment($entry);
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

        $chiFu = new ChiFu();
        $chiFu->setPrivateKey('test');
        $chiFu->setOptions($sourceData);
        $chiFu->verifyOrderPayment($entry);

        $this->assertEquals('0', $chiFu->getMsg());
    }
}
