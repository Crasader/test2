<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MangoPay;
use Buzz\Message\Response;

class MangoPayTest extends DurianTestCase
{
    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

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

        // Create the keypair
        $res = openssl_pkey_new();

        // Get public key
        $pubkey = openssl_pkey_get_details($res);

        $this->publicKey = base64_encode($pubkey['key']);

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

        $mangoPay = new MangoPay();
        $mangoPay->getVerifyData();
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

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->getVerifyData();
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
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰為空字串
     */
    public function testPayGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => '',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰失敗
     */
    public function testPayGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => '123456',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->getVerifyData();
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
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => $this->publicKey,
            'verify_url' => '',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['msg' => '合作商配置不存在或禁用.'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $mangoPay = new MangoPay();
        $mangoPay->setContainer($this->container);
        $mangoPay->setClient($this->client);
        $mangoPay->setResponse($response);
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '合作商配置不存在或禁用.',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '2001',
            'msg' => '合作商配置不存在或禁用.',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $mangoPay = new MangoPay();
        $mangoPay->setContainer($this->container);
        $mangoPay->setClient($this->client);
        $mangoPay->setResponse($response);
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code_url
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => $this->publicKey,
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '0000',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $mangoPay = new MangoPay();
        $mangoPay->setContainer($this->container);
        $mangoPay->setClient($this->client);
        $mangoPay->setResponse($response);
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQRcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => $this->publicKey,
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '0000',
            'msg' => '成功',
            'order_sn' => '2017092617103611773',
            'down_sn' => '201709260000004906',
            'sign' => '8985edc2322727354a5738cc17bcad1d',
            'code_url' => 'weixin:\/\/wxpay\/bizpayurl?pr=geDDGNV',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $mangoPay = new MangoPay();
        $mangoPay->setContainer($this->container);
        $mangoPay->setClient($this->client);
        $mangoPay->setResponse($response);
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $data = $mangoPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin:\/\/wxpay\/bizpayurl?pr=geDDGNV', $mangoPay->getQrcode());
    }

    /**
     * 測試支付對外返回缺少query
     */
    public function testPayReturnWithoutQuery()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => $this->publicKey,
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $codeUrl = 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi';

        $result = [
            'code' => '0000',
            'msg' => '成功',
            'order_sn' => '2017092617103611773',
            'down_sn' => '201709260000004906',
            'sign' => '8985edc2322727354a5738cc17bcad1d',
            'code_url' => $codeUrl,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $mangoPay = new MangoPay();
        $mangoPay->setContainer($this->container);
        $mangoPay->setClient($this->client);
        $mangoPay->setResponse($response);
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '2017083112',
            'orderId' => '201709260000004936',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => $this->publicKey,
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $codeUrl = 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?cipher_data=Uo0jtiexfZ2xSLv2ZlhmZok8o=';

        $result = [
            'code' => '0000',
            'msg' => '成功',
            'order_sn' => '2017092617103611773',
            'down_sn' => '201709260000004906',
            'sign' => '8985edc2322727354a5738cc17bcad1d',
            'code_url' => $codeUrl,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $mangoPay = new MangoPay();
        $mangoPay->setContainer($this->container);
        $mangoPay->setClient($this->client);
        $mangoPay->setResponse($response);
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $data = $mangoPay->getVerifyData();

        $this->assertEquals('http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi', $data['post_url']);
        $this->assertEquals('Uo0jtiexfZ2xSLv2ZlhmZok8o=', $data['params']['cipher_data']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1098',
            'number' => '2017083112',
            'orderId' => '201802080000009818',
            'amount' => '1.01',
            'username' => 'php1test',
            'rsa_public_key' => $this->publicKey,
            'orderCreateDate' => '2018-02-08 14:32:34',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $codeUrl = 'http://www.bbp988.com/goto.php?token=rfiBZOeZmzuFqha2oTfKtuK';

        $result = [
            'code' => '0000',
            'msg' => '成功',
            'order_sn' => '2018020814323418925907',
            'down_sn' => '201802080000009818',
            'sign' => 'c4465a14792703871a38ed2f1ee3c075',
            'code_url' => $codeUrl,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $mangoPay = new MangoPay();
        $mangoPay->setContainer($this->container);
        $mangoPay->setClient($this->client);
        $mangoPay->setResponse($response);
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $data = $mangoPay->getVerifyData();

        $this->assertEquals('http://www.bbp988.com/goto.php', $data['post_url']);
        $this->assertEquals('rfiBZOeZmzuFqha2oTfKtuK', $data['params']['token']);
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

        $mangoPay = new MangoPay();
        $mangoPay->verifyOrderPayment([]);
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

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少code
     */
    public function testReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'msg' => '成功.',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '2',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時code錯誤
     */
    public function testReturnCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单失败',
            180130
        );

        $options = [
            'code' => '1009',
            'msg' => '订单失败',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '2',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment([]);
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
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '2',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment([]);
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
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '2',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
            'sign' => '1ca4c87b2729bc2718263e9b157ff44d',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment([]);
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
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '1',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
            'sign' => 'f60159063ad896ed0b08e53ac0725113',
        ];
        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment([]);
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
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '3',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
            'sign' => '6fcdc058436f46a7ae954bd8aaa40db4',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment([]);
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
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '2',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
            'sign' => '3c793133c647cb7fc663157092c6b618',
        ];

        $entry = ['id' => '201503220000000555'];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment($entry);
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
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '2',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
            'sign' => '3c793133c647cb7fc663157092c6b618',
        ];

        $entry = [
            'id' => '201709260000004912',
            'amount' => '15.00',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '2017092617152311794',
            'down_sn' => '201709260000004912',
            'status' => '2',
            'amount' => '1.01',
            'fee' => '0.02',
            'trans_time' => '20170926171523',
            'sign' => '3c793133c647cb7fc663157092c6b618',
        ];

        $entry = [
            'id' => '201709260000004912',
            'amount' => '1.01',
        ];

        $mangoPay = new MangoPay();
        $mangoPay->setPrivateKey('test');
        $mangoPay->setOptions($options);
        $mangoPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $mangoPay->getMsg());
    }
}
