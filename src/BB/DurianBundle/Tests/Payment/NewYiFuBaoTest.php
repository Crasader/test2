<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewYiFuBao;
use Buzz\Message\Response;

class NewYiFuBaoTest extends DurianTestCase
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

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPayWithNotSupportedBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '100',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->getVerifyData();
    }

    /**
     * 測試支付未帶入 verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->getVerifyData();
    }

    /**
     * 測試支付返回未開通支付渠道
     */
    public function testPayReturnNotSupportedVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '未开通该银行支付渠道权限',
            180130
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
            'verify_url' => 'payment.http.api.hebaobill.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<p class="text-center lead">请求失败，原因：未开通该银行支付渠道权限</p>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->getVerifyData();
    }

    /**
     * 測試支付返回請求失敗
     */
    public function testPayReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '请求失败的错误信息',
            180130
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
            'verify_url' => 'payment.http.api.hebaobill.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<err>请求失败的错误信息</err>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->getVerifyData();
    }

    /**
     * 測試支付未返回 url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
            'verify_url' => 'payment.http.api.hebaobill.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<url></url>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->getVerifyData();
    }

    /**
     * 測試微信二維支付
     */
    public function testPayQrcode()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
            'verify_url' => 'payment.http.api.hebaobill.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $url = 'weixin://wxpay/bizpayurl?pr=LjtJjCV';
        $result = "<url>$url</url>";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $requestData = $newYiFuBao->getVerifyData();

        $this->assertEquals($url, $newYiFuBao->getQrcode());
        $this->assertEquals([], $requestData);
    }


    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'orderCreateDate' => '20150316094511',
            'ip' => '127.0.0.1',
            'merchantId' => '12345',
            'domain' => '6',
            'verify_url' => 'payment.http.api.hebaobill.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $url = 'https://openapi.ysepay.com/gateway.do?bank_account_type=cGVyc29uYWw=&bank_type=MTAyMTAwMA==&business_' .
            'code=MDEwMDAwMTA=&charset=UTF-8&extend_params=eyJwYXJhbTEiOjEyM30=&extra_common_param=bm8=&method=ysepay' .
            '.online.directpay.createbyuser&notify_url=aHR0cDovLzIxMS4xNTIuNDQuMjM2OjgyMy9yZWNlaXB0L25vdGlmeQ==&out_t' .
            'rade_no=MjAxNzA0MjYzMDYzNjYwNDM1OTIxNzYz&partner_id=b3Vibzg4OTMz&pay_mode=aW50ZXJuZXRiYW5r&return_url=aH' .
            'R0cDovL3BheS40MS5jbi9ub3RpZmllZC95c2VwYXkvcmV0dXJu&seller_id=b3Vibzg4OTMz&seller_name=ye7b2srQxbeyqbTvv8' .
            'a8vNPQz965q8u+&sign_type=RSA&subject=bm9fbmFtZQ==&support_card_type=ZGViaXQ=&timeout_express=MzBt&timest' .
            'amp=2017-04-26 14:25:25&total_amount=MC4wMQ==&version=3.1&sign=PE5eKwJQ0HgjeLDQ4V1nlUniLZFqMTkCn0Bh7zXdh' .
            '1kT/u/Lmbr/GVw5HotzT8jxnyt1yYfoO7I+MdLMxuL2LLSuVZnc+2ikeG9wzpl4+bhARCxPA3xpLpKMccQGBdtF3beSTos0wFXenlQ8H' .
            'ORniLoO1aBuq+Bs9Cn3s36VeSY=';
        $result = "<url>$url</url>";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $requestData = $newYiFuBao->getVerifyData();

        $this->assertEquals($url, $requestData['post_url']);
        $this->assertEquals([], $requestData['params']);
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

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->verifyOrderPayment([]);
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

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->verifyOrderPayment([]);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->verifyOrderPayment([]);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
            'sign' => '123456789',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->verifyOrderPayment([]);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'failed',
            'sign' => '45515574cc3dcc5d685199e9b13d66b1',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->verifyOrderPayment([]);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
            'sign' => 'b160427a1f80f99cd39a0cc6ee074b7c',
        ];

        $entry = ['id' => '201503220000000555'];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->verifyOrderPayment($entry);
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
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
            'sign' => 'b160427a1f80f99cd39a0cc6ee074b7c',
        ];

        $entry = [
            'id' => '201506100000002073',
            'amount' => '15.00',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchant_code' => '19822546',
            'notify_type' => 'back_notify',
            'order_no' => '201506100000002073',
            'order_amount' => '100',
            'order_time' => '2015-03-16 09:45:11',
            'return_params' => '',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
            'sign' => 'b160427a1f80f99cd39a0cc6ee074b7c',
        ];

        $entry = [
            'id' => '201506100000002073',
            'amount' => '100.00',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $newYiFuBao->getMsg());
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

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->paymentTracking();
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

        $options = ['number' => '19822546'];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
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
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線失敗
     */
    public function testTrackingReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有response的情況
     */
    public function testTrackingReturnWithoutResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'abc.123',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果訂單不存在
     */
    public function testTrackingReturnOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>FALSE</is_success>' .
            '<error_msg>参数order_no的值201511230000002766不存在</error_msg>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
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

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>FALSE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有sign的情況
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
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

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>123456789</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果交易中
     */
    public function testTrackingReturnPaymentOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>paying</trade_status>' .
            '<sign>d477a0ed2af54abdd9bb8df9096ffcb7</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
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

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>fail</trade_status>' .
            '<sign>6f4395ee2c06b5958e4899d1e29e8c1d</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢單號不正確
     */
    public function testPaymentTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '20130809',
            'orderId' => '9487',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>1acd91e8c32c16b96560cea579afdfa9</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '400.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>1acd91e8c32c16b96560cea579afdfa9</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316094511',
            'amount' => '10.00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $result = '<?xml version="1.0" encoding="utf-8" ?>' .
            '<pay>' .
            '<response>' .
            '<is_success>TRUE</is_success>' .
            '<merchant_code>20130809</merchant_code>' .
            '<order_no>201503160000002219</order_no>' .
            '<order_amount>10.00</order_amount>' .
            '<order_time>2015-06-10 14:24:21</order_time>' .
            '<trade_no>3063601165464056</trade_no>' .
            '<trade_time>2015-06-10 14:24:24</trade_time>' .
            '<trade_status>success</trade_status>' .
            '<sign>1acd91e8c32c16b96560cea579afdfa9</sign>' .
            '</response>' .
            '</pay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setContainer($this->container);
        $newYiFuBao->setClient($this->client);
        $newYiFuBao->setResponse($response);
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->paymentTracking();
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

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->getPaymentTrackingData();
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

        $options = ['number' => '19822546'];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->getPaymentTrackingData();
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
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $newYiFuBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.pay.41.cn',
        ];

        $newYiFuBao = new NewYiFuBao();
        $newYiFuBao->setPrivateKey('test');
        $newYiFuBao->setOptions($options);
        $trackingData = $newYiFuBao->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/query', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.https.pay.41.cn', $trackingData['headers']['Host']);

        $this->assertEquals('UTF-8', $trackingData['form']['input_charset']);
        $this->assertEquals('19822546', $trackingData['form']['merchant_code']);
        $this->assertEquals('c8952cf074859bfba57a5b2f65e1cd78', $trackingData['form']['sign']);
        $this->assertEquals('201506100000002073', $trackingData['form']['order_no']);
        $this->assertEmpty($trackingData['form']['trade_no']);
    }
}
