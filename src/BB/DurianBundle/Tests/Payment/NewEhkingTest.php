<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewEhking;
use Buzz\Message\Response;

class NewEhkingTest extends DurianTestCase
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

        $newEhking = new NewEhking();
        $newEhking->getVerifyData();
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

        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');

        $sourceData = ['number' => ''];

        $newEhking->setOptions($sourceData);
        $newEhking->getVerifyData();
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

        $sourceData = [
            'number' => '120140257',
            'orderId' => '201608260000008190',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '100',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');
        $newEhking->setOptions($sourceData);
        $newEhking->getVerifyData();
    }

    /**
     * 測試支付時對外返回缺少參數
     */
    public function testPayWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'cause' => 'nonsupport exception',
            'error' => 'exception.not.support',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '120140257',
            'orderId' => '201608260000008190',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEhking = new NewEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');
        $newEhking->setOptions($sourceData);
        $newEhking->getVerifyData();
    }

    /**
     * 測試支付時對外返回結果錯誤
     */
    public function testPayWithReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'nonsupport exception',
            180130
        );

        $result = [
            'cause' => 'nonsupport exception',
            'error' => 'exception.not.support',
            'status' => 'ERROR',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '120140257',
            'orderId' => '201608260000008190',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEhking = new NewEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');
        $newEhking->setOptions($sourceData);
        $newEhking->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'redirectUrl' => 'http://www.newEhking.com/pay.php',
            'status' => 'REDIRECT',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '120140257',
            'orderId' => '201608260000008190',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEhking = new NewEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');
        $newEhking->setOptions($sourceData);
        $newEhking->getVerifyData();
    }

    /**
     * 測試支付，帶入微信二維但返回缺少scanCode
     */
    public function testPayWithWeixinButNoScanCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Qrcode not support',
            150180190
        );

        $result = ['status' => 'SUCCESS'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '120140257',
            'orderId' => '201608260000008190',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEhking = new NewEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');
        $newEhking->setOptions($sourceData);
        $newEhking->getVerifyData();
    }

    /**
     * 測試支付，帶入微信二維
     */
    public function testPayWithWeixin()
    {
        $result = [
            'status' => 'SUCCESS',
            'scanCode' => '/9j/4AAQSkZJRgABAgAAAQABAAD/2wBDAAgGBg',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '120140257',
            'orderId' => '201608260000008190',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEhking = new NewEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');
        $newEhking->setOptions($sourceData);
        $data = $newEhking->getVerifyData();

        $codeUrl = '<img src="data:image/png;base64, /9j/4AAQSkZJRgABAgAAAQABAAD/2wBDAAgGBg"/>';

        $this->assertEmpty($data);
        $this->assertEquals($codeUrl, $newEhking->getHtml());
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

        $newEhking = new NewEhking();

        $newEhking->verifyOrderPayment([]);
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

        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');

        $sourceData = [
            'pay_system' => '19551',
            'hallid' => '6',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'b30aca2b5a994d479007bc113e10fe56',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];

        $newEhking->setOptions($sourceData);
        $newEhking->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名數據(hmac)
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');

        $sourceData = [
            'pay_system' => '19551',
            'hallid' => '6',
            'completeDateTime' => '2016-08-29 15:57:32',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];

        $newEhking->setOptions($sourceData);
        $newEhking->verifyOrderPayment([]);
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

        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system' => '19551',
            'hallid' => '6',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'b30aca2b5a994d479007bc113e10fe56',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];

        $newEhking->setOptions($sourceData);
        $newEhking->verifyOrderPayment([]);
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

        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('b045b41582c7b44fc0345b490ac1586f');

        $sourceData = [
            'pay_system' => '19551',
            'hallid' => '6',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'a77a4dd073b66453302f51060b88e847',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'ERROR',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];

        $newEhking->setOptions($sourceData);
        $newEhking->verifyOrderPayment([]);
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

        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system' => '19551',
            'hallid' => '6',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'a82e02719781d481a25854468fb3c148',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '7e9b9f0c0cf844dae39c551b064b5dfc',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];

        $entry = ['id' => '20140102030405006'];

        $newEhking->setOptions($sourceData);
        $newEhking->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
                'BB\DurianBundle\Exception\PaymentException',
                'Order Amount error',
                180058
        );

        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system' => '19551',
            'hallid' => '6',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'a82e02719781d481a25854468fb3c148',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '7e9b9f0c0cf844dae39c551b064b5dfc',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];

        $entry = [
            'id' => '201608260000008190',
            'amount' => '1.0000'
        ];

        $newEhking->setOptions($sourceData);
        $newEhking->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $newEhking = new NewEhking();
        $newEhking->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system' => '19551',
            'hallid' => '6',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'a82e02719781d481a25854468fb3c148',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '7e9b9f0c0cf844dae39c551b064b5dfc',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];

        $entry = [
            'id' => '201608260000008190',
            'amount' => '0.01'
        ];

        $newEhking->setOptions($sourceData);
        $newEhking->verifyOrderPayment($entry);

        $this->assertEquals('success', $newEhking->getMsg());
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

        $newEhking = new newEhking();
        $newEhking->paymentTracking();
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

        $newEhking = new newEhking();
        $newEhking->setPrivateKey('1234567890');
        $newEhking->paymentTracking();
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
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $newEhking = new newEhking();
        $newEhking->setPrivateKey('1234567890');
        $newEhking->setOptions($sourceData);
        $newEhking->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $response = new Response();
        $response->setContent('null');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com'
        ];

        $newEhking = new newEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('1234567890');
        $newEhking->setOptions($sourceData);
        $newEhking->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有hmac的情況
     */
    public function testTrackingReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'bindCardId' => '',
            'completeDateTime' => '2016-08-29 15:57:32',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com'
        ];

        $newEhking = new newEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('1234567890');
        $newEhking->setOptions($sourceData);
        $newEhking->paymentTracking();
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

        $params = [
            'bindCardId' => '',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'b30aca2b5a994d479007bc113e10fe56',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com'
        ];

        $newEhking = new newEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('1234567890');
        $newEhking->setOptions($sourceData);
        $newEhking->paymentTracking();
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

        $params = [
            'bindCardId' => '',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => '7e9b9f0c0cf844dae39c551b064b5dfc',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'ERROR',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com'
        ];

        $newEhking = new newEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('1234567890');
        $newEhking->setOptions($sourceData);
        $newEhking->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入訂單號不正確
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $params = [
            'bindCardId' => '',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'e61ea527aed08642e08eed58abc20b5f',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com',
        ];

        $newEhking = new newEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('1234567890');
        $newEhking->setOptions($sourceData);
        $newEhking->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $params = [
            'bindCardId' => '',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'e61ea527aed08642e08eed58abc20b5f',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '201608260000008190',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com',
            'amount' => '100'
        ];

        $newEhking = new newEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('1234567890');
        $newEhking->setOptions($sourceData);
        $newEhking->paymentTracking();
    }

   /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $params = [
            'bindCardId' => '',
            'completeDateTime' => '2016-08-29 15:57:32',
            'hmac' => 'e61ea527aed08642e08eed58abc20b5f',
            'merchantId' => '120140257',
            'orderAmount' => '1',
            'orderCurrency' => 'CNY',
            'requestId' => '201608260000008190',
            'serialNumber' => '835b1a49cb6b48ef8bdf0f4a720f624a',
            'status' => 'SUCCESS',
            'totalRefundAmount' => '0',
            'totalRefundCount' => '0',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '201608260000008190',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newEhking.com',
            'amount' => '0.01'
        ];

        $newEhking = new newEhking();
        $newEhking->setContainer($this->container);
        $newEhking->setClient($this->client);
        $newEhking->setResponse($response);
        $newEhking->setPrivateKey('1234567890');
        $newEhking->setOptions($sourceData);
        $newEhking->paymentTracking();
    }
}
