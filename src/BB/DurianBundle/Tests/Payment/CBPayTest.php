<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CBPay;
use Buzz\Message\Response;

class CBPayTest extends DurianTestCase
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

        $cbPay = new CBPay();
        $cbPay->getVerifyData();
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

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = ['number' => ''];

        $cbPay->setOptions($sourceData);
        $cbPay->getVerifyData();
    }

    /**
     * 測試加密時代入支付平台不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor not support by PaymentGateway',
            180066
        );

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'number' => '1001',
            'amount' => '0.01',
            'orderId' => '000001234',
            'notify_url' => 'http://domain/chinabank/Receive.asp',
            'paymentVendorId' => '77',
            'username' => '张三',
            'orderCreateDate' => '2005-03-20 12:34:56',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'number' => '1001',
            'amount' => 0.01,
            'orderId' => 201501110000123456,
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => 1,
            'username' => 'acctest',
            'orderCreateDate' => '2015/01/11 12:34:56',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $cbPay->setOptions($sourceData);
        $encodeData = $cbPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );
        $remark2 = '[url:=http://esball.org/app/member/pay_online2/pay_result.php?pay_system=12345&hallid=6]';

        $this->assertEquals($sourceData['number'], $encodeData['v_mid']);
        $this->assertSame('0.01', $encodeData['v_amount']);
        $this->assertEquals('20150111-1001-201501110000123456', $encodeData['v_oid']);
        $this->assertEquals($notifyUrl, $encodeData['v_url']);
        $this->assertEquals($remark2, $encodeData['remark2']);
        $this->assertEquals('1025', $encodeData['pmode_id']);
        $this->assertEquals($sourceData['username'], $encodeData['v_ordername']);
        $this->assertEquals('C8D0AE57B48451E907F9A62DB7409341', $encodeData['v_md5info']);
    }

    /**
     * 測試解密基本參數設定帶入key為空值的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $cbPay = new CBPay();
        $cbPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳指定參數
     */
    public function testVerifyNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'v_oid'       => '20050320-1001-000001234',
            'v_pstatus'   => '20',
            'v_moneytype' => 'CNY',
            'v_md5str'    => '2F1F266702C8365B97D68E1A61D713FB'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳v_md5str(加密簽名)
     */
    public function testVerifyWithoutMd5str()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'v_oid'       => '20050320-1001-000001234',
            'v_pstatus'   => '20',
            'v_amount'    => '0.01',
            'v_moneytype' => 'CNY'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->verifyOrderPayment([]);
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

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'v_oid'       => '20050320-1001-000001234',
            'v_pstatus'   => '20',
            'v_amount'    => '0.01',
            'v_moneytype' => 'CNY',
            'v_md5str'    => 'x'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->verifyOrderPayment([]);
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

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'v_oid'       => '20050320-1001-000001234',
            'v_pstatus'   => '0',
            'v_amount'    => '0.01',
            'v_moneytype' => 'CNY',
            'v_md5str'    => 'B06E4FE1494C4738D795BE9DC389E761'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'v_oid'       => '20050320-1001-000001234',
            'v_pstatus'   => '20',
            'v_amount'    => '0.01',
            'v_moneytype' => 'CNY',
            'v_md5str'    => '2F1F266702C8365B97D68E1A61D713FB'
        ];

        $entry = ['id' => '19990720'];

        $cbPay->setOptions($sourceData);
        $cbPay->verifyOrderPayment($entry);
    }

    /**
     * 測試金額比對錯誤的情況
     */
    public function testAmountFailure()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'v_oid'       => '20050320-1001-000001234',
            'v_pstatus'   => '20',
            'v_amount'    => '0.01',
            'v_moneytype' => 'CNY',
            'v_md5str'    => '2F1F266702C8365B97D68E1A61D713FB'
        ];

        $entry = [
            'id' => '000001234',
            'amount' => '1.00'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $cbPay = new CBPay();
        $cbPay->setPrivateKey('1234');

        $sourceData = [
            'v_oid'       => '20050320-1001-000001234',
            'v_pstatus'   => '20',
            'v_amount'    => '0.01',
            'v_moneytype' => 'CNY',
            'v_md5str'    => '2F1F266702C8365B97D68E1A61D713FB'
        ];

        $entry = [
            'id' => '000001234',
            'amount' => '0.01'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $cbPay->getMsg());
    }

    /**
     * 測試訂單查詢加密
     */
    public function testPaymentTracking()
    {
        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
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

        $cbPay = new CBPay();
        $cbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密缺少指定查詢參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $cbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密缺少帶入verifyUrl的情況
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
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

        $result = '<br>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
    }

    /**
     * 測試驗證訂單查詢結果缺少必要參數的情況
     */
    public function testPaymentTrackingtWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<input type=hidden name="xx" value="1234567890">';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="0">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="10">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
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

        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="30">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
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

        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B814">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密訂單金額錯誤的情況
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="100" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="B5FAEB582C9266554C7001AC9F952FB3">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密訂單號錯誤的情況
     */
    public function testPaymentTrackingOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000045">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="9A74C489466C8AC3B6068B6149C34D8D">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GB2312', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html; charset=GB2312');

        $cbPay = new CBPay();
        $cbPay->setContainer($this->container);
        $cbPay->setClient($this->client);
        $cbPay->setResponse($response);
        $cbPay->setPrivateKey('biwgh2iuh98763SS');

        $sourceData = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.cbpay.com'
        ];

        $cbPay->setOptions($sourceData);
        $cbPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $cbPay = new CBPay();
        $cbPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入number
     */
    public function testGetPaymentTrackingDataWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['orderId' => '201411060000000024'];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($options);
        $cbPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($options);
        $cbPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay3.chinabank.com.cn'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($options);
        $trackingData = $cbPay->getPaymentTrackingData();

        $path = '/receiveorder.jsp?v_oid=20141106-22958909-201411060000000024&' .
            'v_mid=22958909&v_url=&billNo_md5=518BC1B696C408A9584F03FA6EB41DE6';
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('payment.https.pay3.chinabank.com.cn', $trackingData['headers']['Host']);
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

        $cbPay = new CBPay();
        $cbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未指定查詢參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但沒代入number
     */
    public function testPaymentTrackingVerifyWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $sourceData = [
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->getPaymentTrackingData();
    }

    /**
     * 測試驗證訂單查詢但查詢失敗
     */
    public function testPaymentTrackingVerifyButPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $sourceData = [
            'content' => '<br>',
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
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

        $content = '<input type=hidden name="xx" value="1234567890">';
        $sourceData = [
            'content' => $content,
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但回傳訂單不存在
     */
    public function testPaymentTrackingVerifyButOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="0">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';
        $sourceData = [
            'content' => $content,
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單未支付
     */
    public function testPaymentTrackingVerifyButUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $content = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="10">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';
        $sourceData = [
            'content' => $content,
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
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

        $content = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="30">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B843">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';
        $sourceData = [
            'content' => $content,
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
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

        $content = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="259F8A3CD6B02BCB852D7D3C0E17B814">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';
        $sourceData = [
            'content' => $content,
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
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

        $content = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000024">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="100" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="B5FAEB582C9266554C7001AC9F952FB3">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';
        $sourceData = [
            'content' => $content,
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單號錯誤
     */
    public function testPaymentTrackingVerifyButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000045">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="9A74C489466C8AC3B6068B6149C34D8D">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';
        $sourceData = [
            'content' => $content,
            'number' => '22958909',
            'orderId' => '201411060000000024',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<input type=hidden name="v_oid" value="20141106-22958909-201411060000000045">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="0.01" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="9A74C489466C8AC3B6068B6149C34D8D">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>';
        $sourceData = [
            'content' => $content,
            'number' => '22958909',
            'orderId' => '201411060000000045',
            'amount' => '0.0100',
            'orderCreateDate' => '2014-11-06 16:34:28'
        ];

        $cbPay = new CBPay();
        $cbPay->setPrivateKey('biwgh2iuh98763SS');
        $cbPay->setOptions($sourceData);
        $cbPay->paymentTrackingVerify();
    }

    /**
     * 測試轉換訂單查詢支付平台返回的編碼
     */
    public function testProcessTrackingResponseEncoding()
    {
        // 將支付平台的返回做編碼模擬 kue 返回
        $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <body>
            <form name="PAResForm" action="" method="post">
            <input type=hidden name="v_oid" value="20130428-12345-201304280000000001">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="100.00" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="FA649F788FD7C16212B52BAB39389C4C">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>
            </form>
            </body>
            </html>';
        $encodedBody = base64_encode(iconv('UTF-8', 'GB2312', $body));

        $encodedResponse = [
            'header' => [
                'server' => 'nginx',
                'content-type' => 'text/html; charset=GB2312'
            ],
            'body' => $encodedBody
        ];

        $cbPay = new CBPay();
        $trackingResponse = $cbPay->processTrackingResponseEncoding($encodedResponse);

        $this->assertEquals($encodedResponse['header'], $trackingResponse['header']);
        $this->assertEquals($body, $trackingResponse['body']);
    }
}
