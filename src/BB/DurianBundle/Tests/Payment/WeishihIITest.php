<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\WeishihII;
use Buzz\Message\Response;

class WeishihIITest extends DurianTestCase
{
    /**
     * 此部分用於需要取得Container的時候
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 此部分用於需要取得Container的時候
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger]
        ];

        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $weishihII = new WeishihII();
        $weishihII->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = ['number' => ''];

        $weishihII->setOptions($sourceData);
        $weishihII->getVerifyData();
    }

    /**
     * 測試加密基本參數設定沒有帶入verifyUrl的情況
     */
    public function testSetEncodeSourceNoVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'number' => '4909251014942593',
            'orderId' => '20140610000123',
            'amount' => '0.01',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'username' => 'acctest',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'verify_url' => '',
            'merchantId' => '12345',
            'domain' => '6',
            'user_agent' => 'Chrome/61.0.3163.100 Safari/537.36',
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'number' => '4909251014942593',
            'orderId' => '20140610000123',
            'amount' => '0.01',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'username' => 'acctest',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '999',
            'verify_url' => 'cloud1.semanticweb.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
            'user_agent' => 'Chrome/61.0.3163.100 Safari/537.36',
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->getVerifyData();
    }

    /**
     * 測試返回時支付平台連線失敗
     */
    public function testReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'number' => '4909251014942593',
            'orderId' => '20140610000123',
            'amount' => '0.01',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'username' => 'acctest',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'verify_url' => 'cloud1.semanticweb.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
            'user_agent' => 'Chrome/61.0.3163.100 Safari/537.36',
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->getVerifyData();
    }

    /**
     * 測試返回時支付平台連線失敗
     */
    public function testReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 499');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setResponse($respone);
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'number' => '4909251014942593',
            'orderId' => '20140610000123',
            'amount' => '0.01',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'username' => 'acctest',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'verify_url' => 'cloud1.semanticweb.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
            'user_agent' => 'Chrome/61.0.3163.100 Safari/537.36',
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->getVerifyData();
    }

    /**
     * 測試加密時支付平台回傳結果為空
     */
    public function testPayEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setResponse($respone);
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'number' => '4909251014942593',
            'orderId' => '20140610000123',
            'amount' => '0.01',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'username' => 'acctest',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'verify_url' => 'cloud1.semanticweb.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
            'user_agent' => 'Chrome/61.0.3163.100 Safari/537.36',
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->getVerifyData();
    }

    /**
     * 測試支付時對外返回結果錯誤
     */
    public function testPayConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '[error:暂不支持您选定的银行，请更换&10009]',
            180130
        );

        $result = '[error:暂不支持您选定的银行，请更换&10009]';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setResponse($respone);
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'number' => '4909251014942593',
            'orderId' => '20140610000123',
            'amount' => '0.01',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'username' => 'acctest',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'verify_url' => 'cloud1.semanticweb.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
            'user_agent' => 'Chrome/61.0.3163.100 Safari/537.36',
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->getVerifyData();
    }

    /**
     * 測試支付時對外返回結果錯誤，且有換行訊息
     */
    public function testPayConnectionPaymentGatewayErrorWithNewLine()
    {
        $this->expectException('BB\DurianBundle\Exception\PaymentConnectionException');
        $this->expectExceptionMessageRegExp('/^\[error:可用支付通道已满额，请十分钟后再尝试&10008\]$/');
        $this->expectExceptionCode(180130);

        $result = "\n\n\n\n[error:可用支付通道已满额，请十分钟后再尝试&10008]\n\n\n\n";

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setResponse($respone);
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'number' => '4909251014942593',
            'orderId' => '20140610000123',
            'amount' => '0.01',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'username' => 'acctest',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'verify_url' => 'cloud1.semanticweb.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
            'user_agent' => 'Chrome/61.0.3163.100 Safari/537.36',
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->getVerifyData();
    }

    /**
     * 測試加密參數設定成功
     */
    public function testSetEncodeSuccess()
    {
        $sourceData = [
            'number' => '4909251014942593',
            'orderId' => '20140610000123',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'acctest',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'verify_url' => 'cloud1.semanticweb.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '12345',
            'domain' => '6',
            'user_agent' => 'Chrome/61.0.3163.100 Safari/537.36',
        ];

        $respone = new Response();
        $respone->setContent('true');
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setResponse($respone);
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');
        $weishihII->setOptions($sourceData);
        $verifyData = $weishihII->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $verifyData['userID']);
        $this->assertEquals($sourceData['orderId'], $verifyData['orderId']);
        $this->assertSame(0.0100, $verifyData['amt']);
        $this->assertEquals($notifyUrl, $verifyData['url']);
        $this->assertEquals($sourceData['username'], $verifyData['name']);
        $this->assertEquals('R', $verifyData['bank']);
        $this->assertEquals('d1cc721d3ce965bddf3ae27b3860d5c7', $verifyData['hmac']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $weishihII = new WeishihII();

        $weishihII->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'pay_system'  => '12345',
            'orderId'     => '20140610000123',
            'r_orderId'   => '',
            'amt'         => '0.01',
            'userID'      => '4909251014942593',
            'systemAppID' => '',
            'time'        => '',
            'cur'         => '1',
            'des'         => '',
            'hmac'        => 'c5b039c217b168a096a49963d523efac',
            'paytype'     => '33',
            'sid'         => '122362',
            'userOrderID' => '20140610000123',
            'hmac2'       => 'aad95555d151935269f16edc9dc7dea0',
            'attach'      => 'null'
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試hmac2:加密簽名)
     */
    public function testVerifyWithoutHmac2()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'pay_system'  => '12345',
            'orderId'     => '20140610000123',
            'r_orderId'   => '',
            'amt'         => '0.01',
            'succ'        => 'Y',
            'userID'      => '4909251014942593',
            'systemAppID' => '',
            'time'        => '',
            'cur'         => '1',
            'des'         => '',
            'hmac'        => 'c5b039c217b168a096a49963d523efac',
            'paytype'     => '33',
            'sid'         => '122362',
            'userOrderID' => '20140610000123',
            'attach'      => 'null'
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->verifyOrderPayment([]);
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

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'pay_system'  => '12345',
            'orderId'     => '20140610000321',
            'r_orderId'   => '',
            'amt'         => '0.01',
            'succ'        => 'Y',
            'userID'      => '4909251014942593',
            'systemAppID' => '',
            'time'        => '',
            'cur'         => '1',
            'des'         => '',
            'hmac'        => 'c5b039c217b168a096a49963d523efac',
            'paytype'     => '33',
            'sid'         => '122362',
            'userOrderID' => '20140610000123',
            'hmac2'       => 'aad95555d151935269f16edc9dc7dea0',
            'attach'      => 'null'
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->verifyOrderPayment([]);
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

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'pay_system'  => '12345',
            'orderId'     => '20140610000123',
            'r_orderId'   => '',
            'amt'         => '0.01',
            'succ'        => 'N',
            'userID'      => '4909251014942593',
            'systemAppID' => '',
            'time'        => '',
            'cur'         => '1',
            'des'         => '',
            'hmac'        => 'c5b039c217b168a096a49963d523efac',
            'paytype'     => '33',
            'sid'         => '122362',
            'userOrderID' => '20140610000123',
            'hmac2'       => '28cc4fa8e7dbee2cdbeeb6eb7f3d84f8',
            'attach'      => 'null'
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'pay_system'  => '12345',
            'orderId'     => '20140610000123',
            'r_orderId'   => '',
            'amt'         => '0.01',
            'succ'        => 'Y',
            'userID'      => '4909251014942593',
            'systemAppID' => '',
            'time'        => '',
            'cur'         => '1',
            'des'         => '',
            'hmac'        => 'c5b039c217b168a096a49963d523efac',
            'paytype'     => '33',
            'sid'         => '122362',
            'userOrderID' => '20140610000123',
            'hmac2'       => 'aad95555d151935269f16edc9dc7dea0',
            'attach'      => 'null'
        ];

        $entry = ['id' => '20140610000321'];

        $weishihII->setOptions($sourceData);
        $weishihII->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'pay_system'  => '12345',
            'orderId'     => '20140610000123',
            'r_orderId'   => '',
            'amt'         => '0.01',
            'succ'        => 'Y',
            'userID'      => '4909251014942593',
            'systemAppID' => '',
            'time'        => '',
            'cur'         => '1',
            'des'         => '',
            'hmac'        => 'c5b039c217b168a096a49963d523efac',
            'paytype'     => '33',
            'sid'         => '122362',
            'userOrderID' => '20140610000123',
            'hmac2'       => 'aad95555d151935269f16edc9dc7dea0',
            'attach'      => 'null'
        ];

        $entry = [
            'id' => '20140610000123',
            'amount' => '12345.6000'
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('3c9544fd10d6637ce27b7286f3551dd2');

        $sourceData = [
            'pay_system'  => '12345',
            'orderId'     => '20140610000123',
            'r_orderId'   => '',
            'amt'         => '0.01',
            'succ'        => 'Y',
            'userID'      => '4909251014942593',
            'systemAppID' => '',
            'time'        => '',
            'cur'         => '1',
            'des'         => '',
            'hmac'        => 'c5b039c217b168a096a49963d523efac',
            'paytype'     => '33',
            'sid'         => '122362',
            'userOrderID' => '20140610000123',
            'hmac2'       => 'aad95555d151935269f16edc9dc7dea0',
            'attach'      => 'null'
        ];

        $entry = [
            'id' => '20140610000123',
            'amount' => '0.01'
        ];

        $weishihII->setOptions($sourceData);
        $weishihII->verifyOrderPayment($entry);

        $this->assertEquals('success', $weishihII->getMsg());
    }

    /**
     * 測試出款沒有帶入privateKey
     */
    public function testWithdrawWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $weishihII = new WeishihII();
        $weishihII->withdrawPayment();
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

        $sourceData = ['username' => ''];

        $weishihII = new WeishihII();
        $weishihII->setPrivateKey('test123');

        $weishihII->setOptions($sourceData);
        $weishihII->withdrawPayment();
    }

    /**
     * 測試出款金額有小數點
     */
    public function testWithdrawButAmountNotAnInteger()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Amount must be an integer',
            150180193
        );

        $sourceData = [
            'username' => 'php1test',
            'orderId' => '201707130001',
            'amount' => '100.23',
            'account' => '112233445566',
            'number' => '77889900',
            'nameReal' => '宇宙人',
            'bank_info_id' => '1',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $respone = new Response();
        $respone->setContent('A-07');
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setResponse($respone);
        $weishihII->setPrivateKey('test123');
        $weishihII->setOptions($sourceData);
        $weishihII->withdrawPayment();
    }

    /**
     * 測試出款返回結果失敗
     */
    public function testWithdrawButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'A-07',
            180124
        );

        $sourceData = [
            'username' => 'php1test',
            'orderId' => '201707130001',
            'amount' => '100.00',
            'account' => '112233445566',
            'number' => '77889900',
            'nameReal' => '宇宙人',
            'bank_info_id' => '1',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $respone = new Response();
        $respone->setContent('A-07');
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setResponse($respone);
        $weishihII->setPrivateKey('test123');
        $weishihII->setOptions($sourceData);
        $weishihII->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'username' => 'php1test',
            'orderId' => '201707130001',
            'amount' => '100.00',
            'account' => '112233445566',
            'number' => '77889900',
            'nameReal' => '宇宙人',
            'bank_info_id' => '1',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $respone = new Response();
        $respone->setContent('ok');
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $weishihII = new WeishihII();
        $weishihII->setContainer($this->container);
        $weishihII->setClient($this->client);
        $weishihII->setResponse($respone);
        $weishihII->setPrivateKey('test123');
        $weishihII->setOptions($sourceData);
        $weishihII->withdrawPayment();
    }
}
