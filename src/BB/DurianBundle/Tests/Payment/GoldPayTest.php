<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\GoldPay;
use Buzz\Message\Response;

class GoldPayTest extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $goldPay = new GoldPay();
        $goldPay->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '800120',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201802080000004181',
            'notify_url' => 'http://pay.my/pay/return.php',
            'ip' => '111.235.135.54',
            'username' => 'php1test',
        ];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->getVerifyData();
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

        $sourceData = [
            'number' => '800120',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201802080000004181',
            'notify_url' => 'http://pay.my/pay/return.php',
            'ip' => '111.235.135.54',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回error
     */
    public function testPayReturnWithoutError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '800120',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201802080000004181',
            'notify_url' => 'http://pay.my/pay/return.php',
            'ip' => '111.235.135.54',
            'username' => 'php1test',
            'verify_url' => 'payment.https.api.goldpayment.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => '1',
            'message' => '请求成功，请跳入url完成支付',
            'appid' => '800120',
            'order_id' => '201802080000004181',
            'billno' => '18020816245651341800120250418138',
            'url' => 'http://wap.ailante.cc/trans/wap/18020816245651341800120250418138/wx/t/',
            'sign' => '1C2920011A8DAF8B2F5CC21C8A03D8A7',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $goldPay = new GoldPay();
        $goldPay->setContainer($this->container);
        $goldPay->setClient($this->client);
        $goldPay->setResponse($response);
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->getVerifyData();
    }

    /**
     * 測試加密返回error不等於0，且有返回message
     */
    public function testGetEncodeReturnErrorNotEqualToZeroAndHaveMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '请求失敗',
            180130
        );

        $sourceData = [
            'number' => '800120',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201802080000004181',
            'notify_url' => 'http://pay.my/pay/return.php',
            'ip' => '111.235.135.54',
            'username' => 'php1test',
            'verify_url' => 'payment.https.api.goldpayment.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => '1',
            'error' => 1,
            'message' => '请求失敗',
            'appid' => '800120',
            'order_id' => '201802080000004181',
            'billno' => '18020816245651341800120250418138',
            'url' => 'http://wap.ailante.cc/trans/wap/18020816245651341800120250418138/wx/t/',
            'sign' => '1C2920011A8DAF8B2F5CC21C8A03D8A7',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $goldPay = new GoldPay();
        $goldPay->setContainer($this->container);
        $goldPay->setClient($this->client);
        $goldPay->setResponse($response);
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->getVerifyData();
    }

    /**
     * 測試加密返回error不等於0
     */
    public function testGetEncodeReturnErrorNotEqualToZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '800120',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201802080000004181',
            'notify_url' => 'http://pay.my/pay/return.php',
            'ip' => '111.235.135.54',
            'username' => 'php1test',
            'verify_url' => 'payment.https.api.goldpayment.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => '1',
            'error' => 1,
            'appid' => '800120',
            'order_id' => '201802080000004181',
            'billno' => '18020816245651341800120250418138',
            'url' => 'http://wap.ailante.cc/trans/wap/18020816245651341800120250418138/wx/t/',
            'sign' => '1C2920011A8DAF8B2F5CC21C8A03D8A7',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $goldPay = new GoldPay();
        $goldPay->setContainer($this->container);
        $goldPay->setClient($this->client);
        $goldPay->setResponse($response);
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->getVerifyData();
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

        $sourceData = [
            'number' => '800120',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201802080000004181',
            'notify_url' => 'http://pay.my/pay/return.php',
            'ip' => '111.235.135.54',
            'username' => 'php1test',
            'verify_url' => 'payment.https.api.goldpayment.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => '1',
            'error' => 0,
            'message' => '请求成功，请跳入url完成支付',
            'appid' => '800120',
            'order_id' => '201802080000004181',
            'billno' => '18020816245651341800120250418138',
            'sign' => '1C2920011A8DAF8B2F5CC21C8A03D8A7',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $goldPay = new GoldPay();
        $goldPay->setContainer($this->container);
        $goldPay->setClient($this->client);
        $goldPay->setResponse($response);
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '800120',
            'paymentVendorId' => '1097',
            'amount' => '0.01',
            'orderId' => '201802080000004181',
            'notify_url' => 'http://pay.my/pay/return.php',
            'ip' => '111.235.135.54',
            'username' => 'php1test',
            'verify_url' => 'payment.https.api.goldpayment.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'success' => '1',
            'error' => 0,
            'message' => '请求成功，请跳入url完成支付',
            'appid' => '800120',
            'order_id' => '201802080000004181',
            'billno' => '18020816245651341800120250418138',
            'url' => 'http://wap.ailante.cc/trans/wap/180208162456513418001202504181/wx/t/',
            'sign' => '1C2920011A8DAF8B2F5CC21C8A03D8A7',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $goldPay = new GoldPay();
        $goldPay->setContainer($this->container);
        $goldPay->setClient($this->client);
        $goldPay->setResponse($response);
        $goldPay->setPrivateKey('test');
        $goldPay->setOptions($sourceData);
        $data = $goldPay->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals('http://wap.ailante.cc/trans/wap/180208162456513418001202504181/wx/t/', $data['post_url']);
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

        $goldPay = new GoldPay();
        $goldPay->verifyOrderPayment([]);
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

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->verifyOrderPayment([]);
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
            'success' => '1',
            'error' => '0',
            'message' => '支付成功',
            'appid' => '800120',
            'billno' => '18020816245651341800120250418138',
            'order_id' => '201802080000004181',
            'remark' => '',
            'amount' => '500',
            'payment' => '500',
            'type' => 'pay',
            'state' => '1',
            'time' => '2018-02-08 16:25:52',
        ];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->verifyOrderPayment([]);
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
            'success' => '1',
            'error' => '0',
            'message' => '支付成功',
            'appid' => '800120',
            'billno' => '18020816245651341800120250418138',
            'order_id' => '201802080000004181',
            'remark' => '',
            'amount' => '500',
            'payment' => '500',
            'type' => 'pay',
            'state' => '1',
            'time' => '2018-02-08 16:25:52',
            'sign' => '37B13AD39F0099ACCB1C6999EAEC5AD1',
        ];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時success不等於1
     */
    public function testReturnWithSuccessNotEqualToOne()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'success' => '0',
            'error' => '0',
            'message' => '支付成功',
            'appid' => '800120',
            'billno' => '18020816245651341800120250418138',
            'order_id' => '201802080000004181',
            'remark' => '',
            'amount' => '500',
            'payment' => '500',
            'type' => 'pay',
            'state' => '1',
            'time' => '2018-02-08 16:25:52',
            'sign' => 'D63AD3474DED19897B6199D100DBCC12',
        ];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時error不等於0
     */
    public function testReturnWithErrorNotEqualToZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'success' => '1',
            'error' => '1',
            'message' => '支付成功',
            'appid' => '800120',
            'billno' => '18020816245651341800120250418138',
            'order_id' => '201802080000004181',
            'remark' => '',
            'amount' => '500',
            'payment' => '500',
            'type' => 'pay',
            'state' => '1',
            'time' => '2018-02-08 16:25:52',
            'sign' => '00771489FE8821E4C4CCC05B4864F1B4',
        ];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'success' => '1',
            'error' => '0',
            'message' => '支付成功',
            'appid' => '800120',
            'billno' => '18020816245651341800120250418138',
            'order_id' => '201802080000004181',
            'remark' => '',
            'amount' => '500',
            'payment' => '500',
            'type' => 'pay',
            'state' => '1',
            'time' => '2018-02-08 16:25:52',
            'sign' => '4C71D22BA8920012990CECDF59FC6062',
        ];

        $entry = ['id' => '201802080000004182'];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'success' => '1',
            'error' => '0',
            'message' => '支付成功',
            'appid' => '800120',
            'billno' => '18020816245651341800120250418138',
            'order_id' => '201802080000004181',
            'remark' => '',
            'amount' => '500',
            'payment' => '500',
            'type' => 'pay',
            'state' => '1',
            'time' => '2018-02-08 16:25:52',
            'sign' => '4C71D22BA8920012990CECDF59FC6062',
        ];

        $entry = [
            'id' => '201802080000004181',
            'amount' => '1',
        ];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'success' => '1',
            'error' => '0',
            'message' => '支付成功',
            'appid' => '800120',
            'billno' => '18020816245651341800120250418138',
            'order_id' => '201802080000004181',
            'remark' => '',
            'amount' => '500',
            'payment' => '500',
            'type' => 'pay',
            'state' => '1',
            'time' => '2018-02-08 16:25:52',
            'sign' => '4C71D22BA8920012990CECDF59FC6062',
        ];

        $entry = [
            'id' => '201802080000004181',
            'amount' => '5',
        ];

        $goldPay = new GoldPay();
        $goldPay->setPrivateKey('1234');
        $goldPay->setOptions($sourceData);
        $goldPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $goldPay->getMsg());
    }
}
