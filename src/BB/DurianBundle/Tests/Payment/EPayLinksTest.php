<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\EPayLinks;
use Buzz\Message\Response;

class EPayLinksTest extends DurianTestCase
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->getVerifyData();
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = ['number' => ''];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->getVerifyData();
    }

    /**
     * 測試加密時代入支付平台不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentGateway unsupported the PaymentVendor',
            180066
        );

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'amount' => '10',
            'notify_url' => 'http://pay.qingbinmall.com/pay/pay_response.php?pay_system=48209&hallid=3818073',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->getVerifyData();
    }

    /**
     * 測試提交加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'amount' => '10',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '3',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $encodeData = $ePayLinks->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals($sourceData['orderId'], $encodeData['out_trade_no']);
        $this->assertSame('10.00', $encodeData['total_fee']);
        $this->assertEquals($notifyUrl, $encodeData['return_url']);
        $this->assertEquals('nonghang', $encodeData['pay_id']);
        $this->assertEquals('3910af7d561e95fb5e90c7680600f6bc3c7ad2c5544d9cad90c2f9ae7ab497d6', $encodeData['sign']);
    }

    /**
     * 測試解密驗證時沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ePayLinks = new EPayLinks();

        $ePayLinks->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = [
            'amount'      => '10.0',
            'base64_memo' => '',
            'hallid'      => '3818073',
            'partner'     => 'EC130517YL0251',
            'pay_no'      => '1848210',
            'pay_result'  => '1',
            'pay_system'  => '48209',
            'sett_date'   => '20140410',
            'sign'        => '8f61a0f0329e3102978c41a3afdc3467ec3e6684d629370b5ab4523b83dda3d1',
            'sign_type'   => 'SHA256',
            'version'     => '3.0'
        ];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數sign(加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = [
            'amount'       => '10.0',
            'base64_memo'  => '',
            'hallid'       => '3818073',
            'out_trade_no' => '201404100013593016',
            'partner'      => 'EC130517YL0251',
            'pay_no'       => '1848210',
            'pay_result'   => '1',
            'pay_system'   => '48209',
            'sett_date'    => '20140410',
            'sign_type'    => 'SHA256',
            'version'      => '3.0'
        ];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->verifyOrderPayment([]);
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = [
            'amount'       => '10.0',
            'base64_memo'  => '',
            'hallid'       => '3818073',
            'out_trade_no' => '201404100013593016',
            'partner'      => 'EC130517YL0251',
            'pay_no'       => '1848210',
            'pay_result'   => '1',
            'pay_system'   => '48209',
            'sett_date'    => '20140410',
            'sign'         => '3e6684d629370b5ab4523b83dda3d18f61a0f0329e3102978c41a3afdc3467ec',
            'sign_type'    => 'SHA256',
            'version'      => '3.0'
        ];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->verifyOrderPayment([]);
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = [
            'amount'       => '10.0',
            'base64_memo'  => '',
            'hallid'       => '3818073',
            'out_trade_no' => '201404100013593016',
            'partner'      => 'EC130517YL0251',
            'pay_no'       => '1848210',
            'pay_result'   => '0',
            'pay_system'   => '48209',
            'sett_date'    => '20140410',
            'sign'         => 'e0ef746ef40f9683d73ec8a7d6f16833e9b087c7c7254c41fbbe4d48e4d033f7',
            'sign_type'    => 'SHA256',
            'version'      => '3.0'
        ];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = [
            'amount'       => '10.0',
            'base64_memo'  => '',
            'hallid'       => '3818073',
            'out_trade_no' => '201404100013593016',
            'partner'      => 'EC130517YL0251',
            'pay_no'       => '1848210',
            'pay_result'   => '1',
            'pay_system'   => '48209',
            'sett_date'    => '20140410',
            'sign'         => '8f61a0f0329e3102978c41a3afdc3467ec3e6684d629370b5ab4523b83dda3d1',
            'sign_type'    => 'SHA256',
            'version'      => '3.0'
        ];

        $entry = ['id' => '201404100013593018'];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = [
            'amount'       => '10.0',
            'base64_memo'  => '',
            'hallid'       => '3818073',
            'out_trade_no' => '201404100013593016',
            'partner'      => 'EC130517YL0251',
            'pay_no'       => '1848210',
            'pay_result'   => '1',
            'pay_system'   => '48209',
            'sett_date'    => '20140410',
            'sign'         => '8f61a0f0329e3102978c41a3afdc3467ec3e6684d629370b5ab4523b83dda3d1',
            'sign_type'    => 'SHA256',
            'version'      => '3.0'
        ];

        $entry = [
            'id' => '201404100013593016',
            'amount' => '100.0000'
        ];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->verifyOrderPayment($entry);
    }

    /**
     * 測試支付解密驗證成功
     */
    public function testPaySuccess()
    {
        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');

        $sourceData = [
            'amount'       => '10.0',
            'base64_memo'  => '',
            'hallid'       => '3818073',
            'out_trade_no' => '201404100013593016',
            'partner'      => 'EC130517YL0251',
            'pay_no'       => '1848210',
            'pay_result'   => '1',
            'pay_system'   => '48209',
            'sett_date'    => '20140410',
            'sign'         => '8f61a0f0329e3102978c41a3afdc3467ec3e6684d629370b5ab4523b83dda3d1',
            'sign_type'    => 'SHA256',
            'version'      => '3.0'
        ];

        $entry = [
            'id' => '201404100013593016',
            'amount' => '10.0000'
        ];

        $ePayLinks->setOptions($sourceData);
        $ePayLinks->verifyOrderPayment($entry);

        $this->assertEquals('success', $ePayLinks->getMsg());
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->paymentTracking();
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->paymentTracking();
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
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數resp_code
     */
    public function testPaymentTrackingResultWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="GBK" ?><root></root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗(resp_code回傳非00)
     */
    public function testTrackingReturnPaymentFailureWithRespCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '<?xml version="1.0" encoding="GBK" ?>'.
            '<root><resp_code>10</resp_code></root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數pay_result
     */
    public function testPaymentTrackingResultWithoutPayResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="GBK" ?>'.
            '<root><resp_code>00</resp_code></root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗(PayResult回傳非1)
     */
    public function testTrackingReturnPaymentFailureWithPayResultError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = '<?xml version="1.0" encoding="GBK" ?>'.
            '<root>'.
            '<resp_code>00</resp_code>'.
            '<pay_result>0</pay_result>'.
            '</root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數
     */
    public function testPaymentTrackingResultWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="GBK" ?>'.
            '<root>'.
            '<resp_code>00</resp_code>'.
            '<pay_result>1</pay_result>'.
            '</root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數sign
     */
    public function testPaymentTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="GBK" ?>'.
            '<root>'.
            '<resp_code>00</resp_code>'.
            '<pay_result>1</pay_result>'.
            '<amount>10.00</amount>'.
            '<curr_code>RMB</curr_code>'.
            '<out_trade_no>201404100013593016</out_trade_no>'.
            '<partner>EC130517YL0251</partner>'.
            '<pay_no>1848210</pay_no>'.
            '<resp_desc>Success</resp_desc>'.
            '<sett_date>20140410</sett_date>'.
            '<sign_type>SHA256</sign_type>'.
            '<version>3.0</version>'.
            '</root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="GBK" ?>'.
            '<root>'.
            '<resp_code>00</resp_code>'.
            '<pay_result>1</pay_result>'.
            '<amount>10.00</amount>'.
            '<curr_code>RMB</curr_code>'.
            '<out_trade_no>201404100013593016</out_trade_no>'.
            '<partner>EC130517YL0251</partner>'.
            '<pay_no>1848210</pay_no>'.
            '<resp_desc>success</resp_desc>'.
            '<sett_date>20140410</sett_date>'.
            '<sign_type>SHA256</sign_type>'.
            '<version>3.0</version>'.
            '<sign>22a468a52564cf326b8635c1c71507e1af6080eeae1e4817825561539d4bf001</sign>'.
            '</root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="GBK" ?>'.
            '<root>'.
            '<resp_code>00</resp_code>'.
            '<pay_result>1</pay_result>'.
            '<amount>10.00</amount>'.
            '<curr_code>RMB</curr_code>'.
            '<out_trade_no>201404100013593016</out_trade_no>'.
            '<partner>EC130517YL0251</partner>'.
            '<pay_no>1848210</pay_no>'.
            '<resp_desc>Success</resp_desc>'.
            '<sett_date>20140410</sett_date>'.
            '<sign_type>SHA256</sign_type>'.
            '<version>3.0</version>'.
            '<sign>22a468a52564cf326b8635c1c71507e1af6080eeae1e4817825561539d4bf001</sign>'.
            '</root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com',
            'amount' => '1000.00'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $result = '<?xml version="1.0" encoding="GBK" ?>'.
            '<root>'.
            '<resp_code>00</resp_code>'.
            '<pay_result>1</pay_result>'.
            '<amount>10.00</amount>'.
            '<curr_code>RMB</curr_code>'.
            '<out_trade_no>201404100013593016</out_trade_no>'.
            '<partner>EC130517YL0251</partner>'.
            '<pay_no>1848210</pay_no>'.
            '<resp_desc>Success</resp_desc>'.
            '<sett_date>20140410</sett_date>'.
            '<sign_type>SHA256</sign_type>'.
            '<version>3.0</version>'.
            '<sign>22a468a52564cf326b8635c1c71507e1af6080eeae1e4817825561539d4bf001</sign>'.
            '</root>';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.epaylink.com',
            'amount' => '10.00'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setContainer($this->container);
        $ePayLinks->setClient($this->client);
        $ePayLinks->setResponse($response);
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTracking();
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->getPaymentTrackingData();
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->getPaymentTrackingData();
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
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($options);
        $ePayLinks->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => 'EC130517YL0251',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.epaylinks.cn',
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($options);
        $trackingData = $ePayLinks->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/paycenter/queryOrder.do', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.www.epaylinks.cn', $trackingData['headers']['Host']);

        $this->assertEquals('EC130517YL0251', $trackingData['form']['partner']);
        $this->assertEquals('201404100013593016', $trackingData['form']['out_trade_no']);

        $sign = 'fc3fe2a190559eb5912946357ac11d7f81829ea0ce82b3236a832f128ad46806';
        $this->assertEquals($sign, $trackingData['form']['sign']);
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

        $ePayLinks = new EPayLinks();
        $ePayLinks->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數resp_code
     */
    public function testPaymentTrackingVerifyWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="UTF-8" ?><root></root>';
        $sourceData = ['content' => $content];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗(resp_code回傳非00)
     */
    public function testPaymentTrackingVerifyPaymentFailureWithRespCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root><resp_code>10</resp_code></root>';
        $sourceData = ['content' => $content];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數pay_result
     */
    public function testPaymentTrackingVerifyWithoutPayResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root><resp_code>00</resp_code></root>';
        $sourceData = ['content' => $content];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗(PayResult回傳非1)
     */
    public function testPaymentTrackingVerifyPaymentFailureWithPayResultError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<resp_code>00</resp_code>' .
            '<pay_result>0</pay_result>' .
            '</root>';
        $sourceData = ['content' => $content];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<resp_code>00</resp_code>' .
            '<pay_result>1</pay_result>' .
            '</root>';
        $sourceData = ['content' => $content];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<resp_code>00</resp_code>' .
            '<pay_result>1</pay_result>' .
            '<amount>10.00</amount>' .
            '<curr_code>RMB</curr_code>' .
            '<out_trade_no>201404100013593016</out_trade_no>' .
            '<partner>EC130517YL0251</partner>' .
            '<pay_no>1848210</pay_no>' .
            '<resp_desc>Success</resp_desc>' .
            '<sett_date>20140410</sett_date>' .
            '<sign_type>SHA256</sign_type>' .
            '<version>3.0</version>' .
            '</root>';
        $sourceData = ['content' => $content];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
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

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<resp_code>00</resp_code>' .
            '<pay_result>1</pay_result>' .
            '<amount>10.00</amount>' .
            '<curr_code>RMB</curr_code>' .
            '<out_trade_no>201404100013593016</out_trade_no>' .
            '<partner>EC130517YL0251</partner>' .
            '<pay_no>1848210</pay_no>' .
            '<resp_desc>success</resp_desc>' .
            '<sett_date>20140410</sett_date>' .
            '<sign_type>SHA256</sign_type>' .
            '<version>3.0</version>' .
            '<sign>22a468a52564cf326b8635c1c71507e1af6080eeae1e4817825561539d4bf001</sign>' .
            '</root>';
        $sourceData = ['content' => $content];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<resp_code>00</resp_code>' .
            '<pay_result>1</pay_result>' .
            '<amount>10.00</amount>' .
            '<curr_code>RMB</curr_code>' .
            '<out_trade_no>201404100013593016</out_trade_no>' .
            '<partner>EC130517YL0251</partner>' .
            '<pay_no>1848210</pay_no>' .
            '<resp_desc>Success</resp_desc>' .
            '<sett_date>20140410</sett_date>' .
            '<sign_type>SHA256</sign_type>' .
            '<version>3.0</version>' .
            '<sign>22a468a52564cf326b8635c1c71507e1af6080eeae1e4817825561539d4bf001</sign>' .
            '</root>';
        $sourceData = [
            'content' => $content,
            'amount' => '1000.00'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<resp_code>00</resp_code>' .
            '<pay_result>1</pay_result>' .
            '<amount>10.00</amount>' .
            '<curr_code>RMB</curr_code>' .
            '<out_trade_no>201404100013593016</out_trade_no>' .
            '<partner>EC130517YL0251</partner>' .
            '<pay_no>1848210</pay_no>' .
            '<resp_desc>Success</resp_desc>' .
            '<sett_date>20140410</sett_date>' .
            '<sign_type>SHA256</sign_type>' .
            '<version>3.0</version>' .
            '<sign>22a468a52564cf326b8635c1c71507e1af6080eeae1e4817825561539d4bf001</sign>' .
            '</root>';
        $sourceData = [
            'content' => $content,
            'amount' => '10.00'
        ];

        $ePayLinks = new EPayLinks();
        $ePayLinks->setPrivateKey('4f592c37908ac35a5fd30df2a6c8acc7');
        $ePayLinks->setOptions($sourceData);
        $ePayLinks->paymentTrackingVerify();
    }
}
