<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MushroomPay;
use Buzz\Message\Response;

class MushroomPayTest extends DurianTestCase
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

        $mushroomPay = new MushroomPay();
        $mushroomPay->getVerifyData();
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

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->getVerifyData();
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
            'notify_url' => 'http://kai0517.netii.net/pay/',
            'paymentVendorId' => '9999',
            'number' => 'MG000009',
            'orderId' => '201804200000008141',
            'amount' => '0.5',
            'orderCreateDate' => '2018-04-25 11:32:32',
        ];

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $mushroomPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testEBankPay()
    {
        $options = [
            'notify_url' => 'http://kai0517.netii.net/pay/',
            'paymentVendorId' => '1',
            'number' => 'MG000009',
            'orderId' => '201804200000008141',
            'amount' => '0.5',
            'orderCreateDate' => '2018-04-25 11:32:32',
        ];

        $date = new \DateTime($options['orderCreateDate']);
        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $requestData = $mushroomPay->getVerifyData();

        $this->assertEquals('MG000009', $requestData['merchant_no']);
        $this->assertEquals('pay', $requestData['method']);
        $this->assertEquals('c31f64f16b9a19823fd5793d2f219cc3', $requestData['sign']);
        $this->assertEquals('201804200000008141', $requestData['out_trade_no']);
        $this->assertEquals('201804200000008141', $requestData['body']);
        $this->assertEquals($date->getTimestamp() . '000', $requestData['timestamp']);
        $this->assertEquals(50, $requestData['amount']);
        $this->assertEquals('http://kai0517.netii.net/pay/', $requestData['notify_url']);
        $this->assertEquals('ebank', $requestData['way']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
    }

    /**
     * 測試銀聯快捷支付
     */
    public function testQuickPay()
    {
        $options = [
            'notify_url' => 'http://kai0517.netii.net/pay/',
            'paymentVendorId' => '278',
            'number' => 'MG000013',
            'orderId' => '201804200000008141',
            'amount' => '0.5',
            'orderCreateDate' => '2018-04-25 11:32:32',
        ];

        $date = new \DateTime($options['orderCreateDate']);
        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $requestData = $mushroomPay->getVerifyData();

        $this->assertEquals('MG000013', $requestData['merchant_no']);
        $this->assertEquals('pay', $requestData['method']);
        $this->assertEquals('522232848e9b2bdee33bf3b22a56049a', $requestData['sign']);
        $this->assertEquals('201804200000008141', $requestData['out_trade_no']);
        $this->assertEquals('201804200000008141', $requestData['body']);
        $this->assertEquals($date->getTimestamp() . '000', $requestData['timestamp']);
        $this->assertEquals(50, $requestData['amount']);
        $this->assertEquals('http://kai0517.netii.net/pay/', $requestData['notify_url']);
        $this->assertEquals('quickpay', $requestData['way']);
        $this->assertEquals('', $requestData['bank_code']);
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

        $mushroomPay = new MushroomPay();
        $mushroomPay->verifyOrderPayment([]);
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

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->verifyOrderPayment([]);
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
            'amount' => '50',
            'code' => '0000',
            'msg' => '调用成功',
            'out_trade_no' => '201804200000008141',
            'status' => '1',
            'trade_no' => '20180425083547320208418',
        ];

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $mushroomPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時code不正確
     */
    public function testReturnCodeNotCorrect()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure', 180035
        );

        $options = [
            'amount' => '50',
            'code' => '70002',
            'msg' => '其他错误',
            'out_trade_no' => '201804200000008141',
            'status' => '0',
            'trade_no' => '20180425083547320208418',
            'sign' => 'a842975aedb8ed5e35ec1377ff82a33a',
        ];

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $mushroomPay->verifyOrderPayment([]);
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
            'amount' => '50',
            'code' => '0000',
            'msg' => '调用成功',
            'out_trade_no' => '201804200000008141',
            'status' => '1',
            'trade_no' => '20180425083547320208418',
            'sign' => '11111111',
        ];

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $mushroomPay->verifyOrderPayment([]);
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
            'amount' => '50',
            'code' => '0000',
            'msg' => '调用成功',
            'out_trade_no' => '201804200000008141',
            'status' => '1',
            'trade_no' => '20180425083547320208418',
            'sign' => '7860a7c2fa0933982cea2829928d0042',
        ];

        $entry = ['id' => '201804200000008142'];

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $mushroomPay->verifyOrderPayment($entry);
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
            'amount' => '50',
            'code' => '0000',
            'msg' => '调用成功',
            'out_trade_no' => '201804200000008141',
            'status' => '1',
            'trade_no' => '20180425083547320208418',
            'sign' => '7860a7c2fa0933982cea2829928d0042',
        ];

        $entry = [
            'id' => '201804200000008141',
            'amount' => '1.00',
        ];

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $mushroomPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'amount' => '50',
            'code' => '0000',
            'msg' => '调用成功',
            'out_trade_no' => '201804200000008141',
            'status' => '1',
            'trade_no' => '20180425083547320208418',
            'sign' => '7860a7c2fa0933982cea2829928d0042',
        ];

        $entry = [
            'id' => '201804200000008141',
            'amount' => '0.5',
        ];

        $mushroomPay = new MushroomPay();
        $mushroomPay->setPrivateKey('test');
        $mushroomPay->setOptions($options);
        $mushroomPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $mushroomPay->getMsg());
    }
}
