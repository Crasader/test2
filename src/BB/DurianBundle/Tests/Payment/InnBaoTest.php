<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\InnBao;

class InnBaoTest extends DurianTestCase
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

        $innBao = new InnBao();
        $innBao->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->getVerifyData();
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
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '100',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
        ];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $innBao->getVerifyData();
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
            'paymentVendorId' => '1',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'postUrl' => '',
        ];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $innBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'postUrl' => 'http://pay.9vpay.com/PayBank.aspx'
        ];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $requestData = $innBao->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals('100.00', $requestData['paymoney']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('ICBC', $requestData['banktype']);
        $this->assertEquals('dcb4c95d8b9b0b7760d9efbe77e74e64', $requestData['sign']);
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

        $innBao = new InnBao();
        $innBao->verifyOrderPayment([]);
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

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->verifyOrderPayment([]);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
        ];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $innBao->verifyOrderPayment([]);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '123456789',
        ];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $innBao->verifyOrderPayment([]);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '2',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '287d48b26a2ac57654e0fc7d2984e76d',
        ];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $innBao->verifyOrderPayment([]);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '5182795a6a2084611aaf8bbe3b8e6756',
        ];

        $entry = ['id' => '201503220000000555'];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $innBao->verifyOrderPayment($entry);
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
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '5182795a6a2084611aaf8bbe3b8e6756',
        ];

        $entry = [
            'id' => '201609300000008335',
            'amount' => '15.00',
        ];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $innBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '16960',
            'ordernumber' => '201609300000008335',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '201609301313514341414000000',
            'attach' => '',
            'sign' => '5182795a6a2084611aaf8bbe3b8e6756',
        ];

        $entry = [
            'id' => '201609300000008335',
            'amount' => '0.01',
        ];

        $innBao = new InnBao();
        $innBao->setPrivateKey('test');
        $innBao->setOptions($options);
        $innBao->verifyOrderPayment($entry);

        $this->assertEquals('ok', $innBao->getMsg());
    }
}
