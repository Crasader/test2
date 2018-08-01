<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ShiunFu;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class ShiunFuTest extends DurianTestCase
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

        $ShiunFu = new ShiunFu();
        $ShiunFu->getVerifyData();
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

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->getVerifyData();
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
            'number' => '860000010000211',
            'amount' => '1',
            'orderId' => '201712250000007443',
            'paymentVendorId' => '999',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->getVerifyData();
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
            'number' => '860000010000211',
            'amount' => '1',
            'orderId' => '201712250000007443',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->getVerifyData();
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
            'number' => '860000010000211',
            'amount' => '1.00',
            'orderId' => '201712250000007440',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.mashangshouqian.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"wxp://f2f1DmzSmgdBqHrzXu4ZqTcv9oQVCQPuj7bT",' .
            '"message":"正在支付中,需要发起查询交易","merchno":"860000010000211"' .
            ',"traceno":"201712250000007443","signature":"04E956065E3F41FCC73D' .
            'EBEC18F87776"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ShiunFu = new ShiunFu();
        $ShiunFu->setContainer($this->container);
        $ShiunFu->setClient($this->client);
        $ShiunFu->setResponse($response);
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '不支持该金额,请输入10的整倍数',
            180130
        );

        $options = [
            'number' => '860000010000211',
            'amount' => '1.00',
            'orderId' => '201712250000007443',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.mashangshouqian.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"66","message":"不支持该金额,请输入10的整倍数",' .
            '"merchno":"860000010000211","traceno":"201712250000007437","sign' .
            'ature":"B8229E1E7BFA574ECC4F077DDA3776D7"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ShiunFu = new ShiunFu();
        $ShiunFu->setContainer($this->container);
        $ShiunFu->setClient($this->client);
        $ShiunFu->setResponse($response);
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->getVerifyData();
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
            'number' => '860000010000211',
            'amount' => '1.00',
            'orderId' => '201712250000007437',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.mashangshouqian.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"10","message":"正在支付中,需要发起查询交易","merc' .
            'hno":"860000010000211","traceno":"201712250000007440","signature":' .
            '"AF0D6C45126C636A9B2F373A04159C9A"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ShiunFu = new ShiunFu();
        $ShiunFu->setContainer($this->container);
        $ShiunFu->setClient($this->client);
        $ShiunFu->setResponse($response);
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testPay()
    {
        $options = [
            'number' => '860000010000211',
            'amount' => '1.00',
            'orderId' => '201712250000007443',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.mashangshouqian.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"10",' .
            '"message":"\u6b63\u5728\u652f\u4ed8\u4e2d,\u9700\u8981\u53d1\u8d77\u67e5\u8be2\u4ea4\u6613"' .
            ',"merchno":"860000010000211","traceno":"201801090000007929","amount":"1.00",' .
            '"payAmt":"1.00","signature":"12741BCAA6A272CDA43B260255FB5B0D",' .
            '"payUrl":"http:\/\/api.yiwangxintong.com\/grmOrderPay.jhtml?jnlNo=110021120180109142959988715139"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ShiunFu = new ShiunFu();
        $ShiunFu->setContainer($this->container);
        $ShiunFu->setClient($this->client);
        $ShiunFu->setResponse($response);
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $data = $ShiunFu->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals(
            'http://api.yiwangxintong.com/grmOrderPay.jhtml?jnlNo=110021120180109142959988715139',
            $data['post_url']
        );
    }

    /**
     * 測試手機支付
     */
    public function testPayWithWap()
    {
        $options = [
            'number' => '860000010000211',
            'amount' => '1.00',
            'orderId' => '201712260000007451',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.mashangshouqian.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respCode":"10","message":"\u6b63\u5728\u652f\u4ed8\u4e2d,' .
            '\u9700\u8981\u53d1\u8d77\u67e5\u8be2\u4ea4\u6613",' .
            '"merchno":"860000010000211","traceno":"201801090000007932",' .
            '"amount":"1.00","payAmt":"1.00","signature":"3EED66B7F0551941EB7475F3D6D3CF83",' .
            '"payUrl":"HTTPS:\/\/QR.ALIPAY.COM\/FKX04410VTAMSK9WDFGR91"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $ShiunFu = new ShiunFu();
        $ShiunFu->setContainer($this->container);
        $ShiunFu->setClient($this->client);
        $ShiunFu->setResponse($response);
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $data = $ShiunFu->getVerifyData();

        $this->assertEquals('HTTPS://QR.ALIPAY.COM/FKX04410VTAMSK9WDFGR91', $data['post_url']);
        $this->assertEquals([], $data['params']);
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

        $ShiunFu = new ShiunFu();
        $ShiunFu->verifyOrderPayment([]);
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

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->verifyOrderPayment([]);
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
            'amount' => '1.00',
            'merchno' => '860000010000211',
            'payType' => '2',
            'status' => '1',
            'traceno' => '201712250000007443',
            'transDate' => '2017-12-25',
            'transTime' => '17:20:19',
        ];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->verifyOrderPayment([]);
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
            'amount' => '1.00',
            'merchno' => '860000010000211',
            'payAmt' => '1.00',
            'signature' => '4fa11a5c6ad737a7dc1c903054c138d8',
            'payType' => '1',
            'status' => '1',
            'traceno' => '201801090000007929',
            'transDate' => '2018-01-09',
            'transTime' => '14:32:55',
        ];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('1234');
        $ShiunFu->setOptions($options);
        $ShiunFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'amount' => '1.00',
            'merchno' => '860000010000211',
            'payAmt' => '1.00',
            'signature' => '5f2dc506e39c6abf546ecce501e25c6c',
            'payType' => '1',
            'status' => '0',
            'traceno' => '201801090000007929',
            'transDate' => '2018-01-09',
            'transTime' => '14:32:55',
        ];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->verifyOrderPayment([]);
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
            'amount' => '1.00',
            'merchno' => '860000010000211',
            'payAmt' => '1.00',
            'signature' => 'cb59307f0024dcb9f2aadbb5866885ee',
            'payType' => '1',
            'status' => '2',
            'traceno' => '201801090000007929',
            'transDate' => '2018-01-09',
            'transTime' => '14:32:55',
        ];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->verifyOrderPayment([]);
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
            'amount' => '1.00',
            'merchno' => '860000010000211',
            'payAmt' => '1.00',
            'signature' => 'ed45b5dd836bb439e1af675afb7aa658',
            'payType' => '1',
            'status' => '1',
            'traceno' => '201712250000007443',
            'transDate' => '2018-01-09',
            'transTime' => '14:32:55',
        ];

        $entry = ['id' => '201707250000003581'];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->verifyOrderPayment($entry);
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
            'amount' => '1.00',
            'merchno' => '860000010000211',
            'payAmt' => '1.12',
            'signature' => 'c052dc39f0f3d3a8673b12f249e3b405',
            'payType' => '1',
            'status' => '1',
            'traceno' => '201712250000007443',
            'transDate' => '2018-01-09',
            'transTime' => '14:32:55',
        ];

        $entry = [
            'id' => '201712250000007443',
            'amount' => '1.00',
        ];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'amount' => '1.00',
            'merchno' => '860000010000211',
            'payAmt' => '0.99',
            'signature' => '0b174c55ee888fd0e5e1bffc2e46130f',
            'payType' => '1',
            'status' => '1',
            'traceno' => '201801090000007929',
            'transDate' => '2018-01-09',
            'transTime' => '14:32:55',
        ];

        $entry = [
            'id' => '201801090000007929',
            'amount' => '1.00',
        ];

        $ShiunFu = new ShiunFu();
        $ShiunFu->setPrivateKey('test');
        $ShiunFu->setOptions($options);
        $ShiunFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $ShiunFu->getMsg());
    }
}
