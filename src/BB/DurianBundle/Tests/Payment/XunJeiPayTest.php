<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\XunJeiPay;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class XunJeiPayTest extends WebTestCase
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
     * 測試QQ二維支付時缺少verify_url
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201805140000011527',
            'orderCreateDate' => '2018-05-14 09:11:22',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setPrivateKey('test');
        $xunJeiPay->setOptions($options);
        $xunJeiPay->getVerifyData();
    }

    /**
     * 測試QQ二維支付時返回缺少respCode
     */
    public function testQrcodePayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $options = [
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201805140000011527',
            'orderCreateDate' => '2018-05-14 09:11:22',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setPrivateKey('test');
        $xunJeiPay->setContainer($this->container);
        $xunJeiPay->setClient($this->client);
        $xunJeiPay->setResponse($response);
        $xunJeiPay->setOptions($options);
        $xunJeiPay->getVerifyData();
    }

    /**
     * 測試QQ二維支付時返回提交失敗
     */
    public function testQrcodePayReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易结果未知',
            180130
        );

        $result = [
            'respCode' => 'P999',
            'respDesc' => '交易结果未知',
        ];

        $options = [
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201805140000011527',
            'orderCreateDate' => '2018-05-14 09:11:22',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setPrivateKey('test');
        $xunJeiPay->setContainer($this->container);
        $xunJeiPay->setClient($this->client);
        $xunJeiPay->setResponse($response);
        $xunJeiPay->setOptions($options);
        $xunJeiPay->getVerifyData();
    }

    /**
     * 測試QQ二維支付時返回缺少payQRCode
     */
    public function testQrcodePayReturnWithoutPayQRCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respCode' => 'P000',
            'respDesc' => '交易处理中',
        ];

        $options = [
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201805140000011527',
            'orderCreateDate' => '2018-05-14 09:11:22',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setPrivateKey('test');
        $xunJeiPay->setContainer($this->container);
        $xunJeiPay->setClient($this->client);
        $xunJeiPay->setResponse($response);
        $xunJeiPay->setOptions($options);
        $xunJeiPay->getVerifyData();
    }

    /**
     * 測試QQ二維
     */
    public function testQrcodePay()
    {
        $qrCode = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V2c86ce47222a466ffba7f64fa9562a';
        $result = [
            'respCode' => 'P000',
            'respDesc' => '交易处理中',
            'payQRCode' => $qrCode,
        ];

        $options = [
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201805140000011527',
            'orderCreateDate' => '2018-05-14 09:11:22',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setPrivateKey('test');
        $xunJeiPay->setContainer($this->container);
        $xunJeiPay->setClient($this->client);
        $xunJeiPay->setResponse($response);
        $xunJeiPay->setOptions($options);
        $verifyData = $xunJeiPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertEquals($qrCode, $xunJeiPay->getQrcode());
    }

    /**
     * 測試QQ手機支付
     */
    public function testPhonePay()
    {
        $qrCode = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V2c86ce47222a466ffba7f64fa9562a';
        $result = [
            'respCode' => 'P000',
            'respDesc' => '交易处理中',
            'payQRCode' => $qrCode,
        ];

        $options = [
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201805140000011527',
            'orderCreateDate' => '2018-05-14 09:11:22',
            'amount' => '1',
            'paymentVendorId' => '1104',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setPrivateKey('test');
        $xunJeiPay->setContainer($this->container);
        $xunJeiPay->setClient($this->client);
        $xunJeiPay->setResponse($response);
        $xunJeiPay->setOptions($options);
        $verifyData = $xunJeiPay->getVerifyData();

        $this->assertEquals('GET', $xunJeiPay->getPayMethod());
        $this->assertEquals('1027', $verifyData['params']['_wv']);
        $this->assertEquals('2183', $verifyData['params']['_bid']);
        $this->assertEquals('6V2c86ce47222a466ffba7f64fa9562a', $verifyData['params']['t']);
        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html', $verifyData['post_url']);
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

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($sourceData);
        $xunJeiPay->getVerifyData();
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
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201804170000006543',
            'amount' => '1',
            'orderCreateDate' => '2018-04-17 12:26:41',
            'paymentVendorId' => '9911',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($sourceData);
        $xunJeiPay->getVerifyData();
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
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '2018012912345678',
            'amount' => '1',
            'orderCreateDate' => '2018-04-17 12:26:41',
            'paymentVendorId' => '1',
            'rsa_private_key' => $privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($sourceData);
        $xunJeiPay->getVerifyData();
    }

    /**
     * 測試銀聯在線支付
     */
    public function testQuickPay()
    {
        $options = [
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '1',
            'orderCreateDate' => '2018-04-17 12:26:41',
            'paymentVendorId' => '278',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($options);
        $verifyData = $xunJeiPay->getVerifyData();

        $this->assertEquals('1.0.0', $verifyData['version']);
        $this->assertEquals('SALES', $verifyData['transType']);
        $this->assertEquals('0003', $verifyData['productId']);
        $this->assertEquals('850440050945259', $verifyData['merNo']);
        $this->assertEquals('20180417', $verifyData['orderDate']);
        $this->assertEquals('2018012912345678', $verifyData['orderNo']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $verifyData['notifyUrl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $verifyData['returnUrl']);
        $this->assertEquals(100, $verifyData['transAmt']);

        foreach ($verifyData as $key => $value) {
            if ($key != 'signature') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        openssl_sign($encodeStr, $veirfySign, $xunJeiPay->getRsaPrivateKey(), OPENSSL_ALGO_SHA1);
        $this->assertEquals(base64_encode($veirfySign), $verifyData['signature']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => '850440050945259',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '1',
            'orderCreateDate' => '2018-04-17 12:26:41',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($options);
        $verifyData = $xunJeiPay->getVerifyData();

        $this->assertEquals('1.0.0', $verifyData['version']);
        $this->assertEquals('SALES', $verifyData['transType']);
        $this->assertEquals('0001', $verifyData['productId']);
        $this->assertEquals('850440050945259', $verifyData['merNo']);
        $this->assertEquals('20180417', $verifyData['orderDate']);
        $this->assertEquals('2018012912345678', $verifyData['orderNo']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $verifyData['notifyUrl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $verifyData['returnUrl']);
        $this->assertEquals(100, $verifyData['transAmt']);
        $this->assertEquals('ICBC', $verifyData['bankCode']);

        foreach ($verifyData as $key => $value) {
            if ($key != 'signature') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        openssl_sign($encodeStr, $veirfySign, $xunJeiPay->getRsaPrivateKey(), OPENSSL_ALGO_SHA1);
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

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->verifyOrderPayment([]);
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
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0000',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'orderId' => '201801300000000761',
            'amount' => '0.01',
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($returnData);
        $xunJeiPay->verifyOrderPayment($entry);
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
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0000',
            'signature' => 'test',
            'rsa_public_key' => '',
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($returnData);
        $xunJeiPay->verifyOrderPayment($entry);
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
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0000',
            'signature' => 'test',
            'rsa_public_key' => '123456',
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($returnData);
        $xunJeiPay->verifyOrderPayment($entry);
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
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0000',
            'signature' => 'test',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'orderId' => '201801300000000761',
            'amount' => '0.01',
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($returnData);
        $xunJeiPay->verifyOrderPayment($entry);
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
            'respDesc' => '交易失敗',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0001',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'respDesc' => '交易失敗',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0001',
            'signature' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($returnData);
        $xunJeiPay->verifyOrderPayment($entry);
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
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0000',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0000',
            'signature' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000762',
            'amount' => '0.01',
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($returnData);
        $xunJeiPay->verifyOrderPayment($entry);
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
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0000',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '1',
            'orderDate' => '20180417',
            'respCode' => '0000',
            'signature' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201804170000006543',
            'amount' => '1.01',
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($returnData);
        $xunJeiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回成功
     */
    public function testReturnSuccess()
    {
        $encodeData = [
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '100',
            'orderDate' => '20180417',
            'respCode' => '0000',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'respDesc' => '交易成功',
            'orderNo' => '201804170000006543',
            'merNo' => '850440058115321',
            'productId' => '0001',
            'transType' => 'SALES',
            'serialId' => 'O270001223009',
            'transAmt' => '100',
            'orderDate' => '20180417',
            'respCode' => '0000',
            'signature' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201804170000006543',
            'amount' => '1',
        ];

        $xunJeiPay = new XunJeiPay();
        $xunJeiPay->setOptions($returnData);
        $xunJeiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $xunJeiPay->getMsg());
    }
}
