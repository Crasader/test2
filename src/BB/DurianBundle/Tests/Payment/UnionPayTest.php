<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\UnionPay;
use Buzz\Message\Response;

class UnionPayTest extends DurianTestCase
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

        $resource = openssl_pkey_new();
        $csr = openssl_csr_new([], $resource);
        $csrSign = openssl_csr_sign($csr, null, $resource, 365);

        $privateKey = '';
        openssl_pkcs12_export($csrSign, $privateKey, $resource, 'test');
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

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
        ];

        $unionPay = new UnionPay();
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
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

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->getVerifyData();
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
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1314',
            'merchant_extra' => ['cmpAppId' => '42007'],
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰為空字串
     */
    public function testPayGetMerchantKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_private_key' => '',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰失敗
     */
    public function testPayGetMerchantKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_private_key' => 'acctest',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
    }

    /**
     * 測試支付回傳缺少簽名
     */
    public function testPayWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '0.1',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $webOrderInfo = 'https://cashier.unionpay95516.cc/bus.api/gateway/cashier?summary=php1test&amount=10&payTypeC' .
            'ode=web.pay&mchId=40010&orderNo=201701031007250692200000000000000059643&cmpAppId=42007&orderId=59643&sig' .
            'nature=WO%2BWKFlVFLePDS%2Bqz7HWnqd4G8bzSiHK8Wp%2Bcotmy0r5WsEbIfdrjGczVnn76c5U7e24YWvbdYJAv%2BteZuWxV0HAu' .
            'XVkokDflbmZltsDButO6JfWoUG%2Bb75vmSxO75eCI0b3Xl5skqKVD0hwebo1pg6ZFmjqAV2BWhYU8zWJW%2FA%3D&outTradeNo=201' .
            '701030000000595&buyerId=&encoding=UTF-8&version=1.0.0';

        $result = [
            'summary' => 'php1test',
            'amount' => '10',
            'mchId' => '40010',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'webOrderInfo' => $webOrderInfo,
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'respCode' => '000000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
    }

    /**
     * 測試支付回傳缺少返回碼
     */
    public function testPayWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '0.1',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $webOrderInfo = 'https://cashier.unionpay95516.cc/bus.api/gateway/cashier?summary=php1test&amount=10&payTypeC' .
            'ode=web.pay&mchId=40010&orderNo=201701031007250692200000000000000059643&cmpAppId=42007&orderId=59643&sig' .
            'nature=WO%2BWKFlVFLePDS%2Bqz7HWnqd4G8bzSiHK8Wp%2Bcotmy0r5WsEbIfdrjGczVnn76c5U7e24YWvbdYJAv%2BteZuWxV0HAu' .
            'XVkokDflbmZltsDButO6JfWoUG%2Bb75vmSxO75eCI0b3Xl5skqKVD0hwebo1pg6ZFmjqAV2BWhYU8zWJW%2FA%3D&outTradeNo=201' .
            '701030000000595&buyerId=&encoding=UTF-8&version=1.0.0';

        $result = [
            'summary' => 'php1test',
            'amount' => '10',
            'mchId' => '40010',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'webOrderInfo' => $webOrderInfo,
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
    }

    /**
     * 測試支付回傳缺少提交網址
     */
    public function testPayWithoutWebOrderInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '0.1',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $result = [
            'summary' => 'php1test',
            'amount' => '10',
            'mchId' => '40010',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'respCode' => '000000',
            'respMsg' => '成功',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
    }

    /**
     * 測試支付回傳驗簽失敗
     */
    public function testPayWithSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '0.1',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $webOrderInfo = 'https://cashier.unionpay95516.cc/bus.api/gateway/cashier?summary=php1test&amount=10&payTypeC' .
            'ode=web.pay&mchId=40010&orderNo=201701031007250692200000000000000059643&cmpAppId=42007&orderId=59643&sig' .
            'nature=WO%2BWKFlVFLePDS%2Bqz7HWnqd4G8bzSiHK8Wp%2Bcotmy0r5WsEbIfdrjGczVnn76c5U7e24YWvbdYJAv%2BteZuWxV0HAu' .
            'XVkokDflbmZltsDButO6JfWoUG%2Bb75vmSxO75eCI0b3Xl5skqKVD0hwebo1pg6ZFmjqAV2BWhYU8zWJW%2FA%3D&outTradeNo=201' .
            '701030000000595&buyerId=&encoding=UTF-8&version=1.0.0';

        $result = [
            'summary' => 'php1test',
            'amount' => '10',
            'mchId' => '40010',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'respMsg' => '成功',
            'webOrderInfo' => $webOrderInfo,
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'respCode' => '000000',
            'signature' => 'awesome',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
    }

    /**
     * 測試支付回傳返回碼非成功
     */
    public function testPayWithError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '失敗訊息',
            180130
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '0.1',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $webOrderInfo = 'https://cashier.unionpay95516.cc/bus.api/gateway/cashier?summary=php1test&amount=10&payTypeC' .
            'ode=web.pay&mchId=40010&orderNo=201701031007250692200000000000000059643&cmpAppId=42007&orderId=59643&sig' .
            'nature=WO%2BWKFlVFLePDS%2Bqz7HWnqd4G8bzSiHK8Wp%2Bcotmy0r5WsEbIfdrjGczVnn76c5U7e24YWvbdYJAv%2BteZuWxV0HAu' .
            'XVkokDflbmZltsDButO6JfWoUG%2Bb75vmSxO75eCI0b3Xl5skqKVD0hwebo1pg6ZFmjqAV2BWhYU8zWJW%2FA%3D&outTradeNo=201' .
            '701030000000595&buyerId=&encoding=UTF-8&version=1.0.0';

        $result = [
            'summary' => 'php1test',
            'amount' => '10',
            'mchId' => '40010',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'respMsg' => '失敗訊息',
            'webOrderInfo' => $webOrderInfo,
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'respCode' => '123456',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '0.1',
            'username' => 'php1test',
            'ip' => '131.452.0.0',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1102',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $webOrderInfo = 'https://cashier.unionpay95516.cc/bus.api/gateway/cashier?summary=php1test&amount=10&payTypeC' .
            'ode=web.pay&mchId=40010&orderNo=201701031007250692200000000000000059643&cmpAppId=42007&orderId=59643&sig' .
            'nature=WO%2BWKFlVFLePDS%2Bqz7HWnqd4G8bzSiHK8Wp%2Bcotmy0r5WsEbIfdrjGczVnn76c5U7e24YWvbdYJAv%2BteZuWxV0HAu' .
            'XVkokDflbmZltsDButO6JfWoUG%2Bb75vmSxO75eCI0b3Xl5skqKVD0hwebo1pg6ZFmjqAV2BWhYU8zWJW%2FA%3D&outTradeNo=201' .
            '701030000000595&buyerId=&encoding=UTF-8&version=1.0.0';

        $result = [
            'summary' => 'php1test',
            'amount' => '10',
            'mchId' => '40010',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'respMsg' => '成功',
            'webOrderInfo' => $webOrderInfo,
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'respCode' => '000000',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $requestData = $unionPay->getVerifyData();

        $this->assertEquals($webOrderInfo, $requestData['act_url']);
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

        $unionPay = new UnionPay();
        $unionPay->verifyOrderPayment([]);
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
            'amount' => '300',
            'outTradeNo' => '201612280000000514',
            'payTypeOrderNo' => 'OR1228164210093863635',
            'orderNo' => '201612280440390518300000000000000244720',
            'mchId' => '40010',
            'version' => '1.0.0',
            'encoding' => 'utf-8',
            'rsa_public_key' => '',
        ];

        $options['signature'] = $this->encode($options);

        $unionPay = new UnionPay();
        $unionPay->setOptions($options);
        $unionPay->verifyOrderPayment([]);
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
            'amount' => '300',
            'outTradeNo' => '201612280000000514',
            'payTypeOrderNo' => 'OR1228164210093863635',
            'orderNo' => '201612280440390518300000000000000244720',
            'mchId' => '40010',
            'version' => '1.0.0',
            'encoding' => 'utf-8',
            'rsa_public_key' => '123',
        ];

        $options['signature'] = $this->encode($options);

        $unionPay = new UnionPay();
        $unionPay->setOptions($options);
        $unionPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'amount' => '300',
            'outTradeNo' => '201612280000000514',
            'payTypeOrderNo' => 'OR1228164210093863635',
            'orderNo' => '201612280440390518300000000000000244720',
            'mchId' => '40010',
            'version' => '1.0.0',
            'encoding' => 'utf-8',
        ];

        $unionPay = new UnionPay();
        $unionPay->setOptions($options);
        $unionPay->verifyOrderPayment([]);
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
            'amount' => '300',
            'outTradeNo' => '201612280000000514',
            'payTypeOrderNo' => 'OR1228164210093863635',
            'orderNo' => '201612280440390518300000000000000244720',
            'mchId' => '40010',
            'version' => '1.0.0',
            'encoding' => 'utf-8',
            'rsa_public_key' => $this->publicKey,
        ];

        $options['signature'] = $this->encode($options);

        $unionPay = new UnionPay();
        $unionPay->setOptions($options);
        $unionPay->verifyOrderPayment([]);
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

        $returnData = [
            'amount' => '300',
            'outTradeNo' => '201612280000000514',
            'payTypeOrderNo' => 'OR1228164210093863635',
            'orderNo' => '201612280440390518300000000000000244720',
            'mchId' => '40010',
            'version' => '1.0.0',
            'encoding' => 'utf-8',
        ];

        $returnData['signature'] = $this->encode($returnData);
        $returnData['rsa_public_key'] = $this->publicKey;

        $entry = ['id' => '201612280000000000'];

        $unionPay = new UnionPay();
        $unionPay->setOptions($returnData);
        $unionPay->verifyOrderPayment($entry);
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

        $returnData = [
            'amount' => '300',
            'outTradeNo' => '201612280000000514',
            'payTypeOrderNo' => 'OR1228164210093863635',
            'orderNo' => '201612280440390518300000000000000244720',
            'mchId' => '40010',
            'version' => '1.0.0',
            'encoding' => 'utf-8',
        ];

        $returnData['signature'] = $this->encode($returnData);
        $returnData['rsa_public_key'] = $this->publicKey;

        $entry = [
            'id' => '201612280000000514',
            'amount' => '1314',
        ];

        $unionPay = new UnionPay();
        $unionPay->setOptions($returnData);
        $unionPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $returnData = [
            'amount' => '300',
            'outTradeNo' => '201612280000000514',
            'payTypeOrderNo' => 'OR1228164210093863635',
            'orderNo' => '201612280440390518300000000000000244720',
            'mchId' => '40010',
            'version' => '1.0.0',
            'encoding' => 'utf-8',
        ];

        $returnData['signature'] = $this->encode($returnData);
        $returnData['rsa_public_key'] = $this->publicKey;

        $entry = [
            'id' => '201612280000000514',
            'amount' => '3',
        ];

        $unionPay = new UnionPay();
        $unionPay->setOptions($returnData);
        $unionPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $unionPay->getMsg());
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

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->paymentTracking();
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
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_private_key' => '',
        ];

        $unionPay = new UnionPay();
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
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
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_private_key' => '',
            'payTypeTradeNo' => '123',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
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
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_private_key' => 'acctest',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試訂單查詢缺少商家額外的參數設定 cmpAppId
     */
    public function testTrackingWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => [],
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
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
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
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

        $options = [
            'merchant_extra' => ['cmpAppId' => '42007'],
            'number' => '40010',
            'orderId' => '201701030000000595',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respCode' => '000000',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有簽名
     */
    public function testTrackingReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
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

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
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

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_public_key' => '',
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳返回碼非成功
     */
    public function testTrackingRespondWithError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查詢失敗',
            180123
        );

        $options = [
            'amount' => '0.1',
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '查詢失敗',
            'respCode' => '123456',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
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

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
            'signature' => '123',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功但交易狀態不為 2
     */
    public function testTrackingSuccessRespondWithWrongTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'amount' => '0.1',
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'respMsg' => '成功',
            'respCode' => '000000',
            'tradeStatus' => '1234',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳單號錯誤
     */
    public function testTrackingRespondWithWrongOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'amount' => '0.1',
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000597',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試訂單查詢已支付訂單回傳缺少金額
     */
    public function testTrackingRespondWithoutAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'amount' => '0.1',
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $result = [
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
            'outTradeNo' => '201701030000000595',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳金額錯誤
     */
    public function testTrackingRespondWithWrongAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'amount' => '0.1',
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $result = [
            'amount' => '101',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'amount' => '0.1',
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $result = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $result['signature'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type: application/json;');

        $unionPay = new UnionPay();
        $unionPay->setContainer($this->container);
        $unionPay->setClient($this->client);
        $unionPay->setResponse($response);
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($options);
        $unionPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入 privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $options = [
            'amount' => '0.1',
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $unionPay = new UnionPay();
        $unionPay->setOptions($options);
        $unionPay->getPaymentTrackingData();
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

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入 verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '100120',
            'orderId' => '201612190000000734',
            'orderCreateDate' => '20161219120031',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'rsa_private_key' => $this->privateKey,
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'amount' => '0.1',
            'number' => '40010',
            'orderId' => '201701030000000595',
            'merchant_extra' => ['cmpAppId' => '42007'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.openapi.unionpay95516.cc',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
        ];

        $result = [
            'version' => '1.0.0',
            'encoding' => 'UTF-8',
            'mchId' => '40010',
            'cmpAppId' => '42007',
            'payTypeCode' => 'web.pay',
            'outTradeNo' => '201701030000000595',
        ];
        $result['signature'] = $this->encode($result);

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $trackingData = $unionPay->getPaymentTrackingData();

        $this->assertEquals($result, $trackingData['form']);
        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/pre.lepay.api/order/query', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢但查詢結果未返回 respCode、respMsg
     */
    public function testPaymentTrackingVerifyWithoutRespCodeAndRespMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
        ];

        $sourceData = ['content' => json_encode($content)];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳查詢失敗
     */
    public function testPaymentTrackingVerifyWithError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查詢失敗',
            180123
        );

        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '查詢失敗',
            'respCode' => '000007',
        ];

        $content['signature'] = $this->encode($content);

        $sourceData = [
            'content' => json_encode($content),
            'rsa_public_key' => $this->publicKey,
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $sourceData = ['content' => json_encode($content)];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳缺少簽名
     */
    public function testPaymentTrackingVerifyWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $sourceData = [
            'content' => json_encode($content),
            'rsa_public_key' => $this->publicKey,
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名錯誤
     */
    public function testPaymentTrackingVerifyWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $content['signature'] = '123';

        $sourceData = [
            'content' => json_encode($content),
            'rsa_public_key' => $this->publicKey,
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功但交易狀態不為 2
     */
    public function testPaymentTrackingVerifyWithWrongTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'respMsg' => '成功',
            'respCode' => '000000',
            'tradeStatus' => '1234',
        ];

        $content['signature'] = $this->encode($content);

        $sourceData = [
            'content' => json_encode($content),
            'rsa_public_key' => $this->publicKey,
            'orderId' => '201701030000000595',
            'amount' => '0.1',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單號錯誤
     */
    public function testPaymentTrackingVerifyWithWrongOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $content['signature'] = $this->encode($content);

        $sourceData = [
            'content' => json_encode($content),
            'rsa_public_key' => $this->publicKey,
            'orderId' => '123',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢已支付訂單回傳缺少金額
     */
    public function testPaymentTrackingRespondWithoutAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
            'outTradeNo' => '201701050000000616',
        ];

        $content['signature'] = $this->encode($content);

        $sourceData = [
            'content' => json_encode($content),
            'rsa_public_key' => $this->publicKey,
            'orderId' => '201701050000000616',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢金額錯誤
     */
    public function testPaymentTrackingVerifyWithWrongAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $content['signature'] = $this->encode($content);

        $sourceData = [
            'content' => json_encode($content),
            'rsa_public_key' => $this->publicKey,
            'orderId' => '201701030000000595',
            'amount' => '101',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = [
            'amount' => '10',
            'appPmtChnlId' => '258',
            'mchId' => '40010',
            'payTypeTradeNo' => 'OR0103101047058058235',
            'tradeNo' => '201701031008417621300000000000000257794',
            'encoding' => 'UTF-8',
            'version' => '1.0.0',
            'cmpAppId' => '42007',
            'outTradeNo' => '201701030000000595',
            'tradeStatus' => '2',
            'respMsg' => '成功',
            'respCode' => '000000',
        ];

        $content['signature'] = $this->encode($content);

        $sourceData = [
            'content' => json_encode($content),
            'rsa_public_key' => $this->publicKey,
            'orderId' => '201701030000000595',
            'amount' => '0.1',
        ];

        $unionPay = new UnionPay();
        $unionPay->setPrivateKey('test');
        $unionPay->setOptions($sourceData);
        $unionPay->paymentTrackingVerify();
    }

    /**
     *  產生加密串
     *
     * @param array $encodeData
     * @return string
     */
    private function encode($encodeData)
    {
        ksort($encodeData);

        $encodeStr = sha1(urldecode(http_build_query($encodeData)));

        $privateCert = [];
        openssl_pkcs12_read(base64_decode($this->privateKey), $privateCert, 'test');

        $sign = '';
        openssl_sign($encodeStr, $sign, $privateCert['pkey']);

        return base64_encode($sign);
    }
}
