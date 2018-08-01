<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KuaiYin;
use Buzz\Message\Response;

class KuaiYinTest extends DurianTestCase
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
    public function testEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kuaiYin = new KuaiYin();
        $kuaiYin->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定返回參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = ['number' => ''];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->getVerifyData();
    }

    /**
     * 測試加密基本參數設定沒有帶入merchantId的情況
     */
    public function testEncodeWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'number' => '80140311172356932106',
            'orderId' => '2014072500000001',
            'amount' => '0.05',
            'notify_url' => 'http://118.232.50.208/return.php',
            'orderCreateDate' => '2014-07-25 07:45:00',
            'paymentVendorId' => '1',
            'merchantId' => '',
            'domain' => '',
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->getVerifyData();
    }

    /**
     * 測試加密基本參數設定沒有帶入domain的情況
     */
    public function testEncodeWithoutDomain()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'number' => '80140311172356932106',
            'orderId' => '2014072500000001',
            'amount' => '0.05',
            'notify_url' => 'http://118.232.50.208/return.php',
            'orderCreateDate' => '2014-07-25 07:45:00',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '',
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testEncodeWithouttSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'number' => '80140311172356932106',
            'orderId' => '2014072500000001',
            'amount' => '0.05',
            'notify_url' => 'http://118.232.50.208/return.php',
            'orderCreateDate' => '2014-07-25 07:45:00',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $sourceData = [
            'number' => '80140311172356932106',
            'orderId' => '2014072500000001',
            'amount' => '0.05',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderCreateDate' => '2014-07-25 07:45:00',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $encodeData = $kuaiYin->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['merchant_id']);
        $this->assertEquals($sourceData['orderId'], $encodeData['order_id']);
        $this->assertSame('0.05', $encodeData['amount']);
        $this->assertEquals($notifyUrl, $encodeData['merchant_url']);
        $this->assertEquals('20140725074500', $encodeData['order_time']);
        $this->assertEquals('ICBC', $encodeData['bank_code']);
        $this->assertEquals('12345_6', $encodeData['cust_param']);
        $this->assertEquals('F9401DCB5E1E35848CB07C8288B638A2', $encodeData['sign_msg']);
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

        $kuaiYin = new KuaiYin();

        $kuaiYin->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testDecodePaymentReplyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'account_date'     => '20140725',
            'bank_order_id'    => '20140725074023486593',
            'code'             => '0',
            'cust_param'       => '12345_6',
            'deal_id'          => '5738853',
            'deal_time'        => '20140725074219',
            'kuaiyin_order_id' => '63140725074343383991',
            'merchant_id'      => '80140311172356932106',
            'order_id'         => '2014072500000001',
            'order_time'       => '20140725074500',
            'result'           => 'Y',
            'signMsg'          => '8A3B289BC354255AC502240418D57A87',
            'version'          => '1.0.0'
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台沒有回傳signMsg(加密簽名)
     */
    public function testDecodePaymentReplyWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'account_date'     => '20140725',
            'bank_order_id'    => '20140725074023486593',
            'code'             => '0',
            'cust_param'       => '12345_6',
            'deal_id'          => '5738853',
            'deal_time'        => '20140725074219',
            'kuaiyin_order_id' => '63140725074343383991',
            'merchant_id'      => '80140311172356932106',
            'order_id'         => '2014072500000001',
            'order_time'       => '20140725074500',
            'paid_amount'      => '0.05',
            'result'           => 'Y',
            'version'          => '1.0.0'
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->verifyOrderPayment([]);
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

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'account_date'     => '20140725',
            'bank_order_id'    => '20140725074023486593',
            'code'             => '0',
            'cust_param'       => '12345_6',
            'deal_id'          => '5738853',
            'deal_time'        => '20140725074219',
            'kuaiyin_order_id' => '63140725074343383991',
            'merchant_id'      => '80140311172356932106',
            'order_id'         => '2014072500000001',
            'order_time'       => '20140725074500',
            'paid_amount'      => '0.05',
            'result'           => 'Y',
            'signMsg'          => '502240418D57A878A3B289BC354255AC',
            'version'          => '1.0.0'
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->verifyOrderPayment([]);
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

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'account_date'     => '20140725',
            'bank_order_id'    => '20140725074023486593',
            'code'             => '0',
            'cust_param'       => '12345_6',
            'deal_id'          => '5738853',
            'deal_time'        => '20140725074219',
            'kuaiyin_order_id' => '63140725074343383991',
            'merchant_id'      => '80140311172356932106',
            'order_id'         => '2014072500000001',
            'order_time'       => '20140725074500',
            'paid_amount'      => '0.05',
            'result'           => 'N',
            'signMsg'          => '3C7B6BD8BDB2E29097812822457F93AD',
            'version'          => '1.0.0'
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗(回傳Code不為0)
     */
    public function testReturnPaymentFailureWithCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'account_date'     => '20140725',
            'bank_order_id'    => '20140725074023486593',
            'code'             => '00',
            'cust_param'       => '12345_6',
            'deal_id'          => '5738853',
            'deal_time'        => '20140725074219',
            'kuaiyin_order_id' => '63140725074343383991',
            'merchant_id'      => '80140311172356932106',
            'order_id'         => '2014072500000001',
            'order_time'       => '20140725074500',
            'paid_amount'      => '0.05',
            'result'           => 'Y',
            'signMsg'          => '8DFCEB0F7033D642820B4701ECD24E6C',
            'version'          => '1.0.0'
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'account_date'     => '20140725',
            'bank_order_id'    => '20140725074023486593',
            'code'             => '0',
            'cust_param'       => '12345_6',
            'deal_id'          => '5738853',
            'deal_time'        => '20140725074219',
            'kuaiyin_order_id' => '63140725074343383991',
            'merchant_id'      => '80140311172356932106',
            'order_id'         => '2014072500000001',
            'order_time'       => '20140725074500',
            'paid_amount'      => '0.05',
            'result'           => 'Y',
            'signMsg'          => '8A3B289BC354255AC502240418D57A87',
            'version'          => '1.0.0'
        ];

        $entry = ['id' => '20140625000000333'];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'account_date'     => '20140725',
            'bank_order_id'    => '20140725074023486593',
            'code'             => '0',
            'cust_param'       => '12345_6',
            'deal_id'          => '5738853',
            'deal_time'        => '20140725074219',
            'kuaiyin_order_id' => '63140725074343383991',
            'merchant_id'      => '80140311172356932106',
            'order_id'         => '2014072500000001',
            'order_time'       => '20140725074500',
            'paid_amount'      => '0.05',
            'result'           => 'Y',
            'signMsg'          => '8A3B289BC354255AC502240418D57A87',
            'version'          => '1.0.0'
        ];

        $entry = [
            'id' => '2014072500000001',
            'amount' => '0.0100'
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');

        $sourceData = [
            'account_date'     => '20140725',
            'bank_order_id'    => '20140725074023486593',
            'code'             => '0',
            'cust_param'       => '12345_6',
            'deal_id'          => '5738853',
            'deal_time'        => '20140725074219',
            'kuaiyin_order_id' => '63140725074343383991',
            'merchant_id'      => '80140311172356932106',
            'order_id'         => '2014072500000001',
            'order_time'       => '20140725074500',
            'paid_amount'      => '0.05',
            'result'           => 'Y',
            'signMsg'          => '8A3B289BC354255AC502240418D57A87',
            'version'          => '1.0.0'
        ];

        $entry = [
            'id' => '2014072500000001',
            'amount' => '0.0500'
        ];

        $kuaiYin->setOptions($sourceData);
        $kuaiYin->verifyOrderPayment($entry);

        $this->assertEquals('0000|', $kuaiYin->getMsg());
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

        $kuaiYin = new KuaiYin();
        $kuaiYin->paymentTracking();
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

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->paymentTracking();
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
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API></KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
    }


    /**
     * 測試訂單查詢結果提交的加密簽名錯誤
     */
    public function testPaymentTrackingResultSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>-4</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果提交的參數錯誤
     */
    public function testPaymentTrackingResultSubmitTheParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>-3</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付平台系統錯誤
     */
    public function testPaymentTrackingResultSystemError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'System error, please try again later or contact customer service',
            180076
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>-2</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為請求服務失敗
     */
    public function testTrackingReturnConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Connection error, please try again later or contact customer service',
            180077
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>-1</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>100001</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付時間超時
     */
    public function testTrackingReturnPaidTimeOut()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Paid time out',
            180079
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>100002</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>100003</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>99999</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>0</code>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>0</code>'.
            '<merchant_id>80140311172356932106</merchant_id>'.
            '<cust_id>0</cust_id>'.
            '<order_id>2014072500000001</order_id>'.
            '<order_date>20140725</order_date>'.
            '<order_amount>5</order_amount>'.
            '<paid_amount>5</paid_amount>'.
            '<is_refund>N</is_refund>'.
            '<result>N</result>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>0</code>'.
            '<merchant_id>80140311172356932106</merchant_id>'.
            '<cust_id>0</cust_id>'.
            '<order_id>2014072500000001</order_id>'.
            '<order_date>20140725</order_date>'.
            '<order_amount>5</order_amount>'.
            '<paid_amount>5</paid_amount>'.
            '<is_refund>N</is_refund>'.
            '<result>Y</result>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>0</code>'.
            '<merchant_id>80140311172356932106</merchant_id>'.
            '<cust_id>0</cust_id>'.
            '<order_id>2014072500000001</order_id>'.
            '<order_date>20140825</order_date>'.
            '<order_amount>5</order_amount>'.
            '<paid_amount>5</paid_amount>'.
            '<is_refund>N</is_refund>'.
            '<result>Y</result>'.
            '<signMsg>882D5FC3E0114E51BFE91D4E44B3730B</signMsg>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>0</code>'.
            '<merchant_id>80140311172356932106</merchant_id>'.
            '<cust_id>0</cust_id>'.
            '<order_id>2014072500000001</order_id>'.
            '<order_date>20140725</order_date>'.
            '<order_amount>5</order_amount>'.
            '<paid_amount>5</paid_amount>'.
            '<is_refund>N</is_refund>'.
            '<result>Y</result>'.
            '<signMsg>882D5FC3E0114E51BFE91D4E44B3730B</signMsg>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com',
            'amount' => '1000.00'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = '<?xml version="1.0" encoding="utf-8"?>'.
            '<KUAIYIN_BACK_API>'.
            '<code>0</code>'.
            '<merchant_id>80140311172356932106</merchant_id>'.
            '<cust_id>0</cust_id>'.
            '<order_id>2014072500000001</order_id>'.
            '<order_date>20140725</order_date>'.
            '<order_amount>5</order_amount>'.
            '<paid_amount>5</paid_amount>'.
            '<is_refund>N</is_refund>'.
            '<result>Y</result>'.
            '<signMsg>882D5FC3E0114E51BFE91D4E44B3730B</signMsg>'.
            '</KUAIYIN_BACK_API>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kuaiyin.com',
            'amount' => '0.05'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setContainer($this->container);
        $kuaiYin->setClient($this->client);
        $kuaiYin->setResponse($response);
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTracking();
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

        $kuaiYin = new KuaiYin();
        $kuaiYin->getPaymentTrackingData();
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

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->getPaymentTrackingData();
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
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($options);
        $kuaiYin->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'orderId' => '2014072500000001',
            'number' => '80140311172356932106',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.kuaiyinpay.com',
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($options);
        $trackingData = $kuaiYin->getPaymentTrackingData();

        $path = '/kuaiyinAPI/inquiryOrder/merchantOrderId/' .
            '80140311172356932106/2014072500000001/CF212B752E70F040E5698C4F0FA1072D?' .
            'mer_order_id=2014072500000001&merchant_id=80140311172356932106&' .
            'sign_msg=CF212B752E70F040E5698C4F0FA1072D';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('payment.http.payment.kuaiyinpay.com', $trackingData['headers']['Host']);
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

        $kuaiYin = new KuaiYin();
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數Code
     */
    public function testPaymentTrackingVerifyWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API></KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢提交的加密簽名錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>-4</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢提交的參數錯誤
     */
    public function testPaymentTrackingVerifySubmitTheParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>-3</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但系統錯誤
     */
    public function testPaymentTrackingVerifySystemError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'System error, please try again later or contact customer service',
            180076
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>-2</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但請求服務失敗
     */
    public function testPaymentTrackingVerifyConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Connection error, please try again later or contact customer service',
            180077
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>-1</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
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

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>100001</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付時間超時
     */
    public function testPaymentTrackingVerifyPaidTimeOut()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Paid time out',
            180079
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>100002</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
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

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>100003</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單查詢失敗
     */
    public function testPaymentTrackingVerifyPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>99999</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>0</code>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
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

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>0</code>' .
            '<merchant_id>80140311172356932106</merchant_id>' .
            '<cust_id>0</cust_id>' .
            '<order_id>2014072500000001</order_id>' .
            '<order_date>20140725</order_date>' .
            '<order_amount>5</order_amount>' .
            '<paid_amount>5</paid_amount>' .
            '<is_refund>N</is_refund>' .
            '<result>N</result>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數signMsg
     */
    public function testPaymentTrackingVerifyWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>0</code>' .
            '<merchant_id>80140311172356932106</merchant_id>' .
            '<cust_id>0</cust_id>' .
            '<order_id>2014072500000001</order_id>' .
            '<order_date>20140725</order_date>' .
            '<order_amount>5</order_amount>' .
            '<paid_amount>5</paid_amount>' .
            '<is_refund>N</is_refund>' .
            '<result>Y</result>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
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

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>0</code>' .
            '<merchant_id>80140311172356932106</merchant_id>' .
            '<cust_id>0</cust_id>' .
            '<order_id>2014072500000001</order_id>' .
            '<order_date>20140825</order_date>' .
            '<order_amount>5</order_amount>' .
            '<paid_amount>5</paid_amount>' .
            '<is_refund>N</is_refund>' .
            '<result>Y</result>' .
            '<signMsg>882D5FC3E0114E51BFE91D4E44B3730B</signMsg>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = ['content' => $content];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
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

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>0</code>' .
            '<merchant_id>80140311172356932106</merchant_id>' .
            '<cust_id>0</cust_id>' .
            '<order_id>2014072500000001</order_id>' .
            '<order_date>20140725</order_date>' .
            '<order_amount>5</order_amount>' .
            '<paid_amount>5</paid_amount>' .
            '<is_refund>N</is_refund>' .
            '<result>Y</result>' .
            '<signMsg>882D5FC3E0114E51BFE91D4E44B3730B</signMsg>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = [
            'content' => $content,
            'amount' => '1000.00'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<KUAIYIN_BACK_API>' .
            '<code>0</code>' .
            '<merchant_id>80140311172356932106</merchant_id>' .
            '<cust_id>0</cust_id>' .
            '<order_id>2014072500000001</order_id>' .
            '<order_date>20140725</order_date>' .
            '<order_amount>5</order_amount>' .
            '<paid_amount>5</paid_amount>' .
            '<is_refund>N</is_refund>' .
            '<result>Y</result>' .
            '<signMsg>882D5FC3E0114E51BFE91D4E44B3730B</signMsg>' .
            '</KUAIYIN_BACK_API>';
        $sourceData = [
            'content' => $content,
            'amount' => '0.05'
        ];

        $kuaiYin = new KuaiYin();
        $kuaiYin->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $kuaiYin->setOptions($sourceData);
        $kuaiYin->paymentTrackingVerify();
    }
}
