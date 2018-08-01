<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YinBangPay;
use Buzz\Message\Response;

class YinBangPayTest extends DurianTestCase
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

        $yinBangPay = new YinBangPay();
        $yinBangPay->getVerifyData();
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
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '9999',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->getVerifyData();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => [],
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->getVerifyData();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => '',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->getVerifyData();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => 'test',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->getVerifyData([]);
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => '',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->getVerifyData();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => '123456',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->getVerifyData();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => base64_encode($privkey),
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $requestData = $yinBangPay->getVerifyData();

        $this->assertEquals('1.0.9', $requestData['version']);
        $this->assertEquals($options['number'], $requestData['merId']);
        $this->assertNotNull($requestData['sign']);
        $this->assertNotNull($requestData['encParam']);
    }

    /**
     * 測試快捷支付
     */
    public function testPayWithH5()
    {
        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1100',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $requestData = $yinBangPay->getVerifyData();

        $this->assertEquals('1.0.9', $requestData['version']);
        $this->assertEquals($options['number'], $requestData['merId']);
        $this->assertNotNull($requestData['sign']);
        $this->assertNotNull($requestData['encParam']);
    }

    /**
     * 測試支付寶手機支付
     */
    public function testPayWithAliPay()
    {
        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'username' => 'php1test',
            'orderCreateDate' => '2016-11-24 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://pay.test.com/return.php',
            'paymentVendorId' => '1098',
            'merchant_extra' => ['terId' => '123456'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $requestData = $yinBangPay->getVerifyData();

        $this->assertEquals('1.0.9', $requestData['version']);
        $this->assertEquals($options['number'], $requestData['merId']);
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

        $yinBangPay = new YinBangPay();
        $yinBangPay->verifyOrderPayment([]);
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

        $options = [
            'sign' => '123456',
            'merId' => '201611050517493',
            'encParam' => 'test',
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => '',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment([]);
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

        $options = [
            'sign' => '123456',
            'merId' => '201611050517493',
            'encParam' => 'test',
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => 'test',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment([]);
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
            'sign' => '123456',
            'merId' => '201611050517493',
            'encParam' => 'test',
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment([]);
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20161124164044',
            'payOrderId' => '201611241640254605489882341119',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '1',
            'payType' => '1005',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $options = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => '',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment([]);
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20161124164044',
            'payOrderId' => '201611241640254605489882341119',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '1',
            'payType' => '1005',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $options = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => 'test',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment([]);
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20161124164044',
            'payOrderId' => '201611241640254605489882341119',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '1',
            'payType' => '1005',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $options = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment([]);
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20161124164044',
            'payOrderId' => '201611241640254605489882341119',
            'selfParam' => '',
            'order_state' => '1001',
            'notifyType' => '1001',
            'money' => '1',
            'payType' => '1005',
            'orderId' => '201611240000005053',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $options = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment([]);
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20161124164044',
            'payOrderId' => '201611241640254605489882341119',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '1',
            'payType' => '1005',
            'orderId' => '201611240000005053',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $options = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = ['id' => '201611240000005052'];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment($entry);
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20161124164044',
            'payOrderId' => '201611241640254605489882341119',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '1',
            'payType' => '1005',
            'orderId' => '201611240000005053',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $options = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = [
            'id' => '201611240000005053',
            'amount' => '1',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'payTypeDesc' => '微信支付',
            'payReturnTime' => '20161124164044',
            'payOrderId' => '201611241640254605489882341119',
            'selfParam' => '',
            'order_state' => '1003',
            'notifyType' => '1001',
            'money' => '1',
            'payType' => '1005',
            'orderId' => '201611240000005053',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $options = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'orderId' => '201611240000005053',
            'version' => '1.0.9',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = [
            'id' => '201611240000005053',
            'amount' => '0.01',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yinBangPay->getMsg());
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $yinBangPay = new YinBangPay();
        $yinBangPay->paymentTracking();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => '',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => 'test',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => '',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => 'test',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => base64_encode($privkey),
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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
            'merId' => '201611050517493',
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'respCode' => '0018',
            'respDesc' => '商户订单不存在',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = ['respCode' => '1001'];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201611240000005053',
            'payOrderId' => '201611241640254605489882341119',
            'money' => '1',
            'payReturnTime' => '2016-11-24 16:40:45.8',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201611240000005053',
            'payOrderId' => '201611241640254605489882341119',
            'order_state' => '1001',
            'money' => '1',
            'payReturnTime' => '2016-11-24 16:40:45.8',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201611240000005052',
            'payOrderId' => '201611241640254605489882341119',
            'order_state' => '1003',
            'money' => '1',
            'payReturnTime' => '2016-11-24 16:40:45.8',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'amount' => '0.01',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
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

        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201611240000005053',
            'payOrderId' => '201611241640254605489882341119',
            'order_state' => '1003',
            'money' => '10',
            'payReturnTime' => '2016-11-24 16:40:45.8',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'amount' => '0.01',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $content = base64_decode($this->publicKey);

        $publicKey = openssl_pkey_get_public($content);

        $sign = '';
        $encodeData = [
            'respCode' => '1000',
            'respDesc' => '查询成功',
            'orderId' => '201611240000005053',
            'payOrderId' => '201611241640254605489882341119',
            'order_state' => '1003',
            'money' => '1',
            'payReturnTime' => '2016-11-24 16:40:45.8',
            'selfParam' => '',
            'payType' => '1005',
            'payTypeDesc' => '微信支付',
        ];

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        openssl_sign($encParam, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'merId' => '201611050517493',
            'encParam' => base64_encode($encParam),
            'version' => '1.0.9',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'number' => '201611050517493',
            'orderId' => '201611240000005053',
            'amount' => '0.01',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $yinBangPay = new YinBangPay();
        $yinBangPay->setContainer($this->container);
        $yinBangPay->setClient($this->client);
        $yinBangPay->setResponse($response);
        $yinBangPay->setOptions($options);
        $yinBangPay->paymentTracking();
    }
}
