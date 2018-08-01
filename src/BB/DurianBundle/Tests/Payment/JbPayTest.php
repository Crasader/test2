<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JbPay;
use Buzz\Message\Response;

class JbPayTest extends DurianTestCase
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
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jbPay = new JbPay();
        $jbPay->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '001110173920001',
            'amount' => '100',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://pay.return/',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->getVerifyData();
    }

    /**
     * 測試QQ錢包時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '001110173920001',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => '',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->getVerifyData();
    }

    /**
     * 測試QQ錢包時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '001110173920001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=cLys8Hm","merchno":"001110173920001","message":"下单成功",' .
            '"refno":"02170322000081213163","traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->getVerifyData();
    }

    /**
     * 測試QQ錢包時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败,找不到二维码路由信息',
            180130
        );

        $options = [
            'number' => '001110173920001',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"001110173920001","message":"交易失败,找不到二维码路由信息","refno":"02170324000081299151",' .
            '"respCode":"0001","traceno":"201703240000001427"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->getVerifyData();
    }

    /**
     * 測試QQ錢包時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '001110173920001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"001110173920001","message":"下单成功","refno":"02170322000081213163","respCode":"00",' .
            '"traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->getVerifyData();
    }

    /**
     * 測試QQ_掃碼
     */
    public function testQQScan()
    {
        $options = [
            'number' => '001110173920001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=cLys8Hm","merchno":"001110173920001","message":"下单成功",' .
            '"refno":"02170322000081213163","respCode":"00","traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $data = $jbPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=cLys8Hm', $jbPay->getQrcode());
    }

    /**
     * 測試QQ_手機支付
     */
    public function testQQPhone()
    {
        $options = [
            'number' => '001110173920001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=cLys8Hm","merchno":"001110173920001","message":"下单成功",' .
            '"refno":"02170322000081213163","respCode":"00","traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $data = $jbPay->getVerifyData();

        $this->assertEquals('weixin://wxpay/bizpayurl?pr=cLys8Hm', $data['act_url']);
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'number' => '001110173920001',
            'amount' => '1',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'notify_url' => 'http://pay.return/',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $encodeData = $jbPay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['merchno']);
        $this->assertEquals($options['amount'], $encodeData['amount']);
        $this->assertEquals($options['orderId'], $encodeData['traceno']);
        $this->assertEquals('2', $encodeData['channel']);
        $this->assertEquals('3002', $encodeData['bankCode']);
        $this->assertEquals('2', $encodeData['settleType']);
        $this->assertEquals($options['notify_url'], $encodeData['notifyUrl']);
        $this->assertEquals($options['notify_url'], $encodeData['returnUrl']);
        $this->assertEquals('e6e86dd97aa7a82a4369f999b67fe297', $encodeData['signature']);
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jbPay = new JbPay();
        $jbPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'amount' => '1.00',
            'merchno' => '001110173920001',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'channelOrderno' => '8021800596886170518077699003',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->verifyOrderPayment([]);
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

        $options = [
            'amount' => '1.00',
            'merchno' => '001110173920001',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => '9453',
            'channelOrderno' => '8021800596886170518077699003',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->verifyOrderPayment([]);
    }

    /**
     * 測試網銀返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'amount' => '1.00',
            'merchno' => '001110173920001',
            'status' => '3',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'channelOrderno' => '8021800596886170518077699003',
            'signature' => 'A42ACCD9976E18660B021C1D39A32FB8',
        ];

        $entry = [
            'id' => '201703220000001397',
            'amount' => '1.00',
            'payment_vendor_id' => '1',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->verifyOrderPayment($entry);
    }

    /**
     * 測試QQ錢包返回時支付失敗
     */
    public function testReturnWithQQPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'amount' => '0.01',
            'channelOrderno' => '52170322001730918503',
            'channelTraceno' => '100075161770',
            'merchName' => 'ESBALL',
            'merchno' => '001110173920001',
            'orderno' => '02170322000081213163',
            'payType' => '2',
            'signature' => '48BDE8A4A790F90500A9FE734F74CFA7',
            'status' => '2',
            'traceno' => '201703220000001407',
            'transDate' => '2017-03-22',
            'transTime' => '14:33:49',
        ];

        $entry = [
            'id' => '201703220000001407',
            'amount' => '0.01',
            'payment_vendor_id' => '1103',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'amount' => '1.00',
            'merchno' => '001110173920001',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => 'E53BDDE4C39516A89EF60B6256C88A45',
            'channelOrderno' => '8021800596886170518077699003',
        ];

        $entry = [
            'id' => '9453',
            'payment_vendor_id' => '1',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'amount' => '1.00',
            'merchno' => '001110173920001',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => 'E53BDDE4C39516A89EF60B6256C88A45',
            'channelOrderno' => '8021800596886170518077699003',
        ];

        $entry = [
            'id' => '201703220000001397',
            'amount' => '0.1',
            'payment_vendor_id' => '1',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'amount' => '1.00',
            'merchno' => '001110173920001',
            'status' => '2',
            'traceno' => '201703220000001397',
            'orderno' => '03170322000010488763',
            'signature' => 'E53BDDE4C39516A89EF60B6256C88A45',
            'channelOrderno' => '8021800596886170518077699003',
        ];

        $entry = [
            'id' => '201703220000001397',
            'amount' => '1.00',
            'payment_vendor_id' => '1',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $jbPay->getMsg());
    }

    /**
     * 測試返回結果，channelOrderno值為null
     */
    public function testReturnOrderChannelOrdernoIsNull()
    {
        $options = [
            'amount' => '2.00',
            'merchno' => '001110173920001',
            'status' => '2',
            'traceno' => '201802270000009774',
            'orderno' => '13180227100000009075',
            'signature' => '877ACC0EA580B0183F72BB1799220BE6',
            'channelOrderno' => 'null',
        ];

        $entry = [
            'id' => '201802270000009774',
            'amount' => '2.00',
            'payment_vendor_id' => '1',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $jbPay->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jbPay = new JbPay();
        $jbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢異常
     */
    public function testTrackingReturnWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '找不到交易',
            180123
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"25","message":"找不到交易","traceno":"201703220000001397",' .
            '"orderno":"03170322000010488763","channelOrderno":"null"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"交易成功","respCode":"00"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢返回缺少金額
     */
    public function testTrackingWithoutAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"001110173920001",' .
            '"orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢返回金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '2',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"001110173920001","amount":"1.00",' .
            '"orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試QQ錢包訂單查詢異常
     */
    public function testTrackingQQReturnWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '找不到交易',
            180123
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1103',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"找不到交易","respCode":"3","payType":"2","scanType":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試QQ錢包訂單查詢結果訂單未支付
     */
    public function testTrackingQQReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1103',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"未支付","respCode":"0","payType":"2","scanType":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試QQ錢包訂單查詢結果支付失敗
     */
    public function testTrackingQQReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1103',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"支付失败","respCode":"2","payType":"2","scanType":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回缺少訂單號
     */
    public function testTrackingWithoutOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"001110173920001","amount":"1.00",' .
            '"orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回訂單號錯誤
     */
    public function testTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"001110173920001","amount":"1.00",' .
            '"traceno":"9453","orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"respCode":"00","message":"查询成功","merchno":"001110173920001","amount":"1.00",' .
            '"traceno":"201703220000001397","orderno":"03170322000010488763","channelOrderno":"null",' .
            '"channelTraceno":"null","status":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jbPay = new JbPay();
        $jbPay->setContainer($this->container);
        $jbPay->setClient($this->client);
        $jbPay->setResponse($response);
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jbPay = new JbPay();
        $jbPay->getPaymentTrackingData();
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

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $jbPay = new JbPay();
        $jbPay->setPrivateKey('test');
        $jbPay->setOptions($options);
        $jbPay->getPaymentTrackingData();
    }

    /**
     * 測試取得網銀訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jbPay = new JbPay();
        $jbPay->setOptions($options);
        $jbPay->setPrivateKey('test');
        $trackingData = $jbPay->getPaymentTrackingData();

        $path = '/gateway.do?m=query';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($options['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals($options['number'], $trackingData['form']['merchno']);
        $this->assertEquals($options['orderId'], $trackingData['form']['traceno']);
        $this->assertNotNull($trackingData['form']['signature']);
    }

    /**
     * 測試取得QQ錢包訂單查詢需要的參數
     */
    public function testGetPaymentTrackingDataQQ()
    {
        $options = [
            'number' => '001110173920001',
            'orderId' => '201703220000001397',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jbPay = new JbPay();
        $jbPay->setOptions($options);
        $jbPay->setPrivateKey('test');
        $trackingData = $jbPay->getPaymentTrackingData();

        $path = '/qrcodeQuery';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals($options['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals($options['number'], $trackingData['form']['merchno']);
        $this->assertEquals($options['orderId'], $trackingData['form']['traceno']);
        $this->assertNotNull($trackingData['form']['signature']);
    }

    /**
     * 測試訂單查詢支付平台返回的編碼
     */
    public function testProcessTrackingResponseEncoding()
    {
        $body = '{"message":"查询成功","orderno":"02170328100000001836","payType":"2",' .
            '"respCode":"0","scanType":"2","traceno":"201703280000001450"}';

        $encodedBody = base64_encode(iconv('UTF-8', 'GBK', $body));
        $encodedResponse = [
            'header' => null,
            'body' => $encodedBody,
        ];

        $jbPay = new JbPay();
        $processedResponse = $jbPay->processTrackingResponseEncoding($encodedResponse);

        $this->assertEquals($encodedResponse['header'], $processedResponse['header']);
        $this->assertEquals($body, $processedResponse['body']);
    }
}
