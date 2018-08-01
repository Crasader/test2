<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Kbpay;
use Buzz\Message\Response;

class KbpayTest extends DurianTestCase
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
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kbpay = new Kbpay();
        $kbpay->setContainer($this->container);
        $kbpay->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayyWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');
        $kbpay->setContainer($this->container);
        $kbpay->getVerifyData();
    }

    /**
     * 測試支付時缺少商家額外的參數設定
     */
    public function testPayWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '12345',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'amount' => '500',
            'orderId' => '201606060000000001',
            'orderCreateDate' => '2016-06-13 15:40:00',
            'notify_url' => 'http://test.com/pay/',
            'paymentVendorId' => '1095',
            'merchant_extra' => [],
        ];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');
        $kbpay->setOptions($options);
        $kbpay->setContainer($this->container);
        $kbpay->getVerifyData();
    }

    /**
     * 測試加密時支付平台連線異常
     */
    public function testPayPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');
        $kbpay->setContainer($this->container);
        $kbpay->setClient($this->client);

        $sourceData = [
            'number' => 'sadari',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'amount' => '500',
            'orderId' => '201606060000000001',
            'orderCreateDate' => '2016-06-13 15:40:00',
            'notify_url' => 'http://test.com/pay/',
            'paymentVendorId' => '1095',
            'merchantId' => '12345',
            'domain' => '6',
            'merchant_extra' => [
                'userid' => 'denny5959',
                'btcAddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.www.dq-584.com',
        ];

        $kbpay->setOptions($sourceData);
        $kbpay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');

        $kbpay->setContainer($this->container);
        $kbpay->setClient($this->client);

        $result = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">' .
            '</head><body oncontextmenu="return false"><div class="qr_area">' .
            '<div class="qr_bg"><img id="imgQR" src="data:image/png;base64, ' .
            'iVBORw0KGgoAAAANSUhEUgAAAOUAAADlCAYAAACsyTAWAAAAAXNSR0IArs4c6..." ' .
            'style="width: 160px; margin: 10px;" /></div></div></body></html>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kbpay->setResponse($response);

        $sourceData = [
            'number' => 'sadari',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'amount' => '500',
            'orderId' => '201606060000000001',
            'orderCreateDate' => '2016-06-13 15:40:00',
            'notify_url' => 'http://test.com/pay/',
            'paymentVendorId' => '1095',
            'merchantId' => '12345',
            'domain' => '6',
            'merchant_extra' => [
                'userid' => 'denny5959',
                'btcAddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.http.www.dq-584.com',
        ];

        $kbpay->setOptions($sourceData);
        $encodeData = $kbpay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertRegExp(
            '/<img id="imgQR" src="(.*)" \/>/',
            $kbpay->getHtml()
        );
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kbpay = new Kbpay();
        $kbpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = ['result' => 'FAIL'];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = ['result' => 'SUCCESS'];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時檢查mode參數不為deposit
     */
    public function testVerifyButModeNotDeposit()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'mode' => 'withdraw',
            'type' => 's',
            'shopid' => 'sadari',
            'userid' => 'denny5959',
            'shopaddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX',
            'btc' => '0.0007',
            'price' => '500',
            'date' => '20160725145755',
            'result' => 'SUCCESS',
            'param1' => '201607250000003313',
            'param2' => 'php1test',
            'param3' => '3215_6',
            'param4' => 'b0a2a8f69df10d7525f05f4aa59021c6',
            'param5' => '',
        ];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時userid錯誤
     */
    public function testVerifyButUseridError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, userid error',
            150180179
        );

        $options = [
            'mode' => 'deposit',
            'type' => 's',
            'shopid' => 'sadari',
            'userid' => 'denny5959',
            'shopaddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX',
            'btc' => '0.0007',
            'price' => '500',
            'date' => '20160725145755',
            'result' => 'SUCCESS',
            'param1' => '201607250000003313',
            'param2' => 'php1test',
            'param3' => '3215_6',
            'param4' => 'b0a2a8f69df10d7525f05f4aa59021c6',
            'param5' => '',
            'merchant_extra' => [
                'userid' => 'two123',
                'btcAddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX'],
        ];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時shopaddr錯誤
     */
    public function testVerifyButShopaddrError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, shopaddr error',
            150180180
        );

        $options = [
            'mode' => 'deposit',
            'type' => 's',
            'shopid' => 'sadari',
            'userid' => 'denny5959',
            'shopaddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX',
            'btc' => '0.0007',
            'price' => '500',
            'date' => '20160725145755',
            'result' => 'SUCCESS',
            'param1' => '201607250000003313',
            'param2' => 'php1test',
            'param3' => '3215_6',
            'param4' => 'b0a2a8f69df10d7525f05f4aa59021c6',
            'param5' => '',
            'merchant_extra' => [
                'userid' => 'denny5959',
                'btcAddr' => '1Lsc3PCskKz58KDMwFE8VsfJbEmCDiSRTj'],
        ];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('test');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'mode' => 'deposit',
            'type' => 's',
            'shopid' => 'sadari',
            'userid' => 'denny5959',
            'shopaddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX',
            'btc' => '0.0007',
            'price' => '500',
            'date' => '20160725145755',
            'result' => 'SUCCESS',
            'param1' => '201607250000003313',
            'param2' => 'php1test',
            'param3' => '3215_6',
            'param4' => 'b0a2a8f69df10d7525f05f4aa59021c6',
            'param5' => '',
            'merchant_extra' => [
                'userid' => 'denny5959',
                'btcAddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX'],
        ];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('1234');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testVerifyOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'mode' => 'deposit',
            'type' => 's',
            'shopid' => 'sadari',
            'userid' => 'denny5959',
            'shopaddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX',
            'btc' => '0.0007',
            'price' => '500',
            'date' => '20160725145755',
            'result' => 'SUCCESS',
            'param1' => '201607250000003313',
            'param2' => 'php1test',
            'param3' => '3215_6',
            'param4' => 'b0a2a8f69df10d7525f05f4aa59021c6',
            'param5' => '',
            'merchant_extra' => [
                'userid' => 'denny5959',
                'btcAddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX'],
        ];

        $entry = ['id' => '201607250000003333'];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('3lgWuzArpklpuiPP');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testVerifyOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'mode' => 'deposit',
            'type' => 's',
            'shopid' => 'sadari',
            'userid' => 'denny5959',
            'shopaddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX',
            'btc' => '0.0007',
            'price' => '500',
            'date' => '20160725145755',
            'result' => 'SUCCESS',
            'param1' => '201607250000003313',
            'param2' => 'php1test',
            'param3' => '3215_6',
            'param4' => 'b0a2a8f69df10d7525f05f4aa59021c6',
            'param5' => '',
            'merchant_extra' => [
                'userid' => 'denny5959',
                'btcAddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX'],
        ];

        $entry = [
            'id' => '201607250000003313',
            'amount' => '501.0000',
        ];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('3lgWuzArpklpuiPP');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testVerifyOrder()
    {
        $options = [
            'mode' => 'deposit',
            'type' => 's',
            'shopid' => 'sadari',
            'userid' => 'denny5959',
            'shopaddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX',
            'btc' => '0.0007',
            'price' => '500',
            'date' => '20160725145755',
            'result' => 'SUCCESS',
            'param1' => '201607250000003313',
            'param2' => 'php1test',
            'param3' => '3215_6',
            'param4' => 'b0a2a8f69df10d7525f05f4aa59021c6',
            'param5' => '',
            'merchant_extra' => [
                'userid' => 'denny5959',
                'btcAddr' => '194EskJoSRjob8g8qoiq8D6K1j9iSyktqX'],
        ];

        $entry = [
            'id' => '201607250000003313',
            'amount' => '500.0000',
        ];

        $kbpay = new Kbpay();
        $kbpay->setPrivateKey('3lgWuzArpklpuiPP');
        $kbpay->setOptions($options);
        $kbpay->verifyOrderPayment($entry);
    }
}
