<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BossPay;

class BossPayTest extends DurianTestCase
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

        $bossPay = new BossPay();
        $bossPay->getVerifyData();
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

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->getVerifyData();
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
            'number' => '100278',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201801190000003818',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderCreateDate' => '2018-01-19 11:13:04',
        ];

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->setOptions($options);
        $bossPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '100278',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201801190000003818',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderCreateDate' => '2018-01-19 11:13:04',
        ];

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->setOptions($options);
        $requestData = $bossPay->getVerifyData();

        $this->assertEquals('100278', $requestData['MerchantCode']);
        $this->assertEquals('0', $requestData['KJ']);
        $this->assertEquals('ICBC', $requestData['BankCode']);
        $this->assertEquals('0.01', $requestData['Amount']);
        $this->assertEquals('201801190000003818', $requestData['OrderId']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['NotifyUrl']);
        $this->assertEquals('1516331584', $requestData['OrderDate']);
        $this->assertEquals('77A64AED136F662DD28161A4E595FB17', $requestData['Sign']);
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

        $bossPay = new BossPay();
        $bossPay->verifyOrderPayment([]);
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

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'MerchantCode' => '100278',
            'OrderId' => '201801190000003818',
            'OrderDate' => '2018-01-19 14:05:04',
            'OutTradeNo' => 'C119419442589877',
            'Remark' => '',
            'BankCode' => 'ICBC',
            'Amount' => '2.000000',
            'Status' => '1',
            'Time' => '1516348996',
        ];

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->setOptions($options);
        $bossPay->verifyOrderPayment([]);
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
            'MerchantCode' => '100278',
            'OrderId' => '201801190000003818',
            'OrderDate' => '2018-01-19 14:05:04',
            'OutTradeNo' => 'C119419442589877',
            'Remark' => '',
            'BankCode' => 'ICBC',
            'Amount' => '2.000000',
            'Status' => '1',
            'Time' => '1516348996',
            'Sign' => 'ADC15FD3264D7656E893D8FA41E23393',
        ];

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->setOptions($options);
        $bossPay->verifyOrderPayment([]);
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
            'MerchantCode' => '100278',
            'OrderId' => '201801190000003818',
            'OrderDate' => '2018-01-19 14:05:04',
            'OutTradeNo' => 'C119419442589877',
            'Remark' => '',
            'BankCode' => 'ICBC',
            'Amount' => '2.000000',
            'Status' => '0',
            'Time' => '1516348996',
            'Sign' => '16733E81C4D6B9A886DF40090DDDB8C4',
        ];

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->setOptions($options);
        $bossPay->verifyOrderPayment([]);
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
            'MerchantCode' => '100278',
            'OrderId' => '201801190000003818',
            'OrderDate' => '2018-01-19 14:05:04',
            'OutTradeNo' => 'C119419442589877',
            'Remark' => '',
            'BankCode' => 'ICBC',
            'Amount' => '2.000000',
            'Status' => '1',
            'Time' => '1516348996',
            'Sign' => '9C7CC1D5C98696C28589DBCC33A00236',
        ];

        $entry = [
            'id' => '201801190000003819',
            'amount' => '2',
        ];

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->setOptions($options);
        $bossPay->verifyOrderPayment($entry);
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
            'MerchantCode' => '100278',
            'OrderId' => '201801190000003818',
            'OrderDate' => '2018-01-19 14:05:04',
            'OutTradeNo' => 'C119419442589877',
            'Remark' => '',
            'BankCode' => 'ICBC',
            'Amount' => '2.000000',
            'Status' => '1',
            'Time' => '1516348996',
            'Sign' => '9C7CC1D5C98696C28589DBCC33A00236',
        ];

        $entry = [
            'id' => '201801190000003818',
            'amount' => '0.02',
        ];

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->setOptions($options);
        $bossPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'MerchantCode' => '100278',
            'OrderId' => '201801190000003818',
            'OrderDate' => '2018-01-19 14:05:04',
            'OutTradeNo' => 'C119419442589877',
            'Remark' => '',
            'BankCode' => 'ICBC',
            'Amount' => '2.000000',
            'Status' => '1',
            'Time' => '1516348996',
            'Sign' => '9C7CC1D5C98696C28589DBCC33A00236',
        ];

        $entry = [
            'id' => '201801190000003818',
            'amount' => '2',
        ];

        $bossPay = new BossPay();
        $bossPay->setPrivateKey('test');
        $bossPay->setOptions($options);
        $bossPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $bossPay->getMsg());
    }
}
