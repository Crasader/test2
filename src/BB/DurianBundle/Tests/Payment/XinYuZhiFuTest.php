<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XinYuZhiFu;
use Buzz\Message\Response;

class XinYuZhiFuTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 對外登入成功返回的結果
     *
     * @var string
     */
    private $loginSuccessResult;

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

        $this->loginSuccessResult = '<xml><token_expir_second>604800</token_expir_second><token>' .
            '<![CDATA[eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiIxMDAwNDQiLCJjcmVhdGVkIjoxNTIwN' .
            'Tc0MDgyMjYwLCJleHAiOjE1MjExNzg4ODJ9.vhmwNHMCPT5XnUO1-DCW_LjrDHdlyGOK2H7' .
            '__HU4ycS45puJC8YpMbuQo26fkLYcJjTpKHj28ob6Gro8cVhE5w]]></token></xml>';
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

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->getVerifyData();
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

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->getVerifyData();
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
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => '',
            'paymentVendorId' => '999',
        ];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
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
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => '',
            'paymentVendorId' => '1',
        ];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試支付對外返回不是xml格式
     */
    public function testPayReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試取得token時回傳訊息
     */
    public function testLoginToGetTokenReturnMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '找不到該商戶',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<message><![CDATA[找不到該商戶]]></message>' .
            '<version><![CDATA[1.0]]></version><sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[10000002]]></status></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試取得token失敗
     */
    public function testLoginToGetTokenFail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $result = '<xml><token_expir_second>604800</token_expir_second></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/xml;charset=UTF-8;');

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有status的情況
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $response = $this->mockReponse();

        $result = '<xml></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試支付對外返回時status不為0且有錯誤訊息
     */
    public function testPayReturnStatusNotZeroAndHaveMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '参数错误',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[A0D63F229C5DD040AE2C8F5CF012BD7B]]></sign>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[参数错误]]></status>' .
            '<message><![CDATA[参数错误]]></message></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有result_code的情況
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[EA13D7551C01A9BB9812BE8FD0D16F49]]></sign>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試支付對外返回時result_code不為0
     */
    public function testPayReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[EA13D7551C01A9BB9812BE8FD0D16F49]]></sign>' .
            '<result_code><![CDATA[1234]]></result_code>' .
            '<version><![CDATA[1.0]]></version><sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試網銀支付對外返回時status不為0
     */
    public function testOnlinePayReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[80000001]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有pay_info的情況
     */
    public function testPayReturnWithoutPayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset><sign>' .
            '<![CDATA[EA13D7551C01A9BB9812BE8FD0D16F49]]></sign>' .
            '<result_code><![CDATA[0]]></result_code><version><![CDATA[1.0]]>' .
            '</version><sign_type><![CDATA[MD5]]></sign_type><status><![CDATA[0]]></status></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testOnlinePay()
    {
        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<pay_info><![CDATA[http://api.xinyuzhifu.com/redirect/v1/union/net?' .
            'mch_id=100044&trade_no=d0df88cc6dde4194b48d0e08a2ba8c75&nonce_str=' .
            '49626233023322012764350557685382&sign=D8653F488D1F2B0C5438A4ED28E44F9F]]></pay_info>' .
            '<sign><![CDATA[EA13D7551C01A9BB9812BE8FD0D16F49]]></sign>' .
            '<result_code><![CDATA[0]]></result_code><version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type><status><![CDATA[0]]></status></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $data = $xinYuZhiFu->getVerifyData();

        $this->assertEquals('http://api.xinyuzhifu.com/redirect/v1/union/net', $data['post_url']);
        $this->assertEquals('100044', $data['params']['mch_id']);
        $this->assertEquals('d0df88cc6dde4194b48d0e08a2ba8c75', $data['params']['trade_no']);
        $this->assertEquals('49626233023322012764350557685382', $data['params']['nonce_str']);
        $this->assertEquals('D8653F488D1F2B0C5438A4ED28E44F9F', $data['params']['sign']);
    }

    /**
     * 測試快捷支付
     */
    public function testUnionQuickPay()
    {
        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '278',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<pay_info><![CDATA[http://api.xinyuzhifu.com/redirect/v1/union/quick?' .
            'mch_id=100044&trade_no=a270bdb24b354c4180d7446942d1c156&nonce_str=' .
            '68881539494064352911454372007268&sign=99FC08AACD91946956CFF7257148E2B9]]></pay_info>' .
            '<sign><![CDATA[19EDBE038879AB35E0072E96469882BC]]></sign><result_code><![CDATA[0]]></result_code>' .
            '<version><![CDATA[1.0]]></version><sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $data = $xinYuZhiFu->getVerifyData();

        $this->assertEquals('http://api.xinyuzhifu.com/redirect/v1/union/quick', $data['post_url']);
        $this->assertEquals('100044', $data['params']['mch_id']);
        $this->assertEquals('a270bdb24b354c4180d7446942d1c156', $data['params']['trade_no']);
        $this->assertEquals('68881539494064352911454372007268', $data['params']['nonce_str']);
        $this->assertEquals('99FC08AACD91946956CFF7257148E2B9', $data['params']['sign']);
    }

    /**
     * 測試二維支付對外返回時沒有status的情況
     */
    public function testQrcodePayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1103',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[A0D63F229C5DD040AE2C8F5CF012BD7B]]></sign>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試二維支付對外返回時status不為0且有錯誤訊息
     */
    public function testQrcodePayReturnStatusNotZeroAndHaveMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '参数错误',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1103',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[A0D63F229C5DD040AE2C8F5CF012BD7B]]></sign>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[参数错误]]></status>' .
            '<message><![CDATA[参数错误]]></message></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試二維支付對外返回時沒有result_code的情況
     */
    public function testQrcodePayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1103',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[A0D63F229C5DD040AE2C8F5CF012BD7B]]></sign>' .
            '<version><![CDATA[1.0]]></version><sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試二維支付對外返回時result_code不為0
     */
    public function testQrcodePayReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1103',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[A0D63F229C5DD040AE2C8F5CF012BD7B]]></sign>' .
            '<result_code><![CDATA[1234]]></result_code>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試二維支付對外返回時status不為0
     */
    public function testQrcodePayReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1103',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[80000001]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試二維支付對外返回時沒有codeUrl情況
     */
    public function testQrcodePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1103',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[A0D63F229C5DD040AE2C8F5CF012BD7B]]></sign>' .
            '<result_code><![CDATA[0]]></result_code><version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type><status><![CDATA[0]]></status></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1092',
        ];

        $codeUrl = 'http://api.xinyuzhifu.com/common/qrcode?width=300&height=300&content=https://openauth.alip' .
            'ay.com/oauth2/publicAppAuthorize.htm?app_id=2018011301836250&scope=auth_base&redirect_uri=http%3A' .
            '%2F%2Fauth.3hus.cn%2Fwebwt%2Fpay%2Fauth.do%3FoutTradeNo%3D313231383036323631343430313532373430383' .
            '53937323252644d7650';

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            "<code_img_url><![CDATA[$codeUrl]]></code_img_url><code_url><![CDATA[$codeUrl]]></code_url>" .
            '<sign><![CDATA[420084DCF5903ACB1599309CE40C70F1]]></sign><result_code><![CDATA[0]]></result_code>' .
            '<version><![CDATA[1.0]]></version><sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $data = $xinYuZhiFu->getVerifyData();

        $html = sprintf('<img src="%s"/>', $codeUrl);

        $this->assertEmpty($data);
        $this->assertEquals($html, $xinYuZhiFu->getHtml());
    }

    /**
     * 測試手機支付對外返回時沒有status的情況
     */
    public function testPhonePayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1104',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[803964C3AEE8F121FA3080A060C4DBCA]]></sign>' .
            '<version><![CDATA[1.0]]></version><sign_type><![CDATA[MD5]]></sign_type></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付對外返回時status不為0且有錯誤訊息
     */
    public function testPhonePayReturnStatusNotZeroAndHaveMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '系统错误 : 80000001@@系统繁忙，稍后在试',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1104',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<message><![CDATA[系统错误 : 80000001@@系统繁忙，稍后在试]]></message>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[80000001]]></status></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付對外返回時沒有result_code的情況
     */
    public function testPhonePayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1104',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[803964C3AEE8F121FA3080A060C4DBCA]]></sign>' .
            '<version><![CDATA[1.0]]></version><sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付對外返回時result_code不為0
     */
    public function testPhonePayReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1104',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[803964C3AEE8F121FA3080A060C4DBCA]]></sign>' .
            '<result_code><![CDATA[1234]]></result_code>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付對外返回時status不為0
     */
    public function testPhonePayReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1104',
        ];

        $response = $this->mockReponse();

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[80000001]]></status></xml>';

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付對外返回時沒有pay_info情況
     */
    public function testPhonePayReturnWithoutPayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1104',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[803964C3AEE8F121FA3080A060C4DBCA]]></sign>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => '100044',
            'orderId' => '201803120000010127',
            'username' => 'php1test',
            'amount' => '1',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.https.api.xinyuzhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1104',
        ];

        $result = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<pay_info><![CDATA[http://api.xinyuzhifu.com/redirect/v1/qq/wap?mch_id=' .
            '100044&trade_no=a8b41b15922e4f00a3ca71733dd602e6&nonce_str=' .
            '97579735105734110852613455261699&sign=445DEC6DB409590D78A5E33E90FDF2AB]]></pay_info>' .
            '<sign><![CDATA[803964C3AEE8F121FA3080A060C4DBCA]]></sign><result_code><![CDATA[0]]></result_code>' .
            '<version><![CDATA[1.0]]></version><sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status></xml>';

        $response = $this->mockReponse();

        $response->method('getContent')
            ->willReturnOnConsecutiveCalls($this->loginSuccessResult, $result);

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setContainer($this->container);
        $xinYuZhiFu->setClient($this->client);
        $xinYuZhiFu->setResponse($response);
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $data = $xinYuZhiFu->getVerifyData();

        $this->assertEquals('http://api.xinyuzhifu.com/redirect/v1/qq/wap', $data['post_url']);
        $this->assertEquals('100044', $data['params']['mch_id']);
        $this->assertEquals('a8b41b15922e4f00a3ca71733dd602e6', $data['params']['trade_no']);
        $this->assertEquals('97579735105734110852613455261699', $data['params']['nonce_str']);
        $this->assertEquals('445DEC6DB409590D78A5E33E90FDF2AB', $data['params']['sign']);
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

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時status不是0
     */
    public function testReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '根據文件寫的錯誤回調格式',
            180130
        );

        $xml = '<xml><charset><![CDATA[UTF-8]]></charset>' .
            '<sign><![CDATA[11DF82899FFF563ED43924FE19829E7A]]></sign>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>12345</status>' .
            '<message>根據文件寫的錯誤回調格式</message></xml>';

        $options = ['content' => $xml];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xml = '<xml><charset><![CDATA[UTF-8]]></charset><sign>' .
            '<![CDATA[11DF82899FFF563ED43924FE19829E7A]]></sign>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status></xml>';

        $options = ['content' => $xml];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時result_code不是0
     */
    public function testReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '根據文件寫的錯誤回調格式',
            180130
        );

        $xml = '<xml><charset><![CDATA[UTF-8]]></charset><sign>' .
            '<![CDATA[11DF82899FFF563ED43924FE19829E7A]]></sign>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status>' .
            '<result_code>9453</result_code>' .
            '<err_msg>根據文件寫的錯誤回調格式</err_msg></xml>';

        $options = ['content' => $xml];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時status或result_code不是0
     */
    public function testReturnStatusOrResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<xml><charset><![CDATA[UTF-8]]></charset><sign>' .
            '<![CDATA[11DF82899FFF563ED43924FE19829E7A]]></sign>' .
            '<version><![CDATA[1.0]]></version>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status>' .
            '<result_code>9453</result_code></xml>';

        $options = ['content' => $xml];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xml = '<xml><transaction_id><![CDATA[d0df88cc6dde4194b48d0e08a2ba8c75]]></transaction_id>' .
            '<sign><![CDATA[39C0CC84FC11FDC096992A899AE353B0]]></sign>' .
            '<mch_id><![CDATA[100044]]></mch_id>' .
            '<pay_result>0</pay_result>' .
            '<out_trade_no><![CDATA[201803120000010127]]></out_trade_no>' .
            '<total_fee>100</total_fee>' .
            '<trade_type>NET</trade_type>' .
            '<result_code>0</result_code>' .
            '<status>0</status></xml>';

        $options = ['content' => $xml];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xml = '<xml><transaction_id><![CDATA[d0df88cc6dde4194b48d0e08a2ba8c75]]></transaction_id>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<nonce_str>1520851734226</nonce_str>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[100044]]></mch_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '<pay_result>0</pay_result>' .
            '<out_trade_no><![CDATA[201803120000010127]]></out_trade_no>' .
            '<total_fee>100</total_fee>' .
            '<trade_type>NET</trade_type>' .
            '<result_code>0</result_code>' .
            '<time_end><![CDATA[2018-03-12 18:48:54]]></time_end>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status></xml>';

        $options = ['content' => $xml];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xml = '<xml><transaction_id><![CDATA[d0df88cc6dde4194b48d0e08a2ba8c75]]></transaction_id>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<nonce_str>1520851734226</nonce_str>' .
            '<sign><![CDATA[11DF82899FFF563ED43924FE19829E7A]]></sign>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[100044]]></mch_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '<pay_result>0</pay_result>' .
            '<out_trade_no><![CDATA[201803120000010127]]></out_trade_no>' .
            '<total_fee>100</total_fee>' .
            '<trade_type>NET</trade_type>' .
            '<result_code>0</result_code>' .
            '<time_end><![CDATA[2018-03-12 18:48:54]]></time_end>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status></xml>';

        $options = ['content' => $xml];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xml = '<xml><transaction_id><![CDATA[d0df88cc6dde4194b48d0e08a2ba8c75]]></transaction_id>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<nonce_str>1520851734226</nonce_str>' .
            '<sign><![CDATA[BA1CF9DCC749EBA7DCF293BCBA9393E2]]></sign>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[100044]]></mch_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '<pay_result>1234</pay_result>' .
            '<out_trade_no><![CDATA[201803120000010127]]></out_trade_no>' .
            '<total_fee>100</total_fee>' .
            '<trade_type>NET</trade_type>' .
            '<result_code>0</result_code>' .
            '<time_end><![CDATA[2018-03-12 18:48:54]]></time_end>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status></xml>';

        $options = ['content' => $xml];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment([]);
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

        $xml = '<xml><transaction_id><![CDATA[d0df88cc6dde4194b48d0e08a2ba8c75]]></transaction_id>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<nonce_str>1520851734226</nonce_str>' .
            '<sign><![CDATA[39C0CC84FC11FDC096992A899AE353B0]]></sign>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[100044]]></mch_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '<pay_result>0</pay_result>' .
            '<out_trade_no><![CDATA[201803120000010127]]></out_trade_no>' .
            '<total_fee>100</total_fee>' .
            '<trade_type>NET</trade_type>' .
            '<result_code>0</result_code>' .
            '<time_end><![CDATA[2018-03-12 18:48:54]]></time_end>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status></xml>';

        $options = ['content' => $xml];

        $entry = ['id' => '201711080000005428'];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment($entry);
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

        $xml = '<xml><transaction_id><![CDATA[d0df88cc6dde4194b48d0e08a2ba8c75]]></transaction_id>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<nonce_str>1520851734226</nonce_str>' .
            '<sign><![CDATA[39C0CC84FC11FDC096992A899AE353B0]]></sign>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[100044]]></mch_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '<pay_result>0</pay_result>' .
            '<out_trade_no><![CDATA[201803120000010127]]></out_trade_no>' .
            '<total_fee>100</total_fee>' .
            '<trade_type>NET</trade_type>' .
            '<result_code>0</result_code>' .
            '<time_end><![CDATA[2018-03-12 18:48:54]]></time_end>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status></xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201803120000010127',
            'amount' => '15.00',
        ];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $xml = '<xml><transaction_id><![CDATA[d0df88cc6dde4194b48d0e08a2ba8c75]]></transaction_id>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<nonce_str>1520851734226</nonce_str>' .
            '<sign><![CDATA[39C0CC84FC11FDC096992A899AE353B0]]></sign>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<mch_id><![CDATA[100044]]></mch_id>' .
            '<version><![CDATA[1.0]]></version>' .
            '<pay_result>0</pay_result>' .
            '<out_trade_no><![CDATA[201803120000010127]]></out_trade_no>' .
            '<total_fee>100</total_fee>' .
            '<trade_type>NET</trade_type>' .
            '<result_code>0</result_code>' .
            '<time_end><![CDATA[2018-03-12 18:48:54]]></time_end>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status>0</status></xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201803120000010127',
            'amount' => '1',
        ];

        $xinYuZhiFu = new XinYuZhiFu();
        $xinYuZhiFu->setPrivateKey('test');
        $xinYuZhiFu->setOptions($options);
        $xinYuZhiFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $xinYuZhiFu->getMsg());
    }

    /**
     * 產生假對外返回物件
     *
     * @return Response
     */
    private function mockReponse()
    {
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->method('getStatusCode')
            ->willReturn(200);

        return $response;
    }
}
