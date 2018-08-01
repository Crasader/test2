<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiYuTung;

class YiYuTungTest extends DurianTestCase
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

        $yiYuTung = new YiYuTung();
        $yiYuTung->getVerifyData();
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

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->getVerifyData();
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
            'number' => '8000006021',
            'orderId' => '201801290000003898',
            'amount' => '1',
            'orderCreateDate' => '2018-01-29 14:00:30',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '999',
            'username' => 'php1test',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $yiYuTung->getVerifyData();
    }

    /**
     * 測試二维支付
     */
    public function testQrCodePay()
    {
        $options = [
            'number' => '8000006021',
            'orderId' => '201801290000003898',
            'amount' => '1',
            'orderCreateDate' => '2018-01-29 14:00:30',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $requestData = $yiYuTung->getVerifyData();

        $this->assertEquals('8000006021', $requestData['merchant_id']);
        $this->assertEquals('201801290000003898', $requestData['billno']);
        $this->assertEquals('1', $requestData['amount']);
        $this->assertEquals('20180129140030', $requestData['order_date']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['notify_url']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['return_url']);
        $this->assertEquals('QQPay', $requestData['bank_code']);
        $this->assertEquals('php1test', $requestData['goods_name']);
        $this->assertEquals('4', $requestData['pay_type']);
        $this->assertEquals('MD5', $requestData['sign_type']);
        $this->assertEquals('d94632d707aadbc5e4ff2c267b587a35', $requestData['sign']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '8000006021',
            'orderId' => '201801290000003898',
            'amount' => '1',
            'orderCreateDate' => '2018-01-29 14:00:30',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $requestData = $yiYuTung->getVerifyData();

        $this->assertEquals('8000006021', $requestData['merchant_id']);
        $this->assertEquals('201801290000003898', $requestData['billno']);
        $this->assertEquals('1', $requestData['amount']);
        $this->assertEquals('20180129140030', $requestData['order_date']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['notify_url']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['return_url']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
        $this->assertEquals('php1test', $requestData['goods_name']);
        $this->assertEquals('1', $requestData['pay_type']);
        $this->assertEquals('MD5', $requestData['sign_type']);
        $this->assertEquals('d94632d707aadbc5e4ff2c267b587a35', $requestData['sign']);
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

        $yiYuTung = new YiYuTung();
        $yiYuTung->verifyOrderPayment([]);
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

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'billno' => '201801290000003898',
            'merchant_id' => '8000006021',
            'order_id' => '20180129140030957855956799197184',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180129140030',
            'attach' => '',
            'sign_type' => 'MD5',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $yiYuTung->verifyOrderPayment([]);
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
            'billno' => '201801290000003898',
            'merchant_id' => '8000006021',
            'order_id' => '20180129140030957855956799197184',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180129140030',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => '4b822b67dc5c83ab7dc13e1001b1987e',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $yiYuTung->verifyOrderPayment([]);
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
            'billno' => '201801290000003898',
            'merchant_id' => '8000006021',
            'order_id' => '20180129140030957855956799197184',
            'success' => 'Failure',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180129140030',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => '6b19c8418e685985a1d7a652ca15fdbd',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $yiYuTung->verifyOrderPayment([]);
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
            'billno' => '201801290000003898',
            'merchant_id' => '8000006021',
            'order_id' => '20180129140030957855956799197184',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180129140030',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => '2e280812993add6a21c483e41852927c',
        ];

        $entry = [
            'id' => '201801290000003899',
            'amount' => '1',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $yiYuTung->verifyOrderPayment($entry);
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
            'billno' => '201801290000003898',
            'merchant_id' => '8000006021',
            'order_id' => '20180129140030957855956799197184',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180129140030',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => '2e280812993add6a21c483e41852927c',
        ];

        $entry = [
            'id' => '201801290000003898',
            'amount' => '0.01',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $yiYuTung->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'billno' => '201801290000003898',
            'merchant_id' => '8000006021',
            'order_id' => '20180129140030957855956799197184',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180129140030',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => '2e280812993add6a21c483e41852927c',
        ];

        $entry = [
            'id' => '201801290000003898',
            'amount' => '1',
        ];

        $yiYuTung = new YiYuTung();
        $yiYuTung->setPrivateKey('test');
        $yiYuTung->setOptions($options);
        $yiYuTung->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yiYuTung->getMsg());
    }
}
