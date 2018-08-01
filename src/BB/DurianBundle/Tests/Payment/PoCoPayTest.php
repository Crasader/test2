<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\PoCoPay;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class PoCoPayTest extends WebTestCase
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

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($sourceData);
        $poCoPay->getVerifyData();
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
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201802060000006543',
            'amount' => '1',
            'paymentVendorId' => '9911',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($sourceData);
        $poCoPay->getVerifyData();
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
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($sourceData);
        $poCoPay->getVerifyData();
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

        $sourceData = [
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => '',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($sourceData);
        $poCoPay->getVerifyData();
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

        $sourceData = [
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => base64_decode($this->publicKey),
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($sourceData);
        $poCoPay->getVerifyData();
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
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($sourceData);
        $poCoPay->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少errcode
     */
    public function testPayParameterWithoutErrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $content = '{}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $poCoPay = new PoCoPay();
        $poCoPay->setContainer($this->container);
        $poCoPay->setClient($this->client);
        $poCoPay->setOptions($options);
        $poCoPay->setResponse($response);
        $poCoPay->getVerifyData();
    }

    /**
     * 測試支付對外返回失敗
     */
    public function testPayParameterFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '付款方式编号不合法',
            180130
        );

        $options = [
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $content = '{"errcode":75005,"msg":"付款方式编号不合法","data":"","sign":""}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $poCoPay = new PoCoPay();
        $poCoPay->setContainer($this->container);
        $poCoPay->setClient($this->client);
        $poCoPay->setOptions($options);
        $poCoPay->setResponse($response);
        $poCoPay->getVerifyData();
    }

    /**
     * 測試支付對外返回失敗沒有Msg
     */
    public function testPayParameterFailureWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $content = '{"errcode":75005,"data":"","sign":""}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $poCoPay = new PoCoPay();
        $poCoPay->setContainer($this->container);
        $poCoPay->setClient($this->client);
        $poCoPay->setOptions($options);
        $poCoPay->setResponse($response);
        $poCoPay->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少out_pay_url
     */
    public function testPayParameterWithoutOutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $content = '{"errcode":0,"msg":"操作成功","data":{"merchant_no":"1180522165157574",' .
            '"order_sn":"8060000866180709115710287671","merchant_order_sn":"201807090000014988",' .
            '"trade_amount":"100","result_code":"SUCCESS"},"sign":"MP7uHhRp2dr76QSWGYIoXWBWqK7h/' .
            'VSK0gpHmmoHUdN1q8S7LCkKDPdgWpahnVqNAPVlzRCB93hwAQfrIOCvjLQcEjM0RcfNvZYK88LYnM9PpxqV' .
            'uL02t3TbILiUtW6zpNNU ItZQbwr1rqsvu9X6uVj1eXenL7UL7DeaOZYL7o="}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $poCoPay = new PoCoPay();
        $poCoPay->setContainer($this->container);
        $poCoPay->setClient($this->client);
        $poCoPay->setOptions($options);
        $poCoPay->setResponse($response);
        $poCoPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '1180522165157574',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201807090000014983',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['partner_id' => '10803866000000325634'],
        ];

        $content = '{"errcode":0,"msg":"操作成功","data":{"merchant_no":"1180522165157574",' .
            '"order_sn":"8060000866180709115710287671","merchant_order_sn":"201807090000014988",' .
            '"trade_amount":"100","out_pay_url":"https://myun.tenpay.com/mqq/pay/qrcode.html?_wv' .
            '=1027&_bid=2183&t=6Va4609ad9da44f1b5e9ab716250867e","result_code":"SUCCESS"},"sign"' .
            ':"MP7uHhRp2dr76QSWGYIoXWBWqK7h/VSK0gpHmmoHUdN1q8S7LCkKDPdgWpahnVqNAPVlzRCB93hwAQfrI' .
            'OCvjLQcEjM0RcfNvZYK88LYnM9PpxqVuL02t3TbILiUtW6zpNNU ItZQbwr1rqsvu9X6uVj1eXenL7UL7De' .
            'aOZYL7o="}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $poCoPay = new PoCoPay();
        $poCoPay->setContainer($this->container);
        $poCoPay->setClient($this->client);
        $poCoPay->setOptions($options);
        $poCoPay->setResponse($response);
        $data = $poCoPay->getVerifyData();

        $qrcode = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Va4609ad9da44f1b5e9ab716250867e';

        $this->assertEmpty($data);
        $this->assertEquals($qrcode, $poCoPay->getQrcode());
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

        $poCoPay = new PoCoPay();
        $poCoPay->verifyOrderPayment([]);
    }

    /**
     * 測試支付異步返回通知缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $returnData = [
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
            'rsa_public_key' => $this->publicKey,
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($returnData);
        $poCoPay->verifyOrderPayment([]);
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
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
            'sign' => 'test',
            'rsa_public_key' => '',
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($returnData);
        $poCoPay->verifyOrderPayment([]);
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
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
            'sign' => 'test',
            'rsa_public_key' => '123456',
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($returnData);
        $poCoPay->verifyOrderPayment([]);
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
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
            'sign' => 'test',
            'rsa_public_key' => $this->publicKey,
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($returnData);
        $poCoPay->verifyOrderPayment([]);
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
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '0',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '0',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($returnData);
        $poCoPay->verifyOrderPayment([]);
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
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = ['id' => '201801300000000762'];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($returnData);
        $poCoPay->verifyOrderPayment($entry);
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
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201807090000014983',
            'amount' => '1.01',
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($returnData);
        $poCoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回成功
     */
    public function testReturnSuccess()
    {
        $encodeData = [
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'merchant_no' => '1180522165157574',
            'merchant_order_sn' => '201807090000014983',
            'order_sn' => '8060000866180709103630614569',
            'trade_amount' => '100',
            'paychannel_type' => 'qq_qrcode',
            'pay_status' => '1',
            'pay_time' => '1531103826',
            'rand_str' => '26A41567C3787E758886E781EE83C227',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201807090000014983',
            'amount' => '1',
        ];

        $poCoPay = new PoCoPay();
        $poCoPay->setOptions($returnData);
        $poCoPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $poCoPay->getMsg());
    }
}
