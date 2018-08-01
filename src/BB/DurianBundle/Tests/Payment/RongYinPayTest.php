<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\RongYinPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class RongYinPayTest extends DurianTestCase
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

        $rongYinPay = new RongYinPay();
        $rongYinPay->getVerifyData();
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

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->getVerifyData();
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
            'number' => 'jc00000205',
            'amount' => '20.00',
            'orderId' => '201804230000012430',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
        ];

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->getVerifyData();
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
            'number' => 'jc00000205',
            'amount' => '20.00',
            'orderId' => '201804230000012430',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
        ];

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回payState
     */
    public function testPayReturnWithoutPayState()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'jc00000205',
            'amount' => '20.00',
            'orderId' => '201804230000012430',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $rongYinPay = new RongYinPay();
        $rongYinPay->setContainer($this->container);
        $rongYinPay->setClient($this->client);
        $rongYinPay->setResponse($response);
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单匹配不足',
            180130
        );

        $options = [
            'number' => 'jc00000205',
            'amount' => '20.00',
            'orderId' => '201804230000012430',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'payState' => 'fail',
            'message' => '订单匹配不足',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $rongYinPay = new RongYinPay();
        $rongYinPay->setContainer($this->container);
        $rongYinPay->setClient($this->client);
        $rongYinPay->setResponse($response);
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'jc00000205',
            'amount' => '20.00',
            'orderId' => '201804230000012430',
            'paymentVendorId' => '1092',
            'username' => 'php1test',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '',
            'payCode' => 'alipay',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
            'payState' => 'success',
            'message' => '',
            'content' => '',
            'payWay' => 'ALIPAY',
            'shopCode' => 'jc00000205',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $rongYinPay = new RongYinPay();
        $rongYinPay->setContainer($this->container);
        $rongYinPay->setClient($this->client);
        $rongYinPay->setResponse($response);
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $options = [
            'number' => 'jc00000205',
            'amount' => '20.00',
            'orderId' => '201804230000012430',
            'paymentVendorId' => '1092',
            'username' => 'php1test',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '',
            'payCode' => 'alipay',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
            'payState' => 'success',
            'message' => '',
            'url' => 'https://qr.alipay.com/upx00548b6yz0aevbbji00e9',
            'content' => '',
            'payWay' => 'ALIPAY',
            'shopCode' => 'jc00000205',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=gbk');

        $rongYinPay = new RongYinPay();
        $rongYinPay->setContainer($this->container);
        $rongYinPay->setClient($this->client);
        $rongYinPay->setResponse($response);
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $data = $rongYinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.alipay.com/upx00548b6yz0aevbbji00e9', $rongYinPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => 'jc00000205',
            'amount' => '20.00',
            'orderId' => '201804230000012430',
            'paymentVendorId' => '1098',
            'username' => 'php1test',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '',
            'payCode' => 'alipay',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
            'payState' => 'success',
            'message' => '',
            'url' => 'https://qr.alipay.com/upx00548b6yz0aevbbji00e9',
            'content' => '',
            'payWay' => 'ALIPAY',
            'shopCode' => 'jc00000205',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=gbk');

        $rongYinPay = new RongYinPay();
        $rongYinPay->setContainer($this->container);
        $rongYinPay->setClient($this->client);
        $rongYinPay->setResponse($response);
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $data = $rongYinPay->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/upx00548b6yz0aevbbji00e9', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $rongYinPay->getPayMethod());
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

        $rongYinPay = new RongYinPay();
        $rongYinPay->verifyOrderPayment([]);
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

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'shopCode' => 'jc00000205',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'code' => '0',
            'nonStr' => 'ROnwPiCayQunMVLU',
            'msg' => 'SUCCESS',
        ];

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->verifyOrderPayment([]);
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
            'shopCode' => 'jc00000205',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'code' => '0',
            'nonStr' => 'ROnwPiCayQunMVLU',
            'msg' => 'SUCCESS',
            'sign' => '9487',
        ];

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->verifyOrderPayment([]);
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
            'shopCode' => 'jc00000205',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'code' => '-1',
            'nonStr' => 'ROnwPiCayQunMVLU',
            'msg' => 'FAIL',
            'sign' => '245f46a92b05a9890e71273985b37489',
        ];

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->verifyOrderPayment([]);
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
            'shopCode' => 'jc00000205',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'code' => '0',
            'nonStr' => 'ROnwPiCayQunMVLU',
            'msg' => 'SUCCESS',
            'sign' => '3537cd7c9d8d0ba59ccade5c7c809e72',
        ];

        $entry = ['id' => '9453'];

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->verifyOrderPayment($entry);
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
            'shopCode' => 'jc00000205',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'code' => '0',
            'nonStr' => 'ROnwPiCayQunMVLU',
            'msg' => 'SUCCESS',
            'sign' => '3537cd7c9d8d0ba59ccade5c7c809e72',
        ];

        $entry = [
            'id' => '201804230000012430',
            'amount' => '1',
        ];

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'shopCode' => 'jc00000205',
            'outOrderNo' => '201804230000012430',
            'goodsClauses' => '201804230000012430',
            'tradeAmount' => '20.00',
            'code' => '0',
            'nonStr' => 'ROnwPiCayQunMVLU',
            'msg' => 'SUCCESS',
            'sign' => '3537cd7c9d8d0ba59ccade5c7c809e72',
        ];

        $entry = [
            'id' => '201804230000012430',
            'amount' => '20.00',
        ];

        $rongYinPay = new RongYinPay();
        $rongYinPay->setPrivateKey('test');
        $rongYinPay->setOptions($options);
        $rongYinPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $rongYinPay->getMsg());
    }
}
