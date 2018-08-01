<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\IShangRsa;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class IShangRsaTest extends WebTestCase
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

        $res = openssl_pkey_new();

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $this->privateKey = base64_encode($privateKey);

        $publicKey = openssl_pkey_get_details($res);

        $this->publicKey = base64_encode($publicKey['key']);

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

        $sourceData = ['number' => ''];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($sourceData);
        $iShangRsa->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '201802060000006543',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '9911',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($sourceData);
        $iShangRsa->getVerifyData();
    }

    /**
     * 測試加密簽名參數失敗
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
        $res = openssl_pkey_new($config);

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $privateKey = base64_encode($privateKey);

        $sourceData = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '1',
            'rsa_private_key' => $privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($sourceData);
        $iShangRsa->getVerifyData();
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

        $sourceData = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => '',
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
            'ip' => '111.235.135.54',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($sourceData);
        $iShangRsa->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少respCode
     */
    public function testPayParameterWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
            'ip' => '111.235.135.54',
        ];

        $content = '{}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iShangRsa = new IShangRsa();
        $iShangRsa->setContainer($this->container);
        $iShangRsa->setClient($this->client);
        $iShangRsa->setOptions($options);
        $iShangRsa->setResponse($response);
        $iShangRsa->getVerifyData();
    }

    /**
     * 測試支付對外返回失敗
     */
    public function testPayParameterFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户不存在！',
            180130
        );

        $options = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
            'ip' => '111.235.135.54',
        ];

        $content = '{"respCode":10011,"respMsg":"\u5546\u6237\u4e0d\u5b58\u5728\uff01","signType":"RSA","version"' .
            ':"1.0.0"}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iShangRsa = new IShangRsa();
        $iShangRsa->setContainer($this->container);
        $iShangRsa->setClient($this->client);
        $iShangRsa->setOptions($options);
        $iShangRsa->setResponse($response);
        $iShangRsa->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少qrCode
     */
    public function testPayParameterWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
            'ip' => '111.235.135.54',
        ];

        $content = '{"orderNo":"C20775659866600077","merOrderNo":"201802070000006552","respCode":10000,"respMsg":"\u' .
            '4ea4\u6613\u6210\u529f\uff01","signType":"RSA","version":"1.0.0","signature":"S11nf\/sJqQg6="}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iShangRsa = new IShangRsa();
        $iShangRsa->setContainer($this->container);
        $iShangRsa->setClient($this->client);
        $iShangRsa->setOptions($options);
        $iShangRsa->setResponse($response);
        $iShangRsa->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少payUrl
     */
    public function testPayParameterWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '1104',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
            'ip' => '111.235.135.54',
        ];

        $content = '{"orderNo":"C20775659866600077","merOrderNo":"201802070000006552","respCode":10000,"respMsg":"\u' .
            '4ea4\u6613\u6210\u529f\uff01","signType":"RSA","version":"1.0.0","signature":"S11nf\/sJqQg6="}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iShangRsa = new IShangRsa();
        $iShangRsa->setContainer($this->container);
        $iShangRsa->setClient($this->client);
        $iShangRsa->setOptions($options);
        $iShangRsa->setResponse($response);
        $iShangRsa->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
            'ip' => '111.235.135.54',
        ];

        $content = '{"orderNo":"C20775659866600077","merOrderNo":"201802070000006552","qrCode":"https:\/\/qr.9551' .
            '6.com\/00010000\/62324025678890163299456287625025","respCode":10000,"respMsg":"\u4ea4\u6613\u6210\u5' .
            '29f\uff01","signType":"RSA","version":"1.0.0","signature":"S11nf\/sJ"}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iShangRsa = new IShangRsa();
        $iShangRsa->setContainer($this->container);
        $iShangRsa->setClient($this->client);
        $iShangRsa->setOptions($options);
        $iShangRsa->setResponse($response);
        $data = $iShangRsa->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/62324025678890163299456287625025', $iShangRsa->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '1104',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
            'ip' => '111.235.135.54',
        ];

        $content = '{"orderNo":"C20797535856255077","merOrderNo":"201802070000006550","payUrl":"https:\/\/qpay.qq.c' .
            'om\/qr\/6b99ad9e","respCode":10000,"respMsg":"\u4ea4\u6613\u6210\u529f\uff01","signType":"RSA","versio' .
            'n":"1.0.0","signature":"jfGY97ptLA"}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iShangRsa = new IShangRsa();
        $iShangRsa->setContainer($this->container);
        $iShangRsa->setClient($this->client);
        $iShangRsa->setOptions($options);
        $iShangRsa->setResponse($response);
        $data = $iShangRsa->getVerifyData();


        $this->assertEquals('https://qpay.qq.com/qr/6b99ad9e', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => 'M20180115152348733737',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '1',
            'username' => 'phptest1',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'platformNo' => 'P20180115116370467709',
                'tradeRate' => '1.80',
            ],
            'ip' => '111.235.135.54',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($options);
        $verifyData = $iShangRsa->getVerifyData();

        $this->assertEquals('1.0.0', $verifyData['version']);
        $this->assertEquals('40000', $verifyData['tranType']);
        $this->assertEquals('P20180115116370467709', $verifyData['platformNo']);
        $this->assertEquals($options['number'], $verifyData['merNo']);
        $this->assertEquals('pay', $verifyData['service']);
        $this->assertEquals($options['amount'] * 100, $verifyData['orderAmount']);
        $this->assertEquals($options['username'], $verifyData['subject']);
        $this->assertEquals('', $verifyData['desc']);
        $this->assertEquals($options['orderId'], $verifyData['merOrderNo']);
        $this->assertEquals('1001', $verifyData['bankType']);
        $this->assertEquals($options['notify_url'], $verifyData['frontUrl']);
        $this->assertEquals($options['notify_url'], $verifyData['backUrl']);
        $this->assertEquals('1.80', $verifyData['tradeRate']);
        $this->assertEquals('0', $verifyData['drawFee']);

        foreach ($verifyData as $key => $value) {
            if ($key != 'signature') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        openssl_sign($encodeStr, $veirfySign, $iShangRsa->getRsaPrivateKey());
        $this->assertEquals(base64_encode($veirfySign), $verifyData['signature']);
    }

    /**
     * 測試支付異步返回返回未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $iShangRsa = new IShangRsa();
        $iShangRsa->verifyOrderPayment([]);
    }

    /**
     * 測試支付異步返回通知缺少signature
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $returnData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'orderId' => '201801300000000761',
            'amount' => '0.01',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($returnData);
        $iShangRsa->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知驗簽時公鑰為空
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $returnData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
            'signature' => 'test',
            'rsa_public_key' => '',
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($returnData);
        $iShangRsa->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知驗簽時取得公鑰失敗
     */
    public function testReturnGetRsaPublicKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $returnData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
            'signature' => 'test',
            'rsa_public_key' => '123456',
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($returnData);
        $iShangRsa->verifyOrderPayment($entry);
    }


    /**
     * 測試支付異步返回通知sign錯誤
     */
    public function testReturnSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $returnData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
            'signature' => 'test',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'orderId' => '201801300000000761',
            'amount' => '0.01',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($returnData);
        $iShangRsa->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回支付失敗
     */
    public function testReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '0',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '0',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
            'signature' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($returnData);
        $iShangRsa->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
            'signature' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000762',
            'amount' => '0.01',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($returnData);
        $iShangRsa->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
            'signature' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201802060000006543',
            'amount' => '1.01',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($returnData);
        $iShangRsa->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回成功
     */
    public function testReturnSuccess()
    {
        $encodeData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'orderNo' => '202180130000926069',
            'merOrderNo' => '201802060000006543',
            'payAmount' => '100',
            'status' => '1',
            'reqTime' => '2018-02-06 17:06:25',
            'payTime' => '2018-02-06 17:07:17',
            'signType' => 'RSA',
            'version' => '1.0.0',
            'signature' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201802060000006543',
            'amount' => '1',
        ];

        $iShangRsa = new IShangRsa();
        $iShangRsa->setOptions($returnData);
        $iShangRsa->verifyOrderPayment($entry);

        $this->assertEquals('success', $iShangRsa->getMsg());
    }
}
