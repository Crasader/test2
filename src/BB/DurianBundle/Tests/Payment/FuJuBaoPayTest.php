<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\FuJuBaoPay;
use Buzz\Message\Response;

class FuJuBaoPayTest extends DurianTestCase
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
     * 出款參數
     *
     * @var array
     */
    private $withdrawParams;

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

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

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

        $this->withdrawParams = [
            'number' => '2017060800000615',
            'shop_url' => 'http://pay.test.com/pay/',
            'orderId' => '100000027',
            'amount' => '10',
            'nameReal' => 'php1test',
            'account' => '12345678910',
            'bank_info_id' => '11',
            'bank_name' => 'test',
            'province' => 'test',
            'city' => 'test',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];
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

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'amount' => '0.01',
            'paymentVendorId' => '99999',
            'rsa_private_key' => $this->privateKey,
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getVerifyData();
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
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '2017041111111',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'rsa_private_key' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getVerifyData();
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
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '2017041111111',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'rsa_private_key' => base64_decode($this->publicKey),
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getVerifyData();
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
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '2017041111111',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respType' => 'R',
            'transTime' => '',
            'merchantId' => '2017051200000422',
            'outOrderId' => '201706210000003082',
            'sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz',
            'transAmt' => '1',
            'respMsg' => '处理中',
            'localOrderId' => '2017062204294317',
            'scanType' => '20000002',
            'payCode' => 'weixin://wxpay/bizpayurl?pr=6ILmM9m',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'pay.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '不支持的支付类型',
            180130
        );

        $result = [
            'respType' => 'E',
            'merchantId' => '2017051200000422',
            'outOrderId' => '201706220000003093',
            'sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz',
            'transAmt' => '1',
            'respMsg' => '不支持的支付类型',
            'respCode' => '95',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706220000003093',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'pay.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少payCode
     */
    public function testPayReturnWithoutPayCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respType' => 'R',
            'transTime' => '',
            'merchantId' => '2017051200000422',
            'outOrderId' => '201706210000003082',
            'sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz',
            'transAmt' => '1',
            'respMsg' => '处理中',
            'localOrderId' => '2017062204294317',
            'scanType' => '20000002',
            'respCode' => '99',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'pay.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getVerifyData();
    }

    /**
     * 測試QQ支付
     */
    public function testQQPay()
    {
        $result = [
            'respType' => 'R',
            'transTime' => '',
            'merchantId' => '2017051200000422',
            'outOrderId' => '201706210000003082',
            'sign' => 'yr%2Fw3QPo7FzjQmwR13oBtBoeOxedRFdl2EHGTXHquO21IFArvjLHnw42iIz',
            'transAmt' => '1',
            'respMsg' => '处理中',
            'localOrderId' => '2017062204294317',
            'scanType' => '20000002',
            'payCode' => 'https://qpay.qq.com/qr/563de2b3',
            'respCode' => '99',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201706210000003082',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'pay.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $data = $fuJuBaoPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/563de2b3', $fuJuBaoPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'postUrl' => 'uyinpay.com',
            'number' => '2017060800000615',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '201801220000009188',
            'amount' => '1',
            'paymentVendorId' => '1104',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'pay.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $data = $fuJuBaoPay->getVerifyData();

        $this->assertEquals('https://payment.uyinpay.com/sfpay/h5PayServlet', $data['post_url']);
        $this->assertEquals('2017060800000615', $data['params']['merchantId']);
        $this->assertEquals('http://payment.pz-hero.com/return.php', $data['params']['notifyUrl']);
        $this->assertEquals('201801220000009188', $data['params']['outOrderId']);
        $this->assertEquals('201801220000009188', $data['params']['subject']);
        $this->assertEquals('201801220000009188', $data['params']['body']);
        $this->assertEquals('1', $data['params']['transAmt']);
        $this->assertNotNull($data['params']['sign']);
    }

    /**
     * 測試銀聯在線支付
     */
    public function testQuickPay()
    {
        $sourceData = [
            'postUrl' => 'fujubaopay.com',
            'number' => '2017060800000615',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'orderId' => '201805030189163216',
            'amount' => '0.01',
            'paymentVendorId' => '278',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $requestData = $fuJuBaoPay->getVerifyData();

        $this->assertEquals('https://payment.fujubaopay.com/sfpay/fastUnionPayServlet', $requestData['post_url']);
        $this->assertEquals('2017060800000615', $requestData['params']['merchantId']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $requestData['params']['notifyUrl']);
        $this->assertEquals('201805030189163216', $requestData['params']['outOrderId']);
        $this->assertEquals('201805030189163216', $requestData['params']['subject']);
        $this->assertEquals('201805030189163216', $requestData['params']['body']);
        $this->assertEquals('0.01', $requestData['params']['transAmt']);
        $this->assertNotNull($requestData['params']['sign']);
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $sourceData = [
            'postUrl' => 'uyinpay.com',
            'number' => '2017051200000422',
            'notify_url' => 'http://payment.pz-hero.com/return.php',
            'orderId' => '2017041111111',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'rsa_public_key' => $this->publicKey,
            'rsa_private_key' => $this->privateKey,
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $requestData = $fuJuBaoPay->getVerifyData();

        $this->assertEquals('https://payment.uyinpay.com/sfpay/payServlet', $requestData['post_url']);
        $this->assertEquals('2017051200000422', $requestData['params']['merchantId']);
        $this->assertEquals('http://payment.pz-hero.com/return.php', $requestData['params']['notifyUrl']);
        $this->assertEquals('http://payment.pz-hero.com/return.php', $requestData['params']['returnUrl']);
        $this->assertEquals('2017041111111', $requestData['params']['outOrderId']);
        $this->assertEquals('2017041111111', $requestData['params']['subject']);
        $this->assertEquals('2017041111111', $requestData['params']['body']);
        $this->assertEquals('0.01', $requestData['params']['transAmt']);
        $this->assertEquals('01020000', $requestData['params']['defaultBank']);
        $this->assertEquals('B2C', $requestData['params']['channel']);
        $this->assertEquals('1', $requestData['params']['cardAttr']);
        $this->assertNotNull($requestData['params']['sign']);
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

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回Sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
            'rsa_public_key' => '',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家公鑰為空字串
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $sourceData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'sign' => 'gqcxdDzb48NTFvNelKN0c8pShXr/D6G2yqPzI6j3vjkJVgQEVc',
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
            'rsa_public_key' => '',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家公鑰失敗
     */
    public function testReturnGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $sourceData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'sign' => 'gqcxdDzb48NTFvNelKN0c8pShXr/D6G2yqPzI6j3vjkJVgQEVc',
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
            'rsa_public_key' => 'test',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->verifyOrderPayment([]);
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
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'sign' => 'gqcxdDzb48NTFvNelKN0c8pShXr/D6G2yqPzI6j3vjkJVgQEVc',
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
            'rsa_public_key' => $this->publicKey,
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->verifyOrderPayment([]);
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

        $encodeData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'respMsg' => '交易失敗',
            'localOrderId' => '2018032205131538',
            'respCode' => '04',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $sourceData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'sign' => base64_encode($sign),
            'respMsg' => '交易失敗',
            'localOrderId' => '2018032205131538',
            'respCode' => '04',
            'rsa_public_key' => $this->publicKey,
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $sourceData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'sign' => base64_encode($sign),
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = ['id' => '2014052200123'];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $sourceData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'sign' => base64_encode($sign),
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201803220000004477',
            'amount' => '1.0000',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $encodeData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $sourceData = [
            'respType' => 'S',
            'transTime' => '20180322112557',
            'merchantId' => '2017060800000615',
            'outOrderId' => '201803220000004477',
            'transAmt' => '0.10',
            'sign' => base64_encode($sign),
            'respMsg' => '交易成功',
            'localOrderId' => '2018032205131538',
            'respCode' => '00',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201803220000004477',
            'amount' => '0.1',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $fuJuBaoPay->getMsg());
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

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->paymentTracking();
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

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
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
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為缺少回傳參數respType
     */
    public function testPaymentTrackingResultWithoutRespType()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'oriRespType' => 'R',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '处理中',
            'outOrderId' => '201706270000003195',
            'transAmt' => '1.00',
            'sign' => 'hmgRS2z6XierGYA==',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062704295227',
            'respCode' => '00',
            'queryId' => '201706270000003195',
            'oriRespCode' => '99',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為查詢訂單不存在
     */
    public function testTrackingReturnRequestError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查询订单不存在',
            180123
        );

        $result = [
            'respType' => 'E',
            'oriRespType' => null,
            'merchantId' => '2017051200000422',
            'oriRespMsg' => null,
            'outOrderId' => '201706270000003196',
            'transAmt' => null,
            'sign' => 'YsgupATp2fHUsxqBGIYjhg==',
            'respMsg' => '查询订单不存在',
            'localOrderId' => null,
            'respCode' => '05',
            'queryId' => '201706270000003196',
            'oriRespCode' => null,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706220000003131',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為缺少回傳參數Sign
     */
    public function testPaymentTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respType' => 'S',
            'oriRespType' => 'S',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '交易成功',
            'outOrderId' => '201706210000003082',
            'transAmt' => '0.10',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062104294147',
            'respCode' => '00',
            'queryId' => '201706210000003082',
            'oriRespCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
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
            'respType' => 'S',
            'oriRespType' => 'S',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '交易成功',
            'outOrderId' => '201706210000003082',
            'transAmt' => '0.10',
            'sign' => 'JhurTpIM7QIkIVKA==',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062104294147',
            'respCode' => '00',
            'queryId' => '201706210000003082',
            'oriRespCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $encodeData = [
            'respType' => 'S',
            'oriRespType' => 'R',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '处理中',
            'outOrderId' => '201706270000003195',
            'transAmt' => '1.00',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062704295227',
            'respCode' => '00',
            'queryId' => '201706270000003195',
            'oriRespCode' => '99',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'respType' => 'S',
            'oriRespType' => 'R',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '处理中',
            'outOrderId' => '201706270000003195',
            'transAmt' => '1.00',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062704295227',
            'respCode' => '00',
            'queryId' => '201706270000003195',
            'oriRespCode' => '99',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
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

        $encodeData = [
            'respType' => 'S',
            'oriRespType' => 'R',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '处理中',
            'outOrderId' => '201706270000003195',
            'transAmt' => '1.00',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062704295227',
            'respCode' => '00',
            'queryId' => '201706270000003195',
            'oriRespCode' => '97',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'respType' => 'S',
            'oriRespType' => 'R',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '处理中',
            'outOrderId' => '201706270000003195',
            'transAmt' => '1.00',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062704295227',
            'respCode' => '00',
            'queryId' => '201706270000003195',
            'oriRespCode' => '97',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢單號錯誤
     */
    public function testPaymentTrackingOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'respType' => 'S',
            'oriRespType' => 'S',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '交易成功',
            'outOrderId' => '201706210000003082',
            'transAmt' => '0.10',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062104294147',
            'respCode' => '00',
            'queryId' => '201706210000003082',
            'oriRespCode' => '00',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'respType' => 'S',
            'oriRespType' => 'S',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '交易成功',
            'outOrderId' => '201706210000003082',
            'transAmt' => '0.10',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062104294147',
            'respCode' => '00',
            'queryId' => '201706210000003082',
            'oriRespCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003081',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單金額錯誤
     */
    public function testPaymentTrackingOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'respType' => 'S',
            'oriRespType' => 'S',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '交易成功',
            'outOrderId' => '201706210000003082',
            'transAmt' => '0.10',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062104294147',
            'respCode' => '00',
            'queryId' => '201706210000003082',
            'oriRespCode' => '00',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'respType' => 'S',
            'oriRespType' => 'S',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '交易成功',
            'outOrderId' => '201706210000003082',
            'transAmt' => '0.10',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062104294147',
            'respCode' => '00',
            'queryId' => '201706210000003082',
            'oriRespCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'amount' => '1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeData = [
            'respType' => 'S',
            'oriRespType' => 'S',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '交易成功',
            'outOrderId' => '201706210000003082',
            'transAmt' => '0.10',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062104294147',
            'respCode' => '00',
            'queryId' => '201706210000003082',
            'oriRespCode' => '00',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = [
            'sign' => base64_encode($sign),
            'respType' => 'S',
            'oriRespType' => 'S',
            'merchantId' => '2017051200000422',
            'oriRespMsg' => '交易成功',
            'outOrderId' => '201706210000003082',
            'transAmt' => '0.10',
            'respMsg' => '查询成功',
            'localOrderId' => '2017062104294147',
            'respCode' => '00',
            'queryId' => '201706210000003082',
            'oriRespCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'amount' => '0.1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->paymentTracking();
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

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->getPaymentTrackingData();
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
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $fuJuBaoPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '2017051200000422',
            'orderId' => '201706210000003082',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.test.com',
        ];

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($sourceData);
        $trackingData = $fuJuBaoPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/order/query', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($sourceData['number'], $trackingData['form']['merchantId']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['queryId']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['outOrderId']);
        $this->assertNotEmpty($trackingData['form']['sign']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試出款未指定出款參數
     */
    public function testWithdrawNoWithdrawParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw parameter specified',
            150180196
        );

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions([]);
        $fuJuBaoPay->withdrawPayment();
    }

    /**
     * 測試出款沒有帶入Withdraw_host
     */
    public function testWithdrawWithoutWithdrawHost()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw_host specified',
            150180194
        );

        $this->withdrawParams['withdraw_host'] = '';

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($this->withdrawParams);
        $fuJuBaoPay->withdrawPayment();
    }

    /**
     * 測試出款帶入未支援的出款銀行
     */
    public function testWithdrawBankInfoNotSupported()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'BankInfo is not supported by PaymentGateway',
            150180195
        );

        $this->withdrawParams['bank_info_id'] = '66666';

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($this->withdrawParams);
        $fuJuBaoPay->withdrawPayment();
    }

    /**
     * 測試出款產生簽名失敗
     */
    public function testWithdrawGenerateSignatureFailure()
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

        $this->withdrawParams['rsa_private_key'] = base64_encode($privateKey);

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setOptions($this->withdrawParams);
        $fuJuBaoPay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少參數
     */
    public function testWithdrawButNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($this->withdrawParams);
        $fuJuBaoPay->withdrawPayment();
    }

    /**
     * 測試出款餘額不足
     */
    public function testWithdrawInsufficientBalance()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Insufficient balance',
            150180197
        );

        $result = [
            'respType' => 'E',
            'transTime' => null,
            'merchantId' => '2017060800000615',
            'outOrderId' => '100000030',
            'transAmt' => '10000.00',
            'transDate' => null,
            'sign' => 'E4nwLIn9KmuCf',
            'respMsg' => '代付金额加代付手续费大于账号余额',
            'localOrderId' => null,
            'channelId' => '0',
            'respCode' => '20',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($this->withdrawParams);
        $fuJuBaoPay->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '不允许重复提交订单',
            180124
        );

        $result = [
            'respType' => 'E',
            'transTime' => null,
            'merchantId' => '2017060800000615',
            'outOrderId' => '100000029',
            'transAmt' => '10.10',
            'transDate' => null,
            'sign' => 'E4nwLIn9KmuCf',
            'respMsg' => '不允许重复提交订单',
            'localOrderId' => null,
            'channelId' => '0',
            'respCode' => '02',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($this->container);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($this->withdrawParams);
        $fuJuBaoPay->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $result = [
            'respType' => 'R',
            'transTime' => '090757',
            'merchantId' => '2017060800000615',
            'outOrderId' => '100000027',
            'transAmt' => '10.00',
            'transDate' => '20180521',
            'sign' => 'E4nwLIn9KmuCf',
            'respMsg' => '处理中',
            'localOrderId' => '20180521000005607608',
            'channelId' => '0',
            'respCode' => '99',
        ];

        $mockCwe = $this->getMockBuilder('BB\DurianBundle\Entity\CashWithdrawEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCwe->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCwe);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCwe);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $fuJuBaoPay = new FuJuBaoPay();
        $fuJuBaoPay->setContainer($mockContainer);
        $fuJuBaoPay->setClient($this->client);
        $fuJuBaoPay->setResponse($response);
        $fuJuBaoPay->setOptions($this->withdrawParams);
        $fuJuBaoPay->withdrawPayment();
    }
}
