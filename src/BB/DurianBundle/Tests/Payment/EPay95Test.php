<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\EPay95;

class EPay95Test extends DurianTestCase
{
    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ePay95 = new EPay95();
        $ePay95->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = ['number' => ''];

        $ePay95->setOptions($sourceData);
        $ePay95->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = [
            'number' => '168885',
            'amount' => '0.01',
            'orderId' => '201412180000000067',
            'notify_url' => 'http://neteller.6te.net/neteller.php?payment_id=54',
            'paymentVendorId' => '999',
            'paymentGatewayId' => '54',
        ];

        $ePay95->setOptions($sourceData);
        $ePay95->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '168885',
            'amount' => '0.01',
            'orderId' => '201412180000000067',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'paymentGatewayId' => '54',
        ];

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');
        $ePay95->setOptions($sourceData);
        $encodeData = $ePay95->getVerifyData();

        $notifyUrl = sprintf(
            '%s?payment_id=%s',
            $sourceData['notify_url'],
            $sourceData['paymentGatewayId']
        );

        $this->assertEquals('168885', $encodeData['MerNo']);
        $this->assertEquals('0.01', $encodeData['Amount']);
        $this->assertEquals('201412180000000067', $encodeData['BillNo']);
        $this->assertEquals($notifyUrl, $encodeData['ReturnURL']);
        $this->assertEquals('ICBC', $encodeData['PaymentType']);
        $this->assertEquals('CSPAY', $encodeData['PayType']);
        $this->assertEquals('7C9C4D03330859F2794B95B233F9E2C8', $encodeData['MD5info']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ePay95 = new EPay95();

        $ePay95->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試amount:金額)
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = [
            'MerNo' => '168885',
            'BillNo' => '201412180000000067',
            'Currency' => '',
            'Succeed' => '14',
            'Result' => '\u7f51\u5740\u672a\u6ce8\u518c',
            'MerRemark' => '',
            'MD5info' => '265414DB8427EF2CDE41D3AE88A986C5',
            'signatureData' => '',
            'sign' => '',
            'Orderno' => '16888514121817939156591'
        ];

        $ePay95->setOptions($sourceData);
        $ePay95->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試MD5info:加密簽名)
     */
    public function testVerifyWithoutMD5info()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = [
            'MerNo' => '168885',
            'BillNo' => '201412180000000067',
            'Currency' => '',
            'Amount' => '0.01',
            'Succeed' => '14',
            'Result' => '\u7f51\u5740\u672a\u6ce8\u518c',
            'MerRemark' => '',
            'signatureData' => '',
            'sign' => '',
            'Orderno' => '16888514121817939156591'
        ];

        $ePay95->setOptions($sourceData);
        $ePay95->verifyOrderPayment([]);
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

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = [
            'MerNo' => '168885',
            'BillNo' => '201412180000000067',
            'Currency' => '',
            'Amount' => '0.01',
            'Succeed' => '88',
            'Result' => '\u7f51\u5740\u672a\u6ce8\u518c',
            'MerRemark' => '',
            'MD5info' => '265414DB8427EF2CDE41D3AE88A986C5',
            'signatureData' => '',
            'sign' => '',
            'Orderno' => '16888514121817939156591'
        ];

        $ePay95->setOptions($sourceData);
        $ePay95->verifyOrderPayment([]);
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

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = [
            'MerNo' => '168885',
            'BillNo' => '201412180000000067',
            'Currency' => '',
            'Amount' => '0.01',
            'Succeed' => '14',
            'Result' => '\u7f51\u5740\u672a\u6ce8\u518c',
            'MerRemark' => '',
            'MD5info' => '265414DB8427EF2CDE41D3AE88A986C5',
            'signatureData' => '',
            'sign' => '',
            'Orderno' => '16888514121817939156591'
        ];

        $ePay95->setOptions($sourceData);
        $ePay95->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = [
            'MerNo' => '168885',
            'BillNo' => '201412180000000067',
            'Currency' => '',
            'Amount' => '0.01',
            'Succeed' => '88',
            'Result' => '\u7f51\u5740\u672a\u6ce8\u518c',
            'MerRemark' => '',
            'MD5info' => 'DC1ED80367822C3CDF53BF48F074E7E1',
            'signatureData' => '',
            'sign' => '',
            'Orderno' => '16888514121817939156591'
        ];

        $entry = ['id' => '20140113143143'];

        $ePay95->setOptions($sourceData);
        $ePay95->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = [
            'MerNo' => '168885',
            'BillNo' => '201412180000000067',
            'Currency' => '',
            'Amount' => '0.01',
            'Succeed' => '88',
            'Result' => '\u7f51\u5740\u672a\u6ce8\u518c',
            'MerRemark' => '',
            'MD5info' => 'DC1ED80367822C3CDF53BF48F074E7E1',
            'signatureData' => '',
            'sign' => '',
            'Orderno' => '16888514121817939156591'
        ];

        $entry = [
            'id' => '201412180000000067',
            'amount' => '115.00'
        ];

        $ePay95->setOptions($sourceData);
        $ePay95->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $ePay95 = new EPay95();
        $ePay95->setPrivateKey('12345678');

        $sourceData = [
            'MerNo' => '168885',
            'BillNo' => '201412180000000067',
            'Currency' => '',
            'Amount' => '0.01',
            'Succeed' => '88',
            'Result' => '\u7f51\u5740\u672a\u6ce8\u518c',
            'MerRemark' => '',
            'MD5info' => 'DC1ED80367822C3CDF53BF48F074E7E1',
            'signatureData' => '',
            'sign' => '',
            'Orderno' => '16888514121817939156591'
        ];

        $entry = [
            'id' => '201412180000000067',
            'amount' => '0.01'
        ];

        $ePay95->setOptions($sourceData);
        $ePay95->verifyOrderPayment($entry);

        $this->assertEquals('Succeed', $ePay95->getMsg());
    }
}
