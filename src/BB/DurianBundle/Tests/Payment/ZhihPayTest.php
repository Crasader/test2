<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhihPay;
use Buzz\Message\Response;

class ZhihPayTest extends DurianTestCase
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

        $zhihPay = new ZhihPay();

        $sourceData = ['number' => ''];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
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

        $zhihPay = new ZhihPay();

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '99999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
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

        $zhihPay = new ZhihPay();

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
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

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_desc>成功</resp_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=MtTpobS</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhihPay->setResponse($response);

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
    }

    /**
     * 測試加密返回resp_code不為SUCCESS
     */
    public function testGetEncodeReturnRespCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商家订单号太长',
            180130
        );

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>OREDER_NO_IS_TOO_LONG</resp_code>' .
            '<resp_desc>商家订单号太长</resp_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhihPay->setResponse($response);

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111444444444',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
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

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>成功</resp_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=MtTpobS</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhihPay->setResponse($response);

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
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

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhihPay->setResponse($response);

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
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

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhihPay->setResponse($response);

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
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

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<result_desc>获取二维码成功</result_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhihPay->setResponse($response);

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPayWithOnlineBank()
    {
        $encodeStr = 'bank_code=ICBC&client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.0' .
            '&merchant_code=6000016833&notify_url=http://59.126.84.197/return.php&order_amount=0.01' .
            '&order_no=2017041111111&order_time=2017-05-10 16:48:41&product_name=php1test&service_type=direct_pay';

        $sourceData = [
            'number' => '6000016833',
            'notify_url' => 'http://59.126.84.197/return.php',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-05-10 16:48:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zhihpay.com',
            'ip' => '111.235.135.54',
        ];

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $requestData = $zhihPay->getVerifyData();

        $postUrl = 'https://pay.' . $sourceData['postUrl'] . '/gateway?input_charset=UTF-8';
        $this->assertEquals('ICBC', $requestData['params']['bank_code']);
        $this->assertEquals('V3.0', $requestData['params']['interface_version']);
        $this->assertEquals('direct_pay', $requestData['params']['service_type']);
        $this->assertEquals('6000016833', $requestData['params']['merchant_code']);
        $this->assertEquals('http://59.126.84.197/return.php', $requestData['params']['notify_url']);
        $this->assertEquals('UTF-8', $requestData['params']['input_charset']);
        $this->assertEquals('RSA-S', $requestData['params']['sign_type']);
        $this->assertEquals('2017041111111', $requestData['params']['order_no']);
        $this->assertEquals('2017-05-10 16:48:41', $requestData['params']['order_time']);
        $this->assertEquals('0.01', $requestData['params']['order_amount']);
        $this->assertEquals('php1test', $requestData['params']['product_name']);
        $this->assertEquals($postUrl, $requestData['post_url']);
        $this->assertEquals(base64_encode($sign), $requestData['params']['sign']);
        $this->assertEquals($sourceData['amount'], $requestData['params']['order_amount']);
    }

    /**
     * 測試微信手機支付payUrl格式不正確
     */
    public function testPayWithWeixinWapPayUrlFormatError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $url = 'htt://statecheck.swiftpass.cn';
        $url = urlencode($url);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>mMvPA7W7exkMDFIn/xOSkHyB8cAjXvnlEvrDL1AR3ZAepiBjsEXThN31T TUlq8ugvv9IfPm/wuj' .
            'yfc12aU1VvDVYeVnO h7lkSgcta3d/J857DxLrbdYY /elAD uHxLEDWKBlqwqZHI/I4w1uv6LHvUUbdvlSIIc0uFS1YX9Y=</sign>' .
            '<payURL>' . $url . '</payURL>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $encodeData = $zhihPay->getVerifyData();
    }

    /**
     * 測試微信手機支付
     */
    public function testWeixinPhonePay()
    {
        $url = 'https://statecheck.swiftpass.cn/pay/wappay?token_id=171d6c' .
            '404a882ecbbf774066016cfc985&service=pay.weixin.wappayv2';
        $url = urlencode($url);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>mMvPA7W7exkMDFIn/xOSkHyB8cAjXvnlEvrDL1AR3ZAepiBjsEXThN31T TUlq8ugvv9IfPm/wuj' .
            'yfc12aU1VvDVYeVnO h7lkSgcta3d/J857DxLrbdYY /elAD uHxLEDWKBlqwqZHI/I4w1uv6LHvUUbdvlSIIc0uFS1YX9Y=</sign>' .
            '<payURL>' . $url . '</payURL>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $encodeData = $zhihPay->getVerifyData();

        $this->assertEquals('https://statecheck.swiftpass.cn/pay/wappay', $encodeData['post_url']);
        $this->assertEquals('171d6c404a882ecbbf774066016cfc985', $encodeData['params']['token_id']);
        $this->assertEquals('pay.weixin.wappayv2', $encodeData['params']['service']);
    }

    /**
     * 測試掃碼支付需重新定向
     */
    public function testPayWithIsRedirect()
    {
        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'ip' => '111.235.135.54',
        ];

        $url = 'https://openapi.alipay.com/gateway.do?alipay_sdk=alipay-sdk-java-dynamicVersionNo';

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<isRedirect>Y</isRedirect>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '<qrcode>' . $url . '</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $encodeData = $zhihPay->getVerifyData();

        $this->assertEquals($url, $encodeData['post_url']);
        $this->assertEmpty($encodeData['params']);
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<result_desc>获取二维码成功</result_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=MtTpobS</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhihPay->setResponse($response);

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745',
            'orderId' => '2017041111111',
            'orderCreateDate' => '2017-04-11 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.zhihpay.com',
            'merchantId' => '123456789',
            'ip' => '111.235.135.54',
        ];

        $zhihPay->setOptions($sourceData);
        $encodeData = $zhihPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=MtTpobS', $zhihPay->getQrcode());
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

        $zhihPay = new ZhihPay();

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1003450919',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111130200',
            'order_no' => '201704190000001913',
            'sign' => '080a1529519594d50db187f2a36b1649',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000005221224275',
            'order_time' => '2017-04-19 08:59:10',
            'notify_id' => '6449d835356847458ab8c21f3381be10',
            'trade_time' => '2017-04-19 08:59:13'
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->verifyOrderPayment([]);
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

        $zhihPay = new ZhihPay();

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1003450919',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111130200',
            'order_no' => '2014052200001',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000005221224275',
            'order_time' => '2014-05-22 09:30:11',
            'notify_id' => '6449d835356847458ab8c21f3381be10',
            'trade_time' => '2014-05-22 09:31:31'
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->verifyOrderPayment([]);
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

        $zhihPay = new ZhihPay();

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1003450919',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111130200',
            'order_no' => '2014052200001',
            'trade_status' => 'SUCCESS',
            'sign' => 'd50db187f2a36b1649080a1529519594',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000005221224275',
            'order_time' => '2014-05-22 09:30:11',
            'notify_id' => '6449d835356847458ab8c21f3381be10',
            'trade_time' => '2014-05-22 09:31:31',
            'rsa_public_key' => $this->publicKey,
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->verifyOrderPayment([]);
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

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($options);
        $zhihPay->verifyOrderPayment([]);
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

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($options);
        $zhihPay->verifyOrderPayment([]);
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

        $zhihPay = new ZhihPay();

        $encodeStr = 'bank_seq_no=7551102936201603310273064491&interface_version=V3.0&merchant_code=1111110166&' .
            'notify_id=5b9b67ecf6be4346ab738dddf9127c62&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201603310000004830&order_time=2016-03-25+16%3A35%3A06&trade_no=1125060594&trade_status=UNPAY&' .
            'trade_time=2016-03-31+16%3A14%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1125060594',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111110166',
            'order_no' => '201603310000004830',
            'trade_status' => 'UNPAY',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7551102936201603310273064491',
            'order_time' => '2016-03-25 16:35:06',
            'notify_id' => '5b9b67ecf6be4346ab738dddf9127c62',
            'trade_time' => '2016-03-31 16:14:02',
            'rsa_public_key' => $this->publicKey,
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->verifyOrderPayment([]);
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

        $zhihPay = new ZhihPay();

        $encodeStr = 'bank_seq_no=7551102936201603310273064491&interface_version=V3.0&merchant_code=1111110166&' .
            'notify_id=5b9b67ecf6be4346ab738dddf9127c62&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201603310000004830&order_time=2016-03-25+16%3A35%3A06&trade_no=1125060594&trade_status=SUCCESS&' .
            'trade_time=2016-03-31+16%3A14%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1125060594',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111110166',
            'order_no' => '201603310000004830',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7551102936201603310273064491',
            'order_time' => '2016-03-25 16:35:06',
            'notify_id' => '5b9b67ecf6be4346ab738dddf9127c62',
            'trade_time' => '2016-03-31 16:14:02',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = ['id' => '2014052200123'];

        $zhihPay->setOptions($sourceData);
        $zhihPay->verifyOrderPayment($entry);
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

        $zhihPay = new ZhihPay();

        $encodeStr = 'bank_seq_no=7551102936201603310273064491&interface_version=V3.0&merchant_code=1111110166&' .
            'notify_id=5b9b67ecf6be4346ab738dddf9127c62&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201603310000004830&order_time=2016-03-25+16%3A35%3A06&trade_no=1125060594&trade_status=SUCCESS&' .
            'trade_time=2016-03-31+16%3A14%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1125060594',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111110166',
            'order_no' => '201603310000004830',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7551102936201603310273064491',
            'order_time' => '2016-03-25 16:35:06',
            'notify_id' => '5b9b67ecf6be4346ab738dddf9127c62',
            'trade_time' => '2016-03-31 16:14:02',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201603310000004830',
            'amount' => '1.0000'
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccess()
    {
        $zhihPay = new ZhihPay();

        $encodeStr = 'bank_seq_no=7551102936201603310273064491&interface_version=V3.0&merchant_code=1111110166&' .
            'notify_id=5b9b67ecf6be4346ab738dddf9127c62&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201603310000004830&order_time=2016-03-25+16%3A35%3A06&trade_no=1125060594&trade_status=SUCCESS&' .
            'trade_time=2016-03-31+16%3A14%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1125060594',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111110166',
            'order_no' => '201603310000004830',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7551102936201603310273064491',
            'order_time' => '2016-03-25 16:35:06',
            'notify_id' => '5b9b67ecf6be4346ab738dddf9127c62',
            'trade_time' => '2016-03-31 16:14:02',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201603310000004830',
            'amount' => '0.0100'
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $zhihPay->getMsg());
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

        $newDinPayWeiXin = new ZhihPay();
        $newDinPayWeiXin->paymentTracking();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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

        $zhihPay = new ZhihPay();

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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
            '<trade><merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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
            '<order_no>201604270000002345</order_no>' .
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
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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
            '<order_no>201604270000002345</order_no>' .
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
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2014052200001' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2014052200001' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2014052200001' .
            '&order_time=2014-05-22 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2014-05-22 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2014-05-22 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2014-05-22 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com',
            'amount' => '0.01'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setContainer($this->container);
        $zhihPay->setClient($this->client);
        $zhihPay->setResponse($response);
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTracking();
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

        $newDinPayWeiXin = new ZhihPay();
        $newDinPayWeiXin->getPaymentTrackingData();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com'
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $trackingData = $zhihPay->getPaymentTrackingData();

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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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
            'orderId' => '201604270000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result ,
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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
            '<trade><merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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
            '<order_no>201604270000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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
            '<order_no>201604270000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2014052200001' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '20140522000016',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2014052200001' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'amount' => '0.02',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2014052200001' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2014052200001' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2014052200001' .
            '&order_time=2014-05-22 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2014-05-22 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2014-05-22 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2014-05-22 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhihpay.com',
            'amount' => '0.01',
            'content' => $result
        ];

        $zhihPay = new ZhihPay();
        $zhihPay->setOptions($sourceData);
        $zhihPay->paymentTrackingVerify();
    }
}
