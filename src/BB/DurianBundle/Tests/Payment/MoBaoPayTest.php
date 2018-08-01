<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MoBaoPay;
use Buzz\Message\Response;

class MoBaoPayTest extends DurianTestCase
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
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->getVerifyData();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '99',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getVerifyData();
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

        $options = [
            'merchantId' => '47867',
            'domain' => '6',
            'number' => '20130809',
            'orderId' => '2014072414125',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '17',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => '',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰為空字串
     */
    public function testPayGetMerchantKeyFileContentNull()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $options = [
            'merchantId' => '47867',
            'domain' => '6',
            'number' => '20130809',
            'orderId' => '2014072414125',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '17',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => '',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰失敗
     */
    public function testPayGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'merchantId' => '47867',
            'domain' => '6',
            'number' => '20130809',
            'orderId' => '2014072414125',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '17',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => 'acctest',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getVerifyData();
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

        $cert = '';
        $pkcsPrivate = '';
        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DSA,
        ];

        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '20130809',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://www.mobao.cn/return.php',
            'paymentVendorId' => '1',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $requestData = $moBaoPay->getVerifyData();

        $this->assertEquals($options['number'], $requestData['merchNo']);
        $this->assertEquals($options['orderId'], $requestData['orderNo']);
        $this->assertEquals('20150322', $requestData['tradeDate']);
        $this->assertRegExp('/^100.00$/', $requestData['amt']);
        $this->assertEquals($options['notify_url'], $requestData['merchUrl']);
        $this->assertEquals('35660_6', $requestData['merchParam']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
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

        $moBaoPay = new MoBaoPay();
        $moBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得RSA公鑰為空
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'signMsg' => 'acctest',
            'rsa_public_key' => '',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'signMsg' => 'acctest',
            'rsa_public_key' => '123',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment([]);
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

        $cert = '';
        $newKey = openssl_pkey_new();
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $dropEnd = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $dropLine = str_replace("\n", '', $dropEnd);
        $pkcsPublic = wordwrap($dropLine, 64, "\n");

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'signMsg' => 'acctest',
            'rsa_public_key' => $pkcsPublic,
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment([]);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '0',
            'rsa_public_key' => $pkcsPublic,
        ];

        // 組加密簽名
        $signParams = [
            'apiName',
            'notifyTime',
            'tradeAmt',
            'merchNo',
            'merchParam',
            'orderNo',
            'tradeDate',
            'accNo',
            'accDate',
            'orderStatus',
        ];

        $signData = [];
        foreach ($signParams as $index) {
            if (isset($options[$index])) {
                $signData[$index] = $options[$index];
            }
        }

        $signStr = urldecode(http_build_query($signData));
        $sign = '';
        openssl_sign($signStr, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $options['signMsg'] = base64_encode($sign);

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment([]);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'rsa_public_key' => $pkcsPublic,
        ];

        $signParams = [
            'apiName',
            'notifyTime',
            'tradeAmt',
            'merchNo',
            'merchParam',
            'orderNo',
            'tradeDate',
            'accNo',
            'accDate',
            'orderStatus',
        ];

        $signData = [];
        foreach ($signParams as $index) {
            if (isset($options[$index])) {
                $signData[$index] = $options[$index];
            }
        }

        $signStr = urldecode(http_build_query($signData));
        $sign = '';
        openssl_sign($signStr, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $options['signMsg'] = base64_encode($sign);

        $entry = ['id' => '201503220000000321'];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment($entry);
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
        $pkcsPublic = str_replace("\n", '', $dropEnd);

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'rsa_public_key' => wordwrap($pkcsPublic, 64, "\n"),
        ];

        $signParams = [
            'apiName',
            'notifyTime',
            'tradeAmt',
            'merchNo',
            'merchParam',
            'orderNo',
            'tradeDate',
            'accNo',
            'accDate',
            'orderStatus',
        ];

        $signData = [];
        foreach ($signParams as $index) {
            if (isset($options[$index])) {
                $signData[$index] = $options[$index];
            }
        }

        $signStr = urldecode(http_build_query($signData));
        $sign = '';
        openssl_sign($signStr, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $options['signMsg'] = base64_encode($sign);

        $entry = [
            'id' => '201503220000000123',
            'amount' => '10.00',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
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
        $pkcsPublic = str_replace("\n", '', $dropEnd);

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'rsa_public_key' => wordwrap($pkcsPublic, 64, "\n"),
        ];

        $signParams = [
            'apiName',
            'notifyTime',
            'tradeAmt',
            'merchNo',
            'merchParam',
            'orderNo',
            'tradeDate',
            'accNo',
            'accDate',
            'orderStatus',
        ];

        $signData = [];
        foreach ($signParams as $index) {
            if (isset($options[$index])) {
                $signData[$index] = $options[$index];
            }
        }

        $signStr = urldecode(http_build_query($signData));
        $sign = '';
        openssl_sign($signStr, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $options['signMsg'] = base64_encode($sign);

        $entry = [
            'id' => '201503220000000123',
            'amount' => '100.00',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $moBaoPay->getMsg());
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

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢缺少商家額外的參數設定platformID
     */
    public function testTrackingWithoutMerchantExtraPlatformID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => []
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => '',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => '',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => 'acctest',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢時生成加密簽名錯誤
     */

    public function testTrackingSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $cert = '';
        $pkcsPrivate = '';
        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DSA,
        ];

        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有respData的情況
     */
    public function testTrackingReturnWithoutRespData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<moboAccount></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有signMsg的情況
     */
    public function testTrackingReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<moboAccount><respData></respData></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果驗證沒有respCode的情況
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<moboAccount>'.
            '<respData></respData>'.
            '<signMsg>00000000</signMsg>'.
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單不存在
     */
    public function testTrackingReturnPaymentTrackingOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<moboAccount>'.
            '<respData>'.
            '<respCode>22</respCode>'.
            '<respDesc>查询订单信息不存在[订单信息不存在]</respDesc>'.
            '</respData>'.
            '<signMsg>00000000</signMsg>'.
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;content-type:charset=utf-8');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<moboAccount>'.
            '<respData>'.
            '<respCode>22</respCode>'.
            '<respDesc>交易成功</respDesc>'.
            '<orderDate>20150323</orderDate>'.
            '<accDate>20150323</accDate>'.
            '<orderNo>744214</orderNo>'.
            '<accNo>744214</accNo>'.
            '<Status>1</Status>'.
            '</respData>'.
            '<signMsg>00000000</signMsg>'.
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果驗證取得RSA公鑰為空
     */
    public function testTrackingReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => '',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<moboAccount>'.
            '<respData>'.
            '<respCode>00</respCode>'.
            '<respDesc>交易成功</respDesc>'.
            '<orderDate>20150323</orderDate>'.
            '<accDate>20150323</accDate>'.
            '<orderNo>744214</orderNo>'.
            '<accNo>744214</accNo>'.
            '<Status>1</Status>'.
            '</respData>'.
            '<signMsg>00000000</signMsg>'.
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => $pkcsPublic,
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<moboAccount>'.
            '<respData>'.
            '<respCode>00</respCode>'.
            '<respDesc>交易成功</respDesc>'.
            '<orderDate>20150323</orderDate>'.
            '<accDate>20150323</accDate>'.
            '<orderNo>744214</orderNo>'.
            '<accNo>744214</accNo>'.
            '<Status>1</Status>'.
            '</respData>'.
            '<signMsg>00000000</signMsg>'.
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態為未支付
     */
    public function testTrackingReturnOrderPaymentUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

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

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => $pkcsPublic,
        ];

        $responseData = '<respData>'.
            '<respCode>00</respCode>'.
            '<respDesc>无记录</respDesc>'.
            '<orderDate>20150323</orderDate>'.
            '<accDate>20150323</accDate>'.
            '<orderNo>744214</orderNo>'.
            '<accNo>744214</accNo>'.
            '<Status>0</Status>'.
            '</respData>';

        $sign = '';
        openssl_sign($responseData, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $options['signMsg'] = base64_encode($sign);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>'.
            '<moboAccount>'.
            $responseData.
            '<signMsg>'.
            $options['signMsg'].
            '</signMsg>'.
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態不為1則代表支付失敗
     */
    public function testTrackingReturnOrderPaymentfailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

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

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => $pkcsPublic,
        ];

        $responseData = '<respData>'.
            '<respCode>00</respCode>'.
            '<respDesc>交易成功</respDesc>'.
            '<orderDate>20150323</orderDate>'.
            '<accDate>20150323</accDate>'.
            '<orderNo>744214</orderNo>'.
            '<accNo>744214</accNo>'.
            '<Status>2</Status>'.
            '</respData>';

        $sign = '';
        openssl_sign($responseData, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $options['signMsg'] = base64_encode($sign);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>'.
            '<moboAccount>'.
            $responseData.
            '<signMsg>'.
            $options['signMsg'].
            '</signMsg>'.
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
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

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => $pkcsPublic,
            'merchantId' => '1',
            'domain' => '6',
        ];

        $responseData = '<respData>'.
            '<respCode>00</respCode>'.
            '<respDesc>交易成功</respDesc>'.
            '<orderDate>20150323</orderDate>'.
            '<accDate>20150323</accDate>'.
            '<orderNo>744214</orderNo>'.
            '<accNo>744214</accNo>'.
            '<Status>1</Status>'.
            '</respData>';

        $sign = '';
        openssl_sign($responseData, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $options['signMsg'] = base64_encode($sign);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>'.
            '<moboAccount>'.
            $responseData.
            '<signMsg>'.
            $options['signMsg'].
            '</signMsg>'.
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $moBaoPay = new MoBaoPay();
        $moBaoPay->getPaymentTrackingData();
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

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少商家額外的參數設定platformID
     */
    public function testGetPaymentTrackingDataWithoutMerchantExtraPlatformID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => []
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時取得商家私鑰為空字串
     */
    public function testGetPaymentTrackingDataGetMerchantKeyFileContentNull()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => '',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時取得商家私鑰失敗
     */
    public function testGetPaymentTrackingDataGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => 'acctest',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時生成加密簽名錯誤
     */
    public function testGetPaymentTrackingDataSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $cert = '';
        $pkcsPrivate = '';
        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DSA,
        ];

        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => '722216'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getPaymentTrackingData();
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

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => ['platformID' => 'mrof'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.trade.mobaopay.com',
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $trackingData = $moBaoPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/cgi-bin/netpayment/pay_gate.cgi', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.trade.mobaopay.com', $trackingData['headers']['Host']);

        $this->assertEquals('MOBO_TRAN_QUERY', $trackingData['form']['apiName']);
        $this->assertEquals('mrof', $trackingData['form']['platformID']);
        $this->assertEquals('20130809', $trackingData['form']['merchNo']);
        $this->assertEquals('201503160000002219', $trackingData['form']['orderNo']);
        $this->assertEquals('20150316', $trackingData['form']['tradeDate']);
        $this->assertEquals('100.00', $trackingData['form']['amt']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $moBaoPay = new MoBaoPay();
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢返回沒有respData的情況
     */
    public function testPaymentTrackingVerifyWithoutRespData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount></moboAccount>';
        $sourceData = ['content' => $content];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢返回沒有signMsg的情況
     */
    public function testPaymentTrackingVerifyWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount><respData></respData></moboAccount>';
        $sourceData = ['content' => $content];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢沒有respCode的情況
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData></respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';
        $sourceData = ['content' => $content];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單不存在
     */
    public function testPaymentTrackingVerifyButOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>22</respCode>' .
            '<respDesc>查询订单信息不存在[订单信息不存在]</respDesc>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';
        $sourceData = ['content' => $content];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢失敗
     */
    public function testPaymentTrackingVerifyPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>22</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';
        $sourceData = ['content' => $content];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢取得RSA公鑰為空
     */
    public function testPaymentTrackingVerifyGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';
        $sourceData = [
            'content' => $content,
            'rsa_public_key' => ''
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢解密驗證錯誤
     */
    public function testPaymentTrackingVerifyDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

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

        $content = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';
        $sourceData = [
            'content' => $content,
            'rsa_public_key' => $pkcsPublic
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單狀態為未支付
     */
    public function testPaymentTrackingVerifyOrderPaymentUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

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

        $responseData = '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>无记录</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>0</Status>' .
            '</respData>';

        $sign = '';
        openssl_sign($responseData, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $signMsg = base64_encode($sign);

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            $responseData .
            '<signMsg>' .
            $signMsg .
            '</signMsg>' .
            '</moboAccount>';
        $sourceData = [
            'content' => $content,
            'rsa_public_key' => $pkcsPublic
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單狀態不為1則代表支付失敗
     */
    public function testPaymentTrackingVerifyOrderPaymentfailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

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

        $responseData = '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>2</Status>' .
            '</respData>';

        $sign = '';
        openssl_sign($responseData, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $signMsg = base64_encode($sign);

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            $responseData.
            '<signMsg>' .
            $signMsg.
            '</signMsg>' .
            '</moboAccount>';
        $sourceData = [
            'content' => $content,
            'rsa_public_key' => $pkcsPublic
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
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

        $responseData = '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>1</Status>' .
            '</respData>';

        $sign = '';
        openssl_sign($responseData, $sign, $privatekey, OPENSSL_ALGO_MD5);
        $signMsg = base64_encode($sign);

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            $responseData .
            '<signMsg>' .
            $signMsg.
            '</signMsg>' .
            '</moboAccount>';
        $sourceData = [
            'content' => $content,
            'rsa_public_key' => $pkcsPublic
        ];

        $moBaoPay = new MoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($sourceData);
        $moBaoPay->paymentTrackingVerify();
    }
}
