<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HeYi;

class HeYiTest extends DurianTestCase
{
    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $heYi = new HeYi();
        $heYi->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201801090000008482',
            'notify_url' => 'http://two123.comuv.com',
        ];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201801090000008482',
            'notify_url' => 'http://two123.comuv.com',
        ];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $encodeData = $heYi->getVerifyData();

        $this->assertEquals($sourceData['amount'], $encodeData['amount']);
        $this->assertEquals($sourceData['number'], $encodeData['mId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderNumber']);
        $this->assertEquals('weixinQRCode', $encodeData['payType']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['returnUrl']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notifyUrl']);
        $this->assertEquals('', $encodeData['extend']);
        $this->assertEquals('42219dcb2552a966f84086602d4a6389', $encodeData['sign']);
    }

    /**
     * 測試返回時基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $heYi = new HeYi();
        $heYi->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
        ];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
            'sysTradeNumber' => '180109668243307001',
            'amount' => '0.01',
            'dealTime' => '1515466912',
            'dealCode' => '10000',
            'extend' => '',
        ];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->verifyOrderPayment([]);
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
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
            'sysTradeNumber' => '180109668243307001',
            'amount' => '0.01',
            'dealTime' => '1515466912',
            'dealCode' => '10000',
            'extend' => '',
            'sign' => '655bb26ac4cc9e5720f2646f90f55f64',
        ];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->verifyOrderPayment([]);
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
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
            'sysTradeNumber' => '180109668243307001',
            'amount' => '0.01',
            'dealTime' => '1515466912',
            'dealCode' => '222222',
            'extend' => '',
            'sign' => '33dc3ad223e2fca5b59f19f4a78a5468',
        ];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
            'sysTradeNumber' => '180109668243307001',
            'amount' => '0.01',
            'dealTime' => '1515466912',
            'dealCode' => '10000',
            'extend' => '',
            'sign' => '81b0fa21bf067521152ddbfbc99ffaee',
        ];

        $entry = ['id' => '201606220000002806'];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
            'sysTradeNumber' => '180109668243307001',
            'amount' => '0.01',
            'dealTime' => '1515466912',
            'dealCode' => '10000',
            'extend' => '',
            'sign' => '81b0fa21bf067521152ddbfbc99ffaee',
        ];

        $entry = [
            'id' => '201801090000008482',
            'amount' => '1.0000',
        ];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
            'sysTradeNumber' => '180109668243307001',
            'amount' => '0.01',
            'dealTime' => '1515466912',
            'dealCode' => '10000',
            'extend' => '',
            'sign' => '81b0fa21bf067521152ddbfbc99ffaee',
        ];

        $entry = [
            'id' => '201801090000008482',
            'amount' => '0.01',
        ];

        $heYi = new HeYi();
        $heYi->setPrivateKey('1234');
        $heYi->setOptions($sourceData);
        $heYi->verifyOrderPayment($entry);

        $this->assertEquals('success', $heYi->getMsg());
    }
}
