<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JinMiPay;

class JinMiPayTest extends DurianTestCase
{
    /**
     * 測試支付時沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jinMiPay = new JinMiPay();
        $jinMiPay->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions([]);
        $jinMiPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '9527',
            'amount' => '0.01',
            'orderId' => '201710050000009527',
            'notify_url' => 'http://seafood.help',
            'paymentVendorId' => '999',
            'username' => 'seafood',
        ];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $jinMiPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'number' => '9527',
            'amount' => '0.01',
            'orderId' => '201710050000009527',
            'notify_url' => 'http://seafood.help',
            'paymentVendorId' => '1',
            'username' => 'seafood',
        ];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $encodeData = $jinMiPay->getVerifyData();

        $this->assertEquals('pay.b2c', $encodeData['Service']);
        $this->assertEquals($options['number'], $encodeData['MerNo']);
        $this->assertEquals($options['orderId'], $encodeData['BillNo']);
        $this->assertEquals($options['amount'] * 100, $encodeData['Amount']);
        $this->assertEquals($options['notify_url'], $encodeData['ReturnURL']);
        $this->assertEquals($options['notify_url'], $encodeData['NotifyURL']);
        $this->assertEquals('660469c28c60afc4cdc9aa4339c4c45f', $encodeData['MD5info']);
        $this->assertEquals($options['username'], $encodeData['GoodsSubject']);
        $this->assertEquals('1021000', $encodeData['BankCode']);
        $this->assertEquals('', $encodeData['Remark']);
    }

    /**
     * 測試快捷支付
     */
    public function testQuickPay()
    {
        $options = [
            'number' => '9527',
            'amount' => '0.01',
            'orderId' => '201710050000009527',
            'notify_url' => 'http://seafood.help',
            'paymentVendorId' => '279',
            'username' => 'seafood',
        ];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $encodeData = $jinMiPay->getVerifyData();

        $this->assertEquals('pay.kj', $encodeData['Service']);
        $this->assertEquals($options['number'], $encodeData['MerNo']);
        $this->assertEquals($options['orderId'], $encodeData['BillNo']);
        $this->assertEquals($options['amount'] * 100, $encodeData['Amount']);
        $this->assertEquals($options['notify_url'], $encodeData['ReturnURL']);
        $this->assertEquals($options['notify_url'], $encodeData['NotifyURL']);
        $this->assertEquals('fe042481fe9b05e36e610a9da2e9da25', $encodeData['MD5info']);
        $this->assertEquals($options['username'], $encodeData['GoodsSubject']);
        $this->assertEquals($options['username'], $encodeData['UserId']);
        $this->assertEquals('', $encodeData['Remark']);
    }

    /**
     * 測試返回缺少PrivateKey
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jinMiPay = new JinMiPay();
        $jinMiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回缺少回傳參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回缺少簽名參數
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'FlowId' => '9527',
            'BillNo' => '201710050000009527',
            'Amount' => '0.01',
            'Status' => '1',
            'TransTime' => '20171005175806',
        ];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $jinMiPay->verifyOrderPayment([]);
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
            'FlowId' => '9527',
            'BillNo' => '201710050000009527',
            'Amount' => '0.01',
            'Status' => '1',
            'TransTime' => '20171005175806',
            'MD5info' => 'seafood-help-me',
        ];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $jinMiPay->verifyOrderPayment([]);
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
            'FlowId' => '9527',
            'BillNo' => '201710050000009527',
            'Amount' => '0.01',
            'Status' => '0',
            'TransTime' => '20171005175806',
            'MD5info' => 'f689cf215b3169fe637254ae3a67014f',
        ];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $jinMiPay->verifyOrderPayment([]);
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
            'FlowId' => '9527',
            'BillNo' => '201710050000009527',
            'Amount' => '0.01',
            'Status' => '1',
            'TransTime' => '20171005175806',
            'MD5info' => '40f1288a67a0c7d5db58ffd79741085b',
        ];

        $entry = ['id' => '201710050000009453'];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $jinMiPay->verifyOrderPayment($entry);
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
            'FlowId' => '9527',
            'BillNo' => '201710050000009527',
            'Amount' => '0.01',
            'Status' => '1',
            'TransTime' => '20171005175806',
            'MD5info' => '40f1288a67a0c7d5db58ffd79741085b',
        ];

        $entry = [
            'id' => '201710050000009527',
            'amount' => '11.00',
        ];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $jinMiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'FlowId' => '9527',
            'BillNo' => '201710050000009527',
            'Amount' => '100',
            'Status' => '1',
            'TransTime' => '20171005175806',
            'MD5info' => '59c639a5b46485fb3da4f06f1020a07f',
        ];

        $entry = [
            'id' => '201710050000009527',
            'amount' => '1',
        ];

        $jinMiPay = new JinMiPay();
        $jinMiPay->setPrivateKey('test');
        $jinMiPay->setOptions($options);
        $jinMiPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jinMiPay->getMsg());
    }
}
