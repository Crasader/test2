<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SinFu;

class SinFuTest extends DurianTestCase
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

        $sinFu = new SinFu();
        $sinFu->getVerifyData();
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

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->getVerifyData();
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
            'number' => '1625',
            'paymentVendorId' => '7',
            'amount' => '2.00',
            'orderId' => '201709050000004011',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1625',
            'paymentVendorId' => '1090',
            'amount' => '2.00',
            'orderId' => '201709050000004011',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $encodeData = $sinFu->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('1004', $encodeData['type']);
        $this->assertSame('2.00', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('a143e0a360cf69dc8500375ea588d626', $encodeData['sign']);
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

        $sinFu = new SinFu();
        $sinFu->verifyOrderPayment([]);
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

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->verifyOrderPayment([]);
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
            'orderid' => '201709050000004011',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1625201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->verifyOrderPayment([]);
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
            'orderid' => '201709050000004011',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1625201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '8b27dea7edf10f440ad4852b771fd4fb',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->verifyOrderPayment([]);
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
            'orderid' => '201709050000004011',
            'opstate' => '-1',
            'ovalue' => '2',
            'sysorderid' => 'B1625201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => 'a442d2aac43700733e314aafca4f93ca',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->verifyOrderPayment([]);
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
            'orderid' => '201709050000004011',
            'opstate' => '-2',
            'ovalue' => '2',
            'sysorderid' => 'B1625201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '1fc7754cb234f824517130eb55a9d8d4',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->verifyOrderPayment([]);
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
            'orderid' => '201709050000004011',
            'opstate' => '99',
            'ovalue' => '2',
            'sysorderid' => 'B1625201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '9b3b962be7e0df19dc09d97530a1d60c',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->verifyOrderPayment([]);
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
            'orderid' => '201709050000004011',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1625201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '368159781d1c854d510d58d0e34b1b82',
        ];

        $entry = ['id' => '201702090000001337'];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->verifyOrderPayment($entry);
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
            'orderid' => '201709050000004011',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1625201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '368159781d1c854d510d58d0e34b1b82',
        ];

        $entry = [
            'id' => '201709050000004011',
            'amount' => '0.01',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201709050000004011',
            'opstate' => '0',
            'ovalue' => '2',
            'sysorderid' => 'B1625201702091339453704949',
            'systime' => '2017/02/09 13:40:06',
            'attach' => '',
            'msg' => '1',
            'sign' => '368159781d1c854d510d58d0e34b1b82',
        ];

        $entry = [
            'id' => '201709050000004011',
            'amount' => '2',
        ];

        $sinFu = new SinFu();
        $sinFu->setPrivateKey('test');
        $sinFu->setOptions($sourceData);
        $sinFu->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $sinFu->getMsg());
    }
}
