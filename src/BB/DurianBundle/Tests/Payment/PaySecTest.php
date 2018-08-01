<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\PaySec;
use Buzz\Message\Response;

class PaySecTest extends DurianTestCase
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
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $paySec = new PaySec();
        $paySec->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數(PostUrl)
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['postUrl' => ''];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'postUrl' => 'http://pay.paysec.com',
            'paymentVendorId' => '999',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->getVerifyData();
    }

    /**
     * 測試支付加密時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'postUrl' => 'http://pay.paysec.com',
            'number' => 'M999-C-423',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201708240000006857',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->getVerifyData();
    }

    /**
     * 測試支付加密時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'postUrl' => 'http://pay.paysec.com',
            'number' => 'M999-C-423',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201708240000006857',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->getVerifyData();
    }

    /**
     * 測試網銀支付加密對外返回缺少token
     */
    public function testPayWithoutToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];
        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'http://pay.paysec.com',
            'number' => 'M999-C-423',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201708240000006857',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->getVerifyData();
    }

    /**
     * 測試掃碼支付加密對外返回缺少token
     */
    public function testPayScanWithoutToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];
        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'http://pay.paysec.com',
            'number' => 'M999-C-423',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201708240000006857',
            'orderCreateDate' => '2017-08-24 14:02:00',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $token = 'xTd04wS VKIw9dfoFF2coU4xYYtKrtI4tLfXIybyLelu/W6u8xwHVTXy9vXFmkamQ2' .
            'J43kI/GVwYypmZ9fQB0zJA7JwcG3/DfDJdDRJVhWjDsB3P36s2AbzPZXqjgdMC!/3UcpqNDDQw2PLDNHHWdpA==';
        $result = ['token' => $token];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'http://pay.paysec.com',
            'number' => 'M999-C-423',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201708240000006857',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $encodeData = $paySec->getVerifyData();

        $this->assertEquals('http://pay.paysec.com/GUX/GPay', $encodeData['post_url']);
        $this->assertEquals($token, $encodeData['params']['token']);
    }

    /**
     * 測試支付加密帶入泰國銀行
     */
    public function testPayWithThb()
    {
        $token = 'xTd04wS VKIw9dfoFF2coU4xYYtKrtI4tLfXIybyLelu/W6u8xwHVTXy9vXFmkamQ2' .
            'J43kI/GVwYypmZ9fQB0zJA7JwcG3/DfDJdDRJVhWjDsB3P36s2AbzPZXqjgdMC!/3UcpqNDDQw2PLDNHHWdpA==';
        $result = ['token' => $token];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'http://pay.paysec.com',
            'number' => 'M999-C-423',
            'paymentVendorId' => '31',
            'amount' => '0.01',
            'orderId' => '201708240000006857',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $encodeData = $paySec->getVerifyData();

        $this->assertEquals('http://pay.paysec.com/GUX/GPay', $encodeData['post_url']);
        $this->assertEquals($token, $encodeData['params']['token']);
    }

    /**
     * 測試掃碼支付加密
     */
    public function testPayScan()
    {
        $token = 'xTd04wS VKIw9dfoFF2coU4xYYtKrtI4tLfXIybyLelu/W6u8xwHVTXy9vXFmkamQ2' .
            'J43kI/GVwYypmZ9fQB0zJA7JwcG3/DfDJdDRJVhWjDsB3P36s2AbzPZXqjgdMC!/3UcpqNDDQw2PLDNHHWdpA==';
        $result = [
            'header' => '',
            'body' => ['token' => $token],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'http://pay.paysec.com',
            'number' => 'M999-C-423',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201708240000006857',
            'orderCreateDate' => '2017-08-24 14:02:00',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $encodeData = $paySec->getVerifyData();

        $this->assertEquals('http://pay.paysec.com/payin-wechat/send-tokenform', $encodeData['post_url']);
        $this->assertEquals($token, $encodeData['params']['token']);
    }

    /**
     * 測試返回沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $paySec = new PaySec();
        $paySec->verifyOrderPayment([]);
    }

    /**
     * 測試返回未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $entry = ['payment_vendor_id' => '1'];

        $sourceData = ['mid' => 'M999-C-423'];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);
    }

    /**
     * 測試返回缺少簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'amt' => '10.00',
            'cartid' => '201708240000006857',
            'status' => 'SUCCESS',
            'EPKey' => '',
        ];

        $entry = ['payment_vendor_id' => '1'];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);
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

        $sourceData = [
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'amt' => '10.00',
            'cartid' => '201708240000006857',
            'signature' => 'eb9f003ddf3ad2cc4ecb2f6f4bde27f2',
            'status' => 'SUCCESS',
            'EPKey' => '',
        ];

        $entry = ['payment_vendor_id' => '1'];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);
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
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'amt' => '10.00',
            'cartid' => '201708240000006857',
            'signature' => 'b75eaabe8ffd4499e4e0bfde7f180349',
            'status' => 'FAIL',
            'EPKey' => '',
        ];

        $entry = ['payment_vendor_id' => '1'];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);
    }

    /**
     * 測試返回但單號不正確
     */
    public function testReturnButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'amt' => '10.00',
            'cartid' => '201708240000006857',
            'signature' => 'be42d66543f840f48eae9cd6b3fb3acc',
            'status' => 'SUCCESS',
            'EPKey' => '',
        ];

        $entry = [
            'payment_vendor_id' => '1',
            'id' => '201708240000006855',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);
    }

    /**
     * 測試返回但金額不正確
     */
    public function testReturnButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'amt' => '10.00',
            'cartid' => '201708240000006857',
            'signature' => 'be42d66543f840f48eae9cd6b3fb3acc',
            'status' => 'SUCCESS',
            'EPKey' => '',
        ];

        $entry = [
            'payment_vendor_id' => '1',
            'id' => '201708240000006857',
            'amount' => '1.00',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'amt' => '10.00',
            'cartid' => '201708240000006857',
            'signature' => 'be42d66543f840f48eae9cd6b3fb3acc',
            'status' => 'SUCCESS',
            'EPKey' => '',
        ];

        $entry = [
            'payment_vendor_id' => '1',
            'id' => '201708240000006857',
            'amount' => '10.00',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);

        $this->assertEquals('OK', $paySec->getMsg());
    }

    /**
     * 測試二維返回但單號不正確
     */
    public function testReturnScanButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'merchantCode' => 'M999-C-444',
            'reference' => 'M151-WC-747782',
            'currency' => 'CNY',
            'amount' => '10.00',
            'cartId' => '201708240000006857',
            'signature' => '7115917f2f95e3d89297581bb0d836ec',
            'status' => 'SUCCESS',
            'epKey' => '',
            'userDefinedField' => '',
        ];

        $entry = [
            'payment_vendor_id' => '1090',
            'id' => '201708240000006855',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);
    }

    /**
     * 測試二維返回但金額不正確
     */
    public function testReturnScanButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'merchantCode' => 'M999-C-444',
            'reference' => 'M151-WC-747782',
            'currency' => 'CNY',
            'amount' => '10.00',
            'cartId' => '201708240000006857',
            'signature' => '7115917f2f95e3d89297581bb0d836ec',
            'status' => 'SUCCESS',
            'epKey' => '',
            'userDefinedField' => '',
        ];

        $entry = [
            'payment_vendor_id' => '1090',
            'id' => '201708240000006857',
            'amount' => '1.00',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);
    }

    /**
     * 測試二維返回成功
     */
    public function testReturnScanSuccess()
    {
        $sourceData = [
            'merchantCode' => 'M999-C-444',
            'reference' => 'M151-WC-747782',
            'currency' => 'CNY',
            'amount' => '10.00',
            'cartId' => '201708240000006857',
            'signature' => '7115917f2f95e3d89297581bb0d836ec',
            'status' => 'SUCCESS',
            'epKey' => '',
            'userDefinedField' => '',
        ];

        $entry = [
            'payment_vendor_id' => '1090',
            'id' => '201708240000006857',
            'amount' => '10.00',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->verifyOrderPayment($entry);

        $this->assertEquals('OK', $paySec->getMsg());
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

        $paySec = new PaySec();
        $paySec->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '31',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數
     */
    public function testTrackingReturnWithoutParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $returnValues = ['mid' => ''];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '31',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $returnValues = [
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'status' => 'FAILED',
            'cartid' => '201708240000006857',
            'createdDateTime' => '2017-08-22 11:03:06',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->paymentTracking();
    }

    /**
     * 測試訂單查詢結果但單號不正確
     */
    public function testTrackingReturnButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $returnValues = [
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'status' => 'SUCCESS',
            'cartid' => '201708240000006857',
            'createdDateTime' => '2017-08-22 11:03:06',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006858',
            'amount' => '10.00',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->paymentTracking();
    }

    /**
     * 測試訂單查詢結果成功
     */
    public function testTrackingReturnSuccess()
    {
        $returnValues = [
            'mid' => 'M999-C-423',
            'oid' => 'M151-PO-2082445',
            'cur' => 'CNY',
            'status' => 'SUCCESS',
            'cartid' => '201708240000006857',
            'createdDateTime' => '2017-08-22 11:03:06',
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->paymentTracking();
    }

    /**
     * 測試二維訂單查詢結果缺少回傳參數
     */
    public function testTrackingReturnScanWithoutParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $returnValues = [
            'header' => '',
            'body' => [],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->paymentTracking();
    }

    /**
     * 測試二維訂單查詢結果為失敗
     */
    public function testTrackingReturnScanPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $returnValues = [
            'header' => '',
            'body' => [
                'transactionStatus' => 'SENT',
                'transactionReference' => 'M152-WC-757313',
                'qrCode' => 'weixin://wxpay/bizpayurl?pr=cDMb3P3',
            ],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->paymentTracking();
    }

    /**
     * 測試二維訂單查詢結果成功
     */
    public function testTrackingReturnScanSuccess()
    {
        $returnValues = [
            'header' => '',
            'body' => [
                'transactionStatus' => 'COMPLETED',
                'transactionReference' => 'M152-WC-757313',
                'qrCode' => 'weixin://wxpay/bizpayurl?pr=cDMb3P3',
            ],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setContainer($this->container);
        $paySec->setClient($this->client);
        $paySec->setResponse($response);
        $paySec->setPrivateKey('1234');
        $paySec->setOptions($sourceData);
        $paySec->paymentTracking();
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

        $paySec = new PaySec();
        $paySec->getPaymentTrackingData();
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

        $paySec = new PaySec();
        $paySec->setPrivateKey('test');
        $paySec->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒帶入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '31',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('test');
        $paySec->setOptions($sourceData);
        $paySec->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '31',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('test');
        $paySec->setOptions($sourceData);
        $trackingData = $paySec->getPaymentTrackingData();

        $this->assertEquals('/GUX/GQueryPayment', $trackingData['path']);
        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);

        $this->assertEquals($sourceData['number'], $trackingData['form']['CID']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['v_CartID']);
        $this->assertEquals($sourceData['amount'], $trackingData['form']['v_amount']);
        $this->assertEquals('THB', $trackingData['form']['v_currency']);
        $this->assertEquals('05ab3b5cd2a29f63f160612f4ac1e324', $trackingData['form']['signature']);
    }

    /**
     * 測試二維取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingDataWithScan()
    {
        $sourceData = [
            'number' => 'M999-C-423',
            'orderId' => '201708240000006857',
            'amount' => '10.00',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.paysec.com',
        ];

        $paySec = new PaySec();
        $paySec->setPrivateKey('test');
        $paySec->setOptions($sourceData);
        $trackingData = $paySec->getPaymentTrackingData();

        $this->assertEquals('/payin-wechat/status', $trackingData['path']);
        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);

        $this->assertEquals('1.0', $trackingData['json']['header']['version']);
        $this->assertEquals($sourceData['number'], $trackingData['json']['header']['merchantCode']);
        $this->assertEquals('61be16f37c3062038f690604f6f9e2e1', $trackingData['json']['header']['signature']);
        $this->assertEquals($sourceData['orderId'], $trackingData['json']['body']['cartId']);
        $this->assertEquals($sourceData['amount'], $trackingData['json']['body']['orderAmount']);
        $this->assertEquals('CNY', $trackingData['json']['body']['currency']);
    }
}
