<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Iyibank;
use Buzz\Message\Response;

class IyibankTest extends DurianTestCase
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

        $iyibank = new Iyibank();
        $iyibank->getVerifyData();
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

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->getVerifyData();
    }

    /**
     * 測試支付時代入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'paymentVendorId' => '9999',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
        ];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => '',
        ];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有status的情況
     */
    public function testPrepayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
    }

    /**
     * 測試返回預付單時status不為0，有錯誤訊息
     */
    public function testPrepayReturnStatusNotZeroWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'WX支付方式未开通',
            180130
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<message><![CDATA[WX支付方式未开通]]></message>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[1]]></status>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有result_code的情況
     */
    public function testPrepayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<version><![CDATA[1.0]]></version>' .
            '<result_code><![CDATA[1]]></result_code>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
    }

    /**
     * 測試返回預付單時取得支付參數失敗
     */
    public function testPrepayReturnWithGetPayParametersFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[01]]></err_code>' .
            '<err_msg><![CDATA[Success]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[成功]]></message>' .
            '<nonce_str><![CDATA[-544451306]]></nonce_str>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[6261007993775B836E044D6486730236]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type><status><![CDATA[0]]></status>' .
            '<token_id><![CDATA[https://www.iyibank.com/ajax/code.ashx?str=weixin://wxpay/bizpayurl?pr=uTrxIwi]]>' .
            '</token_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
    }

    /**
     * 測試返回預付單時沒有sign的情況
     */
    public function testPrepayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[01]]></err_code>' .
            '<err_msg><![CDATA[Success]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[成功]]></message>' .
            '<nonce_str><![CDATA[-544451306]]></nonce_str>' .
            '<pay_info><![CDATA[weixin://wxpay/bizpayurl?pr=uTrxIwi]]></pay_info>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign_type><![CDATA[MD5]]></sign_type><status><![CDATA[0]]></status>' .
            '<token_id><![CDATA[https://www.iyibank.com/ajax/code.ashx?str=weixin://wxpay/bizpayurl?pr=uTrxIwi]]>' .
            '</token_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
    }

    /**
     * 測試返回預付單時解密驗證錯誤
     */
    public function testPayRuturnDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[01]]></err_code>' .
            '<err_msg><![CDATA[Success]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[成功]]></message>' .
            '<nonce_str><![CDATA[-544451306]]></nonce_str>' .
            '<pay_info><![CDATA[weixin://wxpay/bizpayurl?pr=uTrxIwi]]></pay_info>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[6261007993775B836E044D6486730236]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type><status><![CDATA[0]]></status>' .
            '<token_id><![CDATA[https://www.iyibank.com/ajax/code.ashx?str=weixin://wxpay/bizpayurl?pr=uTrxIwi]]>' .
            '</token_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'paymentVendorId' => '1090',
            'number' => '162340474915',
            'orderId' => '201608020000044770',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[01]]></err_code>' .
            '<err_msg><![CDATA[Success]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[成功]]></message>' .
            '<nonce_str><![CDATA[-544451306]]></nonce_str>' .
            '<pay_info><![CDATA[weixin://wxpay/bizpayurl?pr=uTrxIwi]]></pay_info>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[67B11335C2398A72F285F77B670B9D47]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type><status><![CDATA[0]]></status>' .
            '<token_id><![CDATA[https://www.iyibank.com/ajax/code.ashx?str=weixin://wxpay/bizpayurl?pr=uTrxIwi]]>' .
            '</token_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $encodeData = $iyibank->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=uTrxIwi', $iyibank->getQrcode());
    }

    /**
     * 測試支付寶手機支付
     */
    public function testPayWithAlipayWap()
    {
        $options = [
            'paymentVendorId' => '1098',
            'number' => '2043',
            'orderId' => '201701170000005765',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://www.iyibank.cn/return.php',
            'verify_url' => 'payment.https.pay.iyibank.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[01]]></err_code>' .
            '<err_msg><![CDATA[Success]]></err_msg>' .
            '<mch_id><![CDATA[2043]]></mch_id>' .
            '<message><![CDATA[成功]]></message>' .
            '<nonce_str><![CDATA[1203445696]]></nonce_str>' .
            '<pay_info><![CDATA[https://qr.alipay.com/bax01142ar1qleoxbaqw2036]]></pay_info>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[AF01FA5C45EA4743E11D0E53BE0421B4]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type><status><![CDATA[0]]></status>' .
            '<token_id>' .
            '<![CDATA[https://www.iyibank.com/ajax/code.ashx?str=https://qr.alipay.com/bax01142ar1qleoxbaqw2036]]>' .
            '</token_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('f69b64c4215b46379e1c862004a1b152');
        $iyibank->setOptions($options);
        $encodeData = $iyibank->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/bax01142ar1qleoxbaqw2036', $encodeData['act_url']);
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

        $iyibank = new Iyibank();
        $iyibank->verifyOrderPayment([]);
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

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->verifyOrderPayment([]);
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

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment([]);
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

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment([]);
    }

    /**
     * 測試返回時status不是0，有錯誤訊息
     */
    public function testReturnStatusNotZeroWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature error',
            180130
        );

        $xml = '<xml>' .
            '<status><![CDATA[1]]></status>' .
            '<message><![CDATA[Signature error]]></message>' .
            '</xml>';

        $options = ['content' => $xml];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<status><![CDATA[0]]></status>' .
            '</xml>';

        $options = ['content' => $xml];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<status><![CDATA[0]]></status>' .
            '<result_code><![CDATA[1]]></result_code>' .
            '</xml>';

        $options = ['content' => $xml];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<status><![CDATA[0]]></status>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '</xml>';

        $options = ['content' => $xml];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<charset><![CDATA[uft-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[ok]]></err_code>' .
            '<err_msg><![CDATA[ok]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[ok]]></message>' .
            '<nonce_str><![CDATA[-975013650]]></nonce_str>' .
            '<orderid><![CDATA[1612191556398706501554]]></orderid>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<service><![CDATA[1536]]></service>' .
            '<sign_type><![CDATA[md5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<charset><![CDATA[uft-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[ok]]></err_code>' .
            '<err_msg><![CDATA[ok]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[ok]]></message>' .
            '<nonce_str><![CDATA[-975013650]]></nonce_str>' .
            '<orderid><![CDATA[1612191556398706501554]]></orderid>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<service><![CDATA[1536]]></service>' .
            '<sign><![CDATA[test123]]></sign>' .
            '<sign_type><![CDATA[md5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<charset><![CDATA[uft-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[ok]]></err_code>' .
            '<err_msg><![CDATA[ok]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[ok]]></message>' .
            '<nonce_str><![CDATA[-975013650]]></nonce_str>' .
            '<orderid><![CDATA[1612191556398706501554]]></orderid>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<service><![CDATA[1536]]></service>' .
            '<sign><![CDATA[626C0BB59A7855D73EF428E733972D7B]]></sign>' .
            '<sign_type><![CDATA[md5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = ['id' => '201509140000002475'];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment($entry);
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

        $xml = '<xml>' .
            '<charset><![CDATA[uft-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[ok]]></err_code>' .
            '<err_msg><![CDATA[ok]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[ok]]></message>' .
            '<nonce_str><![CDATA[-975013650]]></nonce_str>' .
            '<orderid><![CDATA[1612191556398706501554]]></orderid>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<service><![CDATA[1536]]></service>' .
            '<sign><![CDATA[626C0BB59A7855D73EF428E733972D7B]]></sign>' .
            '<sign_type><![CDATA[md5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201612190000006952',
            'amount' => '15.00',
        ];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $xml = '<xml>' .
            '<charset><![CDATA[uft-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[ok]]></err_code>' .
            '<err_msg><![CDATA[ok]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[ok]]></message>' .
            '<nonce_str><![CDATA[-975013650]]></nonce_str>' .
            '<orderid><![CDATA[1612191556398706501554]]></orderid>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<service><![CDATA[1536]]></service>' .
            '<sign><![CDATA[626C0BB59A7855D73EF428E733972D7B]]></sign>' .
            '<sign_type><![CDATA[md5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201612190000006952',
            'amount' => '0.01',
        ];

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $iyibank->getMsg());
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

        $iyibank = new Iyibank();
        $iyibank->paymentTracking();
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

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->paymentTracking();
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

        $iyibank = new Iyibank();
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
    }

    /**
     * 測試訂單查詢結果status不為0，有錯誤訊息
     */
    public function testTrackingStatusNotZeroWithErrorMessage()
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

        $result = '<xml>' .
            '<status><![CDATA[1]]></status>' .
            '<message><![CDATA[Signature error]]></message>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<status><![CDATA[0]]></status>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<status><![CDATA[0]]></status>' .
            '<result_code><![CDATA[1]]></result_code>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回trade_state
     */
    public function testTrackingReturnWithoutTradeState()
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

        $result = '<xml>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<status><![CDATA[0]]></status>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[0]]></err_code>' .
            '<err_msg><![CDATA[]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[]]></message>' .
            '<nonce_str><![CDATA[1972940009]]></nonce_str>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[D67F53F498DA25EFB11F84E161A8384E]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<trade_state><![CDATA[NOTPAY]]></trade_state>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[0]]></err_code>' .
            '<err_msg><![CDATA[]]></err_msg>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[]]></message>' .
            '<nonce_str><![CDATA[1972940009]]></nonce_str>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[D67F53F498DA25EFB11F84E161A8384E]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<trade_state><![CDATA[PAYERROR]]></trade_state>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<attach><![CDATA[]]></attach>' .
            '<bank_billno><![CDATA[101530009792201612194208678274]]></bank_billno>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<coupon_fee><![CDATA[0.00]]></coupon_fee>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[0]]></err_code>' .
            '<err_msg><![CDATA[]]></err_msg>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[]]></message>' .
            '<nonce_str><![CDATA[-1780784439]]></nonce_str>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[D6F9277EE92A869E920E03ED6790CCEE]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20161219035650]]></time_end>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<trade_state><![CDATA[SUCCESS]]></trade_state>' .
            '<trade_type><![CDATA[cibweixin]]></trade_type>' .
            '<transaction_id><![CDATA[1612191556398706501554]]></transaction_id>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<attach><![CDATA[]]></attach>' .
            '<bank_billno><![CDATA[101530009792201612194208678274]]></bank_billno>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<coupon_fee><![CDATA[0.00]]></coupon_fee>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[0]]></err_code>' .
            '<err_msg><![CDATA[]]></err_msg>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[]]></message>' .
            '<nonce_str><![CDATA[-1780784439]]></nonce_str>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20161219035650]]></time_end>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<trade_state><![CDATA[SUCCESS]]></trade_state>' .
            '<trade_type><![CDATA[cibweixin]]></trade_type>' .
            '<transaction_id><![CDATA[1612191556398706501554]]></transaction_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<attach><![CDATA[]]></attach>' .
            '<bank_billno><![CDATA[101530009792201612194208678274]]></bank_billno>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<coupon_fee><![CDATA[0.00]]></coupon_fee>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[0]]></err_code>' .
            '<err_msg><![CDATA[]]></err_msg>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[]]></message>' .
            '<nonce_str><![CDATA[-1780784439]]></nonce_str>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[D6F9277EE92A869E920E03ED6790CCEE]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20161219035650]]></time_end>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<trade_state><![CDATA[SUCCESS]]></trade_state>' .
            '<trade_type><![CDATA[cibweixin]]></trade_type>' .
            '<transaction_id><![CDATA[1612191556398706501554]]></transaction_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<attach><![CDATA[]]></attach>' .
            '<bank_billno><![CDATA[101530009792201612194208678274]]></bank_billno>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<coupon_fee><![CDATA[0.00]]></coupon_fee>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[0]]></err_code>' .
            '<err_msg><![CDATA[]]></err_msg>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[]]></message>' .
            '<nonce_str><![CDATA[-1780784439]]></nonce_str>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[00B3E2A2AD7374A73F0CC27AEB0E571E]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20161219035650]]></time_end>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<trade_state><![CDATA[SUCCESS]]></trade_state>' .
            '<trade_type><![CDATA[cibweixin]]></trade_type>' .
            '<transaction_id><![CDATA[1612191556398706501554]]></transaction_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
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

        $result = '<xml>' .
            '<attach><![CDATA[]]></attach>' .
            '<bank_billno><![CDATA[101530009792201612194208678274]]></bank_billno>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<coupon_fee><![CDATA[0.00]]></coupon_fee>' .
            '<device_info><![CDATA[]]></device_info>' .
            '<err_code><![CDATA[0]]></err_code>' .
            '<err_msg><![CDATA[]]></err_msg>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[1782]]></mch_id>' .
            '<message><![CDATA[]]></message>' .
            '<nonce_str><![CDATA[-1780784439]]></nonce_str>' .
            '<out_trade_no><![CDATA[201612190000006952]]></out_trade_no>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[00B3E2A2AD7374A73F0CC27AEB0E571E]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20161219035650]]></time_end>' .
            '<total_fee><![CDATA[0.01]]></total_fee>' .
            '<trade_state><![CDATA[SUCCESS]]></trade_state>' .
            '<trade_type><![CDATA[cibweixin]]></trade_type>' .
            '<transaction_id><![CDATA[1612191556398706501554]]></transaction_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type: text/plain; charset=utf-8');

        $iyibank = new Iyibank();
        $iyibank->setContainer($this->container);
        $iyibank->setClient($this->client);
        $iyibank->setResponse($response);
        $iyibank->setPrivateKey('test');
        $iyibank->setOptions($options);
        $iyibank->paymentTracking();
    }
}
