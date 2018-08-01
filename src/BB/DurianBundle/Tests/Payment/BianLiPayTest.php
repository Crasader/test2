<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BianLiPay;
use Buzz\Message\Response;

class BianLiPayTest extends DurianTestCase
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

        $bianLiPay = new BianLiPay();
        $bianLiPay->getVerifyData();
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

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->getVerifyData();
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
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '9999',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
        ];

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setOptions($options);
        $bianLiPay->getVerifyData();
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

        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1111',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setOptions($options);
        $bianLiPay->getVerifyData();
    }

    /**
     * 測試支付加密未返回successno及errorno
     */
    public function testGetEncodeNoReturnNo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1111',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"msg":"获取数据成功","data":{"pay_orderid":"201711230000002581",' .
            '"pay_QR":"http://p.bianlipay.com/kuaijie/index.php?id=201711230000002581&amount=3.00",' .
            '"pay_url":"http://p.bianlipay.com/kjtz.php?id=201711230000002581"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $bianLiPay->getVerifyData();
    }

    /**
     * 測試支付加密未返回msg
     */
    public function testGetEncodeNoReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1111',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"successno":100001,"data":{"pay_orderid":"201711230000002581",' .
            '"pay_QR":"http://p.bianlipay.com/kuaijie/index.php?id=201711230000002581&amount=3.00",' .
            '"pay_url":"http://p.bianlipay.com/kjtz.php?id=201711230000002581"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $bianLiPay->getVerifyData();
    }

    /**
     * 測試支付加密返回successno不為100001
     */
    public function testGetEncodeReturnWithFailedCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '获取数据失敗',
            180130
        );

        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1111',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"successno":100002,"msg":"获取数据失敗","data":{"pay_orderid":"201711230000002581",' .
            '"pay_QR":"http://p.bianlipay.com/kuaijie/index.php?id=201711230000002581&amount=3.00",' .
            '"pay_url":"http://p.bianlipay.com/kjtz.php?id=201711230000002581"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $bianLiPay->getVerifyData();
    }

    /**
     * 測試支付加密返回errorno
     */
    public function testGetEncodeReturnWithErrorno()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '上送交易金额过低',
            180130
        );

        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1111',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"errorno":400001,"msg":"上送交易金额过低"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $bianLiPay->getVerifyData();
    }

    /**
     * 測試手機加密未返回pay_url
     */
    public function testPhoneGetEncodeNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1093',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"successno":100001,"msg":"获取数据成功","data":{"pay_orderid":"201711230000002581",' .
            '"pay_QR":"http://p.bianlipay.com/kuaijie/index.php?id=201711230000002581&amount=3.00"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $bianLiPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1093',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"successno":100001,"msg":"获取数据成功","data":{"pay_orderid":"201711230000002581",' .
            '"pay_QR":"http://p.bianlipay.com/kuaijie/index.php?id=201711230000002581&amount=3.00",' .
            '"pay_url":"http://p.bianlipay.com/kjtz.php?id=201711230000002581"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $data = $bianLiPay->getVerifyData();

        $this->assertEquals('http://p.bianlipay.com/kjtz.php', $data['post_url']);
        $this->assertEquals('201711230000002581', $data['params']['id']);
    }

    /**
     * 測試微信手機支付
     */
    public function testWeiXinPhonePay()
    {
        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1097',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"successno":100001,"msg":"获取数据成功","data":{"pay_orderid":"201711230000002581",' .
            '"pay_QR":"http://p.bianlipay.com/kuaijie/index.php?id=201711230000002581&amount=3.00",' .
            '"pay_url":"http://p.bianlipay.com/kjtz.php?id=201711230000002581"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $data = $bianLiPay->getVerifyData();

        $this->assertEquals('http://p.bianlipay.com/kjtz.php', $data['post_url']);
        $this->assertEquals('201711230000002581', $data['params']['id']);
    }

    /**
     * 測試QQ_二維支付
     */
    public function testQQScan()
    {
        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1103',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"successno":100001,"msg":"获取数据成功","data":{"pay_orderid":"201711230000002581",' .
            '"pay_QR":"http://p.bianlipay.com/kuaijie/index.php?id=201711230000002581&amount=3.00",' .
            '"pay_url":"http://p.bianlipay.com/kjtz.php?id=201711230000002581"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $data = $bianLiPay->getVerifyData();

        $this->assertEquals('http://p.bianlipay.com/kjtz.php?id=201711230000002581', $bianLiPay->getQrcode());
    }

    /**
     * 測試二維加密未返回pay_QR
     */
    public function testQrcodeGetEncodeNoReturnPayQR()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1111',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"successno":100001,"msg":"获取数据成功","data":{"pay_orderid":"201711230000002581",' .
            '"pay_url":"http://p.bianlipay.com/kjtz.php?id=201711230000002581"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $bianLiPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '10046',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1111',
            'orderId' => '201711230000002581',
            'amount' => '3',
            'orderCreateDate' => '2017-11-23 10:06:06',
            'verify_url' => 'payment.http.p.bianlipay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"successno":100001,"msg":"获取数据成功","data":{"pay_orderid":"201711230000002581",' .
            '"pay_QR":"http://p.bianlipay.com/kuaijie/index.php?id=201711230000002581&amount=3.00",' .
            '"pay_url":"http://p.bianlipay.com/kjtz.php?id=201711230000002581"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setContainer($this->container);
        $bianLiPay->setClient($this->client);
        $bianLiPay->setResponse($response);
        $bianLiPay->setOptions($options);
        $data = $bianLiPay->getVerifyData();

        $this->assertEquals('http://p.bianlipay.com/kuaijie/index.php', $data['post_url']);
        $this->assertEquals('201711230000002581', $data['params']['id']);
        $this->assertEquals('3.00', $data['params']['amount']);
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

        $bianLiPay = new BianLiPay();
        $bianLiPay->verifyOrderPayment([]);
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

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'memberid' => '10046',
            'orderid' => '201711230000002581',
            'amount' => '3.000',
            'datetime' => '20171123115304',
            'returncode' => '00',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setOptions($sourceData);
        $bianLiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'memberid' => '10046',
            'orderid' => '201711230000002581',
            'amount' => '3.000',
            'datetime' => '20171123115304',
            'returncode' => '00',
            'sign' => '5B75442763C5F3CAD0311553A8FAA1FF',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setOptions($sourceData);
        $bianLiPay->verifyOrderPayment([]);
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
            'memberid' => '10046',
            'orderid' => '201711230000002581',
            'amount' => '3.000',
            'datetime' => '20171123115304',
            'returncode' => '01',
            'sign' => 'DECBB1ED357627AF04C7E0D8C4EE8D2B',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setOptions($sourceData);
        $bianLiPay->verifyOrderPayment([]);
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
            'memberid' => '10046',
            'orderid' => '201711230000002581',
            'amount' => '3.000',
            'datetime' => '20171123115304',
            'returncode' => '00',
            'sign' => 'D10019AB00530C61B511AD961E90DEC4',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $entry = [
            'id' => '201711230000002582',
            'amount' => '3',
        ];

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setOptions($sourceData);
        $bianLiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'memberid' => '10046',
            'orderid' => '201711230000002581',
            'amount' => '3.000',
            'datetime' => '20171123115304',
            'returncode' => '00',
            'sign' => 'D10019AB00530C61B511AD961E90DEC4',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $entry = [
            'id' => '201711230000002581',
            'amount' => '300',
        ];

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setOptions($sourceData);
        $bianLiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'memberid' => '10046',
            'orderid' => '201711230000002581',
            'amount' => '3.000',
            'datetime' => '20171123115304',
            'returncode' => '00',
            'sign' => 'D10019AB00530C61B511AD961E90DEC4',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $entry = [
            'id' => '201711230000002581',
            'amount' => '3',
        ];

        $bianLiPay = new BianLiPay();
        $bianLiPay->setPrivateKey('test');
        $bianLiPay->setOptions($sourceData);
        $bianLiPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $bianLiPay->getMsg());
    }
}
