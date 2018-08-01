<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Mokepay;
use Buzz\Message\Response;

class MokepayTest extends DurianTestCase
{
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
            180142
        );

        $mokepay = new Mokepay();
        $mokepay->getVerifyData();
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

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '99',
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定platformID
     */
    public function testPayWithoutMerchantExtraPlatformID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => [],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $requestData = $mokepay->getVerifyData();

        $this->assertEquals('acctest', $requestData['merchNo']);
        $this->assertEquals('201605270000002562', $requestData['orderNo']);
        $this->assertEquals('20160527', $requestData['tradeDate']);
        $this->assertEquals('100', $requestData['amt']);
        $this->assertEquals('http://www.mokepay.cn/return.php', $requestData['merchUrl']);
        $this->assertEquals('35660_6', $requestData['merchParam']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
    }

    /**
     * 測試銀聯在線
     */
    public function testUnionPay()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '278',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $requestData = $mokepay->getVerifyData();

        $this->assertEquals('acctest', $requestData['merchNo']);
        $this->assertEquals('201605270000002562', $requestData['orderNo']);
        $this->assertEquals('20160527', $requestData['tradeDate']);
        $this->assertEquals('100', $requestData['amt']);
        $this->assertEquals('http://www.mokepay.cn/return.php', $requestData['merchUrl']);
        $this->assertEquals('35660_6', $requestData['merchParam']);
    }

    /**
     * 測試支付銀行為微信二維，但沒帶入verify_url
     */
    public function testPayWithWeiXinWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => '',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但沒返回signMsg
     */
    public function testPayWithWeiXinWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respDesc>交易成功</respDesc>' .
            '<codeUrl>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPWp1NzRtMlM=</codeUrl>' .
            '</respData>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但返回錯誤的簽名
     */
    public function testPayWithWeiXinSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<codeUrl>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPWp1NzRtMlM=</codeUrl>' .
            '</respData>' .
            '<signMsg>94870857</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但沒返回respCode
     */
    public function testPayWithWeiXinWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respDesc>交易成功</respDesc>' .
            '<codeUrl>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPWp1NzRtMlM=</codeUrl>' .
            '</respData>' .
            '<signMsg>1588E2B86F27163DEAD65CEA2EB75106</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但沒返回respDesc
     */
    public function testPayWithWeiXinWithoutRespDesc()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<codeUrl>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPWp1NzRtMlM=</codeUrl>' .
            '</respData>' .
            '<signMsg>1A501199605DF661349230B926AD4965</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但返回商戶未開啟微信二維直連通道
     */
    public function testPayWithWeiXinButReturnQrcodeNotSupported()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Qrcode not support',
            150180190
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>307</respCode>' .
            '<respDesc>商户未开通该支付方式</respDesc>' .
            '</respData>' .
            '<signMsg>307D212B69F8CDDA6728B635C69BC249</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但返回失敗
     */
    public function testPayWithWeiXinButReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败（交易数据不正确）',
            180130
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>05</respCode>' .
            '<respDesc>交易失败（交易数据不正确）</respDesc>' .
            '</respData>' .
            '<signMsg>FD2C775AE8ABB5AD02DFAA74C6DE6355</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，沒返回codeUrl
     */
    public function testPayWithWeiXinWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '</respData>' .
            '<signMsg>81BFCF0C7D896DE542E225D6E32898EE</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維
     */
    public function testPayWithWeiXin()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<codeUrl>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPWp1NzRtMlM=</codeUrl>' .
            '</respData>' .
            '<signMsg>46CF46B91AE481C351C2A65374B2A6A8</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $requestData = $mokepay->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=ju74m2S', $mokepay->getQrcode());
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $mokepay = new Mokepay();
        $mokepay->verifyOrderPayment([]);
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

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20160527112844',
            'tradeAmt' => '0.01',
            'merchNo' => '210000440001260',
            'merchParam' => '3142_6',
            'orderNo' => '201605270000002562',
            'tradeDate' => '20160527',
            'accNo' => '39880',
            'accDate' => '20160527',
            'orderStatus' => '1',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->verifyOrderPayment([]);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20160527112844',
            'tradeAmt' => '0.01',
            'merchNo' => '210000440001260',
            'merchParam' => '3142_6',
            'orderNo' => '201605270000002562',
            'tradeDate' => '20160527',
            'accNo' => '39880',
            'accDate' => '20160527',
            'orderStatus' => '1',
            'signMsg' => '1234',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->verifyOrderPayment([]);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20160527112844',
            'tradeAmt' => '0.01',
            'merchNo' => '210000440001260',
            'merchParam' => '3142_6',
            'orderNo' => '201605270000002562',
            'tradeDate' => '20160527',
            'accNo' => '39880',
            'accDate' => '20160527',
            'orderStatus' => '0',
            'signMsg' => 'b933e0f9317d5ed4879339f1e711429a',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20160527112844',
            'tradeAmt' => '0.01',
            'merchNo' => '210000440001260',
            'merchParam' => '3142_6',
            'orderNo' => '201605270000002562',
            'tradeDate' => '20160527',
            'accNo' => '39880',
            'accDate' => '20160527',
            'orderStatus' => '1',
            'signMsg' => '6c0b1fe29cfd975c159a590d21546859',
        ];

        $entry = ['id' => '201503220000000321'];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20160527112844',
            'tradeAmt' => '0.01',
            'merchNo' => '210000440001260',
            'merchParam' => '3142_6',
            'orderNo' => '201605270000002562',
            'tradeDate' => '20160527',
            'accNo' => '39880',
            'accDate' => '20160527',
            'orderStatus' => '1',
            'signMsg' => '6c0b1fe29cfd975c159a590d21546859',
        ];

        $entry = [
            'id' => '201605270000002562',
            'amount' => '10.00',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20160527112844',
            'tradeAmt' => '0.01',
            'merchNo' => '210000440001260',
            'merchParam' => '3142_6',
            'orderNo' => '201605270000002562',
            'tradeDate' => '20160527',
            'accNo' => '39880',
            'accDate' => '20160527',
            'orderStatus' => '1',
            'signMsg' => '6c0b1fe29cfd975c159a590d21546859',
        ];

        $entry = [
            'id' => '201605270000002562',
            'amount' => '0.01',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $mokepay->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $mokepay = new Mokepay();
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢缺少商家額外的參數設定platformID
     */
    public function testTrackingWithoutMerchantExtraPlatformID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => []
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $mokepay = new Mokepay();
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有respData的情況
     */
    public function testTrackingReturnWithoutRespData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有signMsg的情況
     */
    public function testTrackingReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount><respData></respData></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果驗證沒有respCode的情況
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData></respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單不存在
     */
    public function testTrackingReturnPaymentTrackingOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>22</respCode>' .
            '<respDesc>查询订单信息不存在[订单信息不存在]</respDesc>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;content-type:charset=utf-8');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
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

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>22</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20160527</orderDate>' .
            '<accDate>20160527</accDate>' .
            '<orderNo>39880</orderNo>' .
            '<accNo>39880</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果解密驗證錯誤
     */
    public function testTrackingReturnDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20160527</orderDate>' .
            '<accDate>20160527</accDate>' .
            '<orderNo>39880</orderNo>' .
            '<accNo>39880</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態為未支付
     */
    public function testTrackingReturnOrderPaymentUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '210000440001260',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '0.01',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易状态未明</respDesc>' .
            '<orderDate>20160530</orderDate>' .
            '<accDate>20160530</accDate>' .
            '<orderNo>49625</orderNo>' .
            '<accNo>49625</accNo>' .
            '<Status>0</Status>' .
            '</respData>' .
            '<signMsg>f7970c2ffe72b1634374317d2cbd19b5</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態不為1則代表支付失敗
     */
    public function testTrackingReturnOrderPaymentfailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20160527</orderDate>' .
            '<accDate>20160527</accDate>' .
            '<orderNo>39880</orderNo>' .
            '<accNo>39880</accNo>' .
            '<Status>2</Status>' .
            '</respData>' .
            '<signMsg>4839399835d1caf94f31636fc2692e27</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('test');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '210000440001260',
            'orderId' => '201605270000002562',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '0.0100',
            'merchant_extra' => ['platformID' => '210000440001260'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'merchantId' => '3142',
            'domain' => '6',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20160527</orderDate>' .
            '<accDate>20160527</accDate>' .
            '<orderNo>39880</orderNo>' .
            '<accNo>39880</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>E9DF411F7FD103E8DC10AE3616A0F818</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $mokepay = new Mokepay();
        $mokepay->setContainer($this->container);
        $mokepay->setClient($this->client);
        $mokepay->setResponse($response);
        $mokepay->setPrivateKey('813572f9adf12a80627db1b8371e0b2c');
        $mokepay->setOptions($options);
        $mokepay->paymentTracking();
    }
}
