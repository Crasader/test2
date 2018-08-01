<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\RongXinPay;

class RongXinPayTest extends DurianTestCase
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

        $rongXinPay = new RongXinPay();
        $rongXinPay->getVerifyData();
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

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->getVerifyData();
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
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '100',
            'number' => '16965',
            'orderId' => '201802140000009937',
            'amount' => '100',
        ];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $rongXinPay->getVerifyData();
    }

    /**
     * 測試支付沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1092',
            'number' => '16965',
            'orderId' => '201802140000009937',
            'amount' => '100',
            'postUrl' => '',
        ];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $rongXinPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1092',
            'number' => '16965',
            'orderId' => '201802140000009937',
            'amount' => '100',
            'postUrl' => 'http://www.cyqhk.com/payBank.aspx',
        ];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $requestData = $rongXinPay->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals('100.00', $requestData['paymoney']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('ALIPAY', $requestData['banktype']);
        $this->assertEquals('7a1a37d6303c5058e5a76fe4bf8a6519', $requestData['sign']);

        $postUrl = 'http://www.cyqhk.com/payBank.aspx?partner=16965&banktype=ALIPAY&' .
            'paymoney=100.00&ordernumber=201802140000009937&callbackurl=http%3A%2F%2F' .
            'two123.comxa.com%2F&hrefbackurl=&attach=&sign=7a1a37d6303c5058e5a76fe4bf8a6519';
        $this->assertEquals($postUrl, $requestData['act_url']);
        $this->assertEquals('GET', $rongXinPay->getPayMethod());
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

        $rongXinPay = new RongXinPay();
        $rongXinPay->verifyOrderPayment([]);
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

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->verifyOrderPayment([]);
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
            'partner' => '16965',
            'ordernumber' => '201802140000009937',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
        ];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $rongXinPay->verifyOrderPayment([]);
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
            'partner' => '16965',
            'ordernumber' => '201802140000009937',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => '123456789',
        ];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $rongXinPay->verifyOrderPayment([]);
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
            'partner' => '16965',
            'ordernumber' => '201802140000009937',
            'orderstatus' => '2',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => 'aa1e0db255fc940a3812525653c98004',
        ];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $rongXinPay->verifyOrderPayment([]);
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
            'partner' => '16965',
            'ordernumber' => '201802140000009937',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => 'd18ea27a639d866789363937011c5d5a',
        ];

        $entry = ['id' => '201503220000000555'];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $rongXinPay->verifyOrderPayment($entry);
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
            'partner' => '16965',
            'ordernumber' => '201709150000007037',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => '624fd989ef8c11974cf92a157968ce8a',
        ];

        $entry = [
            'id' => '201709150000007037',
            'amount' => '15.00',
        ];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $rongXinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'partner' => '16965',
            'ordernumber' => '201709150000007037',
            'orderstatus' => '1',
            'paymoney' => '0.05',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => '73fe3c9c46e3e4eb920eefe2be328b4b',
        ];

        $entry = [
            'id' => '201709150000007037',
            'amount' => '0.05',
        ];

        $rongXinPay = new RongXinPay();
        $rongXinPay->setPrivateKey('test');
        $rongXinPay->setOptions($options);
        $rongXinPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $rongXinPay->getMsg());
    }
}
