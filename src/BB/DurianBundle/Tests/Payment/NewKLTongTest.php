<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewKLTong;
use Buzz\Message\Response;

class NewKLTongTest extends DurianTestCase
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

        $newKLTong = new NewKLTong();
        $newKLTong->getVerifyData();
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

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');

        $sourceData = ['number' => ''];

        $newKLTong->setOptions($sourceData);
        $newKLTong->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'postUrl' => 'openepay.com',
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

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $encodeData = $newKLTong->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('https://pg.openepay.com/gateway/index.do', $encodeData['post_url']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['merchantId']);
        $this->assertEquals(1, $encodeData['params']['orderAmount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['orderNo']);
        $this->assertEquals($notifyUrl, $encodeData['params']['receiveUrl']);
        $this->assertEquals('icbc', $encodeData['params']['issuerId']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['payerName']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['productName']);
        $this->assertEquals('20140606154000', $encodeData['params']['orderDatetime']);
        $this->assertEquals('1', $encodeData['params']['payType']);
        $this->assertEquals('33BA4D801658490A0CA7D1F3D5064947', $encodeData['params']['signMsg']);
    }

    /**
     * 測試銀聯在線支付
     */
    public function testQuickPay()
    {
        $sourceData = [
            'postUrl' => 'openepay.com',
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '278',
            'username' => 'php1test',
            'orderCreateDate' => '2014-06-06 15:40:00',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $encodeData = $newKLTong->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('https://pg.openepay.com/gateway/index.do', $encodeData['post_url']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['merchantId']);
        $this->assertEquals(1, $encodeData['params']['orderAmount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['orderNo']);
        $this->assertEquals($notifyUrl, $encodeData['params']['receiveUrl']);
        $this->assertEquals('', $encodeData['params']['issuerId']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['payerName']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['productName']);
        $this->assertEquals('20140606154000', $encodeData['params']['orderDatetime']);
        $this->assertEquals('39', $encodeData['params']['payType']);
        $this->assertEquals('92C79AAB7EAEC8E08342EC021232571A', $encodeData['params']['signMsg']);
    }

    /**
     * 測試銀聯在線手機支付
     */
    public function testQuickPhonePay()
    {
        $sourceData = [
            'postUrl' => 'openepay.com',
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1088',
            'username' => 'php1test',
            'orderCreateDate' => '2014-06-06 15:40:00',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $encodeData = $newKLTong->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('https://mobile.openepay.com/mobilepay/index.do', $encodeData['post_url']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['merchantId']);
        $this->assertEquals(1, $encodeData['params']['orderAmount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['orderNo']);
        $this->assertEquals($notifyUrl, $encodeData['params']['receiveUrl']);
        $this->assertEquals('', $encodeData['params']['issuerId']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['productName']);
        $this->assertEquals('20140606154000', $encodeData['params']['orderDatetime']);
        $this->assertEquals('39', $encodeData['params']['payType']);
        $this->assertEquals('3448F064B192C3FDDA56CB9A47307624', $encodeData['params']['signMsg']);
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

        $newKLTong = new NewKLTong();

        $newKLTong->verifyOrderPayment([]);
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

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');

        $sourceData = [
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => 'A2E1737DEC8AF33EEC4CF8027B021A5E',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
        ];

        $newKLTong->setOptions($sourceData);
        $newKLTong->verifyOrderPayment([]);
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

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');

        $sourceData = [
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
        ];

        $newKLTong->setOptions($sourceData);
        $newKLTong->verifyOrderPayment([]);
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

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');

        $sourceData = [
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => 'A2E1737DEC8AF33EEC4CF8027B021A5E',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
        ];

        $newKLTong->setOptions($sourceData);
        $newKLTong->verifyOrderPayment([]);
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

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');

        $sourceData = [
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '2',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => '37A42DFEAE1ADB1E6B7801EC38514CBB',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
        ];

        $newKLTong->setOptions($sourceData);
        $newKLTong->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');

        $sourceData = [
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => 'BF8B60D5426FF8CA46FEC674F5A4BFEB',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
        ];

        $entry = ['id' => '20140102030405006'];

        $newKLTong->setOptions($sourceData);
        $newKLTong->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
                'BB\DurianBundle\Exception\PaymentException',
                'Order Amount error',
                180058
        );

        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');

        $sourceData = [
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => 'BF8B60D5426FF8CA46FEC674F5A4BFEB',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
        ];

        $entry = [
            'id' => '201608220000008155',
            'amount' => '1.0000',
        ];

        $newKLTong->setOptions($sourceData);
        $newKLTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $newKLTong = new NewKLTong();
        $newKLTong->setPrivateKey('1234567890');

        $sourceData = [
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => 'BF8B60D5426FF8CA46FEC674F5A4BFEB',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
        ];

        $entry = [
            'id' => '201608220000008155',
            'amount' => '0.1',
        ];

        $newKLTong->setOptions($sourceData);
        $newKLTong->verifyOrderPayment($entry);

        $this->assertEquals('success', $newKLTong->getMsg());
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

        $newKLTong = new newKLTong();
        $newKLTong->paymentTracking();
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

        $newKLTong = new newKLTong();
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->paymentTracking();
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
            'verify_url' => '',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
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
            'ERRORMSG' => '该笔订单不存在',
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
            'verify_url' => 'www.newKLTong.com',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setContainer($this->container);
        $newKLTong->setClient($this->client);
        $newKLTong->setResponse($response);
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $params = [
            'ERRORCODE' => '10026',
            'ERRORMSG' => '该笔订单未支付成功',
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;charset=UTF-8');

        $sourceData = [
            'number' => '109065311204094',
            'orderId' => '20140606000000002',
            'orderCreateDate' => '20140606154000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newKLTong.com',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setContainer($this->container);
        $newKLTong->setClient($this->client);
        $newKLTong->setResponse($response);
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
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
            'verify_url' => 'www.newKLTong.com',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setContainer($this->container);
        $newKLTong->setClient($this->client);
        $newKLTong->setResponse($response);
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
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
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
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
            'verify_url' => 'www.newKLTong.com',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setContainer($this->container);
        $newKLTong->setClient($this->client);
        $newKLTong->setResponse($response);
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
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
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => 'A2E1737DEC8AF33EEC4CF8027B021A5E',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
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
            'verify_url' => 'www.newKLTong.com',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setContainer($this->container);
        $newKLTong->setClient($this->client);
        $newKLTong->setResponse($response);
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
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
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '2',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => '37A42DFEAE1ADB1E6B7801EC38514CBB',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
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
            'verify_url' => 'www.newKLTong.com',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setContainer($this->container);
        $newKLTong->setClient($this->client);
        $newKLTong->setResponse($response);
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
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
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => 'BF8B60D5426FF8CA46FEC674F5A4BFEB',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
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
            'verify_url' => 'www.newKLTong.com',
            'amount' => '100',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setContainer($this->container);
        $newKLTong->setClient($this->client);
        $newKLTong->setResponse($response);
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = [
            'issuerId' => '',
            'orderNo' => '201608220000008155',
            'payDatetime' => '20160822171020',
            'ext1' => '',
            'mchtOrderId' => '201608221709385457',
            'payResult' => '1',
            'orderAmount' => '10',
            'ext2' => '',
            'signMsg' => 'BF8B60D5426FF8CA46FEC674F5A4BFEB',
            'signType' => '0',
            'payType' => '1',
            'merchantId' => '102410160613002',
            'language' => '1',
            'orderDatetime' => '20160822170933',
            'version' => 'v1.0',
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
            'verify_url' => 'www.newKLTong.com',
            'amount' => '0.10',
        ];

        $newKLTong = new newKLTong();
        $newKLTong->setContainer($this->container);
        $newKLTong->setClient($this->client);
        $newKLTong->setResponse($response);
        $newKLTong->setPrivateKey('1234567890');
        $newKLTong->setOptions($sourceData);
        $newKLTong->paymentTracking();
    }
}
