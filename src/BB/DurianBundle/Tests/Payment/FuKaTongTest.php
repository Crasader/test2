<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\FuKaTong;

class FuKaTongTest extends DurianTestCase
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

        $fuKaTong = new FuKaTong();
        $fuKaTong->getVerifyData();
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

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->getVerifyData();
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

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171124114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '99',
            'orderCreateDate' => '2017-11-24 11:46:12',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->getVerifyData();
    }

    /**
     * 測試支付設定密鑰格式錯誤
     */
    public function testPayPrivateKeyFormatError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Invalid Private Key',
            150180208
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171124114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-11-24 11:46:12',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('test');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '20171124114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-11-24 11:46:12',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $requestData = $fuKaTong->getVerifyData();

        $this->assertEquals('UTF-8', $requestData['input_charset']);
        $this->assertEquals($sourceData['notify_url'], $requestData['inform_url']);
        $this->assertEquals($sourceData['notify_url'], $requestData['return_url']);
        $this->assertEquals('1', $requestData['pay_type']);
        $this->assertEquals('ICBC', $requestData['bank_code']);
        $this->assertEquals('9527', $requestData['merchant_code']);
        $this->assertEquals('20171124114612', $requestData['order_no']);
        $this->assertEquals('AAEEE4128E72D012023C384228AADF95', $requestData['order_amount']);
        $this->assertEquals('2017-11-24 11:46:12', $requestData['order_time']);
        $this->assertEquals('', $requestData['req_referer']);
        $this->assertEquals('127.0.0.1', $requestData['customer_ip']);
        $this->assertEquals('', $requestData['return_params']);
        $this->assertEquals('86ebc89efc343816275ca92185638cf8', $requestData['sign']);
    }

    /**
     * 測試非網銀支付
     */
    public function testPayNonOnlineBank()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '20171124114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2017-11-24 11:46:12',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $requestData = $fuKaTong->getVerifyData();

        $this->assertEquals('UTF-8', $requestData['input_charset']);
        $this->assertEquals($sourceData['notify_url'], $requestData['inform_url']);
        $this->assertEquals($sourceData['notify_url'], $requestData['return_url']);
        $this->assertEquals('5', $requestData['pay_type']);
        $this->assertEquals('9527', $requestData['merchant_code']);
        $this->assertEquals('20171124114612', $requestData['order_no']);
        $this->assertEquals('AAEEE4128E72D012023C384228AADF95', $requestData['order_amount']);
        $this->assertEquals('2017-11-24 11:46:12', $requestData['order_time']);
        $this->assertEquals('', $requestData['req_referer']);
        $this->assertEquals('127.0.0.1', $requestData['customer_ip']);
        $this->assertEquals('', $requestData['return_params']);
        $this->assertEquals('dc546ebc9a75ac1cf30a59be7a564574', $requestData['sign']);
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

        $fuKaTong = new FuKaTong();
        $fuKaTong->verifyOrderPayment([]);
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

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->verifyOrderPayment([]);
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

        $sourceData = [
            'merchant_code' => '9527',
            'order_no' => '20171124114656',
            'order_amount' => '100.00',
            'order_time' => '2017-11-24 11:46:12',
            'trade_status' => 'success',
            'trade_no' => '17112411060448608549',
            'return_params' => '',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->verifyOrderPayment([]);
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

        $sourceData = [
            'merchant_code' => '9527',
            'order_no' => '20171124114656',
            'order_amount' => '100.00',
            'order_time' => '2017-11-24 11:46:12',
            'trade_status' => 'success',
            'trade_no' => '17112411060448608549',
            'return_params' => '',
            'sign' => 'SeafoodIsGood',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $sourceData = [
            'merchant_code' => '9527',
            'order_no' => '20171124114656',
            'order_amount' => '100.00',
            'order_time' => '2017-11-24 11:46:12',
            'trade_status' => 'paying',
            'trade_no' => '17112411060448608549',
            'return_params' => '',
            'sign' => '2094e7174dee188359ec5f5aef07b0ef',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->verifyOrderPayment([]);
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

        $sourceData = [
            'merchant_code' => '9527',
            'order_no' => '20171124114656',
            'order_amount' => '100.00',
            'order_time' => '2017-11-24 11:46:12',
            'trade_status' => 'failed',
            'trade_no' => '17112411060448608549',
            'return_params' => '',
            'sign' => '93bab553b40f947528d08532c2b303e6',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->verifyOrderPayment([]);
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

        $sourceData = [
            'merchant_code' => '9527',
            'order_no' => '20171124114656',
            'order_amount' => '100.00',
            'order_time' => '2017-11-24 11:46:12',
            'trade_status' => 'success',
            'trade_no' => '17112411060448608549',
            'return_params' => '',
            'sign' => '23c064628cc5bb62896b0e9c40adeaa4',
        ];

        $entry = ['id' => '201705220000000321'];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->verifyOrderPayment($entry);
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

        $sourceData = [
            'merchant_code' => '9527',
            'order_no' => '20171124114656',
            'order_amount' => '100.00',
            'order_time' => '2017-11-24 11:46:12',
            'trade_status' => 'success',
            'trade_no' => '17112411060448608549',
            'return_params' => '',
            'sign' => '23c064628cc5bb62896b0e9c40adeaa4',
        ];

        $entry = [
            'id' => '20171124114656',
            'amount' => '10.00',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'merchant_code' => '9527',
            'order_no' => '20171124114656',
            'order_amount' => '100.00',
            'order_time' => '2017-11-24 11:46:12',
            'trade_status' => 'success',
            'trade_no' => '17112411060448608549',
            'return_params' => '',
            'sign' => '23c064628cc5bb62896b0e9c40adeaa4',
        ];

        $entry = [
            'id' => '20171124114656',
            'amount' => '100.00',
        ];

        $fuKaTong = new FuKaTong();
        $fuKaTong->setPrivateKey('74657374');
        $fuKaTong->setOptions($sourceData);
        $fuKaTong->verifyOrderPayment($entry);

        $this->assertEquals('success', $fuKaTong->getMsg());
    }
}
