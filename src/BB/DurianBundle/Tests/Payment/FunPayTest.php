<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\FunPay;
use Buzz\Message\Response;

class FunPayTest extends DurianTestCase
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

        $funPay = new FunPay();
        $funPay->getVerifyData();
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

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入username的情況
     */
    public function testPayNoUserName()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'orderId' => '201608110000003592',
            'orderCreateDate' => '2016-08-11 13:41:49',
            'amount' => '30',
            'paymentVendorId' => '1',
            'notify_url' => 'https://www.funpay.com/website/pay.htm',
            'number' => '10000018704',
            'username' => '',
            'domain' => '6',
            'merchantId' => '3290',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPayWithNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'orderId' => '201608110000003592',
            'orderCreateDate' => '2016-08-11 13:41:49',
            'amount' => '0.01',
            'paymentVendorId' => '999',
            'notify_url' => 'https://www.funpay.com/website/pay.htm',
            'number' => '10000018704',
            'username' => 'php1test',
            'domain' => '6',
            'merchantId' => '3290',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'orderId' => '201608110000003592',
            'orderCreateDate' => '2016-08-11 13:41:49',
            'amount' => '0.01',
            'paymentVendorId' => '1', //icbc(工商銀行，要回傳的銀行代碼)
            'notify_url' => 'https://www.funpay.com/website/pay.htm',
            'number' => '10000018704',
            'username' => 'php1test',
            'domain' => '6',
            'merchantId' => '3290',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);

        $requestData = $funPay->getVerifyData();
        $url = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('icbc', $requestData['orgCode']);
        $this->assertEquals('BANK_B2C', $requestData['payType']);
        $this->assertEquals('201608110000003592,1,php1test,goodsName,1', $requestData['orderDetails']);
        $this->assertEquals('1', $requestData['totalAmount']);
        $this->assertEquals($url, $requestData['returnUrl']);
        $this->assertEquals($url, $requestData['noticeUrl']);
        $this->assertEquals('f7ec7fe288c885fbfb6d5c0e7ba6d4a6', $requestData['signMsg']);
    }

    /**
     * 測試支付，帶入微信二維
     */
    public function testPayWithWx()
    {
        $sourceData = [
            'orderId' => '201608110000003592',
            'orderCreateDate' => '2016-08-11 13:41:49',
            'amount' => '0.01',
            'paymentVendorId' => '1090', // wx(維信二維)
            'notify_url' => 'https://www.funpay.com/website/pay.htm',
            'number' => '10000018704',
            'username' => 'php1test',
            'domain' => '6',
            'merchantId' => '3290',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $requestData = $funPay->getVerifyData();

        $this->assertEquals('wx', $requestData['orgCode']);
        $this->assertEquals('WX', $requestData['payType']);
        $this->assertEquals('201608110000003592,1,php1test,goodsName,1', $requestData['orderDetails']);
        $this->assertEquals('1', $requestData['totalAmount']);
        $this->assertEquals('9553f5ea2fe97dda595b115a054ad010', $requestData['signMsg']);
    }

    /**
     * 測試支付，帶入支付寶二維
     */
    public function testPayWithZfb()
    {
        $sourceData = [
            'orderId' => '201608110000003592',
            'orderCreateDate' => '2016-08-11 13:41:49',
            'amount' => '0.01',
            'paymentVendorId' => '1092', // zfb(支付寶二維)
            'notify_url' => 'https://www.funpay.com/website/pay.htm',
            'number' => '10000018704',
            'username' => 'php1test',
            'domain' => '6',
            'merchantId' => '3290',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $requestData = $funPay->getVerifyData();

        $this->assertEquals('zfb', $requestData['orgCode']);
        $this->assertEquals('ZFB', $requestData['payType']);
        $this->assertEquals('201608110000003592,1,php1test,goodsName,1', $requestData['orderDetails']);
        $this->assertEquals('1', $requestData['totalAmount']);
        $this->assertEquals('17681019ead96f13454fd814b8fb9100', $requestData['signMsg']);
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

        $funPay = new FunPay();
        $funPay->verifyOrderPayment([]);
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

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->verifyOrderPayment([]);
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
            'orderID' => '201608110000003592',
            'resultCode' => '',
            'stateCode' => '2',
            'orderAmount' => '3000',
            'payAmount' => '3000',
            'acquiringTime' => '20160811134149',
            'completeTime' => '20160811134150',
            'orderNo' => '1051608111341063057',
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->verifyOrderPayment([]);
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
            'orderID' => '201608110000003592',
            'resultCode' => '',
            'stateCode' => '2',
            'orderAmount' => '3000',
            'payAmount' => '3000',
            'acquiringTime' => '20160811134149',
            'completeTime' => '20160811134150',
            'orderNo' => '1051608111341063057',
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => 'ce591e13e5c5ad62e6eab5f7976225d0',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->verifyOrderPayment([]);
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
            'orderID' => '201608110000003592',
            'resultCode' => '',
            'stateCode' => '3',
            'orderAmount' => '3000',
            'payAmount' => '3000',
            'acquiringTime' => '20160811134149',
            'completeTime' => '20160811134150',
            'orderNo' => '1051608111341063057',
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => 'd015afb8e27f0080be70e316918babc2',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->verifyOrderPayment([]);
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
            'orderID' => '201608110000003592',
            'resultCode' => '',
            'stateCode' => '2',
            'orderAmount' => '3000',
            'payAmount' => '3000',
            'acquiringTime' => '20160811134149',
            'completeTime' => '20160811134150',
            'orderNo' => '1051608111341063057',
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '1afb25f481967a03d18bf5382b81eeb9',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');

        $entry = ['id' => '147896325'];

        $funPay->setOptions($sourceData);
        $funPay->verifyOrderPayment($entry);
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
            'orderID' => '201608110000003592',
            'resultCode' => '',
            'stateCode' => '2',
            'orderAmount' => '3000',
            'payAmount' => '3000',
            'acquiringTime' => '20160811134149',
            'completeTime' => '20160811134150',
            'orderNo' => '1051608111341063057',
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '1afb25f481967a03d18bf5382b81eeb9',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');

        $entry = [
            'id' => '201608110000003592',
            'amount' => '5.0000',
        ];

        $funPay->setOptions($sourceData);
        $funPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'orderID' => '201608110000003592',
            'resultCode' => '',
            'stateCode' => '2',
            'orderAmount' => '3000',
            'payAmount' => '3000',
            'acquiringTime' => '20160811134149',
            'completeTime' => '20160811134150',
            'orderNo' => '1051608111341063057',
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '1afb25f481967a03d18bf5382b81eeb9',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');

        $entry = [
            'id' => '201608110000003592',
            'amount' => '30.0000',
        ];

        $funPay->setOptions($sourceData);
        $funPay->verifyOrderPayment($entry);

        $this->assertEquals('200', $funPay->getMsg());
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

        $funPay = new FunPay();
        $funPay->paymentTracking();
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

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入orderCreateDate
     */
    public function testTrackingWithoutOrderCreateDate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $sourceData = [
            'orderId' => '201608110000003592',
            'number' => '10000018704',
            'orderCreateDate' => '',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入verifyUrl
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '10000018704',
            'orderId' => '201608110000003592',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $funPay = new FunPay();
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果未指定返回參數
     */
    public function testTrackingWithNoTrackingReturnParameterSpecified()
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
            'number' => '10000018704',
            'orderId' => '201608110000003592',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.funpay.com',
        ];

        $funPay = new FunPay();
        $funPay->setContainer($this->container);
        $funPay->setClient($this->client);
        $funPay->setResponse($response);
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果交易失敗
     */
    public function testTrackingWithResultCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $params = [
            'serialID' => '2016081100000035920.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0009',
            'queryDetailsSize' => '0',
            'queryDetails' => '',
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10000018704',
            'orderId' => '201608110000003592',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.funpay.com',
        ];

        $funPay = new FunPay();
        $funPay->setContainer($this->container);
        $funPay->setClient($this->client);
        $funPay->setResponse($response);
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證參數數量錯誤
     */
    public function testTrackingWithSignatureVerificationParmsCountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $params = [
            'serialID' => '2016081100000035920.82685000',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => '201608110000003592,1,1,20160811134149,2',
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10000018704',
            'orderId' => '201608110000003592',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.funpay.com',
        ];

        $funPay = new FunPay();
        $funPay->setContainer($this->container);
        $funPay->setClient($this->client);
        $funPay->setResponse($response);
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數signMsg
     */
    public function testTrackingWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $queryDetails = '201608110000003592,1,1,20160811134149,20160811134303,1051608111341063057,2';
        $params = [
            'serialID' => '2016081100000035920.82685000',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10000018704',
            'orderId' => '201608110000003594',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.funpay.com',
        ];

        $funPay = new FunPay();
        $funPay->setContainer($this->container);
        $funPay->setClient($this->client);
        $funPay->setResponse($response);
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testTrackingSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $queryDetails = '201608110000003592,1,1,20160811134149,20160811134303,1051608111341063057,2';
        $params = [
            'serialID' => '2016081100000035920.82685000',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '77d7515488921c0f21c52e752bc51837',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10000018704',
            'orderId' => '201608110000003594',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.funpay.com',
        ];

        $funPay = new FunPay();
        $funPay->setContainer($this->container);
        $funPay->setClient($this->client);
        $funPay->setResponse($response);
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnOrderPaymentfailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $queryDetails = '201608110000003592,1,1,20160811134149,20160811134303,1051608111341063057,1';
        $params = [
            'serialID' => '2016081100000035920.82685000',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10000018704',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '22b3af4120ca716ee183caa7904efc2b',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10000018704',
            'orderId' => '201608110000003594',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.funpay.com',
        ];

        $funPay = new FunPay();
        $funPay->setContainer($this->container);
        $funPay->setClient($this->client);
        $funPay->setResponse($response);
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $queryDetails = '201608110000003592,1,1,20160811134149,20160811134303,1051608111341063057,2';
        $params = [
            'serialID' => '2016081100000035920.82685000',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails'=> $queryDetails,
            'partnerID' => '10000018704',
            'remark' => 'remark',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '9614e3b93ae4c279a0a0f653c8d9f935',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10000018704',
            'orderId' => '201608110000003594',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.funpay.com',
            'amount' => '50.00',
        ];

        $funPay = new FunPay();
        $funPay->setContainer($this->container);
        $funPay->setClient($this->client);
        $funPay->setResponse($response);
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $queryDetails = '201608110000003592,1,1,20160811134149,20160811134303,1051608111341063057,2';
        $params = [
            'serialID' => '2016081100000035920.82685000',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails'=> $queryDetails,
            'partnerID' => '10000018704',
            'remark' => 'remark',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '9614e3b93ae4c279a0a0f653c8d9f935',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10000018704',
            'orderId' => '201608110000003594',
            'orderCreateDate' => '20160811134149',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.funpay.com',
            'amount' => '0.01',
        ];

        $funPay = new FunPay();
        $funPay->setContainer($this->container);
        $funPay->setClient($this->client);
        $funPay->setResponse($response);
        $funPay->setPrivateKey('1234');
        $funPay->setOptions($sourceData);
        $funPay->paymentTracking();
    }
}
