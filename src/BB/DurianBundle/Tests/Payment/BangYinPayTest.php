<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BangYinPay;
use Buzz\Message\Response;

class BangYinPayTest extends DurianTestCase
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

        $bangYinPay = new BangYinPay();
        $bangYinPay->getVerifyData();
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

        $sourceData = ['number' => ''];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '929000053991017',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201711100000002223',
            'notify_url' => 'http://pay.my/pay/return.php'
        ];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->getVerifyData();
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '929000053991017',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201711100000002223',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->getVerifyData();
    }

    /**
     * 測試二維支付加密未返回code
     */
    public function testQrcodeGetEncodeNoReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '929000053991017',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201711100000002223',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.88uyx.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"message":"[17]生成微信扫码支付支付二维码成功",' .
            '"imgUrl":"weixin://wxpay/bizpayurl?pr=z45NMMP","merOrderId":"201711100000002230",' .
            '"finalOrderId":"505302806220171110145941593","alertOrderId":"5053028062145941593"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->getVerifyData();
    }

    /**
     * 測試二維支付加密未返回message
     */
    public function testQrcodeGetEncodeNoReturnMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '929000053991017',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201711100000002223',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.88uyx.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"code":"10000",' .
            '"imgUrl":"weixin://wxpay/bizpayurl?pr=z45NMMP","merOrderId":"201711100000002230",' .
            '"finalOrderId":"505302806220171110145941593","alertOrderId":"5053028062145941593"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->getVerifyData();
    }

    /**
     * 測試二維支付加密返回code不為10000
     */
    public function testQrcodeGetEncodeReturnWithFailedCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '[transAmt]支付金额小于100分',
            180130
        );

        $sourceData = [
            'number' => '929000053991017',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201711100000002223',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.88uyx.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"imgUrl":"","message":"[transAmt]支付金额小于100分","finalOrderId":"0000",' .
            '"alertOrderId":"0000","code":"20001","merOrderId":"0000","success":false}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->getVerifyData();
    }

    /**
     * 測試二維加密未返回imgUrl
     */
    public function testQrcodeGetEncodeNoReturnImgUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '929000053991017',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201711100000002223',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.88uyx.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"message":"[17]生成微信扫码支付支付二维码成功","code":"10000",' .
            '"merOrderId":"201711100000002230","finalOrderId":"505302806220171110145941593",' .
            '"alertOrderId":"5053028062145941593"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $sourceData = [
            'number' => '929000053991017',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201711100000002223',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.88uyx.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"message":"[17]生成微信扫码支付支付二维码成功","code":"10000",' .
            '"imgUrl":"weixin://wxpay/bizpayurl?pr=z45NMMP","merOrderId":"201711100000002230",' .
            '"finalOrderId":"505302806220171110145941593","alertOrderId":"5053028062145941593"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $data = $bangYinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=z45NMMP', $bangYinPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"message":"[15]生成网关WEB支付支付二维码成功","code":"10000",' .
            '"imgUrl":"http://pay.88uyx.com/d8/ys_505302806120171110160122714.html",' .
            '"merOrderId":"201711100000002237","finalOrderId":"505302806120171110160122714",' .
            '"alertOrderId":"5053028061160122714"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $data = $bangYinPay->getVerifyData();

        $this->assertEquals('http://pay.88uyx.com/d8/ys_505302806120171110160122714.html', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試支付返回預付單時imgUrl格式錯誤
     */
    public function testPayGetEncodeReturnImgUrlWithError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1088',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"message":"[19]生成银联快捷支付支付二维码成功","code":"10000",' .
            '"imgUrl":"pay.88uyx.com/d8/ys_505302806120171110160122714.html",' .
            '"merOrderId":"201711100000002237","finalOrderId":"505302806120171110160122714",' .
            '"alertOrderId":"5053028061160122714"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $data = $bangYinPay->getVerifyData();
    }

    /**
     * 測試銀聯在線手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1088',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"message":"[19]生成银联快捷支付支付二维码成功","code":"10000",' .
            '"imgUrl":"http://pay.88uyx.com/d8/ys_505302806120171110160122714.html",' .
            '"merOrderId":"201711100000002237","finalOrderId":"505302806120171110160122714",' .
            '"alertOrderId":"5053028061160122714"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $data = $bangYinPay->getVerifyData();

        $this->assertEquals('http://pay.88uyx.com/d8/ys_505302806120171110160122714.html', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試微信手機支付
     */
    public function testWeiXinPhonePay()
    {
        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1097',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"message":"[23]生成微信APP支付支付二维码成功","code":"10000",' .
            '"imgUrl":"https://api.ulopay.com/pay/jspay?ret=1&prepay_id=f4c9e41f16842a404ab8f0fad90818c7",' .
            '"merOrderId":"201711100000002237","finalOrderId":"505302806120171110160122714",' .
            '"alertOrderId":"5053028061160122714"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setContainer($this->container);
        $bangYinPay->setClient($this->client);
        $bangYinPay->setResponse($response);
        $bangYinPay->setOptions($sourceData);
        $data = $bangYinPay->getVerifyData();

        $this->assertEquals('https://api.ulopay.com/pay/jspay', $data['post_url']);
        $this->assertEquals(1, $data['params']['ret']);
        $this->assertEquals('f4c9e41f16842a404ab8f0fad90818c7', $data['params']['prepay_id']);
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

        $bangYinPay = new BangYinPay();
        $bangYinPay->verifyOrderPayment([]);
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

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'finalOrderId' => '505302806120171110115148853',
            'merId' => '929000053991017',
            'merOrderId' => '201711100000002223',
            'respCode' => '60006',
            'respMsg' => '支付完成',
            'succTime' => '20171110115803',
            'transAmt' => '100',
        ];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'finalOrderId' => '505302806120171110115148853',
            'merId' => '929000053991017',
            'merOrderId' => '201711100000002223',
            'respCode' => '60006',
            'respMsg' => '支付完成',
            'sign' => '5A518E792210BFEB5A2003BAFBD32D99',
            'succTime' => '20171110115803',
            'transAmt' => '100',
        ];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->verifyOrderPayment([]);
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

        $sourceData = [
            'finalOrderId' => '505302806120171110115148853',
            'merId' => '929000053991017',
            'merOrderId' => '201711100000002223',
            'respCode' => '60004',
            'respMsg' => '银行拒绝交易',
            'sign' => 'eb20c3a446febd18b39e8cdf17cc698f',
            'succTime' => '20171110115803',
            'transAmt' => '100',
        ];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'finalOrderId' => '505302806120171110115148853',
            'merId' => '929000053991017',
            'merOrderId' => '201711100000002223',
            'respCode' => '60006',
            'respMsg' => '支付完成',
            'sign' => 'c640292aa0812e1ed8d167a3c528342c',
            'succTime' => '20171110115803',
            'transAmt' => '100',
        ];

        $entry = [
            'id' => '201711100000002224',
            'amount' => '1.00',
        ];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'finalOrderId' => '505302806120171110115148853',
            'merId' => '929000053991017',
            'merOrderId' => '201711100000002223',
            'respCode' => '60006',
            'respMsg' => '支付完成',
            'sign' => 'c640292aa0812e1ed8d167a3c528342c',
            'succTime' => '20171110115803',
            'transAmt' => '100',
        ];

        $entry = [
            'id' => '201711100000002223',
            'amount' => '100',
        ];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'finalOrderId' => '505302806120171110115148853',
            'merId' => '929000053991017',
            'merOrderId' => '201711100000002223',
            'respCode' => '60006',
            'respMsg' => '支付完成',
            'sign' => 'c640292aa0812e1ed8d167a3c528342c',
            'succTime' => '20171110115803',
            'transAmt' => '100',
        ];

        $entry = [
            'id' => '201711100000002223',
            'amount' => '1.00',
        ];

        $bangYinPay = new BangYinPay();
        $bangYinPay->setPrivateKey('test');
        $bangYinPay->setOptions($sourceData);
        $bangYinPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $bangYinPay->getMsg());
    }
}
