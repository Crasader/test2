<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TelepayII;
use Buzz\Message\Response;

class TelepayIITest extends DurianTestCase
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
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試支付未帶入密鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $telepayII = new TelepayII();
        $telepayII->setOptions([]);
        $telepayII->getVerifyData();
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

        $telepayII = new TelepayII();
        $telepayII->setPrivateKey('private key');
        $telepayII->setOptions([]);
        $telepayII->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '999',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
        ];

        $telepayII = new TelepayII();
        $telepayII->setPrivateKey('private key');
        $telepayII->setOptions($sourceData);
        $telepayII->getVerifyData();
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
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '278',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $telepayII = new TelepayII();
        $telepayII->setPrivateKey('private key');
        $telepayII->setContainer($this->container);
        $telepayII->setClient($this->client);
        $telepayII->setOptions($sourceData);
        $telepayII->getVerifyData();
    }

    /**
     * 測試支付沒有返回status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '278',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respcode":"000000","respmsg":"success","type":"2",' .
            '"url":"https://scc.gavspay.com/order/redirectGw.htm?sequenceId=1157131&' .
            'mac=4B7350C8DC75781E945DAFEF290C8718"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepayII = new TelepayII();
        $telepayII->setPrivateKey('private key');
        $telepayII->setContainer($this->container);
        $telepayII->setClient($this->client);
        $telepayII->setResponse($response);
        $telepayII->setOptions($sourceData);
        $telepayII->getVerifyData();
    }

    /**
     * 測試支付但返回結果失敗
     */
    public function testPayButReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'payment method is not applied or not enabled',
            180130
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '278',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"status":"-1","respcode":"13","respmsg":"payment method is not applied or not enabled"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepayII = new TelepayII();
        $telepayII->setPrivateKey('private key');
        $telepayII->setContainer($this->container);
        $telepayII->setClient($this->client);
        $telepayII->setResponse($response);
        $telepayII->setOptions($sourceData);
        $telepayII->getVerifyData();
    }

    /**
     * 測試支付沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '278',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"status":"1","respcode":"000000","respmsg":"success","type":"2"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepayII = new TelepayII();
        $telepayII->setPrivateKey('private key');
        $telepayII->setContainer($this->container);
        $telepayII->setClient($this->client);
        $telepayII->setResponse($response);
        $telepayII->setOptions($sourceData);
        $telepayII->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '278',
            'notify_url' => 'http://pay.my/pay/return.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"status":"1","respcode":"000000","respmsg":"success","type":"2",' .
            '"url":"https://scc.gavspay.com/order/redirectGw.htm?sequenceId=1157131&' .
            'mac=4B7350C8DC757290C8718"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepayII = new TelepayII();
        $telepayII->setPrivateKey('private key');
        $telepayII->setContainer($this->container);
        $telepayII->setClient($this->client);
        $telepayII->setResponse($response);
        $telepayII->setOptions($sourceData);
        $encodeData = $telepayII->getVerifyData();

        $this->assertEquals('https://scc.gavspay.com/order/redirectGw.htm', $encodeData['post_url']);
        $this->assertEquals('1157131', $encodeData['params']['sequenceId']);
        $this->assertEquals('4B7350C8DC757290C8718', $encodeData['params']['mac']);
        $this->assertEquals('GET', $telepayII->getPayMethod());
    }

    /**
     * 測試返回時未帶入密鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $telepayII = new TelepayII();
        $telepayII->setOptions([]);
        $telepayII->verifyOrderPayment([]);
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

        $telepayII = new TelepayII();
        $telepayII->setOptions([]);
        $telepayII->setPrivateKey('private key');
        $telepayII->verifyOrderPayment([]);
    }

    /**
     * 測試返回缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2018032200000581',
            'orderid' => '201803220000004479',
            'amount' => '1000',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2018-03-22 13:42:58',
            'status' => '1',
            'respcode' => '00',
            'paytype' => 'unionpayq',
            'productname' => '201803220000004479',
            'rmbrate' => '',
        ];

        $telepayII = new TelepayII();
        $telepayII->setOptions($sourceData);
        $telepayII->setPrivateKey('private key');
        $telepayII->verifyOrderPayment([]);
    }

    /**
     * 測試返回驗簽失敗
     */
    public function testReturnWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2018032200000581',
            'orderid' => '201803220000004479',
            'amount' => '1000',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2018-03-22 13:42:58',
            'status' => '1',
            'respcode' => '00',
            'paytype' => 'unionpayq',
            'productname' => '201803220000004479',
            'rmbrate' => '',
            'sign' => 'ea38936732c0e7c5295d451d9980b5f0',
        ];

        $telepayII = new TelepayII();
        $telepayII->setOptions($sourceData);
        $telepayII->setPrivateKey('private key');
        $telepayII->verifyOrderPayment([]);
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

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2018032200000581',
            'orderid' => '201803220000004479',
            'amount' => '1000',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2018-03-22 13:42:58',
            'status' => '2',
            'respcode' => '00',
            'paytype' => 'unionpayq',
            'productname' => '201803220000004479',
            'rmbrate' => '',
            'sign' => '4894a95031d3f90c92a88a8c6b68abec',
        ];

        $telepayII = new TelepayII();
        $telepayII->setOptions($sourceData);
        $telepayII->setPrivateKey('private key');
        $telepayII->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2018032200000581',
            'orderid' => '201803220000004479',
            'amount' => '1000',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2018-03-22 13:42:58',
            'status' => '1',
            'respcode' => '00',
            'paytype' => 'unionpayq',
            'productname' => '201803220000004479',
            'rmbrate' => '',
            'sign' => '7d03274268a048fcffa040744d70a642',
        ];

        $entry = ['id' => '201803220000004478'];

        $telepayII = new TelepayII();
        $telepayII->setOptions($sourceData);
        $telepayII->setPrivateKey('private key');
        $telepayII->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2018032200000581',
            'orderid' => '201803220000004479',
            'amount' => '1000',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2018-03-22 13:42:58',
            'status' => '1',
            'respcode' => '00',
            'paytype' => 'unionpayq',
            'productname' => '201803220000004479',
            'rmbrate' => '',
            'sign' => '7d03274268a048fcffa040744d70a642',
        ];

        $entry = [
            'id' => '201803220000004479',
            'amount' => '1000',
        ];

        $telepayII = new TelepayII();
        $telepayII->setOptions($sourceData);
        $telepayII->setPrivateKey('private key');
        $telepayII->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2018032200000581',
            'orderid' => '201803220000004479',
            'amount' => '1000',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2018-03-22 13:42:58',
            'status' => '1',
            'respcode' => '00',
            'paytype' => 'unionpayq',
            'productname' => '201803220000004479',
            'rmbrate' => '',
            'sign' => '7d03274268a048fcffa040744d70a642',
        ];

        $entry = [
            'id' => '201803220000004479',
            'amount' => '10',
        ];

        $telepayII = new TelepayII();
        $telepayII->setOptions($sourceData);
        $telepayII->setPrivateKey('private key');
        $telepayII->verifyOrderPayment($entry);

        $this->assertEquals('OK', $telepayII->getMsg());
    }
}
