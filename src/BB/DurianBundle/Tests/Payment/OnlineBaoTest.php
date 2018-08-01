<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\OnlineBao;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class OnlineBaoTest extends DurianTestCase
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

        $onlineBao = new OnlineBao();
        $onlineBao->getVerifyData();
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

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->getVerifyData();
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
            'number' => '666330247330001',
            'amount' => '100',
            'username' => 'php1test',
            'orderId' => '201709050000004580',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://pay.in-action.tw/',
        ];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->getVerifyData();
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
            'number' => '666330247330001',
            'amount' => '9453',
            'username' => 'php1test',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => '',
        ];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->getVerifyData();
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
            'number' => '666440358110001',
            'amount' => '0.01',
            'username' => 'php1test',
            'orderId' => '201709050000004331',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'barCode' => 'weixin://wxpay/bizpayurl?pr=DFvRow5',
            'merchno' => '666440358110001',
            'message' => '下单成功',
            'refno' => '103758031',
            'traceno' => '201709050000004331',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $onlineBao = new OnlineBao();
        $onlineBao->setContainer($this->container);
        $onlineBao->setClient($this->client);
        $onlineBao->setResponse($response);
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败,找不到二维码路由信息',
            180130
        );

        $options = [
            'number' => '666440358110001',
            'amount' => '0.01',
            'username' => 'php1test',
            'orderId' => '201709050000004331',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merchno' => '666440358110001',
            'message' => '交易失败,找不到二维码路由信息',
            'respCode' => '58',
            'traceno' => '201709050000004331',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $onlineBao = new OnlineBao();
        $onlineBao->setContainer($this->container);
        $onlineBao->setClient($this->client);
        $onlineBao->setResponse($response);
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->getVerifyData();
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
            'number' => '666440358110001',
            'amount' => '0.01',
            'username' => 'php1test',
            'orderId' => '201709050000004331',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merchno' => '666440358110001',
            'message' => '下单成功',
            'refno' => '103758031',
            'respCode' => '00',
            'traceno' => '201709050000004331',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $onlineBao = new OnlineBao();
        $onlineBao->setContainer($this->container);
        $onlineBao->setClient($this->client);
        $onlineBao->setResponse($response);
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '666440358110001',
            'amount' => '0.01',
            'username' => 'php1test',
            'orderId' => '201709050000004331',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'barCode' => 'weixin://wxpay/bizpayurl?pr=DFvRow5',
            'merchno' => '666440358110001',
            'message' => '下单成功',
            'refno' => '103758031',
            'respCode' => '00',
            'traceno' => '201709050000004331',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $onlineBao = new OnlineBao();
        $onlineBao->setContainer($this->container);
        $onlineBao->setClient($this->client);
        $onlineBao->setResponse($response);
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $data = $onlineBao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=DFvRow5', $onlineBao->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testWapPay()
    {
        $options = [
            'number' => '666440358110001',
            'amount' => '0.01',
            'username' => 'php1test',
            'orderId' => '201709050000004331',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'barCode' => 'http://a.cc8pay.com/api/wap/?url=https://qr.alipay.com/bax019883ti8p',
            'merchno' => '666440358110001',
            'message' => '交易成功',
            'refno' => '103758031',
            'respCode' => '00',
            'traceno' => '201709050000004331',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $onlineBao = new OnlineBao();
        $onlineBao->setContainer($this->container);
        $onlineBao->setClient($this->client);
        $onlineBao->setResponse($response);
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $data = $onlineBao->getVerifyData();

        $this->assertEquals('http://a.cc8pay.com/api/wap/?url=https://qr.alipay.com/bax019883ti8p', $data['act_url']);
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

        $onlineBao = new OnlineBao();
        $onlineBao->verifyOrderPayment([]);
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

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->verifyOrderPayment([]);
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
            'merchno' => '666330247330001',
            'status' => '1',
            'traceno' => '201709060000004378',
            'orderno' => '103838851',
            'merchName' => '测试商户-下发',
            'channelOrderno' => '',
            'amount' => '1.01',
            'transDate' => '2017-09-06',
            'channelTraceno' => '',
            'transTime' => '08:37:05',
            'payType' => '1',
            'openId' => 'https://qr.alipay.com/bax02290wn9ucvhlg9ar60de',
        ];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->verifyOrderPayment([]);
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
            'merchno' => '666330247330001',
            'status' => '1',
            'traceno' => '201709060000004378',
            'orderno' => '103838851',
            'merchName' => '测试商户-下发',
            'channelOrderno' => '',
            'amount' => '1.01',
            'transDate' => '2017-09-06',
            'channelTraceno' => '',
            'transTime' => '08:37:05',
            'payType' => '1',
            'signature' => 'EC7CAB65DE3CCC66501D91489C543EE2',
            'openId' => 'https://qr.alipay.com/bax02290wn9ucvhlg9ar60de',
        ];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'merchno' => '666330247330001',
            'status' => '0',
            'traceno' => '201709060000004378',
            'orderno' => '103838851',
            'merchName' => '测试商户-下发',
            'channelOrderno' => '',
            'amount' => '1.01',
            'transDate' => '2017-09-06',
            'channelTraceno' => '',
            'transTime' => '08:37:05',
            'payType' => '1',
            'signature' => '0A9B9E9939DCFB96BB5232CAA55BDC42',
            'openId' => 'https://qr.alipay.com/bax02290wn9ucvhlg9ar60de',
        ];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->verifyOrderPayment([]);
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
            'merchno' => '666330247330001',
            'status' => '2',
            'traceno' => '201709060000004378',
            'orderno' => '103838851',
            'merchName' => '测试商户-下发',
            'channelOrderno' => '',
            'amount' => '1.01',
            'transDate' => '2017-09-06',
            'channelTraceno' => '',
            'transTime' => '08:37:05',
            'payType' => '1',
            'signature' => 'EB02C9B6E5EAB1F74030FF83DCEE92CD',
            'openId' => 'https://qr.alipay.com/bax02290wn9ucvhlg9ar60de',
        ];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->verifyOrderPayment([]);
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
            'merchno' => '666330247330001',
            'status' => '1',
            'traceno' => '201709060000004378',
            'orderno' => '103838851',
            'merchName' => '测试商户-下发',
            'channelOrderno' => '',
            'amount' => '1.01',
            'transDate' => '2017-09-06',
            'channelTraceno' => '',
            'transTime' => '08:37:05',
            'payType' => '1',
            'signature' => '2EF7AFDE83C88CF8B13BE129F60DA1BE',
            'openId' => 'https://qr.alipay.com/bax02290wn9ucvhlg9ar60de',
        ];

        $entry = ['id' => '9453'];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->verifyOrderPayment($entry);
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
            'merchno' => '666330247330001',
            'status' => '1',
            'traceno' => '201709060000004378',
            'orderno' => '103838851',
            'merchName' => '测试商户-下发',
            'channelOrderno' => '',
            'amount' => '1.01',
            'transDate' => '2017-09-06',
            'channelTraceno' => '',
            'transTime' => '08:37:05',
            'payType' => '1',
            'signature' => '2EF7AFDE83C88CF8B13BE129F60DA1BE',
            'openId' => 'https://qr.alipay.com/bax02290wn9ucvhlg9ar60de',
        ];

        $entry = [
            'id' => '201709060000004378',
            'amount' => '1',
        ];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'merchno' => '666330247330001',
            'status' => '1',
            'traceno' => '201709060000004378',
            'orderno' => '103838851',
            'merchName' => '测试商户-下发',
            'channelOrderno' => '',
            'amount' => '1.01',
            'transDate' => '2017-09-06',
            'channelTraceno' => '',
            'transTime' => '08:37:05',
            'payType' => '1',
            'signature' => '2EF7AFDE83C88CF8B13BE129F60DA1BE',
            'openId' => 'https://qr.alipay.com/bax02290wn9ucvhlg9ar60de',
        ];

        $entry = [
            'id' => '201709060000004378',
            'amount' => '1.01',
        ];

        $onlineBao = new OnlineBao();
        $onlineBao->setPrivateKey('test');
        $onlineBao->setOptions($options);
        $onlineBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $onlineBao->getMsg());
    }
}
