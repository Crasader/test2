<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\IpPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class IpPayTest extends DurianTestCase
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

        $ipPay = new IpPay();
        $ipPay->getVerifyData();
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

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->getVerifyData();
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
            'number' => 'ippaytest',
            'amount' => '10',
            'orderId' => '201711170000002383',
            'username' => 'php1test',
            'paymentVendorId' => '999',
            'notify_url' => 'http://pay.my/pay/pay.php',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => 'ippaytest',
            'amount' => '10',
            'orderId' => '201711170000002383',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'verify_url' => '',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'ippaytest',
            'amount' => '10',
            'orderId' => '201711170000002383',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'verify_url' => 'payment.http.api.ippay.xyz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"amount":"10","goodsName":"php1test","merchno":"ippaytest",' .
            '"notifyUrl":"https://tingliu.000webhostapp.com/pay/return.php","payType":"2",' .
            '"signature":"f54ef1ed0791ca484eebcda561fe9d68","traceno":"201711170000002384",' .
            '"message":"\u4ea4\u6613\u6210\u529f","barCode":"weixin://wxpay/bizpayurl?pr=eNFhT4K"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ipPay = new IpPay();
        $ipPay->setContainer($this->container);
        $ipPay->setClient($this->client);
        $ipPay->setResponse($response);
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '参数内容不正确，入款金额不得低于10元',
            180130
        );

        $options = [
            'number' => 'ippaytest',
            'amount' => '10',
            'orderId' => '201711170000002383',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'verify_url' => 'payment.http.api.ippay.xyz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"message":"参数内容不正确，入款金额不得低于10元","respCode":"F5"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ipPay = new IpPay();
        $ipPay->setContainer($this->container);
        $ipPay->setClient($this->client);
        $ipPay->setResponse($response);
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'ippaytest',
            'amount' => '10',
            'orderId' => '201711170000002383',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'verify_url' => 'payment.http.api.ippay.xyz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"amount":"10","goodsName":"php1test","merchno":"ippaytest",' .
            '"notifyUrl":"https://tingliu.000webhostapp.com/pay/return.php","payType":"2",' .
            '"signature":"f54ef1ed0791ca484eebcda561fe9d68","traceno":"201711170000002384",' .
            '"respCode":"00","message":"\u4ea4\u6613\u6210\u529f"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ipPay = new IpPay();
        $ipPay->setContainer($this->container);
        $ipPay->setClient($this->client);
        $ipPay->setResponse($response);
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => 'ippaytest',
            'amount' => '10',
            'orderId' => '201711170000002383',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'verify_url' => 'payment.http.api.ippay.xyz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"amount":"10","goodsName":"php1test","merchno":"ippaytest",' .
            '"notifyUrl":"https://tingliu.000webhostapp.com/pay/return.php","payType":"2",' .
            '"signature":"f54ef1ed0791ca484eebcda561fe9d68","traceno":"201711170000002384",' .
            '"respCode":"00","message":"\u4ea4\u6613\u6210\u529f",' .
            '"barCode":"weixin://wxpay/bizpayurl?pr=eNFhT4K"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ipPay = new IpPay();
        $ipPay->setContainer($this->container);
        $ipPay->setClient($this->client);
        $ipPay->setResponse($response);
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $data = $ipPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=eNFhT4K', $ipPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => 'ippaytest',
            'amount' => '10',
            'orderId' => '201711170000002383',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'verify_url' => 'payment.http.api.ippay.xyz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $verifyData = $ipPay->getVerifyData();

        $this->assertEquals('ippaytest', $verifyData['merchno']);
        $this->assertEquals('10', $verifyData['amount']);
        $this->assertEquals('201711170000002383', $verifyData['traceno']);
        $this->assertEquals('3002', $verifyData['bankCode']);
        $this->assertEquals('http://pay.my/pay/pay.php', $verifyData['notifyUrl']);
        $this->assertEquals('c0c0a650ec45456af3bf7b1bbb362e8b', $verifyData['signature']);
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

        $ipPay = new IpPay();
        $ipPay->verifyOrderPayment([]);
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

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'amount' => '10.0000',
            'merchno' => 'ippaytest',
            'orderno' => '2017111749100499',
            'payType' => '0',
            'status' => '1',
            'traceno' => '201711170000002383',
            'transDate' => '2017-11-17',
            'transTime' => '10:57:24',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->verifyOrderPayment([]);
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
            'amount' => '10.0000',
            'merchno' => 'ippaytest',
            'orderno' => '2017111749100499',
            'payType' => '0',
            'status' => '1',
            'traceno' => '201711170000002383',
            'transDate' => '2017-11-17',
            'transTime' => '10:57:24',
            'signature' => '342f1af120e97a08abbfca9789b30682',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'amount' => '10.0000',
            'merchno' => 'ippaytest',
            'orderno' => '2017111749100499',
            'payType' => '0',
            'status' => '0',
            'traceno' => '201711170000002383',
            'transDate' => '2017-11-17',
            'transTime' => '10:57:24',
            'signature' => '9c408aa9b89810a31df910cf8c94336c',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'amount' => '10.0000',
            'merchno' => 'ippaytest',
            'orderno' => '2017111749100499',
            'payType' => '0',
            'status' => '2',
            'traceno' => '201711170000002383',
            'transDate' => '2017-11-17',
            'transTime' => '10:57:24',
            'signature' => '18b92c3ff71597f1869908a1548797ff',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->verifyOrderPayment([]);
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
            'amount' => '10.0000',
            'merchno' => 'ippaytest',
            'orderno' => '2017111749100499',
            'payType' => '0',
            'status' => '1',
            'traceno' => '201711170000002383',
            'transDate' => '2017-11-17',
            'transTime' => '10:57:24',
            'signature' => '0e3b823b69de1ee1d45f8c8ef1fef831',
        ];

        $entry = [
            'id' => '201711170000002384',
            'amount' => '10',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'amount' => '10.0000',
            'merchno' => 'ippaytest',
            'orderno' => '2017111749100499',
            'payType' => '0',
            'status' => '1',
            'traceno' => '201711170000002383',
            'transDate' => '2017-11-17',
            'transTime' => '10:57:24',
            'signature' => '0e3b823b69de1ee1d45f8c8ef1fef831',
        ];

        $entry = [
            'id' => '201711170000002383',
            'amount' => '100',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'amount' => '10.0000',
            'merchno' => 'ippaytest',
            'orderno' => '2017111749100499',
            'payType' => '0',
            'status' => '1',
            'traceno' => '201711170000002383',
            'transDate' => '2017-11-17',
            'transTime' => '10:57:24',
            'signature' => '0e3b823b69de1ee1d45f8c8ef1fef831',
        ];

        $entry = [
            'id' => '201711170000002383',
            'amount' => '10',
        ];

        $ipPay = new IpPay();
        $ipPay->setPrivateKey('test');
        $ipPay->setOptions($options);
        $ipPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $ipPay->getMsg());
    }
}
