<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Shengpay;
use Buzz\Message\Response;

class ShengpayTest extends DurianTestCase
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
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shengPay = new Shengpay();
        $shengPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $sourceData = ['amount' => ''];

        $shengPay->setOptions($sourceData);
        $shengPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $sourceData = [
            'amount' => '1',
            'orderId' => '2007070621530',
            'number' => '234838',
            'notify_url' => 'http://netpay.sdo.com/paygate/ibankpay.aspx',
            'orderCreateDate' => '2014-04-01 00:00:00',
            'username' => '777999',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $shengPay->setOptions($sourceData);
        $shengPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'amount' => '1',
            'orderId' => '2007070621530',
            'number' => '234838',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderCreateDate' => '2014-04-01 00:00:00',
            'username' => '777999',
            'paymentVendorId' => '3',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $encodeData = $shengPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['MerchantNo']);
        $this->assertEquals($sourceData['orderId'], $encodeData['OrderNo']);
        $this->assertEquals($sourceData['amount'], $encodeData['Amount']);
        $this->assertEquals($sourceData['username'], $encodeData['ProductDesc']);
        $this->assertEquals($notifyUrl, $encodeData['PostBackUrl']);
        $this->assertEquals($notifyUrl, $encodeData['NotifyUrl']);
        $this->assertEquals('20140401000000', $encodeData['OrderTime']);
        $this->assertEquals('ABOC', $encodeData['BankCode']);
        $this->assertEquals('471fd3cb58a169a5a3a64b0109a06280', $encodeData['MAC']);
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

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('');

        $sourceData = [];

        $shengPay->setOptions($sourceData);
        $shengPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $shengPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數(測試少回傳MAC:加密簽名)
     */
    public function testVerifyWithoutMac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $sourceData = [
            'Amount' => '1.00',
            'PayAmount' => '',
            'OrderNo' => '2007070621530',
            'serialno' => '',
            'Status' => '01',
            'MerchantNo' => '234838',
            'PayChannel' => '04',
            'Discount' => '',
            'SignType' => '2',
            'PayTime' => '',
            'CurrencyType' => 'RMB',
            'ProductNo' => '',
            'ProductDesc' => '',
            'Remark1' => '',
            'Remark2' => '',
            'ExInfo' => ''
        ];

        $shengPay->setOptions($sourceData);
        $shengPay->verifyOrderPayment([]);
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

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $sourceData = [
            'Amount' => '1.00',
            'PayAmount' => '',
            'OrderNo' => '2007070621530',
            'serialno' => '',
            'Status' => '01',
            'MerchantNo' => '234838',
            'PayChannel' => '04',
            'Discount' => '',
            'SignType' => '2',
            'PayTime' => '',
            'CurrencyType' => 'RMB',
            'ProductNo' => '',
            'ProductDesc' => '',
            'Remark1' => '',
            'Remark2' => '',
            'ExInfo' => '',
            'MAC' => '4de383a22f9595f966c2f9e78b0f1bc'
        ];

        $shengPay->setOptions($sourceData);
        $shengPay->verifyOrderPayment([]);
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

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $sourceData = [
            'Amount' => '1.00',
            'PayAmount' => '',
            'OrderNo' => '2007070621530',
            'serialno' => '',
            'Status' => '02',
            'MerchantNo' => '234838',
            'PayChannel' => '04',
            'Discount' => '',
            'SignType' => '2',
            'PayTime' => '',
            'CurrencyType' => 'RMB',
            'ProductNo' => '',
            'ProductDesc' => '',
            'Remark1' => '',
            'Remark2' => '',
            'ExInfo' => '',
            'MAC' => '59fe07c903bf45eab36be1edc601db30'
        ];

        $shengPay->setOptions($sourceData);
        $shengPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $sourceData = [
            'Amount' => '1.00',
            'PayAmount' => '',
            'OrderNo' => '2007070621530',
            'serialno' => '',
            'Status' => '01',
            'MerchantNo' => '234838',
            'PayChannel' => '04',
            'Discount' => '',
            'SignType' => '2',
            'PayTime' => '',
            'CurrencyType' => 'RMB',
            'ProductNo' => '',
            'ProductDesc' => '',
            'Remark1' => '',
            'Remark2' => '',
            'ExInfo' => '',
            'MAC' => 'f9195010681edb9484a86586f192cd74'
        ];

        $entry = ['id' => '20140113143143'];

        $shengPay->setOptions($sourceData);
        $shengPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $sourceData = [
            'Amount' => '1.00',
            'PayAmount' => '',
            'OrderNo' => '2007070621530',
            'serialno' => '',
            'Status' => '01',
            'MerchantNo' => '234838',
            'PayChannel' => '04',
            'Discount' => '',
            'SignType' => '2',
            'PayTime' => '',
            'CurrencyType' => 'RMB',
            'ProductNo' => '',
            'ProductDesc' => '',
            'Remark1' => '',
            'Remark2' => '',
            'ExInfo' => '',
            'MAC' => 'f9195010681edb9484a86586f192cd74'
        ];

        $entry = [
            'id' => '2007070621530',
            'amount' => '10.00'
        ];

        $shengPay->setOptions($sourceData);
        $shengPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');

        $sourceData = [
            'Amount' => '1.00',
            'PayAmount' => '1.00',
            'OrderNo' => '2007070621530',
            'serialno' => '',
            'Status' => '01',
            'MerchantNo' => '234838',
            'PayChannel' => '04',
            'Discount' => '',
            'SignType' => '2',
            'PayTime' => '',
            'CurrencyType' => 'RMB',
            'ProductNo' => '',
            'ProductDesc' => '',
            'Remark1' => '',
            'Remark2' => '',
            'ExInfo' => '',
            'MAC' => 'ee792678457c8744d20d8b15a94cb215'
        ];

        $entry = [
            'id' => '2007070621530',
            'amount' => '1.00'
        ];

        $shengPay->setOptions($sourceData);
        $shengPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $shengPay->getMsg());
    }

    /**
     * 測試訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shengPay = new Shengpay();
        $shengPay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingWithoutOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('1234');
        $shengPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入verifyUrl
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'orderId' => '2007070621530',
            'number' => '234838',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數Code
     */
    public function testPaymentTrackingResultWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $response = new Response();
        $response->setContent('null');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2007070621530',
            'number' => '234838',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shengpay.com'
        ];

        $shengPay = new Shengpay();
        $shengPay->setContainer($this->container);
        $shengPay->setClient($this->client);
        $shengPay->setResponse($response);
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數Status
     */
    public function testPaymentTrackingResultWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = ['OrderQueryResult' => ['Code' => '1']];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2007070621530',
            'number' => '234838',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shengpay.com'
        ];

        $shengPay = new Shengpay();
        $shengPay->setContainer($this->container);
        $shengPay->setClient($this->client);
        $shengPay->setResponse($response);
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTracking();
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

        $params = [
            'OrderQueryResult' => [
                'Code' => '1',
                'Order' => ['Status' => '02']
            ]
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2007070621530',
            'number' => '234838',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shengpay.com'
        ];

        $shengPay = new Shengpay();
        $shengPay->setContainer($this->container);
        $shengPay->setClient($this->client);
        $shengPay->setResponse($response);
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTracking();
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

        $params = [
            'OrderQueryResult' => [
                'Code' => '0',
                'Order' => [
                    'Status' => '01',
                    'OrderNo' => '2007070621530',
                    'PayAmount' => 0.01
                ]
            ]
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2007070621530',
            'number' => '234838',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shengpay.com',
            'amount' => '1.234'
        ];

        $shengPay = new Shengpay();
        $shengPay->setContainer($this->container);
        $shengPay->setClient($this->client);
        $shengPay->setResponse($response);
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $params = [
            'OrderQueryResult' => [
                'Code' => '0',
                'Order' => [
                    'Status' => '01',
                    'OrderNo' => '2007070621530',
                    'PayAmount' => 0.01
                ]
            ]
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2007070621530',
            'number' => '234838',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shengpay.com',
            'amount' => '0.01'
        ];

        $shengPay = new Shengpay();
        $shengPay->setContainer($this->container);
        $shengPay->setClient($this->client);
        $shengPay->setResponse($response);
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTracking();
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

        $shengPay = new Shengpay();
        $shengPay->getPaymentTrackingData();
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

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->getPaymentTrackingData();
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
            'orderId' => '2007070621530',
            'number' => '234838',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($options);
        $shengPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'orderId' => '2007070621530',
            'number' => '234838',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.settle.netpay.sdo.com',
        ];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($options);
        $trackingData = $shengPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/orders.asmx', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.settle.netpay.sdo.com', $trackingData['headers']['Host']);

        $this->assertEquals('2007070621530', $trackingData['form']['OrderNo']);
        $this->assertEquals('234838', $trackingData['form']['MerchantNo']);
        $this->assertEquals('2', $trackingData['form']['SignType']);
        $this->assertEquals('fcaaa972916295402594ad9d8737a9c0', $trackingData['form']['Mac']);
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

        $shengPay = new Shengpay();
        $shengPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $sourceData = ['content' => ''];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數(測試少回傳Code)
     */
    public function testPaymentTrackingVerifyWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'OrderQueryResult' => [
                'Message' => '',
                'Order' => [
                    'OrderNo' => '2007070621530',
                    'SerialNo' => '',
                    'OrderAmount' => '1.00',
                    'PayAmount' => '1.00',
                    'Status' => '02',
                    'PayTime'
                ],
                'Ex1' => '',
                'Ex2' => ''
            ]
        ];

        $sourceData = ['content' => http_build_query($content)];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數(測試少回傳Status)
     */
    public function testPaymentTrackingVerifyWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'OrderQueryResult' => [
                'Code' => '1',
                'Message' => '',
                'Order' => [
                    'OrderNo' => '2007070621530',
                    'SerialNo' => '',
                    'OrderAmount' => '1.00',
                    'PayAmount' => '1.00',
                    'PayTime'
                ],
                'Ex1' => '',
                'Ex2' => ''
            ]
        ];

        $sourceData = ['content' => http_build_query($content)];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = [
            'OrderQueryResult' => [
                'Code' => '1',
                'Message' => '',
                'Order' => [
                    'OrderNo' => '2007070621530',
                    'SerialNo' => '',
                    'OrderAmount' => '1.00',
                    'PayAmount' => '1.00',
                    'Status' => '02',
                    'PayTime'
                ],
                'Ex1' => '',
                'Ex2' => ''
            ]
        ];

        $sourceData = ['content' => http_build_query($content)];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTrackingVerify();
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

        $content = [
            'OrderQueryResult' => [
                'Code' => '0',
                'Message' => '',
                'Order' => [
                    'OrderNo' => '2007070621530',
                    'SerialNo' => '',
                    'OrderAmount' => '1.00',
                    'PayAmount' => '1.00',
                    'Status' => '01',
                    'PayTime'
                ],
                'Ex1' => '',
                'Ex2' => ''
            ]
        ];

        $sourceData = [
            'content' => http_build_query($content),
            'amount' => 500
        ];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = [
            'OrderQueryResult' => [
                'Code' => '0',
                'Message' => '',
                'Order' => [
                    'OrderNo' => '2007070621530',
                    'SerialNo' => '',
                    'OrderAmount' => '1.00',
                    'PayAmount' => '1.00',
                    'Status' => '01',
                    'PayTime'
                ],
                'Ex1' => '',
                'Ex2' => ''
            ]
        ];

        $sourceData = [
            'content' => http_build_query($content),
            'amount' => 1
        ];

        $shengPay = new Shengpay();
        $shengPay->setPrivateKey('3gd4cd8f5640fdb5a06d0d8321');
        $shengPay->setOptions($sourceData);
        $shengPay->paymentTrackingVerify();
    }
}
