<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Pay35;
use Buzz\Message\Response;

class Pay35Test extends DurianTestCase
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

        $pay35 = new Pay35();
        $pay35->getVerifyData();
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

        $sourceData = ['number' => ''];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'paymentVendorId' => '999',
            'amount' => '2.00',
            'orderId' => '201707200000003498',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->getVerifyData();
    }

    /**
     * 測試支付時缺少商家額外的參數設定merchantCerNo
     */
    public function testPayWithoutMerchantExtra()

    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201707200000003498',
            'username' => 'php1test',
            'merchant_extra' => [],
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQRcodePay()
    {
        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201707200000003498',
            'username' => 'php1test',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $encodeData = $pay35->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchantNo']);
        $this->assertEquals($sourceData['merchant_extra']['merchantCerNo'], $encodeData['merchantCerNo']);
        $this->assertEquals('2d5ab4e4bfb15a66644587dba8e3c6f8', $encodeData['sign']);
        $this->assertEquals('MD5', $encodeData['signType']);
        $this->assertEquals($sourceData['orderId'], $encodeData['outTradeNo']);
        $this->assertEquals('CNY', $encodeData['currency']);
        $this->assertEquals('1', $encodeData['amount']);
        $this->assertEquals($sourceData['username'], $encodeData['content']);
        $this->assertEquals('WECHAT_QRCODE_PAY', $encodeData['payType']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['returnURL']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackURL']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201707200000003498',
            'username' => 'php1test',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $encodeData = $pay35->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchantNo']);
        $this->assertEquals($sourceData['merchant_extra']['merchantCerNo'], $encodeData['merchantCerNo']);
        $this->assertEquals('b2bc58cda0d7d8cc4b27fdff8202b9bb', $encodeData['sign']);
        $this->assertEquals('MD5', $encodeData['signType']);
        $this->assertEquals($sourceData['orderId'], $encodeData['outTradeNo']);
        $this->assertEquals('CNY', $encodeData['currency']);
        $this->assertEquals('1', $encodeData['amount']);
        $this->assertEquals($sourceData['username'], $encodeData['content']);
        $this->assertEquals('DEBIT_BANK_CARD_PAY', $encodeData['payType']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['returnURL']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackURL']);
        $this->assertEquals('ICBC', $encodeData['defaultBank']);
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

        $pay35 = new Pay35();
        $pay35->verifyOrderPayment([]);
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

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->verifyOrderPayment([]);
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
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003532',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 2,
            'amount' => 2,
            'tradeNo' => 'UYGH573025XCSQ',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->verifyOrderPayment([]);
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
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => '16a884b3288bf4ecf51558608ca8f92a',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003532',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 2,
            'amount' => 2,
            'tradeNo' => 'UYGH573025XCSQ',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->verifyOrderPayment([]);
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
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => '75dc320923ca1fa9f5f62212fa36eb75',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003532',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 2,
            'amount' => 2,
            'tradeNo' => 'UYGH573025XCSQ',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED_FAILED',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->verifyOrderPayment([]);
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
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => '3aaed5bad081720dbba6c9726fa63d57',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003532',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 2,
            'amount' => 2,
            'tradeNo' => 'UYGH573025XCSQ',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED',
        ];

        $entry = ['id' => '201702090000001337'];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => '3aaed5bad081720dbba6c9726fa63d57',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003532',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 2,
            'amount' => 2,
            'tradeNo' => 'UYGH573025XCSQ',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED',
        ];

        $entry = [
            'id' => '201707210000003532',
            'amount' => '0.01',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => '3aaed5bad081720dbba6c9726fa63d57',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003532',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 2,
            'amount' => 2,
            'tradeNo' => 'UYGH573025XCSQ',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED',
        ];

        $entry = [
            'id' => '201707210000003532',
            'amount' => '0.02',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->verifyOrderPayment($entry);

        $this->assertEquals('SUCCEED', $pay35->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $pay35 = new Pay35();
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢加密缺少商家額外的參數設定merchantCerNo
     */
    public function testTrackingWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => [],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單不存在
     */
    public function testTrackingReturnWithOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单不存在',
            180123
        );

        $result = [
            'errorCode' => 'ERROR_BIZ_ERROR',
            'errorMsg' => '%E8%AE%A2%E5%8D%95%E4%B8%8D%E5%AD%98%E5%9C%A8',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為查詢失敗沒有錯誤訊息
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = ['errorCode' => 'ERROR_BIZ_ERROR'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為缺少回傳參數
     */
    public function testTrackingReturnNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => '06425a576a8ac77b63098b14f5a3b78c',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003533',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 0,
            'tradeNo' => 'BXQQ574025AFXS',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'WAITING_PAY',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果缺少回傳sign
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003533',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 0,
            'amount' => 1000,
            'tradeNo' => 'BXQQ574025AFXS',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'WAITING_PAY',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => '06425a576a8ac77b63098b14f5a3b78c',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003533',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 0,
            'amount' => 1000,
            'tradeNo' => 'BXQQ574025AFXS',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'WAITING_PAY',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => 'a414975904c6c50e919acdd69f4eaea5',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003533',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 0,
            'amount' => 1000,
            'tradeNo' => 'BXQQ574025AFXS',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'WAITING_PAY',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => '4140e2230bd3ee88837032e3f28422d9',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003533',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 0,
            'amount' => 1000,
            'tradeNo' => 'BXQQ574025AFXS',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'PAYED_FAILED',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => 'd02d9de57a312d7a575c36d6ea651734',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003533',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 0,
            'amount' => 1000,
            'tradeNo' => 'BXQQ574025AFXS',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingReturnWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => 'd02d9de57a312d7a575c36d6ea651734',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003533',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 0,
            'amount' => 1000,
            'tradeNo' => 'BXQQ574025AFXS',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201707210000003533',
            'amount' => '0.01',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = [
            'settleFee' => 0,
            'settlePeriod' => 'T1',
            'sign' => 'e32da7ec0a5fa49b6c23a925b6e24a3d',
            'payType' => 'DEBIT_BANK_CARD_PAY',
            'outTradeNo' => '201707210000003533',
            'signType' => 'MD5',
            'currency' => 'CNY',
            'payedAmount' => 1000,
            'amount' => 1000,
            'tradeNo' => 'BXQQ574025AFXS',
            'settleType' => 'SELF',
            'merchantNo' => 'EGCT52000RFFV',
            'status' => 'SETTLED',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201707210000003533',
            'amount' => '10',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setContainer($this->container);
        $pay35->setClient($this->client);
        $pay35->setResponse($response);
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少私鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $pay35 = new Pay35();
        $pay35->getPaymentTrackingData();
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

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少商家額外的參數設定merchantCerNo
     */
    public function testGetPaymentTrackingDataWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => [],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->getPaymentTrackingData();
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

        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $pay35->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => 'EGCT52000RFFV',
            'orderId' => '201702100000001344',
            'merchant_extra' => ['merchantCerNo' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.555pay.com',
        ];

        $pay35 = new Pay35();
        $pay35->setPrivateKey('test');
        $pay35->setOptions($sourceData);
        $trackingData = $pay35->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/native/com.opentech.cloud.pay.trade.query/1.0.0', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals($sourceData['number'], $trackingData['form']['merchantNo']);
        $this->assertEquals($sourceData['merchant_extra']['merchantCerNo'], $trackingData['form']['merchantCerNo']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['outTradeNo']);
        $this->assertEquals('7a53bc96eac5989c4136dbb9511ddc0d', $trackingData['form']['sign']);
        $this->assertEquals('MD5', $trackingData['form']['signType']);
    }
}
