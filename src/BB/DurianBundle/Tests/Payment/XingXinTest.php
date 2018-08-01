<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XingXin;
use Buzz\Message\Response;

class XingXinTest extends DurianTestCase
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

        $xingXin = new XingXin();
        $xingXin->getVerifyData();
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

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->getVerifyData();
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
            'number' => '860000010000128',
            'amount' => '100',
            'orderId' => '201709140000007019',
            'paymentVendorId' => '999',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentGatewayId' => '229',
        ];

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->getVerifyData();
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
            'number' => '860000010000128',
            'amount' => '1',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
            'paymentGatewayId' => '229',
        ];

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '860000010000128',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.wangdailm.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '229',
        ];

        $result = '{"message":"通道方未开通该业务","merchno":"860000010000128",' .
            '"traceno":"201709140000007022","remark":"","signature":"9CE4DF74E9777417745B2D148E3DCC23"';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xingXin = new XingXin();
        $xingXin->setContainer($this->container);
        $xingXin->setClient($this->client);
        $xingXin->setResponse($response);
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '通道方未开通该业务',
            180130
        );

        $options = [
            'number' => '860000010000128',
            'amount' => '0.1',
            'orderId' => '201709050000006920',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.wangdailm.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '229',
        ];

        $result = '{"respCode":"57","message":"通道方未开通该业务","merchno":"860000010000128",' .
            '"traceno":"201709140000007022","remark":"","signature":"9CE4DF74E9777417745B2D148E3DCC23"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xingXin = new XingXin();
        $xingXin->setContainer($this->container);
        $xingXin->setClient($this->client);
        $xingXin->setResponse($response);
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->getVerifyData();
    }

    /**
     * 測試支付時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '860000010000128',
            'amount' => '0.01',
            'orderId' => '201709140000007019',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.wangdailm.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '229',
        ];

        $result = '{"respCode":"10", "message":"正在支付中,需要发起查询交易","merchno":"860000010000128",' .
            '"traceno":"201709140000007019","remark":"","signature":"FC71FC58B18FB4720061ABB04B47B221"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xingXin = new XingXin();
        $xingXin->setContainer($this->container);
        $xingXin->setClient($this->client);
        $xingXin->setResponse($response);
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '860000010000128',
            'amount' => '0.01',
            'orderId' => '201709140000007019',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.wangdailm.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '229',
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=VYLSJoG","respCode":"10",' .
            '"message":"正在支付中,需要发起查询交易","merchno":"860000010000128","traceno":' .
            '"201709140000007019","remark":"","signature":"FC71FC58B18FB4720061ABB04B47B221"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xingXin = new XingXin();
        $xingXin->setContainer($this->container);
        $xingXin->setClient($this->client);
        $xingXin->setResponse($response);
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $data = $xingXin->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=VYLSJoG', $xingXin->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPayWithWap()
    {
        $options = [
            'number' => '860000010000128',
            'amount' => '0.01',
            'orderId' => '201709050000006917',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.wangdailm.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentGatewayId' => '229',
        ];

        $result = '{"barCode":"https://qr.alipay.com/bax035365dcsiiynqg5o60fd","respCode":"10"' .
            ',"message":"正在支付中,需要发起查询交易","merchno":"860000010000128","traceno":"' .
            '201709140000007024","remark":"","signature":"B6D23627ACA0267EC56343CE8FFC62E4"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $xingXin = new XingXin();
        $xingXin->setContainer($this->container);
        $xingXin->setClient($this->client);
        $xingXin->setResponse($response);
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $data = $xingXin->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/bax035365dcsiiynqg5o60fd', $data['act_url']);
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

        $xingXin = new XingXin();
        $xingXin->verifyOrderPayment([]);
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

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->verifyOrderPayment([]);
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
            'amount' => '3.10',
            'merchno' => '860000010000128',
            'payType' => '2',
            'status' => '1',
            'traceno' => '201709140000007019',
            'transDate' => '2017-09-14',
            'transTime' => '10:48:51',
        ];

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->verifyOrderPayment([]);
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
            'amount' => '3.10',
            'merchno' => '860000010000128',
            'payType' => '2',
            'signature' => '87375BC31EBFB6789795D41E7B139484',
            'status' => '1',
            'traceno' => '201709140000007019',
            'transDate' => '2017-09-14',
            'transTime' => '10:48:51',
        ];

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'amount' => '3.10',
            'merchno' => '860000010000128',
            'payType' => '2',
            'signature' => '6B9A5C6CCBC8054A00B7D50B4A8BDC16',
            'status' => '9',
            'traceno' => '201709140000007019',
            'transDate' => '2017-09-14',
            'transTime' => '10:48:51',
        ];

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->verifyOrderPayment([]);
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
            'amount' => '3.10',
            'merchno' => '860000010000128',
            'payType' => '2',
            'signature' => '51D7CE2BAE4F0AFD944111B52261C8BF',
            'status' => '1',
            'traceno' => '201709140000007019',
            'transDate' => '2017-09-14',
            'transTime' => '10:48:51',
        ];

        $entry = ['id' => '201707250000003581'];

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->verifyOrderPayment($entry);
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
            'amount' => '3.10',
            'merchno' => '860000010000128',
            'payType' => '2',
            'signature' => '51D7CE2BAE4F0AFD944111B52261C8BF',
            'status' => '1',
            'traceno' => '201709140000007019',
            'transDate' => '2017-09-14',
            'transTime' => '10:48:51',
        ];

        $entry = [
            'id' => '201709140000007019',
            'amount' => '1',
        ];

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'amount' => '3.10',
            'merchno' => '860000010000128',
            'payType' => '2',
            'signature' => '51D7CE2BAE4F0AFD944111B52261C8BF',
            'status' => '1',
            'traceno' => '201709140000007019',
            'transDate' => '2017-09-14',
            'transTime' => '10:48:51',
        ];

        $entry = [
            'id' => '201709140000007019',
            'amount' => '3.10',
        ];

        $xingXin = new XingXin();
        $xingXin->setPrivateKey('test');
        $xingXin->setOptions($options);
        $xingXin->verifyOrderPayment($entry);

        $this->assertEquals('success', $xingXin->getMsg());
    }
}
