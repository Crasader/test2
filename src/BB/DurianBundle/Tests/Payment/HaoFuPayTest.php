<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HaoFuPay;
use Buzz\Message\Response;

class HaoFuPayTest extends DurianTestCase
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
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    public function setUp()
    {
        parent::setUp();

        $resource = openssl_pkey_new(['private_key_bits' => 1024]);

        $privateKey = '';
        openssl_pkey_export($resource, $privateKey);
        $publicKey = openssl_pkey_get_details($resource)['key'];

        $this->privateKey = base64_encode($privateKey);
        $this->publicKey = base64_encode($publicKey);

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

        $haoFuPay = new HaoFuPay();
        $haoFuPay->getVerifyData();
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

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->getVerifyData();
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
            'number' => '160801',
            'orderId' => '201702090000000978',
            'orderCreateDate' => '2017-02-09 17:03:27',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '9487',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->getVerifyData();
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
            'number' => '160801',
            'orderId' => '201702090000000978',
            'orderCreateDate' => '2017-02-09 17:03:27',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1090',
            'rsa_private_key' => '',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->getVerifyData();
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
            'number' => '160801',
            'orderId' => '201702090000000978',
            'orderCreateDate' => '2017-02-09 17:03:27',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1090',
            'rsa_private_key' => 'acctest',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->getVerifyData();
    }

    /**
     * 測試支付加密產生簽名失敗
     */
    public function testPayGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $resource = openssl_pkey_new([
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ]);

        $privateKey = '';
        openssl_pkey_export($resource, $privateKey);

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000978',
            'orderCreateDate' => '2017-02-09 17:03:27',
            'amount' => '0.1',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1090',
            'rsa_private_key' => base64_encode($privateKey),
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '160801',
            'orderId' => '201702090000000978',
            'orderCreateDate' => '2017-02-09 17:03:27',
            'amount' => '0.1',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $requestData = $haoFuPay->getVerifyData();

        $this->assertEquals($options['number'], $requestData['merchantID']);
        $this->assertEquals('1.0', $requestData['version']);
        $this->assertEquals('1', $requestData['inputCharset']);
        $this->assertEquals('1', $requestData['signType']);
        $this->assertEquals($options['notify_url'], $requestData['pageUrl']);
        $this->assertEquals($options['notify_url'], $requestData['noticeUrl']);
        $this->assertEquals($options['orderId'], $requestData['orderId']);
        $this->assertEquals($options['amount'] * 100, $requestData['orderAmount']);
        $this->assertEquals('20170209170327', $requestData['orderTime']);
        $this->assertEquals($options['username'], $requestData['productName']);
        $this->assertEquals(1, $requestData['productNum']);
        $this->assertEquals('WEIXIN', $requestData['payType']);
        $this->assertEquals($this->encode($requestData), $requestData['sign']);
    }

    /**
     * 測試返回缺少密鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $haoFuPay = new HaoFuPay();
        $haoFuPay->verifyOrderPayment([]);
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

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'payResult' => '10',
            'rsa_public_key' => $this->publicKey,
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回簽名驗證錯誤
     */
    public function testReturnWithSignatureVerificationFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errMsg' => '',
            'rsa_public_key' => $this->publicKey,
        ];

        $options['sign'] = '';

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付失敗且有錯誤訊息
     */
    public function testReturnFailureWithErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付失敗',
            180130
        );

        $options = [
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '9487',
            'errMsg' => '支付失敗',
            'rsa_public_key' => $this->publicKey,
        ];

        $options['sign'] = $this->encode($options);

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment([]);
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
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'rsa_public_key' => '',
        ];

        $options['sign'] = $this->encode($options);

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment([]);
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
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'rsa_public_key' => 'test',
        ];

        $options['sign'] = $this->encode($options);

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '9487',
            'rsa_public_key' => $this->publicKey,
        ];

        $options['sign'] = $this->encode($options);

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment([]);
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
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errMsg' => '',
            'rsa_public_key' => $this->publicKey,
        ];

        $options['sign'] = $this->encode($options);

        $entry = ['id' => '201612280000000000'];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment($entry);
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
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errMsg' => '',
            'rsa_public_key' => $this->publicKey,
        ];

        $options['sign'] = $this->encode($options);

        $entry = [
            'id' => '201702090000000939',
            'amount' => '9487',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchantID' => '160801',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000939',
            'orderAmount' => '10',
            'orderTime' => '20170209150211',
            'dealId' => '201702091512241840160801',
            'dealTime' => '19700101080000',
            'payAmount' => '10',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errMsg' => '',
            'rsa_public_key' => $this->publicKey,
        ];

        $options['sign'] = $this->encode($options);

        $entry = [
            'id' => '201702090000000939',
            'amount' => '0.1',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->verifyOrderPayment($entry);

        $this->assertEquals('<result>SUCCESS</result>', $haoFuPay->getMsg());
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

        $haoFuPay = new HaoFuPay();
        $haoFuPay->paymentTracking();
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

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->paymentTracking();
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
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => '',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
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
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => 'acctest',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試查詢加密產生簽名失敗
     */
    public function testTrackingGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $resource = openssl_pkey_new([
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ]);

        $privateKey = '';
        openssl_pkey_export($resource, $privateKey);

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'amount' => '0.1',
            'rsa_private_key' => base64_encode($privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入 verify_url 的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有 err 和 msg 的情況
     */
    public function testTrackingReturnWithoutErrAndMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $response = new Response();
        $response->setContent(json_encode([]));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setContainer($this->container);
        $haoFuPay->setClient($this->client);
        $haoFuPay->setResponse($response);
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回 err 非 0
     */
    public function testTrackingReturnWithErrNonZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单信息不存在',
            180123
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $result = [
            'err' => '1081',
            'msg' => '订单信息不存在',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setContainer($this->container);
        $haoFuPay->setClient($this->client);
        $haoFuPay->setResponse($response);
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有 data 的情況
     */
    public function testTrackingReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $result = [
            'err' => '0',
            'msg' => 'ok',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setContainer($this->container);
        $haoFuPay->setClient($this->client);
        $haoFuPay->setResponse($response);
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回 data 缺少必要參數
     */
    public function testTrackingReturnWithoutTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $result = [
            'err' => '0',
            'msg' => 'ok',
            'data' => [],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setContainer($this->container);
        $haoFuPay->setClient($this->client);
        $haoFuPay->setResponse($response);
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
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
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $result = [
            'err' => '0',
            'msg' => 'ok',
            'data' => [
                'orderId' => '201702090000000939',
                'orderAmount' => '100',
                'dealId' => '201702091703318784160801',
                'dealTime' => '19700101080000',
                'payResult' => '',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setContainer($this->container);
        $haoFuPay->setClient($this->client);
        $haoFuPay->setResponse($response);
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回單號錯誤
     */
    public function testTrackingReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $result = [
            'err' => '0',
            'msg' => 'ok',
            'data' => [
                'orderId' => '201702090000000940',
                'orderAmount' => '100',
                'dealId' => '201702091703318784160801',
                'dealTime' => '19700101080000',
                'payResult' => '10',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setContainer($this->container);
        $haoFuPay->setClient($this->client);
        $haoFuPay->setResponse($response);
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'amount' => '0.1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $result = [
            'err' => '0',
            'msg' => 'ok',
            'data' => [
                'orderId' => '201702090000000939',
                'orderAmount' => '1000',
                'dealId' => '201702091703318784160801',
                'dealTime' => '19700101080000',
                'payResult' => '10',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setContainer($this->container);
        $haoFuPay->setClient($this->client);
        $haoFuPay->setResponse($response);
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'amount' => '0.1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $result = [
            'err' => '0',
            'msg' => 'ok',
            'data' => [
                'orderId' => '201702090000000939',
                'orderAmount' => '10',
                'dealId' => '201702091703318784160801',
                'dealTime' => '19700101080000',
                'payResult' => '10',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setContainer($this->container);
        $haoFuPay->setClient($this->client);
        $haoFuPay->setResponse($response);
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000939',
            'amount' => '0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setOptions($options);
        $haoFuPay->getPaymentTrackingData();
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

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '160801',
            'orderId' => '201702090000000978',
            'orderCreateDate' => '2017-02-09 17:03:27',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
        ];

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $haoFuPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '160801',
            'orderId' => '201702090000000978',
            'orderCreateDate' => '2017-02-09 17:03:27',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.gate.xiaojd160.com',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
        ];

        $result = [
            'merchantID' => '160801',
            'method' => 'queryOrder',
            'version' => '1.0',
            'signType' => '1',
            'orderId' => '201702090000000978',
        ];

        $result['sign'] = $this->encode($result);

        $haoFuPay = new HaoFuPay();
        $haoFuPay->setPrivateKey('test');
        $haoFuPay->setOptions($options);
        $trackingData = $haoFuPay->getPaymentTrackingData();

        $this->assertEquals($result, $trackingData['form']);
        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/query', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($options['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     *  產生加密串
     *
     * @param array $encodeData
     * @return string
     */
    private function encode($encodeData)
    {
        $exceptKeys = ['sign', 'rsa_public_key', 'rsa_private_key'];
        foreach ($exceptKeys as $key) {
            unset($encodeData[$key]);
        }

        // 空值不須加密
        foreach ($encodeData as $key => $data) {
            if (trim($data) === '') {
                unset($encodeData[$key]);
            }
        }

        ksort($encodeData);
        $encodeData['key'] = 'test';
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        return base64_encode($sign);
    }
}
