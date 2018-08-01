<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiDauPay;
use Buzz\Message\Response;

class YiDauPayTest extends DurianTestCase
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

        $yiDauPay = new YiDauPay();

        $sourceData = ['number' => ''];

        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
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
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
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
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
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
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003202</order_no>' .
            '<order_time>2017-12-20 10:28:22</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=Hk3DCax</qrcode>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>sHPWWyD6m50i dtveExDqGNH0rb8HlWq5znepMrbIUHHzHqpwZDTsb0TBI6ezaHzKlXKayfhjLuFpl qzjdD8' .
            '//D09AJlwr0gSXMS9fUpR3NZYlCkwGBAXiUjGmepGO5dqWS8QqhYi4WfSg5ArE5ugdQhcwmCxBlo40qodWj2Qg=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001255142</trade_no>' .
            '<trade_time>2017-12-20 10:28:24</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
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
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003202</order_no>' .
            '<order_time>2017-12-20 10:28:22</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=Hk3DCax</qrcode>' .
            '<resp_code>FAILED</resp_code>' .
            '<resp_desc>商家订单号太长</resp_desc>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
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
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003202</order_no>' .
            '<order_time>2017-12-20 10:28:22</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=Hk3DCax</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
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
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003202</order_no>' .
            '<order_time>2017-12-20 10:28:22</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=Hk3DCax</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
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
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003202</order_no>' .
            '<order_time>2017-12-20 10:28:22</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=Hk3DCax</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
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
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003202</order_no>' .
            '<order_time>2017-12-20 10:28:22</order_time>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>sHPWWyD6m50i dtveExDqGNH0rb8HlWq5znepMrbIUHHzHqpwZDTsb0TBI6ezaHzKlXKayfhjLuFpl qzjdD8' .
            '//D09AJlwr0gSXMS9fUpR3NZYlCkwGBAXiUjGmepGO5dqWS8QqhYi4WfSg5ArE5ugdQhcwmCxBlo40qodWj2Qg=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001255142</trade_no>' .
            '<trade_time>2017-12-20 10:28:24</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getVerifyData();
    }

    /**
     * 測試加密(掃碼)
     */
    public function testGetEncodeData()
    {
        $encodeStr = 'client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.1&' .
            'merchant_code=588001002001&notify_url=http://payment/return.php&order_amount=0.01&' .
            'order_no=201712200000003202&order_time=2017-12-20 10:28:22&product_name=php1test&' .
            'redo_flag=1&service_type=weixin_scan';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003202</order_no>' .
            '<order_time>2017-12-20 10:28:22</order_time>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=Hk3DCax</qrcode>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>sHPWWyD6m50i dtveExDqGNH0rb8HlWq5znepMrbIUHHzHqpwZDTsb0TBI6ezaHzKlXKayfhjLuFpl qzjdD8' .
            '//D09AJlwr0gSXMS9fUpR3NZYlCkwGBAXiUjGmepGO5dqWS8QqhYi4WfSg5ArE5ugdQhcwmCxBlo40qodWj2Qg=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1001255142</trade_no>' .
            '<trade_time>2017-12-20 10:28:24</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $encodeData = $yiDauPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=Hk3DCax', $yiDauPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testPayWithOnlineBank()
    {
        $encodeStr = 'bank_code=ICBC&client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.0&' .
            'merchant_code=588001002001&notify_url=http://payment/return.php&order_amount=0.01&' .
            'order_no=201712200000003202&order_time=2017-12-20 10:28:22&product_name=php1test&redo_flag=1&' .
            'return_url=http://payment/return.php&service_type=direct_pay';

        $sourceData = [
            'number' => '588001002001',
            'notify_url' => 'http://payment/return.php',
            'orderId' => '201712200000003202',
            'orderCreateDate' => '2017-12-20 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'postUrl' => 'yidpay.com',
            'ip' => '111.235.135.54',
        ];

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $requestData = $yiDauPay->getVerifyData();

        $this->assertEquals('https://pay.yidpay.com/gateway?input_charset=UTF-8', $requestData['post_url']);
        $this->assertEquals('588001002001', $requestData['params']['merchant_code']);
        $this->assertEquals('direct_pay', $requestData['params']['service_type']);
        $this->assertEquals('http://payment/return.php', $requestData['params']['notify_url']);
        $this->assertEquals('V3.0', $requestData['params']['interface_version']);
        $this->assertEquals('UTF-8', $requestData['params']['input_charset']);
        $this->assertEquals('RSA-S', $requestData['params']['sign_type']);
        $this->assertEquals(base64_encode($sign), $requestData['params']['sign']);
        $this->assertEquals('http://payment/return.php', $requestData['params']['return_url']);
        $this->assertEquals('111.235.135.54', $requestData['params']['client_ip']);
        $this->assertEquals('201712200000003202', $requestData['params']['order_no']);
        $this->assertEquals('2017-12-20 10:28:22', $requestData['params']['order_time']);
        $this->assertEquals('0.01', $requestData['params']['order_amount']);
        $this->assertEquals('ICBC', $requestData['params']['bank_code']);
        $this->assertEquals('1', $requestData['params']['redo_flag']);
        $this->assertEquals('php1test', $requestData['params']['product_name']);
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

        $yiDauPay = new YiDauPay();
        $yiDauPay->verifyOrderPayment([]);
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
            'trade_no' => '1001255142',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '888001001002',
            'order_no' => '201712200000003202',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7895253000230042',
            'order_time' => '2017-12-20 10:28:22',
            'notify_id' => 'a7f0c7ca1a284258911901fb1c0b90ff',
            'trade_time' => '2017-12-20 10:28:24',
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->verifyOrderPayment([]);
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
            'trade_no' => '1001255142',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '888001001002',
            'order_no' => '201712200000003202',
            'trade_status' => 'SUCCESS',
            'sign' => '123123',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7895253000230042',
            'order_time' => '2017-12-20 10:28:22',
            'notify_id' => 'a7f0c7ca1a284258911901fb1c0b90ff',
            'trade_time' => '2017-12-20 10:28:24',
            'rsa_public_key' => '',
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->verifyOrderPayment([]);
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
            'trade_no' => '1001255142',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '888001001002',
            'order_no' => '201712200000003202',
            'trade_status' => 'SUCCESS',
            'sign' => '123123',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7895253000230042',
            'order_time' => '2017-12-20 10:28:22',
            'notify_id' => 'a7f0c7ca1a284258911901fb1c0b90ff',
            'trade_time' => '2017-12-20 10:28:24',
            'rsa_public_key' => '123456789',
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->verifyOrderPayment([]);
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
            'trade_no' => '1001255142',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '888001001002',
            'order_no' => '201712200000003202',
            'trade_status' => 'SUCCESS',
            'sign' => '1231231',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7895253000230042',
            'order_time' => '2017-12-20 10:28:22',
            'notify_id' => 'a7f0c7ca1a284258911901fb1c0b90ff',
            'trade_time' => '2017-12-20 10:28:24',
            'rsa_public_key' => $this->publicKey,
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=7895253000230042&interface_version=V3.0&merchant_code=888001001002&' .
            'notify_id=a7f0c7ca1a284258911901fb1c0b90ff&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201712200000003202&order_time=2017-12-20 10:28:22&trade_no=1001255142&' .
            'trade_status=FAILURE&trade_time=2017-12-20 10:28:24';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1001255142',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '888001001002',
            'order_no' => '201712200000003202',
            'trade_status' => 'FAILURE',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7895253000230042',
            'order_time' => '2017-12-20 10:28:22',
            'notify_id' => 'a7f0c7ca1a284258911901fb1c0b90ff',
            'trade_time' => '2017-12-20 10:28:24',
            'rsa_public_key' => $this->publicKey,
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=7895253000230042&interface_version=V3.0&merchant_code=888001001002&' .
            'notify_id=a7f0c7ca1a284258911901fb1c0b90ff&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201712200000003202&order_time=2017-12-20 10:28:22&trade_no=1001255142&' .
            'trade_status=SUCCESS&trade_time=2017-12-20 10:28:24';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1001255142',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '888001001002',
            'order_no' => '201712200000003202',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7895253000230042',
            'order_time' => '2017-12-20 10:28:22',
            'notify_id' => 'a7f0c7ca1a284258911901fb1c0b90ff',
            'trade_time' => '2017-12-20 10:28:24',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201712200000003203',
            'amount' => '0.01',
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->verifyOrderPayment($entry);
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

        $encodeStr = 'bank_seq_no=7895253000230042&interface_version=V3.0&merchant_code=888001001002&' .
            'notify_id=a7f0c7ca1a284258911901fb1c0b90ff&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201712200000003202&order_time=2017-12-20 10:28:22&trade_no=1001255142&' .
            'trade_status=SUCCESS&trade_time=2017-12-20 10:28:24';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1001255142',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '888001001002',
            'order_no' => '201712200000003202',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7895253000230042',
            'order_time' => '2017-12-20 10:28:22',
            'notify_id' => 'a7f0c7ca1a284258911901fb1c0b90ff',
            'trade_time' => '2017-12-20 10:28:24',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201712200000003202',
            'amount' => '10',
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $encodeStr = 'bank_seq_no=7895253000230042&interface_version=V3.0&merchant_code=888001001002&' .
            'notify_id=a7f0c7ca1a284258911901fb1c0b90ff&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201712200000003202&order_time=2017-12-20 10:28:22&trade_no=1001255142&' .
            'trade_status=SUCCESS&trade_time=2017-12-20 10:28:24';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'trade_no' => '1001255142',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '888001001002',
            'order_no' => '201712200000003202',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7895253000230042',
            'order_time' => '2017-12-20 10:28:22',
            'notify_id' => 'a7f0c7ca1a284258911901fb1c0b90ff',
            'trade_time' => '2017-12-20 10:28:24',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201712200000003202',
            'amount' => '0.01',
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yiDauPay->getMsg());
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

        $yiDauPay = new YiDauPay();
        $yiDauPay->paymentTracking();
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
            'orderId' => '201712200000003202',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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

        $yiDauPay = new YiDauPay();

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712200000003202',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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
            'orderId' => '201712200000003202',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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
            'orderId' => '201712200000003202',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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
            'orderId' => '201712200000003202',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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
            '<sign_type>RSA-S</sign_type>' .
            '<sign>BrRG9XaukvwaRQRpMnJHBK448Bz9GMv891He0D5/cWgnnXAPtqFMSpuMWAWcy8eDpC/QX1e5B6lQHf/rFZWhOd' .
            'Im fH/6bqDI7GcHjxhlPxQPYjyW7/0 Maau6NiBOfUsC1Z1tEso724ScFSprkiW4VjvQwrDGSYryyT/if29VU=</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_no>201712200000003199</order_no>' .
            '<order_time>2017-12-20 10:01:45</order_time>' .
            '<trade_no>1001255137</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-12-20 10:01:57</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712200000003199',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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
            '<sign_type>RSA-S</sign_type>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003199</order_no>' .
            '<order_time>2017-12-20 10:01:45</order_time>' .
            '<trade_no>1001255137</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-12-20 10:01:57</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712200000003199',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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
            '<sign_type>RSA-S</sign_type>' .
            '<sign>BrRG9XaukvwaRQRpMnJHBK448Bz9GMv891He0D5/cWgnnXAPtqFMSpuMWAWcy8eDpC/QX1e5B6lQHf/rFZWhOd' .
            'Im fH/6bqDI7GcHjxhlPxQPYjyW7/0 Maau6NiBOfUsC1Z1tEso724ScFSprkiW4VjvQwrDGSYryyT/if29VU=</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712200000003199</order_no>' .
            '<order_time>2017-12-20 10:01:45</order_time>' .
            '<trade_no>1001255137</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-12-20 10:01:57</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712200000003199',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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

        $encodeStr = 'merchant_code=888001001002&order_amount=0.01&order_no=201712210000003206&' .
            'order_time=2017-12-21 16:21:29&trade_no=1001255179&trade_status=UNPAY&trade_time=2017-12-21 16:21:37';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '888001001002',
            'orderId' => '201712210000003206',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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

        $encodeStr = 'merchant_code=888001001002&order_amount=0.01&order_no=201712210000003206&' .
            'order_time=2017-12-21 16:21:29&trade_no=1001255179&trade_status=FAILED&trade_time=2017-12-21 16:21:37';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '888001001002',
            'orderId' => '201712210000003206',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeStr = 'merchant_code=888001001002&order_amount=0.01&order_no=201712210000003206&' .
            'order_time=2017-12-21 16:21:29&trade_no=1001255179&trade_status=SUCCESS&trade_time=2017-12-21 16:21:37';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '888001001002',
            'orderId' => '201712210000003206',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setContainer($this->container);
        $yiDauPay->setClient($this->client);
        $yiDauPay->setResponse($response);
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTracking();
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

        $yiDauPay = new YiDauPay();
        $yiDauPay->getPaymentTrackingData();
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
            'number' => '888001001002',
            'orderId' => '201712210000003206',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->getPaymentTrackingData();
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
            'verify_url' => 'payment.https.query.yidpay.com'
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $trackingData = $yiDauPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/query', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參數
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
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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
            '<sign_type>RSA-S</sign_type>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712210000003206',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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
            '<sign_type>RSA-S</sign_type>' .
            '<sign>bOQrGuLTKIEj6bL04GYf42uUsdhyYXnu9PlXsY860IkqmDuzXNSNsuVKoMNXrs1akC6JN1Uc65se+6Kczqka7k' .
            'YlXaZzir5\/OjPXfNfexJOgq42trIGO1gdRJLarfCzEi3kL+NdRfB9sZ8lgWlJCQe\/RvFcwuw0EXxZ7PNoIQX0=</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712210000003206',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=888001001002&order_amount=0.01&order_no=201712210000003206&' .
            'order_time=2017-12-21 16:21:29&trade_no=1001255179&trade_status=SUCCESS&trade_time=2017-12-21 16:21:37';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201710170000005142',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=888001001002&order_amount=0.01&order_no=201712210000003206&' .
            'order_time=2017-12-21 16:21:29&trade_no=1001255179&trade_status=SUCCESS&trade_time=2017-12-21 16:21:37';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712210000003206',
            'amount' => '0.02',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=888001001002&order_amount=0.01&order_no=201712210000003206&' .
            'order_time=2017-12-21 16:21:29&trade_no=1001255179&trade_status=UNPAY&trade_time=2017-12-21 16:21:37';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712210000003206',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=888001001002&order_amount=0.01&order_no=201712210000003206&' .
            'order_time=2017-12-21 16:21:29&trade_no=1001255179&trade_status=FAILED&trade_time=2017-12-21 16:21:37';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712210000003206',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $encodeStr = 'merchant_code=888001001002&order_amount=0.01&order_no=201712210000003206&' .
            'order_time=2017-12-21 16:21:29&trade_no=1001255179&trade_status=SUCCESS&trade_time=2017-12-21 16:21:37';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade><merchant_code>888001001002</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201712210000003206</order_no>' .
            '<order_time>2017-12-21 16:21:29</order_time>' .
            '<trade_no>1001255179</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-12-21 16:21:37</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '588001002001',
            'orderId' => '201712210000003206',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.yidpay.com',
            'content' => $result
        ];

        $yiDauPay = new YiDauPay();
        $yiDauPay->setOptions($sourceData);
        $yiDauPay->paymentTrackingVerify();
    }
}
