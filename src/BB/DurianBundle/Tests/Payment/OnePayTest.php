<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\OnePay;

class OnePayTest extends DurianTestCase
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

        $onePay = new OnePay();
        $onePay->getVerifyData();
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

        $sourceData = ['number' => ''];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $onePay->getVerifyData();
    }

    /**
     * 測試未支援的銀行
     */
    public function testNoSupportVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '135325',
            'orderCreateDate' => '2017-09-22 06:08:30',
            'orderId' => '201709220000007080',
            'amount' => '50.90',
            'username' => 'php1test',
            'ip' => '1.1.1.1',
            'paymentVendorId' => '1',
        ];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $onePay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '135325',
            'orderCreateDate' => '2017-09-22 06:08:30',
            'orderId' => '201709220000007080',
            'amount' => '50.90',
            'username' => 'php1test',
            'ip' => '1.1.1.1',
            'paymentVendorId' => '1097',
        ];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $verifyData = $onePay->getVerifyData();

        $this->assertEquals('135325', $verifyData['ag_account']);
        $this->assertEquals('20170922060830', $verifyData['pay_time']);
        $this->assertEquals('201709220000007080', $verifyData['order_no']);
        $this->assertEquals('50.90', $verifyData['amount']);
        $this->assertEquals('php1test', $verifyData['attach']);
        $this->assertEquals('1.1.1.1', $verifyData['pay_ip']);
        $this->assertEquals('47beeffdf91eed908aacb65ce57711b6', $verifyData['sign']);
        $this->assertEquals('4', $verifyData['order_type']);
        $this->assertEquals('2', $verifyData['sign_type']);
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

        $onePay = new OnePay();
        $onePay->verifyOrderPayment([]);
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

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'amount' => '50.90',
            'ag_account' => '2',
            'order_no' => '201709220000007080',
            'order_id' => '28194',
            'status' => 'SUCCESS',
            'paid_time' => '20170922140904',
            'attach' => 'php1test',
        ];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $onePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'amount' => '50.90',
            'ag_account' => '2',
            'order_no' => '201709220000007080',
            'order_id' => '28194',
            'sign' => 'b26dcf983e43531a929d08725e25d998',
            'status' => 'SUCCESS',
            'paid_time' => '20170922140904',
            'attach' => 'php1test',
        ];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $onePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'amount' => '50.90',
            'ag_account' => '2',
            'order_no' => '201709220000007080',
            'order_id' => '28194',
            'sign' => '3718ab07b3b0709eb1024c2bec075bf9',
            'status' => 'FAIL',
            'paid_time' => '20170922140904',
            'attach' => 'php1test',
        ];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $onePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'amount' => '50.90',
            'ag_account' => '2',
            'order_no' => '201709220000007080',
            'order_id' => '28194',
            'sign' => '3718ab07b3b0709eb1024c2bec075bf9',
            'status' => 'SUCCESS',
            'paid_time' => '20170922140904',
            'attach' => 'php1test',
        ];

        $entry = [
            'id' => '201709220000007788',
            'amount' => '50.90',
        ];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $onePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'amount' => '50.90',
            'ag_account' => '2',
            'order_no' => '201709220000007080',
            'order_id' => '28194',
            'sign' => '3718ab07b3b0709eb1024c2bec075bf9',
            'status' => 'SUCCESS',
            'paid_time' => '20170922140904',
            'attach' => 'php1test',
        ];

        $entry = [
            'id' => '201709220000007080',
            'amount' => '59.00',
        ];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $onePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'amount' => '50.90',
            'ag_account' => '2',
            'order_no' => '201709220000007080',
            'order_id' => '28194',
            'sign' => '3718ab07b3b0709eb1024c2bec075bf9',
            'status' => 'SUCCESS',
            'paid_time' => '20170922140904',
            'attach' => 'php1test',
        ];

        $entry = [
            'id' => '201709220000007080',
            'amount' => '50.90',
        ];

        $onePay = new OnePay();
        $onePay->setPrivateKey('test');
        $onePay->setOptions($sourceData);
        $onePay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $onePay->getMsg());
    }
}
