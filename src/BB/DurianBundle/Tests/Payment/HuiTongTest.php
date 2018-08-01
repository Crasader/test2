<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiTong;

class HuiTongTest extends DurianTestCase
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

        $huiTong = new HuiTong();
        $huiTong->getVerifyData();
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

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithNotSupportedBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'notify_url' => 'http://seafood.help.com',
            'paymentVendorId' => '100',
            'number' => '9527',
            'orderId' => '201710020000002073',
            'amount' => '1.00',
            'orderCreateDate' => '2017-10-02 15:10:15',
            'ip' => '127.0.0.1',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->getVerifyData();
    }

    /**
     * 測試支付時帶入錯誤格式notify_url
     */
    public function testPayWithInvalidNotifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Invalid notify_url',
            180146
        );

        $options = [
            'notify_url' => 'http:seafood.hh',
            'paymentVendorId' => '1',
            'number' => '9527',
            'orderId' => '201710020000002073',
            'amount' => '1.00',
            'orderCreateDate' => '2017-10-02 15:10:15',
            'ip' => '127.0.0.1',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->getVerifyData();
    }

    /**
     * 測試微信支付
     */
    public function testWeChatPay()
    {
        $options = [
            'notify_url' => 'http://seafood.help.com',
            'paymentVendorId' => '1090',
            'number' => '9527',
            'orderId' => '201710020000002073',
            'amount' => '1.00',
            'orderCreateDate' => '2017-10-02 15:10:15',
            'ip' => '127.0.0.1',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $requestData = $huiTong->getVerifyData();

        $this->assertEquals($options['notify_url'], $requestData['notify_url']);
        $this->assertEquals($options['notify_url'], $requestData['return_url']);
        $this->assertEquals('2', $requestData['pay_type']);
        $this->assertEquals($options['number'], $requestData['merchant_code']);
        $this->assertEquals($options['orderId'], $requestData['order_no']);
        $this->assertEquals($options['amount'], $requestData['order_amount']);
        $this->assertEquals($options['orderCreateDate'], $requestData['order_time']);
        $this->assertEquals('seafood.help.com', $requestData['req_referer']);
        $this->assertEquals($options['ip'], $requestData['customer_ip']);
        $this->assertEquals('383dd1099510e1db315d839fbd3dc89c', $requestData['sign']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://seafood.help.com',
            'paymentVendorId' => '1',
            'number' => '9527',
            'orderId' => '201710020000002073',
            'amount' => '1.00',
            'orderCreateDate' => '2017-10-02 15:10:15',
            'ip' => '127.0.0.1',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $requestData = $huiTong->getVerifyData();

        $this->assertEquals($options['notify_url'], $requestData['notify_url']);
        $this->assertEquals($options['notify_url'], $requestData['return_url']);
        $this->assertEquals('1', $requestData['pay_type']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
        $this->assertEquals($options['number'], $requestData['merchant_code']);
        $this->assertEquals($options['orderId'], $requestData['order_no']);
        $this->assertEquals($options['amount'], $requestData['order_amount']);
        $this->assertEquals($options['orderCreateDate'], $requestData['order_time']);
        $this->assertEquals('seafood.help.com', $requestData['req_referer']);
        $this->assertEquals($options['ip'], $requestData['customer_ip']);
        $this->assertEquals('854b1a6b6320c81c3b4f1f9721319a44', $requestData['sign']);
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

        $huiTong = new HuiTong();
        $huiTong->verifyOrderPayment([]);
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

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->verifyOrderPayment([]);
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
            'order_no' => '201710020000002073',
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_amount' => '100',
            'order_time' => '2017-03-16 09:45:11',
            'trade_no' => '3063601165464056',
            'trade_time' => '2015-06-10 14:24:24',
            'trade_status' => 'success',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->verifyOrderPayment([]);
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
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201710020000002073',
            'order_amount' => '0.01',
            'order_time' => '2017-03-16 09:45:11',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-03-16 09:45:11',
            'trade_status' => 'success',
            'sign' => 'seafood9595995',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單支付中
     */
    public function testReturnPaymentOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201710020000002073',
            'order_amount' => '0.01',
            'order_time' => '2017-03-16 09:45:11',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-03-16 09:45:11',
            'trade_status' => 'paying',
            'sign' => '00cb16079f8deedc7b89def8bca15a89',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->verifyOrderPayment([]);
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
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201710020000002073',
            'order_amount' => '0.01',
            'order_time' => '2017-03-16 09:45:11',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-03-16 09:45:11',
            'trade_status' => 'faild',
            'sign' => 'f4e14e7558ed5e25b22379c20764d4da',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->verifyOrderPayment([]);
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
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201710020000002073',
            'order_amount' => '0.01',
            'order_time' => '2017-03-16 09:45:11',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-03-16 09:45:11',
            'trade_status' => 'success',
            'sign' => 'f168baef56185bde985fc0e236bb1959',
        ];

        $entry = ['id' => '201703220000000666'];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->verifyOrderPayment($entry);
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
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201710020000002073',
            'order_amount' => '0.01',
            'order_time' => '2017-03-16 09:45:11',
            'return_params' => 'phptest',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-03-16 09:45:11',
            'trade_status' => 'success',
            'sign' => 'f168baef56185bde985fc0e236bb1959',
        ];

        $entry = [
            'id' => '201710020000002073',
            'amount' => '15.00',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchant_code' => '9527',
            'notify_type' => 'back_notify',
            'order_no' => '201710020000002073',
            'order_amount' => '0.01',
            'order_time' => '2017-03-16 09:45:11',
            'return_params' => 'phptest',
            'trade_no' => '3063601165464056',
            'trade_time' => '2017-03-16 09:45:11',
            'trade_status' => 'success',
            'sign' => 'f168baef56185bde985fc0e236bb1959',
        ];

        $entry = [
            'id' => '201710020000002073',
            'amount' => '0.01',
        ];

        $huiTong = new HuiTong();
        $huiTong->setPrivateKey('test');
        $huiTong->setOptions($options);
        $huiTong->verifyOrderPayment($entry);

        $this->assertEquals('success', $huiTong->getMsg());
    }
}
