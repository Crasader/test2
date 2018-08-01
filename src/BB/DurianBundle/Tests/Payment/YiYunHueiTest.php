<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiYunHuei;
use Buzz\Message\Response;

class YiYunHueiTest extends DurianTestCase
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

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->getVerifyData();
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

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->getVerifyData();
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
            'number' => 'CX0002606',
            'orderId' => '201711270000005837',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->getVerifyData();
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
            'number' => 'CX0002606',
            'orderId' => '201711270000005837',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => '',
        ];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回dealCode
     */
    public function testQrCodePayReturnWithoutDealCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'CX0002606',
            'orderId' => '201711270000005837',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['sign' => '123'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setContainer($this->container);
        $yiYunHuei->setClient($this->client);
        $yiYunHuei->setResponse($response);
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗且有錯誤訊息
     */
    public function testQrCodePayReturnNotSuccessHasMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '找不到可用的路由,请联系客服',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'CX0002606',
            'orderId' => '201711270000005837',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'dealCode' => '90010',
            'dealMsg' => '找不到可用的路由,请联系客服',
            'sign' => '7A3A4F93623934DFF683275BC170AFE6',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setContainer($this->container);
        $yiYunHuei->setClient($this->client);
        $yiYunHuei->setResponse($response);
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQrCodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'CX0002606',
            'orderId' => '201711270000005837',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'dealCode' => '90001',
            'sign' => '7A3A4F93623934DFF683275BC170AFE6',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setContainer($this->container);
        $yiYunHuei->setClient($this->client);
        $yiYunHuei->setResponse($response);
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->getVerifyData();
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
            'number' => 'CX0002606',
            'orderId' => '201711270000005837',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'dealMsg' => '交易成功',
            'dealCode' => '10000',
            'merchantNo' => 'CX0002606',
            'sign' => '7A3A4F93623934DFF683275BC170AFE6',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setContainer($this->container);
        $yiYunHuei->setClient($this->client);
        $yiYunHuei->setResponse($response);
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'CX0002606',
            'orderId' => '201711270000005837',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'codeUrl' => 'weixin://wxpay/bizpayurl?pr=cfbX8PU',
            'dealMsg' => '交易成功',
            'dealCode' => '10000',
            'merchantNo' => 'CX0002606',
            'sign' => '7A3A4F93623934DFF683275BC170AFE6',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setContainer($this->container);
        $yiYunHuei->setClient($this->client);
        $yiYunHuei->setResponse($response);
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $data = $yiYunHuei->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=cfbX8PU', $yiYunHuei->getQrcode());
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => 'CX0002606',
            'orderId' => '201711270000005837',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $data = $yiYunHuei->getVerifyData();

        $this->assertEquals('bankPay', $data['service']);
        $this->assertEquals('CX0002606', $data['merchantNo']);
        $this->assertEquals('http://pay.in-action.tw/', $data['bgUrl']);
        $this->assertEquals('V2.0', $data['version']);
        $this->assertEquals('ICBC', $data['payChannelCode']);
        $this->assertEquals('1', $data['payChannelType']);
        $this->assertEquals('201711270000005837', $data['orderNo']);
        $this->assertEquals('101', $data['orderAmount']);
        $this->assertEquals('CNY', $data['curCode']);
        $this->assertEquals('20170824113232', $data['orderTime']);
        $this->assertEquals('1', $data['orderSource']);
        $this->assertEquals('1', $data['signType']);
        $this->assertEquals('1519c426075c205ccbb15df27aead3ea', $data['sign']);
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

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->verifyOrderPayment([]);
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

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->verifyOrderPayment([]);
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
            'orderNo' => '201711270000005837',
            'dealMsg' => '交易成功',
            'fee' => '1',
            'version' => 'V2.0',
            'productName' => '微信扫码支付',
            'cxOrderNo' => '100000100012826163',
            'orderAmount' => '100',
            'orderTime' => '20171127153337',
            'dealTime' => '20171127153439',
            'payChannelCode' => 'CX_WX',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0002606',
        ];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->verifyOrderPayment([]);
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
            'orderNo' => '201711270000005837',
            'dealMsg' => '交易成功',
            'fee' => '1',
            'sign' => '123',
            'version' => 'V2.0',
            'productName' => '微信扫码支付',
            'cxOrderNo' => '100000100012826163',
            'orderAmount' => '100',
            'orderTime' => '20171127153337',
            'dealTime' => '20171127153439',
            'payChannelCode' => 'CX_WX',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0002606',
        ];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->verifyOrderPayment([]);
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
            'orderNo' => '201711270000005837',
            'dealMsg' => '交易成功',
            'fee' => '1',
            'sign' => '1832F102B4BB0C539DA8D39A051AA8E6',
            'version' => 'V2.0',
            'productName' => '微信扫码支付',
            'cxOrderNo' => '100000100012826163',
            'orderAmount' => '100',
            'orderTime' => '20171127153337',
            'dealTime' => '20171127153439',
            'payChannelCode' => 'CX_WX',
            'dealCode' => '10001',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0002606',
        ];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->verifyOrderPayment([]);
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
            'orderNo' => '201711270000005837',
            'dealMsg' => '交易成功',
            'fee' => '1',
            'sign' => '4F38BC3E8E192A9D9BD5F6068F05E849',
            'version' => 'V2.0',
            'productName' => '微信扫码支付',
            'cxOrderNo' => '100000100012826163',
            'orderAmount' => '100',
            'orderTime' => '20171127153337',
            'dealTime' => '20171127153439',
            'payChannelCode' => 'CX_WX',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0002606',
        ];

        $entry = ['id' => '201503220000000555'];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->verifyOrderPayment($entry);
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
            'orderNo' => '201711270000005837',
            'dealMsg' => '交易成功',
            'fee' => '1',
            'sign' => '4F38BC3E8E192A9D9BD5F6068F05E849',
            'version' => 'V2.0',
            'productName' => '微信扫码支付',
            'cxOrderNo' => '100000100012826163',
            'orderAmount' => '100',
            'orderTime' => '20171127153337',
            'dealTime' => '20171127153439',
            'payChannelCode' => 'CX_WX',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0002606',
        ];

        $entry = [
            'id' => '201711270000005837',
            'amount' => '15.00',
        ];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'orderNo' => '201711270000005837',
            'dealMsg' => '交易成功',
            'fee' => '1',
            'sign' => '4F38BC3E8E192A9D9BD5F6068F05E849',
            'version' => 'V2.0',
            'productName' => '微信扫码支付',
            'cxOrderNo' => '100000100012826163',
            'orderAmount' => '100',
            'orderTime' => '20171127153337',
            'dealTime' => '20171127153439',
            'payChannelCode' => 'CX_WX',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0002606',
        ];

        $entry = [
            'id' => '201711270000005837',
            'amount' => '1',
        ];

        $yiYunHuei = new YiYunHuei();
        $yiYunHuei->setPrivateKey('test');
        $yiYunHuei->setOptions($options);
        $yiYunHuei->verifyOrderPayment($entry);

        $returnMsg = '{"dealResult":"SUCCESS","merchantNo":"CX0002606","signType":"1","sign":"6a7979c0fee80db4' .
            'e4dee373f9d946c6"}';

        $this->assertEquals($returnMsg, $yiYunHuei->getMsg());
    }
}
