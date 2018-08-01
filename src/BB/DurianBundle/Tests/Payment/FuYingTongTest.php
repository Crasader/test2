<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\FuYingTong;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class FuYingTongTest extends DurianTestCase
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

        $fuYingTong = new FuYingTong();
        $fuYingTong->getVerifyData();
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

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->getVerifyData();
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
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://payment/return.php',
        ];

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->getVerifyData();
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
            'number' => '10102',
            'amount' => '1',
            'orderId' => '201803200000010357',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => '',
        ];

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回內容值
     */
    public function testQrcodePayReturnWithoutValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '10102',
            'amount' => '1',
            'orderId' => '201803200000010357',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/x-www-form-urlencoded; charset=UTF-8');

        $fuYingTong = new FuYingTong();
        $fuYingTong->setContainer($this->container);
        $fuYingTong->setClient($this->client);
        $fuYingTong->setResponse($response);
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQrcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '积分不足，请充值后再试！',
            180130
        );

        $options = [
            'number' => '10102',
            'amount' => '1',
            'orderId' => '201803200000010357',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '积分不足，请充值后再试！';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/x-www-form-urlencoded; charset=UTF-8');

        $fuYingTong = new FuYingTong();
        $fuYingTong->setContainer($this->container);
        $fuYingTong->setClient($this->client);
        $fuYingTong->setResponse($response);
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->getVerifyData();
    }

    /**
     * 測試二維支付時返回Code不正確
     */
    public function testQrcodePayReturnCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '10102',
            'amount' => '1',
            'orderId' => '201803200000010357',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'A1B2C3D4E5F6';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/x-www-form-urlencoded; charset=UTF-8');

        $fuYingTong = new FuYingTong();
        $fuYingTong->setContainer($this->container);
        $fuYingTong->setClient($this->client);
        $fuYingTong->setResponse($response);
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '10102',
            'amount' => '1',
            'orderId' => '201803200000010357',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://pay1.cggoon.com',
        ];

        $result = 'E7226F6B10DF359B94AA1DB87A9AB05CCFB4885165439AF3';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/x-www-form-urlencoded; charset=UTF-8');

        $fuYingTong = new FuYingTong();
        $fuYingTong->setContainer($this->container);
        $fuYingTong->setClient($this->client);
        $fuYingTong->setResponse($response);
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $data = $fuYingTong->getVerifyData();

        $this->assertEquals($result, $data['params']['Code']);
        $this->assertEquals('http://payment/return.php', $data['params']['SuccessUrl']);
        $this->assertEquals('http://pay1.cggoon.com/pay/WeChat.aspx', $data['post_url']);
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

        $fuYingTong = new FuYingTong();
        $fuYingTong->verifyOrderPayment([]);
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

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->verifyOrderPayment([]);
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
            'tradeNo' => '1521526137',
            'desc' => '',
            'time' => '2018-03-20 14:08:59',
            'userid' => '201803200000010357',
            'amount' => '1.00',
            'status' => '交易成功',
            'type' => '微信',
        ];

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->verifyOrderPayment([]);
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
            'tradeNo' => '1521526137',
            'desc' => '',
            'time' => '2018-03-20 14:08:59',
            'userid' => '201803200000010357',
            'amount' => '1.00',
            'status' => '交易成功',
            'type' => '微信',
            'sig' => 'test',
        ];

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->verifyOrderPayment([]);
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
            'tradeNo' => '1521526137',
            'desc' => '',
            'time' => '2018-03-20 14:08:59',
            'userid' => '201803200000010357',
            'amount' => '1.00',
            'status' => '交易失败',
            'type' => '微信',
            'sig' => 'EA0259D693BA9501D7D776A09858021C',
        ];

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->verifyOrderPayment([]);
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
            'tradeNo' => '1521526137',
            'desc' => '',
            'time' => '2018-03-20 14:08:59',
            'userid' => '201803200000010357',
            'amount' => '1.00',
            'status' => '交易成功',
            'type' => '微信',
            'sig' => '53F73AA64B0AF0B0D161ED7F9E7C1983',
        ];

        $entry = ['id' => '666666'];

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->verifyOrderPayment($entry);
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
            'tradeNo' => '1521526137',
            'desc' => '',
            'time' => '2018-03-20 14:08:59',
            'userid' => '201803200000010357',
            'amount' => '1.00',
            'status' => '交易成功',
            'type' => '微信',
            'sig' => '53F73AA64B0AF0B0D161ED7F9E7C1983',
        ];

        $entry = [
            'id' => '201803200000010357',
            'amount' => '777',
        ];

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'tradeNo' => '1521526137',
            'desc' => '',
            'time' => '2018-03-20 14:08:59',
            'userid' => '201803200000010357',
            'amount' => '1.00',
            'status' => '交易成功',
            'type' => '微信',
            'sig' => '53F73AA64B0AF0B0D161ED7F9E7C1983',
        ];

        $entry = [
            'id' => '201803200000010357',
            'amount' => '1',
        ];

        $fuYingTong = new FuYingTong();
        $fuYingTong->setPrivateKey('test');
        $fuYingTong->setOptions($options);
        $fuYingTong->verifyOrderPayment($entry);

        $this->assertEquals('ok', $fuYingTong->getMsg());
    }
}
