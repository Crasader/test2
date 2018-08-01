<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\OneTung;
use Buzz\Message\Response;

class OneTungTest extends DurianTestCase
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

        $oneTung = new OneTung();
        $oneTung->getVerifyData();
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

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->getVerifyData();
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
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '100',
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'orderCreateDate' => '2017-11-15 15:40:00',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setOptions($options);
        $oneTung->getVerifyData();
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

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setOptions($options);
        $oneTung->getVerifyData();
    }

    /**
     * 測試支付時返回缺少ret
     */
    public function testPayReturnWithoutRet()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"msg":"操作失败，请查询返回结果或联系管理员", "error_msg":"认证错误，请检查商户号或签名。"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setContainer($this->container);
        $oneTung->setClient($this->client);
        $oneTung->setResponse($response);
        $oneTung->setOptions($options);
        $oneTung->getVerifyData();
    }

    /**
     * 測試支付時返回結果失敗
     */
    public function testPayReturnButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '操作失败，请查询返回结果或联系管理员',
            180130
        );

        $result = '{"ret":"fail","msg":"操作失败，请查询返回结果或联系管理员",' .
            '"error_msg":"认证错误，请检查商户号或签名。"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setContainer($this->container);
        $oneTung->setClient($this->client);
        $oneTung->setResponse($response);
        $oneTung->setOptions($options);
        $oneTung->getVerifyData();
    }

    /**
     * 測試支付時返回缺少qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"ret":"success","msg":"成功","merchant":"20171109b9","amount":"0.05",' .
            '"merchant_order_id":"201711150000007600","pay_method":"微信扫码","submitted":true,' .
            '"success":false,"notify_url":"http://two123.comuv.com/pay/return.php", ' .
            '"sign":"37737B62562D35A61AD27E46E5BFAA65"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setContainer($this->container);
        $oneTung->setClient($this->client);
        $oneTung->setResponse($response);
        $oneTung->setOptions($options);
        $oneTung->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = '{"ret":"success","msg":"成功","merchant":"20171109b9","amount":"0.05",' .
            '"merchant_order_id":"201711150000007600","pay_method":"微信扫码","submitted":true,' .
            '"success":false,"notify_url":"http://two123.comuv.com/pay/return.php", ' .
            '"QRCode_url":"weixin://wxpay/bizpayurl?pr=pPesnZu",' .
            '"sign":"37737B62562D35A61AD27E46E5BFAA65"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setContainer($this->container);
        $oneTung->setClient($this->client);
        $oneTung->setResponse($response);
        $oneTung->setOptions($options);
        $verifyData = $oneTung->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('weixin://wxpay/bizpayurl?pr=pPesnZu', $oneTung->getQrcode());
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

        $oneTung = new OneTung();
        $oneTung->verifyOrderPayment([]);
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

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->verifyOrderPayment([]);
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
            'ret' => 'success',
            'msg' => '成功',
            'merchant' => '20171109b9',
            'amount' => '0.05',
            'merchant_order_id' => '201711150000007600',
            'pay_method' => '微信扫码',
            'submitted' => 'True',
            'success' => 'True',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'QRCode_url' => 'weixin://wxpay/bizpayurl?pr=pPesnZu',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setOptions($options);
        $oneTung->verifyOrderPayment([]);
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
            'ret' => 'success',
            'msg' => '成功',
            'merchant' => '20171109b9',
            'amount' => '0.05',
            'merchant_order_id' => '201711150000007600',
            'pay_method' => '微信扫码',
            'submitted' => 'True',
            'success' => 'True',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'QRCode_url' => 'weixin://wxpay/bizpayurl?pr=pPesnZu',
            'sign' => 'E2962E5368455AB93EC769F2E2A93C86',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setOptions($options);
        $oneTung->verifyOrderPayment([]);
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
            'ret' => 'fail',
            'msg' => '失败',
            'merchant' => '20171109b9',
            'amount' => '0.05',
            'merchant_order_id' => '201711150000007600',
            'pay_method' => '微信扫码',
            'submitted' => 'True',
            'success' => 'false',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'QRCode_url' => 'weixin://wxpay/bizpayurl?pr=pPesnZu',
            'sign' => 'a327df78cdb6c3c9409e8e22b8a68fc5',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setOptions($options);
        $oneTung->verifyOrderPayment([]);
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
            'ret' => 'success',
            'msg' => '成功',
            'merchant' => '20171109b9',
            'amount' => '0.05',
            'merchant_order_id' => '201711150000007600',
            'pay_method' => '微信扫码',
            'submitted' => 'True',
            'success' => 'True',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'QRCode_url' => 'weixin://wxpay/bizpayurl?pr=pPesnZu',
            'sign' => 'a551489c2b388d42e3d96d568a767a42',
        ];

        $entry = ['id' => '201503220000000555'];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setOptions($options);
        $oneTung->verifyOrderPayment($entry);
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
            'ret' => 'success',
            'msg' => '成功',
            'merchant' => '20171109b9',
            'amount' => '0.05',
            'merchant_order_id' => '201711150000007600',
            'pay_method' => '微信扫码',
            'submitted' => 'True',
            'success' => 'True',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'QRCode_url' => 'weixin://wxpay/bizpayurl?pr=pPesnZu',
            'sign' => 'a551489c2b388d42e3d96d568a767a42',
        ];

        $entry = [
            'id' => '201711150000007600',
            'amount' => '15.00',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setOptions($options);
        $oneTung->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'ret' => 'success',
            'msg' => '成功',
            'merchant' => '20171109b9',
            'amount' => '0.05',
            'merchant_order_id' => '201711150000007600',
            'pay_method' => '微信扫码',
            'submitted' => 'True',
            'success' => 'True',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'QRCode_url' => 'weixin://wxpay/bizpayurl?pr=pPesnZu',
            'sign' => 'a551489c2b388d42e3d96d568a767a42',
        ];

        $entry = [
            'id' => '201711150000007600',
            'amount' => '0.05',
        ];

        $oneTung = new OneTung();
        $oneTung->setPrivateKey('test');
        $oneTung->setOptions($options);
        $oneTung->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $oneTung->getMsg());
    }
}
