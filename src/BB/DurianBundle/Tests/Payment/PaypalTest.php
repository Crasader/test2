<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Paypal;
use Buzz\Message\Response;

class PaypalTest extends DurianTestCase
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
     * 測試加密時沒有帶入privateKey的情況
     */
    public function testEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $paypal = new Paypal();
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時未指定支付參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $paypal = new Paypal();
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = ['number' => ''];

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時沒有帶入notify_url的情況
     */
    public function testEncodeWithoutNotifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $paypal = new Paypal();
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => '',
        ];

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時沒有帶入PaymentGatewayId的情況
     */
    public function testEncodeWithoutPaymentGatewayId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $paypal = new Paypal();
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '',
        ];

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時沒有帶入MerchantId的情況
     */
    public function testEncodeWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $paypal = new Paypal();
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '',
        ];

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時沒有帶入MerchantExtra的情況
     */
    public function testEncodeWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'merchant_extra' => [],
        ];

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時沒有帶入verifyUrl的情況
     */
    public function testEncodeWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => '',
            'merchant_extra' => ['Password' => ''],
        ];

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時沒有帶入postUrl的情況
     */
    public function testEncodeWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => 'api-3t.sandbox.paypal.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => '',
            'merchant_extra' => ['Password' => ''],
        ];

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時支付平台回傳結果為空
     */
    public function testPayEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => 'api-3t.sandbox.paypal.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
            'merchant_extra' => ['Password' => ''],
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線異常
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

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => 'api-3t.sandbox.paypal.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
            'merchant_extra' => ['Password' => ''],
        ];

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線失敗
     */
    public function testPayPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => 'api-3t.sandbox.paypal.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
            'merchant_extra' => ['Password' => ''],
        ];

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'ACK' => 'Failure'
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試取得token驗證沒有回傳狀態
     */
    public function testGetTokenWithoutACK()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => 'api-3t.sandbox.paypal.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
            'merchant_extra' => ['Password' => ''],
        ];

        $params = ['TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z'];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密時取得支付參數失敗
     */
    public function testPayGetParametersFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => 'api-3t.sandbox.paypal.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
            'merchant_extra' => ['Password' => ''],
        ];

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'ACK' => 'Failure'
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試取得token沒有回傳
     */
    public function testGetTokenWithoutToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://neteller.6te.net/neteller.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => 'api-3t.sandbox.paypal.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
            'merchant_extra' => ['Password' => ''],
        ];

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'ACK' => 'Success',
            'TOKEN' => ''
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $paypal->setOptions($sourceData);
        $paypal->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $sourceData = [
            'number' => 'keith3306-facilitator_api1.gmail.com',
            'orderId' => '2014102112345',
            'amount' => '10',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentGatewayId' => '82',
            'merchantId' => '12345',
            'verify_url' => 'api-3t.sandbox.paypal.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
            'merchant_extra' => ['Password' => ''],
            'domain' => '6',
        ];

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'ACK' => 'Success',
            'TOKEN' => 'EC%2d8A93818226262863G'
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');
        $paypal->setOptions($sourceData);
        $encodeData = $paypal->getVerifyData();

        $actUrl = sprintf(
            '%s?cmd=_express-checkout&token=%s',
            $sourceData['postUrl'],
            $params['TOKEN']
        );

        $this->assertEquals($actUrl, $encodeData['act_url']);
    }

    /**
     * 測試解密驗證缺少回傳token
     */
    public function testVerifyWithoutToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $paypal = new Paypal();
        $paypal->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證沒有帶入MerchantExtra的情況
     */
    public function testVerifyWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $paypal = new Paypal();
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => []
        ];

        $entry = ['merchant_number' => 'keith3306-facilitator_api1.gmail.com'];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證沒有帶入VerifyUrl的情況
     */
    public function testVerifyWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $paypal = new Paypal();
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = ['merchant_number' => 'keith3306-facilitator_api1.gmail.com'];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證沒有回傳付款人資訊
     */
    public function testVerifyWithoutPayerId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => ''
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = ['merchant_number' => 'keith3306-facilitator_api1.gmail.com'];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證沒有回傳訂單號
     */
    public function testVerifyWithoutInvNum()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => ''
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = ['merchant_number' => 'keith3306-facilitator_api1.gmail.com'];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證訂單號錯誤
     */
    public function testVerifyOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => '123456'
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);


        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = [
            'merchant_number' => 'keith3306-facilitator_api1.gmail.com',
            'id' => '201409220000000173'
        ];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證沒有回傳ACK
     */
    public function testVerifyWithoutACK()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => '201409220000000173',
            'ACK' => ''
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = [
            'merchant_number' => 'keith3306-facilitator_api1.gmail.com',
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時取得支付參數失敗
     */
    public function testReturnGetParametersFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => '201409220000000173',
            'ACK' => 'Failure'
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = [
            'merchant_number' => 'keith3306-facilitator_api1.gmail.com',
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證沒有回傳狀態
     */
    public function testVerifyWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => '201409220000000173',
            'ACK' => 'Success',
            'PAYMENTINFO_0_PAYMENTSTATUS' => ''
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = [
            'merchant_number' => 'keith3306-facilitator_api1.gmail.com',
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
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

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => '201409220000000173',
            'ACK' => 'Success',
            'PAYMENTINFO_0_PAYMENTSTATUS' => 'failure'
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = [
            'merchant_number' => 'keith3306-facilitator_api1.gmail.com',
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證沒有回傳金額
     */
    public function testVerifyWithoutAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => '201409220000000173',
            'ACK' => 'Success',
            'PAYMENTINFO_0_PAYMENTSTATUS' => 'Completed',
            'PAYMENTINFO_0_AMT' => ''
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = [
            'merchant_number' => 'keith3306-facilitator_api1.gmail.com',
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證金額錯誤
     */
    public function testVerifyAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => '201409220000000173',
            'ACK' => 'Success',
            'PAYMENTINFO_0_PAYMENTSTATUS' => 'Completed',
            'PAYMENTINFO_0_AMT' => '500'
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = [
            'merchant_number' => 'keith3306-facilitator_api1.gmail.com',
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證
     */
    public function testVerify()
    {
        $params = [
            'TIMESTAMP' => '2014%2d10%2d21T07%3a31%3a50Z',
            'PAYERID' => '3W4DMQGD4R4XA',
            'INVNUM' => '201409220000000173',
            'ACK' => 'Success',
            'PAYMENTINFO_0_PAYMENTSTATUS' => 'Completed',
            'PAYMENTINFO_0_AMT' => '0.01'
        ];

        $result = urlencode(http_build_query($params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $paypal = new Paypal();
        $paypal->setContainer($this->container);
        $paypal->setClient($this->client);
        $paypal->setResponse($response);
        $paypal->setPrivateKey('AFcWxV21C7fd0v3bYYYRCpSSRl31ABGO61P0el48z0PkhUKoXSe-cSoH');

        $sourceData = [
            'token' => 'EC%2d8A93818226262863G',
            'merchant_extra' => ['Password' => ''],
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $entry = [
            'merchant_number' => 'keith3306-facilitator_api1.gmail.com',
            'id' => '201409220000000173',
            'amount' => '0.01'
        ];

        $paypal->setOptions($sourceData);
        $paypal->verifyOrderPayment($entry);

        $this->assertEquals('success', $paypal->getMsg());
    }
}
