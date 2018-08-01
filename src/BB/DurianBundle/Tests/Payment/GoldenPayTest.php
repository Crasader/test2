<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\GoldenPay;
use Buzz\Message\Response;

class GoldenPayTest extends DurianTestCase
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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);
        $this->privateKey = base64_encode($privkey);

        // Get public key
        $pubkey = openssl_pkey_get_details($res);

        $this->publicKey = base64_encode($pubkey['key']);

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

        $goldenPay = new GoldenPay();
        $goldenPay->getVerifyData();
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
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '9999',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定terId
     */
    public function testPayWithoutMerchantExtraTerId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1102',
            'merchant_extra' => [],
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰為空
     */
    public function testPayGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => '',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰失敗
     */
    public function testPayGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => 'test',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->getVerifyData();
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
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => '',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->getVerifyData();
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
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => '123456',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->getVerifyData();
    }

    /**
     * 測試支付時產生簽名失敗
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

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => base64_encode($privkey),
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $requestData = $goldenPay->getVerifyData();

        $this->assertEquals('1.0.9', $requestData['version']);
        $this->assertEquals($sourceData['number'], $requestData['merId']);
        $this->assertNotNull($requestData['sign']);
        $this->assertNotNull($requestData['encParam']);
    }

    /**
     * 測試支付寶手機支付
     */
    public function testPayWithAliPayAPP()
    {
        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002400',
            'username' => 'php1test',
            'amount' => '0.1',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1098',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $requestData = $goldenPay->getVerifyData();

        $this->assertEquals('1.0.9', $requestData['version']);
        $this->assertEquals($sourceData['number'], $requestData['merId']);
        $this->assertNotNull($requestData['sign']);
        $this->assertNotNull($requestData['encParam']);
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

        $goldenPay = new GoldenPay();
        $goldenPay->verifyOrderPayment([]);
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
            'sign' => '123456',
            'merId' => '201704101019585',
            'encParam' => 'test',
            'orderId' => '201704210000002403',
            'version' => '1.0.9',
            'rsa_public_key' => '',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment([]);
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
            'sign' => '123456',
            'merId' => '201704101019585',
            'encParam' => 'test',
            'orderId' => '201704210000002403',
            'version' => '1.0.9',
            'rsa_public_key' => 'test',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment([]);
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
            'sign' => '123456',
            'merId' => '201704101019585',
            'encParam' => 'test',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家私鑰為空字串
     */
    public function testReturnGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20170421142517',
            'payOrderId' => 'EVU704211424358133567113198910',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '10',
            'payType' => '1005',
            'orderId' => '201704210000002403',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $sourceData = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => '',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家私鑰失敗
     */
    public function testReturnGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20170421142517',
            'payOrderId' => 'EVU704211424358133567113198910',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '10',
            'payType' => '1005',
            'orderId' => '201704210000002403',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $sourceData = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => 'test',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回業務參數
     */
    public function testReturnWithoutEncParam()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20170421142517',
            'payOrderId' => 'EVU704211424358133567113198910',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '10',
            'payType' => '1005',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $sourceData = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment([]);
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
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20170421142517',
            'payOrderId' => 'EVU704211424358133567113198910',
            'selfParam' => '',
            'order_state' => '1009',
            'notifyType' => '1001',
            'money' => '10',
            'payType' => '1005',
            'orderId' => '201704210000002403',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $sourceData = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回單號不正確的情況
     */
    public function testReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20170421142517',
            'payOrderId' => 'EVU704211424358133567113198910',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '10',
            'payType' => '1005',
            'orderId' => '201704210000002403',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $sourceData = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = ['id' => '201704210000002402'];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確的情況
     */
    public function testReturnWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20170421142517',
            'payOrderId' => 'EVU704211424358133567113198910',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '10',
            'payType' => '1005',
            'orderId' => '201704210000002403',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $sourceData = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = [
            'id' => '201704210000002403',
            'amount' => '1',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testPaySuccess()
    {
        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20170421142517',
            'payOrderId' => 'EVU704211424358133567113198910',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '10',
            'payType' => '1005',
            'orderId' => '201704210000002403',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $sourceData = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = [
            'id' => '201704210000002403',
            'amount' => '0.1',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $goldenPay->getMsg());
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

        $goldenPay = new GoldenPay();
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得RSA公鑰為空
     */
    public function testTrackingGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => '',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得RSA公鑰失敗
     */
    public function testTrackingGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => 'test',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得RSA私鑰為空字串
     */
    public function testTrackingGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => '',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得RSA私鑰失敗
     */
    public function testTrackingGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => 'test',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢產生簽名失敗
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

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => base64_encode($privkey),
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未代入verifyUrl
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數encParam
     */
    public function testTrackingResultWithoutEncParam()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'sign' => 'test',
            'merId' => '201704101019585',
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢簽名驗證錯誤
     */
    public function testTrackingSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'sign' => 'test',
            'merId' => '201611050517493',
            'encParam' => 'test',
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢商戶訂單不存在
     */
    public function testTrackingReturnWithOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户订单不存在',
            180123
        );

        $encodeData = [
            'respCode' => '0018',
            'respDesc' => '商户订单不存在',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $encodeData = ['respCode' => '1001'];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少業務參數
     */
    public function testTrackingWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002403',
            'payOrderId' => 'EVU704211424358133567113198910',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
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

        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002403',
            'payOrderId' => 'EVU704211424358133567113198910',
            'order_state' => '1001',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單號錯誤
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002402',
            'payOrderId' => 'EVU704211424358133567113198910',
            'order_state' => '1003',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingReturnWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002403',
            'payOrderId' => 'EVU704211424358133567113198910',
            'order_state' => '1003',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'amount' => '0.01',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002403',
            'payOrderId' => 'EVU704211424358133567113198910',
            'order_state' => '1003',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'amount' => '0.1',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setContainer($this->container);
        $goldenPay->setClient($this->client);
        $goldenPay->setResponse($response);
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢時需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $goldenPay = new GoldenPay();
        $goldenPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '201704101019585',
            'orderId' => '201704210000002403',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $trackingData = $goldenPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/gateway/queryPaymentRecord', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals($sourceData['number'], $trackingData['form']['merId']);
        $this->assertEquals('1.0.9', $trackingData['form']['version']);
        $this->assertNotNull($trackingData['form']['sign']);
        $this->assertNotNull($trackingData['form']['encParam']);
    }

    /**
     * 測試驗證訂單查詢但返回結果為缺少回傳參數encParam
     */
    public function testPaymentTrackingVerifyWithoutEncParam()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'sign' => 'test',
            'merId' => '201704101019585',
            'version' => '1.0.9',
        ];

        $sourceData = ['content' => json_encode($result)];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為簽名驗證錯誤
     */
    public function testPaymentTrackingVerifyWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'sign' => 'test',
            'merId' => '201611050517493',
            'encParam' => 'test',
            'version' => '1.0.9',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為商戶訂單不存在
     */
    public function testPaymentTrackingVerifyWithOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户订单不存在',
            180123
        );

        $encodeData = [
            'respCode' => '0018',
            'respDesc' => '商户订单不存在',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為查詢失敗
     */
    public function testPaymentTrackingVerifyWithTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $encodeData = ['respCode' => '1001'];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為缺少業務參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002403',
            'payOrderId' => 'EVU704211424358133567113198910',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為支付失敗
     */
    public function testPaymentTrackingVerifyWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002403',
            'payOrderId' => 'EVU704211424358133567113198910',
            'order_state' => '1001',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為訂單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002402',
            'payOrderId' => 'EVU704211424358133567113198910',
            'order_state' => '1003',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'orderId' => '2016000012300001',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為金額錯誤
     */
    public function testPaymentTrackingVerifyWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002403',
            'payOrderId' => 'EVU704211424358133567113198910',
            'order_state' => '1003',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'orderId' => '201704210000002403',
            'amount' => '0.01',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201704210000002403',
            'payOrderId' => 'EVU704211424358133567113198910',
            'order_state' => '1003',
            'money' => '10',
            'payReturnTime' => '2017-04-21 14:25:02.797',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $encParam = $this->getEncParam($encodeData);
        $sign = $this->getSign($encParam);

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201704101019585',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'orderId' => '201704210000002403',
            'amount' => '0.1',
        ];

        $goldenPay = new GoldenPay();
        $goldenPay->setOptions($sourceData);
        $goldenPay->paymentTrackingVerify();
    }

    /**
     * 組成支付平台回傳的encParam
     *
     * @param array $encodeData
     * @return string
     */
    private function getEncParam($encodeData)
    {
        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        return $encParam;
    }

    /**
     * 組成支付平台回傳的sign
     *
     * @param string $encParam
     * @return string
     */
    private function getSign($encParam)
    {
        $sign = '';

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        return $sign;
    }
}
