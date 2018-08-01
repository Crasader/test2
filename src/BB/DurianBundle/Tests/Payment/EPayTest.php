<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\EPay;
use Buzz\Message\Response;

class EPayTest extends DurianTestCase
{
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

    /**
     * 二維支付回傳參數
     *
     * @var array
     */
    private $qrcodeResult;

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

        $this->option = [
            'number' => '999941000821',
            'orderId' => '201807160000005451',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'amount' => '50',
            'paymentVendorId' => '1102',
            'orderCreateDate' => '2018-07-16 14:50:06',
            'ip' => '192.168.29.94',
        ];

        $this->returnResult = [
            'code' => '10000',
            'message' => '下单成功',
            'merchNo' => '999941000821',
            'amount' => '2.0',
            'cOrderNo' => '201807160000005451',
            'pOrderNo' => '201807160000005487',
            'status' => '4',
            'sign' => '252997b4da5166d2b3c14cbfea0acdd1',
        ];

        $this->qrcodeResult = [
            'serverCode' => "ser2001",
            'amount' => "50",
            'code' => "10000",
            'merchNo' => "999941000821",
            'cOrderNo' => "201807160000005412",
            'pOrderNo' => "201807161232539096043",
            'sign' => "72e61a124669789e8b9ba33a8279451f",
            'payUrl' => "https://qr.95516.com/00010000/62222398601660103592632963215836",
            'message' => "下单成功",
            'command' => "cmd101",
            'status' => '1',
        ];

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();
        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(['{a:1}']);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ePay = new EPay();
        $ePay->getVerifyData();
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

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions([]);
        $ePay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->option['paymentVendorId'] = '9999';

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions($this->option);
        $ePay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions($this->option);
        $encodeData = $ePay->getVerifyData();

        $this->assertEquals('cmd105', $encodeData['command']);
        $this->assertEquals('ser2001', $encodeData['serverCode']);
        $this->assertEquals('999941000821', $encodeData['merchNo']);
        $this->assertEquals('2.0', $encodeData['version']);
        $this->assertEquals('utf-8', $encodeData['charset']);
        $this->assertEquals('CNY', $encodeData['currency']);
        $this->assertEquals('192.168.29.94', $encodeData['reqIp']);
        $this->assertEquals('20180716145006', $encodeData['reqTime']);
        $this->assertEquals('MD5', $encodeData['signType']);
        $this->assertEquals('9', $encodeData['payType']);
        $this->assertEquals('201807160000005451', $encodeData['cOrderNo']);
        $this->assertEquals('50', $encodeData['amount']);
        $this->assertEquals('201807160000005451', $encodeData['goodsName']);
        $this->assertEquals('201807160000005451', $encodeData['goodsNum']);
        $this->assertEquals('201807160000005451', $encodeData['goodsDesc']);
        $this->assertEquals('201807160000005451', $encodeData['memberId']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['returnUrl']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['notifyUrl']);
        $this->assertEquals('25212184f773173420d5f45831eeb1f5', $encodeData['sign']);
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->option['verify_url'] = "";
        $this->option['paymentVendorId'] = '1111';

        $response = new Response();
        $response->setContent(json_encode($this->qrcodeResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setContainer($this->container);
        $ePay->setClient($this->client);
        $ePay->setResponse($response);
        $ePay->setOptions($this->option);
        $ePay->getVerifyData();
    }

    /**
     * 測試二維支付缺少回傳code
     */
    public function testQrcodePayWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->qrcodeResult['code']);

        $this->option['paymentVendorId'] = '1111';

        $response = new Response();
        $response->setContent(json_encode($this->qrcodeResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setContainer($this->container);
        $ePay->setClient($this->client);
        $ePay->setResponse($response);
        $ePay->setOptions($this->option);
        $ePay->getVerifyData();
    }

    /**
     * 測試二維支付缺少回傳message
     */
    public function testQrcodePayWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->qrcodeResult['message']);

        $this->option['paymentVendorId'] = '1111';

        $response = new Response();
        $response->setContent(json_encode($this->qrcodeResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setContainer($this->container);
        $ePay->setClient($this->client);
        $ePay->setResponse($response);
        $ePay->setOptions($this->option);
        $ePay->getVerifyData();
    }

    /**
     * 測試二維支付回傳交易失敗
     */
    public function testQrcodePayFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '此通道交易最低限额2',
            180130
        );

        $this->qrcodeResult['code'] = "10001";
        $this->qrcodeResult['message'] = "此通道交易最低限额2";

        $this->option['paymentVendorId'] = '1111';

        $response = new Response();
        $response->setContent(json_encode($this->qrcodeResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setContainer($this->container);
        $ePay->setClient($this->client);
        $ePay->setResponse($response);
        $ePay->setOptions($this->option);
        $ePay->getVerifyData();
    }

    /**
     * 測試二維支付回傳缺少payUrl
     */
    public function testQrcodePayWithoutPayURL()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->qrcodeResult['payUrl']);

        $this->option['paymentVendorId'] = '1111';

        $response = new Response();
        $response->setContent(json_encode($this->qrcodeResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setContainer($this->container);
        $ePay->setClient($this->client);
        $ePay->setResponse($response);
        $ePay->setOptions($this->option);
        $ePay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $this->option['paymentVendorId'] = '1111';

        $response = new Response();
        $response->setContent(json_encode($this->qrcodeResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setContainer($this->container);
        $ePay->setClient($this->client);
        $ePay->setResponse($response);
        $ePay->setOptions($this->option);
        $verifyData = $ePay->getVerifyData();
        $this->assertEmpty($verifyData);
        $this->assertEquals("https://qr.95516.com/00010000/62222398601660103592632963215836", $ePay->getQrcode());
    }

    /**
     * 測試返回沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ePay = new EPay();
        $ePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions($this->returnResult);
        $ePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign'] = '65e75ffec99e62438d4ea1e0ba5bebe8';

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions($this->returnResult);
        $ePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['status'] = '2';
        $this->returnResult['sign'] = '362948ccea828ef35b1a8591290f3bc6';

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions($this->returnResult);
        $ePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回單號不正確
     */
    public function testReturnPaymentOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '20180711085851981018'];

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions($this->returnResult);
        $ePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確
     */
    public function testReturnPaymentOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201807160000005451',
            'amount' => '100',
        ];

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions($this->returnResult);
        $ePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201807160000005451',
            'amount' => '2',
        ];

        $ePay = new EPay();
        $ePay->setPrivateKey('test');
        $ePay->setOptions($this->returnResult);
        $ePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $ePay->getMsg());
    }
}
