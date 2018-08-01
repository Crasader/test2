<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HeYiPay;
use Buzz\Message\Response;

class HeYiPayTest extends DurianTestCase
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

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1000',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
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
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => '',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
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
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => '123456',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試支付時產生簽名失敗
     */
    public function testPayGenerateSignatureFailure()
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
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => base64_encode($privkey),
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時缺少verify_url
     */
    public function testBankPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'verify_url' => '',
            'rsa_private_key' => $this->privateKey,
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時返回缺少retCode
     */
    public function testBankPayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'sign' => 'dJP1Q1pglJ6Xtq/o=',
            'expireTime' => '60',
            'tradeNo' => '17010301002881513',
            'retMsg' => '处理成功!',
            'payUrl' => 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?cipher_data=E3NPB9BtKZX/Gkf5hagKJbs',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時返回提交失敗
     */
    public function testBankPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '第三方平台支付失败',
            180130
        );

        $result = [
            'rpid' => 'R148366887194800000',
            'retMsg' => '第三方平台支付失败',
            'retCode' => '603001',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時返回缺少Sign
     */
    public function testBankPayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'expireTime' => '60',
            'tradeNo' => '17010301002881513',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payUrl' => 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?cipher_data=E3NPB9BtKZX/Gkf5hagKJbs',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰為空字串
     */
    public function testPayGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $result = [
            'sign' => 'dJP1Q1pglJ6Xtq/o=',
            'expireTime' => '60',
            'retCode' => '0000',
            'tradeNo' => '17010301002881513',
            'retMsg' => '处理成功!',
            'payUrl' => 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?cipher_data=E3NPB9BtKZX/Gkf5hagKJbs',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => '',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰失敗
     */
    public function testPayGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $result = [
            'sign' => 'dJP1Q1pglJ6Xtq/o=',
            'expireTime' => '60',
            'retCode' => '0000',
            'tradeNo' => '17010301002881513',
            'retMsg' => '处理成功!',
            'payUrl' => 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?cipher_data=E3NPB9BtKZX/Gkf5hagKJbs',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => '123456',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時返回簽名驗證錯誤
     */
    public function testBankPayReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'sign' => 'dJP1Q1pglJ6Xtq/o=',
            'expireTime' => '60',
            'retCode' => '0000',
            'tradeNo' => '17010301002881513',
            'retMsg' => '处理成功!',
            'payUrl' => 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?cipher_data=E3NPB9BtKZX/Gkf5hagKJbs',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時返回缺少payUrl
     */
    public function testBankPayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'expireTime' => '60',
            'retCode' => '0000',
            'tradeNo' => '17010301002881513',
            'retMsg' => '处理成功!',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時返回缺少cipher_data
     */
    public function testBankPayReturnWithoutCipherData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'expireTime' => '60',
            'retCode' => '0000',
            'tradeNo' => '17010301002881513',
            'retMsg' => '处理成功!',
            'payUrl' => 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?data=E3NPB9BtKZX/Gkf5hagKJbs',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $result = [
            'expireTime' => '60',
            'retCode' => '0000',
            'tradeNo' => '17010301002881513',
            'retMsg' => '处理成功!',
            'payUrl' => 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?cipher_data=E3NPB9BtKZX/Gkf5hagKJbs',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $data = $heYiPay->getVerifyData();

        $this->assertEquals('http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi', $data['post_url']);
        $this->assertEquals('E3NPB9BtKZX/Gkf5hagKJbs', $data['params']['cipher_data']);
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'verify_url' => '',
            'rsa_private_key' => $this->privateKey,
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少retCode
     */
    public function testQrcodePayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'sign' => 'dJP1Q1pglJ6Xtq/o=',
            'expireTime' => '60',
            'orderDate' => '20161227',
            'merId' => '10000012',
            'tradeNo' => '17010301002881513',
            'retMsg' => '处理成功!',
            'codeUrl' => 'https://qr.alipay.com/bax04265pynadnmvjgg080cf',
            'merOrderId' => '2016000012270011',
            'notifyUrl' => 'http://keithtest.comxa.com/pay/return.php',
            'goodsName' => 'php1test',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQrcodePayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '第三方平台支付失败',
            180130
        );

        $result = [
            'rpid' => 'R148366887194800000',
            'retMsg' => '第三方平台支付失败',
            'retCode' => '603001',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少Sign
     */
    public function testQrcodePayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'expireTime' => '60',
            'orderDate' => '20161227',
            'merId' => '10000012',
            'tradeNo' => '17010301002881513',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'codeUrl' => 'https://qr.alipay.com/bax04265pynadnmvjgg080cf',
            'merOrderId' => '2016000012270011',
            'notifyUrl' => 'http://keithtest.comxa.com/pay/return.php',
            'goodsName' => 'php1test',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回簽名驗證錯誤
     */
    public function testQrcodePayReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'sign' => 'dJP1Q1pglJ6Xtq/o=',
            'expireTime' => '60',
            'orderDate' => '20161227',
            'merId' => '10000012',
            'tradeNo' => '17010301002881513',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'codeUrl' => 'https://qr.alipay.com/bax04265pynadnmvjgg080cf',
            'merOrderId' => '2016000012270011',
            'notifyUrl' => 'http://keithtest.comxa.com/pay/return.php',
            'goodsName' => 'php1test',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少codeUrl
     */
    public function testQrcodePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'expireTime' => '60',
            'orderDate' => '20161227',
            'merId' => '10000012',
            'tradeNo' => '17010301002881513',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'merOrderId' => '2016000012270011',
            'notifyUrl' => 'http://keithtest.comxa.com/pay/return.php',
            'goodsName' => 'php1test',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'expireTime' => '60',
            'orderDate' => '20161227',
            'merId' => '10000012',
            'tradeNo' => '17010301002881513',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'codeUrl' => 'https://qr.alipay.com/bax04265pynadnmvjgg080cf',
            'merOrderId' => '2016000012270011',
            'notifyUrl' => 'http://keithtest.comxa.com/pay/return.php',
            'goodsName' => 'php1test',
            'orderAmt' => '100',
            'version' => 'V1.0',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '192.168.121.133',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $data = $heYiPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.alipay.com/bax04265pynadnmvjgg080cf', $heYiPay->getQrcode());
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

        $heYiPay = new HeYiPay();
        $heYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'retCode' => '0000',
            'retMsg' => 'SUCCESS',
            'merId' => '10000012',
            'merOrderId' => '201701050000000996',
            'tradeNo' => '17010301002881481',
            'payDate' => '20170105',
            'stlDate' => '20170105',
            'orderAmt' => '10',
            'cardType' => '1',
            'version' => 'V1.0',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->verifyOrderPayment([]);
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
            'sign' => '2b5da424a39752d5ff8bfe1743f8b078',
            'retCode' => '0000',
            'retMsg' => 'SUCCESS',
            'merId' => '10000012',
            'merOrderId' => '201701050000000996',
            'tradeNo' => '17010301002881481',
            'payDate' => '20170105',
            'stlDate' => '20170105',
            'orderAmt' => '10',
            'cardType' => '1',
            'version' => 'V1.0',
            'rsa_public_key' => '',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->verifyOrderPayment([]);
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
            'sign' => '2b5da424a39752d5ff8bfe1743f8b078',
            'retCode' => '0000',
            'retMsg' => 'SUCCESS',
            'merId' => '10000012',
            'merOrderId' => '201701050000000996',
            'tradeNo' => '17010301002881481',
            'payDate' => '20170105',
            'stlDate' => '20170105',
            'orderAmt' => '10',
            'cardType' => '1',
            'version' => 'V1.0',
            'rsa_public_key' => '123456',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'sign' => '2b5da424a39752d5ff8bfe1743f8b078',
            'retCode' => '0000',
            'retMsg' => 'SUCCESS',
            'merId' => '10000012',
            'merOrderId' => '201701050000000996',
            'tradeNo' => '17010301002881481',
            'payDate' => '20170105',
            'stlDate' => '20170105',
            'orderAmt' => '10',
            'cardType' => '1',
            'version' => 'V1.0',
            'rsa_public_key' => $this->publicKey,
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'retCode' => '1111',
            'retMsg' => 'SUCCESS',
            'merId' => '10000012',
            'merOrderId' => '201701050000000996',
            'tradeNo' => '17010301002881481',
            'payDate' => '20170105',
            'stlDate' => '20170105',
            'orderAmt' => '10',
            'cardType' => '1',
            'version' => 'V1.0',
        ];

        $sourceData['sign'] = $this->encode($sourceData);
        $sourceData['rsa_public_key'] = $this->publicKey;

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'retCode' => '0000',
            'retMsg' => 'SUCCESS',
            'merId' => '10000012',
            'merOrderId' => '201701050000000996',
            'tradeNo' => '17010301002881481',
            'payDate' => '20170105',
            'stlDate' => '20170105',
            'orderAmt' => '10',
            'cardType' => '1',
            'version' => 'V1.0',
        ];

        $sourceData['sign'] = $this->encode($sourceData);
        $sourceData['rsa_public_key'] = $this->publicKey;

        $entry = ['id' => '201701050000000999'];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'retCode' => '0000',
            'retMsg' => 'SUCCESS',
            'merId' => '10000012',
            'merOrderId' => '201701050000000996',
            'tradeNo' => '17010301002881481',
            'payDate' => '20170105',
            'stlDate' => '20170105',
            'orderAmt' => '10',
            'cardType' => '1',
            'version' => 'V1.0',
        ];

        $sourceData['sign'] = $this->encode($sourceData);
        $sourceData['rsa_public_key'] = $this->publicKey;

        $entry = [
            'id' => '201701050000000996',
            'amount' => '0.01',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'retCode' => '0000',
            'retMsg' => 'SUCCESS',
            'merId' => '10000012',
            'merOrderId' => '201701050000000996',
            'tradeNo' => '17010301002881481',
            'payDate' => '20170105',
            'stlDate' => '20170105',
            'orderAmt' => '10',
            'cardType' => '1',
            'version' => 'V1.0',
        ];

        $sourceData['sign'] = $this->encode($sourceData);
        $sourceData['rsa_public_key'] = $this->publicKey;

        $entry = [
            'id' => '201701050000000996',
            'amount' => '0.1',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $heYiPay->getMsg());
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $heYiPay = new HeYiPay();
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得RSA私鑰為空字串
     */
    public function testTrackingGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'rsa_private_key' => '',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢取得RSA私鑰失敗
     */
    public function testTrackingGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'rsa_private_key' => '123456',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢時產生簽名失敗
     */
    public function testTrackingGenerateSignatureFailure()
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
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回未指定返回參數
     */
    public function testTrackingReturnNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'sign' => 'MH1c8GNVUbw0ieh+FxgPLng1LBWLk6I0TenI5DB5gKcs=',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為請求處理失敗
     */
    public function testTrackingReturnRequestError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查询的订单不存在',
            180123
        );

        $result = [
            'rpid' => 'R148394842551400000',
            'retMsg' => '查询的订单不存在',
            'retCode' => '601013',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果Sign為空
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-15 17:15:10',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'sign' => 'test',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '201701050000000996',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-30 17:15:10',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = [
            'status' => '1',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '201701050000000996',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-30 17:15:10',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'status' => '3',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '201701050000000996',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-30 17:15:10',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '201701050000000999',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-30 17:15:10',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '201701050000000996',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-30 17:15:10',
            'amount' => '1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '201701050000000996',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '10000012',
            'orderId' => '201701050000000996',
            'orderCreateDate' => '2016-12-30 17:15:10',
            'amount' => '0.1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setContainer($this->container);
        $heYiPay->setClient($this->client);
        $heYiPay->setResponse($response);
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢時需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $heYiPay = new HeYiPay();
        $heYiPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '100120',
            'orderId' => '201612190000000734',
            'orderCreateDate' => '20161219120031',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '100120',
            'orderId' => '201612190000000734',
            'orderCreateDate' => '20161219120031',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $trackingData = $heYiPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/paygate/api', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals($sourceData['number'], $trackingData['form']['merId']);
        $this->assertEquals('1021', $trackingData['form']['funCode']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['merOrderId']);
    }

    /**
     * 測試驗證訂單查詢但查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'sign' => 'MH1c8GNVUbw0ieh+FxgPLng1LBWLk6I0TenI5DB5gKcs=',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $sourceData = ['content' => json_encode($result)];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回結果為請求處理失敗
     */
    public function testPaymentTrackingVerifyWithRequestError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查询的订单不存在',
            180123
        );

        $result = [
            'rpid' => 'R148394842551400000',
            'retMsg' => '查询的订单不存在',
            'retCode' => '601013',
        ];

        $sourceData = ['content' => json_encode($result)];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $sourceData = ['content' => json_encode($result)];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但簽名驗證錯誤
     */
    public function testPaymentTrackingVerifyWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'sign' => 'test',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單處理中
     */
    public function testPaymentTrackingVerifyWithOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = [
            'status' => '1',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢失敗
     */
    public function testPaymentTrackingVerifyWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'status' => '3',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'orderId' => '201612190000000734',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'orderId' => '2016000012300001',
            'amount' => '1',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $result = [
            'status' => '2',
            'tradeNo' => '16120301002881351',
            'orderDate' => '20161230',
            'merId' => '10000012',
            'cardType' => '1',
            'version' => 'V1.0',
            'retCode' => '0000',
            'retMsg' => '处理成功!',
            'payType' => 'BKP',
            'refundAmt' => '0',
            'merOrderId' => '2016000012300001',
            'orderAmt' => '10',
            'goodsName' => 'php1test',
        ];

        $result['sign'] = $this->encode($result);

        $sourceData = [
            'content' => json_encode($result),
            'rsa_public_key' => $this->publicKey,
            'orderId' => '2016000012300001',
            'amount' => '0.1',
        ];

        $heYiPay = new HeYiPay();
        $heYiPay->setOptions($sourceData);
        $heYiPay->paymentTrackingVerify();
    }

    /**
     * 組成回傳的sign
     *
     * @param array $encodeData
     * @return string
     */
    private function encode($encodeData)
    {
        $passphrase = '';

        $content = trim(base64_decode($this->privateKey));

        $privateKey = openssl_pkey_get_private($content, $passphrase);

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';

        openssl_sign($encodeStr, $sign, $privateKey, OPENSSL_ALGO_SHA1);

        return base64_encode($sign);
    }
}
