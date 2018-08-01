<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Yun70;

class Yun70Test extends DurianTestCase
{
    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yun70 = new Yun70();
        $yun70->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1651',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201711270000002649',
            'notify_url' => 'http://pay.my/pay/return.php',
            'postUrl' => 'http://pay.p163.cn/bank/',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '1651',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201711270000002649',
            'notify_url' => 'http://pay.my/pay/return.php',
            'postUrl' => 'http://pay.p163.cn/bank/',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $encodeData = $yun70->getVerifyData();

        $this->assertEquals('1651', $encodeData['parter']);
        $this->assertEquals('1004', $encodeData['type']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals('201711270000002649', $encodeData['orderid']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['callbackurl']);
        $this->assertEquals('1c5d90d5a36bb2e0ddbaccf5f281134e', $encodeData['sign']);
        $this->assertEquals('GET', $yun70->getPayMethod());
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yun70 = new Yun70();
        $yun70->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201711270000002649',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sysorderid' => 'BF6CFO1651171127144203281',
            'systime' => '2017/11/27 14:42:22',
            'attach' => '',
            'msg' => '支付成功',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->verifyOrderPayment([]);
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
            'orderid' => '201711270000002649',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sysorderid' => 'BF6CFO1651171127144203281',
            'systime' => '2017/11/27 14:42:22',
            'attach' => '',
            'msg' => '支付成功',
            'sign' => '6b50915247d343b804715224fe972a03',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $sourceData = [
            'orderid' => '201711270000002649',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'sysorderid' => 'BF6CFO1651171127144203281',
            'systime' => '2017/11/27 14:42:22',
            'attach' => '',
            'msg' => '',
            'sign' => '7e154b09269a0554552896e6e10a38e7',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付平台驗證簽名錯誤
     */
    public function testReturnPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $sourceData = [
            'orderid' => '201711270000002649',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'sysorderid' => 'BF6CFO1651171127144203281',
            'systime' => '2017/11/27 14:42:22',
            'attach' => '',
            'msg' => '',
            'sign' => 'ca6ac380705c456825c3728cdb41e00d',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->verifyOrderPayment([]);
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
            'orderid' => '201711270000002649',
            'opstate' => '-5',
            'ovalue' => '0.01',
            'sysorderid' => 'BF6CFO1651171127144203281',
            'systime' => '2017/11/27 14:42:22',
            'attach' => '',
            'msg' => '',
            'sign' => '7015fd17a0b14296990bdca3220dfb03',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201711270000002649',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sysorderid' => 'BF6CFO1651171127144203281',
            'systime' => '2017/11/27 14:42:22',
            'attach' => '',
            'msg' => '支付成功',
            'sign' => 'fffba8621a70b92bcccacbeb3cc7f1c0',
        ];

        $entry = [
            'id' => '201711270000002648',
            'amount' => '0.01',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201711270000002649',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sysorderid' => 'BF6CFO1651171127144203281',
            'systime' => '2017/11/27 14:42:22',
            'attach' => '',
            'msg' => '支付成功',
            'sign' => 'fffba8621a70b92bcccacbeb3cc7f1c0',
        ];

        $entry = [
            'id' => '201711270000002649',
            'amount' => '1.00',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201711270000002649',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sysorderid' => 'BF6CFO1651171127144203281',
            'systime' => '2017/11/27 14:42:22',
            'attach' => '',
            'msg' => '支付成功',
            'sign' => 'fffba8621a70b92bcccacbeb3cc7f1c0',
        ];

        $entry = [
            'id' => '201711270000002649',
            'amount' => '0.01',
        ];

        $yun70 = new Yun70();
        $yun70->setPrivateKey('1234');
        $yun70->setOptions($sourceData);
        $yun70->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $yun70->getMsg());
    }
}
