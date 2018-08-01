<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XinYuFu;
use Buzz\Message\Response;

class XinYuFuTest extends DurianTestCase
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
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xinYuFu = new XinYuFu();
        $xinYuFu->getVerifyData();
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

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $option = [
            'number' => '9453',
            'orderId' => '201802010000009453',
            'orderCreateDate' => '2018-02-01 16:00:00',
            'amount' => '1.9453',
            'paymentVendorId' => '9999',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $xinYuFu->getVerifyData();
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $option = [
            'number' => '9453',
            'orderId' => '201802010000009453',
            'orderCreateDate' => '2018-02-01 16:00:00',
            'amount' => '1.9453',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_url' => '',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $xinYuFu->getVerifyData();
    }

    /**
     * 測試二維支付時缺少resCode
     */
    public function testPayReturnWithoutResCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'resMsg' => '商户未开通此产品',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802010000009453',
            'orderCreateDate' => '2018-02-01 16:00:00',
            'amount' => '1.9453',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setContainer($this->container);
        $xinYuFu->setClient($this->client);
        $xinYuFu->setResponse($response);
        $xinYuFu->setOptions($option);
        $xinYuFu->getVerifyData();
    }

    /**
     * 測試二維支付時返回resCode不等於10000
     */
    public function testPayReturnResCodeNotEqual10000()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'resCode' => '10001',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802010000009453',
            'orderCreateDate' => '2018-02-01 16:00:00',
            'amount' => '1.9453',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setContainer($this->container);
        $xinYuFu->setClient($this->client);
        $xinYuFu->setResponse($response);
        $xinYuFu->setOptions($option);
        $xinYuFu->getVerifyData();
    }

    /**
     * 測試二維支付時返回錯誤訊息
     */
    public function testPayReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户未开通此产品',
            180130
        );

        $result = [
            'resCode' => '10001',
            'resMsg' => '商户未开通此产品',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802010000009453',
            'orderCreateDate' => '2018-02-01 16:00:00',
            'amount' => '1.9453',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setContainer($this->container);
        $xinYuFu->setClient($this->client);
        $xinYuFu->setResponse($response);
        $xinYuFu->setOptions($option);
        $xinYuFu->getVerifyData();
    }

    /**
     * 測試二維支付時缺少Payurl
     */
    public function testPayReturnWithoutPayurl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'resCode' => '10000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802010000009453',
            'orderCreateDate' => '2018-02-01 16:00:00',
            'amount' => '1.9453',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setContainer($this->container);
        $xinYuFu->setClient($this->client);
        $xinYuFu->setResponse($response);
        $xinYuFu->setOptions($option);
        $xinYuFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $result = [
            'resCode' => '10000',
            'Payurl' => 'http://se.zhpywh.COM/Pay/apipay.html',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802010000009453',
            'orderCreateDate' => '2018-02-01 16:00:00',
            'amount' => '1.9453',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setContainer($this->container);
        $xinYuFu->setClient($this->client);
        $xinYuFu->setResponse($response);
        $xinYuFu->setOptions($option);
        $encodeData = $xinYuFu->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('http://se.zhpywh.COM/Pay/apipay.html', $xinYuFu->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $option = [
            'number' => '9453',
            'orderId' => '201802010000009453',
            'orderCreateDate' => '2018-02-01 16:00:00',
            'amount' => '1.9453',
            'paymentVendorId' => '278',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $encodeData = $xinYuFu->getVerifyData();

        $this->assertEquals('V4.0', $encodeData['V']);
        $this->assertEquals('9453', $encodeData['UserNo']);
        $this->assertEquals('201802010000009453', $encodeData['ordNo']);
        $this->assertEquals('20180201160000', $encodeData['ordTime']);
        $this->assertEquals('195', $encodeData['amount']);
        $this->assertEquals('cxkzf', $encodeData['pid']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['notifyUrl']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['frontUrl']);
        $this->assertEquals('seafood', $encodeData['remark']);
        $this->assertEquals('127.0.0.1', $encodeData['ip']);
        $this->assertEquals('C1E9D690E6186D16FCED2C0E1AD8CDCD', $encodeData['sign']);
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xinYuFu = new XinYuFu();
        $xinYuFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $option = [
            'V' => 'V4.0',
            'UserNo' => '9453',
            'ordNo' => '201802010000009453',
            'amount' => '195',
            'status' => '1001',
            'reqTime' => '20180201160000',
            'reqNo' => 'A2E3B482E9B41F23A0F1',
            'remark' => 'seafood',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $xinYuFu->verifyOrderPayment([]);
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

        $option = [
            'V' => 'V4.0',
            'UserNo' => '9453',
            'ordNo' => '201802010000009453',
            'amount' => '195',
            'status' => '1001',
            'reqTime' => '20180201160000',
            'reqNo' => 'A2E3B482E9B41F23A0F1',
            'remark' => 'seafood',
            'Sign' => '9453',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $xinYuFu->verifyOrderPayment([]);
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

        $option = [
            'V' => 'V4.0',
            'UserNo' => '9453',
            'ordNo' => '201802010000009453',
            'amount' => '195',
            'status' => '1002',
            'reqTime' => '20180201160000',
            'reqNo' => 'A2E3B482E9B41F23A0F1',
            'remark' => 'seafood',
            'Sign' => 'AC75B32665403E25FB16CF3B54CB3A43',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $xinYuFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $option = [
            'V' => 'V4.0',
            'UserNo' => '9453',
            'ordNo' => '201802010000009453',
            'amount' => '195',
            'status' => '1001',
            'reqTime' => '20180201160000',
            'reqNo' => 'A2E3B482E9B41F23A0F1',
            'remark' => 'seafood',
            'Sign' => '1D6ED7E5484E40F1DB2F2137542494E6',
        ];

        $entry = ['id' => '201802010000009487'];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $xinYuFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $option = [
            'V' => 'V4.0',
            'UserNo' => '9453',
            'ordNo' => '201802010000009453',
            'amount' => '195',
            'status' => '1001',
            'reqTime' => '20180201160000',
            'reqNo' => 'A2E3B482E9B41F23A0F1',
            'remark' => 'seafood',
            'Sign' => '1D6ED7E5484E40F1DB2F2137542494E6',
        ];

        $entry = [
            'id' => '201802010000009453',
            'amount' => '195',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $xinYuFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $option = [
            'V' => 'V4.0',
            'UserNo' => '9453',
            'ordNo' => '201802010000009453',
            'amount' => '195',
            'status' => '1001',
            'reqTime' => '20180201160000',
            'reqNo' => 'A2E3B482E9B41F23A0F1',
            'remark' => 'seafood',
            'Sign' => '1D6ED7E5484E40F1DB2F2137542494E6',
        ];

        $entry = [
            'id' => '201802010000009453',
            'amount' => '1.95',
        ];

        $xinYuFu = new XinYuFu();
        $xinYuFu->setPrivateKey('test');
        $xinYuFu->setOptions($option);
        $xinYuFu->verifyOrderPayment($entry);

        $this->assertEquals('OK', $xinYuFu->getMsg());
    }
}
