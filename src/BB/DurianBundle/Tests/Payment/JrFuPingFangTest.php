<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JrFuPingFang;
use Buzz\Message\Response;

class JrFuPingFangTest extends DurianTestCase
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
     * 對外返回的參數
     *
     * @var array
     */
    private $verifyResult;

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

        $this->option = [
            'number' => 'P88882018071910000069',
            'orderId' => '201807200000012640',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'amount' => '1.00',
            'orderCreateDate' => '2018-07-20 15:26:35',
            'ip' => '192.168.0.1',
            'verify_ip' => ['172.26.54.41', '172.26.54.42'],
            'verify_url' => 'payment.http.118.31.21.217',
        ];

        $this->verifyResult = [
            'resultCode' => '0000',
            'errMsg' => '',
            'sign' => '8B9557C0BEB4B8974DFC5DC955B9B21A',
            'payMsg' => 'http://47.91.210.61:13002/getqrcode2?parm=FKX05159EPGONG3AENWIF8&t=1532057660461&' .
                'orderno=2018072310041751&merchantid=CA0000260003',
        ];

        $this->returnResult = [
            'goodsName' => '201807200000012640',
            'merchantNo' => 'P88882018071910000069',
            'orderPrice' => '1.00',
            'orderTime' => '20180720175157',
            'outOrderNo' => '201807200000012640',
            'successTime' => '201807200000012640',
            'tradeNo' => 'P77772018072310041751',
            'tradeStatus' => 'SUCCESS',
            'tradeType' => 'ali_pay_wap_t0',
            'sign' => 'FA0625E770EB397DA9DF22F431CD5C01',
        ];
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

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->getVerifyData();
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

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions([]);
        $jrFuPingFang->getVerifyData();
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

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->option);
        $jrFuPingFang->getVerifyData();
    }

    /**
     * 測試支付對外時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
    );

        $this->option['verify_url'] = '';

        $yiHuiFu = new JrFuPingFang();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->option);
        $yiHuiFu->getVerifyData();
    }

    /**
     * 測試對外返回沒有resultCode
     */
    public function testVerifyResultWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['resultCode']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setContainer($this->container);
        $jrFuPingFang->setClient($this->client);
        $jrFuPingFang->setResponse($response);
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->option);
        $jrFuPingFang->getVerifyData();
    }

    /**
     * 測試對外返回不成功
     */
    public function testVerifyResultNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单签名异常',
            180130
        );

        $result = [
            'resultCode' => '9998',
            'errMsg' => '订单签名异常',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setContainer($this->container);
        $jrFuPingFang->setClient($this->client);
        $jrFuPingFang->setResponse($response);
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->option);
        $jrFuPingFang->getVerifyData();
    }

    /**
     * 測試對外返回不成功且無錯誤訊息
     */
    public function testVerifyResultNotSuccessAndNoErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = ['resultCode' => '9998'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setContainer($this->container);
        $jrFuPingFang->setClient($this->client);
        $jrFuPingFang->setResponse($response);
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->option);
        $jrFuPingFang->getVerifyData();
    }

    /**
     * 測試支付未返回payMsg
     */
    public function testVerifyResultWithoutPayMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['payMsg']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setContainer($this->container);
        $jrFuPingFang->setClient($this->client);
        $jrFuPingFang->setResponse($response);
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->option);
        $jrFuPingFang->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setContainer($this->container);
        $jrFuPingFang->setClient($this->client);
        $jrFuPingFang->setResponse($response);
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->option);
        $data = $jrFuPingFang->getVerifyData();

        $payMsg = 'http://47.91.210.61:13002/getqrcode2?parm=FKX05159EPGONG3AENWIF8&' .
            't=1532057660461&orderno=2018072310041751&merchantid=CA0000260003';

        $this->assertEmpty($data);
        $this->assertSame($payMsg, $jrFuPingFang->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $this->option['paymentVendorId'] = '1098';

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setContainer($this->container);
        $jrFuPingFang->setClient($this->client);
        $jrFuPingFang->setResponse($response);
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->option);
        $data = $jrFuPingFang->getVerifyData();

        $this->assertEquals('http://47.91.210.61:13002/getqrcode2', $data['post_url']);
        $this->assertEquals('FKX05159EPGONG3AENWIF8', $data['params']['parm']);
        $this->assertEquals('1532057660461', $data['params']['t']);
        $this->assertEquals('2018072310041751', $data['params']['orderno']);
        $this->assertEquals('CA0000260003', $data['params']['merchantid']);
        $this->assertEquals('GET', $jrFuPingFang->getPayMethod());
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->returnResult);
        $jrFuPingFang->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->returnResult);
        $jrFuPingFang->verifyOrderPayment([]);
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

        $this->returnResult['tradeStatus'] = 'FAILED';
        $this->returnResult['sign'] = '48FF1A0EB7F4ECCFFC85A8F936BC743A';

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->returnResult);
        $jrFuPingFang->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnPaymentOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201805070000005138'];

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->returnResult);
        $jrFuPingFang->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnPaymentOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201807200000012640',
            'amount' => '100',
        ];

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->returnResult);
        $jrFuPingFang->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201807200000012640',
            'amount' => '1',
        ];

        $jrFuPingFang = new JrFuPingFang();
        $jrFuPingFang->setPrivateKey('test');
        $jrFuPingFang->setOptions($this->returnResult);
        $jrFuPingFang->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jrFuPingFang->getMsg());
    }
}
