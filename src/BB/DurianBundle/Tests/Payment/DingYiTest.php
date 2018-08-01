<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DingYi;

class DingYiTest extends DurianTestCase
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

        $dingYi = new DingYi();
        $dingYi->getVerifyData();
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

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->getVerifyData();
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
            'number' => '10117',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201709120000006972',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '10117',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201709120000006972',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $encodeData = $dingYi->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('d0285676e69d9cb9c1f3a0f3b42b7441', $encodeData['sign']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $dingYi = new DingYi();
        $dingYi->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201709120000006972',
            'opstate' => '0',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201709120000006972',
            'opstate' => '0',
            'ovalue' => '0.01',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment([]);
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
            'orderid' => '201709120000006972',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => 'cfe863c91cc417c8a08a43f08b7dfaf8',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment([]);
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
            'orderid' => '201709120000006972',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'sign' => 'dbe91ea48025cc2a194d9f7b7a4c476a',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment([]);
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
            'orderid' => '201709120000006972',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'sign' => '8dd874bb7968528147cef072eab03ca1',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment([]);
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
            'orderid' => '201709120000006972',
            'opstate' => '-5',
            'ovalue' => '0.01',
            'sign' => '39ce427104d23bc46cc6a0dcd3ba39a4',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment([]);
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

        $sourceData = [
            'orderid' => '201709120000006972',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '719ca9310380e97425429d99ab178a13',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = ['id' => '201606220000002806'];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment($entry);
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

        $sourceData = [
            'orderid' => '201709120000006972',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '719ca9310380e97425429d99ab178a13',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = [
            'id' => '201709120000006972',
            'amount' => '1.0000',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'orderid' => '201709120000006972',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '719ca9310380e97425429d99ab178a13',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = [
            'id' => '201709120000006972',
            'amount' => '0.0100',
        ];

        $dingYi = new DingYi();
        $dingYi->setPrivateKey('1234');
        $dingYi->setOptions($sourceData);
        $dingYi->verifyOrderPayment($entry);

        $this->assertEquals('success', $dingYi->getMsg());
    }
}
