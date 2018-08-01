<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HnaPay;
use Buzz\Message\Response;

class HnaPayTest extends DurianTestCase
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

        $hnaPay = new HnaPay();
        $hnaPay->getVerifyData();
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

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d01010');

        $sourceData = ['orderId' => ''];

        $hnaPay->setOptions($sourceData);
        $hnaPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定沒有帶入username的情況
     */
    public function testSetEncodeSourceNoUserName()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d01010');

        $sourceData = [
            'orderId' => '201405020016749709',
            'orderCreateDate' => '2014-05-02 12:34:56',
            'amount' => '30',
            'paymentVendorId' => '1',
            'notify_url' => 'https://www.hnapay.com/website/pay.htm',
            'number' => '10004316792',
            'username' => '',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $hnaPay->setOptions($sourceData);
        $hnaPay->getVerifyData();
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

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d01010');

        $sourceData = [
            'orderId' => '20140503000000001',
            'orderCreateDate' => '2014-05-03 00:06:00',
            'amount' => '0.01',
            'paymentVendorId' => '999',
            'notify_url' => 'https://www.hnapay.com/website/pay.htm',
            'number' => '10004316792',
            'username' => 'php1test',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $hnaPay->setOptions($sourceData);
        $hnaPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281'.
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00'.
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0'.
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b'.
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a'.
            '46d10203010001';

        $sourceData = [
            'orderId' => '20140503000000001',
            'orderCreateDate' => '2014-05-03 00:06:00',
            'amount' => '0.01',
            'paymentVendorId' => '1', //icbc(工商銀行，要回傳的銀行代碼)
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'number' => '10004316792',
            'username' => 'php1test',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey($privateKey);
        $hnaPay->setOptions($sourceData);
        $encodeData = $hnaPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('icbc', $encodeData['orgCode']);
        $this->assertEquals('20140503000000001,1,php1test,goodsName,1', $encodeData['orderDetails']);
        $this->assertEquals('1', $encodeData['totalAmount']);
        $this->assertEquals($notifyUrl, $encodeData['returnUrl']);
        $this->assertEquals($notifyUrl, $encodeData['noticeUrl']);
        $this->assertEquals('2d09ff9b4511682134dd544e37ffba85', $encodeData['signMsg']);
    }

    /**
     * 測試加密，帶入微信二維
     */
    public function testGetEncodeDataWithWechatPay()
    {
        $hnaPay = new HnaPay();

        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281'.
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00'.
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0'.
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b'.
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a'.
            '46d10203010001';

        $hnaPay->setPrivateKey($privateKey);

        $sourceData = [
            'orderId' => '20140503000000001',
            'orderCreateDate' => '2014-05-03 00:06:00',
            'amount' => '0.01',
            'paymentVendorId' => '1090', // WECHATPAY
            'notify_url' => 'https://www.hnapay.com/website/pay.htm',
            'number' => '10004316792',
            'username' => 'php1test',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $hnaPay->setOptions($sourceData);
        $encodeData = $hnaPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('WECHATPAY', $encodeData['orgCode']);
        $this->assertEquals('20140503000000001,1,php1test,goodsName,1', $encodeData['orderDetails']);
        $this->assertEquals('1', $encodeData['totalAmount']);
        $this->assertEquals($notifyUrl, $encodeData['returnUrl']);
        $this->assertEquals($notifyUrl, $encodeData['noticeUrl']);
        $this->assertEquals('0d5b7858439ad1d2f94367c6110f0203', $encodeData['signMsg']);
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

        $hnaPay = new HnaPay();

        $hnaPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testVerifyWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d00');

        $sourceData = [
            'orderID'       => '201405020016749709',
            'stateCode'     => '2',
            'orderAmount'   => '3000',
            'payAmount'     => '3000',
            'acquiringTime' => '20140502001339',
            'completeTime'  => '20140502001339',
            'orderNo'       => '1051405020013089250',
            'partnerID'     => '10004316792',
            'remark'        => '1',
            'charset'       => '1',
            'signType'      => '2',
            'signMsg'       => 'af80c11705778917bd9e9b5c0850e861'
        ];

        $hnaPay->setOptions($sourceData);
        $hnaPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證沒有必要的參數(測試signMsg)
     */
    public function testVerifyWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d00');

        $sourceData = [
            'orderID'       => '201405020016749709',
            'resultCode'    => '',
            'stateCode'     => '2',
            'orderAmount'   => '3000',
            'payAmount'     => '3000',
            'acquiringTime' => '20140502001339',
            'completeTime'  => '20140502001339',
            'orderNo'       => '1051405020013089250',
            'partnerID'     => '10004316792',
            'remark'        => '1',
            'charset'       => '1',
            'signType'      => '2'
        ];

        $hnaPay->setOptions($sourceData);
        $hnaPay->verifyOrderPayment([]);
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

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d00');

        $sourceData = [
            'orderID'       => '201405020016749709',
            'resultCode'    => '',
            'stateCode'     => '2',
            'orderAmount'   => '3000',
            'payAmount'     => '3000',
            'acquiringTime' => '20140502001339',
            'completeTime'  => '20140502001339',
            'orderNo'       => '1051405020013089250',
            'partnerID'     => '10004316792',
            'remark'        => '1',
            'charset'       => '1',
            'signType'      => '2',
            'signMsg'       => 'ce591e13e5c5ad62e6eab5f7976225d0'
        ];

        $hnaPay->setOptions($sourceData);
        $hnaPay->verifyOrderPayment([]);
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

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d00');

        $sourceData = [
            'orderID'       => '201405020016749709',
            'resultCode'    => '',
            'stateCode'     => '3',
            'orderAmount'   => '3000',
            'payAmount'     => '3000',
            'acquiringTime' => '20140502001339',
            'completeTime'  => '20140502001339',
            'orderNo'       => '1051405020013089250',
            'partnerID'     => '10004316792',
            'remark'        => '1',
            'charset'       => '1',
            'signType'      => '2',
            'signMsg'       => '4b2d23c04ea7b4049f29ebd1f0088738'
        ];

        $hnaPay->setOptions($sourceData);
        $hnaPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $hnaPay = new HnaPay();

        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281'.
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00'.
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0'.
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b'.
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a'.
            '46d10203010001';

        $hnaPay->setPrivateKey($privateKey);

        $sourceData = [
            'orderID'       => '201405020016749709',
            'resultCode'    => '',
            'stateCode'     => '2',
            'orderAmount'   => '3000',
            'payAmount'     => '3000',
            'acquiringTime' => '20140502001339',
            'completeTime'  => '20140502001339',
            'orderNo'       => '1051405020013089250',
            'partnerID'     => '10004316792',
            'remark'        => '1',
            'charset'       => '1',
            'signType'      => '2',
            'signMsg'       => 'af80c11705778917bd9e9b5c0850e861'
        ];

        $entry = ['id' => '147896325'];

        $hnaPay->setOptions($sourceData);
        $hnaPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $hnaPay = new HnaPay();

        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281'.
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00'.
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0'.
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b'.
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a'.
            '46d10203010001';

        $hnaPay->setPrivateKey($privateKey);

        $sourceData = [
            'orderID'       => '201405020016749709',
            'resultCode'    => '',
            'stateCode'     => '2',
            'orderAmount'   => '3000',
            'payAmount'     => '3000',
            'acquiringTime' => '20140502001339',
            'completeTime'  => '20140502001339',
            'orderNo'       => '1051405020013089250',
            'partnerID'     => '10004316792',
            'remark'        => '1',
            'charset'       => '1',
            'signType'      => '2',
            'signMsg'       => 'af80c11705778917bd9e9b5c0850e861'
        ];

        $entry = [
            'id' => '201405020016749709',
            'amount' => '3000.0000'
        ];

        $hnaPay->setOptions($sourceData);
        $hnaPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $hnaPay = new HnaPay();

        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281'.
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00'.
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0'.
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b'.
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a'.
            '46d10203010001';

        $hnaPay->setPrivateKey($privateKey);

        $sourceData = [
            'orderID'       => '201405020016749709',
            'resultCode'    => '',
            'stateCode'     => '2',
            'orderAmount'   => '3000',
            'payAmount'     => '3000',
            'acquiringTime' => '20140502001339',
            'completeTime'  => '20140502001339',
            'orderNo'       => '1051405020013089250',
            'partnerID'     => '10004316792',
            'remark'        => '1',
            'charset'       => '1',
            'signType'      => '2',
            'signMsg'       => 'af80c11705778917bd9e9b5c0850e861'
        ];

        $entry = [
            'id' => '201405020016749709',
            'amount' => '30.0000'
        ];

        $hnaPay->setOptions($sourceData);
        $hnaPay->verifyOrderPayment($entry);

        $this->assertEquals('200', $hnaPay->getMsg());
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

        $hnaPay = new HnaPay();
        $hnaPay->paymentTracking();
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

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入orderCreateDate
     */
    public function testPaymentTrackingWithoutOrderCreateDate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $sourceData = [
            'orderId' => '10004316792',
            'number' => '201404210015073550',
            'orderCreateDate' => '',
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
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
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果未指定返回參數
     */
    public function testPaymentTrackingResultWithNoTrackingReturnParameterSpecified()
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
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.hnapay.com'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setContainer($this->container);
        $hnaPay->setClient($this->client);
        $hnaPay->setResponse($response);
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果交易失敗
     */
    public function testTrackingReturnResultCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $params = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0009',
            'queryDetailsSize' => '0',
            'queryDetails' => '',
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.hnapay.com'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setContainer($this->container);
        $hnaPay->setClient($this->client);
        $hnaPay->setResponse($response);
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
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

        $params = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => '201404210015073550,8000,8000,20140421012515',
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.hnapay.com'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setContainer($this->container);
        $hnaPay->setClient($this->client);
        $hnaPay->setResponse($response);
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數signMsg
     */
    public function testPaymentTrackingResultWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,2';
        $params = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.hnapay.com'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setContainer($this->container);
        $hnaPay->setClient($this->client);
        $hnaPay->setResponse($response);
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
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

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,2';
        $params = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '77d7515488921c0f21c52e752bc51837'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.hnapay.com'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setContainer($this->container);
        $hnaPay->setClient($this->client);
        $hnaPay->setResponse($response);
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
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

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,3';
        $params = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '77d7515488921c0f21c52e752bc51837'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.hnapay.com'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setContainer($this->container);
        $hnaPay->setClient($this->client);
        $hnaPay->setResponse($response);
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
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

        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281'.
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00'.
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0'.
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b'.
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a'.
            '46d10203010001';

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,2';
        $params = [
            'serialID' => '2014042100150735500.09614400',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails'=> $queryDetails,
            'partnerID' => '10004316792',
            'remark' => 'remark',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '46f9c2ebbc6701f63a773c360f83a0bf'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.hnapay.com',
            'amount' => '100.00'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setContainer($this->container);
        $hnaPay->setClient($this->client);
        $hnaPay->setResponse($response);
        $hnaPay->setPrivateKey($privateKey);
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281'.
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00'.
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0'.
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b'.
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a'.
            '46d10203010001';

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,2';
        $params = [
            'serialID' => '2014042100150735500.09614400',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails'=> $queryDetails,
            'partnerID' => '10004316792',
            'remark' => 'remark',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '46f9c2ebbc6701f63a773c360f83a0bf'
        ];
        $result = http_build_query($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.hnapay.com',
            'amount' => '80.00'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setContainer($this->container);
        $hnaPay->setClient($this->client);
        $hnaPay->setResponse($response);
        $hnaPay->setPrivateKey($privateKey);
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTracking();
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

        $hnaPay = new HnaPay();
        $hnaPay->getPaymentTrackingData();
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

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入number
     */
    public function testGetPaymentTrackingDataWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['orderId' => '10004316792'];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($options);
        $hnaPay->getPaymentTrackingData();
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

        $options = [
            'orderId' => '10004316792',
            'number' => '201404210015073550',
            'orderCreateDate' => ''
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($options);
        $hnaPay->getPaymentTrackingData();
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
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($options);
        $hnaPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '10004316792',
            'orderId' => '201404210015073550',
            'orderCreateDate' => '20140421012642',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.hnapay.com',
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($options);
        $trackingData = $hnaPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/website/queryOrderResult.htm', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.www.hnapay.com', $trackingData['headers']['Host']);

        // serialID格式為訂單號+microtime微秒的部分(ex:0.12345678)
        $this->assertRegExp('/^2014042100150735500.[0-9]{8}$/', $trackingData['form']['serialID']);
        $this->assertEquals('201404210015073550', $trackingData['form']['orderID']);
        $this->assertEquals('20140421012642', $trackingData['form']['beginTime']);
        $this->assertEquals('20140421012642', $trackingData['form']['endTime']);
        $this->assertEquals('10004316792', $trackingData['form']['partnerID']);
        $this->assertTrue(isset($trackingData['form']['signMsg']));
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

        $hnaPay = new HnaPay();
        $hnaPay->paymentTrackingVerify();
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

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但交易失敗
     */
    public function testPaymentTrackingVerifyButResultCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0009',
            'queryDetailsSize' => '0',
            'queryDetails' => '',
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2'
        ];
        $sourceData = ['content' => http_build_query($content)];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但簽名驗證參數數量錯誤
     */
    public function testPaymentTrackingVerifyButSignatureVerificationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => '201404210015073550,8000,8000,20140421012515',
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2'
        ];
        $sourceData = ['content' => http_build_query($content)];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數signMsg
     */
    public function testPaymentTrackingVerifyWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,2';
        $content = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2'
        ];
        $sourceData = ['content' => http_build_query($content)];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTrackingVerify();
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

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,2';
        $content = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '77d7515488921c0f21c52e752bc51837'
        ];

        $sourceData = ['content' => http_build_query($content)];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTrackingVerify();
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

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,3';
        $content = [
            'serialID' => '2014042100150735500.55164900',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '',
            'queryDetailsSize' => '1',
            'queryDetails' => $queryDetails,
            'partnerID' => '10004316792',
            'remark' => '1',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '77d7515488921c0f21c52e752bc51837'
        ];

        $sourceData = ['content' => http_build_query($content)];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey('30819f300d06092a864886f70d010101050003818d003081');
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTrackingVerify();
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

        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281' .
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00' .
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0' .
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b' .
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a' .
            '46d10203010001';

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,2';
        $content = [
            'serialID' => '2014042100150735500.09614400',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails'=> $queryDetails,
            'partnerID' => '10004316792',
            'remark' => 'remark',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '46f9c2ebbc6701f63a773c360f83a0bf'
        ];

        $sourceData = [
            'content' => http_build_query($content),
            'amount' => '100.00'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey($privateKey);
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $privateKey = '30819f300d06092a864886f70d010101050003818d003081890281' .
            '8100a38efa6cfe6744f10eb9c37ff78a76f9dcc2ef245732c14b1c9520e2da00' .
            'c391849d9d5cbfd86ac5e840f7749cc1eef0850d1cf1940c8865511d64665ae0' .
            '972a2e83b7e9e4c080f4c6b6956afe0b90a0277259088bce499e3c358477ba2b' .
            'f50991ebe17f0c380fdf4353030a6ec9f0c40984ff8ff87d6a09f5caa439e04a' .
            '46d10203010001';

        $queryDetails = '201404210015073550,8000,8000,20140421012515,20140421012642,1051404210125047227,2';
        $content = [
            'serialID' => '2014042100150735500.09614400',
            'mode' => '1',
            'type' => '1',
            'resultCode' => '0000',
            'queryDetailsSize' => '1',
            'queryDetails'=> $queryDetails,
            'partnerID' => '10004316792',
            'remark' => 'remark',
            'charset' => '1',
            'signType' => '2',
            'signMsg' => '46f9c2ebbc6701f63a773c360f83a0bf'
        ];

        $sourceData = [
            'content' => http_build_query($content),
            'amount' => '80.00'
        ];

        $hnaPay = new HnaPay();
        $hnaPay->setPrivateKey($privateKey);
        $hnaPay->setOptions($sourceData);
        $hnaPay->paymentTrackingVerify();
    }

    /**
     * 測試轉換訂單查詢支付平台返回的編碼
     */
    public function testProcessTrackingResponseEncoding()
    {
        // 將支付平台的返回做編碼模擬 kue 返回
        $body = 'serialID=2016012600000066290.55773600&mode=1&type=1&resultCode=0009&queryDetailsSize=0&' .
            'queryDetails=&partnerID=10056136570&remark=remark&charset=1&signType=2&' .
            'signMsg=aac3c012c50a234e9e4678358461b196';
        $encodedBody = base64_encode($body);

        $encodedResponse = [
            'header' => [
                'server' => 'nginx',
                'content-length' => '198'
            ],
            'body' => $encodedBody
        ];

        $hnaPay = new HnaPay();
        $trackingResponse = $hnaPay->processTrackingResponseEncoding($encodedResponse);

        $this->assertEquals($encodedResponse['header'], $trackingResponse['header']);
        $this->assertEquals($body, $trackingResponse['body']);
    }
}
