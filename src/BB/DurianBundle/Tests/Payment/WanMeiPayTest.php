<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\WanMeiPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class WanMeiPayTest extends DurianTestCase
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
            'orderId' => '201805240000046339',
            'amount' => '1',
            'orderCreateDate' => '2018-05-24 15:40:05',
            'notify_url' => 'http://www.seafood.help/',
            'number' => '9527',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.wmpay8.com',
            'postUrl' => 'http://www.wmpay8.com/pay/paystate/',
        ];

        $this->returnResult = [
            'id' => '201805240000046339',
            'money' => '1',
            'token' => '0276601527151992964',
            'time' => '1527152184484',
            'hash' => '8066FE39594878D537EB12826AC1E7D3',
            'state' => '1',
            'payType' => '1',
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

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->getVerifyData();
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

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->getVerifyData();
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

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->option);
        $wanMeiPay->getVerifyData();
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

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->option);
        $wanMeiPay->getVerifyData();
    }

    /**
     * 測試支付時返回error
     */
    public function testPayReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '没有合适的二维码',
            180130
        );

        $result = ['error' => '没有合适的二维码'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setContainer($this->container);
        $wanMeiPay->setClient($this->client);
        $wanMeiPay->setResponse($response);
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->option);
        $wanMeiPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回token
     */
    public function testPayReturnWithoutToken()
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

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setContainer($this->container);
        $wanMeiPay->setClient($this->client);
        $wanMeiPay->setResponse($response);
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->option);
        $wanMeiPay->getVerifyData();
    }

    /**
     * 測試支付時token不為數字
     */
    public function testPayTokenNotNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['token' => '当前支付不可用，请联系客服'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setContainer($this->container);
        $wanMeiPay->setClient($this->client);
        $wanMeiPay->setResponse($response);
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->option);
        $wanMeiPay->getVerifyData();
    }

    /**
     * 測試支付時token不為19個數字
     */
    public function testPayTokenLengthNotEqual19()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['token' => '123456'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setContainer($this->container);
        $wanMeiPay->setClient($this->client);
        $wanMeiPay->setResponse($response);
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->option);
        $wanMeiPay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $result = ['token' => '0276601527151992964'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $this->option['postUrl'] = '';

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setContainer($this->container);
        $wanMeiPay->setClient($this->client);
        $wanMeiPay->setResponse($response);
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->option);
        $wanMeiPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = ['token' => '0276601527151992964'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setContainer($this->container);
        $wanMeiPay->setClient($this->client);
        $wanMeiPay->setResponse($response);
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->option);
        $data = $wanMeiPay->getVerifyData();

        $this->assertEquals('http://www.wmpay8.com/pay/paystate/0276601527151992964', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $wanMeiPay->getPayMethod());
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

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->verifyOrderPayment([]);
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

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->verifyOrderPayment([]);
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

        unset($this->returnResult['hash']);

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->returnResult);
        $wanMeiPay->verifyOrderPayment([]);
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

        $this->returnResult['hash'] = '9C20148E2BAE9A1CA3CD114DD102AA93';

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->returnResult);
        $wanMeiPay->verifyOrderPayment([]);
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

        $this->returnResult['state'] = '2';
        $this->returnResult['hash'] = '8066FE39594878D537EB12826AC1E7D3';

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->returnResult);
        $wanMeiPay->verifyOrderPayment([]);
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

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->returnResult);
        $wanMeiPay->verifyOrderPayment($entry);
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
            'id' => '201805240000046339',
            'amount' => '123',
        ];

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->returnResult);
        $wanMeiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805240000046339',
            'amount' => '1',
        ];

        $wanMeiPay = new WanMeiPay();
        $wanMeiPay->setPrivateKey('test');
        $wanMeiPay->setOptions($this->returnResult);
        $wanMeiPay->verifyOrderPayment($entry);

        $this->assertEquals('{"message":"成功"}', $wanMeiPay->getMsg());
    }
}
