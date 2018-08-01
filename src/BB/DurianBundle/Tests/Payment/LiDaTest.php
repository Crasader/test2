<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\LiDa;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class LiDaTest extends DurianTestCase
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
     * 對外取得accessToken成功返回的結果
     *
     * @var string
     */
    private $getTokenSuccessResult;

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

        $this->getTokenSuccessResult = '{"success":true,"value":{"accessToken":"' .
            '60c9d55a67d042a698fcb91f7aff0e9a824a492f6b7044b7a90da8395e28e857"' .
            ',"count":1,"maxCount":5000,"expireTime":7200},"errorCode":0,"message":null}';
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

        $liDa = new LiDa();
        $liDa->getVerifyData();
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

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->getVerifyData();
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
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://payment/return.php',
        ];

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
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
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => '',
        ];

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試取得accessToken時沒有返回success參數
     */
    public function testGetAccessTokenReturnWithoutSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{}';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:application/json;charset=UTF-8;');

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試取得accessToken時回傳錯誤訊息
     */
    public function testGetAccessTokenReturnMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '服务器发生错误',
            180130
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":false,"errorCode":-1,"message":"服务器发生错误"}';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:application/json;charset=UTF-8;');

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試取得accessToken時失敗且無訊息
     */
    public function testGetAccessTokenNotSuccessAndNoMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":false,"errorCode":-1}';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:application/json;charset=UTF-8;');

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試取得accessToken成功但無返回accessToken
     */
    public function testGetAccessTokenSuccessWithoutAccessToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"value":{"count":1,"maxCount":5000,"expireTime":7200},' .
            '"errorCode":0,"message":null}';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:application/json;charset=UTF-8;');

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回內容值
     */
    public function testPhonePayReturnWithoutContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->getTokenSuccessResult, $result);

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回success
     */
    public function testPhonePayReturnWithoutSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"value":"https://qpay.qq.com/qr/5c4fdb1c","errorCode":0,"message":null}';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->getTokenSuccessResult, $result);

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試手機支付時返回提交失敗且有錯誤訊息
     */
    public function testPhonePayReturnNotSuccessAndHasMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '可用支付通道下单失败',
            180130
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":false,"value":null,"errorCode":-1,"message":"可用支付通道下单失败"}';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->getTokenSuccessResult, $result);

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試手機支付時返回提交失敗
     */
    public function testPhonePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":false,"value":null,"errorCode":-1}';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->getTokenSuccessResult, $result);

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試手機支付時未返回提交網址參數
     */
    public function testPhonePayReturnWithoutValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"errorCode":0,"message":null}';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->getTokenSuccessResult, $result);

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->getVerifyData();
    }

    /**
     * 測試QQ手機支付
     */
    public function testQQWap()
    {
        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"value":"https://qpay.qq.com/qr/5fc42d39","errorCode":0,"message":null}';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->getTokenSuccessResult, $result);

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $data = $liDa->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals('https://qpay.qq.com/qr/5fc42d39', $data['post_url']);
    }

    /**
     * 測試支付寶手機支付
     */
    public function testAliPayWap()
    {
        $options = [
            'number' => 'LDM000000000000370',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://payment/return.php',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"success":true,"value":"http://pay.hzyunrui.xyz/Pay_Aliwap_callback.html?id=10814",' .
            '"errorCode":0,"message":null}';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->getTokenSuccessResult, $result);

        $liDa = new LiDa();
        $liDa->setContainer($this->container);
        $liDa->setClient($this->client);
        $liDa->setResponse($response);
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $data = $liDa->getVerifyData();

        $this->assertEquals('http://pay.hzyunrui.xyz/Pay_Aliwap_callback.html', $data['post_url']);
        $this->assertEquals('10814', $data['params']['id']);
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

        $liDa = new LiDa();
        $liDa->verifyOrderPayment([]);
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

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->verifyOrderPayment([]);
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
            'nonce' => '6d6c8045bcb24643be2aa98dfb95e0b2',
            'timestamp' => '1521691603541',
            'no' => '201803221205440125430657329',
            'merchantNo' => 'LDM000000000000413',
            'outTradeNo' => '201803220000010472',
            'productId' => '201803220000010472',
            'money' => '100',
            'date' => 'null',
            'tradeType' => 'T0',
            'body' => '201803220000010472',
            'detail' => '201803220000010472',
            'success' => true,
        ];

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->verifyOrderPayment([]);
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
            'sign' => 'test',
            'nonce' => '6d6c8045bcb24643be2aa98dfb95e0b2',
            'timestamp' => '1521691603541',
            'no' => '201803221205440125430657329',
            'merchantNo' => 'LDM000000000000413',
            'outTradeNo' => '201803220000010472',
            'productId' => '201803220000010472',
            'money' => '100',
            'date' => 'null',
            'tradeType' => 'T0',
            'body' => '201803220000010472',
            'detail' => '201803220000010472',
            'success' => true,
        ];

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->verifyOrderPayment([]);
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
            'sign' => 'CE62B5B8F431531C7F3B448482BCAF44',
            'nonce' => '6d6c8045bcb24643be2aa98dfb95e0b2',
            'timestamp' => '1521691603541',
            'no' => '201803221205440125430657329',
            'merchantNo' => 'LDM000000000000413',
            'outTradeNo' => '201803220000010472',
            'productId' => '201803220000010472',
            'money' => '100',
            'date' => 'null',
            'tradeType' => 'T0',
            'body' => '201803220000010472',
            'detail' => '201803220000010472',
            'success' => false,
        ];

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->verifyOrderPayment([]);
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
            'sign' => 'CE62B5B8F431531C7F3B448482BCAF44',
            'nonce' => '6d6c8045bcb24643be2aa98dfb95e0b2',
            'timestamp' => '1521691603541',
            'no' => '201803221205440125430657329',
            'merchantNo' => 'LDM000000000000413',
            'outTradeNo' => '201803220000010472',
            'productId' => '201803220000010472',
            'money' => '100',
            'date' => 'null',
            'tradeType' => 'T0',
            'body' => '201803220000010472',
            'detail' => '201803220000010472',
            'success' => true,
        ];

        $entry = ['id' => '666666'];

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->verifyOrderPayment($entry);
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
            'sign' => 'CE62B5B8F431531C7F3B448482BCAF44',
            'nonce' => '6d6c8045bcb24643be2aa98dfb95e0b2',
            'timestamp' => '1521691603541',
            'no' => '201803221205440125430657329',
            'merchantNo' => 'LDM000000000000413',
            'outTradeNo' => '201803220000010472',
            'productId' => '201803220000010472',
            'money' => '100',
            'date' => 'null',
            'tradeType' => 'T0',
            'body' => '201803220000010472',
            'detail' => '201803220000010472',
            'success' => true,
        ];

        $entry = [
            'id' => '201803220000010472',
            'amount' => '777',
        ];

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'sign' => 'CE62B5B8F431531C7F3B448482BCAF44',
            'nonce' => '6d6c8045bcb24643be2aa98dfb95e0b2',
            'timestamp' => '1521691603541',
            'no' => '201803221205440125430657329',
            'merchantNo' => 'LDM000000000000413',
            'outTradeNo' => '201803220000010472',
            'productId' => '201803220000010472',
            'money' => '100',
            'date' => 'null',
            'tradeType' => 'T0',
            'body' => '201803220000010472',
            'detail' => '201803220000010472',
            'success' => true,
        ];

        $entry = [
            'id' => '201803220000010472',
            'amount' => '1',
        ];

        $liDa = new LiDa();
        $liDa->setPrivateKey('test');
        $liDa->setOptions($options);
        $liDa->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $liDa->getMsg());
    }

    /**
     * 產生假對外返回物件
     *
     * @return Response
     */
    private function mockReponse()
    {
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->method('getStatusCode')
            ->willReturn(200);

        return $response;
    }
}
