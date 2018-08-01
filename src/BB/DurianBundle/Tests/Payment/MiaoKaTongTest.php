<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MiaoKaTong;

class MiaoKaTongTest extends DurianTestCase
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

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->getVerifyData();
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

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('1234');
        $miaoKaTong->getVerifyData();
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
            'number' => '80560489',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '999',
            'orderId' => '201709140000004664',
            'amount' => '0.10',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('1234');
        $miaoKaTong->setOptions($options);
        $miaoKaTong->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'orderId' => '201709080000004557',
            'amount' => '1.00',
            'number' => '80560489',
            'ip' => '10.123.123.123',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('1234');
        $miaoKaTong->setOptions($options);
        $encodeData = $miaoKaTong->getVerifyData();

        $this->assertEquals('UTF-8', $encodeData['input_charset']);
        $this->assertEquals($options['notify_url'], $encodeData['inform_url']);
        $this->assertEquals('', $encodeData['return_url']);
        $this->assertEquals('1', $encodeData['pay_type']);
        $this->assertEquals('ICBC', $encodeData['bank_code']);
        $this->assertEquals($options['number'], $encodeData['merchant_code']);
        $this->assertEquals($options['orderId'], $encodeData['order_no']);
        $this->assertEquals('1CE0648BA2BB732EF3EC1241C9C14000', $encodeData['order_amount']);
        $this->assertEquals('2017-06-06 10:06:06', $encodeData['order_time']);
        $this->assertEquals('', $encodeData['req_referer']);
        $this->assertEquals($options['ip'], $encodeData['customer_ip']);
        $this->assertEquals('', $encodeData['return_params']);
        $this->assertEquals('9250bfdaa3348179885190244aeac7ef', $encodeData['sign']);
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

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->verifyOrderPayment([]);
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

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('test');
        $miaoKaTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'merchant_code' => '80560489',
            'order_no' => '201709150000004693',
            'order_amount' => '0.10',
            'order_time' => '2017-09-15 14:32:11',
            'trade_status' => 'success',
            'trade_no' => '17091514321048986720',
            'return_params' => '',
        ];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('test');
        $miaoKaTong->setOptions($options);
        $miaoKaTong->verifyOrderPayment([]);
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
            'merchant_code' => '80560489',
            'order_no' => '201709150000004693',
            'order_amount' => '0.10',
            'order_time' => '2017-09-15 14:32:11',
            'trade_status' => 'success',
            'trade_no' => '17091514321048986720',
            'return_params' => '',
            'sign' => 'c45d11f5b1c07e60287c8e464c58020b',
        ];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('test');
        $miaoKaTong->setOptions($options);
        $miaoKaTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'merchant_code' => '80560489',
            'order_no' => '201709150000004693',
            'order_amount' => '0.10',
            'order_time' => '2017-09-15 14:32:11',
            'trade_status' => 'paying',
            'trade_no' => '17091514321048986720',
            'return_params' => '',
            'sign' => '30b6b621db7d4eede372ad5a57ce0c1c',
        ];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('test');
        $miaoKaTong->setOptions($options);
        $miaoKaTong->verifyOrderPayment([]);
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
            'merchant_code' => '80560489',
            'order_no' => '201709150000004693',
            'order_amount' => '0.10',
            'order_time' => '2017-09-15 14:32:11',
            'trade_status' => 'failed',
            'trade_no' => '17091514321048986720',
            'return_params' => '',
            'sign' => '1ff06046b0fee55c3ae2b0f6a0ad419b',
        ];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('test');
        $miaoKaTong->setOptions($options);
        $miaoKaTong->verifyOrderPayment([]);
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
            'merchant_code' => '80560489',
            'order_no' => '201709150000004693',
            'order_amount' => '0.10',
            'order_time' => '2017-09-15 14:32:11',
            'trade_status' => 'success',
            'trade_no' => '17091514321048986720',
            'return_params' => '',
            'sign' => '2f1af0608e4dd45e635873ee0acd17df',
        ];

        $entry = ['id' => '201706050000001234'];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('test');
        $miaoKaTong->setOptions($options);
        $miaoKaTong->verifyOrderPayment($entry);
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
            'merchant_code' => '80560489',
            'order_no' => '201709150000004693',
            'order_amount' => '0.10',
            'order_time' => '2017-09-15 14:32:11',
            'trade_status' => 'success',
            'trade_no' => '17091514321048986720',
            'return_params' => '',
            'sign' => '2f1af0608e4dd45e635873ee0acd17df',
        ];

        $entry = [
            'id' => '201709150000004693',
            'amount' => '15.00',
        ];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('test');
        $miaoKaTong->setOptions($options);
        $miaoKaTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'merchant_code' => '80560489',
            'order_no' => '201709150000004693',
            'order_amount' => '0.10',
            'order_time' => '2017-09-15 14:32:11',
            'trade_status' => 'success',
            'trade_no' => '17091514321048986720',
            'return_params' => '',
            'sign' => '2f1af0608e4dd45e635873ee0acd17df',
        ];

        $entry = [
            'id' => '201709150000004693',
            'amount' => '0.10',
        ];

        $miaoKaTong = new MiaoKaTong();
        $miaoKaTong->setPrivateKey('test');
        $miaoKaTong->setOptions($options);
        $miaoKaTong->verifyOrderPayment($entry);

        $this->assertEquals('success', $miaoKaTong->getMsg());
    }
}
