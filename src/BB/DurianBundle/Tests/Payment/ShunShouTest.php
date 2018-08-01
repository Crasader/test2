<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShunShou;
use Buzz\Message\Response;

class ShunShouTest extends DurianTestCase
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

        $shunShou = new ShunShou();
        $shunShou->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $arrSourceData = ['number' => ''];

        $shunShou->setOptions($arrSourceData);
        $shunShou->getVerifyData();
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

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $sourceData = [
            'number' => '03358',
            'orderId' => '20120922-00001',
            'amount' => '10',
            'paymentVendorId' => '999',
            'notify_url' => 'http://www.xxx.com/response.do',
            'merchantId' => '42465',
            'domain' => '69',
        ];

        $shunShou->setOptions($sourceData);
        $shunShou->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '03358',
            'orderId' => '20120922-00001',
            'amount' => '10',
            'paymentVendorId' => '1',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'merchantId' => '42465',
            'domain' => '69',
        ];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $encodeData = $shunShou->getVerifyData();

        $this->assertEquals('03358', $encodeData['consumerNo']);
        $this->assertEquals('20120922-00001', $encodeData['merOrderNum']);
        $this->assertEquals('10.00', $encodeData['tranAmt']);
        $this->assertEquals('ICBC', $encodeData['bankCode']);
        $this->assertEquals('42465_69', $encodeData['merRemark1']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackUrl']);
        $this->assertEquals('0b6ccd43f2db891ed177ed81d77a2e0c', $encodeData['signValue']);
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

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('');

        $arrSourceData = [];

        $shunShou->setOptions($arrSourceData);
        $shunShou->verifyOrderPayment([]);
    }

    /**
     * 測試解密基本參數設定沒有callbackUrl參數
     */
    public function testSetDecodeSourceNoCallbackUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $sourceData = [
            'merOrderNum' => '20120922-00001',
            'tranAmt'     => '10.00',
            'respCode'    => 'no',
            'signValue'   => '9d2b85262d14172785adfcf3188002c'
        ];

        $shunShou->setOptions($sourceData);
        $shunShou->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $sourceData = [
            'merOrderNum' => '20120922-00001',
            'tranAmt'     => '10.00',
            'callbackUrl' => 'http%3A%2F%2Fwww.xxx.com%2Fresponse.do',
            'respCode'    => 'no',
            'signValue'   => '9d2b85262d14172785adfcf3188002c'
        ];

        $shunShou->setOptions($sourceData);
        $shunShou->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試signValue:加密簽名)
     */
    public function testVerifyWithoutSignValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $sourceData = [
            'consumerNo'  => '03358',
            'merOrderNum' => '20120922-00001',
            'tranAmt'     => '10.00',
            'callbackUrl' => 'http%3A%2F%2Fwww.xxx.com%2Fresponse.do',
            'respCode'    => 'no'
        ];

        $shunShou->setOptions($sourceData);
        $shunShou->verifyOrderPayment([]);
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

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $arrSourceData = [
            'consumerNo'  => '03358',
            'merOrderNum' => '20120922-00001',
            'tranAmt'     => '10.00',
            'callbackUrl' => 'http%3A%2F%2Fwww.xxx.com%2Fresponse.do',
            'respCode'    => 'no',
            'signValue'   => '9d2b85262d14172785adfcf3188002c'
        ];

        $shunShou->setOptions($arrSourceData);
        $shunShou->verifyOrderPayment([]);
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

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $arrSourceData = [
            'consumerNo'  => '03358',
            'merOrderNum' => '20120922-00001',
            'tranAmt'     => '10.00',
            'callbackUrl' => 'http%3A%2F%2Fwww.xxx.com%2Fresponse.do',
            'respCode'    => 'no',
            'signValue'   => '9d2b85262d14172785adfcf3188002c1'
        ];

        $shunShou->setOptions($arrSourceData);
        $shunShou->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $arrSourceData = [
            'consumerNo'  => '03358',
            'merOrderNum' => '2012092200001',
            'tranAmt'     => '10.00',
            'callbackUrl' => 'http%3A%2F%2Fwww.xxx.com%2Fresponse.do',
            'respCode'    => 'OK',
            'signValue'   => 'fe06c4c11a62ac1abce2c137e643bfca',
        ];

        $entry = ['id' => '20140113143143'];

        $shunShou->setOptions($arrSourceData);
        $shunShou->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $arrSourceData = [
            'consumerNo'  => '03358',
            'merOrderNum' => '2012092200001',
            'tranAmt'     => '10.00',
            'callbackUrl' => 'http%3A%2F%2Fwww.xxx.com%2Fresponse.do',
            'respCode'    => 'OK',
            'signValue'   => 'fe06c4c11a62ac1abce2c137e643bfca',
        ];

        $entry = [
            'id' => '2012092200001',
            'amount' => '100.00'
        ];

        $shunShou->setOptions($arrSourceData);
        $shunShou->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');

        $arrSourceData = [
            'consumerNo'  => '03358',
            'merOrderNum' => '2012092200001',
            'tranAmt'     => '10.00',
            'callbackUrl' => 'http%3A%2F%2Fwww.xxx.com%2Fresponse.do',
            'respCode'    => 'OK',
            'signValue'   => 'fe06c4c11a62ac1abce2c137e643bfca',
        ];

        $entry = [
            'id' => '2012092200001',
            'amount' => '10.00'
        ];

        $shunShou->setOptions($arrSourceData);
        $shunShou->verifyOrderPayment($entry);

        $this->assertEquals('success', $shunShou->getMsg());
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

        $shunShou = new ShunShou();
        $shunShou->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->paymentTracking();
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
            'number' => '03358',
            'orderId' => '20120922-00001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證參數數量錯誤
     */
    public function testTrackingReturnSignatureVerificationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $response = new Response();
        $response->setContent('null');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '20120922-00001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shunshou.com'
        ];

        $shunShou = new ShunShou();
        $shunShou->setContainer($this->container);
        $shunShou->setClient($this->client);
        $shunShou->setResponse($response);
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTracking();
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

        $params = [
            'consumerNo' => '03358',
            'merOrderNum' => '20120922-00001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '1',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '59ec0d991a6c409aefedb07b4e603a6'
        ];
        $result = implode('|', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '20120922-00001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shunshou.com'
        ];

        $shunShou = new ShunShou();
        $shunShou->setContainer($this->container);
        $shunShou->setClient($this->client);
        $shunShou->setResponse($response);
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $params = [
            'consumerNo' => '03358',
            'merOrderNum' => '20120922-00001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '0',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '5c87f21c10233fe637181c828debbd06',
        ];
        $result = implode('|', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '20120922-00001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shunshou.com'
        ];

        $shunShou = new ShunShou();
        $shunShou->setContainer($this->container);
        $shunShou->setClient($this->client);
        $shunShou->setResponse($response);
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTracking();
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
            'consumerNo' => '03358',
            'merOrderNum' => '20120922-00001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '2',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '7d2eae53915292e46f332e54e2d1b75c'
        ];
        $result = implode('|', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '20120922-00001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shunshou.com'
        ];

        $shunShou = new ShunShou();
        $shunShou->setContainer($this->container);
        $shunShou->setClient($this->client);
        $shunShou->setResponse($response);
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTracking();
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
            'consumerNo' => '03358',
            'merOrderNum' => '2012092200001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '1',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '117b3414444310ad4e90e616c8aa0579'
        ];
        $result = urlencode(implode('|', $params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '2012092200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shunshou.com',
            'amount' => '1.234'
        ];

        $shunShou = new ShunShou();
        $shunShou->setContainer($this->container);
        $shunShou->setClient($this->client);
        $shunShou->setResponse($response);
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = [
            'consumerNo' => '03358',
            'merOrderNum' => '2012092200001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '1',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '117b3414444310ad4e90e616c8aa0579'
        ];
        $result = urlencode(implode('|', $params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '03358',
            'orderId' => '2012092200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.shunshou.com',
            'amount' => '10.00'
        ];

        $shunShou = new ShunShou();
        $shunShou->setContainer($this->container);
        $shunShou->setClient($this->client);
        $shunShou->setResponse($response);
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTracking();
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

        $shunShou = new ShunShou();
        $shunShou->getPaymentTrackingData();
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

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->getPaymentTrackingData();
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
            'number' => '03358',
            'orderId' => '2012092200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($options);
        $shunShou->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '03358',
            'orderId' => '2012092200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.shunshou.com',
        ];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($options);
        $trackingData = $shunShou->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/query_bank_order.do', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.pay.shunshou.com', $trackingData['headers']['Host']);

        $this->assertEquals('03358', $trackingData['form']['consumerNo']);
        $this->assertEquals('2012092200001', $trackingData['form']['merOrderNum']);
        $this->assertEquals('e44c7941c72fe944f08390e9e9c6c896', $trackingData['form']['sign']);
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

        $shunShou = new ShunShou();
        $shunShou->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名驗證參數數量錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = [
            'consumerNo' => '03358',
            'merOrderNum' => '20120922-00001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '1',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '59ec0d991a6c409aefedb07b4e603a6'
        ];

        $encodeContent = urlencode(implode('|', $content));
        $sourceData = ['content' => $encodeContent];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = [
            'consumerNo' => '03358',
            'merOrderNum' => '20120922-00001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '1',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '59ec0d991a6c409aefedb07b4e603a6'
        ];

        $encodeContent = urlencode(implode('|', $content));
        $sourceData = ['content' => $encodeContent];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳訂單處理中
     */
    public function testPaymentTrackingVerifyOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $content = [
            'consumerNo' => '03358',
            'merOrderNum' => '20120922-00001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '0',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '5c87f21c10233fe637181c828debbd06',
        ];

        $encodeContent = urlencode(implode('|', $content));
        $sourceData = ['content' => $encodeContent];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTrackingVerify();
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
            'consumerNo' => '03358',
            'merOrderNum' => '20120922-00001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '2',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '7d2eae53915292e46f332e54e2d1b75c'
        ];

        $encodeContent = urlencode(implode('|', $content));
        $sourceData = ['content' => $encodeContent];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTrackingVerify();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = [
            'consumerNo' => '03358',
            'merOrderNum' => '2012092200001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '1',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '117b3414444310ad4e90e616c8aa0579'
        ];

        $encodeContent = urlencode(implode('|', $content));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 100
        ];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = [
            'consumerNo' => '03358',
            'merOrderNum' => '2012092200001',
            'requestAmt' => '',
            'tranAmt' => '10.00',
            'requestTime' => '20130618050000',
            'transTime' => '',
            'orderStatus' => '1',
            'bankCode' => '',
            'orderId' => '',
            'returnCode' => '',
            'merRemark1' => '',
            'sign' => '117b3414444310ad4e90e616c8aa0579'
        ];

        $encodeContent = urlencode(implode('|', $content));
        $sourceData = [
            'content' => $encodeContent,
            'amount' => 10
        ];

        $shunShou = new ShunShou();
        $shunShou->setPrivateKey('1234');
        $shunShou->setOptions($sourceData);
        $shunShou->paymentTrackingVerify();
    }
}
