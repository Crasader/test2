<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\RuiJieTong;
use Buzz\Message\Response;

class RuiJieTongTest extends DurianTestCase
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

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->getVerifyData();
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

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->getVerifyData();
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
            'number' => 'RJT201711190005',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201712080000007926',
            'notify_url' => 'http://kai0517.netii.net/',
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->getVerifyData();
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
            'number' => 'RJT201711190005',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201712080000007926',
            'notify_url' => 'http://kai0517.netii.net/',
            'verify_url' => '',
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->getVerifyData();
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
            'merNo' => 'RJT201711190005',
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
            'number' => 'RJT201711190005',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201712080000007926',
            'notify_url' => 'http://kai0517.netii.net/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setContainer($this->container);
        $ruiJieTong->setClient($this->client);
        $ruiJieTong->setResponse($response);
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->getVerifyData();
    }

    /**
     * 測試支付失敗
     */
    public function testPayFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '无可用通道!',
            180130
        );

        $result = [
            'stateCode' => '99',
            'msg' => '无可用通道!',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'RJT201711190005',
            'paymentVendorId' => '1090',
            'amount' => '0.1',
            'orderId' => '201712080000007926',
            'notify_url' => 'http://kai0517.netii.net/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setContainer($this->container);
        $ruiJieTong->setClient($this->client);
        $ruiJieTong->setResponse($response);
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->getVerifyData();
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
            'merNo' => 'RJT201711190005',
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
            'number' => 'RJT201711190005',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201712080000007926',
            'notify_url' => 'http://kai0517.netii.net/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setContainer($this->container);
        $ruiJieTong->setClient($this->client);
        $ruiJieTong->setResponse($response);
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'merNo' => 'RJT201711190005',
            'msg' => '提交成功',
            'orderNum' => '201704110000002221',
            'qrcodeUrl' => 'weixin://wxpay/bizpayurl?pr=SKdmLVH',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'RJT201711190005',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201712080000007926',
            'notify_url' => 'http://kai0517.netii.net/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setContainer($this->container);
        $ruiJieTong->setClient($this->client);
        $ruiJieTong->setResponse($response);
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->getVerifyData();
        $data = $ruiJieTong->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=SKdmLVH', $ruiJieTong->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testWapPay()
    {
        $result = [
            'merNo' => 'RJT201711190005',
            'msg' => '提交成功',
            'orderNum' => '201704110000002221',
            'qrcodeUrl' => 'https://qpay.qq.com/qr/54484701',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'RJT201711190005',
            'paymentVendorId' => '1104',
            'amount' => '1',
            'orderId' => '201712080000007926',
            'notify_url' => 'http://kai0517.netii.net/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setContainer($this->container);
        $ruiJieTong->setClient($this->client);
        $ruiJieTong->setResponse($response);
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->getVerifyData();
        $data = $ruiJieTong->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/54484701', $data['post_url']);
        $this->assertEmpty($data['params']);
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

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->verifyOrderPayment([]);
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

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->verifyOrderPayment([]);
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
            'merNo' => 'RJT201711190005',
            'netway' => 'QQ',
            'orderNum' => '201712080000007926',
            'payDate' => '20171208103837',
            'payResult' => '00',
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->verifyOrderPayment([]);
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
            'merNo' => 'RJT201711190005',
            'netway' => 'QQ',
            'orderNum' => '201712080000007926',
            'payDate' => '20171208103837',
            'payResult' => '00',
            'sign' => 'F80CB3B3220C76C36739735392590A40',
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->verifyOrderPayment([]);
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
            'merNo' => 'RJT201711190005',
            'netway' => 'QQ',
            'orderNum' => '201712080000007926',
            'payDate' => '20171208103837',
            'payResult' => '99',
            'sign' => '9E26A1BE86CA7AADE39656DDABDCD00E',
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->verifyOrderPayment([]);
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
            'merNo' => 'RJT201711190005',
            'netway' => 'QQ',
            'orderNum' => '201712080000007926',
            'payDate' => '20171208103837',
            'payResult' => '00',
            'sign' => 'C6B892EBAF75C278BD15FED6734EBE19',
        ];

        $entry = ['id' => '201704100000002210'];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->verifyOrderPayment($entry);
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
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'RJT201711190005',
            'netway' => 'QQ',
            'orderNum' => '201712080000007926',
            'payDate' => '20171208103837',
            'payResult' => '00',
            'sign' => 'C6B892EBAF75C278BD15FED6734EBE19',
        ];

        $entry = [
            'id' => '201712080000007926',
            'amount' => '8',
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'RJT201711190005',
            'netway' => 'QQ',
            'orderNum' => '201712080000007926',
            'payDate' => '20171208103837',
            'payResult' => '00',
            'sign' => 'C6B892EBAF75C278BD15FED6734EBE19',
        ];

        $entry = [
            'id' => '201712080000007926',
            'amount' => '1.00',
        ];

        $ruiJieTong = new RuiJieTong();
        $ruiJieTong->setPrivateKey('test');
        $ruiJieTong->setOptions($sourceData);
        $ruiJieTong->verifyOrderPayment($entry);

        $this->assertEquals('0', $ruiJieTong->getMsg());
    }
}
