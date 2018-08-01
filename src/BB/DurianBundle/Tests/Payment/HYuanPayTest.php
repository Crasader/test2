<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\HYuanPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class HYuanPayTest extends DurianTestCase
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
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201804120000046021',
            'notify_url' => 'http://www.seafood.help/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payapi.3vpay.net',
        ];

        $this->returnResult = [
            'partner' => '698000098',
            'ordernumber' => '201804120000046021',
            'orderstatus' => '1',
            'paymoney' => '1.00',
            'sysnumber' => 'HY1804121136190356',
            'attach' => '',
            'sign' => '4731fc5049168331ec146c2ff5903321',
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

        $hYuanPay = new HYuanPay();
        $hYuanPay->getVerifyData();
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

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->getVerifyData();
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

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->option);
        $hYuanPay->getVerifyData();
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

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->option);
        $hYuanPay->getVerifyData();
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

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hYuanPay = new HYuanPay();
        $hYuanPay->setContainer($this->container);
        $hYuanPay->setClient($this->client);
        $hYuanPay->setResponse($response);
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->option);
        $hYuanPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回message
     */
    public function testPayReturnWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['status' => '0'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hYuanPay = new HYuanPay();
        $hYuanPay->setContainer($this->container);
        $hYuanPay->setClient($this->client);
        $hYuanPay->setResponse($response);
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->option);
        $hYuanPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单号已重复',
            180130
        );

        $result = [
            'status' => '0',
            'message' => '订单号已重复',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hYuanPay = new HYuanPay();
        $hYuanPay->setContainer($this->container);
        $hYuanPay->setClient($this->client);
        $hYuanPay->setResponse($response);
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->option);
        $hYuanPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回qrurl
     */
    public function testPayReturnWithoutQrurl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'status' => '1',
            'message' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hYuanPay = new HYuanPay();
        $hYuanPay->setContainer($this->container);
        $hYuanPay->setClient($this->client);
        $hYuanPay->setResponse($response);
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->option);
        $hYuanPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'status' => '1',
            'message' => '',
            'qrurl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hYuanPay = new HYuanPay();
        $hYuanPay->setContainer($this->container);
        $hYuanPay->setClient($this->client);
        $hYuanPay->setResponse($response);
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->option);
        $data = $hYuanPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183', $hYuanPay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $this->option['paymentVendorId'] = '1';

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->option);
        $data = $hYuanPay->getVerifyData();

        $this->assertEquals('3.0', $data['version']);
        $this->assertEquals('HY.online.interface', $data['method']);
        $this->assertEquals('9527', $data['partner']);
        $this->assertEquals('ICBC', $data['banktype']);
        $this->assertEquals('1.00', $data['paymoney']);
        $this->assertEquals('201804120000046021', $data['ordernumber']);
        $this->assertEquals('http://www.seafood.help/', $data['callbackurl']);
        $this->assertEquals('', $data['hrefbackurl']);
        $this->assertEquals('', $data['attach']);
        $this->assertEquals('0', $data['isshow']);
        $this->assertEquals('3ae104c3e40e6464c55b0f843be231c4', $data['sign']);
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

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->verifyOrderPayment([]);
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

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->returnResult);
        $hYuanPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'bb35e00edabd7427c9cb60b139e80d63';

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->returnResult);
        $hYuanPay->verifyOrderPayment([]);
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

        $this->returnResult['orderstatus'] = '0';
        $this->returnResult['sign'] = 'd4e87bee3983785114ca35bdebb8cb37';

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->returnResult);
        $hYuanPay->verifyOrderPayment([]);
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

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->returnResult);
        $hYuanPay->verifyOrderPayment($entry);
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
            'id' => '201804120000046021',
            'amount' => '123',
        ];

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->returnResult);
        $hYuanPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201804120000046021',
            'amount' => '1',
        ];

        $hYuanPay = new HYuanPay();
        $hYuanPay->setPrivateKey('test');
        $hYuanPay->setOptions($this->returnResult);
        $hYuanPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $hYuanPay->getMsg());
    }
}
