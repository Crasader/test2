<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewIPS7;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class NewIPS7Test extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Aw\Nusoap\NusoapClient
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

        $this->client = $this->getMockBuilder('\Aw\Nusoap\NusoapClient')
            ->disableOriginalConstructor()
            ->setMethods(['call'])
            ->getMock();
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

        $newIps7 = new NewIPS7();
        $newIps7->getVerifyData();
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

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->getVerifyData();
    }

    /**
     * 測試支付時缺少商家額外的參數設定Account
     */
    public function testPayWithoutMerchantExtraAccount()

    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'merchant_extra' => [],
            'username' => 'testuser',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://www.ips.cn/return.php',
            'merchant_extra' => ['Account' => '123456'],
            'username' => 'testuser',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $requestData = $newIps7->getVerifyData();

        // 先確認存在平台要驗證的唯一參數
        $this->assertArrayHasKey('pGateWayReq', $requestData);

        // 解析xml以確認相關參數正常
        $encoders = [new XmlEncoder()];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $parseData = $serializer->decode($requestData['pGateWayReq'], 'xml');

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $this->assertEquals($options['number'], $parseData['GateWayReq']['head']['MerCode']);
        $this->assertEquals($options['orderId'], $parseData['GateWayReq']['body']['MerBillNo']);
        $this->assertEquals('20150322', $parseData['GateWayReq']['body']['Date']);
        $this->assertEquals($options['amount'], $parseData['GateWayReq']['body']['Amount']);
        $this->assertEquals($notifyUrl, $parseData['GateWayReq']['body']['Merchanturl']);
        $this->assertEquals($options['merchant_extra']['Account'], $parseData['GateWayReq']['head']['Account']);
        $this->assertEquals($options['username'], $parseData['GateWayReq']['body']['GoodsName']);
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

        $newIps7 = new NewIPS7();
        $newIps7->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少paymentResult
     */
    public function testReturnWithoutPaymentResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少<head>
     */
    public function testReturnWithoutHead()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<Ips><GateWayRsp>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少<body>
     */
    public function testReturnWithoutBody()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '</head>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少RspCode
     */
    public function testReturnWithoutRspCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '</head>' .
            '<body>' .
            '<CurrencyType></CurrencyType>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->verifyOrderPayment([]);
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

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '<RspCode></RspCode>' .
            '</head>' .
            '<body>' .
            '<CurrencyType></CurrencyType>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->verifyOrderPayment([]);
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

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '<RspCode>00000</RspCode>' .
            '</head>' .
            '<body>' .
            '<CurrencyType></CurrencyType>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少Signature
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '</head>' .
            '<body>' .
            '<MerBillNo></MerBillNo>' .
            '<CurrencyType></CurrencyType>' .
            '<Amount></Amount>' .
            '<Date></Date>' .
            '<Status></Status>' .
            '<Msg></Msg>' .
            '<Attach></Attach>' .
            '<IpsBillNo></IpsBillNo>' .
            '<IpsTradeNo></IpsTradeNo>' .
            '<BankBillNo></BankBillNo>' .
            '<RetEncodeType></RetEncodeType>' .
            '<ResultType></ResultType>' .
            '<IpsBillTime></IpsBillTime>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $entry = ['merchant_number' => '201503220000000321'];

        $newIps7 = new NewIPS7();
        $newIps7->setOptions($options);
        $newIps7->setPrivateKey('test');
        $newIps7->verifyOrderPayment($entry);
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

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<Signature></Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo></MerBillNo>' .
            '<CurrencyType></CurrencyType>' .
            '<Amount></Amount>' .
            '<Date></Date>' .
            '<Status></Status>' .
            '<Msg></Msg>' .
            '<Attach></Attach>' .
            '<IpsBillNo></IpsBillNo>' .
            '<IpsTradeNo></IpsTradeNo>' .
            '<BankBillNo></BankBillNo>' .
            '<RetEncodeType></RetEncodeType>' .
            '<ResultType></ResultType>' .
            '<IpsBillTime></IpsBillTime>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $entry = ['merchant_number' => '201503220000000321'];

        $newIps7 = new NewIPS7();
        $newIps7->setOptions($options);
        $newIps7->setPrivateKey('test');
        $newIps7->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付失敗(回傳Status非Y)
     */
    public function testReturnPaymentFailureWithStatusError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<Signature>fb0c9a0ae9e28cf76e99e9e795871824</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<CurrencyType>156</CurrencyType>' .
            '<Amount>100</Amount>' .
            '<Date>20150410</Date>' .
            '<Status>N</Status>' .
            '<Msg><![CDATA[支付成功！]]></Msg>' .
            '<Attach><![CDATA[]]></Attach>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<IpsTradeNo>2015041012043087416</IpsTradeNo>' .
            '<RetEncodeType>17</RetEncodeType>' .
            '<BankBillNo>72006568914</BankBillNo>' .
            '<ResultType>0</ResultType>' .
            '<IpsBillTime>20150410122832</IpsBillTime>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $entry = ['merchant_number' => '201503220000000321'];

        $newIps7 = new NewIPS7();
        $newIps7->setOptions($options);
        $newIps7->setPrivateKey('test');
        $newIps7->verifyOrderPayment($entry);
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

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<Signature>1b353db696420309e2baf62beee84d87</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<CurrencyType>156</CurrencyType>' .
            '<Amount>100</Amount>' .
            '<Date>20150410</Date>' .
            '<Status>Y</Status>' .
            '<Msg><![CDATA[支付成功！]]></Msg>' .
            '<Attach><![CDATA[]]></Attach>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<IpsTradeNo>2015041012043087416</IpsTradeNo>' .
            '<RetEncodeType>17</RetEncodeType>' .
            '<BankBillNo>72006568914</BankBillNo>' .
            '<ResultType>0</ResultType>' .
            '<IpsBillTime>20150410122832</IpsBillTime>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $entry = [
            'merchant_number' => '201503220000000321',
            'id' => '201504100000214367',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setOptions($options);
        $newIps7->setPrivateKey('test');
        $newIps7->verifyOrderPayment($entry);
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

        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<Signature>1b353db696420309e2baf62beee84d87</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<CurrencyType>156</CurrencyType>' .
            '<Amount>100</Amount>' .
            '<Date>20150410</Date>' .
            '<Status>Y</Status>' .
            '<Msg><![CDATA[支付成功！]]></Msg>' .
            '<Attach><![CDATA[]]></Attach>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<IpsTradeNo>2015041012043087416</IpsTradeNo>' .
            '<RetEncodeType>17</RetEncodeType>' .
            '<BankBillNo>72006568914</BankBillNo>' .
            '<ResultType>0</ResultType>' .
            '<IpsBillTime>20150410122832</IpsBillTime>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $entry = [
            'merchant_number' => '201503220000000321',
            'id' => '201504100000214366',
            'amount' => '20.00',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setOptions($options);
        $newIps7->setPrivateKey('test');
        $newIps7->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $xml = '<Ips><GateWayRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<Signature>864e9afe4667fbd6d191e8e28edd2e9e</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<CurrencyType>156</CurrencyType>' .
            '<Amount>100.00</Amount>' .
            '<Date>20150410</Date>' .
            '<Status>Y</Status>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<IpsTradeNo>2015041012043087416</IpsTradeNo>' .
            '<RetEncodeType>17</RetEncodeType>' .
            '</body>' .
            '</GateWayRsp></Ips>';
        $options = ['paymentResult' => $xml];

        $entry = [
            'merchant_number' => '201503220000000321',
            'id' => '201504100000214366',
            'amount' => '100.00',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setOptions($options);
        $newIps7->setPrivateKey('test');
        $newIps7->verifyOrderPayment($entry);
    }

    /**
     * 測試訂單查詢加密缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newIps7 = new NewIPS7();
        $newIps7->paymentTracking();
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

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->paymentTracking();
    }

    /**
     * 測試訂單查詢加密缺少商家額外的參數設定Account
     */
    public function testTrackingWithoutMerchantExtraAccount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => []
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付返回沒有head的情況
     */
    public function testTrackingReturnWithoutHead()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $xml = '<Ips><OrderQueryRsp></OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付返回沒有body的情況
     */
    public function testTrackingReturnWithoutBody()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $xml = '<Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '</head>' .
            '</OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有RspCode的情況
     */
    public function testTrackingReturnWithoutRspCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $xml = '<Ips><OrderQueryRsp>' .
            '<head>' .
            '</head>' .
            '<body>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
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

        $xml = '<Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode></RspCode>' .
            '</head>' .
            '<body>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
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

        $xml = '<Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '</head>' .
            '<body>' .
            '<IpsBillNo></IpsBillNo>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
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

        $xml = '<Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature></Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo></MerBillNo>' .
            '<IpsBillNo></IpsBillNo>' .
            '<TradeType></TradeType>' .
            '<Currency></Currency>' .
            '<Amount></Amount>' .
            '<MerBillDate></MerBillDate>' .
            '<IpsBillTime></IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status></Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature>9bfbe61e9e3fdaf9bc42c1cff44ccee9</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<TradeType>1001</TradeType>' .
            '<Currency>156</Currency>' .
            '<Amount>100.00</Amount>' .
            '<MerBillDate>20150410</MerBillDate>' .
            '<IpsBillTime>20150410122730</IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status>N</Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $xml = '<Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature>d66947a43b4de1cd43f69abb4b0d4468</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<TradeType>1001</TradeType>' .
            '<Currency>156</Currency>' .
            '<Amount>100.00</Amount>' .
            '<MerBillDate>20150410</MerBillDate>' .
            '<IpsBillTime>20150410122730</IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status>Y</Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201504100000214366',
            'orderCreateDate' => '20150316',
            'amount' => '123.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $xml = '<Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature>d66947a43b4de1cd43f69abb4b0d4468</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<TradeType>1001</TradeType>' .
            '<Currency>156</Currency>' .
            '<Amount>100.00</Amount>' .
            '<MerBillDate>20150410</MerBillDate>' .
            '<IpsBillTime>20150410122730</IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status>Y</Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $options = [
            'number' => '20130809',
            'orderId' => '201504100000214366',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setContainer($this->container);
        $newIps7->setClient($this->client);
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newIps7 = new NewIPS7();
        $newIps7->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入orderCreateDate
     */
    public function testGetPaymentTrackingDataWithoutOrderCreateDate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['orderId' => '201504100000214366'];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入商家的Account附加設定值
     */
    public function testGetPaymentTrackingDataButNoAccountSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'orderId' => '201504100000214366',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => []
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'orderId' => '201504100000214366',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $newIps7->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201504100000214366',
            'orderCreateDate' => '20150316',
            'amount' => '100.00',
            'merchant_extra' => ['Account' => '123456'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.ips7.com',
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($options);
        $trackingData = $newIps7->getPaymentTrackingData();


        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/psfp-entry/services/order?wsdl', $trackingData['path']);
        $this->assertEquals('getOrderByMerBillNo', $trackingData['function']);
        $this->assertEquals('payment.http.www.ips7.com', $trackingData['headers']['Host']);

        $orderQuery= '<?xml version="1.0" encoding="utf-8"?><Ips><OrderQueryReq><head><Version>v1.0.0</Version>' .
            '<MerCode>20130809</MerCode><MerName></MerName><Account>123456</Account><ReqDate>20150316000000' .
            '</ReqDate><Signature>6edb5982c23dffdfb4de9c130ac07705</Signature></head><body><MerBillNo>20150' .
            '4100000214366</MerBillNo><Date>20150316</Date><Amount>100.00</Amount></body></OrderQueryReq></Ips>';
        $this->assertEquals($orderQuery, $trackingData['arguments']['orderQuery']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newIps7 = new NewIPS7();
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回不是xml格式
     */
    public function testPaymentTrackingVerifyButReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>不是XML</getOrderByMerBillNoResult>' .
            '</ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = ['content' => $content];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回沒有head
     */
    public function testPaymentTrackingVerifyWithoutHead()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp></OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = ['content' => $content];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回沒有body
     */
    public function testPaymentTrackingVerifyWithoutBody()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '</head>' .
            '</OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = ['content' => $content];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回沒有RspCode
     */
    public function testPaymentTrackingVerifyWithoutRspCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '</head>' .
            '<body>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = ['content' => $content];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單查詢失敗
     */
    public function testPaymentTrackingVerifyButPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>999999</RspCode>' .
            '</head>' .
            '<body/>' .
            '</OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = ['content' => $content];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '</head>' .
            '<body>' .
            '<IpsBillNo>BO2016012703222572405</IpsBillNo>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = ['content' => $content];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但簽名驗證錯誤
     */
    public function testPaymentTrackingVerifyButSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature></Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo></MerBillNo>' .
            '<IpsBillNo></IpsBillNo>' .
            '<TradeType></TradeType>' .
            '<Currency></Currency>' .
            '<Amount></Amount>' .
            '<MerBillDate></MerBillDate>' .
            '<IpsBillTime></IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status></Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = [
            'content' => $content,
            'number' => '20130809'
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但支付失敗
     */
    public function testPaymentTrackingVerifyButPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature>9bfbe61e9e3fdaf9bc42c1cff44ccee9</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<TradeType>1001</TradeType>' .
            '<Currency>156</Currency>' .
            '<Amount>100.00</Amount>' .
            '<MerBillDate>20150410</MerBillDate>' .
            '<IpsBillTime>20150410122730</IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status>N</Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = [
            'content' => $content,
            'number' => '20130809'
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但金額不正確
     */
    public function testPaymentTrackingVerifyButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature>d66947a43b4de1cd43f69abb4b0d4468</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<TradeType>1001</TradeType>' .
            '<Currency>156</Currency>' .
            '<Amount>100.00</Amount>' .
            '<MerBillDate>20150410</MerBillDate>' .
            '<IpsBillTime>20150410122730</IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status>Y</Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = [
            'content' => $content,
            'number' => '20130809',
            'amount' => '123.00'
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Ips><OrderQueryRsp>' .
            '<head>' .
            '<RspCode>000000</RspCode>' .
            '<RspMsg></RspMsg>' .
            '<ReqDate></ReqDate>' .
            '<RspDate></RspDate>' .
            '<Signature>d66947a43b4de1cd43f69abb4b0d4468</Signature>' .
            '</head>' .
            '<body>' .
            '<MerBillNo>201504100000214366</MerBillNo>' .
            '<IpsBillNo>BO20150410122730112862</IpsBillNo>' .
            '<TradeType>1001</TradeType>' .
            '<Currency>156</Currency>' .
            '<Amount>100.00</Amount>' .
            '<MerBillDate>20150410</MerBillDate>' .
            '<IpsBillTime>20150410122730</IpsBillTime>' .
            '<Attach></Attach>' .
            '<Status>Y</Status>' .
            '</body>' .
            '</OrderQueryRsp></Ips>';

        $content = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>' . htmlspecialchars($xml) .
            '</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $sourceData = [
            'content' => $content,
            'number' => '20130809',
            'amount' => '100.00'
        ];

        $newIps7 = new NewIPS7();
        $newIps7->setPrivateKey('test');
        $newIps7->setOptions($sourceData);
        $newIps7->paymentTrackingVerify();
    }

    /**
     * 測試轉換訂單查詢支付平台返回的編碼
     */
    public function testProcessTrackingResponseEncoding()
    {
        // 將支付平台的返回做編碼模擬 kue 返回
        $body = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>' .
            '<ns1:getOrderByMerBillNoResponse xmlns:ns1="http://payat.ips.com.cn/WebService/OrderQuery">' .
            '<getOrderByMerBillNoResult>&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;' .
            '&lt;Ips&gt;&lt;OrderQueryRsp&gt;&lt;head&gt;&lt;RspCode&gt;999999&lt;/RspCode&gt;&lt;RspMsg' .
            '&gt;&lt;![CDATA[该订单记录不存在。]]&gt;&lt;/RspMsg&gt;&lt;ReqDate&gt;20160128095114' .
            '&lt;/ReqDate&gt;&lt;RspDate&gt;20160128095738&lt;/RspDate&gt;&lt;Signature&gt;330026ae27060' .
            '7064b7bcb1a3e1951df&lt;/Signature&gt;&lt;/head&gt;&lt;body/&gt;&lt;/OrderQueryRsp&gt;&lt;/I' .
            'ps&gt;</getOrderByMerBillNoResult></ns1:getOrderByMerBillNoResponse></soap:Body></soap:Envelope>';
        $encodedBody = base64_encode($body);

        $encodedResponse = [
            'header' => null,
            'body' => $encodedBody
        ];

        $newIps7 = new NewIPS7();
        $trackingResponse = $newIps7->processTrackingResponseEncoding($encodedResponse);

        $this->assertEquals($encodedResponse['header'], $trackingResponse['header']);
        $this->assertEquals($body, $trackingResponse['body']);
    }
}
