<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KcPayScan;
use Buzz\Message\Response;

class KcPayScanTest extends DurianTestCase
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

        $kcPayScan = new KcPayScan();
        $kcPayScan->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '100',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->getVerifyData();
    }

    /**
     * 測試支付，但沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->getVerifyData();
    }

    /**
     * 測試支付，但回傳結果失敗
     */
    public function testPayButReturnFail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '系统维护中，请联系管理员',
            180130
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => 'payment.http.api.kcpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '系统维护中，请联系管理员';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kcPayScan = new KcPayScan();
        $kcPayScan->setContainer($this->container);
        $kcPayScan->setClient($this->client);
        $kcPayScan->setResponse($response);
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->getVerifyData();
    }

    /**
     * 測試支付，但回傳結果沒有掃碼
     */
    public function testPayButNoData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => 'payment.http.api.kcpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'http://api.kcpay.com/MakeQRCode.aspx?data=';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kcPayScan = new KcPayScan();
        $kcPayScan->setContainer($this->container);
        $kcPayScan->setClient($this->client);
        $kcPayScan->setResponse($response);
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => 'payment.http.api.kcpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'http://api.kcpay.com/MakeQRCode.aspx?data=weixin://wxpay/bizpayurl?pr=uLNm7Mr';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $kcPayScan = new KcPayScan();
        $kcPayScan->setContainer($this->container);
        $kcPayScan->setClient($this->client);
        $kcPayScan->setResponse($response);
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $requestData = $kcPayScan->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=uLNm7Mr', $kcPayScan->getQrcode());
    }

    /**
     * 測試支付寶手機支付沒有帶入postUrl的情況
     */
    public function testAliPayWapWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1098',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'postUrl' => '',
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->getVerifyData();
    }

    /**
     * 測試支付寶手機支付
     */
    public function testPayWithAlipayWap()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1098',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'postUrl' => 'http://pay.9vpay.com/PayBank.aspx',
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $requestData = $kcPayScan->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals('100.00', $requestData['paymoney']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('ALIPAYWAP', $requestData['banktype']);
        $this->assertEquals('87fe22aed7e369e12723b79862c31e9a', $requestData['sign']);
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

        $kcPayScan = new KcPayScan();
        $kcPayScan->verifyOrderPayment([]);
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

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->verifyOrderPayment([]);
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

        $options = [
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->verifyOrderPayment([]);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '123456789',
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->verifyOrderPayment([]);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '2',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '287d48b26a2ac57654e0fc7d2984e76d',
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->verifyOrderPayment([]);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '5182795a6a2084611aaf8bbe3b8e6756',
        ];

        $entry = ['id' => '201503220000000555'];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->verifyOrderPayment($entry);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '5182795a6a2084611aaf8bbe3b8e6756',
        ];

        $entry = [
            'id' => '201609300000008335',
            'amount' => '15.00',
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '5182795a6a2084611aaf8bbe3b8e6756',
        ];

        $entry = [
            'id' => '201609300000008335',
            'amount' => '0.01',
        ];

        $kcPayScan = new KcPayScan();
        $kcPayScan->setPrivateKey('test');
        $kcPayScan->setOptions($options);
        $kcPayScan->verifyOrderPayment($entry);

        $this->assertEquals('ok', $kcPayScan->getMsg());
    }
}
