<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ShengYingPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class ShengYingPayTest extends DurianTestCase
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
            'amount' => '1',
            'orderId' => '201805240000046330',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://www.seafood.help/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aj.htcsys.com',
        ];

        $this->returnResult = [
            'sign' => 'B282ED3BCAE4A7C6B5B5D54A11798F67',
            'tradeDate' => '2018-05-24',
            'tradeStatus' => '1',
            'tradeNo' => '201805240000046330',
            'tradeTime' => '14:00:41',
            'pt' => 'alipay',
            'tradeAmount' => '50.00',
            'merchNo' => '7350144680001',
            'channelNo' => '3520107',
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->getVerifyData();
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->getVerifyData();
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->option);
        $shengYingPay->getVerifyData();
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->option);
        $shengYingPay->getVerifyData();
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setContainer($this->container);
        $shengYingPay->setClient($this->client);
        $shengYingPay->setResponse($response);
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->option);
        $shengYingPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respInfo
     */
    public function testPayReturnWithoutRespInfo()
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setContainer($this->container);
        $shengYingPay->setClient($this->client);
        $shengYingPay->setResponse($response);
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->option);
        $shengYingPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '系统异常,其他错误,签名有误',
            180130
        );

        $result = [
            'respCode' => 'A0',
            'respInfo' => '系统异常,其他错误,签名有误',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setContainer($this->container);
        $shengYingPay->setClient($this->client);
        $shengYingPay->setResponse($response);
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->option);
        $shengYingPay->getVerifyData();
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

        $result = [
            'channelNo' => '3525207',
            'merchNo' => '9527',
            'respCode' => '0000',
            'respInfo' => '成功',
            'tradeNo' => '201805240000046330',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setContainer($this->container);
        $shengYingPay->setClient($this->client);
        $shengYingPay->setResponse($response);
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->option);
        $shengYingPay->getVerifyData();
    }

    /**
     * 測試QQ二維支付
     */
    public function testQQScanPay()
    {
        $this->option['paymentVendorId'] = '1103';

        $result = [
            'channelNo' => '3522373',
            'merchNo' => '9527',
            'payUrl' => 'https://qpay.qq.com/qr/5332bfd0',
            'respCode' => '0000',
            'respInfo' => '成功',
            'tradeNo' => '201805240000046330',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setContainer($this->container);
        $shengYingPay->setClient($this->client);
        $shengYingPay->setResponse($response);
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->option);
        $data = $shengYingPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/5332bfd0', $shengYingPay->getQrcode());
    }

    /**
     * 測試支付寶手機支付
     */
    public function testAlipayPhonePay()
    {
        $result = [
            'channelNo' => '3525207',
            'merchNo' => '9527',
            'payUrl' => 'http://api.y8pay.com/jk/pay/wapsyt.html?refno=10039881065',
            'respCode' => '0000',
            'respInfo' => '成功',
            'tradeNo' => '201805240000046330',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setContainer($this->container);
        $shengYingPay->setClient($this->client);
        $shengYingPay->setResponse($response);
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->option);
        $data = $shengYingPay->getVerifyData();

        $this->assertEquals('http://api.y8pay.com/jk/pay/wapsyt.html', $data['post_url']);
        $this->assertEquals('10039881065', $data['params']['refno']);
        $this->assertEquals('GET', $shengYingPay->getPayMethod());
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->verifyOrderPayment([]);
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->verifyOrderPayment([]);
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->returnResult);
        $shengYingPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'D62AAA3C05134E14BED309A259C0DCE9';

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->returnResult);
        $shengYingPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['tradeStatus'] = '0';
        $this->returnResult['sign'] = 'EE69D2DB5478A46D0C555CBE46D1CDAA';

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->returnResult);
        $shengYingPay->verifyOrderPayment([]);
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

        $this->returnResult['tradeStatus'] = '2';
        $this->returnResult['sign'] = 'DAD96907E91A3F0DAA31D77A89CDF712';

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->returnResult);
        $shengYingPay->verifyOrderPayment([]);
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

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->returnResult);
        $shengYingPay->verifyOrderPayment($entry);
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
            'id' => '201805240000046330',
            'amount' => '123',
        ];

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->returnResult);
        $shengYingPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805240000046330',
            'amount' => '50',
        ];

        $shengYingPay = new ShengYingPay();
        $shengYingPay->setPrivateKey('test');
        $shengYingPay->setOptions($this->returnResult);
        $shengYingPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $shengYingPay->getMsg());
    }
}
