<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\JrFuHuei;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class JrFuHueiTest extends DurianTestCase
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

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
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
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '99999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
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
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
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
            '<merchant_code>800005007003</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201711210000002447</order_no>' .
            '<order_time>2017-11-21 11:44:35</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=pSVJSSO</qrcode>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>HyGLp5sf4xbTZHIcOIo6Ub4swbyJUiEXvonNs2tqt4zlkC72b2qWOXxLaZxgVjbNlO93cWA' .
            'ankHWBePNEzRIZQAcaq90L3rPKICNhKoVu/JOR9O1LkHd2EtwYXvyDCyXI52aM2GY9NJQSpDhEm0Ii' .
            'vAbRuvwrLLSuekSNCNFT/E=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001956975</trade_no>' .
            '<trade_time>2017-11-21 11:44:37</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
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
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<resp_code>FAIL</resp_code>' .
            '<resp_desc>商家订单号太长</resp_desc>' .
            '<sign>RAlt65bV3DJTOV4KQAS4bVHlJQ4n5krh4IfqtKTp2REZ6j/yaHJY/c I6DiR1atvHm' .
            'bywaSWdfHd2mgxL95ssRxG3TnATmB7xRhxTxra5yO9iZg/aKc7tHpmqieRR0aZhZ uZGeebHu' .
            'XCifGBaLHVyIf7u1ElaLsW34nplop03c=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
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
            '<merchant_code>800005007003</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201711210000002447</order_no>' .
            '<order_time>2017-11-21 11:44:35</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=pSVJSSO</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<sign>HyGLp5sf4xbTZHIcOIo6Ub4swbyJUiEXvonNs2tqt4zlkC72b2qWOXxLaZxgVjbNlO93cWA' .
            'ankHWBePNEzRIZQAcaq90L3rPKICNhKoVu/JOR9O1LkHd2EtwYXvyDCyXI52aM2GY9NJQSpDhEm0Ii' .
            'vAbRuvwrLLSuekSNCNFT/E=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001956975</trade_no>' .
            '<trade_time>2017-11-21 11:44:37</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
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
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>800005007003</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201711210000002447</order_no>' .
            '<order_time>2017-11-21 11:44:35</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=pSVJSSO</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<sign>HyGLp5sf4xbTZHIcOIo6Ub4swbyJUiEXvonNs2tqt4zlkC72b2qWOXxLaZxgVjbNlO93cWA' .
            'ankHWBePNEzRIZQAcaq90L3rPKICNhKoVu/JOR9O1LkHd2EtwYXvyDCyXI52aM2GY9NJQSpDhEm0Ii' .
            'vAbRuvwrLLSuekSNCNFT/E=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001956975</trade_no>' .
            '<trade_time>2017-11-21 11:44:37</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
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
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>800005007003</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201711210000002447</order_no>' .
            '<order_time>2017-11-21 11:44:35</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=pSVJSSO</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '<sign>HyGLp5sf4xbTZHIcOIo6Ub4swbyJUiEXvonNs2tqt4zlkC72b2qWOXxLaZxgVjbNlO93cWA' .
            'ankHWBePNEzRIZQAcaq90L3rPKICNhKoVu/JOR9O1LkHd2EtwYXvyDCyXI52aM2GY9NJQSpDhEm0Ii' .
            'vAbRuvwrLLSuekSNCNFT/E=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001956975</trade_no>' .
            '<trade_time>2017-11-21 11:44:37</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
    }

    /**
     * 測試手機支付加密未返回payURL
     */
    public function testPhoneGetEncodeNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>123456789001</merchant_code>' .
            '<order_amount>0.02</order_amount>' .
            '<order_no>201711210000002436</order_no>' .
            '<order_time>2017-11-21 10:55:51</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>UdLcrZDqCCg2TdqR4AIExIcL0n3/lYNYkNNrYcOhnnrHa SAV6v5AX0hEQMJQTsOnb83cq' .
            'Ms15wqulgvD 98vKlbkiBbFFmIl95IlR2Goph31Hd75AThkzlotr565 2Ocu/cUL XHVDlYMPw8ZZ' .
            '4HZgxTtejCcYpuWgycpqG6BQ=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001953893</trade_no>' .
            '<trade_time>2017-11-21 10:55:52</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '123456789001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002436',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
    }

    /**
     * 測試手機支付時返回payURL缺少query
     */
    public function testPhonePayGetEncodeReturnPayURLWithoutQuery()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>123456789001</merchant_code>' .
            '<order_amount>0.02</order_amount>' .
            '<order_no>201711210000002436</order_no>' .
            '<order_time>2017-11-21 10:55:51</order_time>' .
            '<payURL>https://zhongxin.junka.com/MSite/Cashier/WeixinQRCodePay.aspx?</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>UdLcrZDqCCg2TdqR4AIExIcL0n3/lYNYkNNrYcOhnnrHa SAV6v5AX0hEQMJQTsOnb83cq' .
            'Ms15wqulgvD 98vKlbkiBbFFmIl95IlR2Goph31Hd75AThkzlotr565 2Ocu/cUL XHVDlYMPw8ZZ' .
            '4HZgxTtejCcYpuWgycpqG6BQ=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001953893</trade_no>' .
            '<trade_time>2017-11-21 10:55:52</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '123456789001',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002436',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
    }

    /**
     * 測試掃碼支付需重新定向
     */
    public function testPayWithIsRedirect()
    {
        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $url = 'https://qr.alipay.com/bax02294ehozxioq1nnu8071';

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<isRedirect>Y</isRedirect>' .
            '<merchant_code>800005007003</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201711210000002447</order_no>' .
            '<order_time>2017-11-21 11:44:35</order_time>' .
            '<qrcode>' . $url . '</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>HyGLp5sf4xbTZHIcOIo6Ub4swbyJUiEXvonNs2tqt4zlkC72b2qWOXxLaZxgVjbNlO93cWA' .
            'ankHWBePNEzRIZQAcaq90L3rPKICNhKoVu/JOR9O1LkHd2EtwYXvyDCyXI52aM2GY9NJQSpDhEm0Ii' .
            'vAbRuvwrLLSuekSNCNFT/E=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001956975</trade_no>' .
            '<trade_time>2017-11-21 11:44:37</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $encodeData = $jrFuHuei->getVerifyData();

        $this->assertEquals($url, $encodeData['post_url']);
        $this->assertEmpty($encodeData['params']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>123456789001</merchant_code>' .
            '<order_amount>0.02</order_amount>' .
            '<order_no>201711210000002436</order_no>' .
            '<order_time>2017-11-21 10:55:51</order_time>' .
            '<payURL>https://zhongxin.junka.com/MSite/Cashier/WeixinQRCodePay.aspx?st' .
            'id=H1711216053613A6_ea99c8686de741aa7f673f5cf7a07c2a</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>UdLcrZDqCCg2TdqR4AIExIcL0n3/lYNYkNNrYcOhnnrHa SAV6v5AX0hEQMJQTsOnb83cq' .
            'Ms15wqulgvD 98vKlbkiBbFFmIl95IlR2Goph31Hd75AThkzlotr565 2Ocu/cUL XHVDlYMPw8ZZ' .
            '4HZgxTtejCcYpuWgycpqG6BQ=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001953893</trade_no>' .
            '<trade_time>2017-11-21 10:55:52</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $encodeData = $jrFuHuei->getVerifyData();

        $this->assertEquals('https://zhongxin.junka.com/MSite/Cashier/WeixinQRCodePay.aspx', $encodeData['post_url']);
        $this->assertEquals('H1711216053613A6_ea99c8686de741aa7f673f5cf7a07c2a', $encodeData['params']['stid']);
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
            '<merchant_code>800005007003</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201711210000002447</order_no>' .
            '<order_time>2017-11-21 11:44:35</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>HyGLp5sf4xbTZHIcOIo6Ub4swbyJUiEXvonNs2tqt4zlkC72b2qWOXxLaZxgVjbNlO93cWA' .
            'ankHWBePNEzRIZQAcaq90L3rPKICNhKoVu/JOR9O1LkHd2EtwYXvyDCyXI52aM2GY9NJQSpDhEm0Ii' .
            'vAbRuvwrLLSuekSNCNFT/E=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001956975</trade_no>' .
            '<trade_time>2017-11-21 11:44:37</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getVerifyData();
    }

    /**
     * 測試掃碼加密
     */
    public function testQrCodePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>800005007003</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201711210000002447</order_no>' .
            '<order_time>2017-11-21 11:44:35</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=pSVJSSO</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>HyGLp5sf4xbTZHIcOIo6Ub4swbyJUiEXvonNs2tqt4zlkC72b2qWOXxLaZxgVjbNlO93cWA' .
            'ankHWBePNEzRIZQAcaq90L3rPKICNhKoVu/JOR9O1LkHd2EtwYXvyDCyXI52aM2GY9NJQSpDhEm0Ii' .
            'vAbRuvwrLLSuekSNCNFT/E=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001956975</trade_no>' .
            '<trade_time>2017-11-21 11:44:37</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $encodeData = $jrFuHuei->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=pSVJSSO', $jrFuHuei->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testPayWithOnlineBank()
    {
        $encodeStr = 'bank_code=ICBC&client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.0' .
            '&merchant_code=800005007003&notify_url=http://pay.my/pay/return.php&order_amount=0.01' .
            '&order_no=201711210000002447&order_time=2017-11-21 11:44:37&product_name=php1test&redo_flag=1' .
            '&service_type=direct_pay';

        $sourceData = [
            'number' => '800005007003',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711210000002447',
            'orderCreateDate' => '2017-11-21 11:44:37',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'zfhuipay.com',
            'ip' => '111.235.135.54',
        ];

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $requestData = $jrFuHuei->getVerifyData();

        $this->assertEquals('https://pay.zfhuipay.com/gateway?input_charset=UTF-8', $requestData['post_url']);
        $this->assertEquals('ICBC', $requestData['params']['bank_code']);
        $this->assertEquals('V3.0', $requestData['params']['interface_version']);
        $this->assertEquals('direct_pay', $requestData['params']['service_type']);
        $this->assertEquals('0.01', $requestData['params']['order_amount']);
        $this->assertEquals(base64_encode($sign), $requestData['params']['sign']);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->verifyOrderPayment([]);
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
            'trade_no' => '1001950935',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '800005007003',
            'order_no' => '201711210000002428',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '1628965219',
            'order_time' => '2017-11-21 10:05:41',
            'notify_id' => 'd4a77b91e6424fc4af6b87db0bc57c08',
            'trade_time' => '2017-11-21 10:05:53',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->verifyOrderPayment([]);
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
            'trade_no' => '1001950935',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '800005007003',
            'order_no' => '201711210000002428',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '1628965219',
            'order_time' => '2017-11-21 10:05:41',
            'notify_id' => 'd4a77b91e6424fc4af6b87db0bc57c08',
            'trade_time' => '2017-11-21 10:05:53',
            'sign' => 'test',
            'rsa_public_key' => $this->publicKey,
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->verifyOrderPayment([]);
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
            'trade_no' => '1001950935',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '800005007003',
            'order_no' => '201711210000002428',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '1628965219',
            'order_time' => '2017-11-21 10:05:41',
            'notify_id' => 'd4a77b91e6424fc4af6b87db0bc57c08',
            'trade_time' => '2017-11-21 10:05:53',
            'sign' => 'test',
            'rsa_public_key' => '',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->verifyOrderPayment([]);
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
            'trade_no' => '1001950935',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '800005007003',
            'order_no' => '201711210000002428',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '1628965219',
            'order_time' => '2017-11-21 10:05:41',
            'notify_id' => 'd4a77b91e6424fc4af6b87db0bc57c08',
            'trade_time' => '2017-11-21 10:05:53',
            'sign' => 'test',
            'rsa_public_key' => 'test',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=1628965219&interface_version=V3.0&merchant_code=800005007003' .
            '&notify_id=d4a77b91e6424fc4af6b87db0bc57c08&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201711210000002428&order_time=2017-11-21 10:05:41&trade_no=1001950935' .
            '&trade_status=FAILURE&trade_time=2017-11-21 10:05:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1001950935',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '800005007003',
            'order_no' => '201711210000002428',
            'trade_status' => 'FAILURE',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '1628965219',
            'order_time' => '2017-11-21 10:05:41',
            'notify_id' => 'd4a77b91e6424fc4af6b87db0bc57c08',
            'trade_time' => '2017-11-21 10:05:53',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=1628965219&interface_version=V3.0&merchant_code=800005007003' .
            '&notify_id=d4a77b91e6424fc4af6b87db0bc57c08&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201711210000002428&order_time=2017-11-21 10:05:41&trade_no=1001950935' .
            '&trade_status=SUCCESS&trade_time=2017-11-21 10:05:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1001950935',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '800005007003',
            'order_no' => '201711210000002428',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '1628965219',
            'order_time' => '2017-11-21 10:05:41',
            'notify_id' => 'd4a77b91e6424fc4af6b87db0bc57c08',
            'trade_time' => '2017-11-21 10:05:53',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201711210000002429',
            'amount' => '0.01',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->verifyOrderPayment($entry);
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

        $encodeStr = 'bank_seq_no=1628965219&interface_version=V3.0&merchant_code=800005007003' .
            '&notify_id=d4a77b91e6424fc4af6b87db0bc57c08&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201711210000002428&order_time=2017-11-21 10:05:41&trade_no=1001950935' .
            '&trade_status=SUCCESS&trade_time=2017-11-21 10:05:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1001950935',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '800005007003',
            'order_no' => '201711210000002428',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '1628965219',
            'order_time' => '2017-11-21 10:05:41',
            'notify_id' => 'd4a77b91e6424fc4af6b87db0bc57c08',
            'trade_time' => '2017-11-21 10:05:53',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201711210000002428',
            'amount' => '1',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $encodeStr = 'bank_seq_no=1628965219&interface_version=V3.0&merchant_code=800005007003' .
            '&notify_id=d4a77b91e6424fc4af6b87db0bc57c08&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201711210000002428&order_time=2017-11-21 10:05:41&trade_no=1001950935' .
            '&trade_status=SUCCESS&trade_time=2017-11-21 10:05:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1001950935',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '800005007003',
            'order_no' => '201711210000002428',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '1628965219',
            'order_time' => '2017-11-21 10:05:41',
            'notify_id' => 'd4a77b91e6424fc4af6b87db0bc57c08',
            'trade_time' => '2017-11-21 10:05:53',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201711210000002428',
            'amount' => '0.01',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jrFuHuei->getMsg());
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

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->paymentTracking();
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

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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

        $jrFuHuei = new JrFuHuei();

        $sourceData = [
            'number' => '9527',
            'orderId' => '201711280000002106',
            'rsa_private_key' => base64_encode($privateKey),
        ];

        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com',
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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
            'verify_url' => 'www.zfhuipay.com',
            'amount' => '0.01'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setContainer($this->container);
        $jrFuHuei->setClient($this->client);
        $jrFuHuei->setResponse($response);
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTracking();
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

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->getPaymentTrackingData();
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

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->getPaymentTrackingData();
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
            'verify_url' => 'www.zfhuipay.com'
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $trackingData = $jrFuHuei->getPaymentTrackingData();

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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result ,
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
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
            'verify_url' => 'www.zfhuipay.com',
            'amount' => '0.01',
            'content' => $result
        ];

        $jrFuHuei = new JrFuHuei();
        $jrFuHuei->setOptions($sourceData);
        $jrFuHuei->paymentTrackingVerify();
    }
}
