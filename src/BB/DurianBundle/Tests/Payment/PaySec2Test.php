<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\PaySec2;
use Buzz\Message\Response;

class PaySec2Test extends DurianTestCase
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

        $paySec2 = new PaySec2();
        $paySec2->getVerifyData();
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

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->getVerifyData();
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
            'number' => '4857ce3a-7175-47fb-9e25-4cb384dc4abd',
            'orderId' => '201804030000010741',
            'amount' => 10,
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => '',
            'orderCreateDate' => '2018-04-03 11:13:04',
            'paymentVendorId' => '999',
        ];

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->getVerifyData();
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

        $options = [
            'number' => '4857ce3a-7175-47fb-9e25-4cb384dc4abd',
            'orderId' => '201804030000010741',
            'amount' => 10,
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => '',
            'orderCreateDate' => '2018-04-03 11:13:04',
            'paymentVendorId' => '1',
        ];

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->getVerifyData();
    }

    /**
     * 測試取得token時沒有返回status
     */
    public function testGetTokenWothoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '4857ce3a-7175-47fb-9e25-4cb384dc4abd',
            'orderId' => '201804030000010741',
            'amount' => 10,
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.paysecure.paysec.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-04-03 11:13:04',
            'paymentVendorId' => '1',
        ];

        $result = [
            'header' => [],
            'body' => null,
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent(json_encode($result));
        $response->addHeader('application/json;charset=UTF-8');

        $paySec2 = new PaySec2();
        $paySec2->setContainer($this->container);
        $paySec2->setClient($this->client);
        $paySec2->setResponse($response);
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->getVerifyData();
    }

    /**
     * 測試取得token失敗且未回傳訊息
     */
    public function testGetTokenFailAndWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '4857ce3a-7175-47fb-9e25-4cb384dc4abd',
            'orderId' => '201804030000010741',
            'amount' => 10,
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.paysecure.paysec.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-04-03 11:13:04',
            'paymentVendorId' => '1',
        ];

        $result = [
            'header' => [
                'status' => 'FAILURE',
                'statusMessage' => [
                    'code' => '503',
                ],
            ],
            'body' => null,
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent(json_encode($result));
        $response->addHeader('application/json;charset=UTF-8');

        $paySec2 = new PaySec2();
        $paySec2->setContainer($this->container);
        $paySec2->setClient($this->client);
        $paySec2->setResponse($response);
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->getVerifyData();
    }

    /**
     * 測試取得token失敗
     */
    public function testGetTokenFail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid Currency for Merchant',
            180130
        );

        $options = [
            'number' => '4857ce3a-7175-47fb-9e25-4cb384dc4abd',
            'orderId' => '201804030000010741',
            'amount' => 10,
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.paysecure.paysec.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-04-03 11:13:04',
            'paymentVendorId' => '1',
        ];

        $result = [
            'header' => [
                'status' => 'FAILURE',
                'statusMessage' => [
                    'code' => '503',
                    'statusMessage' => 'Invalid Currency for Merchant',
                ],
            ],
            'body' => null,
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent(json_encode($result));
        $response->addHeader('application/json;charset=UTF-8');

        $paySec2 = new PaySec2();
        $paySec2->setContainer($this->container);
        $paySec2->setClient($this->client);
        $paySec2->setResponse($response);
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->getVerifyData();
    }

    /**
     * 測試取得token成功卻未返回token
     */
    public function testGetTokenWithoutToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '4857ce3a-7175-47fb-9e25-4cb384dc4abd',
            'orderId' => '201804030000010741',
            'amount' => 10,
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.paysecure.paysec.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-04-03 11:13:04',
            'paymentVendorId' => '1',
        ];

        $result = [
            'header' => [
                'status' => 'SUCCESS',
                'statusMessage' => null,
            ],
            'body' => [],
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent(json_encode($result));
        $response->addHeader('application/json;charset=UTF-8');

        $paySec2 = new PaySec2();
        $paySec2->setContainer($this->container);
        $paySec2->setClient($this->client);
        $paySec2->setResponse($response);
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testOnlinePay()
    {
        $options = [
            'number' => '4857ce3a-7175-47fb-9e25-4cb384dc4abd',
            'orderId' => '201804030000010741',
            'amount' => 10,
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.paysecure.paysec.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-04-03 11:13:04',
            'paymentVendorId' => '1',
        ];

        $token = 'eyJhbGciOiJIUzUxMiJ95jb20iLCJzdWIiOiIyODg5MDpudWxsIiwiZXhwIjo' .
            'yMTE1MzExNTcwLCJpYXQiOjE1MjI3MTg5OTAsImp0aSI6IjAuMSJ9.kqGXmCMQKGHJ' .
            '-u393iewGWXmNTTvF8zlCx1yahZvjahFhwzhZF79NJld099S8z0iYNxGc0KN0ntWVnlAGCWWvw';

        $result = [
            'header' => [
                'status' => 'SUCCESS',
                'statusMessage' => null,
            ],
            'body' => [
                'token' => $token,
            ],
        ];

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturn(json_encode($result));

        $paySec2 = new PaySec2();
        $paySec2->setContainer($this->container);
        $paySec2->setClient($this->client);
        $paySec2->setResponse($response);
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $data = $paySec2->getVerifyData();

        $this->assertEquals($token, $data['token']);
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '4857ce3a-7175-47fb-9e25-4cb384dc4abd',
            'orderId' => '201804030000010741',
            'amount' => 10,
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.paysecure.paysec.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-04-03 11:13:04',
            'paymentVendorId' => '1103',
        ];

        $token = 'eyJhbGciOiJIUzUxMiJ95jb20iLCJzdWIiOiIyODg5MDpudWxsIiwiZXhwIjo' .
            'yMTE1MzExNTcwLCJpYXQiOjE1MjI3MTg5OTAsImp0aSI6IjAuMSJ9.kqGXmCMQKGHJ' .
            '-u393iewGWXmNTTvF8zlCx1yahZvjahFhwzhZF79NJld099S8z0iYNxGc0KN0ntWVnlAGCWWvw';

        $result = [
            'header' => [
                'status' => 'SUCCESS',
                'statusMessage' => null,
            ],
            'body' => [
                'token' => $token,
            ],
        ];

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturn(json_encode($result));

        $paySec2 = new PaySec2();
        $paySec2->setContainer($this->container);
        $paySec2->setClient($this->client);
        $paySec2->setResponse($response);
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $data = $paySec2->getVerifyData();

        $this->assertEquals($token, $data['token']);
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

        $paySec2 = new PaySec2();
        $paySec2->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'orderAmount' => '10.00',
            'orderTime' => 1522729246432,
            'transactionReference' => 'WXX3SZ6FHBKV2DIPDEPX17ZHX',
            'completedTime' => 1522729299164,
            'cartId' => '201804030000010723',
            'currency' => 'CNY',
            'version' => '3.0',
            'statusMessage' => null,
            'status' => 'SUCCESS',
        ];

        $entry = [
            'merchant_number' => '123456789',
        ];

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->verifyOrderPayment($entry);
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
            'orderAmount' => '10.00',
            'orderTime' => 1522729246432,
            'transactionReference' => 'WXX3SZ6FHBKV2DIPDEPX17ZHX',
            'signature' => 'mShLOt64pmofgGciYqO9LyJuTYYjTu2',
            'completedTime' => 1522729299164,
            'cartId' => '201804030000010723',
            'currency' => 'CNY',
            'version' => '3.0',
            'statusMessage' => null,
            'status' => 'SUCCESS',
        ];

        $entry = [
            'merchant_number' => '123456789',
        ];

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->verifyOrderPayment($entry);
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
            'orderAmount' => '10.00',
            'orderTime' => 1522729246432,
            'transactionReference' => 'WXX3SZ6FHBKV2DIPDEPX17ZHX',
            'signature' => 'tetuXZGYsWK9I',
            'completedTime' => 1522729299164,
            'cartId' => '201804030000010723',
            'currency' => 'CNY',
            'version' => '3.0',
            'statusMessage' => null,
            'status' => 'FAILED',
        ];

        $entry = [
            'merchant_number' => '123456789',
        ];

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->verifyOrderPayment($entry);
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
            'orderAmount' => '10.00',
            'orderTime' => 1522729246432,
            'transactionReference' => 'WXX3SZ6FHBKV2DIPDEPX17ZHX',
            'signature' => 'teXMQXqvSfNvM',
            'completedTime' => 1522729299164,
            'cartId' => '201804030000010723',
            'currency' => 'CNY',
            'version' => '3.0',
            'statusMessage' => null,
            'status' => 'SUCCESS',
        ];

        $entry = [
            'merchant_number' => '123456789',
            'id' => '201801190000003819',
        ];

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->verifyOrderPayment($entry);
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
            'orderAmount' => '10.00',
            'orderTime' => 1522729246432,
            'transactionReference' => 'WXX3SZ6FHBKV2DIPDEPX17ZHX',
            'signature' => 'teXMQXqvSfNvM',
            'completedTime' => 1522729299164,
            'cartId' => '201804030000010723',
            'currency' => 'CNY',
            'version' => '3.0',
            'statusMessage' => null,
            'status' => 'SUCCESS',
        ];

        $entry = [
            'merchant_number' => '123456789',
            'id' => '201804030000010723',
            'amount' => 2.8,
        ];

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'orderAmount' => '10.00',
            'orderTime' => 1522729246432,
            'transactionReference' => 'WXX3SZ6FHBKV2DIPDEPX17ZHX',
            'signature' => 'teXMQXqvSfNvM',
            'completedTime' => 1522729299164,
            'cartId' => '201804030000010723',
            'currency' => 'CNY',
            'version' => '3.0',
            'statusMessage' => null,
            'status' => 'SUCCESS',
        ];

        $entry = [
            'merchant_number' => '123456789',
            'id' => '201804030000010723',
            'amount' => 10,
        ];

        $paySec2 = new PaySec2();
        $paySec2->setPrivateKey('test');
        $paySec2->setOptions($options);
        $paySec2->verifyOrderPayment($entry);

        $this->assertEquals('OK', $paySec2->getMsg());
    }

    /**
     * 產生假對外返回物件
     *
     * @return Response
     */
    private function mockReponse()
    {
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->method('getStatusCode')
            ->willReturn(200);

        return $response;
    }
}
