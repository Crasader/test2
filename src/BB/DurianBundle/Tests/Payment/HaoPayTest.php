<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HaoPay;
use Buzz\Message\Response;

class HaoPayTest extends DurianTestCase
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
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $haoPay = new HaoPay();
        $haoPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions(['number' => '']);
        $haoPay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPayWithNotSupportedBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '40010',
            'orderId' => '201701030000000595',
            'orderCreateDate' => '2017-01-03 12:26:41',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
            'paymentVendorId' => '1314',
        ];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions($options);
        $haoPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '40010',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'orderId' => '201701030000000595',
            'amount' => '100',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
        ];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privateKey');
        $haoPay->setOptions($options);
        $encodeData = $haoPay->getVerifyData();

        $this->assertEquals('Pay.Request', $encodeData['service']);
        $this->assertEquals('40010', $encodeData['partner']);
        $this->assertEquals('1', $encodeData['clientapp']);
        $this->assertEquals('7fb8e33475bf10c45ee265834c3e04f2', $encodeData['sign']);
        $this->assertEquals('MD5', $encodeData['sign_type']);
        $this->assertEquals('Wechatnative', $encodeData['type']);
        $this->assertEquals('utf-8', $encodeData['charset']);
        $this->assertEquals('php1test', $encodeData['subject']);
        $this->assertEquals('201701030000000595', $encodeData['out_trade_no']);
        $this->assertEquals('100', $encodeData['total_fee']);
        $this->assertEquals('http://pay.abc.xyz/pay/return.php', $encodeData['notify_url']);
        $this->assertEquals('', $encodeData['return_url']);
        $this->assertEquals('', $encodeData['show_url']);
        $this->assertEquals('', $encodeData['body']);
        $this->assertEquals('', $encodeData['extra_common_param']);
        $this->assertEquals('', $encodeData['movement']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $haoPay = new HaoPay();
        $haoPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testReturnWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'type' => 'wechatnative',
            'trade_no' => '4001012001201705150894017525',
            'extra_common_param' => '',
            'movement' => '0',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201705150000002088',
            'notify_url' => 'http://pay.6669123.com/pay/return.php',
            'return_url' => '',
            'clientid' => '1326896487',
            'status' => '0',
            'subject' => 'php1test',
            'body' => '',
            'sign' => 'c85bdd6b472a6f45b373e81761b4aaf7',
        ];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions($options);
        $haoPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'type' => 'wechatnative',
            'trade_no' => '4001012001201705150894017525',
            'total_fee' => '0.01',
            'extra_common_param' => '',
            'movement' => '0',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201705150000002088',
            'notify_url' => 'http://pay.6669123.com/pay/return.php',
            'return_url' => '',
            'clientid' => '1326896487',
            'status' => '0',
            'subject' => 'php1test',
            'body' => '',
        ];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions($options);
        $haoPay->verifyOrderPayment([]);
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
            'type' => 'wechatnative',
            'trade_no' => '4001012001201705150894017525',
            'total_fee' => '0.01',
            'extra_common_param' => '',
            'movement' => '0',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201705150000002088',
            'notify_url' => 'http://pay.6669123.com/pay/return.php',
            'return_url' => '',
            'clientid' => '1326896487',
            'status' => '0',
            'subject' => 'php1test',
            'body' => '',
            'sign' => 'wrong sign',
        ];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions($options);
        $haoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'type' => 'wechatnative',
            'trade_no' => '4001012001201705150894017525',
            'total_fee' => '0.01',
            'extra_common_param' => '',
            'movement' => '0',
            'trade_status' => '',
            'out_trade_no' => '201705150000002088',
            'notify_url' => 'http://pay.6669123.com/pay/return.php',
            'return_url' => '',
            'clientid' => '1326896487',
            'status' => '0',
            'subject' => 'php1test',
            'body' => '',
            'sign' => 'e1fca066d74fdec76cfc4180c2941a4f',
        ];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions($options);
        $haoPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'type' => 'wechatnative',
            'trade_no' => '4001012001201705150894017525',
            'total_fee' => '0.01',
            'extra_common_param' => '',
            'movement' => '0',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '1314520',
            'notify_url' => 'http://pay.6669123.com/pay/return.php',
            'return_url' => '',
            'clientid' => '1326896487',
            'status' => '0',
            'subject' => 'php1test',
            'body' => '',
            'sign' => '256c73caee5121f4a0231e25c4afedba',
        ];

        $entry = ['id' => '201701160000000781'];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions($options);
        $haoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'type' => 'wechatnative',
            'trade_no' => '4001012001201705150894017525',
            'total_fee' => '9487',
            'extra_common_param' => '',
            'movement' => '0',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201705150000002088',
            'notify_url' => 'http://pay.6669123.com/pay/return.php',
            'return_url' => '',
            'clientid' => '1326896487',
            'status' => '0',
            'subject' => 'php1test',
            'body' => '',
            'sign' => 'dc32d01a2e793171659957717a9dbadf',
        ];

        $entry = [
            'id' => '201705150000002088',
            'amount' => '0.01',
        ];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions($options);
        $haoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'type' => 'wechatnative',
            'trade_no' => '4001012001201705150894017525',
            'total_fee' => '0.01',
            'extra_common_param' => '',
            'movement' => '0',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => '201705150000002088',
            'notify_url' => 'http://pay.6669123.com/pay/return.php',
            'return_url' => '',
            'clientid' => '1326896487',
            'status' => '0',
            'subject' => 'php1test',
            'body' => '',
            'sign' => '032581f463d31100dec2a3afb885713d',
        ];

        $entry = [
            'id' => '201705150000002088',
            'amount' => '0.01',
        ];

        $haoPay = new HaoPay();
        $haoPay->setPrivateKey('privatekey');
        $haoPay->setOptions($options);
        $haoPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $haoPay->getMsg());
    }
}
