<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HaiBei;
use Buzz\Message\Response;

class HaiBeiTest extends DurianTestCase
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
            ->getMock();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
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

        $haiBei = new HaiBei();
        $haiBei->getVerifyData();
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

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

         $options = [
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '9999',
        ];

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->getVerifyData();
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
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_url' => '',
        ];

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->getVerifyData();
    }

    /**
     * 測試支付時沒有返回status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_url' => 'payment.https.pay.166985.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"errCode":"9004","errMsg":"订单号重复"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $haiBei = new HaiBei();
        $haiBei->setContainer($this->container);
        $haiBei->setClient($this->client);
        $haiBei->setResponse($response);
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->getVerifyData();
    }

    /**
     * 測試支付時返回errMsg錯誤訊息
     */
    public function testPayReturnWithErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单号重复',
            180130
        );

        $options = [
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_url' => 'payment.https.pay.166985.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"errCode":"9004","errMsg":"订单号重复","status":"F"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $haiBei = new HaiBei();
        $haiBei->setContainer($this->container);
        $haiBei->setClient($this->client);
        $haiBei->setResponse($response);
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnButStatusIsF()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_url' => 'payment.https.pay.166985.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"errCode":"9004","status":"F"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $haiBei = new HaiBei();
        $haiBei->setContainer($this->container);
        $haiBei->setClient($this->client);
        $haiBei->setResponse($response);
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->getVerifyData();
    }

    /**
     * 測試支付時沒有返回payUrl
     */
    public function testPayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_url' => 'payment.https.pay.166985.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchantNo":"1234","orderAmount":"101","orderNo":"201803050000008212",' .
            '"sign":"c4b9420751817ca8de3d0afa9b6ed2dc","status":"T"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $haiBei = new HaiBei();
        $haiBei->setContainer($this->container);
        $haiBei->setClient($this->client);
        $haiBei->setResponse($response);
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1111',
            'verify_url' => 'payment.https.pay.166985.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchantNo":"1234","orderAmount":"101","orderNo":"201803050000008212",' .
            '"payUrl":"https://qr.95516.com/0000/82853","sign":"c4b9420751817ca8de3d0afa9b6ed2dc","status":"T"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $haiBei = new HaiBei();
        $haiBei->setContainer($this->container);
        $haiBei->setClient($this->client);
        $haiBei->setResponse($response);
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $data = $haiBei->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/0000/82853', $haiBei->getQrcode());
    }

    /**
     * 測試支付對外返回缺少query
     */
    public function testPayReturnWithoutQuery()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_url' => 'payment.https.pay.166985.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchantNo":"1234","orderAmount":"101","orderNo":"201803050000008212",' .
            '"payUrl":"http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi",' .
            '"sign":"c4b9420751817ca8de3d0afa9b6ed2dc","status":"T"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $haiBei = new HaiBei();
        $haiBei->setContainer($this->container);
        $haiBei->setClient($this->client);
        $haiBei->setResponse($response);
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'orderId' => '201803050000008212',
            'number' => '1234',
            'amount' => '1.01',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_url' => 'payment.https.pay.166985.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchantNo":"1234","orderAmount":"101","orderNo":"201803050000008212",' .
            '"payUrl":"http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?cipher_data=test",' .
            '"sign":"c4b9420751817ca8de3d0afa9b6ed2dc","status":"T"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $haiBei = new HaiBei();
        $haiBei->setContainer($this->container);
        $haiBei->setClient($this->client);
        $haiBei->setResponse($response);
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $data = $haiBei->getVerifyData();

        $this->assertEquals('http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi', $data['post_url']);
        $this->assertEquals(['cipher_data' => 'test'], $data['params']);
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

        $haiBei = new HaiBei();
        $haiBei->verifyOrderPayment([]);
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

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->verifyOrderPayment([]);
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
            'productDesc' => '201803050000008212',
            'orderAmount' => '1',
            'orderNo' => '201803050000008212',
            'wtfOrderNo' => '200000004377328',
            'payTime' => '2018-03-06 11:31:56',
            'orderStatus' => 'SUCCESS',
            'remark' => '',
            'productName' => '201803050000008212',
            'merchantNo' => '1234',
        ];

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->verifyOrderPayment([]);
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
            'productDesc' => '201803050000008212',
            'orderAmount' => '1',
            'orderNo' => '201803050000008212',
            'wtfOrderNo' => '200000004377328',
            'payTime' => '2018-03-06 11:31:56',
            'sign' => '11e331fa6d2dd3d91b18e9b2c976c289',
            'orderStatus' => 'SUCCESS',
            'remark' => '',
            'productName' => '201803050000008212',
            'merchantNo' => '1234',
        ];

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->verifyOrderPayment([]);
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
            'productDesc' => '201803050000008212',
            'orderAmount' => '1',
            'orderNo' => '201803050000008212',
            'wtfOrderNo' => '200000004377328',
            'payTime' => '2018-03-06 11:31:56',
            'sign' => '63b9e9bbb4eae7d65ab4d23966f98e95',
            'orderStatus' => 'FAILED',
            'remark' => '',
            'productName' => '201803050000008212',
            'merchantNo' => '1234',
        ];

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->verifyOrderPayment([]);
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
            'productDesc' => '201803050000008212',
            'orderAmount' => '1',
            'orderNo' => '201803050000008212',
            'wtfOrderNo' => '200000004377328',
            'payTime' => '2018-03-06 11:31:56',
            'sign' => 'ed0e7f790a2197927b86685b91fc4c48',
            'orderStatus' => 'SUCCESS',
            'remark' => '',
            'productName' => '201803050000008212',
            'merchantNo' => '1234',
        ];

        $entry = ['id' => '201503220000000555'];

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->verifyOrderPayment($entry);
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
            'productDesc' => '201803050000008212',
            'orderAmount' => '1',
            'orderNo' => '201803050000008212',
            'wtfOrderNo' => '200000004377328',
            'payTime' => '2018-03-06 11:31:56',
            'sign' => 'ed0e7f790a2197927b86685b91fc4c48',
            'orderStatus' => 'SUCCESS',
            'remark' => '',
            'productName' => '201803050000008212',
            'merchantNo' => '1234',
        ];

        $entry = [
            'id' => '201803050000008212',
            'amount' => '15.00',
        ];

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'productDesc' => '201803050000008212',
            'orderAmount' => '1',
            'orderNo' => '201803050000008212',
            'wtfOrderNo' => '200000004377328',
            'payTime' => '2018-03-06 11:31:56',
            'sign' => 'ed0e7f790a2197927b86685b91fc4c48',
            'orderStatus' => 'SUCCESS',
            'remark' => '',
            'productName' => '201803050000008212',
            'merchantNo' => '1234',
        ];

        $entry = [
            'id' => '201803050000008212',
            'amount' => '0.01',
        ];

        $haiBei = new HaiBei();
        $haiBei->setPrivateKey('test');
        $haiBei->setOptions($options);
        $haiBei->verifyOrderPayment($entry);

        $this->assertEquals('OK', $haiBei->getMsg());
    }
}
