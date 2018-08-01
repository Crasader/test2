<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SwiftPass;
use Buzz\Message\Response;

class SwiftPassTest extends DurianTestCase
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

        $swiftPass = new SwiftPass();
        $swiftPass->getVerifyData();
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

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->getVerifyData();
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
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => '',
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '999',
        ];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->getVerifyData();
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

        $options = [
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => '',
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1090',
        ];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->getVerifyData();
    }

    /**
     * 測試對外支付返回預付單不是xml格式
     */
    public function testPrepayReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $options = [
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => 'payment.https.pay.swiftpass.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1090',
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有status的情況
     */
    public function testPrepayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => 'payment.https.pay.swiftpass.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1090',
        ];

        $result = '<xml></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->getVerifyData();
    }

    /**
     * 測試返回預付單時status不為0
     */
    public function testPrepayReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature error',
            180130
        );

        $options = [
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => 'payment.https.pay.swiftpass.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1090',
        ];

        $result = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<status><![CDATA[400]]></status>
<message><![CDATA[Signature error]]></message>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有result_code的情況
     */
    public function testPrepayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => 'payment.https.pay.swiftpass.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1090',
        ];

        $result = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->getVerifyData();
    }

    /**
     * 測試返回預付單時result_code不為0，且有錯誤訊息
     */
    public function testPrepayReturnResultCodeNotZeroWithErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户未开通[pay.tenpay.native]支付类型',
            180130
        );

        $options = [
            'number' => '103560018443',
            'orderId' => '201705190000006351',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => 'payment.https.pay.swiftpass.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1103',
        ];

        $result = <<<EOT
<xml><charset><![CDATA[UTF-8]]></charset>
<err_code><![CDATA[Auth valid fail]]></err_code>
<err_msg><![CDATA[商户未开通[pay.tenpay.native]支付类型]]></err_msg>
<mch_id><![CDATA[103560018443]]></mch_id>
<nonce_str><![CDATA[b5c2f27961888474fac5de38c09c7fe6]]></nonce_str>
<result_code><![CDATA[1]]></result_code>
<sign><![CDATA[E739746543EDCE4312E26D41E3ED4571]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->getVerifyData();
    }

    /**
     * 測試返回預付單時result_code不為0
     */
    public function testPrepayReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => 'payment.https.pay.swiftpass.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1090',
        ];

        $result = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<mch_id><![CDATA[162340474915]]></mch_id>
