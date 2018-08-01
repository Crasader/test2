<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\HiPay;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class HiPayTest extends WebTestCase
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
     * 測試加密基本參數設定為指定支付參數
     */
    public function testSetEncodeSourceNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $hiPay = new HiPay();
        $hiPay->setOptions($sourceData);
        $hiPay->getVerifyData();
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
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '9911',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($sourceData);
        $hiPay->getVerifyData();
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
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1',
            'rsa_private_key' => $privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://tnndbear.net',
            'ip' => '111.235.135.54',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($sourceData);
        $hiPay->getVerifyData();
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
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1109',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => '',
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($sourceData);
        $hiPay->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少data
     */
    public function testPayParameterWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $content = '{"code":-1}';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hiPay = new HiPay();
        $hiPay->setContainer($this->container);
        $hiPay->setClient($this->client);
        $hiPay->setOptions($options);
        $hiPay->setResponse($response);
        $hiPay->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少code
     */
    public function testPayParameterWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $content = '{"data":{"sign":"OMt8sfrKDY9hAOHQxw6B89 E\\/O\\/qYpUisnfJxuiGSHfLLKBrWVghfQbJqCtxXZzXz' .
            'zWic=","trxNo":"202180130000926069","amount":"0.01","mp":"","orderNo":"201801300000000761","merId":"8' .
            '01100000002149","codeStream":"data:image\\/png;base64,iVBORw0KGgoAAAANSUhEUgAAANIAAADSAQAAAAAX4qPvAAA' .
            'Bv0lEQVR4u","url":"https:\\/\\/qr.95516.com\\/00010000\\/62252598771311217743462577624637","goodsName":' .
            '"php1test"}}';

        $response = new Response();
        $response->setContent(json_encode($content));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hiPay = new HiPay();
        $hiPay->setContainer($this->container);
        $hiPay->setClient($this->client);
        $hiPay->setOptions($options);
        $hiPay->setResponse($response);
        $hiPay->getVerifyData();
    }

    /**
     * 測試支付對外返回失敗
     */
    public function testPayParameterFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '未找到支付通道',
            180130
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $content = '{"code":-1,"data":[],"msg":"\u672a\u627e\u5230\u652f\u4ed8\u901a\u9053"}';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hiPay = new HiPay();
        $hiPay->setContainer($this->container);
        $hiPay->setClient($this->client);
        $hiPay->setOptions($options);
        $hiPay->setResponse($response);
        $hiPay->getVerifyData();
    }

    /**
     * 測試支付對外返回失敗沒返回msg
     */
    public function testPayParameterFailureWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $content = '{"code":-1,"data":[]}';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hiPay = new HiPay();
        $hiPay->setContainer($this->container);
        $hiPay->setClient($this->client);
        $hiPay->setOptions($options);
        $hiPay->setResponse($response);
        $hiPay->getVerifyData();
    }

    /**
     * 測試支付對外返回沒url
     */
    public function testPayParameterWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $content = '{"code":1,"data":{"sign":"OMt8sfrKDY9hAOHQxw6B89 E\\/O\\/qYpUisnfJxuiGSHfLLKBrWVghfQbJqCtxXZzXz' .
            'zWic=","trxNo":"202180130000926069","amount":"0.01","mp":"","orderNo":"201801300000000761","merId":"801' .
            '100000002149","codeStream":"data:image\\/png;base64,iVBORw0KGgoAAAANSUhEUgAAANIAAADSAQAAAAAX4qPvAAABv0l'.
            'EQVR4u","goodsName":"php1test"}}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hiPay = new HiPay();
        $hiPay->setContainer($this->container);
        $hiPay->setClient($this->client);
        $hiPay->setOptions($options);
        $hiPay->setResponse($response);
        $hiPay->getVerifyData();
    }

    /**
     * 測試微信二維支付
     */
    public function testWeiXinQrcodePay()
    {
        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1090',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $content = '{"code":1,"data":{"sign":"OMt8sfrKDY9hAOHQxw6B89 E\\/O\\/qYpUisnfJxuiGSHfLLKBrWVghfQbJqCtxXZzXz' .
            'zWic=","trxNo":"202180130000926069","amount":"0.01","mp":"","orderNo":"201801300000000761","merId":"8011' .
            '00000002149","codeStream":"data:image\\/png;base64,iVBORw0KGgoAAAANSUhEUgAAANIAAADSAQAAAAAX4qPvAAABv0lE' .
            'QVR4u","url":"https:\\/\\/qr.95516.com\\/00010000\\/622525987713112177434625776","goodsName":' .
            '"php1test"}}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hiPay = new HiPay();
        $hiPay->setContainer($this->container);
        $hiPay->setClient($this->client);
        $hiPay->setOptions($options);
        $hiPay->setResponse($response);
        $data = $hiPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/622525987713112177434625776', $hiPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($options);
        $verifyData = $hiPay->getVerifyData();

        $params = $verifyData['params'];
        $postUrl = $verifyData['post_url'];

        $this->assertEquals($options['number'], $params['merId']);
        $this->assertEquals($options['orderId'], $params['orderNo']);
        $this->assertEquals($options['amount'], $params['amount']);
        $this->assertEquals('B2C_ICBC', $params['payType']);
        $this->assertEquals($options['username'], $params['goodsName']);
        $this->assertEquals($options['notify_url'], $params['returnUrl']);
        $this->assertEquals($options['notify_url'], $params['notifyUrl']);
        $this->assertEquals($options['postUrl'] . '/gateway/init', $postUrl);

        $encodeStr = '0.10phptest194539487http://ii.love.cap/return.php2018012912345678B2C_ICBChttp://ii.love.cap/re' .
            'turn.php';
        openssl_sign($encodeStr, $veirfySign, $hiPay->getRsaPrivateKey(), OPENSSL_ALGO_MD5);
        $this->assertEquals(base64_encode($veirfySign), $params['sign']);
    }

    /**
     * 測試QQ手機支付
     */
    public function testQQH5Pay()
    {
        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1104',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://t.hipay100.com:9001',
            'ip' => '111.235.135.54',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($options);
        $verifyData = $hiPay->getVerifyData();

        $params = $verifyData['params'];
        $postUrl = $verifyData['post_url'];

        $this->assertEquals($options['number'], $params['merId']);
        $this->assertEquals($options['orderId'], $params['orderNo']);
        $this->assertEquals($options['amount'], $params['amount']);
        $this->assertEquals('QQ_H5', $params['payType']);
        $this->assertEquals($options['username'], $params['goodsName']);
        $this->assertArrayNotHasKey('returnUrl', $params);
        $this->assertEquals($options['notify_url'], $params['notifyUrl']);
        $this->assertEquals($options['postUrl'] . '/trade/jhpay', $postUrl);
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
            'trxNo' => '202180130000926069',
            'amount' => '0.01',
            'mp' => '',
            'orderNo' => '201801300000000761',
            'status' => 1,
            'merId' => '801100000002149',
            'payTime' => '2018-01-30 13:36:06',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'orderId' => '201801300000000761',
            'amount' => '0.01',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($returnData);
        $hiPay->verifyOrderPayment($entry);
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
            'sign' => '1122334',
            'trxNo' => '202180130000926069',
            'amount' => '0.01',
            'mp' => '',
            'orderNo' => '201801300000000761',
            'status' => '1',
            'merId' => '801100000002149',
            'payTime' => '2018-01-30 13:36:06',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'orderId' => '201801300000000761',
            'amount' => '0.01',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($returnData);
        $hiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知失敗
     */
    public function testReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeStr = '0.018011000000021492018013000000007612018-01-30 13:36:06-1202180130000926069';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $returnData = [
            'sign' => base64_encode($sign),
            'trxNo' => '202180130000926069',
            'amount' => '0.01',
            'mp' => '',
            'orderNo' => '201801300000000761',
            'status' => '-1',
            'merId' => '801100000002149',
            'payTime' => '2018-01-30 13:36:06',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($returnData);
        $hiPay->verifyOrderPayment($entry);
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

        $encodeStr = '0.018011000000021492018013000000007612018-01-30 13:36:061202180130000926069';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $returnData = [
            'sign' => base64_encode($sign),
            'trxNo' => '202180130000926069',
            'amount' => '0.01',
            'mp' => '',
            'orderNo' => '201801300000000761',
            'status' => '1',
            'merId' => '801100000002149',
            'payTime' => '2018-01-30 13:36:06',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000762',
            'amount' => '0.01',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($returnData);
        $hiPay->verifyOrderPayment($entry);
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

        $encodeStr = '0.018011000000021492018013000000007612018-01-30 13:36:061202180130000926069';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $returnData = [
            'sign' => base64_encode($sign),
            'trxNo' => '202180130000926069',
            'amount' => '0.01',
            'mp' => '',
            'orderNo' => '201801300000000761',
            'status' => '1',
            'merId' => '801100000002149',
            'payTime' => '2018-01-30 13:36:06',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '1.01',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($returnData);
        $hiPay->verifyOrderPayment($entry);
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

        $encodeStr = '0.018011000000021492018013000000007612018-01-30 13:36:061202180130000926069';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $returnData = [
            'sign' => base64_encode($sign),
            'trxNo' => '202180130000926069',
            'amount' => '0.01',
            'mp' => '',
            'orderNo' => '201801300000000761',
            'status' => '1',
            'merId' => '801100000002149',
            'payTime' => '2018-01-30 13:36:06',
            'rsa_public_key' => '',
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($returnData);
        $hiPay->verifyOrderPayment($entry);
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

        $encodeStr = '0.018011000000021492018013000000007612018-01-30 13:36:061202180130000926069';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $returnData = [
            'sign' => base64_encode($sign),
            'trxNo' => '202180130000926069',
            'amount' => '0.01',
            'mp' => '',
            'orderNo' => '201801300000000761',
            'status' => '1',
            'merId' => '801100000002149',
            'payTime' => '2018-01-30 13:36:06',
            'rsa_public_key' => 'gggg',
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($returnData);
        $hiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回成功
     */
    public function testReturnSuccess()
    {
        $encodeStr = '0.018011000000021492018013000000007612018-01-30 13:36:061202180130000926069';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $returnData = [
            'sign' => base64_encode($sign),
            'trxNo' => '202180130000926069',
            'amount' => '0.01',
            'mp' => '',
            'orderNo' => '201801300000000761',
            'status' => '1',
            'merId' => '801100000002149',
            'payTime' => '2018-01-30 13:36:06',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '0.01',
        ];

        $hiPay = new HiPay();
        $hiPay->setOptions($returnData);
        $hiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $hiPay->getMsg());
    }
}
