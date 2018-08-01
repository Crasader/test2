<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShuenShinFu;
use Buzz\Message\Response;

class ShuenShinFuTest extends DurianTestCase
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

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->getVerifyData();
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

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->getVerifyData();
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
            'number' => '10000080001641',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '99',
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->getVerifyData();
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
            'number' => '10000080001641',
            'orderId' => '201709150000007065',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'merchant_extra' => [],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '10000080001641',
            'orderId' => '201709150000007065',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'merchant_extra' => ['platformID' => '10000080001641'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $requestData = $shuenShinFu->getVerifyData();

        $this->assertEquals('10000080001641', $requestData['merchNo']);
        $this->assertEquals('201709150000007065', $requestData['orderNo']);
        $this->assertEquals('20160527', $requestData['tradeDate']);
        $this->assertEquals('100', $requestData['amt']);
        $this->assertEquals('http://two123.comxa.com/', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
    }

    /**
     * 測試支付銀行為二維，但沒帶入verify_url
     */
    public function testPayWithQrcodeWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '10000080001641',
            'orderId' => '201709150000007065',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'merchant_extra' => ['platformID' => '10000080001641'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => '',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，但沒返回respCode
     */
    public function testPayWithQrcodeWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '10000080001641',
            'orderId' => '201709150000007065',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'merchant_extra' => ['platformID' => '10000080001641'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respDesc>交易处理失败[]</respDesc></respData>' .
            '<signMsg>1A24C9B7A7C3A7E467F0847B644A5218</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setContainer($this->container);
        $shuenShinFu->setClient($this->client);
        $shuenShinFu->setResponse($response);
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，但返回失敗
     */
    public function testPayWithQrcodeButReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易处理失败[]',
            180130
        );

        $options = [
            'number' => '10000080001641',
            'orderId' => '201709150000007065',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => '10000080001641'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>01</respCode><respDesc>交易处理失败[]</respDesc></respData>' .
            '<signMsg>1A24C9B7A7C3A7E467F0847B644A5218</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setContainer($this->container);
        $shuenShinFu->setClient($this->client);
        $shuenShinFu->setResponse($response);
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維，沒返回codeUrl
     */
    public function testPayWithQrcodeWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '10000080001641',
            'orderId' => '201709150000007065',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'merchant_extra' => ['platformID' => '10000080001641'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount><respData><respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc></respData><signMsg>A128D2F11A50490ACEDEA9FD92AD20B1' .
            '</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setContainer($this->container);
        $shuenShinFu->setClient($this->client);
        $shuenShinFu->setResponse($response);
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維
     */
    public function testPayWithQrcode()
    {
        $options = [
            'number' => '10000080001641',
            'orderId' => '201709150000007065',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'merchant_extra' => ['platformID' => '10000080001641'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount><respData><respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc><codeUrl>aHR0cHM6Ly9teXVuLnRlbnBheS5jb20vbXFxL3BheS9xcmNvZGUu' .
            'aHRtbD9fd3Y9MTAyNyZfYmlkPTIxODMmdD01VmRlYjQzMWJmZTFhN2JmZDFjMDFjZjg1MmVlZGY3Mg==</codeUrl>' .
            '</respData><signMsg>A128D2F11A50490ACEDEA9FD92AD20B1</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;');

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setContainer($this->container);
        $shuenShinFu->setClient($this->client);
        $shuenShinFu->setResponse($response);
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $requestData = $shuenShinFu->getVerifyData();

        $url = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=5Vdeb431bfe1a7bfd1c01cf852eedf72';
        $this->assertEmpty($requestData);
        $this->assertEquals($url, $shuenShinFu->getQrcode());
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

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->verifyOrderPayment([]);
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

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->verifyOrderPayment([]);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '10000080001641',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'notifyType' => '1',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->verifyOrderPayment([]);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '10000080001641',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'signMsg' => '80B9A254C11B629732BD197AE82DFB14',
            'notifyType' => '1',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單未支付
     */
    public function testReturnButUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '10000080001641',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '0',
            'signMsg' => 'c557cd4ffbe0ef53dc814f38e56dd3d2',
            'notifyType' => '1',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->verifyOrderPayment([]);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '10000080001641',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '9',
            'signMsg' => '426abb47a20d7bc053ecdd48e3126bdf',
            'notifyType' => '1',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->verifyOrderPayment([]);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '10000080001641',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'signMsg' => 'b18c96ade3d280b47388770bdfdf9cf5',
            'notifyType' => '1',
        ];

        $entry = ['id' => '201503220000000321'];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->verifyOrderPayment($entry);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '10000080001641',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'signMsg' => 'b18c96ade3d280b47388770bdfdf9cf5',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201712220000008233',
            'amount' => '10.00',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '10000080001641',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'signMsg' => 'b18c96ade3d280b47388770bdfdf9cf5',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201712220000008233',
            'amount' => '0.01',
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('test');
        $shuenShinFu->setOptions($options);
        $shuenShinFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $shuenShinFu->getMsg());
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

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->withdrawPayment();
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

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('jy9CV6uguTE=');

        $shuenShinFu->setOptions($sourceData);
        $shuenShinFu->withdrawPayment();
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
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'merchant_extra' => [],
        ];

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setPrivateKey('jy9CV6uguTE=');
        $shuenShinFu->setOptions($sourceData);
        $shuenShinFu->withdrawPayment();
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
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setContainer($this->container);
        $shuenShinFu->setClient($this->client);
        $shuenShinFu->setResponse($response);
        $shuenShinFu->setPrivateKey('12345');
        $shuenShinFu->setOptions($sourceData);
        $shuenShinFu->withdrawPayment();
    }

    /**
     * 測試出款但餘額不足
     */
    public function testWithdrawButInsufficientBalance()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Insufficient balance',
            150180197
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
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京之行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['platformID' => '10000080001641'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>20</respCode>' .
            '<respDesc>商户可用余额不足[余额不足]</respDesc>' .
            '</respData><signMsg>2691856FDBE3D20BCA5BA962B9E68372</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setContainer($this->container);
        $shuenShinFu->setClient($this->client);
        $shuenShinFu->setResponse($response);
        $shuenShinFu->setPrivateKey('12345');
        $shuenShinFu->setOptions($sourceData);
        $shuenShinFu->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户人工结算申请金额超过可结算金额[商户余额不足',
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
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setContainer($this->container);
        $shuenShinFu->setClient($this->client);
        $shuenShinFu->setResponse($response);
        $shuenShinFu->setPrivateKey('12345');
        $shuenShinFu->setOptions($sourceData);
        $shuenShinFu->withdrawPayment();
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
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shuenShinFu = new ShuenShinFu();
        $shuenShinFu->setContainer($mockContainer);
        $shuenShinFu->setClient($this->client);
        $shuenShinFu->setResponse($response);
        $shuenShinFu->setPrivateKey('12345');
        $shuenShinFu->setOptions($sourceData);
        $shuenShinFu->withdrawPayment();
    }
}
