<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XinBao;
use Buzz\Message\Response;

class XinBaoTest extends DurianTestCase
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
            ->will($this->returnValue(null));

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

        $xinBao = new XinBao();
        $xinBao->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->getVerifyData();
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
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '99999',
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '1.01',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->getVerifyData();
    }

    /**
     * 測試支付設定回傳成功
     */
    public function testPaySuccess()
    {
        $options = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1102',
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '1.01',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $requestData = $xinBao->getVerifyData();

        $this->assertEquals($options['notify_url'], $requestData['asyn_notify_url']);
        $this->assertEquals($options['number'], $requestData['merchant_code']);
        $this->assertEquals('EBANK', $requestData['pay_type']);
        $this->assertEquals($options['orderId'], $requestData['order_number']);
        $this->assertEquals($options['amount'], $requestData['amount']);
        $this->assertEquals('d40aa3ec39ae1ad4b1c070e8d9033309', $requestData['sign']);
    }

    /**
     * 測試支付銀行為微信二維，但沒帶入verify_url
     */
    public function testPayWithWeiXinWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '0.01',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但沒返回code
     */
    public function testPayWithWeiXinNotReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '0.01',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk636.com',
        ];

        $result = '{"msg":"提交成功","r_data":"weixin://wxpay/bizpayurl?pr=Cb7Rjsi"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但沒返回msg
     */
    public function testPayWithWeiXinNotReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '0.01',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk636.com',
        ];

        $result = '{"code":"1","r_data":"weixin://wxpay/bizpayurl?pr=Cb7Rjsi"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但沒返回r_data
     */
    public function testPayWithWeiXinNotReturnRdata()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '0.01',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk636.com',
        ];

        $result = '{"code":"1","msg":"提交成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維，但支付失敗
     */
    public function testPayWithWeiXinFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '[order_number]商户订单号重复',
            180130
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '0.01',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk636.com',
        ];

        $result = '{"code":"0","msg":"[order_number]商户订单号重复","r_data":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維
     */
    public function testPayWithWeiXin()
    {
        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '0.01',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk636.com',
        ];

        $result = '{"code":1,"msg":"提交成功","r_data":"weixin://wxpay/bizpayurl?pr=Cb7Rjsi"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $requestData = $xinBao->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=Cb7Rjsi', $xinBao->getQrcode());
    }

    /**
     * 測試支付銀行為支付寶二維
     */
    public function testPayWithAliPay()
    {
        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'amount' => '0.01',
            'notify_url' => 'http://pay.a/return.php',
            'paymentVendorId' => '1092',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk636.com',
        ];

        $result = '{"code":1,"msg":"提交成功","r_data":"https://qr.alipay.com/bax05930ae4fesscug3640f2"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $requestData = $xinBao->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('https://qr.alipay.com/bax05930ae4fesscug3640f2', $xinBao->getQrcode());
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

        $xinBao = new XinBao();
        $xinBao->verifyOrderPayment([]);
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

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'order_number' => '201612270000000453',
            'merchant_code' => 'DM20161216000010',
            'pay_type' => 'WECHATQR',
            'amount' => '1.01',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->verifyOrderPayment([]);
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
            'merchant_code' => 'DM20161216000010',
            'order_number' => '201612270000000453',
            'code' => '1',
            'msg' => '处理成功',
            'amount' => '0.010',
            'sign' => '123456789',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時處理中
     */
    public function testReturnPaymentProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'merchant_code' => 'DM20161216000010',
            'order_number' => '201612270000000453',
            'code' => '0',
            'msg' => '处理中',
            'amount' => '0.01',
            'sign' => '270e325643ae9c8af28fc2aaeebe92dc',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->verifyOrderPayment([]);
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

        $options = [
            'merchant_code' => 'DM20161216000010',
            'order_number' => '201612270000000453',
            'code' => '-1',
            'msg' => '处理失败',
            'amount' => '0.01',
            'sign' => '214bd0e4f7103350342107fb7512e0f4',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->verifyOrderPayment([]);
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
            'merchant_code' => 'DM20161216000010',
            'order_number' => '201612270000000453',
            'code' => '1',
            'msg' => '处理成功',
            'amount' => '0.01',
            'sign' => '21f2a80ba385339de68f86682c14bae6',
        ];

        $entry = ['id' => '201503220000000555'];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'merchant_code' => 'DM20161216000010',
            'order_number' => '201612270000000453',
            'code' => '1',
            'msg' => '处理成功',
            'amount' => '0.01',
            'sign' => '21f2a80ba385339de68f86682c14bae6',
        ];

        $entry = [
            'id' => '201612270000000453',
            'amount' => '15.00',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'merchant_code' => 'DM20161216000010',
            'order_number' => '201612270000000453',
            'code' => '1',
            'msg' => '处理成功',
            'amount' => '0.01',
            'sign' => '21f2a80ba385339de68f86682c14bae6',
        ];

        $entry = [
            'id' => '201612270000000453',
            'amount' => '0.01',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->verifyOrderPayment($entry);

        $this->assertEquals('ok', $xinBao->getMsg());
    }

    /**
     * 測試訂單查詢密鑰沒帶入
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xinBao = new XinBao();
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithoutTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '1234567',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回特定參數code
     */
    public function testTrackingWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1234567',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"61eef498d7b36f3d61fbe49bc682da2e"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回特定參數msg
     */
    public function testTrackingWithoutReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1234567',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"code":1,"r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"61eef498d7b36f3d61fbe49bc682da2e"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回特定參數r_data
     */
    public function testTrackingWithoutReturnRdata()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1234567',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"code":1,"msg":"查询成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回特定參數
     */
    public function testTrackingWithoutReturnParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1234567',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"code":1,"msg":"查询成功","r_data":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单不存在或超过查询期限[90天]',
            180130
        );

        $options = [
            'number' => '1234567',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"code":0,"msg":"订单不存在或超过查询期限[90天]","r_data":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有返回簽名
     */
    public function testTrackingWithoutReturnSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1234567',
            'orderId' => 'orderId',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":""}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }


    /**
     * 測試訂單查詢結果返回簽名錯誤
     */
    public function testTrackingReturnSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"1234"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單未支付
     */
    public function testTrackingResultPaymentUnpaid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"0","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"f4bd2f8296e85f8eae674e39a21cd5f0"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單失敗
     */
    public function testTrackingResultPaymentFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $result = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"-1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"3269a82f87d185b16f2a3c032bf83608"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為金額錯誤
     */
    public function testTrackingResultAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
            'amount' => '111',
        ];

        $result = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"f6d820af0e757d2c4593f10a06070261"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
            'amount' => '0.01',
        ];

        $result = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"f6d820af0e757d2c4593f10a06070261"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $xinBao = new XinBao();
        $xinBao->setContainer($this->container);
        $xinBao->setClient($this->client);
        $xinBao->setResponse($response);
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->paymentTracking();
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

        $xinBao = new XinBao();
        $xinBao->getPaymentTrackingData();
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

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->getPaymentTrackingData();
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

        $options = ['orderId' => '201508060000000201'];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒帶入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '19822546',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $xinBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => 'DM20161216000010',
            'orderId' => '201612270000000453',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.pk767.com',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($options);
        $trackingData = $xinBao->getPaymentTrackingData();

        $path = '/s/order?merchant_code=DM20161216000010&merchant_no=201612270000000453&sign=b01d728c57818fa579dba1d' .
            '9cbaeaca6';
        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('payment.http.api.pk767.com', $trackingData['headers']['Host']);
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

        $xinBao = new XinBao();
        $xinBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '{"code":1,"msg":"查询成功","r_data":""}';

        $sourceData = ['content' => $content];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($sourceData);
        $xinBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"0","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":""}}';

        $sourceData = ['content' => $content];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($sourceData);
        $xinBao->paymentTrackingVerify();
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

        $content = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"1"}}';

        $sourceData = ['content' => $content];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($sourceData);
        $xinBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢未支付
     */
    public function testPaymentTrackingVerifyButUnpaid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $content = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"0","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"f4bd2f8296e85f8eae674e39a21cd5f0"}}';

        $sourceData = [
            'content' => $content,
            'amount' => 0.01,
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($sourceData);
        $xinBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但查詢失敗
     */
    public function testPaymentTrackingVerifyButPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"-1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"3269a82f87d185b16f2a3c032bf83608"}}';

        $sourceData = [
            'content' => $content,
            'amount' => 0.01,
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($sourceData);
        $xinBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單號不正確
     */
    public function testPaymentTrackingVerifyButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"f6d820af0e757d2c4593f10a06070261"}}';

        $sourceData = [
            'orderId' => '201503220000000555',
            'content' => $content,
            'amount' => '0.01',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($sourceData);
        $xinBao->paymentTrackingVerify();
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

        $content = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"f6d820af0e757d2c4593f10a06070261"}}';

        $sourceData = [
            'orderId' => '201612270000000453',
            'content' => $content,
            'amount' => '400.00',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($sourceData);
        $xinBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '{"code":1,"msg":"查询成功","r_data":{"no":"TO20161227000650","merchant_code":"DM20161216000010",' .
            '"merchant_no":"201612270000000453","pay_type_code":"WECHATQR","money":"0.01","syn_notify_url":' .
            '"http://abc.tnndbear.net/pay/return.php","asyn_notify_url":"http://abc.tnndbear.net/pay/return.php",' .
            '"create_time":"2016-12-27 13:50:00","status":"1","in_rate_money":"0.00","real_money":"0.01",' .
            '"success_time":"2016-12-27 13:50:41","attach":"","sign":"f6d820af0e757d2c4593f10a06070261"}}';

        $sourceData = [
            'orderId' => '201612270000000453',
            'content' => $content,
            'amount' => '0.01',
        ];

        $xinBao = new XinBao();
        $xinBao->setPrivateKey('test');
        $xinBao->setOptions($sourceData);
        $xinBao->paymentTrackingVerify();
    }
}
