<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Smartpay;
use Buzz\Message\Response;

class SmartpayTest extends DurianTestCase
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
     * 測試加密時沒有帶入merchantId的情況
     */
    public function testEncodeWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $smartpay = new Smartpay();

        $sourceData = ['merchantId' => ''];

        $smartpay->setOptions($sourceData);
        $smartpay->getVerifyData();
    }

    /**
     * 測試加密時未指定支付參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $smartpay = new Smartpay();

        $sourceData = [
            'merchantId' => '35660',
            'number' => '',
            'rsa_private_key' => $this->privateKey
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->getVerifyData();
    }

    /**
     * 測試加密時取得商家私鑰為空字串
     */
    public function testEncodeWithoutFileContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $smartpay = new Smartpay();

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderCreateDate' => '2014-07-02 09:32:40',
            'orderId' => '150111',
            'amount' => '10000',
            'notify_url' => 'http://192.168.0.245/',
            'ip' => '127.0.0.1',
            'rsa_private_key' => '',
            'domain' => '6',
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->getVerifyData();
    }

    /**
     * 測試加密時取得商家私鑰失敗
     */
    public function testEncodeGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $smartpay = new Smartpay();

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderCreateDate' => '2014-07-02 09:32:40',
            'orderId' => '150111',
            'amount' => '10000',
            'notify_url' => 'http://192.168.0.245/',
            'ip' => '127.0.0.1',
            'rsa_private_key' => 'acctest',
            'domain' => '6',
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->getVerifyData();
    }

    /**
     * 測試加密時生成加密簽名錯誤
     */
    public function testEncodeGenerateSignatureFailure()
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

        $smartpay = new Smartpay();

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderCreateDate' => '2014-07-02 09:32:40',
            'orderId' => '150111',
            'amount' => '10000',
            'notify_url' => 'http://192.168.0.245/',
            'ip' => '127.0.0.1',
            'rsa_private_key' => base64_encode($privkey),
            'domain' => '6',
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderCreateDate' => '2014-07-02 09:32:40',
            'orderId' => '150111',
            'amount' => '10000',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'ip' => '127.0.0.1',
            'rsa_private_key' => base64_encode($this->privateKey),
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $encodeData = $smartpay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $encodeStr = 'cmd=_webpay&mobile=null&merchantId=%s&tx_date=20140702&'.
            'tx_time=093240&tx_no=%s&tx_params=null&payment_mode=null&'.
            'debit_list=null&debit_periodic_unit=null&debit_interval=null&'.
            'first_debit_date=null&close_date=null&amount=%s&price=null&'.
            'discount=null&item_name=null&item_no=%s&item_quantity=null&'.
            'image_url=null&notice_method=1&return_url=null&cancel_return_url=null&'.
            'notice_url=%s&no_shipping=null&valid_time=null&client_ip=%s';

        $encodeStr = sprintf(
            $encodeStr,
            $sourceData['number'],
            $sourceData['orderId'],
            round($sourceData['amount'] * 100, 0),
            $sourceData['orderId'],
            $notifyUrl,
            $sourceData['ip']
        );

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = $smartpay->hexToStr($sign);

        $this->assertEquals($sourceData['number'], $encodeData['merchantId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['tx_no']);
        $this->assertSame(round($sourceData['amount'] * 100, 0), $encodeData['amount']);
        $this->assertEquals('20140702', $encodeData['tx_date']);
        $this->assertEquals('093240', $encodeData['tx_time']);
        $this->assertEquals('', $encodeData['return_url']);
        $this->assertEquals($notifyUrl, $encodeData['notice_url']);
        $this->assertEquals($sourceData['ip'], $encodeData['client_ip']);
        $this->assertEquals($sourceData['orderId'], $encodeData['item_no']);
        $this->assertEquals($stringSign, $encodeData['sign']);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testDecodeWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $smartpay = new Smartpay();

        $encodeStr = 'cmd=_webpay&merchantId=20124252&mobile=15921439746&amount=10000&tx_date=20101207&tx_no=150111&'.
            'tx_params=null&temp=1&settlement_date=20101207&status=02&desc=null';

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = $smartpay->hexToStr($sign);

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '01',
            'desc' => '',
            'sign' => $stringSign,
            'rsa_public_key' => $this->publicKey
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳sign(加密簽名)
     */
    public function testDecodeWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $smartpay = new Smartpay();

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'amount' => '10000',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '01',
            'desc' => '',
            'rsa_public_key' => $this->publicKey
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment([]);
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

        $smartpay = new Smartpay();

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'amount' => '10000',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '01',
            'desc' => '',
            'sign' => 'x',
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment([]);
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

        $smartpay = new Smartpay();

        $encodeStr = 'cmd=_webpay&merchantId=20124252&mobile=15921439746&amount=10000&tx_date=20101207&tx_no=150111&'.
            'tx_params=null&temp=1&settlement_date=20101207&status=02&desc=null';

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = $smartpay->hexToStr($sign);

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'amount' => '10000',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '02',
            'desc' => '',
            'sign' => $stringSign,
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $smartpay = new Smartpay();

        $encodeStr = 'cmd=_webpay&merchantId=20124252&mobile=15921439746&amount=10000&tx_date=20101207&tx_no=150111&'.
            'tx_params=null&temp=1&settlement_date=20101207&status=01&desc=null';

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = $smartpay->hexToStr($sign);

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'amount' => '10000',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '01',
            'desc' => '',
            'sign' => $stringSign,
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $entry = ['id' => '19990720'];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $smartpay = new Smartpay();

        $encodeStr = 'cmd=_webpay&merchantId=20124252&mobile=15921439746&amount=10000&tx_date=20101207&tx_no=150111&'.
            'tx_params=null&temp=1&settlement_date=20101207&status=01&desc=null';

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = $smartpay->hexToStr($sign);

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'amount' => '10000',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '01',
            'desc' => '',
            'sign' => $stringSign,
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $entry = [
            'id' => '150111',
            'amount' => '9900.0000'
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證時取得商家公鑰
     */
    public function testPayWithoutContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $smartpay = new Smartpay();

        $encodeStr = 'cmd=_webpay&merchantId=20124252&mobile=15921439746&amount=10000&tx_date=20101207&tx_no=150111&'.
            'tx_params=null&temp=1&settlement_date=20101207&status=01&desc=null';

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = $smartpay->hexToStr($sign);

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'amount' => '10000',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '01',
            'desc' => '',
            'sign' => $stringSign,
            'rsa_public_key' => ''
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment([]);
    }

    /**
     * 測試支付驗證沒有公鑰的情況
     */
    public function testPayGetPublicKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $smartpay = new Smartpay();

        $encodeStr = 'cmd=_webpay&merchantId=20124252&mobile=15921439746&amount=10000&tx_date=20101207&tx_no=150111&'.
            'tx_params=null&temp=1&settlement_date=20101207&status=01&desc=null';

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = $smartpay->hexToStr($sign);

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'amount' => '10000',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '01',
            'desc' => '',
            'sign' => $stringSign,
            'rsa_public_key' => 'noPublicKey'
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment([]);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $smartpay = new Smartpay();

        $encodeStr = 'cmd=_webpay&merchantId=20124252&mobile=15921439746&amount=10000&tx_date=20101207&tx_no=150111&'.
            'tx_params=null&temp=1&settlement_date=20101207&status=01&desc=null';

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = $smartpay->hexToStr($sign);

        $sourceData = [
            'cmd' => '_webpay',
            'merchantId' => '20124252',
            'mobile' => '15921439746',
            'amount' => '10000',
            'tx_date' => '20101207',
            'tx_no' => '150111',
            'tx_params' => '',
            'temp' => '1',
            'settlement_date' => '20101207',
            'status' => '01',
            'desc' => '',
            'sign' => $stringSign,
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $entry = [
            'id' => '150111',
            'amount' => '100'
        ];

        $smartpay->setOptions($sourceData);
        $smartpay->verifyOrderPayment($entry);

        $this->assertEquals('code=200', $smartpay->getMsg());
    }

    /**
     * 測試訂單查詢沒代入merchantId
     */
    public function testPaymentTrackingWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $sourceData = ['merchantId' => ''];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
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

        $sourceData = ['merchantId' => '35660'];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
    }

    /**
     * 測試訂單查詢時取得商家私鑰失敗
     */
    public function testPaymentTrackingGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'rsa_private_key' => 'acctest'
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
    }

    /**
     * 測試訂單查詢時生成加密簽名錯誤
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

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'rsa_private_key' => base64_encode($privkey)
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
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
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
            'rsa_private_key' => base64_encode($this->privateKey)
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數topupResult
     */
    public function testPaymentTrackingResultWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = '<?xml version="1.0" encoding="GBK" ?><list mark="0"></list>';
        $result = iconv('UTF-8', 'GBK', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.smartpay.com',
            'rsa_private_key' => base64_encode($this->privateKey)
        ];

        $smartpay = new Smartpay();
        $smartpay->setContainer($this->container);
        $smartpay->setClient($this->client);
        $smartpay->setResponse($response);
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數transactionStatus
     */
    public function testPaymentTrackingResultWithoutTransactionStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = '<?xml version="1.0" encoding="GBK" ?>'.
            '<list mark="0"><topupResult></topupResult></list>';
        $result = iconv('UTF-8', 'GBK', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.smartpay.com',
            'rsa_private_key' => base64_encode($this->privateKey)
        ];

        $smartpay = new Smartpay();
        $smartpay->setContainer($this->container);
        $smartpay->setClient($this->client);
        $smartpay->setResponse($response);
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
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

        $params = '<?xml version="1.0" encoding="GBK" ?>'.
            '<list mark="0">'.
            '<topupResult>'.
            '<transactionStatus>00</transactionStatus>'.
            '</topupResult>'.
            '</list>';
        $result = iconv('UTF-8', 'GBK', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.smartpay.com',
            'rsa_private_key' => base64_encode($this->privateKey)
        ];

        $smartpay = new Smartpay();
        $smartpay->setContainer($this->container);
        $smartpay->setClient($this->client);
        $smartpay->setResponse($response);
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
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

        $params = '<?xml version="1.0" encoding="GBK" ?>'.
            '<list mark="0">'.
            '<topupResult>'.
            '<transactionStatus>20</transactionStatus>'.
            '</topupResult>'.
            '</list>';
        $result = iconv('UTF-8', 'GBK', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.smartpay.com',
            'rsa_private_key' => base64_encode($this->privateKey)
        ];

        $smartpay = new Smartpay();
        $smartpay->setContainer($this->container);
        $smartpay->setClient($this->client);
        $smartpay->setResponse($response);
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
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

        $params = '<?xml version="1.0" encoding="GBK" ?>'.
            '<list mark="0">'.
            '<topupResult>'.
            '<transactionStatus>10</transactionStatus>'.
            '<merchantTxSeqNo>201404100013593016</merchantTxSeqNo>'.
            '<transactionFactAmount>100</transactionFactAmount>'.
            '</topupResult>'.
            '</list>';
        $result = iconv('UTF-8', 'GBK', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.smartpay.com',
            'rsa_private_key' => base64_encode($this->privateKey),
            'amount' => '1.234'
        ];

        $smartpay = new Smartpay();
        $smartpay->setContainer($this->container);
        $smartpay->setClient($this->client);
        $smartpay->setResponse($response);
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $params = '<?xml version="1.0" encoding="GBK" ?>'.
            '<list mark="0">'.
            '<topupResult>'.
            '<transactionStatus>10</transactionStatus>'.
            '<merchantTxSeqNo>201404100013593016</merchantTxSeqNo>'.
            '<transactionFactAmount>100</transactionFactAmount>'.
            '</topupResult>'.
            '</list>';
        $result = iconv('UTF-8', 'GBK', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.smartpay.com',
            'rsa_private_key' => base64_encode($this->privateKey),
            'amount' => '1.00'
        ];

        $smartpay = new Smartpay();
        $smartpay->setContainer($this->container);
        $smartpay->setClient($this->client);
        $smartpay->setResponse($response);
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入merchantId
     */
    public function testGetPaymentTrackingDataWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['merchantId' => ''];

        $smartpay = new Smartpay();
        $smartpay->setOptions($options);
        $smartpay->getPaymentTrackingData();
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

        $options = ['merchantId' => '35660'];

        $smartpay = new Smartpay();
        $smartpay->setOptions($options);
        $smartpay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時但取得商家私鑰失敗
     */
    public function testGetPaymentTrackingDataButGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'rsa_private_key' => 'acctest'
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($options);
        $smartpay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時但生成加密簽名錯誤
     */
    public function testGetPaymentTrackingDataButGenerateSignatureFailure()
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
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'rsa_private_key' => base64_encode($privkey)
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($options);
        $smartpay->getPaymentTrackingData();
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

        $options = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($options);
        $smartpay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'merchantId' => '35660',
            'number' => '20124252',
            'orderId' => '150111',
            'rsa_private_key' => base64_encode($this->privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.172.com',
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($options);
        $trackingData = $smartpay->getPaymentTrackingData();

        $path = '/paymentGateway/queryMerchantOrder.htm?user_id=20124252' .
            '&card_no=&card_pswd=&merchant_tx_seq_no=150111';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertContains($path, $trackingData['path']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('payment.https.www.172.com', $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數topupResult
     */
    public function testPaymentTrackingVerifyWithoutTopupResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = '<?xml version="1.0" encoding="GBK" ?><list mark="0"></list>';
        $content = urlencode(iconv('UTF-8', 'GBK', $params));
        $sourceData = ['content' => $content];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數transactionStatus
     */
    public function testPaymentTrackingVerifyWithoutTransactionStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = '<?xml version="1.0" encoding="GBK" ?>' .
            '<list mark="0"><topupResult></topupResult></list>';
        $content = urlencode(iconv('UTF-8', 'GBK', $params));
        $sourceData = ['content' => $content];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $params = '<?xml version="1.0" encoding="GBK" ?>' .
            '<list mark="0">' .
            '<topupResult>' .
            '<transactionStatus>00</transactionStatus>' .
            '</topupResult>' .
            '</list>';
        $content = urlencode(iconv('UTF-8', 'GBK', $params));
        $sourceData = ['content' => $content];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $params = '<?xml version="1.0" encoding="GBK" ?>' .
            '<list mark="0">' .
            '<topupResult>' .
            '<transactionStatus>20</transactionStatus>' .
            '</topupResult>' .
            '</list>';
        $content = urlencode(iconv('UTF-8', 'GBK', $params));
        $sourceData = ['content' => $content];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $params = '<?xml version="1.0" encoding="GBK" ?>' .
            '<list mark="0">' .
            '<topupResult>' .
            '<transactionStatus>10</transactionStatus>' .
            '<merchantTxSeqNo>201404100013593016</merchantTxSeqNo>' .
            '<transactionFactAmount>100</transactionFactAmount>' .
            '</topupResult>' .
            '</list>';
        $content = urlencode(iconv('UTF-8', 'GBK', $params));
        $sourceData = [
            'content' => $content,
            'amount' => '1.234'
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單
     */
    public function testPaymentTrackingVerify()
    {
        $params = '<?xml version="1.0" encoding="GBK" ?>' .
            '<list mark="0">' .
            '<topupResult>' .
            '<transactionStatus>10</transactionStatus>' .
            '<merchantTxSeqNo>201404100013593016</merchantTxSeqNo>' .
            '<transactionFactAmount>100</transactionFactAmount>' .
            '</topupResult>' .
            '</list>';
        $content = urlencode(iconv('UTF-8', 'GBK', $params));
        $sourceData = [
            'content' => $content,
            'amount' => '1.00'
        ];

        $smartpay = new Smartpay();
        $smartpay->setOptions($sourceData);
        $smartpay->paymentTrackingVerify();
    }
}
