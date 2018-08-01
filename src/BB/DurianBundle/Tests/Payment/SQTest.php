<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SQ;
use Buzz\Message\Response;

class SQTest extends DurianTestCase
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

        $sQ = new SQ();
        $sQ->getVerifyData();
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

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->getVerifyData();
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
            'paymentVendorId' => '999',
            'number' => 'spade88-2',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201803070000004333',
            'amount' => '1',
            'ip' => '111.235.135.54',
        ];

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->getVerifyData();
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
            'paymentVendorId' => '1090',
            'number' => 'spade88-2',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201803070000004333',
            'amount' => '1',
            'ip' => '111.235.135.54',
            'verify_url' => '',
        ];

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->getVerifyData();
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

        $options = [
            'paymentVendorId' => '1090',
            'number' => 'spade88-2',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201803070000004333',
            'amount' => '1',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"message":"AmountError"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sQ = new SQ();
        $sQ->setContainer($this->container);
        $sQ->setClient($this->client);
        $sQ->setResponse($response);
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'AmountError',
            180130
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => 'spade88-2',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201803070000004333',
            'amount' => '1',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"result":"fail","message":"AmountError"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sQ = new SQ();
        $sQ->setContainer($this->container);
        $sQ->setClient($this->client);
        $sQ->setResponse($response);
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->getVerifyData();
    }

    /**
     * 測試支付時沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'paymentVendorId' => '1090',
            'number' => 'spade88-2',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201803070000004333',
            'amount' => '1',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchant_id":"spade88-2","message":"success","order_no":"201803070000004333",' .
            '"out_trade_no":"8d6d5d590db3cc736dd601b886d7ee09","result":"success","total_fee":100,' .
            '"sign":"155418E767DFB6F1B1DE76A4A505646F"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sQ = new SQ();
        $sQ->setContainer($this->container);
        $sQ->setClient($this->client);
        $sQ->setResponse($response);
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'paymentVendorId' => '1090',
            'number' => 'spade88-2',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201803070000004333',
            'amount' => '1',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchant_id":"spade88-2","message":"success","order_no":"201803070000004333",' .
            '"out_trade_no":"8d6d5d590db3cc736dd601b886d7ee09","result":"success","total_fee":100,' .
            '"url":"weixin://wxpay/bizpayurl?pr=9Ooe7ZZ","sign":"155418E767DFB6F1B1DE76A4A505646F"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sQ = new SQ();
        $sQ->setContainer($this->container);
        $sQ->setClient($this->client);
        $sQ->setResponse($response);
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $data = $sQ->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=9Ooe7ZZ', $sQ->getQrcode());
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

        $sQ = new SQ();
        $sQ->verifyOrderPayment([]);
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

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->verifyOrderPayment([]);
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
            'is_paid' => 'true',
            'merchant_id' => 'spade88-2',
            'nonce_str' => '9ad4131417e63039539a3e51a959314a',
            'notify_time' => '20180307090005',
            'order_no' => '201803070000004333',
            'out_trade_no' => '8d6d5d590db3cc736dd601b886d7ee09',
            'service' => 'Square_Weixin_QR',
            'total_fee' => 100,
        ];

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->verifyOrderPayment([]);
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
            'is_paid' => 'true',
            'merchant_id' => 'spade88-2',
            'nonce_str' => '9ad4131417e63039539a3e51a959314a',
            'notify_time' => '20180307090005',
            'order_no' => '201803070000004333',
            'out_trade_no' => '8d6d5d590db3cc736dd601b886d7ee09',
            'service' => 'Square_Weixin_QR',
            'total_fee' => 100,
            'sign' => '7EE24EF4CAA621043AEF7243B7DE82CC',
        ];

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->verifyOrderPayment([]);
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
            'is_paid' => 'false',
            'merchant_id' => 'spade88-2',
            'nonce_str' => '9ad4131417e63039539a3e51a959314a',
            'notify_time' => '20180307090005',
            'order_no' => '201803070000004333',
            'out_trade_no' => '8d6d5d590db3cc736dd601b886d7ee09',
            'service' => 'Square_Weixin_QR',
            'total_fee' => 100,
            'sign' => '982A42C4473F6E004021E0BE5A010D04',
        ];

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->verifyOrderPayment([]);
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
            'is_paid' => 'true',
            'merchant_id' => 'spade88-2',
            'nonce_str' => '9ad4131417e63039539a3e51a959314a',
            'notify_time' => '20180307090005',
            'order_no' => '201803070000004333',
            'out_trade_no' => '8d6d5d590db3cc736dd601b886d7ee09',
            'service' => 'Square_Weixin_QR',
            'total_fee' => 100,
            'sign' => '70E48C044804499EEC5DD71C249B8891',
        ];

        $entry = ['id' => '201803070000004334'];

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->verifyOrderPayment($entry);
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
            'is_paid' => 'true',
            'merchant_id' => 'spade88-2',
            'nonce_str' => '9ad4131417e63039539a3e51a959314a',
            'notify_time' => '20180307090005',
            'order_no' => '201803070000004333',
            'out_trade_no' => '8d6d5d590db3cc736dd601b886d7ee09',
            'service' => 'Square_Weixin_QR',
            'total_fee' => 100,
            'sign' => '70E48C044804499EEC5DD71C249B8891',
        ];

        $entry = [
            'id' => '201803070000004333',
            'amount' => '100',
        ];

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'is_paid' => 'true',
            'merchant_id' => 'spade88-2',
            'nonce_str' => '9ad4131417e63039539a3e51a959314a',
            'notify_time' => '20180307090005',
            'order_no' => '201803070000004333',
            'out_trade_no' => '8d6d5d590db3cc736dd601b886d7ee09',
            'service' => 'Square_Weixin_QR',
            'total_fee' => 100,
            'sign' => '70E48C044804499EEC5DD71C249B8891',
        ];

        $entry = [
            'id' => '201803070000004333',
            'amount' => '1.00',
        ];

        $sQ = new SQ();
        $sQ->setPrivateKey('test');
        $sQ->setOptions($options);
        $sQ->verifyOrderPayment($entry);

        $this->assertEquals('success', $sQ->getMsg());
    }
}
