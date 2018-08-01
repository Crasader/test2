<?php

namespace BB\DurianBundle\Test\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShangMaFu;
use Buzz\Message\Response;

class ShangMaFuTest extends DurianTestCase
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
     * @var  \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var  \Buzz\Client\Curl
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
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            '180142'
        );

        $shangMaFu = new ShangMaFu();
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithoutPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試支付時帶入支付平台不支援的銀行
     */
    public function testPayWithoutSupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '99999',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
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
            'private_key_bits' => 507,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privateKey = '';
        // Get private key
        openssl_pkey_export($res, $privateKey);

        // 轉換格式
        $search = [
            '-----BEGIN PRIVATE KEY-----',
            '-----END PRIVATE KEY-----',
            "\n",
        ];
        $privateKey = str_replace($search, '', $privateKey);

        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => base64_encode($privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試加密時取得RSA私鑰為空
     */
    public function testReturnGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試加密時取得RSA私鑰失敗
     */
    public function testReturnGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => '12345',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testOnlinePay()
    {
        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://pay.shangmafu.com/merchantPay/webpay',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $data = $shangMaFu->getVerifyData();

        $sign = '';

        $encodeStr = 'amount=100&bankCode=ICBC&bankType=11&body=php1test&chann' .
            'el=1&mchNo=26101043&notifyUrl=http://59.126.84.197:3030/return.ph' .
            'p&outTradeNo=201705051234&payDate=20180213122600&returnUrl=http://' .
            '59.126.84.197:3030/return.php&signKey=test';

        openssl_sign(md5($encodeStr), $sign, $shangMaFu->getRsaPrivateKey(), OPENSSL_ALGO_SHA1);

        $this->assertEquals('http://pay.shangmafu.com/merchantPay/webpay', $data['post_url']);
        $this->assertEquals($sourceData['number'], $data['params']['mchNo']);
        $this->assertEquals($sourceData['orderId'], $data['params']['outTradeNo']);
        $this->assertEquals(round($sourceData['amount'] * 100), $data['params']['amount']);
        $this->assertEquals($sourceData['username'], $data['params']['body']);
        $this->assertEquals('20180213122600', $data['params']['payDate']);
        $this->assertEquals($sourceData['notify_url'], $data['params']['notifyUrl']);
        $this->assertEquals($sourceData['notify_url'], $data['params']['returnUrl']);
        $this->assertEquals('1', $data['params']['channel']);
        $this->assertEquals('11', $data['params']['bankType']);
        $this->assertEquals('ICBC', $data['params']['bankCode']);
        $this->assertEquals(base64_encode($sign), $data['params']['sign']);
        $this->assertEquals('', $data['params']['remark']);
    }

    /**
     * 測試二維支付對外返回沒有resultCode
     */
    public function testQrcodePayWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.shangmafu.com',
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $result = '{"resultMsg":"\u64cd\u4f5c\u6210\u529f","outTradeNo":"201802130000009449",' .
            '"amount":"100","returnCode":2,"remark":"","qrcode":"weixin:\/\/wxpay\/bizpayurl?pr=yt3rDNB"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setContainer($this->container);
        $shangMaFu->setClient($this->client);
        $shangMaFu->setResponse($response);
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試二維支付對外返回沒有resultMsg
     */
    public function testQrcodePayWithoutResultMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.shangmafu.com',
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $result = '{"resultCode":"00","outTradeNo":"201802130000009449",' .
            '"amount":"100","returnCode":2,"remark":"","qrcode":"weixin:\/\/wxpay\/bizpayurl?pr=yt3rDNB"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setContainer($this->container);
        $shangMaFu->setClient($this->client);
        $shangMaFu->setResponse($response);
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試二維支付取得Qrcode不成功
     */
    public function testQrcodePayGetQrcodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '渠道未开通',
            180130
        );

        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.shangmafu.com',
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $result = '{"resultCode":"01","resultMsg":" \u6e20\u9053\u672a\u5f00\u901a",' .
            '"outTradeNo":"201802130000009451","amount":"100","returnCode":-1,"remar' .
            'k":"","qrcode":"","sign":"k9xzEBQdyWodFQp7oQzsS0le3sC5s7pf iqlfg1Np7 Wl' .
            'YHdmujyq2ozqnpqcb90VrZ8R chY0RF8f1N1cjp9XqRneNKTqYGfekirEqYmhNjA4DBJRLM' .
            'KL53VhNZ1uH F40\/W5PVkaEO1aSBjxzkjZ95xATx3FjgB9CL8H38Sor208CNiu2Xv4DO a' .
            'Wb\/SCMMQT2hr5foradyk8LszdTHZvs5pL1ug8rtT0GOJgQeiEz6w x21lHXzZNlXJfp3ht' .
            'eXjEMpAbQBJqRKVfuAkwe1GcGP1iuJS55djgtT8hS4cK6ckCnNwen1p9zWtyOfUa7s3jvlk7pDJhWVECbLZ4ww=="}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setContainer($this->container);
        $shangMaFu->setClient($this->client);
        $shangMaFu->setResponse($response);
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試二維支付對外返回沒有qrcode
     */
    public function testQrcodePayWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.shangmafu.com',
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $result = '{"resultCode":"00","resultMsg":"\u64cd\u4f5c\u6210\u529f","outTradeNo":"201802130000009449",' .
            '"amount":"100","returnCode":2,"remark":"","sign":"DP8WvgYay5p\/QYzIN5dVyN1z8DlE9BzPlNbXl9felS' .
            'bfO867Cry5vWUDcgANLuN8PlnEo59BYMdQGagCqHQozjZafG\/nSyycpdSluASV JVm8 lc35KdanwVXo7WQURHKAmDaE' .
            'OJJAaecdbOxSuqZo2qOJDRbISCmvbvjFGJmMmEq7pqGxIW2ZVwMWtrfxvfgUaC6PqlN5 G9w5wPaihyp1Lv gCNxZQJCt' .
            'p9Cf74\/dLNiwbQ8JijUpnPOqw9tNTBqqMzrjsKbEmlzQPXvT lZ0K5Ra\/Ebom4bhq9qCaU5ECSX2whLqcCWv7cqg=="}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setContainer($this->container);
        $shangMaFu->setClient($this->client);
        $shangMaFu->setResponse($response);
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $sourceData = [
            'number' => '26101043',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2018-02-13 12:26:00',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.shangmafu.com',
            'postUrl' => 'http://pay.shangmafu.com',
        ];

        $result = '{"resultCode":"00","resultMsg":"\u64cd\u4f5c\u6210\u529f","outTradeNo":"201802130000009449",' .
            '"amount":"100","returnCode":2,"remark":"","qrcode":"weixin:\/\/wxpay\/bizpayurl?pr=yt3rDNB","sign":"D' .
            'o0G8BwVQcP8WvgYay5p\/QYzIN5dVyN1z8DlE9BzPlNbXl9felSbfO867Cry5vWUDcgANLuN8PlnEo59BYMdQGagCqHQozjZafG\/' .
            '54IUcecmG35mowbIlrnSyycpdSluASV JVm8 lc35KdanwVXo7WQURHKAmDaEOJJAaecdbOxSuqZo2qOJDRbISCmvbvjFGJmMmEq7' .
            'pqGxIW2ZVwMWtrfxvfgUaC6PqlN5 G9w5wPaihyp1Lv gCNxZQJCtp9Cf74\/dLNiwbQ8JijUpnPOqw9tNTBqqMzrjsKbEmlzQPXv' .
            'T lZ0K5Ra\/Ebom4bhq9qCaU5ECSX2whLqcCWv7cqg=="}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setContainer($this->container);
        $shangMaFu->setClient($this->client);
        $shangMaFu->setResponse($response);
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $data = $shangMaFu->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=yt3rDNB', $shangMaFu->getQrcode());
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shangMaFu = new ShangMaFu();
        $shangMaFu->verifyOrderPayment([]);
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

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'amount' => '100.0',
            'outTradeNo' => '201802130000009447',
            'remark' => '',
            'resultCode' => '00',
            'resultMsg' => 'æ“ä½œæˆåŠŸ',
            'returnCode' => '2',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->verifyOrderPayment([]);
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
            'amount' => '100.0',
            'outTradeNo' => '201802130000009447',
            'remark' => '',
            'resultCode' => '00',
            'resultMsg' => 'æ“ä½œæˆåŠŸ',
            'returnCode' => '2',
            'sign' => 'q657zGPGYnXBYdoCB2yJV9i0JRIqe8A0XSAtv1JiOyhlYkjdHkEcZey/MfCk+Fvv3' .
                'jgWzlYiZto+8qONFcxCudb2+ioWijAiR4ExA6JRwLyLd59F0Qg1h9jPz8G140HOBobCetNUG' .
                'ipZsZ9O2uEwmed92V8cEKx+f481BK4ONOYsWMhafDFse5DuymO4FoLkROw6Okm/wC+RmYYauI' .
                'o3/Igz1TgI7vzLDQgxU9GuKPfs9qzA40YYQ/1LQPqaL2hvQ3oOcY+o8Tdq60/lbZEJhsqjJ/SE' .
                'xWG1MD9Nn2EHq0TsnnzXaZkrVBThuXYCKw1pMo6fhT7AXBuskPUE3aJtAw==',
            'rsa_public_key' => $this->publicKey,
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->verifyOrderPayment([]);
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
            'amount' => '100.0',
            'outTradeNo' => '201802130000009447',
            'remark' => '',
            'resultCode' => '00',
            'resultMsg' => 'æ“ä½œæˆåŠŸ',
            'returnCode' => '2',
            'sign' => 'q657zGPGYnXBYdoCB2yJV9i0JRIqe8A0XSAtv1JiOyhlYkjdHkEcZey/MfCk+Fvv3' .
                'jgWzlYiZto+8qONFcxCudb2+ioWijAiR4ExA6JRwLyLd59F0Qg1h9jPz8G140HOBobCetNUG' .
                'ipZsZ9O2uEwmed92V8cEKx+f481BK4ONOYsWMhafDFse5DuymO4FoLkROw6Okm/wC+RmYYauI' .
                'o3/Igz1TgI7vzLDQgxU9GuKPfs9qzA40YYQ/1LQPqaL2hvQ3oOcY+o8Tdq60/lbZEJhsqjJ/SE' .
                'xWG1MD9Nn2EHq0TsnnzXaZkrVBThuXYCKw1pMo6fhT7AXBuskPUE3aJtAw==',
            'rsa_public_key' => '',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->verifyOrderPayment([]);
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
            'amount' => '100.0',
            'outTradeNo' => '201802130000009447',
            'remark' => '',
            'resultCode' => '00',
            'resultMsg' => 'æ“ä½œæˆåŠŸ',
            'returnCode' => '2',
            'sign' => 'q657zGPGYnXBYdoCB2yJV9i0JRIqe8A0XSAtv1JiOyhlYkjdHkEcZey/MfCk+Fvv3' .
                'jgWzlYiZto+8qONFcxCudb2+ioWijAiR4ExA6JRwLyLd59F0Qg1h9jPz8G140HOBobCetNUG' .
                'ipZsZ9O2uEwmed92V8cEKx+f481BK4ONOYsWMhafDFse5DuymO4FoLkROw6Okm/wC+RmYYauI' .
                'o3/Igz1TgI7vzLDQgxU9GuKPfs9qzA40YYQ/1LQPqaL2hvQ3oOcY+o8Tdq60/lbZEJhsqjJ/SE' .
                'xWG1MD9Nn2EHq0TsnnzXaZkrVBThuXYCKw1pMo6fhT7AXBuskPUE3aJtAw==',
            'rsa_public_key' => '123',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->verifyOrderPayment([]);
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

        $content = base64_decode($this->privateKey);

        $privateKey = openssl_pkey_get_private($content);

        $encodeStr = 'amount=100.0&outTradeNo=201802130000009447&remark=&resultCode=00&' .
            'resultMsg=æ“ä½œæˆåŠŸ&returnCode=-1&signKey=test';

        $encode = md5($encodeStr);

        $sign = '';

        openssl_sign($encode, $sign, $privateKey, OPENSSL_ALGO_SHA1);

        $sourceData = [
            'amount' => '100.0',
            'outTradeNo' => '201802130000009447',
            'remark' => '',
            'resultCode' => '00',
            'resultMsg' => 'æ“ä½œæˆåŠŸ',
            'returnCode' => '-1',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = base64_decode($this->privateKey);

        $privateKey = openssl_pkey_get_private($content);

        $encodeStr = 'amount=100.0&outTradeNo=201802130000009447&remark=&resultCode=00&' .
            'resultMsg=æ“ä½œæˆåŠŸ&returnCode=2&signKey=test';

        $encode = md5($encodeStr);

        $sign = '';

        openssl_sign($encode, $sign, $privateKey, OPENSSL_ALGO_SHA1);

        $sourceData = [
            'amount' => '100.0',
            'outTradeNo' => '201802130000009447',
            'remark' => '',
            'resultCode' => '00',
            'resultMsg' => 'æ“ä½œæˆåŠŸ',
            'returnCode' => '2',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = ['id' => '2014052200123'];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = base64_decode($this->privateKey);

        $privateKey = openssl_pkey_get_private($content);

        $encodeStr = 'amount=100.0&outTradeNo=201802130000009447&remark=&resultCode=00&' .
            'resultMsg=æ“ä½œæˆåŠŸ&returnCode=2&signKey=test';

        $encode = md5($encodeStr);

        $sign = '';

        openssl_sign($encode, $sign, $privateKey, OPENSSL_ALGO_SHA1);

        $sourceData = [
            'amount' => '100.0',
            'outTradeNo' => '201802130000009447',
            'remark' => '',
            'resultCode' => '00',
            'resultMsg' => 'æ“ä½œæˆåŠŸ',
            'returnCode' => '2',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = [
            'id' => '201802130000009447',
            'amount' => '50.0',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付驗證成功
     */
    public function testPaySuccess()
    {
        $content = base64_decode($this->privateKey);

        $privateKey = openssl_pkey_get_private($content);

        $encodeStr = 'amount=100.0&outTradeNo=201802130000009447&remark=&resultCode=00&' .
            'resultMsg=æ“ä½œæˆåŠŸ&returnCode=2&signKey=test';

        $encode = md5($encodeStr);

        $sign = '';

        openssl_sign($encode, $sign, $privateKey, OPENSSL_ALGO_SHA1);

        $sourceData = [
            'amount' => '100.0',
            'outTradeNo' => '201802130000009447',
            'remark' => '',
            'resultCode' => '00',
            'resultMsg' => 'æ“ä½œæˆåŠŸ',
            'returnCode' => '2',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $entry = [
            'id' => '201802130000009447',
            'amount' => '1.00',
        ];

        $shangMaFu = new ShangMaFu();
        $shangMaFu->setPrivateKey('test');
        $shangMaFu->setOptions($sourceData);
        $shangMaFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $shangMaFu->getMsg());
    }
}
