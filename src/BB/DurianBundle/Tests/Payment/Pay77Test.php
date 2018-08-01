<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Pay77;
use Buzz\Message\Response;

class Pay77Test extends DurianTestCase
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

        $pay77 = new Pay77();
        $pay77->getVerifyData();
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

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->getVerifyData();
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
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '10',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '9999',
            'ip' => '111.235.135.54',
        ];

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->getVerifyData();
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
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '10',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '1090',
            'ip' => '111.235.135.54',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->getVerifyData();
    }

    /**
     * 測試支付時沒有返回Status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '0.01',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '1090',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.www.777-pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['Error' => '金额最低10元'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay77 = new Pay77();
        $pay77->setContainer($this->container);
        $pay77->setClient($this->client);
        $pay77->setResponse($response);
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->getVerifyData();
    }

    /**
     * 測試支付Status不為1時沒有返回Error
     */
    public function testPayReturnWithoutError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '0.01',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '1090',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.www.777-pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['Status' => '0'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay77 = new Pay77();
        $pay77->setContainer($this->container);
        $pay77->setClient($this->client);
        $pay77->setResponse($response);
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->getVerifyData();
    }

    /**
     * 測試支付時Status不為1
     */
    public function testPayReturnWithStatusNotOne()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '金额最低10元',
            180130
        );

        $options = [
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '0.01',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '1090',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.www.777-pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'Status' => '0',
            'Error' => '金额最低10元',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay77 = new Pay77();
        $pay77->setContainer($this->container);
        $pay77->setClient($this->client);
        $pay77->setResponse($response);
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->getVerifyData();
    }

    /**
     * 測試支付時沒有返回Qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '10',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '1090',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.www.777-pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'UId' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'Amount' => '10.00',
            'Msg' => '201801090000003592',
            'Status' => '1',
            'OrderNo' => '201801091557418683657',
            'Sh_OrderNo' => '201801090000003592',
            'sign' => 'FC75F5E528ABA30B7E3183931B22C3EC',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay77 = new Pay77();
        $pay77->setContainer($this->container);
        $pay77->setClient($this->client);
        $pay77->setResponse($response);
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->getVerifyData();
    }

    /**
     * 測試QQ手機支付
     */
    public function testQQPhonePay()
    {
        $options = [
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '10',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '1104',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.www.777-pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'UId' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'Amount' => '10.00',
            'Msg' => '201801090000003592',
            'Status' => '1',
            'OrderNo' => '201801091557418683657',
            'Sh_OrderNo' => '201801090000003592',
            'Qrcode' => 'https://qpay.qq.com/qr/643e3b2d',
            'sign' => 'FC75F5E528ABA30B7E3183931B22C3EC',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setContainer($this->container);
        $pay77->setClient($this->client);
        $pay77->setResponse($response);
        $pay77->setOptions($options);
        $data = $pay77->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $pay77->getPayMethod());
        $this->assertEquals('https://qpay.qq.com/qr/643e3b2d', $data['post_url']);
    }

    /**
     * 測試二維支付
     */
    public function testQRCodeScan()
    {
        $options = [
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '10',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '1090',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.www.777-pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'UId' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'Amount' => '10.00',
            'Msg' => '201801090000003592',
            'Status' => '1',
            'OrderNo' => '201801091557418683657',
            'Sh_OrderNo' => '201801090000003592',
            'Qrcode' => 'https://qpay.qq.com/qr/643e3b2d',
            'sign' => 'FC75F5E528ABA30B7E3183931B22C3EC',
        ];


        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setContainer($this->container);
        $pay77->setClient($this->client);
        $pay77->setResponse($response);
        $pay77->setOptions($options);
        $data = $pay77->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/643e3b2d', $pay77->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'amount' => '10',
            'orderId' => '201801090000003587',
            'paymentVendorId' => '1098',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.www.777-pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'UId' => '8d1d7255-1409-4669-8ad6-a11f6465e05e',
            'Amount' => '10.00',
            'Msg' => '201801090000003592',
            'Status' => '1',
            'OrderNo' => '201801091557418683657',
            'Sh_OrderNo' => '201801090000003592',
            'Qrcode' => 'https://qpay.qq.com/qr/643e3b2d',
            'sign' => 'FC75F5E528ABA30B7E3183931B22C3EC',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setContainer($this->container);
        $pay77->setClient($this->client);
        $pay77->setResponse($response);
        $pay77->setOptions($options);
        $data = $pay77->getVerifyData();

        $this->assertEquals('8d1d7255-1409-4669-8ad6-a11f6465e05e', $data['UId']);
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

        $pay77 = new Pay77();
        $pay77->verifyOrderPayment([]);
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

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->verifyOrderPayment([]);
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
            'Msg' => '201801090000003587',
            'OrderAmount' => '10.0',
            'OrderNo' => '201801091213500297929',
            'TimeEnd' => '',
        ];

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->verifyOrderPayment([]);
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
            'Msg' => '201801090000003587',
            'OrderAmount' => '10.0',
            'OrderNo' => '201801091213500297929',
            'TimeEnd' => '',
            'sign' => 'E720F2A53B39079DD24BF3AA5C88E158',
        ];

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->verifyOrderPayment([]);
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
            'Msg' => '201801090000003587',
            'OrderAmount' => '10.0',
            'OrderNo' => '201801091213500297929',
            'TimeEnd' => '',
            'sign' => '1A9D162E2DE5D63EF132A55ED4931FE2',
        ];

        $entry = [
            'id' => '201801090000003588',
            'amount' => '10',
        ];

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->verifyOrderPayment($entry);
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
            'Msg' => '201801090000003587',
            'OrderAmount' => '10.0',
            'OrderNo' => '201801091213500297929',
            'TimeEnd' => '',
            'sign' => '1A9D162E2DE5D63EF132A55ED4931FE2',
        ];

        $entry = [
            'id' => '201801090000003587',
            'amount' => '100',
        ];

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'Msg' => '201801090000003587',
            'OrderAmount' => '10.0',
            'OrderNo' => '201801091213500297929',
            'TimeEnd' => '',
            'sign' => '1A9D162E2DE5D63EF132A55ED4931FE2',
        ];

        $entry = [
            'id' => '201801090000003587',
            'amount' => '10',
        ];

        $pay77 = new Pay77();
        $pay77->setPrivateKey('test');
        $pay77->setOptions($options);
        $pay77->verifyOrderPayment($entry);

        $this->assertEquals('0000', $pay77->getMsg());
    }
}
