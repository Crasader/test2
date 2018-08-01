<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NPay;
use Buzz\Message\Response;

class NPayTest extends DurianTestCase
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

        $nPay = new NPay();
        $nPay->getVerifyData();
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

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->getVerifyData();
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
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '999',
            'orderId' => '201802260000004272',
            'amount' => '0.01',
            'username' => 'php1test',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQrCodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '1103',
            'orderId' => '201802260000004272',
            'amount' => '0.01',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回success
     */
    public function testQrCodePayReturnWithoutSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '1103',
            'orderId' => '201802260000004273',
            'amount' => '0.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => 0,
            'msg' => '',
            'timestamp' => 1519636438,
            'payLink' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vfd612941',
            'merOrderId' => '201802260000004273',
            'txnAmt' => '200',
            'signature' => 'Q7zlVmO9xe5k8aykmkyvpA==',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $nPay = new NPay();
        $nPay->setContainer($this->container);
        $nPay->setClient($this->client);
        $nPay->setResponse($response);
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回msg
     */
    public function testQrCodePayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '1103',
            'orderId' => '201802260000004273',
            'amount' => '0.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => 1,
            'code' => 0,
            'timestamp' => 1519636438,
            'payLink' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vfd612941',
            'merOrderId' => '201802260000004273',
            'txnAmt' => '200',
            'signature' => 'Q7zlVmO9xe5k8aykmkyvpA==',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $nPay = new NPay();
        $nPay->setContainer($this->container);
        $nPay->setClient($this->client);
        $nPay->setResponse($response);
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回success不等於1
     */
    public function testQrCodePayReturnSuccessNotEqualToOne()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易金额超限(最低单笔交易金额:2元)',
            180130
        );

        $options = [
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '1103',
            'orderId' => '201802260000004273',
            'amount' => '0.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => 0,
            'code' => 3001,
            'msg' => '交易金额超限(最低单笔交易金额:2元)',
            'timestamp' => 1519636438,
            'payLink' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vfd612941',
            'merOrderId' => '201802260000004273',
            'txnAmt' => '200',
            'signature' => 'Q7zlVmO9xe5k8aykmkyvpA==',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $nPay = new NPay();
        $nPay->setContainer($this->container);
        $nPay->setClient($this->client);
        $nPay->setResponse($response);
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回payLink
     */
    public function testQrCodePayReturnWithoutPayLink()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '1103',
            'orderId' => '201802260000004273',
            'amount' => '0.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => 1,
            'code' => 0,
            'msg' => '',
            'timestamp' => 1519636438,
            'merOrderId' => '201802260000004273',
            'txnAmt' => '200',
            'signature' => 'Q7zlVmO9xe5k8aykmkyvpA==',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $nPay = new NPay();
        $nPay->setContainer($this->container);
        $nPay->setClient($this->client);
        $nPay->setResponse($response);
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '1103',
            'orderId' => '201802260000004273',
            'amount' => '0.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => 1,
            'code' => 0,
            'msg' => '',
            'timestamp' => 1519636438,
            'payLink' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V',
            'merOrderId' => '201802260000004273',
            'txnAmt' => '200',
            'signature' => 'Q7zlVmO9xe5k8aykmkyvpA==',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $nPay = new NPay();
        $nPay->setContainer($this->container);
        $nPay->setClient($this->client);
        $nPay->setResponse($response);
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $data = $nPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V', $nPay->getQrcode());
    }

    /**
     * 測試支付使用銀聯在線
     */
    public function testPayByBCExpress()
    {
        $options = [
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '278',
            'orderId' => '201802260000004273',
            'amount' => '0.01',
            'username' => 'php1test',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $requestData = $nPay->getVerifyData();

        $this->assertArrayNotHasKey('bankId', $requestData);
        $this->assertArrayNotHasKey('dcType', $requestData);
        $this->assertEquals('MD5', $requestData['signMethod']);
        $this->assertEquals('VEL2n+H371xotlnC2zpX0Q==', $requestData['signature']);
        $this->assertEquals('910180126141819', $requestData['merchantId']);
        $this->assertEquals('201802260000004273', $requestData['merOrderId']);
        $this->assertEquals('1', $requestData['txnAmt']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $requestData['frontUrl']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $requestData['backUrl']);
        $this->assertEquals('cGhwMXRlc3Q=', $requestData['subject']);
        $this->assertEquals('cGhwMXRlc3Q=', $requestData['body']);
        $this->assertEquals('kuaijie_unionpay', $requestData['gateway']);
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'number' => '910180126141819',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '1',
            'orderId' => '201802260000004273',
            'amount' => '0.01',
            'username' => 'php1test',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $requestData = $nPay->getVerifyData();

        $this->assertEquals('MD5', $requestData['signMethod']);
        $this->assertEquals('5r/MRIdi85NhF649JDaCrg==', $requestData['signature']);
        $this->assertEquals('910180126141819', $requestData['merchantId']);
        $this->assertEquals('201802260000004273', $requestData['merOrderId']);
        $this->assertEquals('1', $requestData['txnAmt']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $requestData['frontUrl']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $requestData['backUrl']);
        $this->assertEquals('01020000', $requestData['bankId']);
        $this->assertEquals('0', $requestData['dcType']);
        $this->assertEquals('cGhwMXRlc3Q=', $requestData['subject']);
        $this->assertEquals('cGhwMXRlc3Q=', $requestData['body']);
        $this->assertEquals('bank', $requestData['gateway']);
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

        $nPay = new NPay();
        $nPay->verifyOrderPayment([]);
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

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'merchantId' => '910180126141819',
            'merOrderId' => '201802260000004272',
            'txnAmt' => '1',
            'respCode' => '1001',
            'respMsg' => '交易成功',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->verifyOrderPayment([]);
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
            'merchantId' => '910180126141819',
            'merOrderId' => '201802260000004272',
            'txnAmt' => '1',
            'respCode' => '1001',
            'respMsg' => '交易成功',
            'signature' => 'LELLIqT1Qjm75tk88HXv4w==',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->verifyOrderPayment([]);
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
            'merchantId' => '910180126141819',
            'merOrderId' => '201802260000004272',
            'txnAmt' => '1',
            'respCode' => '1000',
            'respMsg' => '待交易',
            'signature' => 'oWw6Ynu0wZLqWEnPRyotJQ==',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->verifyOrderPayment([]);
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
            'merchantId' => '910180126141819',
            'merOrderId' => '201802260000004272',
            'txnAmt' => '1',
            'respCode' => '1001',
            'respMsg' => '交易成功',
            'signature' => 'ndALEMnmA2R6TvH4sNaTVg==',
        ];

        $entry = ['id' => '201802260000004271'];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->verifyOrderPayment($entry);
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
            'merchantId' => '910180126141819',
            'merOrderId' => '201802260000004272',
            'txnAmt' => '1',
            'respCode' => '1001',
            'respMsg' => '交易成功',
            'signature' => 'ndALEMnmA2R6TvH4sNaTVg==',
        ];

        $entry = [
            'id' => '201802260000004272',
            'amount' => '1',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchantId' => '910180126141819',
            'merOrderId' => '201802260000004272',
            'txnAmt' => '1',
            'respCode' => '1001',
            'respMsg' => '交易成功',
            'signature' => 'ndALEMnmA2R6TvH4sNaTVg==',
        ];

        $entry = [
            'id' => '201802260000004272',
            'amount' => '0.01',
        ];

        $nPay = new NPay();
        $nPay->setPrivateKey('test');
        $nPay->setOptions($options);
        $nPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $nPay->getMsg());
    }
}
