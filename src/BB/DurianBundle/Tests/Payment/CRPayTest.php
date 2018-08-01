<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CRPay;
use Buzz\Message\Response;

class CRPayTest extends DurianTestCase
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

        $cRPay = new CRPay();
        $cRPay->getVerifyData();
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

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '9999',
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => '',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'msg' => '签名不匹配!',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名不匹配!',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '70001',
            'msg' => '签名不匹配!',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code_url
     */
    public function testPayReturnWithoutCodeurl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'sign' => 'd87d4cc9b6638daacbd97e1f95897e81',
            'amount' => '1007',
            'trade_no' => '201708312333449414920192',
            'remark' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004219',
            'msg' => '调用成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'sign' => 'd87d4cc9b6638daacbd97e1f95897e81',
            'amount' => '1007',
            'trade_no' => '201708312333449414920192',
            'remark' => '',
            'code_url' => 'weixin://wxpay/bizpayurl?pr=GopNQBS',
            'code' => '0000',
            'out_trade_no' => '201708310000004219',
            'msg' => '调用成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $data = $cRPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=GopNQBS', $cRPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1104',
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'sign' => 'd87d4cc9b6638daacbd97e1f95897e81',
            'amount' => '1007',
            'trade_no' => '201708312333449414920192',
            'remark' => '',
            'code_url' => 'http://api.crpay.com/payapi/api/trade/qqh5?code=C5EnW87tyta0=',
            'code' => '0000',
            'out_trade_no' => '201708310000004219',
            'msg' => '调用成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $data = $cRPay->getVerifyData();

        $this->assertEquals('http://api.crpay.com/payapi/api/trade/qqh5?code=C5EnW87tyta0=', $data['act_url']);
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

        $cRPay = new CRPay();
        $cRPay->verifyOrderPayment([]);
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

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時code不正確
     */
    public function testReturnCodeNotCorrect()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'amount' => '10',
            'sign' => 'bd3147e0913f64fbe994154e6bb3dc80',
            'trade_no' => '201708312333590234432512',
            'remark' => '',
            'status' => '1',
            'code' => '0001',
            'out_trade_no' => '201708310000004225',
            'msg' => '接口调用成功',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->verifyOrderPayment([]);
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
            'amount' => '10',
            'trade_no' => '201708312333590234432512',
            'remark' => '',
            'status' => '1',
            'code' => '0000',
            'out_trade_no' => '201708310000004225',
            'msg' => '接口调用成功',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->verifyOrderPayment([]);
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
            'amount' => '10',
            'sign' => 'bd3147e0913f64fbe994154e6bb3dc80',
            'trade_no' => '201708312333590234432512',
            'remark' => '',
            'status' => '1',
            'code' => '0000',
            'out_trade_no' => '201708310000004225',
            'msg' => '接口调用成功',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->verifyOrderPayment([]);
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
            'amount' => '10',
            'sign' => 'aa017b7a4b6e6e56664fe7d016ee0fff',
            'trade_no' => '201708312333590234432512',
            'remark' => '',
            'status' => '2',
            'code' => '0000',
            'out_trade_no' => '201708310000004225',
            'msg' => '接口调用成功',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->verifyOrderPayment([]);
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
            'amount' => '10',
            'sign' => 'e2c4002ad5d91ee922f7217d0c0ec5a6',
            'trade_no' => '201708312333590234432512',
            'remark' => '',
            'status' => '1',
            'code' => '0000',
            'out_trade_no' => '201708310000004225',
            'msg' => '接口调用成功',
        ];

        $entry = ['id' => '201503220000000555'];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->verifyOrderPayment($entry);
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
            'amount' => '10',
            'sign' => 'e2c4002ad5d91ee922f7217d0c0ec5a6',
            'trade_no' => '201708312333590234432512',
            'remark' => '',
            'status' => '1',
            'code' => '0000',
            'out_trade_no' => '201708310000004225',
            'msg' => '接口调用成功',
        ];

        $entry = [
            'id' => '201708310000004225',
            'amount' => '15.00',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'amount' => '10',
            'sign' => 'e2c4002ad5d91ee922f7217d0c0ec5a6',
            'trade_no' => '201708312333590234432512',
            'remark' => '',
            'status' => '1',
            'code' => '0000',
            'out_trade_no' => '201708310000004225',
            'msg' => '接口调用成功',
        ];

        $entry = [
            'id' => '201708310000004225',
            'amount' => '0.1',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $cRPay->getMsg());
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

        $cRPay = new CRPay();
        $cRPay->paymentTracking();
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

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->paymentTracking();
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
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數缺少code
     */
    public function testTrackingReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => 'TOF00086',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'msg' => '订单号不存在!',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳code不正確
     */
    public function testTrackingReturnWithCodeNotCorrect()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单号不存在!',
            180123
        );

        $options = [
            'number' => 'TOF00086',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'code' => '20005',
            'msg' => '订单号不存在!',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳參數缺少sign
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => 'TOF00086',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '00',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
    }

    /**
     * 測試訂單查詢驗簽錯誤
     */
    public function testTrackingReturnWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => 'TOF00086',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'sign' => '44adc558e267cdccb56c16f8233ed476',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '00',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單支付中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'number' => 'TOF00086',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'sign' => 'a4fb38a218274f7e0184bdeafa5393a2',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '00',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
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
            'number' => 'TOF00086',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'sign' => '6e83b7c03f8fdd290236ce81bab51314',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '02',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
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
            'number' => 'TOF00086',
            'orderId' => '201611110000000101',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'sign' => '4b65d9e5ea5335e6ab085dec5f39418d',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '03',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004224',
            'amount' => '100.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'sign' => '4b65d9e5ea5335e6ab085dec5f39418d',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '03',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004224',
            'amount' => '10.39',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $params = [
            'sign' => '4b65d9e5ea5335e6ab085dec5f39418d',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '03',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params );

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cRPay = new CRPay();
        $cRPay->setContainer($this->container);
        $cRPay->setClient($this->client);
        $cRPay->setResponse($response);
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($options);
        $cRPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少私鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $cRPay = new CRPay();
        $cRPay->getPaymentTrackingData();
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

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->getPaymentTrackingData();
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

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $trackingData = $cRPay->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/payapi/gateway', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);

        $this->assertEquals('TOF00086', $trackingData['form']['merchant_no']);
        $this->assertEquals('unified.trade.payquery', $trackingData['form']['method']);
        $this->assertEquals('1.0', $trackingData['form']['version']);
        $this->assertEquals('201708310000004225', $trackingData['form']['out_trade_no']);
        $this->assertEquals('', $trackingData['form']['trade_no']);
        $this->assertEquals('e69cd27988fb99a8c0e123532ac475d3', $trackingData['form']['sign']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少私鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $cRPay = new CRPay();
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少code
     */
    public function testPaymentTrackingVerifyWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'msg' => '订单号不存在!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時查詢結果code不正確
     */
    public function testPaymentTrackingVerifyButCodeNotCorrect()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单号不存在!',
            180123
        );

        $params = [
            'code' => '20005',
            'msg' => '订单号不存在!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '00',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $params = [
            'sign' => '44adc558e267cdccb56c16f8233ed476',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '00',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單處理中
     */
    public function testPaymentTrackingVerifyOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $params = [
            'sign' => 'a4fb38a218274f7e0184bdeafa5393a2',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '00',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $params = [
            'sign' => '6e83b7c03f8fdd290236ce81bab51314',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '02',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004225',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $params = [
            'sign' => '4b65d9e5ea5335e6ab085dec5f39418d',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '03',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201705040000006242',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付金額錯誤
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $params = [
            'sign' => '4b65d9e5ea5335e6ab085dec5f39418d',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '03',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004224',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $params = [
            'sign' => '4b65d9e5ea5335e6ab085dec5f39418d',
            'amount' => '1039',
            'trade_no' => '201708312333586453791744',
            'status' => '03',
            'order_time' => '20170831210235',
            'finish_time' => '',
            'code' => '0000',
            'out_trade_no' => '201708310000004224',
            'msg' => '调用接口成功!',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => 'TOF00086',
            'orderId' => '201708310000004224',
            'amount' => '10.39',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $cRPay = new CRPay();
        $cRPay->setPrivateKey('test');
        $cRPay->setOptions($sourceData);
        $cRPay->paymentTrackingVerify();
    }
}
