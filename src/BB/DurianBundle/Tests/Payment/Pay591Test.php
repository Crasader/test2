<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Pay591;

class Pay591Test extends DurianTestCase
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

        $pay591 = new Pay591();
        $pay591->getVerifyData();
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

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1611',
            'paymentVendorId' => '7',
            'amount' => '2.00',
            'orderId' => '201708160000003832',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1611',
            'paymentVendorId' => '1',
            'amount' => '2.00',
            'orderId' => '201708160000003832',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $encodeData = $pay591->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertSame('2.00', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('818c67acb75455c896bbd31cdf2b1bd9', $encodeData['sign']);
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

        $pay591 = new Pay591();
        $pay591->verifyOrderPayment([]);
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

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->verifyOrderPayment([]);
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
            'orderid' => '201708160000003832',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1611201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->verifyOrderPayment([]);
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
            'orderid' => '201708160000003832',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1611201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '8b27dea7edf10f440ad4852b771fd4fb',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnWithInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $sourceData = [
            'orderid' => '201708160000003832',
            'opstate' => '-1',
            'ovalue' => '2',
            'sysorderid' => 'B1611201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => 'a65c87129743cbb501fb284edd09dd9f',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付平台驗證簽名錯誤
     */
    public function testReturnWithPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $sourceData = [
            'orderid' => '201708160000003832',
            'opstate' => '-2',
            'ovalue' => '2',
            'sysorderid' => 'B1611201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '8f8f696dbecc291bdb19d7e6dd57f4ec',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->verifyOrderPayment([]);
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
            'orderid' => '201708160000003832',
            'opstate' => '99',
            'ovalue' => '2',
            'sysorderid' => 'B1611201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => 'fc0372f9434e28c72f250f269532d5a8',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->verifyOrderPayment([]);
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
            'orderid' => '201708160000003832',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1611201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => 'a224f4485fc02b538896fe3fdc9cbcc0',
        ];

        $entry = ['id' => '201702090000001337'];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->verifyOrderPayment($entry);
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
            'orderid' => '201708160000003832',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1611201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => 'a224f4485fc02b538896fe3fdc9cbcc0',
        ];

        $entry = [
            'id' => '201708160000003832',
            'amount' => '0.01',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201708160000003832',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1611201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => 'a224f4485fc02b538896fe3fdc9cbcc0',
        ];

        $entry = [
            'id' => '201708160000003832',
            'amount' => '2',
        ];

        $pay591 = new Pay591();
        $pay591->setPrivateKey('test');
        $pay591->setOptions($sourceData);
        $pay591->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $pay591->getMsg());
    }
}
