<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZFTong;
use Buzz\Message\Response;

class ZFTongTest extends DurianTestCase
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

        $zFTong = new ZFTong();
        $zFTong->getVerifyData();
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

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->getVerifyData();
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
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQrCodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回respCode
     */
    public function testQrCodePayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respMessage":"没有可用支付渠道"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zFTong = new ZFTong();
        $zFTong->setContainer($this->container);
        $zFTong->setClient($this->client);
        $zFTong->setResponse($response);
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->getVerifyData();
    }

    /**
     * 測試二維支付時返回respCode錯誤但沒有返回respMessage
     */
    public function testQrCodePayReturnNotSuccessWithoutRespMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"F0008"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zFTong = new ZFTong();
        $zFTong->setContainer($this->container);
        $zFTong->setClient($this->client);
        $zFTong->setResponse($response);
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '没有可用支付渠道',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respMessage":"没有可用支付渠道","respCode":"F0008"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zFTong = new ZFTong();
        $zFTong->setContainer($this->container);
        $zFTong->setClient($this->client);
        $zFTong->setResponse($response);
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回codeUrl
     */
    public function testQrCodePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zFTong = new ZFTong();
        $zFTong->setContainer($this->container);
        $zFTong->setClient($this->client);
        $zFTong->setResponse($response);
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"codeUrl":"https://qpay.qq.com/qr/67de7750","respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zFTong = new ZFTong();
        $zFTong->setContainer($this->container);
        $zFTong->setClient($this->client);
        $zFTong->setResponse($response);
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $data = $zFTong->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/67de7750', $zFTong->getQrcode());
    }


    /**
     * 測試QQ手機支付
     */
    public function testQQWapPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1104',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"codeUrl":"https://qpay.qq.com/qr/5fc479b9","respCode":"S0001"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $zFTong = new ZFTong();
        $zFTong->setContainer($this->container);
        $zFTong->setClient($this->client);
        $zFTong->setResponse($response);
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $data = $zFTong->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/5fc479b9', $data['post_url']);
        $this->assertEquals([], $data['params']);
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'postUrl' => 'https://ebank.ztpo.cn/payment/v1/order/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $data = $zFTong->getVerifyData();


        $postUrl = $options['postUrl'] . $data['params']['merchantId'] . '-' . $data['params']['orderNo'];

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals($options['username'], $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('ICBC', $data['params']['defaultbank']);
        $this->assertEquals('web', $data['params']['isApp']);
        $this->assertEquals($options['number'], $data['params']['merchantId']);
        $this->assertEquals($options['notify_url'], $data['params']['notifyUrl']);
        $this->assertEquals($options['orderId'], $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals($options['notify_url'], $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals($options['username'], $data['params']['title']);
        $this->assertEquals($options['amount'], $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('828CAB9FDF2E1F56A6400440AAB966B6791823D4', $data['params']['sign']);
    }

    /**
     * 測試網銀快捷支付
     */
    public function testQuickPay()
    {
        $options = [
            'postUrl' => 'https://ebank.ztpo.cn/payment/v1/order/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '278',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $data = $zFTong->getVerifyData();

        $postUrl = $options['postUrl'] . $data['params']['merchantId'] . '-' . $data['params']['orderNo'];

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals($options['username'], $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('QUICKPAY', $data['params']['defaultbank']);
        $this->assertEquals('web', $data['params']['isApp']);
        $this->assertEquals($options['number'], $data['params']['merchantId']);
        $this->assertEquals($options['notify_url'], $data['params']['notifyUrl']);
        $this->assertEquals($options['orderId'], $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('bankPay', $data['params']['paymethod']);
        $this->assertEquals($options['notify_url'], $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals($options['username'], $data['params']['title']);
        $this->assertEquals($options['amount'], $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('2DC3E138D89CEE70266DC09A3DAF124891F76BBC', $data['params']['sign']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'postUrl' => 'https://ebank.ztpo.cn/payment/v1/order/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1097',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '127.0.0.1',
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $data = $zFTong->getVerifyData();

        $postUrl = $options['postUrl'] . $data['params']['merchantId'] . '-' . $data['params']['orderNo'];

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals($options['username'], $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('WXPAY', $data['params']['defaultbank']);
        $this->assertEquals('H5', $data['params']['isApp']);
        $this->assertEquals($options['number'], $data['params']['merchantId']);
        $this->assertEquals($options['notify_url'], $data['params']['notifyUrl']);
        $this->assertEquals($options['orderId'], $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals($options['notify_url'], $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals($options['username'], $data['params']['title']);
        $this->assertEquals($options['amount'], $data['params']['totalFee']);
        $this->assertEquals($options['ip'], $data['params']['userIp']);
        $this->assertEquals('appName', $data['params']['appName']);
        $this->assertEquals('AppPay', $data['params']['appMsg']);
        $this->assertEquals('android', $data['params']['appType']);
        $this->assertEquals($options['notify_url'], $data['params']['backUrl']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('F7B6F61A9FFEA865A8E4C07937390D0763392F90', $data['params']['sign']);
    }

    /**
     * 測試京東手機之支付
     */
    public function testJDPhonePay()
    {
        $options = [
            'postUrl' => 'https://ebank.ztpo.cn/payment/v1/order/',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1108',
            'number' => '100000000002467',
            'orderId' => '201712130000008038',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $data = $zFTong->getVerifyData();

        $postUrl = $options['postUrl'] . $data['params']['merchantId'] . '-' . $data['params']['orderNo'];

        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEquals($options['username'], $data['params']['body']);
        $this->assertEquals('', $data['params']['buyerEmail']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('JDPAY', $data['params']['defaultbank']);
        $this->assertEquals('H5', $data['params']['isApp']);
        $this->assertEquals($options['number'], $data['params']['merchantId']);
        $this->assertEquals($options['notify_url'], $data['params']['notifyUrl']);
        $this->assertEquals($options['orderId'], $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['paymentType']);
        $this->assertEquals('directPay', $data['params']['paymethod']);
        $this->assertEquals($options['notify_url'], $data['params']['returnUrl']);
        $this->assertEquals('', $data['params']['riskItem']);
        $this->assertEquals('online_pay', $data['params']['service']);
        $this->assertEquals($options['username'], $data['params']['title']);
        $this->assertEquals($options['amount'], $data['params']['totalFee']);
        $this->assertEquals('SHA', $data['params']['signType']);
        $this->assertEquals('B1498D5A8BEE03908EB8D906E287DA85FF381540', $data['params']['sign']);
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

        $zFTong = new ZFTong();
        $zFTong->verifyOrderPayment([]);
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

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->verifyOrderPayment([]);
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
            'gmt_create' => '2017-12-13 16:15:53',
            'order_no' => '201712130000008038',
            'gmt_payment' => '2017-12-13 16:15:53',
            'seller_email' => 'e3168025031@163.com',
            'notify_time' => '2017-12-13 16:15:53',
            'quantity' => '1',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
            'gmt_logistics_modify' => '2017-12-13 16:15:53',
            'notify_id' => '88d5ccc766fc4070ace1ecb5b421370b',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.01',
            'total_fee' => '0.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101712136620498',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002467',
            'is_total_fee_adjust' => '0',
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->verifyOrderPayment([]);
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
            'gmt_create' => '2017-12-13 16:15:53',
            'order_no' => '201712130000008038',
            'gmt_payment' => '2017-12-13 16:15:53',
            'seller_email' => 'e3168025031@163.com',
            'notify_time' => '2017-12-13 16:15:53',
            'quantity' => '1',
            'sign' => 'FADB4F43EDC2284840CD227CC5B5B51B071009F4',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
            'gmt_logistics_modify' => '2017-12-13 16:15:53',
            'notify_id' => '88d5ccc766fc4070ace1ecb5b421370b',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.01',
            'total_fee' => '0.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101712136620498',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002467',
            'is_total_fee_adjust' => '0',
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->verifyOrderPayment([]);
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
            'gmt_create' => '2017-12-13 16:15:53',
            'order_no' => '201712130000008038',
            'gmt_payment' => '2017-12-13 16:15:53',
            'seller_email' => 'e3168025031@163.com',
            'notify_time' => '2017-12-13 16:15:53',
            'quantity' => '1',
            'sign' => '0bd86f259904796ddf38ed84f4b453db5a4abf9e',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'F',
            'title' => 'php1test',
            'gmt_logistics_modify' => '2017-12-13 16:15:53',
            'notify_id' => '0b81b91e22c3c6a7348f8b451327ccb641fa002e',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.01',
            'total_fee' => '0.01',
            'trade_status' => 'FAILED',
            'trade_no' => '101712136620498',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002467',
            'is_total_fee_adjust' => '0',
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->verifyOrderPayment([]);
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
            'gmt_create' => '2017-12-13 16:15:53',
            'order_no' => '201712130000008038',
            'gmt_payment' => '2017-12-13 16:15:53',
            'seller_email' => 'e3168025031@163.com',
            'notify_time' => '2017-12-13 16:15:53',
            'quantity' => '1',
            'sign' => '0b81b91e22c3c6a7348f8b451327ccb641fa002e',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
            'gmt_logistics_modify' => '2017-12-13 16:15:53',
            'notify_id' => '834cf14dc3fe911aeccfee6df5aafa46f41339f2',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.01',
            'total_fee' => '0.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101712136620498',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002467',
            'is_total_fee_adjust' => '0',
        ];

        $entry = ['id' => '201503220000000555'];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->verifyOrderPayment($entry);
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
            'gmt_create' => '2017-12-13 16:15:53',
            'order_no' => '201712130000008038',
            'gmt_payment' => '2017-12-13 16:15:53',
            'seller_email' => 'e3168025031@163.com',
            'notify_time' => '2017-12-13 16:15:53',
            'quantity' => '1',
            'sign' => '0b81b91e22c3c6a7348f8b451327ccb641fa002e',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
            'gmt_logistics_modify' => '2017-12-13 16:15:53',
            'notify_id' => '834cf14dc3fe911aeccfee6df5aafa46f41339f2',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.01',
            'total_fee' => '0.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101712136620498',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002467',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201712130000008038',
            'amount' => '15.00',
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'gmt_create' => '2017-12-13 16:15:53',
            'order_no' => '201712130000008038',
            'gmt_payment' => '2017-12-13 16:15:53',
            'seller_email' => 'e3168025031@163.com',
            'notify_time' => '2017-12-13 16:15:53',
            'quantity' => '1',
            'sign' => '0b81b91e22c3c6a7348f8b451327ccb641fa002e',
            'discount' => '0.00',
            'body' => 'php1test',
            'is_success' => 'T',
            'title' => 'php1test',
            'gmt_logistics_modify' => '2017-12-13 16:15:53',
            'notify_id' => '834cf14dc3fe911aeccfee6df5aafa46f41339f2',
            'notify_type' => 'WAIT_TRIGGER',
            'payment_type' => '1',
            'ext_param2' => 'WXPAY',
            'price' => '0.01',
            'total_fee' => '0.01',
            'trade_status' => 'TRADE_FINISHED',
            'trade_no' => '101712136620498',
            'signType' => 'SHA',
            'seller_actions' => 'SEND_GOODS',
            'seller_id' => '100000000002467',
            'is_total_fee_adjust' => '0',
        ];

        $entry = [
            'id' => '201712130000008038',
            'amount' => '0.01',
        ];

        $zFTong = new ZFTong();
        $zFTong->setPrivateKey('test');
        $zFTong->setOptions($options);
        $zFTong->verifyOrderPayment($entry);

        $this->assertEquals('success', $zFTong->getMsg());
    }
}
