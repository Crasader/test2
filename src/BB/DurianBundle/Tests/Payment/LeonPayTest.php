<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\LeonPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class LeonPayTest extends DurianTestCase
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

        $leonPay = new LeonPay();
        $leonPay->getVerifyData();
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

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->getVerifyData();
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
            'number' => '1496219556263',
            'amount' => '100',
            'orderId' => '201709050000006913',
            'paymentVendorId' => '999',
            'notify_url' => 'http://two123.comxa.com/',
            'merchantId' => '1234',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-09-18 20:12:49',
        ];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->getVerifyData();
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
            'number' => '1496219556263',
            'amount' => '1',
            'orderId' => '201709180000007103',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
            'merchantId' => '1234',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-09-18 20:12:49',
        ];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->getVerifyData();
    }

    /**
     * 測試二維支付返回缺少code
     */
    public function testQrcodeWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1496219556263',
            'amount' => '0.01',
            'orderId' => '201709180000007103',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.apipoxy.gpp365.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '1234',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-09-18 20:12:49',
        ];

        $result = '{"message":"參數錯誤"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $leonPay = new LeonPay();
        $leonPay->setContainer($this->container);
        $leonPay->setClient($this->client);
        $leonPay->setResponse($response);
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQrcodeReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '參數錯誤',
            180130
        );

        $options = [
            'number' => '1496219556263',
            'amount' => '0.01',
            'orderId' => '201709180000007103',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.apipoxy.gpp365.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '1234',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-09-18 20:12:49',
        ];

        $result = '{"code":"1006","message":"參數錯誤"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $leonPay = new LeonPay();
        $leonPay->setContainer($this->container);
        $leonPay->setClient($this->client);
        $leonPay->setResponse($response);
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回qrcode
     */
    public function testQrcodeReturnWithoutQrode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1496219556263',
            'amount' => '0.01',
            'orderId' => '201709180000007103',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.apipoxy.gpp365.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '1234',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-09-18 20:12:49',
        ];

        $result = '{"code":"0000","message":"操作成功","data":{"amount":"20.00}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $leonPay = new LeonPay();
        $leonPay->setContainer($this->container);
        $leonPay->setClient($this->client);
        $leonPay->setResponse($response);
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '1496219556263',
            'amount' => '0.01',
            'orderId' => '201709180000007103',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.apipoxy.gpp365.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '1234',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-09-18 20:12:49',
        ];

        $result = '{"code":"0000","message":"操作成功","data":{"amount":"20.00","curType":"CNY",' .
            '"merchant":"1496219556263","ordernumber":"10150573677151391253","payType":"1",' .
            '"qrcode":"weixin://wxpay/bizpayurl?pr=64RvoaU","sign":"273dbb187f67398676a22cfa8970a2aa",' .
            '"tradeNo":"201709180000007103"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $leonPay = new LeonPay();
        $leonPay->setContainer($this->container);
        $leonPay->setClient($this->client);
        $leonPay->setResponse($response);
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $data = $leonPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=64RvoaU', $leonPay->getQrcode());
    }

    /**
     * 測試支付缺少回傳post_url
     */
    public function testPayReturnWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1496219556263',
            'amount' => '10.00',
            'orderId' => '201709190000007147',
            'paymentVendorId' => '1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.apipoxy.gpp365.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '3648',
            'ip' => '192.168.233.1',
            'orderCreateDate' => '2017-09-19 15:16:08',
        ];

        $result = '<html><head><title>支付中..</title></head><body>' .
            '<form name="dataForm" method="post"">' .
            '<input type="hidden" name="PMerchantId" value="25" />' .
            '</form></body></html>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $leonPay = new LeonPay();
        $leonPay->setContainer($this->container);
        $leonPay->setClient($this->client);
        $leonPay->setResponse($response);
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '1496219556263',
            'amount' => '10.00',
            'orderId' => '201709190000007147',
            'paymentVendorId' => '1',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.apipoxy.gpp365.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '3648',
            'ip' => '192.168.233.1',
            'orderCreateDate' => '2017-09-19 15:16:08',
        ];

        $result = '<html><head><title>支付中..</title></head><body>' .
            '<form name="dataForm" method="post" action="http://gpp-test.tnmert.top/pay/bank/stage2/v1">' .
            '<input type="hidden" name="PMerchantId" value="25" />' .
            '<input type="hidden" name="PThirdPartyId" value="2" />' .
            '<input type="hidden" name="amount" value="10.00" />' .
            '<input type="hidden" name="bankSwiftCode" value="ICBK" />' .
            '<input type="hidden" name="curType" value="CNY" />' .
            '<input type="hidden" name="deviceType" value="0" />' .
            '<input type="hidden" name="ip" value="192.168.233.1" />' .
            '<input type="hidden" name="merchant" value="1496219556263" />' .
            '<input type="hidden" name="notifyUrl" value="http://two123.comuv.com/pay/return.php" />' .
            '<input type="hidden" name="reqTime" value="2017-09-19 15:16:08" />' .
            '<input type="hidden" name="returnUrl" value="http://two123.comuv.com/pay/return.php" />' .
            '<input type="hidden" name="sign" value="8f72ff5ca1db020262a37123b158d9d3" />' .
            '<input type="hidden" name="tradeNo" value="201709190000007147" />' .
            '<input type="hidden" name="userId" value="3648" />' .
            '</form></body></html>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $leonPay = new LeonPay();
        $leonPay->setContainer($this->container);
        $leonPay->setClient($this->client);
        $leonPay->setResponse($response);
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $requestData = $leonPay->getVerifyData();

        $this->assertEquals('http://gpp-test.tnmert.top/pay/bank/stage2/v1', $requestData['post_url']);
        $this->assertEquals('25', $requestData['params']['PMerchantId']);
        $this->assertEquals('2', $requestData['params']['PThirdPartyId']);
        $this->assertEquals($options['amount'], $requestData['params']['amount']);
        $this->assertEquals('ICBK', $requestData['params']['bankSwiftCode']);
        $this->assertEquals('CNY', $requestData['params']['curType']);
        $this->assertEquals('0', $requestData['params']['deviceType']);
        $this->assertEquals($options['ip'], $requestData['params']['ip']);
        $this->assertEquals($options['number'], $requestData['params']['merchant']);
        $this->assertEquals('http://two123.comuv.com/pay/return.php', $requestData['params']['notifyUrl']);
        $this->assertEquals('2017-09-19 15:16:08', $requestData['params']['reqTime']);
        $this->assertEquals('http://two123.comuv.com/pay/return.php', $requestData['params']['returnUrl']);
        $this->assertEquals('8f72ff5ca1db020262a37123b158d9d3', $requestData['params']['sign']);
        $this->assertEquals($options['orderId'], $requestData['params']['tradeNo']);
        $this->assertEquals($options['merchantId'], $requestData['params']['userId']);
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

        $leonPay = new LeonPay();
        $leonPay->verifyOrderPayment([]);
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

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->verifyOrderPayment([]);
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
            'tradeTime' => '2017-09-18 20:14:00',
            'amount' => '1.01',
            'payType' => '1',
            'tradeNo' => '201709180000007104',
            'curType' => 'CNY',
            'ordernumber' => '10150573682403075389',
            'merchant' => '1496219556263',
            'status' => '1',
        ];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->verifyOrderPayment([]);
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
            'tradeTime' => '2017-09-18 20:14:00',
            'amount' => '1.01',
            'payType' => '1',
            'tradeNo' => '201709180000007104',
            'curType' => 'CNY',
            'ordernumber' => '10150573682403075389',
            'sign' => '75a3f781db6f08ce3fefaac8524a9ad4',
            'merchant' => '1496219556263',
            'status' => '1',
        ];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單處理中
     */
    public function testReturnWithOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'tradeTime' => '2017-09-18 20:14:00',
            'amount' => '1.01',
            'payType' => '1',
            'tradeNo' => '201709180000007104',
            'curType' => 'CNY',
            'ordernumber' => '10150573682403075389',
            'sign' => '1fc597f58d3704734fae67f4515545cd',
            'merchant' => '1496219556263',
            'status' => '0',
        ];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->verifyOrderPayment([]);
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
            'tradeTime' => '2017-09-18 20:14:00',
            'amount' => '1.01',
            'payType' => '1',
            'tradeNo' => '201709180000007104',
            'curType' => 'CNY',
            'ordernumber' => '10150573682403075389',
            'sign' => '42feeba01952e90d37aad7c0821437f1',
            'merchant' => '1496219556263',
            'status' => '2',
        ];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->verifyOrderPayment([]);
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
            'tradeTime' => '2017-09-18 20:14:00',
            'amount' => '1.01',
            'payType' => '1',
            'tradeNo' => '201709180000007104',
            'curType' => 'CNY',
            'ordernumber' => '10150573682403075389',
            'sign' => '7ef1ebe0c60ff47c1db01385558c3248',
            'merchant' => '1496219556263',
            'status' => '1',
        ];

        $entry = ['id' => '201707250000003581'];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->verifyOrderPayment($entry);
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
            'tradeTime' => '2017-09-18 20:14:00',
            'amount' => '1.01',
            'payType' => '1',
            'tradeNo' => '201709180000007104',
            'curType' => 'CNY',
            'ordernumber' => '10150573682403075389',
            'sign' => '7ef1ebe0c60ff47c1db01385558c3248',
            'merchant' => '1496219556263',
            'status' => '1',
        ];

        $entry = [
            'id' => '201709180000007104',
            'amount' => '1',
        ];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'tradeTime' => '2017-09-18 20:14:00',
            'amount' => '1.01',
            'payType' => '1',
            'tradeNo' => '201709180000007104',
            'curType' => 'CNY',
            'ordernumber' => '10150573682403075389',
            'sign' => '7ef1ebe0c60ff47c1db01385558c3248',
            'merchant' => '1496219556263',
            'status' => '1',
        ];

        $entry = [
            'id' => '201709180000007104',
            'amount' => '1.01',
        ];

        $leonPay = new LeonPay();
        $leonPay->setPrivateKey('test');
        $leonPay->setOptions($options);
        $leonPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $leonPay->getMsg());
    }
}
