<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhiFuJia;

class ZhiFuJiaTest extends DurianTestCase
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

        $zhifujia = new ZhiFuJia();
        $zhifujia->getVerifyData();
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

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->getVerifyData();
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
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201706050000006534',
            'amount' => '0.10',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->setOptions($options);
        $zhifujia->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'number' => '33728',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->setOptions($options);
        $encodeData = $zhifujia->getVerifyData();

        $this->assertEquals('33728', $encodeData['pay_memberid']);
        $this->assertEquals('201608160000003698', $encodeData['pay_orderid']);
        $this->assertEquals('1.00', $encodeData['pay_amount']);
        $this->assertEquals('2017-06-06 10:06:06', $encodeData['pay_applydate']);
        $this->assertEquals('908', $encodeData['pay_bankcode']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['pay_notifyurl']);
        $this->assertEquals('8A57FFAA861C4ADE7C73791777543683', $encodeData['pay_md5sign']);
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

        $zhifujia = new ZhiFuJia();
        $zhifujia->verifyOrderPayment([]);
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

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->verifyOrderPayment([]);
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
            'returncode' => '00000',
        ];

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->setOptions($options);
        $zhifujia->verifyOrderPayment([]);
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
            'transaction_id' => '201706050000006534',
            'sign' => '123456798798',
        ];

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->setOptions($options);
        $zhifujia->verifyOrderPayment([]);
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
            'transaction_id' => '201706050000006534',
            'sign' => '43C7BFA963523F2FFBDA02E9F7B83E85',
        ];

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->setOptions($options);
        $zhifujia->verifyOrderPayment([]);
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
            'transaction_id' => '201706050000006534',
            'sign' => '0B6F49BB7AE01924C0E62026B15CC2AD',
        ];

        $entry = [
            'id' => '201706050000001234',
        ];

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->setOptions($options);
        $zhifujia->verifyOrderPayment($entry);
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
            'transaction_id' => '201706050000006534',
            'sign' => '0B6F49BB7AE01924C0E62026B15CC2AD',
        ];

        $entry = [
            'id' => '201706050000006534',
            'amount' => '15.00',
        ];

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->setOptions($options);
        $zhifujia->verifyOrderPayment($entry);
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
            'transaction_id' => '201706050000006534',
            'sign' => '0B6F49BB7AE01924C0E62026B15CC2AD',
        ];

        $entry = [
            'id' => '201706050000006534',
            'amount' => '0.01',
        ];

        $zhifujia = new ZhiFuJia();
        $zhifujia->setPrivateKey('test');
        $zhifujia->setOptions($options);
        $zhifujia->verifyOrderPayment($entry);

        $this->assertEquals('OK', $zhifujia->getMsg());
    }
}
