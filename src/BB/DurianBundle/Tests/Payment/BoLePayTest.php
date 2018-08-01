<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BoLePay;
use Buzz\Message\Response;

class BoLePayTest extends DurianTestCase
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

        $boLePay = new BoLePay();
        $boLePay->getVerifyData();
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

        $options = ['paymentVendorId' => '1090'];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
    }

    /**
     * 測試網銀支付時沒指定支付參數
     */
    public function testBankPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $options = ['paymentVendorId' => '1'];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
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
            'paymentVendorId' => '999',
            'number' => '100774',
            'username' => 'php1test',
            'ip' => '10.123.125.169',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
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
            'number' => '100774',
            'username' => 'php1test',
            'ip' => '10.123.125.169',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => '',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回is_succ
     */
    public function testPayReturnWithoutIssucc()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'result_code' => 'E1000000',
            'result_msg' => '请求处理成功',
            'sign' => '59540b8001d8b1aec34dc3559097b393',
            'fail_reason' => NULL,
            'result_json' => '{"wx_pay_sm_url":"weixin://wxpay/bizpayurl?pr=MKrCNv8","wx_pay_hx_url":null,"base6' .
                '4QRCode":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA"}',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
    }

    /**
     * 測試支付時返回低於每次交易下限
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '低于每次交易下限',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'is_succ' => 'F',
            'result_code' => 'E1010027',
            'result_msg' => '请求处理失败',
            'sign' => NULL,
            'fail_reason' => '低于每次交易下限',
            'result_json' => NULL,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且沒有錯誤訊息
     */
    public function testPayReturnNotSuccessAndWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'is_succ' => 'F',
            'result_code' => 'E1010027',
            'result_msg' => '请求处理失败',
            'sign' => NULL,
            'result_json' => NULL,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回result_json
     */
    public function testPayReturnWithoutResultJson()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'is_succ' => 'T',
            'result_code' => 'E1000000',
            'result_msg' => '请求处理成功',
            'fail_reason' => NULL,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'is_succ' => 'T',
            'result_code' => 'E1000000',
            'result_msg' => '请求处理成功',
            'sign' => '45a4fcbfba917f6b4edb5d0dcf0f26bd',
            'fail_reason' => NULL,
            'result_json' => '{"wx_pay_hx_url":null,"base64QRCode":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA"}',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->getVerifyData();
    }

    /**
     * 測試支付寶手機支付
     */
    public function testAliPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1098',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'is_succ' => 'T',
            'result_code' => 'E1000000',
            'result_msg' => '请求处理成功',
            'sign' => 'c2df8fea6ada70eb0d0ed2eff1770b00',
            'fail_reason' => NULL,
            'result_json' => '{"ali_pay_sm_url":"https://qr.alipay.com/bax00286ltk4yllcwmvg60b9","base64QRCode":"d' .
                'ata:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA"}',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $data = $boLePay->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/bax00286ltk4yllcwmvg60b9', $data['act_url']);
    }

    /**
     * 測試微信二維
     */
    public function testWxScan()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'is_succ' => 'T',
            'result_code' => 'E1000000',
            'result_msg' => '请求处理成功',
            'sign' => '66e27935c8e8a49a112c53f5bf0ef9d0',
            'fail_reason' => NULL,
            'result_json' => '{"wx_pay_sm_url":"weixin://wxpay/bizpayurl?pr=MKrCNv8","wx_pay_hx_url":null,"base6' .
                '4QRCode":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA"}',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $data = $boLePay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=MKrCNv8', $boLePay->getQrcode());
    }

    /**
     * 測試支付寶二維
     */
    public function testAliScan()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'is_succ' => 'T',
            'result_code' => 'E1000000',
            'result_msg' => '请求处理成功',
            'sign' => 'c2df8fea6ada70eb0d0ed2eff1770b00',
            'fail_reason' => NULL,
            'result_json' => '{"ali_pay_sm_url":"https://qr.alipay.com/bax00286ltk4yllcwmvg60b9","base64QRCode":"d' .
                'ata:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA"}',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $data = $boLePay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.alipay.com/bax00286ltk4yllcwmvg60b9', $boLePay->getQrcode());
    }

    /**
     * 測試QQ二維
     */
    public function testQQScan()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '100774',
            'username' => 'php1test',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'is_succ' => 'T',
            'result_code' => 'E1000000',
            'result_msg' => '请求处理成功',
            'sign' => 'de436eee0cb9509b9f5e8b12f68403be',
            'fail_reason' => NULL,
            'result_json' => '{"qq_pay_sm_url":"https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=23&t=5' .
                'V","base64QRCode":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA"}',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $boLePay = new BoLePay();
        $boLePay->setContainer($this->container);
        $boLePay->setClient($this->client);
        $boLePay->setResponse($response);
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $data = $boLePay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=23&t=5V', $boLePay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testBankPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '100774',
            'username' => 'php1test',
            'ip' => '10.123.125.169',
            'orderCreateDate' => '2017-09-13 10:06:06',
            'orderId' => '201709130000004640',
            'amount' => '1.01',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $requestData = $boLePay->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals('gateway_pay', $requestData['service']);
        $this->assertEquals('UTF-8', $requestData['input_charset']);
        $this->assertEquals('MD5', $requestData['sign_type']);
        $this->assertEquals('20170913100606', $requestData['request_time']);
        $this->assertEquals($options['orderId'], $requestData['out_trade_no']);
        $this->assertEquals($options['amount'], $requestData['amount_str']);
        $this->assertEquals($options['notify_url'], $requestData['return_url']);
        $this->assertEquals('', $requestData['tran_time']);
        $this->assertEquals($options['ip'], $requestData['tran_ip']);
        $this->assertEquals('', $requestData['buyer_name']);
        $this->assertEquals('', $requestData['buyer_contact']);
        $this->assertEquals($options['username'], $requestData['good_name']);
        $this->assertEquals($options['username'], $requestData['goods_detail']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
        $this->assertEquals('', $requestData['receiver_address']);
        $this->assertEquals($options['notify_url'], $requestData['redirect_url']);
        $this->assertEquals('576b7edb0faa52d237970163516a5240', $requestData['sign']);
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

        $boLePay = new BoLePay();
        $boLePay->verifyOrderPayment([]);
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

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->verifyOrderPayment([]);
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
            'request_time' => '2017-09-13 09:17:34',
            'input_charset' => 'utf-8',
            'sign_type' => 'MD5',
            'content' => 'amount_fee=0.010100&amount_str=1.010000&business_type=8&create_time=2017-09-13 09:15:43&m' .
                'odified_time=2017-09-13 09:17:34&out_trade_no=201709130000004640&status=1&trade_id=TT2017091309790832',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->verifyOrderPayment([]);
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
            'request_time' => '2017-09-13 09:17:34',
            'input_charset' => 'utf-8',
            'sign' => '930b4aca4ab0b1e02dfac11701149122',
            'sign_type' => 'MD5',
            'content' => 'amount_fee=0.010100&amount_str=1.010000&business_type=8&create_time=2017-09-13 09:15:43&m' .
                'odified_time=2017-09-13 09:17:34&out_trade_no=201709130000004640&status=1&trade_id=TT2017091309790832',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果Content缺少必要參數
     */
    public function testReturnButContentWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'request_time' => '2017-09-13 09:17:34',
            'input_charset' => 'utf-8',
            'sign' => '1309a29d135abe98c2076e2c2bbf69ca',
            'sign_type' => 'MD5',
            'content' => 'amount_fee=0.010100&amount_str=1.010000&business_type=8&create_time=2017-09-13 09:15:43&m' .
                'odified_time=2017-09-13 09:17:34&status=0&trade_id=TT2017091309790832',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'request_time' => '2017-09-13 09:17:34',
            'input_charset' => 'utf-8',
            'sign' => '7d59d6858c7b4a7890eba9bab95f52b5',
            'sign_type' => 'MD5',
            'content' => 'amount_fee=0.010100&amount_str=1.010000&business_type=8&create_time=2017-09-13 09:15:43&m' .
                'odified_time=2017-09-13 09:17:34&out_trade_no=201709130000004640&status=0&trade_id=TT2017091309790832',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'request_time' => '2017-09-13 09:17:34',
            'input_charset' => 'utf-8',
            'sign' => '92e85a6b96057e807455df4d55be23b0',
            'sign_type' => 'MD5',
            'content' => 'amount_fee=0.010100&amount_str=1.010000&business_type=8&create_time=2017-09-13 09:15:43&m' .
                'odified_time=2017-09-13 09:17:34&out_trade_no=201709130000004640&status=2&trade_id=TT2017091309790832',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->verifyOrderPayment([]);
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
            'request_time' => '2017-09-13 09:17:34',
            'input_charset' => 'utf-8',
            'sign' => '776b0e610e0cfb7d560bd083ea601dc3',
            'sign_type' => 'MD5',
            'content' => 'amount_fee=0.010100&amount_str=1.010000&business_type=8&create_time=2017-09-13 09:15:43&m' .
                'odified_time=2017-09-13 09:17:34&out_trade_no=201709130000004640&status=1&trade_id=TT2017091309790832',
        ];

        $entry = ['id' => '201503220000000555'];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->verifyOrderPayment($entry);
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
            'request_time' => '2017-09-13 09:17:34',
            'input_charset' => 'utf-8',
            'sign' => '776b0e610e0cfb7d560bd083ea601dc3',
            'sign_type' => 'MD5',
            'content' => 'amount_fee=0.010100&amount_str=1.010000&business_type=8&create_time=2017-09-13 09:15:43&m' .
                'odified_time=2017-09-13 09:17:34&out_trade_no=201709130000004640&status=1&trade_id=TT2017091309790832',
        ];

        $entry = [
            'id' => '201709130000004640',
            'amount' => '15.00',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'request_time' => '2017-09-13 09:17:34',
            'input_charset' => 'utf-8',
            'sign' => '776b0e610e0cfb7d560bd083ea601dc3',
            'sign_type' => 'MD5',
            'content' => 'amount_fee=0.010100&amount_str=1.010000&business_type=8&create_time=2017-09-13 09:15:43&m' .
                'odified_time=2017-09-13 09:17:34&out_trade_no=201709130000004640&status=1&trade_id=TT2017091309790832',
        ];

        $entry = [
            'id' => '201709130000004640',
            'amount' => '1.01',
        ];

        $boLePay = new BoLePay();
        $boLePay->setPrivateKey('test');
        $boLePay->setOptions($options);
        $boLePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $boLePay->getMsg());
    }
}
