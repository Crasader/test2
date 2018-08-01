<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\DeBao;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class DeBaoTest extends DurianTestCase
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

        $sourceData = ['number' => ''];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
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

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002056',
            'orderCreateDate' => '2017-11-01 14:58:06',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '99999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
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

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002056',
            'orderCreateDate' => '2017-11-01 14:58:06',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
    }

    /**
     * 測試加密未返回resp_code
     */
    public function testGetEncodeNoReturnRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>200009991001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711010000002058</order_no>' .
            '<order_time>2017-11-01 15:25:39</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=ewaOSdj</qrcode>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>OZsV1f/g CJ7jRC5goN14hPe9uZbFVctW9pnM7nWhR60jf9/6S2PaVIuhhpB5' .
            '968i 8glSQ64/jmg TDRSpUoODpTfAEaWmRZuoakSjsKYxbOKAmvGlgvIRAW63aEng1' .
            'jCBxfZp7tUY3OtiGPmNyRbty3XB58mDvmYjfeGa95dM=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000021896</trade_no>' .
            '<trade_time>2017-11-01 15:25:40</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002058',
            'orderCreateDate' => '2017-11-01 15:25:39',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
    }

    /**
     * 測試加密返回resp_code不為SUCCESS
     */
    public function testGetEncodeReturnRespCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '请输入真实的用户ip',
            180130
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<resp_code>FAIL</resp_code>' .
            '<resp_desc>请输入真实的用户ip</resp_desc>' .
            '<result_code>1</result_code>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002058',
            'orderCreateDate' => '2017-11-01 15:25:39',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
    }

    /**
     * 測試加密未返回result_code
     */
    public function testGetEncodeNoReturnResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>200009991001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711010000002058</order_no>' .
            '<order_time>2017-11-01 15:25:39</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=ewaOSdj</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<sign>OZsV1f/g CJ7jRC5goN14hPe9uZbFVctW9pnM7nWhR60jf9/6S2PaVIuhhpB5' .
            '968i 8glSQ64/jmg TDRSpUoODpTfAEaWmRZuoakSjsKYxbOKAmvGlgvIRAW63aEng1' .
            'jCBxfZp7tUY3OtiGPmNyRbty3XB58mDvmYjfeGa95dM=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000021896</trade_no>' .
            '<trade_time>2017-11-01 15:25:40</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002058',
            'orderCreateDate' => '2017-11-01 15:25:39',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
    }

    /**
     * 測試加密返回result_code不等於0
     */
    public function testGetEncodeReturnResultCodeNotEqualToZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '获取二维码失败',
            180130
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<error_code>GET_QRCODE_FAILED</error_code>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>200009991001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711010000002058</order_no>' .
            '<order_time>2017-11-01 15:25:39</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '<sign>OZsV1f/g CJ7jRC5goN14hPe9uZbFVctW9pnM7nWhR60jf9/6S2PaVIuhhpB5' .
            '968i 8glSQ64/jmg TDRSpUoODpTfAEaWmRZuoakSjsKYxbOKAmvGlgvIRAW63aEng1' .
            'jCBxfZp7tUY3OtiGPmNyRbty3XB58mDvmYjfeGa95dM=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002058',
            'orderCreateDate' => '2017-11-01 15:25:39',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
    }

    /**
     * 測試加密返回result_code不等於0，且沒有返回result_desc
     */
    public function testGetEncodeReturnResultCodeNotEqualToZeroAndNoResultDesc()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<error_code>GET_QRCODE_FAILED</error_code>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>200009991001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711010000002058</order_no>' .
            '<order_time>2017-11-01 15:25:39</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<sign>OZsV1f/g CJ7jRC5goN14hPe9uZbFVctW9pnM7nWhR60jf9/6S2PaVIuhhpB5' .
            '968i 8glSQ64/jmg TDRSpUoODpTfAEaWmRZuoakSjsKYxbOKAmvGlgvIRAW63aEng1' .
            'jCBxfZp7tUY3OtiGPmNyRbty3XB58mDvmYjfeGa95dM=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002058',
            'orderCreateDate' => '2017-11-01 15:25:39',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
    }

    /**
     * 測試加密未返回qrcode
     */
    public function testGetEncodeNoReturnQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>200009991001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711010000002058</order_no>' .
            '<order_time>2017-11-01 15:25:39</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>OZsV1f/g CJ7jRC5goN14hPe9uZbFVctW9pnM7nWhR60jf9/6S2PaVIuhhpB5' .
            '968i 8glSQ64/jmg TDRSpUoODpTfAEaWmRZuoakSjsKYxbOKAmvGlgvIRAW63aEng1' .
            'jCBxfZp7tUY3OtiGPmNyRbty3XB58mDvmYjfeGa95dM=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000021896</trade_no>' .
            '<trade_time>2017-11-01 15:25:40</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002058',
            'orderCreateDate' => '2017-11-01 15:25:39',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPayWithOnlineBank()
    {
        $encodeStr = 'bank_code=ICBC&client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.0' .
            '&merchant_code=200009991001&notify_url=http://pay.my/pay/return.php&order_amount=0.01' .
            '&order_no=201711010000002058&order_time=2017-11-01 15:25:39&product_name=php1test&service_type=direct_pay';

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002058',
            'orderCreateDate' => '2017-11-01 15:25:39',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $requestData = $deBao->getVerifyData();

        $postUrl = 'https://pay.' . $sourceData['postUrl'] . '/gateway?input_charset=UTF-8';
        $this->assertEquals('ICBC', $requestData['params']['bank_code']);
        $this->assertEquals('V3.0', $requestData['params']['interface_version']);
        $this->assertEquals('direct_pay', $requestData['params']['service_type']);
        $this->assertEquals('200009991001', $requestData['params']['merchant_code']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['params']['notify_url']);
        $this->assertEquals('UTF-8', $requestData['params']['input_charset']);
        $this->assertEquals('RSA-S', $requestData['params']['sign_type']);
        $this->assertEquals('201711010000002058', $requestData['params']['order_no']);
        $this->assertEquals('2017-11-01 15:25:39', $requestData['params']['order_time']);
        $this->assertEquals('0.01', $requestData['params']['order_amount']);
        $this->assertEquals('php1test', $requestData['params']['product_name']);
        $this->assertEquals($postUrl, $requestData['post_url']);
        $this->assertEquals(base64_encode($sign), $requestData['params']['sign']);
        $this->assertEquals($sourceData['amount'], $requestData['params']['order_amount']);
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>200009991001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711010000002058</order_no>' .
            '<order_time>2017-11-01 15:25:39</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=ewaOSdj</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>OZsV1f/g CJ7jRC5goN14hPe9uZbFVctW9pnM7nWhR60jf9/6S2PaVIuhhpB5' .
            '968i 8glSQ64/jmg TDRSpUoODpTfAEaWmRZuoakSjsKYxbOKAmvGlgvIRAW63aEng1' .
            'jCBxfZp7tUY3OtiGPmNyRbty3XB58mDvmYjfeGa95dM=</sign><sign_type>RSA-S</sign_type>' .
            '<trade_no>1000021896</trade_no>' .
            '<trade_time>2017-11-01 15:25:40</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200009991001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711010000002058',
            'orderCreateDate' => '2017-11-01 15:25:39',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $encodeData = $deBao->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=ewaOSdj', $deBao->getQrcode());
    }

    /**
     * 測試手機支未返回payURL
     */
    public function testPhonePayReturnWithoutPayURL()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response><interface_version>V3.3</interface_version>' .
            '<merchant_code>200004001003</merchant_code>' .
            '<order_amount>5.0o0</order_amount>' .
            '<order_no>201804250000011376</order_no>' .
            '<order_time>2018-04-25 12:26:33</order_time>' .
            '<resp_code>SUCCESS</resp_code><resp_desc>通讯成功</resp_desc><result_code>0</result_code>' .
            '<sign>bWOfPuwgdeC6FL6a0F6VoVN76AdEGTVur1tZSsiNMyxdK3uIzRVPzlTqZAkU2dVg/ulZi/D0yV38oh4VGZ' .
            's6SB0eBoO1SdqvRS4SwZZgGdWAIOu4pQMl0Vhtjy4WXwQoeOCGFKw/hzcC1GdKrfFTJwlppMNfAZKOpDBOoPiuVnLA=</sign>' .
            '<sign_type>RSA-S</sign_type><trade_no>1001361459</trade_no>' .
            '<trade_time>2018-04-25: 12:26:37</trade_time></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200004001003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201804250000011376',
            'orderCreateDate' => '2018-04-25 11:25:39',
            'amount' => '5',
            'username' => 'php1test',
            'paymentVendorId' => '1098',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $encodeData = $deBao->getVerifyData();

        $this->assertEquals('https://pay.debaozhifu.com/FormH5ApiPay', $encodeData['post_url']);
        $this->assertEquals('100136145970572064a59c475eae1e9989eec8883c6', $encodeData['params']['tokenId']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response><interface_version>V3.3</interface_version>' .
            '<merchant_code>200004001003</merchant_code>' .
            '<order_amount>5.0o0</order_amount>' .
            '<order_no>201804250000011376</order_no>' .
            '<order_time>2018-04-25 12:26:33</order_time><payURL>' .
            'https://pay.debaozhifu.com/FormH5ApiPay?tokenId=100136145970572064a59c475eae1e9989eec8883c6</payURL>' .
            '<resp_code>SUCCESS</resp_code><resp_desc>通讯成功</resp_desc><result_code>0</result_code>' .
            '<sign>bWOfPuwgdeC6FL6a0F6VoVN76AdEGTVur1tZSsiNMyxdK3uIzRVPzlTqZAkU2dVg/ulZi/D0yV38oh4VGZ' .
            's6SB0eBoO1SdqvRS4SwZZgGdWAIOu4pQMl0Vhtjy4WXwQoeOCGFKw/hzcC1GdKrfFTJwlppMNfAZKOpDBOoPiuVnLA=</sign>' .
            '<sign_type>RSA-S</sign_type><trade_no>1001361459</trade_no>' .
            '<trade_time>2018-04-25: 12:26:37</trade_time></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '200004001003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201804250000011376',
            'orderCreateDate' => '2018-04-25 11:25:39',
            'amount' => '5',
            'username' => 'php1test',
            'paymentVendorId' => '1098',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'debaozhifu.com',
            'ip' => '111.235.135.54',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $encodeData = $deBao->getVerifyData();

        $this->assertEquals('https://pay.debaozhifu.com/FormH5ApiPay', $encodeData['post_url']);
        $this->assertEquals('100136145970572064a59c475eae1e9989eec8883c6', $encodeData['params']['tokenId']);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithoutTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $deBao = new DeBao();
        $deBao->verifyOrderPayment([]);
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

        $sourceData = [
            'trade_no' => '1000021877',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '200009991001',
            'order_no' => '201711010000002056',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1084621322',
            'order_time' => '2017-11-01 14:58:06',
            'notify_id' => 'b9d4541fa2d245d1bb1245f13eb0f478',
            'trade_time' => '2017-11-01 14:58:11',
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->verifyOrderPayment([]);
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

        $sourceData = [
            'trade_no' => '1000021877',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '200009991001',
            'order_no' => '201711010000002056',
            'trade_status' => 'SUCCESS',
            'sign' => 'd50db187f2a36b1649080a1529519594',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1084621322',
            'order_time' => '2017-11-01 14:58:06',
            'notify_id' => 'b9d4541fa2d245d1bb1245f13eb0f478',
            'trade_time' => '2017-11-01 14:58:11',
            'rsa_public_key' => $this->publicKey,
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->verifyOrderPayment([]);
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

        $sourceData = [
            'trade_no' => '1000021877',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '200009991001',
            'order_no' => '201711010000002056',
            'trade_status' => 'SUCCESS',
            'sign' => 'd50db187f2a36b1649080a1529519594',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1084621322',
            'order_time' => '2017-11-01 14:58:06',
            'notify_id' => 'b9d4541fa2d245d1bb1245f13eb0f478',
            'trade_time' => '2017-11-01 14:58:11',
            'rsa_public_key' => '',
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->verifyOrderPayment([]);
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

        $sourceData = [
            'trade_no' => '1000021877',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '200009991001',
            'order_no' => '201711010000002056',
            'trade_status' => 'SUCCESS',
            'sign' => 'd50db187f2a36b1649080a1529519594',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1084621322',
            'order_time' => '2017-11-01 14:58:06',
            'notify_id' => 'b9d4541fa2d245d1bb1245f13eb0f478',
            'trade_time' => '2017-11-01 14:58:11',
            'rsa_public_key' => '123',
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=C1084621322&interface_version=V3.0&merchant_code=200009991001&' .
            'notify_id=b9d4541fa2d245d1bb1245f13eb0f478&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201711010000002056&order_time=2017-11-01 14:58:06&trade_no=1000021877&' .
            'trade_status=FAILURE&trade_time=2017-11-01 14:58:11';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1000021877',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '200009991001',
            'order_no' => '201711010000002056',
            'trade_status' => 'FAILURE',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1084621322',
            'order_time' => '2017-11-01 14:58:06',
            'notify_id' => 'b9d4541fa2d245d1bb1245f13eb0f478',
            'trade_time' => '2017-11-01 14:58:11',
            'rsa_public_key' => $this->publicKey,
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=C1084621322&interface_version=V3.0&merchant_code=200009991001&' .
            'notify_id=b9d4541fa2d245d1bb1245f13eb0f478&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201711010000002056&order_time=2017-11-01 14:58:06&trade_no=1000021877&' .
            'trade_status=SUCCESS&trade_time=2017-11-01 14:58:11';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1000021877',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '200009991001',
            'order_no' => '201711010000002056',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1084621322',
            'order_time' => '2017-11-01 14:58:06',
            'notify_id' => 'b9d4541fa2d245d1bb1245f13eb0f478',
            'trade_time' => '2017-11-01 14:58:11',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201711010000002057',
            'amount' => '0.01',
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->verifyOrderPayment($entry);
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

        $encodeStr = 'bank_seq_no=C1084621322&interface_version=V3.0&merchant_code=200009991001&' .
            'notify_id=b9d4541fa2d245d1bb1245f13eb0f478&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201711010000002056&order_time=2017-11-01 14:58:06&trade_no=1000021877&' .
            'trade_status=SUCCESS&trade_time=2017-11-01 14:58:11';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1000021877',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '200009991001',
            'order_no' => '201711010000002056',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1084621322',
            'order_time' => '2017-11-01 14:58:06',
            'notify_id' => 'b9d4541fa2d245d1bb1245f13eb0f478',
            'trade_time' => '2017-11-01 14:58:11',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201711010000002056',
            'amount' => '0.05',
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $encodeStr = 'bank_seq_no=C1084621322&interface_version=V3.0&merchant_code=200009991001&' .
            'notify_id=b9d4541fa2d245d1bb1245f13eb0f478&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201711010000002056&order_time=2017-11-01 14:58:06&trade_no=1000021877&' .
            'trade_status=SUCCESS&trade_time=2017-11-01 14:58:11';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1000021877',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '200009991001',
            'order_no' => '201711010000002056',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1084621322',
            'order_time' => '2017-11-01 14:58:06',
            'notify_id' => 'b9d4541fa2d245d1bb1245f13eb0f478',
            'trade_time' => '2017-11-01 14:58:11',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201711010000002056',
            'amount' => '0.01',
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $deBao->getMsg());
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

        $deBao = new DeBao();
        $deBao->paymentTracking();
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
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
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

        $privateKey = '';

        // Get private key
        openssl_pkey_export($res, $privateKey);

        $deBao = new DeBao();

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => base64_encode($privateKey),
        ];

        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com'
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>F</is_success>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com'
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數trade
     */
    public function testPaymentTrackingResultWithoutTrade()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response><is_success>T</is_success></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com'
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201711280000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com'
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign></sign>' .
            '<trade><merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201711280000002345',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com'
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
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

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=201711280000002106' .
            '&order_time=2017-11-28 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2017-11-28 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-11-28 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-11-28 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com'
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
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

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=201711280000002106' .
            '&order_time=2017-11-28 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2017-11-28 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-11-28 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2017-11-28 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com'
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=201711280000002106' .
            '&order_time=2017-11-28 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2017-11-28 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-11-28 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-11-28 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'amount' => '0.01'
        ];

        $deBao = new DeBao();
        $deBao->setContainer($this->container);
        $deBao->setClient($this->client);
        $deBao->setResponse($response);
        $deBao->setOptions($sourceData);
        $deBao->paymentTracking();
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

        $deBao = new DeBao();
        $deBao->getPaymentTrackingData();
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

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com'
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $trackingData = $deBao->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/query', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response></response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢失敗
     */
    public function testPaymentTrackingVerifyFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>F</is_success>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數trade
     */
    public function testPaymentTrackingVerifyWithoutTrade()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response><is_success>T</is_success></response></dinpay>';

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201711280000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result ,
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201711280000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign></sign>' .
            '<trade><merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201711280000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=201711280000002106' .
            '&order_time=2017-11-28 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2017-11-28 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-11-28 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-11-28 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017112800000021066',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付金額錯誤
     */
    public function testPaymentTrackingVerifyWithPayAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=201711280000002106' .
            '&order_time=2017-11-28 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2017-11-28 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-11-28 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-11-28 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'amount' => '0.02',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=201711280000002106' .
            '&order_time=2017-11-28 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2017-11-28 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-11-28 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-11-28 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=201711280000002106' .
            '&order_time=2017-11-28 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2017-11-28 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-11-28 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2017-11-28 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=201711280000002106' .
            '&order_time=2017-11-28 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2017-11-28 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201711280000002106</order_no>' .
            '<order_time>2017-11-28 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-11-28 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.debaozhifu.com',
            'amount' => '0.01',
            'content' => $result
        ];

        $deBao = new DeBao();
        $deBao->setOptions($sourceData);
        $deBao->paymentTrackingVerify();
    }
}
