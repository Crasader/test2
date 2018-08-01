<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewShangYinXin;
use Buzz\Message\Response;

class NewShangYinXinTest extends DurianTestCase
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

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->getVerifyData();
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
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '0.01',
            'paymentVendorId' => '99999',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->getVerifyData();
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

        $privateKey = '';
        // Get private key
        openssl_pkey_export($res, $privateKey);

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '2017041111111',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'ip' => '127.0.0.1',
            'rsa_private_key' => base64_encode($privateKey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQRcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '0.01',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($options);
        $newShangYinXin->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回reCode
     */
    public function testQRcodePayReturnWithoutReCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '0.01',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<ScanCode>' .
            '<merchantId>001016051700644</merchantId>' .
            '<outOrderId>201707170000003388</outOrderId>' .
            '<transAmt>20.34</transAmt>' .
            '<payMethod>default_alipay</payMethod>' .
            '<message>商户不支持支付宝支付</message>' .
            '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($options);
        $newShangYinXin->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户不支持支付宝支付',
            180130
        );

        $options = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '0.01',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<ScanCode>' .
            '<merchantId>001016051700644</merchantId>' .
            '<outOrderId>201707170000003388</outOrderId>' .
            '<transAmt>20.34</transAmt>' .
            '<payMethod>default_alipay</payMethod>' .
            '<reCode>FAIL</reCode>' .
            '<message>商户不支持支付宝支付</message>' .
            '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($options);
        $newShangYinXin->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回sign
     */
    public function testQRcodePayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '0.01',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<ScanCode>' .
            '<merchantId>1111130200</merchantId>' .
            '<outOrderId>201705051234</outOrderId>' .
            '<transAmt>0.01</transAmt>' .
            '<payMethod>default_wechat</payMethod>' .
            '<dateTime>20170717142638</dateTime>' .
            '<reCode>SUCCESS</reCode>' .
            '<message>成功</message>' .
            '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($options);
        $newShangYinXin->getVerifyData();
    }

    /**
     * 測試二維支付時返回驗簽失敗
     */
    public function testQRcodePayReturnWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '0.01',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<ScanCode>' .
            '<merchantId>1111130200</merchantId>' .
            '<outOrderId>201705051234</outOrderId>' .
            '<transAmt>0.01</transAmt>' .
            '<payMethod>default_wechat</payMethod>' .
            '<dateTime>20170717142638</dateTime>' .
            '<reCode>SUCCESS</reCode>' .
            '<message>成功</message>' .
            '<sign>ftYGJKy1orqPT7sNgOC4BbXYPRML2sn9AaQFg87CN0gmBKrqov0</sign>' .
            '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($options);
        $newShangYinXin->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回payCode
     */
    public function testQRcodePayReturnWithoutPayCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $encodeStr = 'dateTime=20170718144054&merchantId=001016051700644&message=成功&outOrderId=20170718000000' .
            '3418&payMethod=default_wechat&reCode=SUCCESS&transAmt=16.14';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));
        $sign = str_replace('+', '*', base64_encode($sign));
        $sign = str_replace('/', '-', $sign);

        $options = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '0.01',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<ScanCode>' .
            '<merchantId>001016051700644</merchantId>' .
            '<outOrderId>201707180000003418</outOrderId>' .
            '<transAmt>16.14</transAmt>' .
            '<payMethod>default_wechat</payMethod>' .
            '<dateTime>20170718144054</dateTime>' .
            '<reCode>SUCCESS</reCode>' .
            '<message>成功</message>' .
            '<sign>' . $sign . '</sign>' .
            '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($options);
        $newShangYinXin->getVerifyData();
    }

    /**
     * 測試微信二維
     */
    public function testWeiXinPay()
    {
        $encodeStr = 'dateTime=20170718144054&merchantId=001016051700644&message=成功&outOrderId=201707180000003' .
            '418&payCode=weixin://wxpay/bizpayurl?pr=xvVEVLq&payMethod=default_wechat&reCode=SUCCESS&transAmt=16.14';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));
        $sign = str_replace('+', '*', base64_encode($sign));
        $sign = str_replace('/', '-', $sign);

        $options = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '201705051234',
            'amount' => '0.01',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<ScanCode>' .
            '<merchantId>001016051700644</merchantId>' .
            '<outOrderId>201707180000003418</outOrderId>' .
            '<transAmt>16.14</transAmt>' .
            '<payMethod>default_wechat</payMethod>' .
            '<dateTime>20170718144054</dateTime>' .
            '<reCode>SUCCESS</reCode>' .
            '<message>成功</message>' .
            '<payCode>weixin://wxpay/bizpayurl?pr=xvVEVLq</payCode>' .
            '<sign>' . $sign . '</sign>' .
            '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($options);
        $data = $newShangYinXin->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=xvVEVLq', $newShangYinXin->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '2017041111111',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'ip' => '127.0.0.1',
            'rsa_private_key' => $this->privateKey,
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($sourceData);
        $encodeData = $newShangYinXin->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchantId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['outOrderId']);
        $this->assertEquals('2017041111111', $encodeData['subject']);
        $this->assertEquals($sourceData['amount'], $encodeData['transAmt']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notifyUrl']);
        $this->assertEquals('ICBC', $encodeData['defaultBank']);
        $this->assertEquals('directPay', $encodeData['service']);
        $this->assertEquals('RSA', $encodeData['signType']);
        $this->assertEquals('UTF-8', $encodeData['inputCharset']);
        $this->assertEquals('bankPay', $encodeData['payMethod']);
        $this->assertEquals('B2C', $encodeData['channel']);
        $this->assertEquals('01', $encodeData['cardAttr']);
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
            'body' => 'body',
            'notifyTime' => '20170504140857',
            'tradeStatus' => '1',
            'inputCharset' => 'UTF-8',
            'subject' => 'php1test',
            'transTime' => '20170504140817',
            'notifyId' => '51892609',
            'merchantId' => '001015112700437',
            'transAmt' => '0.01',
            'localOrderId' => '44434032',
            'outOrderId' => '201705040000001998',
            'rsa_public_key' => $this->publicKey,
        ];

        $newShangXinYin = new NewShangYinXin();
        $newShangXinYin->setOptions($sourceData);
        $newShangXinYin->verifyOrderPayment([]);
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
            'sign' => 'abcdefffhdkdoekdjsldfk',
            'body' => 'body',
            'notifyTime' => '20170504140857',
            'tradeStatus' => '2',
            'inputCharset' => 'UTF-8',
            'subject' => 'php1test',
            'transTime' => '20170504140817',
            'notifyId' => '51892609',
            'merchantId' => '001015112700437',
            'transAmt' => '0.01',
            'localOrderId' => '44434032',
            'outOrderId' => '201705040000001998',
            'rsa_public_key' => $this->publicKey,
        ];

        $newShangXinYin = new NewShangYinXin();
        $newShangXinYin->setOptions($sourceData);
        $newShangXinYin->verifyOrderPayment([]);
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
            'body' => 'body',
            'notifyTime' => '20170504140857',
            'tradeStatus' => '2',
            'inputCharset' => 'UTF-8',
            'subject' => 'php1test',
            'transTime' => '20170504140817',
            'notifyId' => '51892609',
            'merchantId' => '001015112700437',
            'transAmt' => '0.01',
            'localOrderId' => '44434032',
            'outOrderId' => '201705040000001998',
            'sign' => 'test',
            'rsa_public_key' => '',
        ];

        $newShangXinYin = new NewShangYinXin();
        $newShangXinYin->setOptions($sourceData);
        $newShangXinYin->verifyOrderPayment([]);
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
            'body' => 'body',
            'notifyTime' => '20170504140857',
            'tradeStatus' => '2',
            'inputCharset' => 'UTF-8',
            'subject' => 'php1test',
            'transTime' => '20170504140817',
            'notifyId' => '51892609',
            'merchantId' => '001015112700437',
            'transAmt' => '0.01',
            'localOrderId' => '44434032',
            'outOrderId' => '201705040000001998',
            'sign' => 'test',
            'rsa_public_key' => '123',
        ];

        $newShangXinYin = new NewShangYinXin();
        $newShangXinYin->setOptions($sourceData);
        $newShangXinYin->verifyOrderPayment([]);
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

        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=1&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $sourceData = [
            'body' => 'body',
            'notifyTime' => '20170504140857',
            'tradeStatus' => '1',
            'inputCharset' => 'UTF-8',
            'subject' => 'php1test',
            'transTime' => '20170504140817',
            'notifyId' => '51892609',
            'merchantId' => '001015112700437',
            'transAmt' => '0.01',
            'localOrderId' => '44434032',
            'outOrderId' => '201705040000001998',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $newShangXinYin = new NewShangYinXin();
        $newShangXinYin->setOptions($sourceData);
        $newShangXinYin->verifyOrderPayment([]);
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

        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=2&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $sourceData = [
            'body' => 'body',
            'notifyTime' => '20170504140857',
            'tradeStatus' => '2',
            'inputCharset' => 'UTF-8',
            'subject' => 'php1test',
            'transTime' => '20170504140817',
            'notifyId' => '51892609',
            'merchantId' => '001015112700437',
            'transAmt' => '0.01',
            'localOrderId' => '44434032',
            'outOrderId' => '201705040000001998',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = ['id' => '2014052200123'];

        $newShangXinYin = new NewShangYinXin();
        $newShangXinYin->setOptions($sourceData);
        $newShangXinYin->verifyOrderPayment($entry);
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

        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=2&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $sourceData = [
            'body' => 'body',
            'notifyTime' => '20170504140857',
            'tradeStatus' => '2',
            'inputCharset' => 'UTF-8',
            'subject' => 'php1test',
            'transTime' => '20170504140817',
            'notifyId' => '51892609',
            'merchantId' => '001015112700437',
            'transAmt' => '0.01',
            'localOrderId' => '44434032',
            'outOrderId' => '201705040000001998',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201705040000001998',
            'amount' => '1.0000',
        ];

        $newShangXinYin = new NewShangYinXin();
        $newShangXinYin->setOptions($sourceData);
        $newShangXinYin->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccess()
    {
        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=2&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $sourceData = [
            'body' => 'body',
            'notifyTime' => '20170504140857',
            'tradeStatus' => '2',
            'inputCharset' => 'UTF-8',
            'subject' => 'php1test',
            'transTime' => '20170504140817',
            'notifyId' => '51892609',
            'merchantId' => '001015112700437',
            'transAmt' => '0.01',
            'localOrderId' => '44434032',
            'outOrderId' => '201705040000001998',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201705040000001998',
            'amount' => '0.0100',
        ];

        $newShangXinYin = new NewShangYinXin();
        $newShangXinYin->setOptions($sourceData);
        $newShangXinYin->verifyOrderPayment($entry);

        $this->assertEquals('success', $newShangXinYin->getMsg());
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

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->paymentTracking();
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
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->paymentTracking();
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
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數 Pays
     */
    public function testPaymentTrackingResultWithoutPays()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<queryResult>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/><pays></pays></queryResult>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'paymenta.allscore.com',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->paymentTracking();
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

        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=2&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<queryResult>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<payOrderId>20170504140817887728</payOrderId>' .
            '<payStatus>ORDER_STATUS_PENDING</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';


        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'paymenta.allscore.com',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->paymentTracking();
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

        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=2&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<queryResult>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<payOrderId>20170504140817887728</payOrderId>' .
            '<payStatus>XX</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';


        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'paymenta.allscore.com',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->paymentTracking();
    }

    /**
     * 測試訂單查詢單號錯誤
     */
    public function testPaymentTrackingOlderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=2&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<queryResult>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201705040000001998999</srcOutOrderId>' .
            '<payOrderId>20170504140817887728</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';


        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'paymenta.allscore.com',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單金額錯誤
     */
    public function testPaymentTrackingOlderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=2&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<queryResult>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.05</tranAmt>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<payOrderId>20170504140817887728</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';


        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'paymenta.allscore.com',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeStr = 'body=body&inputCharset=UTF-8&localOrderId=44434032&merchantId=001015112700437&' .
            'notifyId=51892609&notifyTime=20170504140857&outOrderId=201705040000001998&subject=php1test&' .
            'tradeStatus=2&transAmt=0.01&transTime=20170504140817';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<queryResult>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201705040000001998</srcOutOrderId>' .
            '<payOrderId>20170504140817887728</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';


        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'paymenta.allscore.com',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setContainer($this->container);
        $newShangYinXin->setClient($this->client);
        $newShangYinXin->setResponse($response);
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->paymentTracking();
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

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->getPaymentTrackingData();
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
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($sourceData);
        $newShangYinXin->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $encodeStr = 'inputCharset=utf-8&merchantId=001015112700437&outOrderId=201705040000001998&service=orderQuery';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));
        $sign = urlencode(base64_encode(base64_encode($sign)));

        $sourceData = [
            'number' => '001015112700437',
            'orderId' => '201705040000001998',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.paymenta.allscore.com',
        ];

        $newShangYinXin = new NewShangYinXin();
        $newShangYinXin->setOptions($sourceData);
        $trackingData = $newShangYinXin->getPaymentTrackingData();

        $path = '/olgateway/orderQuery.htm?merchantId=001015112700437&outOrderId=201705040000001998&' .
            'service=orderQuery&inputCharset=utf-8&signType=RSA&sign=' . $sign;
        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }
}
