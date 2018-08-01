<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewSmartpay;
use Buzz\Message\Response;

class NewSmartpayTest extends DurianTestCase
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
     * 測試加密時沒有帶入privateKey的情況
     */
    public function testEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newsmartpay = new NewSmartpay();
        $newsmartpay->getVerifyData();
    }

    /**
     * 測試加密時未指定返回參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');

        $sourceData = ['number' => ''];

        $newsmartpay->setOptions($sourceData);
        $newsmartpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $sourceData = [
            'number' => '20124252',
            'orderCreateDate' => '2014-07-02 09:32:40',
            'orderId' => '150111',
            'amount' => '10000',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');
        $newsmartpay->setOptions($sourceData);
        $encodeData = $newsmartpay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['merchantId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['tx_no']);
        $this->assertSame(round($sourceData['amount'] * 100, 0), $encodeData['amount']);
        $this->assertEquals('20140702', $encodeData['tx_date']);
        $this->assertEquals('093240', $encodeData['tx_time']);
        $this->assertEquals('', $encodeData['return_url']);
        $this->assertEquals($notifyUrl, $encodeData['notice_url']);
        $this->assertEquals($sourceData['ip'], $encodeData['client_ip']);
        $this->assertEquals($sourceData['orderId'], $encodeData['item_no']);
        $this->assertEquals('E23B5CDE3FD0F9366088AEB973DF6CD8', $encodeData['sign']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testDecodeWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newsmartpay = new NewSmartpay();

        $newsmartpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testDecodeWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');

        $sourceData = [
            'cmd'             => '_webpay',
            'merchantId'      => '20124252',
            'mobile'          => '15921439746',
            'tx_date'         => '20101207',
            'tx_no'           => '150111',
            'tx_params'       => '',
            'temp'            => '1',
            'settlement_date' => '20101207',
            'status'          => '01',
            'desc'            => '',
            'sign'            => '8B1E64AD8650F4B48EC4E6B35C1EA30B'
        ];

        $newsmartpay->setOptions($sourceData);
        $newsmartpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳sign(加密簽名)
     */
    public function testDecodeWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');

        $sourceData = [
            'cmd'             => '_webpay',
            'merchantId'      => '20124252',
            'mobile'          => '15921439746',
            'amount'           => '10000',
            'tx_date'         => '20101207',
            'tx_no'           => '150111',
            'tx_params'       => '',
            'temp'            => '1',
            'settlement_date' => '20101207',
            'status'          => '01',
            'desc'            => ''
        ];

        $newsmartpay->setOptions($sourceData);
        $newsmartpay->verifyOrderPayment([]);
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

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');

        $sourceData = [
            'cmd'             => '_webpay',
            'merchantId'      => '20124252',
            'mobile'          => '15921439746',
            'amount'          => '10000',
            'tx_date'         => '20101207',
            'tx_no'           => '150111',
            'tx_params'       => '',
            'temp'            => '1',
            'settlement_date' => '20101207',
            'status'          => '01',
            'desc'            => '',
            'sign'            => 'x'
        ];

        $newsmartpay->setOptions($sourceData);
        $newsmartpay->verifyOrderPayment([]);
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

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');

        $sourceData = [
            'cmd'             => '_webpay',
            'merchantId'      => '20124252',
            'mobile'          => '15921439746',
            'amount'          => '10000',
            'tx_date'         => '20101207',
            'tx_no'           => '150111',
            'tx_params'       => '',
            'temp'            => '1',
            'settlement_date' => '20101207',
            'status'          => '02',
            'desc'            => '',
            'sign'            => '18419EC48C3B41D2DBC426DDAD58B804'
        ];

        $newsmartpay->setOptions($sourceData);
        $newsmartpay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');

        $sourceData = [
            'cmd'             => '_webpay',
            'merchantId'      => '20124252',
            'mobile'          => '15921439746',
            'amount'          => '10000',
            'tx_date'         => '20101207',
            'tx_no'           => '150111',
            'tx_params'       => '',
            'temp'            => '1',
            'settlement_date' => '20101207',
            'status'          => '01',
            'desc'            => '',
            'sign'            => '8B1E64AD8650F4B48EC4E6B35C1EA30B'
        ];

        $entry = ['id' => '19990720'];

        $newsmartpay->setOptions($sourceData);
        $newsmartpay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');

        $sourceData = [
            'cmd'             => '_webpay',
            'merchantId'      => '20124252',
            'mobile'          => '15921439746',
            'amount'          => '10000',
            'tx_date'         => '20101207',
            'tx_no'           => '150111',
            'tx_params'       => '',
            'temp'            => '1',
            'settlement_date' => '20101207',
            'status'          => '01',
            'desc'            => '',
            'sign'            => '8B1E64AD8650F4B48EC4E6B35C1EA30B'
        ];

        $entry = [
            'id' => '150111',
            'amount' => '9900.0000'
        ];

        $newsmartpay->setOptions($sourceData);
        $newsmartpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');

        $sourceData = [
            'cmd'             => '_webpay',
            'merchantId'      => '20124252',
            'mobile'          => '15921439746',
            'amount'          => '10000',
            'tx_date'         => '20101207',
            'tx_no'           => '150111',
            'tx_params'       => '',
            'temp'            => '1',
            'settlement_date' => '20101207',
            'status'          => '01',
            'desc'            => '',
            'sign'            => '8B1E64AD8650F4B48EC4E6B35C1EA30B'
        ];

        $entry = [
            'id' => '150111',
            'amount' => '100'
        ];

        $newsmartpay->setOptions($sourceData);
        $newsmartpay->verifyOrderPayment($entry);

        $this->assertEquals('code=200', $newsmartpay->getMsg());
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

        $newsmartpay = new NewSmartpay();
        $newsmartpay->paymentTracking();
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

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');
        $newsmartpay->paymentTracking();
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
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數transactionStatus
     */
    public function testPaymentTrackingResultWithoutTransactionStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = '<topupResult></topupResult>';
        $result = iconv('UTF-8', 'GB2312', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newsmartpay.com'
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setContainer($this->container);
        $newsmartpay->setClient($this->client);
        $newsmartpay->setResponse($response);
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTracking();
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

        $params = '<topupResult>'.
            '<transactionStatus>00</transactionStatus>'.
            '</topupResult>';
        $result = iconv('UTF-8', 'GB2312', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newsmartpay.com'
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setContainer($this->container);
        $newsmartpay->setClient($this->client);
        $newsmartpay->setResponse($response);
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTracking();
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

        $params = '<topupResult>'.
            '<transactionStatus>99</transactionStatus>'.
            '</topupResult>';
        $result = iconv('UTF-8', 'GB2312', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newsmartpay.com'
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setContainer($this->container);
        $newsmartpay->setClient($this->client);
        $newsmartpay->setResponse($response);
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTracking();
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

        $params = '<topupResult>'.
            '<merchantTxSeqNo>20140113161012</merchantTxSeqNo>'.
            '<transactionFactAmount>15</transactionFactAmount>'.
            '<beginTime>2010-07-27 15:39:30</beginTime>'.
            '<transactionStatus>10</transactionStatus>'.
            '</topupResult>';
        $result = iconv('UTF-8', 'GB2312', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '20124252',
            'orderId' => '20140113161012',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newsmartpay.com',
            'amount' => '1.234'
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setContainer($this->container);
        $newsmartpay->setClient($this->client);
        $newsmartpay->setResponse($response);
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = '<topupResult>'.
            '<merchantTxSeqNo>201404100013593016</merchantTxSeqNo>'.
            '<transactionFactAmount>15</transactionFactAmount>'.
            '<beginTime>2010-07-27 15:39:30</beginTime>'.
            '<transactionStatus>10</transactionStatus>'.
            '</topupResult>';
        $result = iconv('UTF-8', 'GB2312', $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '20124252',
            'orderId' => '201404100013593016',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newsmartpay.com',
            'amount' => '0.15'
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setContainer($this->container);
        $newsmartpay->setClient($this->client);
        $newsmartpay->setResponse($response);
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTracking();
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

        $newsmartpay = new NewSmartpay();
        $newsmartpay->getPaymentTrackingData();
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

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');
        $newsmartpay->getPaymentTrackingData();
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
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');
        $newsmartpay->setOptions($options);
        $newsmartpay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '20124252',
            'orderId' => '150111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.172.com'
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('qwsxcvgtfv1258ed');
        $newsmartpay->setOptions($options);
        $trackingData = $newsmartpay->getPaymentTrackingData();

        $path = '/paymentGateway/queryMerchantOrder.htm?user_id=20124252' .
            '&card_no=&card_pswd=&merchant_tx_seq_no=150111&sign=50769CE40B355531CAF41F53902D13C3';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('payment.https.www.172.com', $trackingData['headers']['Host']);
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

        $newsmartpay = new NewSmartpay();
        $newsmartpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數transactionStatus
     */
    public function testPaymentTrackingVerifyWithoutTransactionStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = '<topupResult></topupResult>';
        $content = urlencode(iconv('UTF-8', 'GB2312', $params));
        $sourceData = ['content' => $content];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $params = '<topupResult>' .
            '<transactionStatus>00</transactionStatus>' .
            '</topupResult>';
        $content = urlencode(iconv('UTF-8', 'GB2312', $params));
        $sourceData = ['content' => $content];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTrackingVerify();
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

        $params = '<topupResult>' .
            '<transactionStatus>99</transactionStatus>' .
            '</topupResult>';
        $content = urlencode(iconv('UTF-8', 'GB2312', $params));
        $sourceData = ['content' => $content];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTrackingVerify();
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

        $params = '<topupResult>' .
            '<merchantTxSeqNo>20140113161012</merchantTxSeqNo>' .
            '<transactionFactAmount>15</transactionFactAmount>' .
            '<beginTime>2010-07-27 15:39:30</beginTime>' .
            '<transactionStatus>10</transactionStatus>' .
            '</topupResult>';
        $content = urlencode(iconv('UTF-8', 'GB2312', $params));
        $sourceData = [
            'content' => $content,
            'amount' => '1.234'
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $params = '<topupResult>' .
            '<merchantTxSeqNo>20140113161012</merchantTxSeqNo>' .
            '<transactionFactAmount>15</transactionFactAmount>' .
            '<beginTime>2010-07-27 15:39:30</beginTime>' .
            '<transactionStatus>10</transactionStatus>' .
            '</topupResult>';
        $content = urlencode(iconv('UTF-8', 'GB2312', $params));
        $sourceData = [
            'content' => $content,
            'amount' => '0.15'
        ];

        $newsmartpay = new NewSmartpay();
        $newsmartpay->setPrivateKey('fdsiojosdgdjioioj');
        $newsmartpay->setOptions($sourceData);
        $newsmartpay->paymentTrackingVerify();
    }
}
