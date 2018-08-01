<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\XingHang;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class XingHangTest extends DurianTestCase
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
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xingHang = new XingHang();
        $xingHang->getVerifyData();
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

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->getVerifyData();
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
            'orderId' => '201711130000007537',
            'amount' => '1.00',
        ];

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->getVerifyData();
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
            'orderId' => '201711130000007537',
            'amount' => '1.00',
        ];

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $requestData = $xingHang->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('XingHang.online.interface', $requestData['method']);
        $this->assertEquals('9527', $requestData['partner']);
        $this->assertEquals('ICBC', $requestData['banktype']);
        $this->assertEquals('1.00', $requestData['paymoney']);
        $this->assertEquals('201711130000007537', $requestData['ordernumber']);
        $this->assertEquals('http://orz.com/', $requestData['callbackurl']);
        $this->assertEquals('', $requestData['hrefbackurl']);
        $this->assertEquals('', $requestData['attach']);
        $this->assertEquals('0', $requestData['isshow']);
        $this->assertEquals('baffc888abb3cf5384969eed49eda06a', $requestData['sign']);
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

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->getVerifyData();
    }

    /**
     * 測試二維支付但取得返回參數失敗
     */
    public function testPayWithQrcodeButGetPayParametersFailed()
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
            'verify_url' => 'payment.http.soso.xinghjk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $xingHang = new XingHang();
        $xingHang->setContainer($this->container);
        $xingHang->setClient($this->client);
        $xingHang->setResponse($response);
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->getVerifyData();
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
            'verify_url' => 'payment.http.soso.xinghjk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $xingHang = new XingHang();
        $xingHang->setContainer($this->container);
        $xingHang->setClient($this->client);
        $xingHang->setResponse($response);
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->getVerifyData();
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
            'verify_url' => 'payment.http.soso.xinghjk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $xingHang = new XingHang();
        $xingHang->setContainer($this->container);
        $xingHang->setClient($this->client);
        $xingHang->setResponse($response);
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->getVerifyData();
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
            'verify_url' => 'payment.http.soso.xinghjk.com',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $xingHang = new XingHang();
        $xingHang->setContainer($this->container);
        $xingHang->setClient($this->client);
        $xingHang->setResponse($response);
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $encodeData = $xingHang->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('https://qpay.qq.com/qr/57827a0e', $xingHang->getQrcode());
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

        $xingHang = new XingHang();
        $xingHang->verifyOrderPayment([]);
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

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->verifyOrderPayment([]);
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
            'partner' => '33000025',
            'ordernumber' => '201711130000007537',
            'orderstatus' => '1',
            'paymoney' => '1.000',
            'sysnumber' => 'XH171113110374600',
            'attach' => '',
        ];

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->verifyOrderPayment([]);
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
            'partner' => '33000025',
            'ordernumber' => '201711130000007537',
            'orderstatus' => '1',
            'paymoney' => '1.000',
            'sysnumber' => 'XH171113110374600',
            'attach' => '',
            'sign' => '6313c7030885a4ad528e30ff9f5e64b6',
        ];

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->verifyOrderPayment([]);
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
            'partner' => '33000025',
            'ordernumber' => '201711130000007537',
            'orderstatus' => '9',
            'paymoney' => '1.000',
            'sysnumber' => 'XH171113110374600',
            'attach' => '',
            'sign' => '0c6c56d77c3d724f4ece2a66304f9109',
        ];

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->verifyOrderPayment([]);
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
            'partner' => '33000025',
            'ordernumber' => '201711130000007537',
            'orderstatus' => '1',
            'paymoney' => '1.000',
            'sysnumber' => 'XH171113110374600',
            'attach' => '',
            'sign' => 'f5fe3bf42e04f4c4e2026a4661763eac',
        ];

        $entry = ['id' => '201707030000000105'];

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->verifyOrderPayment($entry);
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
            'partner' => '33000025',
            'ordernumber' => '201711130000007537',
            'orderstatus' => '1',
            'paymoney' => '1.000',
            'sysnumber' => 'XH171113110374600',
            'attach' => '',
            'sign' => 'f5fe3bf42e04f4c4e2026a4661763eac',
        ];

        $entry = [
            'id' => '201711130000007537',
            'amount' => '2.0000',
        ];

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'partner' => '33000025',
            'ordernumber' => '201711130000007537',
            'orderstatus' => '1',
            'paymoney' => '1.000',
            'sysnumber' => 'XH171113110374600',
            'attach' => '',
            'sign' => 'f5fe3bf42e04f4c4e2026a4661763eac',
        ];

        $entry = [
            'id' => '201711130000007537',
            'amount' => '1.0000',
        ];

        $xingHang = new XingHang();
        $xingHang->setPrivateKey('test');
        $xingHang->setOptions($sourceData);
        $xingHang->verifyOrderPayment($entry);

        $this->assertEquals('ok', $xingHang->getMsg());
    }
}
