<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HengTung;

class HengTungTest extends DurianTestCase
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

        $hengTung = new HengTung();
        $hengTung->getVerifyData();
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

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->getVerifyData();
    }

    /**
     * 測試支付加密時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '888936',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201711080000007486',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
        ];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '888936',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201711080000007486',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
        ];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $encodeData = $hengTung->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['customer']);
        $this->assertEquals('967', $encodeData['banktype']);
        $this->assertSame('0.01', $encodeData['amount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['asynbackurl']);
        $this->assertEquals('20171113154000', $encodeData['request_time']);
        $this->assertEquals('', $encodeData['synbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('', $encodeData['israndom']);
        $this->assertEquals('03a1f4cb5ee7aa700cc53c9f73a769c2', $encodeData['sign']);
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $hengTung = new HengTung();
        $hengTung->verifyOrderPayment([]);
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
            'orderid' => '201711080000007486',
            'result' => '1',
        ];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->verifyOrderPayment([]);
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
            'orderid' => '201711080000007486',
            'result' => '1',
            'amount' => '1.99',
            'systemorderid' => 'B171108141806580505646468',
            'completetime' => '20171108141851',
            'notifytime' => '20171108142057',
            'attach' => '',
            'sourceamount' => '2.00',
            'payamount' => '2.00',
        ];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->verifyOrderPayment([]);
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
            'orderid' => '201711080000007486',
            'result' => '1',
            'amount' => '1.99',
            'systemorderid' => 'B171108141806580505646468',
            'completetime' => '20171108141851',
            'notifytime' => '20171108142057',
            'sign' => 'afd992597a77dc704ec695253eddce5f',
            'attach' => '',
            'sourceamount' => '2.00',
            'payamount' => '2.00',
        ];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->verifyOrderPayment([]);
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
            'orderid' => '201711080000007486',
            'result' => '9',
            'amount' => '1.99',
            'systemorderid' => 'B171108141806580505646468',
            'completetime' => '20171108141851',
            'notifytime' => '20171108142057',
            'sign' => '13c70d97bf197c99a8315effbb30e815',
            'attach' => '',
            'sourceamount' => '2.00',
            'payamount' => '2.00',
        ];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201711080000007486',
            'result' => '1',
            'amount' => '1.99',
            'systemorderid' => 'B171108141806580505646468',
            'completetime' => '20171108141851',
            'notifytime' => '20171108142057',
            'sign' => 'ab6897cfc1bb24a1ba515defc124483d',
            'attach' => '',
            'sourceamount' => '2.00',
            'payamount' => '2.00',
        ];

        $entry = ['id' => '201606220000002806'];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201711080000007486',
            'result' => '1',
            'amount' => '1.99',
            'systemorderid' => 'B171108141806580505646468',
            'completetime' => '20171108141851',
            'notifytime' => '20171108142057',
            'sign' => 'ab6897cfc1bb24a1ba515defc124483d',
            'attach' => '',
            'sourceamount' => '2.00',
            'payamount' => '2.00',
        ];

        $entry = [
            'id' => '201711080000007486',
            'amount' => '1.0000',
        ];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'orderid' => '201711080000007486',
            'result' => '1',
            'amount' => '1.99',
            'systemorderid' => 'B171108141806580505646468',
            'completetime' => '20171108141851',
            'notifytime' => '20171108142057',
            'sign' => 'ab6897cfc1bb24a1ba515defc124483d',
            'attach' => '',
            'sourceamount' => '2.00',
            'payamount' => '2.00',
        ];

        $entry = [
            'id' => '201711080000007486',
            'amount' => '2.00',
        ];

        $hengTung = new HengTung();
        $hengTung->setPrivateKey('1234');
        $hengTung->setOptions($sourceData);
        $hengTung->verifyOrderPayment($entry);

        $this->assertEquals('success', $hengTung->getMsg());
    }
}
