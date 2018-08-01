<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\JinMu;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class JinMuTest extends DurianTestCase
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
     * 訂單參數
     *
     * @var array
     */
    private $options;

    /**
     * 返回結果
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

        $this->options = [
            'number' => 'B80030',
            'amount' => '1',
            'orderId' => '201805070000011734',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://www.seafood.help/',
            'verify_url' => 'payment.https.pay.jingmugukj.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'errcode' => '0',
            'orderno' => '201805070000011734',
            'total_fee' => 100,
            'attach' => '201805070000011734',
            'sign' => '3bd085fda3e3b33661b289f01cdf0d6f',
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

        $jinMu = new JinMu();
        $jinMu->getVerifyData();
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

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->getVerifyData();
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

        $this->options['paymentVendorId'] = '9999';

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $jinMu->getVerifyData();
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

        $this->options['verify_url'] = '';

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $jinMu->getVerifyData();
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

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinMu = new JinMu();
        $jinMu->setContainer($this->container);
        $jinMu->setClient($this->client);
        $jinMu->setResponse($response);
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $jinMu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回msg
     */
    public function testPayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['err' => '-1'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinMu = new JinMu();
        $jinMu->setContainer($this->container);
        $jinMu->setClient($this->client);
        $jinMu->setResponse($response);
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $jinMu->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '当前已用户禁用所以支付通道',
            180130
        );

        $result = [
            'err' => '-1',
            'msg' => '当前已用户禁用所以支付通道',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinMu = new JinMu();
        $jinMu->setContainer($this->container);
        $jinMu->setClient($this->client);
        $jinMu->setResponse($response);
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $jinMu->getVerifyData();
    }

    /**
     * 測試WAP支付時沒有返回code_url
     */
    public function testWapPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'err' => '200',
            'msg' => '获取支付成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $this->options['paymentVendorId'] = '1098';

        $jinMu = new JinMu();
        $jinMu->setContainer($this->container);
        $jinMu->setClient($this->client);
        $jinMu->setResponse($response);
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $jinMu->getVerifyData();
    }

    /**
     * 測試掃碼支付時沒有返回code_img_url
     */
    public function testScanPayReturnWithoutCodeImgUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'err' => '200',
            'msg' => '获取支付成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinMu = new JinMu();
        $jinMu->setContainer($this->container);
        $jinMu->setClient($this->client);
        $jinMu->setResponse($response);
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $jinMu->getVerifyData();
    }

    /**
     * 測試掃碼支付
     */
    public function testScanPay()
    {
        $result = [
            'err' => '200',
            'msg' => '获取支付成功',
            'code_img_url' => 'https://pay.jingmugukj.com/qrcode?id=a4154d0f84a4737435f4ec00af1f3d66',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinMu = new JinMu();
        $jinMu->setContainer($this->container);
        $jinMu->setClient($this->client);
        $jinMu->setResponse($response);
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $data = $jinMu->getVerifyData();

        $this->assertEquals('a4154d0f84a4737435f4ec00af1f3d66', $data['params']['id']);
        $this->assertEquals('https://pay.jingmugukj.com/qrcode', $data['post_url']);
    }

    /**
     * 測試手機支付
     */
    public function testWapPay()
    {
        $result = [
            'err' => '200',
            'msg' => '获取支付成功',
            'code_url' => 'https://pay.jingmugukj.com/userpay?id=ee959a644495ef7e96d12f48e85f136a',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $this->options['paymentVendorId'] = '1098';

        $jinMu = new JinMu();
        $jinMu->setContainer($this->container);
        $jinMu->setClient($this->client);
        $jinMu->setResponse($response);
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->options);
        $data = $jinMu->getVerifyData();

        $this->assertEquals('https://pay.jingmugukj.com/userpay', $data['post_url']);
        $this->assertEquals('ee959a644495ef7e96d12f48e85f136a', $data['params']['id']);
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

        $jinMu = new JinMu();
        $jinMu->verifyOrderPayment([]);
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

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->verifyOrderPayment([]);
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

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->returnResult);
        $jinMu->verifyOrderPayment([]);
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

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->returnResult);
        $jinMu->verifyOrderPayment([]);
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

        $this->returnResult['errcode'] = 'fail';
        $this->returnResult['sign'] = '9675227788f3a5f910996d066ff4c647';

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->returnResult);
        $jinMu->verifyOrderPayment([]);
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

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->returnResult);
        $jinMu->verifyOrderPayment($entry);
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
            'id' => '201805070000011734',
            'amount' => '12',
        ];

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->returnResult);
        $jinMu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805070000011734',
            'amount' => '1',
        ];

        $jinMu = new JinMu();
        $jinMu->setPrivateKey('test');
        $jinMu->setOptions($this->returnResult);
        $jinMu->verifyOrderPayment($entry);

        $this->assertEquals('success', $jinMu->getMsg());
    }
}
