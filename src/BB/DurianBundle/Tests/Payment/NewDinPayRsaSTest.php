<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewDinPayRsaS;
use Buzz\Message\Response;

class NewDinPayRsaSTest extends DurianTestCase
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

        // Create the keypair
        $res = openssl_pkey_new();

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
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newDinPayRsaS = new NewDinPayRsaS();

        $sourceData = ['number' => ''];

        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->getVerifyData();
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
            'number' => '2000600129',
            'notify_url' => 'http://keith-app-test.3eeweb.com/web/return.php?pay_system=3082',
            'orderId' => '201604270000002345',
            'orderCreateDate' => '2016-04-27 15:10:02',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '9999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://pay.dinpay.com/gateway?input_charset=UTF-8',
            'merchantId' => '3082',
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->getVerifyData();
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
            'number' => '2000600129',
            'notify_url' => 'http://keith-app-test.3eeweb.com/web/return.php?pay_system=3082',
            'orderId' => '201604270000002345',
            'orderCreateDate' => '2016-04-27 15:10:02',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://pay.dinpay.com/gateway?input_charset=UTF-8',
            'merchantId' => '3082',
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->getVerifyData();
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

        $newDinPayRsaS = new NewDinPayRsaS();

        $options = [
            'number' => '2000600129',
            'notify_url' => 'http://keith-app-test.3eeweb.com/web/return.php?pay_system=3082',
            'orderId' => '201604270000002345',
            'orderCreateDate' => '2016-04-27 15:10:02',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => '1234',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://pay.dinpay.com/gateway?input_charset=UTF-8',
            'merchantId' => '3082',
        ];

        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->getVerifyData();
    }

    /**
     * 測試加密產生簽名失敗
     */
    public function testGetEncodeGenerateSignatureFailure()
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

        $newDinPayRsaS = new NewDinPayRsaS();

        $options = [
            'number' => '2000600129',
            'notify_url' => 'http://keith-app-test.3eeweb.com/web/return.php?pay_system=3082',
            'orderId' => '201604270000002345',
            'orderCreateDate' => '2016-04-27 15:10:02',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://pay.dinpay.com/gateway?input_charset=UTF-8',
            'merchantId' => '3082',
        ];

        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '2000600129',
            'notify_url' => 'http://keith-app-test.3eeweb.com/web/return.php',
            'orderId' => '201604270000002345',
            'orderCreateDate' => '2016-04-27 15:10:02',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://pay.dinpay.com/gateway?input_charset=UTF-8',
            'merchantId' => '3082',
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setOptions($options);
        $requestData = $newDinPayRsaS->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s',
            $options['notify_url'],
            $options['merchantId']
        );

        $this->assertEquals($options['number'], $requestData['merchant_code']);
        $this->assertEquals('direct_pay', $requestData['service_type']);
        $this->assertEquals('V3.0', $requestData['interface_version']);
        $this->assertEquals('UTF-8', $requestData['input_charset']);
        $this->assertEquals($notifyUrl, $requestData['notify_url']);
        $this->assertEquals('RSA-S', $requestData['sign_type']);
        $this->assertEquals($options['orderId'], $requestData['order_no']);
        $this->assertEquals($options['orderCreateDate'], $requestData['order_time']);
        $this->assertEquals($options['amount'], $requestData['order_amount']);
        $this->assertEquals($options['username'], $requestData['product_name']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
    }

    /**
     * 測試APP支付
     */
    public function testAppPay()
    {
        $options = [
            'number' => '2000600129',
            'notify_url' => 'http://keith-app-test.3eeweb.com/web/return.php',
            'orderId' => '201604270000002345',
            'orderCreateDate' => '2016-04-27 15:10:02',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1094',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => '1.2.3.4',
            'merchantId' => '3082',
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setOptions($options);
        $requestData = $newDinPayRsaS->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s',
            $options['notify_url'],
            $options['merchantId']
        );

        $this->assertEquals($options['number'], $requestData['merchant_code']);
        $this->assertEquals('V3.0', $requestData['interface_version']);
        $this->assertEquals($notifyUrl, $requestData['notify_url']);
        $this->assertEquals('RSA-S', $requestData['sign_type']);
        $this->assertEquals($options['orderId'], $requestData['order_no']);
        $this->assertEquals($options['orderCreateDate'], $requestData['order_time']);
        $this->assertEquals($options['amount'], $requestData['order_amount']);
        $this->assertEquals($options['username'], $requestData['product_name']);
    }

    /**
     * 測試解密驗證支付平台缺少回傳參數(merchant_code)
     */
    public function testVerifyWithoutMerchantCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newDinPayRsaS = new NewDinPayRsaS();

        $options = [
            'pay_system' => '3082',
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'order_no' => '201604270000002345',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02'
        ];

        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newDinPayRsaS = new NewDinPayRsaS();

        $options = [
            'pay_system' => '3082',
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201604270000002345',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02'
        ];

        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->verifyOrderPayment([]);
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

        $newDinPayRsaS = new NewDinPayRsaS();

        $options = [
            'pay_system' => '3082',
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201604270000002345',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02',
            'sign' => 'test',
            'rsa_public_key' => $this->publicKey,
        ];

        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->verifyOrderPayment([]);
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
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201604270000002345',
            'trade_status' => 'UNPAY',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02',
            'sign' => 'test',
            'rsa_public_key' => '',
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->verifyOrderPayment([]);
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
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201604270000002345',
            'trade_status' => 'UNPAY',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02',
            'sign' => 'test',
            'rsa_public_key' => '123',
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->verifyOrderPayment([]);
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

        $newDinPayRsaS = new NewDinPayRsaS();

        $encodeStr = 'bank_seq_no=HFG000007999858313&interface_version=V3.0&merchant_code=2000600129' .
            '&notify_id=aa1f5f6df8d24fafaa6c14faee93cd11&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201604270000002345&order_time=2016-04-27+15%3A09%3A53&trade_no=1136435210' .
            '&trade_status=UNPAY&trade_time=2016-04-27+15%3A10%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $options = [
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201604270000002345',
            'trade_status' => 'UNPAY',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $newDinPayRsaS = new NewDinPayRsaS();

        $encodeStr = 'bank_seq_no=HFG000007999858313&interface_version=V3.0&merchant_code=2000600129' .
            '&notify_id=aa1f5f6df8d24fafaa6c14faee93cd11&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201604270000002345&order_time=2016-04-27+15%3A09%3A53&trade_no=1136435210' .
            '&trade_status=SUCCESS&trade_time=2016-04-27+15%3A10%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $options = [
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201604270000002345',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = ['id' => '201604270000001234'];

        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $newDinPayRsaS = new NewDinPayRsaS();

        $encodeStr = 'bank_seq_no=HFG000007999858313&interface_version=V3.0&merchant_code=2000600129' .
            '&notify_id=aa1f5f6df8d24fafaa6c14faee93cd11&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201604270000002345&order_time=2016-04-27+15%3A09%3A53&trade_no=1136435210' .
            '&trade_status=SUCCESS&trade_time=2016-04-27+15%3A10%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $options = [
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201604270000002345',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201604270000002345',
            'amount' => '123'
        ];

        $newDinPayRsaS->setOptions($options);
        $newDinPayRsaS->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccessBySynchronous()
    {
        $newDinPayRsaS = new NewDinPayRsaS();

        $encodeStr = 'bank_seq_no=HFG000007999858313&interface_version=V3.0&merchant_code=2000600129' .
            '&notify_id=aa1f5f6df8d24fafaa6c14faee93cd11&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201604270000002345&order_time=2016-04-27+15%3A09%3A53&trade_no=1136435210' .
            '&trade_status=SUCCESS&trade_time=2016-04-27+15%3A10%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201604270000002345',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2016-04-27 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2016-04-27 15:10:02',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201604270000002345',
            'amount' => '0.0100'
        ];

        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $newDinPayRsaS->getMsg());
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

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->paymentTracking();
    }

    /**
     * 測試訂單查詢產生簽名失敗
     */
    public function testPaymentTrackingGenerateSignatureFailure()
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

        $newDinPayRsaS = new NewDinPayRsaS();

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
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
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數is_success
     */
    public function testPaymentTrackingResultWithoutIsSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayrsas.com'
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setContainer($this->container);
        $newDinPayRsaS->setClient($this->client);
        $newDinPayRsaS->setResponse($response);
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
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

        $result = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>F</is_success>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayrsas.com'
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setContainer($this->container);
        $newDinPayRsaS->setClient($this->client);
        $newDinPayRsaS->setResponse($response);
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數trade_status
     */
    public function testPaymentTrackingResultWithoutTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayrsas.com'
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setContainer($this->container);
        $newDinPayRsaS->setClient($this->client);
        $newDinPayRsaS->setResponse($response);
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數sign
     */
    public function testTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><trade_status>UNPAY</trade_status></trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayrsas.com'
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setContainer($this->container);
        $newDinPayRsaS->setClient($this->client);
        $newDinPayRsaS->setResponse($response);
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
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

        $result = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign></sign>' .
            '<trade><trade_status>UNPAY</trade_status></trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayrsas.com'
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setContainer($this->container);
        $newDinPayRsaS->setClient($this->client);
        $newDinPayRsaS->setResponse($response);
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
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

        $encodeStr = 'merchant_code=2000600129&order_amount=0.01&order_no=201604270000002345' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201604270000002345</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayrsas.com'
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setContainer($this->container);
        $newDinPayRsaS->setClient($this->client);
        $newDinPayRsaS->setResponse($response);
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
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

        $encodeStr = 'merchant_code=2000600129&order_amount=0.01&order_no=201604270000002345' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201604270000002345</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayrsas.com'
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setContainer($this->container);
        $newDinPayRsaS->setClient($this->client);
        $newDinPayRsaS->setResponse($response);
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeStr = 'merchant_code=2000600129&order_amount=0.01&order_no=201604270000002345' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201604270000002345</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayrsas.com',
            'amount' => '0.01'
        ];

        $newDinPayRsaS = new NewDinPayRsaS();
        $newDinPayRsaS->setContainer($this->container);
        $newDinPayRsaS->setClient($this->client);
        $newDinPayRsaS->setResponse($response);
        $newDinPayRsaS->setOptions($sourceData);
        $newDinPayRsaS->paymentTracking();
    }
}
