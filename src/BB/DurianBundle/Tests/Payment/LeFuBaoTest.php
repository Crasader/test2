<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\LeFuBao;
use Buzz\Message\Response;

class LeFuBaoTest extends DurianTestCase
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

        $leFuBao = new LeFuBao();
        $leFuBao->getVerifyData();
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

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->getVerifyData();
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
            'number' => '210001140011444',
            'orderId' => '201612080000000111',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.a/',
            'paymentVendorId' => '99',
            'username' => 'php1test',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->getVerifyData();
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
            'number' => '210001140011444',
            'orderId' => '201612080000000111',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => [],
            'username' => 'php1test',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->getVerifyData();
    }

    /**
     * 測試支付設定回傳成功
     */
    public function testPay()
    {
        $options = [
            'number' => '210001140011444',
            'orderId' => '201612080000000111',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['platformID' => '201612080000000111'],
            'username' => 'php1test',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $requestData = $leFuBao->getVerifyData();

        $this->assertEquals($options['number'], $requestData['merchNo']);
        $this->assertEquals($options['orderId'], $requestData['orderNo']);
        $this->assertEquals('20161212', $requestData['tradeDate']);
        $this->assertEquals($options['amount'], $requestData['amt']);
        $this->assertEquals($options['notify_url'], $requestData['merchUrl']);
        $this->assertEquals('20161212', $requestData['tradeDate']);
        $this->assertEquals($options['merchant_extra']['platformID'], $requestData['platformID']);
        $this->assertEquals('836b25a70df6350946be17d12bd1b7cc', $requestData['signMsg']);
    }

    /**
     * 測試支付銀行為二維
     */
    public function testPayWithScan()
    {
        $options = [
            'number' => '210001140011444',
            'orderId' => '201612080000000111',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['platformID' => '201612080000000111'],
            'username' => 'php1test',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $requestData = $leFuBao->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals($options['number'], $requestData['merchNo']);
        $this->assertEquals($options['orderId'], $requestData['orderNo']);
        $this->assertEquals('20161212', $requestData['tradeDate']);
        $this->assertEquals($options['amount'], $requestData['amt']);
        $this->assertEquals($options['notify_url'], $requestData['merchUrl']);
        $this->assertEquals('20161212', $requestData['tradeDate']);
        $this->assertEquals($options['merchant_extra']['platformID'], $requestData['platformID']);
        $this->assertEquals('836b25a70df6350946be17d12bd1b7cc', $requestData['signMsg']);
        $this->assertEquals('5', $requestData['choosePayType']);
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

        $leFuBao = new LeFuBao();
        $leFuBao->verifyOrderPayment([]);
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

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->verifyOrderPayment([]);
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
            'notifyTime' => '20161212122018',
            'tradeAmt' => '100.00',
            'merchNo' => '210001140011444',
            'merchParam' => '',
            'orderNo' => '201612080000000185',
            'tradeDate' => '20161208',
            'accNo' => '18316133',
            'accDate' => '20161212',
            'orderStatus' => '1',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->verifyOrderPayment([]);
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
            'notifyTime' => '20161212122018',
            'tradeAmt' => '100.00',
            'merchNo' => '210001140011444',
            'merchParam' => '',
            'orderNo' => '201612080000000185',
            'tradeDate' => '20161212',
            'accNo' => '18316133',
            'accDate' => '20161208',
            'orderStatus' => '1',
            'signMsg' => 'aaaaaaa',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->verifyOrderPayment([]);
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
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '0',
            'signMsg' => '8ea37251add2506d374ccbf990595ed7',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->verifyOrderPayment([]);
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
            'notifyTime' => '20161212122018',
            'tradeAmt' => '0.01',
            'merchNo' => '210001140011444',
            'merchParam' => '58_6',
            'orderNo' => '201612080000000185',
            'tradeDate' => '20161208',
            'accNo' => '18316133',
            'accDate' => '20161212',
            'orderStatus' => '1',
            'signMsg' => 'e588be8927623362a6c28fbab943bba0',
        ];

        $entry = ['id' => '2016120800000001285'];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->verifyOrderPayment($entry);
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
            'notifyTime' => '20161212122018',
            'tradeAmt' => '0.01',
            'merchNo' => '210001140011444',
            'merchParam' => '58_6',
            'orderNo' => '201612080000000185',
            'tradeDate' => '20161208',
            'accNo' => '18316133',
            'accDate' => '20161212',
            'orderStatus' => '1',
            'signMsg' => 'e588be8927623362a6c28fbab943bba0',
        ];

        $entry = [
            'id' => '201612080000000185',
            'amount' => '10.00',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20161212122018',
            'tradeAmt' => '0.01',
            'merchNo' => '210001140011444',
            'merchParam' => '58_6',
            'orderNo' => '201612080000000185',
            'tradeDate' => '20161208',
            'accNo' => '18316133',
            'accDate' => '20161212',
            'orderStatus' => '1',
            'signMsg' => 'e588be8927623362a6c28fbab943bba0',
        ];

        $entry = [
            'id' => '201612080000000185',
            'amount' => '0.01',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $leFuBao->getMsg());
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

        $leFuBao = new LeFuBao();
        $leFuBao->paymentTracking();
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

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->paymentTracking();
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
            'number' => '20161212',
            'orderId' => '201612080000000185',
            'orderCreateDate' => '20161208',
            'amount' => '100',
            'merchant_extra' => []
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
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
            'number' => '210001140011444',
            'orderId' => '201612080000000185',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'abc'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
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
            'number' => '210001140011444',
            'orderId' => '201612080000000181',
            'orderCreateDate' => '2015-12-08 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
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
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount><respData></respData></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果驗證沒有respCode的情況
     */
    public function testTrackingReturnWithNoRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
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

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單不存在
     */
    public function testTrackingReturnWithOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $options = [
            'number' => '210001140011444',
            'orderId' => '201612080000000999',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>22</respCode>' .
            '<respDesc>查询订单信息不存在[订单信息不存在]</respDesc>' .
            '</respData>' .
            '<signMsg>D56439DAD50B7E900AAB9ECF2ED6554E</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;content-type:charset=utf-8');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
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
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<moboAccount>' .
                '<respData>' .
                '<respCode>22</respCode>' .
                '<respDesc>交易成功</respDesc>' .
                '<orderDate>20161212</orderDate>' .
                '<accDate/>' .
                '<orderNo>18300165</orderNo>' .
                '<accNo>18300165</accNo>' .
                '<exchangeRate>0.0000</exchangeRate>' .
                '<Status>0</Status></respData>' .
                '<signMsg>c8cf9aa3fe1ec20da112453fe7fe2a03</signMsg>' .
                '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
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
            'orderId' => '201612080000000185',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20161212</orderDate>' .
            '<accDate/>' .
            '<orderNo>18300165</orderNo>' .
            '<accNo>18300165</accNo>' .
            '<exchangeRate>0.0000</exchangeRate>' .
            '<Status>0</Status></respData>' .
            '<signMsg>C8CF9AA3FE1EC20DA112453FE7FE2A03</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態為未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '210001140011444',
            'orderId' => '201612080000000181',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '0.01',
            'merchant_extra' => ['platformID' => '210001140011444'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20161212</orderDate>' .
            '<accDate/>' .
            '<orderNo>18300165</orderNo>' .
            '<accNo>18300165</accNo>' .
            '<exchangeRate>0.0000</exchangeRate>' .
            '<Status>0</Status></respData>' .
            '<signMsg>ecf66facf0a2d354103120915599bf06</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
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
            'number' => '210001140011444',
            'orderId' => '201612080000000181',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '0.01',
            'merchant_extra' => ['platformID' => '210001140011444'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20161212</orderDate>' .
            '<accDate/>' .
            '<orderNo>18300165</orderNo>' .
            '<accNo>18300165</accNo>' .
            '<exchangeRate>0.0000</exchangeRate>' .
            '<Status>2</Status></respData>' .
            '<signMsg>8b6b057d086311149d066dae30126287</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '210001140011444',
            'orderId' => '201612080000000181',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '0.01',
            'merchant_extra' => ['platformID' => '210001140011444'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20161212</orderDate>' .
            '<accDate/>' .
            '<orderNo>18300165</orderNo>' .
            '<accNo>18300165</accNo>' .
            '<exchangeRate>0.0000</exchangeRate>' .
            '<Status>1</Status></respData>' .
            '<signMsg>fa0a1030c5f8c152f98c07ab097bc97c</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
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
            'number' => '210001140011444',
            'orderId' => '201612080000000181',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '0.01',
            'merchant_extra' => ['platformID' => '210001140011444'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
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
            'number' => '210001140011444',
            'orderId' => '201612080000000181',
            'orderCreateDate' => '2016-12-12 21:25:29',
            'amount' => '0.01',
            'merchant_extra' => ['platformID' => '210001140011444'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');

        $leFuBao = new LeFuBao();
        $leFuBao->setContainer($this->container);
        $leFuBao->setClient($this->client);
        $leFuBao->setResponse($response);
        $leFuBao->setPrivateKey('test');
        $leFuBao->setOptions($options);
        $leFuBao->paymentTracking();
    }
}
