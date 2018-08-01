<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YaFuPay;
use Buzz\Message\Response;

class YaFuPayTest extends DurianTestCase
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

        $yaFuPay = new YaFuPay();
        $yaFuPay->getVerifyData();
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

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->getVerifyData();
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
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1.01',
            'username' => 'php1test',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->getVerifyData();
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
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回code
     */
    public function testQrCodePayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'msg' => '交易异常，请联系在线客服！',
            'sign' => 'BDBDC22869D3C291D1A6054BB057D517',

        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yaFuPay = new YaFuPay();
        $yaFuPay->setContainer($this->container);
        $yaFuPay->setClient($this->client);
        $yaFuPay->setResponse($response);
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易异常，请联系在线客服！',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '900001',
            'msg' => '交易异常，请联系在线客服！',
            'sign' => 'BDBDC22869D3C291D1A6054BB057D517',

        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yaFuPay = new YaFuPay();
        $yaFuPay->setContainer($this->container);
        $yaFuPay->setClient($this->client);
        $yaFuPay->setResponse($response);
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回busContent
     */
    public function testQrCodePayReturnWithoutBusContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'contentType' => '01',
            'orderNo' => '20171013092648658997',
            'merOrderNo' => '201710130000005094',
            'consumerNo' => '20781',
            'transAmt' => '0.10',
            'orderStatus' => '0',
            'code' => '000000',
            'msg' => 'success',
            'sign' => '641353F0924230F1265243F12E405250',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yaFuPay = new YaFuPay();
        $yaFuPay->setContainer($this->container);
        $yaFuPay->setClient($this->client);
        $yaFuPay->setResponse($response);
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'busContent' => 'weixin://wxpay/bizpayurl?pr=UZKRB2o',
            'contentType' => '01',
            'orderNo' => '20171013092648658997',
            'merOrderNo' => '201710130000005094',
            'consumerNo' => '20781',
            'transAmt' => '0.10',
            'orderStatus' => '0',
            'code' => '000000',
            'msg' => 'success',
            'sign' => '641353F0924230F1265243F12E405250',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yaFuPay = new YaFuPay();
        $yaFuPay->setContainer($this->container);
        $yaFuPay->setClient($this->client);
        $yaFuPay->setResponse($response);
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $data = $yaFuPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=UZKRB2o', $yaFuPay->getQrcode());
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1.01',
            'username' => 'php1test',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $data = $yaFuPay->getVerifyData();

        $this->assertEquals('3.0', $data['version']);
        $this->assertEquals($options['number'], $data['consumerNo']);
        $this->assertEquals($options['orderId'], $data['merOrderNo']);
        $this->assertEquals($options['amount'], $data['transAmt']);
        $this->assertEquals($options['notify_url'], $data['backUrl']);
        $this->assertEquals($options['notify_url'], $data['frontUrl']);
        $this->assertEquals('ICBC', $data['bankCode']);
        $this->assertEquals('0101', $data['payType']);
        $this->assertEquals($options['username'], $data['goodsName']);
        $this->assertEquals('php1test', $data['merRemark']);
        $this->assertEquals('', $data['buyIp']);
        $this->assertEquals('', $data['buyPhome']);
        $this->assertEquals('', $data['shopName']);
        $this->assertEquals('268ee55afb5cbd0b177b44969253d78b', $data['sign']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1097',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1.01',
            'username' => 'php1test',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $data = $yaFuPay->getVerifyData();

        $this->assertEquals('3.0', $data['version']);
        $this->assertEquals($options['number'], $data['consumerNo']);
        $this->assertEquals($options['orderId'], $data['merOrderNo']);
        $this->assertEquals($options['amount'], $data['transAmt']);
        $this->assertEquals($options['notify_url'], $data['backUrl']);
        $this->assertEquals($options['notify_url'], $data['frontUrl']);
        $this->assertEquals('0901', $data['payType']);
        $this->assertEquals($options['username'], $data['goodsName']);
        $this->assertEquals('php1test', $data['merRemark']);
        $this->assertEquals('', $data['buyIp']);
        $this->assertEquals('', $data['buyPhome']);
        $this->assertEquals('', $data['shopName']);
        $this->assertEquals('b59bcbe0e78a17ab243578af35421953', $data['sign']);
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

        $yaFuPay = new YaFuPay();
        $yaFuPay->verifyOrderPayment([]);
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

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->verifyOrderPayment([]);
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
            'consumerNo' => '20781',
            'merOrderNo' => '201710130000005096',
            'orderNo' => '20171013092938707063',
            'orderStatus' => '1',
            'payType' => '0202',
            'transAmt' => '0.10',
            'version' => '3.0',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment([]);
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
            'consumerNo' => '20781',
            'merOrderNo' => '201710130000005096',
            'orderNo' => '20171013092938707063',
            'orderStatus' => '1',
            'payType' => '0202',
            'sign' => 'CCF04CC6585AF55C7B284D47F61AF31E',
            'transAmt' => '0.10',
            'version' => '3.0',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment([]);
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
            'consumerNo' => '20781',
            'merOrderNo' => '201710130000005096',
            'orderNo' => '20171013092938707063',
            'orderStatus' => '0',
            'payType' => '0202',
            'sign' => '3168A6FF9518EC8FC20000187756246D',
            'transAmt' => '0.10',
            'version' => '3.0',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment([]);
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
            'consumerNo' => '20781',
            'merOrderNo' => '201710130000005096',
            'orderNo' => '20171013092938707063',
            'orderStatus' => '2',
            'payType' => '0202',
            'sign' => 'A86BED3CCF5B56FD25433DE91FF42EA2',
            'transAmt' => '0.10',
            'version' => '3.0',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment([]);
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
            'consumerNo' => '20781',
            'merOrderNo' => '201710130000005096',
            'orderNo' => '20171013092938707063',
            'orderStatus' => '1',
            'payType' => '0202',
            'sign' => '1F7358189E339027B0E581E1B13AC25D',
            'transAmt' => '0.10',
            'version' => '3.0',
        ];

        $entry = ['id' => '201503220000000555'];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment($entry);
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
            'consumerNo' => '20781',
            'merOrderNo' => '201710130000005096',
            'orderNo' => '20171013092938707063',
            'orderStatus' => '1',
            'payType' => '0202',
            'sign' => '1F7358189E339027B0E581E1B13AC25D',
            'transAmt' => '0.10',
            'version' => '3.0',
        ];

        $entry = [
            'id' => '201710130000005096',
            'amount' => '15.00',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'consumerNo' => '20781',
            'merOrderNo' => '201710130000005096',
            'orderNo' => '20171013092938707063',
            'orderStatus' => '1',
            'payType' => '0202',
            'sign' => '1F7358189E339027B0E581E1B13AC25D',
            'transAmt' => '0.10',
            'version' => '3.0',
        ];

        $entry = [
            'id' => '201710130000005096',
            'amount' => '0.1',
        ];

        $yaFuPay = new YaFuPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yaFuPay->getMsg());
    }
}