<nonce_str><![CDATA[4tMaH1VyPwMLIBMV]]></nonce_str>
<result_code><![CDATA[1]]></result_code>
<sign><![CDATA[7B882652B4053E4A39A82BB8E1B06FB0]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => 'payment.https.pay.swiftpass.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1090',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<charset><![CDATA[UTF-8]]></charset>
<code_img_url><![CDATA[https://pay.swiftpass.cn/pay/qrcod
uuid=weixin://wxpay/bizpayurl?pr=yGSgcch]]></code_img_url>
<code_url><![CDATA[weixin://wxpay/bizpayurl?pr=yGSgcch]]></code_url>
<mch_id><![CDATA[162340474915]]></mch_id>
<nonce_str><![CDATA[4tMaH1VyPwMLIBMV]]></nonce_str>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[14FE6E9BAE1C0D458CDC9C4802A87EBF]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<uuid><![CDATA[bfcb78fbedd4b95824c1a7e6ec9179b9]]></uuid>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $encodeData = $swiftPass->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=yGSgcch', $swiftPass->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.swiftpass.cn/return.php',
            'verify_url' => 'payment.https.pay.swiftpass.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '2341',
            'domain' => '6',
            'paymentVendorId' => '1098',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<charset><![CDATA[UTF-8]]></charset>
<code_img_url><![CDATA[https://pay.swiftpass.cn/pay/qrcod
uuid=weixin://wxpay/bizpayurl?pr=yGSgcch]]></code_img_url>
<code_url><![CDATA[https://qr.alipay.com/bax070382fdbd3dfwkwo6030]]></code_url>
<mch_id><![CDATA[162340474915]]></mch_id>
<nonce_str><![CDATA[4tMaH1VyPwMLIBMV]]></nonce_str>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[14FE6E9BAE1C0D458CDC9C4802A87EBF]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<uuid><![CDATA[bfcb78fbedd4b95824c1a7e6ec9179b9]]></uuid>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $encodeData = $swiftPass->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/bax070382fdbd3dfwkwo6030', $encodeData['act_url']);
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $swiftPass = new SwiftPass();
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少content
     */
    public function testReturnWithoutContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回不是xml格式
     */
    public function testReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $xml = '不是xml';

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少status
     */
    public function testReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml></xml>';

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回時status不是0
     */
    public function testReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature error',
            180130
        );

        $xml = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<status><![CDATA[400]]></status>
<message><![CDATA[Signature error]]></message>
</xml>
EOT;

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少result_code
     */
    public function testReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
</xml>
EOT;

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回時result_code不是0
     */
    public function testReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[1470132004813]]></nonce_str>
<result_code><![CDATA[1]]></result_code>
<sign><![CDATA[373B0C4CDD38DC4FA43C47C921535417]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
</xml>
EOT;

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[1470132004813]]></nonce_str>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[373B0C4CDD38DC4FA43C47C921535417]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
</xml>
EOT;

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = <<<EOT
<xml><bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[1470132004813]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608020389395281]]></out_transaction_id>
<pay_result><![CDATA[0]]></pay_result>
<result_code><![CDATA[0]]></result_code>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160802180004]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608027479229649]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
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

        $xml = <<<EOT
<xml><bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[1470132004813]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608020389395281]]></out_transaction_id>
<pay_result><![CDATA[0]]></pay_result>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[373B0C4CDD38DC4FA43C47C921535417]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160802180004]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608027479229649]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
    }

    /**
     * 測試返回時pay_result不是0
     */
    public function testReturnPayResultNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = <<<EOT
<xml><bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[1470132004813]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608020389395281]]></out_transaction_id>
<pay_result><![CDATA[1]]></pay_result>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[85364F3028350D81344D23024204E98D]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160802180004]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608027479229649]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $options = ['content' => $xml];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment([]);
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

        $xml = <<<EOT
<xml><bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[1470132004813]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608020389395281]]></out_transaction_id>
<pay_result><![CDATA[0]]></pay_result>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[3404960CA409CC16655A139887879DD8]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160802180004]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608027479229649]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $options = ['content' => $xml];

        $entry = ['id' => '201509140000002475'];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment($entry);
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

        $xml = <<<EOT
<xml><bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[1470132004813]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608020389395281]]></out_transaction_id>
<pay_result><![CDATA[0]]></pay_result>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[3404960CA409CC16655A139887879DD8]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160802180004]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608027479229649]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $options = ['content' => $xml];

        $entry = [
            'id' => '201608020000044770',
            'amount' => '15.00',
        ];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $xml = <<<EOT
<xml><bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[1470132004813]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608020389395281]]></out_transaction_id>
<pay_result><![CDATA[0]]></pay_result>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[3404960CA409CC16655A139887879DD8]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160802180004]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608027479229649]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $options = ['content' => $xml];

        $entry = [
            'id' => '201608020000044770',
            'amount' => '0.01',
        ];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->verifyOrderPayment($entry);

        $this->assertEquals('success', $swiftPass->getMsg());
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

        $swiftPass = new SwiftPass();
        $swiftPass->paymentTracking();
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

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->paymentTracking();
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

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $swiftPass = new SwiftPass();
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線異常
     */
    public function testTrackingReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線失敗
     */
    public function testTrackingReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳結果為空
     */
    public function testTrackingReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付返回不是xml格式
     */
    public function testTrackingReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回status
     */
    public function testTrackingReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = '<xml></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢結果status不為0
     */
    public function testTrackingStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature error',
            180123
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<status><![CDATA[400]]></status>
<message><![CDATA[Signature error]]></message>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回result_code
     */
    public function testTrackingReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<status><![CDATA[0]]></status>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢結果result_code不為0
     */
    public function testTrackingResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><version><![CDATA[2.0]]></version>
