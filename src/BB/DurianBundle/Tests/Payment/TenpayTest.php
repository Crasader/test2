<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Tenpay;
use Buzz\Message\Response;

class TenpayTest extends DurianTestCase
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

        $tenPay = new Tenpay();
        $tenPay->getVerifyData();
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

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');

        $sourceData = ['number' => ''];

        $tenPay->setOptions($sourceData);
        $tenPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '1212600101',
            'orderId' => '201401020000717444',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'test',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($sourceData);
        $encodeData = $tenPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('5f6a1953c9a7571cf61514167d5e360d', $encodeData['sign']);
        $this->assertEquals('1212600101', $encodeData['bargainor_id']);
        $this->assertEquals('201401020000717444', $encodeData['sp_billno']);
        $this->assertEquals('1', $encodeData['total_fee']);
        $this->assertEquals($notifyUrl, $encodeData['return_url']);
        $this->assertEquals('test', $encodeData['desc']);
        $this->assertEquals('20140612', $encodeData['date']);
        $this->assertEquals('127.0.0.1', $encodeData['spbill_create_ip']);
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

        $tenPay = new Tenpay();

        $tenPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');

        $sourceData = [
            'cmdno' => '1',
            'pay_result' => '0',
            'bargainor_id' => '1212600101',
            'date' => '20140612',
            'transaction_id' => '',
            'total_fee' => '1',
            'fee_type' => '1',
            'attach' => '1'
        ];

        $tenPay->setOptions($sourceData);
        $tenPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少加密簽名(Sign)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');

        $sourceData = [
            'cmdno' => '1',
            'pay_result' => '0',
            'bargainor_id' => '1212600101',
            'date' => '20140612',
            'transaction_id' => '',
            'sp_billno' => '201401020000717444',
            'total_fee' => '1',
            'fee_type' => '1',
            'attach' => '1'
        ];

        $tenPay->setOptions($sourceData);
        $tenPay->verifyOrderPayment([]);
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

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');

        $sourceData = [
            'cmdno' => '1',
            'pay_result' => '0',
            'brgainor_id' => '1212600101',
            'date' => '20140612',
            'transaction_id' => '',
            'sp_billno' => '201401020000717444',
            'total_fee' => '1',
            'fee_type' => '1',
            'attach' => '1',
            'sign' => '417472e2396808e3c9fb54529000667'
        ];

        $tenPay->setOptions($sourceData);
        $tenPay->verifyOrderPayment([]);
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

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');

        $sourceData = [
            'cmdno' => '1',
            'pay_result' => '1',
            'bargainor_id' => '1212600101',
            'date' => '20140612',
            'transaction_id' => '',
            'sp_billno' => '201401020000717444',
            'total_fee' => '1',
            'fee_type' => '1',
            'attach' => '1',
            'sign' => '1A5AF4C9CE423F85D876BB712AB23B9E'
        ];

        $tenPay->setOptions($sourceData);
        $tenPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');

        $sourceData = [
            'cmdno' => '1',
            'pay_result' => '0',
            'brgainor_id' => '1212600101',
            'date' => '20140612',
            'transaction_id' => '',
            'sp_billno' => '201401020000717444',
            'total_fee' => '1',
            'fee_type' => '1',
            'attach' => '1',
            'sign' => 'B4DD3AC48643A4C85CE4C887BBE8FF24'
        ];

        $entry = ['id' => '6104193835'];

        $tenPay->setOptions($sourceData);
        $tenPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');

        $sourceData = [
            'cmdno' => '1',
            'pay_result' => '0',
            'brgainor_id' => '1212600101',
            'date' => '20140612',
            'transaction_id' => '',
            'sp_billno' => '201401020000717444',
            'total_fee' => '1',
            'fee_type' => '1',
            'attach' => '1',
            'sign' => 'B4DD3AC48643A4C85CE4C887BBE8FF24'
        ];

        $entry = [
            'id' => '201401020000717444',
            'amount' => '0.1'
        ];

        $tenPay->setOptions($sourceData);
        $tenPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');

        $sourceData = [
            'cmdno' => '1',
            'pay_result' => '0',
            'brgainor_id' => '1212600101',
            'date' => '20140612',
            'transaction_id' => '',
            'sp_billno' => '201401020000717444',
            'total_fee' => '1',
            'fee_type' => '1',
            'attach' => '1',
            'sign' => 'B4DD3AC48643A4C85CE4C887BBE8FF24'
        ];

        $entry = [
            'id' => '201401020000717444',
            'amount' => '0.01'
        ];

        $tenPay->setOptions($sourceData);
        $tenPay->verifyOrderPayment($entry);

        $this->assertEquals('<meta name="TENCENT_ONLINE_PAYMENT" content="China TENCENT">', $tenPay->getMsg());
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

        $tenPay = new Tenpay();
        $tenPay->paymentTracking();
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

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->paymentTracking();
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
            'number'  => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?><root></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number'  => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.tenpay.com'
        ];

        $tenPay = new Tenpay();
        $tenPay->setContainer($this->container);
        $tenPay->setClient($this->client);
        $tenPay->setResponse($response);
        $tenPay->setPrivateKey('1234');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root><pay_result>999</pay_result></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number'  => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.tenpay.com'
        ];

        $tenPay = new Tenpay();
        $tenPay->setContainer($this->container);
        $tenPay->setClient($this->client);
        $tenPay->setResponse($response);
        $tenPay->setPrivateKey('1234');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root><pay_result>0</pay_result></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number'  => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.tenpay.com'
        ];

        $tenPay = new Tenpay();
        $tenPay->setContainer($this->container);
        $tenPay->setClient($this->client);
        $tenPay->setResponse($response);
        $tenPay->setPrivateKey('1234');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<pay_result>0</pay_result>' .
            '<attach>1</attach>' .
            '<bargainor_id>1214382801</bargainor_id>' .
            '<cmdno>2</cmdno>' .
            '<date>20140102</date>' .
            '<fee_type>1</fee_type>' .
            '<pay_info>OK</pay_info>' .
            '<sp_billno>201401020000717444</sp_billno>' .
            '<total_fee>40000</total_fee>' .
            '<transaction_id>1214382801201401020000717444</transaction_id>' .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number'  => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.tenpay.com'
        ];

        $tenPay = new Tenpay();
        $tenPay->setContainer($this->container);
        $tenPay->setClient($this->client);
        $tenPay->setResponse($response);
        $tenPay->setPrivateKey('1234');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<pay_result>0</pay_result>' .
            '<attach>1</attach>' .
            '<bargainor_id>1214382801</bargainor_id>' .
            '<cmdno>2</cmdno>' .
            '<date>20140102</date>' .
            '<fee_type>1</fee_type>' .
            '<pay_info>OK</pay_info>' .
            '<sp_billno>201401020000717444</sp_billno>' .
            '<total_fee>40000</total_fee>' .
            '<transaction_id>1214382801201401020000717444</transaction_id>' .
            '<sign>EDCEAD361C4DB6022E76B901F664083E</sign>' .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number'  => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.tenpay.com'
        ];

        $tenPay = new Tenpay();
        $tenPay->setContainer($this->container);
        $tenPay->setClient($this->client);
        $tenPay->setResponse($response);
        $tenPay->setPrivateKey('1234');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<pay_result>0</pay_result>' .
            '<attach>1</attach>' .
            '<bargainor_id>1214382801</bargainor_id>' .
            '<cmdno>2</cmdno>' .
            '<date>20140102</date>' .
            '<fee_type>1</fee_type>' .
            '<pay_info>OK</pay_info>' .
            '<sp_billno>201401020000717444</sp_billno>' .
            '<total_fee>40000</total_fee>' .
            '<transaction_id>1214382801201401020000717444</transaction_id>' .
            '<sign>EDCEAD361C4DB6022E76B901F664083E</sign>' .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number'  => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.tenpay.com',
            'amount' => '1.234'
        ];

        $tenPay = new Tenpay();
        $tenPay->setContainer($this->container);
        $tenPay->setClient($this->client);
        $tenPay->setResponse($response);
        $tenPay->setPrivateKey('06ffc412fd2c534b1dae92c0c015bali');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<pay_result>0</pay_result>' .
            '<attach>1</attach>' .
            '<bargainor_id>1214382801</bargainor_id>' .
            '<cmdno>2</cmdno>' .
            '<date>20140102</date>' .
            '<fee_type>1</fee_type>' .
            '<pay_info>OK</pay_info>' .
            '<sp_billno>201401020000717444</sp_billno>' .
            '<total_fee>40000</total_fee>' .
            '<transaction_id>1214382801201401020000717444</transaction_id>' .
            '<sign>EDCEAD361C4DB6022E76B901F664083E</sign>' .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number'  => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.tenpay.com',
            'amount' => '400.00'
        ];

        $tenPay = new Tenpay();
        $tenPay->setContainer($this->container);
        $tenPay->setClient($this->client);
        $tenPay->setResponse($response);
        $tenPay->setPrivateKey('06ffc412fd2c534b1dae92c0c015bali');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTracking();
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

        $tenPay = new Tenpay();
        $tenPay->getPaymentTrackingData();
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

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->getPaymentTrackingData();
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
            'number' => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($options);
        $tenPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '1212600101',
            'orderId' => '201401020000717444',
            'orderCreateDate' => '2014-06-12 00:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.mch.tenpay.com',
        ];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($options);
        $trackingData = $tenPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/cgi-bin/cfbi_query_order_v3.cgi', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.mch.tenpay.com', $trackingData['headers']['Host']);

        $this->assertEquals('2', $trackingData['form']['cmdno']);
        $this->assertEquals('20140612', $trackingData['form']['date']);
        $this->assertEquals('1212600101', $trackingData['form']['bargainor_id']);
        $this->assertEquals('1212600101201401020000717444', $trackingData['form']['transaction_id']);
        $this->assertEquals('eda65b75fefc25c8521e31cfd0be5df6', $trackingData['form']['sign']);
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

        $tenPay = new Tenpay();
        $tenPay->paymentTrackingVerify();
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

        $content = '<?xml version="1.0" encoding="UTF-8" ?><root></root>';
        $sourceData = ['content' => $content];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTrackingVerify();
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

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root><pay_result>999</pay_result></root>';
        $sourceData = ['content' => $content];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root><pay_result>0</pay_result></root>';
        $sourceData = ['content' => $content];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單缺少回傳參數sign
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
            '<pay_result>0</pay_result>' .
            '<attach>1</attach>' .
            '<bargainor_id>1214382801</bargainor_id>' .
            '<cmdno>2</cmdno>' .
            '<date>20140102</date>' .
            '<fee_type>1</fee_type>' .
            '<pay_info>OK</pay_info>' .
            '<sp_billno>201401020000717444</sp_billno>' .
            '<total_fee>40000</total_fee>' .
            '<transaction_id>1214382801201401020000717444</transaction_id>' .
            '</root>';
        $sourceData = ['content' => $content];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單簽名驗證錯誤
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
            '<pay_result>0</pay_result>' .
            '<attach>1</attach>' .
            '<bargainor_id>1214382801</bargainor_id>' .
            '<cmdno>2</cmdno>' .
            '<date>20140102</date>' .
            '<fee_type>1</fee_type>' .
            '<pay_info>OK</pay_info>' .
            '<sp_billno>201401020000717444</sp_billno>' .
            '<total_fee>40000</total_fee>' .
            '<transaction_id>1214382801201401020000717444</transaction_id>' .
            '<sign>EDCEAD361C4DB6022E76B901F664083E</sign>' .
            '</root>';
        $sourceData = ['content' => $content];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('b4c881732169515577e42abdadec50b3');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單帶入金額不正確
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
            '<pay_result>0</pay_result>' .
            '<attach>1</attach>' .
            '<bargainor_id>1214382801</bargainor_id>' .
            '<cmdno>2</cmdno>' .
            '<date>20140102</date>' .
            '<fee_type>1</fee_type>' .
            '<pay_info>OK</pay_info>' .
            '<sp_billno>201401020000717444</sp_billno>' .
            '<total_fee>40000</total_fee>' .
            '<transaction_id>1214382801201401020000717444</transaction_id>' .
            '<sign>EDCEAD361C4DB6022E76B901F664083E</sign>' .
            '</root>';
        $sourceData = [
            'content' => $content,
            'amount' => '1.234'
        ];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('06ffc412fd2c534b1dae92c0c015bali');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<root>' .
            '<pay_result>0</pay_result>' .
            '<attach>1</attach>' .
            '<bargainor_id>1214382801</bargainor_id>' .
            '<cmdno>2</cmdno>' .
            '<date>20140102</date>' .
            '<fee_type>1</fee_type>' .
            '<pay_info>OK</pay_info>' .
            '<sp_billno>201401020000717444</sp_billno>' .
            '<total_fee>40000</total_fee>' .
            '<transaction_id>1214382801201401020000717444</transaction_id>' .
            '<sign>EDCEAD361C4DB6022E76B901F664083E</sign>' .
            '</root>';
        $sourceData = [
            'content' => $content,
            'amount' => '400.00'
        ];

        $tenPay = new Tenpay();
        $tenPay->setPrivateKey('06ffc412fd2c534b1dae92c0c015bali');
        $tenPay->setOptions($sourceData);
        $tenPay->paymentTrackingVerify();
    }
}
