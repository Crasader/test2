<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BanksPay;

class BanksPayTest extends DurianTestCase
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

        $banksPay = new BanksPay();
        $banksPay->getVerifyData();
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

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions([]);
        $banksPay->getVerifyData();
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
            'paymentVendorId' => '1314',
            'orderId' => '201701030000000595',
            'amount' => '100',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
        ];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions($options);
        $banksPay->getVerifyData();
    }

    /**
     * 測試二維加密
     */
    public function testPayQrcode()
    {
        $options = [
            'number' => '40010',
            'paymentVendorId' => '1090',
            'orderId' => '201701030000000595',
            'amount' => '100',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
        ];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privateKey');
        $banksPay->setOptions($options);
        $encodeData = $banksPay->getVerifyData();

        $this->assertEquals('V1.0', $encodeData['version']);
        $this->assertEquals($options['number'], $encodeData['partner_id']);
        $this->assertEquals('0002', $encodeData['pay_type']);
        $this->assertEquals('', $encodeData['bank_code']);
        $this->assertEquals($options['orderId'], $encodeData['order_no']);
        $this->assertEquals($options['amount'], $encodeData['amount']);
        $this->assertEquals($options['notify_url'], $encodeData['return_url']);
        $this->assertEquals($options['notify_url'], $encodeData['notify_url']);
        $this->assertEquals('', $encodeData['summary']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('56a0d5103b120f5532f4e698de16f720', $encodeData['sign']);
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '40010',
            'paymentVendorId' => '1',
            'orderId' => '201701030000000595',
            'amount' => '100',
            'notify_url' => 'http://pay.abc.xyz/pay/return.php',
        ];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privateKey');
        $banksPay->setOptions($options);
        $encodeData = $banksPay->getVerifyData();

        $this->assertEquals('V1.0', $encodeData['version']);
        $this->assertEquals($options['number'], $encodeData['partner_id']);
        $this->assertSame('0001', $encodeData['pay_type']);
        $this->assertEquals('ICBC', $encodeData['bank_code']);
        $this->assertEquals($options['orderId'], $encodeData['order_no']);
        $this->assertEquals($options['amount'], $encodeData['amount']);
        $this->assertEquals($options['notify_url'], $encodeData['return_url']);
        $this->assertEquals($options['notify_url'], $encodeData['notify_url']);
        $this->assertEquals('', $encodeData['summary']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('a4eb016f0f3f29d53fa75795d173022e', $encodeData['sign']);
    }

    /**
     * 測試解密基本參數設定沒有帶入privateKey的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $banksPay = new BanksPay();
        $banksPay->verifyOrderPayment([]);
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

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions([]);
        $banksPay->verifyOrderPayment([]);
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
            'code' => '00',
            'message' => 'Pay Success',
            'partner_id' => '108701',
            'order_no' => '201707310000002434',
            'trade_no' => '1425113058175504706',
            'amount' => '1.0100',
            'attach' => '',
        ];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions($options);
        $banksPay->verifyOrderPayment([]);
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
            'code' => '00',
            'message' => 'Pay Success',
            'partner_id' => '108701',
            'order_no' => '201707310000002434',
            'trade_no' => '1425113058175504706',
            'amount' => '1.0100',
            'attach' => '',
            'sign' => '',
        ];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions($options);
        $banksPay->verifyOrderPayment([]);
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
            'code' => '01',
            'message' => 'Pay Failed',
            'partner_id' => '108701',
            'order_no' => '201707310000002434',
            'trade_no' => '1425113058175504706',
            'amount' => '1.0100',
            'attach' => '',
            'sign' => 'e3825769a989e1ac991a216c3d4e4acb',
        ];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions($options);
        $banksPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單號不正確
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'code' => '00',
            'message' => 'Pay Success',
            'partner_id' => '108701',
            'order_no' => '201707310000002434',
            'trade_no' => '1425113058175504706',
            'amount' => '1.0100',
            'attach' => '',
            'sign' => '698a6106abd9490081141dce5c833fff',
        ];

        $entry = ['id' => '201707310000002435'];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions($options);
        $banksPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'code' => '00',
            'message' => 'Pay Success',
            'partner_id' => '108701',
            'order_no' => '201707310000002434',
            'trade_no' => '1425113058175504706',
            'amount' => '1.0100',
            'attach' => '',
            'sign' => '698a6106abd9490081141dce5c833fff',
        ];

        $entry = [
            'id' => '201707310000002434',
            'amount' => '0.01',
        ];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions($options);
        $banksPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'code' => '00',
            'message' => 'Pay Success',
            'partner_id' => '108701',
            'order_no' => '201707310000002434',
            'trade_no' => '1425113058175504706',
            'amount' => '1.0100',
            'attach' => '',
            'sign' => '698a6106abd9490081141dce5c833fff',
        ];

        $entry = [
            'id' => '201707310000002434',
            'amount' => '1.01',
        ];

        $banksPay = new BanksPay();
        $banksPay->setPrivateKey('privatekey');
        $banksPay->setOptions($options);
        $banksPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $banksPay->getMsg());
    }
}