<charset><![CDATA[UTF-8]]></charset>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[QvlXtGSiLiQP7wWJ]]></nonce_str>
<result_code><![CDATA[1]]></result_code>
<sign><![CDATA[541FD208D47741FCFDD40A2A6C5DE5BC]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<charset><![CDATA[UTF-8]]></charset>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[sNd4EI9SxxMo8kQB]]></nonce_str>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[D3EA63E299D0D6EFA8A524A4F47AB9AC]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<trade_state><![CDATA[NOTPAY]]></trade_state>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢支付失敗
     */
    public function testTrackingReturnPayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<charset><![CDATA[UTF-8]]></charset>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[sNd4EI9SxxMo8kQB]]></nonce_str>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[D3EA63E299D0D6EFA8A524A4F47AB9AC]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<trade_state><![CDATA[PAYERROR]]></trade_state>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
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

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<charset><![CDATA[UTF-8]]></charset>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[sNd4EI9SxxMo8kQB]]></nonce_str>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[D3EA63E299D0D6EFA8A524A4F47AB9AC]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<trade_state><![CDATA[SUCCESS]]></trade_state>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有sign的情況
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<attach><![CDATA[463_6]]></attach>
<bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[LP6enYr2xZ2YMSUM]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608010280333311]]></out_transaction_id>
<result_code><![CDATA[0]]></result_code>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160801140115]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_state><![CDATA[SUCCESS]]></trade_state>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608013496440785]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
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

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<attach><![CDATA[463_6]]></attach>
<bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[LP6enYr2xZ2YMSUM]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608010280333311]]></out_transaction_id>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[714B5309E842FFFC33F5794393AF0153]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160801140115]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_state><![CDATA[SUCCESS]]></trade_state>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608013496440785]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<attach><![CDATA[463_6]]></attach>
<bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[LP6enYr2xZ2YMSUM]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608010280333311]]></out_transaction_id>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[E20C7D0960DFEA132A6D180AAD015D6E]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160801140115]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_state><![CDATA[SUCCESS]]></trade_state>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608013496440785]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '100540014915',
            'orderId' => '201608020000044770',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = <<<EOT
<xml><appid><![CDATA[wx290ce4878c94369d]]></appid>
<attach><![CDATA[463_6]]></attach>
<bank_type><![CDATA[CFT]]></bank_type>
<charset><![CDATA[UTF-8]]></charset>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[100540014915]]></mch_id>
<nonce_str><![CDATA[LP6enYr2xZ2YMSUM]]></nonce_str>
<openid><![CDATA[oMJGHs0V5WTuS_JmMd_mtuBwewf8]]></openid>
<out_trade_no><![CDATA[201608020000044770]]></out_trade_no>
<out_transaction_id><![CDATA[4002092001201608010280333311]]></out_transaction_id>
<result_code><![CDATA[0]]></result_code>
<sign><![CDATA[E20C7D0960DFEA132A6D180AAD015D6E]]></sign>
<sign_type><![CDATA[MD5]]></sign_type>
<status><![CDATA[0]]></status>
<time_end><![CDATA[20160801140115]]></time_end>
<total_fee><![CDATA[1]]></total_fee>
<trade_state><![CDATA[SUCCESS]]></trade_state>
<trade_type><![CDATA[pay.weixin.native]]></trade_type>
<transaction_id><![CDATA[100540014915201608013496440785]]></transaction_id>
<version><![CDATA[2.0]]></version>
</xml>
EOT;

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $swiftPass = new SwiftPass();
        $swiftPass->setContainer($this->container);
        $swiftPass->setClient($this->client);
        $swiftPass->setResponse($response);
        $swiftPass->setPrivateKey('test');
        $swiftPass->setOptions($options);
        $swiftPass->paymentTracking();
    }
}
