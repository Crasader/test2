<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewDinPay;
use Buzz\Message\Response;

class NewDinPayTest extends DurianTestCase
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

        $newDinPay = new NewDinPay();
        $newDinPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = ['number' => ''];

        $newDinPay->setOptions($sourceData);
        $newDinPay->getVerifyData();
    }

    /**
     * 測試加密時代入支付平台不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=49745&hallid=3389593',
            'orderId' => '2014052200001',
            'orderCreateDate' => '2014-05-22 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '999',
            'merchantId' => '49745',
        ];

        $newDinPay->setOptions($sourceData);
        $newDinPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '1111130200',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '2014052200001',
            'orderCreateDate' => '2014-05-22 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'merchantId' => '49745',
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $encodeData = $newDinPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId']
        );

        $this->assertEquals($sourceData['number'], $encodeData['merchant_code']);
        $this->assertEquals($notifyUrl, $encodeData['notify_url']);
        $this->assertEquals($sourceData['orderId'], $encodeData['order_no']);
        $this->assertEquals($sourceData['orderCreateDate'], $encodeData['order_time']);
        $this->assertSame('0.01', $encodeData['order_amount']);
        $this->assertEquals($sourceData['username'], $encodeData['product_name']);
        $this->assertEquals('ICBC', $encodeData['bank_code']);
        $this->assertEquals('1', $encodeData['redo_flag']);
        $this->assertEquals('04b4208ac0b8e66c40e5e6aba7a382a3', $encodeData['sign']);
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

        $newDinPay = new NewDinPay();

        $newDinPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithouTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'pay_system'        => '49745',
            'hallid'            => '3389593',
            'trade_no'          => '1003450919',
            'sign_type'         => 'MD5',
            'notify_type'       => 'offline_notify',
            'merchant_code'     => '1111130200',
            'order_no'          => '2014052200001',
            'sign'              => '080a1529519594d50db187f2a36b1649',
            'order_amount'      => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no'       => 'HFG000005221224275',
            'order_time'        => '2014-05-22 09:30:11',
            'notify_id'         => '6449d835356847458ab8c21f3381be10',
            'trade_time'        => '2014-05-22 09:31:31'
        ];

        $newDinPay->setOptions($sourceData);
        $newDinPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithoutDigest()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'pay_system'        => '49745',
            'hallid'            => '3389593',
            'trade_no'          => '1003450919',
            'sign_type'         => 'MD5',
            'notify_type'       => 'offline_notify',
            'merchant_code'     => '1111130200',
            'order_no'          => '2014052200001',
            'trade_status'      => 'SUCCESS',
            'order_amount'      => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no'       => 'HFG000005221224275',
            'order_time'        => '2014-05-22 09:30:11',
            'notify_id'         => '6449d835356847458ab8c21f3381be10',
            'trade_time'        => '2014-05-22 09:31:31'
        ];

        $newDinPay->setOptions($sourceData);
        $newDinPay->verifyOrderPayment([]);
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

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'pay_system'        => '49745',
            'hallid'            => '3389593',
            'trade_no'          => '1003450919',
            'sign_type'         => 'MD5',
            'notify_type'       => 'offline_notify',
            'merchant_code'     => '1111130200',
            'order_no'          => '2014052200001',
            'trade_status'      => 'SUCCESS',
            'sign'              => 'd50db187f2a36b1649080a1529519594',
            'order_amount'      => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no'       => 'HFG000005221224275',
            'order_time'        => '2014-05-22 09:30:11',
            'notify_id'         => '6449d835356847458ab8c21f3381be10',
            'trade_time'        => '2014-05-22 09:31:31'
        ];

        $newDinPay->setOptions($sourceData);
        $newDinPay->verifyOrderPayment([]);
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

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'pay_system'        => '49745',
            'hallid'            => '3389593',
            'trade_no'          => '1003450919',
            'sign_type'         => 'MD5',
            'notify_type'       => 'offline_notify',
            'merchant_code'     => '1111130200',
            'order_no'          => '2014052200001',
            'trade_status'      => 'FAILED',
            'sign'              => 'e9aef9df0ddd67a5ff989144330e4d5e',
            'order_amount'      => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no'       => 'HFG000005221224275',
            'order_time'        => '2014-05-22 09:30:11',
            'notify_id'         => '6449d835356847458ab8c21f3381be10',
            'trade_time'        => '2014-05-22 09:31:31'
        ];

        $newDinPay->setOptions($sourceData);
        $newDinPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'pay_system'        => '49745',
            'hallid'            => '3389593',
            'trade_no'          => '1003450919',
            'sign_type'         => 'MD5',
            'notify_type'       => 'offline_notify',
            'merchant_code'     => '1111130200',
            'order_no'          => '2014052200001',
            'trade_status'      => 'SUCCESS',
            'sign'              => '080a1529519594d50db187f2a36b1649',
            'order_amount'      => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no'       => 'HFG000005221224275',
            'order_time'        => '2014-05-22 09:30:11',
            'notify_id'         => '6449d835356847458ab8c21f3381be10',
            'trade_time'        => '2014-05-22 09:31:31'
        ];

        $entry = ['id' => '2014052200123'];

        $newDinPay->setOptions($sourceData);
        $newDinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'pay_system'        => '49745',
            'hallid'            => '3389593',
            'trade_no'          => '1003450919',
            'sign_type'         => 'MD5',
            'notify_type'       => 'offline_notify',
            'merchant_code'     => '1111130200',
            'order_no'          => '2014052200001',
            'trade_status'      => 'SUCCESS',
            'sign'              => '080a1529519594d50db187f2a36b1649',
            'order_amount'      => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no'       => 'HFG000005221224275',
            'order_time'        => '2014-05-22 09:30:11',
            'notify_id'         => '6449d835356847458ab8c21f3381be10',
            'trade_time'        => '2014-05-22 09:31:31'
        ];

        $entry = [
            'id' => '2014052200001',
            'amount' => '1.0000'
        ];

        $newDinPay->setOptions($sourceData);
        $newDinPay->verifyOrderPayment($entry);
    }
    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccessBySynchronous()
    {
        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'pay_system'        => '49745',
            'hallid'            => '3389593',
            'trade_no'          => '1003450919',
            'sign_type'         => 'MD5',
            'notify_type'       => 'offline_notify',
            'merchant_code'     => '1111130200',
            'order_no'          => '2014052200001',
            'trade_status'      => 'SUCCESS',
            'sign'              => '080a1529519594d50db187f2a36b1649',
            'order_amount'      => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no'       => 'HFG000005221224275',
            'order_time'        => '2014-05-22 09:30:11',
            'notify_id'         => '6449d835356847458ab8c21f3381be10',
            'trade_time'        => '2014-05-22 09:31:31'
        ];

        $entry = [
            'id' => '2014052200001',
            'amount' => '0.0100'
        ];

        $newDinPay->setOptions($sourceData);
        $newDinPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $newDinPay->getMsg());
    }

    /**
     * 測試支付驗證成功(同步返回)
     */
    public function testPaySuccessByAsynchronous()
    {
        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');

        $sourceData = [
            'pay_system'        => '49745',
            'hallid'            => '3389593',
            'merchant_code'     => '1111130200',
            'notify_type'       => 'page_notify',
            'notify_id'         => '3c8878d27bb742e585eb193011e65340',
            'interface_version' => 'V3.0',
            'sign_type'         => 'MD5',
            'order_no'          => '2014052200001',
            'order_amount'      => '0.01',
            'order_time'        => '2014-05-22 09:30:11',
            'trade_no'          => '1003450919',
            'trade_time'        => '2014-05-22 09:31:31',
            'trade_status'      => 'SUCCESS',
            'bank_seq_no'       => 'HFG000005221224275',
            'sign'              => '0d0c9afde90c3edd006b6e0f79a4d74a'
        ];

        $entry = [
            'id' => '2014052200001',
            'amount' => '0.0100'
        ];

        $newDinPay->setOptions($sourceData);
        $newDinPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $newDinPay->getMsg());
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

        $newDinPay = new NewDinPay();
        $newDinPay->paymentTracking();
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

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->paymentTracking();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數is_success
     */
    public function testPaymentTrackingResultWithoutIsSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
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

        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response>'.
            '<is_success>F</is_success>'.
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數trade_status
     */
    public function testPaymentTrackingResultWithoutTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response>'.
            '<is_success>T</is_success>'.
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
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

        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response>'.
            '<is_success>T</is_success>'.
            '<trade><trade_status>UNPAY</trade_status></trade>'.
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
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

        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response>'.
            '<is_success>T</is_success>'.
            '<trade><trade_status>FAILED</trade_status></trade>'.
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
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

        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response>'.
            '<is_success>T</is_success>'.
            '<trade><trade_status>SUCCESS</trade_status></trade>'.
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
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

        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response>'.
            '<is_success>T</is_success>'.
            '<sign>212b280f20d923b2c5b11e35552cfb2c</sign>'.
            '<trade><trade_status>SUCCESS</trade_status></trade>'.
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
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

        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response>'.
            '<is_success>T</is_success>'.
            '<sign_type>MD5</sign_type>'.
            '<sign>2c5b11e35552cfb2c212b280f20d923b</sign>'.
            '<trade>'.
            '<merchant_code>1111130200</merchant_code>'.
            '<order_amount>0.01</order_amount>'.
            '<order_no>2014052200001</order_no>'.
            '<order_time>2014-05-22 09:30:11</order_time>'.
            '<trade_no>1003450919</trade_no>'.
            '<trade_status>SUCCESS</trade_status>'.
            '<trade_time>2014-05-22 09:31:31</trade_time>'.
            '</trade>'.
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com',
            'amount' => '1.234'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = "<?xml version='1.0' encoding='UTF-8' ?>".
            '<dinpay><response>'.
            '<is_success>T</is_success>'.
            '<sign_type>MD5</sign_type>'.
            '<sign>2c5b11e35552cfb2c212b280f20d923b</sign>'.
            '<trade>'.
            '<merchant_code>1111130200</merchant_code>'.
            '<order_amount>0.01</order_amount>'.
            '<order_no>2014052200001</order_no>'.
            '<order_time>2014-05-22 09:30:11</order_time>'.
            '<trade_no>1003450919</trade_no>'.
            '<trade_status>SUCCESS</trade_status>'.
            '<trade_time>2014-05-22 09:31:31</trade_time>'.
            '</trade>'.
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newdinpay.com',
            'amount' => '0.01'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setContainer($this->container);
        $newDinPay->setClient($this->client);
        $newDinPay->setResponse($response);
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTracking();
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

        $newDinPay = new NewDinPay();
        $newDinPay->getPaymentTrackingData();
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

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->getPaymentTrackingData();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($options);
        $newDinPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.query.dinpay.com',
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($options);
        $trackingData = $newDinPay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/query', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.query.dinpay.com', $trackingData['headers']['Host']);

        $this->assertEquals('single_trade_query', $trackingData['form']['service_type']);
        $this->assertEquals('1111130200', $trackingData['form']['merchant_code']);
        $this->assertEquals('MD5', $trackingData['form']['sign_type']);
        $this->assertEquals('2014052200001', $trackingData['form']['order_no']);
        $this->assertEquals('8a0be7eafe43a528f08c50ad29c93ce9', $trackingData['form']['sign']);
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

        $newDinPay = new NewDinPay();
        $newDinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數is_success
     */
    public function testPaymentTrackingVerifyWithoutIsSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response></response></dinpay>';
        $sourceData = ['content' => $content];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
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

        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>F</is_success>' .
            '</response></dinpay>';
        $sourceData = ['content' => $content];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數trade_status
     */
    public function testPaymentTrackingVerifyWithoutTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '</response></dinpay>';
        $sourceData = ['content' => $content];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
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

        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><trade_status>UNPAY</trade_status></trade>' .
            '</response></dinpay>';
        $sourceData = ['content' => $content];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
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

        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><trade_status>FAILED</trade_status></trade>' .
            '</response></dinpay>';
        $sourceData = ['content' => $content];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
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

        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><trade_status>SUCCESS</trade_status></trade>' .
            '</response></dinpay>';
        $sourceData = ['content' => $content];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
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

        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>212b280f20d923b2c5b11e35552cfb2c</sign>' .
            '<trade><trade_status>SUCCESS</trade_status></trade>' .
            '</response></dinpay>';
        $sourceData = ['content' => $content];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
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

        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>MD5</sign_type>' .
            '<sign>2c5b11e35552cfb2c212b280f20d923b</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2014-05-22 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2014-05-22 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';
        $sourceData = [
            'content' => $content,
            'amount' => '1.234'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = "<?xml version='1.0' encoding='UTF-8' ?>" .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>MD5</sign_type>' .
            '<sign>2c5b11e35552cfb2c212b280f20d923b</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2014052200001</order_no>' .
            '<order_time>2014-05-22 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2014-05-22 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';
        $sourceData = [
            'content' => $content,
            'amount' => '0.01'
        ];

        $newDinPay = new NewDinPay();
        $newDinPay->setPrivateKey('hfgd_83yt_90hb_gf98n');
        $newDinPay->setOptions($sourceData);
        $newDinPay->paymentTrackingVerify();
    }
}
