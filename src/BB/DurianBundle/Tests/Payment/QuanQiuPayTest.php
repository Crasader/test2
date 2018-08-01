<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\QuanQiuPay;
use Buzz\Message\Response;

class QuanQiuPayTest extends DurianTestCase
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

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->getVerifyData();
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

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions([]);
        $quanQiuPay->getVerifyData();
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
            'orderId' => '201801232100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions($option);
        $quanQiuPay->getVerifyData();
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
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201801232100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_url' => '',
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions($option);
        $quanQiuPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少errorcode
     */
    public function testPayReturnWithoutErrorcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $response = new Response();
        $response->setContent(json_encode([]));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201801232100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_url' => 'http://www.seafood.help.you',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setContainer($this->container);
        $quanQiuPay->setClient($this->client);
        $quanQiuPay->setResponse($response);
        $quanQiuPay->setOptions($option);
        $quanQiuPay->getVerifyData();
    }

    /**
     * 測試支付時返回errorcode不等於0,且返回errormsg
     */
    public function testPayReturnErrorCodeNotEqualZeroAndReturnErrorMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '提交參數錯誤',
            180130
        );

        $result = [
            'errorcode' => '999',
            'errormsg' => '提交參數錯誤',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201801232100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_url' => 'http://www.seafood.help.you',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setContainer($this->container);
        $quanQiuPay->setClient($this->client);
        $quanQiuPay->setResponse($response);
        $quanQiuPay->setOptions($option);
        $quanQiuPay->getVerifyData();
    }

    /**
     * 測試支付時返回errorcode不等於0
     */
    public function testPayReturnErrorCodeNotEqualZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['errorcode' => '3'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201801232100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_url' => 'http://www.seafood.help.you',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setContainer($this->container);
        $quanQiuPay->setClient($this->client);
        $quanQiuPay->setResponse($response);
        $quanQiuPay->setOptions($option);
        $quanQiuPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少code_url
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'errorcode' => '0',
            'errormsg' => '',
            'code_url' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201801232100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_url' => 'http://www.seafood.help.you',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setContainer($this->container);
        $quanQiuPay->setClient($this->client);
        $quanQiuPay->setResponse($response);
        $quanQiuPay->setOptions($option);
        $quanQiuPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'errorcode' => '0',
            'errormsg' => '',
            'code_url' => 'http://SuperSeaFood.help.you/pay/seafood.php?test=seafood',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $option = [
            'number' => '9527',
            'paymentVendorId' => '1097',
            'amount' => '1.00',
            'orderId' => '201801232100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'verify_url' => 'http://www.seafood.help.you',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setContainer($this->container);
        $quanQiuPay->setClient($this->client);
        $quanQiuPay->setResponse($response);
        $quanQiuPay->setOptions($option);
        $encodeData = $quanQiuPay->getVerifyData();

        $this->assertEquals('GET', $quanQiuPay->getPayMethod());
        $this->assertEquals('seafood', $encodeData['params']['test']);
        $this->assertEquals('http://SuperSeaFood.help.you/pay/seafood.php', $encodeData['post_url']);
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

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->verifyOrderPayment([]);
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

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions([]);
        $quanQiuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $option = [
            'mchorderid' => '201801232100009527',
            'pdorderid' => '123456789987654321',
            'total_fee' => '101',
            'pay_type' => 'pay_weixin_wap',
            'transactionid' => 'OX00000001',
            'mchno' => '9527',
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions($option);
        $quanQiuPay->verifyOrderPayment([]);
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
            'mchorderid' => '201801232100009527',
            'pdorderid' => '123456789987654321',
            'total_fee' => '101',
            'pay_type' => 'pay_weixin_wap',
            'transactionid' => 'OX00000001',
            'mchno' => '9527',
            'sign' => 'SeafoodIsGood',
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions($option);
        $quanQiuPay->verifyOrderPayment([]);
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
            'mchorderid' => '201801232100009527',
            'pdorderid' => '123456789987654321',
            'total_fee' => '101',
            'pay_type' => 'pay_weixin_wap',
            'transactionid' => 'OX00000001',
            'mchno' => '9527',
            'sign' => 'D612C6EADD5E3D3E411BDC80059D09A0',
        ];

        $entry = ['id' => '201709220000009528'];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions($option);
        $quanQiuPay->verifyOrderPayment($entry);
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
            'mchorderid' => '201801232100009527',
            'pdorderid' => '123456789987654321',
            'total_fee' => '101',
            'pay_type' => 'pay_weixin_wap',
            'transactionid' => 'OX00000001',
            'mchno' => '9527',
            'sign' => 'D612C6EADD5E3D3E411BDC80059D09A0',
        ];

        $entry = [
            'id' => '201801232100009527',
            'amount' => '1.00',
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions($option);
        $quanQiuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $option = [
            'mchorderid' => '201801232100009527',
            'pdorderid' => '123456789987654321',
            'total_fee' => '101',
            'pay_type' => 'pay_weixin_wap',
            'transactionid' => 'OX00000001',
            'mchno' => '9527',
            'sign' => 'D612C6EADD5E3D3E411BDC80059D09A0',
        ];

        $entry = [
            'id' => '201801232100009527',
            'amount' => '1.01',
        ];

        $quanQiuPay = new QuanQiuPay();
        $quanQiuPay->setPrivateKey('test');
        $quanQiuPay->setOptions($option);
        $quanQiuPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $quanQiuPay->getMsg());
    }
}
