<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DongNanPay;
use Buzz\Message\Response;

class DongNanPayTest extends DurianTestCase
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

        $dongNanPay = new DongNanPay();

        $sourceData = ['number' => ''];

        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
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
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
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
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1098',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
    }

    /**
     * 測試加密沒代入postUrl
     */
    public function testGetEncodeWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => '',
            'ip' => '111.235.135.54',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
    }

    /**
     * 測試加密未返回response
     */
    public function testGetEncodeNoReturnResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = [
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>10.00</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:03</order_time>' .
            '<qrcode>https://qpay.qq.com/qr/622aa8b1</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<sign>HSc8vWHvEheVK JxtoPCNFmZ423BsU798c3fUkf936O RFOO2Dxx71z/gMFyNL0UWerJoO8iQCpMFtB' .
            'ttt1qf/Rb3AEVevq1MwR5GxAT9GhHW6RFLvIdBNuRLSRDNfnoeGoj7QJ0srmrbRkLMnZewqB9e7Ww2NfO6AtwDu9NGp4=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000870206</trade_no>' .
            '<trade_time>2018-02-13 11:28:04</trade_time>' .
            '</dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
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

        $sourceData = [
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>10.00</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:03</order_time>' .
            '<qrcode>https://qpay.qq.com/qr/622aa8b1</qrcode>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>HSc8vWHvEheVK JxtoPCNFmZ423BsU798c3fUkf936O RFOO2Dxx71z/gMFyNL0UWerJoO8iQCpMFtB' .
            'ttt1qf/Rb3AEVevq1MwR5GxAT9GhHW6RFLvIdBNuRLSRDNfnoeGoj7QJ0srmrbRkLMnZewqB9e7Ww2NfO6AtwDu9NGp4=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000870206</trade_no>' .
            '<trade_time>2018-02-13 11:28:04</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
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

        $sourceData = [
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>10.00</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:03</order_time>' .
            '<qrcode>https://qpay.qq.com/qr/622aa8b1</qrcode>' .
            '<resp_code>FAILED</resp_code>' .
            '<resp_desc>商家订单号太长</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>HSc8vWHvEheVK JxtoPCNFmZ423BsU798c3fUkf936O RFOO2Dxx71z/gMFyNL0UWerJoO8iQCpMFtB' .
            'ttt1qf/Rb3AEVevq1MwR5GxAT9GhHW6RFLvIdBNuRLSRDNfnoeGoj7QJ0srmrbRkLMnZewqB9e7Ww2NfO6AtwDu9NGp4=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000870206</trade_no>' .
            '<trade_time>2018-02-13 11:28:04</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
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

        $sourceData = [
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>10.00</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:03</order_time>' .
            '<qrcode>https://qpay.qq.com/qr/622aa8b1</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<sign>HSc8vWHvEheVK JxtoPCNFmZ423BsU798c3fUkf936O RFOO2Dxx71z/gMFyNL0UWerJoO8iQCpMFtB' .
            'ttt1qf/Rb3AEVevq1MwR5GxAT9GhHW6RFLvIdBNuRLSRDNfnoeGoj7QJ0srmrbRkLMnZewqB9e7Ww2NfO6AtwDu9NGp4=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000870206</trade_no>' .
            '<trade_time>2018-02-13 11:28:04</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
    }

    /**
     * 測試加密返回result_code不等於0，且沒有返回result_desc
     */
    public function testGetEncodeReturnResultCodeNotEqualToZeroAndNoResultDesc()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $sourceData = [
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>10.00</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:03</order_time>' .
            '<qrcode>https://qpay.qq.com/qr/622aa8b1</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<sign>HSc8vWHvEheVK JxtoPCNFmZ423BsU798c3fUkf936O RFOO2Dxx71z/gMFyNL0UWerJoO8iQCpMFtB' .
            'ttt1qf/Rb3AEVevq1MwR5GxAT9GhHW6RFLvIdBNuRLSRDNfnoeGoj7QJ0srmrbRkLMnZewqB9e7Ww2NfO6AtwDu9NGp4=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000870206</trade_no>' .
            '<trade_time>2018-02-13 11:28:04</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
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

        $sourceData = [
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>10.00</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:03</order_time>' .
            '<qrcode>https://qpay.qq.com/qr/622aa8b1</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '<sign>HSc8vWHvEheVK JxtoPCNFmZ423BsU798c3fUkf936O RFOO2Dxx71z/gMFyNL0UWerJoO8iQCpMFtB' .
            'ttt1qf/Rb3AEVevq1MwR5GxAT9GhHW6RFLvIdBNuRLSRDNfnoeGoj7QJ0srmrbRkLMnZewqB9e7Ww2NfO6AtwDu9NGp4=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000870206</trade_no>' .
            '<trade_time>2018-02-13 11:28:04</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
    }

    /**
     * 測試加密未返回payURL
     */
    public function testGetEncodeNoReturnPayURL()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '100100100054',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201806130000011913',
            'orderCreateDate' => '2018-06-13 15:00:55',
            'amount' => '1.00',
            'username' => 'php1test',
            'paymentVendorId' => '1098',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<error_code>GET_PAYURL_FAILED</error_code>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100054</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201806130000011913</order_no>' .
            '<order_time>2018-06-13 15:00:55</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<result_desc>获取支付链接失败</result_desc>' .
            '<sign>j/+GXatLH/O9CgVIK6xRQzpf0N1kj8g9lLeOz1XXM7aiJ81jTSC34bYSlf71lM3m+DjWltL5LrSlAuHg6ITZYEN0k95J1NfW+7' .
            'orssr5Pi5pQURucEjmFFh7749yjQTaRpyeIHdRKPgQJTSQzgVxtwivnnVpS5L3DvA5kNtt1PA=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
    }

    /**
     * 測試支付寶手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '100100100054',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201806130000011914',
            'orderCreateDate' => '2018-06-13 15:26:35',
            'amount' => '1.00',
            'username' => 'php1test',
            'paymentVendorId' => '1098',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100054</merchant_code>' .
            '<order_amount>1.00</order_amount>' .
            '<order_no>201806130000011914</order_no>' .
            '<order_time>2018-06-13 15:26:35</order_time>' .
            '<payURL>http%3A%2F%2Fddone.whrcpx.com%2Fpay%2Fuupay%2Fuupayhtmlgetway.php%3Forderid%3D201806131526384160' .
            '58</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>ZwbTa2xn0XAwpYGU48Wysiv02oKbHAA973W1oq16ASHjjWWgkbUv9MqnQGSf15DWz7LMEOoak6QTplcJxxdQBE3gCvi4ozo782' .
            'ZCPWFahlIvAFjcCfyFcjQ579XTJvnQBrfPoCWWzuIqP1Zimew3yvsylnX0h1iUQJLjcLwR034=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>LP240F1001829800</trade_no>' .
            '<trade_time>2018-06-13 15:26:39</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $verifyData = $dongNanPay->getVerifyData();

        $this->assertEquals('GET', $dongNanPay->getPayMethod());
        $this->assertEquals('http://ddone.whrcpx.com/pay/uupay/uupayhtmlgetway.php', $verifyData['post_url']);
        $this->assertEquals('20180613152638416058', $verifyData['params']['orderid']);
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

        $sourceData = [
            'number' => '100100100002',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201802130000009892',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '10.00',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>10.00</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:03</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '<sign>HSc8vWHvEheVK JxtoPCNFmZ423BsU798c3fUkf936O RFOO2Dxx71z/gMFyNL0UWerJoO8iQCpMFtB' .
            'ttt1qf/Rb3AEVevq1MwR5GxAT9GhHW6RFLvIdBNuRLSRDNfnoeGoj7QJ0srmrbRkLMnZewqB9e7Ww2NfO6AtwDu9NGp4=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000870206</trade_no>' .
            '<trade_time>2018-02-13 11:28:04</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getVerifyData();
    }

    /**
     * 測試加密(掃碼)
     */
    public function testGetEncodeData()
    {
        $encodeStr = 'interface_version=V3.1&merchant_code=588001002001&order_amount=0.01&order_no=201709180000000940' .
            '&order_time=2017-09-18 14:45:38&qrcode=weixin://wxpay/bizpayurl?pr=OPL6rnW&resp_code=SUCCESS' .
            '&resp_desc=通讯成功&result_code=0&trade_no=1004050291&trade_time=2017-09-18 14:45:39';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'number' => '588001002001',
            'notify_url' => 'http://pay.my/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2018-02-13 11:28:04',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ttkag.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>10.00</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:03</order_time>' .
            '<qrcode>https://qpay.qq.com/qr/622aa8b1</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1000870206</trade_no>' .
            '<trade_time>2018-02-13 11:28:04</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $encodeData = $dongNanPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('https://qpay.qq.com/qr/622aa8b1', $dongNanPay->getQrcode());
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

        $dongNanPay = new DongNanPay();
        $dongNanPay->verifyOrderPayment([]);
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
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '100100100002',
            'order_no' => '201802130000009892',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2018-02-13 11:28:04',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2018-02-13 11:28:05',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->verifyOrderPayment([]);
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
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '100100100002',
            'order_no' => '201802130000009892',
            'trade_status' => 'SUCCESS',
            'sign' => 'h/xmESLkHjHoOvPmuFoMGeDP9pHPQTLKfGfs6t9IfOkRbN2cvgAszPSBOLTIg80NLxQkSkhUmnvlxj0lOjo' .
                'DZpHbzKsmkj62Nt65EVHwiRzhFnP3JqoHICoLdrn/zRowes3t2s4aiQVY5TrawMM0+1oqXWy/Pm8DNCegQ2jb1wY=',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2018-02-13 11:28:04',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2018-02-13 11:28:05',
            'rsa_public_key' => '',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->verifyOrderPayment([]);
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
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '100100100002',
            'order_no' => '201802130000009892',
            'trade_status' => 'SUCCESS',
            'sign' => 'h/xmESLkHjHoOvPmuFoMGeDP9pHPQTLKfGfs6t9IfOkRbN2cvgAszPSBOLTIg80NLxQkSkhUmnvlxj0lOjo' .
                'DZpHbzKsmkj62Nt65EVHwiRzhFnP3JqoHICoLdrn/zRowes3t2s4aiQVY5TrawMM0+1oqXWy/Pm8DNCegQ2jb1wY=',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2018-02-13 11:28:04',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2018-02-13 11:28:05',
            'rsa_public_key' => '123456789',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->verifyOrderPayment([]);
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
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '100100100002',
            'order_no' => '201802130000009892',
            'trade_status' => 'SUCCESS',
            'sign' => 'h/xmESLkHjHoOvPmuFoMGeDP9pHPQTLKfGfs6t9IfOkRbN2cvgAszPSBOLTIg80NLxQkSkhUmnvlxj0lOjo' .
                'DZpHbzKsmkj62Nt65EVHwiRzhFnP3JqoHICoLdrn/zRowes3t2s4aiQVY5TrawMM0+1oqXWy/Pm8DNCegQ2jb1wY=',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2018-02-13 11:28:04',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2018-02-13 11:28:05',
            'rsa_public_key' => $this->publicKey,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=C1065108922&interface_version=V3.0&merchant_code=588001002001' .
            '&notify_id=52bc933afaa344589aa6ef0c1675959f&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201709180000000940&order_time=2017-09-18 14:45:38&trade_no=1004050291' .
            '&trade_status=FAILURE&trade_time=2017-09-18 14:45:39';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '588001002001',
            'order_no' => '201709180000000940',
            'trade_status' => 'FAILURE',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2017-09-18 14:45:38',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2017-09-18 14:45:39',
            'rsa_public_key' => $this->publicKey,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=C1065108922&interface_version=V3.0&merchant_code=100100100002' .
            '&notify_id=52bc933afaa344589aa6ef0c1675959f&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201802130000009892&order_time=2018-02-13 11:28:04&trade_no=1004050291' .
            '&trade_status=SUCCESS&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '100100100002',
            'order_no' => '201802130000009892',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2018-02-13 11:28:04',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2018-02-13 11:28:05',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201709180000000941',
            'amount' => '0.01',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->verifyOrderPayment($entry);
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

        $encodeStr = 'bank_seq_no=C1065108922&interface_version=V3.0&merchant_code=100100100002' .
            '&notify_id=52bc933afaa344589aa6ef0c1675959f&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201802130000009892&order_time=2018-02-13 11:28:04&trade_no=1004050291' .
            '&trade_status=SUCCESS&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '100100100002',
            'order_no' => '201802130000009892',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2018-02-13 11:28:04',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2018-02-13 11:28:05',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201802130000009892',
            'amount' => '1.00',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccess()
    {
        $encodeStr = 'bank_seq_no=C1065108922&interface_version=V3.0&merchant_code=100100100002' .
            '&notify_id=52bc933afaa344589aa6ef0c1675959f&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201802130000009892&order_time=2018-02-13 11:28:04&trade_no=1004050291' .
            '&trade_status=SUCCESS&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '100100100002',
            'order_no' => '201802130000009892',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2018-02-13 11:28:04',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2018-02-13 11:28:05',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201802130000009892',
            'amount' => '0.01',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $dongNanPay->getMsg());
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

        $dongNanPay = new DongNanPay();
        $dongNanPay->paymentTracking();
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
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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

        $dongNanPay = new DongNanPay();

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數response
     */
    public function testPaymentTrackingResultWithoutResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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
            '<trade><merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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
            '<trade><merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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
            '<trade><merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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

        $encodeStr = 'merchant_code=100100100002&order_amount=0.01&order_no=201802130000009892' .
            '&order_time=2018-02-13 11:28:04&trade_no=1136435210&trade_status=UNPAY&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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

        $encodeStr = 'merchant_code=100100100002&order_amount=0.01&order_no=201802130000009892' .
            '&order_time=2018-02-13 11:28:04&trade_no=1136435210&trade_status=FAILED&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeStr = 'merchant_code=588001002001&order_amount=0.01&order_no=201710170000005142' .
            '&order_time=2014-05-22 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2014-05-22 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2014-05-22 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2014-05-22 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'amount' => '0.01',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setContainer($this->container);
        $dongNanPay->setClient($this->client);
        $dongNanPay->setResponse($response);
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTracking();
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

        $dongNanPay = new DongNanPay();
        $dongNanPay->getPaymentTrackingData();
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
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $trackingData = $dongNanPay->getPaymentTrackingData();

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
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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
            '<trade><merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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
            '<trade><merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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
            '<trade><merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=100100100002&order_amount=0.01&order_no=201802130000009892' .
            '&order_time=2018-02-13 11:28:04&trade_no=1136435210&trade_status=SUCCESS&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '2017101700000051426',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=100100100002&order_amount=0.01&order_no=201802130000009892' .
            '&order_time=2018-02-13 11:28:04&trade_no=1136435210&trade_status=SUCCESS&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.02',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=100100100002&order_amount=0.01&order_no=201802130000009892' .
            '&order_time=2018-02-13 11:28:04&trade_no=1136435210&trade_status=UNPAY&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=100100100002&order_amount=0.01&order_no=201802130000009892' .
            '&order_time=2018-02-13 11:28:04&trade_no=1136435210&trade_status=FAILED&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $encodeStr = 'merchant_code=100100100002&order_amount=0.01&order_no=201802130000009892' .
            '&order_time=2018-02-13 11:28:04&trade_no=1003450919&trade_status=SUCCESS&trade_time=2018-02-13 11:28:05';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>100100100002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201802130000009892</order_no>' .
            '<order_time>2018-02-13 11:28:04</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-02-13 11:28:05</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '100100100002',
            'orderId' => '201802130000009892',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.ttkag.com',
            'amount' => '0.01',
            'content' => $result,
        ];

        $dongNanPay = new DongNanPay();
        $dongNanPay->setOptions($sourceData);
        $dongNanPay->paymentTrackingVerify();
    }
}
