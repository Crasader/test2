<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\AtrustPay;
use Buzz\Message\Response;

class AtrustPayTest extends DurianTestCase
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

        $atrustPay = new AtrustPay();
        $atrustPay->getVerifyData();
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

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->getVerifyData();
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
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '9999',
            'number' => '100519325',
            'orderId' => '201710110000001458',
            'amount' => '2',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-10-11 11:32:32',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定receivableType
     */
    public function testPayWithoutMerchantExtraReceivableType()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'number' => '100519325',
            'orderId' => '201710110000001458',
            'amount' => '2',
            'username' => 'php1test',
            'merchant_extra' => [],
            'orderCreateDate' => '2017-10-11 11:32:32',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->getVerifyData();
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
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'number' => '100519325',
            'orderId' => '201710110000001458',
            'amount' => '2',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-10-11 11:32:32',
            'verify_url' => '',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回retCode
     */
    public function testQrCodePayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'number' => '100519325',
            'orderId' => '201710110000001458',
            'amount' => '2',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-10-11 11:32:32',
            'verify_url' => 'payment.http.online.atrustpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['retMsg' => '100519325商户未配置该支付方式……'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $atrustPay = new AtrustPay();
        $atrustPay->setContainer($this->container);
        $atrustPay->setClient($this->client);
        $atrustPay->setResponse($response);
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '00000000518773商户未配置该支付方式……',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'number' => '100519325',
            'orderId' => '201710110000001458',
            'amount' => '2',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-10-11 11:32:32',
            'verify_url' => 'payment.http.online.atrustpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'retCode' => '1518025',
            'retMsg' => '00000000518773商户未配置该支付方式……',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $atrustPay = new AtrustPay();
        $atrustPay->setContainer($this->container);
        $atrustPay->setClient($this->client);
        $atrustPay->setResponse($response);
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回qrcode
     */
    public function testQrCodePayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'number' => '100519325',
            'orderId' => '201710110000001458',
            'amount' => '2',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-10-11 11:32:32',
            'verify_url' => 'payment.http.online.atrustpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'signData' => 'C03DF0F7452891AA31B1D79A1FD78C22',
            'code' => '1',
            'signType' => 'MD5',
            'platmerord' => '918009512005214208',
            'retCode' => '1',
            'serviceName' => '支付申请-扫码支付订单建立',
            'retMsg' => '下单成功',
            'desc' => '下单成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $atrustPay = new AtrustPay();
        $atrustPay->setContainer($this->container);
        $atrustPay->setClient($this->client);
        $atrustPay->setResponse($response);
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'number' => '100519325',
            'orderId' => '201710110000001458',
            'amount' => '2',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-10-11 11:32:32',
            'verify_url' => 'payment.http.online.atrustpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'signData' => 'C03DF0F7452891AA31B1D79A1FD78C22',
            'code' => '1',
            'qrcode' => 'weixin://wxpay/bizpayurl?pr=9XxEzMU',
            'signType' => 'MD5',
            'platmerord' => '918009512005214208',
            'retCode' => '1',
            'serviceName' => '支付申请-扫码支付订单建立',
            'retMsg' => '下单成功',
            'desc' => '下单成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $atrustPay = new AtrustPay();
        $atrustPay->setContainer($this->container);
        $atrustPay->setClient($this->client);
        $atrustPay->setResponse($response);
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $data = $atrustPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=9XxEzMU', $atrustPay->getQrcode());
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'number' => '100519325',
            'orderId' => '201710110000001458',
            'amount' => '2',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-10-11 11:32:32',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $data = $atrustPay->getVerifyData();

        $this->assertEquals('1.0', $data['versionId']);
        $this->assertEquals($options['amount'] * 100, $data['orderAmount']);
        $this->assertEquals('20171011113232', $data['orderDate']);
        $this->assertEquals('RMB', $data['currency']);
        $this->assertEquals('0', $data['accountType']);
        $this->assertEquals('008', $data['transType']);
        $this->assertEquals($options['notify_url'], $data['asynNotifyUrl']);
        $this->assertEquals($options['notify_url'], $data['synNotifyUrl']);
        $this->assertEquals('MD5', $data['signType']);
        $this->assertEquals($options['number'], $data['merId']);
        $this->assertEquals($options['orderId'], $data['prdOrdNo']);
        $this->assertEquals('00020', $data['payMode']);
        $this->assertEquals('102', $data['tranChannel']);
        $this->assertEquals($options['merchant_extra']['receivableType'], $data['receivableType']);
        $this->assertEquals('', $data['prdAmt']);
        $this->assertEquals('', $data['prdDisUrl']);
        $this->assertEquals($options['username'], $data['prdName']);
        $this->assertEquals('', $data['prdShortName']);
        $this->assertEquals($options['username'], $data['prdDesc']);
        $this->assertEquals('1', $data['pnum']);
        $this->assertEquals('', $data['merParam']);
        $this->assertEquals('3ee2d52b9540ff89d787d3ca2caec84c', $data['signData']);
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

        $atrustPay = new AtrustPay();
        $atrustPay->verifyOrderPayment([]);
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

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->verifyOrderPayment([]);
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
            'versionId' => '1.0',
            'orderAmount' => '200',
            'transType' => '008',
            'asynNotifyUrl' => 'https://pay.my/pay/return.php',
            'payTime' => '20171011150534',
            'synNotifyUrl' => 'https://pay.my/pay/return.php',
            'orderStatus' => '01',
            'signType' => 'MD5',
            'merId' => '100519325',
            'payId' => '918009512005214208',
            'prdOrdNo' => '201710110000001458',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->verifyOrderPayment([]);
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
            'signData' => '98BE1FF146CA9CB21613EB8D20DFA97C',
            'versionId' => '1.0',
            'orderAmount' => '200',
            'transType' => '008',
            'asynNotifyUrl' => 'https://pay.my/pay/return.php',
            'payTime' => '20171011150534',
            'synNotifyUrl' => 'https://pay.my/pay/return.php',
            'orderStatus' => '01',
            'signType' => 'MD5',
            'merId' => '100519325',
            'payId' => '918009512005214208',
            'prdOrdNo' => '201710110000001458',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->verifyOrderPayment([]);
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
            'signData' => '187C82312C1E7DD3C8CD8C67D5E3D134',
            'versionId' => '1.0',
            'orderAmount' => '200',
            'transType' => '008',
            'asynNotifyUrl' => 'https://pay.my/pay/return.php',
            'payTime' => '20171011150534',
            'synNotifyUrl' => 'https://pay.my/pay/return.php',
            'orderStatus' => '02',
            'signType' => 'MD5',
            'merId' => '100519325',
            'payId' => '918009512005214208',
            'prdOrdNo' => '201710110000001458',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->verifyOrderPayment([]);
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
            'signData' => 'E872B532A9785E38AE3144797F40B4E1',
            'versionId' => '1.0',
            'orderAmount' => '200',
            'transType' => '008',
            'asynNotifyUrl' => 'https://pay.my/pay/return.php',
            'payTime' => '20171011150534',
            'synNotifyUrl' => 'https://pay.my/pay/return.php',
            'orderStatus' => '00',
            'signType' => 'MD5',
            'merId' => '100519325',
            'payId' => '918009512005214208',
            'prdOrdNo' => '201710110000001458',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->verifyOrderPayment([]);
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
            'signData' => 'E04FCA65641C0FEFFE8D4D593FE4687E',
            'versionId' => '1.0',
            'orderAmount' => '200',
            'transType' => '008',
            'asynNotifyUrl' => 'https://pay.my/pay/return.php',
            'payTime' => '20171011150534',
            'synNotifyUrl' => 'https://pay.my/pay/return.php',
            'orderStatus' => '01',
            'signType' => 'MD5',
            'merId' => '100519325',
            'payId' => '918009512005214208',
            'prdOrdNo' => '201710110000001458',
        ];

        $entry = [
            'id' => '201710110000001459',
            'amount' => '2.00',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->verifyOrderPayment($entry);
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
            'signData' => 'E04FCA65641C0FEFFE8D4D593FE4687E',
            'versionId' => '1.0',
            'orderAmount' => '200',
            'transType' => '008',
            'asynNotifyUrl' => 'https://pay.my/pay/return.php',
            'payTime' => '20171011150534',
            'synNotifyUrl' => 'https://pay.my/pay/return.php',
            'orderStatus' => '01',
            'signType' => 'MD5',
            'merId' => '100519325',
            'payId' => '918009512005214208',
            'prdOrdNo' => '201710110000001458',
        ];

        $entry = [
            'id' => '201710110000001458',
            'amount' => '200',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'signData' => 'E04FCA65641C0FEFFE8D4D593FE4687E',
            'versionId' => '1.0',
            'orderAmount' => '200',
            'transType' => '008',
            'asynNotifyUrl' => 'https://pay.my/pay/return.php',
            'payTime' => '20171011150534',
            'synNotifyUrl' => 'https://pay.my/pay/return.php',
            'orderStatus' => '01',
            'signType' => 'MD5',
            'merId' => '100519325',
            'payId' => '918009512005214208',
            'prdOrdNo' => '201710110000001458',
        ];

        $entry = [
            'id' => '201710110000001458',
            'amount' => '2.00',
        ];

        $atrustPay = new AtrustPay();
        $atrustPay->setPrivateKey('test');
        $atrustPay->setOptions($options);
        $atrustPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $atrustPay->getMsg());
    }
}