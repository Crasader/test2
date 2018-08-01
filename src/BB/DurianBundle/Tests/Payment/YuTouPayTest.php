<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YuTouPay;
use Buzz\Message\Response;

class YuTouPayTest extends DurianTestCase
{
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

        $yaFuPay = new YuTouPay();
        $yaFuPay->getVerifyData();
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

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->getVerifyData();
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
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '9999',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1',
            'username' => 'php1test',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->getVerifyData();
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '1',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1',
            'username' => 'php1test',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $data = $yaFuPay->getVerifyData();

        $this->assertEquals($options['number'], $data['merNo']);
        $this->assertEquals($options['orderId'], $data['orderNo']);
        $this->assertEquals($options['amount'], $data['amount']);
        $this->assertEquals($options['notify_url'], $data['returnUrl']);
        $this->assertEquals($options['notify_url'], $data['notifyUrl']);
        $this->assertEquals('WY', $data['payType']);
        $this->assertEquals('0', $data['isDirect']);
        $this->assertEquals('102', $data['bankSegment']);
        $this->assertEquals('e4a6e8d15cd86decfc690a0c65f57da0', $data['sign']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '1097',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1',
            'username' => 'php1test',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $data = $yaFuPay->getVerifyData();

        $this->assertEquals($options['number'], $data['merNo']);
        $this->assertEquals($options['orderId'], $data['orderNo']);
        $this->assertEquals($options['amount'], $data['amount']);
        $this->assertEquals($options['notify_url'], $data['returnUrl']);
        $this->assertEquals($options['notify_url'], $data['notifyUrl']);
        $this->assertEquals('WXH5', $data['payType']);
        $this->assertEquals('0', $data['isDirect']);
        $this->assertEquals('6472311a19358d9d344517be8b11db64', $data['sign']);
    }

    /**
     * 測試掃碼支付
     */
    public function testQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '1090',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1',
            'username' => 'php1test',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $data = $yaFuPay->getVerifyData();

        $this->assertEquals($options['number'], $data['merNo']);
        $this->assertEquals($options['orderId'], $data['orderNo']);
        $this->assertEquals($options['amount'], $data['amount']);
        $this->assertEquals($options['notify_url'], $data['returnUrl']);
        $this->assertEquals($options['notify_url'], $data['notifyUrl']);
        $this->assertEquals('WEIXIN', $data['payType']);
        $this->assertEquals('0', $data['isDirect']);
        $this->assertEquals('8c2aa25346ffddb2475d38ee292c6f54', $data['sign']);
    }

    /**
     * 測試銀聯在線
     */
    public function testQuickPay()
    {
        $options = [
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '278',
            'number' => '20781',
            'orderId' => '201710130000005096',
            'amount' => '1',
            'username' => 'php1test',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $data = $yaFuPay->getVerifyData();

        $this->assertEquals($options['number'], $data['merNo']);
        $this->assertEquals($options['orderId'], $data['orderNo']);
        $this->assertEquals($options['amount'], $data['amount']);
        $this->assertEquals($options['notify_url'], $data['returnUrl']);
        $this->assertEquals($options['notify_url'], $data['notifyUrl']);
        $this->assertEquals('KUAIJIE', $data['payType']);
        $this->assertEquals('0', $data['isDirect']);
        $this->assertEquals('6013f45164cd4dd57c6d4993ee468c48', $data['sign']);
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

        $yaFuPay = new YuTouPay();
        $yaFuPay->verifyOrderPayment([]);
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

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->verifyOrderPayment([]);
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
            'payoverTime' => '2018-01-03 10:48:03',
            'orderNo' => '201801030000008637',
            'orderAmount' => '1.000000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment([]);
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
            'sign' => '123456798798',
            'payoverTime' => '2018-01-03 10:48:03',
            'orderNo' => '201801030000008637',
            'orderAmount' => '1.000000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment([]);
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
            'sign' => '6a3642df86b2d31f93daa294d8654554',
            'payoverTime' => '2018-01-03 10:48:03',
            'orderNo' => '201801030000008637',
            'orderAmount' => '1.000000',
            'status' => '0',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment([]);
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
            'sign' => 'efe0074574465c5d2ce6079efd6ae964',
            'payoverTime' => '2018-01-03 10:48:03',
            'orderNo' => '201801030000008',
            'orderAmount' => '1.000000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = ['id' => '201503220000000555'];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment($entry);
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
            'sign' => '66e107b4f77353a55068e1f2e3c6c83b',
            'payoverTime' => '2018-01-03 10:48:03',
            'orderNo' => '201801030000008637',
            'orderAmount' => '1.000000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = [
            'id' => '201801030000008637',
            'amount' => '15.00',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'sign' => '66e107b4f77353a55068e1f2e3c6c83b',
            'payoverTime' => '2018-01-03 10:48:03',
            'orderNo' => '201801030000008637',
            'orderAmount' => '1.000000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = [
            'id' => '201801030000008637',
            'amount' => '1.000000',
        ];

        $yaFuPay = new YuTouPay();
        $yaFuPay->setPrivateKey('test');
        $yaFuPay->setOptions($options);
        $yaFuPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yaFuPay->getMsg());
    }
}
