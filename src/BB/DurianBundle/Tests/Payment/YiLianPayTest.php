<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiLianPay;

class YiLianPayTest extends DurianTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

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

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);
        $this->privateKey = base64_encode($privkey);

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
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yiLianPay = new YiLianPay();
        $yiLianPay->getVerifyData();
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
            'number' => '1716068208095558',
            'orderId' => '201710190000005202',
            'amount' => '1.01',
            'orderCreateDate' => '2017-10-20 11:45:55',
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA私鑰為空字串
     */
    public function testPayGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '1716068208095558',
            'orderId' => '201710190000005202',
            'amount' => '1.01',
            'orderCreateDate' => '2017-10-20 11:45:55',
            'rsa_private_key' => '',
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA私鑰失敗
     */
    public function testPayGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '1716068208095558',
            'orderId' => '201710190000005202',
            'amount' => '1.01',
            'orderCreateDate' => '2017-10-20 11:45:55',
            'rsa_private_key' => '123456',
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->getVerifyData();
    }

    /**
     * 測試加密產生簽名失敗
     */
    public function testGetEncodeGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '1716068208095558',
            'orderId' => '201710190000005202',
            'amount' => '1.01',
            'orderCreateDate' => '2017-10-20 11:45:55',
            'rsa_private_key' => base64_encode($privkey),
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '1716068208095558',
            'orderId' => '201710190000005202',
            'amount' => '0.1',
            'orderCreateDate' => '2017-10-20 11:45:55',
            'postUrl' => 'http://47.91.251.192:5555/trade/pay/',
            'rsa_private_key' => $this->privateKey,
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $encodeData = $yiLianPay->getVerifyData();

        $encodeStr = 'inputCharset=1&notifyUrl=http://pay.in-action.tw/&orderAmount=10&orderCurrency=156&orderDatetim' .
            'e=20171020114555&orderNo=201710190000005202&partnerId=1716068208095558&returnUrl=http://pay.in-action.tw/';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $this->assertEquals('http://47.91.251.192:5555/trade/pay/wxcreate', $encodeData['post_url']);
        $this->assertEquals('1', $encodeData['params']['inputCharset']);
        $this->assertEquals($options['number'], $encodeData['params']['partnerId']);
        $this->assertEquals('1', $encodeData['params']['signType']);
        $this->assertEquals($options['notify_url'], $encodeData['params']['notifyUrl']);
        $this->assertEquals($options['notify_url'], $encodeData['params']['returnUrl']);
        $this->assertEquals($options['orderId'], $encodeData['params']['orderNo']);
        $this->assertEquals('10', $encodeData['params']['orderAmount']);
        $this->assertEquals('156', $encodeData['params']['orderCurrency']);
        $this->assertEquals('20171020114555', $encodeData['params']['orderDatetime']);
        $this->assertEquals(base64_encode($sign), $encodeData['params']['signMsg']);
        $this->assertEquals('', $encodeData['params']['subject']);
        $this->assertEquals('', $encodeData['params']['body']);
        $this->assertEquals('', $encodeData['params']['extraCommonParam']);
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

        $yiLianPay = new YiLianPay();
        $yiLianPay->verifyOrderPayment([]);
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
            'extraCommonParam' => '',
            'orderDatetime' => '20171019115153',
            'returnDatetime' => '20171019115153',
            'payResult' => '1',
            'orderNo' => '201710190000005202',
            'orderAmount' => '10',
            'paymentOrderId' => 'ea345a1df431412d97cde45c65798569',
            'signType' => '1',
            'inputCharset' => 'UTF-8',
            'partnerId' => '1716068208095558',
            'payDatetime' => '20171019115153',
            'rsa_public_key' => $this->publicKey,
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->verifyOrderPayment([]);
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
            'extraCommonParam' => '',
            'orderDatetime' => '20171019115153',
            'returnDatetime' => '20171019115153',
            'payResult' => '1',
            'orderNo' => '201710190000005202',
            'orderAmount' => '10',
            'paymentOrderId' => 'ea345a1df431412d97cde45c65798569',
            'signType' => '1',
            'inputCharset' => 'UTF-8',
            'partnerId' => '1716068208095558',
            'signMsg' => '9453',
            'payDatetime' => '20171019115153',
            'rsa_public_key' => $this->publicKey,
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->verifyOrderPayment([]);
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

        $encodeStr = 'inputCharset=UTF-8&orderAmount=10&orderDatetime=20171019115153&orderNo=201710190000005202&' .
            'partnerId=1716068208095558&payDatetime=20171019115153&payResult=0&paymentOrderId=ea345a1df431412d97' .
            'cde45c65798569&returnDatetime=20171019115153';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $options = [
            'extraCommonParam' => '',
            'orderDatetime' => '20171019115153',
            'returnDatetime' => '20171019115153',
            'payResult' => '0',
            'orderNo' => '201710190000005202',
            'orderAmount' => '10',
            'paymentOrderId' => 'ea345a1df431412d97cde45c65798569',
            'signType' => '1',
            'inputCharset' => 'UTF-8',
            'partnerId' => '1716068208095558',
            'signMsg' => base64_encode($sign),
            'payDatetime' => '20171019115153',
            'rsa_public_key' => $this->publicKey,
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->verifyOrderPayment([]);
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

        $encodeStr = 'inputCharset=UTF-8&orderAmount=10&orderDatetime=20171019115153&orderNo=201710190000005202&' .
            'partnerId=1716068208095558&payDatetime=20171019115153&payResult=1&paymentOrderId=ea345a1df431412d97' .
            'cde45c65798569&returnDatetime=20171019115153';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $options = [
            'extraCommonParam' => '',
            'orderDatetime' => '20171019115153',
            'returnDatetime' => '20171019115153',
            'payResult' => '1',
            'orderNo' => '201710190000005202',
            'orderAmount' => '10',
            'paymentOrderId' => 'ea345a1df431412d97cde45c65798569',
            'signType' => '1',
            'inputCharset' => 'UTF-8',
            'partnerId' => '1716068208095558',
            'signMsg' => base64_encode($sign),
            'payDatetime' => '20171019115153',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = ['id' => '201503220000000555'];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->verifyOrderPayment($entry);
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

        $encodeStr = 'inputCharset=UTF-8&orderAmount=10&orderDatetime=20171019115153&orderNo=201710190000005202&' .
            'partnerId=1716068208095558&payDatetime=20171019115153&payResult=1&paymentOrderId=ea345a1df431412d97' .
            'cde45c65798569&returnDatetime=20171019115153';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $options = [
            'extraCommonParam' => '',
            'orderDatetime' => '20171019115153',
            'returnDatetime' => '20171019115153',
            'payResult' => '1',
            'orderNo' => '201710190000005202',
            'orderAmount' => '10',
            'paymentOrderId' => 'ea345a1df431412d97cde45c65798569',
            'signType' => '1',
            'inputCharset' => 'UTF-8',
            'partnerId' => '1716068208095558',
            'signMsg' => base64_encode($sign),
            'payDatetime' => '20171019115153',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201710190000005202',
            'amount' => '15.00',
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $encodeStr = 'inputCharset=UTF-8&orderAmount=10&orderDatetime=20171019115153&orderNo=201710190000005202&' .
            'partnerId=1716068208095558&payDatetime=20171019115153&payResult=1&paymentOrderId=ea345a1df431412d97' .
            'cde45c65798569&returnDatetime=20171019115153';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $options = [
            'extraCommonParam' => '',
            'orderDatetime' => '20171019115153',
            'returnDatetime' => '20171019115153',
            'payResult' => '1',
            'orderNo' => '201710190000005202',
            'orderAmount' => '10',
            'paymentOrderId' => 'ea345a1df431412d97cde45c65798569',
            'signType' => '1',
            'inputCharset' => 'UTF-8',
            'partnerId' => '1716068208095558',
            'signMsg' => base64_encode($sign),
            'payDatetime' => '20171019115153',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201710190000005202',
            'amount' => '0.1',
        ];

        $yiLianPay = new YiLianPay();
        $yiLianPay->setOptions($options);
        $yiLianPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $yiLianPay->getMsg());
    }
}
