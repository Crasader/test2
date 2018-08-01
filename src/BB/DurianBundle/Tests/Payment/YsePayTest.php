<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YsePay;
use Buzz\Message\Response;

class YsePayTest extends DurianTestCase
{
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

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $dropEnd = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $dropLine = str_replace("\n", '', $dropEnd);
        $pkcsPublic = wordwrap($dropLine, 64, "\n");

        $this->publicKey = $pkcsPublic;
        $this->privateKey = base64_encode($pkcsPrivate);

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

        $sourceData = [];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
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

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '99999',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試支付時缺少商家額外的參數設定seller_name
     */
    public function testPayWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'merchant_extra' => [],
            'paymentVendorId' => '1',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA私鑰為空字串
     */
    public function testPayGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1',
            'rsa_private_key' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA私鑰失敗
     */
    public function testPayGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1',
            'rsa_private_key' => base64_decode($this->publicKey),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少sign
     */
    public function testPayReturnWithoutSign()
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
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '1',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'postUrl' => 'payment.https.qrcode.ysepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少ysepay_online_qrcodepay_response
     */
    public function testPayReturnWithoutYsepayOnlineQrcodepayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706220000003093',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '1',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'postUrl' => 'payment.https.qrcode.ysepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試二維支付時返回驗簽失敗
     */
    public function testPayReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $qrcodepayResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'out_trade_no' => '201708070000003750',
            'trade_no' => '01O170807328941756',
            'trade_status' => 'WAIT_BUYER_PAY',
            'total_amount' => '5.15',
            'qr_code_url' => 'https://pay.swiftpass.cn/pay/qrcode?uuid=weixin://wxpay/bizpayurl?pr=Gxv7rSH',
            'bank_type' => '1902000',
            'expire_time' => '10',
        ];

        $result = [
            'sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz',
            'ysepay_online_qrcodepay_response' => $qrcodepayResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706220000003093',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '1',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1090',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'postUrl' => 'payment.https.qrcode.ysepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $qrcodepayResponse = [
            'msg' => '业务异常',
            'sub_code' => '7990',
            'sub_msg' => '没找到路由',
        ];

        $result = [
            'sign' => $this->getSign(json_encode($qrcodepayResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'ysepay_online_qrcodepay_response' => $qrcodepayResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706220000003093',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '1',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'postUrl' => 'payment.https.qrcode.ysepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試二維支付時有返回錯誤訊息
     */
    public function testPayReturnHasSubMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '没找到路由',
            180130
        );

        $qrcodepayResponse = [
            'code' => '40004',
            'msg' => '业务异常',
            'sub_code' => '7990',
            'sub_msg' => '没找到路由',
        ];

        $result = [
            'sign' => $this->getSign(json_encode($qrcodepayResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'ysepay_online_qrcodepay_response' => $qrcodepayResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706220000003093',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '1',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1090',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'postUrl' => 'payment.https.qrcode.ysepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少source_qr_code_url
     */
    public function testPayReturnWithoutSourceQrcodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $qrcodepayResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'out_trade_no' => '201708070000003750',
            'trade_no' => '01O170807328941756',
            'trade_status' => 'WAIT_BUYER_PAY',
            'total_amount' => '5.15',
            'qr_code_url' => 'https://pay.swiftpass.cn/pay/qrcode?uuid=weixin://wxpay/bizpayurl?pr=Gxv7rSH',
            'bank_type' => '1902000',
            'expire_time' => '10',
        ];

        $result = [
            'sign' => $this->getSign(json_encode($qrcodepayResponse, JSON_UNESCAPED_SLASHES)),
            'ysepay_online_qrcodepay_response' => $qrcodepayResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '1',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1090',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'postUrl' => 'payment.https.qrcode.ysepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->getVerifyData();
    }

    /**
     * 測試微信支付
     */
    public function testWxPay()
    {
        $qrcodepayResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'out_trade_no' => '201708070000003750',
            'trade_no' => '01O170807328941756',
            'trade_status' => 'WAIT_BUYER_PAY',
            'total_amount' => '5.15',
            'qr_code_url' => 'https://pay.swiftpass.cn/pay/qrcode?uuid=weixin://wxpay/bizpayurl?pr=Gxv7rSH',
            'bank_type' => '1902000',
            'expire_time' => '10',
            'source_qr_code_url' => 'weixin://wxpay/bizpayurl?pr=Gxv7rSH',
        ];

        $result = [
            'sign' => $this->getSign(json_encode($qrcodepayResponse, JSON_UNESCAPED_SLASHES)),
            'ysepay_online_qrcodepay_response' => $qrcodepayResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '1',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1090',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'postUrl' => 'payment.https.qrcode.ysepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $data = $ysePay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=Gxv7rSH', $ysePay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => 'zhifu669',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'merchant_extra' => [
                'seller_name' => '成都戴荣富商贸有限公司',
                'business_code' => '9527',
            ],
            'paymentVendorId' => '1',
            'postUrl' => 'ysepay.com',
            'rsa_private_key' => $this->privateKey,
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $requestData = $ysePay->getVerifyData();

        $encodeData = [];

        foreach ($requestData['params'] as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = $requestData['params'][$key];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = $this->getSign($encodeStr);
        $postUrl = 'https://openapi.' . $sourceData['postUrl'] . '/gateway.do';
        $this->assertEquals($postUrl, $requestData['post_url']);
        $this->assertEquals('ysepay.online.directpay.createbyuser', $requestData['params']['method']);
        $this->assertEquals($sourceData['number'], $requestData['params']['partner_id']);
        $this->assertEquals($sourceData['orderCreateDate'], $requestData['params']['timestamp']);
        $this->assertEquals('UTF-8', $requestData['params']['charset']);
        $this->assertEquals('RSA', $requestData['params']['sign_type']);
        $this->assertEquals($sourceData['notify_url'], $requestData['params']['notify_url']);
        $this->assertEquals('3.0', $requestData['params']['version']);
        $this->assertEquals($sourceData['orderId'], $requestData['params']['out_trade_no']);
        $this->assertEquals($sourceData['username'], $requestData['params']['subject']);
        $this->assertEquals($sourceData['amount'], $requestData['params']['total_amount']);
        $this->assertEquals($sourceData['number'], $requestData['params']['seller_id']);
        $this->assertEquals($sourceData['merchant_extra']['seller_name'], $requestData['params']['seller_name']);
        $this->assertEquals('96h', $requestData['params']['timeout_express']);
        $this->assertEquals('internetbank', $requestData['params']['pay_mode']);
        $this->assertEquals('1021000', $requestData['params']['bank_type']);
        $this->assertEquals('personal', $requestData['params']['bank_account_type']);
        $this->assertEquals('debit', $requestData['params']['support_card_type']);
        $this->assertEquals('9527', $requestData['params']['business_code']);
        $this->assertEquals($sign, $requestData['params']['sign']);
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

        $ysePay = new YsePay();
        $ysePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回Sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
        ];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家公鑰為空字串
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $sourceData = [
            'sign' => '123',
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
            'rsa_public_key' => '',
        ];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家公鑰失敗
     */
    public function testReturnGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $sourceData = [
            'sign' => '123',
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
            'rsa_public_key' => 'test',
        ];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->verifyOrderPayment([]);
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
            'sign' => '123',
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
            'rsa_public_key' => $this->publicKey,
        ];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->verifyOrderPayment([]);
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

        $encodeData = [
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'WAIT_BUYER_PAY',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'sign' => $this->getSign($encodeStr),
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'WAIT_BUYER_PAY',
            'rsa_public_key' => $this->publicKey,
        ];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->verifyOrderPayment([]);
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

        $encodeData = [
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'sign' => $this->getSign($encodeStr),
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = ['id' => '2014052200123'];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->verifyOrderPayment($entry);
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

        $encodeData = [
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'sign' => $this->getSign($encodeStr),
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201708070000003759',
            'amount' => '1.0000',
        ];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $encodeData = [
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'sign' => $this->getSign($encodeStr),
            'total_amount' => '0.02',
            'trade_no' => '01O170807329477618',
            'notify_time' => '2017-08-07 15:36:15',
            'account_date' => '20170807',
            'sign_type' => 'RSA',
            'notify_type' => 'directpay.status.sync',
            'out_trade_no' => '201708070000003759',
            'trade_status' => 'TRADE_SUCCESS',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201708070000003759',
            'amount' => '0.02',
        ];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $ysePay->getMsg());
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->paymentTracking();
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

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => '',
        ];

        $ysePay = new YsePay();
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得商家私鑰為空字串
     */
    public function testTrackingGetMerchantKeyFileContentNull()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => '',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得商家私鑰失敗
     */
    public function testTrackingGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => 'acctest',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
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
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為缺少回傳參數Sign
     */
    public function testPaymentTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為缺少回傳ysepay_online_trade_query_response
     */
    public function testPaymentTrackingResultWithoutYsepayOnlineTradeQueryResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = ['sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
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

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'WAIT_BUYER_PAY',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz',
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為缺少回傳code
     */
    public function testPaymentTrackingResultWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $queryResponse = [
            'msg' => '业务异常',
            'sub_code' => 'ACQ.QUERY_NO_RESULT',
            'sub_msg' => '查询无记录',
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回有錯誤訊息
     */
    public function testTrackingReturnHasSubMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查询无记录',
            180123
        );

        $queryResponse = [
            'code' => '40004',
            'msg' => '业务异常',
            'sub_code' => 'ACQ.QUERY_NO_RESULT',
            'sub_msg' => '查询无记录',
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'WAIT_BUYER_PAY',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
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

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'TRADE_CLOSED',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢單號錯誤
     */
    public function testPaymentTrackingOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201706210000003081',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單金額錯誤
     */
    public function testPaymentTrackingOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201708070000003757',
            'amount' => '1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201708070000003757',
            'amount' => '100.47',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setContainer($this->container);
        $ysePay->setClient($this->client);
        $ysePay->setResponse($response);
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTracking();
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

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->getPaymentTrackingData();
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
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201708070000003757',
            'amount' => '100.47',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $trackingData = $ysePay->getPaymentTrackingData();

        $encodeData = [];

        foreach ($trackingData['form'] as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = $trackingData['form'][$key];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = $this->getSign($encodeStr);
        $bizContent = json_encode(['out_trade_no' => $sourceData['orderId']]);

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/gateway.do', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('ysepay.online.trade.query', $trackingData['form']['method']);
        $this->assertEquals($sourceData['number'], $trackingData['form']['partner_id']);
        $this->assertEquals($sourceData['orderCreateDate'], $trackingData['form']['timestamp']);
        $this->assertEquals('UTF-8', $trackingData['form']['charset']);
        $this->assertEquals('RSA', $trackingData['form']['sign_type']);
        $this->assertEquals('3.0', $trackingData['form']['version']);
        $this->assertEquals($bizContent, $trackingData['form']['biz_content']);
        $this->assertEquals($sign, $trackingData['form']['sign']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數ysepay_online_trade_query_response
     */
    public function testPaymentTrackingVerifyWithoutYsepayOnlineTradeQueryResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = ['sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz'];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'WAIT_BUYER_PAY',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz',
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數code
     */
    public function testPaymentTrackingVerifyWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $queryResponse = [
            'msg' => '业务异常',
            'sub_code' => 'ACQ.QUERY_NO_RESULT',
            'sub_msg' => '查询无记录',
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果有錯誤訊息
     */
    public function testPaymentTrackingVerifyHasSubMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查询无记录',
            180123
        );

        $queryResponse = [
            'code' => '40004',
            'msg' => '业务异常',
            'sub_code' => 'ACQ.QUERY_NO_RESULT',
            'sub_msg' => '查询无记录',
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果訂單處理中
     */
    public function testPaymentTrackingVerifyOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'WAIT_BUYER_PAY',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'TRADE_CLOSED',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付金額錯誤
     */
    public function testPaymentTrackingVerifyWithPayAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '2017008080000000595',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '2017008080000000595',
            'amount' => '1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $queryResponse = [
            'code' => '10000',
            'msg' => 'Success',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201708070000003757',
            'trade_no' => '01O170807329279463',
            'total_amount' => 100.47,
        ];

        $result = [
            'sign' => $this->getSign(json_encode($queryResponse, JSON_UNESCAPED_UNICODE)),
            'ysepay_online_trade_query_response' => $queryResponse,
        ];

        $sourceData = [
            'number' => 'zhifu669',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'orderId' => '201708070000003757',
            'amount' => '100.47',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ];

        $ysePay = new YsePay();
        $ysePay->setPrivateKey('test');
        $ysePay->setOptions($sourceData);
        $ysePay->paymentTrackingVerify();
    }

    /**
     * 組成支付平台回傳的sign
     *
     * @param string $encParam
     * @return string
     */
    private function getSign($encParam)
    {
        $passphrase = 'test';

        $content = base64_decode($this->privateKey);

        $privateCert = [];
        openssl_pkcs12_read($content, $privateCert, $passphrase);

        $key = $privateCert['pkey'];

        $sign = '';

        openssl_sign($encParam, $sign, $key);

        return base64_encode($sign);
    }
}
