<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\AnFu91;
use Buzz\Message\Response;

class AnFu91Test extends DurianTestCase
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
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $anFu91 = new AnFu91();
        $anFu91->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions(['number' => '']);
        $anFu91->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPayWithNotSupportedBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1314',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1090',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privateKey');
        $anFu91->setOptions($options);
        $encodeData = $anFu91->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['Mer_code']);
        $this->assertEquals($options['orderId'], $encodeData['Billno']);
        $this->assertEquals($options['amount'], $encodeData['Amount']);
        $this->assertEquals('20170103', $encodeData['Date']);
        $this->assertEquals('RMB', $encodeData['Currency_Type']);
        $this->assertEquals('01', $encodeData['Gateway_Type']);
        $this->assertEquals($options['username'], $encodeData['Attach']);
        $this->assertEquals('5', $encodeData['OrderEncodeType']);
        $this->assertEquals('17', $encodeData['RetEncodeType']);
        $this->assertEquals('1', $encodeData['Rettype']);
        $this->assertEquals($options['notify_url'], $encodeData['ServerUrl']);
        $this->assertEquals('weixinpay', $encodeData['BankCo']);
        $this->assertEquals('8fe3997f0aaba9aa410d03ca08e510d4', $encodeData['SignMD5']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $anFu91 = new AnFu91();
        $anFu91->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testReturnWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'mercode' => '62012',
            'Currency_type' => 'RMB',
            'amount' => '1.00',
            'date' => '20170116',
            'succ' => 'Y',
            'msg' => '成功',
            'attach' => 'php1test',
            'ipsbillno' => '201701161140346294570x8dfc',
            'retencodetype' => '17',
            'bankbillno' => '',
            'signature' => '5641a6ae7aab37b4018b388b72c4fff1',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少signature
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'billno' => '201701160000000781',
            'mercode' => '62012',
            'Currency_type' => 'RMB',
            'amount' => '1.00',
            'date' => '20170116',
            'succ' => 'Y',
            'msg' => '成功',
            'attach' => 'php1test',
            'ipsbillno' => '201701161140346294570x8dfc',
            'retencodetype' => '17',
            'bankbillno' => '',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->verifyOrderPayment([]);
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
            'billno' => '201701160000000781',
            'mercode' => '62012',
            'Currency_type' => 'RMB',
            'amount' => '1.00',
            'date' => '20170116',
            'succ' => 'Y',
            'msg' => '成功',
            'attach' => 'php1test',
            'ipsbillno' => '201701161140346294570x8dfc',
            'retencodetype' => '17',
            'bankbillno' => '',
            'signature' => 'abc',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->verifyOrderPayment([]);
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

        $options = [
            'billno' => '201701160000000781',
            'mercode' => '62012',
            'Currency_type' => 'RMB',
            'amount' => '1.00',
            'date' => '20170116',
            'succ' => 'N',
            'msg' => '失敗',
            'attach' => 'php1test',
            'ipsbillno' => '201701161140346294570x8dfc',
            'retencodetype' => '17',
            'bankbillno' => '',
            'signature' => '2f05255622d6eb09e8cf6baba67b8bce',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->verifyOrderPayment([]);
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

        $options = [
            'billno' => '123',
            'mercode' => '62012',
            'Currency_type' => 'RMB',
            'amount' => '1.00',
            'date' => '20170116',
            'succ' => 'Y',
            'msg' => '成功',
            'attach' => 'php1test',
            'ipsbillno' => '201701161140346294570x8dfc',
            'retencodetype' => '17',
            'bankbillno' => '',
            'signature' => '083afdacf6cf0eb5f3aa72fb045717af',
        ];

        $entry = ['id' => '201701160000000781'];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->verifyOrderPayment($entry);
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

        $options = [
            'billno' => '201701160000000781',
            'mercode' => '62012',
            'Currency_type' => 'RMB',
            'amount' => '1.00',
            'date' => '20170116',
            'succ' => 'Y',
            'msg' => '成功',
            'attach' => 'php1test',
            'ipsbillno' => '201701161140346294570x8dfc',
            'retencodetype' => '17',
            'bankbillno' => '',
            'signature' => '8ca01b87319e6e877b82a0b61c9ef863',
        ];

        $entry = [
            'id' => '201701160000000781',
            'amount' => '0.1000',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'billno' => '201701160000000781',
            'mercode' => '62012',
            'Currency_type' => 'RMB',
            'amount' => '1.00',
            'date' => '20170116',
            'succ' => 'Y',
            'msg' => '成功',
            'attach' => 'php1test',
            'ipsbillno' => '201701161140346294570x8dfc',
            'retencodetype' => '17',
            'bankbillno' => '',
            'signature' => '8ca01b87319e6e877b82a0b61c9ef863',
        ];

        $entry = [
            'id' => '201701160000000781',
            'amount' => '1.00',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->verifyOrderPayment($entry);

        $this->assertEquals('success', $anFu91->getMsg());
    }

    /**
     * 測試訂單查詢沒代入privateKey
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $result = 'without private key';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithoutTrackingParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入 verifyUrl
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢只有回傳訊息(不符合文件定義的格式)
     */
    public function testTrackingReturnWithOnlyMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名错误',
            180123
        );

        $result = '签名错误';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳支付失敗
     */
    public function testTrackingReturnWithFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付失敗',
            180123
        );

        $result = 'fail|支付失敗';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳支付成功但缺少參數
     */
    public function testTrackingReturnWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        // 缺少簽名: 商號|訂單號|狀態|成功金額|成功時間|簽名
        $result = '62012|201701170000000820|unpaid|0|';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢返回缺少簽名
     */
    public function testTrackingReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        // 缺少簽名: 商號|訂單號|狀態|成功金額|成功時間|簽名
        $result = '62012|201701170000000820|unpaid|0|';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢返回簽名錯誤
     */
    public function testTrackingReturnWithSignatureFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '62012|201701170000000820|unpaid|0||wrong signature';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單已退款
     */
    public function testTrackingReturnOrderRefunded()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order has been refunded',
            180078
        );

        $result = '62012|201701170000000820|refund|0||050b4c84299b6f23326a7d662418df10';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnOrderUnpaid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = '62012|201701170000000820|unpaid|0||6eac205e2f0d989f98a4aac01c420598';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '62012',
            'orderId' => '201701170000000820',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢結果失敗
     */
    public function testTrackingPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '62012|201701170000000820|wtf|0||a6a88f29da4f9c1595f26585745c6470';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '62012',
            'orderId' => '201701170000000820',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();

    }

    /**
     * 測試訂單查詢結果訂單號不正確
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '62012|wrong order id|paid|0||35c8dcbb8a0e4463558d85e5d00bf391';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單金額不合法
     */
    public function testTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '015187|201404150014262827|paid|1.314||688bcef39cb21f1ed93b0eeb52bbd659';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
            'amount' => '4.312',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testTrackingSuccess()
    {
        $result = '015187|201404150014262827|paid|1.31||48b558b03996ff91f51dfefd95b47d68';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
            'amount' => '1.31',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setContainer($this->container);
        $anFu91->setClient($this->client);
        $anFu91->setResponse($response);
        $anFu91->setPrivateKey('privatekey');
        $anFu91->setOptions($options);
        $anFu91->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $anFu91 = new AnFu91();
        $anFu91->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithoutTrackingParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('privatekey');
        $anFu91->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('test');
        $anFu91->setOptions($options);
        $anFu91->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gw.91anfu.com',
        ];

        $result = [
            'Mer_code' => '015187',
            'Billno' => '201404150014262827',
            'SignMD5' => 'fcadb8045c0d885b1f567817747d532c',
        ];

        $anFu91 = new AnFu91();
        $anFu91->setPrivateKey('test');
        $anFu91->setOptions($options);
        $trackingData = $anFu91->getPaymentTrackingData();

        $this->assertEquals($result, $trackingData['form']);
        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/orderquery.aspx', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($options['verify_url'], $trackingData['headers']['Host']);
    }
}
