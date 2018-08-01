<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\Vip100Pay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class Vip100PayTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $option;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'number' => '9527',
            'orderId' => '201805280000046362',
            'amount' => '1',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://www.seafood.help/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.3030ka.com',
        ];

        $this->returnResult = [
            'merId' => '9527',
            'merOrderNo' => '201805280000046362',
            'orderAmt' => '1.00',
            'orderDesc' => '201805280000046362',
            'orderTitle' => '201805280000046362',
            'payDate' => '2018-05-28',
            'payNo' => '2018052811054387156019144',
            'payStatus' => 'S',
            'payTime' => '11:08:07',
            'realAmt' => '0.97',
            'sign' => 'f507bd63b49e8f5819ddb4f4e5a31334',
            'version' => '1.0.0',
        ];
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

        $vip100Pay = new Vip100Pay();
        $vip100Pay->getVerifyData();
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

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->option);
        $vip100Pay->getVerifyData();
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

        $this->option['verify_url'] = '';

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->option);
        $vip100Pay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setContainer($this->container);
        $vip100Pay->setClient($this->client);
        $vip100Pay->setResponse($response);
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->option);
        $vip100Pay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respMsg
     */
    public function testPayReturnWithoutRespMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['respCode' => '0000'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setContainer($this->container);
        $vip100Pay->setClient($this->client);
        $vip100Pay->setResponse($response);
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->option);
        $vip100Pay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名错误',
            180130
        );

        $result = [
            'respCode' => '0004',
            'respMsg' => '签名错误',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setContainer($this->container);
        $vip100Pay->setClient($this->client);
        $vip100Pay->setResponse($response);
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->option);
        $vip100Pay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回jumpUrl
     */
    public function testPayReturnWithoutJumpUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respCode' => '0000',
            'respMsg' => '请求成功',
            'payNo' => '2018052811054387156019144',
            'merOrderNo' => '201805280000046362',
            'realAmt' => '0.97',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setContainer($this->container);
        $vip100Pay->setClient($this->client);
        $vip100Pay->setResponse($response);
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->option);
        $vip100Pay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = [
            'respCode' => '0000',
            'respMsg' => '请求成功',
            'payNo' => '2018052811054387156019144',
            'merOrderNo' => '201805280000046362',
            'jumpUrl' => 'HTTPS://QR.ALIPAY.COM/FKX046015GPPJAH8UHZH15?t=1525611859391',
            'realAmt' => '0.97',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setContainer($this->container);
        $vip100Pay->setClient($this->client);
        $vip100Pay->setResponse($response);
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->option);
        $data = $vip100Pay->getVerifyData();

        $this->assertEquals('HTTPS://QR.ALIPAY.COM/FKX046015GPPJAH8UHZH15', $data['post_url']);
        $this->assertEquals('1525611859391', $data['params']['t']);
        $this->assertEquals('GET', $vip100Pay->getPayMethod());
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $this->option['paymentVendorId'] = '1092';

        $result = [
            'respCode' => '0000',
            'respMsg' => '请求成功',
            'payNo' => '2018052811054387156019144',
            'merOrderNo' => '201805280000046362',
            'jumpUrl' => 'http://api.3030ka.com/grmApp/pay.do?id=2018052811361143347336139',
            'realAmt' => '0.97',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setContainer($this->container);
        $vip100Pay->setClient($this->client);
        $vip100Pay->setResponse($response);
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->option);
        $data = $vip100Pay->getVerifyData();

        $this->assertEquals('http://api.3030ka.com/grmApp/pay.do', $data['post_url']);
        $this->assertEquals('2018052811361143347336139', $data['params']['id']);
        $this->assertEquals('GET', $vip100Pay->getPayMethod());
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

        $vip100Pay = new Vip100Pay();
        $vip100Pay->verifyOrderPayment([]);
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

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->returnResult);
        $vip100Pay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'd92fb3b01e183e6650e3ea50e6b02e06';

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->returnResult);
        $vip100Pay->verifyOrderPayment([]);
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

        $this->returnResult['payStatus'] = 'F';
        $this->returnResult['sign'] = '8775931dfd79a58f0eb5aced64cd6fee';

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->returnResult);
        $vip100Pay->verifyOrderPayment([]);
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

        $entry = ['id' => '9453'];

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->returnResult);
        $vip100Pay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201805280000046362',
            'amount' => '123',
        ];

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->returnResult);
        $vip100Pay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805280000046362',
            'amount' => '1',
        ];

        $vip100Pay = new Vip100Pay();
        $vip100Pay->setPrivateKey('test');
        $vip100Pay->setOptions($this->returnResult);
        $vip100Pay->verifyOrderPayment($entry);

        $this->assertEquals('success', $vip100Pay->getMsg());
    }
}
