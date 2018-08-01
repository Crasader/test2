<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\TCPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class TCPayTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $option;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

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

        $this->option = [
            'orderId' => '201805220000046263',
            'orderCreateDate' => '2018-05-22 11:40:05',
            'amount' => '5',
            'notify_url' => 'http://www.seafood.help/',
            'paymentVendorId' => '1102',
            'number' => '82d060c7-dff2-48c6-9316-17b97ea178ca',
            'merchant_extra' => ['accessToken' => 'WwOIKQoCAQYAAHbVr5oAAACS'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.maikopay.com',
        ];

        $content = [
            'data' => [
                'merchant_id' => '82d060c7-dff2-48c6-9316-17b97ea178ca',
                'channel_id' => '9a0aa317-77d0-45d7-82a8-f81b9de1a6f1',
                'merchant_order_no' => '201805220000046263',
                'merchant_order_date' => 1526958541,
                'order_no' => '201805220309034',
                'product_name' => '201805220000046263',
                'remark' => '201805220000046263',
                'amount' => '5',
                'redirect_url' => 'https://fintech.moneypay.cloud/formal/notify/zysy/payment/pay.php?bizContext=G/b4=',
                'status' => 2,
            ],
            'sign_code' => 'xEwweCiApnMyjJVB4+16BQowj3Y=',
        ];
        $this->returnResult = [
            'content' => json_encode($content),
            'merchant_extra' => ['accessToken' => 'WwOIKQoCAQYAAHbVr5oAAACS'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.maikopay.com',
        ];
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

        $tCPay = new TCPay();
        $tCPay->getVerifyData();
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

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->getVerifyData();
    }

    /**
     * 測試支付時未帶入paymentVendorId參數
     */
    public function testPayWithoutPaymentVendorId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        unset($this->option['paymentVendorId']);

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $tCPay->getVerifyData();
    }

    /**
     * 測試支付時未帶入number參數
     */
    public function testPayWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        unset($this->option['number']);

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $tCPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $tCPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定accessToken
     */
    public function testPayButMerchantExtraWithoutAccessToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $this->option['merchant_extra'] = [];

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $tCPay->getVerifyData();
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

        $this->option['verify_url'] = '';

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $tCPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回status
     */
    public function testPayReturnWithoutStatus()
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
        $response->addHeader('Content-Type:application/json');

        $tCPay = new TCPay();
        $tCPay->setContainer($this->container);
        $tCPay->setClient($this->client);
        $tCPay->setResponse($response);
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $tCPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'order' => [
                'status' => 0,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tCPay = new TCPay();
        $tCPay->setContainer($this->container);
        $tCPay->setClient($this->client);
        $tCPay->setResponse($response);
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $tCPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回redirect_url
     */
    public function testPayReturnWithoutRedirectUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'order' => [
                'merchant_id' => '82d060c7-dff2-48c6-9316-17b97ea178ca',
                'channel_id' => '9a0aa317-77d0-45d7-82a8-f81b9de1a6f1',
                'merchant_order_no' => '201805220000046263',
                'merchant_order_date' => 1526958541,
                'order_no' => '201805220309034',
                'product_name' => '201805220000046263',
                'remark' => '201805220000046263',
                'amount' => '5.00',
                'status' => 1,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tCPay = new TCPay();
        $tCPay->setContainer($this->container);
        $tCPay->setClient($this->client);
        $tCPay->setResponse($response);
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $tCPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $result = [
            'order' => [
                'merchant_id' => '82d060c7-dff2-48c6-9316-17b97ea178ca',
                'channel_id' => '9a0aa317-77d0-45d7-82a8-f81b9de1a6f1',
                'merchant_order_no' => '201805220000046263',
                'merchant_order_date' => 1526958541,
                'order_no' => '201805220309034',
                'product_name' => '201805220000046263',
                'remark' => '201805220000046263',
                'amount' => '5.00',
                'redirect_url' => 'https://fintech.moneypay.cloud/formal/notify/zysy/payment/pay.php?bizContext=G/b4=',
                'status' => 1,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tCPay = new TCPay();
        $tCPay->setContainer($this->container);
        $tCPay->setClient($this->client);
        $tCPay->setResponse($response);
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->option);
        $data = $tCPay->getVerifyData();

        $this->assertEquals('https://fintech.moneypay.cloud/formal/notify/zysy/payment/pay.php', $data['post_url']);
        $this->assertEquals('G/b4=', $data['params']['bizContext']);
        $this->assertEquals('GET', $tCPay->getPayMethod());
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

        $tCPay = new TCPay();
        $tCPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回content參數
     */
    public function testReturnWithoutContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回data參數
     */
    public function testReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $this->returnResult['content'] = json_encode([]);

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment([]);
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

        $content = [
            'data' => [],
            'sign_code' => 'C2GyHWe0svEPQBiwR2BeBLGESuw=',
        ];
        $this->returnResult['content'] = json_encode($content);

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $content = json_decode($this->returnResult['content'], true);
        unset($content['sign_code']);

        $this->returnResult['content'] = json_encode($content);

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment([]);
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

        $content = json_decode($this->returnResult['content'], true);
        $content['sign_code'] = 'C2GyHWe0svEPQBiwR2BeBLGESuw=';

        $this->returnResult['content'] = json_encode($content);

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = json_decode($this->returnResult['content'], true);
        $content['data']['status'] = 3;
        $content['sign_code'] = '9q/sjcoh/yzxZxFCNBm1upZxtSg=';

        $this->returnResult['content'] = json_encode($content);

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment([]);
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

        $entry = ['id' => '9453'];

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201805220000046263',
            'amount' => '123',
        ];

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回但缺少商家額外的參數設定accessToken
     */
    public function testReturnButMerchantExtraWithoutAccessToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $entry = [
            'id' => '201805220000046263',
            'amount' => '5',
        ];

        $this->returnResult['merchant_extra'] = [];

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時沒有帶入verify_url的情況
     */
    public function testReturnWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $entry = [
            'id' => '201805220000046263',
            'amount' => '5',
        ];

        $this->returnResult['verify_url'] = '';

        $tCPay = new TCPay();
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果，但支付平台連線異常
     */
    public function testReturnButPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $entry = [
            'id' => '201805220000046263',
            'merchant_number' => '82d060c7-dff2-48c6-9316-17b97ea178ca',
            'amount' => '5',
        ];

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $tCPay = new TCPay();
        $tCPay->setContainer($this->container);
        $tCPay->setClient($this->client);
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tCPay->getMsg());
    }

    /**
     * 測試返回結果，但支付平台連線返回status code不為2xx
     */
    public function testReturnButReturnStatusCodeNot2xx()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $entry = [
            'id' => '201805220000046263',
            'merchant_number' => '82d060c7-dff2-48c6-9316-17b97ea178ca',
            'amount' => '5',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type:application/json');

        $tCPay = new TCPay();
        $tCPay->setContainer($this->container);
        $tCPay->setClient($this->client);
        $tCPay->setResponse($response);
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tCPay->getMsg());
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805220000046263',
            'merchant_number' => '82d060c7-dff2-48c6-9316-17b97ea178ca',
            'amount' => '5',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tCPay = new TCPay();
        $tCPay->setContainer($this->container);
        $tCPay->setClient($this->client);
        $tCPay->setResponse($response);
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tCPay->getMsg());
    }

    /**
     * 測試返回結果，且支付平台連線返回status code為2xx
     */
    public function testReturnWithReturnStatusCodeIs2xx()
    {
        $entry = [
            'id' => '201805220000046263',
            'merchant_number' => '82d060c7-dff2-48c6-9316-17b97ea178ca',
            'amount' => '5',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 204 No Content');
        $response->addHeader('Content-Type:application/json');

        $tCPay = new TCPay();
        $tCPay->setContainer($this->container);
        $tCPay->setClient($this->client);
        $tCPay->setResponse($response);
        $tCPay->setPrivateKey('test');
        $tCPay->setOptions($this->returnResult);
        $tCPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tCPay->getMsg());
    }
}
