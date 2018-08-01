<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiHePay;
use Buzz\Message\Response;

class HuiHePayTest extends DurianTestCase
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
            ->will($this->returnValue(null));

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

        $huiHePay = new HuiHePay();
        $huiHePay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '9999',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => '',
        ];

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['Message' => "渠道权限未开通，请联系商务代表开通相关渠道使用权限"];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '渠道权限未开通，请联系商务代表开通相关渠道使用权限',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'Code' => 1,
            'Message' => "渠道权限未开通，请联系商务代表开通相关渠道使用权限",
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗沒有Message
     */
    public function testPayReturnNotSuccessWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['Code' => 1];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回QrCode
     */
    public function testPayReturnWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['Code' => 0];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->getVerifyData();
    }

    /**
     * 測試京東手機支付時沒有返回Form
     */
    public function testJDphonePayReturnWithoutForm()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1108',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['Code' => 0];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->getVerifyData();
    }

    /**
     * 測試京東手機支付時返回的Form沒有提交網址
     */
    public function testJDPhonePayReturnFormWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1108',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $formString = "<form action='' method='post' target='_top'></form>" .
            "<script>document.getElementsByTagName('form')[0].submit()</script>";

        $result = [
            'Form' => $formString,
            'Code' => 0,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'QrCode' => 'https://qr.alipay.com/bax07199zgusr2em2x2x6036',
            'Code' => 0,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $data = $huiHePay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.alipay.com/bax07199zgusr2em2x2x6036', $huiHePay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1098',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'QrCode' => 'https://qr.alipay.com/bax07199zgusr2em2x2x6036',
            'Code' => 0,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $data = $huiHePay->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/bax07199zgusr2em2x2x6036', $data['act_url']);
    }

    /**
     * 測試京東手機支付
     */
    public function testJDPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1108',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $formString = "<form action='https://h5pay.jd.com/code?c=64kkkwmb5p0t5n' method='post'" .
            "target='_top'></form><script>document.getElementsByTagName('form')[0].submit()</script>";

        $result = [
            'Form' => $formString,
            'Code' => 0,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiHePay = new HuiHePay();
        $huiHePay->setContainer($this->container);
        $huiHePay->setClient($this->client);
        $huiHePay->setResponse($response);
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $data = $huiHePay->getVerifyData();

        $this->assertEquals('https://h5pay.jd.com/code?c=64kkkwmb5p0t5n', $data['act_url']);
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

        $huiHePay = new HuiHePay();
        $huiHePay->verifyOrderPayment([]);
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

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時code不正確
     */
    public function testReturnCodeNotCorrect()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'AppId' => '201708181614086782',
            'Code' => '1',
            'OutTradeNo' => '201709180000004711',
            'Sign' => '59648C0F339C6EEA9263A9DEE16D8590',
            'SignType' => 'MD5',
            'TotalAmount' => '0.1',
            'TradeNo' => '2017091812052353159682',
        ];

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->verifyOrderPayment([]);
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
            'AppId' => '201708181614086782',
            'Code' => '0',
            'OutTradeNo' => '201709180000004711',
            'SignType' => 'MD5',
            'TotalAmount' => '0.1',
            'TradeNo' => '2017091812052353159682',
        ];

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->verifyOrderPayment([]);
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
            'AppId' => '201708181614086782',
            'Code' => '0',
            'OutTradeNo' => '201709180000004711',
            'Sign' => '59648C0F339C6EEA9263A9DEE16D8590',
            'SignType' => 'MD5',
            'TotalAmount' => '0.1',
            'TradeNo' => '2017091812052353159682',
        ];

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->verifyOrderPayment([]);
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
            'AppId' => '201708181614086782',
            'Code' => '0',
            'OutTradeNo' => '201709180000004711',
            'Sign' => 'C0C075F239B9029213C8CA4B8B2CCE17',
            'SignType' => 'MD5',
            'TotalAmount' => '0.1',
            'TradeNo' => '2017091812052353159682',
        ];

        $entry = ['id' => '201503220000000555'];

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->verifyOrderPayment($entry);
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
            'AppId' => '201708181614086782',
            'Code' => '0',
            'OutTradeNo' => '201709180000004711',
            'Sign' => 'C0C075F239B9029213C8CA4B8B2CCE17',
            'SignType' => 'MD5',
            'TotalAmount' => '0.1',
            'TradeNo' => '2017091812052353159682',
        ];

        $entry = [
            'id' => '201709180000004711',
            'amount' => '15.00',
        ];

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'AppId' => '201708181614086782',
            'Code' => '0',
            'OutTradeNo' => '201709180000004711',
            'Sign' => 'C0C075F239B9029213C8CA4B8B2CCE17',
            'SignType' => 'MD5',
            'TotalAmount' => '0.1',
            'TradeNo' => '2017091812052353159682',
        ];

        $entry = [
            'id' => '201709180000004711',
            'amount' => '0.1',
        ];

        $huiHePay = new HuiHePay();
        $huiHePay->setPrivateKey('test');
        $huiHePay->setOptions($options);
        $huiHePay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $huiHePay->getMsg());
    }
}
