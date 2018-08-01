<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SinPay;
use Buzz\Message\Response;

class SinPayTest extends DurianTestCase
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

        $sinPay = new SinPay();
        $sinPay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '9999',
            'number' => '10382633',
            'orderId' => '201801250000006424',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
        ];

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '10382633',
            'orderId' => '201801250000006424',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
            'verify_url' => '',
        ];

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回resultCode
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '10382633',
            'orderId' => '201801250000006424',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"beConfirm":0}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sinPay = new SinPay();
        $sinPay->setContainer($this->container);
        $sinPay->setClient($this->client);
        $sinPay->setResponse($response);
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '10382633',
            'orderId' => '201801250000006424',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"beConfirm":0,"resultCode":400}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sinPay = new SinPay();
        $sinPay->setContainer($this->container);
        $sinPay->setClient($this->client);
        $sinPay->setResponse($response);
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回pay_link
     */
    public function testPayReturnWithoutPayLink()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '10382633',
            'orderId' => '201801250000006424',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"beConfirm":0,"order":{},"resultCode":200}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sinPay = new SinPay();
        $sinPay->setContainer($this->container);
        $sinPay->setClient($this->client);
        $sinPay->setResponse($response);
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '10382633',
            'orderId' => '201801250000006424',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"beConfirm":0,"order":{"pay_link":"weixin:\/\/wxpay\/bizpayurl?pr=Yu3i2NU"},"resultCode":200}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sinPay = new SinPay();
        $sinPay->setContainer($this->container);
        $sinPay->setClient($this->client);
        $sinPay->setResponse($response);
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $data = $sinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=Yu3i2NU', $sinPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1098',
            'number' => '10382633',
            'orderId' => '201801250000006424',
            'amount' => '1.01',
            'ip' => '10.123.123.123',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"beConfirm":0,"order":{"bill_no":"f0cee199acbc1e54458718d79d49d9e6",' .
            '"linkId":"201803060000009891","bill_fee":10100,"bill_title":"php1test",' .
            '"bill_body":"php1test","bill_create_ip":"192.168.101.1","pay_type":4,' .
            '"pay_link":"http://120.79.49.119/gateway/payment/waitPay?token=178808' .
            '5D2C54D2480FE069DBD6526B922ED6FBABD485B4945EF709E7"},"resultCode":200}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sinPay = new SinPay();
        $sinPay->setContainer($this->container);
        $sinPay->setClient($this->client);
        $sinPay->setResponse($response);
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $data = $sinPay->getVerifyData();

        $this->assertEquals('1788085D2C54D2480FE069DBD6526B922ED6FBABD485B4945EF709E7', $data['params']['token']);
        $this->assertEquals('http://120.79.49.119/gateway/payment/waitPay', $data['post_url']);
        $this->assertEquals('GET', $sinPay->getPayMethod());
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

        $sinPay = new SinPay();
        $sinPay->verifyOrderPayment([]);
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

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->verifyOrderPayment([]);
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
            'bill_no' => '1ddc455415c181d514f59a3cdddb0c21',
            'pay_type' => '9',
            'bill_fee' => '10',
            'link_id' => '201801250000006424',
            'feeResult' => '0',
        ];

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->verifyOrderPayment([]);
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
            'bill_no' => '1ddc455415c181d514f59a3cdddb0c21',
            'pay_type' => '9',
            'bill_fee' => '10',
            'link_id' => '201801250000006424',
            'feeResult' => '0',
            'sign' => '344C7173EC29B50C4B216D4192FB2CD5',
        ];

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->verifyOrderPayment([]);
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

        $options = [
            'bill_no' => '1ddc455415c181d514f59a3cdddb0c21',
            'pay_type' => '9',
            'bill_fee' => '10',
            'link_id' => '201801250000006424',
            'feeResult' => '1',
            'sign' => '8D00082589B902B2F6DC41AFC0879BD7',
        ];

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->verifyOrderPayment([]);
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
            'bill_no' => '1ddc455415c181d514f59a3cdddb0c21',
            'pay_type' => '9',
            'bill_fee' => '10',
            'link_id' => '201801250000006424',
            'feeResult' => '0',
            'sign' => '8D00082589B902B2F6DC41AFC0879BD7',
        ];

        $entry = ['id' => '201503220000000555'];

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->verifyOrderPayment($entry);
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
            'bill_no' => '1ddc455415c181d514f59a3cdddb0c21',
            'pay_type' => '9',
            'bill_fee' => '10',
            'link_id' => '201801250000006424',
            'feeResult' => '0',
            'sign' => '8D00082589B902B2F6DC41AFC0879BD7',
        ];

        $entry = [
            'id' => '201801250000006424',
            'amount' => '15.00',
        ];

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'bill_no' => '1ddc455415c181d514f59a3cdddb0c21',
            'pay_type' => '9',
            'bill_fee' => '10',
            'link_id' => '201801250000006424',
            'feeResult' => '0',
            'sign' => '8D00082589B902B2F6DC41AFC0879BD7',
        ];

        $entry = [
            'id' => '201801250000006424',
            'amount' => '0.1',
        ];

        $sinPay = new SinPay();
        $sinPay->setPrivateKey('test');
        $sinPay->setOptions($options);
        $sinPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $sinPay->getMsg());
    }
}
