<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\UNPay;

class UNPayTest extends DurianTestCase
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

        $unPay = new UNPay();
        $unPay->getVerifyData();
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

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->getVerifyData();
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
            'number' => '2119',
            'paymentVendorId' => '9999',
            'amount' => '2.00',
            'orderId' => '201803010000010128',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '111.235.135.54',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '2119',
            'paymentVendorId' => '1',
            'amount' => '2.00',
            'orderId' => '201803010000010128',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '111.235.135.54',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $encodeData = $unPay->getVerifyData();

        $this->assertEquals('2119', $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertEquals('102', $encodeData['tyid']);
        $this->assertSame('2.00', $encodeData['value']);
        $this->assertEquals('201803010000010128', $encodeData['orderid']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('111.235.135.54', $encodeData['payerIp']);
        $this->assertEquals('8a3484fd303db03b23a4eb1897fa6c34', $encodeData['sign']);
    }

    /**
     * 測試非網銀支付
     */
    public function testUnBankPay()
    {
        $sourceData = [
            'number' => '2119',
            'paymentVendorId' => '1088',
            'amount' => '2.00',
            'orderId' => '201803010000010128',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '111.235.135.54',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $encodeData = $unPay->getVerifyData();

        $this->assertEquals('2119', $encodeData['parter']);
        $this->assertEquals('1005', $encodeData['type']);
        $this->assertEquals('1020', $encodeData['tyid']);
        $this->assertSame('2.00', $encodeData['value']);
        $this->assertEquals('201803010000010128', $encodeData['orderid']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('111.235.135.54', $encodeData['payerIp']);
        $this->assertEquals('1e76ee49b91ac8812ad9cb84ebd84f2b', $encodeData['sign']);
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

        $unPay = new UNPay();
        $unPay->verifyOrderPayment([]);
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

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->verifyOrderPayment([]);
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
            'orderid' => '201803010000010128',
            'opstate' => '0',
            'ovalue' => '0.10',
            'sysorderid' => '18030110311722020980',
            'systime' => '2018/03/01 10:32:31',
            'attach' => '',
            'msg' => '1',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->verifyOrderPayment([]);
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
            'orderid' => '201803010000010128',
            'opstate' => '0',
            'ovalue' => '0.10',
            'sysorderid' => '18030110311722020980',
            'systime' => '2018/03/01 10:32:31',
            'attach' => '',
            'msg' => '1',
            'sign' => '8b27dea7edf10f440ad4852b771fd4fb',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->verifyOrderPayment([]);
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
            'orderid' => '201803010000010128',
            'opstate' => '-1',
            'ovalue' => '2',
            'sysorderid' => '18030110311722020980',
            'systime' => '2018/03/01 10:32:31',
            'attach' => '',
            'msg' => '1',
            'sign' => '0a3ceaa70648e9cf1910027dadd912d1',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->verifyOrderPayment([]);
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
            'orderid' => '201803010000010128',
            'opstate' => '-2',
            'ovalue' => '2',
            'sysorderid' => '18030110311722020980',
            'systime' => '2018/03/01 10:32:31',
            'attach' => '',
            'msg' => '1',
            'sign' => '6c03ca8e797e63ae949fec04eca345f1',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->verifyOrderPayment([]);
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
            'orderid' => '201803010000010128',
            'opstate' => '99',
            'ovalue' => '2',
            'sysorderid' => '18030110311722020980',
            'systime' => '2018/03/01 10:32:31',
            'attach' => '',
            'msg' => '1',
            'sign' => '22690e654385123e01184da4589b7058',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->verifyOrderPayment([]);
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
            'orderid' => '201803010000010128',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => '18030110311722020980',
            'systime' => '2018/03/01 10:32:31',
            'attach' => '',
            'msg' => '1',
            'sign' => 'cbe40e685d7a2c3b43436df7fc151b8b',
        ];

        $entry = ['id' => '201702090000001337'];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->verifyOrderPayment($entry);
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
            'orderid' => '201803010000010128',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => '18030110311722020980',
            'systime' => '2018/03/01 10:32:31',
            'attach' => '',
            'msg' => '1',
            'sign' => 'cbe40e685d7a2c3b43436df7fc151b8b',
        ];

        $entry = [
            'id' => '201803010000010128',
            'amount' => '0.01',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201803010000010128',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => '18030110311722020980',
            'systime' => '2018/03/01 10:32:31',
            'attach' => '',
            'msg' => '1',
            'sign' => 'cbe40e685d7a2c3b43436df7fc151b8b',
        ];

        $entry = [
            'id' => '201803010000010128',
            'amount' => '2',
        ];

        $unPay = new UNPay();
        $unPay->setPrivateKey('test');
        $unPay->setOptions($sourceData);
        $unPay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $unPay->getMsg());
    }
}
