<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\BananaPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class BananaPayTest extends DurianTestCase
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

        $bananaPay = new BananaPay();
        $bananaPay->getVerifyData();
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

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->getVerifyData();
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
            'number' => '1001',
            'amount' => '100',
            'orderId' => '201710110000005052',
            'paymentVendorId' => '9453',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
        ];

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->getVerifyData();
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
            'number' => '1001',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => '',
        ];

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回err
     */
    public function testPayReturnWithoutErr()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bananaPay = new BananaPay();
        $bananaPay->setContainer($this->container);
        $bananaPay->setClient($this->client);
        $bananaPay->setResponse($response);
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->getVerifyData();
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
            'number' => '1001',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"err":-1}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bananaPay = new BananaPay();
        $bananaPay->setContainer($this->container);
        $bananaPay->setClient($this->client);
        $bananaPay->setResponse($response);
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->getVerifyData();
    }

    /**
     * 測試微信WAP支付時沒有返回code_img_url
     */
    public function testWxWapPayReturnWithoutCodeImgUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"err":0,"code_url":"weixin://wxpay/bizpayurl?pr=nXFs6e1"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bananaPay = new BananaPay();
        $bananaPay->setContainer($this->container);
        $bananaPay->setClient($this->client);
        $bananaPay->setResponse($response);
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->getVerifyData();
    }

    /**
     * 測試掃碼支付時沒有返回code_url
     */
    public function testScanPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"err":0,"code_img_url":"https://pay.swiftpass.cn/pay/qrcode?uuid=https://qr.alipay.com/bax0384"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bananaPay = new BananaPay();
        $bananaPay->setContainer($this->container);
        $bananaPay->setClient($this->client);
        $bananaPay->setResponse($response);
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->getVerifyData();
    }

    /**
     * 測試掃碼支付
     */
    public function testScanPay()
    {
        $options = [
            'number' => '1001',
            'amount' => '0.01',
            'orderId' => '201801150000009004',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"err":0,"code_img_url":"https://pay.swiftpass.cn/pay/qrcode?uuid=weixin://wxpay/bizpayurl' .
            '?pr=nXFs6e1","code_url":"weixin://wxpay/bizpayurl?pr=nXFs6e1"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bananaPay = new BananaPay();
        $bananaPay->setContainer($this->container);
        $bananaPay->setClient($this->client);
        $bananaPay->setResponse($response);
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $data = $bananaPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=nXFs6e1', $bananaPay->getQrcode());
    }

    /**
     * 測試微信WAP支付對外返回缺少query
     */
    public function testWxWapPayReturnWithoutQuery()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1001',
            'amount' => '0.01',
            'orderId' => '201801150000009004',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"err":0,"code_img_url":"//api.ulopay.com/pay/jspay?ret=1&' .
            'prepay_id=cac2f6bf9b7e157bec7b33d39be891b8"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bananaPay = new BananaPay();
        $bananaPay->setContainer($this->container);
        $bananaPay->setClient($this->client);
        $bananaPay->setResponse($response);
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $data = $bananaPay->getVerifyData();
    }

    /**
     * 測試微信手機支付
     */
    public function testWxWapPay()
    {
        $options = [
            'number' => '1001',
            'amount' => '0.01',
            'orderId' => '201801150000009004',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"err":0,"code_img_url":"https://api.ulopay.com/pay/jspay?ret=1&' .
            'prepay_id=cac2f6bf9b7e157bec7b33d39be891b8"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bananaPay = new BananaPay();
        $bananaPay->setContainer($this->container);
        $bananaPay->setClient($this->client);
        $bananaPay->setResponse($response);
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $data = $bananaPay->getVerifyData();

        $this->assertEquals('https://api.ulopay.com/pay/jspay', $data['post_url']);
        $this->assertEquals(1, $data['params']['ret']);
        $this->assertEquals('cac2f6bf9b7e157bec7b33d39be891b8', $data['params']['prepay_id']);
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

        $bananaPay = new BananaPay();
        $bananaPay->verifyOrderPayment([]);
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

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->verifyOrderPayment([]);
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
            'errcode' => '0',
            'orderno' => '201710110000005052',
            'total_fee' => '1',
            'attach' => '',
        ];

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->verifyOrderPayment([]);
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
            'errcode' => '0',
            'orderno' => '201710110000005052',
            'total_fee' => '1',
            'attach' => '',
            'sign' => '26575A9B5A6316C90470A5DE2C5ED813',
        ];

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->verifyOrderPayment([]);
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
            'errcode' => '1',
            'orderno' => '201710110000005052',
            'total_fee' => '1',
            'attach' => '',
            'sign' => 'B0824F7CE360D2A342D89E0039DDA0C7',
        ];

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->verifyOrderPayment([]);
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
            'errcode' => '0',
            'orderno' => '201710110000005052',
            'total_fee' => '1',
            'attach' => '',
            'sign' => 'A49CBF9DE76DD9FBA7757BA20F5F13C9',
        ];

        $entry = ['id' => '9453'];

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->verifyOrderPayment($entry);
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
            'errcode' => '0',
            'orderno' => '201710110000005052',
            'total_fee' => '1',
            'attach' => '',
            'sign' => 'A49CBF9DE76DD9FBA7757BA20F5F13C9',
        ];

        $entry = [
            'id' => '201710110000005052',
            'amount' => '1',
        ];

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'errcode' => '0',
            'orderno' => '201710110000005052',
            'total_fee' => '1',
            'attach' => '',
            'sign' => 'A49CBF9DE76DD9FBA7757BA20F5F13C9',
        ];

        $entry = [
            'id' => '201710110000005052',
            'amount' => '0.01',
        ];

        $bananaPay = new BananaPay();
        $bananaPay->setPrivateKey('test');
        $bananaPay->setOptions($options);
        $bananaPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $bananaPay->getMsg());
    }
}
