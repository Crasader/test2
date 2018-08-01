<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\RongCaiFu;

class RongCaiFuTest extends DurianTestCase
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

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->getVerifyData();
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

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->getVerifyData();
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
            'paymentVendorId' => '999',
            'number' => '888440089991039',
            'orderId' => '201801230000006381',
            'amount' => '1.01',
            'username' => 'jason',
            'orderCreateDate' => '2017-02-09 17:03:27',
        ];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $rongCaiFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQRcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '888440089991039',
            'orderId' => '201801230000006381',
            'amount' => '1.01',
            'username' => 'jason',
            'orderCreateDate' => '2017-02-09 17:03:27',
        ];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $requestData = $rongCaiFu->getVerifyData();

        $this->assertEquals($options['orderId'], $requestData['requestNo']);
        $this->assertEquals('V4.0', $requestData['version']);
        $this->assertEquals('0104', $requestData['productId']);
        $this->assertEquals('10', $requestData['transId']);
        $this->assertEquals('20170209', $requestData['orderDate']);
        $this->assertEquals($options['orderId'], $requestData['orderNo']);
        $this->assertEquals($options['notify_url'], $requestData['returnUrl']);
        $this->assertEquals($options['notify_url'], $requestData['notifyUrl']);
        $this->assertEquals($options['username'], $requestData['commodityName']);
        $this->assertEquals('1', $requestData['cashier']);
        $this->assertEquals($options['username'], $requestData['memo']);
        $this->assertEquals('77FF4C37C17AA595DA50574BEE8F4B3E', $requestData['signature']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1104',
            'number' => '888440089991039',
            'orderId' => '201801230000006381',
            'amount' => '1.01',
            'username' => 'jason',
            'orderCreateDate' => '2017-02-09 17:03:27',
        ];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $requestData = $rongCaiFu->getVerifyData();

        $this->assertEquals($options['orderId'], $requestData['requestNo']);
        $this->assertEquals('V4.0', $requestData['version']);
        $this->assertEquals('0122', $requestData['productId']);
        $this->assertEquals('01', $requestData['transId']);
        $this->assertEquals('20170209', $requestData['orderDate']);
        $this->assertEquals($options['orderId'], $requestData['orderNo']);
        $this->assertEquals($options['notify_url'], $requestData['returnUrl']);
        $this->assertEquals($options['notify_url'], $requestData['notifyUrl']);
        $this->assertEquals($options['username'], $requestData['commodityName']);
        $this->assertEquals($options['username'], $requestData['memo']);
        $this->assertEquals('67FA98A85087259587A7C3789B5FCBD4', $requestData['signature']);
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '888440089991039',
            'orderId' => '201801230000006381',
            'amount' => '1.01',
            'username' => 'jason',
            'orderCreateDate' => '2017-02-09 17:03:27',
        ];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $requestData = $rongCaiFu->getVerifyData();

        $this->assertEquals($options['orderId'], $requestData['requestNo']);
        $this->assertEquals('V4.0', $requestData['version']);
        $this->assertEquals('0103', $requestData['productId']);
        $this->assertEquals('01', $requestData['transId']);
        $this->assertEquals('20170209', $requestData['orderDate']);
        $this->assertEquals($options['orderId'], $requestData['orderNo']);
        $this->assertEquals($options['notify_url'], $requestData['returnUrl']);
        $this->assertEquals($options['notify_url'], $requestData['notifyUrl']);
        $this->assertEquals($options['username'], $requestData['commodityName']);
        $this->assertEquals('0', $requestData['cashier']);
        $this->assertEquals('01020000', $requestData['bankCode']);
        $this->assertEquals('1', $requestData['payType']);
        $this->assertEquals($options['username'], $requestData['memo']);
        $this->assertEquals('2DF51CCF0CEDB7D67F10C0EB4C81DF41', $requestData['signature']);
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

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->verifyOrderPayment([]);
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

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->verifyOrderPayment([]);
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
            'respCode' => '0000',
            'memo' => 'php1test',
            'orderDate' => '20180123',
            'respDesc' => '交易成功',
            'payTime' => '20180123161505',
            'transAmt' => '10',
            'productId' =>'0104',
            'payId' => '100135194700',
            'orderNo' => '201801230000006400',
            'transId' => '10',
            'notifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'merNo' => '888440089991039',
        ];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $rongCaiFu->verifyOrderPayment([]);
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
            'respCode' => '0000',
            'memo' => 'php1test',
            'orderDate' => '20180123',
            'respDesc' => '交易成功',
            'payTime' => '20180123161505',
            'transAmt' => '10',
            'productId' =>'0104',
            'payId' => '100135194700',
            'orderNo' => '201801230000006400',
            'transId' => '10',
            'notifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signature' => 'test',
            'merNo' => '888440089991039',
        ];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $rongCaiFu->verifyOrderPayment([]);
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
            'respCode' => '0001',
            'memo' => 'php1test',
            'orderDate' => '20180123',
            'respDesc' => '交易成功',
            'payTime' => '20180123161505',
            'transAmt' => '10',
            'productId' =>'0104',
            'payId' => '100135194700',
            'orderNo' => '201801230000006400',
            'transId' => '10',
            'notifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signature' => 'b01f3b70cfe6cf72e3ce1234f8100806',
            'merNo' => '888440089991039',
        ];
        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $rongCaiFu->verifyOrderPayment([]);
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
            'respCode' => '0000',
            'memo' => 'php1test',
            'orderDate' => '20180123',
            'respDesc' => '交易成功',
            'payTime' => '20180123161505',
            'transAmt' => '10',
            'productId' =>'0104',
            'payId' => '100135194700',
            'orderNo' => '201801230000006400',
            'transId' => '10',
            'notifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signature' => 'd0375e0d674e7f03d53b2a2c895b0f1a',
            'merNo' => '888440089991039',
        ];

        $entry = ['id' => '201503220000000555'];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $rongCaiFu->verifyOrderPayment($entry);
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
            'respCode' => '0000',
            'memo' => 'php1test',
            'orderDate' => '20180123',
            'respDesc' => '交易成功',
            'payTime' => '20180123161505',
            'transAmt' => '10',
            'productId' =>'0104',
            'payId' => '100135194700',
            'orderNo' => '201801230000006400',
            'transId' => '10',
            'notifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signature' => 'd0375e0d674e7f03d53b2a2c895b0f1a',
            'merNo' => '888440089991039',
        ];

        $entry = [
            'id' => '201801230000006400',
            'amount' => '15.00',
        ];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $rongCaiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'respCode' => '0000',
            'memo' => 'php1test',
            'orderDate' => '20180123',
            'respDesc' => '交易成功',
            'payTime' => '20180123161505',
            'transAmt' => '10',
            'productId' =>'0104',
            'payId' => '100135194700',
            'orderNo' => '201801230000006400',
            'transId' => '10',
            'notifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signature' => 'd0375e0d674e7f03d53b2a2c895b0f1a',
            'merNo' => '888440089991039',
        ];

        $entry = [
            'id' => '201801230000006400',
            'amount' => '0.1',
        ];

        $rongCaiFu = new RongCaiFu();
        $rongCaiFu->setPrivateKey('test');
        $rongCaiFu->setOptions($options);
        $rongCaiFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $rongCaiFu->getMsg());
    }
}
