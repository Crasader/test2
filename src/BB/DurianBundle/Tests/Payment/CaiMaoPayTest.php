<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CaiMaoPay;
use Buzz\Message\Response;

class CaiMaoPayTest extends DurianTestCase
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

        $caiMaoPay = new CaiMaoPay();

        $sourceData = ['number' => ''];

        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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
            'paymentVendorId' => '1092',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709180000000940</order_no>' .
            '<order_time>2017-09-18 14:45:38</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=OPL6rnW</qrcode>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>CJWvODcra0e0WQkdcGr3KwYT1NQmEoQMVlxF7o99o1Szda27OBziuJQR6E/L8W46DCuDImdlB0QToeMaRwqkf' .
            '0VhJNkI3Xz9 JQh/WuGl4AXmpm9vPhSdBiQpd/Hlmkcvtt0HXas0 rdBLBSlig7NLgl8XYxaw8kgjnDwcMcHfk=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1004050291</trade_no>' .
            '<trade_time>2017-09-18 14:45:39</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709180000000940</order_no>' .
            '<order_time>2017-09-18 14:45:38</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=OPL6rnW</qrcode>' .
            '<resp_code>FAILED</resp_code>' .
            '<resp_desc>商家订单号太长</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>CJWvODcra0e0WQkdcGr3KwYT1NQmEoQMVlxF7o99o1Szda27OBziuJQR6E/L8W46DCuDImdlB0QToeMaRwqkf' .
            '0VhJNkI3Xz9 JQh/WuGl4AXmpm9vPhSdBiQpd/Hlmkcvtt0HXas0 rdBLBSlig7NLgl8XYxaw8kgjnDwcMcHfk=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1004050291</trade_no>' .
            '<trade_time>2017-09-18 14:45:39</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709180000000940</order_no>' .
            '<order_time>2017-09-18 14:45:38</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=OPL6rnW</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<sign>CJWvODcra0e0WQkdcGr3KwYT1NQmEoQMVlxF7o99o1Szda27OBziuJQR6E/L8W46DCuDImdlB0QToeMaRwqkf' .
            '0VhJNkI3Xz9 JQh/WuGl4AXmpm9vPhSdBiQpd/Hlmkcvtt0HXas0 rdBLBSlig7NLgl8XYxaw8kgjnDwcMcHfk=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1004050291</trade_no>' .
            '<trade_time>2017-09-18 14:45:39</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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

        $sourceData = [
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709180000000940</order_no>' .
            '<order_time>2017-09-18 14:45:38</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=OPL6rnW</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<sign>CJWvODcra0e0WQkdcGr3KwYT1NQmEoQMVlxF7o99o1Szda27OBziuJQR6E/L8W46DCuDImdlB0QToeMaRwqkf' .
            '0VhJNkI3Xz9 JQh/WuGl4AXmpm9vPhSdBiQpd/Hlmkcvtt0HXas0 rdBLBSlig7NLgl8XYxaw8kgjnDwcMcHfk=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1004050291</trade_no>' .
            '<trade_time>2017-09-18 14:45:39</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709180000000940</order_no>' .
            '<order_time>2017-09-18 14:45:38</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=OPL6rnW</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '<sign>CJWvODcra0e0WQkdcGr3KwYT1NQmEoQMVlxF7o99o1Szda27OBziuJQR6E/L8W46DCuDImdlB0QToeMaRwqkf' .
            '0VhJNkI3Xz9 JQh/WuGl4AXmpm9vPhSdBiQpd/Hlmkcvtt0HXas0 rdBLBSlig7NLgl8XYxaw8kgjnDwcMcHfk=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1004050291</trade_no>' .
            '<trade_time>2017-09-18 14:45:39</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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
            '<merchant_code>501506003005</merchant_code>' .
            '<order_amount>50.00</order_amount>' .
            '<order_no>201711080000002133</order_no>' .
            '<order_time>2017-11-08 13:35:55</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>A2HNX m6sS0sxxwvOxem ct7pzlyyob7uufMZ15uHeEtm1N/kQ1Jfzq5TR10vEAOE2wnh' .
            'NZ xveVTFIX0WIYkTQ8aM3AE/4ch/RWVIVjHnwNvXFmXjrIbCctUctXHOulWpWknGBM6HyDhQHm' .
            't0n/r6J40OtN6r9KZSwSh4mCyIY=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1005581758</trade_no>' .
            '<trade_time>2017-11-08 13:36:03</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
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
            '<merchant_code>501506003005</merchant_code>' .
            '<order_amount>50.00</order_amount>' .
            '<order_no>201711080000002133</order_no>' .
            '<order_time>2017-11-08 13:35:55</order_time>' .
            '<payURL>https://api.caimao9.com/pay/location</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>A2HNX m6sS0sxxwvOxem ct7pzlyyob7uufMZ15uHeEtm1N/kQ1Jfzq5TR10vEAOE2wnh' .
            'NZ xveVTFIX0WIYkTQ8aM3AE/4ch/RWVIVjHnwNvXFmXjrIbCctUctXHOulWpWknGBM6HyDhQHm' .
            't0n/r6J40OtN6r9KZSwSh4mCyIY=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1005581758</trade_no>' .
            '<trade_time>2017-11-08 13:36:03</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '501506003005',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201711030000002106',
            'orderCreateDate' => '2017-11-03 14:59:57',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>588001002289</merchant_code>' .
            '<order_amount>10.45</order_amount>' .
            '<order_no>201712120000007089</order_no>' .
            '<order_time>2017-12-12 15:04:24</order_time>' .
            '<payURL>http://fpay.yeeyk.com/fourth-app/sim/fetchWxWap?payNo=2017121215042791862886</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>LwDW83Jyw7gndMJkg21nKLMS114T/1c5E0HiZP3RT4GU2kf9EAeZiDSgYV2Q' .
            'b0o5bsgt9Rh0kGx/5dvAwBo0kOzi6y3CtPGBD0wI5XqkZ/T7/mtunnRT5iFg' .
            'KDY5jw9nL4/O8wptX/NKiG0ZJ1nYPkabM F IwY6/JerTJYXWJ4=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1009690446</trade_no>' .
            '<trade_time>2017-12-12 15:04:27</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002289',
            'notify_url' => 'http://pay.simu/pay/return.php',
            'orderId' => '201712120000007089',
            'orderCreateDate' => '2017-12-12 15:04:24',
            'amount' => '10.45',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $encodeData = $caiMaoPay->getVerifyData();

        $this->assertEquals('http://fpay.yeeyk.com/fourth-app/sim/fetchWxWap', $encodeData['post_url']);
        $this->assertEquals('2017121215042791862886', $encodeData['params']['payNo']);
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
            'number' => '588001002001',
            'notify_url' => 'https://tingliu.000webhostapp.com/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709180000000940</order_no>' .
            '<order_time>2017-09-18 14:45:38</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>CJWvODcra0e0WQkdcGr3KwYT1NQmEoQMVlxF7o99o1Szda27OBziuJQR6E/L8W46DCuDImdlB0QToeMaRwqkf' .
            '0VhJNkI3Xz9 JQh/WuGl4AXmpm9vPhSdBiQpd/Hlmkcvtt0HXas0 rdBLBSlig7NLgl8XYxaw8kgjnDwcMcHfk=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1004050291</trade_no>' .
            '<trade_time>2017-09-18 14:45:39</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getVerifyData();
    }

    /**
     * 測試掃碼支付需重新定向
     */
    public function testPayWithIsRedirect()
    {
        $sourceData = [
            'number' => '588001002001',
            'notify_url' => 'http://pay.my/return.php',
            'orderId' => '201711150000002329',
            'orderCreateDate' => '2017-11-15 15:08:02',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $url = 'https://openapi.alipay.com/gateway.do?alipay_sdk=alipay-sdk-java-dynamicVersionNo';

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<isRedirect>Y</isRedirect>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>10.50</order_amount>' .
            '<order_no>201711150000002329</order_no>' .
            '<order_time>2017-11-15 15:08:02</order_time>' .
            '<qrcode>' . $url . '</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>a8U7vlS33lQPC2pvj/anh jeVLWZojtjd7SpMraf9dbDgZhFDc1/jHQSNCUU6ooY QihUdhEbsPF' .
            'BXErD/qYFRANTAJC8w4Tjxkcnnq wa7aoUlV75 Mn1ypo02AL3ukjGDhx/1lnpd73V2sdXAPnMeOJVIUeN4ZFbj9Tt509pQ=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1007175799</trade_no>' .
            '<trade_time>2017-11-15 15:08:03</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $encodeData = $caiMaoPay->getVerifyData();

        $this->assertEquals($url, $encodeData['post_url']);
        $this->assertEmpty($encodeData['params']);
    }

    /**
     * 測試網銀支付
     */
    public function testPayWithOnlineBank()
    {
        $encodeStr = 'bank_code=ICBC&client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.0' .
            '&merchant_code=588001002001&notify_url=http://pay.my/return.php' .
            '&order_amount=0.01&order_no=201709180000000940&order_time=2017-09-18 14:45:38&product_name=php1test' .
            '&redo_flag=1&return_url=http://pay.my/return.php&service_type=direct_pay';

        $sourceData = [
            'number' => '588001002001',
            'notify_url' => 'http://pay.my/return.php',
            'orderId' => '201709180000000940',
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $requestData = $caiMaoPay->getVerifyData();

        $postUrl = 'https://pay.' . $sourceData['postUrl'] . '/gateway?input_charset=UTF-8';
        $this->assertEquals('ICBC', $requestData['params']['bank_code']);
        $this->assertEquals('V3.0', $requestData['params']['interface_version']);
        $this->assertEquals('direct_pay', $requestData['params']['service_type']);
        $this->assertEquals('588001002001', $requestData['params']['merchant_code']);
        $this->assertEquals('http://pay.my/return.php', $requestData['params']['notify_url']);
        $this->assertEquals('UTF-8', $requestData['params']['input_charset']);
        $this->assertEquals('RSA-S', $requestData['params']['sign_type']);
        $this->assertEquals('http://pay.my/return.php', $requestData['params']['return_url']);
        $this->assertEquals('201709180000000940', $requestData['params']['order_no']);
        $this->assertEquals('2017-09-18 14:45:38', $requestData['params']['order_time']);
        $this->assertEquals('0.01', $requestData['params']['order_amount']);
        $this->assertEquals('php1test', $requestData['params']['product_name']);
        $this->assertEquals($postUrl, $requestData['post_url']);
        $this->assertEquals(base64_encode($sign), $requestData['params']['sign']);
        $this->assertEquals($sourceData['amount'], $requestData['params']['order_amount']);
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
            'orderCreateDate' => '2017-09-18 14:45:38',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.caimao9.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709180000000940</order_no>' .
            '<order_time>2017-09-18 14:45:38</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=OPL6rnW</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1004050291</trade_no>' .
            '<trade_time>2017-09-18 14:45:39</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $encodeData = $caiMaoPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=OPL6rnW', $caiMaoPay->getQrcode());
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

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->verifyOrderPayment([]);
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
            'merchant_code' => '588001002001',
            'order_no' => '201709180000000940',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2017-09-18 14:45:38',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2017-09-18 14:45:39',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->verifyOrderPayment([]);
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
            'merchant_code' => '588001002001',
            'order_no' => '201709180000000940',
            'trade_status' => 'SUCCESS',
            'sign' => 'h/xmESLkHjHoOvPmuFoMGeDP9pHPQTLKfGfs6t9IfOkRbN2cvgAszPSBOLTIg80NLxQkSkhUmnvlxj0lOjo' .
                'DZpHbzKsmkj62Nt65EVHwiRzhFnP3JqoHICoLdrn/zRowes3t2s4aiQVY5TrawMM0+1oqXWy/Pm8DNCegQ2jb1wY=',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2017-09-18 14:45:38',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2017-09-18 14:45:39',
            'rsa_public_key' => '',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->verifyOrderPayment([]);
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
            'merchant_code' => '588001002001',
            'order_no' => '201709180000000940',
            'trade_status' => 'SUCCESS',
            'sign' => 'h/xmESLkHjHoOvPmuFoMGeDP9pHPQTLKfGfs6t9IfOkRbN2cvgAszPSBOLTIg80NLxQkSkhUmnvlxj0lOjo' .
                'DZpHbzKsmkj62Nt65EVHwiRzhFnP3JqoHICoLdrn/zRowes3t2s4aiQVY5TrawMM0+1oqXWy/Pm8DNCegQ2jb1wY=',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2017-09-18 14:45:38',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2017-09-18 14:45:39',
            'rsa_public_key' => '123456789',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->verifyOrderPayment([]);
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
            'merchant_code' => '588001002001',
            'order_no' => '201709180000000940',
            'trade_status' => 'SUCCESS',
            'sign' => 'h/xmESLkHjHoOvPmuFoMGeDP9pHPQTLKfGfs6t9IfOkRbN2cvgAszPSBOLTIg80NLxQkSkhUmnvlxj0lOjo' .
                'DZpHbzKsmkj62Nt65EVHwiRzhFnP3JqoHICoLdrn/zRowes3t2s4aiQVY5TrawMM0+1oqXWy/Pm8DNCegQ2jb1wY=',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2017-09-18 14:45:38',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2017-09-18 14:45:39',
            'rsa_public_key' => $this->publicKey,
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->verifyOrderPayment([]);
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

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=C1065108922&interface_version=V3.0&merchant_code=588001002001' .
            '&notify_id=52bc933afaa344589aa6ef0c1675959f&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201709180000000940&order_time=2017-09-18 14:45:38&trade_no=1004050291' .
            '&trade_status=SUCCESS&trade_time=2017-09-18 14:45:39';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '588001002001',
            'order_no' => '201709180000000940',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2017-09-18 14:45:38',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2017-09-18 14:45:39',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201709180000000941',
            'amount' => '0.01'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->verifyOrderPayment($entry);
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

        $encodeStr = 'bank_seq_no=C1065108922&interface_version=V3.0&merchant_code=588001002001' .
            '&notify_id=52bc933afaa344589aa6ef0c1675959f&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201709180000000940&order_time=2017-09-18 14:45:38&trade_no=1004050291' .
            '&trade_status=SUCCESS&trade_time=2017-09-18 14:45:39';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '588001002001',
            'order_no' => '201709180000000940',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2017-09-18 14:45:38',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2017-09-18 14:45:39',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201709180000000940',
            'amount' => '1.00'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccess()
    {
        $encodeStr = 'bank_seq_no=C1065108922&interface_version=V3.0&merchant_code=588001002001' .
            '&notify_id=52bc933afaa344589aa6ef0c1675959f&notify_type=offline_notify&order_amount=0.01' .
            '&order_no=201709180000000940&order_time=2017-09-18 14:45:38&trade_no=1004050291' .
            '&trade_status=SUCCESS&trade_time=2017-09-18 14:45:39';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1004050291',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '588001002001',
            'order_no' => '201709180000000940',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'C1065108922',
            'order_time' => '2017-09-18 14:45:38',
            'notify_id' => '52bc933afaa344589aa6ef0c1675959f',
            'trade_time' => '2017-09-18 14:45:39',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201709180000000940',
            'amount' => '0.01'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $caiMaoPay->getMsg());
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

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->paymentTracking();
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
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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

        $caiMaoPay = new CaiMaoPay();

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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
            '<trade><merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201604270000002345',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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

        $encodeStr = 'merchant_code=588001002001&order_amount=0.01&order_no=201710170000005142' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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

        $encodeStr = 'merchant_code=588001002001&order_amount=0.01&order_no=201710170000005142' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpayweixin.com'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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
            'verify_url' => 'www.newdinpayweixin.com',
            'amount' => '0.01'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setContainer($this->container);
        $caiMaoPay->setClient($this->client);
        $caiMaoPay->setResponse($response);
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTracking();
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

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->getPaymentTrackingData();
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
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com'
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $trackingData = $caiMaoPay->getPaymentTrackingData();

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
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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
            'verify_url' => 'www.caimao9.com',
            'content' => $result ,
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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
            '<trade><merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=588001002001&order_amount=0.01&order_no=201710170000005142' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '2017101700000051426',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=588001002001&order_amount=0.01&order_no=201710170000005142' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.02',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=588001002001&order_amount=0.01&order_no=201710170000005142' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=588001002001&order_amount=0.01&order_no=201710170000005142' .
            '&order_time=2016-04-27 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2016-04-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>588001002001</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201710170000005142</order_no>' .
            '<order_time>2016-04-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2016-04-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
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

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.caimao9.com',
            'amount' => '0.01',
            'content' => $result
        ];

        $caiMaoPay = new CaiMaoPay();
        $caiMaoPay->setOptions($sourceData);
        $caiMaoPay->paymentTrackingVerify();
    }
}
