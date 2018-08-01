<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BeeCloud;
use Buzz\Message\Response;

class BeeCloudTest extends DurianTestCase
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

        $beeCloud = new BeeCloud();
        $beeCloud->getVerifyData();
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

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->getVerifyData();
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
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '100',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'paymentGatewayId' => '105',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->getVerifyData();
    }

    /**
     * 測試支付時未指定支付實名認證參數
     */
    public function testPayWithNoPayRealNameAuthenticationParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay real name authentication parameter specified',
            150180187
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '278',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'paymentGatewayId' => '105',
            'merchant_extra' => ['real_name_auth' => 1],
            'real_name_auth_params' => [],
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線失敗
     */
    public function testPayReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'verify_url' => 'www.beecloud.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '105',
        ];

        $res = ['result_msg' => 'ConnectionFailure'];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 499');
        $respone->addHeader("Content-Type:application/json");

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($respone);
        $beeCloud->getVerifyData();
    }

    /**
     * 測試支付缺少回傳支付參數
     */
    public function testPayReturnNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'verify_url' => 'www.beecloud.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '105',
        ];

        $res = [
            'result_msg' => 'OK',
            'resultCode' => '0',
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json");

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($respone);
        $beeCloud->getVerifyData();
    }

    /**
     * 測試支付回傳結果為不合法
     */
    public function testPayReturnWithAppInvalid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'APP_INVALID:时间戳不正确，请检查机器时间',
            180130
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'verify_url' => 'www.beecloud.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '105',
        ];

        $res = [
            'result_msg' => 'APP_INVALID',
            'resultCode' => '1',
            'errMsg' => 'APP_INVALID:时间戳不正确，请检查机器时间'
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json");

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($respone);
        $beeCloud->getVerifyData();
    }

    /**
     * 測試支付缺少回傳html
     */
    public function testPayReturnWithoutHtml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'verify_url' => 'www.beecloud.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '105',
        ];

        $res = [
            'result_msg' => 'OK',
            'resultCode' => '0',
            'errMsg' => 'OK'
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json");

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($respone);
        $beeCloud->getVerifyData();
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
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'verify_url' => 'www.beecloud.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '105',
        ];

        $res = [
            'result_msg' => 'OK',
            'resultCode' => '0',
            'errMsg' => 'OK',
            'html' => '<html>' .
            '<body>' .
            '</body>' .
            '</html>'
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json");

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($respone);
        $beeCloud->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'verify_url' => 'www.beecloud.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '105',
        ];

        $res = [
            'result_msg' => 'OK',
            'resultCode' => '0',
            'errMsg' => 'OK',
            'html' => '<html>' .
            '<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"/></head>' .
            '<body>' .
            "<form id = \"pay_form\" action=\"https://beecloud/gateway/pay\" method=\"post\">" .
            "<input type=\"hidden\" name=\"orderId\" id=\"orderId\" value=\"201506100000002073\"/>" .
            "<input type=\"hidden\" name=\"backUrl\" id=\"backUrl\" value=\"http://154.58.78.54/\"/>" .
            "<input type=\"hidden\" name=\"signature\" id=\"signature\" value=\"123456789123456789123456789\"/>" .
            "<input type=\"hidden\" name=\"txnTime\" id=\"txnTime\" value=\"20160630152645\"/>" .
            "<input type=\"hidden\" name=\"orderDesc\" id=\"orderDesc\" value=\"test123\"/>" .
            "<input type=\"hidden\" name=\"txnAmt\" id=\"txnAmt\" value=\"10050\"/>" .
            '</form>' .
            '</body>' .
            '</html>'
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json");

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($respone);
        $requestData = $beeCloud->getVerifyData();

        $this->assertEquals('https://beecloud/gateway/pay', $requestData['post_url']);
        $this->assertEquals($options['notify_url'], $requestData['params']['backUrl']);
        $this->assertEquals($options['orderId'], $requestData['params']['orderId']);
        $this->assertEquals($options['username'], $requestData['params']['orderDesc']);
        $this->assertEquals($options['amount'] * 100, $requestData['params']['txnAmt']);
    }

    /**
     * 測試支付使用需實名認證商家
     */
    public function testPayWithRealNameAuthMerchant()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '278',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'verify_url' => 'www.beecloud.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '105',
            'merchant_extra' => ['real_name_auth' => 1],
            'real_name_auth_params' => ['card_no' => '123465798'],
        ];

        $html = '<html>' .
            '<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"/></head>' .
            '<body>' .
            "<form id = \"pay_form\" action=\"https://beecloud/gateway/pay\" method=\"post\">" .
            "<input type=\"hidden\" name=\"orderId\" id=\"orderId\" value=\"201506100000002073\"/>" .
            "<input type=\"hidden\" name=\"backUrl\" id=\"backUrl\" value=\"http://154.58.78.54/\"/>" .
            "<input type=\"hidden\" name=\"signature\" id=\"signature\" value=\"123456789123456789123456789\"/>" .
            "<input type=\"hidden\" name=\"txnTime\" id=\"txnTime\" value=\"20160630152645\"/>" .
            "<input type=\"hidden\" name=\"orderDesc\" id=\"orderDesc\" value=\"test123\"/>" .
            "<input type=\"hidden\" name=\"txnAmt\" id=\"txnAmt\" value=\"10050\"/>" .
            '</form>' .
            '</body>' .
            '</html>';

        $res = [
            'result_msg' => 'OK',
            'resultCode' => '0',
            'errMsg' => 'OK',
            'html' => $html,
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json");

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($respone);
        $requestData = $beeCloud->getVerifyData();

        $this->assertEquals('https://beecloud/gateway/pay', $requestData['post_url']);
        $this->assertEquals($options['notify_url'], $requestData['params']['backUrl']);
        $this->assertEquals($options['orderId'], $requestData['params']['orderId']);
        $this->assertEquals($options['username'], $requestData['params']['orderDesc']);
        $this->assertEquals($options['amount'] * 100, $requestData['params']['txnAmt']);
    }

    /**
     * 測試支付使用銀聯快捷
     */
    public function testPayByBCExpress()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '278',
            'orderId' => '201506100000002073',
            'amount' => '100.5',
            'number' => '123456',
            'username' => 'test123',
            'verify_url' => 'www.beecloud.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '105',
        ];

        $res = [
            'result_msg' => 'OK',
            'resultCode' => '0',
            'errMsg' => 'OK',
            'html' => '<html>' .
            '<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"/></head>' .
            '<body>' .
            "<form id = \"pay_form\" action=\"https://beecloud/gateway/pay\" method=\"post\">" .
            "<input type=\"hidden\" name=\"orderId\" id=\"orderId\" value=\"201506100000002073\"/>" .
            "<input type=\"hidden\" name=\"backUrl\" id=\"backUrl\" value=\"http://154.58.78.54/\"/>" .
            "<input type=\"hidden\" name=\"signature\" id=\"signature\" value=\"123456789123456789123456789\"/>" .
            "<input type=\"hidden\" name=\"txnTime\" id=\"txnTime\" value=\"20160630152645\"/>" .
            "<input type=\"hidden\" name=\"orderDesc\" id=\"orderDesc\" value=\"test123\"/>" .
            "<input type=\"hidden\" name=\"txnAmt\" id=\"txnAmt\" value=\"10050\"/>" .
            '</form>' .
            '</body>' .
            '</html>'
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json");

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($respone);
        $requestData = $beeCloud->getVerifyData();

        $this->assertEquals('https://beecloud/gateway/pay', $requestData['post_url']);
        $this->assertEquals($options['notify_url'], $requestData['params']['backUrl']);
        $this->assertEquals($options['orderId'], $requestData['params']['orderId']);
        $this->assertEquals($options['username'], $requestData['params']['orderDesc']);
        $this->assertEquals($options['amount'] * 100, $requestData['params']['txnAmt']);
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

        $beeCloud = new BeeCloud();
        $beeCloud->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回data
     */
    public function testReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->verifyOrderPayment([]);
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

        $options = ['timestamp' => '1462859926000'];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'timestamp' => '1462859926000',
            'trade_success' => true,
            'transaction_id' => '201506100000002073',
            'transaction_fee' => '1',
        ];

        $entry = ['merchant_number' => '1234'];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->verifyOrderPayment($entry);
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
            'timestamp' => '1462859926000',
            'trade_success' => true,
            'transaction_id' => '201506100000002073',
            'transaction_fee' => '1',
            'sign' => 'test1234',
        ];

        $entry = ['merchant_number' => '1234'];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->verifyOrderPayment($entry);
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
            'timestamp' => '1462859926000',
            'trade_success' => false,
            'transaction_id' => '201506100000002073',
            'transaction_fee' => '1',
            'sign' => '4def919946ee5e4d006f6d2cd24332e7',
        ];

        $entry = ['merchant_number' => '1234'];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->verifyOrderPayment($entry);
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
            'timestamp' => '1462859926000',
            'trade_success' => true,
            'transaction_id' => '201506100000002073',
            'transaction_fee' => '1',
            'sign' => '4def919946ee5e4d006f6d2cd24332e7',
        ];

        $entry = [
            'merchant_number' => '1234',
            'id' => '201509140000002475',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->verifyOrderPayment($entry);
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

        $options = [
            'timestamp' => '1462859926000',
            'trade_success' => true,
            'transaction_id' => '201506100000002073',
            'transaction_fee' => '1',
            'sign' => '4def919946ee5e4d006f6d2cd24332e7',
        ];

        $entry = [
            'merchant_number' => '1234',
            'id' => '201506100000002073',
            'amount' => 100,
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'timestamp' => '1462859926000',
            'trade_success' => true,
            'transaction_id' => '201506100000002073',
            'transaction_fee' => '1',
            'sign' => '4def919946ee5e4d006f6d2cd24332e7',
        ];

        $entry = [
            'merchant_number' => '1234',
            'id' => '201506100000002073',
            'amount' => 0.01,
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->verifyOrderPayment($entry);
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

        $beeCloud = new BeeCloud();
        $beeCloud->paymentTracking();
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

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->paymentTracking();
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

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
            'paymentVendorId' => '1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線異常
     */
    public function testTrackingReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
            'paymentVendorId' => '1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線失敗
     */
    public function testTrackingReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳結果為空
     */
    public function testTrackingReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
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

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $returnValues = ['result_msg' => 'OK'];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
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

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $returnValues = [
            'result_msg' => 'FALSE',
            'result_code' => '1',
            'bills' => [],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnWithoutTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $returnValues = [
            'result_msg' => 'OK',
            'result_code' => '0',
            'bills' => [],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testPaymentTrackingWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $returnValues = [
            'result_msg' => 'OK',
            'result_code' => '0',
            'bills' => [
                [
                    'spay_result' => false,
                    'bill_no' => '201506100000002075',
                    'total_fee' => 1
                ],
            ],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入訂單號不正確
     */
    public function testPaymentTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $returnValues = [
            'result_msg' => 'OK',
            'result_code' => '0',
            'bills' => [
                [
                    'spay_result' => true,
                    'bill_no' => '201506100000002075',
                    'total_fee' => 1
                ],
            ],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
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

        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $returnValues = [
            'result_msg' => 'OK',
            'result_code' => '0',
            'bills' => [
                [
                    'spay_result' => true,
                    'bill_no' => '201506100000002073',
                    'total_fee' => '100'
                ],
            ],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'orderId' => '201506100000002073',
            'number' => 'test123',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
            'paymentVendorId' => '1',
        ];

        $returnValues = [
            'result_msg' => 'OK',
            'result_code' => '0',
            'bills' => [
                [
                    'spay_result' => true,
                    'bill_no' => '201506100000002073',
                    'total_fee' => '1'
                ],
            ],
        ];

        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $beeCloud = new BeeCloud();
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $beeCloud->setOptions($options);
        $beeCloud->paymentTracking();
    }

    /**
     * 測試取得實名認證所需的參數欄位
     */
    public function testGetRealNameAuthParams()
    {
        $beeCloud = new BeeCloud();

        $realNameAuthParams = [
            'name',
            'id_no',
            'card_no',
        ];
        $this->assertEquals($realNameAuthParams, $beeCloud->getRealNameAuthParams());
    }

    /**
     * 測試實名認證時缺少私鑰
     */
    public function testRealNameAuthWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $beeCloud = new BeeCloud();
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時未指定認證參數
     */
    public function testRealNameAuthWithNoAuthenticationParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No authentication parameter specified',
            150180183
        );

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時付款廠商不需要實名認證
     */
    public function testRealNameAuthButPaymentVendorHaveNoNeedToAuthenticate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor have no need to authenticate',
            150180186
        );

        $options = [
            'paymentVendorId' => 1,
            'number' => 'test123',
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時沒有帶入verify_url的情況
     */
    public function testRealNameAuthWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'paymentVendorId' => 1088,
            'number' => 'test123',
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時支付平台連線異常
     */
    public function testRealNameAuthButPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'paymentVendorId' => 1088,
            'number' => 'test123',
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時支付平台連線失敗
     */
    public function testRealNameAuthButPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'paymentVendorId' => 1088,
            'number' => 'test123',
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時平台回傳結果為空
     */
    public function testRealNameAuthButEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'paymentVendorId' => 1088,
            'number' => 'test123',
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時認證結果未指定返回參數
     */
    public function testRealNameAuthWithNoAuthenticationReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No authentication return parameter specified',
            150180181
        );

        $returnValues = [
            'result_msg' => 'CHANNEL_ERROR',
            'err_detail' => '不支持该银行卡验证',
            'resultCode' => 7,
            'errMsg' => 'CHANNEL_ERROR:不支持该银行卡验证',
            'result_code' => 7,
        ];
        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'paymentVendorId' => 1088,
            'number' => 'test123',
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時認證失敗
     */
    public function testRealNameAuthButRealNameAuthenticationFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Real Name Authentication failure',
            150180182
        );

        $returnValues = [
            'result_msg' => 'OK',
            'auth_result' => false,
            'err_detail' => '',
            'resultCode' => 0,
            'errMsg' => 'OK:',
            'auth_msg' => '',
            'result_code' => 0,
            'card_id' => '123456a3-1234-4d32-bf02-54fb4b408f70',
        ];
        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'paymentVendorId' => 278,
            'number' => 'test123',
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->realNameAuth();
    }

    /**
     * 測試實名認證時認證成功
     */
    public function testRealNameAuthSuccess()
    {
        $returnValues = [
            'result_msg' => 'OK',
            'auth_result' => true,
            'err_detail' => '',
            'resultCode' => 0,
            'errMsg' => 'OK:',
            'auth_msg' => '',
            'result_code' => 0,
            'card_id' => '123456a3-1234-4d32-bf02-54fb4b408f70',
        ];
        $result = json_encode($returnValues);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'paymentVendorId' => 278,
            'number' => 'test123',
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $beeCloud = new BeeCloud();
        $beeCloud->setPrivateKey('test');
        $beeCloud->setOptions($options);
        $beeCloud->setContainer($this->container);
        $beeCloud->setClient($this->client);
        $beeCloud->setResponse($response);
        $beeCloud->realNameAuth();
    }
}
