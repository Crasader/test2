<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\AliPayApp;
use Buzz\Message\Response;

class AliPayAppTest extends DurianTestCase
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
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

    public function setUp()
    {
        parent::setUp();

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);
        $this->privateKey = $privkey;

        // Get public key
        $pubkey = openssl_pkey_get_details($res);
        $this->publicKey = $pubkey['key'];

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
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $aliPayApp = new AliPayApp();
        $aliPayApp->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰失敗
     */
    public function testPayGetMerchantPrivateKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/',
            'username' => 'testUser',
            'rsa_private_key' => '1234',
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->getVerifyData();
    }

    /**
     * 測試支付時生成加密簽名錯誤
     */
    public function testPayGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        // Get private key
        $privkey = '';
        openssl_pkey_export($res, $privkey);
        $privateKey = base64_encode($privkey);

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/',
            'username' => 'testUser',
            'rsa_private_key' => $privateKey,
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/',
            'username' => 'testUser',
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $requestData = $aliPayApp->getVerifyData();

        $content = json_decode($requestData['biz_content'], true);

        $this->assertEquals($options['number'], $requestData['app_id']);
        $this->assertEquals('utf-8', $requestData['charset']);
        $this->assertEquals('alipay.trade.app.pay', $requestData['method']);
        $this->assertEquals('RSA', $requestData['sign_type']);
        $this->assertEquals('1.0', $requestData['version']);
        $this->assertEquals($options['notify_url'], $requestData['notify_url']);
        $this->assertEquals($options['amount'], $content['total_amount']);
        $this->assertEquals($options['username'], $content['subject']);
        $this->assertEquals($options['orderId'], $content['out_trade_no']);
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

        $aliPayApp = new AliPayApp();
        $aliPayApp->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'sign_type' => 'RSA',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_SUCCESS',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得RSA公鑰失敗
     */
    public function testReturnGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $options = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'sign_type' => 'RSA',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_SUCCESS',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
            'sign' => '1234',
            'rsa_public_key' => '1234',
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->verifyOrderPayment([]);
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
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'sign_type' => 'RSA',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_SUCCESS',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
            'sign' => '1234',
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setPrivateKey('test');
        $aliPayApp->setOptions($options);
        $aliPayApp->verifyOrderPayment([]);
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

        $data = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_CLOSED',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
        ];
        ksort($data);

        $encodeStr = urldecode(http_build_query($data));
        $sign = '';

        openssl_sign($encodeStr, $sign, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'sign_type' => 'RSA',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_CLOSED',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
            'sign' => base64_encode($sign),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->verifyOrderPayment([]);
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

        $data = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_FINISHED',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
        ];
        ksort($data);

        $encodeStr = urldecode(http_build_query($data));
        $sign = '';

        openssl_sign($encodeStr, $sign, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'sign_type' => 'RSA',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_FINISHED',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
            'sign' => base64_encode($sign),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $entry = ['id' => '2015102700040153'];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->verifyOrderPayment($entry);
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

        $data = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_FINISHED',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
        ];
        ksort($data);

        $encodeStr = urldecode(http_build_query($data));
        $sign = '';

        openssl_sign($encodeStr, $sign, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'sign_type' => 'RSA',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_FINISHED',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
            'sign' => base64_encode($sign),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $entry = [
            'id' => '201611150000008467',
            'amount' => '500',
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $data = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_FINISHED',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
        ];
        ksort($data);

        $encodeStr = urldecode(http_build_query($data));
        $sign = '';

        openssl_sign($encodeStr, $sign, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'total_amount' => '2.00',
            'trade_no' => '2016071921001003030200089909',
            'refund_fee' => '0.00',
            'notify_time' => '2016-07-19 14:10:49',
            'subject' => 'testuser',
            'sign_type' => 'RSA',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'out_trade_no' => '201611150000008467',
            'trade_status' => 'TRADE_FINISHED',
            'version' => '1.0',
            'app_id' => '2015102700040153',
            'notify_id' => '4a91b7a78a503640467525113fb7d8bg8e',
            'sign' => base64_encode($sign),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $entry = [
            'id' => '201611150000008467',
            'amount' => '2.00',
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->verifyOrderPayment($entry);
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

        $aliPayApp = new AliPayApp();
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢取得商家私鑰失敗
     */
    public function testTrackingGetMerchantPrivateKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => '1234',
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢生成加密簽名錯誤
     */
    public function testTrackingGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        // Get private key
        $privkey = '';
        openssl_pkey_export($res, $privkey);
        $privateKey = base64_encode($privkey);

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => $privateKey,
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
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

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $aliPayApp = new AliPayApp();
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有alipay_trade_query_response的情況
     */
    public function testTrackingReturnWithoutAlipayTradeQueryResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aliPayApp.com',
        ];

        $result = 'error';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $aliPayApp = new AliPayApp();
        $aliPayApp->setContainer($this->container);
        $aliPayApp->setClient($this->client);
        $aliPayApp->setResponse($response);
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少返回Code
     */
    public function testTrackingReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aliPayApp.com',
        ];

        $result = ['alipay_trade_query_response' => ''];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $aliPayApp = new AliPayApp();
        $aliPayApp->setContainer($this->container);
        $aliPayApp->setClient($this->client);
        $aliPayApp->setResponse($response);
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢結果返回錯誤訊息
     */
    public function testTrackingReturnWithCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Service Currently Unavailable',
            180130
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aliPayApp.com',
        ];

        $result = [
            'alipay_trade_query_response' => [
                'code' => 20000,
                'msg' => 'Service Currently Unavailable',
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $aliPayApp = new AliPayApp();
        $aliPayApp->setContainer($this->container);
        $aliPayApp->setClient($this->client);
        $aliPayApp->setResponse($response);
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少驗證參數
     */
    public function testTrackingReturnNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aliPayApp.com',
        ];

        $result = [
            'alipay_trade_query_response' => [
                'code' => 10000,
                'msg' => 'Success',
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $aliPayApp = new AliPayApp();
        $aliPayApp->setContainer($this->container);
        $aliPayApp->setClient($this->client);
        $aliPayApp->setResponse($response);
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aliPayApp.com',
        ];

        $result = [
            'alipay_trade_query_response' => [
                'trade_no' => '2013112011001004330000121536',
                'out_trade_no' => '201608040000004412',
                'buyer_logon_id' => '159****5620',
                'trade_status' => 'WAIT_BUYER_PAY',
                'total_amount' => '2.00',
                'receipt_amount' => '2.00',
                'send_pay_date' => '2014-11-27 15:45:57',
                'fund_bill_list' => ['amount' => '2'],
                'buyer_user_id' => '2088101117955611',
                'discount_goods_detail' => [
                    'goods_id' => '1234',
                    'goods_name' => 'test',
                ],
                'code' => 10000,
                'msg' => 'Success',
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $aliPayApp = new AliPayApp();
        $aliPayApp->setContainer($this->container);
        $aliPayApp->setClient($this->client);
        $aliPayApp->setResponse($response);
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
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
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aliPayApp.com',
        ];

        $result = [
            'alipay_trade_query_response' => [
                'trade_no' => '2013112011001004330000121536',
                'out_trade_no' => '201608040000004412',
                'buyer_logon_id' => '159****5620',
                'trade_status' => 'TRADE_CLOSED',
                'total_amount' => '2.00',
                'receipt_amount' => '2.00',
                'send_pay_date' => '2014-11-27 15:45:57',
                'fund_bill_list' => ['amount' => '2'],
                'buyer_user_id' => '2088101117955611',
                'discount_goods_detail' => [
                    'goods_id' => '1234',
                    'goods_name' => 'test',
                ],
                'code' => 10000,
                'msg' => 'Success',
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $aliPayApp = new AliPayApp();
        $aliPayApp->setContainer($this->container);
        $aliPayApp->setClient($this->client);
        $aliPayApp->setResponse($response);
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為訂單金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'amount' => '500',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aliPayApp.com',
        ];

        $result = [
            'alipay_trade_query_response' => [
                'trade_no' => '2013112011001004330000121536',
                'out_trade_no' => '201608040000004412',
                'buyer_logon_id' => '159****5620',
                'trade_status' => 'TRADE_FINISHED',
                'total_amount' => '2.00',
                'receipt_amount' => '2.00',
                'send_pay_date' => '2014-11-27 15:45:57',
                'fund_bill_list' => ['amount' => '2'],
                'buyer_user_id' => '2088101117955611',
                'discount_goods_detail' => [
                    'goods_id' => '1234',
                    'goods_name' => 'test',
                ],
                'code' => 10000,
                'msg' => 'Success',
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $aliPayApp = new AliPayApp();
        $aliPayApp->setContainer($this->container);
        $aliPayApp->setClient($this->client);
        $aliPayApp->setResponse($response);
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'amount' => '2',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.aliPayApp.com',
        ];

        $result = [
            'alipay_trade_query_response' => [
                'trade_no' => '2013112011001004330000121536',
                'out_trade_no' => '201608040000004412',
                'buyer_logon_id' => '159****5620',
                'trade_status' => 'TRADE_FINISHED',
                'total_amount' => '2.00',
                'receipt_amount' => '2.00',
                'send_pay_date' => '2014-11-27 15:45:57',
                'fund_bill_list' => ['amount' => '2'],
                'buyer_user_id' => '2088101117955611',
                'discount_goods_detail' => [
                    'goods_id' => '1234',
                    'goods_name' => 'test',
                ],
                'code' => 10000,
                'msg' => 'Success',
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $aliPayApp = new AliPayApp();
        $aliPayApp->setContainer($this->container);
        $aliPayApp->setClient($this->client);
        $aliPayApp->setResponse($response);
        $aliPayApp->setOptions($options);
        $aliPayApp->paymentTracking();
    }
}
