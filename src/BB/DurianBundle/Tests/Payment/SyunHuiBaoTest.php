<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SyunHuiBao;
use Buzz\Message\Response;

class SyunHuiBaoTest extends DurianTestCase
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

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->getVerifyData();
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

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->getVerifyData();
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
            'number' => '770110154990001',
            'amount' => '100',
            'orderId' => '201611290000000371',
            'paymentVendorId' => '1',
            'notify_url' => 'http://pay.return/',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->getVerifyData();
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
            'number' => '770110154990001',
            'amount' => '100',
            'orderId' => '201611290000000371',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => '',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->getVerifyData();
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

        $options = [
            'number' => '770110154990001',
            'amount' => '100',
            'orderId' => '201611290000000371',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=d3zzmGr","merchno":"770110154990001","message":"下单成功",' .
            '"refno":"800000275146","traceno":"201611290000000371"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败,交易金额过低,微信最低交易金额[0.02]',
            180130
        );

        $options = [
            'number' => '770110154990001',
            'amount' => '100',
            'orderId' => '201611300000000378',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"770110154990001","message":"交易失败,交易金额过低,微信最低交易金额[0.02]",' .
            '"respCode":"62","traceno":"201611300000000378"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '770110154990001',
            'amount' => '100',
            'orderId' => '201611300000000371',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"770110154990001","message":"下单成功","refno":"800000275146","respCode":"00",' .
            '"traceno":"201611300000000371"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '770110154990001',
            'amount' => '100',
            'orderId' => '201611290000000371',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=d3zzmGr","merchno":"770110154990001","message":"下单成功",' .
            '"refno":"800000275146","respCode":"00","traceno":"201611290000000371"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $data = $syunHuiBao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=d3zzmGr', $syunHuiBao->getQrcode());
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

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->verifyOrderPayment([]);
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

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未初始化變數
     */
    public function testReturnNoInitializeVarible()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'merchno' => '',
            'status' => '',
            'traceno' => '',
            'orderno' => '',
            'merchName' => '',
            'channelOrderno' => '',
            'amount' => '',
            'transDate' => '',
            'channelTraceno' => '',
            'transTime' => '',
            'payType' => '',
            'openId' => '',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->verifyOrderPayment([]);
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
            'merchno' => '770110154990001',
            'status' => '1',
            'traceno' => '201611290000000372',
            'orderno' => '800000279038',
            'merchName' => '1012-鸿翔浩宇',
            'channelOrderno' => '800000279038',
            'amount' => '1.00',
            'transDate' => '2016-11-29',
            'channelTraceno' => '800000279038',
            'transTime' => '16:20:25',
            'payType' => '2',
            'openId' => 'weixin://wxpay/bizpayurl?pr=6VY3gH3',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->verifyOrderPayment([]);
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
            'merchno' => '770110154990001',
            'status' => '1',
            'traceno' => '201611290000000372',
            'orderno' => '800000279038',
            'merchName' => '1012-鸿翔浩宇',
            'channelOrderno' => '800000279038',
            'amount' => '1.00',
            'transDate' => '2016-11-29',
            'channelTraceno' => '800000279038',
            'transTime' => '16:20:25',
            'payType' => '2',
            'signature' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ012345',
            'openId' => 'weixin://wxpay/bizpayurl?pr=6VY3gH3',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->verifyOrderPayment([]);
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

        $options = [
            'merchno' => '770110154990001',
            'status' => '2',
            'traceno' => '201611290000000372',
            'orderno' => '800000279038',
            'merchName' => '1012-鸿翔浩宇',
            'channelOrderno' => '800000279038',
            'amount' => '1.00',
            'transDate' => '2016-11-29',
            'channelTraceno' => '800000279038',
            'transTime' => '16:20:25',
            'payType' => '2',
            'signature' => '603E669380BA4BC114A5643397752E4A',
            'openId' => 'weixin://wxpay/bizpayurl?pr=6VY3gH3',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->verifyOrderPayment([]);
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
            'merchno' => '770110154990001',
            'status' => '1',
            'traceno' => '201611290000000372',
            'orderno' => '800000279038',
            'merchName' => '1012-鸿翔浩宇',
            'channelOrderno' => '800000279038',
            'amount' => '1.00',
            'transDate' => '2016-11-29',
            'channelTraceno' => '800000279038',
            'transTime' => '16:20:25',
            'payType' => '2',
            'signature' => '251CC281BE956193E46E2776FA6CDEAE',
            'openId' => 'weixin://wxpay/bizpayurl?pr=6VY3gH3',
        ];

        $entry = ['id' => '201611290000009999'];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->verifyOrderPayment($entry);
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
            'merchno' => '770110154990001',
            'status' => '1',
            'traceno' => '201611290000000372',
            'orderno' => '800000279038',
            'merchName' => '1012-鸿翔浩宇',
            'channelOrderno' => '800000279038',
            'amount' => '1.00',
            'transDate' => '2016-11-29',
            'channelTraceno' => '800000279038',
            'transTime' => '16:20:25',
            'payType' => '2',
            'signature' => '251CC281BE956193E46E2776FA6CDEAE',
            'openId' => 'weixin://wxpay/bizpayurl?pr=6VY3gH3',
        ];

        $entry = [
            'id' => '201611290000000372',
            'amount' => '0.1',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchno' => '770110154990001',
            'status' => '1',
            'traceno' => '201611290000000372',
            'orderno' => '800000279038',
            'merchName' => '1012-鸿翔浩宇',
            'channelOrderno' => '800000279038',
            'amount' => '1.00',
            'transDate' => '2016-11-29',
            'channelTraceno' => '800000279038',
            'transTime' => '16:20:25',
            'payType' => '2',
            'signature' => '251CC281BE956193E46E2776FA6CDEAE',
            'openId' => 'weixin://wxpay/bizpayurl?pr=6VY3gH3',
        ];

        $entry = [
            'id' => '201611290000000372',
            'amount' => '1.00',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $syunHuiBao->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000372',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線異常
     */
    public function testTrackingReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000372',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線失敗
     */
    public function testTrackingReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000372',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳結果為空
     */
    public function testTrackingReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000372',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000372',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢異常
     */
    public function testTrackingReturnWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '找不到交易',
            180123
        );

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000372',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"找不到交易","respCode":"25"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000368',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"交易失败[205235],交易处理中!","respCode":"0"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000999',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"支付失败","respCode":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單號錯誤
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000372',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"channelOrderno":"1898446","message":"交易成功","orderno":"800000279038",' .
            '"payType":"2","refno":"800000279038","respCode":"1","scanType":"2","traceno":"201611290000000371"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '770110154990001',
            'orderId' => '201611290000000372',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"channelOrderno":"1898446","message":"交易成功","orderno":"800000279038",' .
            '"payType":"2","refno":"800000279038","respCode":"1","scanType":"2","traceno":"201611290000000372"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $syunHuiBao = new SyunHuiBao();
        $syunHuiBao->setContainer($this->container);
        $syunHuiBao->setClient($this->client);
        $syunHuiBao->setResponse($response);
        $syunHuiBao->setPrivateKey('test');
        $syunHuiBao->setOptions($options);
        $syunHuiBao->paymentTracking();
    }
}
