<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JubaoPay;
use Buzz\Message\Response;

class JubaoPayTest extends DurianTestCase
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
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jubaoPay = new JubaoPay();
        $jubaoPay->getVerifyData();
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

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->getVerifyData();
    }

    /**
     * 測試支付時取得商家公鑰失敗
     */
    public function testPayGetMerchantPubilcKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/',
            'username' => 'testUser',
            'rsa_public_key' => '1234',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->getVerifyData();
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
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => '1234',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->getVerifyData();
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
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => $privateKey,
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->getVerifyData();
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
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $requestData = $jubaoPay->getVerifyData();

        $message = $requestData['message'];
        $rawKey = base64_decode(substr($message, 0, 172));
        openssl_private_decrypt($rawKey, $key, $this->privateKey);
        $rawIv = base64_decode(substr($message, 172, 172));
        openssl_private_decrypt($rawIv, $iv, $this->privateKey);
        $decrypted = base64_decode(substr($message, 172+172));
        $plainString = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted, MCRYPT_MODE_CBC, $iv));

        $items = explode('&', urldecode($plainString));
        for ($i = 0; $i < count($items)/2; $i++) {
            $field = $items[2*$i];
            $value = $items[2*$i+1];
            $encrypts[$field] = $value;
        }

        $this->assertEquals('ALL', $requestData['payMethod']);
        $this->assertEquals('testUser', $encrypts['goodsName']);
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

        $jubaoPay = new JubaoPay();
        $jubaoPay->verifyOrderPayment([]);
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

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家私鑰為空
     */
    public function testReturnWithGetMerchantPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
        }
        $encodeStr = implode('&', $encodeData);

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'rsa_private_key' => '',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得RSA私鑰失敗
     */
    public function testReturnGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
        }
        $encodeStr = implode('&', $encodeData);

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'rsa_private_key' => '1234'
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得Key隨機值失敗
     */
    public function testReturnGetKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
        }
        $encodeStr = implode('&', $encodeData);

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        // Create the keypair
        $res = openssl_pkey_new();

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);
        $privateKey = base64_encode($privkey);

        $options = [
            'trans_id' => '201611150000008467',
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'jubaopay' => 'jubaopay',
            'rsa_private_key' => $privateKey,
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳signature(加密簽名)
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
        }
        $encodeStr = implode('&', $encodeData);

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        $options = [
            'trans_id' => '201611150000008467',
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'jubaopay' => 'jubaopay',
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment([]);
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

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => $signature,
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => '1234',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment([]);
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

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
        }
        $encodeStr = implode('&', $encodeData);

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => '1234',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少驗證的參數
     */
    public function testReturnNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment([]);
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
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '0',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment([]);
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
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $entry = ['id' => '201608040000004406'];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment($entry);
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
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $entry = [
            'id' => '201611150000008467',
            'amount' => '100',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $options = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $entry = [
            'id' => '201611150000008467',
            'amount' => '0.01',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->verifyOrderPayment($entry);
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

        $jubaoPay = new JubaoPay();
        $jubaoPay->paymentTracking();
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

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得商家公鑰失敗
     */
    public function testTrackingGetMerchantPubilcKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_public_key' => 'test',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
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
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => '1234',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
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
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => $privateKey,
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
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
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少返回參數
     */
    public function testTrackingReturnNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.jubaopay.com',
        ];

        $result = 'error';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $jubaoPay = new JubaoPay();
        $jubaoPay->setContainer($this->container);
        $jubaoPay->setClient($this->client);
        $jubaoPay->setResponse($response);
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有signature的情況
     */
    public function testTrackingReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
        }
        $encodeStr = implode('&', $encodeData);

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        $result = ['message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.jubaopay.com',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setContainer($this->container);
        $jubaoPay->setClient($this->client);
        $jubaoPay->setResponse($response);
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果解密驗證錯誤
     */
    public function testTrackingReturnDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
        }
        $encodeStr = implode('&', $encodeData);

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        $result = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => '1234',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.jubaopay.com',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setContainer($this->container);
        $jubaoPay->setClient($this->client);
        $jubaoPay->setResponse($response);
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
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

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $result = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.jubaopay.com',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setContainer($this->container);
        $jubaoPay->setClient($this->client);
        $jubaoPay->setResponse($response);
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
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

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '3',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $result = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.jubaopay.com',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setContainer($this->container);
        $jubaoPay->setClient($this->client);
        $jubaoPay->setResponse($response);
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
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

        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $result = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'amount' => '100',
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.jubaopay.com',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setContainer($this->container);
        $jubaoPay->setClient($this->client);
        $jubaoPay->setResponse($response);
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $data = [
            'amount' => '0.01',
            'createTime' => '2016-11-15 17:53:37',
            'partnerid' => '14061642390911131749',
            'orderNo' => '16111553321703921186',
            'state' => '2',
            'payid' => '201611150000008467',
            'modifyTime' => '2016-11-15 17:53:58',
            'payerName' => 'hikaru',
            'mobile' => '13867105942',
        ];

        $encodeData = [];
        $signStr = '';

        // 加密設定
        foreach ($data as $key => $value) {
            $encodeData[] = urlencode($key) . '&' . urlencode($value);
            $signStr .= $value;
        }
        $encodeStr = implode('&', $encodeData);
        $signStr .= 'test';

        $key = 'testtesttesttest';
        $iv = '1234123412341234';
        $keyResult = '';
        $ivResult = '';
        $signature = '';

        openssl_public_encrypt($key, $keyResult, $this->publicKey);
        openssl_public_encrypt($iv, $ivResult, $this->publicKey);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_sign($signStr, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

        $result = [
            'message' => base64_encode($keyResult) . base64_encode($ivResult) . $encrypted,
            'signature' => base64_encode($signature),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'amount' => '0.01',
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.jubaopay.com',
        ];

        $jubaoPay = new JubaoPay();
        $jubaoPay->setContainer($this->container);
        $jubaoPay->setClient($this->client);
        $jubaoPay->setResponse($response);
        $jubaoPay->setPrivateKey('test');
        $jubaoPay->setOptions($options);
        $jubaoPay->paymentTracking();
    }
}
