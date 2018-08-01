<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhiJinBao;

class ZhiJinBaoTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Aw\Nusoap\NusoapClient
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Aw\Nusoap\NusoapClient')
            ->disableOriginalConstructor()
            ->setMethods(['call'])
            ->getMock();

        $this->container = $container;
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

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->getVerifyData();
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

        $sourceData = ['number' => ''];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($sourceData);
        $zhiJinBao->getVerifyData();
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

        $sourceData = [
            'number' => '20171128111703',
            'orderId' => '201801170000006238',
            'orderCreateDate' => '2018-01-18 12:26:41',
            'amount' => '200',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '999',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setClient($this->client);
        $zhiJinBao->setContainer($this->container);
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($sourceData);
        $zhiJinBao->getVerifyData();
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

        $sourceData = [
            'number' => '20171128111703',
            'orderId' => '201801170000006238',
            'orderCreateDate' => '2018-01-18 12:26:41',
            'amount' => '200',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setClient($this->client);
        $zhiJinBao->setContainer($this->container);
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($sourceData);
        $zhiJinBao->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線失敗
     */
    public function testPayPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->client->expects($this->any())
            ->method('call')
            ->willThrowException($exception);

        $sourceData = [
            'number' => '20171128111703',
            'orderId' => '201801170000006238',
            'orderCreateDate' => '2018-01-18 12:26:41',
            'amount' => '200',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.pay.com',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setClient($this->client);
        $zhiJinBao->setContainer($this->container);
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($sourceData);
        $zhiJinBao->getVerifyData();
    }

    /**
     * 測試支付時支付平台回傳結果為空
     */
    public function testPayEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn('');

        $sourceData = [
            'number' => '20171128111703',
            'orderId' => '201801170000006238',
            'orderCreateDate' => '2018-01-18 12:26:41',
            'amount' => '200',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.pay.com',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setClient($this->client);
        $zhiJinBao->setContainer($this->container);
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($sourceData);
        $zhiJinBao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回result
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn('test');

        $sourceData = [
            'number' => '20171128111703',
            'orderId' => '201801170000006238',
            'orderCreateDate' => '2018-01-18 12:26:41',
            'amount' => '200',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.pay.com',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setClient($this->client);
        $zhiJinBao->setContainer($this->container);
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($sourceData);
        $zhiJinBao->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $sourceData = [
            'number' => '20171128111703',
            'orderId' => '201801170000006238',
            'orderCreateDate' => '2018-01-18 12:26:41',
            'amount' => '200',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.pay.com',
        ];

        $response = [
            'result' => 'weixin://wxpay/bizpayurl?pr=Yxyxrk1',
        ];

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($response);

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setClient($this->client);
        $zhiJinBao->setContainer($this->container);
        $zhiJinBao->setOptions($sourceData);
        $data = $zhiJinBao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=Yxyxrk1', $zhiJinBao->getQrcode());
    }

    /**
     * 測試支付時href跳轉網址取得失敗
     */
    public function testPayReturnWithoutHref()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<script language="javascript" type="text/javascript">window.locati' .
            'on="http://47.52.152.93/gateway?9d18c"</script>';

        $response = [
            'result' => $result,
        ];

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($response);

        $sourceData = [
            'number' => '20171128111703',
            'orderId' => '201801170000006238',
            'orderCreateDate' => '2018-01-18 12:26:41',
            'amount' => '200',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.pay.com',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setClient($this->client);
        $zhiJinBao->setContainer($this->container);
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($sourceData);
        $zhiJinBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '20171128111703',
            'orderId' => '201801170000006238',
            'orderCreateDate' => '2018-01-18 12:26:41',
            'amount' => '200',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.pay.com',
        ];

        $result = '<script language="javascript" type="text/javascript">window.locati' .
            'on.href="http://47.52.152.93/gateway?9d18c"</script>';

        $response = [
            'result' => $result,
        ];

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($response);

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setClient($this->client);
        $zhiJinBao->setContainer($this->container);
        $zhiJinBao->setOptions($sourceData);
        $data = $zhiJinBao->getVerifyData();

        $this->assertEquals('http://47.52.152.93/gateway?9d18c', $data['post_url']);
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

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->verifyOrderPayment([]);
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

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->verifyOrderPayment([]);
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
            'merchantNo' => '20171128111703',
            'tradeNo' => '201801170000006238',
            'payNo' => 'hfb1516162772727664707322',
            'tradeDate' => '20180117121932',
            'amount' => '0.1000',
            'status' => '2',
            'amount' => '0.10',
            'summary' => 'php1test',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($options);
        $zhiJinBao->verifyOrderPayment([]);
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
            'merchantNo' => '20171128111703',
            'tradeNo' => '201801170000006238',
            'payNo' => 'hfb1516162772727664707322',
            'tradeDate' => '20180117121932',
            'amount' => '0.1000',
            'status' => '2',
            'amount' => '0.10',
            'summary' => 'php1test',
            'sign' => 'pig123',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($options);
        $zhiJinBao->verifyOrderPayment([]);
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
            'merchantNo' => '20171128111703',
            'tradeNo' => '201801170000006238',
            'payNo' => 'hfb1516162772727664707322',
            'tradeDate' => '20180117121932',
            'amount' => '0.1000',
            'status' => '1',
            'amount' => '0.10',
            'summary' => 'php1test',
            'sign' => '32c9746e65520dc1ad0009a32a415edd',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($options);
        $zhiJinBao->verifyOrderPayment([]);
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
            'merchantNo' => '20171128111703',
            'tradeNo' => '201801170000006238',
            'payNo' => 'hfb1516162772727664707322',
            'tradeDate' => '20180117121932',
            'amount' => '0.1000',
            'status' => '2',
            'amount' => '0.10',
            'summary' => 'php1test',
            'sign' => 'dbe1036cde37fb29f5f26221c7e50094',
        ];

        $entry = ['id' => '201503220000000555'];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($options);
        $zhiJinBao->verifyOrderPayment($entry);
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
            'merchantNo' => '20171128111703',
            'tradeNo' => '201801170000006238',
            'payNo' => 'hfb1516162772727664707322',
            'tradeDate' => '20180117121932',
            'amount' => '0.1000',
            'status' => '2',
            'amount' => '0.10',
            'summary' => 'php1test',
            'sign' => 'dbe1036cde37fb29f5f26221c7e50094',
        ];

        $entry = [
            'id' => '201801170000006238',
            'amount' => '15.00',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($options);
        $zhiJinBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'merchantNo' => '20171128111703',
            'tradeNo' => '201801170000006238',
            'payNo' => 'hfb1516162772727664707322',
            'tradeDate' => '20180117121932',
            'amount' => '0.1000',
            'status' => '2',
            'amount' => '0.10',
            'summary' => 'php1test',
            'sign' => 'dbe1036cde37fb29f5f26221c7e50094',
        ];

        $entry = [
            'id' => '201801170000006238',
            'amount' => '0.1',
        ];

        $zhiJinBao = new ZhiJinBao();
        $zhiJinBao->setPrivateKey('test');
        $zhiJinBao->setOptions($options);
        $zhiJinBao->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $zhiJinBao->getMsg());
    }
}
