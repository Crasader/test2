<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JuChengFu;
use Buzz\Message\Response;

class JuChengFuTest extends DurianTestCase
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

        $juChengFu = new JuChengFu();
        $juChengFu->getVerifyData();
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

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->getVerifyData();
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
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '100',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少resultCode
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"sign":"8406B0C063B6889666797C40F1CB1C92","payMessage":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少errMsg
     */
    public function testPayReturnWithoutErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"resultCode":"9998","sign":"8406B0C063B6889666797C40F1CB1C92","payMessage":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試二維支付時返回結果失敗
     */
    public function testPayReturnButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '通道异常',
            180130
        );

        $result = '{"resultCode":"9998","errMsg":"通道异常","sign":"9E1A19DEE42B65A876BE30DE9F579EF5","payMessage":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少payMessage
     */
    public function testPayReturnWithoutPayMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"resultCode":"0000","errMsg":"","sign":"7389AF2516A80FF05DDAC55044FE4223"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '{"resultCode":"0000","errMsg":"","sign":"7389AF2516A80FF05DDAC55044FE4223",' .
            '"payMessage":"https:\/\/qpay.qq.com\/qr\/6fc43825"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $verifyData = $juChengFu->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/6fc43825', $verifyData['post_url']);
        $this->assertEmpty($verifyData['params']);
    }

    /**
     * 測試手機支付
     */
    public function testH5PhonePay()
    {
        $result = '{"resultCode":"0000","errMsg":"","sign":"D16F93F648F4980DCEDCDF84' .
            '9312C0F4","payMessage":"https:\/\/qpay.qq.com\/qr\/650ae689"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1104',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201802070000009284',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $verifyData = $juChengFu->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/650ae689', $verifyData['post_url']);
        $this->assertEmpty($verifyData['params']);
    }

    /**
     * 測試網銀支付時返回缺少returnMsg
     */
    public function testPayReturnWithoutReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"returnCode":"0000","url":"http:\/\/wy.mjfhb.top\/mpay\/yee' .
            'SubmitToBank.jsp?p0_Cmd=Buy&p1_MerId=10017242404&p2_Order=2018011095093888208' .
            '7514113&p3_Amt=0.01&p4_Cur=CNY&p5_Pid=php1test&p6_Pcat=productcat&p7_Pdesc=ph' .
            'p1test&p8_Url=http:\/\/wy.mjfhb.top\/mpay\/yeeBankPaySuccess.jsp&p9_SAF=0&pa_' .
            'MP=&pb_ServerNotifyUrl=http:\/\/106.15.82.115\/cnpPayNotify\/notify\/YEE_B2C_' .
            'BANKPAY_T0&pd_FrpId=&pm_Period=&pn_Unit=&pr_NeedResponse=&pt_UserName=&pt_Pos' .
            'talCode=&pt_Address=&pt_TeleNo=&pt_Mobile=&pt_Email=&pt_LeaveMessage=&hmac=32' .
            '5cd30ed02abed843d5857be2afccd0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201801100000008055',
            'amount' => '0.01',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-01-10 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試網銀支付時返回結果失敗
     */
    public function testPayReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单签名异常',
            180130
        );

        $result = '{"returnCode":"0002","returnMsg":"订单签名异常","url":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試網銀支付時返回缺少url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"returnCode":"0000","returnMsg":"\u652f\u4ed8\u8bf7\u6c42\u6210\u529f,' .
            '\u751f\u6210\u94fe\u63a5\u5730\u5740"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $juChengFu->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $result = '{"returnCode":"0000","returnMsg":"\u652f\u4ed8\u8bf7\u6c42\u6210\u529f,' .
            '\u751f\u6210\u94fe\u63a5\u5730\u5740","url":"http:\/\/wy.mjfhb.top\/mpay\/yee' .
            'SubmitToBank.jsp?p0_Cmd=Buy&p1_MerId=10017242404&p2_Order=2018011095093888208' .
            '7514113&p3_Amt=0.01&p4_Cur=CNY&p5_Pid=php1test&p6_Pcat=productcat&p7_Pdesc=ph' .
            'p1test&p8_Url=http:\/\/wy.mjfhb.top\/mpay\/yeeBankPaySuccess.jsp&p9_SAF=0&pa_' .
            'MP=&pb_ServerNotifyUrl=http:\/\/106.15.82.115\/cnpPayNotify\/notify\/YEE_B2C_' .
            'BANKPAY_T0&pd_FrpId=&pm_Period=&pn_Unit=&pr_NeedResponse=&pt_UserName=&pt_Pos' .
            'talCode=&pt_Address=&pt_TeleNo=&pt_Mobile=&pt_Email=&pt_LeaveMessage=&hmac=32' .
            '5cd30ed02abed843d5857be2afccd0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'number' => 'PRO88882018010310001172',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setContainer($this->container);
        $juChengFu->setClient($this->client);
        $juChengFu->setResponse($response);
        $juChengFu->setOptions($options);
        $verifyData = $juChengFu->getVerifyData();

        $postUrl = 'http://wy.mjfhb.top/mpay/yeeSubmitToBank.jsp?p0_Cmd=Buy&p1_MerId=10017242404&' .
            'p2_Order=20180110950938882087514113&p3_Amt=0.01&p4_Cur=CNY&p5_Pid=php1test&p6_Pcat=productcat&' .
            'p7_Pdesc=php1test&p8_Url=http://wy.mjfhb.top/mpay/yeeBankPaySuccess.jsp&p9_SAF=0&pa_MP=' .
            '&pb_ServerNotifyUrl=http://106.15.82.115/cnpPayNotify/notify/YEE_B2C_BANKPAY_T0&pd_FrpId=&' .
            'pm_Period=&pn_Unit=&pr_NeedResponse=&pt_UserName=&pt_PostalCode=&pt_Address=&pt_TeleNo=&pt_Mobile=' .
            '&pt_Email=&pt_LeaveMessage=&hmac=325cd30ed02abed843d5857be2afccd0';

        $this->assertEmpty($verifyData['params']);
        $this->assertSame($postUrl, $verifyData['post_url']);
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

        $juChengFu = new JuChengFu();
        $juChengFu->verifyOrderPayment([]);
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

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'orderPrice' => '0.01',
            'orderTime' => '20180110115428',
            'outTradeNo' => '201801100000008055',
            'payKey' => 'b69bb82af07543169437b5e031047315',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20180110115606',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '20180110950938882087514112',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->verifyOrderPayment([]);
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
            'orderPrice' => '0.01',
            'orderTime' => '20180110115428',
            'outTradeNo' => '201801100000008055',
            'payKey' => 'b69bb82af07543169437b5e031047315',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20180110115606',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '20180110950938882087514112',
            'sign' => '1234',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'orderPrice' => '0.01',
            'orderTime' => '20180110115428',
            'outTradeNo' => '201801100000008055',
            'payKey' => 'b69bb82af07543169437b5e031047315',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20180110115606',
            'tradeStatus' => 'WAITING_PAYMENT',
            'trxNo' => '20180110950938882087514112',
            'sign' => '22cd3721b571b905d60cf485450da130',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->verifyOrderPayment([]);
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
            'orderPrice' => '0.01',
            'orderTime' => '20180110115428',
            'outTradeNo' => '201801100000008055',
            'payKey' => 'b69bb82af07543169437b5e031047315',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20180110115606',
            'tradeStatus' => 'FAILED',
            'trxNo' => '20180110950938882087514112',
            'sign' => 'cec93b8bf73bf08f803d51806501d340',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->verifyOrderPayment([]);
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
            'orderPrice' => '0.01',
            'orderTime' => '20180110115428',
            'outTradeNo' => '201801100000008055',
            'payKey' => 'b69bb82af07543169437b5e031047315',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20180110115606',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '20180110950938882087514112',
            'sign' => '4c18929064998e0f83bd38c996b16479',
        ];

        $entry = ['id' => '201503220000000555'];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->verifyOrderPayment($entry);
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
            'orderPrice' => '0.01',
            'orderTime' => '20180110115428',
            'outTradeNo' => '201801100000008055',
            'payKey' => 'b69bb82af07543169437b5e031047315',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20180110115606',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '20180110950938882087514112',
            'sign' => '4c18929064998e0f83bd38c996b16479',
        ];

        $entry = [
            'id' => '201801100000008055',
            'amount' => '15.00',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'orderPrice' => '0.01',
            'orderTime' => '20180110115428',
            'outTradeNo' => '201801100000008055',
            'payKey' => 'b69bb82af07543169437b5e031047315',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20180110115606',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '20180110950938882087514112',
            'sign' => '4c18929064998e0f83bd38c996b16479',
        ];

        $entry = [
            'id' => '201801100000008055',
            'amount' => '0.01',
        ];

        $juChengFu = new JuChengFu();
        $juChengFu->setPrivateKey('test');
        $juChengFu->setOptions($options);
        $juChengFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $juChengFu->getMsg());
    }
}
