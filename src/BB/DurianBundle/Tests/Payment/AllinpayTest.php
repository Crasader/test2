<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Allinpay;
use Buzz\Message\Response;

class AllinpayTest extends DurianTestCase
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
    public function testSetEncodeSourceWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $allinpay = new Allinpay();
        $allinpay->getVerifyData();
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

        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');

        $sourceData = ['number' => ''];

        $allinpay->setOptions($sourceData);
        $allinpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'orderCreateDate' => '2014-06-06 15:40:00',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $encodeData = $allinpay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['merchantId']);
        $this->assertEquals(1, $encodeData['orderAmount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderNo']);
        $this->assertEquals($notifyUrl, $encodeData['pickupUrl']);
        $this->assertEquals($notifyUrl, $encodeData['receiveUrl']);
        $this->assertEquals('icbc', $encodeData['issuerId']);
        $this->assertEquals($sourceData['username'], $encodeData['payerName']);
        $this->assertEquals($sourceData['username'], $encodeData['productName']);
        $this->assertEquals('20140606154000', $encodeData['orderDatetime']);
        $this->assertEquals('9D31930B571E591E378FCBB39280FEC2', $encodeData['signMsg']);
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

        $allinpay = new Allinpay();

        $allinpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數payResult(支付結果)
     */
    public function testVerifyNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system'     => '12345',
            'hallid'         => '6',
            'payDatetime'    => '20140606154634',
            'ext1'           => '',
            'payAmount'      => '1',
            'returnDatetime' => '20140606154634',
            'issuerId'       => '',
            'signMsg'        => '42A83F190BF2BEF2781BD8DFB2742946',
            'payType'        => '1',
            'language'       => '1',
            'errorCode'      => '',
            'merchantId'     => '109065311204094',
            'orderDatetime'  => '20140606154000',
            'version'        => 'v1.0',
            'orderNo'        => '20140606000000002',
            'ext2'           => '',
            'signType'       => '0',
            'orderAmount'    => '1',
            'paymentOrderId' => '201406061546098747'
        ];

        $allinpay->setOptions($sourceData);
        $allinpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數signMsg(加密簽名)
     */
    public function testVerifyWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system'     => '12345',
            'hallid'         => '6',
            'payDatetime'    => '20140606154634',
            'ext1'           => '',
            'payAmount'      => '1',
            'returnDatetime' => '20140606154634',
            'issuerId'       => '',
            'payType'        => '1',
            'language'       => '1',
            'errorCode'      => '',
            'merchantId'     => '109065311204094',
            'orderDatetime'  => '20140606154000',
            'version'        => 'v1.0',
            'orderNo'        => '20140606000000002',
            'ext2'           => '',
            'signType'       => '0',
            'orderAmount'    => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult'      => '1'
        ];

        $allinpay->setOptions($sourceData);
        $allinpay->verifyOrderPayment([]);
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

        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system'     => '12345',
            'hallid'         => '6',
            'payDatetime'    => '20140606154634',
            'ext1'           => '',
            'payAmount'      => '1',
            'returnDatetime' => '20140606154634',
            'issuerId'       => '',
            'signMsg'        => '781BD8DFB274294642A83F190BF2BEF2',
            'payType'        => '1',
            'language'       => '1',
            'errorCode'      => '',
            'merchantId'     => '109065311204094',
            'orderDatetime'  => '20140606154000',
            'version'        => 'v1.0',
            'orderNo'        => '20140606000000002',
            'ext2'           => '',
            'signType'       => '0',
            'orderAmount'    => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult'      => '1'
        ];

        $allinpay->setOptions($sourceData);
        $allinpay->verifyOrderPayment([]);
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

        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system'     => '12345',
            'hallid'         => '6',
            'payDatetime'    => '20140606154634',
            'ext1'           => '',
            'payAmount'      => '1',
            'returnDatetime' => '20140606154634',
            'issuerId'       => '',
            'signMsg'        => '3A988580F7C1BC6685574EE7650546D5',
            'payType'        => '1',
            'language'       => '1',
            'errorCode'      => '00000',
            'merchantId'     => '109065311204094',
            'orderDatetime'  => '20140606154000',
            'version'        => 'v1.0',
            'orderNo'        => '20140606000000002',
            'ext2'           => '',
            'signType'       => '0',
            'orderAmount'    => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult'      => '0'
        ];

        $allinpay->setOptions($sourceData);
        $allinpay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system'     => '12345',
            'hallid'         => '6',
            'payDatetime'    => '20140606154634',
            'ext1'           => '',
            'payAmount'      => '1',
            'returnDatetime' => '20140606154634',
            'issuerId'       => '',
            'signMsg'        => '42A83F190BF2BEF2781BD8DFB2742946',
            'payType'        => '1',
            'language'       => '1',
            'errorCode'      => '',
            'merchantId'     => '109065311204094',
            'orderDatetime'  => '20140606154000',
            'version'        => 'v1.0',
            'orderNo'        => '20140606000000002',
            'ext2'           => '',
            'signType'       => '0',
            'orderAmount'    => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult'      => '1'
        ];

        $entry = ['id' => '20140102030405006'];

        $allinpay->setOptions($sourceData);
        $allinpay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system'     => '12345',
            'hallid'         => '6',
            'payDatetime'    => '20140606154634',
            'ext1'           => '',
            'payAmount'      => '1',
            'returnDatetime' => '20140606154634',
            'issuerId'       => '',
            'signMsg'        => '42A83F190BF2BEF2781BD8DFB2742946',
            'payType'        => '1',
            'language'       => '1',
            'errorCode'      => '',
            'merchantId'     => '109065311204094',
            'orderDatetime'  => '20140606154000',
            'version'        => 'v1.0',
            'orderNo'        => '20140606000000002',
            'ext2'           => '',
            'signType'       => '0',
            'orderAmount'    => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult'      => '1'
        ];

        $entry = [
            'id' => '20140606000000002',
            'amount' => '1.0000'
        ];

        $allinpay->setOptions($sourceData);
        $allinpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $allinpay = new Allinpay();
        $allinpay->setPrivateKey('1234567890');

        $sourceData = [
            'pay_system'     => '12345',
            'hallid'         => '6',
            'payDatetime'    => '20140606154634',
            'ext1'           => '',
            'payAmount'      => '1',
            'returnDatetime' => '20140606154634',
            'issuerId'       => '',
            'signMsg'        => '42A83F190BF2BEF2781BD8DFB2742946',
            'payType'        => '1',
            'language'       => '1',
            'errorCode'      => '',
            'merchantId'     => '109065311204094',
            'orderDatetime'  => '20140606154000',
            'version'        => 'v1.0',
            'orderNo'        => '20140606000000002',
            'ext2'           => '',
            'signType'       => '0',
            'orderAmount'    => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult'      => '1'
        ];

        $entry = [
            'id' => '20140606000000002',
            'amount' => '0.0100'
        ];

        $allinpay->setOptions($sourceData);
        $allinpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $allinpay->getMsg());
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

        $allinpay = new allinpay();
        $allinpay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->paymentTracking();
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
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳訂單不存在
     */
    public function testPaymentTrackingResultOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $params = [
            'ERRORCODE' => '10027',
            'ERRORMSG' => '该笔订单不存在'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allinpay.com'
        ];

        $allinpay = new allinpay();
        $allinpay->setContainer($this->container);
        $allinpay->setClient($this->client);
        $allinpay->setResponse($response);
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少支付平台返回參數
     */
    public function testPaymentTrackingNoTrackingReturnParameterSpecified()
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
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allinpay.com'
        ];

        $allinpay = new allinpay();
        $allinpay->setContainer($this->container);
        $allinpay->setClient($this->client);
        $allinpay->setResponse($response);
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數signMsg
     */
    public function testPaymentTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'merchantId' => '109065311204094',
            'version' => 'v1.0',
            'signType' => '0',
            'paymentOrderId' => '201406061546098747',
            'orderNo' => '20140606000000002',
            'orderDatetime' => '20140606154000',
            'orderAmount' => '1',
            'payDatetime' => '20140606154634',
            'payAmount' => '1',
            'payResult' => '1',
            'returnDatetime' => '20140607134953'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allinpay.com'
        ];

        $allinpay = new allinpay();
        $allinpay->setContainer($this->container);
        $allinpay->setClient($this->client);
        $allinpay->setResponse($response);
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTracking();
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
            'merchantId' => '109065311204094',
            'version' => 'v1.0',
            'signType' => '0',
            'paymentOrderId' => '201406061546098747',
            'orderNo' => '20140606000000002',
            'orderDatetime' => '20140606154000',
            'orderAmount' => '1',
            'payDatetime' => '20140606154634',
            'payAmount' => '1',
            'payResult' => '1',
            'returnDatetime' => '20140607134953',
            'signMsg' => '9A210E6C3578C5EA5492EFDBB315DA36'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allinpay.com'
        ];

        $allinpay = new allinpay();
        $allinpay->setContainer($this->container);
        $allinpay->setClient($this->client);
        $allinpay->setResponse($response);
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTracking();
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
            'merchantId' => '109065311204094',
            'version' => 'v1.0',
            'signType' => '0',
            'paymentOrderId' => '201406061546098747',
            'orderNo' => '20140606000000002',
            'orderDatetime' => '20140606154000',
            'orderAmount' => '1',
            'payDatetime' => '20140606154634',
            'payAmount' => '1',
            'payResult' => '0',
            'returnDatetime' => '20140607134953',
            'signMsg' => '7E984EC327FED60EFA34AF3BFC5AEBC8'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allinpay.com'
        ];

        $allinpay = new allinpay();
        $allinpay->setContainer($this->container);
        $allinpay->setClient($this->client);
        $allinpay->setResponse($response);
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTracking();
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
            'merchantId' => '109065311204094',
            'version' => 'v1.0',
            'signType' => '0',
            'paymentOrderId' => '201406061546098747',
            'orderNo' => '20140606000000002',
            'orderDatetime' => '20140606154000',
            'orderAmount' => '1',
            'payDatetime' => '20140606154634',
            'payAmount' => '1',
            'payResult' => '1',
            'returnDatetime' => '20140607134953',
            'signMsg' => '8E3BFF208F049BED192A0D7970EEC4A4'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allinpay.com',
            'amount' => '100'
        ];

        $allinpay = new allinpay();
        $allinpay->setContainer($this->container);
        $allinpay->setClient($this->client);
        $allinpay->setResponse($response);
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = [
            'merchantId' => '109065311204094',
            'version' => 'v1.0',
            'signType' => '0',
            'paymentOrderId' => '201406061546098747',
            'orderNo' => '20140606000000002',
            'orderDatetime' => '20140606154000',
            'orderAmount' => '1',
            'payDatetime' => '20140606154634',
            'payAmount' => '1',
            'payResult' => '1',
            'returnDatetime' => '20140607134953',
            'signMsg' => '8E3BFF208F049BED192A0D7970EEC4A4'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allinpay.com',
            'amount' => '0.01'
        ];

        $allinpay = new allinpay();
        $allinpay->setContainer($this->container);
        $allinpay->setClient($this->client);
        $allinpay->setResponse($response);
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTracking();
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

        $allinpay = new allinpay();
        $allinpay->getPaymentTrackingData();
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

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->getPaymentTrackingData();
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
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($options);
        $allinpay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.service.allinpay.com',
        ];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($options);
        $trackingData = $allinpay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/gateway/query.do', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.service.allinpay.com', $trackingData['headers']['Host']);

        $this->assertEquals('109065311204094', $trackingData['form']['merchantId']);
        $this->assertEquals('v1.5', $trackingData['form']['version']);
        $this->assertEquals('0', $trackingData['form']['signType']);
        $this->assertEquals('20140606000000002', $trackingData['form']['orderNo']);
        $this->assertEquals('20140606154000', $trackingData['form']['orderDatetime']);
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

        $allinpay = new allinpay();
        $allinpay->paymentTrackingVerify();
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

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('fdsiojosdgdjioioj');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單不存在
     */
    public function testPaymentTrackingVerifyOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = [
            'ERRORCODE' => '10027',
            'ERRORMSG' => '该笔订单不存在'
        ];

        $sourceData = ['content' => http_build_query($content)];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('fdsiojosdgdjioioj');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數signMsg(加密簽名)
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'payDatetime' => '20140606154634',
            'userName' => '',
            'credentialsType' => '',
            'pan' => '',
            'txOrgId' => '',
            'ext1' => '',
            'payAmount' => '1',
            'returnDatetime' => '20140607134953',
            'credentialsNo' => '',
            'issuerId' => '',
            'payType' => '1',
            'language' => '1',
            'errorCode' => '',
            'merchantId' => '109065311204094',
            'orderDatetime' => '20140606154000',
            'version' => 'v1.0',
            'orderNo' => '20140606000000002',
            'ext2' => '',
            'signType' => '0',
            'orderAmount' => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult' => '1'
        ];

        $sourceData = ['content' => http_build_query($content)];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTrackingVerify();
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
            'payDatetime' => '20140606154634',
            'userName' => '',
            'credentialsType' => '',
            'pan' => '',
            'txOrgId' => '',
            'ext1' => '',
            'payAmount' => '1',
            'returnDatetime' => '20140607134953',
            'credentialsNo' => '',
            'issuerId' => '',
            'payType' => '1',
            'language' => '1',
            'errorCode' => '',
            'merchantId' => '109065311204094',
            'orderDatetime' => '20140606154000',
            'version' => 'v1.0',
            'orderNo' => '20140606000000002',
            'ext2' => '',
            'signType' => '0',
            'orderAmount' => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult' => '1',
            'signMsg' => '9A210E6C3578C5EA5492EFDBB315DA36',
        ];

        $sourceData = ['content' => http_build_query($content)];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTrackingVerify();
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
            'payDatetime' => '20140606154634',
            'userName' => '',
            'credentialsType' => '',
            'pan' => '',
            'txOrgId' => '',
            'ext1' => '',
            'payAmount' => '1',
            'returnDatetime' => '20140607134953',
            'credentialsNo' => '',
            'issuerId' => '',
            'payType' => '1',
            'language' => '1',
            'errorCode' => '',
            'merchantId' => '109065311204094',
            'orderDatetime' => '20140606154000',
            'version' => 'v1.0',
            'orderNo' => '20140606000000002',
            'ext2' => '',
            'signType' => '0',
            'orderAmount' => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult' => '0',
            'signMsg' => '9A210E6C3578C5EA5492EFDBB315DA36',
        ];

        $sourceData = ['content' => http_build_query($content)];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTrackingVerify();
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
            'payDatetime' => '20140606154634',
            'userName' => '',
            'credentialsType' => '',
            'pan' => '',
            'txOrgId' => '',
            'ext1' => '',
            'payAmount' => '1',
            'returnDatetime' => '20140607134953',
            'credentialsNo' => '',
            'issuerId' => '',
            'payType' => '1',
            'language' => '1',
            'errorCode' => '',
            'merchantId' => '109065311204094',
            'orderDatetime' => '20140606154000',
            'version' => 'v1.0',
            'orderNo' => '20140606000000002',
            'ext2' => '',
            'signType' => '0',
            'orderAmount' => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult' => '1',
            'signMsg' => '4DBF5A01AFA3F525CB865BE9A986FE0E',
        ];

        $sourceData = [
            'content' => http_build_query($content),
            'amount' => 500
        ];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = [
            'payDatetime' => '20140606154634',
            'userName' => '',
            'credentialsType' => '',
            'pan' => '',
            'txOrgId' => '',
            'ext1' => '',
            'payAmount' => '1',
            'returnDatetime' => '20140607134953',
            'credentialsNo' => '',
            'issuerId' => '',
            'payType' => '1',
            'language' => '1',
            'errorCode' => '',
            'merchantId' => '109065311204094',
            'orderDatetime' => '20140606154000',
            'version' => 'v1.0',
            'orderNo' => '20140606000000002',
            'ext2' => '',
            'signType' => '0',
            'orderAmount' => '1',
            'paymentOrderId' => '201406061546098747',
            'payResult' => '1',
            'signMsg' => '4DBF5A01AFA3F525CB865BE9A986FE0E',
        ];

        $sourceData = [
            'content' => http_build_query($content),
            'amount' => 0.01
        ];

        $allinpay = new allinpay();
        $allinpay->setPrivateKey('1234567890');
        $allinpay->setOptions($sourceData);
        $allinpay->paymentTrackingVerify();
    }
}
