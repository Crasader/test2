<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YunBao;
use Buzz\Message\Response;

class YunBaoTest extends DurianTestCase
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
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yunBao = new YunBao();
        $yunBao->getVerifyData();
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

        $option = ['number' => ''];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('1234');
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
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

        $option = [
            'number' => '9527',
            'paymentVendorId' => '999',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_url' => '',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試支付時返回缺少status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'message' => '签名失败参数格式校验错误',
            'result_code' => '0',
            'code_url' => 'weixin:seafood.help',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setContainer($this->container);
        $yunBao->setClient($this->client);
        $yunBao->setResponse($response);
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試支付時返回Status不等於0,且沒返回Message
     */
    public function testPayReturnStatusNotEqualZeroAndWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'status' => '1',
            'result_code' => '0',
            'code_url' => 'weixin:seafood.help',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setContainer($this->container);
        $yunBao->setClient($this->client);
        $yunBao->setResponse($response);
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試支付時返回Status不等於0
     */
    public function testPayReturnStatusNotEqualZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '未知错误',
            180130
        );

        $result = [
            'status' => '1',
            'message' => '未知错误',
            'result_code' => '0',
            'code_url' => 'weixin:seafood.help',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setContainer($this->container);
        $yunBao->setClient($this->client);
        $yunBao->setResponse($response);
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試支付時返回缺少result_code
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'status' => '0',
            'message' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setContainer($this->container);
        $yunBao->setClient($this->client);
        $yunBao->setResponse($response);
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試支付時返回缺少err_msg
     */
    public function testPayReturnWithoutErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'status' => '0',
            'message' => '成功',
            'result_code' => '1',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setContainer($this->container);
        $yunBao->setClient($this->client);
        $yunBao->setResponse($response);
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試支付時返回時result_code不等於0
     */
    public function testPayReturnResultCodeNotEqualZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '通訊失敗',
            180130
        );

        $result = [
            'status' => '0',
            'message' => '成功',
            'result_code' => '1',
            'err_msg' => '通訊失敗',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setContainer($this->container);
        $yunBao->setClient($this->client);
        $yunBao->setResponse($response);
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試支付時返回缺少code_url
     */
    public function testPayWithReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'status' => '0',
            'message' => '成功',
            'result_code' => '0',
            'err_msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setContainer($this->container);
        $yunBao->setClient($this->client);
        $yunBao->setResponse($response);
        $yunBao->setOptions($option);
        $yunBao->getVerifyData();
    }

    /**
     * 測試銀聯支付
     */
    public function testUnionPay()
    {
        $option = [
            'number' => '9527',
            'paymentVendorId' => '278',
            'amount' => '1.00',
            'orderId' => '201803152100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $encodeData = $yunBao->getVerifyData();

        $this->assertEquals('9527', $encodeData['P_UserId']);
        $this->assertEquals('201803152100009527', $encodeData['P_OrderId']);
        $this->assertEquals('1.00', $encodeData['P_FaceValue']);
        $this->assertEquals('5', $encodeData['P_Type']);
        $this->assertEquals('3.1.3', $encodeData['P_SDKVersion']);
        $this->assertEquals('0', $encodeData['P_RequestType']);
        $this->assertEquals('seafood', $encodeData['P_Subject']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['P_Result_URL']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['P_Notify_URL']);
        $this->assertEquals('5fa28c5ea3ee547783c04b875f49f5f8', $encodeData['P_PostKey']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'status' => '0',
            'message' => '成功',
            'result_code' => '0',
            'err_msg' => '成功',
            'code_url' => 'weixin://wxpay/bizpayurl?pr=0V3ISA0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201710202100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setContainer($this->container);
        $yunBao->setClient($this->client);
        $yunBao->setResponse($response);
        $yunBao->setOptions($option);
        $encodeData = $yunBao->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=0V3ISA0', $yunBao->getQrcode());
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

        $yunBao = new YunBao();
        $yunBao->verifyOrderPayment([]);
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

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions([]);
        $yunBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutPPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $option = [
            'P_UserId' => '9527',
            'P_OrderId' => '201710202100009527',
            'P_SMPayId' => 'seafood520',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '94',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->verifyOrderPayment([]);
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

        $option = [
            'P_UserId' => '9527',
            'P_OrderId' => '201710202100009527',
            'P_SMPayId' => 'seafood520',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '94',
            'P_PostKey' => 'SeafoodHelpYourFamily',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有回傳 P_ErrCode
     */
    public function testReturnWithoutPErrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $option = [
            'P_UserId' => '9527',
            'P_OrderId' => '201710202100009527',
            'P_SMPayId' => 'seafood520',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '94',
            'P_PostKey' => '494d5c7a4674964342fab1a53a95f4fa',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->verifyOrderPayment([]);
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

        $option = [
            'P_UserId' => '9527',
            'P_OrderId' => '201710202100009527',
            'P_SMPayId' => 'seafood520',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '94',
            'P_ErrCode' => '1',
            'P_PostKey' => '494d5c7a4674964342fab1a53a95f4fa',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $option = [
            'P_UserId' => '9527',
            'P_OrderId' => '201710202100009527',
            'P_SMPayId' => 'seafood520',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '94',
            'P_ErrCode' => '0',
            'P_PostKey' => '494d5c7a4674964342fab1a53a95f4fa',
        ];

        $entry = ['id' => '201709220000009528'];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $option = [
            'P_UserId' => '9527',
            'P_OrderId' => '201710202100009527',
            'P_SMPayId' => 'seafood520',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '94',
            'P_ErrCode' => '0',
            'P_PostKey' => '494d5c7a4674964342fab1a53a95f4fa',
        ];

        $entry = [
            'id' => '201710202100009527',
            'amount' => '1.00',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $option = [
            'P_UserId' => '9527',
            'P_OrderId' => '201710202100009527',
            'P_SMPayId' => 'seafood520',
            'P_FaceValue' => '0.01',
            'P_ChannelId' => '94',
            'P_ErrCode' => '0',
            'P_PostKey' => '494d5c7a4674964342fab1a53a95f4fa',
        ];

        $entry = [
            'id' => '201710202100009527',
            'amount' => '0.01',
        ];

        $yunBao = new YunBao();
        $yunBao->setPrivateKey('test');
        $yunBao->setOptions($option);
        $yunBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $yunBao->getMsg());
    }
}
