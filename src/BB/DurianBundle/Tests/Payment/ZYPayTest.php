<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ZYPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class ZYPayTest extends DurianTestCase
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

        $zYPay = new ZYPay();
        $zYPay->getVerifyData();
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

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->getVerifyData();
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
            'number' => '8000000000022',
            'amount' => '100',
            'orderId' => '201711280000005865',
            'paymentVendorId' => '9453',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
        ];

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->getVerifyData();
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
            'number' => '8000000000022',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => '',
        ];

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回Code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '8000000000022',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zYPay = new ZYPay();
        $zYPay->setContainer($this->container);
        $zYPay->setClient($this->client);
        $zYPay->setResponse($response);
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付金额不能小于10元',
            180130
        );

        $options = [
            'number' => '8000000000022',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'Code' => '1002',
            'Msg' => '支付金额不能小于10元',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zYPay = new ZYPay();
        $zYPay->setContainer($this->container);
        $zYPay->setClient($this->client);
        $zYPay->setResponse($response);
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回Code_url
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '8000000000022',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'Code' => '1000',
            'Msg' => '成功',
            'Data' => [
                'OrderNo' => 'ZF20171128561413808255',
                'OutTradeNo' => '201711280000005869',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zYPay = new ZYPay();
        $zYPay->setContainer($this->container);
        $zYPay->setClient($this->client);
        $zYPay->setResponse($response);
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '8000000000022',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'Code' => '1000',
            'Msg' => '成功',
            'Data' => [
                'OrderNo' => 'ZF20171128561413808255',
                'OutTradeNo' => '201711280000005869',
                'CodeUrl' => 'http://m.zypay.net/Upload/Qrcode/123.jpg',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zYPay = new ZYPay();
        $zYPay->setContainer($this->container);
        $zYPay->setClient($this->client);
        $zYPay->setResponse($response);
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $data = $zYPay->getVerifyData();

        $codeUrl = '<img src="http://m.zypay.net/Upload/Qrcode/123.jpg"/>';

        $this->assertEmpty($data);
        $this->assertEquals($codeUrl, $zYPay->getHtml());
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

        $zYPay = new ZYPay();
        $zYPay->verifyOrderPayment([]);
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

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->verifyOrderPayment([]);
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
            'OrderNo' => 'ZF20171128461407009033',
            'MerchantNo' => '8000000000022',
            'Amount' => '1000',
            'OutTradeNo' => '201711280000005865',
            'RetCode' => '00',
            'RetMsg' => '支付成功',
            'TradeTime' => '2017-11-28 15:05:37',
            'Attach' => 'php1test',
        ];

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->verifyOrderPayment([]);
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
            'OrderNo' => 'ZF20171128461407009033',
            'MerchantNo' => '8000000000022',
            'Amount' => '1000',
            'OutTradeNo' => '201711280000005865',
            'RetCode' => '00',
            'RetMsg' => '支付成功',
            'TradeTime' => '2017-11-28 15:05:37',
            'Attach' => 'php1test',
            'Sign' => '9487',
        ];

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->verifyOrderPayment([]);
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
            'OrderNo' => 'ZF20171128461407009033',
            'MerchantNo' => '8000000000022',
            'Amount' => '1000',
            'OutTradeNo' => '201711280000005865',
            'RetCode' => '01',
            'RetMsg' => '支付失敗',
            'TradeTime' => '2017-11-28 15:05:37',
            'Attach' => 'php1test',
            'Sign' => 'eccc5d2c586e1f62976ea7e912b940cd',
        ];

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->verifyOrderPayment([]);
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
            'OrderNo' => 'ZF20171128461407009033',
            'MerchantNo' => '8000000000022',
            'Amount' => '1000',
            'OutTradeNo' => '201711280000005865',
            'RetCode' => '00',
            'RetMsg' => '支付成功',
            'TradeTime' => '2017-11-28 15:05:37',
            'Attach' => 'php1test',
            'Sign' => '4c9f0b8e376b90418d7a9b87fa2ec2f7',
        ];

        $entry = ['id' => '9453'];

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->verifyOrderPayment($entry);
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
            'OrderNo' => 'ZF20171128461407009033',
            'MerchantNo' => '8000000000022',
            'Amount' => '1000',
            'OutTradeNo' => '201711280000005865',
            'RetCode' => '00',
            'RetMsg' => '支付成功',
            'TradeTime' => '2017-11-28 15:05:37',
            'Attach' => 'php1test',
            'Sign' => '4c9f0b8e376b90418d7a9b87fa2ec2f7',
        ];

        $entry = [
            'id' => '201711280000005865',
            'amount' => '1',
        ];

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'OrderNo' => 'ZF20171128461407009033',
            'MerchantNo' => '8000000000022',
            'Amount' => '1000',
            'OutTradeNo' => '201711280000005865',
            'RetCode' => '00',
            'RetMsg' => '支付成功',
            'TradeTime' => '2017-11-28 15:05:37',
            'Attach' => 'php1test',
            'Sign' => '4c9f0b8e376b90418d7a9b87fa2ec2f7',
        ];

        $entry = [
            'id' => '201711280000005865',
            'amount' => '10',
        ];

        $zYPay = new ZYPay();
        $zYPay->setPrivateKey('test');
        $zYPay->setOptions($options);
        $zYPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $zYPay->getMsg());
    }
}
