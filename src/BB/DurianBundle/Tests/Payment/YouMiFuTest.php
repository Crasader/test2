<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YouMiFu;
use Buzz\Message\Response;

class YouMiFuTest extends DurianTestCase
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

        $youMiFu = new YouMiFu();
        $youMiFu->getVerifyData();
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

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->getVerifyData();
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
            'orderId' => '201802050000008859',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '99',
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->getVerifyData();
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
            'orderId' => '201802050000008859',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => [],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201802050000008859',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['platformID' => '856086110012949'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $requestData = $youMiFu->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('1.0.0.0', $requestData['apiVersion']);
        $this->assertEquals('856086110012949', $requestData['platformID']);
        $this->assertEquals('acctest', $requestData['merchNo']);
        $this->assertEquals('201802050000008859', $requestData['orderNo']);
        $this->assertEquals('20180202', $requestData['tradeDate']);
        $this->assertEquals('100.00', $requestData['amt']);
        $this->assertEquals('http://www.mokepay.cn/return.php', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('php1test', $requestData['tradeSummary']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
        $this->assertEquals('3bcc68f0650bab5de16752448c042bff', $requestData['signMsg']);
        $this->assertEquals('1', $requestData['choosePayType']);
        $this->assertEquals('192.168.0.100', $requestData['customerIP']);
    }

    /**
     * 測試手機支付
     */
    public function testWapPay()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201802050000008859',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1097',
            'merchant_extra' => ['platformID' => '856086110012949'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $requestData = $youMiFu->getVerifyData();

        $this->assertEquals('WAP_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('1.0.0.0', $requestData['apiVersion']);
        $this->assertEquals('856086110012949', $requestData['platformID']);
        $this->assertEquals('acctest', $requestData['merchNo']);
        $this->assertEquals('201802050000008859', $requestData['orderNo']);
        $this->assertEquals('20180202', $requestData['tradeDate']);
        $this->assertEquals('100.00', $requestData['amt']);
        $this->assertEquals('http://www.mokepay.cn/return.php', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('php1test', $requestData['tradeSummary']);
        $this->assertEquals('', $requestData['bankCode']);
        $this->assertEquals('8b8ff87332ee1808386008fcd58203d7', $requestData['signMsg']);
        $this->assertEquals('13', $requestData['choosePayType']);
        $this->assertEquals('192.168.0.100', $requestData['customerIP']);
    }

    /**
     * 測試支付銀行為二維，但沒帶入verify_url
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
            'orderId' => '856086110012949',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['platformID' => '856086110012949'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => '',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，但沒返回respCode
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
            'orderId' => '856086110012949',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['platformID' => '856086110012949'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount><respData>' .
            '<respDesc>该银行正在维护，暂停使用[银行通道维护，请稍后重试！]</respDesc><codeUrl/></respData>' .
            '<signMsg>A5658EDC4A75C6099461834F45C92F7A</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $youMiFu = new YouMiFu();
        $youMiFu->setContainer($this->container);
        $youMiFu->setClient($this->client);
        $youMiFu->setResponse($response);
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，但返回失敗
     */
    public function testPayWithWeiXinButReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '该银行正在维护，暂停使用[银行通道维护，请稍后重试！]',
            180130
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '856086110012949',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['platformID' => '856086110012949'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount><respData><respCode>30</respCode>' .
            '<respDesc>该银行正在维护，暂停使用[银行通道维护，请稍后重试！]</respDesc><codeUrl/></respData>' .
            '<signMsg>A5658EDC4A75C6099461834F45C92F7A</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $youMiFu = new YouMiFu();
        $youMiFu->setContainer($this->container);
        $youMiFu->setClient($this->client);
        $youMiFu->setResponse($response);
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，沒返回codeUrl
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
            'orderId' => '856086110012949',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['platformID' => '856086110012949'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount><respData><respCode>00</respCode>' .
            '<respDesc>成功</respDesc></respData><signMsg>0709FAB00073138B97B7989E48887A18</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/xml;');

        $youMiFu = new YouMiFu();
        $youMiFu->setContainer($this->container);
        $youMiFu->setClient($this->client);
        $youMiFu->setResponse($response);
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維
     */
    public function testPayWithWeiXin()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '856086110012949',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['platformID' => '856086110012949'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.https.trade.hfbpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?><moboAccount><respData><respCode>00</respCode>' .
            '<respDesc>成功</respDesc><codeUrl>d2VpeGluOi8vd3hwYXkvYml6cGF5dXJsP3ByPXVaSGN3SnU=</codeUrl>' .
            '</respData><signMsg>0709FAB00073138B97B7989E48887A18</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;');

        $youMiFu = new YouMiFu();
        $youMiFu->setContainer($this->container);
        $youMiFu->setClient($this->client);
        $youMiFu->setResponse($response);
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $requestData = $youMiFu->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=uZHcwJu', $youMiFu->getQrcode());
    }

    /**
     * 測試銀聯在線支付
     */
    public function testUnionPay()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201802050000008859',
            'orderCreateDate' => '2018-02-02 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://www.mokepay.cn/return.php',
            'paymentVendorId' => '278',
            'merchant_extra' => ['platformID' => '856086110012949'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $requestData = $youMiFu->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('1.0.0.0', $requestData['apiVersion']);
        $this->assertEquals('856086110012949', $requestData['platformID']);
        $this->assertEquals('acctest', $requestData['merchNo']);
        $this->assertEquals('201802050000008859', $requestData['orderNo']);
        $this->assertEquals('20180202', $requestData['tradeDate']);
        $this->assertEquals('100.00', $requestData['amt']);
        $this->assertEquals('http://www.mokepay.cn/return.php', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('php1test', $requestData['tradeSummary']);
        $this->assertEquals('12', $requestData['bankCode']);
        $this->assertEquals('3bcc68f0650bab5de16752448c042bff', $requestData['signMsg']);
        $this->assertEquals('12', $requestData['choosePayType']);
        $this->assertEquals('192.168.0.100', $requestData['customerIP']);
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

        $youMiFu = new YouMiFu();
        $youMiFu->verifyOrderPayment([]);
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

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->verifyOrderPayment([]);
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
            'notifyTime' => '20180205115030',
            'tradeAmt' => '0.01',
            'merchNo' => '856086110012949',
            'merchParam' => '3740_6',
            'orderNo' => '201802050000008859',
            'tradeDate' => '20180205',
            'accNo' => '10241514',
            'accDate' => '20180205',
            'orderStatus' => '1',
            'notifyType' => '1',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->verifyOrderPayment([]);
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
            'notifyTime' => '20180205115030',
            'tradeAmt' => '0.01',
            'merchNo' => '856086110012949',
            'merchParam' => '3740_6',
            'orderNo' => '201802050000008859',
            'tradeDate' => '20180205',
            'accNo' => '10241514',
            'accDate' => '20180205',
            'orderStatus' => '1',
            'signMsg' => '300E759C9CCA47309608DC9286D489EC',
            'notifyType' => '1',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->verifyOrderPayment([]);
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
            'notifyTime' => '20180205115030',
            'tradeAmt' => '0.01',
            'merchNo' => '856086110012949',
            'merchParam' => '3740_6',
            'orderNo' => '201802050000008859',
            'tradeDate' => '20180205',
            'accNo' => '10241514',
            'accDate' => '20180205',
            'orderStatus' => '5',
            'signMsg' => '05a38919cfb93aeaad643a61b044ede7',
            'notifyType' => '1',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->verifyOrderPayment([]);
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
            'notifyTime' => '20180205115030',
            'tradeAmt' => '0.01',
            'merchNo' => '856086110012949',
            'merchParam' => '3740_6',
            'orderNo' => '201802050000008859',
            'tradeDate' => '20180205',
            'accNo' => '10241514',
            'accDate' => '20180205',
            'orderStatus' => '1',
            'signMsg' => 'beb6478742abfcdd2137a9d31f9fb24e',
            'notifyType' => '1',
        ];

        $entry = ['id' => '201503220000000321'];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->verifyOrderPayment($entry);
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
            'notifyTime' => '20180205115030',
            'tradeAmt' => '0.01',
            'merchNo' => '856086110012949',
            'merchParam' => '3740_6',
            'orderNo' => '201802050000008859',
            'tradeDate' => '20180205',
            'accNo' => '10241514',
            'accDate' => '20180205',
            'orderStatus' => '1',
            'signMsg' => 'beb6478742abfcdd2137a9d31f9fb24e',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201802050000008859',
            'amount' => '10.00',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180205115030',
            'tradeAmt' => '0.01',
            'merchNo' => '856086110012949',
            'merchParam' => '3740_6',
            'orderNo' => '201802050000008859',
            'tradeDate' => '20180205',
            'accNo' => '10241514',
            'accDate' => '20180205',
            'orderStatus' => '1',
            'signMsg' => 'beb6478742abfcdd2137a9d31f9fb24e',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201802050000008859',
            'amount' => '0.01',
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('test');
        $youMiFu->setOptions($options);
        $youMiFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $youMiFu->getMsg());
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

        $youMiFu = new YouMiFu();
        $youMiFu->withdrawPayment();
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

        $sourceData = ['account' => ''];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('jy9CV6uguTE=');

        $youMiFu->setOptions($sourceData);
        $youMiFu->withdrawPayment();
    }

    /**
     * 測試出款缺少商家附加設定值
     */
    public function testWithdrawWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'province' => '湖南省',
            'city' => '株洲市',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'merchant_extra' => [],
        ];

        $youMiFu = new YouMiFu();
        $youMiFu->setPrivateKey('jy9CV6uguTE=');
        $youMiFu->setOptions($sourceData);
        $youMiFu->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少參數
     */
    public function testWithdrawButNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'province' => '湖南省',
            'city' => '株洲市',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京之行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['platformID' => '10000080001641'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>20</respCode>' .
            '</respData><signMsg>2691856FDBE3D20BCA5BA962B9E68372</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $youMiFu = new YouMiFu();
        $youMiFu->setContainer($this->container);
        $youMiFu->setClient($this->client);
        $youMiFu->setResponse($response);
        $youMiFu->setPrivateKey('12345');
        $youMiFu->setOptions($sourceData);
        $youMiFu->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户人工结算申请金额超过可结算金额[商户余额不足]',
            180124
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'province' => '湖南省',
            'city' => '株洲市',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京之行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['platformID' => '10000080001641'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>276</respCode>' .
            '<respDesc>商户人工结算申请金额超过可结算金额[商户余额不足]</respDesc>' .
            '</respData><signMsg>2691856FDBE3D20BCA5BA962B9E68372</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $youMiFu = new YouMiFu();
        $youMiFu->setContainer($this->container);
        $youMiFu->setClient($this->client);
        $youMiFu->setResponse($response);
        $youMiFu->setPrivateKey('12345');
        $youMiFu->setOptions($sourceData);
        $youMiFu->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'province' => '湖南省',
            'city' => '株洲市',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京之行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['platformID' => '10000080001641'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc><batchNo>705111</batchNo><accDate>20180110</accDate>' .
            '</respData><signMsg>8ADFDAFDB14AD630BCC5897D38038F97</signMsg></moboAccount>';

        $mockCwe = $this->getMockBuilder('BB\DurianBundle\Entity\CashWithdrawEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCwe->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCwe);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCwe);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $youMiFu = new YouMiFu();
        $youMiFu->setContainer($mockContainer);
        $youMiFu->setClient($this->client);
        $youMiFu->setResponse($response);
        $youMiFu->setPrivateKey('12345');
        $youMiFu->setOptions($sourceData);
        $youMiFu->withdrawPayment();
    }
}
