<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\LLPay;
use Buzz\Message\Response;

class LLPayTest extends DurianTestCase
{
    /**
     * 此部分用於需要取得MerchantExtra資料的時候
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 此部分用於需要取得MerchantExtra資料的時候
     */
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

        $LLPay = new LLPay();
        $LLPay->getVerifyData();
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

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = ['number' => ''];

        $LLPay->setOptions($sourceData);
        $LLPay->getVerifyData();
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

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'number' => '201405122000003615',
            'username' => 'hikaru',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=21347&hallid=243',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '201405122000003615',
            'username' => 'hikaru',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'merchant_extra' => ['businessType' => '101001'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');
        $LLPay->setOptions($sourceData);
        $encodeData = $LLPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['oid_partner']);
        $this->assertEquals($sourceData['username'], $encodeData['user_id']);
        $this->assertEquals($sourceData['orderId'], $encodeData['no_order']);
        $this->assertEquals('20140516164717', $encodeData['dt_order']);
        $this->assertEquals('20140516164717', $encodeData['timestamp']);
        $this->assertSame('0.01', $encodeData['money_order']);
        $this->assertEquals($notifyUrl, $encodeData['notify_url']);
        $this->assertEquals($sourceData['ip'], $encodeData['userreq_ip']);
        $this->assertEquals('01020000', $encodeData['bank_code']);
        $this->assertEquals('d75c8c7f9b24975c5b7876413d299ed6', $encodeData['sign']);
    }

    /**
     * 測試加密,找不到商家的businessType附加設定值
     */
    public function testGetEncodeDataButNoBusinessTypeSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'number' => '201405122000003615',
            'username' => 'hikaru',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'amount' => '0.01',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=21347&hallid=243',
            'ip' => '111.235.135.3',
            'paymentVendorId' => '1',
            'merchant_extra' => [],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->getVerifyData();
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

        $LLPay = new LLPay();

        $LLPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'dt_order'    => '20140516164717',
            'money_order' => '0.01',
            'no_order'    => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type'    => '8',
            'settle_date' => '20140516',
            'sign'        => '0d432d2fcbfb375326b0741a00e44c8f',
            'sign_type'   => 'MD5'
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->verifyOrderPayment([]);
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

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'dt_order'    => '20140516164717',
            'money_order' => '0.01',
            'no_order'    => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type'    => '8',
            'result_pay'  => 'SUCCESS',
            'settle_date' => '20140516',
            'sign_type'   => 'MD5'
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->verifyOrderPayment([]);
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

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'dt_order'    => '20140516164717',
            'money_order' => '0.01',
            'no_order'    => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type'    => '8',
            'result_pay'  => 'SUCCESS',
            'settle_date' => '20140516',
            'sign'        => '26b0741a00e44c8f0d432d2fcbfb3753',
            'sign_type'   => 'MD5'
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->verifyOrderPayment([]);
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

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'dt_order'    => '20140516164717',
            'money_order' => '0.01',
            'no_order'    => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type'    => '8',
            'result_pay'  => 'FAILURE',
            'settle_date' => '20140516',
            'sign'        => 'ff8cfc166a052db9c0c73823b25b1138',
            'sign_type'   => 'MD5'
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'dt_order'    => '20140516164717',
            'money_order' => '0.01',
            'no_order'    => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type'    => '8',
            'result_pay'  => 'SUCCESS',
            'settle_date' => '20140516',
            'sign'        => '0d432d2fcbfb375326b0741a00e44c8f',
            'sign_type'   => 'MD5'
        ];

        $entry = ['id' => '20140102030405006'];

        $LLPay->setOptions($sourceData);
        $LLPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'dt_order'    => '20140516164717',
            'money_order' => '0.01',
            'no_order'    => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type'    => '8',
            'result_pay'  => 'SUCCESS',
            'settle_date' => '20140516',
            'sign'        => '0d432d2fcbfb375326b0741a00e44c8f',
            'sign_type'   => 'MD5'
        ];

        $entry = [
            'id' => '201405160000000042',
            'amount' => '0.1000'
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccessBySynchronous()
    {
        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'dt_order'    => '20140516164717',
            'money_order' => '0.01',
            'no_order'    => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type'    => '8',
            'result_pay'  => 'SUCCESS',
            'settle_date' => '20140516',
            'sign'        => '0d432d2fcbfb375326b0741a00e44c8f',
            'sign_type'   => 'MD5'
        ];

        $entry = [
            'id' => '201405160000000042',
            'amount' => '0.0100'
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->verifyOrderPayment($entry);

        $this->assertEquals('{"ret_code":"0000","ret_msg":"交易成功"}', $LLPay->getMsg());
    }

    /**
     * 測試支付驗證成功(同步返回)
     */
    public function testPaySuccessByAsynchronous()
    {
        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');

        $sourceData = [
            'oid_partner' => '201405122000003615',
            'sign_type'   => 'MD5',
            'sign'        => '9551f19de913bca449b9ba1e8543da82',
            'dt_order'    => '20140516164717',
            'no_order'    => '201405160000000042',
            'oid_paybill' => '2014051603082943',
            'money_order' => '0.01',
            'result_pay'  => 'SUCCESS',
            'settle_date' => '20140516',
            'info_order'  => '',
            'pay_type'    => '',
            'bank_code'   => '01020000'
        ];

        $entry = [
            'id' => '201405160000000042',
            'amount' => '0.0100'
        ];

        $LLPay->setOptions($sourceData);
        $LLPay->verifyOrderPayment($entry);

        $this->assertEquals('{"ret_code":"0000","ret_msg":"交易成功"}', $LLPay->getMsg());
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

        $LLPay = new LLPay();
        $LLPay->paymentTracking();
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

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('acctest');
        $LLPay->paymentTracking();
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
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $LLPay = new LLPay();
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數result_pay
     */
    public function testPaymentTrackingResultWithoutResultPay()
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
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
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

        $params = ['result_pay' => 'WAITING'];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
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

        $params = ['result_pay' => 'PROCESSING'];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為已退款
     */
    public function testTrackingReturnOrderRefunded()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order has been refunded',
            180078
        );

        $params = ['result_pay' => 'REFUND'];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
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

        $params = ['result_pay' => 'FAILURE'];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
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

        $params = ['result_pay' => 'SUCCESS'];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
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
            'result_pay' => 'SUCCESS',
            'sign' => 'dadbd44fe7ce848732647d65551e322d'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
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
            'bank_code' => '01020000',
            'bank_name' => '中国工商银行',
            'dt_order' => '20140516164717',
            'money_order' => '0.01',
            'no_order' => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type' => '8',
            'result_pay' => 'SUCCESS',
            'ret_code' => '0000',
            'ret_msg' => '交易成功',
            'settle_date' => '20140516',
            'sign' => '629bec15708b1180facc47b0e841dc5f',
            'sign_type' => 'MD5'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com',
            'amount' => '1000.00'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = [
            'bank_code' => '01020000',
            'bank_name' => '中国工商银行',
            'dt_order' => '20140516164717',
            'money_order' => '0.01',
            'no_order' => '201405160000000042',
            'oid_partner' => '201405122000003615',
            'oid_paybill' => '2014051603082943',
            'pay_type' => '8',
            'result_pay' => 'SUCCESS',
            'ret_code' => '0000',
            'ret_msg' => '交易成功',
            'settle_date' => '20140516',
            'sign' => '629bec15708b1180facc47b0e841dc5f',
            'sign_type' => 'MD5'
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '201405122000003615',
            'orderId' => '201405160000000042',
            'orderCreateDate' => '2014-05-16 16:47:17',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.llpay.com',
            'amount' => '0.01'
        ];

        $LLPay = new LLPay();
        $LLPay->setContainer($this->container);
        $LLPay->setClient($this->client);
        $LLPay->setResponse($response);
        $LLPay->setPrivateKey('e60f64c0b5924000974dafeb3fe11c83');
        $LLPay->setOptions($sourceData);
        $LLPay->paymentTracking();
    }
}
