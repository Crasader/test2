<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JinAnFu;
use Buzz\Message\Response;

class JinAnFuTest extends DurianTestCase
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

        $jinAnFu = new JinAnFu();
        $jinAnFu->getVerifyData();
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

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->getVerifyData();
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
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '100',
            'notify_url' => 'http://pay.return/',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => '',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=rNqnLQe","merchno":"678110154110001","message":"下单成功",' .
            '"refno":"800000152040","traceno":"201609050000004645"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '下单失败[400],total_fee:Must be greater or equal to [1]',
            180130
        );

        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"678110154110001","message":"下单失败[400],total_fee:Must be greater or equal to [1]",' .
            '"refno":"800000152037","respCode":"30","traceno":"201609050000004644"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"678110154110001","message":"下单成功","refno":"800000152040","respCode":"00","traceno' .
            '":"201609050000004645"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testWxPay()
    {
        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=rNqnLQe","merchno":"678110154110001","message":"下单成' .
            '功","refno":"800000152040","respCode":"00","traceno":"201609050000004645"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $data = $jinAnFu->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=rNqnLQe', $jinAnFu->getQrcode());
    }

    /**
     * 測試手機支付時沒有帶入verify_url的情況
     */
    public function testWapPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://pay.return/',
            'verify_url' => '',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回respCode
     */
    public function testWapPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=rNqnLQe","merchno":"678110154110001","message":"下单成功",' .
            '"refno":"800000152040","traceno":"201609050000004645"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試手機支付時返回提交失敗
     */
    public function testWapPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败,商户的手续费未设置',
            180130
        );

        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"678110154110001","message":"交易失败,商户的手续费未设置",' .
            '"refno":"800000152037","respCode":"30","traceno":"201609050000004644"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回barCode
     */
    public function testWapPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"678110154110001","message":"下单成功","refno":"800000152040","respCode":"00","traceno' .
            '":"201609050000004645"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testWapPay()
    {
        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=rNqnLQe","merchno":"678110154110001","message":"下单成' .
            '功","refno":"800000152040","respCode":"00","traceno":"201609050000004645"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $data = $jinAnFu->getVerifyData();

        $this->assertEquals('weixin://wxpay/bizpayurl?pr=rNqnLQe', $data['act_url']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '678110154110001',
            'amount' => '100',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1',
            'notify_url' => 'http://pay.return/',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $encodeData = $jinAnFu->getVerifyData();

        $notifyUrl = sprintf(
            '%s?vendor_id=%s',
            $options['notify_url'],
            $options['paymentVendorId']
        );

        $this->assertEquals($options['number'], $encodeData['merchno']);
        $this->assertEquals($options['amount'], $encodeData['amount']);
        $this->assertEquals($options['orderId'], $encodeData['traceno']);
        $this->assertEquals('2', $encodeData['channel']);
        $this->assertEquals('3002', $encodeData['bankCode']);
        $this->assertEquals('2', $encodeData['settleType']);
        $this->assertEquals($notifyUrl, $encodeData['notifyUrl']);
        $this->assertEquals('d794c3d3bf3a4f2ffdc513db4a3e72d5', $encodeData['signature']);
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

        $jinAnFu = new JinAnFu();
        $jinAnFu->verifyOrderPayment([]);
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

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->verifyOrderPayment([]);
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
            'merchno' => '678110154110001',
            'customerno' => '',
            'status' => '1',
            'traceno' => '201609050000004640',
            'orderno' => '800000151260',
            'merchName' => '盄奻聆彸A',
            'channelOrderno' => '4004132001201609053147481176',
            'amount' => '0.01',
            'transDate' => '2016-09-05',
            'channelTraceno' => '101590000547201609054041520358',
            'transTime' => '10:49:04',
            'payType' => '2',
            'openId' => 'weixin://wxpay/bizpayurl?pr=xlXy82d',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->verifyOrderPayment([]);
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
            'merchno' => '678110154110001',
            'customerno' => '',
            'status' => '1',
            'traceno' => '201609050000004640',
            'orderno' => '800000151260',
            'merchName' => '盄奻聆彸A',
            'channelOrderno' => '4004132001201609053147481176',
            'amount' => '0.01',
            'transDate' => '2016-09-05',
            'channelTraceno' => '101590000547201609054041520358',
            'transTime' => '10:49:04',
            'payType' => '2',
            'signature' => 'DB27EDA3E5C557EAF8523378B20B0F49',
            'openId' => 'weixin://wxpay/bizpayurl?pr=xlXy82d',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少回傳vendor_id
     */
    public function testReturnWithoutVendorId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'merchno' => '678110154110001',
            'customerno' => '',
            'status' => '1',
            'traceno' => '201609050000004640',
            'orderno' => '800000151260',
            'merchName' => '盄奻聆彸A',
            'channelOrderno' => '4004132001201609053147481176',
            'amount' => '0.01',
            'transDate' => '2016-09-05',
            'channelTraceno' => '101590000547201609054041520358',
            'transTime' => '10:49:04',
            'payType' => '2',
            'openId' => 'weixin://wxpay/bizpayurl?pr=xlXy82d',
            'signature' => 'ABDB017F9DC0157AA1C3A2D90672ABDF',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->verifyOrderPayment([]);
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
            'merchno' => '678110154110001',
            'customerno' => '',
            'status' => '1',
            'traceno' => '201609050000004640',
            'orderno' => '800000151260',
            'merchName' => '盄奻聆彸A',
            'channelOrderno' => '4004132001201609053147481176',
            'amount' => '0.01',
            'transDate' => '2016-09-05',
            'channelTraceno' => '101590000547201609054041520358',
            'transTime' => '10:49:04',
            'payType' => '2',
            'signature' => 'ABDB017F9DC0157AA1C3A2D90672ABDF',
            'openId' => 'weixin://wxpay/bizpayurl?pr=xlXy82d',
            'vendor_id' => '1',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->verifyOrderPayment([]);
    }

    /**
     * 測試二維返回時支付失敗
     */
    public function testReturnWithWxPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'merchno' => '678110154110001',
            'customerno' => '',
            'status' => '2',
            'traceno' => '201609050000004640',
            'orderno' => '800000151260',
            'merchName' => '盄奻聆彸A',
            'channelOrderno' => '4004132001201609053147481176',
            'amount' => '0.01',
            'transDate' => '2016-09-05',
            'channelTraceno' => '101590000547201609054041520358',
            'transTime' => '10:49:04',
            'payType' => '2',
            'signature' => '8A6394AC496C9C306F967300A69A28E3',
            'openId' => 'weixin://wxpay/bizpayurl?pr=xlXy82d',
            'vendor_id' => '1090',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->verifyOrderPayment([]);
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
            'merchno' => '678110154110001',
            'customerno' => '',
            'status' => '1',
            'traceno' => '201609050000004640',
            'orderno' => '800000151260',
            'merchName' => '盄奻聆彸A',
            'channelOrderno' => '4004132001201609053147481176',
            'amount' => '0.01',
            'transDate' => '2016-09-05',
            'channelTraceno' => '101590000547201609054041520358',
            'transTime' => '10:49:04',
            'payType' => '2',
            'signature' => 'ABDB017F9DC0157AA1C3A2D90672ABDF',
            'openId' => 'weixin://wxpay/bizpayurl?pr=xlXy82d',
            'vendor_id' => '1090',
        ];

        $entry = ['id' => '201608150000004475'];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->verifyOrderPayment($entry);
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
            'merchno' => '678110154110001',
            'customerno' => '',
            'status' => '1',
            'traceno' => '201609050000004640',
            'orderno' => '800000151260',
            'merchName' => '盄奻聆彸A',
            'channelOrderno' => '4004132001201609053147481176',
            'amount' => '0.01',
            'transDate' => '2016-09-05',
            'channelTraceno' => '101590000547201609054041520358',
            'transTime' => '10:49:04',
            'payType' => '2',
            'signature' => 'ABDB017F9DC0157AA1C3A2D90672ABDF',
            'openId' => 'weixin://wxpay/bizpayurl?pr=xlXy82d',
            'vendor_id' => '1090',
        ];

        $entry = [
            'id' => '201609050000004640',
            'amount' => '0.1',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchno' => '678110154110001',
            'customerno' => '',
            'status' => '1',
            'traceno' => '201609050000004640',
            'orderno' => '800000151260',
            'merchName' => '盄奻聆彸A',
            'channelOrderno' => '4004132001201609053147481176',
            'amount' => '0.01',
            'transDate' => '2016-09-05',
            'channelTraceno' => '101590000547201609054041520358',
            'transTime' => '10:49:04',
            'payType' => '2',
            'signature' => 'ABDB017F9DC0157AA1C3A2D90672ABDF',
            'openId' => 'weixin://wxpay/bizpayurl?pr=xlXy82d',
            'vendor_id' => '1090',
        ];

        $entry = [
            'id' => '201609050000004640',
            'amount' => '0.01',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $jinAnFu->getMsg());
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

        $jinAnFu = new JinAnFu();
        $jinAnFu->paymentTracking();
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

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->paymentTracking();
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
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線異常
     */
    public function testTrackingReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
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
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳結果為空
     */
    public function testTrackingReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
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
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }

    /**
     * 測試訂單查詢異常
     */
    public function testTrackingReturnWithErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '找不到交易',
            180123
        );

        $options = [
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"找不到交易","respCode":"25"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢結果缺少回傳參數
     */
    public function testTrackingReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"未支付","respCode":"0"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }

    /**
     * 測試二維訂單查詢結果訂單未支付
     */
    public function testTrackingWxReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"未支付","respCode":"0"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }

    /**
     * 測試網銀訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"未支付","respCode":"0","status":"1"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }

    /**
     * 測試二維訂單查詢結果支付失敗
     */
    public function testTrackingWxReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"支付失败","respCode":"2"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
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
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"message":"支付失败","respCode":"2","status":"5"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('test');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
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
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"channelOrderno":"4004132001201609053147481176","message":"交易成功","orderno":"800000151260",' .
            '"payType":"2","refno":"800000151260","respCode":"1","traceno":"201609050000004642"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '678110154110001',
            'orderId' => '201609050000004640',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $result = '{"channelOrderno":"4004132001201609053147481176","message":"交易成功","orderno":"800000151260",' .
            '"payType":"2","refno":"800000151260","respCode":"1","traceno":"201609050000004640"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $jinAnFu = new JinAnFu();
        $jinAnFu->setContainer($this->container);
        $jinAnFu->setClient($this->client);
        $jinAnFu->setResponse($response);
        $jinAnFu->setPrivateKey('b36c55b7aecb4b3e884e7bba8c890884');
        $jinAnFu->setOptions($options);
        $jinAnFu->paymentTracking();
    }
}
