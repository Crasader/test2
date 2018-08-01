<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChrYuPay;

class ChrYuPayTest extends DurianTestCase
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

        $chrYuPay = new ChrYuPay();
        $chrYuPay->getVerifyData();
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

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->getVerifyData();
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
            'number' => '33728',
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '999',
            'orderId' => '201706050000006534',
            'amount' => '0.10',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $chrYuPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '1',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'number' => '33728',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $encodeData = $chrYuPay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['pay_memberid']);
        $this->assertEquals($options['orderId'], $encodeData['pay_orderid']);
        $this->assertEquals($options['amount'], $encodeData['pay_amount']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['pay_applydate']);
        $this->assertEquals('ICBC', $encodeData['pay_bankcode']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_notifyurl']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_callbackurl']);
        $this->assertEquals('ShangYinXinBank', $encodeData['tongdao']);
        $this->assertEquals('FA2F98DFFE4130BF187C8B16D5F66B13', $encodeData['pay_md5sign']);
    }

    /**
     * 測試微信掃碼支付
     */
    public function testWeiXinQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'number' => '33728',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $encodeData = $chrYuPay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['pay_memberid']);
        $this->assertEquals($options['orderId'], $encodeData['pay_orderid']);
        $this->assertEquals($options['amount'], $encodeData['pay_amount']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['pay_applydate']);
        $this->assertEquals('WXZF', $encodeData['pay_bankcode']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_notifyurl']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_callbackurl']);
        $this->assertEquals('ShangYinXinWxSm', $encodeData['tongdao']);
        $this->assertEquals('910FCB0D95CBB32E06E02902A41C977B', $encodeData['pay_md5sign']);
    }

    /**
     * 測試QQ掃碼支付
     */
    public function testQqQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.payment.test/pay/return.php',
            'paymentVendorId' => '1103',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'number' => '33728',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $encodeData = $chrYuPay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['pay_memberid']);
        $this->assertEquals($options['orderId'], $encodeData['pay_orderid']);
        $this->assertEquals($options['amount'], $encodeData['pay_amount']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['pay_applydate']);
        $this->assertEquals('QQZF', $encodeData['pay_bankcode']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_notifyurl']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_callbackurl']);
        $this->assertEquals('ShangYinXinQqQb', $encodeData['tongdao']);
        $this->assertEquals('6675895570ABAFC4711A77AD992AFB33', $encodeData['pay_md5sign']);
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

        $chrYuPay = new ChrYuPay();
        $chrYuPay->verifyOrderPayment([]);
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

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'memberid' => '33728',
            'orderid' => '201706050000006534',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $chrYuPay->verifyOrderPayment([]);
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
            'memberid' => '33728',
            'orderid' => '201706050000006534',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => '123456798798',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $chrYuPay->verifyOrderPayment([]);
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
            'memberid' => '33728',
            'orderid' => '201706050000006534',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '99999',
            'sign' => 'C594605475722FE6D8FAF03F037E0061',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $encodeData = $chrYuPay->verifyOrderPayment([]);
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
            'memberid' => '33728',
            'orderid' => '201706050000006534',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => '2381BF913E701BAE284411633A76231F',
        ];

        $entry = ['id' => '201706050000001234'];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $chrYuPay->verifyOrderPayment($entry);
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
            'memberid' => '33728',
            'orderid' => '201706050000006534',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => '2381BF913E701BAE284411633A76231F',
        ];

        $entry = [
            'id' => '201706050000006534',
            'amount' => '15.00',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $chrYuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'memberid' => '33728',
            'orderid' => '201706050000006534',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => '2381BF913E701BAE284411633A76231F',
        ];

        $entry = [
            'id' => '201706050000006534',
            'amount' => '0.01',
        ];

        $chrYuPay = new ChrYuPay();
        $chrYuPay->setPrivateKey('test');
        $chrYuPay->setOptions($options);
        $chrYuPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $chrYuPay->getMsg());
    }
}
