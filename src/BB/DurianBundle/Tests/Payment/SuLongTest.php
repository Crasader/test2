<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SuLong;
use Buzz\Message\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class SuLongTest extends DurianTestCase
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

    /**
     * 支付時的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 對外返回結果
     *
     * @var array
     */
    private $verifyResult;

    /**
     * 返回時的參數
     *
     * @var array
     */
    private $returnResult;

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

        $this->sourceData = [
            'number' => '103288201001',
            'notify_url' => 'http://pay.return.php',
            'orderId' => '201806050000012689',
            'orderCreateDate' => '2018-06-05 14:45:38',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'ip' => '192.168.101.1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.islpay.com',
        ];

        $qrcode = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=5V03dc681157250fccc297718ae9cec3';
        $sign = 'M6BchxcBrohBEMmnsviORoDE/y8J8ExOORfJwvyM4ZG5+waCxuoYf7lDQTkB+NpYLrJOmrnyX69GI4JKYN2i2IOy0cKvawlsOc' .
            '8bJnF+34nmeer5SmX71aV5K9XoTW4C3mm7BXBuz/F6G2DQ7m7qMMES6s8Z2efFNmcpU6WswW0=';

        $this->verifyResult = [
            'response' => [
                'interface_version' => 'V3.1',
                'merchant_code' => '103288201001',
                'order_amount' => '1.00',
                'order_no' => '201806050000012689',
                'order_time' => '2018-06-05 15:49:04',
                'qrcode' => $qrcode,
                'resp_code' => 'SUCCESS',
                'resp_desc' => '通讯成功',
                'result_code' => '0',
                'sign' => $sign,
                'sign_type' => 'RSA-S',
                'trade_no' => '1001163700',
                'trade_time' => '2018-06-05 15:49:08',
            ],
        ];

        $encodeStr = 'interface_version=V3.0&merchant_code=103288201001&notify_id=6d362c320b8847e780deb6f694027617&' .
            'notify_type=offline_notify&order_amount=1&order_no=201806050000012689&order_time=2018-06-05 15:49:04&' .
            'trade_no=1001163700&trade_status=SUCCESS&trade_time=2018-06-05 15:49:08';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $this->returnResult = [
            'sign' => base64_encode($sign),
            'trade_no' => '1001163700',
            'order_amount' => '1',
            'interface_version' => 'V3.0',
            'order_time' => '2018-06-05 15:49:04',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'notify_id' => '6d362c320b8847e780deb6f694027617',
            'trade_time' => '2018-06-05 15:49:08',
            'merchant_code' => '103288201001',
            'trade_status' => 'SUCCESS',
            'order_no' => '201806050000012689',
            'rsa_public_key' => $this->publicKey,
        ];
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

        $suLong = new SuLong();
        $suLong->setOptions([]);
        $suLong->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->sourceData['paymentVendorId'] = '9999';

        $suLong = new SuLong();
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試產生加密簽名失敗
     */
    public function testGenerateSignatureFailure()
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

        $this->sourceData['rsa_private_key'] = base64_encode($privkey);

        $suLong = new SuLong();
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->sourceData['verify_url'] = '';

        $suLong = new SuLong();
        $suLong->setPrivateKey('test');
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試二維支付未返回resp_code
     */
    public function testQrPayNoReturnRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['response']['resp_code']);

        $response = new Response();
        $response->setContent($this->arrayToXml($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $suLong = new SuLong();
        $suLong->setContainer($this->container);
        $suLong->setClient($this->client);
        $suLong->setResponse($response);
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試二維支付未返回resp_desc
     */
    public function testQrPayNoReturnRespDesc()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['response']['resp_desc']);

        $response = new Response();
        $response->setContent($this->arrayToXml($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $suLong = new SuLong();
        $suLong->setContainer($this->container);
        $suLong->setClient($this->client);
        $suLong->setResponse($response);
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試二維支付返回resp_code不為SUCCESS
     */
    public function testQrPayReturnRespCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '请输入真实的用户ip',
            180130
        );

        $this->verifyResult['response']['resp_code'] = 'FAIL';
        $this->verifyResult['response']['resp_desc'] = '请输入真实的用户ip';

        $response = new Response();
        $response->setContent($this->arrayToXml($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $suLong = new SuLong();
        $suLong->setContainer($this->container);
        $suLong->setClient($this->client);
        $suLong->setResponse($response);
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試二維支付未返回result_code
     */
    public function testQrPayNoReturnResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['response']['result_code']);

        $response = new Response();
        $response->setContent($this->arrayToXml($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $suLong = new SuLong();
        $suLong->setContainer($this->container);
        $suLong->setClient($this->client);
        $suLong->setResponse($response);
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試二維支付返回result_code不等於0，且沒有返回result_desc
     */
    public function testQrPayReturnResultCodeNotEqualToZeroAndNoResultDesc()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->verifyResult['response']['result_code'] = -1;
        unset($this->verifyResult['response']['result_desc']);

        $response = new Response();
        $response->setContent($this->arrayToXml($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $suLong = new SuLong();
        $suLong->setContainer($this->container);
        $suLong->setClient($this->client);
        $suLong->setResponse($response);
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試二維支付返回result_code不等於0
     */
    public function testQrPayReturnResultCodeNotEqualToZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '获取二维码失败',
            180130
        );

        $this->verifyResult['response']['result_code'] = 1;
        $this->verifyResult['response']['result_desc'] = '获取二维码失败';

        $response = new Response();
        $response->setContent($this->arrayToXml($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $suLong = new SuLong();
        $suLong->setContainer($this->container);
        $suLong->setClient($this->client);
        $suLong->setResponse($response);
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試二維支付未返回qrcode
     */
    public function testQrPayNoReturnQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['response']['qrcode']);

        $response = new Response();
        $response->setContent($this->arrayToXml($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $suLong = new SuLong();
        $suLong->setContainer($this->container);
        $suLong->setClient($this->client);
        $suLong->setResponse($response);
        $suLong->setOptions($this->sourceData);
        $suLong->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrPay()
    {
        $response = new Response();
        $response->setContent($this->arrayToXml($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $suLong = new SuLong();
        $suLong->setContainer($this->container);
        $suLong->setClient($this->client);
        $suLong->setResponse($response);
        $suLong->setOptions($this->sourceData);
        $encodeData = $suLong->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals(
            'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=5V03dc681157250fccc297718ae9cec3',
            $suLong->getQrcode()
        );
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->sourceData['paymentVendorId'] = '1104';

        $suLong = new SuLong();
        $suLong->setOptions($this->sourceData);
        $encodeData = $suLong->getVerifyData();

        $encodeStr = 'client_ip=192.168.101.1&extend_param=orderId^201806050000012689&input_charset=UTF-8&' .
            'interface_version=V3.0&merchant_code=103288201001&notify_url=http://pay.return.php&' .
            'order_amount=1.00&order_no=201806050000012689&order_time=2018-06-05 14:45:38&' .
            'product_name=201806050000012689&service_type=h5_qq';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $this->assertEquals('103288201001', $encodeData['merchant_code']);
        $this->assertEquals('h5_qq', $encodeData['service_type']);
        $this->assertEquals('http://pay.return.php', $encodeData['notify_url']);
        $this->assertEquals('V3.0', $encodeData['interface_version']);
        $this->assertEquals('UTF-8', $encodeData['input_charset']);
        $this->assertEquals('RSA-S', $encodeData['sign_type']);
        $this->assertEquals(base64_encode($sign), $encodeData['sign']);
        $this->assertEquals('192.168.101.1', $encodeData['client_ip']);
        $this->assertEquals('201806050000012689', $encodeData['order_no']);
        $this->assertEquals('2018-06-05 14:45:38', $encodeData['order_time']);
        $this->assertEquals('1.00', $encodeData['order_amount']);
        $this->assertEquals('201806050000012689', $encodeData['product_name']);
        $this->assertEquals('orderId^201806050000012689', $encodeData['extend_param']);
    }

    /**
     * 測試銀聯快捷支付
     */
    public function testUnionPay()
    {
        $this->sourceData['paymentVendorId'] = '278';

        $suLong = new SuLong();
        $suLong->setOptions($this->sourceData);
        $encodeData = $suLong->getVerifyData();

        $encodeStr = 'client_ip=192.168.101.1&extend_param=orderId^201806050000012689&input_charset=UTF-8&' .
            'interface_version=V3.0&merchant_code=103288201001&notify_url=http://pay.return.php&' .
            'order_amount=1.00&order_no=201806050000012689&order_time=2018-06-05 14:45:38&pay_type=express_d&' .
            'product_name=201806050000012689&redo_flag=1&service_type=direct_pay';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $this->assertEquals('103288201001', $encodeData['merchant_code']);
        $this->assertEquals('direct_pay', $encodeData['service_type']);
        $this->assertEquals('express_d', $encodeData['pay_type']);
        $this->assertEquals('http://pay.return.php', $encodeData['notify_url']);
        $this->assertEquals('V3.0', $encodeData['interface_version']);
        $this->assertEquals('UTF-8', $encodeData['input_charset']);
        $this->assertEquals('RSA-S', $encodeData['sign_type']);
        $this->assertEquals(base64_encode($sign), $encodeData['sign']);
        $this->assertEquals('192.168.101.1', $encodeData['client_ip']);
        $this->assertEquals('201806050000012689', $encodeData['order_no']);
        $this->assertEquals('2018-06-05 14:45:38', $encodeData['order_time']);
        $this->assertEquals('1.00', $encodeData['order_amount']);
        $this->assertEquals('201806050000012689', $encodeData['product_name']);
        $this->assertEquals('orderId^201806050000012689', $encodeData['extend_param']);
    }

    /**
     * 測試網銀支付
     */
    public function testPayWithOnlineBank()
    {
        $this->sourceData['paymentVendorId'] = '1';

        $encodeStr = 'bank_code=ICBC&client_ip=192.168.101.1&extend_param=orderId^201806050000012689&' .
            'input_charset=UTF-8&interface_version=V3.0&merchant_code=103288201001&' .
            'notify_url=http://pay.return.php&order_amount=1.00&order_no=201806050000012689&' .
            'order_time=2018-06-05 14:45:38&product_name=201806050000012689&redo_flag=1&service_type=direct_pay';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $suLong = new SuLong();
        $suLong->setOptions($this->sourceData);
        $requestData = $suLong->getVerifyData();

        $this->assertEquals('103288201001', $requestData['merchant_code']);
        $this->assertEquals('direct_pay', $requestData['service_type']);
        $this->assertEquals('http://pay.return.php', $requestData['notify_url']);
        $this->assertEquals('V3.0', $requestData['interface_version']);
        $this->assertEquals('UTF-8', $requestData['input_charset']);
        $this->assertEquals('RSA-S', $requestData['sign_type']);
        $this->assertEquals(base64_encode($sign), $requestData['sign']);
        $this->assertEquals('192.168.101.1', $requestData['client_ip']);
        $this->assertEquals('201806050000012689', $requestData['order_no']);
        $this->assertEquals('2018-06-05 14:45:38', $requestData['order_time']);
        $this->assertEquals('1.00', $requestData['order_amount']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
        $this->assertEquals('1', $requestData['redo_flag']);
        $this->assertEquals('201806050000012689', $requestData['product_name']);
        $this->assertEquals('orderId^201806050000012689', $requestData['extend_param']);
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

        $suLong = new SuLong();
        $suLong->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $suLong = new SuLong();
        $suLong->setOptions($this->returnResult);
        $suLong->verifyOrderPayment([]);
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

        $this->returnResult['rsa_public_key'] = '';

        $suLong = new SuLong();
        $suLong->setOptions($this->returnResult);
        $suLong->verifyOrderPayment([]);
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

        $this->returnResult['rsa_public_key'] = '123456';

        $suLong = new SuLong();
        $suLong->setOptions($this->returnResult);
        $suLong->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $suLong = new SuLong();
        $suLong->setOptions($this->returnResult);
        $suLong->verifyOrderPayment([]);
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

        $encodeStr = 'interface_version=V3.0&merchant_code=103288201001&notify_id=6d362c320b8847e780deb6f694027617&' .
            'notify_type=offline_notify&order_amount=1&order_no=201806050000012689&order_time=2018-06-05 15:49:04&' .
            'trade_no=1001163700&trade_status=FAILED&trade_time=2018-06-05 15:49:08';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $this->returnResult['trade_status'] = 'FAILED';
        $this->returnResult['sign'] = base64_encode($sign);

        $suLong = new SuLong();
        $suLong->setOptions($this->returnResult);
        $suLong->verifyOrderPayment([]);
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

        $entry = [
            'id' => '201709180000000941',
        ];

        $suLong = new SuLong();
        $suLong->setOptions($this->returnResult);
        $suLong->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201806050000012689',
            'amount' => '11.00'
        ];

        $suLong = new SuLong();
        $suLong->setOptions($this->returnResult);
        $suLong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccess()
    {
        $entry = [
            'id' => '201806050000012689',
            'amount' => '1'
        ];

        $suLong = new SuLong();
        $suLong->setOptions($this->returnResult);
        $suLong->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $suLong->getMsg());
    }

    /**
     * 將array格式轉成xml
     *
     * @param array $data 待轉換的原始資料
     * @return string
     */
    private function arrayToXml($data)
    {
        $rootNodeName = 'dinpay';
        $context = [
            'xml_encoding' => 'UTF-8',
            'xml_standalone' => true,
        ];

        $encoders = [new XmlEncoder($rootNodeName)];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($data, 'xml', $context);

        return str_replace("\n", '', $xml);
    }
}
