<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Pay32;
use Buzz\Message\Response;

class Pay32Test extends DurianTestCase
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

        $pay32 = new Pay32();
        $pay32->getVerifyData();
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

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->getVerifyData();
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
            'number' => '901504943502480648',
            'orderId' => '201710240000001777',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'paymentVendorId' => '9999',
            'orderCreateDate' => '2017-10-27 12:00:20',
            'verify_url' => 'payment.https.api.32pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->getVerifyData();
    }

    /**
     * 測試取提交網址時缺少verify_url
     */
    public function testGetPostUrlWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '901504943502480648',
            'orderId' => '201710240000001777',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-10-27 12:00:20',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->getVerifyData();
    }

    /**
     * 測試取提交網址未返回P_Errcode
     */
    public function testGetPostUrlNoReturnPErrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'orderId' => '201710240000001777',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-10-27 12:00:20',
            'verify_url' => 'payment.https.api.32pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"P_ErrMsg": "success","P_Notic": "",' .
            '"P_SubmitUrl": "https://api.32pay.com/pay/KDBank.aspx",' .
            '"P_PostKey":"500e219d953f84f9dcf386d603e00877"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setContainer($this->container);
        $pay32->setClient($this->client);
        $pay32->setResponse($response);
        $pay32->setOptions($sourceData);
        $pay32->getVerifyData();
    }

    /**
     * 測試取提交網址未返回P_ErrMsg
     */
    public function testGetPostUrlNoReturnPErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'orderId' => '201710240000001777',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-10-27 12:00:20',
            'verify_url' => 'payment.https.api.32pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"P_Errcode": "0","P_Notic": "",' .
            '"P_SubmitUrl": "https://api.32pay.com/pay/KDBank.aspx",' .
            '"P_PostKey":"500e219d953f84f9dcf386d603e00877"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setContainer($this->container);
        $pay32->setClient($this->client);
        $pay32->setResponse($response);
        $pay32->setOptions($sourceData);
        $pay32->getVerifyData();
    }

    /**
     * 測試取提交網址返回P_Errcode不等於0
     */
    public function testGetPostUrlReturnPErrcodeNotEqualToZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '加密串（P_PostKey）不能空！',
            180130
        );

        $sourceData = [
            'number' => '901504943502480648',
            'orderId' => '201710240000001777',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-10-27 12:00:20',
            'verify_url' => 'payment.https.api.32pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"P_Errcode": "110","P_ErrMsg": "加密串（P_PostKey）不能空！",' .
            '"P_Notic": null,"P_SubmitUrl": null,"P_PostKey": null}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setContainer($this->container);
        $pay32->setClient($this->client);
        $pay32->setResponse($response);
        $pay32->setOptions($sourceData);
        $pay32->getVerifyData();
    }

    /**
     * 測試取提交網址未返回P_SubmitUrl
     */
    public function testGetPostUrlNoReturnPSubmitUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'orderId' => '201710240000001777',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-10-27 12:00:20',
            'verify_url' => 'payment.https.api.32pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"P_Errcode": "0","P_ErrMsg": "success","P_Notic": "",' .
            '"P_PostKey":"500e219d953f84f9dcf386d603e00877"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setContainer($this->container);
        $pay32->setClient($this->client);
        $pay32->setResponse($response);
        $pay32->setOptions($sourceData);
        $pay32->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '901504943502480648',
            'orderId' => '201710240000001777',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-10-27 12:00:20',
            'verify_url' => 'payment.https.api.32pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"P_Errcode": "0","P_ErrMsg": "success","P_Notic": "",' .
            '"P_SubmitUrl": "https://api.32pay.com/pay/KDBank.aspx",' .
            '"P_PostKey":"500e219d953f84f9dcf386d603e00877"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setContainer($this->container);
        $pay32->setClient($this->client);
        $pay32->setResponse($response);
        $pay32->setOptions($sourceData);
        $data = $pay32->getVerifyData();

        $this->assertEquals('https://api.32pay.com/pay/KDBank.aspx', $data['post_url']);
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $sourceData = [
            'number' => '901504943502480648',
            'orderId' => '201710240000001777',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-10-27 12:00:20',
            'verify_url' => 'payment.https.api.32pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"P_Errcode": "0","P_ErrMsg": "success","P_Notic": "",' .
            '"P_SubmitUrl": "https://api.32pay.com/pay/KDBank.aspx",' .
            '"P_PostKey":"500e219d953f84f9dcf386d603e00877"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setContainer($this->container);
        $pay32->setClient($this->client);
        $pay32->setResponse($response);
        $pay32->setOptions($sourceData);
        $data = $pay32->getVerifyData();

        $this->assertEquals('https://api.32pay.com/pay/KDBank.aspx', $data['post_url']);
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

        $pay32 = new Pay32();
        $pay32->verifyOrderPayment([]);
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

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳P_PostKey
     */
    public function testReturnWithoutPPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'P_UserId' => '1000797',
            'P_OrderId' => '201710270000001886',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => 'php1test',
            'P_Price' => '0.0200',
            'P_Quantity' => '1',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_ErrMsg' => '支付成功',
        ];

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->verifyOrderPayment([]);
    }

    /**
     * 測試返回時P_PostKey簽名驗證錯誤
     */
    public function testReturnPPostKeyVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'P_UserId' => '1000797',
            'P_OrderId' => '201710270000001886',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => 'php1test',
            'P_Price' => '0.0200',
            'P_Quantity' => '1',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_PostKey' => 'b63101bab962c678db3bb0e632503fb1',
            'P_ErrMsg' => '支付成功',
        ];

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->verifyOrderPayment([]);
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
            'P_UserId' => '1000797',
            'P_OrderId' => '201710270000001886',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => 'php1test',
            'P_Price' => '0.0200',
            'P_Quantity' => '1',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '1',
            'P_PostKey' => '0e30635ecc0bf16cc012b0c0626de38c',
            'P_ErrMsg' => '支付失敗',
        ];

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->verifyOrderPayment([]);
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
            'P_UserId' => '1000797',
            'P_OrderId' => '201710270000001886',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => 'php1test',
            'P_Price' => '0.0200',
            'P_Quantity' => '1',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_PostKey' => '94834a6fe9230464479f7236ac5fb23c',
            'P_ErrMsg' => '支付成功',
        ];

        $entry = [
            'id' => '201710270000001887',
            'amount' => '0.02',
        ];

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

         $sourceData = [
            'P_UserId' => '1000797',
            'P_OrderId' => '201710270000001886',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => 'php1test',
            'P_Price' => '0.0200',
            'P_Quantity' => '1',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_PostKey' => '94834a6fe9230464479f7236ac5fb23c',
            'P_ErrMsg' => '支付成功',
        ];

        $entry = [
            'id' => '201710270000001886',
            'amount' => '1',
        ];

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'P_UserId' => '1000797',
            'P_OrderId' => '201710270000001886',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => 'php1test',
            'P_Price' => '0.0200',
            'P_Quantity' => '1',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_PostKey' => '94834a6fe9230464479f7236ac5fb23c',
            'P_ErrMsg' => '支付成功',
        ];

        $entry = [
            'id' => '201710270000001886',
            'amount' => '0.02',
        ];

        $pay32 = new Pay32();
        $pay32->setPrivateKey('test');
        $pay32->setOptions($sourceData);
        $pay32->verifyOrderPayment($entry);

        $this->assertEquals('ErrCode=0', $pay32->getMsg());
    }
}
