<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ELongPay;
use Buzz\Message\Response;

class ELongPayTest extends DurianTestCase
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

        $eLongPay = new ELongPay();
        $eLongPay->getVerifyData();
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

        $sourceData = ['number' => ''];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '9999',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '1092',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->getVerifyData();
    }

    /**
     * 測試支付時未返回code
     */
    public function testPayNoReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'trade_no' => '1805216397752462815543296',
            'out_trade_no' => '201805210000013036',
            'total_amount' => '1.00',
            'type' => '1',
            'content' => 'https://qr.alipay.com/bax09948hzxmzan6fgrl8065',
            'message' => '调用成功',
            'biz_code' => '10010',
            'biz_message' => '提交成功',
            'sign' => 'D2428FFDD779A588F288802FEBF90C22',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '1092',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setContainer($this->container);
        $eLongPay->setClient($this->client);
        $eLongPay->setResponse($response);
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'trade_no' => '1805216397752462815543296',
            'out_trade_no' => '201805210000013036',
            'total_amount' => '1.00',
            'type' => '1',
            'content' => 'https://qr.alipay.com/bax09948hzxmzan6fgrl8065',
            'code' => '2',
            'message' => '调用失敗',
            'biz_code' => '10010',
            'biz_message' => '提交成功',
            'sign' => 'D2428FFDD779A588F288802FEBF90C22',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '1092',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setContainer($this->container);
        $eLongPay->setClient($this->client);
        $eLongPay->setResponse($response);
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->getVerifyData();
    }

    /**
     * 測試支付時未返回content
     */
    public function testPayNoReturnContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'trade_no' => '1805216397752462815543296',
            'out_trade_no' => '201805210000013036',
            'total_amount' => '1.00',
            'type' => '1',
            'code' => '0',
            'message' => '调用成功',
            'biz_code' => '10010',
            'biz_message' => '提交成功',
            'sign' => 'D2428FFDD779A588F288802FEBF90C22',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '1092',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setContainer($this->container);
        $eLongPay->setClient($this->client);
        $eLongPay->setResponse($response);
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->getVerifyData();
    }

    /**
     * 測試掃碼支付
     */
    public function testQrcodePay()
    {
        $result = [
            'trade_no' => '1805216397752462815543296',
            'out_trade_no' => '201805210000013036',
            'total_amount' => '1.00',
            'type' => '1',
            'content' => 'https://qr.alipay.com/bax09948hzxmzan6fgrl8065',
            'code' => '0',
            'message' => '调用成功',
            'biz_code' => '10010',
            'biz_message' => '提交成功',
            'sign' => 'D2428FFDD779A588F288802FEBF90C22',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '1092',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setContainer($this->container);
        $eLongPay->setClient($this->client);
        $eLongPay->setResponse($response);
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $data = $eLongPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertSame('https://qr.alipay.com/bax09948hzxmzan6fgrl8065', $eLongPay->getQrcode());
    }

    /**
     * 測試手機支付未返回action
     */
    public function testPhonePayContentWihtoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'trade_no' => '1805216397752462815543296',
            'out_trade_no' => '201805210000013036',
            'total_amount' => '1.00',
            'type' => '1',
            'content' => '<form name="punchout_form" method="post"></form>',
            'code' => '0',
            'message' => '调用成功',
            'biz_code' => '10010',
            'biz_message' => '提交成功',
            'sign' => 'D2428FFDD779A588F288802FEBF90C22',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '1098',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setContainer($this->container);
        $eLongPay->setClient($this->client);
        $eLongPay->setResponse($response);
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $data = $eLongPay->getVerifyData();
    }

    /**
     * 測試網銀支付未返回input元素
     */
    public function testPayReturnWithoutInput()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $content = '<form name="punchout_form" method="post" ' .
            'action="https://openapi.alipay.com/gateway.do?charset=UTF-8&method=alipay.trade.wap.pay">' .
            '<script type="text/javascript">document.getElementById("sform").submit();</script>';

        $result = [
            'trade_no' => '1805216397752462815543296',
            'out_trade_no' => '201805210000013036',
            'total_amount' => '1.00',
            'type' => '1',
            'content' => $content,
            'code' => '0',
            'message' => '调用成功',
            'biz_code' => '10010',
            'biz_message' => '提交成功',
            'sign' => 'D2428FFDD779A588F288802FEBF90C22',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '1098',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setContainer($this->container);
        $eLongPay->setClient($this->client);
        $eLongPay->setResponse($response);
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $content = '<form name="punchout_form" method="post" ' .
            'action="https://openapi.alipay.com/gateway.do?charset=UTF-8&method=alipay.trade.wap.pay">' .
            '<input type="hidden" name="biz_content" value="123"/>' .
            '</form>' .
            '<script type="text/javascript">document.getElementById("sform").submit();</script>';

        $result = [
            'trade_no' => '1805216397752462815543296',
            'out_trade_no' => '201805210000013036',
            'total_amount' => '1.00',
            'type' => '1',
            'content' => $content,
            'code' => '0',
            'message' => '调用成功',
            'biz_code' => '10010',
            'biz_message' => '提交成功',
            'sign' => 'D2428FFDD779A588F288802FEBF90C22',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'elong1524118582211',
            'paymentVendorId' => '1098',
            'amount' => '1.00',
            'orderId' => '201805210000013034',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-05-21 11:45:55',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setContainer($this->container);
        $eLongPay->setClient($this->client);
        $eLongPay->setResponse($response);
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $data = $eLongPay->getVerifyData();

        $this->assertEquals('https://openapi.alipay.com/gateway.do', $data['post_url']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('alipay.trade.wap.pay', $data['params']['method']);
        $this->assertEquals('123', $data['params']['biz_content']);
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

        $eLongPay = new ELongPay();
        $eLongPay->verifyOrderPayment([]);
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

        $sourceData = [];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'trade_no' => '1805216397750940761341952',
            'out_trade_no' => '201805210000013034',
            'total_amount' => 0.01,
            'goods_name' => '201805210000013034',
            'remarks' => '',
            'status' => 1000,
            'pay_time' => '2018-05-21 10:10:38',
            'version' => '1.0.0',
            'sign_type' => 'MD5',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時加密簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'trade_no' => '1805216397750940761341952',
            'out_trade_no' => '201805210000013034',
            'total_amount' => 0.01,
            'goods_name' => '201805210000013034',
            'remarks' => '',
            'status' => 1000,
            'pay_time' => '2018-05-21 10:10:38',
            'version' => '1.0.0',
            'sign_type' => 'MD5',
            'sign' => 'E072F1D8B38F3C4B32BD87B0F70F287B',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->verifyOrderPayment([]);
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

        $sourceData = [
            'trade_no' => '1805216397750940761341952',
            'out_trade_no' => '201805210000013034',
            'total_amount' => 0.01,
            'goods_name' => '201805210000013034',
            'remarks' => '',
            'status' => 1002,
            'pay_time' => '2018-05-21 10:10:38',
            'version' => '1.0.0',
            'sign_type' => 'MD5',
            'sign' => 'EBF0B77AA5B941B022B1B963512DA7A6',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'trade_no' => '1805216397750940761341952',
            'out_trade_no' => '201805210000013034',
            'total_amount' => 0.01,
            'goods_name' => '201805210000013034',
            'remarks' => '',
            'status' => 1000,
            'pay_time' => '2018-05-21 10:10:38',
            'version' => '1.0.0',
            'sign_type' => 'MD5',
            'sign' => '529670707AED3E92210CA644FE1C0C90',
        ];

        $entry = ['id' => '201704100000002210'];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->verifyOrderPayment($entry);
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

        $sourceData = [
            'trade_no' => '1805216397750940761341952',
            'out_trade_no' => '201805210000013034',
            'total_amount' => 0.01,
            'goods_name' => '201805210000013034',
            'remarks' => '',
            'status' => 1000,
            'pay_time' => '2018-05-21 10:10:38',
            'version' => '1.0.0',
            'sign_type' => 'MD5',
            'sign' => '529670707AED3E92210CA644FE1C0C90',
        ];

        $entry = [
            'id' => '201805210000013034',
            'amount' => '100',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'trade_no' => '1805216397750940761341952',
            'out_trade_no' => '201805210000013034',
            'total_amount' => 0.01,
            'goods_name' => '201805210000013034',
            'remarks' => '',
            'status' => 1000,
            'pay_time' => '2018-05-21 10:10:38',
            'version' => '1.0.0',
            'sign_type' => 'MD5',
            'sign' => '529670707AED3E92210CA644FE1C0C90',
        ];

        $entry = [
            'id' => '201805210000013034',
            'amount' => '0.01',
        ];

        $eLongPay = new ELongPay();
        $eLongPay->setPrivateKey('test');
        $eLongPay->setOptions($sourceData);
        $eLongPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $eLongPay->getMsg());
    }
}
