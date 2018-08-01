<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewMoBaoPay;
use Buzz\Message\Response;

class NewMoBaoPayTest extends DurianTestCase
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->getVerifyData();
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->getVerifyData();
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
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '99',
            'username' => 'php1test',
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getVerifyData();
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
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://www.mobao.cn/return.php',
            'paymentVendorId' => '1',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => [],
            'username' => 'php1test',
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->getVerifyData();
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
            'notify_url' => 'http://www.mobao.cn/return.php',
            'paymentVendorId' => '1',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => ['platformID' => 'mrof'],
            'username' => 'php1test',
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $requestData = $moBaoPay->getVerifyData();

        $this->assertEquals('acctest', $requestData['merchNo']);
        $this->assertEquals('201503220000000123', $requestData['orderNo']);
        $this->assertEquals('20150322', $requestData['tradeDate']);
        $this->assertEquals('100', $requestData['amt']);
        $this->assertEquals('http://www.mobao.cn/return.php', $requestData['merchUrl']);
        $this->assertEquals('35660_6', $requestData['merchParam']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->verifyOrderPayment([]);
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->verifyOrderPayment([]);
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
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment([]);
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
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'signMsg' => 'acctest',
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment([]);
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment([]);
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
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'signMsg' => '0683b980358aebc8e0b8864b285fea2c',
        ];

        $entry = ['id' => '201503220000000321'];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment($entry);
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
            'notifyTime' => '20150316151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201503220000000123',
            'tradeDate' => '20150316',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'signMsg' => '0683b980358aebc8e0b8864b285fea2c',
        ];

        $entry = [
            'id' => '201503220000000123',
            'amount' => '10.00',
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
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
            'orderStatus' => '1',
            'signMsg' => '0683b980358aebc8e0b8864b285fea2c',
        ];

        $entry = [
            'id' => '201503220000000123',
            'amount' => '100.00',
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $moBaoPay->getMsg());
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->paymentTracking();
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => []
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果驗證沒有respCode的情況
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
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

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單不存在
     */
    public function testTrackingReturnPaymentTrackingOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
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

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>22</respCode>' .
            '<respDesc>查询订单信息不存在[订单信息不存在]</respDesc>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;content-type:charset=utf-8');

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>22</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>00000000</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態為未支付
     */
    public function testTrackingReturnOrderPaymentUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>无记录</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>0</Status>' .
            '</respData>' .
            '<signMsg>43ac044ada4f3d36f1690ea5d16b7b32</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
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
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>2</Status>' .
            '</respData>' .
            '<signMsg>8bad74a31bac8efe1e57a449930a6aec</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100.0000',
            'merchant_extra' => ['platformID' => 'mrof'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'merchantId' => '1',
            'domain' => '6',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<moboAccount>' .
            '<respData>' .
            '<respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc>' .
            '<orderDate>20150323</orderDate>' .
            '<accDate>20150323</accDate>' .
            '<orderNo>744214</orderNo>' .
            '<accNo>744214</accNo>' .
            '<Status>1</Status>' .
            '</respData>' .
            '<signMsg>4e6609fe6cdc2ba06ac88d7fff899f31</signMsg>' .
            '</moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $moBaoPay = new NewMoBaoPay();
        $moBaoPay->setContainer($this->container);
        $moBaoPay->setClient($this->client);
        $moBaoPay->setResponse($response);
        $moBaoPay->setPrivateKey('test');
        $moBaoPay->setOptions($options);
        $moBaoPay->paymentTracking();
    }
}
