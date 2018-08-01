<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BeePay;

class BeePayTest extends DurianTestCase
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

        $beePy = new BeePay();
        $beePy->getVerifyData();
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

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->getVerifyData();
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
            'number' => '100000003',
            'orderId' => '201711230000005778',
            'amount' => '1.01',
            'username' => 'php1test',
        ];

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->setOptions($options);
        $beePy->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '100000003',
            'orderId' => '201711230000005778',
            'amount' => '1.01',
            'username' => 'php1test',
        ];

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->setOptions($options);
        $data = $beePy->getVerifyData();

        $this->assertEquals($options['number'], $data['merchant_code']);
        $this->assertEquals($options['orderId'], $data['merchant_order_no']);
        $this->assertEquals($options['username'], $data['merchant_goods']);
        $this->assertEquals($options['amount'], $data['merchant_amount']);
        $this->assertEquals('wechat', $data['gateway']);
        $this->assertEquals($options['notify_url'], $data['urlcall']);
        $this->assertEquals('', $data['urlback']);
        $this->assertEquals('OWIwODcwNjk3YjcxMGU4MTZiNTA5MTI0ZmNkYzM1Njc=', $data['merchant_sign']);
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

        $beePy = new BeePay();
        $beePy->verifyOrderPayment([]);
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

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->verifyOrderPayment([]);
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
            'merchant_code' => '100000003',
            'merchant_order_no' => '201711230000005778',
            'merchant_amount' => '1.00',
            'merchant_amount_orig' => '1.00',
        ];

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->setOptions($options);
        $beePy->verifyOrderPayment([]);
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
            'merchant_code' => '100000003',
            'merchant_order_no' => '201711230000005778',
            'merchant_amount' => '1.00',
            'merchant_amount_orig' => '1.00',
            'merchant_sign' => 'WTF',
        ];

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->setOptions($options);
        $beePy->verifyOrderPayment([]);
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
            'merchant_code' => '100000003',
            'merchant_order_no' => '201711230000005778',
            'merchant_amount' => '1.00',
            'merchant_amount_orig' => '1.00',
            'merchant_sign' => 'MGI2N2Q0N2UyYzdhZDJhNWZlMDQzYzVkYWVlMDhjYWY=',
        ];

        $entry = ['id' => '201503220000000555'];

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->setOptions($options);
        $beePy->verifyOrderPayment($entry);
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
            'merchant_code' => '100000003',
            'merchant_order_no' => '201711230000005778',
            'merchant_amount' => '1.00',
            'merchant_amount_orig' => '1.00',
            'merchant_sign' => 'MGI2N2Q0N2UyYzdhZDJhNWZlMDQzYzVkYWVlMDhjYWY=',
        ];

        $entry = [
            'id' => '201711230000005778',
            'amount' => '15.00',
        ];

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->setOptions($options);
        $beePy->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'merchant_code' => '100000003',
            'merchant_order_no' => '201711230000005778',
            'merchant_amount' => '1.00',
            'merchant_amount_orig' => '1.00',
            'merchant_sign' => 'MGI2N2Q0N2UyYzdhZDJhNWZlMDQzYzVkYWVlMDhjYWY=',
        ];

        $entry = [
            'id' => '201711230000005778',
            'amount' => '1.0',
        ];

        $beePy = new BeePay();
        $beePy->setPrivateKey('test');
        $beePy->setOptions($options);
        $beePy->verifyOrderPayment($entry);

        $this->assertEquals('success', $beePy->getMsg());
    }
}
