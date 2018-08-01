<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\YiShengFu;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class YiShengFuTest extends DurianTestCase
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

        $yiShengFu = new YiShengFu();
        $yiShengFu->getVerifyData();
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

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->getVerifyData();
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

        $sourceData = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '99',
            'orderCreateDate' => '2017-08-24 21:25:29',
            'ip' => '192.168.108.88',
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->getVerifyData();
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

        $sourceData = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-08-24 21:25:29',
            'ip' => '192.168.108.88',
            'merchant_extra' => [],
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testOnlinePay()
    {
        $sourceData = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2018-04-10 21:25:29',
            'ip' => '192.168.108.88',
            'merchant_extra' => ['platformID' => '866001110014489'],
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $requestData = $yiShengFu->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('1.0.0.1', $requestData['apiVersion']);
        $this->assertEquals('866001110014489', $requestData['platformID']);
        $this->assertEquals('866001110014489', $requestData['merchNo']);
        $this->assertEquals('201804100000010835', $requestData['orderNo']);
        $this->assertEquals('20180410', $requestData['tradeDate']);
        $this->assertEquals('1.00', $requestData['amt']);
        $this->assertEquals('http://154.58.78.54/', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('201804100000010835', $requestData['tradeSummary']);
        $this->assertEquals('192.168.108.88', $requestData['customerIP']);
        $this->assertEquals('a9c765c6ccc1bb118d8bc864dfed736e', $requestData['signMsg']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
        $this->assertEquals('', $requestData['choosePayType']);
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-04-10 21:25:29',
            'ip' => '192.168.108.88',
            'merchant_extra' => ['platformID' => '866001110014489'],
            'verify_url' => '',
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($options);
        $yiShengFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-04-10 21:25:29',
            'ip' => '192.168.108.88',
            'merchant_extra' => ['platformID' => '866001110014489'],
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount>' .
            '<respData></respData></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yiShengFu = new YiShengFu();
        $yiShengFu->setContainer($this->container);
        $yiShengFu->setClient($this->client);
        $yiShengFu->setResponse($response);
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($options);
        $yiShengFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respDesc
     */
    public function testPayReturnWithoutRespDesc()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-04-10 21:25:29',
            'ip' => '192.168.108.88',
            'merchant_extra' => ['platformID' => '866001110014489'],
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount>' .
            '<respData><respCode>00</respCode></respData></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yiShengFu = new YiShengFu();
        $yiShengFu->setContainer($this->container);
        $yiShengFu->setClient($this->client);
        $yiShengFu->setResponse($response);
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($options);
        $yiShengFu->getVerifyData();
    }

    /**
     * 測試支付時返回錯誤訊息
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '银行交易不成功[该银行正在维护，暂停使用[银行通道维护，请稍后重试！]]',
            180130
        );

        $options = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-04-10 21:25:29',
            'ip' => '192.168.108.88',
            'merchant_extra' => ['platformID' => '866001110014489'],
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount><respData>' .
            '<respCode>59</respCode>' .
            '<respDesc>银行交易不成功[该银行正在维护，暂停使用[银行通道维护，请稍后重试！]]</respDesc>' .
            '<codeUrl/></respData><signMsg>5EBD38FB804A2AC7F7DBDCE690D1DFF5</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yiShengFu = new YiShengFu();
        $yiShengFu->setContainer($this->container);
        $yiShengFu->setClient($this->client);
        $yiShengFu->setResponse($response);
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($options);
        $yiShengFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回codeUrl
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-04-10 21:25:29',
            'ip' => '192.168.108.88',
            'merchant_extra' => ['platformID' => '866001110014489'],
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount>' .
            '<respData><respCode>00</respCode><respDesc>成功</respDesc>' .
            '</respData><signMsg>3D580DDC3CFE453A5060F384D85F31E0</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yiShengFu = new YiShengFu();
        $yiShengFu->setContainer($this->container);
        $yiShengFu->setClient($this->client);
        $yiShengFu->setResponse($response);
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($options);
        $yiShengFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '866001110014489',
            'orderId' => '201804100000010835',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-04-10 21:25:29',
            'ip' => '192.168.108.88',
            'merchant_extra' => ['platformID' => '866001110014489'],
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount>' .
            '<respData><respCode>00</respCode><respDesc>成功</respDesc>' .
            '<codeUrl>aHR0cHM6Ly9xcGF5LnFxLmNvbS9xci81ZTE4YTlkOA==</codeUrl>' .
            '</respData><signMsg>3D580DDC3CFE453A5060F384D85F31E0</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yiShengFu = new YiShengFu();
        $yiShengFu->setContainer($this->container);
        $yiShengFu->setClient($this->client);
        $yiShengFu->setResponse($response);
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($options);
        $requestData = $yiShengFu->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals(base64_decode('aHR0cHM6Ly9xcGF5LnFxLmNvbS9xci81ZTE4YTlkOA=='), $yiShengFu->getQrcode());
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

        $yiShengFu = new YiShengFu();
        $yiShengFu->verifyOrderPayment([]);
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

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->verifyOrderPayment([]);
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

        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180410161704',
            'tradeAmt' => '1.00',
            'merchNo' => '866001110014489',
            'merchParam' => '',
            'orderNo' => '201804100000010835',
            'tradeDate' => '20180410',
            'accNo' => '17376480',
            'accDate' => '20180410',
            'orderStatus' => '1',
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180410161704',
            'tradeAmt' => '1.00',
            'merchNo' => '866001110014489',
            'merchParam' => '',
            'orderNo' => '201804100000010835',
            'tradeDate' => '20180410',
            'accNo' => '17376480',
            'accDate' => '20180410',
            'orderStatus' => '1',
            'signMsg' => 'wadeissohansome',
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180410161704',
            'tradeAmt' => '1.00',
            'merchNo' => '866001110014489',
            'merchParam' => '',
            'orderNo' => '201804100000010835',
            'tradeDate' => '20180410',
            'accNo' => '17376480',
            'accDate' => '20180410',
            'orderStatus' => '0',
            'signMsg' => '6f32bb368d950a4d2461cfbee4cb6a8c',
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->verifyOrderPayment([]);
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

        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180410161704',
            'tradeAmt' => '1.00',
            'merchNo' => '866001110014489',
            'merchParam' => '',
            'orderNo' => '201804100000010835',
            'tradeDate' => '20180410',
            'accNo' => '17376480',
            'accDate' => '20180410',
            'orderStatus' => '2',
            'signMsg' => '2910d200dd9b5e7072f7c50435d796cf',
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->verifyOrderPayment([]);
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

        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180410161704',
            'tradeAmt' => '1.00',
            'merchNo' => '866001110014489',
            'merchParam' => '',
            'orderNo' => '201804100000010835',
            'tradeDate' => '20180410',
            'accNo' => '17376480',
            'accDate' => '20180410',
            'orderStatus' => '1',
            'signMsg' => 'c479407008aa96b2da377ee46ef7e6a5',
        ];

        $entry = ['id' => '201708240000000321'];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->verifyOrderPayment($entry);
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

        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180410161704',
            'tradeAmt' => '1.00',
            'merchNo' => '866001110014489',
            'merchParam' => '',
            'orderNo' => '201804100000010835',
            'tradeDate' => '20180410',
            'accNo' => '17376480',
            'accDate' => '20180410',
            'orderStatus' => '1',
            'signMsg' => 'c479407008aa96b2da377ee46ef7e6a5',
        ];

        $entry = [
            'id' => '201804100000010835',
            'amount' => '10.00',
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180410161704',
            'tradeAmt' => '1.00',
            'merchNo' => '866001110014489',
            'merchParam' => '',
            'orderNo' => '201804100000010835',
            'tradeDate' => '20180410',
            'accNo' => '17376480',
            'accDate' => '20180410',
            'orderStatus' => '1',
            'signMsg' => 'c479407008aa96b2da377ee46ef7e6a5',
        ];

        $entry = [
            'id' => '201804100000010835',
            'amount' => '1.00',
        ];

        $yiShengFu = new YiShengFu();
        $yiShengFu->setPrivateKey('test');
        $yiShengFu->setOptions($sourceData);
        $yiShengFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yiShengFu->getMsg());
    }
}
