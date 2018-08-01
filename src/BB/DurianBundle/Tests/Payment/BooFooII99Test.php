<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BooFooII99;
use Buzz\Message\Response;

class BooFooII99Test extends DurianTestCase
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
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');

        $sourceData = ['number' => ''];

        $booFooII99->setOptions($sourceData);
        $booFooII99->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '03358',
            'orderCreateDate' => '20131128120000',
            'orderId' => '2',
            'amount' => '10',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $encodeData = $booFooII99->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('03358', $encodeData['MerchantID']);
        $this->assertEquals('20131128120000', $encodeData['TradeDate']);
        $this->assertEquals('2', $encodeData['TransID']);
        $this->assertEquals('1000.00', $encodeData['OrderMoney']);
        $this->assertEquals($notifyUrl, $encodeData['Merchant_url']);
        $this->assertEquals($notifyUrl, $encodeData['Return_url']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $booFooII99 = new BooFooII99();

        $booFooII99->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試Result:交易狀態)
     */
    public function testVerifyWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'resultDesc'     => '3',
            'factMoney'      => '1000.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => '97b252d52ed773eaafe47276d1c99f4f'
        ];

        $booFooII99->setOptions($sourceData);
        $booFooII99->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試Md5Sign:加密簽名)
     */
    public function testVerifyWithoutMd5Sign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '3',
            'factMoney'      => '1000.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000'
        ];

        $booFooII99->setOptions($sourceData);
        $booFooII99->verifyOrderPayment([]);
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

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '3',
            'factMoney'      => '1000.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => '97b252d52ed773eaafe47276d1c99f4f'
        ];

        $booFooII99->setOptions($sourceData);
        $booFooII99->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗(支付結果不為1)
     */
    public function testReturnPaymentFailureWithResultNotTrue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '2',
            'resultDesc'     => '3',
            'factMoney'      => '1000.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => '98db0dd1e2bc75b872c58c1955ca4a8b'
        ];

        $booFooII99->setOptions($sourceData);
        $booFooII99->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗(支付結果描述不為01)
     */
    public function testReturnPaymentFailureWithResultDescNotTrue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '1',
            'factMoney'      => '1000.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => '2b7c22b83ce65f7d87e35e319c72e699'
        ];

        $booFooII99->setOptions($sourceData);
        $booFooII99->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');

        $sourceData = [
            'MerchantID'     => '117766',
            'TransID'        => '201401030000000159',
            'Result'         => '1',
            'resultDesc'     => '01',
            'factMoney'      => '1',
            'additionalInfo' => '',
            'SuccTime'       => '20140103140738',
            'Md5Sign'        => '641e3fd98d9eb7d375c7e2100ca86e5e'
        ];

        $entry = ['id' => '20140113143143'];

        $booFooII99->setOptions($sourceData);
        $booFooII99->verifyOrderPayment($entry);
    }

    /**
     * 測試金額比對錯誤的情況
     */
    public function testAmountFailure()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');

        $sourceData = [
            'MerchantID'     => '117766',
            'TransID'        => '201401030000000159',
            'Result'         => '1',
            'resultDesc'     => '01',
            'factMoney'      => '1',
            'additionalInfo' => '',
            'SuccTime'       => '20140103140738',
            'Md5Sign'        => '641e3fd98d9eb7d375c7e2100ca86e5e'
        ];

        $entry = [
            'id' => '201401030000000159',
            'amount' => '0.10'
        ];

        $booFooII99->setOptions($sourceData);
        $booFooII99->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');

        $sourceData = [
            'MerchantID'     => '117766',
            'TransID'        => '201401030000000159',
            'Result'         => '1',
            'resultDesc'     => '01',
            'factMoney'      => '1',
            'additionalInfo' => '',
            'SuccTime'       => '20140103140738',
            'Md5Sign'        => '641e3fd98d9eb7d375c7e2100ca86e5e'
        ];

        $entry = [
            'id' => '201401030000000159',
            'amount' => '0.01'
        ];

        $booFooII99->setOptions($sourceData);
        $booFooII99->verifyOrderPayment($entry);

        $this->assertEquals('OK', $booFooII99->getMsg());
    }

    /**
     * 測試訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');
        $booFooII99->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入verifyUrl
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
         $booFooII99->paymentTracking();
     }

    /**
     * 測試訂單查詢結果簽名驗證參數分析錯誤
     */
    public function testTrackingReturnSignatureVerificationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '03358%7C2%7CY%7C100.00%7Cce7a4f736bd1550505183ad118f7183a';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.boofooII99.com'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setContainer($this->container);
        $booFooII99->setClient($this->client);
        $booFooII99->setResponse($response);
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '03358%7C2%7CY%7C100.00%7C20131128000000%7Cce7a4f736bd1550505183ad118f7183a';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.boofooII99.com'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setContainer($this->container);
        $booFooII99->setClient($this->client);
        $booFooII99->setResponse($response);
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = '03358%7C2%7CP%7C100.00%7C20131128000000%7Cae7f998a76b8683d6df1aac391ff9701';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.boofooII99.com'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setContainer($this->container);
        $booFooII99->setClient($this->client);
        $booFooII99->setResponse($response);
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = '03358%7C2%7CN%7C100.00%7C20131128000000%7Cce7a4f736bd1550505183ad118f7183a';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.boofooII99.com'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setContainer($this->container);
        $booFooII99->setClient($this->client);
        $booFooII99->setResponse($response);
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '03358%7C2%7CF%7C100.00%7C20131128000000%7C8ecd1062361deb817ca4c6fb24278bfb';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.boofooII99.com'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setContainer($this->container);
        $booFooII99->setClient($this->client);
        $booFooII99->setResponse($response);
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '117766%7C201401030000000159%7CY%7C1%7C20140103140738%7C256a12a251446b18b67f05614efbf2db';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.boofooII99.com',
            'amount' => '100'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setContainer($this->container);
        $booFooII99->setClient($this->client);
        $booFooII99->setResponse($response);
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = '117766%7C201401030000000159%7CY%7C1%7C20140103140738%7C256a12a251446b18b67f05614efbf2db';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.boofooII99.com',
            'amount' => '0.01'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setContainer($this->container);
        $booFooII99->setClient($this->client);
        $booFooII99->setResponse($response);
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');
        $booFooII99->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');
        $booFooII99->setOptions($options);
        $booFooII99->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '03358',
            'orderId' => '201401030000000159',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.paygate.baofoo.com',
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');
        $booFooII99->setOptions($options);
        $trackingData = $booFooII99->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/Check/OrderQuery.aspx', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.paygate.baofoo.com', $trackingData['headers']['Host']);

        $this->assertEquals('03358', $trackingData['form']['MerchantID']);
        $this->assertEquals('201401030000000159', $trackingData['form']['TransID']);
        $this->assertEquals('d72da8a6246be243c241adf6cd2e0695', $trackingData['form']['Md5Sign']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $booFooII99 = new BooFooII99();
        $booFooII99->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名參數分析錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'content' => '2%7CY%7C100.00%7C20131128000000%7Cce7a4f736bd1550505183ad118f7183a'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'content' => '03358%7C2%7CY%7C100.00%7C20131128000000%7Cce7a4f736bd1550505183ad118f7183a'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳訂單處理中
     */
    public function testPaymentTrackingVerifyOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $sourceData = [
            'content' => '03358%7C2%7CP%7C100.00%7C20131128000000%7Cae7f998a76b8683d6df1aac391ff9701'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳訂單不存在
     */
    public function testPaymentTrackingVerifyOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $sourceData = [
            'content' => '03358%7C2%7CN%7C100.00%7C20131128000000%7Cce7a4f736bd1550505183ad118f7183a'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'content' => '03358%7C2%7CF%7C100.00%7C20131128000000%7C8ecd1062361deb817ca4c6fb24278bfb'
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('1234');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'content' => '117766%7C201401030000000159%7CY%7C1%7C20140103140738%7C256a12a251446b18b67f05614efbf2db',
            'amount' => 100
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $sourceData = [
            'content' => '117766%7C201401030000000159%7CY%7C1%7C20140103140738%7C256a12a251446b18b67f05614efbf2db',
            'amount' => 0.01
        ];

        $booFooII99 = new BooFooII99();
        $booFooII99->setPrivateKey('3jpdzl59rwvwpg4j');
        $booFooII99->setOptions($sourceData);
        $booFooII99->paymentTrackingVerify();
    }
}
